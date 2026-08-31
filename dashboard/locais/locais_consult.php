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

$tipo = ($_GET['tipo'] ?? '') === 'externo' ? 'externo' : 'loja';
$ehExterno = ($tipo === 'externo');
$titulo = $ehExterno ? 'Consulta de Locais Externos' : 'Consulta de Lojas Próprias';
$icone = $ehExterno ? 'fa-truck-ramp-box' : 'fa-store';

$successMessage = null;
$errorMessage = null;

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':     $successMessage = 'Local cadastrado com sucesso!'; break;
        case 'updated':        $successMessage = 'Local atualizado com sucesso!'; break;
        case 'deleted':        $successMessage = 'Local removido permanentemente do sistema.'; break;
        default:               $successMessage = 'Operação realizada com sucesso.'; break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'local_not_found': $errorMessage = 'Local não encontrado.'; break;
        case 'server_error':    $errorMessage = 'Erro no servidor. Tente novamente.'; break;
        default:                $errorMessage = 'Ocorreu um erro. Tente novamente.'; break;
    }
}

$search = trim($_GET['search'] ?? '');
$like = "%" . $search . "%";

if ($ehExterno) {
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT id, name, address, city, state, latitude, longitude, type, maps_url, image_url, created_at, updated_at
            FROM destinations
            WHERE name LIKE ? OR address LIKE ? OR type LIKE ?
            ORDER BY name ASC
        ");
        $stmt->bind_param("sss", $like, $like, $like);
    } else {
        $stmt = $conn->prepare("
            SELECT id, name, address, city, state, latitude, longitude, type, maps_url, image_url, created_at, updated_at
            FROM destinations
            ORDER BY name ASC
        ");
    }
} else {
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT id, name, address, city, state, latitude, longitude, maps_url, image_url, created_at, updated_at
            FROM stores
            WHERE name LIKE ? OR address LIKE ?
            ORDER BY name ASC
        ");
        $stmt->bind_param("ss", $like, $like);
    } else {
        $stmt = $conn->prepare("
            SELECT id, name, address, city, state, latitude, longitude, maps_url, image_url, created_at, updated_at
            FROM stores
            ORDER BY name ASC
        ");
    }
}

$stmt->execute();
$result = $stmt->get_result();
$locais = $result->fetch_all(MYSQLI_ASSOC);
$total = count($locais);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - <?php echo $titulo; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/favicon.png?v=2">
    <link rel="stylesheet" href="../css/style.css?v=3">
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
                <i class="fa-solid <?php echo $icone; ?>"></i>
                <?php echo $titulo; ?>
            </h1>
            <p>Visualize, edite ou remova <?php echo $ehExterno ? 'os locais externos' : 'as lojas próprias'; ?> cadastradas no sistema.</p>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form class="search-bar" method="GET" action="">
                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                <input
                    type="text"
                    name="search"
                    placeholder="<?php echo $ehExterno ? 'Buscar por nome, endereço ou categoria...' : 'Buscar por nome ou endereço...'; ?>"
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </form>

            <?php if (!empty($search)): ?>
                <div class="results-info">
                    Encontrados <strong><?php echo $total; ?></strong>
                    <?php echo $total === 1 ? 'resultado' : 'resultados'; ?>
                    para "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    — <a href="locais_consult.php?tipo=<?php echo $tipo; ?>" style="color: #2c7da0;">Limpar busca</a>
                </div>
            <?php endif; ?>

            <?php if ($total > 0): ?>

                <!-- LEGENDA DOS BOTÕES (visível apenas em telas pequenas) -->
                <div class="actions-legend">
                    <span><i class="fa-solid fa-eye"></i> Consultar</span>
                    <span><i class="fa-solid fa-pen-to-square"></i> Editar</span>
                    <span><i class="fa-solid fa-trash-can"></i> Excluir</span>
                </div>

                <table class="users-table locais-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th><?php echo $ehExterno ? 'Categoria' : 'Cidade/UF'; ?></th>
                            <th><?php echo $ehExterno ? 'Cidade/UF' : 'Endereço'; ?></th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locais as $local): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($local['image_url'])): ?>
                                        <img
                                            src="../../<?php echo htmlspecialchars($local['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($local['name']); ?>"
                                            class="vehicle-photo">
                                    <?php else: ?>
                                        <div class="vehicle-photo-placeholder">
                                            <i class="fa-solid <?php echo $icone; ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($local['name']); ?></td>
                                <td>
                                    <?php
                                        if ($ehExterno) {
                                            echo htmlspecialchars($local['type'] ?? '—');
                                        } else {
                                            echo htmlspecialchars(trim(($local['city'] ?? '') . ' ' . ($local['state'] ?? '')), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars(trim(($local['city'] ?? '') . ' - ' . ($local['state'] ?? '')), ENT_QUOTES, 'UTF-8') : '—';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php echo $ehExterno
                                        ? htmlspecialchars(trim(($local['city'] ?? '') . ' - ' . ($local['state'] ?? '')), ENT_QUOTES, 'UTF-8')
                                        : htmlspecialchars($local['address'] ?? '—'); ?>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button
                                            type="button"
                                            class="btn-table btn-table-consult"
                                            title="Consultar local"
                                            onclick='abrirModalConsulta(<?php echo json_encode($local, JSON_HEX_APOS | JSON_HEX_TAG); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <span class="btn-label">Consultar</span>
                                        </button>

                                        <a href="locais_edit.php?tipo=<?php echo $tipo; ?>&id=<?php echo $local['id']; ?>" class="btn-table btn-table-edit" title="Editar local">
                                            <i class="fa-solid fa-pen-to-square"></i> <span class="btn-label">Editar</span>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn-table btn-table-delete"
                                            title="Excluir local"
                                            onclick="abrirModal(<?php echo $local['id']; ?>, '<?php echo htmlspecialchars(addslashes($local['name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fa-solid fa-trash-can"></i> <span class="btn-label">Excluir</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <i class="fa-solid <?php echo $icone; ?>"></i>
                    Nenhum <?php echo $ehExterno ? 'local externo' : 'local'; ?> encontrado.
                </div>
            <?php endif; ?>

        </main>

    </div>

    <!-- MODAL CONSULTA -->
    <div class="modal-overlay" id="modalConsulta">
        <div class="modal-box modal-box-consulta">
            <button type="button" class="modal-close-btn" onclick="fecharModalConsulta()">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="consulta-header">
                <div class="vehicle-consulta-photo-wrapper">
                    <img id="consultaFoto" src="" alt="Foto do local" class="vehicle-consulta-photo">
                    <div id="consultaFotoPlaceholder" class="vehicle-consulta-photo-placeholder">
                        <i class="fa-solid <?php echo $icone; ?>"></i>
                    </div>
                </div>
                <h2 id="consultaNome"></h2>
                <span id="consultaCidadeBadge" class="consulta-role-badge"></span>
            </div>

            <div class="consulta-body">

                <div class="consulta-section">
                    <h3><i class="fa-solid fa-location-dot"></i> Informações do Local</h3>
                    <div class="consulta-grid">
                        <div class="consulta-field">
                            <label><?php echo $ehExterno ? 'Categoria' : 'Tipo'; ?></label>
                            <span id="consultaTipo"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Endereço</label>
                            <span id="consultaEndereco"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Cidade</label>
                            <span id="consultaCidade"></span>
                        </div>
                        <div class="consulta-field">
                            <label>UF</label>
                            <span id="consultaUF"></span>
                        </div>
                    </div>
                </div>

                <div class="consulta-section">
                    <h3><i class="fa-solid fa-map-location-dot"></i> Geolocalização</h3>
                    <div class="consulta-grid">
                        <div class="consulta-field">
                            <label>Latitude</label>
                            <span id="consultaLatitude"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Longitude</label>
                            <span id="consultaLongitude"></span>
                        </div>
                        <div class="consulta-field consulta-field-full">
                            <label>Link do Google Maps</label>
                            <span id="consultaMapsUrl"></span>
                        </div>
                    </div>
                </div>

                <div class="consulta-section">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Sistema</h3>
                    <div class="consulta-grid">
                        <div class="consulta-field">
                            <label>Cadastrado em</label>
                            <span id="consultaCriadoEm"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Última atualização</label>
                            <span id="consultaAtualizadoEm"></span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="consulta-actions">
                <a id="consultaEditarLink" href="#" class="modal-btn modal-btn-confirm">
                    <span class="btn-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                    <span class="btn-info">
                        <strong>Editar Local</strong>
                        <small>Alterar informações deste local</small>
                    </span>
                </a>
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModalConsulta()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Fechar</strong>
                        <small>Voltar para a lista de locais</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL EXCLUSÃO -->
    <div class="modal-overlay" id="modalExcluir">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2>Tem certeza?</h2>
            <p class="modal-user-name" id="modalLocalName"></p>
            <p class="modal-desc">
                Escolha uma das opções abaixo para continuar.
            </p>

            <div class="modal-actions">
                <form method="POST" action="process_delete.php">
                    <input type="hidden" name="local_id" id="deleteLocalId">
                    <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                    <button type="submit" class="modal-btn modal-btn-delete">
                        <span class="btn-icon"><i class="fa-solid fa-trash-can"></i></span>
                        <span class="btn-info">
                            <strong>Eliminar definitivamente</strong>
                            <small>Remove o local permanentemente do banco de dados</small>
                        </span>
                    </button>
                </form>

                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModal()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Cancelar</strong>
                        <small>Fechar e manter o local como está</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script src="../js/menu.js"></script>
    <script>
        var ehExterno = <?php echo $ehExterno ? 'true' : 'false'; ?>;

        function abrirModalConsulta(local) {
            var foto = document.getElementById('consultaFoto');
            var placeholder = document.getElementById('consultaFotoPlaceholder');

            if (local.image_url) {
                foto.src = '../../' + local.image_url;
                foto.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                foto.style.display = 'none';
                placeholder.style.display = 'flex';
            }

            document.getElementById('consultaNome').textContent = local.name || '—';

            var cidadeUf = (local.city || '') + (local.state ? ' - ' + local.state : '');
            document.getElementById('consultaCidadeBadge').textContent = cidadeUf.trim() || 'Local';
            document.getElementById('consultaTipo').textContent = ehExterno ? (local.type || '—') : 'Loja Própria';
            document.getElementById('consultaEndereco').textContent = local.address || '—';
            document.getElementById('consultaCidade').textContent = local.city || '—';
            document.getElementById('consultaUF').textContent = local.state || '—';
            document.getElementById('consultaLatitude').textContent = fmtDecBr(local.latitude) || '—';
            document.getElementById('consultaLongitude').textContent = fmtDecBr(local.longitude) || '—';

            var mapsSpan = document.getElementById('consultaMapsUrl');
            if (local.maps_url) {
                mapsSpan.innerHTML = '<a href="' + local.maps_url + '" target="_blank" rel="noopener">Abrir no Google Maps <i class="fa-solid fa-arrow-up-right-from-square"></i></a>';
            } else {
                mapsSpan.textContent = '—';
            }

            document.getElementById('consultaCriadoEm').textContent = formatarDataHora(local.created_at);
            document.getElementById('consultaAtualizadoEm').textContent = formatarDataHora(local.updated_at);
            document.getElementById('consultaEditarLink').href = 'locais_edit.php?tipo=' + (ehExterno ? 'externo' : 'loja') + '&id=' + local.id;

            document.getElementById('modalConsulta').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        function fmtDecBr(v) {
            if (v === null || v === undefined || v === '') return '';
            return String(v).replace('.', ',');
        }

        function formatarDataHora(dataStr) {
            if (!dataStr) return '—';
            var d = new Date(dataStr);
            if (isNaN(d.getTime())) return dataStr;
            var dia = String(d.getDate()).padStart(2, '0');
            var mes = String(d.getMonth() + 1).padStart(2, '0');
            var ano = d.getFullYear();
            var hora = String(d.getHours()).padStart(2, '0');
            var min = String(d.getMinutes()).padStart(2, '0');
            return dia + '/' + mes + '/' + ano + ' às ' + hora + ':' + min;
        }

        function fecharModalConsulta() {
            document.getElementById('modalConsulta').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        document.getElementById('modalConsulta').addEventListener('click', function(e) {
            if (e.target === this) fecharModalConsulta();
        });

        function abrirModal(localId, localName) {
            document.getElementById('modalLocalName').textContent = localName;
            document.getElementById('deleteLocalId').value = localId;
            document.getElementById('modalExcluir').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        function fecharModal() {
            document.getElementById('modalExcluir').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        document.getElementById('modalExcluir').addEventListener('click', function(e) {
            if (e.target === this) fecharModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalConsulta();
                fecharModal();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>