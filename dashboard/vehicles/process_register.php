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

$plateNumber        = strtoupper(trim($_POST['plate_number'] ?? ''));
$model              = trim($_POST['model'] ?? '');
$fancyName          = trim($_POST['fancy_name'] ?? '');
$renavam            = trim($_POST['renavam'] ?? '');
$chassisNumber      = strtoupper(trim($_POST['chassis_number'] ?? ''));
$yearModel          = trim($_POST['year_model'] ?? '');
$yearManufactured   = trim($_POST['year_manufactured'] ?? '');
$fuel               = trim($_POST['fuel'] ?? '');
$speciesType        = trim($_POST['species_type'] ?? '');
$bodywork           = trim($_POST['bodywork'] ?? '');
$exerciseYear       = trim($_POST['exercise_year'] ?? '');
$ownerDocument      = trim($_POST['owner_document'] ?? '');
$ownerName          = trim($_POST['owner_name'] ?? '');
$powerDisplacement  = trim($_POST['power_displacement'] ?? '');
$axles              = trim($_POST['axles'] ?? '');
$occupancy          = trim($_POST['occupancy'] ?? '');
$grossWeightTxt     = str_replace(',', '.', trim($_POST['gross_weight'] ?? ''));
$capacityTxt        = str_replace(',', '.', trim($_POST['capacity'] ?? ''));
$cmtTxt             = str_replace(',', '.', trim($_POST['cmt'] ?? ''));
$grossWeight        = ($grossWeightTxt !== '') ? (float)$grossWeightTxt : null;
$capacity           = ($capacityTxt !== '') ? (float)$capacityTxt : null;
$cmt                = ($cmtTxt !== '') ? (float)$cmtTxt : null;
$currentKm          = (int)($_POST['current_km'] ?? 0);

/*
|--------------------------------------------------------------------------
| GUARDAR DADOS PARA REPOPULAR O FORMULÁRIO EM CASO DE ERRO
|--------------------------------------------------------------------------
*/

$_SESSION['old_input_vehicles'] = [
    'plate_number'       => $plateNumber,
    'model'              => $model,
    'fancy_name'         => $fancyName,
    'renavam'            => $renavam,
    'chassis_number'     => $chassisNumber,
    'year_model'         => $yearModel,
    'year_manufactured'  => $yearManufactured,
    'fuel'               => $fuel,
    'gross_weight'       => $grossWeightTxt,
    'capacity'           => $capacityTxt,
    'species_type'       => $speciesType,
    'bodywork'           => $bodywork,
    'exercise_year'      => $exerciseYear,
    'owner_document'     => $ownerDocument,
    'owner_name'         => $ownerName,
    'power_displacement' => $powerDisplacement,
    'cmt'                => $cmtTxt,
    'axles'              => $axles,
    'occupancy'          => $occupancy,
    'current_km'         => $currentKm,
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
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Falha ao criar diretorio: $uploadDir");
            header("Location: vehicles_register.php?error=upload_error");
            exit();
        }
    }

    if (!is_writable($uploadDir)) {
        error_log("Diretorio sem permissao de escrita: $uploadDir");
        header("Location: vehicles_register.php?error=upload_error");
        exit();
    }

    $newName = uniqid("vehicle_", true) . ".jpg";
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
        header("Location: vehicles_register.php?error=upload_error");
        exit();
    }

    $photo_url = "img/vehicles/" . $newName;
    error_log("Foto do veiculo salva: $photo_url");
}

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO vehicles (
        plate_number, fancy_name, renavam, chassis_number, model,
        year_model, year_manufactured, fuel, gross_weight, capacity,
        species_type, bodywork, exercise_year, owner_document, owner_name,
        power_displacement, cmt, axles, occupancy,
        photo_url, current_km, created_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    error_log("Erro ao preparar INSERT: " . $conn->error);
    header("Location: vehicles_register.php?error=server_error");
    exit();
}

$stmt->bind_param(
    "ssssssssddssssssdsssi",
    $plateNumber, $fancyName, $renavam, $chassisNumber, $model,
    $yearModel, $yearManufactured, $fuel, $grossWeight, $capacity,
    $speciesType, $bodywork, $exerciseYear, $ownerDocument, $ownerName,
    $powerDisplacement, $cmt, $axles, $occupancy,
    $photo_url, $currentKm
);

if ($stmt->execute()) {
    unset($_SESSION['old_input_vehicles']);
    $stmt->close();
    $conn->close();
    header("Location: vehicles_consult.php?success=registered");
    exit();
} else {
    error_log("Erro ao inserir veiculo: " . $stmt->error);
    error_log("Dados: plate=$plateNumber, model=$model, km=$currentKm, photo=" . ($photo_url ?: 'vazio'));
    header("Location: vehicles_register.php?error=server_error");
    exit();
}
?>
