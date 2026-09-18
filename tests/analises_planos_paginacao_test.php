<?php
require_once __DIR__ . '/../config.php';

function httpReq(string $metodo, string $path, array $dados = [], bool $limparSessao = false): array {
    static $cookieSessao = '';
    if ($limparSessao) {
        $cookieSessao = '';
    }

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
            return httpReq('GET', $novoUrl);
        }
    }

    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

echo "=== TESTE HTTP REAL DE PAGINAÇÃO DE ANÁLISES DE PLANOS ===" . PHP_EOL;

// 1. Obter CSRF e Fazer Login como ADMIN
$respLoginGet = httpReq('GET', '/login', [], true);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $respLoginGet['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';

$respLoginPost = httpReq('POST', '/login', [
    'csrf_token' => $csrf,
    'email'      => 'teste@teste.com',
    'senha'      => 'teste123'
]);

if ($respLoginPost['code'] !== 200 || str_contains($respLoginPost['body'], 'name="email"')) {
    echo "Falha ao autenticar admin. HTTP: " . $respLoginPost['code'] . PHP_EOL;
    exit(1);
}
echo "✓ Login efetuado com sucesso." . PHP_EOL;

// 2. Acessar /analises-planos (Página 1 padrão - 15 itens)
$respP1 = httpReq('GET', '/analises-planos');
if ($respP1['code'] !== 200) {
    echo "Falha ao carregar /analises-planos. HTTP: " . $respP1['code'] . PHP_EOL;
    exit(1);
}

if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP1['body'], $m1)) {
    echo "✓ Página 1: {$m1[0]}" . PHP_EOL;
    if ($m1[1] !== '1') {
        echo "ERRO: Esperado início 1, obtido {$m1[1]}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo de paginação não encontrado na página 1" . PHP_EOL;
    exit(1);
}

if (str_contains($respP1['body'], 'paginacao-footer')) {
    echo "✓ Container .paginacao-footer renderizado na página 1." . PHP_EOL;
} else {
    echo "ERRO: .paginacao-footer ausente." . PHP_EOL;
    exit(1);
}

if (str_contains($respP1['body'], 'selectPorPagina')) {
    echo "✓ Seletor de registros por página presente." . PHP_EOL;
} else {
    echo "ERRO: selectPorPagina ausente." . PHP_EOL;
    exit(1);
}

// 3. Acessar com 5 por página para forçar múltiplas páginas: /analises-planos?por_pagina=5
$respP5 = httpReq('GET', '/analises-planos?por_pagina=5');
if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP5['body'], $m5)) {
    echo "✓ Por página 5: {$m5[0]}" . PHP_EOL;
    if ($m5[1] !== '1' || (int)$m5[2] > 5) {
        echo "ERRO: Inconsistência na paginação com 5 itens" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo não encontrado com por_pagina=5" . PHP_EOL;
    exit(1);
}

if (str_contains($respP5['body'], 'class="paginacao-nav"')) {
    echo "✓ Barra de paginação .paginacao-nav renderizada com múltiplas páginas." . PHP_EOL;
}

// 4. Acessar página 2: /analises-planos?por_pagina=5&pagina=2
$respP5Pag2 = httpReq('GET', '/analises-planos?por_pagina=5&pagina=2');
if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP5Pag2['body'], $m5p2)) {
    echo "✓ Página 2 com 5 itens: {$m5p2[0]}" . PHP_EOL;
    if ($m5p2[1] !== '6') {
        echo "ERRO: Esperado início 6 na página 2 com 5 itens, obtido {$m5p2[1]}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo não encontrado na página 2" . PHP_EOL;
    exit(1);
}

// 5. Testar busca com paginação: /analises-planos?busca=AM-RAP&por_pagina=5
$respBusca = httpReq('GET', '/analises-planos?busca=AM-RAP&por_pagina=5');
if (str_contains($respBusca['body'], 'filtrando por')) {
    echo "✓ Indicador de termo filtrado presente no rodapé da busca." . PHP_EOL;
} else {
    echo "ERRO: Termo filtrado ausente na resposta de busca." . PHP_EOL;
    exit(1);
}

echo "=== TODOS OS TESTES HTTP DE PAGINAÇÃO PASSARAM COM 100% DE SUCESSO! ===" . PHP_EOL;
