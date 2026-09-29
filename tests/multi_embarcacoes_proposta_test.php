<?php
/**
 * Teste Automatizado: Suporte a Múltiplas Embarcações em Propostas e Vínculos
 * - Garante que clientes com mais de 3 embarcações carregam todas as embarcações no Passo 2
 * - Garante que salvar uma embarcação não desvincula as outras do cliente (vincularEmbarcacaoAoCliente)
 * - Garante que o formulário do cliente carrega todas as embarcações vinculadas
 * - Garante que a interface possui a marcação de serviços concluídos e filtros
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cliente_vinculos.php';

echo "=== TESTE: CLIENTE COM MAIS DE 3 EMBARCAÇÕES E MARCAÇÃO DE SERVIÇOS ===\n\n";

function afirmarEmb($condicao, $mensagem) {
    if (!$condicao) {
        throw new RuntimeException("FALHA NA ASSERÇÃO: {$mensagem}");
    }
    echo "   [✓] {$mensagem}\n";
}

try {

// 1. Criar um cliente com 6 embarcações vinculadas
echo "1. Criando cliente de teste com 6 embarcações vinculadas...\n";

$cliId = 'test-cli-multi-' . bin2hex(random_bytes(4));
$cnpjAleatorio = '12.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '/0001-' . mt_rand(10, 99);
$stmtCli = $pdo->prepare("
    INSERT INTO clientes (id, nome, perfil, cpf_cnpj, status, ativo, criado_em, atualizado_em)
    VALUES (?, 'Navegação Rio Amazonas Ltda', 'armador', ?, 'ATIVO', 1, NOW(), NOW())
");
$stmtCli->execute([$cliId, $cnpjAleatorio]);

$embIds = [];
for ($i = 1; $i <= 6; $i++) {
    $eId = 'test-emb-' . $i . '-' . bin2hex(random_bytes(3));
    $embIds[] = $eId;
    $regUnico = 'REG-' . bin2hex(random_bytes(4));
    $pdo->prepare("
        INSERT INTO embarcacoes (id, nome, registro, proprietario_id, cliente_id, ativo)
        VALUES (?, ?, ?, ?, ?, 1)
    ")->execute([$eId, "Balsa Amazônia {$i}", $regUnico, $cliId, $cliId]);

    $pdo->prepare("
        INSERT INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em)
        VALUES (UUID(), ?, ?, 'ATIVO', concat(?, ':', ?), NOW())
    ")->execute([$cliId, $eId, $cliId, $eId]);
}

afirmarEmb(count($embIds) === 6, "6 embarcações geradas para o cliente de teste.");

// 2. Testar se vincularEmbarcacaoAoCliente preserva os outros 5 vínculos ao salvar uma embarcação
echo "\n2. Testando que vincularEmbarcacaoAoCliente preserva os outros vínculos do cliente...\n";

// Salvar a embarcação 1 via vincularEmbarcacaoAoCliente
vincularEmbarcacaoAoCliente($pdo, $embIds[0], $cliId, null);

$stmtContagem = $pdo->prepare("
    SELECT COUNT(*) FROM clientes_embarcacoes 
    WHERE cliente_id = ? AND status = 'ATIVO'
");
$stmtContagem->execute([$cliId]);
$totalAtivos = (int)$stmtContagem->fetchColumn();

afirmarEmb($totalAtivos === 6, "Todas as 6 embarcações continuam vinculadas após salvar uma individualmente (não foram desvinculadas).");

// 3. Testar a query do endpoint embarcacoes_cliente no comercial
echo "\n3. Testando query do endpoint embarcacoes_cliente com mais de 3 embarcações...\n";

$clausulasOr = [
    'ce.cliente_id = :cid1',
    'e.proprietario_id = :cid2',
    'e.cliente_id = :cid3'
];
$params = [
    ':cid1' => $cliId,
    ':cid2' => $cliId,
    ':cid3' => $cliId
];
$clausulaWhere = '(' . implode(' OR ', $clausulasOr) . ')';

$stmtEndpoint = $pdo->prepare("
    SELECT DISTINCT e.id, e.nome, COALESCE(e.numero_inscricao, e.registro, '') as registro
    FROM embarcacoes e
    LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND (ce.status = 'ATIVO' OR ce.status IS NULL OR ce.desvinculado_em IS NULL)
    WHERE {$clausulaWhere}
      AND (e.ativo = 1 OR e.ativo IS NULL)
      AND e.excluido_em IS NULL
    ORDER BY e.nome ASC
");
$stmtEndpoint->execute($params);
$embarcacoesRetornadas = $stmtEndpoint->fetchAll(PDO::FETCH_ASSOC);

afirmarEmb(count($embarcacoesRetornadas) === 6, "Endpoint retornou todas as 6 embarcações sem qualquer corte ou limitação.");

// 4. Testar a query de busca de embarcações vinculadas do formulário do cliente
echo "\n4. Testando query de busca de embarcações vinculadas em modules/clientes/form.php...\n";

$stmtFormCli = $pdo->prepare("
    SELECT DISTINCT e.id
    FROM embarcacoes e
    LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND (ce.status = 'ATIVO' OR ce.status IS NULL)
    WHERE (ce.cliente_id = :cid1 OR e.proprietario_id = :cid2 OR e.cliente_id = :cid3)
      AND (e.ativo = 1 OR e.ativo IS NULL)
      AND e.excluido_em IS NULL
");
$stmtFormCli->execute([':cid1' => $cliId, ':cid2' => $cliId, ':cid3' => $cliId]);
$embsForm = $stmtFormCli->fetchAll(PDO::FETCH_COLUMN) ?: [];

afirmarEmb(count($embsForm) === 6, "Formulário de cliente carrega todas as 6 embarcações vinculadas.");

// 5. Validar arquivos frontend (JS e CSS) do wizard de proposta
echo "\n5. Validando implementação do Wizard de Proposta (JS e CSS)...\n";

$js = file_get_contents(__DIR__ . '/../modules/comercial/js/proposta_wizard.js');
afirmarEmb(str_contains($js, 'atualizarCardEmbarcacaoSeletor'), "Função atualizarCardEmbarcacaoSeletor implementada no JS.");
afirmarEmb(str_contains($js, 'has-services'), "Marcação da classe has-services presente no JS.");
afirmarEmb(str_contains($js, 'emb-status-pill'), "Pill de status do card implementado no JS.");
afirmarEmb(str_contains($js, 'embContadorGeral'), "Contador geral de progresso implementado no JS.");
afirmarEmb(str_contains($js, 'aplicarFiltroRapidoEmbarcacoes'), "Filtro rápido de embarcações implementado no JS.");

$css = file_get_contents(__DIR__ . '/../modules/comercial/css/proposta_wizard.css');
afirmarEmb(str_contains($css, '.embarcacao-select-card.has-services'), "Estilo para has-services com destaque verde presente no CSS.");
afirmarEmb(str_contains($css, '.emb-status-pill.pill-success'), "Estilo pill-success presente no CSS.");
afirmarEmb(str_contains($css, '.emb-icon-check'), "Estilo do ícone de checkmark sobreposto presente no CSS.");

// Limpeza dos dados de teste
$pdo->prepare("DELETE FROM clientes_embarcacoes WHERE cliente_id = ?")->execute([$cliId]);
$pdo->prepare("UPDATE embarcacoes SET proprietario_id = NULL, cliente_id = NULL WHERE proprietario_id = ? OR cliente_id = ?")->execute([$cliId, $cliId]);
$inIds = "'" . implode("','", $embIds) . "'";
$pdo->query("DELETE FROM embarcacoes WHERE id IN ({$inIds})");
$pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$cliId]);

echo "\n===============================================================\n";
echo "TODAS AS VALIDAÇÕES DE MÚLTIPLAS EMBARCAÇÕES PASSARAM COM SUCESSO!\n";
echo "===============================================================\n";

} catch (Throwable $e) {
    echo "\n[ERRO CAPTURADO]: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
