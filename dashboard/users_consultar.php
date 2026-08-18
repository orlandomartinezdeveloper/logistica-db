<?php
session_start();

/*
|--------------------------------------------------------------------------
| VERIFICAR SESSÃO
|--------------------------------------------------------------------------
| Redireciona para o login caso o usuário não esteja autenticado.
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
require __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| BUSCA DE USUÁRIOS
|--------------------------------------------------------------------------
| Recebe o termo de busca via GET e filtra por nome, sobrenome,
| e-mail ou telefone. Se vazio, retorna todos os usuários.
*/

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT id, name, lastname, email, phone, role, status, photo_url
        FROM users
        WHERE name LIKE ? OR lastname LIKE ? OR email LIKE ? OR phone LIKE ?
        ORDER BY name ASC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("ssss", $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT id, name, lastname, email, phone, role, status, photo_url
        FROM users
        ORDER BY name ASC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$total = count($users);

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
    <title>Calebito - Consulta de Usuários</title>
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

        <!-- BOTÃO MENU MOBILE -->
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

            <!-- TÍTULO DA PÁGINA -->
            <h1>
                <i class="fa-solid fa-magnifying-glass"></i>
                Consulta de Usuários
            </h1>
            <p>Visualize todos os usuários cadastrados no sistema.</p>

            <!-- BARRA DE BUSCA -->
            <form class="search-bar" method="GET" action="">
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por nome, sobrenome, e-mail ou telefone..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </form>

            <!-- INFORMAÇÃO DOS RESULTADOS -->
            <?php if (!empty($search)): ?>
                <div class="results-info">
                    Encontrados <strong><?php echo $total; ?></strong>
                    <?php echo $total === 1 ? 'resultado' : 'resultados'; ?>
                    para "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    — <a href="users_consultar.php" style="color: #2c7da0;">Limpar busca</a>
                </div>
            <?php endif; ?>

            <!-- TABELA DE USUÁRIOS -->
            <?php if ($total > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Sobrenome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Função</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($user['photo_url'])): ?>
                                        <img
                                            src="../<?php echo htmlspecialchars($user['photo_url']); ?>"
                                            alt="<?php echo htmlspecialchars($user['name']); ?>"
                                            class="user-photo">
                                    <?php else: ?>
                                        <div class="user-photo-placeholder">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: '—'); ?></td>
                                <td>
                                    <span class="role-badge">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <!-- MENSAGEM QUANDO NÃO HÁ RESULTADOS -->
            <?php else: ?>
                <div class="no-results">
                    <i class="fa-solid fa-user-slash"></i>
                    Nenhum usuário encontrado.
                </div>
            <?php endif; ?>

        </main>

    </div>

    <!-- JS -->
    <script src="js/menu.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
