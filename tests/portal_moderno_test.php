<?php
declare(strict_types=1);

function portalAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$header = file_get_contents($root . '/includes/portal_header.php');
$dashboard = file_get_contents($root . '/modules/portal/index.php');
$documentos = file_get_contents($root . '/modules/portal/documentos.php');
$analises = file_get_contents($root . '/modules/portal/analises_planos.php');
$embarcacoes = file_get_contents($root . '/modules/portal/embarcacoes.php');
$router = file_get_contents($root . '/index.php');
$css = file_get_contents($root . '/assets/css/portal-modern.css');
$js = file_get_contents($root . '/assets/js/portal-modern.js');

portalAssert(str_contains($header, 'portal-modern.css'), 'O design system moderno não está carregado.');
portalAssert(str_contains($header, 'aria-controls="portal-navigation"'), 'O menu móvel não possui associação acessível.');
portalAssert(str_contains($header, '/portal/embarcacoes'), 'A navegação não aponta para a rota de embarcações.');
portalAssert(str_contains($router, "'portal/embarcacoes'"), 'A rota de embarcações não foi registrada.');
portalAssert(str_contains($dashboard, 'Vencido há'), 'O dashboard não trata documentos vencidos.');
portalAssert(str_contains($documentos, 'portal-table-actions'), 'As ações de documentos não usam o componente responsivo.');
portalAssert(str_contains($analises, 'data-portal-upload'), 'O envio progressivo de revisão não está habilitado.');
portalAssert(str_contains($analises, 'Tamanho máximo por arquivo: 50 MB'), 'O limite real de upload não está comunicado.');
portalAssert(str_contains($analises, 'Seus arquivos anteriores serão preservados'), 'A preservação de revisões não está destacada.');
portalAssert(str_contains($embarcacoes, 'clientePortalEmbarcacoes'), 'A página não restringe embarcações ao cliente autenticado.');
portalAssert(str_contains($css, '--p-ink:#102f29'), 'Os tokens do portal não foram definidos.');
portalAssert(str_contains($css, '@media(max-width:600px)'), 'O layout móvel do portal não foi definido.');
portalAssert(str_contains($js, 'Enviando revisão...'), 'A prevenção visual de envio duplicado não foi implementada.');

echo "Portal moderno: OK\n";
