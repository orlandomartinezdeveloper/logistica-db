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
    header("Location: locais_register.php?error=db_connection");
    exit();
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$tipo       = trim($_POST['tipo'] ?? '');
$tipo       = ($tipo === 'externo') ? 'externo' : 'loja';
$name       = trim($_POST['name'] ?? '');
$categoria  = trim($_POST['categoria'] ?? '');
$address    = trim($_POST['address'] ?? '');
$city       = trim($_POST['city'] ?? '');
$state      = strtoupper(trim($_POST['state'] ?? ''));
$mapsUrl    = trim($_POST['maps_url'] ?? '');
$latTxt     = str_replace(',', '.', trim($_POST['latitude'] ?? ''));
$lngTxt     = str_replace(',', '.', trim($_POST['longitude'] ?? ''));
$latitude   = ($latTxt !== '') ? (float)$latTxt : null;
$longitude  = ($lngTxt !== '') ? (float)$lngTxt : null;

/*
|--------------------------------------------------------------------------
| GUARDAR DADOS PARA REPOPULAR O FORMULÁRIO EM CASO DE ERRO
|--------------------------------------------------------------------------
*/

$_SESSION['old_input_locais'] = [
    'tipo'      => $tipo,
    'categoria' => $categoria,
    'name'      => $name,
    'address'   => $address,
    'city'      => $city,
    'state'     => $state,
    'latitude'  => $latTxt,
    'longitude' => $lngTxt,
    'maps_url'  => $mapsUrl,
];

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (empty($name)) {
    header("Location: locais_register.php?tipo=$tipo&error=name_required");
    exit();
}

if ($tipo === 'externo') {
    $allowedCategories = ['Cliente', 'Franqueado', 'Revendedor', 'Centro de Distribuição', 'Fornecedor', 'Outro'];
    if ($categoria === '' || !in_array($categoria, $allowedCategories)) {
        header("Location: locais_register.php?tipo=$tipo&error=category_invalid");
        exit();
    }
}

if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
    header("Location: locais_register.php?tipo=$tipo&error=lat_invalid");
    exit();
}

if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
    header("Location: locais_register.php?tipo=$tipo&error=lng_invalid");
    exit();
}

if ($mapsUrl !== '' && filter_var($mapsUrl, FILTER_VALIDATE_URL) === false) {
    header("Location: locais_register.php?tipo=$tipo&error=maps_invalid");
    exit();
}

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$image_url = "";
$uploadSubdir = ($tipo === 'externo') ? 'destinations' : 'stores';
$prefix = ($tipo === 'externo') ? 'destino_' : 'loja_';

$maxSize = 5 * 1024 * 1024;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: locais_register.php?tipo=$tipo&error=upload_error");
        exit();
    }

    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: locais_register.php?tipo=$tipo&error=image_too_large");
        exit();
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: locais_register.php?tipo=$tipo&error=invalid_image");
        exit();
    }

    $allowedMime = ["image/jpeg", "image/png", "image/webp"];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: locais_register.php?tipo=$tipo&error=invalid_image");
        exit();
    }

    $uploadDir = __DIR__ . "/../../img/" . $uploadSubdir . "/";
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Falha ao criar diretorio: $uploadDir");
            header("Location: locais_register.php?tipo=$tipo&error=upload_error");
            exit();
        }
    }

    if (!is_writable($uploadDir)) {
        error_log("Diretorio sem permissao de escrita: $uploadDir");
        header("Location: locais_register.php?tipo=$tipo&error=upload_error");
        exit();
    }

    $newName = uniqid($prefix, true) . ".jpg";
    $destination = $uploadDir . $newName;

    $gdAvailable = function_exists('imagecreatefromjpeg') && function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

    if ($gdAvailable) {
        $targetWidth = 1000;
        $targetHeight = 1000;
        $jpegQuality = 75;

        $sourceImage = null;

        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($_FILES['photo']['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($_FILES['photo']['tmp_name']);
                }
                break;
        }

        if (!$sourceImage) {
            $saved = move_uploaded_file($_FILES['photo']['tmp_name'], $destination);
        } else {
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            $origRatio = $origWidth / $origHeight;

            if ($origWidth > $origHeight) {
                $targetWidth = 1000;
                $targetHeight = (int)round($targetWidth / $origRatio);
            } else {
                $targetHeight = 1000;
                $targetWidth = (int)round($targetHeight * $origRatio);
            }

            $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($imageInfo['mime'] === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            } else {
                $white = imagecolorallocate($resizedImage, 255, 255, 255);
                imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $white);
            }

            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $targetWidth, $targetHeight,
                $origWidth, $origHeight
            );

            $saved = imagejpeg($resizedImage, $destination, $jpegQuality);

            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
        }
    } else {
        $saved = move_uploaded_file($_FILES['photo']['tmp_name'], $destination);
    }

    if (!$saved) {
        error_log("Falha ao salvar imagem: $destination");
        header("Location: locais_register.php?tipo=$tipo&error=upload_error");
        exit();
    }

    $image_url = "img/" . $uploadSubdir . "/" . $newName;
    error_log("Foto do local salva: $image_url");
}

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

if ($tipo === 'externo') {
    $stmt = $conn->prepare("
        INSERT INTO destinations (
            name, address, city, state, latitude, longitude, type, maps_url, image_url, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("Erro ao preparar INSERT destinations: " . $conn->error);
        header("Location: locais_register.php?tipo=$tipo&error=server_error");
        exit();
    }

    $stmt->bind_param(
        "ssssddsss",
        $name, $address, $city, $state, $latitude, $longitude,
        $categoria, $mapsUrl, $image_url
    );
} else {
    $stmt = $conn->prepare("
        INSERT INTO stores (
            name, address, city, state, latitude, longitude, maps_url, image_url, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("Erro ao preparar INSERT stores: " . $conn->error);
        header("Location: locais_register.php?tipo=$tipo&error=server_error");
        exit();
    }

    $stmt->bind_param(
        "ssssddss",
        $name, $address, $city, $state, $latitude, $longitude,
        $mapsUrl, $image_url
    );
}

if ($stmt->execute()) {
    unset($_SESSION['old_input_locais']);
    $stmt->close();
    $conn->close();
    header("Location: locais_consult.php?tipo=$tipo&success=registered");
    exit();
} else {
    error_log("Erro ao inserir local: " . $stmt->error);
    error_log("Dados: tipo=$tipo, name=$name, lat=$latTxt, lng=$lngTxt, photo=" . ($image_url ?: 'vazio'));
    header("Location: locais_register.php?tipo=$tipo&error=server_error");
    exit();
}
?>