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

echo "=== TESTE HTTP REAL DE PAGINAÇÃO DE PROTOCOLOS ===" . PHP_EOL;

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

// 2. Acessar /protocolos (Página 1 padrão - 15 itens)
$respP1 = httpReq('GET', '/protocolos');
if ($respP1['code'] !== 200) {
    echo "Falha ao carregar /protocolos. HTTP: " . $respP1['code'] . PHP_EOL;
    exit(1);
}

if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP1['body'], $m1)) {
    echo "✓ Página 1: {$m1[0]}" . PHP_EOL;
    if ($m1[1] !== '1') {
        echo "ERRO: Esperado início 1, obtido {$m1[1]}" . PHP_EOL;
        exit(1);
    }
    if ((int)$m1[2] > 15) {
        echo "ERRO: Esperado no máximo 15 itens na página 1, obtido {$m1[2]}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo de paginação não encontrado na página 1" . PHP_EOL;
    exit(1);
}

if (strpos($respP1['body'], 'class="prot-pagination"') !== false) {
    echo "✓ Barra de paginação renderizada na página 1." . PHP_EOL;
} else {
    echo "Aviso: Menos de 1 página de registros." . PHP_EOL;
}

// 3. Acessar /protocolos?pagina=2 (Página 2)
$respP2 = httpReq('GET', '/protocolos?pagina=2');
if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP2['body'], $m2)) {
    echo "✓ Página 2: {$m2[0]}" . PHP_EOL;
    if ($m2[1] !== '16') {
        echo "ERRO: Esperado início 16 na página 2, obtido {$m2[1]}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo de paginação não encontrado na página 2" . PHP_EOL;
    exit(1);
}

// 4. Acessar com 25 por página: /protocolos?por_pagina=25
$respP25 = httpReq('GET', '/protocolos?por_pagina=25');
if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respP25['body'], $m25)) {
    echo "✓ Por página 25: {$m25[0]}" . PHP_EOL;
    if ($m25[1] !== '1' || (int)$m25[2] > 25) {
        echo "ERRO: Inconsistência na paginação com 25 itens" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ERRO: Resumo de paginação não encontrado com 25 por página" . PHP_EOL;
    exit(1);
}

// 5. Testar Aba rápida com paginação preservada
$respAba = httpReq('GET', '/protocolos?aba=CONCLUIDO');
if (preg_match('/Mostrando <strong>(.*?)<\/strong> a <strong>(.*?)<\/strong> de <strong>(.*?)<\/strong>/', $respAba['body'], $mAba)) {
    echo "✓ Aba CONCLUIDO: {$mAba[0]}" . PHP_EOL;
}

echo PHP_EOL . "SUCESSO TOTAL: PAGINAÇÃO WEB 100% OPERACIONAL E TESTADA VIA HTTP!" . PHP_EOL;
