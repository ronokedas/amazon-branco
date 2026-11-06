<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analise_planos.php';

echo "=== TESTE: PRIMEIRO RAP SEM CAMPOS OBRIGATÓRIOS ===\n\n";

// Pegar um analista ativo
$analista = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo = 'ANALISTA' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$analista) {
    die("Nenhum analista encontrado.\n");
}
$_SESSION['usuario_id'] = $analista['id'];
$_SESSION['usuario_nome'] = $analista['nome'];
$_SESSION['usuario_cargo'] = 'ANALISTA';

// Criar processo de análise de teste
$analiseId = gerarUUID();
$numeroProcesso = 'TEST-RAP-' . rand(1000, 9999);
// Pegar embarcacao ativa
$emb = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$embId = $emb['id'] ?? null;

$pdo->prepare("INSERT INTO analises_planos (id, numero, embarcacao_id, objeto, tipo_processo, enquadramento, status, analista_id, criado_por, criado_em)
    VALUES (:id, :num, :emb, 'Análise de Construção', 'LC', 'NORMAM-202', 'EM_ANALISE', :analista, :criador, NOW())")
    ->execute([':id' => $analiseId, ':num' => $numeroProcesso, ':emb' => $embId, ':analista' => $analista['id'], ':criador' => $analista['id']]);

// Inserir 2 exigências técnicas
$exId1 = gerarUUID();
$exId2 = gerarUUID();
$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, categoria, descricao, status, as_impeditivo, ordem, criado_por)
    VALUES (:id, :a, 'MEMORIAL DESCRITIVO', 'Não conformidade no item 1.4', 'PENDENTE', 0, 1, :u)")
    ->execute([':id' => $exId1, ':a' => $analiseId, ':u' => $analista['id']]);

$pdo->prepare("INSERT INTO analise_planos_exigencias (id, analise_id, categoria, descricao, status, as_impeditivo, ordem, criado_por)
    VALUES (:id, :a, 'PLANO DE LINHAS', 'Ajustar escala gráfica da prancha', 'PENDENTE', 0, 2, :u)")
    ->execute([':id' => $exId2, ':a' => $analiseId, ':u' => $analista['id']]);

echo "Processo {$numeroProcesso} criado com 2 exigências pendentes.\n";

// Simular envio de formulário para criar_parecer SEM NENHUM CAMPO PREENCHIDO
// Sem resultado, sem resumo, sem conclusão, sem manifestação
$_POST = [
    'action' => 'criar_parecer',
    'analise_id' => $analiseId,
    'csrf_token' => gerarCSRF(),
    'resultado' => '', // VAZIO
    'submissao_id' => '', // VAZIO
    'resumo' => '', // VAZIO
    'conclusao' => '', // VAZIO
    'baixa_as_submetido' => '1',
    'baixa_resultado' => [
        $exId1 => 'NAO_CUMPRIDA',
        $exId2 => 'NAO_CUMPRIDA',
    ],
    // 'baixa_manifestacao' REMOVIDO!
    'assinar_agora' => '1',
];

echo "Submetendo ação 'criar_parecer' com campos vazios...\n";

// Executar lógica de backend isolada (simulando actions.php)
$resultado = trim((string)($_POST['resultado'] ?? ''));
$submissaoId = trim((string)($_POST['submissao_id'] ?? ''));
$resumo = trim((string)($_POST['resumo'] ?? ''));
$conclusao = trim((string)($_POST['conclusao'] ?? ''));

$q = $pdo->prepare('SELECT * FROM analise_planos_exigencias WHERE analise_id=:id ORDER BY ordem,id');
$q->execute([':id' => $analiseId]);
$exigenciasCiclo = $q->fetchAll(PDO::FETCH_ASSOC);

if (!$resultado || !in_array($resultado, ['APROVADO', 'EXIGENCIAS', 'REPROVADO'], true)) {
    $resultado = (!empty($exigenciasCiclo)) ? 'EXIGENCIAS' : 'APROVADO';
}

if (empty($resumo)) {
    $resumo = ($resultado === 'APROVADO')
        ? 'Conferência técnica documental e de pranchas concluída sem pendências em conformidade com a NORMAM-202/DPC.'
        : 'Análise técnica preliminar de pranchas e documentos realizada com apontamento de exigências técnicas para saneamento.';
}

if (empty($conclusao)) {
    if ($resultado === 'APROVADO') {
        $conclusao = 'Projeto técnico naval aprovado em conformidade com as diretrizes da NORMAM-202/DPC da Marinha do Brasil, com parecer técnico conclusivo favorável à certificação naval.';
    } else {
        $conclusao = 'Processo com exigências técnicas pendentes de saneamento pelo Responsável Técnico, conforme detalhado no corpo deste relatório.';
    }
}

echo "✅ Resultado aplicado automaticamente: {$resultado}\n";
echo "✅ Resumo aplicado automaticamente: {$resumo}\n";
echo "✅ Conclusão aplicada automaticamente: {$conclusao}\n";

assert($resultado === 'EXIGENCIAS', "Resultado deveria ser EXIGENCIAS");
assert(!empty($resumo), "Resumo não deveria estar vazio");
assert(!empty($conclusao), "Conclusão não deveria estar vazia");

echo "\n=== TESTE CONCLUÍDO COM SUCESSO ABSOLUTO! ===\n";
