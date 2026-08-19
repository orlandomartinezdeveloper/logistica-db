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
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

$conn->set_charset(DB_CHARSET);

/*
|--------------------------------------------------------------------------
| MENSAGENS DE ERRO E SUCESSO
|--------------------------------------------------------------------------
*/

$errorMessages = [
    'db_connection'      => 'Erro de conexão com o banco de dados. Tente novamente.',
    'name_required'      => 'O campo Nome é obrigatório.',
    'lastname_required'  => 'O campo Sobrenome é obrigatório.',
    'username_required'  => 'O campo Nome de Usuário é obrigatório.',
    'username_invalid'   => 'O nome de usuário deve conter apenas letras minúsculas, números, pontos, hífens ou underscores.',
    'username_exists'    => 'Este nome de usuário já está em uso. Escolha outro.',
    'email_required'     => 'O campo E-mail é obrigatório.',
    'email_exists'       => 'Este e-mail já está cadastrado para outro usuário.',
    'birth_day_required' => 'O campo Data de Nascimento é obrigatório.',
    'phone_required'     => 'O campo Telefone é obrigatório.',
    'address_required'   => 'O campo Endereço é obrigatório.',
    'cnh_required'       => 'O campo CNH é obrigatório.',
    'invalid_email'      => 'O e-mail informado não é válido.',
    'upload_error'       => 'Ocorreu um erro ao enviar a foto. Tente novamente.',
    'image_too_large'    => 'A foto enviada é muito grande (máximo 5MB).',
    'invalid_image'      => 'O arquivo enviado não é uma imagem válida (use JPG, JPEG, PNG ou WEBP).',
    'server_error'       => 'Erro no servidor ao atualizar usuário. Tente novamente.',
    'user_not_found'     => 'Usuário não encontrado.',
];

$errorMessage = null;

if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMessage = $errorMessages[$_GET['error']];
} elseif (isset($_GET['error'])) {
    $errorMessage = 'Erro ao atualizar usuário. Tente novamente.';
}

/*
|--------------------------------------------------------------------------
| CARREGAR DADOS DO USUÁRIO
|--------------------------------------------------------------------------
| O ID do usuário é obrigatório via parâmetro GET.
| Caso não seja informado ou o usuário não exista, redireciona para a consulta.
*/

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    header("Location: users_consultar.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT id, name, lastname, username, email, birth_day, phone, cep, address, cnh, role, photo_url, status
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: users_consultar.php?error=user_not_found");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Editar Usuário</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../img/favicon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
</head>
<body>

    <!-- CABEÇALHO -->
    <header class="header">
        <div class="header-left">
            <img src="../../img/logo-light.svg" alt="Calebito" class="logo">
            <div class="title">
                Sistema Inteligente de Gestão de Frotas
                <small>Version: Beta 0.5</small>
            </div>
        </div>

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

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="content">

            <!-- TÍTULO -->
            <h1>
                <i class="fa-solid fa-pen-to-square"></i>
                Editar Usuário
            </h1>

            <!-- MENSAGENS DE ERRO -->
            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <p>Editando: <strong><?php echo htmlspecialchars($user['name'] . ' ' . $user['lastname']); ?></strong></p>

            <!-- FOTO ATUAL DO USUÁRIO -->
            <?php if (!empty($user['photo_url'])): ?>
                <div class="current-photo">
                    <img
                        src="../<?php echo htmlspecialchars($user['photo_url']); ?>"
                        alt="<?php echo htmlspecialchars($user['name']); ?>"
                        class="user-photo-large">
                    <small>Foto atual</small>
                </div>
            <?php endif; ?>

            <!-- FORMULÁRIO DE EDIÇÃO -->
            <form
                id="formEditar"
                action="process_edit_user.php"
                method="POST"
                enctype="multipart/form-data"
                class="register-form">

                <!-- ID oculto do usuário -->
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                <!-- Nome -->
                <div class="form-group">
                    <label for="name">
                        <i class="fa-solid fa-user"></i>
                        Nome:
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        value="<?php echo htmlspecialchars($user['name']); ?>"
                        placeholder="Digite o nome">
                </div>

                <!-- Nome de Usuário -->
                <div class="form-group">
                    <label for="username">
                        <i class="fa-solid fa-at"></i>
                        Nome de Usuário:
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                        placeholder="Ex: joao.silva"
                        class="lowercase-input">
                    <small>Letras minúsculas, sem espaços. Será usado para login.</small>
                </div>

                <!-- Sobrenome -->
                <div class="form-group">
                    <label for="lastname">
                        <i class="fa-solid fa-user"></i>
                        Sobrenome:
                    </label>
                    <input
                        type="text"
                        id="lastname"
                        name="lastname"
                        required
                        value="<?php echo htmlspecialchars($user['lastname']); ?>"
                        placeholder="Digite o sobrenome">
                </div>

                <!-- Nova Foto -->
                <div class="form-group">
                    <label for="photo">
                        <i class="fa-solid fa-image"></i>
                        Nova Foto (opcional):
                    </label>
                    <input
                        type="file"
                        id="photo"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp,image/*">
                    <small>
                        Formatos permitidos: JPG, JPEG, PNG e WEBP. Máximo 5MB. Deixe vazio para manter a foto atual.
                    </small>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fa-solid fa-envelope"></i>
                        E-mail:
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        placeholder="exemplo@email.com">
                </div>

                <!-- Data de nascimento -->
                <div class="form-group">
                    <label for="birth_day">
                        <i class="fa-solid fa-calendar-days"></i>
                        Data de Nascimento:
                    </label>
                    <input
                        type="date"
                        id="birth_day"
                        name="birth_day"
                        required
                        value="<?php echo htmlspecialchars($user['birth_day']); ?>">
                </div>

                <!-- Telefone -->
                <div class="form-group">
                    <label for="phone">
                        <i class="fa-solid fa-phone"></i>
                        Telefone:
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        required
                        value="<?php echo htmlspecialchars($user['phone']); ?>"
                        placeholder="(22) 99999-9999">
                </div>

                <!-- CEP -->
                <div class="form-group">
                    <label for="cep">
                        <i class="fa-solid fa-map-pin"></i>
                        CEP:
                    </label>
                    <input
                        type="text"
                        id="cep"
                        name="cep"
                        maxlength="9"
                        value="<?php echo htmlspecialchars($user['cep'] ?? ''); ?>"
                        placeholder="00000-000">
                    <small>Digite o CEP para preencher o endereço automaticamente.</small>
                </div>

                <!-- Endereço -->
                <div class="form-group">
                    <label for="address">
                        <i class="fa-solid fa-location-dot"></i>
                        Endereço:
                    </label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        required
                        value="<?php echo htmlspecialchars($user['address']); ?>"
                        placeholder="Preenchido automaticamente pelo CEP">
                </div>

                <!-- CNH -->
                <div class="form-group">
                    <label for="cnh">
                        <i class="fa-solid fa-id-card"></i>
                        CNH:
                    </label>
                    <?php $selectedCnh = $user['cnh']; ?>
                    <select id="cnh" name="cnh" required>
                        <option value="" disabled <?= $selectedCnh === '' ? 'selected' : '' ?>>Selecione a categoria</option>
                        <option value="A" <?= $selectedCnh === 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $selectedCnh === 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= $selectedCnh === 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= $selectedCnh === 'D' ? 'selected' : '' ?>>D</option>
                        <option value="E" <?= $selectedCnh === 'E' ? 'selected' : '' ?>>E</option>
                        <option value="nao_tenho" <?= $selectedCnh === 'nao_tenho' ? 'selected' : '' ?>>Não tenho</option>
                    </select>
                </div>

                <!-- Tipo de Usuário -->
                <div class="form-group">
                    <label for="role">
                        <i class="fa-solid fa-badge"></i>
                        Tipo de Usuário:
                    </label>
                    <?php $selectedRole = $user['role']; ?>
                    <select id="role" name="role">
                        <option value="motorista" <?= $selectedRole === 'motorista' ? 'selected' : '' ?>>🚛 Motorista</option>
                        <option value="ajudante" <?= $selectedRole === 'ajudante' ? 'selected' : '' ?>>🛄 Ajudante</option>
                        <option value="gestor_logistica" <?= $selectedRole === 'gestor_logistica' ? 'selected' : '' ?>>📊 Gestor de Logística</option>
                        <option value="socio_proprietario" <?= $selectedRole === 'socio_proprietario' ? 'selected' : '' ?>>🏢 Sócio-Proprietário</option>
                        <option value="lider_setor" <?= $selectedRole === 'lider_setor' ? 'selected' : '' ?>>👔 Líder de Setor</option>
                        <option value="gerente_loja" <?= $selectedRole === 'gerente_loja' ? 'selected' : '' ?>>🏪 Gerente de Loja</option>
                        <option value="sub_gerente" <?= $selectedRole === 'sub_gerente' ? 'selected' : '' ?>>📋 Sub-gerente</option>
                        <option value="estoque" <?= $selectedRole === 'estoque' ? 'selected' : '' ?>>📦 Estoquista</option>
                        <option value="gestor_eventos" <?= $selectedRole === 'gestor_eventos' ? 'selected' : '' ?>>🎪 Gestor de Eventos</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">
                        <i class="fa-solid fa-toggle-on"></i>
                        Status:
                    </label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $user['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="ferias" <?= $user['status'] === 'ferias' ? 'selected' : '' ?>>Férias</option>
                        <option value="desligado" <?= $user['status'] === 'desligado' ? 'selected' : '' ?>>Desligado</option>
                    </select>
                </div>

                <!-- Senha (opcional na edição) -->
                <div class="form-divider"><span>Deixe vazio para manter a senha atual</span></div>

                <div class="form-group">
                    <label for="password">
                        <i class="fa-solid fa-lock"></i>
                        Nova Senha:
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Mínimo 6 caracteres">
                    <small>🔒 Mínimo 6 caracteres. Só preencha quiser alterar.</small>
                </div>

                <!-- BOTÃO ATUALIZAR (abre modal de confirmação) -->
                <button type="button" class="btn-submit" onclick="abrirModalConfirmar()">
                    <i class="fa-solid fa-save"></i>
                    Atualizar Usuário
                </button>

                <!-- BOTÃO VOLTAR À LISTA -->
                <a href="users_consultar.php" class="btn-submit" style="text-align: center; text-decoration: none; margin-top: 10px; display: block; background: #6c757d;">
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar à Lista
                </a>

            </form>

        </main>

    </div>

    <!-- ==============================
         MODAL DE CONFIRMAÇÃO - ATUALIZAR
    ============================== -->
    <div class="modal-overlay" id="modalConfirmar">
        <div class="modal-box">
            <!-- ÍCONE DE CONFIRMAÇÃO -->
            <div class="modal-icon modal-icon-confirm">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <h2>Tem certeza?</h2>
            <p class="modal-user-name"><?php echo htmlspecialchars($user['name'] . ' ' . $user['lastname']); ?></p>
            <p class="modal-desc">
                Deseja salvar as alterações realizadas neste usuário?
            </p>

            <div class="modal-actions">
                <!-- OPÇÃO: SIM, ATUALIZAR (submete o formulário) -->
                <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmarAtualizacao()">
                    <span class="btn-icon"><i class="fa-solid fa-check"></i></span>
                    <span class="btn-info">
                        <strong>Sim, atualizar</strong>
                        <small>Salvar todas as alterações realizadas</small>
                    </span>
                </button>

                <!-- OPÇÃO: NÃO, CANCELAR (fecha o modal) -->
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModalConfirmar()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Não, cancelar</strong>
                        <small>Manter os dados como estão</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="js/menu.js"></script>
    <script src="js/mask.js"></script>
    <script>
        /*
        |--------------------------------------------------------------------------
        | MODAL DE CONFIRMAÇÃO - ATUALIZAR USUÁRIO
        |--------------------------------------------------------------------------
        | Abre o modal perguntando se o usuário tem certeza das alterações.
        */
        function abrirModalConfirmar() {
            document.getElementById('modalConfirmar').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        /*
        | Fecha o modal de confirmação e remove o bloqueio de scroll.
        */
        function fecharModalConfirmar() {
            document.getElementById('modalConfirmar').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        /*
        | Confirma a atualização: submete o formulário de edição.
        */
        function confirmarAtualizacao() {
            document.getElementById('formEditar').submit();
        }

        /* Fecha o modal ao clicar fora da caixa de confirmação */
        document.getElementById('modalConfirmar').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalConfirmar();
            }
        });

        /* Fecha o modal ao pressionar a tecla ESC */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalConfirmar();
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
