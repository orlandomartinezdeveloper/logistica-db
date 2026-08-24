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
$newStatus = trim($_POST['new_status'] ?? '');

if ($vehicleId <= 0) {
    header("Location: vehicles_consult.php?error=vehicle_not_found");
    exit();
}

$allowedStatuses = ['ativo', 'desligado'];

if (!in_array($newStatus, $allowedStatuses)) {
    header("Location: vehicles_consult.php?error=server_error");
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

$stmt = $conn->prepare("UPDATE vehicles SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $newStatus, $vehicleId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: vehicles_consult.php?success=status_changed");
    exit();
} else {
    error_log("Erro ao mudar status do veículo: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: vehicles_consult.php?error=server_error");
    exit();
}
?>
