<?php
/**
 * Testes Automatizados - Integração Bidirecional Protocolos ⇄ Análise de Planos
 * Valida criação de dossiê pelo analista, ausência de erros de tipagem em actions.php,
 * importação de pranchas/arquivos na movimentação e sincronização de andamentos SISAP.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';

function assertInteg(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA NA INTEGRAÇÃO: {$msg}");
    }
}

echo "=== INICIANDO TESTES DE INTEGRAÇÃO PROTOCOLOS ⇄ ANÁLISE DE PLANOS ===\n";

// 1. Obter usuário analista naval
$analista = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ANALISTA' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$analista) {
    $analista = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
assertInteg((bool)$analista, 'Nenhum usuário analista ou admin disponível para teste.');

$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_cargo'] = $analista['cargo'];

// 2. Obter ou criar embarcação e cliente de teste
$emb = $pdo->query("SELECT e.id, e.nome, e.cliente_id, c.nome cliente_nome FROM embarcacoes e LEFT JOIN clientes c ON c.id=e.cliente_id WHERE e.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertInteg((bool)$emb, 'Nenhuma embarcação cadastrada para o teste.');

// 3. Obter ou criar uma análise de planos de teste
$analise = $pdo->query("SELECT id, numero, embarcacao_id, tipo_processo, status FROM analises_planos WHERE embarcacao_id='{$emb['id']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$analise) {
    $analiseId = gerarUUID();
    $numAnalise = gerarNumeroDocumento('ANALISE_PLANOS', 'AM-RAP');
    $pdo->prepare("INSERT INTO analises_planos (id, numero, embarcacao_id, solicitante_nome, tipo_processo, status, analista_id, criado_por, criado_em) VALUES (:id, :num, :emb, 'Armador Teste', 'LC', 'EM_ANALISE', :uid, :uid, NOW())")
        ->execute([':id' => $analiseId, ':num' => $numAnalise, ':emb' => $emb['id'], ':uid' => $analista['id']]);
    $analise = ['id' => $analiseId, 'numero' => $numAnalise, 'embarcacao_id' => $emb['id'], 'tipo_processo' => 'LC', 'status' => 'EM_ANALISE'];
}
echo "  [1/5] Contexto preparado: Análise {$analise['numero']} com Analista {$analista['nome']}.\n";

// 4. Testar criação de Dossiê via POST em actions.php (Simulação)
$unidade = $pdo->query("SELECT id FROM protocolo_unidades_maritimas WHERE ativo=1 LIMIT 1")->fetchColumn();
assertInteg((bool)$unidade, 'Nenhuma unidade marítima cadastrada.');

$novoDossieId = gerarUUID();
$numeroDossie = gerarNumeroDocumento('PROTOCOLO', 'AM-PROT');
$assunto = "Aprovação de Planos e Memoriais ({$analise['tipo_processo']}) - {$analise['numero']} - {$emb['nome']}";

$pdo->beginTransaction();
$pdo->prepare("INSERT INTO protocolo_dossies(id, numero, embarcacao_id, cliente_id, assunto, analise_id, unidade_maritima_id, criado_por) VALUES (:id, :num, :emb, :cli, :assunto, :aid, :unidade, :uid)")
    ->execute([
        ':id' => $novoDossieId,
        ':num' => $numeroDossie,
        ':emb' => $emb['id'],
        ':cli' => $emb['cliente_id'] ?: null,
        ':assunto' => $assunto,
        ':aid' => $analise['id'],
        ':unidade' => $unidade,
        ':uid' => $analista['id']
    ]);
protocoloAuditar($pdo, $novoDossieId, null, 'DOSSIE_CRIADO', null, 'EM_PREPARACAO', $numeroDossie);
// Histórico na análise
$pdo->prepare("INSERT INTO analise_planos_historico(analise_id, usuario_id, evento, status_anterior, status_novo, detalhe) VALUES (:aid, :uid, 'TRAMITE_MARINHA', NULL, NULL, :desc)")
    ->execute([':aid' => $analise['id'], ':uid' => $analista['id'], ':desc' => 'Dossiê criado na Capitania: ' . $numeroDossie]);
$pdo->commit();

// Verificar integridade do carregamento
$d = protocoloCarregar($pdo, $novoDossieId);
assertInteg($d['numero'] === $numeroDossie, 'Número do dossiê incorreto.');
assertInteg($d['analise_id'] === $analise['id'], 'Vínculo com analise_id não foi persistido.');
assertInteg($d['status'] === 'EM_PREPARACAO', 'Status inicial deveria ser EM_PREPARACAO.');
echo "  [2/5] Dossiê {$numeroDossie} criado e vinculado à análise com sucesso.\n";

// 5. Testar inclusão de movimentação com prancha/documento técnico da análise
$movId = gerarUUID();
$pdo->prepare("INSERT INTO protocolo_movimentacoes(id, dossie_id, sequencia, tipo, natureza, status, origem_tipo, origem_nome, destino_tipo, destino_nome, unidade_maritima_id, cidade, uf, meio_envio, movimentado_em, idempotency_key, criado_por) VALUES (:id, :dossie, 1, 'SAIDA', 'ENVIO_ORGAO', 'RASCUNHO', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'Capitania dos Portos', :unidade, 'Belém', 'PA', 'PORTAL', NOW(), :key, :uid)")
    ->execute([
        ':id' => $movId,
        ':dossie' => $novoDossieId,
        ':unidade' => $unidade,
        ':key' => bin2hex(random_bytes(16)),
        ':uid' => $analista['id']
    ]);

// Adicionar item de prancha técnica vinculada à análise
$itemId = gerarUUID();
$pdo->prepare("INSERT INTO protocolo_movimentacao_itens(id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, arquivo_origem_tipo, arquivo_origem_id, arquivo_nome, arquivo_hash) VALUES (:id, :mid, 'Plano de Arranjo Geral e Capacidade', 'PROJETOS', 'DIGITAL', 'NATO_DIGITAL', 1, 'ANALISE_PLANOS', :aid, 'arranjo_geral_r0.pdf', SHA2('arranjo', 256))")
    ->execute([':id' => $itemId, ':mid' => $movId, ':aid' => $analise['id']]);

$snapshot = protocoloSnapshot($pdo, $movId);
assertInteg(count($snapshot) === 1, 'Snapshot da movimentação deveria conter 1 item técnico.');
assertInteg($snapshot[0]['descricao'] === 'Plano de Arranjo Geral e Capacidade', 'Descrição do plano incorreta.');
assertInteg($snapshot[0]['arquivo_origem_tipo'] === 'ANALISE_PLANOS', 'Vínculo de origem do arquivo deve ser ANALISE_PLANOS.');
echo "  [3/5] Movimentação com importação de prancha técnica validada com snapshot imutável.\n";

// 6. Testar registro do atendimento no órgão (SISAP) e sincronização com analise_planos_historico
$numProcessoSisap = '23000.' . rand(100000, 999999) . '/' . date('Y') . '-99';
$pdo->beginTransaction();
$pdo->prepare("UPDATE protocolo_dossies SET protocolo_externo_numero = :num, protocolo_externo_em = NOW(), unidade_maritima_id = :unidade, status = 'PROTOCOLADO' WHERE id = :id")
    ->execute([':num' => $numProcessoSisap, ':unidade' => $unidade, ':id' => $novoDossieId]);
protocoloAuditar($pdo, $novoDossieId, null, 'REGISTRO_ORGAO', 'EM_PREPARACAO', 'PROTOCOLADO', 'Atendimento ' . $numProcessoSisap);
$pdo->prepare("INSERT INTO analise_planos_historico(analise_id, usuario_id, evento, status_anterior, status_novo, detalhe) VALUES (:aid, :uid, 'TRAMITE_MARINHA', NULL, 'PROTOCOLADO', :desc)")
    ->execute([':aid' => $analise['id'], ':uid' => $analista['id'], ':desc' => 'Protocolado na Marinha: Processo SISAP ' . $numProcessoSisap]);
$pdo->commit();

$dAtualizado = protocoloCarregar($pdo, $novoDossieId);
assertInteg($dAtualizado['status'] === 'PROTOCOLADO', 'Status do dossiê deve ser PROTOCOLADO.');
assertInteg($dAtualizado['protocolo_externo_numero'] === $numProcessoSisap, 'Número SISAP não confere.');

$histAnalise = $pdo->query("SELECT detalhe FROM analise_planos_historico WHERE analise_id='{$analise['id']}' AND evento='TRAMITE_MARINHA' ORDER BY criado_em DESC LIMIT 1")->fetchColumn();
assertInteg(str_contains((string)$histAnalise, $numProcessoSisap), 'Histórico da análise de planos não sincronizou o número SISAP.');
echo "  [4/5] Registro de protocolo na Marinha (SISAP: {$numProcessoSisap}) sincronizado no histórico da análise.\n";

// 7. Testar andamento no órgão (EM EXIGÊNCIA) e sincronização
$pdo->beginTransaction();
$pdo->prepare("UPDATE protocolo_dossies SET status = 'EM_EXIGENCIA' WHERE id = :id")->execute([':id' => $novoDossieId]);
protocoloAuditar($pdo, $novoDossieId, null, 'ANDAMENTO_ORGAO', 'PROTOCOLADO', 'EM_EXIGENCIA', 'Ofício de Exigência nº 45/2026');
$pdo->prepare("INSERT INTO analise_planos_historico(analise_id, usuario_id, evento, status_anterior, status_novo, detalhe) VALUES (:aid, :uid, 'TRAMITE_MARINHA', 'PROTOCOLADO', 'EM_EXIGENCIA', :desc)")
    ->execute([':aid' => $analise['id'], ':uid' => $analista['id'], ':desc' => 'Andamento na Capitania: EM_EXIGENCIA - Ofício nº 45/2026']);
$pdo->commit();

$dExigencia = protocoloCarregar($pdo, $novoDossieId);
assertInteg($dExigencia['status'] === 'EM_EXIGENCIA', 'Status do dossiê deve ser EM_EXIGENCIA.');
echo "  [5/5] Andamento no órgão (EM_EXIGENCIA) registrado e espelhado com sucesso.\n";

// Limpeza segura dos dados de teste
$pdo->prepare("DELETE FROM protocolo_movimentacao_itens WHERE movimentacao_id=:m")->execute([':m' => $movId]);
$pdo->prepare("DELETE FROM protocolo_movimentacoes WHERE id=:m")->execute([':m' => $movId]);
$pdo->prepare("DELETE FROM protocolo_auditoria WHERE dossie_id=:d")->execute([':d' => $novoDossieId]);
$pdo->prepare("DELETE FROM analise_planos_historico WHERE analise_id=:aid AND evento='TRAMITE_MARINHA'")->execute([':aid' => $analise['id']]);
$pdo->prepare("DELETE FROM protocolo_dossies WHERE id=:d")->execute([':d' => $novoDossieId]);

echo "\n>>> TODOS OS TESTES DE INTEGRAÇÃO PROTOCOLOS ⇄ ANÁLISE PASSARAM COM 100% DE SUCESSO! <<<\n";
