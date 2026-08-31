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

$tipo    = ($_GET['tipo'] ?? '') === 'externo' ? 'externo' : 'loja';
$ehExterno = ($tipo === 'externo');
$localId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errorMessages = [
    'db_connection'     => 'Erro de conexão com o banco de dados. Tente novamente.',
    'name_required'     => 'O campo Nome é obrigatório.',
    'category_invalid'  => 'Selecione uma categoria válida para o local externo.',
    'lat_invalid'       => 'A latitude informada não é válida.',
    'lng_invalid'       => 'A longitude informada não é válida.',
    'maps_invalid'      => 'O link do Google Maps informado não é válido.',
    'upload_error'      => 'Ocorreu um erro ao enviar a foto. Tente novamente.',
    'image_too_large'   => 'A foto enviada é muito grande (máximo 5MB).',
    'invalid_image'     => 'O arquivo enviado não é uma imagem válida (use JPG, JPEG, PNG ou WEBP).',
    'server_error'      => 'Erro no servidor ao atualizar o local. Tente novamente.',
    'local_not_found'   => 'Local não encontrado.',
];

$errorMessage = null;

if (isset($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMessage = $errorMessages[$_GET['error']];
} elseif (isset($_GET['error'])) {
    $errorMessage = 'Erro ao atualizar o local. Tente novamente.';
}

if ($localId <= 0) {
    header("Location: locais_consult.php?tipo=$tipo");
    exit();
}

$colunas = $ehExterno
    ? "
        SELECT id, name, address, city, state, latitude, longitude, type AS categoria, maps_url, image_url, created_at, updated_at
        FROM destinations
        WHERE id = ?
      "
    : "
        SELECT id, name, address, city, state, latitude, longitude, NULL AS categoria, maps_url, image_url, created_at, updated_at
        FROM stores
        WHERE id = ?
      ";

$stmt = $conn->prepare($colunas);
$stmt->bind_param("i", $localId);
$stmt->execute();
$result = $stmt->get_result();
$local = $result->fetch_assoc();
$stmt->close();

if (!$local) {
    header("Location: locais_consult.php?tipo=$tipo&error=local_not_found");
    exit();
}

function fmtDecimal($value) {
    if ($value === null || $value === '') return '';
    return str_replace('.', ',', (string)$value);
}

$ufList = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Local - Calebito</title>
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
                <a href="../vehicles/vehicles_select.php"><i class="fa-solid fa-truck"></i> Frota</a>
                <a class="active" href="locais_select.php"><i class="fa-solid fa-location-dot"></i> Locais</a>
                <a href="#"><i class="fa-solid fa-route"></i> Rota</a>
            </nav>
            <a href="../../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </aside>

        <main class="content">

            <h1>
                <i class="fa-solid fa-pen-to-square"></i>
                Editar Local
            </h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form
                action="process_edit.php"
                method="POST"
                enctype="multipart/form-data"
                class="register-form"
                id="formLocais">

                <input type="hidden" name="local_id" value="<?php echo $local['id']; ?>">
                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">

                <div class="form-divider"><span>Tipo de Local</span></div>

                <div class="form-group">
                    <label for="tipoDisplay">
                        <i class="fa-solid fa-shapes"></i>
                        Tipo de Local:
                    </label>
                    <input
                        type="text"
                        id="tipoDisplay"
                        value="<?php echo $ehExterno ? 'Local Externo' : 'Loja Própria'; ?>"
                        disabled>
                </div>

                <?php if ($ehExterno): ?>
                <div class="form-group" id="categoriaGroup">
                    <label for="categoria">
                        <i class="fa-solid fa-tags"></i>
                        Categoria:
                    </label>
                    <select id="categoria" name="categoria">
                        <option value="">Selecione...</option>
                        <option value="Cliente" <?= $local['categoria'] === 'Cliente' ? 'selected' : ''; ?>>Cliente</option>
                        <option value="Franqueado" <?= $local['categoria'] === 'Franqueado' ? 'selected' : ''; ?>>Franqueado</option>
                        <option value="Revendedor" <?= $local['categoria'] === 'Revendedor' ? 'selected' : ''; ?>>Revendedor</option>
                        <option value="Centro de Distribuição" <?= $local['categoria'] === 'Centro de Distribuição' ? 'selected' : ''; ?>>Centro de Distribuição</option>
                        <option value="Fornecedor" <?= $local['categoria'] === 'Fornecedor' ? 'selected' : ''; ?>>Fornecedor</option>
                        <option value="Outro" <?= $local['categoria'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-divider"><span>Dados do Local</span></div>

                <div class="form-group">
                    <label for="name">
                        <i class="fa-solid fa-signature"></i>
                        Nome:
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        maxlength="150"
                        value="<?php echo htmlspecialchars($local['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Ex: Loja Centro">
                </div>

                <div class="form-group">
                    <label for="address">
                        <i class="fa-solid fa-location-arrow"></i>
                        Endereço:
                    </label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        maxlength="255"
                        value="<?php echo htmlspecialchars($local['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Ex: Rua das Flores, 123 - Centro">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="city">
                            <i class="fa-solid fa-city"></i>
                            Cidade:
                        </label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($local['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ex: São Paulo">
                    </div>

                    <div class="form-group">
                        <label for="state">
                            <i class="fa-solid fa-map"></i>
                            UF:
                        </label>
                        <select id="state" name="state">
                            <option value="">Selecione...</option>
                            <?php foreach ($ufList as $uf): ?>
                                <option value="<?= $uf; ?>" <?= $local['state'] === $uf ? 'selected' : ''; ?>><?= $uf; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-divider"><span>Geolocalização (opcional)</span></div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="latitude">
                            <i class="fa-solid fa-latitude"></i>
                            Latitude:
                        </label>
                        <input
                            type="number"
                            id="latitude"
                            name="latitude"
                            min="-90"
                            max="90"
                            step="0.0000001"
                            value="<?php echo fmtDecimal($local['latitude']); ?>"
                            placeholder="Ex: -23,5505201">
                    </div>

                    <div class="form-group">
                        <label for="longitude">
                            <i class="fa-solid fa-longitude"></i>
                            Longitude:
                        </label>
                        <input
                            type="number"
                            id="longitude"
                            name="longitude"
                            min="-180"
                            max="180"
                            step="0.0000001"
                            value="<?php echo fmtDecimal($local['longitude']); ?>"
                            placeholder="Ex: -46,6333084">
                    </div>
                </div>

                <div class="form-group">
                    <label for="maps_url">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Link do Google Maps:
                    </label>
                    <input
                        type="url"
                        id="maps_url"
                        name="maps_url"
                        maxlength="255"
                        value="<?php echo htmlspecialchars($local['maps_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="https://maps.app.goo.gl/...">
                </div>

                <div class="form-divider"><span>Foto do Local</span></div>

                <?php if (!empty($local['image_url'])): ?>
                    <div class="form-group">
                        <img
                            src="../../<?php echo htmlspecialchars($local['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($local['name']); ?>"
                            class="vehicle-photo">
                        <small>Foto atual. Envie uma nova foto acima para substituí-la.</small>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="photo">
                        <i class="fa-solid fa-image"></i>
                        Nova Foto:
                    </label>
                    <input
                        type="file"
                        id="photo"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp,image/*">
                    <small>Formatos permitidos: JPG, JPEG, PNG e WEBP. Deixe vazio para manter a foto atual.</small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-save"></i>
                    Salvar Alterações
                </button>

                <div class="form-links">
                    <a href="locais_consult.php?tipo=<?php echo $tipo; ?>">
                        <i class="fa-solid fa-arrow-left"></i> Voltar para a lista
                    </a>
                </div>

            </form>

        </main>

    </div>

    <script src="../js/menu.js"></script>
</body>
</html>