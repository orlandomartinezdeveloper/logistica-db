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
| DADOS DA SESSÃO
|--------------------------------------------------------------------------
*/

$user_name = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Sistema de Gestão de Frotas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../../img/favicon.png?v=2">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
</head>
<body>

    <!-- CABEÇALHO -->
    <header class="header">
        <div class="header-left">
            <img src="../../../img/logo-light.svg" alt="Calebito" class="logo">

            <div class="title">
                Sistema Inteligente de Gestão de Frotas
                <small>Version: Beta 0.5</small>
            </div>
        </div>

        <!-- BOTÃO MENU MOBILE -->
        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- OVERLAY DO MENU MOBILE -->
    <div class="overlay" id="overlay"></div>

    <!-- LAYOUT PRINCIPAL -->
    <div class="layout">

        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <nav>
                <a href="../index.php"><i class="fa-solid fa-house"></i> Início</a>
                <a href="#"><i class="fa-solid fa-inbox"></i> Caixa de Entrada</a>
                <a href="#"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
                <a href="#"><i class="fa-solid fa-list-check"></i> Asignar Tarefas</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Status das Tarefas</a>
                <a href="#"><i class="fa-solid fa-plus"></i> Criar Tarefa</a>
                <a class="active" href="users_select.php"><i class="fa-solid fa-users"></i> Usuários</a>
                <a href="#"><i class="fa-solid fa-id-card"></i> Motoristas</a>
                <a href="#"><i class="fa-solid fa-truck"></i> Frota</a>
                <a href="#"><i class="fa-solid fa-route"></i> Rota</a>
            </nav>

            <a href="../../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="content">
            <h1>
                <i class="fa-solid fa-user-plus"></i>
                Gerenciamento de Usuários
            </h1>
            <p>Selecione uma ação para gerenciar os usuários do sistema.</p>

            <!-- BOTÕES DE AÇÃO -->
            <div class="action-buttons">
                <!-- Botão: Cadastrar novo usuário -->
                <a href="users_register.php" class="action-btn btn-create">
                    <span class="action-icon"><i class="fa-solid fa-user-plus"></i></span>
                    <span class="action-text">
                        <strong>Cadastrar</strong>
                        <small>Adicionar novo usuário</small>
                    </span>
                </a>

                <!-- Botão: Consultar, editar e excluir usuários -->
                <a href="users_consult.php" class="action-btn btn-search">
                    <span class="action-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <span class="action-text">
                        <strong>Consultar</strong>
                        <small>Buscar, editar e remover</small>
                    </span>
                </a>
            </div>
        </main>

    </div>

    <!-- SCRIPTS -->
    <script src="../js/menu.js"></script>
</body>
</html>
