<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$old = $_SESSION['old_input_vehicles'] ?? [];
unset($_SESSION['old_input_vehicles']);

function oldv($field, $old) {
    return htmlspecialchars($old[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

$errorMessages = [
    'db_connection'       => 'Erro de conexão com o banco de dados. Tente novamente.',
    'plate_required'      => 'O campo Placa é obrigatório.',
    'plate_invalid'       => 'Formato de placa inválido. Use o padrão ABC-1D23 ou ABC1D23.',
    'plate_exists'        => 'Esta placa já está cadastrada para outro veículo.',
    'model_required'      => 'O campo Modelo é obrigatório.',
    'km_invalid'          => 'A quilometragem deve ser um número positivo.',
    'upload_error'        => 'Ocorreu um erro ao enviar a foto. Tente novamente.',
    'image_too_large'     => 'A foto enviada é muito grande (máximo 5MB).',
    'invalid_image'       => 'O arquivo enviado não é uma imagem válida (use JPG, JPEG, PNG ou WEBP).',
    'server_error'        => 'Erro no servidor ao cadastrar veículo. Tente novamente.',
];

$errorMessage = null;

if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMessage = $errorMessages[$_GET['error']];
} elseif (isset($_GET['error'])) {
    $errorMessage = 'Erro ao cadastrar veículo. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Veículo - Calebito</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/favicon.png?v=2">
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../css/register.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
</head>
<body>

    <header class="header">
        <div class="header-left">
            <img src="../../../img/logo-light.svg" alt="Calebito" class="logo">
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

        <aside class="sidebar">
            <nav>
                <a href="../index.php"><i class="fa-solid fa-house"></i> Início</a>
                <a href="#"><i class="fa-solid fa-inbox"></i> Caixa de Entrada</a>
                <a href="#"><i class="fa-solid fa-calendar-days"></i> Calendário</a>
                <a href="#"><i class="fa-solid fa-list-check"></i> Asignar Tarefas</a>
                <a href="#"><i class="fa-solid fa-chart-line"></i> Status das Tarefas</a>
                <a href="#"><i class="fa-solid fa-plus"></i> Criar Tarefa</a>
                <a href="../users/users_select.php"><i class="fa-solid fa-users"></i> Usuários</a>
                <a href="#"><i class="fa-solid fa-id-card"></i> Motoristas</a>
                <a class="active" href="vehicles_select.php"><i class="fa-solid fa-truck"></i> Frota</a>
                <a href="#"><i class="fa-solid fa-route"></i> Rota</a>
            </nav>
            <a href="../../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </aside>

        <main class="content">

            <h1>
                <i class="fa-solid fa-truck-pickup"></i>
                Cadastrar Novo Veículo
            </h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form
                action="process_register.php"
                method="POST"
                enctype="multipart/form-data"
                class="register-form">

                <!-- Placa -->
                <div class="form-group">
                    <label for="plate_number">
                        <i class="fa-solid fa-hashtag"></i>
                        Placa:
                    </label>
                    <input
                        type="text"
                        id="plate_number"
                        name="plate_number"
                        required
                        maxlength="20"
                        value="<?= oldv('plate_number', $old) ?>"
                        placeholder="Ex: ABC-1D23"
                        style="text-transform: uppercase;">
                    <small>Formato brasileiro: ABC-1D23 ou ABC1D23</small>
                </div>

                <!-- Modelo -->
                <div class="form-group">
                    <label for="model">
                        <i class="fa-solid fa-truck"></i>
                        Modelo:
                    </label>
                    <input
                        type="text"
                        id="model"
                        name="model"
                        required
                        maxlength="100"
                        value="<?= oldv('model', $old) ?>"
                        placeholder="Ex: Mercedes-Benz Sprinter">
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
                    <small>Formatos permitidos: JPG, JPEG, PNG e WEBP. Este campo não é obrigatório.</small>
                </div>

                <!-- Quilometragem Atual -->
                <div class="form-group">
                    <label for="current_km">
                        <i class="fa-solid fa-gauge-high"></i>
                        Quilometragem Atual:
                    </label>
                    <input
                        type="number"
                        id="current_km"
                        name="current_km"
                        min="0"
                        value="<?= oldv('current_km', $old) ?: '0' ?>"
                        placeholder="0">
                    <small>KM atual do veículo.</small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-save"></i>
                    Cadastrar Veículo
                </button>

            </form>

        </main>

    </div>

    <script src="../js/menu.js"></script>
</body>
</html>
