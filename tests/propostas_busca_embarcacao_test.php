<?php
/**
 * Teste Automatizado: Busca de Propostas por Nome/Inscrição da Embarcação, Cliente e Número + Prevenção de Reload ao Digitar
 * Arquivo: tests/propostas_busca_embarcacao_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== TESTE: BUSCA DE PROPOSTAS POR EMBARCAÇÃO E SEM RELOAD AO DIGITAR ===\n\n";

$passou = true;

function assertBusca(bool $condicao, string $mensagem): void
{
    global $passou;
    if ($condicao) {
        echo "  [✓] {$mensagem}\n";
    } else {
        echo "  [✗] FALHA: {$mensagem}\n";
        $passou = false;
    }
}

function httpReqBusca(string $metodo, string $path, array $dados = []): array
{
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
            return httpReqBusca('GET', $novoUrl);
        }
    }

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
    ];
}

function extrairCsrfBusca(string $html): string
{
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\']csrf_token["\']/', $html, $m)) {
        return $m[1];
    }
    return '';
}

$suf = rand(10000, 99999);
$cliId = gerarUUID();
$emb1Id = gerarUUID();
$emb2Id = gerarUUID();
$servId = gerarUUID();
$prop1Id = gerarUUID();
$prop2Id = gerarUUID();

$nomeEmb1 = 'BALSA ESTRELA BUSCA ' . $suf;
$regEmb1 = '021-BS-' . $suf;
$nomeEmb2 = 'EMPURRADOR LEGADO BUSCA ' . $suf;
$numProp1 = 'AM-BUSCA-1-' . $suf;
$numProp2 = 'AM-BUSCA-2-' . $suf;

try {
    // 1. Criar dados de teste (cliente, 2 embarcações, 2 propostas: 1 via propostas_embarcacoes e 1 via propostas_servicos)
    $pdo->prepare("INSERT INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, status, ativo) VALUES (:id, :nome, 'PJ', :doc, 'armador', 'ATIVO', 1)")
        ->execute([':id' => $cliId, ':nome' => 'ARMADOR BUSCA TESTE ' . $suf, ':doc' => '99.888.777/0001-' . ($suf % 90 + 10)]);

    $pdo->prepare("INSERT INTO embarcacoes (id, proprietario_id, nome, registro, numero_inscricao, possui_propulsao, ativo) VALUES (:id, :cid, :nome, :reg, :insc, 0, 1)")
        ->execute([':id' => $emb1Id, ':cid' => $cliId, ':nome' => $nomeEmb1, ':reg' => $regEmb1, ':insc' => $regEmb1]);

    $pdo->prepare("INSERT INTO embarcacoes (id, proprietario_id, nome, registro, numero_inscricao, possui_propulsao, ativo) VALUES (:id, :cid, :nome, :reg, :insc, 1, 1)")
        ->execute([':id' => $emb2Id, ':cid' => $cliId, ':nome' => $nomeEmb2, ':reg' => '021-LG-' . $suf, ':insc' => '021-LG-' . $suf]);

    $pdo->prepare("INSERT INTO servicos (id, nome, preco_padrao, ativo) VALUES (:id, :nome, 1500.00, 1)")
        ->execute([':id' => $servId, ':nome' => 'Serviço Busca Teste ' . $suf]);

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, armador_id, status, valor_total, parcelas, data_emissao) VALUES (:id, :num, :cid, :cid2, 'rascunho', 3500.00, 1, CURDATE())")
        ->execute([':id' => $prop1Id, ':num' => $numProp1, ':cid' => $cliId, ':cid2' => $cliId]);

    $pdo->prepare("INSERT INTO propostas (id, numero, cliente_id, armador_id, status, valor_total, parcelas, data_emissao) VALUES (:id, :num, :cid, :cid2, 'enviada', 4200.00, 1, CURDATE())")
        ->execute([':id' => $prop2Id, ':num' => $numProp2, ':cid' => $cliId, ':cid2' => $cliId]);

    // Vínculo da proposta 1 via propostas_embarcacoes
    $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (:id, :pid, :eid)")
        ->execute([':id' => gerarUUID(), ':pid' => $prop1Id, ':eid' => $emb1Id]);

    // Vínculo da proposta 2 apenas via propostas_servicos
    $pdo->prepare("INSERT INTO propostas_servicos (id, proposta_id, embarcacao_id, servico_id, preco_aplicado, quantidade) VALUES (:id, :pid, :eid, :sid, 4200.00, 1)")
        ->execute([':id' => gerarUUID(), ':pid' => $prop2Id, ':eid' => $emb2Id, ':sid' => $servId]);

    echo "1. Testando login HTTP...\n";
    $respLogin = httpReqBusca('GET', '/login');
    $csrf = extrairCsrfBusca($respLogin['body']);
    $auth = httpReqBusca('POST', '/login', [
        'email' => 'teste@teste.com',
        'senha' => 'teste123',
        'csrf_token' => $csrf,
    ]);
    assertBusca($auth['code'] === 200 && !str_contains($auth['body'], 'name="email"'), "Login realizado com sucesso");

    echo "\n2. Validando que o campo de busca não faz reload no onkeyup (bug do Firefox Ctrl+F)...\n";
    $respComercialBase = httpReqBusca('GET', '/comercial');
    assertBusca($respComercialBase['code'] === 200, "GET /comercial respondeu HTTP 200");
    assertBusca(!str_contains($respComercialBase['body'], 'onkeyup="filtrarPropostas()"'), "Input #buscaProposta NÃO usa mais onkeyup com reload imediato");
    assertBusca(str_contains($respComercialBase['body'], 'oninput="agendarBuscaPropostas()"'), "Input #buscaProposta usa debounce AJAX agendarBuscaPropostas()");
    assertBusca(str_contains($respComercialBase['body'], 'id="propostasListaContainer"'), "Container parcial #propostasListaContainer presente para swap sem perder foco");

    echo "\n3. Testando busca por NOME DA EMBARCAÇÃO em /comercial...\n";
    $respBuscaEmb1 = httpReqBusca('GET', '/comercial?busca=' . urlencode('ESTRELA BUSCA ' . $suf));
    assertBusca($respBuscaEmb1['code'] === 200, "GET /comercial?busca=... respondeu HTTP 200");
    assertBusca(str_contains($respBuscaEmb1['body'], $numProp1) && str_contains($respBuscaEmb1['body'], $nomeEmb1), "Busca pelo nome da embarcação encontrou a proposta {$numProp1}");
    assertBusca(!str_contains($respBuscaEmb1['body'], $numProp2), "Filtro excluiu propostas de outras embarcações");

    $respBuscaReg = httpReqBusca('GET', '/comercial?busca=' . urlencode($regEmb1));
    assertBusca(str_contains($respBuscaReg['body'], $numProp1), "Busca pela inscrição/registro da embarcação também encontrou {$numProp1}");

    $respBuscaEmb2 = httpReqBusca('GET', '/comercial?busca=' . urlencode('LEGADO BUSCA ' . $suf));
    assertBusca(str_contains($respBuscaEmb2['body'], $numProp2) && str_contains($respBuscaEmb2['body'], $nomeEmb2), "Busca encontrou proposta vinculada apenas via propostas_servicos ({$numProp2})");

    echo "\n4. Testando busca por NOME DA EMBARCAÇÃO em /comercial/propostas...\n";
    $respSubLista = httpReqBusca('GET', '/comercial/propostas?busca=' . urlencode('ESTRELA BUSCA ' . $suf));
    assertBusca($respSubLista['code'] === 200, "GET /comercial/propostas?busca=... respondeu HTTP 200");
    assertBusca(str_contains($respSubLista['body'], $numProp1) && !str_contains($respSubLista['body'], $numProp2), "Listagem /comercial/propostas filtrou corretamente por nome da embarcação");
    assertBusca(str_contains($respSubLista['body'], 'id="propostasListaContainer"'), "Container #propostasListaContainer presente também em /comercial/propostas");
} finally {
    $pdo->prepare("DELETE FROM propostas_servicos WHERE proposta_id IN (:p1, :p2)")->execute([':p1' => $prop1Id, ':p2' => $prop2Id]);
    $pdo->prepare("DELETE FROM propostas_embarcacoes WHERE proposta_id IN (:p1, :p2)")->execute([':p1' => $prop1Id, ':p2' => $prop2Id]);
    $pdo->prepare("DELETE FROM propostas WHERE id IN (:p1, :p2)")->execute([':p1' => $prop1Id, ':p2' => $prop2Id]);
    $pdo->prepare("DELETE FROM servicos WHERE id = :sid")->execute([':sid' => $servId]);
    $pdo->prepare("DELETE FROM embarcacoes WHERE id IN (:e1, :e2)")->execute([':e1' => $emb1Id, ':e2' => $emb2Id]);
    $pdo->prepare("DELETE FROM clientes WHERE id = :cid")->execute([':cid' => $cliId]);
    echo "\n  [✓] Registros temporários de teste limpos.\n";
}

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: TESTE DE BUSCA POR EMBARCAÇÃO E FOCO 100% APROVADO!\n";
    exit(0);
}
echo "RESULTADO: FALHA NO TESTE DE BUSCA!\n";
exit(1);
