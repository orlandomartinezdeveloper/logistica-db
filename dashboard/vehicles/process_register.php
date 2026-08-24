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
    header("Location: vehicles_register.php?error=db_connection");
    exit();
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
$model       = trim($_POST['model'] ?? '');
$currentKm   = (int)($_POST['current_km'] ?? 0);

/*
|--------------------------------------------------------------------------
| GUARDAR DADOS PARA REPOPULAR O FORMULÁRIO EM CASO DE ERRO
|--------------------------------------------------------------------------
*/

$_SESSION['old_input_vehicles'] = [
    'plate_number' => $plateNumber,
    'model'        => $model,
    'current_km'   => $currentKm,
];

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (empty($plateNumber)) {
    header("Location: vehicles_register.php?error=plate_required");
    exit();
}

if (!preg_match('/^[A-Z]{3}-?[0-9][A-Z0-9][0-9]{2}$/', $plateNumber)) {
    header("Location: vehicles_register.php?error=plate_invalid");
    exit();
}

if (empty($model)) {
    header("Location: vehicles_register.php?error=model_required");
    exit();
}

if ($currentKm < 0) {
    header("Location: vehicles_register.php?error=km_invalid");
    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR PLACA (única)
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
$check->bind_param("s", $plateNumber);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header("Location: vehicles_register.php?error=plate_exists");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$photo_url = "";

$maxSize = 5 * 1024 * 1024;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: vehicles_register.php?error=upload_error");
        exit();
    }

    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: vehicles_register.php?error=image_too_large");
        exit();
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: vehicles_register.php?error=invalid_image");
        exit();
    }

    $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $allowedExtensions)) {
        header("Location: vehicles_register.php?error=invalid_image");
        exit();
    }

    $allowedMime = ["image/jpeg", "image/png", "image/webp"];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: vehicles_register.php?error=invalid_image");
        exit();
    }

    $uploadDir = __DIR__ . "/../../img/vehicles/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid("vehicle_", true) . ".jpg";
    $destination = $uploadDir . $newName;

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
            header("Location: vehicles_register.php?error=invalid_image");
            exit();
    }

    if (!$sourceImage) {
        header("Location: vehicles_register.php?error=upload_error");
        exit();
    }

    $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($imageInfo['mime'] === 'image/png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

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

    $saved = imagejpeg($resizedImage, $destination, $jpegQuality);

    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$saved) {
        header("Location: vehicles_register.php?error=upload_error");
        exit();
    }

    $photo_url = "img/vehicles/" . $newName;
}

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO vehicles (plate_number, model, photo_url, current_km, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

if (!$stmt) {
    error_log("Erro ao preparar INSERT: " . $conn->error);
    header("Location: vehicles_register.php?error=server_error");
    exit();
}

$stmt->bind_param("sssi", $plateNumber, $model, $photo_url, $currentKm);

if ($stmt->execute()) {
    unset($_SESSION['old_input_vehicles']);
    $stmt->close();
    $conn->close();
    header("Location: vehicles_consult.php?success=registered");
    exit();
} else {
    error_log("Erro ao inserir veículo: " . $stmt->error);
    header("Location: vehicles_register.php?error=server_error");
    exit();
}
?>
