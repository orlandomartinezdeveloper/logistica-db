<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/favicon.png" type="image/x-icon">
    <title>Calebito - Sistema Inteligente de Gestão de Frotas</title>
    <link rel="stylesheet" href="style.css">
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
        <!-- Fondo con montañas parallax -->
        <div class="mountains-parallax"></div>
        <!-- CONTENIDO LOGIN -->
        <div class="content">
            <img src="img/logo-light.svg" alt="Logo Calebito" class="logo">

            <h1 class="subtitle">
                Sistema Inteligente de<br>Gestão de Frotas
            </h1>

            <div class="micro-subtitle">Version Beta 0.5</div>

            <form action="auth/login.php" method="POST" class="form-login">
                <input class="button-user" type="email" name="email" placeholder="Usuário (e-mail)" required>
                <input class="button-password" type="password" name="password" placeholder="Senha" required>
                <button class="button-conecte-se" type="submit">Conecte-se</button>
            </form>
            <?php if (isset($_GET['error'])): ?>
                <p class="error">E-mail ou senha incorretos</p>
            <?php endif; ?>
        </div>
        <!-- CAMIÓN -->
        <div class="truck-container">
            <div class="truck-wrapper">
                <img src="img/camiao-calebito.png" alt="Camión Calebito" class="truck">

                <div class="wheel wheel-back">
                    <img src="img/rueda.png" alt="Rueda girando" class="wheel-img">
                </div>

                <div class="wheel wheel-front">
                    <img src="img/rueda.png" alt="Rueda girando" class="wheel-img">
                </div>
            </div>
        </div>

        <!-- CARRETERA / PISO -->
        <div class="road"></div>
    </div>
    <div id="errorPopup" class="popup-error">Usuário ou senha incorretos</div>
    <script src="js/popup.js"></script>
    <script src="js/loader.js"></script>
</body>
</html>