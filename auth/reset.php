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
$token = $_GET['token'] ?? '';
$valid = false;
$user = null;
if ($token) {
    $stmt = $conn->prepare(
        "SELECT id, reset_expires
        FROM users
        WHERE reset_token=?"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (strtotime($user['reset_expires']) > time()) {
            $valid = true;
        }
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid && $user) {
    $new_password = password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );
    $stmt = $conn->prepare(
        "UPDATE users
        SET password_hash=?,
        reset_token=NULL,
        reset_expires=NULL
        WHERE id=?"
    );
    $stmt->bind_param(
        "si",
        $new_password,
        $user['id']
    );
    $stmt->execute();
    $success = "Senha atualizada com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <link
        rel="shortcut icon"
        href="../../img/favicon.png"
        type="image/x-icon">

    <title>Calebito - Sistema Inteligente de Gestão de Frotas</title>
    <link
        rel="stylesheet"
        href="../style.css">
</head>

<body>
    <!-- LOADER -->
    <div id="loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <p>Carregando...</p>
        </div>
    </div>
    <div class="scene">
        <!-- MONTAÑAS -->
        <div class="mountains-parallax"></div>
        <!-- CONTENIDO -->
        <div class="content">
            <img
                src="../../img/logo-light.svg"
                alt="Logo Calebito"
                class="logo">
            <h1 class="subtitle">
                Sistema Inteligente de<br>Gestão de Frotas
            </h1>
            <div class="mark-subtitle">
                <div class="sub-subtitle">Redefinir Senha</div>
                <div class="micro-subtitle">Digite sua nova senha</div>
            </div>
            <?php if ($valid && !isset($success)): ?>
                <form
                    method="POST"
                    class="form-login">
                    <input
                        class="button-password"
                        type="password"
                        name="password"
                        placeholder="Nova senha"
                        required>
                    <button
                        class="button-conecte-se"
                        type="submit">
                        Atualizar senha
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!$valid): ?>
                <p class="error">
                    Token inválido ou expirado
                </p>
                <a
                    href="../index.php"
                    class="button-conecte-se"
                    style="margin-top:10px;">
                    Voltar ao login
                </a>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="error">
                    <?php echo $success; ?>
                </p>
                <a
                    href="../index.php"
                    class="button-conecte-se"
                    style="margin-top:10px;">
                    Voltar ao login
                </a>
            <?php endif; ?>
        </div>
        <!-- CAMIÓN -->
        <div class="truck-container">
            <div class="truck-wrapper">
                <img
                    src="../../img/camiao-calebito.png"
                    alt="Camión Calebito"
                    class="truck">
                <div class="wheel wheel-back">
                    <img
                        src="../../img/rueda.png"
                        alt="Rueda"
                        class="wheel-img">
                </div>
                <div class="wheel wheel-front">
                    <img
                        src="../../img/rueda.png"
                        alt="Rueda"
                        class="wheel-img">
                </div>
            </div>
        </div>
        <!-- CARRETERA -->
        <div class="road"></div>
    </div>
    <script src="../js/popup.js"></script>
    <script src="../js/loader.js"></script>
</body>
</html>