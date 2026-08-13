<?php

define('ACCESS_ALLOWED', true);

// Cargar configuración segura
require '/home/calebito/config.php';

// Crear conexión
$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

// Verificar conexión
if ($conn->connect_error) {
    error_log($conn->connect_error);
    die("Erro interno do servidor.");
}

// Charset recomendado
$conn->set_charset(DB_CHARSET);

// Recibir datos del formulario
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validar campos vacíos
if (empty($email) || empty($password)) {
    header("Location: ../index.php?error=1");
    exit();
}

// Buscar usuario
$stmt = $conn->prepare("
    SELECT id, name, password_hash 
    FROM users 
    WHERE email = ? OR name = ?
");

$stmt->bind_param("ss", $email, $email);
$stmt->execute();

$result = $stmt->get_result();

// Verificar usuario
if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    // Verificar contraseña
    if (password_verify($password, $user['password_hash'])) {

        session_start();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Login correcto
        header("Location: ../dashboard/index.php");
        exit();
    }
}

// Login incorrecto
header("Location: ../index.php?error=1");
exit();

// Cerrar conexión
$stmt->close();
$conn->close();

?>