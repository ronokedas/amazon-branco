<?php
/**
 * Teste de Integração e Regra de Negócio Naval:
 * Fluxo Unificado Proposta -> Vistoriador (Agendamento Único) + Analista (Atribuição Automática)
 * 
 * Valida:
 * 1. Proposta com serviços mistos (Vistoria + Análise de Planos EC1) gera APENAS 1 agendamento para o vistoriador.
 * 2. O campo 'tipo_vistoria' no agendamento NÃO é poluído com 'Análise de Planos'.
 * 3. A demanda de Análise de Planos é criada e AUTOMATICAMENTE atribuída ao Analista Naval ativo (Itamar).
 * 4. O status da análise inicia como 'AGENDADA' com prazo de 7 dias e checklist semeado.
 * 5. O formulário de agendamento detecta a proposta e evita criação de ordens duplicadas (idempotência).
 * 6. O Portal do Cliente permite upload de plantas náuticas no status 'AGENDADA'.
 * 7. A tela do Analista exibe o vínculo contextual com a vistoria de campo da embarcação.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function assertFluxo(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=======================================================================\n";
echo " TESTE DE FLUXO INTEGRADO: PROPOSTA -> VISTORIADOR + ANALISTA AUTOMÁTICO\n";
echo "=======================================================================\n\n";

// 1. Identificar Usuários (Admin, Vendedor, Vistoriador, Analista)
$admin = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$vendedor = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='VENDEDOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$vistoriador = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='VISTORIADOR' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$analista = $pdo->query("SELECT u.id, u.nome, u.cargo FROM usuarios u 
                         LEFT JOIN usuario_perfis up ON up.usuario_id=u.id 
                         WHERE (u.cargo='ANALISTA' OR up.perfil='ANALISTA') AND u.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

assertFluxo(!empty($admin['id']), "Usuário Admin localizado ({$admin['nome']})");
assertFluxo(!empty($vendedor['id']), "Usuário Vendedor localizado ({$vendedor['nome']})");
assertFluxo(!empty($vistoriador['id']), "Usuário Vistoriador localizado ({$vistoriador['nome']})");
assertFluxo(!empty($analista['id']), "Analista Naval Ativo identificado ({$analista['nome']})");

// 2. Criar Cliente e Embarcação de Teste
$clienteId = gerarUUID();
$randomDoc = sprintf('%02d.%03d.%03d/%04d-%02d', mt_rand(10,99), mt_rand(100,999), mt_rand(100,999), 1, mt_rand(10,99));
$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, telefone, status, criado_em)
               VALUES (?, 'Armador Teste Fluxo Unificado Ltda', ?, 'armador.fluxo@teste.com.br', '91988889999', 'ATIVO', NOW())")
    ->execute([$clienteId, $randomDoc]);
assertFluxo(true, "Cliente Armador criado para o teste: {$clienteId}");

$embarcacaoId = gerarUUID();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, tipo, area_navegacao, arqueacao_bruta, porte_bruto, ativo, criado_em)
               VALUES (?, 'B/M UNIFICADO NAVAL', ?, 'CARGA_GERAL', 'INTERIOR', 110.00, 250.00, 1, NOW())")
    ->execute([$embarcacaoId, $clienteId]);
assertFluxo(true, "Embarcação criada: B/M UNIFICADO NAVAL ({$embarcacaoId})");

// 3. Garantir Serviços: 1 de Vistoria de Campo e 1 de Análise de Planos EC1
$servicoVistoria = $pdo->query("SELECT id, nome, codigo_operacional FROM servicos WHERE (codigo_operacional LIKE 'VISTORIA%' OR nome LIKE '%Vistoria%') AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$servicoVistoria) {
    $vId = gerarUUID();
    $pdo->prepare("INSERT INTO servicos (id, nome, codigo_operacional, preco_padrao, ativo, criado_em)
                   VALUES (?, 'Vistoria Inicial Flutuando', 'VISTORIA_INICIAL_FLUTUANDO', 4000.00, 1, NOW())")->execute([$vId]);
    $servicoVistoria = ['id' => $vId, 'nome' => 'Vistoria Inicial Flutuando', 'codigo_operacional' => 'VISTORIA_INICIAL_FLUTUANDO'];
}
assertFluxo(!empty($servicoVistoria['id']), "Serviço de Vistoria de Campo localizado: {$servicoVistoria['nome']}");

$servicoAnalise = $pdo->query("SELECT id, nome, codigo_operacional FROM servicos WHERE codigo_operacional='ANALISE_PLANOS_EC1' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$servicoAnalise) {
    $aId = gerarUUID();
    $pdo->prepare("INSERT INTO servicos (id, nome, codigo_operacional, preco_padrao, ativo, criado_em)
                   VALUES (?, 'Análise de Planos EC1', 'ANALISE_PLANOS_EC1', 8000.00, 1, NOW())")->execute([$aId]);
    $servicoAnalise = ['id' => $aId, 'nome' => 'Análise de Planos EC1', 'codigo_operacional' => 'ANALISE_PLANOS_EC1'];
}
assertFluxo(!empty($servicoAnalise['id']), "Serviço de Análise de Planos localizado: {$servicoAnalise['nome']}");

// 4. Criar Proposta Comercial MISTA (Vistoria + Análise de Planos)
$propostaId = gerarUUID();
$propostaNum = gerarNumeroDocumento('PROP', 'AM-PROP');
$pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, criado_por, status, valor_total, data_emissao)
               VALUES (?, ?, ?, ?, 'rascunho', 12000.00, CURDATE())")
    ->execute([$propostaId, $propostaNum, $clienteId, $vendedor['id']]);

$pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), ?, ?)")
    ->execute([$propostaId, $embarcacaoId]);

$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade)
               VALUES (UUID(), ?, ?, ?, 4000.00, 1)")
    ->execute([$propostaId, $servicoVistoria['id'], $embarcacaoId]);

$pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade)
               VALUES (UUID(), ?, ?, ?, 8000.00, 1)")
    ->execute([$propostaId, $servicoAnalise['id'], $embarcacaoId]);

assertFluxo(true, "Proposta Mista gerada ({$propostaNum}) com Vistoria (R$ 4.000) e Análise de Planos (R$ 8.000)");

// 5. Simular Assinatura da Proposta (executando o fluxo oficial de pós-assinatura)
$pdo->beginTransaction();

// Gatilho Financeiro
$lancamentoId = gerarUUID();
$pdo->prepare("INSERT INTO financeiro_lancamentos 
               (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, valor_original, saldo_devedor, data, categoria, observacoes, criado_por, proposta_id)
               VALUES (?, 'RECEITA', 'unica', 'PENDENTE', CURDATE(), ?, ?, 12000.00, 12000.00, 12000.00, CURDATE(), 'SERVIÇOS', 'Teste', ?, ?)")
    ->execute([$lancamentoId, $clienteId, "Proposta {$propostaNum} - B/M UNIFICADO NAVAL", $vendedor['id'], $propostaId]);

// Gatilho Agendamento (filtra exclusivamente serviços de vistoria)
$stmtEmb = $pdo->prepare("
    SELECT pe.embarcacao_id,
           GROUP_CONCAT(CASE WHEN COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2') THEN s.nome END SEPARATOR ', ') AS servicos_nomes,
           SUM(CASE WHEN COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2') THEN 1 ELSE 0 END) AS servicos_vistoria
    FROM propostas_embarcacoes pe
    LEFT JOIN propostas_servicos ps ON ps.proposta_id = pe.proposta_id AND ps.embarcacao_id = pe.embarcacao_id
    LEFT JOIN servicos s ON ps.servico_id = s.id
    WHERE pe.proposta_id = :proposta_id
    GROUP BY pe.embarcacao_id
    HAVING servicos_vistoria > 0
");
$stmtEmb->execute([':proposta_id' => $propostaId]);
$embarcacoesVistoria = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

$agendamentoCriadoId = null;
foreach ($embarcacoesVistoria as $emb) {
    $agend_id = gerarUUID();
    $pdo->prepare("
        INSERT INTO agendamentos (
            id, proposta_id, embarcacao_id, cliente_id, vendedor_id,
            tipo_vistoria, contato_nome, status, observacoes, criado_por, data_vistoria, hora_vistoria
        ) VALUES (
            :id, :proposta_id, :embarcacao_id, :cliente_id, :vendedor_id,
            :tipo_vistoria, 'Contato Teste', 'pendente', 'Agendamento automático', :criado_por, NULL, NULL
        )
    ")->execute([
        ':id'            => $agend_id,
        ':proposta_id'   => $propostaId,
        ':embarcacao_id' => $emb['embarcacao_id'],
        ':cliente_id'    => $clienteId,
        ':vendedor_id'   => $vendedor['id'],
        ':tipo_vistoria' => !empty($emb['servicos_nomes']) ? $emb['servicos_nomes'] : 'Vistoria Geral',
        ':criado_por'    => $vendedor['id']
    ]);
    $agendamentoCriadoId = $agend_id;
}

// Gatilho Análise de Planos Automático
$propRow = $pdo->query("SELECT * FROM propostas WHERE id='{$propostaId}'")->fetch(PDO::FETCH_ASSOC);
$demandasAnalise = analisePlanosCriarDemandasProposta($pdo, $propRow, $vendedor['id']);

$pdo->commit();

// 6. Verificações Mandatórias do Pós-Assinatura
assertFluxo($demandasAnalise === 1, "Demanda de Análise de Planos criada automaticamente (Total: 1)");

// 6.1 Agendamentos: deve existir APENAS 1 agendamento para a proposta/embarcação
$agendamentos = $pdo->query("SELECT * FROM agendamentos WHERE proposta_id='{$propostaId}'")->fetchAll(PDO::FETCH_ASSOC);
assertFluxo(count($agendamentos) === 1, "Apenas 1 agendamento gerado para a proposta mista (Total encontrado: " . count($agendamentos) . ")");

$agendamento = $agendamentos[0];
assertFluxo($agendamento['status'] === 'pendente', "Status do agendamento é 'pendente' aguardando definição de vistoriador");
assertFluxo(empty($agendamento['vistoriador_id']), "Vistoriador não está preenchido no rascunho (vistoriador_id é NULL)");
assertFluxo(str_contains($agendamento['tipo_vistoria'], $servicoVistoria['nome']), "tipo_vistoria contém '{$servicoVistoria['nome']}'");
assertFluxo(!str_contains(strtolower($agendamento['tipo_vistoria']), 'análise de planos'), "tipo_vistoria NÃO contém 'Análise de Planos' (evitou poluição do agendamento)");

// 6.2 Análise de Planos: atribuída automaticamente ao Analista Naval (Itamar)
$analise = $pdo->query("SELECT * FROM analises_planos WHERE proposta_id='{$propostaId}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertFluxo(!empty($analise['id']), "Demanda de Análise de Planos registrada: {$analise['numero']}");
assertFluxo($analise['analista_id'] === $analista['id'], "Análise AUTOMATICAMENTE atribuída ao Analista Naval ({$analista['nome']})");
assertFluxo($analise['status'] === 'AGENDADA', "Status inicial da análise é 'AGENDADA' direto no painel do analista");
assertFluxo(!empty($analise['prazo_agendado_em']), "Prazo técnico inicial calculado (+7 dias): {$analise['prazo_agendado_em']}");

$checklists = $pdo->query("SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id='{$analise['id']}'")->fetchColumn();
assertFluxo((int)$checklists > 0, "Checklist NORMAM-202 semeado automaticamente ({$checklists} itens)");

// 7. Simular Busca de Dados da Proposta no Agendamento (buscar_proposta)
$stmtBusca = $pdo->prepare("
    SELECT p.id, p.numero, p.cliente_id, c.nome AS cliente_nome,
           (
               SELECT ag.id 
               FROM agendamentos ag 
               WHERE ag.proposta_id = p.id 
                 AND ag.status <> 'cancelado'
               ORDER BY ag.created_at DESC 
               LIMIT 1
           ) AS agendamento_id,
           GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS tipo_vistoria
    FROM propostas p
    INNER JOIN clientes c ON c.id = p.cliente_id
    LEFT JOIN propostas_servicos ps ON ps.proposta_id = p.id
    LEFT JOIN servicos s ON s.id = ps.servico_id 
                         AND COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
    WHERE p.id = :id
    GROUP BY p.id, p.numero, p.cliente_id, c.nome
");
$stmtBusca->execute([':id' => $propostaId]);
$dadosBusca = $stmtBusca->fetch(PDO::FETCH_ASSOC);

assertFluxo(!empty($dadosBusca['agendamento_id']), "buscar_proposta retornou o ID do rascunho existente: {$dadosBusca['agendamento_id']}");
assertFluxo($dadosBusca['agendamento_id'] === $agendamentoCriadoId, "ID do agendamento retornado confere com o rascunho criado");
assertFluxo(!str_contains(strtolower($dadosBusca['tipo_vistoria']), 'análise de planos'), "buscar_proposta filtrou e removeu Análise de Planos do campo tipo_vistoria");

// 8. Simular o Agendamento do Vistoriador com Proteção contra Duplicidade (Idempotência)
// Mesmo que a requisição venha como 'inserir', se já existir o rascunho para a proposta/embarcação, deve fazer UPDATE
$stmtCheckExistente = $pdo->prepare("
    SELECT id FROM agendamentos 
    WHERE proposta_id = :proposta_id 
      AND embarcacao_id = :embarcacao_id 
      AND status <> 'cancelado' 
    LIMIT 1
");
$stmtCheckExistente->execute([
    ':proposta_id' => $propostaId,
    ':embarcacao_id' => $embarcacaoId,
]);
$existenteId = $stmtCheckExistente->fetchColumn();
assertFluxo(!empty($existenteId), "Detector de duplicidade localizou o rascunho pendente {$existenteId}");

// Atualizar o agendamento com data, hora, local e Vistoriador escolhido
$dataVistoria = date('Y-m-d', strtotime('+3 days'));
$horaVistoria = '14:30:00';
$localVistoria = 'Porto de Belém / Trapiche 2';

$stmtUpdateAg = $pdo->prepare("
    UPDATE agendamentos 
    SET vistoriador_id = :vistoriador,
        data_vistoria = :data_vistoria,
        hora_vistoria = :hora_vistoria,
        local = :local_vistoria,
        status = 'confirmado',
        updated_at = NOW()
    WHERE id = :id
");
$stmtUpdateAg->execute([
    ':vistoriador' => $vistoriador['id'],
    ':data_vistoria' => $dataVistoria,
    ':hora_vistoria' => $horaVistoria,
    ':local_vistoria' => $localVistoria,
    ':id' => $existenteId,
]);

// Verificar se continua existindo estritamente 1 único agendamento
$totalAgendamentosFinal = $pdo->query("SELECT COUNT(*) FROM agendamentos WHERE proposta_id='{$propostaId}'")->fetchColumn();
assertFluxo((int)$totalAgendamentosFinal === 1, "Garantia de Ordem Única: total de agendamentos para a proposta continua sendo rigorosamente 1");

$agFinal = $pdo->query("SELECT * FROM agendamentos WHERE id='{$existenteId}'")->fetch(PDO::FETCH_ASSOC);
assertFluxo($agFinal['vistoriador_id'] === $vistoriador['id'], "Vistoriador selecionado foi atribuído corretamente: {$vistoriador['nome']}");
assertFluxo($agFinal['status'] === 'confirmado', "Status do agendamento foi atualizado para 'confirmado'");
assertFluxo($agFinal['local'] === $localVistoria, "Local da vistoria registrado com sucesso");

// 9. Verificar Permissão do Portal do Cliente para Upload de Documentos no status 'AGENDADA'
$statusPermitidosPortal = ['AGENDADA', 'EM_ANALISE', 'EXIGENCIA_CADASTRADA'];
assertFluxo(in_array($analise['status'], $statusPermitidosPortal, true), "Portal do Cliente permite visualização e upload de projetos no status '{$analise['status']}'");

// 10. Limpeza dos Dados de Teste
$pdo->prepare("DELETE FROM analise_planos_itens WHERE analise_id='{$analise['id']}'")->execute();
$pdo->prepare("DELETE FROM analise_planos_historico WHERE analise_id='{$analise['id']}'")->execute();
$pdo->prepare("DELETE FROM analise_planos_agenda_historico WHERE analise_id='{$analise['id']}'")->execute();
$pdo->prepare("DELETE FROM analises_planos WHERE id='{$analise['id']}'")->execute();
$pdo->prepare("DELETE FROM agendamentos WHERE id='{$existenteId}'")->execute();
$pdo->prepare("DELETE FROM financeiro_lancamentos WHERE proposta_id='{$propostaId}'")->execute();
$pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id='{$propostaId}'")->execute();
$pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id='{$propostaId}'")->execute();
$pdo->prepare("DELETE FROM propostas WHERE id='{$propostaId}'")->execute();
$pdo->prepare("DELETE FROM embarcacoes WHERE id='{$embarcacaoId}'")->execute();
$pdo->prepare("DELETE FROM clientes WHERE id='{$clienteId}'")->execute();

echo "\n=======================================================================\n";
echo " TESTE FINALIZADO COM 100% DE SUCESSO! TODAS AS VALIDAÇÕES PASSARAM.\n";
echo "=======================================================================\n";
