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
    header("Location: locais_consult.php?error=server_error");
    exit();
}

$conn->set_charset(DB_CHARSET);

$localId = (int)($_POST['local_id'] ?? 0);
$tipo    = ($_POST['tipo'] ?? '') === 'externo' ? 'externo' : 'loja';

if ($localId <= 0) {
    header("Location: locais_consult.php?tipo=$tipo&error=server_error");
    exit();
}

$tabela     = ($tipo === 'externo') ? 'destinations' : 'stores';
$uploadSubdir = ($tipo === 'externo') ? 'destinations' : 'stores';

$check = $conn->prepare("SELECT id FROM $tabela WHERE id = ?");
$check->bind_param("i", $localId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header("Location: locais_consult.php?tipo=$tipo&error=local_not_found");
    exit();
}
$check->close();

if (isset($_POST['action']) && $_POST['action'] === 'info') {
    $stmt = $conn->prepare("SELECT id, name FROM $tabela WHERE id = ?");
    $stmt->bind_param("i", $localId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($rid, $rname);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    echo json_encode(['id' => $rid, 'name' => $rname, 'tipo' => $tipo]);
    exit();
}

$photoStmt = $conn->prepare("SELECT image_url FROM $tabela WHERE id = ?");
$photoStmt->bind_param("i", $localId);
$photoStmt->execute();
$photoResult = $photoStmt->get_result();
$photoData = $photoResult->fetch_assoc();
$photoStmt->close();

if (!empty($photoData['image_url']) && strpos($photoData['image_url'], "img/$uploadSubdir/") === 0) {
    $photoPath = __DIR__ . "/../../" . $photoData['image_url'];
    if (file_exists($photoPath)) {
        unlink($photoPath);
    }
}

$stmt = $conn->prepare("DELETE FROM $tabela WHERE id = ?");
$stmt->bind_param("i", $localId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: locais_consult.php?tipo=$tipo&success=deleted");
    exit();
} else {
    error_log("Erro ao excluir local (ID: $localId, tipo: $tipo): " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: locais_consult.php?tipo=$tipo&error=server_error");
    exit();
}
?>