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

$successMessage = null;
$errorMessage = null;

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':     $successMessage = 'Veículo cadastrado com sucesso!'; break;
        case 'updated':        $successMessage = 'Veículo atualizado com sucesso!'; break;
        case 'deleted':        $successMessage = 'Veículo removido permanentemente do sistema.'; break;
        case 'status_changed': $successMessage = 'Status do veículo alterado com sucesso!'; break;
        default:               $successMessage = 'Operação realizada com sucesso.'; break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'vehicle_not_found': $errorMessage = 'Veículo não encontrado.'; break;
        case 'server_error':      $errorMessage = 'Erro no servidor. Tente novamente.'; break;
        default:                  $errorMessage = 'Ocorreu um erro. Tente novamente.'; break;
    }
}

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT id, plate_number, model, status, current_km, photo_url, created_at, updated_at
        FROM vehicles
        WHERE plate_number LIKE ? OR model LIKE ?
        ORDER BY plate_number ASC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT id, plate_number, model, status, current_km, photo_url, created_at, updated_at
        FROM vehicles
        ORDER BY plate_number ASC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$vehicles = $result->fetch_all(MYSQLI_ASSOC);
$total = count($vehicles);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Consulta de Veículos</title>
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
                <a class="active" href="vehicles_select.php"><i class="fa-solid fa-truck"></i> Frota</a>
                <a href="#"><i class="fa-solid fa-route"></i> Rota</a>
            </nav>
            <a href="../../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </aside>

        <main class="content">

            <h1>
                <i class="fa-solid fa-magnifying-glass"></i>
                Consulta de Veículos
            </h1>
            <p>Visualize, edite ou remova veículos cadastrados no sistema.</p>

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
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por placa ou modelo..."
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
                    — <a href="vehicles_consult.php" style="color: #2c7da0;">Limpar busca</a>
                </div>
            <?php endif; ?>

            <?php if ($total > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>KM Atual</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($vehicle['photo_url'])): ?>
                                        <img
                                            src="../../<?php echo htmlspecialchars($vehicle['photo_url']); ?>"
                                            alt="<?php echo htmlspecialchars($vehicle['model']); ?>"
                                            class="user-photo<?php echo $vehicle['status'] === 'desligado' ? ' photo-desligado' : ''; ?>">
                                    <?php else: ?>
                                        <div class="user-photo-placeholder<?php echo $vehicle['status'] === 'desligado' ? ' photo-desligado' : ''; ?>">
                                            <i class="fa-solid fa-truck"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                <td><?php echo number_format($vehicle['current_km'], 0, ',', '.'); ?> km</td>
                                <td>
                                    <span class="status-badge status-<?php echo $vehicle['status']; ?>">
                                        <?php echo $vehicle['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button
                                            type="button"
                                            class="btn-table btn-table-consult"
                                            title="Consultar veículo"
                                            onclick='abrirModalConsulta(<?php echo json_encode($vehicle, JSON_HEX_APOS | JSON_HEX_TAG); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <span class="btn-label">Consultar</span>
                                        </button>

                                        <a href="vehicles_edit.php?id=<?php echo $vehicle['id']; ?>" class="btn-table btn-table-edit" title="Editar veículo">
                                            <i class="fa-solid fa-pen-to-square"></i> <span class="btn-label">Editar</span>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn-table btn-table-delete"
                                            title="Excluir veículo"
                                            onclick="abrirModal(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars(addslashes($vehicle['plate_number'] . ' - ' . $vehicle['model']), ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fa-solid fa-trash-can"></i> <span class="btn-label">Excluir</span>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn-table btn-table-status"
                                            title="Mudar status do veículo"
                                            onclick="abrirModalStatus(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars(addslashes($vehicle['plate_number'] . ' - ' . $vehicle['model']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $vehicle['status']; ?>')">
                                            <i class="fa-solid fa-toggle-on"></i> <span class="btn-label">Status</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <i class="fa-solid fa-truck-slash"></i>
                    Nenhum veículo encontrado.
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
                <div class="consulta-photo-wrapper">
                    <img id="consultaFoto" src="" alt="Foto do veículo" class="consulta-photo">
                    <div id="consultaFotoPlaceholder" class="consulta-photo-placeholder">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <span id="consultaStatusBadge" class="consulta-status-dot"></span>
                </div>
                <h2 id="consultaPlaca"></h2>
                <span id="consultaModelo" class="consulta-role-badge"></span>
            </div>

            <div class="consulta-body">

                <div class="consulta-section">
                    <h3><i class="fa-solid fa-truck"></i> Informações do Veículo</h3>
                    <div class="consulta-grid">
                        <div class="consulta-field">
                            <label>Placa</label>
                            <span id="consultaPlacaDetalhe"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Modelo</label>
                            <span id="consultaModeloDetalhe"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Quilometragem Atual</label>
                            <span id="consultaKM"></span>
                        </div>
                        <div class="consulta-field">
                            <label>Status</label>
                            <span id="consultaStatusDetalhe"></span>
                        </div>
                    </div>
                </div>

                <div class="consulta-section">
                    <h3><i class="fa-solid fa-gear"></i> Sistema</h3>
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
                        <strong>Editar Veículo</strong>
                        <small>Alterar informações deste veículo</small>
                    </span>
                </a>
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModalConsulta()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Fechar</strong>
                        <small>Voltar para a lista de veículos</small>
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
            <p class="modal-user-name" id="modalVehicleName"></p>
            <p class="modal-desc">
                Escolha uma das opções abaixo para continuar.
            </p>

            <div class="modal-actions">
                <form method="POST" action="process_delete_vehicle.php">
                    <input type="hidden" name="vehicle_id" id="deleteVehicleId">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="modal-btn modal-btn-deactivate">
                        <span class="btn-icon"><i class="fa-solid fa-ban"></i></span>
                        <span class="btn-info">
                            <strong>Desativar veículo</strong>
                            <small>O status será alterado para "Inativo"</small>
                        </span>
                    </button>
                </form>

                <form method="POST" action="process_delete_vehicle.php">
                    <input type="hidden" name="vehicle_id" id="deleteVehicleId2">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="modal-btn modal-btn-delete">
                        <span class="btn-icon"><i class="fa-solid fa-trash-can"></i></span>
                        <span class="btn-info">
                            <strong>Eliminar definitivamente</strong>
                            <small>Remove o veículo permanentemente do banco de dados</small>
                        </span>
                    </button>
                </form>

                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModal()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Cancelar</strong>
                        <small>Fechar e manter o veículo como está</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL STATUS -->
    <div class="modal-overlay" id="modalStatus">
        <div class="modal-box">
            <div class="modal-icon" style="background: #d4edda; color: #2c7da0;">
                <i class="fa-solid fa-toggle-on"></i>
            </div>
            <h2>Mudar Status</h2>
            <p class="modal-user-name" id="modalStatusVehicleName"></p>
            <p class="modal-desc">
                Selecione o novo status para este veículo.
            </p>

            <form method="POST" action="process_change_status_vehicle.php">
                <input type="hidden" name="vehicle_id" id="statusVehicleId">
                <select name="new_status" id="statusSelect" class="modal-status-select">
                    <option value="ativo">Ativo</option>
                    <option value="desligado">Inativo</option>
                </select>

                <div class="modal-actions">
                    <button type="submit" class="modal-btn modal-btn-confirm">
                        <span class="btn-icon"><i class="fa-solid fa-check"></i></span>
                        <span class="btn-info">
                            <strong>Salvar</strong>
                            <small>Confirmar a mudança de status</small>
                        </span>
                    </button>
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModalStatus()">
                        <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                        <span class="btn-info">
                            <strong>Cancelar</strong>
                            <small>Fechar sem alterar o status</small>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/menu.js"></script>
    <script>
        function abrirModalConsulta(vehicle) {
            var statusColors = { 'ativo': '#28a745', 'desligado': '#c0392b' };

            var foto = document.getElementById('consultaFoto');
            var placeholder = document.getElementById('consultaFotoPlaceholder');

            if (vehicle.photo_url) {
                foto.src = '../../' + vehicle.photo_url;
                foto.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                foto.style.display = 'none';
                placeholder.style.display = 'flex';
            }

            var dot = document.getElementById('consultaStatusBadge');
            dot.style.background = statusColors[vehicle.status] || '#999';

            document.getElementById('consultaPlaca').textContent = vehicle.plate_number;
            document.getElementById('consultaModelo').textContent = vehicle.model;
            document.getElementById('consultaPlacaDetalhe').textContent = vehicle.plate_number;
            document.getElementById('consultaModeloDetalhe').textContent = vehicle.model;
            document.getElementById('consultaKM').textContent = parseInt(vehicle.current_km).toLocaleString('pt-BR') + ' km';

            var statusEl = document.getElementById('consultaStatusDetalhe');
            statusEl.textContent = vehicle.status === 'ativo' ? 'Ativo' : 'Inativo';
            statusEl.style.color = statusColors[vehicle.status] || '#999';
            statusEl.style.fontWeight = '600';

            document.getElementById('consultaCriadoEm').textContent = formatarDataHora(vehicle.created_at);
            document.getElementById('consultaAtualizadoEm').textContent = formatarDataHora(vehicle.updated_at);
            document.getElementById('consultaEditarLink').href = 'vehicles_edit.php?id=' + vehicle.id;

            document.getElementById('modalConsulta').classList.add('active');
            document.body.classList.add('no-scroll');
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

        function abrirModal(vehicleId, vehicleName) {
            document.getElementById('modalVehicleName').textContent = vehicleName;
            document.getElementById('deleteVehicleId').value = vehicleId;
            document.getElementById('deleteVehicleId2').value = vehicleId;
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

        function abrirModalStatus(vehicleId, vehicleName, currentStatus) {
            document.getElementById('modalStatusVehicleName').textContent = vehicleName;
            document.getElementById('statusVehicleId').value = vehicleId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('modalStatus').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        function fecharModalStatus() {
            document.getElementById('modalStatus').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        document.getElementById('modalStatus').addEventListener('click', function(e) {
            if (e.target === this) fecharModalStatus();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalConsulta();
                fecharModal();
                fecharModalStatus();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
