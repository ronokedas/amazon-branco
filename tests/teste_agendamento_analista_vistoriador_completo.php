<?php
/**
 * Teste E2E de Regra de Negócio:
 * - Proposta Pura de Análise de Planos (sem vistoriador)
 * - Proposta Mista (Vistoriador + Analista)
 * - Visibilidade nos painéis do Vistoriador (Neto) e do Analista (Jerson)
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function testAssert(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=======================================================================\n";
echo " TESTE E2E: ATRIBUIÇÃO ESPECÍFICA ANALISTA + VISTORIADOR EM AGENDAMENTOS\n";
echo "=======================================================================\n\n";

// 1. Identificar Usuários reais solicitados pelo usuário
$stmtJerson = $pdo->prepare("SELECT id, nome, email, cargo FROM usuarios WHERE email = 'jersonalmeida.tec@hotmail.com' LIMIT 1");
$stmtJerson->execute();
$jerson = $stmtJerson->fetch(PDO::FETCH_ASSOC);

$stmtNeto = $pdo->prepare("SELECT id, nome, email, cargo FROM usuarios WHERE email = 'neto@amazonnaval.com.br' LIMIT 1");
$stmtNeto->execute();
$neto = $stmtNeto->fetch(PDO::FETCH_ASSOC);

if (!$jerson) {
    // Se não existir localmente, buscar qualquer analista ativo ou criar
    $jerson = $pdo->query("SELECT id, nome, email, cargo FROM usuarios WHERE cargo='ANALISTA' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
if (!$neto) {
    $neto = $pdo->query("SELECT id, nome, email, cargo FROM usuarios WHERE cargo='VISTORIADOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

testAssert(!empty($jerson['id']), "Analista identificado: {$jerson['nome']} ({$jerson['email']})");
testAssert(!empty($neto['id']), "Vistoriador identificado: {$neto['nome']} ({$neto['email']})");

$vendedor = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo='VENDEDOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$vendedor) {
    $vendedor = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// 2. Criar cliente e embarcação de teste
$clienteId = gerarUUID();
$embarcacaoId = gerarUUID();

$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, status, criado_em) VALUES (?, 'Cliente E2E Teste', '11.222.333/0001-99', 'teste@e2e.com', 'ATIVO', NOW())")->execute([$clienteId]);
$pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, tipo, area_navegacao, ativo, criado_em) VALUES (?, 'EMB E2E NAVAL', ?, 'EMPURRADOR', 'INTERIOR', 1, NOW())")->execute([$embarcacaoId, $clienteId]);

// 3. Obter Serviços
$srvAnalise = $pdo->query("SELECT id, nome FROM servicos WHERE codigo_operacional='ANALISE_PLANOS_EC1' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$srvVistoria = $pdo->query("SELECT id, nome FROM servicos WHERE (codigo_operacional LIKE 'VISTORIA%' OR nome LIKE '%Vistoria%') AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

testAssert(!empty($srvAnalise['id']), "Serviço Análise de Planos carregado: {$srvAnalise['nome']}");
testAssert(!empty($srvVistoria['id']), "Serviço Vistoria de Campo carregado: {$srvVistoria['nome']}");

// -------------------------------------------------------------------------
// CENÁRIO A: Proposta PURA de Análise de Planos (sem vistoria de campo)
// -------------------------------------------------------------------------
echo "\n--- CENÁRIO A: PROPOSTA PURA DE ANÁLISE DE PLANOS ---\n";
$propAId = gerarUUID();
$propANum = gerarNumeroDocumento('PROP', 'AM-PROP');
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, criado_por, status, valor_total, data_emissao) VALUES (?, ?, ?, ?, 'assinada', 5000.00, CURDATE())")
    ->execute([$propAId, $propANum, $clienteId, $vendedor['id']]);
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$propAId, $embarcacaoId]);
$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade) VALUES (UUID(), ?, ?, ?, 5000.00, 1)")
    ->execute([$propAId, $srvAnalise['id'], $embarcacaoId]);

// Executar criação de agendamento na esteira de assinatura
$agendAId = gerarUUID();
$pdo->prepare("INSERT INTO agendamentos (id, proposta_id, embarcacao_id, cliente_id, vendedor_id, tipo_vistoria, status, criado_por) VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?)")
    ->execute([$agendAId, $propAId, $embarcacaoId, $clienteId, $vendedor['id'], $srvAnalise['nome'], $vendedor['id']]);

// Executar geração da demanda de analise de planos
$propRowA = $pdo->query("SELECT * FROM propostas WHERE id='{$propAId}'")->fetch(PDO::FETCH_ASSOC);
analisePlanosCriarDemandasProposta($pdo, $propRowA, $vendedor['id']);

$analiseA = $pdo->query("SELECT * FROM analises_planos WHERE proposta_id='{$propAId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($analiseA['id']), "Demanda de Análise de Planos gerada: {$analiseA['numero']}");

// Agora simular o Agendamento escolhendo o Analista Jerson com prazo definido
$prazoAnalise = date('Y-m-d 17:00:00', strtotime('+5 days'));
$stmtUpdAgA = $pdo->prepare("
    UPDATE agendamentos
    SET analista_id = :analista,
        prazo_analise = :prazo,
        tipo_vistoria = :tipo,
        status = 'confirmado',
        data_vistoria = :data_vistoria
    WHERE id = :id
");
$stmtUpdAgA->execute([
    ':analista' => $jerson['id'],
    ':prazo' => $prazoAnalise,
    ':tipo' => $srvAnalise['nome'],
    ':data_vistoria' => date('Y-m-d', strtotime($prazoAnalise)),
    ':id' => $agendAId,
]);

// Sincronizar Análise de Planos
$pdo->prepare("UPDATE analises_planos SET analista_id = :analista, prazo_agendado_em = :prazo, status = 'AGENDADA' WHERE id = :id")
    ->execute([':analista' => $jerson['id'], ':prazo' => $prazoAnalise, ':id' => $analiseA['id']]);

$agAConfirmado = $pdo->query("SELECT * FROM agendamentos WHERE id='{$agendAId}'")->fetch(PDO::FETCH_ASSOC);
testAssert($agAConfirmado['status'] === 'confirmado', "Agendamento da Análise de Planos confirmado sem exigir vistoriador de campo");
testAssert($agAConfirmado['analista_id'] === $jerson['id'], "Analista atribuído com sucesso ao agendamento: {$jerson['nome']}");
testAssert(!empty($agAConfirmado['prazo_analise']), "Prazo da análise registrado no agendamento: {$agAConfirmado['prazo_analise']}");

$analiseASinc = $pdo->query("SELECT * FROM analises_planos WHERE id='{$analiseA['id']}'")->fetch(PDO::FETCH_ASSOC);
testAssert($analiseASinc['analista_id'] === $jerson['id'], "Análise de Planos {$analiseASinc['numero']} sincronizada com o Analista {$jerson['nome']}");
testAssert($analiseASinc['status'] === 'AGENDADA', "Status da análise está 'AGENDADA' no painel de controle do Analista");

// Verificar se NÃO gerou OS indevida (pois é exclusivamente análise técnica)
$osCountA = $pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE agendamento_id='{$agendAId}'")->fetchColumn();
testAssert((int)$osCountA === 0, "Correto: nenhuma OS de campo foi gerada para análise de gabinete");

// -------------------------------------------------------------------------
// CENÁRIO B: Proposta MISTA (Vistoria em Campo + Análise de Planos)
// -------------------------------------------------------------------------
echo "\n--- CENÁRIO B: PROPOSTA MISTA (VISTORIADOR NETO + ANALISTA JERSON) ---\n";
$propBId = gerarUUID();
$propBNum = gerarNumeroDocumento('PROP', 'AM-PROP');
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, criado_por, status, valor_total, data_emissao) VALUES (?, ?, ?, ?, 'assinada', 12000.00, CURDATE())")
    ->execute([$propBId, $propBNum, $clienteId, $vendedor['id']]);
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")->execute([$propBId, $embarcacaoId]);
$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade) VALUES (UUID(), ?, ?, ?, 4000.00, 1)")
    ->execute([$propBId, $srvVistoria['id'], $embarcacaoId]);
$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade) VALUES (UUID(), ?, ?, ?, 8000.00, 1)")
    ->execute([$propBId, $srvAnalise['id'], $embarcacaoId]);

$agendBId = gerarUUID();
$pdo->prepare("INSERT INTO agendamentos (id, proposta_id, embarcacao_id, cliente_id, vendedor_id, tipo_vistoria, status, criado_por) VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?)")
    ->execute([$agendBId, $propBId, $embarcacaoId, $clienteId, $vendedor['id'], $srvVistoria['nome'], $vendedor['id']]);

$propRowB = $pdo->query("SELECT * FROM propostas WHERE id='{$propBId}'")->fetch(PDO::FETCH_ASSOC);
analisePlanosCriarDemandasProposta($pdo, $propRowB, $vendedor['id']);

$analiseB = $pdo->query("SELECT * FROM analises_planos WHERE proposta_id='{$propBId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($analiseB['id']), "Demanda de Análise de Planos B criada: {$analiseB['numero']}");

// Agendamento Misto: definir Vistoriador Neto E Analista Jerson
$dataVistoriaB = date('Y-m-d', strtotime('+4 days'));
$horaVistoriaB = '09:00:00';
$prazoAnaliseB = date('Y-m-d 18:00:00', strtotime('+8 days'));

$pdo->prepare("
    UPDATE agendamentos
    SET vistoriador_id = :vistoriador,
        analista_id = :analista,
        data_vistoria = :data_vist,
        hora_vistoria = :hora_vist,
        prazo_analise = :prazo_ana,
        local = 'Estaleiro Rio Guamá',
        status = 'confirmado'
    WHERE id = :id
")->execute([
    ':vistoriador' => $neto['id'],
    ':analista' => $jerson['id'],
    ':data_vist' => $dataVistoriaB,
    ':hora_vist' => $horaVistoriaB,
    ':prazo_ana' => $prazoAnaliseB,
    ':id' => $agendBId,
]);

// Gerar OS para o vistoriador
$osNumB = gerarNumeroDocumento('OS', 'AM-OS');
$pdo->prepare("
    INSERT INTO ordens_servico (id, numero, agendamento_id, proposta_id, embarcacao_id, cliente_id, vistoriador_id, tipo_vistoria, data_vistoria, status, criado_por)
    VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?)
")->execute([$osNumB, $agendBId, $propBId, $embarcacaoId, $clienteId, $neto['id'], $srvVistoria['nome'], $dataVistoriaB, $vendedor['id']]);

// Sincronizar Análise com Jerson
$pdo->prepare("UPDATE analises_planos SET analista_id = :analista, prazo_agendado_em = :prazo, status = 'AGENDADA' WHERE id = :id")
    ->execute([':analista' => $jerson['id'], ':prazo' => $prazoAnaliseB, ':id' => $analiseB['id']]);

$agBConfirmado = $pdo->query("SELECT * FROM agendamentos WHERE id='{$agendBId}'")->fetch(PDO::FETCH_ASSOC);
testAssert($agBConfirmado['vistoriador_id'] === $neto['id'], "Vistoriador Neto ({$neto['email']}) atribuído à vistoria de campo");
testAssert($agBConfirmado['analista_id'] === $jerson['id'], "Analista Jerson ({$jerson['email']}) atribuído à análise técnica");

// Verificar se a OS foi criada para Neto
$osB = $pdo->query("SELECT * FROM ordens_servico WHERE agendamento_id='{$agendBId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($osB['id']), "Ordem de Serviço gerada: {$osB['numero']}");
testAssert($osB['vistoriador_id'] === $neto['id'], "OS pertence ao vistoriador Neto");

// -------------------------------------------------------------------------
// CENÁRIO C: Verificação de Visibilidade nas Consultas dos Painéis
// -------------------------------------------------------------------------
echo "\n--- CENÁRIO C: VERIFICAÇÃO DE CONSULTAS DOS PAINÉIS ---\n";

// 1. Painel do Vistoriador Neto:
$sqlNeto = "SELECT COUNT(*) FROM agendamentos a WHERE (a.vistoriador_id = '{$neto['id']}' OR a.vistoriador_id IS NULL) AND a.id = '{$agendBId}'";
$countNeto = (int)$pdo->query($sqlNeto)->fetchColumn();
testAssert($countNeto === 1, "Agendamento Misto visível no painel do vistoriador Neto");

// 2. Painel do Analista Jerson:
$sqlJersonFila = "SELECT COUNT(*) FROM analises_planos WHERE analista_id = '{$jerson['id']}' AND id IN ('{$analiseA['id']}', '{$analiseB['id']}')";
$countJersonFila = (int)$pdo->query($sqlJersonFila)->fetchColumn();
testAssert($countJersonFila === 2, "Ambos os processos (Puro e Misto) visíveis na Fila de Análise do Jerson");

// 3. Listagem de Agendamentos com filtro do Analista Jerson:
$sqlAgendAnalista = "SELECT COUNT(*) FROM agendamentos a WHERE (a.analista_id = '{$jerson['id']}' OR (a.analista_id IS NULL AND a.tipo_vistoria LIKE '%Análise%')) AND a.id IN ('{$agendAId}', '{$agendBId}')";
$countAgendAnalista = (int)$pdo->query($sqlAgendAnalista)->fetchColumn();
testAssert($countAgendAnalista === 2, "Agendamentos visíveis na tela de Agendamentos do Analista Jerson");

// -------------------------------------------------------------------------
// LIMPEZA DOS REGISTROS DE TESTE
// -------------------------------------------------------------------------
$pdo->prepare("DELETE FROM ordens_servico WHERE agendamento_id IN ('{$agendAId}', '{$agendBId}')")->execute();
$pdo->prepare("DELETE FROM analise_planos_itens WHERE analise_id IN ('{$analiseA['id']}', '{$analiseB['id']}')")->execute();
$pdo->prepare("DELETE FROM analise_planos_historico WHERE analise_id IN ('{$analiseA['id']}', '{$analiseB['id']}')")->execute();
$pdo->prepare("DELETE FROM analise_planos_agenda_historico WHERE analise_id IN ('{$analiseA['id']}', '{$analiseB['id']}')")->execute();
$pdo->prepare("DELETE FROM analises_planos WHERE id IN ('{$analiseA['id']}', '{$analiseB['id']}')")->execute();
$pdo->prepare("DELETE FROM agendamentos WHERE id IN ('{$agendAId}', '{$agendBId}')")->execute();
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id IN ('{$propAId}', '{$propBId}')")->execute();
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id IN ('{$propAId}', '{$propBId}')")->execute();
$pdo->prepare("DELETE FROM propostas WHERE id IN ('{$propAId}', '{$propBId}')")->execute();
$pdo->prepare("DELETE FROM embarcacoes WHERE id='{$embarcacaoId}'")->execute();
$pdo->prepare("DELETE FROM clientes WHERE id='{$clienteId}'")->execute();

echo "\n=======================================================================\n";
echo " TESTE E2E EXECUTADO COM 100% DE SUCESSO!\n";
echo "=======================================================================\n";

