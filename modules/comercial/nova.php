<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: nova.php - Orquestrador do Wizard de Nova Proposta
 * Etapa 3: Modularizado por responsabilidade (components/, js/, css/)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

verificar_sessao();
exigirAcesso('comercial');

$modoEdicao = !empty($_GET['id']);
$propostaEdicao = null;
$servicosEdicaoIniciais = [];

if ($modoEdicao) {
    $propostaIdEdicao = (string)$_GET['id'];
    $stmtEdicao = $pdo->prepare("
        SELECT p.*, c.nome AS cliente_nome, c.perfil AS cliente_perfil, c.cpf_cnpj AS cliente_cpfcnpj
        FROM propostas p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmtEdicao->execute([':id' => $propostaIdEdicao]);
    $propostaEdicao = $stmtEdicao->fetch(PDO::FETCH_ASSOC);

    $cargoEdicao = getCargo();
    $usuarioEdicao = (string)($_SESSION['usuario_id'] ?? '');
    $podeEditar = $propostaEdicao
        && in_array($cargoEdicao, ['ADMIN', 'VENDEDOR'], true)
        && ($cargoEdicao === 'ADMIN' || (string)$propostaEdicao['criado_por'] === $usuarioEdicao)
        && ($propostaEdicao['status'] ?? '') === 'rascunho'
        && empty($propostaEdicao['assinado'])
        && financeiroPodeAcessarEscritorio($pdo, (string)($propostaEdicao['escritorio_id'] ?? ''));

    if (!$podeEditar) {
        setMensagem('error', 'A proposta não foi encontrada ou não está disponível para edição.');
        redirecionar(APP_URL . 'comercial');
    }

    $stmtServicosEdicao = $pdo->prepare("
        SELECT ps.embarcacao_id, ps.servico_id, ps.quantidade, ps.preco_aplicado
        FROM propostas_servicos ps
        WHERE ps.proposta_id = :id
    ");
    $stmtServicosEdicao->execute([':id' => $propostaIdEdicao]);
    foreach ($stmtServicosEdicao->fetchAll(PDO::FETCH_ASSOC) as $servicoEdicao) {
        $embarcacaoId = (string)$servicoEdicao['embarcacao_id'];
        $servicoId = (string)$servicoEdicao['servico_id'];
        if (!isset($servicosEdicaoIniciais[$embarcacaoId])) {
            $servicosEdicaoIniciais[$embarcacaoId] = [];
        }
        $servicosEdicaoIniciais[$embarcacaoId][$servicoId] = [
            'qtd' => max(1, (int)$servicoEdicao['quantidade']),
            'preco' => round((float)$servicoEdicao['preco_aplicado'], 2),
        ];
    }
}

$escritoriosProposta = financeiroEscritoriosPermitidos($pdo);
$escritorioProposta = '';
try {
    $escritorioSolicitado = $modoEdicao ? ($propostaEdicao['escritorio_id'] ?? null) : ($_GET['escritorio_id'] ?? null);
    $escritorioProposta = financeiroResolverEscritorio($pdo, $escritorioSolicitado);
} catch (RuntimeException $e) {
    error_log('Nova proposta sem escritorio disponivel: ' . $e->getMessage());
}
if ($escritorioProposta === 'todos') $escritorioProposta = financeiroEscritorioUsuario($pdo) ?: ($escritoriosProposta[0]['id'] ?? '');
$idsEscritoriosProposta = array_column($escritoriosProposta, 'id');
if (!in_array($escritorioProposta, $idsEscritoriosProposta, true)) $escritorioProposta = (string)($idsEscritoriosProposta[0] ?? '');
$selecionarEscritorioProposta = financeiroEhAdmin() || count($escritoriosProposta) > 1;
$escritorioPropostaDisponivel = $escritorioProposta !== '';

// Buscar clientes ativos (Proprietários, Armadores e Despachantes)
try {
    $stmtClientes = $pdo->query("
        SELECT id, nome, perfil, cpf_cnpj 
        FROM clientes 
        WHERE (status = 'ATIVO' OR status IS NULL) 
          AND (ativo = 1 OR ativo IS NULL) 
          AND excluido_em IS NULL 
        ORDER BY criado_em DESC, nome ASC
    ");
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [];
}

$embarcacaoPreSelecionadaId = trim($_GET['embarcacao_id'] ?? '');
$clientePreSelecionadoId = $modoEdicao ? (string)$propostaEdicao['cliente_id'] : ($_GET['cliente_id'] ?? '');

// Se foi passada a embarcação e nenhum cliente, resolver automaticamente o proprietário/armador da embarcação
if (empty($clientePreSelecionadoId) && !empty($embarcacaoPreSelecionadaId)) {
    try {
        $stmtOwner = $pdo->prepare("
            SELECT COALESCE(ce.cliente_id, e.proprietario_id, e.cliente_id) as cid
            FROM embarcacoes e
            LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.status = 'ATIVO'
            WHERE e.id = :emb_id
            ORDER BY ce.vinculado_em DESC
            LIMIT 1
        ");
        $stmtOwner->execute([':emb_id' => $embarcacaoPreSelecionadaId]);
        $cid = $stmtOwner->fetchColumn();
        if (!empty($cid)) {
            $clientePreSelecionadoId = (string)$cid;
        }
    } catch (Exception $e) {
        error_log('Erro ao resolver proprietário da embarcação para proposta: ' . $e->getMessage());
    }
}

$clientePreSelecionadoEncontrado = false;

if (!empty($clientes)) {
    foreach ($clientes as $cliente) {
        if (!empty($clientePreSelecionadoId) && $cliente['id'] === $clientePreSelecionadoId) {
            $clientePreSelecionadoEncontrado = true;
            break;
        }
    }

    if ($clientePreSelecionadoEncontrado) {
        usort($clientes, function($a, $b) use ($clientePreSelecionadoId) {
            if ($a['id'] === $clientePreSelecionadoId) return -1;
            if ($b['id'] === $clientePreSelecionadoId) return 1;
            return 0;
        });
    } elseif (count($clientes) === 1) {
        $clientePreSelecionadoId = $clientes[0]['id'];
        $clientePreSelecionadoEncontrado = true;
    }
}

// Buscar todos os serviços ativos
try {
    if ($modoEdicao) {
        $stmtServicos = $pdo->prepare("
            SELECT DISTINCT s.id, s.nome, s.descricao, s.preco_padrao
            FROM servicos s
            LEFT JOIN propostas_servicos ps ON ps.servico_id = s.id AND ps.proposta_id = :proposta
            WHERE s.ativo = 1 OR ps.id IS NOT NULL
            ORDER BY s.nome ASC
        ");
        $stmtServicos->execute([':proposta' => $propostaEdicao['id']]);
    } else {
        $stmtServicos = $pdo->query("SELECT id, nome, descricao, preco_padrao FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
    }
    $servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $servicos = [];
}

$titulo_page = ($modoEdicao ? 'Editar Proposta' : 'Nova Proposta') . ' - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo APP_URL; ?>modules/comercial/css/proposta_wizard.css">

<div class="conteudo-principal flow-shell">
    <!-- Formulário principal -->
    <form id="wizardForm" action="<?php echo APP_URL; ?>comercial/propostas/actions" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <input type="hidden" name="action" value="<?php echo $modoEdicao ? 'atualizar' : 'criar'; ?>">
        <?php if ($modoEdicao): ?>
        <input type="hidden" name="id" value="<?php echo h($propostaEdicao['id']); ?>">
        <?php endif; ?>
        <input type="hidden" id="dadosCliente" name="dados_cliente" value="">
        <input type="hidden" id="dadosServicosJson" name="dados_servicos_json" value="">

        <?php require __DIR__ . '/components/proposta_cabecalho.php'; ?>
        <?php require __DIR__ . '/components/proposta_embarcacoes_servicos.php'; ?>
        <?php require __DIR__ . '/components/proposta_revisao.php'; ?>
    </form>
</div>

<?php require __DIR__ . '/components/proposta_templates.php'; ?>

<script>
const APP_URL = <?php echo json_encode(APP_URL); ?>;
const ESCRITORIO_DISPONIVEL = <?php echo $escritorioPropostaDisponivel ? 'true' : 'false'; ?>;
const ALL_SERVICOS = <?php echo json_encode($servicos, JSON_UNESCAPED_UNICODE); ?>;
const MODO_EDICAO = <?php echo $modoEdicao ? 'true' : 'false'; ?>;
const SERVICOS_EDICAO_INICIAIS = <?php echo json_encode($servicosEdicaoIniciais, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const EMBARCACAO_URL_INICIAL = <?php echo json_encode($embarcacaoPreSelecionadaId); ?>;
</script>
<script src="<?php echo APP_URL; ?>modules/comercial/js/proposta_wizard.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
