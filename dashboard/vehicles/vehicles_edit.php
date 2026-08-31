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
    'model_required'     => 'O campo Marca/Modelo é obrigatório.',
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
    SELECT id, plate_number, fancy_name, renavam, chassis_number, model,
           year_model, year_manufactured, fuel, gross_weight, capacity,
           species_type, bodywork, exercise_year, owner_document, owner_name,
           power_displacement, cmt, axles, occupancy,
           status, current_km, photo_url, created_at, updated_at
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

function fmtDec($value) {
    if ($value === null || $value === '') return '';
    return str_replace('.', ',', (string)$value);
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
                <a href="../locais/locais_select.php"><i class="fa-solid fa-location-dot"></i> Locais</a>
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

                <div class="form-divider"><span>Foto do Veículo</span></div>

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

                <div class="form-divider"><span>Identificação</span></div>

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

                <!-- Marca / Modelo / Versão -->
                <div class="form-group">
                    <label for="model">
                        <i class="fa-solid fa-truck"></i>
                        Marca / Modelo / Versão:
                    </label>
                    <input
                        type="text"
                        id="model"
                        name="model"
                        required
                        maxlength="100"
                        value="<?php echo htmlspecialchars($vehicle['model']); ?>"
                        placeholder="Ex: Mercedes-Benz Sprinter 316 CDI">
                </div>

                <!-- Nome Fantasia -->
                <div class="form-group">
                    <label for="fancy_name">
                        <i class="fa-solid fa-id-badge"></i>
                        Nome Fantasia:
                    </label>
                    <input
                        type="text"
                        id="fancy_name"
                        name="fancy_name"
                        maxlength="100"
                        value="<?php echo htmlspecialchars($vehicle['fancy_name']); ?>"
                        placeholder="Ex: Furgão Entregas">
                </div>

                <div class="form-divider"><span>Documentação (CRLV)</span></div>

                <!-- RENAVAM -->
                <div class="form-group">
                    <label for="renavam">
                        <i class="fa-solid fa-file-invoice"></i>
                        RENAVAM:
                    </label>
                    <input
                        type="text"
                        id="renavam"
                        name="renavam"
                        maxlength="20"
                        value="<?php echo htmlspecialchars($vehicle['renavam']); ?>"
                        placeholder="Número do RENAVAM">
                </div>

                <!-- Número do Chassi / VIN -->
                <div class="form-group">
                    <label for="chassis_number">
                        <i class="fa-solid fa-fingerprint"></i>
                        Número do Chassi / VIN:
                    </label>
                    <input
                        type="text"
                        id="chassis_number"
                        name="chassis_number"
                        maxlength="20"
                        value="<?php echo htmlspecialchars($vehicle['chassis_number']); ?>"
                        placeholder="Ex: 9BM958194MB012345"
                        style="text-transform: uppercase;">
                </div>

                <!-- Ano Modelo + Ano Fabricação -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="year_model">
                            <i class="fa-solid fa-calendar"></i>
                            Ano Modelo:
                        </label>
                        <input
                            type="number"
                            id="year_model"
                            name="year_model"
                            min="1900"
                            max="2100"
                            value="<?php echo htmlspecialchars($vehicle['year_model']); ?>"
                            placeholder="Ex: 2024">
                    </div>

                    <div class="form-group">
                        <label for="year_manufactured">
                            <i class="fa-solid fa-calendar-check"></i>
                            Ano Fabricação:
                        </label>
                        <input
                            type="number"
                            id="year_manufactured"
                            name="year_manufactured"
                            min="1900"
                            max="2100"
                            value="<?php echo htmlspecialchars($vehicle['year_manufactured']); ?>"
                            placeholder="Ex: 2023">
                    </div>
                </div>

                <!-- Combustível -->
                <div class="form-group">
                    <label for="fuel">
                        <i class="fa-solid fa-gas-pump"></i>
                        Combustível:
                    </label>
                    <select id="fuel" name="fuel">
                        <option value="">Selecione...</option>
                        <option value="Diesel" <?php echo $vehicle['fuel'] === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Gasolina" <?php echo $vehicle['fuel'] === 'Gasolina' ? 'selected' : ''; ?>>Gasolina</option>
                        <option value="Álcool" <?php echo $vehicle['fuel'] === 'Álcool' ? 'selected' : ''; ?>>Álcool</option>
                        <option value="Flex" <?php echo $vehicle['fuel'] === 'Flex' ? 'selected' : ''; ?>>Flex</option>
                        <option value="GNV" <?php echo $vehicle['fuel'] === 'GNV' ? 'selected' : ''; ?>>GNV</option>
                        <option value="GASOLINA/ALCOOL/GAS NATURAL" <?php echo $vehicle['fuel'] === 'GASOLINA/ALCOOL/GAS NATURAL' ? 'selected' : ''; ?>>GASOLINA/ALCOOL/GAS NATURAL</option>
                        <option value="GASOLINA/GAS NATURAL" <?php echo $vehicle['fuel'] === 'GASOLINA/GAS NATURAL' ? 'selected' : ''; ?>>GASOLINA/GAS NATURAL</option>
                        <option value="ALCOOL/GAS NATURAL" <?php echo $vehicle['fuel'] === 'ALCOOL/GAS NATURAL' ? 'selected' : ''; ?>>ALCOOL/GAS NATURAL</option>
                        <option value="Elétrico" <?php echo $vehicle['fuel'] === 'Elétrico' ? 'selected' : ''; ?>>Elétrico</option>
                        <option value="Híbrido" <?php echo $vehicle['fuel'] === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                        <option value="Outro" <?php echo $vehicle['fuel'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <!-- Exercício -->
                <div class="form-group">
                    <label for="exercise_year">
                        <i class="fa-solid fa-calendar-day"></i>
                        Exercício:
                    </label>
                    <input
                        type="number"
                        id="exercise_year"
                        name="exercise_year"
                        min="1900"
                        max="2100"
                        value="<?php echo htmlspecialchars($vehicle['exercise_year']); ?>"
                        placeholder="Ex: 2026">
                    <small>Ano de exercício do licenciamento (CRLV).</small>
                </div>

                <div class="form-divider"><span>Características Técnicas</span></div>

                <!-- Espécie / Tipo -->
                <div class="form-group">
                    <label for="species_type">
                        <i class="fa-solid fa-shapes"></i>
                        Espécie / Tipo:
                    </label>
                    <select id="species_type" name="species_type">
                        <option value="">Selecione...</option>
                        <option value="Passageiro" <?php echo $vehicle['species_type'] === 'Passageiro' ? 'selected' : ''; ?>>Passageiro</option>
                        <option value="Carga" <?php echo $vehicle['species_type'] === 'Carga' ? 'selected' : ''; ?>>Carga</option>
                        <option value="Misto" <?php echo $vehicle['species_type'] === 'Misto' ? 'selected' : ''; ?>>Misto</option>
                        <option value="Especial" <?php echo $vehicle['species_type'] === 'Especial' ? 'selected' : ''; ?>>Especial</option>
                        <option value="Outro" <?php echo $vehicle['species_type'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <!-- Carroceria -->
                <div class="form-group">
                    <label for="bodywork">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                        Carroceria:
                    </label>
                    <select id="bodywork" name="bodywork">
                        <option value="">Selecione...</option>
                        <option value="Baú" <?php echo $vehicle['bodywork'] === 'Baú' ? 'selected' : ''; ?>>Baú</option>
                        <option value="Sider" <?php echo $vehicle['bodywork'] === 'Sider' ? 'selected' : ''; ?>>Sider</option>
                        <option value="Grade Baixa" <?php echo $vehicle['bodywork'] === 'Grade Baixa' ? 'selected' : ''; ?>>Grade Baixa</option>
                        <option value="Furgão" <?php echo $vehicle['bodywork'] === 'Furgão' ? 'selected' : ''; ?>>Furgão</option>
                        <option value="Tanque" <?php echo $vehicle['bodywork'] === 'Tanque' ? 'selected' : ''; ?>>Tanque</option>
                        <option value="Aberta" <?php echo $vehicle['bodywork'] === 'Aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="Fechada" <?php echo $vehicle['bodywork'] === 'Fechada' ? 'selected' : ''; ?>>Fechada</option>
                        <option value="Porta-Container" <?php echo $vehicle['bodywork'] === 'Porta-Container' ? 'selected' : ''; ?>>Porta-Container</option>
                        <option value="Outro" <?php echo $vehicle['bodywork'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <!-- Peso Bruto Total + Capacidade -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="gross_weight">
                            <i class="fa-solid fa-weight-hanging"></i>
                            Peso Bruto Total (t):
                        </label>
                        <input
                            type="number"
                            id="gross_weight"
                            name="gross_weight"
                            min="0"
                            step="0.01"
                            value="<?php echo htmlspecialchars(fmtDec($vehicle['gross_weight'])); ?>"
                            placeholder="Ex: 3,50">
                    </div>

                    <div class="form-group">
                        <label for="capacity">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            Capacidade (t):
                        </label>
                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            min="0"
                            step="0.01"
                            value="<?php echo htmlspecialchars(fmtDec($vehicle['capacity'])); ?>"
                            placeholder="Ex: 1,20">
                    </div>
                </div>

                <!-- Potência / Cilindrada -->
                <div class="form-group">
                    <label for="power_displacement">
                        <i class="fa-solid fa-gauge-high"></i>
                        Potência / Cilindrada:
                    </label>
                    <input
                        type="text"
                        id="power_displacement"
                        name="power_displacement"
                        maxlength="50"
                        value="<?php echo htmlspecialchars($vehicle['power_displacement']); ?>"
                        placeholder="Ex: 150 cv / 2143 cc">
                </div>

                <!-- CMT -->
                <div class="form-group">
                    <label for="cmt">
                        <i class="fa-solid fa-draw-polygon"></i>
                        CMT (Capacidade Máxima de Tração):
                    </label>
                    <input
                        type="number"
                        id="cmt"
                        name="cmt"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars(fmtDec($vehicle['cmt'])); ?>"
                        placeholder="Ex: 5,00">
                    <small>Valor em toneladas.</small>
                </div>

                <!-- Eixos + Lotação -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="axles">
                            <i class="fa-solid fa-circle-dot"></i>
                            Eixos:
                        </label>
                        <input
                            type="number"
                            id="axles"
                            name="axles"
                            min="0"
                            value="<?php echo htmlspecialchars($vehicle['axles']); ?>"
                            placeholder="Ex: 2">
                    </div>

                    <div class="form-group">
                        <label for="occupancy">
                            <i class="fa-solid fa-user"></i>
                            Lotação:
                        </label>
                        <input
                            type="text"
                            id="occupancy"
                            name="occupancy"
                            maxlength="20"
                            value="<?php echo htmlspecialchars($vehicle['occupancy']); ?>"
                            placeholder="Ex: 7 passageiros">
                    </div>
                </div>

                <div class="form-divider"><span>Proprietário</span></div>

                <!-- CPF / CNPJ -->
                <div class="form-group">
                    <label for="owner_document">
                        <i class="fa-solid fa-id-card"></i>
                        CPF / CNPJ do Proprietário:
                    </label>
                    <input
                        type="text"
                        id="owner_document"
                        name="owner_document"
                        maxlength="20"
                        value="<?php echo htmlspecialchars($vehicle['owner_document']); ?>"
                        placeholder="Ex: 123.456.789-00">
                </div>

                <!-- Nome Proprietário -->
                <div class="form-group">
                    <label for="owner_name">
                        <i class="fa-solid fa-user-tie"></i>
                        Nome / Proprietário:
                    </label>
                    <input
                        type="text"
                        id="owner_name"
                        name="owner_name"
                        maxlength="150"
                        value="<?php echo htmlspecialchars($vehicle['owner_name']); ?>"
                        placeholder="Nome do proprietário do veículo">
                </div>

                <!-- Quilometragem -->
                <div class="form-divider"><span>Quilometragem e Status</span></div>
                <div class="form-group">
                    <label for="current_km">
                        <i class="fa-solid fa-gauge-simple"></i>
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