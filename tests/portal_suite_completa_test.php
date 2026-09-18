<?php
/**
 * Teste Abrangente do Portal do Cliente Modernizado
 * Valida:
 * 1. Resumo e KPIs consolidados no dashboard (/portal).
 * 2. Módulo de Trâmites SISAP e Custódia de Originais (/portal/protocolos).
 * 3. Módulo de Minha Frota com especificações técnicas (/portal/embarcacoes).
 * 4. Módulo de Vistorias e Agendamentos (/portal/vistorias).
 * 5. Módulo de Propostas Comerciais (/portal/propostas).
 * 6. Isolamento e Segurança de dados entre clientes (Tenant Isolation).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cliente_portal.php';

function assertPortal(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [✓] {$msg}\n";
}

echo "=======================================================================\n";
echo "   INICIANDO BATERIA DE TESTES - PORTAL DO CLIENTE EXPANDIDO & MODERNO\n";
echo "=======================================================================\n\n";

// 1. Identificar cliente Rosano Souza
$stmtRosano = $pdo->query("SELECT c.id, c.nome, c.email FROM clientes c WHERE c.nome LIKE '%Rosano%' LIMIT 1");
$rosano = $stmtRosano->fetch(PDO::FETCH_ASSOC);
assertPortal(!empty($rosano['id']), "Cliente 'Rosano Souza' localizado no banco de dados.");

// Simular sessão do cliente Rosano
$_SESSION['cliente_id'] = $rosano['id'];
$_SESSION['cliente_nome'] = $rosano['nome'];
$_SESSION['cliente_email'] = $rosano['email'];
$_SESSION['cliente_perfil'] = 'proprietario';
$_SESSION['cliente_logado'] = true;
$_SESSION['cliente_login_time'] = time();
$_SESSION['cliente_forcar_troca_senha'] = false;

// 2. Testar resumo geral do dashboard
echo "\n1. Testando Resumo Geral e KPIs do Dashboard...\n";
$resumo = clientePortalResumoGeral($pdo, $rosano['id']);
assertPortal(is_array($resumo), "Resumo geral gerado com sucesso.");
assertPortal($resumo['total_embarcacoes'] >= 1, "Rosano possui pelo menos 1 embarcação na frota ({$resumo['total_embarcacoes']}).");
assertPortal($resumo['total_propostas'] >= 1, "Rosano possui propostas comerciais registradas ({$resumo['total_propostas']}).");
assertPortal($resumo['total_vistorias'] >= 1, "Rosano possui relatórios de vistoria registrados ({$resumo['total_vistorias']}).");
assertPortal($resumo['analises_ativas'] >= 1, "Rosano possui projeto/análise naval em andamento ({$resumo['analises_ativas']}).");
echo "   -> Resumo: {$resumo['total_embarcacoes']} barcos | {$resumo['protocolos_ativos']} SISAP ativos | {$resumo['total_propostas']} propostas | {$resumo['total_vistorias']} vistorias.\n";

// 3. Testar módulo de protocolos e trâmites SISAP
echo "\n2. Testando Trâmites na Capitania (SISAP & Custódia)...\n";
$protocolos = clientePortalSelectProtocolos($pdo, $rosano['id']);
assertPortal(count($protocolos) >= 1, "Consulta de protocolos retornou registros ({$resumo['total_embarcacoes']} vinculados).");

$temSisap = false;
foreach ($protocolos as $p) {
    if (!empty($p['protocolo_externo_numero'])) {
        $temSisap = true;
        break;
    }
}
assertPortal($temSisap, "Identificado processo oficial com número SISAP registrado.");

$filtrosProt = ['status' => 'ENCERRADO'];
$protocolosEncerrados = clientePortalSelectProtocolos($pdo, $rosano['id'], $filtrosProt);
assertPortal(count($protocolosEncerrados) >= 1, "Filtro por status de protocolo funcionando.");

// 4. Testar embarcações detalhadas
echo "\n3. Testando Ficha Náutica de Embarcações...\n";
$embarcacoesDet = clientePortalSelectEmbarcacoesDetalhadas($pdo, $rosano['id']);
assertPortal(!empty($embarcacoesDet), "Embarcações detalhadas retornadas com sucesso.");
$primeiraEmb = $embarcacoesDet[0];
assertPortal(!empty($primeiraEmb['nome']), "Embarcação identificada: {$primeiraEmb['nome']}");
assertPortal(isset($primeiraEmb['tipo_embarcacao']) || isset($primeiraEmb['tipo']), "Tipo da embarcação presente.");

// 5. Testar vistorias e agendamentos
echo "\n4. Testando Vistorias e Agendamentos Técnicos...\n";
$vistorias = clientePortalSelectVistorias($pdo, $rosano['id']);
assertPortal(!empty($vistorias), "Vistorias recuperadas com sucesso para o cliente.");
assertPortal(!empty($vistorias[0]['numero']), "Número oficial da vistoria identificado: {$vistorias[0]['numero']}");

// 6. Testar propostas comerciais
echo "\n5. Testando Propostas Comerciais e Orçamentos...\n";
$propostas = clientePortalSelectPropostas($pdo, $rosano['id']);
assertPortal(!empty($propostas), "Propostas recuperadas com sucesso para o cliente.");
assertPortal((float)$propostas[0]['valor_total'] > 0, "Valor da proposta registrado corretamente (R$ " . number_format((float)$propostas[0]['valor_total'], 2, ',', '.') . ")");

// 7. Testar isolamento de segurança entre clientes (Tenant Isolation)
echo "\n6. Testando Segurança e Isolamento de Dados (Tenant Isolation)...\n";
try {
    $outroClienteId = gerarUUID();
    $adminId = $pdo->query("SELECT id FROM usuarios WHERE ativo=1 LIMIT 1")->fetchColumn();
    $cnpjRand = sprintf('%02d.%03d.%03d/0001-%02d', mt_rand(10,99), mt_rand(100,999), mt_rand(100,999), mt_rand(10,99));
    $pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, status, perfil, criado_em) VALUES (?, 'Cliente B Isolado Ltda', ?, 'ATIVO', 'armador', NOW())")
        ->execute([$outroClienteId, $cnpjRand]);

    $outraEmbId = gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, ativo, criado_em) VALUES (?, 'EMBARCACAO OUTRO CLIENTE', ?, 1, NOW())")
        ->execute([$outraEmbId, $outroClienteId]);

    $outroDossieId = gerarUUID();
    $pdo->prepare("INSERT INTO protocolo_dossies (id, numero, cliente_id, embarcacao_id, assunto, status, criado_por, criado_em, atualizado_em) VALUES (?, 'AM-PROT-OUTRO-99/26', ?, ?, 'Dossie Confidencial Outro Cliente', 'PROTOCOLADO', ?, NOW(), NOW())")
        ->execute([$outroDossieId, $outroClienteId, $outraEmbId, $adminId]);

    // O cliente Rosano NÃO PODE ver a embarcação nem o dossiê do Outro Cliente
    $embsRosanoIds = array_column(clientePortalEmbarcacoes($pdo, $rosano['id']), 'id');
    assertPortal(!in_array($outraEmbId, $embsRosanoIds, true), "Tenant Isolation: Rosano NÃO visualiza embarcação do Outro Cliente.");

    $protsRosanoIds = array_column(clientePortalSelectProtocolos($pdo, $rosano['id']), 'id');
    assertPortal(!in_array($outroDossieId, $protsRosanoIds, true), "Tenant Isolation: Rosano NÃO visualiza dossiê do Outro Cliente.");

    // Limpar dados do teste de isolamento
    $pdo->prepare("DELETE FROM protocolo_dossies WHERE id=?")->execute([$outroDossieId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id=?")->execute([$outraEmbId]);
    $pdo->prepare("DELETE FROM clientes WHERE id=?")->execute([$outroClienteId]);
    assertPortal(true, "Dados de teste temporário removidos com sucesso.");
} catch (Throwable $e) {
    echo "ERRO SEÇÃO 6: " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
    throw $e;
}

echo "\n=======================================================================\n";
echo "   TODOS OS TESTES DO PORTAL EXPANDIDO PASSARAM COM 100% DE SUCESSO!\n";
echo "=======================================================================\n";
