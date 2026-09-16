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
               e.id AS embarcacao_id, e.foto_url, e.foto_chave, e.foto_atualizada_em,
               e.numero_inscricao AS embarcacao_numero_inscricao,
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

    // Agendamentos de retorno antigos podem ter sido vinculados antes da criação
    // automática do relatório-filho. Ao serem abertos pelo responsável, resolvemos
    // o vínculo de forma transacional/idempotente e redirecionamos para a URL
    // canônica do relatório de cumprimento.
    if ($vistoria_solicitada_id === ''
        && !empty($ag['relatorio_origem_id'])
        && in_array((string)$ag['status'], ['pendente', 'confirmado', 'em_andamento'], true)) {
        $stmtRelatorioRetorno = $pdo->prepare("SELECT id FROM vistorias
            WHERE agendamento_id = :agendamento_id
            ORDER BY criado_em DESC, id DESC
            LIMIT 1");
        $stmtRelatorioRetorno->execute([':agendamento_id' => $agendamento_id]);
        $relatorioRetornoId = $stmtRelatorioRetorno->fetchColumn() ?: null;

        if (!$relatorioRetornoId) {
            $pdo->beginTransaction();
            try {
                $relatorioRetornoId = criarRelatorioCumprimentoAgendamento(
                    $pdo,
                    $ag,
                    (string)$usuario_id
                );
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        }

        if ($relatorioRetornoId) {
            redirecionar(
                APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode((string)$agendamento_id)
                . '&vistoria_id=' . urlencode((string)$relatorioRetornoId)
            );
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
            : " AND (ve.catalogo_id IS NULL OR ve.catalogo_id = '')";
        $stmtE = $pdo->prepare("SELECT ve.*,
                CASE WHEN ve.exigencia_origem_id IS NOT NULL
                    THEN COALESCE(origem.observacao, ve.observacao)
                    ELSE NULL
                END AS observacao_origem
            FROM vistoria_exigencias ve
            LEFT JOIN vistoria_exigencias origem ON origem.id = ve.exigencia_origem_id
            WHERE ve.vistoria_id = :vistoria_id{$filtroExigencias}
            ORDER BY ve.ordem ASC");
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
$tipo_retorno_atual = null;
if ($eh_relatorio_cumprimento) {
    $stmtTipoRetornoAtual = $pdo->prepare("SELECT tipo FROM vistoria_retornos
        WHERE relatorio_resultado_id=:relatorio OR agendamento_id=:agendamento LIMIT 1");
    $stmtTipoRetornoAtual->execute([
        ':relatorio' => $vistoria['id'],
        ':agendamento' => $agendamento_id,
    ]);
    $tipo_retorno_atual = (string)($stmtTipoRetornoAtual->fetchColumn() ?: 'AS');
}
$regra_edicao_relatorio = $editando
    ? avaliarEdicaoRelatorio($pdo, array_merge($vistoria, ['vistoriador_id' => $ag['vistoriador_id'] ?? null]), (string)$usuario_id, (string)$cargo)
    : ['permitido' => $cargo === 'VISTORIADOR' && ($ag['vistoriador_id'] ?? '') === $usuario_id, 'mensagem' => ''];
$pode_editar_relatorio = (bool)$regra_edicao_relatorio['permitido'];
$admin_review_mode = $editando && !$pode_editar_relatorio;
$exigencias_relatorio = [];
$exigencias_as_relatorio = [];
$exigencias_comuns_relatorio = [];
$exigencias_cumpridas_relatorio = [];
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
        foreach ($exigencias_relatorio as $exReview) {
            $cumpridaReview = (string)($exReview['status_item'] ?? '') === 'cumprida'
                && (string)($exReview['conforme'] ?? '') === 'sim';
            if ($cumpridaReview) {
                $exigencias_cumpridas_relatorio[] = $exReview;
            } elseif ((int)($exReview['antes_de_suspender'] ?? 0) === 1) {
                $exigencias_as_relatorio[] = $exReview;
            } else {
                $exigencias_comuns_relatorio[] = $exReview;
            }
        }
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
$possui_exigencia_comum_pendente = $vistoria
    ? relatorioPossuiExigenciaComumPendenteNaRaiz($pdo, $vistoria['id'])
    : false;
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
$tipo_retorno_cadeia = null;
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
    $stmtTipoCadeia = $pdo->prepare("SELECT tipo FROM vistoria_retornos
        WHERE relatorio_origem_id=:origem_id OR relatorio_resultado_id=:resultado_id
        ORDER BY criado_em DESC LIMIT 1");
    $stmtTipoCadeia->execute([
        ':origem_id' => $vistoria['id'],
        ':resultado_id' => $vistoria['id'],
    ]);
    $tipo_retorno_cadeia = $stmtTipoCadeia->fetchColumn() ?: null;
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
$fotos_por_catalogo = [];
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

    if (!empty($vistoria['id'])) {
        $stmtFotosCat = $pdo->prepare("SELECT id, catalogo_id, url_arquivo, nome_original 
            FROM vistoria_anexos 
            WHERE vistoria_id = :v AND excluido_em IS NULL AND catalogo_id IS NOT NULL");
        $stmtFotosCat->execute([':v' => $vistoria['id']]);
        while ($fRow = $stmtFotosCat->fetch(PDO::FETCH_ASSOC)) {
            $fotos_por_catalogo[$fRow['catalogo_id']][] = $fRow;
        }
    }

    foreach ($categorias_bd as $c) {
        $c['itens'] = [];
        $c['total_obrigatorias'] = 0;
        $c['total_exige_foto'] = 0;
        $checklist_categorias[$c['id']] = $c;
    }
    foreach ($itens_bd as $it) {
        if (isset($checklist_categorias[$it['categoria_id']])) {
            $checklist_categorias[$it['categoria_id']]['itens'][] = $it;
            if (!empty($it['obrigatoria'])) {
                $checklist_categorias[$it['categoria_id']]['total_obrigatorias']++;
            }
            if (!empty($it['exige_foto'])) {
                $checklist_categorias[$it['categoria_id']]['total_exige_foto']++;
            }
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

<link rel="stylesheet" href="<?php echo APP_URL; ?>modules/vistorias/css/relatorio.css?v=<?php echo time(); ?>">

<?php require __DIR__ . "/components/linha_tempo_cadeia.php"; ?>

<div class="form-container">
    <?php require __DIR__ . "/components/contexto_cabecalho.php"; ?>

    <?php if ($admin_review_mode): ?>
        <?php require __DIR__ . "/components/admin_review.php"; ?>
    <?php else: ?>
        <!-- ===== FORMULARIO RELATORIO TECNICO ===== -->
        <form action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_relatorio" method="POST" enctype="multipart/form-data" class="form-padrao" id="formRelatorio">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="agendamento_id" value="<?php echo h($agendamento_id); ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="vistoria_id" value="<?php echo h($vistoria["id"]); ?>">
            <?php endif; ?>

            <?php if ($eh_relatorio_cumprimento): ?>
                <?php require __DIR__ . "/components/relatorio_cumprimento.php"; ?>
            <?php else: ?>
                <?php require __DIR__ . "/components/embarcacao_foto_dados.php"; ?>
                <?php require __DIR__ . "/components/dados_realizacao.php"; ?>
                <?php require __DIR__ . "/components/checklist_normam.php"; ?>
                <?php require __DIR__ . "/components/exigencias_avulsas.php"; ?>
                <?php require __DIR__ . "/components/conclusao_vistoria.php"; ?>
            <?php endif; ?>

            <!-- Deve permanecer como o ultimo campo do formulario. Se o PHP truncar
                 a requisicao por max_input_vars, o backend detecta sua ausencia. -->
            <input type="hidden" name="formulario_completo" value="1">

            <!-- ===== BOTOES ===== -->
            <div class="form-actions report-actions" style="padding: 0 20px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i class="fas fa-save"></i>
                    <?php echo $editando ? "Atualizar Relatorio" : "Salvar Relatorio"; ?>
                </button>
                <?php if ($editando && !empty($vistoria["id"])): ?>
                    <a href="<?php echo APP_URL; ?>vistorias/relatorio_pdf.php?id=<?php echo urlencode($vistoria["id"]); ?>" target="_blank" class="btn btn-info report-footer-pdf" style="color: #fff;">
                        <i class="fas fa-file-pdf"></i> Visualizar Relatório
                    </a>
                <?php else: ?>
                    <span class="report-pdf-after-save"><i class="fas fa-file-pdf"></i> O PDF estará disponível após salvar.</span>
                <?php endif; ?>
                <?php if ($editando && $pode_ir_etapa2): ?>
                    <a href="<?php echo APP_URL; ?>documentacao/novo_certificado?agendamento_id=<?php echo urlencode($agendamento_id); ?>&vistoria_id=<?php echo urlencode((string)$vistoria["id"]); ?>" class="btn btn-success">
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

<?php require __DIR__ . "/components/modal_assinatura_substituta.php"; ?>

<script>
window.ERP_VISTORIA_CONFIG = <?= json_encode([
    "appUrl" => APP_URL,
    "csrfToken" => gerarCSRF(),
    "agendamentoId" => (string)$agendamento_id,
    "vistoriaId" => (string)($vistoria["id"] ?? ""),
    "embarcacaoId" => (string)($ag["embarcacao_id"] ?? ""),
    "draftKey" => "erp:relatorio:rascunho:" . $agendamento_id . ":" . ($vistoria["id"] ?? "novo"),
    "adminReviewMode" => (bool)$admin_review_mode,
    "contadorLinhasAvulsa" => count($exigencias_avulsas),
    "blocosVistoriaAvulsa" => $blocos_vistoria_disponiveis,
    "blocoVistoriaPadrao" => $bloco_vistoria_padrao,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?php echo APP_URL; ?>modules/vistorias/js/relatorio.js"></script>
<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
