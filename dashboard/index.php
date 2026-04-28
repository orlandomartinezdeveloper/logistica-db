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
                <a class="active" href="#"><i class="fa-solid fa-house"></i> Início</a>
                <a href="#"><i class="fa-solid fa-inbox"></i> Caixa de Entrada</a>
                <a href="#"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
                <a href="#"><i class="fa-solid fa-list-check"></i> Asignar Tarefas</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Status das Tarefas</a>
                <a href="#"><i class="fa-solid fa-plus"></i> Criar Tarefa</a>
                <a href="#"><i class="fa-solid fa-user-plus"></i> Registrar Usuário</a>
                <a href="#"><i class="fa-solid fa-users"></i> Pessoal Cadastrado</a>
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
            <h1>Bem-vindo ao Sistema Inteligente de Gestão de Frotas da Calebito</h1>

            <p>
                Esta plataforma foi desenvolvida para otimizar a gestão de frotas e a logística da empresa,
                organizando os processos de envio de pedidos para franqueados e lojas próprias.
                Auxilia motoristas e gerência no cumprimento dos objetivos operacionais.
            </p>

            <div class="cards">
                <div class="card">
                    <h3>Tarefas Ativas</h3>
                    <p>12</p>
                </div>

                <div class="card">
                    <h3>Motoristas</h3>
                    <p>8</p>
                </div>

                <div class="card">
                    <h3>Veículos</h3>
                    <p>15</p>
                </div>

                <div class="card">
                    <h3>Relatórios</h3>
                    <p>4</p>
                </div>
            </div>
        </main>

    </div>

    <!-- JS -->
    <script src="js/menu.js"></script>
</body>
</html>
