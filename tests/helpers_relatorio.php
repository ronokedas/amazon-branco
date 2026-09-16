<?php
/**
 * Helper para carregar o módulo modular de vistorias durante testes automatizados,
 * resolvendo inclusões de componentes e scripts do frontend.
 */
function carregarRelatorioParaTeste(): string
{
    $base = __DIR__ . '/../modules/vistorias';
    $conteudo = (string)file_get_contents($base . '/relatorio.php');
    $conteudo = (string)preg_replace_callback('/(?:require|include)(?:_once)?\s+__DIR__\s*\.\s*[\'"](\/components\/[^\'"]+)[\'"]\s*;/i', function($m) use ($base) {
        $arquivo = $base . $m[1];
        return file_exists($arquivo) ? (string)file_get_contents($arquivo) : '';
    }, $conteudo);

    if (file_exists($base . '/js/relatorio.js')) {
        $conteudo .= "\n" . file_get_contents($base . '/js/relatorio.js');
    }

    return $conteudo;
}
