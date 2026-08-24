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
    header("Location: vehicles_consult.php?error=server_error");
    exit();
}

$conn->set_charset(DB_CHARSET);

$vehicleId = (int)($_POST['vehicle_id'] ?? 0);
$action    = trim($_POST['action'] ?? '');

if ($vehicleId <= 0) {
    header("Location: vehicles_consult.php?error=vehicle_not_found");
    exit();
}

$check = $conn->prepare("SELECT id FROM vehicles WHERE id = ?");
$check->bind_param("i", $vehicleId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header("Location: vehicles_consult.php?error=vehicle_not_found");
    exit();
}
$check->close();

if ($action === 'deactivate') {
    $stmt = $conn->prepare("UPDATE vehicles SET status = 'desligado', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $vehicleId);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: vehicles_consult.php?success=status_changed");
        exit();
    } else {
        error_log("Erro ao desativar veículo: " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: vehicles_consult.php?error=server_error");
        exit();
    }
}

if ($action === 'delete') {
    $photoStmt = $conn->prepare("SELECT photo_url FROM vehicles WHERE id = ?");
    $photoStmt->bind_param("i", $vehicleId);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    $photoData = $photoResult->fetch_assoc();
    $photoStmt->close();

    if (!empty($photoData['photo_url']) && strpos($photoData['photo_url'], 'img/vehicles/') === 0) {
        $photoPath = __DIR__ . "/../../" . $photoData['photo_url'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicleId);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: vehicles_consult.php?success=deleted");
        exit();
    } else {
        error_log("Erro ao excluir veículo: " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: vehicles_consult.php?error=server_error");
        exit();
    }
}

$conn->close();
header("Location: vehicles_consult.php?error=server_error");
exit();
?>
