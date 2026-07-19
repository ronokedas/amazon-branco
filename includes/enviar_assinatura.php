<?php
/**
 * Compatibilidade para acoes antigas de certificados.
 * O fluxo publico /assinar/{token} permanece exclusivo das propostas.
 */
function enviarAssinaturaEmail(PDO $pdo, string $certificado_id, string $tabela, string $tipo_label): array
{
    return [
        'success' => false,
        'message' => 'O fluxo publico de assinatura de certificados foi desativado. Use “Aprovar e assinar” como administrador.',
    ];
}
