<?php
session_start();

/*
|--------------------------------------------------------------------------
| VERIFICAR SESSÃO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| CONEXÃO COM O BANCO DE DADOS
|--------------------------------------------------------------------------
*/

define('ACCESS_ALLOWED', true);
require __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    header("Location: users_editar.php?error=db_connection");
    exit();
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$userId   = (int)($_POST['user_id'] ?? 0);
$name     = trim($_POST['name'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$birthDay = trim($_POST['birth_day'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$cnh      = trim($_POST['cnh'] ?? '');
$role     = trim($_POST['role'] ?? '');
$status   = trim($_POST['status'] ?? 'ativo');
$password = $_POST['password'] ?? '';

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {
    header("Location: users_editar.php?error=user_not_found");
    exit();
}

if (empty($name)) {
    header("Location: users_editar.php?id=$userId&error=name_required");
    exit();
}

if (empty($lastname)) {
    header("Location: users_editar.php?id=$userId&error=lastname_required");
    exit();
}

if (empty($email)) {
    header("Location: users_editar.php?id=$userId&error=email_required");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: users_editar.php?id=$userId&error=invalid_email");
    exit();
}

if (empty($birthDay)) {
    header("Location: users_editar.php?id=$userId&error=birth_day_required");
    exit();
}

if (empty($phone)) {
    header("Location: users_editar.php?id=$userId&error=phone_required");
    exit();
}

if (empty($address)) {
    header("Location: users_editar.php?id=$userId&error=address_required");
    exit();
}

if (empty($cnh)) {
    header("Location: users_editar.php?id=$userId&error=cnh_required");
    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR E-MAIL (excluindo o próprio usuário)
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $userId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header("Location: users_editar.php?id=$userId&error=email_exists");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$photo_url = null; // null = manter a foto atual

$maxSize = 5 * 1024 * 1024; // 5 MB

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: users_editar.php?id=$userId&error=upload_error");
        exit();
    }

    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: users_editar.php?id=$userId&error=image_too_large");
        exit();
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: users_editar.php?id=$userId&error=invalid_image");
        exit();
    }

    $allowedMime = ["image/jpeg", "image/png", "image/webp"];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: users_editar.php?id=$userId&error=invalid_image");
        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | REDIMENSIONAR E COMPRESSÃO DE IMAGEM
    |--------------------------------------------------------------------------
    */

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
            header("Location: users_editar.php?id=$userId&error=invalid_image");
            exit();
    }

    if (!$sourceImage) {
        header("Location: users_editar.php?id=$userId&error=upload_error");
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

    $uploadDir = __DIR__ . "/../img/users/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid("user_", true) . ".jpg";
    $destination = $uploadDir . $newName;
    $saved = imagejpeg($resizedImage, $destination, $jpegQuality);

    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$saved) {
        header("Location: users_editar.php?id=$userId&error=upload_error");
        exit();
    }

    // Remover foto antiga (se existir e for local)
    $oldPhoto = $conn->prepare("SELECT photo_url FROM users WHERE id = ?");
    $oldPhoto->bind_param("i", $userId);
    $oldPhoto->execute();
    $oldResult = $oldPhoto->get_result();
    $oldData = $oldResult->fetch_assoc();
    $oldPhoto->close();

    if (!empty($oldData['photo_url']) && strpos($oldData['photo_url'], 'img/users/') === 0) {
        $oldPath = __DIR__ . "/../" . $oldData['photo_url'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $photo_url = "img/users/" . $newName;
}

/*
|--------------------------------------------------------------------------
| ATUALIZAR USUÁRIO
|--------------------------------------------------------------------------
*/

if ($photo_url !== null) {
    // Com foto nova
    if (!empty($password)) {
        // Com senha nova
        if (strlen($password) < 6) {
            header("Location: users_editar.php?id=$userId&error=password_short");
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users SET
                name = ?, lastname = ?, email = ?, birth_day = ?,
                phone = ?, address = ?, cnh = ?, role = ?,
                status = ?, photo_url = ?, password_hash = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssssssi",
            $name, $lastname, $email, $birthDay,
            $phone, $address, $cnh, $role,
            $status, $photo_url, $password_hash, $userId
        );
    } else {
        // Sem senha nova
        $stmt = $conn->prepare("
            UPDATE users SET
                name = ?, lastname = ?, email = ?, birth_day = ?,
                phone = ?, address = ?, cnh = ?, role = ?,
                status = ?, photo_url = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssssssssi",
            $name, $lastname, $email, $birthDay,
            $phone, $address, $cnh, $role,
            $status, $photo_url, $userId
        );
    }
} else {
    // Sem foto nova
    if (!empty($password)) {
        // Com senha nova
        if (strlen($password) < 6) {
            header("Location: users_editar.php?id=$userId&error=password_short");
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users SET
                name = ?, lastname = ?, email = ?, birth_day = ?,
                phone = ?, address = ?, cnh = ?, role = ?,
                status = ?, password_hash = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssssssssi",
            $name, $lastname, $email, $birthDay,
            $phone, $address, $cnh, $role,
            $status, $password_hash, $userId
        );
    } else {
        // Sem senha nova
        $stmt = $conn->prepare("
            UPDATE users SET
                name = ?, lastname = ?, email = ?, birth_day = ?,
                phone = ?, address = ?, cnh = ?, role = ?,
                status = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssssi",
            $name, $lastname, $email, $birthDay,
            $phone, $address, $cnh, $role,
            $status, $userId
        );
    }
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: users_editar.php?id=$userId&success=1");
    exit();
} else {
    error_log("Erro ao atualizar usuário: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: users_editar.php?id=$userId&error=server_error");
    exit();
}
?>
