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
    'model_required'      => 'O campo Marca/Modelo é obrigatório.',
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
                <a href="../locais/locais_select.php"><i class="fa-solid fa-location-dot"></i> Locais</a>
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

                <div class="form-divider"><span>Foto do Veículo</span></div>

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
                        value="<?= oldv('plate_number', $old) ?>"
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
                        value="<?= oldv('model', $old) ?>"
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
                        value="<?= oldv('fancy_name', $old) ?>"
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
                        value="<?= oldv('renavam', $old) ?>"
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
                        value="<?= oldv('chassis_number', $old) ?>"
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
                            value="<?= oldv('year_model', $old) ?>"
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
                            value="<?= oldv('year_manufactured', $old) ?>"
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
                        <option value="Diesel" <?= oldv('fuel', $old) === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Gasolina" <?= oldv('fuel', $old) === 'Gasolina' ? 'selected' : ''; ?>>Gasolina</option>
                        <option value="Álcool" <?= oldv('fuel', $old) === 'Álcool' ? 'selected' : ''; ?>>Álcool</option>
                        <option value="Flex" <?= oldv('fuel', $old) === 'Flex' ? 'selected' : ''; ?>>Flex</option>
                        <option value="GNV" <?= oldv('fuel', $old) === 'GNV' ? 'selected' : ''; ?>>GNV</option>
                        <option value="GASOLINA/ALCOOL/GAS NATURAL" <?= oldv('fuel', $old) === 'GASOLINA/ALCOOL/GAS NATURAL' ? 'selected' : ''; ?>>GASOLINA/ALCOOL/GAS NATURAL</option>
                        <option value="GASOLINA/GAS NATURAL" <?= oldv('fuel', $old) === 'GASOLINA/GAS NATURAL' ? 'selected' : ''; ?>>GASOLINA/GAS NATURAL</option>
                        <option value="ALCOOL/GAS NATURAL" <?= oldv('fuel', $old) === 'ALCOOL/GAS NATURAL' ? 'selected' : ''; ?>>ALCOOL/GAS NATURAL</option>
                        <option value="Elétrico" <?= oldv('fuel', $old) === 'Elétrico' ? 'selected' : ''; ?>>Elétrico</option>
                        <option value="Híbrido" <?= oldv('fuel', $old) === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                        <option value="Outro" <?= oldv('fuel', $old) === 'Outro' ? 'selected' : ''; ?>>Outro</option>
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
                        value="<?= oldv('exercise_year', $old) ?>"
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
                        <option value="Passageiro" <?= oldv('species_type', $old) === 'Passageiro' ? 'selected' : ''; ?>>Passageiro</option>
                        <option value="Carga" <?= oldv('species_type', $old) === 'Carga' ? 'selected' : ''; ?>>Carga</option>
                        <option value="Misto" <?= oldv('species_type', $old) === 'Misto' ? 'selected' : ''; ?>>Misto</option>
                        <option value="Especial" <?= oldv('species_type', $old) === 'Especial' ? 'selected' : ''; ?>>Especial</option>
                        <option value="Outro" <?= oldv('species_type', $old) === 'Outro' ? 'selected' : ''; ?>>Outro</option>
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
                        <option value="Baú" <?= oldv('bodywork', $old) === 'Baú' ? 'selected' : ''; ?>>Baú</option>
                        <option value="Sider" <?= oldv('bodywork', $old) === 'Sider' ? 'selected' : ''; ?>>Sider</option>
                        <option value="Grade Baixa" <?= oldv('bodywork', $old) === 'Grade Baixa' ? 'selected' : ''; ?>>Grade Baixa</option>
                        <option value="Furgão" <?= oldv('bodywork', $old) === 'Furgão' ? 'selected' : ''; ?>>Furgão</option>
                        <option value="Tanque" <?= oldv('bodywork', $old) === 'Tanque' ? 'selected' : ''; ?>>Tanque</option>
                        <option value="Aberta" <?= oldv('bodywork', $old) === 'Aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="Fechada" <?= oldv('bodywork', $old) === 'Fechada' ? 'selected' : ''; ?>>Fechada</option>
                        <option value="Porta-Container" <?= oldv('bodywork', $old) === 'Porta-Container' ? 'selected' : ''; ?>>Porta-Container</option>
                        <option value="Outro" <?= oldv('bodywork', $old) === 'Outro' ? 'selected' : ''; ?>>Outro</option>
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
                            value="<?= oldv('gross_weight', $old) ?>"
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
                            value="<?= oldv('capacity', $old) ?>"
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
                        value="<?= oldv('power_displacement', $old) ?>"
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
                        value="<?= oldv('cmt', $old) ?>"
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
                            value="<?= oldv('axles', $old) ?>"
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
                            value="<?= oldv('occupancy', $old) ?>"
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
                        value="<?= oldv('owner_document', $old) ?>"
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
                        value="<?= oldv('owner_name', $old) ?>"
                        placeholder="Nome do proprietário do veículo">
                </div>

                <!-- Quilometragem Atual -->
                <div class="form-divider"><span>Quilometragem Atual</span></div>
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