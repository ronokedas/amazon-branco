<?php
/**
 * Teste Automatizado: Cadastro de Embarcações com Apenas o Nome Obrigatório
 * Arquivo: tests/embarcacao_apenas_nome_test.php
 */

require_once __DIR__ . '/../config.php';

echo "=== TESTE: CADASTRO DE EMBARCAÇÃO APENAS COM NOME ===\n\n";

$passou = true;

function assertTeste(bool $condicao, string $mensagem): void {
    global $passou;
    if ($condicao) {
        echo "  [✓] {$mensagem}\n";
    } else {
        echo "  [✗] FALHA: {$mensagem}\n";
        $passou = false;
    }
}

function httpReqLocal(string $metodo, string $path, array $dados = []): array {
    static $cookieSessao = '';

    $url = str_starts_with($path, 'http') ? $path : ('http://127.0.0.1' . $path);
    $url = str_replace('localhost:8082', '127.0.0.1', $url);
    $url = str_replace(':8082', '', $url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($cookieSessao !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieSessao);
    }

    if (strtoupper($metodo) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $headers = substr((string)$response, 0, $headerSize);
    $body = substr((string)$response, $headerSize);
    curl_close($ch);

    if (preg_match('/Set-Cookie:\s*(ERPSESSID=[^;]+)/i', $headers, $mCookie)) {
        $cookieSessao = $mCookie[1];
    }

    if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
        if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $loc)) {
            $novoUrl = trim($loc[1]);
            $novoUrl = str_replace(':8082', '', $novoUrl);
            return httpReqLocal('GET', $novoUrl);
        }
    }

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
    ];
}

function extrairCsrf(string $html): string {
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\']csrf_token["\']/', $html, $m)) {
        return $m[1];
    }
    return '';
}

// 1. Autenticação
echo "1. Autenticando usuário no sistema...\n";
$respLogin = httpReqLocal('GET', '/login');
$csrf = extrairCsrf($respLogin['body']);
$auth = httpReqLocal('POST', '/login', [
    'email' => 'teste@teste.com',
    'senha' => 'teste123',
    'csrf_token' => $csrf,
]);
assertTeste($auth['code'] === 200 && !str_contains($auth['body'], 'name="email"'), "Login realizado com sucesso");

// 2. Obter CSRF do formulário de embarcações
echo "\n2. Carregando formulário de embarcação...\n";
$respForm = httpReqLocal('GET', '/embarcacoes/form');
$csrfEmb = extrairCsrf($respForm['body']);
assertTeste(!empty($csrfEmb), "Token CSRF obtido no formulário de embarcações");

// 3. Cadastrar embarcação fornecendo APENAS o nome
echo "\n3. Submetendo cadastro de embarcação apenas com o campo 'nome'...\n";
$nomeTeste = 'EMBARCACAO APENAS NOME ' . rand(10000, 99999);
$respPost = httpReqLocal('POST', '/embarcacoes/actions', [
    'action' => 'salvar',
    'csrf_token' => $csrfEmb,
    'nome' => $nomeTeste,
]);

assertTeste($respPost['code'] === 200, "Submissão respondeu HTTP 200 após redirecionamento");
assertTeste(!str_contains($respPost['body'], 'obrigatório (exigência ISO'), "Nenhum erro de ISO 9001 exibido");
assertTeste(!str_contains($respPost['body'], 'requisito técnico obrigatório'), "Nenhum erro de dimensão obrigatória exibido");

// 4. Verificar persistência no banco
echo "\n4. Verificando dados gravados no banco MySQL...\n";
$stmt = $pdo->prepare("SELECT * FROM embarcacoes WHERE nome = :nome LIMIT 1");
$stmt->execute([':nome' => $nomeTeste]);
$embGravada = $stmt->fetch(PDO::FETCH_ASSOC);

assertTeste(!empty($embGravada), "Embarcação encontrada no banco de dados");
if ($embGravada) {
    assertTeste($embGravada['nome'] === $nomeTeste, "Nome gravado corretamente: {$embGravada['nome']}");
    assertTeste($embGravada['ativo'] == 1, "Status ativo = 1");
    assertTeste(is_null($embGravada['tipo_embarcacao_id']), "tipo_embarcacao_id é NULL");
    assertTeste(is_null($embGravada['comprimento_total']), "comprimento_total é NULL");
    assertTeste(is_null($embGravada['boca_moldada']), "boca_moldada é NULL");
    assertTeste(is_null($embGravada['pontal_moldado']), "pontal_moldado é NULL");
    assertTeste(is_null($embGravada['arqueacao_bruta']), "arqueacao_bruta é NULL");
    assertTeste(is_null($embGravada['possui_propulsao']), "possui_propulsao é NULL");
    assertTeste(is_null($embGravada['fabricante_motor']), "fabricante_motor é NULL");
    echo "  [✓] Todos os campos técnicos complementares foram salvos vazios/NULL com sucesso!\n";

    // 5. Testar edição posterior da mesma embarcação para completar dados
    echo "\n5. Editando a embarcação posteriormente para completar dados técnicos...\n";
    $embId = $embGravada['id'];
    $respEditForm = httpReqLocal('GET', '/embarcacoes/form?id=' . urlencode($embId));
    $csrfEdit = extrairCsrf($respEditForm['body']);

    $tipoId = $pdo->query("SELECT id FROM tipos_embarcacao WHERE ativo = 1 LIMIT 1")->fetchColumn();
    $respUpdate = httpReqLocal('POST', '/embarcacoes/actions', [
        'action' => 'salvar',
        'id' => $embId,
        'csrf_token' => $csrfEdit,
        'nome' => $nomeTeste . ' - COMPLETO',
        'tipo_embarcacao_id' => $tipoId,
        'ano' => '2024',
        'comprimento_total' => '32.50',
        'boca_moldada' => '8.00',
        'pontal_moldado' => '2.50',
        'arqueacao_bruta' => '120.5',
        'possui_propulsao' => '0',
    ]);
    assertTeste($respUpdate['code'] === 200, "Edição respondeu HTTP 200");

    $stmtUpd = $pdo->prepare("SELECT * FROM embarcacoes WHERE id = :id LIMIT 1");
    $stmtUpd->execute([':id' => $embId]);
    $embAtualizada = $stmtUpd->fetch(PDO::FETCH_ASSOC);

    assertTeste($embAtualizada['nome'] === $nomeTeste . ' - COMPLETO', "Nome atualizado");
    assertTeste($embAtualizada['tipo_embarcacao_id'] == $tipoId, "Tipo de embarcação atualizado");
    assertTeste((float)$embAtualizada['comprimento_total'] === 32.50, "Comprimento atualizado para 32.50");
    assertTeste((int)$embAtualizada['possui_propulsao'] === 0, "Propulsão atualizada para 0 (sem propulsão)");

    // 6. Testar que nome continua sendo obrigatório
    echo "\n6. Testando que o nome continua sendo o único campo obrigatório...\n";
    $respNomeVazio = httpReqLocal('POST', '/embarcacoes/actions', [
        'action' => 'salvar',
        'csrf_token' => $csrfEdit,
        'nome' => '',
    ]);
    assertTeste(str_contains($respNomeVazio['body'], 'O nome da embarcação é obrigatório'), "Validação rejeitou embarcação sem nome");

    // Limpeza
    $pdo->prepare("DELETE FROM embarcacoes WHERE id = :id")->execute([':id' => $embId]);
    echo "  [✓] Dados de teste limpos com sucesso.\n";
}

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: CADASTRO COM APENAS NOME FUNCIONANDO 100% COM SUCESSO!\n";
} else {
    echo "RESULTADO: FALHA EM UMA OU MAIS VALIDAÇÕES!\n";
    exit(1);
}
echo "============================================================\n";
