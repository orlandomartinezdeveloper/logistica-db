<?php
// Configuración de la conexión a la base de datos (ajusta según tu cPanel)
$host = 'localhost';
$username = 'calebito_admin';     // Ej: calebito_miweb_user (el que creaste en cPanel)
$password = 'ProdFeb10**';       // La contraseña que pusiste al crear el usuario
$dbname = 'calebito_transporte_db';    // Ej: calebito_miweb_db

// Crear conexión
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Recibir datos del formulario
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: index.php?error=1");
    exit();
}

// Buscar el usuario por email (o por name si usas nombre como login)
$stmt = $conn->prepare("SELECT id, name, password_hash FROM users WHERE email = ? OR name = ?");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verificar la contraseña (usa password_verify porque guardaste hash)
    if (password_verify($password, $user['password_hash'])) {
        // ¡Login exitoso!
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        
        // Redirigir a test.html
        header("Location: ../test.html");
        exit();
    }
}

 // Si llega aquí: error de login
header("Location: index.php?error=1");
exit();

$stmt->close();
$conn->close();
?>