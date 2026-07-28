<?php
require_once __DIR__ . '/../includes/functions.php';

function assertValidacaoIntegridade(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'amazon_validacao_' . bin2hex(random_bytes(5));
if (!mkdir($raiz, 0700, true) && !is_dir($raiz)) {
    throw new RuntimeException('Nao foi possivel criar diretorio temporario.');
}
$arquivo = $raiz . DIRECTORY_SEPARATOR . 'documento.pdf';
file_put_contents($arquivo, '%PDF-1.4 documento de teste');
$hash = hash_file('sha256', $arquivo);

try {
    $base = [
        'status' => 'ASSINADO',
        'caminho_pdf_assinado' => 'documento.pdf',
        'hash_pdf_assinado' => $hash,
    ];
    $valido = avaliarIntegridadeAssinaturaPublica($base, $raiz);
    assertValidacaoIntegridade($valido['estado'] === 'valido' && $valido['http'] === 200, 'Arquivo integro nao foi validado.');

    $cancelado = avaliarIntegridadeAssinaturaPublica(array_merge($base, ['status' => 'CANCELADO']), $raiz);
    assertValidacaoIntegridade($cancelado['estado'] === 'cancelado' && $cancelado['http'] === 410, 'Cancelamento nao prevaleceu.');

    $divergente = avaliarIntegridadeAssinaturaPublica(array_merge($base, ['hash_pdf_assinado' => str_repeat('0', 64)]), $raiz);
    assertValidacaoIntegridade($divergente['estado'] === 'falha' && $divergente['http'] === 409, 'Hash divergente nao falhou.');

    $ausente = avaliarIntegridadeAssinaturaPublica(array_merge($base, ['caminho_pdf_assinado' => 'ausente.pdf']), $raiz);
    assertValidacaoIntegridade($ausente['estado'] === 'falha' && $ausente['http'] === 409, 'Arquivo ausente nao falhou.');

    echo "validacao_assinatura_integridade_test: OK\n";
} finally {
    if (is_file($arquivo)) unlink($arquivo);
    if (is_dir($raiz)) rmdir($raiz);
}
