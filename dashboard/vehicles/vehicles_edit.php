<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

define('ACCESS_ALLOWED', true);
require '/home/calebito/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

$conn->set_charset(DB_CHARSET);

$errorMessages = [
    'db_connection'      => 'Erro de conexão com o banco de dados. Tente novamente.',
    'plate_required'     => 'O campo Placa é obrigatório.',
    'plate_invalid'      => 'Formato de placa inválido.',
    'plate_exists'       => 'Esta placa já está cadastrada para outro veículo.',
    'model_required'     => 'O campo Modelo é obrigatório.',
    'km_invalid'         => 'A quilometragem deve ser um número positivo.',
    'upload_error'       => 'Ocorreu um erro ao enviar a foto. Tente novamente.',
    'image_too_large'    => 'A foto enviada é muito grande (máximo 5MB).',
    'invalid_image'      => 'O arquivo enviado não é uma imagem válida (use JPG, JPEG, PNG ou WEBP).',
    'server_error'       => 'Erro no servidor ao atualizar veículo. Tente novamente.',
    'vehicle_not_found'  => 'Veículo não encontrado.',
];

$errorMessage = null;

if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMessage = $errorMessages[$_GET['error']];
} elseif (isset($_GET['error'])) {
    $errorMessage = 'Erro ao atualizar veículo. Tente novamente.';
}

$vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vehicleId <= 0) {
    header("Location: vehicles_consult.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT id, plate_number, model, status, current_km, photo_url, created_at, updated_at
    FROM vehicles
    WHERE id = ?
");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    header("Location: vehicles_consult.php?error=vehicle_not_found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Editar Veículo</title>
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
                <i class="fa-solid fa-pen-to-square"></i>
                Editar Veículo
            </h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <p>Editando: <strong><?php echo htmlspecialchars($vehicle['plate_number'] . ' - ' . $vehicle['model']); ?></strong></p>

            <?php if (!empty($vehicle['photo_url'])): ?>
                <div class="current-photo">
                    <img
                        src="../../<?php echo htmlspecialchars($vehicle['photo_url']); ?>"
                        alt="<?php echo htmlspecialchars($vehicle['model']); ?>"
                        class="user-photo-large">
                    <small>Foto atual</small>
                </div>
            <?php endif; ?>

            <form
                id="formEditar"
                action="process_edit_vehicle.php"
                method="POST"
                enctype="multipart/form-data"
                class="register-form">

                <input type="hidden" name="vehicle_id" value="<?php echo htmlspecialchars($vehicle['id']); ?>">

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
                        value="<?php echo htmlspecialchars($vehicle['plate_number']); ?>"
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
                        value="<?php echo htmlspecialchars($vehicle['model']); ?>"
                        placeholder="Ex: Mercedes-Benz Sprinter">
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
                    <small>Formatos permitidos: JPG, JPEG, PNG e WEBP. Máximo 5MB. Deixe vazio para manter a foto atual.</small>
                </div>

                <!-- Quilometragem -->
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
                        value="<?php echo htmlspecialchars($vehicle['current_km']); ?>"
                        placeholder="0">
                    <small>KM atual do veículo.</small>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">
                        <i class="fa-solid fa-toggle-on"></i>
                        Status:
                    </label>
                    <select id="status" name="status">
                        <option value="ativo" <?php echo $vehicle['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="desligado" <?php echo $vehicle['status'] === 'desligado' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <button type="button" class="btn-submit" onclick="abrirModalConfirmar()">
                    <i class="fa-solid fa-save"></i>
                    Atualizar Veículo
                </button>

                <a href="vehicles_consult.php" class="btn-submit" style="text-align: center; text-decoration: none; margin-top: 10px; display: block; background: #6c757d;">
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar à Lista
                </a>

            </form>

        </main>

    </div>

    <!-- MODAL CONFIRMAÇÃO -->
    <div class="modal-overlay" id="modalConfirmar">
        <div class="modal-box">
            <div class="modal-icon modal-icon-confirm">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <h2>Tem certeza?</h2>
            <p class="modal-user-name"><?php echo htmlspecialchars($vehicle['plate_number'] . ' - ' . $vehicle['model']); ?></p>
            <p class="modal-desc">
                Deseja salvar as alterações realizadas neste veículo?
            </p>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-confirm" onclick="document.getElementById('formEditar').submit()">
                    <span class="btn-icon"><i class="fa-solid fa-check"></i></span>
                    <span class="btn-info">
                        <strong>Sim, atualizar</strong>
                        <small>Salvar todas as alterações realizadas</small>
                    </span>
                </button>

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

    <script src="../js/menu.js"></script>
    <script>
        function abrirModalConfirmar() {
            document.getElementById('modalConfirmar').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        function fecharModalConfirmar() {
            document.getElementById('modalConfirmar').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        document.getElementById('modalConfirmar').addEventListener('click', function(e) {
            if (e.target === this) fecharModalConfirmar();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') fecharModalConfirmar();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
