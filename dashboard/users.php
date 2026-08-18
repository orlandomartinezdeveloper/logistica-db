<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener el nombre del usuario de la sesión
$user_name = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Sistema de Gestão de Frotas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../img/favicon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="header-left">
            <img src="../../img/logo-light.svg" alt="Calebito" class="logo">

            <div class="title">
                Sistema Inteligente de Gestão de Frotas
                <small>Version: Beta 0.5</small>
            </div>
        </div>

        <!-- BOTÓN MENU MOBILE -->
        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN LAYOUT -->
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <nav>
                <a href="index.php"><i class="fa-solid fa-house"></i> Início</a>
                <a href="#"><i class="fa-solid fa-inbox"></i> Caixa de Entrada</a>
                <a href="#"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
                <a href="#"><i class="fa-solid fa-list-check"></i> Asignar Tarefas</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Status das Tarefas</a>
                <a href="#"><i class="fa-solid fa-plus"></i> Criar Tarefa</a>
                <a class="active" href="users.php"><i class="fa-solid fa-users"></i> Usuários</a>
                <a href="#"><i class="fa-solid fa-id-card"></i> Motoristas</a>
                <a href="#"><i class="fa-solid fa-truck"></i> Frota</a>
                <a href="#"><i class="fa-solid fa-route"></i> Rota</a>
            </nav>

            <button class="publish-btn">
                🚀 Publicar Planejamento
            </button>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <h1>
                <i class="fa-solid fa-user-plus"></i>
                Gerenciamento de Usuários
            </h1>
            <p>Selecione uma ação para gerenciar os usuários do sistema.</p>

            <div class="action-buttons">
                <a href="register.php" class="action-btn btn-create">
                    <span class="action-icon"><i class="fa-solid fa-user-plus"></i></span>
                    <span class="action-text">
                        <strong>Cadastrar</strong>
                        <small>Adicionar novo usuário</small>
                    </span>
                </a>

                <a href="users_consultar.php" class="action-btn btn-search">
                    <span class="action-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <span class="action-text">
                        <strong>Consultar</strong>
                        <small>Buscar e visualizar</small>
                    </span>
                </a>

                <a href="users_editar.php" class="action-btn btn-edit">
                    <span class="action-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                    <span class="action-text">
                        <strong>Editar</strong>
                        <small>Atualizar dados</small>
                    </span>
                </a>

                <a href="users_excluir.php" class="action-btn btn-delete">
                    <span class="action-icon"><i class="fa-solid fa-trash-can"></i></span>
                    <span class="action-text">
                        <strong>Excluir</strong>
                        <small>Remover usuário</small>
                    </span>
                </a>
            </div>
        </main>

    </div>
    <!-- JS -->
    <script src="js/menu.js"></script>
</body>
</html>