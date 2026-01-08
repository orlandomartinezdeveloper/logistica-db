<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calebito - Sistema Inteligente de Gestão de Frotas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Fondo con montañas parallax -->
    <div class="mountains-parallax"></div>
    
    <img src="img/logo-light.svg" alt="Logo Calebito" class="logo">
    <h1 class="subtitle">Sistema Inteligente de<br>Gestão de Frotas</h1>
    <div class="micro-subtitle">Version Beta 0.5</div>
    
    <form action="auth/login.php" method="POST" class="form-login">
        <input class="button-user" type="text" name="email" placeholder="Usuário" required><br>
        <input class="button-password" type="password" name="password" placeholder="Senha" required><br>
        <button class="button-conecte-se" type="submit">Conecte-se</button>
    </form>
    
    <?php if (isset($_GET['error'])): ?>
        <p class="error">E-mail ou senha incorretos</p>
    <?php endif; ?>
    
    <!-- Contenedor del camión con ruedas giratorias y piso -->
    <div class="truck-container">
        <div class="truck-wrapper">
            <img src="img/camiao-calebito.png" alt="Camión Calebito" class="truck">
            <!-- Rueda trasera -->
            <div class="wheel wheel-back">
                <img src="img/rueda.png" alt="Rueda girando" class="wheel-img">
            </div>
            <!-- Rueda delantera -->
            <div class="wheel wheel-front">
                <img src="img/rueda.png" alt="Rueda girando" class="wheel-img">
            </div>
        </div>
        <!-- Piso negro donde rueda el camión -->
        <div class="road"></div>
    </div>
</body>
</html>