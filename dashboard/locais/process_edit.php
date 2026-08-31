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
    header("Location: locais_edit.php?error=db_connection");
    exit();
}

$conn->set_charset(DB_CHARSET);

$localId    = (int)($_POST['local_id'] ?? 0);
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

if ($localId <= 0) {
    header("Location: locais_consult.php?tipo=$tipo&error=local_not_found");
    exit();
}

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (empty($name)) {
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=name_required");
    exit();
}

if ($tipo === 'externo') {
    $allowedCategories = ['Cliente', 'Franqueado', 'Revendedor', 'Centro de Distribuição', 'Fornecedor', 'Outro'];
    if ($categoria === '' || !in_array($categoria, $allowedCategories)) {
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=category_invalid");
        exit();
    }
}

if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=lat_invalid");
    exit();
}

if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=lng_invalid");
    exit();
}

if ($mapsUrl !== '' && filter_var($mapsUrl, FILTER_VALIDATE_URL) === false) {
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=maps_invalid");
    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SE O LOCAL EXISTE
|--------------------------------------------------------------------------
*/

$tabela = ($tipo === 'externo') ? 'destinations' : 'stores';

$check = $conn->prepare("SELECT id FROM $tabela WHERE id = ?");
if (!$check) {
    error_log("Erro ao preparar consulta: " . $conn->error);
    header("Location: locais_consult.php?tipo=$tipo&error=server_error");
    exit();
}
$check->bind_param("i", $localId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header("Location: locais_consult.php?tipo=$tipo&error=local_not_found");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| UPLOAD DA FOTO (campo opcional)
|--------------------------------------------------------------------------
*/

$image_url = null;

$uploadSubdir = ($tipo === 'externo') ? 'destinations' : 'stores';
$prefix = ($tipo === 'externo') ? 'destino_' : 'loja_';

$maxSize = 5 * 1024 * 1024;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=upload_error");
        exit();
    }

    if ($_FILES['photo']['size'] > $maxSize) {
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=image_too_large");
        exit();
    }

    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);

    if ($imageInfo === false) {
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=invalid_image");
        exit();
    }

    $allowedMime = ["image/jpeg", "image/png", "image/webp"];

    if (!in_array($imageInfo['mime'], $allowedMime)) {
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=invalid_image");
        exit();
    }

    $uploadDir = __DIR__ . "/../../img/" . $uploadSubdir . "/";
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Falha ao criar diretorio: $uploadDir");
            header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=upload_error");
            exit();
        }
    }

    if (!is_writable($uploadDir)) {
        error_log("Diretorio sem permissao de escrita: $uploadDir");
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=upload_error");
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
        header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=upload_error");
        exit();
    }

    // Remover foto antiga
    $oldPhoto = $conn->prepare("SELECT image_url FROM $tabela WHERE id = ?");
    $oldPhoto->bind_param("i", $localId);
    $oldPhoto->execute();
    $oldResult = $oldPhoto->get_result();
    $oldData = $oldResult->fetch_assoc();
    $oldPhoto->close();

    if (!empty($oldData['image_url']) && strpos($oldData['image_url'], "img/$uploadSubdir/") === 0) {
        $oldPath = __DIR__ . "/../../" . $oldData['image_url'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $image_url = "img/" . $uploadSubdir . "/" . $newName;
    error_log("Foto do local salva: $image_url");
}

/*
|--------------------------------------------------------------------------
| ATUALIZAR LOCAL
|--------------------------------------------------------------------------
*/

if ($tipo === 'externo') {
    if ($image_url !== null) {
        $stmt = $conn->prepare("
            UPDATE destinations SET
                name = ?, address = ?, city = ?, state = ?, latitude = ?, longitude = ?,
                type = ?, maps_url = ?, image_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssddsssi",
            $name, $address, $city, $state, $latitude, $longitude,
            $categoria, $mapsUrl, $image_url, $localId
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE destinations SET
                name = ?, address = ?, city = ?, state = ?, latitude = ?, longitude = ?,
                type = ?, maps_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssddssi",
            $name, $address, $city, $state, $latitude, $longitude,
            $categoria, $mapsUrl, $localId
        );
    }
} else {
    if ($image_url !== null) {
        $stmt = $conn->prepare("
            UPDATE stores SET
                name = ?, address = ?, city = ?, state = ?, latitude = ?, longitude = ?,
                maps_url = ?, image_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssddssi",
            $name, $address, $city, $state, $latitude, $longitude,
            $mapsUrl, $image_url, $localId
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE stores SET
                name = ?, address = ?, city = ?, state = ?, latitude = ?, longitude = ?,
                maps_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssddsi",
            $name, $address, $city, $state, $latitude, $longitude,
            $mapsUrl, $localId
        );
    }
}

if (!$stmt) {
    error_log("Erro ao preparar UPDATE $tabela: " . $conn->error);
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=server_error");
    exit();
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: locais_consult.php?tipo=$tipo&success=updated");
    exit();
} else {
    error_log("Erro ao atualizar local (ID: $localId, tipo: $tipo): " . $stmt->error);
    error_log("Dados: name=$name, lat=$latTxt, lng=$lngTxt, photo=" . ($image_url ?? 'null'));
    $stmt->close();
    $conn->close();
    header("Location: locais_edit.php?tipo=$tipo&id=$localId&error=server_error");
    exit();
}
?>