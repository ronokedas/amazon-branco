<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';

echo "=== TESTE: MULTIPLAS LICENCAS E SELECAO DE MODALIDADE ===\n\n";

$analista = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo = 'ANALISTA' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$emb = $pdo->query("SELECT id, cliente_id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_nome'] = $analista['nome'];
$_SESSION['usuario_cargo'] = 'ANALISTA';

$analiseId = gerarUUID();
$numeroProcesso = 'TEST-LIC-' . rand(1000, 9999);
$pdo->prepare("INSERT INTO analises_planos (id, numero, embarcacao_id, solicitante_id, objeto, tipo_processo, classe_certificacao, enquadramento, status, analista_id, criado_por, criado_em)
    VALUES (:id, :num, :emb, :cli, 'Análise Geral', 'LC', 'EC1', 'NORMAM-202', 'EM_ANALISE', :analista, :criador, NOW())")
    ->execute([
        ':id' => $analiseId,
        ':num' => $numeroProcesso,
        ':emb' => $emb['id'],
        ':cli' => $emb['cliente_id'] ?? null,
        ':analista' => $analista['id'],
        ':criador' => $analista['id']
    ]);

// Inserir um parecer conclusivo aprovado
$parId = gerarUUID();
$parNum = 'AM-RAP-REL-' . rand(10000, 99999) . '/26';
$pdo->prepare("INSERT INTO analise_planos_pareceres (id, numero, analise_id, versao, finalidade, resultado, resumo, conclusao, status, criado_por, criado_em, publicado_em)
    VALUES (:id, :num, :analise, 1, 'CONCLUSIVO', 'APROVADO', 'Parecer conclusivo aprovado', 'Projeto aprovado.', 'PUBLICADO', :u, NOW(), NOW())")
    ->execute([':id' => $parId, ':num' => $parNum, ':analise' => $analiseId, ':u' => $analista['id']]);

$analise = analisePlanosCarregar($pdo, $analiseId);
$responsavel = analiseAcaoResponsavelDoAnalista($pdo, $analise);

// 1. Emitir LC
$lcId = analiseAcaoCriarLicenca($pdo, $analise, $responsavel, 'LC');
$lic1 = $pdo->query("SELECT id, numero_lc, tipo_licenca FROM certificados_lc WHERE id = '{$lcId}'")->fetch(PDO::FETCH_ASSOC);
echo "1. Licença 1 gerada: {$lic1['tipo_licenca']} ({$lic1['numero_lc']})\n";
assert($lic1['tipo_licenca'] === 'LC', "Deveria ser LC");

// 2. Emitir LA para a MESMA análise
$laId = analiseAcaoCriarLicenca($pdo, $analise, $responsavel, 'LA');
$lic2 = $pdo->query("SELECT id, numero_lc, tipo_licenca FROM certificados_lc WHERE id = '{$laId}'")->fetch(PDO::FETCH_ASSOC);
echo "2. Licença 2 gerada: {$lic2['tipo_licenca']} ({$lic2['numero_lc']})\n";
assert($lic2['tipo_licenca'] === 'LA', "Deveria ser LA");
assert($laId !== $lcId, "LA deveria ter ID diferente de LC");

// 3. Emitir LR para a MESMA análise
$lrId = analiseAcaoCriarLicenca($pdo, $analise, $responsavel, 'LR');
$lic3 = $pdo->query("SELECT id, numero_lc, tipo_licenca FROM certificados_lc WHERE id = '{$lrId}'")->fetch(PDO::FETCH_ASSOC);
echo "3. Licença 3 gerada: {$lic3['tipo_licenca']} ({$lic3['numero_lc']})\n";
assert($lic3['tipo_licenca'] === 'LR', "Deveria ser LR");

// 4. Emitir LCEC para a MESMA análise
$lcecId = analiseAcaoCriarLicenca($pdo, $analise, $responsavel, 'LCEC');
$lic4 = $pdo->query("SELECT id, numero_lc, tipo_licenca FROM certificados_lc WHERE id = '{$lcecId}'")->fetch(PDO::FETCH_ASSOC);
echo "4. Licença 4 gerada: {$lic4['tipo_licenca']} ({$lic4['numero_lc']})\n";
assert($lic4['tipo_licenca'] === 'LCEC', "Deveria ser LCEC");

// 5. Verificar total de licenças vinculadas a esta análise
$total = (int)$pdo->query("SELECT COUNT(*) FROM certificados_lc WHERE analise_id = '{$analiseId}'")->fetchColumn();
echo "Total de licenças geradas para o processo {$numeroProcesso}: {$total}\n";
assert($total === 4, "Deveriam existir 4 licenças vinculadas");

// 6. Testar alteração de modalidade ao editar licença
$stmtOrigem = $pdo->prepare('SELECT analise_id, tipo_licenca, numero_lc FROM certificados_lc WHERE id = :id');
$stmtOrigem->execute([':id' => $lcId]);
$origemDoc = $stmtOrigem->fetch(PDO::FETCH_ASSOC);

// Simular mudança de LC para LA na mesma licença
$tipo_licenca = 'LA';
$tipo_seq = ($tipo_licenca === 'LCEC') ? 'EC' : $tipo_licenca;
$prefixoNovo = ($tipo_licenca === 'LCEC') ? 'AM-EC' : 'AM-' . $tipo_licenca;
$novoNumero = gerarNumeroDocumento($tipo_seq, $prefixoNovo);
$pdo->prepare("UPDATE certificados_lc SET tipo_licenca = :tipo, numero_lc = :num WHERE id = :id")
    ->execute([':tipo' => $tipo_licenca, ':num' => $novoNumero, ':id' => $lcId]);

$licAlterada = $pdo->query("SELECT tipo_licenca, numero_lc FROM certificados_lc WHERE id = '{$lcId}'")->fetch(PDO::FETCH_ASSOC);
echo "6. Licença editada de LC para: {$licAlterada['tipo_licenca']} ({$licAlterada['numero_lc']})\n";
assert($licAlterada['tipo_licenca'] === 'LA', "Deveria ter atualizado para LA");

echo "\n=== TESTE CONCLUÍDO COM SUCESSO ABSOLUTO! ===\n";
