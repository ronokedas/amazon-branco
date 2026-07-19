<?php
/**
 * Resolve o destinatario correto para e-mails de documentos.
 */

function resolverDestinatarioDocumento(PDO $pdo, array $documento, string $tabela): ?array
{
    if ($tabela === 'certificados_cht') {
        $email = trim((string)($documento['email_destinatario'] ?? ''));
        if ($email === '') {
            return null;
        }

        return [
            'cliente_nome' => $documento['nome_embarcacao'] ?? 'Destinatario',
            'cliente_email' => $email,
        ];
    }

    $nomeEmbarcacao = trim((string)($documento['nome_embarcacao'] ?? ''));
    if ($nomeEmbarcacao === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT c.nome AS cliente_nome, c.email AS cliente_email
        FROM embarcacoes e
        INNER JOIN clientes c ON (
            c.id = e.proprietario_id
            OR c.id = e.cliente_id
            OR c.id IN (
                SELECT ce.cliente_id
                FROM clientes_embarcacoes ce
                WHERE ce.embarcacao_id = e.id AND ce.status='ATIVO'
            )
        )
        WHERE e.nome = :emb_nome
          AND e.ativo = 1
          AND c.status = 'ATIVO'
          AND c.email IS NOT NULL
          AND c.email <> ''
        ORDER BY
          CASE
            WHEN c.id = e.proprietario_id THEN 1
            WHEN c.id = e.cliente_id THEN 2
            WHEN c.perfil = 'proprietario' THEN 3
            WHEN c.perfil = 'despachante' THEN 4
            WHEN c.perfil = 'armador' THEN 5
            ELSE 9
          END,
          c.nome
        LIMIT 1
    ");
    $stmt->execute([':emb_nome' => $nomeEmbarcacao]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    return $cliente ?: null;
}
