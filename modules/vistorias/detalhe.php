<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: detalhe.php - Exibir detalhes de uma vistoria com
 *           integracao ao agendamento, OS e exigencias do relatorio tecnico.
 * FASE 3 - passo 7b: link para relatorio + status da OS + exigencias
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

$usuario_id = $_SESSION['usuario_id'];
$cargo = getCargo();

// Buscar vistoria com JOINs expandidos
$id = $_GET['id'] ?? '';
$vistoria = null;
$exigencias = [];
$anexos_campo = [];
$os_info = null;

if (empty($id)) {
    setMensagem('error', 'ID da vistoria invalido.');
    redirecionar(APP_URL . 'vistorias');
}

try {
    $stmt = $pdo->prepare("
        SELECT v.*, 
               e.nome AS embarcacao_nome, e.tipo AS embarcacao_tipo, 
               e.registro AS embarcacao_registro, e.proprietario AS embarcacao_proprietario,
               e.ano AS embarcacao_ano,
               p.nome AS pessoa_nome, p.cpf_cnpj AS pessoa_cpf,
               p.telefone AS pessoa_telefone, p.email AS pessoa_email,
               p.endereco AS pessoa_endereco,
               u.nome AS criado_por_nome,
               a.id AS agendamento_id, a.tipo_vistoria, a.vistoriador_id,
               usr_vist.nome AS vistoriador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status
        FROM vistorias v
        LEFT JOIN embarcacoes e ON v.embarcacao_id = e.id
        LEFT JOIN clientes p ON v.pessoa_id = p.id
        LEFT JOIN usuarios u ON v.criado_por = u.id
        LEFT JOIN agendamentos a ON v.agendamento_id = a.id
        LEFT JOIN usuarios usr_vist ON a.vistoriador_id = usr_vist.id
        LEFT JOIN ordens_servico os ON os.agendamento_id = a.id
        WHERE v.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $vistoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vistoria) {
        // Montar info da OS para exibicao
        if (!empty($vistoria['os_id'])) {
            $os_info = [
                'id'     => $vistoria['os_id'],
                'numero' => $vistoria['os_numero'],
                'status' => $vistoria['os_status'],
            ];
        }

        // Carregar exigencias do relatorio
        $stmtE = $pdo->prepare("
            SELECT * FROM vistoria_exigencias 
            WHERE vistoria_id = :vistoria_id 
            ORDER BY ordem ASC
        ");
        $stmtE->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        try {
            $stmtA = $pdo->prepare("SELECT va.*, ec.descricao AS item_descricao
                                    FROM vistoria_anexos va
                                    LEFT JOIN exigencias_catalogo ec ON ec.id = va.catalogo_id
                                    WHERE va.vistoria_id = :vistoria_id AND va.excluido_em IS NULL
                                    ORDER BY va.criado_em ASC");
            $stmtA->execute([':vistoria_id' => $vistoria['id']]);
            $anexos_campo = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $anexos_campo = [];
        }
    }

} catch (Exception $e) {
    error_log('Erro ao buscar vistoria: ' . $e->getMessage());
}

if (!$vistoria) {
    setMensagem('error', 'Vistoria nao encontrada.');
    redirecionar(APP_URL . 'vistorias');
}

// Vistoriador so ve as proprias vistorias
if ($cargo === 'VISTORIADOR' && ($vistoria['vistoriador_id'] ?? '') !== $usuario_id) {
    setMensagem('error', 'Acesso negado. Esta vistoria nao esta atribuida a voce.');
    redirecionar(APP_URL . 'vistorias');
}

if ($cargo === 'ANALISTA' && ($vistoria['status'] ?? '') !== 'AGUARDANDO_APROVACAO') {
    setMensagem('error', 'O analista só pode acessar vistorias aguardando aprovação.');
    redirecionar(APP_URL . 'vistorias');
}

// Vendedor ve apenas vistorias de agendamentos que ele criou
if ($cargo === 'VENDEDOR' && !empty($vistoria['agendamento_id'])) {
    $stmtVend = $pdo->prepare('SELECT id FROM agendamentos WHERE id = ? AND vendedor_id = ?');
    $stmtVend->execute([$vistoria['agendamento_id'], $usuario_id]);
    if (!$stmtVend->fetch()) {
        setMensagem('error', 'Acesso negado. Voce nao tem permissao para visualizar esta vistoria.');
        redirecionar(APP_URL . 'vistorias');
    }
}

// Buscar vistoriadores ativos se for ADMIN
$vistoriadores = [];
if ($cargo === 'ADMIN') {
    $stmtVist = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo = 'VISTORIADOR' AND ativo = 1 ORDER BY nome ASC");
    $vistoriadores = $stmtVist->fetchAll(PDO::FETCH_ASSOC);
}

// Gerar CSRF para o form de alteracao de status
$csrf = gerarCSRF();

// Status labels
$statusConfig = [
    'PENDENTE'  => ['class' => 'badge-warning', 'icon' => 'fa-clock',  'cor' => '#ffc107'],
    'AGUARDANDO_APROVACAO' => ['class' => 'badge-warning', 'icon' => 'fa-hourglass-half', 'cor' => '#d99700'],
    'APROVADA'  => ['class' => 'badge-success', 'icon' => 'fa-check-circle', 'cor' => '#28a745'],
    'APROVADA_COM_EXIGENCIAS' => ['class' => 'badge-info', 'icon' => 'fa-clipboard-check', 'cor' => '#0d8aa6'],
    'REPROVADA' => ['class' => 'badge-danger',  'icon' => 'fa-times-circle', 'cor' => '#dc3545'],
    'CANCELADA' => ['class' => 'badge-secondary', 'icon' => 'fa-ban',  'cor' => '#6c757d'],
];

$osStatusLabels = [
    'pendente'     => ['class' => 'badge-warning', 'label' => 'Pendente'],
    'em_andamento' => ['class' => 'badge-info',    'label' => 'Em Andamento'],
    'executado'    => ['class' => 'badge-success', 'label' => 'Executada'],
    'cancelado'    => ['class' => 'badge-danger',  'label' => 'Cancelada'],
];

$sCfg = $statusConfig[$vistoria['status']] ?? $statusConfig['PENDENTE'];

$titulo_page = 'Detalhes da Vistoria - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 900px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas fa-clipboard-check"></i> Detalhes da Vistoria
            </h3>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <span class="badge <?php echo $sCfg['class']; ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                    <i class="fas <?php echo $sCfg['icon']; ?>"></i> <?php echo h($vistoria['status']); ?>
                </span>
                <?php if ($os_info): ?>
                    <?php $osLabel = $osStatusLabels[$os_info['status']] ?? ['class' => 'badge-secondary', 'label' => $os_info['status']]; ?>
                    <a href="<?php echo APP_URL; ?>agendamentos/os?id=<?php echo urlencode($os_info['id']); ?>" 
                       class="badge <?php echo $osLabel['class']; ?>" 
                       style="text-decoration: none; font-size: 0.85rem; padding: 6px 12px;"
                       title="Visualizar Ordem de Serviço">
                        <i class="fas fa-clipboard-list"></i> <?php echo h($os_info['numero']); ?> — <?php echo $osLabel['label']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">

            <!-- Informacoes da Vistoria -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-calendar"></i> Data da Vistoria
                    </label>
                    <div style="font-size: 1.05rem; font-weight: 500;">
                        <?php echo formatarData($vistoria['data_vistoria']); ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-clipboard-check"></i> Tipo de Vistoria
                    </label>
                    <div style="font-size: 1.05rem; font-weight: 500;">
                        <?php echo h($vistoria['tipo_vistoria'] ?? 'Nao informado'); ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-user-check"></i> Vistoriador
                    </label>
                    <div style="font-size: 1.05rem; font-weight: 500;">
                        <?php echo h($vistoria['vistoriador_nome'] ?? 'Nao atribuido'); ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-user-shield"></i> Criado por
                    </label>
                    <div style="font-size: 1.05rem; font-weight: 500;">
                        <?php echo h($vistoria['criado_por_nome'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-calendar-plus"></i> Criado em
                    </label>
                    <div style="font-size: 0.95rem;">
                        <?php echo formatarDataCompleta($vistoria['criado_em']); ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="text-muted" style="font-size: 0.8rem;">
                        <i class="fas fa-calendar-check"></i> Atualizado em
                    </label>
                    <div style="font-size: 0.95rem;">
                        <?php echo formatarDataCompleta($vistoria['atualizado_em']); ?>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--cor-borda); margin: 20px 0;">

            <!-- Embarcacao -->
            <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--cor-destaque, #28a745);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <i class="fas fa-ship" style="color: var(--cor-destaque, #28a745); font-size: 1.1rem;"></i>
                    <strong style="font-size: 1rem;">Embarcacao</strong>
                </div>
                <?php if ($vistoria['embarcacao_nome']): ?>
                    <div style="margin-left: 30px;">
                        <strong style="font-size: 1.05rem;"><?php echo h($vistoria['embarcacao_nome']); ?></strong>
                        <?php if (!empty($vistoria['embarcacao_registro'])): ?>
                            <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 10px;">
                                Registro: <?php echo h($vistoria['embarcacao_registro']); ?>
                            </span>
                        <?php endif; ?>
                        <div style="margin-top: 5px;">
                            <small style="color: var(--cor-texto-secundario, #6c757d);">
                                <?php if (!empty($vistoria['embarcacao_tipo'])) echo 'Tipo: ' . h($vistoria['embarcacao_tipo']); ?>
                                <?php if (!empty($vistoria['embarcacao_ano'])) echo ' • Ano: ' . h($vistoria['embarcacao_ano']); ?>
                                <?php if (!empty($vistoria['embarcacao_proprietario'])) echo ' • Proprietario: ' . h($vistoria['embarcacao_proprietario']); ?>
                            </small>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-left: 30px; color: var(--cor-texto-secundario, #6c757d);">
                        <i class="fas fa-exclamation-triangle"></i> Embarcacao nao encontrada (ID: <?php echo h($vistoria['embarcacao_id']); ?>)
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pessoa Responsavel -->
            <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #17a2b8;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <i class="fas fa-user" style="color: #17a2b8; font-size: 1.1rem;"></i>
                    <strong style="font-size: 1rem;">Pessoa Responsavel</strong>
                </div>
                <?php if ($vistoria['pessoa_nome']): ?>
                    <div style="margin-left: 30px;">
                        <strong style="font-size: 1.05rem;"><?php echo h($vistoria['pessoa_nome']); ?></strong>
                        <?php if (!empty($vistoria['pessoa_cpf'])): ?>
                            <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 10px;">
                                CPF: <?php echo h(formatarCPF($vistoria['pessoa_cpf'])); ?>
                            </span>
                        <?php endif; ?>
                        <div style="margin-top: 5px;">
                            <small style="color: var(--cor-texto-secundario, #6c757d);">
                                <?php if (!empty($vistoria['pessoa_telefone'])) echo 'Tel: ' . h($vistoria['pessoa_telefone']); ?>
                                <?php if (!empty($vistoria['pessoa_email'])) echo ' • Email: ' . h($vistoria['pessoa_email']); ?>
                            </small>
                            <?php if (!empty($vistoria['pessoa_endereco'])): ?>
                                <br><small style="color: var(--cor-texto-secundario, #6c757d);">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo h($vistoria['pessoa_endereco']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-left: 30px; color: var(--cor-texto-secundario, #6c757d);">
                        <i class="fas fa-exclamation-triangle"></i> Pessoa nao encontrada (ID: <?php echo h($vistoria['pessoa_id']); ?>)
                    </div>
                <?php endif; ?>
            </div>

            <!-- Observacoes -->
            <?php if (!empty($vistoria['observacoes'])): ?>
            <div style="margin-bottom: 15px;">
                <label class="text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-sticky-note"></i> Observacoes
                </label>
                <div style="background: var(--cor-sidebar); padding: 12px 15px; border-radius: 8px; white-space: pre-wrap;">
                    <?php echo h($vistoria['observacoes']); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Observacoes Tecnicas (relatorio) -->
            <?php if (!empty($vistoria['observacoes_tecnicas'])): ?>
            <div style="margin-bottom: 15px;">
                <label class="text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-microscope"></i> Observacoes Tecnicas
                </label>
                <div style="background: var(--cor-sidebar); padding: 12px 15px; border-radius: 8px; white-space: pre-wrap; border-left: 3px solid #3498DB;">
                    <?php echo nl2br(h($vistoria['observacoes_tecnicas'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($anexos_campo)): ?>
            <div style="margin-bottom: 18px;">
                <label class="text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-camera"></i> Evidências de Campo (<?php echo count($anexos_campo); ?>)
                </label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:8px;">
                    <?php foreach ($anexos_campo as $anexo): ?>
                    <?php $anexo_url = APP_URL . 'api/campo/v1/anexos/' . rawurlencode((string)$anexo['id']); ?>
                    <a href="<?php echo h($anexo_url); ?>" target="_blank" rel="noopener noreferrer"
                       style="display:block;color:inherit;text-decoration:none;border:1px solid var(--cor-borda);border-radius:9px;overflow:hidden;background:var(--cor-sidebar);">
                        <img src="<?php echo h($anexo_url); ?>" alt="Evidência da vistoria" loading="lazy"
                             style="width:100%;height:130px;display:block;object-fit:cover;">
                        <span style="display:block;padding:8px;font-size:0.72rem;line-height:1.35;">
                            <?php echo h(mb_strimwidth($anexo['item_descricao'] ?? 'Evidência geral', 0, 70, '...')); ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== TABELA DE EXIGENCIAS (DO RELATORIO TECNICO) ===== -->
            <?php if (!empty($exigencias)): ?>
            <div style="margin-bottom: 15px;">
                <label class="text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-tasks"></i> Itens Inspecionados (<?php echo count($exigencias); ?> itens)
                </label>
                <div style="overflow-x: auto; margin-top: 5px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: var(--cor-sidebar); border-bottom: 2px solid var(--cor-borda);">
                                <th style="text-align: center; padding: 6px 8px; width: 35px;">#</th>
                                <th style="text-align: left; padding: 6px 8px;">Item</th>
                                <th style="text-align: left; padding: 6px 8px;">Descricao</th>
                                <th style="text-align: center; padding: 6px 8px; width: 80px;">Conforme?</th>
                                <th style="text-align: left; padding: 6px 8px;">Observacao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exigencias as $ex): 
                                $conformeClass = '';
                                $conformeIcon = '';
                                switch ($ex['conforme']) {
                                    case 'sim':
                                        $conformeClass = 'color: #2ECC71; font-weight: 600;';
                                        $conformeIcon = '<i class="fas fa-check-circle"></i> ';
                                        break;
                                    case 'nao':
                                        $conformeClass = 'color: #E74C3C; font-weight: 600;';
                                        $conformeIcon = '<i class="fas fa-times-circle"></i> ';
                                        break;
                                    default:
                                        $conformeClass = 'color: #999;';
                                        $conformeIcon = '<i class="fas fa-minus-circle"></i> ';
                                }
                            ?>
                            <tr style="border-bottom: 1px solid var(--cor-borda);">
                                <td style="text-align: center; padding: 6px 8px;"><?php echo (int)$ex['ordem']; ?></td>
                                <td style="padding: 6px 8px; font-weight: 600;"><?php echo h($ex['item']); ?></td>
                                <td style="padding: 6px 8px; color: var(--cor-texto-secundario, #aaa);"><?php echo h($ex['descricao'] ?: '-'); ?></td>
                                <td style="text-align: center; padding: 6px 8px;">
                                    <span style="<?php echo $conformeClass; ?>">
                                        <?php echo $conformeIcon; ?>
                                        <?php echo $ex['conforme'] === 'sim' ? 'Sim' : ($ex['conforme'] === 'nao' ? 'Nao' : 'N/A'); ?>
                                    </span>
                                </td>
                                <td style="padding: 6px 8px;"><?php echo h($ex['observacao'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Resultado -->
            <?php if (!empty($vistoria['resultado'])): ?>
            <div style="margin-bottom: 15px;">
                <label class="text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-file-alt"></i> Resultado
                </label>
                <div style="background: var(--cor-sidebar); padding: 12px 15px; border-radius: 8px; white-space: pre-wrap;">
                    <?php echo h($vistoria['resultado']); ?>
                </div>
            </div>
            <?php endif; ?>

            <hr style="border-color: var(--cor-borda); margin: 20px 0;">

            <!-- ===== ACAO: LINK PARA RELATORIO TECNICO ===== -->
            <?php if (!empty($vistoria['agendamento_id'])): ?>
            <div style="background: rgba(46,204,113,0.08); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(46,204,113,0.25);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="font-size: 0.95rem;">
                            <i class="fas fa-clipboard-list" style="color: var(--cor-destaque);"></i> Relatorio Tecnico
                        </strong>
                        <br><small class="text-muted">Edite os itens inspecionados e observacoes tecnicas.</small>
                    </div>
                    <a href="<?php echo APP_URL; ?>vistorias/relatorio?agendamento_id=<?php echo urlencode($vistoria['agendamento_id']); ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-edit"></i> Abrir Relatorio Tecnico
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== ALERTA: OS EXECUTADA ===== -->
            <?php if ($os_info && $os_info['status'] === 'executado'): ?>
            <div style="background: rgba(46,204,113,0.12); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(46,204,113,0.3);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: #2ECC71; font-size: 1.5rem;"></i>
                    <div>
                        <strong style="color: #2ECC71;">Ordem de Servico Executada</strong>
                        <br><small class="text-muted">A OS <strong><?php echo h($os_info['numero']); ?></strong> foi concluida. 
                        A geracao dos certificados (CNBL/CNARQ) esta liberada.</small>
                    </div>
                </div>
                <?php if ($cargo === 'ADMIN'): ?>
                <div style="margin-top: 10px;">
                    <a href="<?php echo APP_URL; ?>agendamentos/os?id=<?php echo urlencode($os_info['id']); ?>" 
                       class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Visualizar OS
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Atribuir Vistoriador e Data (apenas ADMIN) -->
            <?php if ($cargo === 'ADMIN' && in_array($vistoria['status'], ['PENDENTE', 'AGUARDANDO_APROVACAO'])): ?>
            <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #f39c12;">
                <h4 style="margin-bottom: 12px; font-size: 0.95rem; color: #f39c12;">
                    <i class="fas fa-calendar-alt"></i> Agendar Vistoria e Atribuir Vistoriador
                </h4>
                <form method="POST" action="<?php echo APP_URL; ?>vistorias/actions?action=atribuir_vistoriador">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <input type="hidden" name="id" value="<?php echo h($vistoria['id']); ?>">

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="data_vistoria">
                                <i class="fas fa-calendar"></i> Data da Vistoria
                            </label>
                            <input type="date" id="data_vistoria" name="data_vistoria" required 
                                   value="<?php echo h($vistoria['data_vistoria'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="vistoriador_id">
                                <i class="fas fa-user-check"></i> Vistoriador
                            </label>
                            <select id="vistoriador_id" name="vistoriador_id" required>
                                <option value="">-- Selecione um Vistoriador --</option>
                                <?php foreach ($vistoriadores as $v): ?>
                                    <option value="<?php echo h($v['id']); ?>" 
                                        <?php echo ($vistoria['vistoriador_id'] == $v['id']) ? 'selected' : ''; ?>>
                                        <?php echo h($v['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Agendamento
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Alterar Status (apenas ADMIN) -->
            <?php if ($cargo === 'ADMIN'): ?>
            <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px;">
                <h4 style="margin-bottom: 12px; font-size: 0.95rem;">
                    <i class="fas fa-cog"></i> Alterar Status (Administrador)
                </h4>
                <form method="POST" action="<?php echo APP_URL; ?>vistorias/actions?action=alterar_status">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <input type="hidden" name="id" value="<?php echo h($vistoria['id']); ?>">

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="status">
                                <i class="fas fa-tag"></i> Novo Status
                            </label>
                            <select id="status" name="status" required>
                                <option value="PENDENTE" <?php echo $vistoria['status'] === 'PENDENTE' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="REPROVADA" <?php echo $vistoria['status'] === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                                <option value="CANCELADA" <?php echo $vistoria['status'] === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="resultado">
                            <i class="fas fa-file-alt"></i> Resultado / Observacao do status
                        </label>
                        <textarea id="resultado" 
                                  name="resultado" 
                                  placeholder="Descreva o resultado da vistoria ou motivo da decisao..." 
                                  rows="3"
                                  maxlength="2000"><?php echo h($vistoria['resultado'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" 
                            onclick="return confirm('Alterar o status desta vistoria? Isto tambem pode afetar a OS vinculada.')">
                        <i class="fas fa-save"></i> Atualizar Status
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Botoes -->
            <div class="d-flex gap-2" style="margin-top: 15px;">
                <a href="<?php echo APP_URL; ?>vistorias" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <?php if (!empty($vistoria['agendamento_id'])): ?>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
                    <i class="fas fa-calendar-check"></i> Ver Agendamento
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
