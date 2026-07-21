<?php
/**
 * MODULO: EMAILS
 * Arquivo: index.php - Histórico de e-mails enviados
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao ADMIN
verificar_sessao();
$cargo = getCargo();
exigirAcesso('emails');

$usuario_id = $_SESSION['usuario_id'];

// Filtros via GET
$filtro_tipo   = $_GET['tipo'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_dt_inicio = $_GET['dt_inicio'] ?? '';
$filtro_dt_fim    = $_GET['dt_fim'] ?? '';
$busca         = $_GET['busca'] ?? '';

// Processar reenvio
$mensagem_reenvio = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reenviar') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de seguranca invalido. Tente novamente.');
        redirecionar(APP_URL . 'emails');
    }

    $email_id = $_POST['id'] ?? '';
    if (!empty($email_id)) {
        try {
            // Buscar log do e-mail
            $stmt = $pdo->prepare("SELECT * FROM email_logs WHERE id = :id");
            $stmt->execute([':id' => $email_id]);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($log && $log['status'] === 'erro') {
                $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $log['enviado_por']]);
                $remetente = $stmt->fetch(PDO::FETCH_ASSOC);

                require_once __DIR__ . '/../../includes/mailer.php';

                $resultado = enviarEmail(
                    $log['destinatario'],
                    $log['destinatario'],
                    $log['assunto'],
                    "<p>Reenvio automático do e-mail: <strong>" . h($log['assunto']) . "</strong></p><p>E-mail original enviado em: " . $log['created_at'] . "</p>"
                );

                if ($resultado['success']) {
                    // Atualizar status do log para enviado
                    $stmtUp = $pdo->prepare("UPDATE email_logs SET status = 'enviado', mensagem_erro = NULL WHERE id = :id");
                    $stmtUp->execute([':id' => $email_id]);
                    $mensagem_reenvio = 'success|E-mail reenviado com sucesso para ' . h($log['destinatario']);
                } else {
                    // Atualizar mensagem de erro
                    $stmtUp = $pdo->prepare("UPDATE email_logs SET mensagem_erro = :erro WHERE id = :id");
                    $stmtUp->execute([':id' => $email_id, ':erro' => $resultado['message']]);
                    $mensagem_reenvio = 'error|Falha ao reenviar: ' . $resultado['message'];
                }
            } else {
                $mensagem_reenvio = 'warning|E-mail selecionado não está com status de erro ou não existe.';
            }
        } catch (Exception $e) {
            error_log('Erro ao reenviar e-mail: ' . $e->getMessage());
            $mensagem_reenvio = 'error|Erro ao reenviar e-mail.';
        }
    }
}

try {
    $where = [];
    $params = [];

    if (!empty($filtro_tipo)) {
        $where[] = "e.tipo = :tipo";
        $params[':tipo'] = $filtro_tipo;
    }

    if (!empty($filtro_status)) {
        $where[] = "e.status = :status";
        $params[':status'] = $filtro_status;
    }

    if (!empty($filtro_dt_inicio)) {
        $where[] = "e.created_at >= :dt_inicio";
        $params[':dt_inicio'] = $filtro_dt_inicio . ' 00:00:00';
    }

    if (!empty($filtro_dt_fim)) {
        $where[] = "e.created_at <= :dt_fim";
        $params[':dt_fim'] = $filtro_dt_fim . ' 23:59:59';
    }

    if (!empty($busca)) {
        $where[] = "(e.destinatario LIKE :busca1 OR e.assunto LIKE :busca2)";
        $params[':busca1'] = '%' . $busca . '%';
        $params[':busca2'] = '%' . $busca . '%';
    }

    $sql = "
        SELECT e.*, u.nome AS usuario_nome
        FROM email_logs e
        LEFT JOIN usuarios u ON e.enviado_por = u.id
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Erro ao listar e-mails: ' . $e->getMessage());
    $emails = [];
}

// Labels de tipo com ícones
$tipo_labels = [
    'proposta'           => ['label' => 'Proposta',           'icon' => 'fa-file-invoice'],
    'agendamento'        => ['label' => 'Agendamento',        'icon' => 'fa-calendar-check'],
    'certificado'        => ['label' => 'Certificado',        'icon' => 'fa-file-certificate'],
    'assinatura'         => ['label' => 'Assinatura',         'icon' => 'fa-file-signature'],
    'alerta_vencimento'  => ['label' => 'Alerta Vencimento',  'icon' => 'fa-exclamation-triangle'],
    'portal_acesso'      => ['label' => 'Portal - Acesso',    'icon' => 'fa-user-shield'],
    'portal_recuperacao_senha' => ['label' => 'Portal - Senha', 'icon' => 'fa-key'],
];

$status_labels = [
    'enviado' => ['label' => 'Enviado', 'class' => 'badge-success'],
    'erro'    => ['label' => 'Erro',    'class' => 'badge-danger'],
];

$titulo_page = 'Histórico de E-mails - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-envelope"></i> Histórico de E-mails</h3>
        </div>

        <!-- Mensagem de reenvio -->
        <?php if (!empty($mensagem_reenvio)): ?>
            <?php 
            $parts = explode('|', $mensagem_reenvio, 2);
            $tipo_msg = $parts[0] ?? 'info';
            $texto_msg = $parts[1] ?? $mensagem_reenvio;
            ?>
            <div class="mensagem mensagem-<?php echo $tipo_msg; ?>" style="margin: 15px 20px;">
                <i class="fas fa-<?php echo $tipo_msg === 'success' ? 'check-circle' : ($tipo_msg === 'error' ? 'times-circle' : 'exclamation-circle'); ?>"></i>
                <?php echo $texto_msg; ?>
                <button type="button" class="btn-close-mensagem" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filtros" style="margin: 15px 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" 
                       id="buscaEmail" 
                       value="<?php echo h($busca); ?>"
                       placeholder="Destinatário ou assunto..."
                       onkeyup="if(event.key==='Enter'){filtrarBusca()}">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label>Tipo</label>
                <select id="filtroTipo" onchange="filtrar()">
                    <option value="">Todos</option>
                    <option value="proposta" <?php echo $filtro_tipo === 'proposta' ? 'selected' : ''; ?>>Proposta</option>
                    <option value="agendamento" <?php echo $filtro_tipo === 'agendamento' ? 'selected' : ''; ?>>Agendamento</option>
                    <option value="certificado" <?php echo $filtro_tipo === 'certificado' ? 'selected' : ''; ?>>Certificado</option>
                    <option value="assinatura" <?php echo $filtro_tipo === 'assinatura' ? 'selected' : ''; ?>>Assinatura</option>
                    <option value="alerta_vencimento" <?php echo $filtro_tipo === 'alerta_vencimento' ? 'selected' : ''; ?>>Alerta Vencimento</option>
                    <option value="portal_acesso" <?php echo $filtro_tipo === 'portal_acesso' ? 'selected' : ''; ?>>Portal - Acesso</option>
                    <option value="portal_recuperacao_senha" <?php echo $filtro_tipo === 'portal_recuperacao_senha' ? 'selected' : ''; ?>>Portal - Senha</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label>Status</label>
                <select id="filtroStatus" onchange="filtrar()">
                    <option value="">Todos</option>
                    <option value="enviado" <?php echo $filtro_status === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                    <option value="erro" <?php echo $filtro_status === 'erro' ? 'selected' : ''; ?>>Erro</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 140px;">
                <label>Data Início</label>
                <input type="date" id="filtroDtInicio" value="<?php echo h($filtro_dt_inicio); ?>" onchange="filtrar()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 140px;">
                <label>Data Fim</label>
                <input type="date" id="filtroDtFim" value="<?php echo h($filtro_dt_fim); ?>" onchange="filtrar()">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>

        <?php if (empty($emails)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-envelope-open-text"></i>
                <h3>Nenhum e-mail encontrado</h3>
                <p>Nenhum e-mail foi enviado ainda ou nenhum resultado corresponde aos filtros.</p>
            </div>
        <?php else: ?>
            <table id="tabelaEmails">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Destinatário</th>
                        <th>Assunto</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Enviado por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emails as $e): ?>
                    <?php 
                    $tipo_info = $tipo_labels[$e['tipo']] ?? ['label' => $e['tipo'], 'icon' => 'fa-envelope'];
                    $st_info = $status_labels[$e['status']] ?? ['label' => $e['status'], 'class' => 'badge-secondary'];
                    ?>
                    <tr class="<?php echo $e['status'] === 'erro' ? 'table-danger' : ''; ?>">
                        <td>
                            <strong><?php echo formatarData(substr($e['created_at'], 0, 10)); ?></strong>
                            <br><small><?php echo h(substr($e['created_at'], 11, 5)); ?></small>
                        </td>
                        <td><?php echo h($e['destinatario']); ?></td>
                        <td>
                            <?php echo h($e['assunto']); ?>
                            <?php if (!empty($e['referencia_tipo']) && !empty($e['referencia_id'])): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-hashtag"></i> 
                                    <?php echo h($e['referencia_tipo']); ?>: <?php echo h(substr($e['referencia_id'], 0, 8)); ?>...
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="fas <?php echo $tipo_info['icon']; ?>"></i>
                            <?php echo $tipo_info['label']; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $st_info['class']; ?>">
                                <?php echo $st_info['label']; ?>
                            </span>
                            <?php if ($e['status'] === 'erro' && !empty($e['mensagem_erro'])): ?>
                                <br><small class="text-danger" 
                                         title="<?php echo h($e['mensagem_erro']); ?>"
                                         style="cursor: help; font-size: 11px;"
                                         onclick="alert('<?php echo h(addslashes($e['mensagem_erro'])); ?>')">
                                    <i class="fas fa-exclamation-circle"></i> Ver erro
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($e['enviado_por'])): ?>
                                <span class="badge badge-info"><i class="fas fa-user"></i> <?php echo h($e['usuario_nome']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><i class="fas fa-robot"></i> Automático</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap; min-width: 132px;">
                            <div class="d-flex gap-1 align-items-center flex-nowrap">
                                <?php if ($e['status'] === 'erro'): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Reenviar este e-mail para <?php echo h(addslashes($e['destinatario'])); ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                    <input type="hidden" name="action" value="reenviar">
                                    <input type="hidden" name="id" value="<?php echo h($e['id']); ?>">
                                    <button type="submit" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1 email-reenviar-btn" title="Reenviar">
                                        <i class="fas fa-redo-alt"></i> Reenviar
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 12px;">
                                        <i class="fas fa-check-circle text-success"></i> Entregue
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="card-footer" style="padding: 12px 20px;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total: <?php echo count($emails); ?> e-mail(s)
                <?php 
                $erros_count = 0;
                foreach ($emails as $e) { if ($e['status'] === 'erro') $erros_count++; }
                if ($erros_count > 0): 
                ?>
                — <span class="text-danger"><?php echo $erros_count; ?> com erro</span>
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>

<style>
.email-reenviar-btn {
    white-space: nowrap;
    min-width: 108px;
    justify-content: center;
}
.tabela-container table th:last-child,
.tabela-container table td:last-child {
    white-space: nowrap;
    min-width: 132px;
}
</style>

<script>
function filtrar() {
    const url = new URL(window.location.href);
    const tipo = document.getElementById('filtroTipo').value;
    const status = document.getElementById('filtroStatus').value;
    const dtInicio = document.getElementById('filtroDtInicio').value;
    const dtFim = document.getElementById('filtroDtFim').value;
    const busca = document.getElementById('buscaEmail').value;

    if (tipo) url.searchParams.set('tipo', tipo); else url.searchParams.delete('tipo');
    if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
    if (dtInicio) url.searchParams.set('dt_inicio', dtInicio); else url.searchParams.delete('dt_inicio');
    if (dtFim) url.searchParams.set('dt_fim', dtFim); else url.searchParams.delete('dt_fim');
    if (busca) url.searchParams.set('busca', busca); else url.searchParams.delete('busca');

    window.location.href = url.toString();
}

function limparFiltros() {
    window.location.href = '<?php echo APP_URL; ?>emails';
}

function buscarEnter(e) {
    if (e.key === 'Enter') {
        filtrar();
    }
}

document.getElementById('buscaEmail').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        filtrar();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
