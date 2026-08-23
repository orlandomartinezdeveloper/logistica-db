<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

define('ACCESS_ALLOWED', true);
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    header("Location: register.php?error=db_connection");
    exit();
}

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$name       = trim($_POST['name'] ?? '');
$lastname   = trim($_POST['lastname'] ?? '');
$username   = strtolower(trim($_POST['username'] ?? ''));
$email      = trim($_POST['email'] ?? '');
$birth_day  = trim($_POST['birth_day'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$cep        = trim($_POST['cep'] ?? '');
$address    = trim($_POST['address'] ?? '');
$cnh        = trim($_POST['cnh'] ?? '');

$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$role       = $_POST['role'] ?? 'user';

/*
|--------------------------------------------------------------------------
| GUARDAR DADOS PARA REPOPULAR O FORMULÁRIO EM CASO DE ERRO
| (nunca guardamos password/confirm_password por segurança)
|--------------------------------------------------------------------------
*/

$_SESSION['old_input'] = [
    'name'      => $name,
    'lastname'  => $lastname,
    'username'  => $username,
    'email'     => $email,
    'birth_day' => $birth_day,
    'phone'     => $phone,
    'cep'       => $cep,
    'address'   => $address,
    'cnh'       => $cnh,
    'role'      => $role,
];

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (empty($name)) {
    header("Location: register.php?error=name_required");
    exit();
}

if (empty($username)) {
    header("Location: register.php?error=username_required");
    exit();
}

if (!preg_match('/^[a-z0-9._-]+$/', $username)) {
    header("Location: register.php?error=username_invalid");
    exit();
}

if (empty($lastname)) {
    header("Location: register.php?error=lastname_required");
    exit();
}

if (empty($email)) {
    header("Location: register.php?error=email_required");
    exit();
}

if (empty($birth_day)) {
    header("Location: register.php?error=birth_day_required");
    exit();
}

if (empty($phone)) {
    header("Location: register.php?error=phone_required");
    exit();
}

if (empty($address)) {
    header("Location: register.php?error=address_required");
    exit();
}

if (empty($cnh)) {
    header("Location: register.php?error=cnh_required");
    exit();
}

if (empty($password)) {
    header("Location: register.php?error=password_required");
    exit();
}

if ($password !== $confirm) {
    header("Location: register.php?error=password_mismatch");
    exit();
}

if (strlen($password) < 6) {
    header("Location: register.php?error=password_short");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=invalid_email");
    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR EMAIL
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id FROM users WHERE email = ?");

$check->bind_param("s", $email);

$check->execute();

$check->store_result();

if ($check->num_rows > 0) {

    $check->close();

    header("Location: register.php?error=email_exists");

    exit();

}

$check->close();

/*
|--------------------------------------------------------------------------
| VERIFICAR USERNAME
|--------------------------------------------------------------------------
*/

$checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
$checkUser->bind_param("s", $username);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows > 0) {
    $checkUser->close();
    header("Location: register.php?error=username_exists");
    exit();
}
$checkUser->close();

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$photo_url = "";

// 5 MB
$maxSize = 5 * 1024 * 1024;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    // Verificar erro de upload
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: register.php?error=upload_error");
        exit();
    }

    // Verificar tamanho
    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: register.php?error=image_too_large");
        exit();
    }

    // Verificar se realmente é uma imagem
    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: register.php?error=invalid_image");
        exit();
    }

    // Extensão
    $extension = strtolower(
        pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)
    );

    // Extensões permitidas
    $allowedExtensions = [
        "jpg",
        "jpeg",
        "png",
        "webp"
    ];

    if (!in_array($extension, $allowedExtensions)) {
        header("Location: register.php?error=invalid_image");
        exit();
    }

    // Mime Types permitidos
    $allowedMime = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: register.php?error=invalid_image");
        exit();
    }

    // Pasta onde serão armazenadas as fotos
    $uploadDir = __DIR__ . "/../img/users/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Gerar nome único
    $newName = uniqid("user_", true) . ".jpg";

    $destination = $uploadDir . $newName;

    /*
    |--------------------------------------------------------------------------
    | REDIMENSIONAR E COMPRESSOR DE IMAGEM
    |--------------------------------------------------------------------------
    | Redimensiona para 1000x1000, converte para JPG e comprime
    | para reduzir o tamanho do arquivo.
    */

    $targetWidth = 1000;
    $targetHeight = 1000;
    $jpegQuality = 75;

    // Criar imagem a partir do arquivo enviado
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
            header("Location: register.php?error=invalid_image");
            exit();
    }

    if (!$sourceImage) {
        header("Location: register.php?error=upload_error");
        exit();
    }

    // Criar nova imagem redimensionada
    $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

    // Preservar transparência para PNG
    if ($imageInfo['mime'] === 'image/png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    // Redimensionar mantendo proporção (crop centrado)
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

    // Salvar como JPG comprimido
    $saved = imagejpeg($resizedImage, $destination, $jpegQuality);

    // Liberar memória
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$saved) {
        header("Location: register.php?error=upload_error");
        exit();
    }

    // Caminho que será salvo no banco
    $photo_url = "img/users/" . $newName;
}

/*
|--------------------------------------------------------------------------
| HASH DA SENHA
|--------------------------------------------------------------------------
*/

$password_hash = password_hash($password, PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    INSERT INTO users (
        name,
        lastname,
        username,
        photo_url,
        email,
        birth_day,
        phone,
        cep,
        address,
        cnh,
        password_hash,
        role,
        created_at
    )
    VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
    )
");

if (!$stmt) {
    error_log("Erro ao preparar INSERT: " . $conn->error);
    header("Location: register.php?error=server_error");
    exit();
}

$stmt->bind_param(
    "ssssssssssss",
    $name,
    $lastname,
    $username,
    $photo_url,
    $email,
    $birth_day,
    $phone,
    $cep,
    $address,
    $cnh,
    $password_hash,
    $role
);

if ($stmt->execute()) {

    // Sucesso: já não precisamos dos dados antigos
    unset($_SESSION['old_input']);

    $stmt->close();
    $conn->close();

    header("Location: users/users_consult.php?success=registered");
    exit();

} else {

    error_log("Erro ao inserir usuário: " . $stmt->error);
    header("Location: register.php?error=server_error");
    exit();

}
