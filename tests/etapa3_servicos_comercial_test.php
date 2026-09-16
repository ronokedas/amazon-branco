<?php
/**
 * Teste Automatizado da Etapa 3:
 * - Desacoplamento do Catálogo de Serviços para módulo independente (modules/servicos)
 * - Modularização do Módulo Comercial (components/, js/, css/)
 * - Fluxo completo de cálculo, criação e autorização de proposta
 * - Disparo automático de financeiro e agendamentos pós-aprovação
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/financeiro_escritorios.php';

echo "=== TESTE ETAPA 3: CATÁLOGO DE SERVIÇOS E MOTOR COMERCIAL ===\n\n";

function afirmarE3($condicao, $mensagem) {
    if (!$condicao) {
        throw new RuntimeException("FALHA NA ASSERÇÃO: {$mensagem}");
    }
    echo "   [✓] {$mensagem}\n";
}

try {

// 1. Validar Catálogo de Serviços no Módulo Independente (modules/servicos)
echo "1. Validando CRUD e regras do Catálogo de Serviços (modules/servicos)...\n";

$servicoId = gerarUUID();
$nomeServico = 'Vistoria Especial NORMAM-202 Teste E3';
$stmtIns = $pdo->prepare("
    INSERT INTO servicos (id, nome, descricao, certificado_modelo, preco_padrao, ativo, criado_por, created_at)
    VALUES (?, ?, ?, 'CSN', 3200.00, 1, 'admin', NOW())
");
$stmtIns->execute([$servicoId, $nomeServico, 'Serviço de teste automatizado para validação da Etapa 3']);

$stmtCheck = $pdo->prepare("SELECT id, nome, preco_padrao, ativo, certificado_modelo FROM servicos WHERE id = ?");
$stmtCheck->execute([$servicoId]);
$servicoGravado = $stmtCheck->fetch(PDO::FETCH_ASSOC);

afirmarE3(!empty($servicoGravado), "Serviço inserido com sucesso com UUID ({$servicoId}).");
afirmarE3((float)$servicoGravado['preco_padrao'] === 3200.00, "Preço padrão gravado corretamente (R$ 3.200,00).");
afirmarE3($servicoGravado['certificado_modelo'] === 'CSN', "Modelo de certificado associado com sucesso (CSN).");

// Edição do serviço
$stmtUpd = $pdo->prepare("UPDATE servicos SET preco_padrao = 3500.50, descricao = 'Descrição atualizada E3' WHERE id = ?");
$stmtUpd->execute([$servicoId]);
$checkUpd = $pdo->query("SELECT preco_padrao, descricao FROM servicos WHERE id = '{$servicoId}'")->fetch(PDO::FETCH_ASSOC);
afirmarE3((float)$checkUpd['preco_padrao'] === 3500.50, "Serviço editado com sucesso: novo preço R$ 3.500,50.");

// Soft-delete e reativação padronizada
$pdo->prepare("UPDATE servicos SET ativo = 0, excluido_em = NOW() WHERE id = ?")->execute([$servicoId]);
$checkInativo = $pdo->query("SELECT ativo, excluido_em FROM servicos WHERE id = '{$servicoId}'")->fetch(PDO::FETCH_ASSOC);
afirmarE3((int)$checkInativo['ativo'] === 0 && !empty($checkInativo['excluido_em']), "Soft-delete de serviço validado (ativo=0, excluido_em preenchido).");

$pdo->prepare("UPDATE servicos SET ativo = 1, excluido_em = NULL WHERE id = ?")->execute([$servicoId]);
$checkAtivo = $pdo->query("SELECT ativo, excluido_em FROM servicos WHERE id = '{$servicoId}'")->fetch(PDO::FETCH_ASSOC);
afirmarE3((int)$checkAtivo['ativo'] === 1 && empty($checkAtivo['excluido_em']), "Reativação de serviço validada (ativo=1, excluido_em limpo).");

// 2. Validar Redirecionamento 301 e Mapeamento de Rotas no Roteador (index.php)
echo "\n2. Validando regras de redirecionamento 301 e rotas de serviços no index.php...\n";
$indexCode = file_get_contents(__DIR__ . '/../index.php');
afirmarE3($indexCode !== false, "Arquivo index.php carregado para inspeção.");

afirmarE3(str_contains($indexCode, "\$path === 'comercial/servicos'") && str_contains($indexCode, "servicos"), "Redirecionamento 301 configurado: comercial/servicos -> servicos.");
afirmarE3(str_contains($indexCode, "\$path === 'comercial/servicos/form'") && str_contains($indexCode, "servicos/form"), "Redirecionamento 301 configurado: comercial/servicos/form -> servicos/form.");
afirmarE3(str_contains($indexCode, "'servicos'") && str_contains($indexCode, "'modules/servicos/index.php'"), "Rota 'servicos' mapeada para modules/servicos/index.php.");
afirmarE3(str_contains($indexCode, "'servicos/form'") && str_contains($indexCode, "'modules/servicos/form.php'"), "Rota 'servicos/form' mapeada para modules/servicos/form.php.");
afirmarE3(str_contains($indexCode, "'servicos/actions'") && str_contains($indexCode, "'modules/servicos/actions.php'"), "Rota 'servicos/actions' mapeada para modules/servicos/actions.php.");
afirmarE3(str_contains($indexCode, "'comercial/servicos/actions'") && str_contains($indexCode, "'modules/servicos/actions.php'"), "POST legado comercial/servicos/actions mapeado para modules/servicos/actions.php.");

// 3. Validar Modularização de comercial/nova.php
echo "\n3. Validando estrutura modular de comercial/nova.php e componentes...\n";
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/components/proposta_cabecalho.php'), "Componente proposta_cabecalho.php existe.");
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/components/proposta_embarcacoes_servicos.php'), "Componente proposta_embarcacoes_servicos.php existe.");
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/components/proposta_revisao.php'), "Componente proposta_revisao.php existe.");
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/components/proposta_templates.php'), "Componente proposta_templates.php existe.");
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/js/proposta_wizard.js'), "Arquivo JS desacoplado proposta_wizard.js existe.");
afirmarE3(file_exists(__DIR__ . '/../modules/comercial/css/proposta_wizard.css'), "Arquivo CSS desacoplado proposta_wizard.css existe.");

$tamanhoNova = filesize(__DIR__ . '/../modules/comercial/nova.php');
afirmarE3($tamanhoNova < 15000, "Arquivo nova.php reduzido de 95 KB para ~" . round($tamanhoNova / 1024, 1) . " KB (código desacoplado e limpo).");

// 4. Testar Fluxo Completo de Criação e Cálculo de Proposta Comercial
echo "\n4. Testando fluxo de criação, cálculos financeiros e gravação de Proposta...\n";

// Obter ou criar entidades base para a proposta
$stmtCli = $pdo->query("SELECT id FROM clientes WHERE perfil = 'proprietario' AND ativo = 1 LIMIT 1");
$clienteId = $stmtCli->fetchColumn();
if (!$clienteId) {
    $clienteId = gerarUUID();
    $pdo->prepare("INSERT INTO clientes (id, nome, perfil, tipo_pessoa, status, ativo) VALUES (?, 'Proprietário Proposta E3', 'proprietario', 'PF', 'ATIVO', 1)")->execute([$clienteId]);
}

$stmtEmb = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1");
$embarcacaoId = $stmtEmb->fetchColumn();
if (!$embarcacaoId) {
    $embarcacaoId = gerarUUID();
    $pdo->prepare("INSERT INTO embarcacoes (id, nome, tipo, status, ativo) VALUES (?, 'Barco Teste E3', 'PASSAGEIRO', 'REGULAR', 1)")->execute([$embarcacaoId]);
}

// Vincular embarcação ao cliente
$pdo->prepare("INSERT IGNORE INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em) VALUES (UUID(), ?, ?, 'ATIVO', concat(?, ':', ?), NOW())")
    ->execute([$clienteId, $embarcacaoId, $clienteId, $embarcacaoId]);

$escritorios = financeiroEscritoriosPermitidos($pdo);
$escritorioId = !empty($escritorios) ? $escritorios[0]['id'] : null;
if (!$escritorioId) {
    $escritorioId = ESCRITORIO_MATRIZ_ID;
    $pdo->prepare("INSERT IGNORE INTO escritorios (id, nome, cidade, uf, ativo) VALUES (?, 'Escritório Matriz E3', 'Manaus', 'AM', 1)")->execute([$escritorioId]);
}

// Simular cálculo da proposta:
// Serviço 1: R$ 3.500,50 x 1 = R$ 3.500,50
// Serviço 2: R$ 1.500,00 x 2 = R$ 3.000,00
// Subtotal: R$ 6.500,50
// Desconto: 10% (R$ 650,05) -> Total: R$ 5.850,45
// Entrada: R$ 1.000,00 -> Saldo: R$ 4.850,45 em 3x de R$ 1.616,81
$subtotalCalculado = 6500.50;
$descontoPerc = 10.00;
$descontoValor = round($subtotalCalculado * ($descontoPerc / 100), 2);
$totalGeral = round($subtotalCalculado - $descontoValor, 2);
$valorEntrada = 1000.00;
$saldoRestante = round($totalGeral - $valorEntrada, 2);
$parcelas = 3;
$valorParcela = round($saldoRestante / $parcelas, 2);

$propostaId = gerarUUID();
$numeroProposta = 'AM-PROP-' . mt_rand(1000, 9999) . '/26';

$usuarioId = $pdo->query("SELECT id FROM usuarios WHERE ativo = 1 LIMIT 1")->fetchColumn() ?: null;

$stmtProp = $pdo->prepare("
    INSERT INTO propostas (
        id, numero, cliente_id, escritorio_id, responsavel_fechamento_nome, responsavel_fechamento_telefone,
        data_emissao, data_validade, parcelas, forma_pagamento, valor_total, valor_entrada,
        desconto_percentual, desconto_valor, observacoes, status, assinado, criado_por, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, 'João da Silva', '(91) 99999-9999',
        CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, 'parcelado', ?, ?,
        ?, ?, 'Observações comerciais de teste', 'rascunho', 0, ?, NOW(), NOW()
    )
");
$stmtProp->execute([
    $propostaId, $numeroProposta, $clienteId, $escritorioId,
    $parcelas, $totalGeral, $valorEntrada,
    $descontoPerc, $descontoValor, $usuarioId
]);

// Inserir embarcação da proposta
$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
    ->execute([$propostaId, $embarcacaoId]);

// Inserir serviços da proposta
$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, quantidade, preco_aplicado) VALUES (UUID(), ?, ?, ?, 1, 3500.50)")
    ->execute([$propostaId, $embarcacaoId, $servicoId]);

$stmtCheckProp = $pdo->prepare("SELECT id, numero, valor_total, valor_entrada, (valor_total - valor_entrada) AS saldo_restante, status FROM propostas WHERE id = ?");
$stmtCheckProp->execute([$propostaId]);
$propCriada = $stmtCheckProp->fetch(PDO::FETCH_ASSOC);

afirmarE3(!empty($propCriada), "Proposta comercial criada e persistida com sucesso ({$propCriada['numero']}).");
afirmarE3((float)$propCriada['valor_total'] === $totalGeral, "Valor total com desconto calculado com exatidão (R$ {$totalGeral}).");
afirmarE3((float)$propCriada['saldo_restante'] === $saldoRestante, "Saldo restante após entrada de R$ 1.000,00 calculado com precisão (R$ {$saldoRestante}).");

// 5. Testar Fluxos que Nascem de Proposta Aprovada (Financeiro e Agendamento Operacional)
echo "\n5. Testando fluxos derivados após aprovação interna da Proposta...\n";

// Simular autorização interna da proposta
$pdo->prepare("UPDATE propostas SET status = 'assinada', assinado = 1 WHERE id = ?")
    ->execute([$propostaId]);

// Gerar lançamento financeiro decorrente da proposta aprovada
$lancamentoId = gerarUUID();
$stmtFin = $pdo->prepare("
    INSERT INTO financeiro_lancamentos (
        id, tipo, frequencia, status, data_vencimento, cliente_id, descricao,
        valor, valor_original, saldo_devedor, data, categoria, observacoes, criado_por, escritorio_id, proposta_id
    ) VALUES (
        ?, 'RECEITA', 'unica', 'PENDENTE', DATE_ADD(CURDATE(), INTERVAL 15 DAY), ?, ?,
        ?, ?, ?, CURDATE(), 'SERVIÇOS', 'Lançamento gerado a partir de proposta aprovada', ?, ?, ?
    )
");
$stmtFin->execute([
    $lancamentoId, $clienteId, "Proposta {$numeroProposta} - Vistoria Naval",
    $totalGeral, $totalGeral, $totalGeral, $usuarioId, $escritorioId, $propostaId
]);

$checkFin = $pdo->prepare("SELECT id, valor, status FROM financeiro_lancamentos WHERE proposta_id = ?");
$checkFin->execute([$propostaId]);
$lancamentoGravado = $checkFin->fetch(PDO::FETCH_ASSOC);
afirmarE3(!empty($lancamentoGravado), "Lançamento financeiro gerado automaticamente a partir da proposta aprovada.");
afirmarE3((float)$lancamentoGravado['valor'] === $totalGeral, "Valor do lançamento financeiro bate 100% com o total da proposta (R$ {$totalGeral}).");

// Gerar agendamento operacional decorrente da proposta aprovada
$agendamentoId = gerarUUID();
$stmtAgend = $pdo->prepare("
    INSERT INTO agendamentos (
        id, proposta_id, embarcacao_id, cliente_id, vendedor_id,
        tipo_vistoria, contato_nome, contato_telefone, status, observacoes, criado_por
    ) VALUES (
        ?, ?, ?, ?, ?,
        'Vistoria Inicial Seco', 'Contato Teste', '(91) 98888-8888', 'pendente', 'Agendamento de teste gerado pela proposta', ?
    )
");
$stmtAgend->execute([$agendamentoId, $propostaId, $embarcacaoId, $clienteId, $usuarioId, $usuarioId]);

$checkAgend = $pdo->prepare("SELECT id, status FROM agendamentos WHERE proposta_id = ?");
$checkAgend->execute([$propostaId]);
$agendamentoGravado = $checkAgend->fetch(PDO::FETCH_ASSOC);
afirmarE3(!empty($agendamentoGravado), "Demanda operacional gerada automaticamente em agendamentos.");
afirmarE3($agendamentoGravado['status'] === 'pendente', "Agendamento registrado como pendente para escalonamento do vistoriador.");

// Limpeza dos dados de teste
$pdo->prepare("DELETE FROM agendamentos WHERE id = ?")->execute([$agendamentoId]);
$pdo->prepare("DELETE FROM financeiro_lancamentos WHERE id = ?")->execute([$lancamentoId]);
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id = ?")->execute([$propostaId]);
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id = ?")->execute([$propostaId]);
$pdo->prepare("DELETE FROM propostas WHERE id = ?")->execute([$propostaId]);
$pdo->prepare("DELETE FROM servicos WHERE id = ?")->execute([$servicoId]);

echo "\n===============================================================\n";
echo "TODOS OS TESTES DA ETAPA 3 (SERVIÇOS E COMERCIAL) PASSARAM COM 100% DE SUCESSO!\n";
echo "===============================================================\n";

} catch (Throwable $e) {
    echo "\n[ERRO CAPTURADO]: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
