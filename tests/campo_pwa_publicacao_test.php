<?php

function assertCampoPwa(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$raiz = dirname(__DIR__);
$html = file_get_contents($raiz . '/pwa-campo/index.html');
$sync = file_get_contents($raiz . '/pwa-campo/src/sync.js');
$sw = file_get_contents($raiz . '/pwa-campo/public/sw.js');
$api = file_get_contents($raiz . '/modules/campo/api.php');

assertCampoPwa($html !== false && str_contains($html, 'src="/src/main.jsx"'), 'O build do Campo ainda aponta para a PWA legada.');
assertCampoPwa(!str_contains($html, '/src/src/main.tsx'), 'A entrada duplicada da PWA ainda está publicada.');
assertCampoPwa($sync !== false && str_contains($sync, "dados?.status !== 'AGUARDANDO_APROVACAO'"), 'A finalização ainda pode ser tratada como sucesso sem confirmação da API.');
assertCampoPwa($sw !== false && str_contains($sw, "amazon-campo-v13"), 'O cache do service worker não foi atualizado.');
assertCampoPwa($sw !== false && str_contains($sw, '/campo/brand-mark.svg'), 'Os ativos de marca não estão no shell offline do Campo.');
assertCampoPwa(is_file($raiz . '/pwa-campo/public/brand-mark.svg'), 'O ícone institucional da marca não foi incluído na PWA.');
assertCampoPwa(is_file($raiz . '/pwa-campo/public/brand-horizontal.svg'), 'A marca horizontal não foi incluída na PWA.');
assertCampoPwa(substr_count((string)$api, "campoRegistrarAuditoria('campo_vistoria_enviada'") === 2, 'A auditoria de finalização não cobre todos os fluxos do Campo.');

echo "campo_pwa_publicacao_test: OK\n";
