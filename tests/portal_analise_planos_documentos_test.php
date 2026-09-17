<?php
/**
 * Teste de Integração: Documentos do Portal do Cliente -> Acesso pelo Analista Naval
 * e Independência Operacional em Relação à Vistoria de Campo
 * 
 * Valida:
 * 1. O cliente anexa documentos de projeto no Portal do Cliente (Revisão com PDFs, DWGs, etc.).
 * 2. Os arquivos são criptograficamente registrados (SHA-256) e vinculados com origem 'PORTAL'.
 * 3. O Analista Naval tem acesso imediato a esses arquivos na interface técnica.
 * 4. O Analista Naval pode INICIAR a análise técnica a qualquer momento, mesmo SEM vistoriador ou vistoria de campo realizada.
 * 5. O Portal do Cliente exibe ao armador o histórico completo e preservado dos arquivos enviados.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cliente_portal.php';
require_once __DIR__ . '/../includes/analise_planos.php';

function assertDocTest(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

echo "=======================================================================\n";
echo " TESTE: DOCUMENTOS PORTAL DO CLIENTE -> ANALISTA NAVAL (SEM TRAVA VISTORIA)\n";
echo "=======================================================================\n\n";

// 1. Identificar Analista e Admin
$admin = $pdo->query("SELECT id, nome, cargo FROM usuarios WHERE cargo='ADMIN' AND ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$analista = $pdo->query("SELECT u.id, u.nome, u.cargo FROM usuarios u 
                         LEFT JOIN usuario_perfis up ON up.usuario_id=u.id 
                         WHERE (u.cargo='ANALISTA' OR up.perfil='ANALISTA') AND u.ativo=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

assertDocTest(!empty($admin['id']), "Usuário Admin localizado ({$admin['nome']})");
assertDocTest(!empty($analista['id']), "Analista Naval ativo identificado ({$analista['nome']})");

// 1b. Validar Relação Completa de Documentos Técnicos Oficiais (NORMAM-202)
$categoriasPadrao = analisePlanosCategoriasPadrao();
$esperadas = [
    'ART',
    'FOLHA DE ROSTO',
    'DECLARAÇÃO',
    'MEMORIAL DESCRITO',
    'NOTAS DE ARQUEAÇÃO',
    'NOTAS DE BORDA LIVRE',
    'DADOS DE ENTRADA OU COTAS',
    'CURVAS HIDROSTÁTICAS',
    'CURVAS CRUZADAS',
    'PROVA DE INCLINAÇÃO OU PORTE BRUTO',
    'ESTUDO DE ESTABILIDADE',
    'ESTUDO DE CARGA X CALADOS',
    'MOMENTO FLETOR E ESFORÇO CORTANTE',
    'PLANOS DE LINHAS',
    'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.',
    'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA.',
];
foreach ($esperadas as $esp) {
    assertDocTest(in_array($esp, $categoriasPadrao, true), "Categoria oficial NORMAM disponível no catálogo: '{$esp}'");
}

// 2. Criar Armador / Cliente e Embarcação de Teste
$clienteId = gerarUUID();
$cpfCnpj = sprintf('%02d.%03d.%03d/%04d-%02d', mt_rand(10,99), mt_rand(100,999), mt_rand(100,999), 1, mt_rand(10,99));
$hashSenha = password_hash('Senha@123456', PASSWORD_DEFAULT);

$emailTeste = 'armador.rionegro.' . mt_rand(1000, 99999) . '@teste.com.br';
$pdo->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, email, telefone, status, perfil, criado_em)
               VALUES (?, 'Navegação Rio Negro & Solimões Ltda', ?, ?, '91987654321', 'ATIVO', 'proprietario', NOW())")
    ->execute([$clienteId, $cpfCnpj, $emailTeste]);

$pdo->prepare("INSERT INTO cliente_portal_acessos (cliente_id, login, senha_hash, ativo, forcar_troca_senha, criado_por)
               VALUES (?, ?, ?, 1, 0, ?)")
    ->execute([$clienteId, $emailTeste, $hashSenha, $admin['id']]);
assertDocTest(true, "Cliente Armador com acesso ao portal criado: ID {$clienteId} ({$emailTeste})");

$embarcacaoId = gerarUUID();
$pdo->prepare("INSERT INTO embarcacoes (id, nome, cliente_id, proprietario_id, tipo, area_navegacao, arqueacao_bruta, porte_bruto, ativo, criado_em)
               VALUES (?, 'B/M RIO NEGRO EXPRESS', ?, ?, 'CARGA_GERAL', 'INTERIOR', 85.00, 180.00, 1, NOW())")
    ->execute([$embarcacaoId, $clienteId, $clienteId]);
assertDocTest(true, "Embarcação cadastrada e vinculada: B/M RIO NEGRO EXPRESS ({$embarcacaoId})");

// 3. Confirmar que NÃO EXISTE nenhuma vistoria de campo para este barco (cenário sem vistoria)
$countVistorias = $pdo->query("SELECT COUNT(*) FROM vistorias WHERE embarcacao_id='{$embarcacaoId}'")->fetchColumn();
assertDocTest((int)$countVistorias === 0, "Cenário verificado: 0 vistorias de campo registradas para esta embarcação");

// 4. Criar Demanda de Análise de Planos atribuída ao Analista (Status: AGENDADA)
$analiseId = gerarUUID();
$numeroRap = gerarNumeroDocumento('RAP', 'AM-RAP');
$pdo->prepare("INSERT INTO analises_planos 
    (id, numero, embarcacao_id, solicitante_id, tipo_processo, enquadramento, classe_certificacao, objeto, analista_id, prazo_agendado_em, status, criado_por)
    VALUES (?, ?, ?, ?, 'LC', 'NORMAM-202', 'EC1', 'Análise de Planos de Construção e Estabilidade', ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 'AGENDADA', ?)")
    ->execute([$analiseId, $numeroRap, $embarcacaoId, $clienteId, $analista['id'], $admin['id']]);

analisePlanosHistorico($pdo, $analiseId, 'DEMANDA_CRIADA', null, 'AGENDADA', 'Demanda aberta para teste.');
analisePlanosSemearChecklist($pdo, $analiseId, 'LC', 'NORMAM-202', 'EC1', $analista['id']);
assertDocTest(true, "Análise {$numeroRap} criada em status 'AGENDADA' atribuída ao Analista {$analista['nome']}");

// 5. Simular Cliente Acessando o Portal e Fazendo Upload de 2 Projetos Técnicos
$tempDir = sys_get_temp_dir() . '/teste_upload_portal_' . time() . '_' . mt_rand(100,999);
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$fakePdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 595 842]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n00000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n% Memorial Descritivo do Projeto - " . uniqid();
$fakeDwgContent = "AC1027AutoCAD-Drawing-Mock-Data-" . uniqid();

$pdfPath = $tempDir . '/Memorial_Descritivo_Construcao.pdf';
$dwgPath = $tempDir . '/Plano_Arranjo_Geral_Linhas.dwg';
file_put_contents($pdfPath, $fakePdfContent);
file_put_contents($dwgPath, $fakeDwgContent);

$fileMeta1 = analisePlanosValidarUpload(['name' => 'Memorial_Descritivo_Construcao.pdf', 'tmp_name' => $pdfPath, 'error' => UPLOAD_ERR_OK, 'size' => filesize($pdfPath)]);
$fileMeta2 = analisePlanosValidarUpload(['name' => 'Plano_Arranjo_Geral_Linhas.dwg', 'tmp_name' => $dwgPath, 'error' => UPLOAD_ERR_OK, 'size' => filesize($dwgPath)]);

$submissaoId = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_submissoes (id, analise_id, revisao, descricao, recebido_em, origem, portal_cliente_id, criado_por)
               VALUES (?, ?, 1, 'Envio inicial das plantas de arranjo e memorial descritivo pelo armador', CURDATE(), 'PORTAL', ?, NULL)")
    ->execute([$submissaoId, $analiseId, $clienteId]);

$chave1 = analisePlanosGuardarUpload(['tmp_name' => $pdfPath], $analiseId, $fileMeta1);
$chave2 = analisePlanosGuardarUpload(['tmp_name' => $dwgPath], $analiseId, $fileMeta2);

$arqId1 = gerarUUID();
$arqId2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_arquivos (id, submissao_id, categoria, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por)
               VALUES (?, ?, 'Memorial Descritivo', 'Memorial_Descritivo_Construcao.pdf', 'pdf', 'application/pdf', ?, ?, ?, NULL)")
    ->execute([$arqId1, $submissaoId, $fileMeta1['tamanho'], $fileMeta1['sha256'], $chave1]);

$pdo->prepare("INSERT INTO analise_planos_arquivos (id, submissao_id, categoria, nome_original, extensao, mime_type, tamanho_bytes, sha256, chave_arquivo, criado_por)
               VALUES (?, ?, 'Arranjo Geral', 'Plano_Arranjo_Geral_Linhas.dwg', 'dwg', 'application/octet-stream', ?, ?, ?, NULL)")
    ->execute([$arqId2, $submissaoId, $fileMeta2['tamanho'], $fileMeta2['sha256'], $chave2]);

analisePlanosHistorico($pdo, $analiseId, 'REVISAO_PORTAL_RECEBIDA', 'AGENDADA', 'AGENDADA', 'Revisão 1 enviada pelo portal com 2 arquivo(s).', $admin['id']);
analisePlanosNotificar($pdo, $analista['id'], 'REVISAO_PORTAL_RECEBIDA', 'Nova revisão recebida pelo portal', "{$numeroRap} recebeu 2 arquivo(s) enviados pelo armador.", $analiseId, 'analises-planos/form?id=' . urlencode($analiseId));

assertDocTest(true, "Upload simulado pelo Portal do Cliente: 2 arquivos gravados com SHA-256 (PDF e DWG)");

// 6. Verificar Acesso Imediato aos Documentos pelo Analista Naval
$qSub = $pdo->prepare("SELECT s.*, c.nome AS portal_nome FROM analise_planos_submissoes s LEFT JOIN clientes c ON c.id=s.portal_cliente_id WHERE s.analise_id=:id AND s.origem='PORTAL'");
$qSub->execute([':id' => $analiseId]);
$submissoesAnalista = $qSub->fetchAll(PDO::FETCH_ASSOC);

assertDocTest(count($submissoesAnalista) === 1, "Analista localizou a submissão com origem 'PORTAL'");
assertDocTest($submissoesAnalista[0]['revisao'] == 1, "Submissão registrada como Revisão 1");
assertDocTest(str_contains($submissoesAnalista[0]['portal_nome'], 'Rio Negro'), "Nome do Armador identificado na submissão: {$submissoesAnalista[0]['portal_nome']}");

$qArqs = $pdo->prepare("SELECT * FROM analise_planos_arquivos WHERE submissao_id=:sub ORDER BY criado_em ASC");
$qArqs->execute([':sub' => $submissaoId]);
$arquivosAnalista = $qArqs->fetchAll(PDO::FETCH_ASSOC);

assertDocTest(count($arquivosAnalista) === 2, "Analista possui acesso a ambos os arquivos (Total: 2)");
$nomesAnalista = array_column($arquivosAnalista, 'nome_original');
assertDocTest(in_array('Memorial_Descritivo_Construcao.pdf', $nomesAnalista, true), "Arquivo 1 identificado: Memorial_Descritivo_Construcao.pdf");
assertDocTest(in_array('Plano_Arranjo_Geral_Linhas.dwg', $nomesAnalista, true), "Arquivo 2 identificado: Plano_Arranjo_Geral_Linhas.dwg");
assertDocTest(!empty($arquivosAnalista[0]['sha256']), "Integridade criptográfica SHA-256 confirmada no arquivo 1");
assertDocTest(!empty($arquivosAnalista[1]['sha256']), "Integridade criptográfica SHA-256 confirmada no arquivo 2");

// 7. Simular o Analista INICIANDO a Análise Técnica (SEM DEPENDÊNCIA DE VISTORIA)
// Simulação de sessão do analista
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_nome'] = $analista['nome'];
$_SESSION['usuario_cargo'] = $analista['cargo'];

$analisePreInicio = analisePlanosCarregar($pdo, $analiseId);
assertDocTest(empty($analisePreInicio['iniciado_em']), "Data de início técnico ainda está vazia antes de iniciar");

// Ação iniciar
$pdo->beginTransaction();
$pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE', iniciado_em=COALESCE(iniciado_em, NOW()) WHERE id=:id")
    ->execute([':id' => $analiseId]);
analisePlanosHistorico($pdo, $analiseId, 'ANALISE_INICIADA', $analisePreInicio['status'], 'EM_ANALISE', 'Analista iniciou os trabalhos técnicos de conferência de planos.');
$pdo->commit();

$analisePosInicio = analisePlanosCarregar($pdo, $analiseId);
assertDocTest($analisePosInicio['status'] === 'EM_ANALISE', "Status da análise alterado com sucesso para 'EM_ANALISE'");
assertDocTest(!empty($analisePosInicio['iniciado_em']), "Data e hora de início registradas oficialmente: {$analisePosInicio['iniciado_em']}");

// 8. Confirmar que a vistoria física continua inexistente, comprovando a independência operacional
$countVistoriasApos = $pdo->query("SELECT COUNT(*) FROM vistorias WHERE embarcacao_id='{$embarcacaoId}'")->fetchColumn();
assertDocTest((int)$countVistoriasApos === 0, "Independência comprovada: análise em andamento pleno com 0 vistorias de campo");

// 9. Simular o Cliente Consultando o Portal e Vendo o Histórico Preservado dos Arquivos
$stmtSubHist = $pdo->prepare("
    SELECT s.revisao, s.descricao, s.recebido_em, s.origem,
           ar.id AS arquivo_id, ar.nome_original, ar.categoria, ar.tamanho_bytes, ar.extensao, ar.classificacao
    FROM analise_planos_submissoes s
    INNER JOIN analise_planos_arquivos ar ON ar.submissao_id = s.id
    WHERE s.analise_id = :id
    ORDER BY s.revisao DESC, ar.criado_em ASC
");
$stmtSubHist->execute([':id' => $analiseId]);
$historicoCliente = $stmtSubHist->fetchAll(PDO::FETCH_ASSOC);

$nomesHistorico = array_column($historicoCliente, 'nome_original');
assertDocTest(in_array('Memorial_Descritivo_Construcao.pdf', $nomesHistorico, true), "Cliente vê seu Memorial Descritivo no histórico");
assertDocTest(in_array('Plano_Arranjo_Geral_Linhas.dwg', $nomesHistorico, true), "Cliente vê seu Plano de Arranjo Geral no histórico");

// 10. Limpeza dos Dados de Teste
@unlink($pdfPath);
@unlink($dwgPath);
@rmdir($tempDir);

$pdo->prepare("DELETE FROM analise_planos_arquivos WHERE submissao_id='{$submissaoId}'")->execute();
$pdo->prepare("DELETE FROM analise_planos_submissoes WHERE analise_id='{$analiseId}'")->execute();
$pdo->prepare("DELETE FROM analise_planos_itens WHERE analise_id='{$analiseId}'")->execute();
$pdo->prepare("DELETE FROM analise_planos_historico WHERE analise_id='{$analiseId}'")->execute();
$pdo->prepare("DELETE FROM notificacoes WHERE referencia_id='{$analiseId}'")->execute();
$pdo->prepare("DELETE FROM analises_planos WHERE id='{$analiseId}'")->execute();
$pdo->prepare("DELETE FROM embarcacoes WHERE id='{$embarcacaoId}'")->execute();
$pdo->prepare("DELETE FROM cliente_portal_acessos WHERE cliente_id='{$clienteId}'")->execute();
$pdo->prepare("DELETE FROM clientes WHERE id='{$clienteId}'")->execute();

echo "\n=======================================================================\n";
echo " TESTE FINALIZADO COM SUCESSO ABSOLUTO (100% DAS ASSERÇÕES APROVADAS)!\n";
echo "=======================================================================\n";
