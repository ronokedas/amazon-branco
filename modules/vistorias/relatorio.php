<?php
/**
 * MODULO: VISTORIAS (EXPANSAO)
 * Arquivo: relatorio.php - Formulario de relatorio tecnico com
 *           tabela dinamica de exigencias, vinculado ao agendamento.
 * ACESSO: ?agendamento_id=UUID — ADMIN e VISTORIADOR
 * REGRA: Ao salvar, avanca status da OS para "Executado"
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/aprovacao_ui.php';

exigirAcesso('vistorias');
$cargo = getCargo();

$usuario_id = $_SESSION['usuario_id'];
$agendamento_id = $_GET['agendamento_id'] ?? '';
$vistoria_solicitada_id = trim($_GET['vistoria_id'] ?? '');

if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', (string)$agendamento_id)
    || ($vistoria_solicitada_id !== '' && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $vistoria_solicitada_id))) {
    http_response_code(400);
    exit('Identificador de relatorio invalido.');
}

// ============================================
// BUSCAR DADOS DO AGENDAMENTO + CLIENTE + EMBARCACAO + OS
// ============================================
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
               c.telefone AS cliente_telefone, c.email AS cliente_email,
               e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
               e.tipo_embarcacao, e.tipo, e.ano AS embarcacao_ano,
               e.comprimento_total, e.boca_moldada, e.pontal_moldado,
               e.material_casco, e.arqueacao_bruta, e.possui_propulsao,
               e.numero_passageiros_n1, e.numero_passageiros_n2,
               u.nome AS vistoriador_nome,
               arm.nome AS armador_nome,
               a.operador_nome AS agendamento_operador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status
        FROM agendamentos a
        INNER JOIN clientes c     ON a.cliente_id = c.id
        INNER JOIN embarcacoes e  ON a.embarcacao_id = e.id
        LEFT  JOIN usuarios u     ON a.vistoriador_id = u.id
        LEFT  JOIN clientes arm   ON a.armador_id = arm.id
        LEFT  JOIN ordens_servico os ON os.agendamento_id = a.id
        WHERE a.id = :id
    ");
    $stmt->execute([':id' => $agendamento_id]);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ag) {
        setMensagem('error', 'Agendamento nao encontrado.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // VISTORIADOR so pode ver relatorio dos proprios agendamentos
    if ($cargo === 'VISTORIADOR' && $ag['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Este agendamento nao esta atribuido a voce.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // Se estiver aprovada, vistoriador não pode mais editar
    $sqlVCheck = "SELECT status FROM vistorias WHERE agendamento_id = :id";
    $paramsVCheck = [':id' => $agendamento_id];
    if ($vistoria_solicitada_id !== '') {
        $sqlVCheck .= " AND id = :vistoria_id";
        $paramsVCheck[':vistoria_id'] = $vistoria_solicitada_id;
    }
    $sqlVCheck .= " ORDER BY criado_em DESC, id DESC LIMIT 1";
    $stmtV_check = $pdo->prepare($sqlVCheck);
    $stmtV_check->execute($paramsVCheck);
    $vistoria_check = $stmtV_check->fetch(PDO::FETCH_ASSOC);
    if ($cargo === 'ANALISTA' && $vistoria_solicitada_id === '' && (!$vistoria_check || $vistoria_check['status'] !== 'AGUARDANDO_APROVACAO')) {
        setMensagem('error', 'O analista só pode acessar relatórios aguardando aprovação.');
        redirecionar(APP_URL . 'vistorias');
    }
} catch (Exception $e) {
    error_log('Erro ao carregar agendamento relatorio: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar dados do agendamento.');
    redirecionar(APP_URL . 'agendamentos');
}

// ============================================
// VERIFICAR SE JA EXISTE UMA VISTORIA VINCULADA
// ============================================
$vistoria = null;
$exigencias_avulsas = [];
$checklist_respostas = [];
$prazo_padrao_exigencias = '';
$prazo_exigencias_dias = '';

try {
    $sqlVistoria = "SELECT * FROM vistorias WHERE agendamento_id = :agendamento_id";
    $paramsVistoria = [':agendamento_id' => $agendamento_id];
    if ($vistoria_solicitada_id !== '') {
        $sqlVistoria .= " AND id = :vistoria_id";
        $paramsVistoria[':vistoria_id'] = $vistoria_solicitada_id;
    }
    $sqlVistoria .= " ORDER BY criado_em DESC, id DESC LIMIT 1";
    $stmtV = $pdo->prepare($sqlVistoria);
    $stmtV->execute($paramsVistoria);
    $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

    if ($vistoria_solicitada_id !== '' && !$vistoria) {
        throw new Exception('O relatorio solicitado nao pertence a este agendamento.');
    }

    if ($vistoria) {
        if (in_array((int)($vistoria['prazo_exigencias_dias'] ?? 0), [60, 90], true)) {
            $prazo_exigencias_dias = (string)(int)$vistoria['prazo_exigencias_dias'];
        }

        // Carregar exigencias da vistoria (Avulsas são as que não tem catalogo_id OU tratadas diferente,
        // mas para manter compatibilidade, vamos tratar itens manuais como avulsos e itens do catalogo pelo checklist)
        $filtroExigencias = (($vistoria['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS')
            ? ''
            : " AND (catalogo_id IS NULL OR catalogo_id = '')";
        $stmtE = $pdo->prepare("SELECT * FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id{$filtroExigencias} ORDER BY ordem ASC");
        $stmtE->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias_avulsas = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        // Carregar respostas do checklist
        $stmtResp = $pdo->prepare("SELECT * FROM vistoria_checklist_respostas WHERE vistoria_id = :v");
        $stmtResp->execute([':v' => $vistoria['id']]);
        while ($r = $stmtResp->fetch(PDO::FETCH_ASSOC)) {
            $checklist_respostas[$r['catalogo_id']] = $r;
            if (empty($prazo_padrao_exigencias) && !empty($r['vencimento'])) {
                $prazo_padrao_exigencias = $r['vencimento'];
            }
        }

        if ($prazo_exigencias_dias === '' && $prazo_padrao_exigencias !== '') {
            $dataBasePrazo = $vistoria['data_vistoria'] ?? $ag['data_vistoria'] ?? '';
            $dataBase = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$dataBasePrazo);
            $dataVencimento = DateTimeImmutable::createFromFormat('!Y-m-d', $prazo_padrao_exigencias);
            if ($dataBase && $dataVencimento && $dataVencimento >= $dataBase) {
                $diasCalculados = (int)$dataBase->diff($dataVencimento)->days;
                if (in_array($diasCalculados, [60, 90], true)) {
                    $prazo_exigencias_dias = (string)$diasCalculados;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('Erro ao buscar vistoria: ' . $e->getMessage());
}

$editando = !empty($vistoria);
$eh_relatorio_cumprimento = (($vistoria['finalidade'] ?? 'VISTORIA') === 'CUMPRIMENTO_EXIGENCIAS');
$admin_review_mode = (temPerfil('ANALISTA') && $editando && $cargo !== 'VISTORIADOR')
    || ($cargo === 'VISTORIADOR' && $editando && in_array($vistoria['status'] ?? '', ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'], true));
$exigencias_relatorio = [];
$total_exigencias_relatorio = 0;
$total_nao_conformes_relatorio = 0;
$armador_relatorio_nome = '';
$resumo_aprovacao_relatorio = [
    'pendentes' => 0,
    'pendentes_as' => 0,
    'pendentes_comuns' => 0,
    'status_esperado' => 'APROVADA',
    'versao' => '',
];

if ($admin_review_mode) {
    try {
        if (!empty($vistoria['armador_id'])) {
            $stmtArmadorReview = $pdo->prepare("SELECT nome FROM clientes WHERE id = :id LIMIT 1");
            $stmtArmadorReview->execute([':id' => $vistoria['armador_id']]);
            $armador_relatorio_nome = (string)($stmtArmadorReview->fetchColumn() ?: '');
        }

        $stmtReviewEx = $pdo->prepare("
            SELECT ve.*, ec.descricao AS catalogo_descricao, ec.item_normam AS catalogo_item_normam
            FROM vistoria_exigencias ve
            LEFT JOIN exigencias_catalogo ec ON ve.catalogo_id = ec.id
            WHERE ve.vistoria_id = :vistoria_id
            ORDER BY ve.ordem ASC
        ");
        $stmtReviewEx->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias_relatorio = $stmtReviewEx->fetchAll(PDO::FETCH_ASSOC);
        $total_exigencias_relatorio = count($exigencias_relatorio);
        foreach ($exigencias_relatorio as $exReview) {
            if (($exReview['conforme'] ?? '') === 'nao') {
                $total_nao_conformes_relatorio++;
            }
        }
        $resumo_aprovacao_relatorio = aprovacaoRelatorioResumoExigencias($pdo, (string)$vistoria['id']);
    } catch (Exception $e) {
        error_log('Erro ao carregar revisao admin do relatorio: ' . $e->getMessage());
        $exigencias_relatorio = [];
    }
}

// --- DETERMINAR ETAPA ATUAL ---
$status_vistoria = $vistoria['status'] ?? 'PENDENTE';
$pode_ir_etapa2 = in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS']);
$etapa_atual = 1;
if ($pode_ir_etapa2) $etapa_atual = 2;

// Se ainda nao tem exigencias avulsas, inicializa vazia (sem a primeira linha em branco se possível, ou controlada via JS)
$relatorio_anterior_id = $vistoria['relatorio_anterior_id'] ?? '';
$possui_as_pendente = $vistoria ? relatorioPossuiASPendente($pdo, $vistoria['id']) : false;
$relatorio_cumprimento_aberto_id = null;
if ($vistoria && $possui_as_pendente && in_array($vistoria['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
    $stmtCumprimentoAberto = $pdo->prepare("SELECT id FROM vistorias
        WHERE relatorio_anterior_id = :anterior
          AND finalidade = 'CUMPRIMENTO_EXIGENCIAS'
          AND status IN ('PENDENTE','AGUARDANDO_APROVACAO')
        ORDER BY criado_em DESC, id DESC LIMIT 1");
    $stmtCumprimentoAberto->execute([':anterior' => $vistoria['id']]);
    $relatorio_cumprimento_aberto_id = $stmtCumprimentoAberto->fetchColumn() ?: null;
}
$liberacao_certificacao = $vistoria ? avaliarLiberacaoCertificacao($pdo, $vistoria['id']) : ['permitido' => false];
$relatorio_substituto_aprovado = null;
if ($vistoria) {
    $stmtSubstituto = $pdo->prepare("SELECT id, numero FROM vistorias
        WHERE relatorio_anterior_id = :anterior
          AND status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')
        ORDER BY criado_em DESC, id DESC LIMIT 1");
    $stmtSubstituto->execute([':anterior' => $vistoria['id']]);
    $relatorio_substituto_aprovado = $stmtSubstituto->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ============================================
// CLASSIFICAÇ?O DA EMBARCAÇ?O E CHECKLIST
// ============================================
function determinarCategoriaEmbarcacao($emb) {
    $ab = (float)str_replace(',', '.', $emb['arqueacao_bruta'] ?? '0');
    $prop = (bool)$emb['possui_propulsao'];
    $pass1 = (int)($emb['numero_passageiros_n1'] ?? 0);
    $pass2 = (int)($emb['numero_passageiros_n2'] ?? 0);
    $passageiros = ($pass1 + $pass2) > 0;

    $tipo = strtolower($emb['tipo_embarcacao'] ?? '');
    $tipo_str = strtolower($emb['tipo'] ?? '');
    $flutuante = (strpos($tipo, 'flutuante') !== false || strpos($tipo_str, 'flutuante') !== false);

    if ($prop && $ab >= 500) return 'd';
    if (!$prop && $ab >= 500) return 'e';
    if ($flutuante) {
        if (($passageiros && $ab >= 50 && $ab < 500) || ($ab >= 100 && $ab < 500)) return 'c';
    }
    if ($prop) {
        if ($passageiros && $ab >= 20 && $ab < 500) return 'a';
        if (!$passageiros && $ab >= 50 && $ab < 500) return 'a';
    }
    if (!$prop && $ab >= 50 && $ab < 500) return 'b';
    return 'f';
}

$categoria_embarcacao = determinarCategoriaEmbarcacao($ag);
$coluna_aplicabilidade = "aplicabilidade_" . $categoria_embarcacao;

$blocos_vistoria_todos = [
    'seco' => 'Vistoria em Seco',
    'flutuando' => 'Vistoria Flutuando',
    'borda_livre' => 'Vistoria de Borda Livre',
    'arqueacao' => 'Vistoria de Arqueação',
];

function normalizarTextoVistoria(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    return strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'ê' => 'e', 'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ç' => 'c',
    ]);
}

function blocosDisponiveisPorTipoVistoria(string $tipoVistoria, array $todos): array
{
    $texto = normalizarTextoVistoria($tipoVistoria);
    $blocos = [];

    if (strpos($texto, 'seco') !== false) {
        $blocos['seco'] = $todos['seco'];
    }
    if (strpos($texto, 'flutu') !== false || strpos($texto, 'agua') !== false || strpos($texto, 'licenca provisoria') !== false) {
        $blocos['flutuando'] = $todos['flutuando'];
    }
    if (strpos($texto, 'borda') !== false || strpos($texto, 'cnbl') !== false) {
        $blocos['borda_livre'] = $todos['borda_livre'];
    }
    if (strpos($texto, 'arquea') !== false || strpos($texto, 'cnarq') !== false) {
        $blocos['arqueacao'] = $todos['arqueacao'];
    }

    return !empty($blocos) ? $blocos : $todos;
}

$blocos_vistoria_disponiveis = blocosDisponiveisPorTipoVistoria((string)($ag['tipo_vistoria'] ?? ''), $blocos_vistoria_todos);
$bloco_vistoria_padrao = array_key_first($blocos_vistoria_disponiveis) ?: 'flutuando';

$checklist_categorias = [];
try {
    $stmtCat = $pdo->query("SELECT * FROM exigencias_categorias ORDER BY nome ASC");
    $categorias_bd = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    $stmtItens = $pdo->prepare("
        SELECT *
        FROM exigencias_catalogo
        WHERE ativo = 1
        ORDER BY codigo_interno ASC
    ");
    $stmtItens->execute();
    $itens_bd = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categorias_bd as $c) {
        $c['itens'] = [];
        $checklist_categorias[$c['id']] = $c;
    }
    foreach ($itens_bd as $it) {
        if (isset($checklist_categorias[$it['categoria_id']])) {
            $checklist_categorias[$it['categoria_id']]['itens'][] = $it;
        }
    }

    // Remove categorias vazias
    foreach ($checklist_categorias as $k => $c) {
        if (empty($c['itens'])) {
            unset($checklist_categorias[$k]);
        }
    }
} catch (Exception $e) {
    error_log('Erro ao carregar catalogo: ' . $e->getMessage());
}

$titulo_page = 'Relatório Técnico - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal flow-shell">
<div class="flow-hero">
    <div>
        <span class="flow-eyebrow"><i class="fas fa-route"></i> Etapa 3 do fluxo</span>
        <h1><i class="fas fa-clipboard-list"></i> Relatório técnico de vistoria</h1>
        <p>Registre a vistoria, marque conformidades, detalhe exigências e envie o relatório para aprovação administrativa.</p>
    </div>
    <div class="flow-actions">
        <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="flow-track">
    <div class="flow-track-step"><span>01</span>Proposta</div>
    <div class="flow-track-step"><span>02</span>Agendamento</div>
    <div class="flow-track-step is-active"><span>03</span>Vistoria</div>
    <div class="flow-track-step"><span>04</span>Aprovação</div>
    <div class="flow-track-step"><span>05</span>Certificados</div>
</div>

<!-- BARRA DE ETAPAS -->
<div class="etapas-fluxo mb-4" style="display: flex; align-items: center; padding: 20px 0;">
    <div class="etapa <?= $etapa_atual >= 1 ? 'ativa' : '' ?>">
        <span class="etapa-numero">1</span>
        <span class="etapa-label">Relatório</span>
    </div>
    <div class="etapa-linha <?= $pode_ir_etapa2 ? 'completa' : '' ?>" style="flex: 1; height: 3px; background: #444; margin: 0 8px; margin-bottom: 20px;"></div>
    <div class="etapa <?= $pode_ir_etapa2 ? 'ativa' : 'bloqueada' ?>">
        <span class="etapa-numero">2</span>
        <span class="etapa-label">Certificado</span>
    </div>
</div>

<style>
.etapa { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.etapa-numero { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
.etapa.ativa .etapa-numero { background: #2ECC71; color: #000; }
.etapa.bloqueada .etapa-numero { background: #444; color: #888; }
.etapa-label { font-size: 12px; color: #ccc; }
.etapa-linha.completa { background: #2ECC71 !important; }

/* Checklist UI */
.checklist-section { margin-bottom: 15px; border: 1px solid var(--cor-borda, #444); border-radius: 6px; overflow: hidden; }
.checklist-header { background: var(--cor-sidebar, #1a1a2e); padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
.checklist-header:hover { background: #2a2a3e; }
.checklist-body { padding: 0; display: none; background: var(--cor-fundo, #121212); }
.checklist-item { padding: 12px 15px; border-top: 1px solid var(--cor-borda, #444); }
.checklist-item:first-child { border-top: none; }
.item-text { margin-bottom: 8px; font-size: 0.95rem; }
.item-normam { font-size: 0.8rem; color: #aaa; margin-bottom: 10px; display: block; }
.item-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-toggle { flex: 1; padding: 8px 12px; border: 1px solid var(--cor-borda, #444); background: #2a2a3e; color: #ccc; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; }
.btn-toggle:hover { background: #3a3a4e; }
.btn-toggle.active.conforme { background: #2ECC71; color: #000; border-color: #2ECC71; }
.btn-toggle.active.nao-conforme { background: #E74C3C; color: #fff; border-color: #E74C3C; }
.btn-toggle.active.na { background: #95a5a6; color: #fff; border-color: #95a5a6; }

.item-details { margin-top: 15px; padding: 15px; background: rgba(0,0,0,0.2); border-left: 3px solid #E74C3C; border-radius: 0 4px 4px 0; }
.item-details label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #aaa; }
.item-details input { width: 100%; padding: 8px 10px; margin-bottom: 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd); }
.admin-review-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr); gap: 18px; padding: 20px; }
.admin-review-panel { border: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); border-radius: 10px; background: rgba(255,255,255,0.025); overflow: hidden; }
.admin-review-panel h4 { margin: 0; padding: 14px 16px; border-bottom: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); color: var(--cor-texto); font-size: 1rem; }
.admin-review-body { padding: 16px; }
.admin-review-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 10px; margin-bottom: 16px; }
.admin-review-kpi { padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.06); }
.admin-review-kpi small { display: block; color: var(--cor-texto-secundario, #aaa); margin-bottom: 4px; }
.admin-review-kpi strong { color: var(--cor-texto); font-size: 1.05rem; }
.admin-review-text { white-space: pre-wrap; line-height: 1.55; color: var(--cor-texto); background: rgba(0,0,0,0.16); border-radius: 8px; padding: 14px; min-height: 68px; }
.admin-review-exigencias { display: grid; gap: 10px; }
.admin-review-exigencia { padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.14); border: 1px solid rgba(255,255,255,0.06); }
.admin-review-exigencia strong { display: block; margin-bottom: 6px; color: var(--cor-texto); }
.admin-review-exigencia small { display: block; color: var(--cor-texto-secundario, #aaa); }
.admin-review-pdf { width: 100%; min-height: 760px; border: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); border-radius: 8px; background: #111; }
.admin-decision-card { border-color: rgba(243, 156, 18, 0.55); background: linear-gradient(135deg, rgba(243,156,18,0.12), rgba(255,255,255,0.02)); }
.avulsa-empty { padding: 18px; border: 1px dashed rgba(86,224,173,.24); border-radius: 12px; color: var(--cor-texto-secundario, #aaa); text-align: center; background: rgba(3,20,18,.24); }
.avulsa-empty i { display: block; margin-bottom: 8px; color: var(--cor-destaque, #2ECC71); font-size: 1.35rem; }
.avulsa-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.avulsa-table-wrap.is-hidden, .avulsa-empty.is-hidden { display: none; }
@media (max-width: 980px) {
    .admin-review-grid { grid-template-columns: 1fr; }
    .admin-review-pdf { min-height: 520px; }
}
@media (max-width: 768px) {
    .flow-shell { padding-inline: 10px !important; }
    .flow-hero { padding: 16px; border-radius: 14px; }
    .flow-hero p { font-size: .88rem; line-height: 1.5; }
    .flow-track { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; overflow: visible; padding-bottom: 6px; }
    .flow-track-step { min-width: 0; width: 100%; padding: 12px; }
    .flow-track-step:last-child { grid-column: 1 / -1; }
    .etapas-fluxo { padding: 12px 4px !important; }
    .form-container { border-radius: 14px; overflow: hidden; }
    #formRelatorio { padding: 0; }
    .checklist-editor-section, .avulsa-section, .observacoes-section { padding: 16px !important; }
    .checklist-header { min-height: 50px; padding: 12px; gap: 10px; line-height: 1.35; }
    .checklist-item { padding: 14px 12px; }
    .item-text { line-height: 1.48; }
    .item-actions { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .btn-toggle { min-height: 44px; }
    .avulsa-add-button { width: 100%; min-height: 46px; justify-content: center; }
    .avulsa-table-wrap { overflow: visible !important; }
    #tabelaExigenciasAvulsas { display: block; min-width: 0 !important; width: 100% !important; table-layout: auto !important; }
    #tabelaExigenciasAvulsas thead { display: none; }
    #tabelaExigenciasAvulsas tbody { display: grid; gap: 12px; width: 100%; }
    #tabelaExigenciasAvulsas tr.linha-exigencia-avulsa { display: block; width: 100%; padding: 14px; border: 1px solid rgba(86,224,173,.16); border-radius: 14px; background: rgba(3,20,18,.44); box-sizing: border-box; }
    #tabelaExigenciasAvulsas td { display: block; width: 100% !important; min-width: 0 !important; padding: 10px 0 !important; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left !important; white-space: normal !important; box-sizing: border-box; }
    #tabelaExigenciasAvulsas td::before { content: attr(data-label); display: block; margin-bottom: 7px; color: var(--cor-texto-secundario, #9fb0ac); font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    #tabelaExigenciasAvulsas td:first-child { padding-top: 0 !important; }
    #tabelaExigenciasAvulsas td:last-child { min-width: 0 !important; padding-bottom: 0 !important; border-bottom: 0; }
    #tabelaExigenciasAvulsas input, #tabelaExigenciasAvulsas select { min-width: 0; min-height: 42px; }
    #tabelaExigenciasAvulsas .btn { width: 44px; min-height: 42px; justify-content: center; }
    .report-actions { position: sticky; bottom: 0; z-index: 40; padding: 12px 16px !important; background: rgba(5,24,21,.97); border-top: 1px solid rgba(86,224,173,.18); backdrop-filter: blur(12px); }
    .report-actions .btn { flex: 1 1 100%; min-height: 46px; justify-content: center; margin: 0 !important; }
    .report-actions .text-muted { margin: 4px 0 0 !important; line-height: 1.45; }
}
</style>

<!-- BOT?O ETAPA 2 (somente ADMIN, somente quando aprovado) -->
<?php if ($relatorio_substituto_aprovado): ?>
    <div class="alert alert-info" style="margin-bottom:20px;">
        <strong>Relatório histórico/substituído.</strong>
        Para decisões atuais e certificação, use
        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>&vistoria_id=<?= urlencode($relatorio_substituto_aprovado['id']) ?>"><?= h($relatorio_substituto_aprovado['numero']) ?></a>.
    </div>
<?php endif; ?>
<?php if ($vistoria && $possui_as_pendente && in_array($vistoria['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong>Certificação bloqueada por exigência A/S.</strong>
        A embarcação não pode receber certificados até a aprovação da verificação de cumprimento.
        <?php if ($relatorio_cumprimento_aberto_id): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>&vistoria_id=<?= urlencode($relatorio_cumprimento_aberto_id) ?>">Continuar verificação de cumprimento</a>
        <?php elseif (getCargo() === 'ADMIN' || ($ag['vistoriador_id'] ?? '') === $usuario_id): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <button type="submit" class="btn btn-warning"><i class="fas fa-clipboard-check"></i> Verificar cumprimento de exigências</button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif (getCargo() === 'ADMIN' && !empty($liberacao_certificacao['permitido'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
        <strong>Relatório vigente aprovado.</strong> Você pode gerar os certificados agora.
        <a href="<?= APP_URL ?>documentacao/novo_certificado?agendamento_id=<?= urlencode($agendamento_id) ?>" class="btn btn-success ms-3"><i class="fas fa-certificate"></i> Ir para Etapa 2 — Gerar Certificado</a>
    </div>
<?php endif; ?>
    <div class="form-container">
        <div class="form-header report-summary-header">
            <div class="report-summary-heading">
                <h3>
                    <i class="fas fa-clipboard-list"></i>
                    Relatório Técnico de Vistoria
                </h3>
                <span class="help-text">Checklist, exigências e resultado final</span>
            </div>
            <?php if (!empty($vistoria['id'])): ?>
                <a href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"
                   target="_blank"
                   rel="noopener"
                   class="report-pdf-primary"
                   data-testid="abrir-pdf-completo">
                    <span class="report-pdf-primary-icon"><i class="fas fa-file-pdf"></i></span>
                    <span><strong>Visualizar PDF do relatório</strong><small>Disponível também enquanto estiver pendente</small></span>
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- ===== DADOS DO AGENDAMENTO ===== -->
        <div class="report-context">
            <div class="report-context-grid">
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-file-invoice"></i> OS</small>
                    <div style="font-weight: 600;"><?php echo $ag['os_numero'] ? h($ag['os_numero']) : '<em class="text-muted">Pendente</em>'; ?></div>
                </div>
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-calendar-day"></i> Data da Vistoria</small>
                    <div style="font-weight: 600;"><?php echo formatarData($ag['data_vistoria']); ?></div>
                </div>
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-user-check"></i> Vistoriador</small>
                    <div style="font-weight: 600;"><?php echo h($ag['vistoriador_nome'] ?? 'Não atribuído'); ?></div>
                </div>
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-ship"></i> Categoria Normam</small>
                    <div><span class="badge bg-info">Tipo <?php echo strtoupper($categoria_embarcacao); ?></span></div>
                </div>
            </div>

            <div class="report-context-grid report-context-grid--secondary">
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-user-tie"></i> Cliente</small>
                    <div style="font-weight: 600;"><?php echo h($ag['cliente_nome']); ?></div>
                </div>
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-ship"></i> Embarcação</small>
                    <div style="font-weight: 600;"><?php echo h($ag['embarcacao_nome']); ?> <?php echo $ag['embarcacao_registro'] ? '(' . h($ag['embarcacao_registro']) . ')' : ''; ?></div>
                </div>
                <div class="report-context-item report-context-item--wide">
                    <small class="text-muted"><i class="fas fa-clipboard-check"></i> Tipo de Vistoria</small>
                    <div><?php echo h($ag['tipo_vistoria']); ?></div>
                </div>
                <div class="report-context-item">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Status Agendamento</small>
                    <div><?php echo h($ag['status']); ?></div>
                </div>
            </div>
        </div>

        <?php if ($admin_review_mode): ?>
            <?php
            $responsavelVistoria = trim((string)($vistoria['operador_nome'] ?? ''));
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $armador_relatorio_nome ?: ($ag['armador_nome'] ?? '');
            }
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $ag['cliente_nome'] ?? 'Nao informado';
            }
            $statusLabelsAdmin = [
                'PENDENTE' => 'Pendente',
                'AGUARDANDO_APROVACAO' => 'Aguardando aprovacao',
                'APROVADA' => 'Aprovada',
                'APROVADA_COM_EXIGENCIAS' => 'Aprovada com exigencias',
                'REPROVADA' => 'Reprovada',
                'CANCELADA' => 'Cancelada',
            ];
            ?>
            <div class="admin-review-grid">
                <div class="admin-review-panel admin-review-main">
                    <h4><i class="fas fa-file-signature"></i> Revisão do relatório enviado</h4>
                    <div class="admin-review-body">
                        <div class="admin-review-kpis">
                            <div class="admin-review-kpi admin-review-kpi--status">
                                <small>Numero do relatorio</small>
                                <strong><?= h($vistoria['numero'] ?? 'S/N') ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Data da vistoria</small>
                                <strong><?= !empty($vistoria['data_vistoria']) ? formatarData($vistoria['data_vistoria']) : '-' ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Responsavel informado</small>
                                <strong><?= h($responsavelVistoria) ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Status atual</small>
                                <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <h5 class="admin-review-section-heading">Observações técnicas do vistoriador</h5>
                            <div class="admin-review-text"><?= h($vistoria['observacoes_tecnicas'] ?: 'Nenhuma observacao tecnica informada.') ?></div>
                        </div>

                        <div class="admin-review-document">
                            <div class="admin-review-document-bar">
                                <div class="admin-review-document-title">
                                    <span><i class="fas fa-file-pdf"></i></span>
                                    <div><strong>Relatório original do vistoriador</strong><small>Confira todas as respostas, exigências e dados oficiais.</small></div>
                                </div>
                                <a href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"
                                   target="_blank" rel="noopener" class="admin-review-document-link">
                                    <i class="fas fa-up-right-from-square"></i> Abrir PDF completo
                                </a>
                            </div>
                            <p class="admin-review-document-mobile-note"><i class="fas fa-mobile-screen-button"></i> No celular, abra o PDF completo para uma leitura melhor.</p>
                            <iframe class="admin-review-pdf" title="Prévia do relatório técnico" loading="lazy" src="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"></iframe>
                        </div>
                    </div>
                </div>

                <div class="admin-review-sidebar">
                    <div class="admin-review-panel admin-decision-card">
                        <h4><i class="fas fa-gavel"></i> Resultado final da vistoria</h4>
                        <div class="admin-review-body">
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' && $cargo === 'ADMIN'): ?>
                                <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=aprovar_ou_reprovar" id="formDecisaoAdmin">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
                                    <input type="hidden" name="id" value="<?= h($vistoria['id']); ?>">
                                    <input type="hidden" name="versao_relatorio" value="<?= h($resumo_aprovacao_relatorio['versao']); ?>">
                                    <div class="form-group mb-3">
                                        <label>Resultado da aprova&ccedil;&atilde;o *</label>
                                        <div style="display:grid;gap:9px;margin-top:8px">
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #b9ded2;border-radius:9px;background:#f2fbf8;<?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'opacity:.55' : '' ?>">
                                                <input type="radio" name="resultado_relatorio" value="APROVADA" <?= $resumo_aprovacao_relatorio['pendentes'] === 0 ? 'checked' : 'disabled' ?>>
                                                <span><strong>Aprovada</strong><br><small>Libera certificados Provis&oacute;rio, Condicional e Definitivo.</small></span>
                                            </label>
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #efd39e;border-radius:9px;background:#fff9ed;<?= $resumo_aprovacao_relatorio['pendentes'] === 0 ? 'opacity:.55' : '' ?>">
                                                <input type="radio" name="resultado_relatorio" value="APROVADA_COM_EXIGENCIAS" <?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'checked' : 'disabled' ?>>
                                                <span><strong>Aprovada com exig&ecirc;ncias</strong><br><small>Permite apenas certificados Provis&oacute;rio e Condicional.</small></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="admin-review-text" style="margin-bottom:14px">
                                        <strong><?= (int)$resumo_aprovacao_relatorio['pendentes'] ?> exig&ecirc;ncia(s) aberta(s)</strong>:
                                        <?= (int)$resumo_aprovacao_relatorio['pendentes_comuns'] ?> comum(ns) e
                                        <?= (int)$resumo_aprovacao_relatorio['pendentes_as'] ?> A/S.
                                        <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                            <br><span style="color:#a52b22">Exig&ecirc;ncia A/S bloqueia toda certifica&ccedil;&atilde;o at&eacute; o cumprimento.</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-bottom:16px">
                                        <?php renderBotaoAprovacaoDocumento($pdo,'RELATORIO',$vistoria['id'],$vistoria['status'],false,!empty($vistoria['responsavel_assinatura_id'])?(int)$vistoria['responsavel_assinatura_id']:null); ?>
                                    </div>
                                    <hr style="border:0;border-top:1px solid #dfe9e5;margin:16px 0">
                                    <div class="form-group mb-3">
                                        <label for="status_vistoria_admin">Outras decis&otilde;es administrativas</label>
                                        <select id="status_vistoria_admin" name="status_vistoria" class="form-control" required>
                                            <option value="AGUARDANDO_APROVACAO" selected>Manter aguardando aprova&ccedil;&atilde;o</option>
                                            <option value="PENDENTE">Devolver para corre&ccedil;&atilde;o</option>
                                            <option value="REPROVADA" <?= ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : '' ?>>Reprovada</option>
                                            <option value="CANCELADA" <?= ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : '' ?>>Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="observacao_admin">Observa&ccedil;&atilde;o do administrador</label>
                                        <textarea id="observacao_admin" name="observacao_admin" class="form-control" rows="4" placeholder="Obrigat&oacute;ria para reprovar."></textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Salvar decis&atilde;o sem aprovar
                                        </button>
                                        <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                            Voltar para aprovacoes
                                        </a>
                                    </div>
                                </form>
                            <?php elseif (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <div class="admin-review-text">
                                    A revis&atilde;o t&eacute;cnica e a inclus&atilde;o de exig&ecirc;ncias est&atilde;o dispon&iacute;veis abaixo.
                                    A decis&atilde;o final e a assinatura pertencem exclusivamente ao administrador.
                                </div>
                            <?php else: ?>
                                <div class="admin-review-text">
                                    Este relatorio ja foi finalizado como <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>.
                                    <?php if (!empty($vistoria['observacao_admin'])): ?>
                                        <br><br>Observacao do admin: <?= h($vistoria['observacao_admin']) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 12px;">
                                    <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                        Voltar para aprovacoes
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-review-panel">
                        <h4><i class="fas fa-plus-circle"></i> Exigencia manual do analista</h4>
                        <div class="admin-review-body">
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=adicionar_exigencia_analista">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
                                    <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']); ?>">
                                    <div class="form-group mb-3">
                                        <label for="analista_bloco">Tipo</label>
                                        <select id="analista_bloco" name="bloco_vistoria" class="form-control">
                                            <?php foreach ($blocos_vistoria_todos as $valorBloco => $rotuloBloco): ?>
                                                <option value="<?= h($valorBloco); ?>"><?= h($rotuloBloco); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_descricao">Descricao da exigencia *</label>
                                        <textarea id="analista_descricao" name="descricao" class="form-control" rows="3" required placeholder="Descreva a pendencia encontrada na revisao."></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_normam">Referencia normativa</label>
                                        <input type="text" id="analista_normam" name="item_normam" class="form-control" placeholder="Ex.: NORMAM-202/DPC, item 4.14">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_observacao">Observacao interna</label>
                                        <textarea id="analista_observacao" name="observacao" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label style="display:flex; gap:8px; align-items:center;">
                                            <input type="checkbox" name="sem_prazo" value="1" checked>
                                            A/S — Antes de suspender
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Adicionar exigencia ao relatorio
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Relatorio finalizado. Novas exigencias manuais ficam bloqueadas para preservar o historico.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-review-panel">
                        <h4><i class="fas fa-list-check"></i> Exigencias registradas</h4>
                        <div class="admin-review-body">
                            <?php if (empty($exigencias_relatorio)): ?>
                                <p class="text-muted">Nenhuma exigencia registrada neste relatorio.</p>
                            <?php else: ?>
                                <div class="admin-review-exigencias">
                                    <?php foreach ($exigencias_relatorio as $exReview): ?>
                                        <div class="admin-review-exigencia">
                                            <strong><?= h($exReview['descricao'] ?? $exReview['catalogo_descricao'] ?? 'Exigencia sem descricao') ?></strong>
                                            <small><?= h($exReview['item_normam'] ?? $exReview['catalogo_item_normam'] ?? 'Sem referencia normativa') ?></small>
                                            <?php if (!empty($exReview['observacao'])): ?>
                                                <small>Obs.: <?= h($exReview['observacao']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php else: ?>
        <!-- ===== FORMULARIO RELATORIO TECNICO ===== -->
        <form action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_relatorio" method="POST" class="form-padrao" id="formRelatorio">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="agendamento_id" value="<?php echo h($agendamento_id); ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="vistoria_id" value="<?php echo h($vistoria['id']); ?>">
            <?php endif; ?>

            <?php if ($eh_relatorio_cumprimento): ?>
                <div style="margin:20px;padding:18px;border:1px solid #f59e0b;border-radius:10px;background:rgba(245,158,11,.08);">
                    <h4 style="margin-top:0;"><i class="fas fa-clipboard-check"></i> Relatório de Verificação de Cumprimento de Exigências</h4>
                    <p>Relatório substituto de <strong><?= h($vistoria['relatorio_anterior_id']) ?></strong>. Classifique somente as exigências pendentes copiadas do relatório anterior.</p>
                </div>
                <div style="padding:0 20px 20px;display:grid;gap:14px;">
                    <div class="form-group">
                        <label for="data_vistoria">Data da verificação *</label>
                        <input type="date" id="data_vistoria" name="data_vistoria" required value="<?= h($vistoria['data_vistoria'] ?? date('Y-m-d')) ?>">
                    </div>
                    <?php foreach ($exigencias_avulsas as $ex): ?>
                        <article style="padding:16px;border:1px solid var(--cor-borda,#444);border-radius:9px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                <div>
                                    <strong><?= h($ex['descricao'] ?: $ex['item']) ?></strong>
                                    <?php if (!empty($ex['item_normam'])): ?><small style="display:block;">NORMAM: <?= h($ex['item_normam']) ?></small><?php endif; ?>
                                </div>
                                <?php if (!empty($ex['antes_de_suspender'])): ?><span class="badge bg-danger">A/S — Antes de suspender</span><?php endif; ?>
                            </div>
                            <div class="form-group" style="margin-top:12px;">
                                <label for="cumprimento_<?= h($ex['id']) ?>">Resultado da verificação *</label>
                                <select id="cumprimento_<?= h($ex['id']) ?>" name="cumprimento_status[<?= h($ex['id']) ?>]" required>
                                    <option value="pendente" <?= ($ex['status_item'] ?? '') === 'pendente' ? 'selected' : '' ?>>Não cumprida / transcrita</option>
                                    <option value="cumprida" <?= ($ex['status_item'] ?? '') === 'cumprida' ? 'selected' : '' ?>>Cumprida</option>
                                    <option value="cumprida_parcial_reescrita" <?= ($ex['status_item'] ?? '') === 'cumprida_parcial_reescrita' ? 'selected' : '' ?>>Parcialmente cumprida / reescrita</option>
                                    <option value="nao_cumprida_transcrita" <?= ($ex['status_item'] ?? '') === 'nao_cumprida_transcrita' ? 'selected' : '' ?>>Não cumprida / transcrita</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observações e evidências verificadas</label>
                                <textarea name="cumprimento_observacao[<?= h($ex['id']) ?>]" rows="3" placeholder="Descreva o que foi verificado e as evidências apresentadas."><?= h($ex['observacao'] ?? '') ?></textarea>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <div class="form-group">
                        <label for="observacoes_tecnicas">Observações técnicas gerais</label>
                        <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"><?= h($vistoria['observacoes_tecnicas'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="padding:14px;border:1px solid #f59e0b;border-radius:8px;background:rgba(245,158,11,.06);">
                        <label for="prazo_exigencias_dias">Validade do relatório para certificação *</label>
                        <select id="prazo_exigencias_dias" name="prazo_exigencias_dias" required style="max-width:280px;">
                            <option value="" <?= $prazo_exigencias_dias === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                            <option value="60" <?= $prazo_exigencias_dias === '60' ? 'selected' : '' ?>>60 dias</option>
                            <option value="90" <?= $prazo_exigencias_dias === '90' ? 'selected' : '' ?>>90 dias</option>
                        </select>
                        <small class="text-muted" style="display:block;margin-top:6px;">
                            A validade será contada a partir da data desta verificação e usada na emissão do certificado. Quando definida no relatório anterior, a opção é herdada automaticamente.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="status_vistoria">Situação do relatório *</label>
                        <select id="status_vistoria" name="status_vistoria" required>
                            <option value="PENDENTE" <?= ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : '' ?>>Salvar como pendente</option>
                            <option value="AGUARDANDO_APROVACAO" <?= ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : '' ?>>Enviar para análise</option>
                        </select>
                    </div>
                </div>
            <?php else: ?>

            <div style="margin: 20px 20px 0; padding: 14px 16px; border: 1px solid var(--cor-destaque); border-radius: 8px; background: rgba(46, 204, 113, 0.08);">
                <strong style="display:block; margin-bottom: 8px; color: var(--cor-destaque);">
                    <i class="fas fa-user-check"></i> Responsável pelo fechamento da proposta
                </strong>
                <div><?php echo h($ag['contato_nome'] ?: 'Não informado'); ?></div>
                <small class="text-muted">
                    Telefone:
                    <?php if (!empty($ag['contato_telefone'])): ?>
                        <a href="tel:<?php echo h(preg_replace('/\D/', '', $ag['contato_telefone'])); ?>"><?php echo h($ag['contato_telefone']); ?></a>
                    <?php else: ?>
                        Não informado
                    <?php endif; ?>
                </small>
            </div>

            <!-- ===== DATA DA VISTORIA E ARMADOR ===== -->
            <div style="padding: 20px 20px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="data_vistoria">
                        <i class="fas fa-calendar-check"></i> Data da Realização da Vistoria *
                    </label>
                    <input type="date" id="data_vistoria" name="data_vistoria" class="form-control"
                           value="<?php echo h($vistoria['data_vistoria'] ?? $ag['data_vistoria']); ?>" required
                           style="background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                </div>

                <div class="form-group">
                    <label for="armador_id">
                        <i class="fas fa-user-tie"></i> Armador na data da Vistoria (Operador)
                    </label>
                    <select id="armador_id" name="armador_id" class="form-control" style="background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                        <option value="" style="background: #2a2a3e; color: #ddd;">-- Nenhum Armador Específico --</option>
                        <?php
                        try {
                            $stmtArm = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE perfil = 'armador' AND status = 'ATIVO' ORDER BY nome ASC");
                            while ($a = $stmtArm->fetch(PDO::FETCH_ASSOC)) {
                                $armadorAtualId = $vistoria['armador_id'] ?? $ag['armador_id'] ?? '';
                                $selected = ($armadorAtualId === $a['id']) ? 'selected' : '';
                                echo "<option value='".h($a['id'])."' $selected style='background: #2a2a3e; color: #ddd;'>".h($a['nome'])." (".h($a['cpf_cnpj']).")</option>";
                            }
                        } catch (Exception $e) {
                            error_log('Erro ao carregar armadores: ' . $e->getMessage());
                        }
                        ?>
                    </select>
                    <small class="text-muted" style="display:block; margin-top: 6px;">
                        Se o responsável presente for funcionário ou outra pessoa, digite o nome abaixo.
                    </small>
                    <input type="text"
                           id="operador_nome"
                           name="operador_nome"
                           class="form-control"
                           value="<?php echo h($vistoria['operador_nome'] ?? $ag['agendamento_operador_nome'] ?? ''); ?>"
                           placeholder="Nome do operador/responsável presente na vistoria"
                           style="margin-top: 8px; background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                </div>
            </div>

            <!-- ===== CHECKLIST DINAMICO ===== -->
            <div style="padding: 20px;" class="checklist-editor-section">
                <h4 style="margin: 0 0 15px 0; font-size: 1.1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-clipboard-check"></i> Checklist de Vistoria
                </h4>

                <div style="margin-bottom: 20px;">
                    <input type="text" id="buscaChecklist" class="form-control" placeholder="Buscar exigência pelo texto (filtra todas as seções)..." style="background: var(--cor-input-bg); color: var(--cor-texto); border: 1px solid var(--cor-borda); font-size: 1rem; padding: 12px;">
                </div>

                <div style="margin-bottom: 20px; padding: 14px; border: 1px solid var(--cor-borda, #444); border-radius: 6px; background: var(--cor-sidebar, #1a1a2e);">
                    <label for="prazo_exigencias_dias" style="display:block; margin-bottom: 6px; font-weight: 600;">
                        Prazo de validade do relatório <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="prazo_exigencias_dias"
                            name="prazo_exigencias_dias"
                            required
                            style="max-width: 280px; padding: 8px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                        <option value="" <?= $prazo_exigencias_dias === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                        <option value="60" <?= $prazo_exigencias_dias === '60' ? 'selected' : '' ?>>60 dias</option>
                        <option value="90" <?= $prazo_exigencias_dias === '90' ? 'selected' : '' ?>>90 dias</option>
                    </select>
                    <small style="display:block; margin-top: 6px; color: var(--cor-texto-secundario, #aaa);">
                        Selecione obrigatoriamente 60 ou 90 dias. Essa escolha define o vencimento das exigências e a validade do CSN. A/S significa “Antes de suspender” e bloqueia a embarcação e todos os certificados.
                    </small>
                </div>

                <div id="checklist-container">
                    <?php foreach ($checklist_categorias as $cat): ?>
                    <div class="checklist-section" data-cat="<?= $cat['id'] ?>">
                        <div class="checklist-header" onclick="toggleSection('cat_<?= $cat['id'] ?>')">
                            <span><?= h($cat['nome']) ?> <span style="color:#aaa; font-weight:normal;">(<?= count($cat['itens']) ?> itens)</span></span>
                            <i class="fas fa-chevron-down icone-toggle"></i>
                        </div>
                        <div class="checklist-body" id="cat_<?= $cat['id'] ?>">
                            <?php foreach ($cat['itens'] as $item):
                                $resp = $checklist_respostas[$item['id']] ?? null;
                                $status = $resp['status'] ?? '';
                                $obs = $resp['observacao'] ?? '';
                                $venc = $resp['vencimento'] ?? '';
                                $semPrazo = ($status === 'NAO_CONFORME' && !empty($resp['sem_prazo']));
                            ?>
                            <div class="checklist-item" data-id="<?= $item['id'] ?>" data-text="<?= htmlspecialchars(strtolower($item['descricao'] . ' ' . $item['item_normam'])) ?>">
                                <div class="item-text"><?= h($item['descricao']) ?></div>
                                <?php if($item['item_normam']): ?>
                                    <span class="item-normam">Normam: <?= h($item['item_normam']) ?></span>
                                <?php endif; ?>

                                <div class="item-actions">
                                    <button type="button" class="btn-toggle conforme <?= $status === 'CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'CONFORME', this)">CONFORME</button>
                                    <button type="button" class="btn-toggle nao-conforme <?= $status === 'NAO_CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_CONFORME', this)">NÃO CONFORME</button>
                                    <button type="button" class="btn-toggle na <?= $status === 'NAO_SE_APLICA' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_SE_APLICA', this)">N/A</button>
                                </div>

                                <input type="hidden" name="checklist_id[]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="checklist_status[]" id="status_<?= $item['id'] ?>" value="<?= h($status) ?>">

                                <div class="item-details" id="details_<?= $item['id'] ?>" style="display: <?= $status === 'NAO_CONFORME' ? 'block' : 'none' ?>;">
                                    <label>Referência da NORMAM (Sobrescreve o padrão do catálogo)</label>
                                    <input type="text" name="checklist_item_normam[]" id="normam_<?= $item['id'] ?>" value="<?= h($resp['item_normam'] ?? $item['item_normam'] ?? '') ?>" placeholder="Ex: NORMAM-202/DPC, Cap. 02, Item 2.1.">

                                    <label>Observação curta (vai para o relatório)</label>
                                    <input type="text" name="checklist_observacao[]" id="obs_<?= $item['id'] ?>" value="<?= h($obs) ?>" placeholder="Especifique o problema encontrado...">

                                    <input type="hidden" name="checklist_sem_prazo[]" id="sem_prazo_<?= $item['id'] ?>" value="<?= $semPrazo ? '1' : '0' ?>">
                                    <label style="display:flex; align-items:center; gap:8px; margin-top: 10px;">
                                        <input type="checkbox"
                                               class="checklist-sem-prazo"
                                               data-target="sem_prazo_<?= $item['id'] ?>"
                                               <?= $semPrazo ? 'checked' : '' ?>>
                                        A/S — Antes de suspender
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ===== EXIGÊNCIAS AVULSAS ===== -->
            <div style="padding: 20px;" class="avulsa-section">
                <h4 style="margin: 0 0 15px 0; font-size: 1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-plus-circle"></i> Exigências Avulsas (Fora do Catálogo)
                </h4>
                <small class="text-muted">Adicione itens pendentes que não constam no checklist acima.</small>

                <div style="margin: 15px 0;" class="no-print">
                    <button type="button" class="btn btn-sm btn-primary avulsa-add-button" onclick="adicionarLinhaAvulsa()">
                        <i class="fas fa-plus"></i> Adicionar Item Avulso
                    </button>
                </div>

                <div id="avulsaEmpty" class="avulsa-empty <?= empty($exigencias_avulsas) ? '' : 'is-hidden' ?>">
                    <i class="fas fa-clipboard-check"></i>
                    Nenhuma exigência avulsa adicionada.
                </div>
                <div id="avulsaTableWrap" class="avulsa-table-wrap <?= empty($exigencias_avulsas) ? 'is-hidden' : '' ?>">
                <table id="tabelaExigenciasAvulsas" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--cor-sidebar, #1a1a2e); border-bottom: 2px solid var(--cor-borda);">
                            <th style="width: 40px; text-align: center; padding: 8px 6px;">#</th>
                            <th style="width: 170px; text-align: left; padding: 8px 6px;">Tipo de Vistoria</th>
                            <th style="text-align: left; padding: 8px 6px;">Descrição da Exigência *</th>
                            <th style="text-align: left; padding: 8px 6px;">Item da NORMAM</th>
                            <th style="width: 120px; text-align: center; padding: 8px 6px;">Situação</th>
                            <th style="text-align: left; padding: 8px 6px;">Observacao / Justificativa</th>
                            <th style="width: 130px; text-align: center; padding: 8px 6px;">A/S — Antes de suspender</th>
                            <th style="width: 40px; text-align: center; padding: 8px 6px;" class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exigencias_avulsas as $idx => $ex): ?>
                        <tr class="linha-exigencia-avulsa">
                            <td data-label="Ordem" style="text-align: center; padding: 6px;">
                                <span class="ordem-num-avulsa"><?php echo (int)$ex['ordem']; ?></span>
                                <input type="hidden" name="exigencia_id[]" value="<?php echo h($ex['id'] ?? ''); ?>">
                                <input type="hidden" name="exigencia_ordem[]" value="<?php echo (int)$ex['ordem']; ?>" class="ordem-input-avulsa">
                            </td>
                            <td data-label="Tipo" style="padding: 6px;">
                                <?php $blocoAtual = $ex['bloco_vistoria'] ?? $bloco_vistoria_padrao; ?>
                                <select name="exigencia_bloco[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <?php foreach ($blocos_vistoria_disponiveis as $valorBloco => $rotuloBloco): ?>
                                        <option value="<?php echo h($valorBloco); ?>" <?php echo $blocoAtual === $valorBloco ? 'selected' : ''; ?>>
                                            <?php echo h($rotuloBloco); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Descrição" style="padding: 6px;">
                                <input type="text" name="exigencia_descricao[]" value="<?php echo h($ex['descricao'] ?? ''); ?>"
                                       placeholder="Ex.: nao tem seguranca" required
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="NORMAM" style="padding: 6px;">
                                <input type="text" name="exigencia_item[]" value="<?php echo h($ex['item']); ?>"
                                       placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="Situação" style="padding: 6px; text-align: center;">
                                <select name="status_item[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <option value="inserida" <?php echo ($ex['status_item'] ?? 'inserida') === 'inserida' ? 'selected' : ''; ?>>Inserida / N/A</option>
                                    <option value="pendente" <?php echo ($ex['status_item'] ?? '') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="cumprida" <?php echo ($ex['status_item'] ?? '') === 'cumprida' ? 'selected' : ''; ?>>Cumprida</option>
                                </select>
                            </td>
                            <td data-label="Observação" style="padding: 6px;">
                                <input type="text" name="exigencia_observacao[]" value="<?php echo h($ex['observacao'] ?? ''); ?>"
                                       placeholder="Observacao"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="Sem prazo" style="padding: 6px; text-align: center;">
                                <?php $avulsaSemPrazo = !empty($ex['antes_de_suspender']); ?>
                                <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="<?php echo $avulsaSemPrazo ? '1' : '0'; ?>">
                                <label style="display:inline-flex; align-items:center; gap:6px;">
                                    <input type="checkbox" class="avulsa-sem-prazo-check" <?php echo $avulsaSemPrazo ? 'checked' : ''; ?>>
                                    A/S
                                </label>
                            </td>
                            <td data-label="Ações" style="text-align: center; padding: 6px;" class="no-print">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover" aria-label="Remover exigência avulsa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- ===== OBSERVACOES TECNICAS ===== -->
            <div style="padding: 0 20px 20px;" class="observacoes-section">
                <div class="form-group">
                    <label for="observacoes_tecnicas">
                        <i class="fas fa-sticky-note"></i> Observações Técnicas
                    </label>
                    <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"
                              placeholder="Observações técnicas gerais, recomendações, restrições encontradas..."
                              style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); resize: vertical;"><?php echo h($vistoria['observacoes_tecnicas'] ?? ''); ?></textarea>
                </div>

                <!-- Status da vistoria (resultado final) -->
                <div class="form-group" style="margin-top: 15px;">
                    <label for="status_vistoria">
                        <i class="fas fa-gavel"></i> Resultado Final da Vistoria *
                    </label>
                    <select id="status_vistoria" name="status_vistoria" required
                            style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); font-size: 1rem;">
                        <option value="PENDENTE" <?php echo ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>Pendente (relatório em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO" <?php echo ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <?php if (getCargo() === 'ADMIN'): ?>
                        <option value="REPROVADA" <?php echo ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                        <option value="CANCELADA" <?php echo ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <?php endif; ?>

            <!-- Deve permanecer como o ultimo campo do formulario. Se o PHP truncar
                 a requisicao por max_input_vars, o backend detecta sua ausencia. -->
            <input type="hidden" name="formulario_completo" value="1">

            <!-- ===== BOTOES ===== -->
            <div class="form-actions report-actions" style="padding: 0 20px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i class="fas fa-save"></i>
                    <?php echo $editando ? 'Atualizar Relatorio' : 'Salvar Relatorio'; ?>
                </button>
                <span id="rascunhoRelatorioStatus" class="text-muted" style="font-size:.8rem;">
                    <i class="fas fa-cloud-arrow-down"></i> Preenchimento preservado automaticamente neste navegador.
                </span>
                <?php if ($editando && !empty($vistoria['id'])): ?>
                    <a href="<?php echo APP_URL; ?>vistorias/relatorio_pdf.php?id=<?php echo urlencode($vistoria['id']); ?>" target="_blank" class="btn btn-info" style="color: #fff;">
                        <i class="fas fa-file-pdf"></i> Visualizar Relatório
                    </a>
                <?php endif; ?>
                <?php if ($editando && $pode_ir_etapa2): ?>
                    <a href="<?php echo APP_URL; ?>documentacao/novo_certificado?agendamento_id=<?php echo urlencode($agendamento_id); ?>" class="btn btn-success">
                        <i class="fas fa-certificate"></i> Gerar Certificado
                    </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <?php if ($editando): ?>
                    <span class="text-muted" style="margin-left: 15px; font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i>
                        Ao salvar com status <strong>Aprovada</strong> ou <strong>Reprovada</strong>,
                        a OS avanca para <strong>"Executada"</strong> automaticamente.
                    </span>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$admin_review_mode): ?>
<script>
// Preserva o preenchimento mesmo quando uma validação do servidor redirecionar
// de volta ao relatório. Dados de segurança e arquivos nunca são armazenados.
(function preservarRascunhoRelatorio() {
    const form = document.getElementById('formRelatorio');
    if (!form || typeof localStorage === 'undefined') return;

    const draftKey = <?= json_encode('erp:relatorio:rascunho:' . $agendamento_id . ':' . ($vistoria['id'] ?? 'novo'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const ignorados = new Set(['csrf_token', 'formulario_completo', 'agendamento_id', 'vistoria_id']);
    const status = document.getElementById('rascunhoRelatorioStatus');

    function controlesPersistiveis() {
        return Array.from(form.elements).filter(function(campo) {
            return campo.name && !ignorados.has(campo.name) && !['file', 'submit', 'button', 'password'].includes(campo.type);
        });
    }

    function salvar() {
        try {
            const ocorrencias = Object.create(null);
            const campos = controlesPersistiveis().map(function(campo) {
                const indice = ocorrencias[campo.name] || 0;
                ocorrencias[campo.name] = indice + 1;
                return {
                    nome: campo.name,
                    indice: indice,
                    valor: campo.value,
                    marcado: campo.type === 'checkbox' || campo.type === 'radio' ? campo.checked : null
                };
            });
            localStorage.setItem(draftKey, JSON.stringify({campos: campos, salvoEm: Date.now()}));
            if (status) status.innerHTML = '<i class="fas fa-cloud-arrow-down"></i> Preenchimento preservado automaticamente.';
        } catch (e) {
            // O formulário continua funcionando normalmente se o armazenamento local estiver indisponível.
        }
    }

    function restaurar() {
        try {
            const bruto = localStorage.getItem(draftKey);
            if (!bruto) return;
            const rascunho = JSON.parse(bruto);
            const totalAvulsasSalvas = (rascunho.campos || []).filter(function(campo) {
                return campo.nome === 'exigencia_descricao[]';
            }).length;
            let totalAvulsasAtuais = form.querySelectorAll('[name="exigencia_descricao[]"]').length;
            while (totalAvulsasAtuais < totalAvulsasSalvas && typeof adicionarLinhaAvulsa === 'function') {
                adicionarLinhaAvulsa();
                totalAvulsasAtuais++;
            }
            const porChave = new Map((rascunho.campos || []).map(function(campo) {
                return [campo.nome + '::' + campo.indice, campo];
            }));
            const ocorrencias = Object.create(null);
            controlesPersistiveis().forEach(function(campo) {
                const indice = ocorrencias[campo.name] || 0;
                ocorrencias[campo.name] = indice + 1;
                const salvo = porChave.get(campo.name + '::' + indice);
                if (!salvo) return;
                if (campo.type === 'checkbox' || campo.type === 'radio') campo.checked = !!salvo.marcado;
                else campo.value = salvo.valor;
            });
            form.querySelectorAll('.checklist-item').forEach(function(item) {
                const itemId = item.dataset.id;
                const valor = document.getElementById('status_' + itemId)?.value || '';
                item.querySelectorAll('.btn-toggle').forEach(function(botao) { botao.classList.remove('active'); });
                const seletor = valor === 'CONFORME' ? '.btn-toggle.conforme'
                    : (valor === 'NAO_CONFORME' ? '.btn-toggle.nao-conforme'
                    : (valor === 'NAO_SE_APLICA' ? '.btn-toggle.na' : ''));
                if (seletor) item.querySelector(seletor)?.classList.add('active');
                const detalhes = document.getElementById('details_' + itemId);
                if (detalhes) detalhes.style.display = valor === 'NAO_CONFORME' ? 'block' : 'none';
                const asOculto = document.getElementById('sem_prazo_' + itemId);
                const asCheck = item.querySelector('.checklist-sem-prazo');
                if (asOculto && asCheck) asCheck.checked = asOculto.value === '1';
            });
            if (status) status.innerHTML = '<i class="fas fa-rotate-left"></i> Preenchimento anterior restaurado.';
        } catch (e) {
            localStorage.removeItem(draftKey);
        }
    }

    const salvoComSucesso = new URLSearchParams(window.location.search).get('salvo') === '1';
    if (salvoComSucesso) {
        localStorage.removeItem(draftKey);
        const urlLimpa = new URL(window.location.href);
        urlLimpa.searchParams.delete('salvo');
        window.history.replaceState({}, '', urlLimpa.toString());
    } else window.setTimeout(restaurar, 0);

    let temporizador = null;
    function agendarSalvamento() {
        window.clearTimeout(temporizador);
        temporizador = window.setTimeout(salvar, 120);
    }
    form.addEventListener('input', agendarSalvamento);
    form.addEventListener('change', salvar);
    form.addEventListener('click', function() { window.setTimeout(salvar, 0); });
    form.addEventListener('submit', salvar);
})();

// Toggle Accordions
function toggleSection(id) {
    const body = document.getElementById(id);
    const icon = body.previousElementSibling.querySelector('.icone-toggle');
    if (body.style.display === 'block') {
        body.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

// Checklist Item Status
function setStatus(itemId, status, btnElement) {
    // Atualiza input hidden
    document.getElementById('status_' + itemId).value = status;

    // Atualiza botoes
    const parent = btnElement.closest('.item-actions');
    parent.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');

    // Mostra div de observação e vencimento se N?O CONFORME
    const detailsDiv = document.getElementById('details_' + itemId);
    if (status === 'NAO_CONFORME') {
        detailsDiv.style.display = 'block';
        // Foca no campo observação se acabou de abrir
        const obsInput = detailsDiv.querySelector('input[name="checklist_observacao[]"]');
        if(obsInput) obsInput.focus();
    } else {
        detailsDiv.style.display = 'none';
        // Limpa campos para não enviar dados lixo caso mude de ideia
        document.getElementById('obs_' + itemId).value = '';
        const semPrazoInput = document.getElementById('sem_prazo_' + itemId);
        if (semPrazoInput) semPrazoInput.value = '0';
        const semPrazoCheck = detailsDiv.querySelector('.checklist-sem-prazo');
        if (semPrazoCheck) semPrazoCheck.checked = false;
    }
}

document.querySelectorAll('.checklist-sem-prazo').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) target.value = this.checked ? '1' : '0';
    });
});

// Busca / Filtro do Checklist
document.getElementById('buscaChecklist').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const sections = document.querySelectorAll('.checklist-section');

    sections.forEach(section => {
        let hasVisible = false;
        const items = section.querySelectorAll('.checklist-item');

        items.forEach(item => {
            const text = item.getAttribute('data-text');
            if (term === '' || text.indexOf(term) > -1) {
                item.style.display = 'block';
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        if (hasVisible) {
            section.style.display = 'block';
            // Se está buscando algo, abre o accordion automaticamente
            if (term !== '') {
                const body = section.querySelector('.checklist-body');
                const icon = section.querySelector('.icone-toggle');
                body.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        } else {
            section.style.display = 'none';
        }
    });
});

// Tabela Avulsa
let contadorLinhasAvulsa = <?php echo count($exigencias_avulsas); ?>;
const blocosVistoriaAvulsa = <?php echo json_encode($blocos_vistoria_disponiveis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const blocoVistoriaPadrao = <?php echo json_encode($bloco_vistoria_padrao); ?>;

function opcoesBlocoVistoriaAvulsa(valorSelecionado) {
    return Object.entries(blocosVistoriaAvulsa).map(function([valor, rotulo]) {
        const selected = valor === valorSelecionado ? ' selected' : '';
        return `<option value="${valor}"${selected}>${rotulo}</option>`;
    }).join('');
}

function adicionarLinhaAvulsa() {
    contadorLinhasAvulsa++;
    const tbody = document.querySelector('#tabelaExigenciasAvulsas tbody');
    const tr = document.createElement('tr');
    tr.className = 'linha-exigencia-avulsa';

    tr.innerHTML = `
        <td data-label="Ordem" style="text-align: center; padding: 6px;">
            <span class="ordem-num-avulsa">${contadorLinhasAvulsa}</span>
            <input type="hidden" name="exigencia_id[]" value="">
            <input type="hidden" name="exigencia_ordem[]" value="${contadorLinhasAvulsa}" class="ordem-input-avulsa">
        </td>
        <td data-label="Tipo" style="padding: 6px;">
            <select name="exigencia_bloco[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                ${opcoesBlocoVistoriaAvulsa(blocoVistoriaPadrao)}
            </select>
        </td>
        <td data-label="Descrição" style="padding: 6px;">
            <input type="text" name="exigencia_descricao[]" value=""
                   placeholder="Ex.: nao tem seguranca" required
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="NORMAM" style="padding: 6px;">
            <input type="text" name="exigencia_item[]" value=""
                   placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="Situação" style="padding: 6px; text-align: center;">
            <select name="status_item[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                <option value="pendente">Pendente</option>
                <option value="cumprida">Cumprida</option>
            </select>
        </td>
        <td data-label="Observação" style="padding: 6px;">
            <input type="text" name="exigencia_observacao[]" value=""
                   placeholder="Observacao"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="Sem prazo" style="padding: 6px; text-align: center;">
            <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="1">
            <label style="display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" class="avulsa-sem-prazo-check" checked>
                AS
            </label>
        </td>
        <td data-label="Ações" style="text-align: center; padding: 6px;" class="no-print">
            <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover" aria-label="Remover exigência avulsa">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    vincularSemPrazoAvulsa(tr);
    renumerarLinhasAvulsas();
    atualizarEstadoTabelaAvulsa();
}

function vincularSemPrazoAvulsa(contexto) {
    contexto.querySelectorAll('.avulsa-sem-prazo-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const input = this.closest('td').querySelector('.avulsa-sem-prazo-input');
            if (input) input.value = this.checked ? '1' : '0';
        });
    });
}

document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr').forEach(vincularSemPrazoAvulsa);

function removerLinhaAvulsa(btn) {
    btn.closest('tr').remove();
    renumerarLinhasAvulsas();
    atualizarEstadoTabelaAvulsa();
}

function atualizarEstadoTabelaAvulsa() {
    const temLinhas = document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr.linha-exigencia-avulsa').length > 0;
    document.getElementById('avulsaEmpty').classList.toggle('is-hidden', temLinhas);
    document.getElementById('avulsaTableWrap').classList.toggle('is-hidden', !temLinhas);
}

function renumerarLinhasAvulsas() {
    const rows = document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr.linha-exigencia-avulsa');
    rows.forEach((row, i) => {
        const num = i + 1;
        row.querySelector('.ordem-num-avulsa').textContent = num;
        row.querySelector('.ordem-input-avulsa').value = num;
    });
    contadorLinhasAvulsa = rows.length;
}

atualizarEstadoTabelaAvulsa();

// Confirmacao ao salvar com status final
document.getElementById('formRelatorio').addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria').value;
    if (status === 'APROVADA' || status === 'REPROVADA') {
        const msg = status === 'APROVADA'
            ? 'Ao salvar como APROVADA, a Ordem de Servico sera marcada como EXECUTADA e os certificados serao liberados. Deseja continuar?'
            : 'Ao salvar como REPROVADA, a Ordem de Servico sera marcada como EXECUTADA. Deseja continuar?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});
</script>
<?php else: ?>
<script>
document.getElementById('formDecisaoAdmin')?.addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria_admin').value;
    const observacao = document.getElementById('observacao_admin').value.trim();

    if (!status) {
        e.preventDefault();
        alert('Selecione o resultado final da vistoria.');
        return;
    }

    if (status === 'REPROVADA' && !observacao) {
        e.preventDefault();
        alert('Informe uma observacao para reprovar o relatorio.');
        return;
    }

    const labels = {
        PENDENTE: 'Pendente',
        AGUARDANDO_APROVACAO: 'Aguardando Aprovacao',
        APROVADA: 'Aprovada',
        APROVADA_COM_EXIGENCIAS: 'Aprovada com Exigencias',
        REPROVADA: 'Reprovada',
        CANCELADA: 'Cancelada'
    };
    const texto = 'Confirmar resultado final como ' + (labels[status] || status) + '?';
    if (!confirm(texto)) {
        e.preventDefault();
    }
});
</script>
<?php endif; ?>

<?php renderAprovacaoUi($pdo); require_once __DIR__ . '/../../includes/footer.php'; ?>
