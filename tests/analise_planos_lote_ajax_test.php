<?php
/**
 * Teste de Integração Real HTTP: Inserção em Lote e Operações AJAX de Exigências Técnicas
 * Valida o endpoint analises-planos/actions sob requisições AJAX reais via curl no Apache.
 */

require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
error_reporting(E_ALL);

function assertLote(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [OK] {$msg}\n";
}

function httpReq(string $metodo, string $path, array $dados = [], array $extraHeaders = []): array {
    static $cookieSessao = '';

    $url = 'http://127.0.0.1' . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($cookieSessao !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieSessao);
    }

    $headers = $extraHeaders;
    if (strtoupper($metodo) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersRaw = substr((string)$response, 0, $headerSize);
    $body = substr((string)$response, $headerSize);
    curl_close($ch);

    if (preg_match('/Set-Cookie:\s*(ERPSESSID=[^;]+)/i', $headersRaw, $mCookie)) {
        $cookieSessao = $mCookie[1];
    }

    return ['code' => $httpCode, 'headers' => $headersRaw, 'body' => $body];
}

echo "=================================================================\n";
echo " TESTE: INSERÇÃO EM LOTE E AÇÕES AJAX DE EXIGÊNCIAS NORMAM\n";
echo "=================================================================\n\n";

// 1. Obter token CSRF da página de login e autenticar como admin
echo "1. Autenticando usuário no sistema...\n";
$resLoginGet = httpReq('GET', '/login');
assertLote($resLoginGet['code'] === 200, "Página de login carregada");
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resLoginGet['body'], $mCsrf);
$csrfLogin = $mCsrf[1] ?? '';
assertLote(!empty($csrfLogin), "Token CSRF de login capturado");

$resLoginPost = httpReq('POST', '/login', [
    'csrf_token' => $csrfLogin,
    'email' => 'teste@teste.com',
    'senha' => 'teste123',
]);
assertLote(in_array($resLoginPost['code'], [302, 200], true), "Autenticação realizada com sucesso (HTTP {$resLoginPost['code']})");

// 2. Obter processo de análise em aberto e token CSRF da tela form
echo "\n2. Localizando processo de análise e capturando CSRF...\n";
$analise = $pdo->query("SELECT id, numero, status FROM analises_planos WHERE status IN ('EM_ANALISE', 'AGUARDANDO_DOCUMENTOS') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$analise) {
    $analise = $pdo->query("SELECT id, numero, status FROM analises_planos LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($analise) {
        $pdo->prepare("UPDATE analises_planos SET status='EM_ANALISE' WHERE id=:id")->execute([':id' => $analise['id']]);
        $analise['status'] = 'EM_ANALISE';
    } else {
        throw new RuntimeException("Nenhum processo de análise encontrado no banco de dados.");
    }
}
$analiseId = $analise['id'];

$resForm = httpReq('GET', '/analises-planos/form?id=' . urlencode($analiseId));
assertLote($resForm['code'] === 200, "Tela de análise carregada via HTTP 200");
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resForm['body'], $mCsrfForm);
$csrfForm = $mCsrfForm[1] ?? '';
assertLote(!empty($csrfForm), "Token CSRF da sessão operacional obtido");

// 3. Testar Inserção em Lote (Batch Insert de 3 exigências NORMAM)
echo "\n3. Testando inserção em lote via AJAX (actions.php?action=inserir_exigencias_lote)...\n";
$qTotalInicial = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id=:id");
$qTotalInicial->execute([':id' => $analiseId]);
$totalAntes = (int)$qTotalInicial->fetchColumn();

$itensLote = [
    [
        'categoria' => 'ESTUDO DE ESTABILIDADE',
        'referencia_normativa' => 'NORMAM-202, Item 6.35.',
        'descricao' => '[TESTE_HTTP_LOTE] Rever altura e boca do tanque de combustível no cálculo de GM.'
    ],
    [
        'categoria' => 'CURVAS CRUZADA',
        'referencia_normativa' => 'NORMAM-202, Anexo 3-F, Item 3, D).',
        'descricao' => '[TESTE_HTTP_LOTE] Apresentar curvas de braço de restauração KN para calados intermediários.'
    ],
    [
        'categoria' => 'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.',
        'referencia_normativa' => 'NORMAM-202, Anexo 3-G/ Anexo 3-F.',
        'descricao' => '[TESTE_HTTP_LOTE] Compatibilizar setor de visibilidade das luzes de navegação.'
    ]
];

$resLote = httpReq('POST', '/analises-planos/actions', [
    'csrf_token' => $csrfForm,
    'action' => 'inserir_exigencias_lote',
    'analise_id' => $analiseId,
    'itens_json' => json_encode($itensLote, JSON_UNESCAPED_UNICODE),
    'is_ajax' => '1',
], ['X-Requested-With: XMLHttpRequest']);

assertLote($resLote['code'] === 200, "Requisição AJAX retornou HTTP 200 OK");
$respJson = json_decode($resLote['body'], true);
assertLote(is_array($respJson), "Resposta do servidor é JSON estruturado");
assertLote(!empty($respJson['success']), "Retorno de sucesso da inserção em lote");
assertLote(($respJson['total_inseridos'] ?? 0) === 3, "Total de 3 itens inseridos retornado");
assertLote(count($respJson['itens'] ?? []) === 3, "Array contém exatamente 3 exigências registradas");

$qTotalDepois = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id=:id");
$qTotalDepois->execute([':id' => $analiseId]);
$totalDepois = (int)$qTotalDepois->fetchColumn();
assertLote($totalDepois === $totalAntes + 3, "Banco de dados confirmou inserção de 3 registros ({$totalAntes} -> {$totalDepois})");

// 4. Testar Inserção Rápida Individual Inline (1 Clique / Ctrl+Enter)
echo "\n4. Testando inserção individual rápida inline via AJAX...\n";
$itemUnico = [
    [
        'categoria' => 'MEMORIAL DESCRITIVO',
        'referencia_normativa' => 'NORMAM-202, Item 3.12',
        'descricao' => '[TESTE_HTTP_LOTE] Informar marca, modelo e rotação do motor propulsor principal.'
    ]
];

$resIndividual = httpReq('POST', '/analises-planos/actions', [
    'csrf_token' => $csrfForm,
    'action' => 'inserir_exigencias_lote',
    'analise_id' => $analiseId,
    'itens_json' => json_encode($itemUnico, JSON_UNESCAPED_UNICODE),
    'is_ajax' => '1',
], ['X-Requested-With: XMLHttpRequest']);

assertLote($resIndividual['code'] === 200, "Inserção individual retornou HTTP 200");
$respIndJson = json_decode($resIndividual['body'], true);
assertLote(!empty($respIndJson['success']), "Sucesso na inclusão individual");
assertLote(($respIndJson['total_inseridos'] ?? 0) === 1, "Exatamente 1 item inserido");
$itemCriado = $respIndJson['itens'][0] ?? null;
assertLote(!empty($itemCriado['id']), "ID UUID da exigência retornado: {$itemCriado['id']}");

// 5. Testar Salvar Alterações na Tabela Inline via AJAX
echo "\n5. Testando salvar alterações existentes via AJAX (action=salvar_exigencias)...\n";
$resSalvar = httpReq('POST', '/analises-planos/actions', [
    'csrf_token' => $csrfForm,
    'action' => 'salvar_exigencias',
    'analise_id' => $analiseId,
    'exigencia_id' => [$itemCriado['id']],
    'exigencia_categoria' => ['MEMORIAL DESCRITIVO'],
    'exigencia_descricao' => ['[TESTE_HTTP_LOTE] Informar motor Scania DI13 071M de 500 HP a 1800 RPM.'],
    'exigencia_referencia' => ['NORMAM-202, Item 3.12-REV'],
    'is_ajax' => '1',
], ['X-Requested-With: XMLHttpRequest']);

assertLote($resSalvar['code'] === 200, "Salvar alterações retornou HTTP 200");
$respSalvarJson = json_decode($resSalvar['body'], true);
assertLote(!empty($respSalvarJson['success']), "Sucesso no salvamento das alterações");

$chkDesc = $pdo->prepare("SELECT descricao, referencia_normativa FROM analise_planos_exigencias WHERE id=:id");
$chkDesc->execute([':id' => $itemCriado['id']]);
$rowDesc = $chkDesc->fetch(PDO::FETCH_ASSOC);
assertLote(str_contains($rowDesc['descricao'], 'Scania DI13'), "Descrição editada confirmada no banco");
assertLote($rowDesc['referencia_normativa'] === 'NORMAM-202, Item 3.12-REV', "Referência normativa editada confirmada");

// 6. Testar Exclusão Assíncrona via AJAX (action=excluir_exigencia)
echo "\n6. Testando exclusão assíncrona via AJAX...\n";
$resExcluir = httpReq('POST', '/analises-planos/actions', [
    'csrf_token' => $csrfForm,
    'action' => 'excluir_exigencia',
    'analise_id' => $analiseId,
    'exigencia_id' => $itemCriado['id'],
    'is_ajax' => '1',
], ['X-Requested-With: XMLHttpRequest']);

assertLote($resExcluir['code'] === 200, "Exclusão via AJAX retornou HTTP 200");
$respExcluirJson = json_decode($resExcluir['body'], true);
assertLote(!empty($respExcluirJson['success']), "Exclusão concluída com sucesso");
assertLote(($respExcluirJson['exigencia_id'] ?? '') === $itemCriado['id'], "ID do item excluído retornado no JSON");

$chkExcluido = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE id=:id");
$chkExcluido->execute([':id' => $itemCriado['id']]);
assertLote((int)$chkExcluido->fetchColumn() === 0, "Exigência removida fisicamente do banco de dados");

// 7. Limpeza dos dados de teste
$pdo->prepare("DELETE FROM analise_planos_exigencias WHERE analise_id=:id AND status='PENDENTE' AND descricao LIKE '%[TESTE_HTTP_LOTE]%'")->execute([':id' => $analiseId]);

echo "\n=================================================================\n";
echo " SUCESSO TOTAL: TODOS OS 20 TESTES HTTP AJAX PASSARAM (100%)!\n";
echo "=================================================================\n";
