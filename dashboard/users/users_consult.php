<?php
session_start();

/*
|--------------------------------------------------------------------------
| VERIFICAR SESSÃO
|--------------------------------------------------------------------------
| Redireciona para o login caso o usuário não esteja autenticado.
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
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
| Exibe mensagens de feedback após ações de edição ou exclusão.
*/

$successMessage = null;
$errorMessage = null;

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'updated':        $successMessage = 'Usuário atualizado com sucesso!'; break;
        case 'registered':     $successMessage = 'Usuário cadastrado com sucesso!'; break;
        case 'status_changed': $successMessage = 'Status do usuário alterado com sucesso!'; break;
        case 'deactivated':    $successMessage = 'Usuário desligado com sucesso!'; break;
        case 'deleted':        $successMessage = 'Usuário removido permanentemente do sistema.'; break;
        default:               $successMessage = 'Operação realizada com sucesso.'; break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_not_found': $errorMessage = 'Usuário não encontrado.'; break;
        case 'self_delete':    $errorMessage = 'Você não pode excluir seu próprio usuário.'; break;
        case 'server_error':   $errorMessage = 'Erro no servidor. Tente novamente.'; break;
        default:               $errorMessage = 'Ocorreu um erro. Tente novamente.'; break;
    }
}

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
        SELECT id, name, lastname, email, phone, cep, address, cnh, birth_day, role, status, photo_url, created_at, updated_at
        FROM users
        WHERE name LIKE ? OR lastname LIKE ? OR email LIKE ? OR phone LIKE ? OR cep LIKE ?
        ORDER BY name ASC
    ");
    $like = "%" . $search . "%";
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT id, name, lastname, email, phone, cep, address, cnh, birth_day, role, status, photo_url, created_at, updated_at
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
$current_user_id = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calebito - Consulta de Usuários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../../../img/favicon.png?v=2">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
</head>
<body>

    <!-- CABEÇALHO -->
    <header class="header">
        <div class="header-left">
            <img src="../../../img/logo-light.svg" alt="Calebito" class="logo">

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

    <!-- OVERLAY DO MENU MOBILE -->
    <div class="overlay" id="overlay"></div>

    <!-- LAYOUT PRINCIPAL -->
    <div class="layout">

        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <nav>
                <a href="../index.php"><i class="fa-solid fa-house"></i> Início</a>
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

            <a href="../../auth/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="content">

            <!-- TÍTULO DA PÁGINA -->
            <h1>
                <i class="fa-solid fa-magnifying-glass"></i>
                Consulta de Usuários
            </h1>
            <p>Visualize, edite ou remova usuários cadastrados no sistema.</p>

            <!-- MENSAGENS DE FEEDBACK -->
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

            <!-- BARRA DE BUSCA -->
            <form class="search-bar" method="GET" action="">
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por nome, sobrenome, e-mail, telefone ou CEP..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </form>

            <!-- INFORMAÇÃO DOS RESULTADOS DA BUSCA -->
            <?php if (!empty($search)): ?>
                <div class="results-info">
                    Encontrados <strong><?php echo $total; ?></strong>
                    <?php echo $total === 1 ? 'resultado' : 'resultados'; ?>
                    para "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    — <a href="users_consult.php" style="color: #2c7da0;">Limpar busca</a>
                </div>
            <?php endif; ?>

            <!-- TABELA DE USUÁRIOS COM AÇÕES -->
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
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $isCurrentUser = ((int)$user['id'] === $current_user_id); ?>
                            <tr>
                                <!-- FOTO DO USUÁRIO -->
                                <td>
                                    <?php if (!empty($user['photo_url'])): ?>
                                        <img
                                            src="../../<?php echo htmlspecialchars($user['photo_url']); ?>"
                                            alt="<?php echo htmlspecialchars($user['name']); ?>"
                                            class="user-photo<?php echo $user['status'] === 'desligado' ? ' photo-desligado' : ''; ?>">
                                    <?php else: ?>
                                        <div class="user-photo-placeholder<?php echo $user['status'] === 'desligado' ? ' photo-desligado' : ''; ?>">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: '—'); ?></td>
                                <!-- FUNÇÃO DO USUÁRIO -->
                                <td>
                                    <span class="role-badge">
                                        <?php
                                        $roleLabels = [
                                            'motorista'          => '🚛 Motorista',
                                            'ajudante'           => '🛄 Ajudante',
                                            'gestor_logistica'   => '📊 Gestor de Logística',
                                            'socio_proprietario' => '🏢 Sócio-Proprietário',
                                            'lider_setor'        => '👔 Líder de Setor',
                                            'gerente_loja'       => '🏪 Gerente de Loja',
                                            'sub_gerente'        => '📋 Sub-gerente',
                                            'estoque'            => '📦 Estoquista',
                                            'gestor_eventos'     => '🎪 Gestor de Eventos',
                                        ];
                                        echo htmlspecialchars($roleLabels[$user['role']] ?? ucfirst($user['role']));
                                        ?>
                                    </span>
                                </td>
                                <!-- STATUS DO USUÁRIO -->
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <!-- BOTÕES DE AÇÃO: EDITAR, EXCLUIR E STATUS -->
                                <td>
                                    <div class="btn-action-group">
                                        <!-- Botão Consultar: sempre disponível -->
                                        <button
                                            type="button"
                                            class="btn-table btn-table-consult"
                                            title="Consultar usuário"
                                            onclick='abrirModalConsulta(<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_TAG); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <span class="btn-label">Consultar</span>
                                        </button>

                                        <!-- Botão Editar: sempre disponível -->
                                        <a href="users_edit.php?id=<?php echo $user['id']; ?>" class="btn-table btn-table-edit" title="Editar usuário">
                                            <i class="fa-solid fa-pen-to-square"></i> <span class="btn-label">Editar</span>
                                        </a>

                                        <!-- Botão Excluir: não exibe para o próprio usuário -->
                                        <?php if (!$isCurrentUser): ?>
                                            <button
                                                type="button"
                                                class="btn-table btn-table-delete"
                                                title="Excluir usuário"
                                                onclick="abrirModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'] . ' ' . $user['lastname']), ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="fa-solid fa-trash-can"></i> <span class="btn-label">Excluir</span>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Botão Status: não exibe para o próprio usuário -->
                                        <?php if (!$isCurrentUser): ?>
                                            <button
                                                type="button"
                                                class="btn-table btn-table-status"
                                                title="Mudar status do usuário"
                                                onclick="abrirModalStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'] . ' ' . $user['lastname']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $user['status']; ?>')">
                                                <i class="fa-solid fa-toggle-on"></i> <span class="btn-label">Status</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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

    <!-- ==============================
         MODAL DE CONSULTA - INFORMAÇÕES DO USUÁRIO
    ============================== -->
    <div class="modal-overlay" id="modalConsulta">
        <div class="modal-box modal-box-consulta">
            <!-- BOTÃO FECHAR -->
            <button type="button" class="modal-close-btn" onclick="fecharModalConsulta()">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- CABEÇALHO DO MODAL COM FOTO -->
            <div class="consulta-header">
                <div class="consulta-photo-wrapper">
                    <img id="consultaFoto" src="" alt="Foto do usuário" class="consulta-photo">
                    <div id="consultaFotoPlaceholder" class="consulta-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span id="consultaStatusBadge" class="consulta-status-dot"></span>
                </div>
                <h2 id="consultaNomeCompleto"></h2>
                <span id="consultaFuncao" class="consulta-role-badge"></span>
            </div>

            <!-- CONTEÚDO SCROLLÁVEL -->
            <div class="consulta-body">

            <!-- INFORMAÇÕES PESSOAIS -->
            <div class="consulta-section">
                <h3><i class="fa-solid fa-user"></i> Informações Pessoais</h3>
                <div class="consulta-grid">
                    <div class="consulta-field">
                        <label>Nome</label>
                        <span id="consultaNome"></span>
                    </div>
                    <div class="consulta-field">
                        <label>Sobrenome</label>
                        <span id="consultaSobrenome"></span>
                    </div>
                    <div class="consulta-field">
                        <label>Data de Nascimento</label>
                        <span id="consultaNascimento"></span>
                    </div>
                    <div class="consulta-field">
                        <label>CNH</label>
                        <span id="consultaCNH"></span>
                    </div>
                </div>
            </div>

            <!-- INFORMAÇÕES DE CONTATO -->
            <div class="consulta-section">
                <h3><i class="fa-solid fa-address-book"></i> Contato</h3>
                <div class="consulta-grid">
                    <div class="consulta-field">
                        <label>E-mail</label>
                        <span id="consultaEmail"></span>
                    </div>
                    <div class="consulta-field">
                        <label>Telefone</label>
                        <span id="consultaTelefone"></span>
                    </div>
                    <div class="consulta-field">
                        <label>CEP</label>
                        <span id="consultaCEP"></span>
                    </div>
                    <div class="consulta-field full-width">
                        <label>Endereço</label>
                        <span id="consultaEndereco"></span>
                    </div>
                </div>
            </div>

            <!-- INFORMAÇÕES DO SISTEMA -->
            <div class="consulta-section">
                <h3><i class="fa-solid fa-gear"></i> Sistema</h3>
                <div class="consulta-grid">
                    <div class="consulta-field">
                        <label>Função</label>
                        <span id="consultaFuncaoDetalhe"></span>
                    </div>
                    <div class="consulta-field">
                        <label>Status</label>
                        <span id="consultaStatusDetalhe"></span>
                    </div>
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

            </div> <!-- /.consulta-body -->

            <!-- BOTÕES DO MODAL -->
            <div class="consulta-actions">
                <a id="consultaEditarLink" href="#" class="modal-btn modal-btn-confirm">
                    <span class="btn-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                    <span class="btn-info">
                        <strong>Editar Usuário</strong>
                        <small>Alterar informações deste usuário</small>
                    </span>
                </a>
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModalConsulta()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Fechar</strong>
                        <small>Voltar para a lista de usuários</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==============================
         MODAL DE CONFIRMAÇÃO - EXCLUSÃO
    ============================== -->
    <div class="modal-overlay" id="modalExcluir">
        <div class="modal-box">
            <!-- ÍCONE DE ALERTA -->
            <div class="modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2>Tem certeza?</h2>
            <p class="modal-user-name" id="modalUserName"></p>
            <p class="modal-desc">
                Escolha uma das opções abaixo para continuar.
            </p>

            <div class="modal-actions">
                <!-- OPÇÃO: DESLIGAR O FUNCIONÁRIO (muda status para inativo) -->
                <form method="POST" action="process_delete_user.php" id="formDeactivate">
                    <input type="hidden" name="user_id" id="deactivateUserId">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="modal-btn modal-btn-deactivate">
                        <span class="btn-icon"><i class="fa-solid fa-user-clock"></i></span>
                        <span class="btn-info">
                            <strong>Desligar o funcionário</strong>
                            <small>O status do usuário será alterado para "Inativo"</small>
                        </span>
                    </button>
                </form>

                <!-- OPÇÃO: EXCLUIR DEFINITIVAMENTE (remove do banco de dados) -->
                <form method="POST" action="process_delete_user.php" id="formDelete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="modal-btn modal-btn-delete">
                        <span class="btn-icon"><i class="fa-solid fa-trash-can"></i></span>
                        <span class="btn-info">
                            <strong>Eliminar definitivamente</strong>
                            <small>Remove o usuário permanentemente do banco de dados</small>
                        </span>
                    </button>
                </form>

                <!-- OPÇÃO: CANCELAR (fecha o modal sem fazer nada) -->
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModal()">
                    <span class="btn-icon"><i class="fa-solid fa-xmark"></i></span>
                    <span class="btn-info">
                        <strong>Cancelar</strong>
                        <small>Fechar e manter o usuário como está</small>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==============================
         MODAL DE MUDANÇA DE STATUS
    ============================== -->
    <div class="modal-overlay" id="modalStatus">
        <div class="modal-box">
            <!-- ÍCONE DE STATUS -->
            <div class="modal-icon" style="background: #d4edda; color: #2c7da0;">
                <i class="fa-solid fa-toggle-on"></i>
            </div>
            <h2>Mudar Status</h2>
            <p class="modal-user-name" id="modalStatusUserName"></p>
            <p class="modal-desc">
                Selecione o novo status para este usuário.
            </p>

            <!-- FORMULÁRIO DE MUDANÇA DE STATUS -->
                <form method="POST" action="process_change_status.php" id="formStatus">
                <input type="hidden" name="user_id" id="statusUserId">

                <!-- LISTA DESPLEGABLE DE STATUS -->
                <select name="new_status" id="statusSelect" class="modal-status-select">
                    <option value="ativo">Ativo</option>
                    <option value="ferias">Férias</option>
                    <option value="desligado">Desligado</option>
                </select>

                <div class="modal-actions">
                    <!-- BOTÃO: SALVAR MUDANÇA -->
                    <button type="submit" class="modal-btn modal-btn-confirm">
                        <span class="btn-icon"><i class="fa-solid fa-check"></i></span>
                        <span class="btn-info">
                            <strong>Salvar</strong>
                            <small>Confirmar a mudança de status</small>
                        </span>
                    </button>

                    <!-- BOTÃO: CANCELAR -->
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

    <!-- SCRIPTS -->
    <script src="../js/menu.js"></script>
    <script>
        /*
        |--------------------------------------------------------------------------
        | MODAL DE CONSULTA - INFORMAÇÕES DO USUÁRIO
        |--------------------------------------------------------------------------
        */
        function abrirModalConsulta(user) {
            var roleLabels = {
                'motorista':          '🚛 Motorista',
                'ajudante':           '🛄 Ajudante',
                'gestor_logistica':   '📊 Gestor de Logística',
                'socio_proprietario': '🏢 Sócio-Proprietário',
                'lider_setor':        '👔 Líder de Setor',
                'gerente_loja':       '🏪 Gerente de Loja',
                'sub_gerente':        '📋 Sub-gerente',
                'estoque':            '📦 Estoquista',
                'gestor_eventos':     '🎪 Gestor de Eventos'
            };

            var cnhLabels = {
                'A':        'Categoria A (Motocicletas)',
                'B':        'Categoria B (Automóveis)',
                'C':        'Categoria C (Caminhões)',
                'D':        'Categoria D (Ônibus)',
                'E':        'Categoria E (Caminhões + Reboques)',
                'nao_tenho': 'Não possui CNH'
            };

            var statusLabels = {
                'ativo':     'Ativo',
                'ferias':    'Férias',
                'desligado': 'Desligado'
            };

            var statusColors = {
                'ativo':     '#28a745',
                'ferias':    '#d99a2b',
                'desligado': '#c0392b'
            };

            var foto = document.getElementById('consultaFoto');
            var placeholder = document.getElementById('consultaFotoPlaceholder');

            if (user.photo_url) {
                foto.src = '../../' + user.photo_url;
                foto.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                foto.style.display = 'none';
                placeholder.style.display = 'flex';
            }

            var dot = document.getElementById('consultaStatusBadge');
            dot.style.background = statusColors[user.status] || '#999';

            document.getElementById('consultaNomeCompleto').textContent = user.name + ' ' + user.lastname;
            document.getElementById('consultaFuncao').textContent = roleLabels[user.role] || user.role;
            document.getElementById('consultaNome').textContent = user.name;
            document.getElementById('consultaSobrenome').textContent = user.lastname;
            document.getElementById('consultaNascimento').textContent = user.birth_day ? formatarData(user.birth_day) : '—';
            document.getElementById('consultaCNH').textContent = cnhLabels[user.cnh] || user.cnh || '—';
            document.getElementById('consultaEmail').textContent = user.email || '—';
            document.getElementById('consultaTelefone').textContent = user.phone || '—';
            document.getElementById('consultaCEP').textContent = user.cep || '—';
            document.getElementById('consultaEndereco').textContent = user.address || '—';
            document.getElementById('consultaFuncaoDetalhe').textContent = roleLabels[user.role] || user.role;

            var statusEl = document.getElementById('consultaStatusDetalhe');
            statusEl.textContent = statusLabels[user.status] || user.status;
            statusEl.style.color = statusColors[user.status] || '#999';
            statusEl.style.fontWeight = '600';

            document.getElementById('consultaCriadoEm').textContent = formatarDataHora(user.created_at);
            document.getElementById('consultaAtualizadoEm').textContent = formatarDataHora(user.updated_at);
            document.getElementById('consultaEditarLink').href = 'users_edit.php?id=' + user.id;

            document.getElementById('modalConsulta').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        function formatarData(dataStr) {
            if (!dataStr) return '—';
            var partes = dataStr.split('-');
            if (partes.length === 3) {
                return partes[2] + '/' + partes[1] + '/' + partes[0];
            }
            return dataStr;
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
            if (e.target === this) {
                fecharModalConsulta();
            }
        });
        /*
        |--------------------------------------------------------------------------
        | MODAL DE EXCLUSÃO
        |--------------------------------------------------------------------------
        | Abre o modal com os dados do usuário selecionado e
        | preenche os formulários ocultos com o ID correspondente.
        */
        function abrirModal(userId, userName) {
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('deactivateUserId').value = userId;
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('modalExcluir').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        /*
        | Fecha o modal de exclusão e remove a classe de bloqueio de scroll.
        */
        function fecharModal() {
            document.getElementById('modalExcluir').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        /* Fecha o modal de exclusão ao clicar fora da caixa de confirmação */
        document.getElementById('modalExcluir').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | MODAL DE MUDANÇA DE STATUS
        |--------------------------------------------------------------------------
        | Abre o modal com o nome do usuário e o status atual selecionado
        | na lista desplegável.
        */
        function abrirModalStatus(userId, userName, currentStatus) {
            document.getElementById('modalStatusUserName').textContent = userName;
            document.getElementById('statusUserId').value = userId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('modalStatus').classList.add('active');
            document.body.classList.add('no-scroll');
        }

        /*
        | Fecha o modal de mudança de status e remove o bloqueio de scroll.
        */
        function fecharModalStatus() {
            document.getElementById('modalStatus').classList.remove('active');
            document.body.classList.remove('no-scroll');
        }

        /* Fecha o modal de status ao clicar fora da caixa de confirmação */
        document.getElementById('modalStatus').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalStatus();
            }
        });

        /* Fecha qualquer modal aberto ao pressionar a tecla ESC */
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
