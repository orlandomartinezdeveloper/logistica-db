<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Usuário';

/*
|--------------------------------------------------------------------------
| RECUPERAR DADOS ANTIGOS (caso tenha havido erro no cadastro anterior)
|--------------------------------------------------------------------------
*/

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']); // usa uma vez só

function old(string $field, array $old): string {
    return htmlspecialchars($old[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| MENSAGENS DE ERRO
|--------------------------------------------------------------------------
*/

$errorMessages = [
    'db_connection'      => 'Erro de conexão com o banco de dados. Tente novamente.',
    'name_required'       => 'O campo Nome é obrigatório.',
    'lastname_required'   => 'O campo Sobrenome é obrigatório.',
    'username_required'   => 'O campo Nome de Usuário é obrigatório.',
    'username_invalid'    => 'O nome de usuário deve conter apenas letras minúsculas, números, pontos, hífens ou underscores.',
    'username_exists'     => 'Este nome de usuário já está em uso. Escolha outro.',
    'email_required'      => 'O campo E-mail é obrigatório.',
    'email_exists'        => 'Este e-mail já está cadastrado para outro usuário.',
    'birth_day_required'  => 'O campo Data de Nascimento é obrigatório.',
    'phone_required'      => 'O campo Telefone é obrigatório.',
    'address_required'    => 'O campo Endereço é obrigatório.',
    'cnh_required'         => 'O campo CNH é obrigatório.',
    'password_required'   => 'O campo Senha é obrigatório.',
    'password_short'      => 'A senha deve ter no mínimo 6 caracteres.',
    'invalid_email'        => 'O e-mail informado não é válido.',
    'upload_error'         => 'Ocorreu um erro ao enviar a foto. Tente novamente.',
    'image_too_large'      => 'A foto enviada é muito grande (máximo 5MB).',
    'invalid_image'        => 'O arquivo enviado não é uma imagem válida (use JPG, JPEG, PNG ou WEBP).',
    'server_error'          => 'Erro no servidor ao cadastrar usuário. Tente novamente.',
];

$errorMessage = null;

if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMessage = $errorMessages[$_GET['error']];
} elseif (isset($_GET['error'])) {
    $errorMessage = 'Erro ao cadastrar usuário. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuário - Calebito</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../img/favicon.png?v=2">

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
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

        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <div class="overlay" id="overlay"></div>

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

        <!-- MAIN CONTENT -->
        <main class="content">

            <h1>
                <i class="fa-solid fa-user-plus"></i>
                Registrar Novo Usuário
            </h1>

            <!-- MENSAGENS -->

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    Usuário cadastrado com sucesso!
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- FORMULÁRIO -->

            <form
                action="process_register.php"
                method="POST"
                enctype="multipart/form-data"
                class="register-form">

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
                        value="<?= old('name', $old) ?>"
                        placeholder="Digite o nome">
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
                        value="<?= old('lastname', $old) ?>"
                        placeholder="Digite o sobrenome">
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
                        value="<?= old('username', $old) ?>"
                        placeholder="Ex: joao.silva"
                        class="lowercase-input">
                    <small>Letras minúsculas, sem espaços. Será usado para login.</small>
                </div>

                <!-- Foto -->
                <div class="form-group">
                    <label for="photo">
                        <i class="fa-solid fa-image"></i>
                        Foto:
                    </label>

                    <input
                        type="file"
                        id="photo"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp,image/*">

                    <small>
                        Formatos permitidos: JPG, JPEG, PNG e WEBP. Este campo não é obrigatório.
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
                        value="<?= old('email', $old) ?>"
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
                        value="<?= old('birth_day', $old) ?>">
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
                        value="<?= old('phone', $old) ?>"
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
                        value="<?= old('cep', $old) ?>"
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
                        value="<?= old('address', $old) ?>"
                        placeholder="Preenchido automaticamente pelo CEP">
                </div>

                <!-- CNH -->
                <div class="form-group">
                    <label for="cnh">
                        <i class="fa-solid fa-id-card"></i>
                        CNH:
                    </label>

                    <?php $selectedCnh = $old['cnh'] ?? ''; ?>
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

                <!-- Senha -->
                <div class="form-group">
                    <label for="password">
                        <i class="fa-solid fa-lock"></i>
                        Senha:
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Mínimo 6 caracteres">

                    <small>🔒 Mínimo 6 caracteres</small>
                </div>

                <!-- Confirmar Senha -->
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fa-solid fa-check-circle"></i>
                        Confirmar Senha:
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        placeholder="Digite a senha novamente">
                </div>

                <!-- Tipo de Usuário -->
                <div class="form-group">
                    <label for="role">
                        <i class="fa-solid fa-badge"></i>
                        Tipo de Usuário:
                    </label>

                    <?php $selectedRole = $old['role'] ?? ''; ?>
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

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-save"></i>
                    Cadastrar Usuário
                </button>

            </form>

        </main>

    </div>

    <!-- JS -->
    <script src="js/menu.js"></script>
    <script src="js/mask.js"></script>

</body>
</html>
