<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';
require '../phpmailer/Exception.php';

define('ACCESS_ALLOWED', true);

// Cargar configuración segura
require '/home/calebito/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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
    $email = trim($_POST['email']);
    $message = "Se o e-mail existir, você receberá instruções.";

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $token = bin2hex(random_bytes(32));

        $expires = date(
            "Y-m-d H:i:s",
            strtotime("+1 hour")
        );

        $stmt = $conn->prepare(
            "UPDATE users
            SET reset_token=?,
            reset_expires=?
            WHERE email=?"
        );

        $stmt->bind_param(
            "sss",
            $token,
            $expires,
            $email
        );

        $stmt->execute();

        $link = "https://calebitosistemadefrotas.site/auth/reset.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'mail.calebitosistemadefrotas.site';
            $mail->SMTPAuth = true;
            $mail->Username = 'ti@calebitosistemadefrotas.site';
            $mail->Password = 'h!vqy$a{;k29';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->Timeout = 10;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(
                'ti@calebitosistemadefrotas.site',
                'Calebito'
            );

            $mail->addAddress($email);

            $mail->isHTML(true);

            $mail->Subject = 'Recuperação de senha';

            $mail->Body = "
            <h2>Recuperação de senha</h2>
            <p>
            Clique no link abaixo para redefinir sua senha:
            </p>
            <p>
            <a href='$link'>
            Redefinir senha
            </a>
            </p>
            <p>
            Este link expira em 1 hora.
            </p>
            ";
            $mail->send();

            $message = "Link de recuperação enviado com sucesso.";

        } catch (Exception $e) {

            error_log($mail->ErrorInfo);

            $message = "Erro ao enviar e-mail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../img/favicon.png" type="image/x-icon">
    <title>Calebito - Sistema Inteligente de Gestão de Frotas</title>
    <link rel="stylesheet" href="../style.css">
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
                <div class="sub-subtitle">Recuperar Minha Senha</div>
                <div class="micro-subtitle">Informe seu e-mail para continuar</div>
            </div>
            <form
                method="POST"
                class="form-login">
                <input
                    class="button-user"
                    type="email"
                    name="email"
                    placeholder="Seu e-mail"
                    required>
                <button
                    class="button-conecte-se"
                    type="submit">
                    Enviar link
                </button>
                <a
                    href="../index.php"
                    class="button-conecte-se">
                    Voltar ao login
                </a>
            </form>
            <?php if (isset($message)): ?>
                <p class="error">
                    <?php echo $message; ?>
                </p>
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