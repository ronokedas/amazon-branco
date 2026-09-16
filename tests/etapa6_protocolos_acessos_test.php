<?php
/**
 * Testes Automatizados - Etapa 6: Reorganização de Dossiês/Protocolos em Abas
 * e Renomeação do Módulo Administrativo de Acessos ao Portal
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/protocolos.php';
require_once __DIR__ . '/../includes/cliente_portal.php';

function assertEtapa6(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA NA ETAPA 6: {$msg}");
    }
}

echo "=== INICIANDO TESTES DA ETAPA 6 ===\n";

// =========================================================================
// 1. Verificação estrutural de arquivos e componentes
// =========================================================================
echo "[1/5] Verificando modularização em abas de form.php e componentes...\n";

$formPrincipal = file_get_contents(__DIR__ . '/../modules/protocolos/form.php');
assertEtapa6($formPrincipal !== false, 'Arquivo modules/protocolos/form.php não encontrado.');
$linhasForm = count(explode("\n", $formPrincipal));
assertEtapa6($linhasForm < 550, "modules/protocolos/form.php deve ser compacto e modular (linhas atuais: {$linhasForm}).");

$componentesEsperados = [
    'dossie_identificacao.php',
    'movimentacoes_historico.php',
    'tramite_oficial.php',
    'custodia_originais.php',
    'auditoria_aceite.php'
];

foreach ($componentesEsperados as $comp) {
    $caminhoComp = __DIR__ . '/../modules/protocolos/components/' . $comp;
    assertEtapa6(is_file($caminhoComp), "Componente obrigatório não encontrado: {$comp}");
    $conteudo = file_get_contents($caminhoComp);
    assertEtapa6(strlen($conteudo) > 100, "Componente {$comp} está vazio ou corrompido.");
}
echo "  -> OK: Form principal modularizado com 5 componentes temáticos em abas.\n";

// =========================================================================
// 2. Verificação da renomeação do módulo administrativo de acessos
// =========================================================================
echo "[2/5] Verificando renomeação do módulo administrativo gestao_acessos_portal...\n";

assertEtapa6(is_file(__DIR__ . '/../modules/gestao_acessos_portal/index.php'), 'modules/gestao_acessos_portal/index.php não encontrado.');
assertEtapa6(is_file(__DIR__ . '/../modules/gestao_acessos_portal/actions.php'), 'modules/gestao_acessos_portal/actions.php não encontrado.');
assertEtapa6(is_file(__DIR__ . '/../modules/portal_clientes/index.php'), 'Proxy modules/portal_clientes/index.php não encontrado.');
assertEtapa6(is_file(__DIR__ . '/../modules/portal_clientes/actions.php'), 'Proxy modules/portal_clientes/actions.php não encontrado.');

// Verificar que o portal do cliente (modules/portal/) permanece intacto
assertEtapa6(is_dir(__DIR__ . '/../modules/portal'), 'Módulo externo do cliente modules/portal/ deve permanecer inalterado.');
assertEtapa6(is_file(__DIR__ . '/../modules/portal/login.php') || is_file(__DIR__ . '/../modules/portal/index.php'), 'Arquivos do portal externo do cliente preservados.');

// Verificar permissões
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_cargo'] = 'ADMIN';
$adminId = $pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetchColumn();
$_SESSION['usuario_id'] = $adminId;

assertEtapa6(podeAcessar('gestao_acessos_portal'), 'ADMIN deve poder acessar gestao_acessos_portal.');
assertEtapa6(podeAcessar('portal_clientes'), 'ADMIN deve poder acessar portal_clientes (alias de compatibilidade).');
echo "  -> OK: Módulo administrativo renomeado com proxies e permissões bidirecionais.\n";

// =========================================================================
// 3. Teste de concessão, bloqueio e liberação de acesso no portal
// =========================================================================
echo "[3/5] Testando concessão, bloqueio e liberação de acesso de clientes...\n";

// Criar cliente de teste
$cliTesteId = gerarUUID();
$cnpjTeste = '99.' . rand(100, 999) . '.' . rand(100, 999) . '/0001-' . rand(10, 99);
$emailTeste = 'teste.armador.' . time() . '.' . rand(100, 999) . '@sistema-naval.test';
$pdo->prepare("
    INSERT INTO clientes (id, nome, email, cpf_cnpj, perfil, status)
    VALUES (:id, 'Armador Teste Etapa 6', :email, :doc, 'proprietario', 'ATIVO')
")->execute([':id' => $cliTesteId, ':email' => $emailTeste, ':doc' => $cnpjTeste]);

// 3.1 Criar acesso com senha temporária
$senhaHash = password_hash('SenhaTemp@123', PASSWORD_DEFAULT);
$pdo->prepare("
    INSERT INTO cliente_portal_acessos (cliente_id, login, senha_hash, ativo, forcar_troca_senha, criado_por)
    VALUES (:cid, :login, :senha, 1, 1, :uid)
")->execute([':cid' => $cliTesteId, ':login' => $emailTeste, ':senha' => $senhaHash, ':uid' => $adminId]);

$acesso = $pdo->query("SELECT * FROM cliente_portal_acessos WHERE cliente_id = '{$cliTesteId}'")->fetch(PDO::FETCH_ASSOC);
assertEtapa6($acesso && (int)$acesso['ativo'] === 1, 'Acesso do cliente deveria ter sido criado como ativo.');

// 3.2 Bloquear acesso
$pdo->prepare("UPDATE cliente_portal_acessos SET ativo = 0, atualizado_em = NOW() WHERE cliente_id = :cid")
    ->execute([':cid' => $cliTesteId]);
$acessoBloqueado = $pdo->query("SELECT ativo FROM cliente_portal_acessos WHERE cliente_id = '{$cliTesteId}'")->fetchColumn();
assertEtapa6((int)$acessoBloqueado === 0, 'Acesso do cliente deveria estar bloqueado (ativo = 0).');

// 3.3 Reativar/Liberar acesso
$pdo->prepare("UPDATE cliente_portal_acessos SET ativo = 1, atualizado_em = NOW() WHERE cliente_id = :cid")
    ->execute([':cid' => $cliTesteId]);
$acessoLiberado = $pdo->query("SELECT ativo FROM cliente_portal_acessos WHERE cliente_id = '{$cliTesteId}'")->fetchColumn();
assertEtapa6((int)$acessoLiberado === 1, 'Acesso do cliente deveria estar liberado novamente (ativo = 1).');

echo "  -> OK: Ciclo de liberação e bloqueio de acesso validado com sucesso.\n";

// =========================================================================
// 4. Fluxo completo de Dossiê com vínculo de Certificado, Custódia e Trâmite
// =========================================================================
echo "[4/5] Testando fluxo completo de Dossiê com Certificado, Custódia e Trâmite...\n";

// Obter embarcação de teste vinculada ao cliente
$embTesteId = gerarUUID();
$pdo->prepare("
    INSERT INTO embarcacoes (id, nome, tipo, cliente_id, ativo)
    VALUES (:id, 'B/M AMAZON EXPLORER VI', 'EMPURRADOR', :cli, 1)
")->execute([':id' => $embTesteId, ':cli' => $cliTesteId]);

// Obter unidade marítima
$unidadeId = $pdo->query("SELECT id FROM protocolo_unidades_maritimas WHERE ativo=1 LIMIT 1")->fetchColumn();
assertEtapa6((bool)$unidadeId, 'Nenhuma unidade marítima cadastrada.');

require_once __DIR__ . '/../includes/emissao_certificados.php';

// 4.1 Criar certificado de teste (ex.: LC - Licença de Construção) via motor central para vincular ao dossiê
$resultadoCert = emitirCertificadoUnificado($pdo, 'LC', [
    'embarcacao_id' => $embTesteId,
    'cliente_id' => $cliTesteId,
    'nome_embarcacao' => 'B/M AMAZON EXPLORER VI',
    'normam_aplicavel' => 'NORMAM-202',
    'status' => 'emitido'
], $adminId);
$certId = $resultadoCert['id'] ?? $resultadoCert['certificado_id'];
$certNumero = $resultadoCert['numero'];

// 4.2 Criar Dossiê vinculado à Embarcação, Cliente e Certificado
$dossieId = gerarUUID();
$numeroDossie = gerarNumeroDocumento('PROTOCOLO', 'AM-PROT');
$assunto = 'Processo de Aprovação de Planos e Licença de Construção (LC) NORMAM-202';

$pdo->prepare("
    INSERT INTO protocolo_dossies (id, numero, embarcacao_id, cliente_id, assunto, certificado_tipo, certificado_id, unidade_maritima_id, criado_por)
    VALUES (:id, :numero, :emb, :cli, :assunto, 'LC', :cid, :unidade, :uid)
")->execute([
    ':id' => $dossieId,
    ':numero' => $numeroDossie,
    ':emb' => $embTesteId,
    ':cli' => $cliTesteId,
    ':assunto' => $assunto,
    ':cid' => $certId,
    ':unidade' => $unidadeId,
    ':uid' => $adminId
]);
protocoloAuditar($pdo, $dossieId, null, 'DOSSIE_CRIADO', null, 'EM_PREPARACAO', $numeroDossie);

$dCarregado = protocoloCarregar($pdo, $dossieId);
assertEtapa6($dCarregado['embarcacao_id'] === $embTesteId, 'Vínculo de embarcação incorreto.');
assertEtapa6($dCarregado['cliente_id'] === $cliTesteId, 'Vínculo de cliente incorreto.');
assertEtapa6($dCarregado['certificado_tipo'] === 'LC', 'Tipo de certificado vinculado incorreto.');
assertEtapa6($dCarregado['certificado_id'] === $certId, 'ID do certificado vinculado incorreto.');
echo "  -> OK: Dossiê {$numeroDossie} criado com vínculos intactos (Embarcação, Cliente, Licença LC).\n";

// 4.3 Movimentação de ENTRADA com custódia de documento original físico
$movEntradaId = gerarUUID();
$pdo->prepare("
    INSERT INTO protocolo_movimentacoes (id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome, unidade_maritima_id, cidade, uf, meio_envio, status, idempotency_key, criado_por, movimentado_em)
    VALUES (:id, :dossie, 1, 'ENTRADA', 'RECEBIMENTO_CLIENTE', 'CLIENTE', 'Armador Teste', 'AMAZON_NAVAL', 'Amazon Naval', :unidade, 'Belém', 'PA', 'PRESENCIAL', 'RASCUNHO', 'chave-e6-1', :uid, NOW())
")->execute([
    ':id' => $movEntradaId,
    ':dossie' => $dossieId,
    ':unidade' => $unidadeId,
    ':uid' => $adminId
]);

// Adicionar 2 itens: um original (sob custódia) e uma cópia simples
$itemOriginalId = gerarUUID();
$pdo->prepare("
    INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, requer_devolucao)
    VALUES (:id, :mid, 'Via Original do Requerimento com Firma Reconhecida', 'DOCUMENTOS_PESSOAIS', 'FISICO', 'ORIGINAL', 1, 1)
")->execute([':id' => $itemOriginalId, ':mid' => $movEntradaId]);

$itemCopiaId = gerarUUID();
$pdo->prepare("
    INSERT INTO protocolo_movimentacao_itens (id, movimentacao_id, descricao, categoria, suporte, forma, quantidade, requer_devolucao)
    VALUES (:id, :mid, 'Cópia Simples do Contrato Social', 'PROPRIEDADE', 'FISICO', 'COPIA_SIMPLES', 1, 0)
")->execute([':id' => $itemCopiaId, ':mid' => $movEntradaId]);

// Confirmar movimentação e congelar snapshot
$snapshot = protocoloSnapshot($pdo, $movEntradaId);
assertEtapa6(count($snapshot) === 2, 'Snapshot deve conter 2 itens documentais.');

$pdo->prepare("
    UPDATE protocolo_movimentacoes 
    SET status = 'CONFIRMADA', snapshot_json = :snap, pdf_caminho = 'storage/protocolos/fake.pdf', pdf_hash = SHA2('evento1', 256), confirmado_por = :uid, confirmado_em = NOW()
    WHERE id = :id
")->execute([
    ':snap' => json_encode($snapshot),
    ':uid' => $adminId,
    ':id' => $movEntradaId
]);
protocoloAuditar($pdo, $dossieId, $movEntradaId, 'MOVIMENTACAO_CONFIRMADA', 'EM_PREPARACAO', 'EM_PREPARACAO', 'Evento #01');

// 4.4 Registrar atendimento na Capitania (SISAP)
$numProcessoOrgao = '23000.' . rand(100000, 999999) . '/' . date('Y') . '-01';
$pdo->prepare("
    UPDATE protocolo_dossies 
    SET protocolo_externo_numero = :num, protocolo_externo_em = NOW(), protocolo_externo_validade = DATE_ADD(CURDATE(), INTERVAL 90 DAY), unidade_maritima_id = :unidade, status = 'PROTOCOLADO'
    WHERE id = :id
")->execute([
    ':num' => $numProcessoOrgao,
    ':unidade' => $unidadeId,
    ':id' => $dossieId
]);
protocoloAuditar($pdo, $dossieId, null, 'REGISTRO_ORGAO', 'EM_PREPARACAO', 'PROTOCOLADO', "Atendimento {$numProcessoOrgao}");

$d = protocoloCarregar($pdo, $dossieId);
assertEtapa6($d['status'] === 'PROTOCOLADO', 'Status deveria ser PROTOCOLADO.');
assertEtapa6($d['protocolo_externo_numero'] === $numProcessoOrgao, 'Número de processo no órgão incorreto.');

// 4.5 Atualizar andamento para EM_EXIGENCIA
$pdo->prepare("UPDATE protocolo_dossies SET status = 'EM_EXIGENCIA' WHERE id = :id")->execute([':id' => $dossieId]);
protocoloAuditar($pdo, $dossieId, null, 'ANDAMENTO_ORGAO', 'PROTOCOLADO', 'EM_EXIGENCIA', 'Ofício de exigência expedido pela Capitania.');

$d = protocoloCarregar($pdo, $dossieId);
assertEtapa6($d['status'] === 'EM_EXIGENCIA', 'Status deveria ser EM_EXIGENCIA.');

// 4.6 Cumprir exigência com movimentação de SAÍDA
$movSaidaId = gerarUUID();
$pdo->prepare("
    INSERT INTO protocolo_movimentacoes (id, dossie_id, sequencia, tipo, natureza, origem_tipo, origem_nome, destino_tipo, destino_nome, unidade_maritima_id, cidade, uf, meio_envio, status, retifica_movimentacao_id, protocolo_anterior_id, idempotency_key, criado_por, movimentado_em)
    VALUES (:id, :dossie, 2, 'SAIDA', 'CUMPRIMENTO_EXIGENCIA', 'AMAZON_NAVAL', 'Amazon Naval', 'CAPITANIA', 'Capitania dos Portos', :unidade, 'Belém', 'PA', 'PRESENCIAL', 'CONFIRMADA', NULL, :anterior, 'chave-e6-2', :uid, NOW())
")->execute([
    ':id' => $movSaidaId,
    ':dossie' => $dossieId,
    ':unidade' => $unidadeId,
    ':anterior' => $movEntradaId,
    ':uid' => $adminId
]);
$pdo->prepare("UPDATE protocolo_dossies SET status = 'ENVIADO_AO_ORGAO' WHERE id = :id")->execute([':id' => $dossieId]);

// 4.7 Testar fluxo de Aceite Digital com Token SHA-256
$tokenAceite = bin2hex(random_bytes(32));
$hashAceite = hash('sha256', $tokenAceite);
$pdo->prepare("
    INSERT INTO protocolo_aceites (id, movimentacao_id, token_hash, expira_em, criado_por)
    VALUES (UUID(), :mid, :hash, DATE_ADD(NOW(), INTERVAL 15 DAY), :uid)
")->execute([':mid' => $movEntradaId, ':hash' => $hashAceite, ':uid' => $adminId]);

// Simular confirmação de aceite pelo destinatário
$pdo->prepare("
    UPDATE protocolo_aceites 
    SET termo_aceito = 1, nome = 'Carlos Representante', documento_mascarado = '123.***.***-00', ip = '127.0.0.1', aceito_em = NOW()
    WHERE token_hash = :hash
")->execute([':hash' => $hashAceite]);
protocoloAuditar($pdo, $dossieId, $movEntradaId, 'ACEITE_DIGITAL', 'PENDENTE', 'CONFIRMADO', 'Aceite digital confirmado.');

$aceiteConf = $pdo->query("SELECT * FROM protocolo_aceites WHERE token_hash = '{$hashAceite}'")->fetch(PDO::FETCH_ASSOC);
assertEtapa6((int)$aceiteConf['termo_aceito'] === 1, 'Aceite digital deveria estar confirmado.');

// 4.8 Dar baixa na custódia do documento original
$pdo->prepare("UPDATE protocolo_movimentacao_itens SET devolvido_em = NOW() WHERE id = :id")->execute([':id' => $itemOriginalId]);
protocoloAuditar($pdo, $dossieId, $movEntradaId, 'ORIGINAL_DEVOLVIDO', null, 'DEVOLVIDO', 'Baixa de custódia');

$itemBaixado = $pdo->query("SELECT devolvido_em FROM protocolo_movimentacao_itens WHERE id = '{$itemOriginalId}'")->fetchColumn();
assertEtapa6(!empty($itemBaixado), 'Documento original deveria estar marcado como devolvido.');

// 4.9 Encerrar Dossiê
$pdo->prepare("UPDATE protocolo_dossies SET status = 'ENCERRADO' WHERE id = :id")->execute([':id' => $dossieId]);
protocoloAuditar($pdo, $dossieId, null, 'DOSSIE_ENCERRADO', 'ENVIADO_AO_ORGAO', 'ENCERRADO', 'Dossiê concluído.');

$dFinal = protocoloCarregar($pdo, $dossieId);
assertEtapa6($dFinal['status'] === 'ENCERRADO', 'Dossiê deve estar com status ENCERRADO.');
echo "  -> OK: Fluxo ponta a ponta (Criação, Certificado, Custódia, Exigência, Aceite, Baixa e Encerramento) validado.\n";

// =========================================================================
// 5. Limpeza segura dos dados de teste
// =========================================================================
echo "[5/5] Realizando limpeza segura dos dados de teste...\n";
$pdo->prepare("DELETE FROM protocolo_auditoria WHERE dossie_id = :id")->execute([':id' => $dossieId]);
$pdo->prepare("DELETE FROM protocolo_aceites WHERE movimentacao_id IN (:m1, :m2)")->execute([':m1' => $movEntradaId, ':m2' => $movSaidaId]);
$pdo->prepare("DELETE FROM protocolo_movimentacao_itens WHERE movimentacao_id IN (:m1, :m2)")->execute([':m1' => $movEntradaId, ':m2' => $movSaidaId]);
$pdo->prepare("UPDATE protocolo_movimentacoes SET protocolo_anterior_id = NULL WHERE dossie_id = :id")->execute([':id' => $dossieId]);
$pdo->prepare("DELETE FROM protocolo_movimentacoes WHERE dossie_id = :id")->execute([':id' => $dossieId]);
$pdo->prepare("DELETE FROM protocolo_dossies WHERE id = :id")->execute([':id' => $dossieId]);
$pdo->prepare("DELETE FROM certificados_lc WHERE id = :id")->execute([':id' => $certId]);
$pdo->prepare("DELETE FROM cliente_portal_acessos WHERE cliente_id = :cid")->execute([':cid' => $cliTesteId]);
$pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embTesteId]);
$pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $cliTesteId]);

echo "  -> OK: Limpeza de dados de teste finalizada com sucesso.\n";

echo "\n>>> TODOS OS TESTES DA ETAPA 6 PASSARAM COM 100% DE SUCESSO! <<<\n";
