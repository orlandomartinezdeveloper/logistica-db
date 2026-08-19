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
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log($conn->connect_error);
    header("Location: users_consultar.php?error=server_error");
    exit();
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$userId = (int)($_POST['user_id'] ?? 0);
$action  = trim($_POST['action'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {
    header("Location: users_consultar.php?error=user_not_found");
    exit();
}

// Não permitir que o usuário exclua a si mesmo
if ($userId === (int)$_SESSION['user_id']) {
    header("Location: users_consultar.php?error=self_delete");
    exit();
}

// Verificar se o usuário existe
$check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check->bind_param("i", $userId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    header("Location: users_consultar.php?error=user_not_found");
    exit();
}
$check->close();

/*
|--------------------------------------------------------------------------
| AÇÃO: DESLIGAR (status -> inativo)
|--------------------------------------------------------------------------
*/

if ($action === 'deactivate') {
    $stmt = $conn->prepare("UPDATE users SET status = 'desligado' WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: users_consultar.php?success=deactivated");
        exit();
    } else {
        error_log("Erro ao desligar usuário: " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: users_consultar.php?error=server_error");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| AÇÃO: EXCLUIR DEFINITIVAMENTE (DELETE)
|--------------------------------------------------------------------------
*/

if ($action === 'delete') {
    // Remover foto do usuário (se existir e for local)
    $photoStmt = $conn->prepare("SELECT photo_url FROM users WHERE id = ?");
    $photoStmt->bind_param("i", $userId);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    $photoData = $photoResult->fetch_assoc();
    $photoStmt->close();

    if (!empty($photoData['photo_url']) && strpos($photoData['photo_url'], 'img/users/') === 0) {
        $photoPath = __DIR__ . "/../" . $photoData['photo_url'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }

    // Excluir o usuário
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: users_consultar.php?success=deleted");
        exit();
    } else {
        error_log("Erro ao excluir usuário: " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: users_consultar.php?error=server_error");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| AÇÃO INVÁLIDA
|--------------------------------------------------------------------------
*/

$conn->close();
header("Location: users_consultar.php?error=server_error");
exit();
?>
