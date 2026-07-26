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
require_once __DIR__ . '/../../includes/aprovacao_documentos.php';

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

    // Consulta do analista é somente leitura e limitada às embarcações atribuídas a ele.
    if ($cargo === 'ANALISTA') {
        $stmtAnalise = $pdo->prepare('SELECT 1 FROM analises_planos WHERE embarcacao_id=:embarcacao AND analista_id=:usuario LIMIT 1');
        $stmtAnalise->execute([':embarcacao' => $ag['embarcacao_id'], ':usuario' => $usuario_id]);
        if (!$stmtAnalise->fetchColumn()) {
            setMensagem('error', 'Acesso negado. O relatório não pertence a uma embarcação atribuída à sua análise.');
            redirecionar(APP_URL . 'analises-planos');
        }
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
        $stmtResp = $pdo->prepare("
            SELECT r.*,
                   CASE
                     WHEN EXISTS (
                         SELECT 1
                         FROM vistoria_exigencias ve
                         WHERE ve.vistoria_id = r.vistoria_id
                           AND ve.catalogo_id = r.catalogo_id
                           AND ve.antes_de_suspender = 1
                           AND ve.conforme = 'nao'
                           AND ve.status_item <> 'cumprida'
                     ) THEN 1
                     ELSE COALESCE(r.sem_prazo, 0)
                   END AS sem_prazo
            FROM vistoria_checklist_respostas r
            WHERE r.vistoria_id = :v
        ");
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
$regra_edicao_relatorio = $editando
    ? avaliarEdicaoRelatorio($pdo, array_merge($vistoria, ['vistoriador_id' => $ag['vistoriador_id'] ?? null]), (string)$usuario_id, (string)$cargo)
    : ['permitido' => $cargo === 'VISTORIADOR' && ($ag['vistoriador_id'] ?? '') === $usuario_id, 'mensagem' => ''];
$pode_editar_relatorio = (bool)$regra_edicao_relatorio['permitido'];
$admin_review_mode = $editando && !$pode_editar_relatorio;
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
if ($vistoria && $possui_as_pendente && (string)$vistoria['status'] === 'RETORNO_AS') {
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
$relatorio_vigente_cadeia = null;
$eh_relatorio_vigente = true;
$cadeia_relatorios = [];
$retorno_as = null;
$relatorio_anterior_numero_ui = '';
if ($vistoria) {
    $cadeia_relatorios = obterCadeiaRelatorios($pdo, (string)$vistoria['id']);
    foreach ($cadeia_relatorios as $indiceCadeiaUi => $itemCadeiaUi) {
        if ((string)$itemCadeiaUi['id'] === (string)$vistoria['id'] && $indiceCadeiaUi > 0) {
            $relatorio_anterior_numero_ui = (string)($cadeia_relatorios[$indiceCadeiaUi - 1]['numero'] ?? '');
            break;
        }
    }
    $relatorio_vigente_cadeia = obterRelatorioVigenteCadeia($pdo, (string)$vistoria['id']);
    $eh_relatorio_vigente = !empty($relatorio_vigente_cadeia)
        && (string)$relatorio_vigente_cadeia['id'] === (string)$vistoria['id'];
    $stmtRetornoAs = $pdo->prepare("SELECT vr.*,a.data_vistoria,a.local,u.nome vistoriador_nome
        FROM vistoria_retornos vr
        LEFT JOIN agendamentos a ON a.id=vr.agendamento_id
        LEFT JOIN usuarios u ON u.id=a.vistoriador_id
        WHERE vr.relatorio_origem_id=:id LIMIT 1");
    $stmtRetornoAs->execute([':id' => $vistoria['id']]);
    $retorno_as = $stmtRetornoAs->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$eh_relatorio_vigente) {
        $relatorio_substituto_aprovado = $relatorio_vigente_cadeia;
    }
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
.report-inspection-card, .report-work-card, .avulsa-section, .observacoes-section { margin: 16px 20px 0; border: 1px solid var(--cor-borda, #d9e2df); border-radius: 12px; overflow: hidden; background: var(--cor-fundo-card, #fff); }
.report-inspection-card, .report-work-card { padding: 0 !important; }
.avulsa-section, .observacoes-section { padding: 18px !important; }
.report-section-heading { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid var(--cor-borda, #d9e2df); background: rgba(46,204,113,.055); }
.report-section-heading > div { display: flex; align-items: center; gap: 12px; }
.report-section-heading > div > i { width: 36px; height: 36px; display: grid; place-items: center; color: var(--cor-destaque, #169b67); background: rgba(46,204,113,.12); border-radius: 9px; }
.report-section-heading span { display: flex; flex-direction: column; gap: 2px; }
.report-section-heading strong { color: var(--cor-texto, #18332c); font-size: 1rem; }
.report-section-heading small, .report-field-help { color: var(--cor-texto-secundario, #72827d); line-height: 1.4; }
.report-inspection-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; padding: 18px; }
.report-inspection-column { display: flex; flex-direction: column; gap: 16px; }
.report-inspection-column .form-group { margin: 0; }
.report-inspection-column .form-control { color: var(--cor-texto, #263a34); background: var(--cor-input-bg, #fff); border: 1px solid var(--cor-borda, #cfdad6); }
.report-inspection-column #operador_nome { margin-top: 8px; }
.report-validity-field { padding: 14px; border: 1px solid rgba(22,155,103,.22); border-radius: 9px; background: rgba(46,204,113,.045); }
.report-validity-field select { max-width: 280px; }
.report-field-help { display: block; margin-top: 7px; }
.required-mark { color: #dc3545; }
.checklist-summary { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 10px; padding: 16px 18px 8px; }
.checklist-summary > div { min-width: 0; display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid var(--cor-borda, #d9e2df); border-radius: 10px; background: rgba(127,145,138,.045); }
.checklist-summary-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 8px; }
.checklist-summary-icon.is-progress { color: #087a58; background: #dff5ec; }
.checklist-summary-icon.is-pending { color: #936516; background: #fff1ce; }
.checklist-summary-icon.is-danger { color: #b42318; background: #fee4e2; }
.checklist-summary-icon.is-as { color: #8a1c13; background: #ffd5d2; }
.checklist-summary small { display: block; color: var(--cor-texto-secundario, #72827d); font-size: .72rem; }
.checklist-summary strong { display: block; color: var(--cor-texto, #18332c); font-size: 1.08rem; }
.checklist-search { position: relative; padding: 8px 18px 16px; }
.checklist-search > i { position: absolute; left: 34px; top: 50%; transform: translateY(-64%); color: var(--cor-texto-secundario, #72827d); pointer-events: none; }
.checklist-search input { min-height: 46px; padding-left: 44px !important; background: var(--cor-input-bg, #fff); color: var(--cor-texto, #263a34); border: 1px solid var(--cor-borda, #cfdad6); }
#checklist-container { padding: 0 18px 18px; }
.checklist-section { margin-bottom: 11px; border: 1px solid var(--cor-borda, #d9e2df); border-radius: 10px; overflow: hidden; }
.checklist-section:last-child { margin-bottom: 0; }
.checklist-header { width: 100%; border: 0; color: var(--cor-texto, #18332c); background: var(--cor-sidebar, #f7faf9); padding: 13px 15px; cursor: pointer; display: grid; grid-template-columns: minmax(180px,1fr) auto 18px; gap: 12px; align-items: center; font: inherit; font-weight: bold; text-align: left; }
.checklist-header:hover { background: rgba(46,204,113,.10); }
.checklist-header:focus-visible { outline: 3px solid rgba(46,204,113,.35); outline-offset: -3px; }
.checklist-category-metrics { display: flex; align-items: center; justify-content: flex-end; gap: 7px; flex-wrap: wrap; font-size: .72rem; font-weight: 700; }
.checklist-category-metrics > span { padding: 4px 8px; border-radius: 999px; white-space: nowrap; }
.category-progress { color: #276b58; background: #e5f5ef; }
.category-issues { color: #a52b22; background: #fee9e7; }
.category-issues.is-zero { color: #64756f; background: #edf2f0; }
.category-as { color: #fff; background: #b42318; }
.is-hidden { display: none !important; }
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
.checklist-no-results { margin: 0 18px 18px; padding: 28px; display: flex; flex-direction: column; align-items: center; gap: 5px; color: var(--cor-texto-secundario, #72827d); text-align: center; border: 1px dashed var(--cor-borda, #cfdad6); border-radius: 10px; background: rgba(127,145,138,.04); }
.checklist-no-results i { margin-bottom: 4px; color: var(--cor-destaque, #169b67); font-size: 1.3rem; }
.checklist-no-results strong { color: var(--cor-texto, #18332c); }
.report-pdf-after-save { display: inline-flex; align-items: center; gap: 7px; padding: 9px 12px; color: var(--cor-texto-secundario, #72827d); background: rgba(127,145,138,.08); border: 1px dashed var(--cor-borda, #cfdad6); border-radius: 7px; font-size: .82rem; }
.report-footer-pdf { font-weight: 700; }
.report-draft-status { flex-basis: 100%; font-size: .8rem; }
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
    .report-inspection-card, .report-work-card, .avulsa-section, .observacoes-section { margin: 12px 12px 0; }
    .avulsa-section, .observacoes-section { padding: 14px !important; }
    .report-inspection-grid { grid-template-columns: 1fr; gap: 16px; padding: 15px; }
    .report-section-heading { padding: 14px 15px; }
    .report-section-heading small { font-size: .76rem; }
    .report-validity-field select { max-width: none; }
    .checklist-summary { grid-template-columns: repeat(2,minmax(0,1fr)); padding: 14px 14px 7px; gap: 8px; }
    .checklist-summary > div { padding: 10px; }
    .checklist-search { padding: 7px 14px 14px; }
    .checklist-search > i { left: 29px; }
    #checklist-container { padding: 0 14px 14px; }
    .checklist-header { min-height: 58px; padding: 12px; gap: 8px; line-height: 1.35; grid-template-columns: 1fr 18px; }
    .checklist-category-name { grid-column: 1; }
    .checklist-category-metrics { grid-column: 1; justify-content: flex-start; }
    .checklist-header .icone-toggle { grid-column: 2; grid-row: 1 / span 2; }
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
    .report-pdf-after-save { width: 100%; min-height: 44px; justify-content: center; box-sizing: border-box; }
    .report-actions .text-muted { margin: 4px 0 0 !important; line-height: 1.45; }
}
@media (max-width: 420px) {
    .checklist-summary { grid-template-columns: 1fr; }
}
</style>

<!-- BOT?O ETAPA 2 (somente ADMIN, somente quando aprovado) -->
<?php if (count($cadeia_relatorios) > 1): ?>
    <div class="form-container" style="margin-bottom:20px">
        <div class="form-header"><h3><i class="fas fa-timeline"></i> Linha do tempo dos relatórios A/S</h3></div>
        <div style="display:grid;gap:10px;padding:16px">
            <?php foreach ($cadeia_relatorios as $indiceCadeia => $itemCadeia): ?>
                <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;padding:12px;border:1px solid #dce8e4;border-radius:9px">
                    <span>
                        <strong><?= h($itemCadeia['numero'] ?: $itemCadeia['id']) ?></strong>
                        · <?= $indiceCadeia === 0 ? 'Relatório técnico original' : 'Cumprimento de A/S' ?>
                        · <?= h($itemCadeia['status']) ?>
                    </span>
                    <a class="btn btn-secondary btn-sm" target="_blank" rel="noopener"
                       href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($itemCadeia['id']) ?>">PDF</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?php if ($relatorio_substituto_aprovado): ?>
    <div class="alert alert-info" style="margin-bottom:20px;">
        <strong>Relatório histórico/substituído — somente leitura.</strong>
        Nenhuma decisão pode ser registrada nesta versão. Use o relatório vigente
        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$relatorio_substituto_aprovado['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$relatorio_substituto_aprovado['id']) ?>"><?= h($relatorio_substituto_aprovado['numero']) ?></a>.
    </div>
<?php endif; ?>
<?php if ($vistoria && $possui_as_pendente && (string)$vistoria['status'] === 'RETORNO_AS'): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong>Certificação bloqueada por exigência A/S.</strong>
        A embarcação não pode receber certificados até a aprovação da verificação de cumprimento.
        <?php if ($relatorio_cumprimento_aberto_id): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>&vistoria_id=<?= urlencode($relatorio_cumprimento_aberto_id) ?>">Continuar verificação de cumprimento</a>
        <?php elseif (!$retorno_as && getCargo() === 'ADMIN'): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-plus"></i> Criar pendência de retorno A/S</button>
            </form>
        <?php endif; ?>
        <?php if ($retorno_as && $retorno_as['status'] === 'PENDENTE_AGENDAMENTO' && getCargo() === 'ADMIN'): ?>
            <a class="btn btn-warning ms-3" href="<?= APP_URL ?>agendamentos/form?relatorio_origem_id=<?= urlencode($vistoria['id']) ?>">
                <i class="fas fa-calendar-plus"></i> Agendar retorno A/S
            </a>
        <?php elseif ($retorno_as && $retorno_as['status'] === 'CANCELADO' && getCargo() === 'ADMIN'): ?>
            <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=iniciar_cumprimento_exigencias" style="display:inline-block;margin-left:12px;">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']) ?>">
                <button type="submit" class="btn btn-warning"><i class="fas fa-rotate-right"></i> Reabrir e agendar retorno</button>
            </form>
        <?php elseif ($retorno_as && in_array($retorno_as['status'], ['AGENDADO','RELATORIO_ENVIADO'], true)): ?>
            <span style="display:inline-block;margin-left:12px">
                Retorno: <strong><?= h($retorno_as['status']) ?></strong>
                <?= !empty($retorno_as['data_vistoria']) ? ' em ' . h(date('d/m/Y', strtotime($retorno_as['data_vistoria']))) : '' ?>
                <?= !empty($retorno_as['vistoriador_nome']) ? ' · ' . h($retorno_as['vistoriador_nome']) : '' ?>
            </span>
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
                'RETORNO_AS' => 'Retorno A/S necessario',
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
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' && $cargo === 'ADMIN' && $eh_relatorio_vigente): ?>
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
                                                <input type="radio" name="resultado_relatorio"
                                                       value="<?= $resumo_aprovacao_relatorio['pendentes_as'] > 0 ? 'RETORNO_AS' : 'APROVADA_COM_EXIGENCIAS' ?>"
                                                       <?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'checked' : 'disabled' ?>>
                                                <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                                    <span><strong>Encaminhar para Retorno A/S</strong><br><small>O relatório não será aprovado. O processo ficará aguardando o agendamento de uma nova visita.</small></span>
                                                <?php else: ?>
                                                    <span><strong>Aprovada com exig&ecirc;ncias</strong><br><small>Permite apenas certificados Provis&oacute;rio e Condicional.</small></span>
                                                <?php endif; ?>
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
                                        <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                            <div style="padding:14px;border:2px solid #d95047;border-radius:10px;background:#fff4f2">
                                                <strong style="display:block;color:#9f261f;margin-bottom:6px">
                                                    <i class="fas fa-ban"></i> Decis&atilde;o obrigat&oacute;ria: n&atilde;o aprovar
                                                </strong>
                                                <div style="margin-bottom:12px;color:#65322e">
                                                    Encaminhe este relat&oacute;rio para <strong>Retornos A/S</strong>. Depois, a pr&oacute;xima a&ccedil;&atilde;o no dashboard ser&aacute; agendar uma nova visita.
                                                </div>
                                                <?php if (($vistoria['assinatura_status'] ?? '') === 'ASSINADO'): ?>
                                                    <div style="margin-bottom:8px;color:#23754f"><i class="fas fa-circle-check"></i> Etapa 1 conclu&iacute;da: relat&oacute;rio assinado.</div>
                                                    <button type="submit" name="decisao" value="retorno_as"
                                                            class="btn btn-danger"
                                                            onclick="return confirm('Este relatorio nao sera aprovado. Encaminhar agora para Retornos A/S e abrir a etapa de novo agendamento?')">
                                                        <i class="fas fa-calendar-plus"></i>
                                                        N&atilde;o aprovar e enviar para Retornos A/S
                                                    </button>
                                                <?php else: ?>
                                                    <div style="margin-bottom:10px"><strong>Etapa 1 de 2:</strong> assine o relat&oacute;rio t&eacute;cnico.</div>
                                                    <a href="<?= APP_URL ?>minhas-assinaturas" class="btn btn-warning">
                                                        <i class="fas fa-file-signature"></i> 1. Assinar como substituto
                                                    </a>
                                                    <button type="button" class="btn btn-danger" disabled style="margin-top:8px;opacity:.65">
                                                        <i class="fas fa-calendar-plus"></i> 2. Enviar para Retornos A/S
                                                    </button>
                                                    <small style="display:block;margin-top:8px;color:#765a56">A assinatura n&atilde;o aprova o relat&oacute;rio com A/S. Ela apenas libera a etapa 2.</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (($vistoria['assinatura_status'] ?? '') === 'ASSINADO'): ?>
                                            <button type="submit" name="decisao" value="aprovar"
                                                    class="btn btn-warning"
                                                    onclick="return confirm('<?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'Aprovar este relatorio com exigencias comuns?' : 'Aprovar este relatorio?' ?>')">
                                                <i class="fas fa-check-circle"></i>
                                                <?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'Aprovar com exig&ecirc;ncias' : 'Aprovar relat&oacute;rio' ?>
                                            </button>
                                        <?php else: ?>
                                            <div class="admin-review-text" style="margin-bottom:10px">O relat&oacute;rio ainda n&atilde;o foi assinado.</div>
                                            <a href="<?= APP_URL ?>minhas-assinaturas" class="btn btn-warning"><i class="fas fa-file-signature"></i> Assinar como substituto</a>
                                        <?php endif; ?>
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
                            <?php elseif (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' && !$eh_relatorio_vigente): ?>
                                <div class="admin-review-text">
                                    Este relatório foi substituído e permanece disponível somente para consulta.
                                    <?php if ($relatorio_vigente_cadeia): ?>
                                        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$relatorio_vigente_cadeia['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$relatorio_vigente_cadeia['id']) ?>">
                                            Abrir <?= h($relatorio_vigente_cadeia['numero'] ?: $relatorio_vigente_cadeia['id']) ?>
                                        </a>.
                                    <?php endif; ?>
                                </div>
                            <?php elseif (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <div class="admin-review-text">
                                    O relat&oacute;rio est&aacute; dispon&iacute;vel para revis&atilde;o. Somente o vistoriador atribu&iacute;do pode alterar seu conte&uacute;do e suas exig&ecirc;ncias.
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

                    <?php if (false): // Inclusao em revisao desativada: somente o vistoriador altera exigencias. ?>
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

                    <?php endif; ?>
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
                    <p>Continuação do relatório <strong><?= h($relatorio_anterior_numero_ui ?: $vistoria['relatorio_anterior_id']) ?></strong>. Classifique as exigências copiadas e registre qualquer nova deficiência encontrada.</p>
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
                                <textarea name="cumprimento_observacao[<?= h($ex['id']) ?>]" rows="3" required placeholder="Descreva o que foi verificado e as evidências apresentadas."><?= h($ex['observacao'] ?? '') ?></textarea>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <div style="padding:16px;border:1px dashed var(--cor-borda,#777);border-radius:9px;">
                        <h4 style="margin-top:0;">Novas exigências encontradas nesta visita</h4>
                        <p>Use esta área somente para uma deficiência nova, que não veio do relatório anterior.</p>
                        <div id="novasExigenciasRetorno"></div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="adicionarNovaExigenciaRetorno()">
                            <i class="fas fa-plus"></i> Adicionar nova exigência
                        </button>
                    </div>
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
            <section class="report-inspection-card">
                <div class="report-section-heading">
                    <div><i class="fas fa-calendar-check"></i><span><strong>Dados da realização</strong><small>Confirme a data, validade e o responsável presente.</small></span></div>
                </div>
                <div class="report-inspection-grid">
                    <div class="report-inspection-column">
                        <div class="form-group">
                            <label for="data_vistoria"><i class="fas fa-calendar-check"></i> Data da Realização da Vistoria *</label>
                            <input type="date" id="data_vistoria" name="data_vistoria" class="form-control"
                                   value="<?php echo h($vistoria['data_vistoria'] ?? $ag['data_vistoria']); ?>" required>
                        </div>
                        <div class="form-group report-validity-field">
                            <label for="prazo_exigencias_dias">Prazo de validade do relatório <span class="required-mark">*</span></label>
                            <select id="prazo_exigencias_dias" name="prazo_exigencias_dias" class="form-control" required>
                                <option value="" <?= $prazo_exigencias_dias === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                                <option value="60" <?= $prazo_exigencias_dias === '60' ? 'selected' : '' ?>>60 dias</option>
                                <option value="90" <?= $prazo_exigencias_dias === '90' ? 'selected' : '' ?>>90 dias</option>
                            </select>
                            <small class="report-field-help">Define o vencimento das exigências e a validade do CSN. A/S significa “Antes de suspender” e bloqueia a embarcação e todos os certificados.</small>
                        </div>
                    </div>
                    <div class="report-inspection-column">
                      <div class="form-group">
                    <label for="armador_id">
                        <i class="fas fa-user-tie"></i> Armador na data da Vistoria (Operador)
                    </label>
                    <select id="armador_id" name="armador_id" class="form-control">
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
                           placeholder="Nome do operador/responsável presente na vistoria">
                      </div>
                    </div>
                </div>
            </section>

            <!-- ===== CHECKLIST DINAMICO ===== -->
            <section class="checklist-editor-section report-work-card">
                <div class="report-section-heading">
                    <div><i class="fas fa-clipboard-check"></i><span><strong>Checklist de Vistoria</strong><small>Classifique cada item como Conforme, Não Conforme ou N/A.</small></span></div>
                </div>

                <div class="checklist-summary" aria-live="polite">
                    <div><span class="checklist-summary-icon is-progress"><i class="fas fa-list-check"></i></span><span><small>Respondidos</small><strong id="checklistRespondidos">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-pending"><i class="fas fa-clock"></i></span><span><small>Pendentes</small><strong id="checklistPendentes">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-danger"><i class="fas fa-triangle-exclamation"></i></span><span><small>Não conformes</small><strong id="checklistNaoConformes">0</strong></span></div>
                    <div><span class="checklist-summary-icon is-as"><i class="fas fa-ban"></i></span><span><small>Exigências A/S</small><strong id="checklistAS">0</strong></span></div>
                </div>

                <div class="checklist-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <label class="sr-only" for="buscaChecklist">Pesquisar item do checklist</label>
                    <input type="search" id="buscaChecklist" class="form-control" placeholder="Pesquisar exigência, descrição ou referência NORMAM...">
                </div>

                <div id="checklist-container">
                    <?php foreach ($checklist_categorias as $cat): ?>
                    <div class="checklist-section" data-cat="<?= $cat['id'] ?>" data-total="<?= count($cat['itens']) ?>">
                        <button type="button" class="checklist-header" aria-expanded="false" aria-controls="cat_<?= $cat['id'] ?>" onclick="toggleSection('cat_<?= $cat['id'] ?>', this)">
                            <span class="checklist-category-name"><?= h($cat['nome']) ?></span>
                            <span class="checklist-category-metrics">
                                <span class="category-progress"><b data-counter="respondidos">0</b>/<?= count($cat['itens']) ?> respondidos</span>
                                <span class="category-issues" data-badge="exigencias"><b data-counter="exigencias">0</b> exigências</span>
                                <span class="category-as is-hidden" data-badge="as"><b data-counter="as">0</b> A/S</span>
                            </span>
                            <i class="fas fa-chevron-down icone-toggle"></i>
                        </button>
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
                                               name="checklist_sem_prazo_por_id[<?= h($item['id']) ?>]"
                                               value="1"
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
                <div id="checklistSemResultados" class="checklist-no-results is-hidden"><i class="fas fa-search"></i><strong>Nenhum item encontrado</strong><span>Tente pesquisar usando outra palavra ou referência.</span></div>
            </section>

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
                <h4 style="margin: 0 0 4px; font-size: 1rem; color: var(--cor-destaque, #2ECC71);"><i class="fas fa-flag-checkered"></i> Conclusão da Vistoria</h4>
                <small class="text-muted" style="display:block; margin-bottom:15px;">Registre observações gerais e defina o encaminhamento do relatório.</small>
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
                <?php if ($editando && !empty($vistoria['id'])): ?>
                    <a href="<?php echo APP_URL; ?>vistorias/relatorio_pdf.php?id=<?php echo urlencode($vistoria['id']); ?>" target="_blank" class="btn btn-info report-footer-pdf" style="color: #fff;">
                        <i class="fas fa-file-pdf"></i> Visualizar Relatório
                    </a>
                <?php else: ?>
                    <span class="report-pdf-after-save"><i class="fas fa-file-pdf"></i> O PDF estará disponível após salvar.</span>
                <?php endif; ?>
                <?php if ($editando && $pode_ir_etapa2): ?>
                    <a href="<?php echo APP_URL; ?>documentacao/novo_certificado?agendamento_id=<?php echo urlencode($agendamento_id); ?>" class="btn btn-success">
                        <i class="fas fa-certificate"></i> Gerar Certificado
                    </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <span id="rascunhoRelatorioStatus" class="text-muted report-draft-status">
                    <i class="fas fa-cloud-arrow-down"></i> Preenchimento preservado automaticamente neste navegador.
                </span>
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
            if (typeof atualizarContadoresChecklist === 'function') atualizarContadoresChecklist();
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

function atualizarContadoresChecklist() {
    let total = 0, respondidos = 0, naoConformes = 0, totalAS = 0;
    document.querySelectorAll('.checklist-section').forEach(function(section) {
        let catRespondidos = 0, catExigencias = 0, catAS = 0;
        const itens = section.querySelectorAll('.checklist-item');
        total += itens.length;
        itens.forEach(function(item) {
            const status = document.getElementById('status_' + item.dataset.id)?.value || '';
            if (status !== '') { respondidos++; catRespondidos++; }
            if (status === 'NAO_CONFORME') {
                naoConformes++; catExigencias++;
                const semPrazo = document.getElementById('sem_prazo_' + item.dataset.id);
                if (semPrazo?.value === '1') { totalAS++; catAS++; }
            }
        });
        section.querySelector('[data-counter="respondidos"]').textContent = String(catRespondidos);
        section.querySelector('[data-counter="exigencias"]').textContent = String(catExigencias);
        section.querySelector('[data-counter="as"]').textContent = String(catAS);
        section.querySelector('[data-badge="exigencias"]')?.classList.toggle('is-zero', catExigencias === 0);
        section.querySelector('[data-badge="as"]')?.classList.toggle('is-hidden', catAS === 0);
    });
    document.getElementById('checklistRespondidos').textContent = respondidos + ' / ' + total;
    document.getElementById('checklistPendentes').textContent = String(Math.max(0, total - respondidos));
    document.getElementById('checklistNaoConformes').textContent = String(naoConformes);
    document.getElementById('checklistAS').textContent = String(totalAS);
}

// Toggle Accordions
function toggleSection(id, headerButton) {
    const body = document.getElementById(id);
    const header = headerButton || body.previousElementSibling;
    const icon = header.querySelector('.icone-toggle');
    if (body.style.display === 'block') {
        body.style.display = 'none';
        header.setAttribute('aria-expanded', 'false');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.style.display = 'block';
        header.setAttribute('aria-expanded', 'true');
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
    atualizarContadoresChecklist();
}

document.querySelectorAll('.checklist-sem-prazo').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) target.value = this.checked ? '1' : '0';
        atualizarContadoresChecklist();
    });
});

// Busca / Filtro do Checklist
document.getElementById('buscaChecklist').addEventListener('input', function() {
    const term = this.value.trim().toLowerCase();
    const sections = document.querySelectorAll('.checklist-section');
    let totalVisiveis = 0;

    sections.forEach(section => {
        let hasVisible = false;
        const items = section.querySelectorAll('.checklist-item');
        const body = section.querySelector('.checklist-body');
        const header = section.querySelector('.checklist-header');
        const icon = section.querySelector('.icone-toggle');

        items.forEach(item => {
            const text = item.getAttribute('data-text');
            if (term === '' || text.indexOf(term) > -1) {
                item.style.display = 'block';
                hasVisible = true;
                totalVisiveis++;
            } else {
                item.style.display = 'none';
            }
        });

        if (hasVisible) {
            section.style.display = 'block';
            // Se está buscando algo, abre o accordion automaticamente
            if (term !== '') {
                if (section.dataset.searchActive !== '1') {
                    section.dataset.searchWasOpen = body.style.display === 'block' ? '1' : '0';
                    section.dataset.searchActive = '1';
                }
                body.style.display = 'block';
                header.setAttribute('aria-expanded', 'true');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else if (section.dataset.searchActive === '1') {
                const abrir = section.dataset.searchWasOpen === '1';
                body.style.display = abrir ? 'block' : 'none';
                header.setAttribute('aria-expanded', abrir ? 'true' : 'false');
                icon.classList.toggle('fa-chevron-up', abrir);
                icon.classList.toggle('fa-chevron-down', !abrir);
                delete section.dataset.searchActive;
                delete section.dataset.searchWasOpen;
            }
        } else {
            section.style.display = 'none';
        }
    });
    document.getElementById('checklistSemResultados').classList.toggle('is-hidden', totalVisiveis !== 0);
});

atualizarContadoresChecklist();

let contadorNovasExigenciasRetorno = 0;
function adicionarNovaExigenciaRetorno() {
    const container = document.getElementById('novasExigenciasRetorno');
    if (!container) return;
    const indice = contadorNovasExigenciasRetorno++;
    const bloco = document.createElement('article');
    bloco.style.cssText = 'margin:12px 0;padding:14px;border:1px solid var(--cor-borda,#555);border-radius:8px;';
    bloco.innerHTML = `
        <div class="form-row">
            <div class="form-group col-8">
                <label>Descrição da nova exigência *</label>
                <input type="text" name="nova_exigencia_descricao[${indice}]" required>
            </div>
            <div class="form-group col-4">
                <label>Item da NORMAM</label>
                <input type="text" name="nova_exigencia_item_normam[${indice}]">
            </div>
        </div>
        <div class="form-group">
            <label>Observação e evidência *</label>
            <textarea name="nova_exigencia_observacao[${indice}]" rows="2" required></textarea>
        </div>
        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="nova_exigencia_as[${indice}]" value="1"> A/S — Antes de suspender
        </label>
        <button type="button" class="btn btn-danger btn-sm" style="margin-top:10px" onclick="this.closest('article').remove()">
            <i class="fas fa-trash"></i> Remover
        </button>`;
    container.appendChild(bloco);
}

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
    if (e.submitter?.name === 'decisao' && e.submitter.value === 'aprovar') {
        return;
    }
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
        RETORNO_AS: 'Retorno A/S Necessario',
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
