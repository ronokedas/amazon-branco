<?php

interface PdfSignatureProviderInterface
{
    /**
     * Recebe um PDF visualmente finalizado e devolve o artefato assinado.
     * A primeira entrega usa AuditOnlyPdfSignatureProvider. O adaptador A1
     * sera implementado sem alterar o fluxo de aprovacao.
     */
    public function sign(string $inputPath, string $outputPath, array $context): array;
}

final class AuditOnlyPdfSignatureProvider implements PdfSignatureProviderInterface
{
    public function sign(string $inputPath, string $outputPath, array $context): array
    {
        if (!copy($inputPath, $outputPath)) {
            throw new RuntimeException('Nao foi possivel finalizar o PDF auditavel.');
        }

        return [
            'standard' => 'AUDIT_ONLY',
            'pades_status' => 'NAO_APLICADO',
            'provider' => 'internal-audit',
            'certificate' => null,
        ];
    }
}

final class IcpBrasilA1PdfSignatureProvider implements PdfSignatureProviderInterface
{
    public function sign(string $inputPath, string $outputPath, array $context): array
    {
        throw new LogicException(
            'O provedor ICP-Brasil A1 esta preparado para integracao, mas ainda nao foi configurado.'
        );
    }
}
