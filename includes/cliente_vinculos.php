<?php

/** Sincroniza vinculos sem apagar o historico. */
function sincronizarClienteEmbarcacoes(PDO $pdo, string $clienteId, array $embarcacaoIds, ?string $usuarioId): void
{
    $embarcacaoIds = array_values(array_unique(array_filter(array_map('trim', $embarcacaoIds))));

    $validos = [];
    if ($embarcacaoIds) {
        $stmtValida = $pdo->prepare('SELECT id FROM embarcacoes WHERE id = :id AND ativo = 1');
        foreach ($embarcacaoIds as $id) {
            $stmtValida->execute([':id' => $id]);
            if ($stmtValida->fetchColumn()) $validos[] = $id;
        }
    }

    $stmtAtuais = $pdo->prepare("SELECT id, embarcacao_id FROM clientes_embarcacoes WHERE cliente_id = :cliente AND status = 'ATIVO' FOR UPDATE");
    $stmtAtuais->execute([':cliente' => $clienteId]);
    $atuais = $stmtAtuais->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    $atuaisPorEmbarcacao = array_flip($atuais);

    $desativar = array_diff(array_keys($atuaisPorEmbarcacao), $validos);
    if ($desativar) {
        $stmt = $pdo->prepare("UPDATE clientes_embarcacoes SET status='INATIVO', vinculo_ativo_chave=NULL, desvinculado_em=NOW(), desvinculado_por=:usuario WHERE cliente_id=:cliente AND embarcacao_id=:embarcacao AND status='ATIVO'");
        foreach ($desativar as $embarcacaoId) {
            $stmt->execute([':usuario' => $usuarioId, ':cliente' => $clienteId, ':embarcacao' => $embarcacaoId]);
        }
    }

    $ativar = array_diff($validos, array_keys($atuaisPorEmbarcacao));
    if ($ativar) {
        $stmt = $pdo->prepare("INSERT INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em, vinculado_por) VALUES (UUID(), :cliente, :embarcacao, 'ATIVO', concat(:cliente_chave, ':', :embarcacao_chave), NOW(), :usuario)");
        foreach ($ativar as $embarcacaoId) {
            $stmt->execute([':cliente' => $clienteId, ':embarcacao' => $embarcacaoId, ':cliente_chave'=>$clienteId, ':embarcacao_chave'=>$embarcacaoId, ':usuario' => $usuarioId]);
        }
    }
}

function clienteEmbarcacoesAtivasIds(PDO $pdo, string $clienteId): array
{
    $stmt = $pdo->prepare("SELECT embarcacao_id FROM clientes_embarcacoes WHERE cliente_id=:cliente AND status='ATIVO' ORDER BY vinculado_em");
    $stmt->execute([':cliente' => $clienteId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function sincronizarLoginPortalCliente(PDO $pdo, string $clienteId): void
{
    $stmt=$pdo->prepare("UPDATE cliente_portal_acessos a INNER JOIN clientes c ON c.id=a.cliente_id SET a.login=lower(trim(c.email)) WHERE a.cliente_id=:id AND c.email IS NOT NULL AND trim(c.email)<>''");
    $stmt->execute([':id'=>$clienteId]);
}
