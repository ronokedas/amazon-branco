<?php

require_once __DIR__ . '/../includes/functions.php';

function testarIpCliente(string $descricao, array $servidor, string $esperado, ?string $proxies = null): void
{
    $_SERVER = $servidor;
    if ($proxies === null) {
        putenv('TRUSTED_PROXY_CIDRS');
    } else {
        putenv('TRUSTED_PROXY_CIDRS=' . $proxies);
    }

    $obtido = obterIpCliente();
    if ($obtido !== $esperado) {
        throw new RuntimeException($descricao . ': esperado ' . $esperado . ', obtido ' . $obtido . '.');
    }
}

if (!ipPertenceAoBloco('104.22.10.182', '104.16.0.0/13')) {
    throw new RuntimeException('Falha ao reconhecer IPv4 dentro do CIDR da Cloudflare.');
}
if (!ipPertenceAoBloco('2606:4700:3030::6815:1001', '2606:4700::/32')) {
    throw new RuntimeException('Falha ao reconhecer IPv6 dentro do CIDR da Cloudflare.');
}
if (ipPertenceAoBloco('203.0.113.10', '104.16.0.0/13')) {
    throw new RuntimeException('IPv4 externo foi reconhecido como pertencente ao CIDR da Cloudflare.');
}

testarIpCliente(
    'Conexao direta nao pode falsificar CF-Connecting-IP',
    ['REMOTE_ADDR' => '203.0.113.10', 'HTTP_CF_CONNECTING_IP' => '198.51.100.22'],
    '203.0.113.10'
);
testarIpCliente(
    'Cloudflare deve informar o IP publico do assinante',
    ['REMOTE_ADDR' => '104.22.10.182', 'HTTP_CF_CONNECTING_IP' => '198.51.100.22'],
    '198.51.100.22'
);
testarIpCliente(
    'Cloudflare IPv6 deve informar o IP publico do assinante',
    ['REMOTE_ADDR' => '2606:4700:3030::6815:1001', 'HTTP_CF_CONNECTING_IP' => '2001:db8::44'],
    '2001:db8::44'
);
testarIpCliente(
    'Proxy configurado pode usar X-Forwarded-For como fallback',
    ['REMOTE_ADDR' => '10.20.30.40', 'HTTP_X_FORWARDED_FOR' => '198.51.100.80, 10.20.30.40'],
    '198.51.100.80',
    '10.0.0.0/8'
);
testarIpCliente(
    'Cabecalho invalido deve manter o IP do proxy',
    ['REMOTE_ADDR' => '104.22.10.182', 'HTTP_CF_CONNECTING_IP' => 'ip-invalido'],
    '104.22.10.182'
);

putenv('TRUSTED_PROXY_CIDRS');
echo "OK: IP publico e proxies confiaveis validados para IPv4 e IPv6.\n";
