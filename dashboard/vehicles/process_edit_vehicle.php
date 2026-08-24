<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

define('ACCESS_ALLOWED', true);
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    header("Location: vehicles_edit.php?error=db_connection");
    exit();
}

$conn->set_charset(DB_CHARSET);

$vehicleId   = (int)($_POST['vehicle_id'] ?? 0);
$plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
$model       = trim($_POST['model'] ?? '');
$currentKm   = (int)($_POST['current_km'] ?? 0);
$status      = trim($_POST['status'] ?? 'ativo');

if ($vehicleId <= 0) {
    header("Location: vehicles_edit.php?error=vehicle_not_found");
    exit();
}

if (empty($plateNumber)) {
    header("Location: vehicles_edit.php?id=$vehicleId&error=plate_required");
    exit();
}

if (!preg_match('/^[A-Z]{3}-?[0-9][A-Z0-9][0-9]{2}$/', $plateNumber)) {
    header("Location: vehicles_edit.php?id=$vehicleId&error=plate_invalid");
    exit();
}

if (empty($model)) {
    header("Location: vehicles_edit.php?id=$vehicleId&error=model_required");
    exit();
}

if ($currentKm < 0) {
    header("Location: vehicles_edit.php?id=$vehicleId&error=km_invalid");
    exit();
}

if (!in_array($status, ['ativo', 'desligado'])) {
    $status = 'ativo';
}

/*
|--------------------------------------------------------------------------
| VERIFICAR PLACA (excluindo o próprio veículo)
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ? AND id != ?");
$check->bind_param("si", $plateNumber, $vehicleId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header("Location: vehicles_edit.php?id=$vehicleId&error=plate_exists");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$photo_url = null;

$maxSize = 5 * 1024 * 1024;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=upload_error");
        exit();
    }

    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=image_too_large");
        exit();
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=invalid_image");
        exit();
    }

    $allowedMime = ["image/jpeg", "image/png", "image/webp"];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=invalid_image");
        exit();
    }

    $targetWidth = 1000;
    $targetHeight = 1000;
    $jpegQuality = 75;

    switch ($imageInfo['mime']) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($_FILES['photo']['tmp_name']);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($_FILES['photo']['tmp_name']);
            break;
        default:
            header("Location: vehicles_edit.php?id=$vehicleId&error=invalid_image");
            exit();
    }

    if (!$sourceImage) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=upload_error");
        exit();
    }

    $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

    $origWidth = imagesx($sourceImage);
    $origHeight = imagesy($sourceImage);
    $ratioOrig = $origWidth / $origHeight;
    $ratioTarget = $targetWidth / $targetHeight;

    if ($ratioOrig > $ratioTarget) {
        $newHeight = $origHeight;
        $newWidth = (int)($origHeight * $ratioTarget);
        $srcX = (int)(($origWidth - $newWidth) / 2);
        $srcY = 0;
    } else {
        $newWidth = $origWidth;
        $newHeight = (int)($origWidth / $ratioTarget);
        $srcX = 0;
        $srcY = (int)(($origHeight - $newHeight) / 2);
    }

    imagecopyresampled(
        $resizedImage, $sourceImage,
        0, 0, $srcX, $srcY,
        $targetWidth, $targetHeight,
        $newWidth, $newHeight
    );

    $uploadDir = __DIR__ . "/../../img/vehicles/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid("vehicle_", true) . ".jpg";
    $destination = $uploadDir . $newName;
    $saved = imagejpeg($resizedImage, $destination, $jpegQuality);

    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$saved) {
        header("Location: vehicles_edit.php?id=$vehicleId&error=upload_error");
        exit();
    }

    // Remover foto antiga
    $oldPhoto = $conn->prepare("SELECT photo_url FROM vehicles WHERE id = ?");
    $oldPhoto->bind_param("i", $vehicleId);
    $oldPhoto->execute();
    $oldResult = $oldPhoto->get_result();
    $oldData = $oldResult->fetch_assoc();
    $oldPhoto->close();

    if (!empty($oldData['photo_url']) && strpos($oldData['photo_url'], 'img/vehicles/') === 0) {
        $oldPath = __DIR__ . "/../../" . $oldData['photo_url'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $photo_url = "img/vehicles/" . $newName;
}

/*
|--------------------------------------------------------------------------
| ATUALIZAR VEÍCULO
|--------------------------------------------------------------------------
*/

if ($photo_url !== null) {
    $stmt = $conn->prepare("
        UPDATE vehicles SET
            plate_number = ?, model = ?, current_km = ?,
            status = ?, photo_url = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssisSi", $plateNumber, $model, $currentKm, $status, $photo_url, $vehicleId);
} else {
    $stmt = $conn->prepare("
        UPDATE vehicles SET
            plate_number = ?, model = ?, current_km = ?,
            status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssisi", $plateNumber, $model, $currentKm, $status, $vehicleId);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: vehicles_consult.php?success=updated");
    exit();
} else {
    error_log("Erro ao atualizar veículo: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: vehicles_edit.php?id=$vehicleId&error=server_error");
    exit();
}
?>
