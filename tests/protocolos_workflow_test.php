<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';

function assertWorkflow(bool $ok, string $msg): void {
    if (!$ok) throw new RuntimeException($msg);
}

echo "Iniciando teste completo de workflow de Protocolos...\n";

// 1. Obter usuário e embarcação de teste
$admin = $pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetchColumn();
assertWorkflow((bool)$admin, 'Nenhum usuário ADMIN encontrado para o teste.');

$emb = $pdo->query("SELECT id, COALESCE(cliente_id, proprietario_id) cliente_id FROM embarcacoes WHERE ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assertWorkflow((bool)$emb, 'Nenhuma embarcação encontrada para o teste.');

$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $admin;
$_SESSION['usuario_cargo'] = 'ADMIN';

// 2. Criar Dossiê
$dossieId = gerarUUID();
$numeroDossie = gerarNumeroDocumento('PROTOCOLO', 'AM-PROT');
$assunto = 'Workflow Test - Licença de Construção NORMAM-202';

$unidade = $pdo->query("SELECT id FROM protocolo_unidades_maritimas WHERE ativo=1 LIMIT 1")->fetchColumn();
assertWorkflow((bool)$unidade, 'Nenhuma unidade marítima cadastrada.');

$stmt = $pdo->prepare("INSERT INTO protocolo_dossies (id, numero, embarcacao_id, cliente_id, assunto, unidade_maritima_id, criado_por) 
VALUES (:id, :numero, :emb, :cli, :assunto, :unidade, :uid)");
$stmt->execute([
    ':id' => $dossieId,
    ':numero' => $numeroDossie,
    ':emb' => $emb['id'],
    ':cli' => $emb['cliente_id'] ?: null,
    ':assunto' => $assunto,
    ':unidade' => $unidade,
    ':uid' => $admin
]);
protocoloAuditar($pdo, $dossieId, null, 'DOSSIE_CRIADO', null, 'EM_PREPARACAO', $numeroDossie);

$d = protocoloCarregar($pdo, $dossieId);
assertWorkflow($d['numero'] === $numeroDossie, 'Número do dossiê não confere.');
assertWorkflow($d['status'] === 'EM_PREPARACAO', 'Status inicial deve ser EM_PREPARACAO.');
echo "  [✓] Dossiê criado: {$numeroDossie}\n";

// 3. Editar Dossiê
$assuntoAtualizado = 'Workflow Test - LC NORMAM-202 Atualizado';
$pdo->prepare("UPDATE protocolo_dossies SET assunto = :assunto WHERE id = :id")
    ->execute([':assunto' => $assuntoAtualizado, ':id' => $dossieId]);
protocoloAuditar($pdo, $dossieId, null, 'DOSSIE_EDITADO', null, null, 'Assunto atualizado.');

$d = protocoloCarregar($pdo, $dossieId);
assertWorkflow($d['assunto'] === $assuntoAtualizado, 'Assunto não foi atualizado.');
echo "  [✓] Dossiê editado com sucesso\n";

// 4. Adicionar Movimentação de ENTRADA (com item de custódia que exige devolução)
$movId = gerarUUID();
$seq = 1;
$key = bin2hex(random_bytes(16));

$stmt = $pdo->prepare("INSERT INTO protocolo_movimentacoes 
(id, dossie_id, sequencia, tipo, natureza, status, origem_tipo, origem_nome, destino_tipo, destino_nome, unidade_maritima_id, cidade, uf, meio_envio, movimentado_em, idempotency_key, criado_por)
VALUES (:id, :dossie, :seq, 'ENTRADA', 'RECEBIMENTO_CLIENTE', 'RASCUNHO', 'CLIENTE', 'Armador Teste', 'AMAZON_NAVAL', 'Amazon Naval', :unidade, 'Belém', 'PA', 'PRESENCIAL', NOW(), :key, :uid)");
$stmt->execute([
    ':id' => $movId,
    ':dossie' => $dossieId,
    ':seq' => $seq,
    ':unidade' => $unidade,
    ':key' => $key,
    ':uid' => $admin
]);

$catDoc = $pdo->query("SELECT id, nome, categoria FROM protocolo_catalogo_documentos WHERE codigo='ART' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Inserir item 1 (Comum)
$itemId1 = gerarUUID();
$pdo->prepare("INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, catalogo_id, descricao, categoria, suporte, forma, quantidade, requer_devolucao)
VALUES (:id, :mov, :cat, :desc, :categoria, 'DIGITAL', 'NATO_DIGITAL', 1, 0)")
    ->execute([
        ':id' => $itemId1,
        ':mov' => $movId,
        ':cat' => $catDoc['id'] ?? null,
        ':desc' => $catDoc['nome'] ?? 'ART de Projeto',
        ':categoria' => $catDoc['categoria'] ?? 'RESPONSABILIDADE_TECNICA'
    ]);

// Inserir item 2 (Original com custódia que exige devolução)
$itemIdCustodia = gerarUUID();
$pdo->prepare("INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, condicao_documento, requer_devolucao)
VALUES (:id, :mov, 'Escritura Pública de Compra e Venda (Original)', 'PROPRIEDADE', 'FISICO', 'ORIGINAL', 1, 'Via original com selo', 1)")
    ->execute([
        ':id' => $itemIdCustodia,
        ':mov' => $movId
    ]);

echo "  [✓] Movimentação #01 de ENTRADA criada com itens (1 original sob custódia)\n";

// 5. Confirmar e Congelar a Movimentação
$snapshot = protocoloSnapshot($pdo, $movId);
assertWorkflow(count($snapshot) === 2, 'Snapshot deve conter 2 itens.');

$relPdf = 'storage/protocolos/' . date('Y') . '/' . $dossieId . '/evento-01.pdf';
$absPdf = dirname(__DIR__) . '/' . $relPdf;
if (!is_dir(dirname($absPdf))) mkdir(dirname($absPdf), 0750, true);

$salvar_pdf_caminho = $absPdf;
$movimentacao_pdf_id = $movId;
require __DIR__ . '/../modules/protocolos/pdf.php';
assertWorkflow(is_file($absPdf) && filesize($absPdf) > 200, 'PDF da movimentação não foi gerado.');
$hashPdf = hash_file('sha256', $absPdf);

$pdo->prepare("UPDATE protocolo_movimentacoes SET status='CONFIRMADA', snapshot_json=:snap, pdf_caminho=:pdf, pdf_hash=:hash, confirmado_por=:uid, confirmado_em=NOW() WHERE id=:id")
    ->execute([':snap' => json_encode($snapshot), ':pdf' => $relPdf, ':hash' => $hashPdf, ':uid' => $admin, ':id' => $movId]);

protocoloAuditar($pdo, $dossieId, $movId, 'MOVIMENTACAO_CONFIRMADA', 'EM_PREPARACAO', 'EM_PREPARACAO', 'Evento 01', $hashPdf);
echo "  [✓] Movimentação confirmada e PDF congelado com SHA-256\n";

// 6. Registro de Atendimento na Marinha com Número Oficial
$numProcessoMarinha = '23000.098765/2026-12';
$pdo->prepare("UPDATE protocolo_dossies SET protocolo_externo_numero = :num_ext, protocolo_externo_em = NOW(), protocolo_externo_validade = DATE_ADD(CURDATE(), INTERVAL 90 DAY), unidade_maritima_id = :unidade, status = 'PROTOCOLADO' WHERE id = :id")
    ->execute([
        ':num_ext' => $numProcessoMarinha,
        ':unidade' => $unidade,
        ':id' => $dossieId
    ]);
protocoloAuditar($pdo, $dossieId, null, 'REGISTRO_ORGAO', 'EM_PREPARACAO', 'PROTOCOLADO', 'Processo Marinha: ' . $numProcessoMarinha);

$d = protocoloCarregar($pdo, $dossieId);
assertWorkflow($d['protocolo_externo_numero'] === $numProcessoMarinha, 'Número do processo na Marinha não foi salvo.');
assertWorkflow($d['status'] === 'PROTOCOLADO', 'Status deve ser PROTOCOLADO.');
echo "  [✓] Atendimento no órgão registrado com processo: {$numProcessoMarinha}\n";

// 7. Gerar Token de Aceite Digital
$token = bin2hex(random_bytes(32));
$hashToken = hash('sha256', $token);
$pdo->prepare("INSERT INTO protocolo_aceites (id, movimentacao_id, token_hash, expira_em, criado_por) 
VALUES (UUID(), :mov, :hash, DATE_ADD(NOW(), INTERVAL 15 DAY), :uid)")
    ->execute([':mov' => $movId, ':hash' => $hashToken, ':uid' => $admin]);
protocoloAuditar($pdo, $dossieId, $movId, 'ACEITE_CRIADO', null, 'PENDENTE');

$checkAceite = $pdo->prepare("SELECT COUNT(*) FROM protocolo_aceites WHERE token_hash=:h");
$checkAceite->execute([':h' => $hashToken]);
assertWorkflow((int)$checkAceite->fetchColumn() === 1, 'Aceite digital não foi registrado.');
echo "  [✓] Link de aceite digital gerado com token SHA-256\n";

// 8. Baixa de Custódia (Devolução do original ao cliente)
$pdo->prepare("UPDATE protocolo_movimentacao_itens SET devolvido_em = NOW() WHERE id = :id")
    ->execute([':id' => $itemIdCustodia]);
protocoloAuditar($pdo, $dossieId, $movId, 'ORIGINAL_DEVOLVIDO', null, 'DEVOLVIDO', 'Baixa de custódia');

$itemCustodia = $pdo->query("SELECT devolvido_em FROM protocolo_movimentacao_itens WHERE id = '{$itemIdCustodia}'")->fetch(PDO::FETCH_ASSOC);
assertWorkflow(!empty($itemCustodia['devolvido_em']), 'Baixa da custódia do original não foi registrada.');
echo "  [✓] Baixa da custódia do original registrada com sucesso\n";

// 9. Gerar PDF Consolidado do Dossiê
$salvar_pdf_dossie_caminho = dirname(__DIR__) . '/storage/protocolos/' . date('Y') . '/' . $dossieId . '/dossie_consolidado.pdf';
$dossie_pdf_id = $dossieId;
require __DIR__ . '/../modules/protocolos/pdf_dossie.php';
assertWorkflow(is_file($salvar_pdf_dossie_caminho) && filesize($salvar_pdf_dossie_caminho) > 500, 'PDF consolidado do dossiê falhou ao gerar.');
echo "  [✓] PDF Consolidado do Dossiê gerado com sucesso (" . filesize($salvar_pdf_dossie_caminho) . " bytes)\n";

// 10. Encerrar Dossiê
$pdo->prepare("UPDATE protocolo_dossies SET status = 'ENCERRADO' WHERE id = :id")
    ->execute([':id' => $dossieId]);
protocoloAuditar($pdo, $dossieId, null, 'DOSSIE_ENCERRADO', 'PROTOCOLADO', 'ENCERRADO');

$d = protocoloCarregar($pdo, $dossieId);
assertWorkflow($d['status'] === 'ENCERRADO', 'Status final deve ser ENCERRADO.');
echo "  [✓] Dossiê encerrado com sucesso\n";

echo "\nTODOS OS 10 PASSOS DO WORKFLOW DE PROTOCOLOS PASSARAM COM SUCESSO!\n";
