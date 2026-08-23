<?php
session_start();

/*
|--------------------------------------------------------------------------
| VERIFICAR SESSÃO
|--------------------------------------------------------------------------
| Redireciona para o login caso o usuário não esteja autenticado.
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| CONEXÃO COM O BANCO DE DADOS
|--------------------------------------------------------------------------
*/

define('ACCESS_ALLOWED', true);
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    header("Location: users/users_consult.php?error=server_error");
    exit();
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$userId    = (int)($_POST['user_id'] ?? 0);
$newStatus = trim($_POST['new_status'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
| Verifica se o ID é válido e se o novo status é permitido.
*/

if ($userId <= 0) {
    header("Location: users/users_consult.php?error=user_not_found");
    exit();
}

$allowedStatuses = ['ativo', 'ferias', 'desligado'];

if (!in_array($newStatus, $allowedStatuses)) {
    header("Location: users/users_consult.php?error=server_error");
    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SE O USUÁRIO EXISTE
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check->bind_param("i", $userId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header("Location: users/users_consult.php?error=user_not_found");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| ATUALIZAR O STATUS DO USUÁRIO
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $userId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: users/users_consult.php?success=status_changed");
    exit();
} else {
    error_log("Erro ao mudar status do usuário: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: users/users_consult.php?error=server_error");
    exit();
}
?>
