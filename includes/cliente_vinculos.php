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
        $stmtDesvEmb = $pdo->prepare("UPDATE embarcacoes SET proprietario_id = NULL, cliente_id = NULL WHERE id = :embarcacao AND (proprietario_id = :cliente1 OR cliente_id = :cliente2)");
        foreach ($desativar as $embarcacaoId) {
            $stmt->execute([':usuario' => $usuarioId, ':cliente' => $clienteId, ':embarcacao' => $embarcacaoId]);
            $stmtDesvEmb->execute([':cliente1' => $clienteId, ':cliente2' => $clienteId, ':embarcacao' => $embarcacaoId]);
        }
    }

    $ativar = array_diff($validos, array_keys($atuaisPorEmbarcacao));
    if ($ativar) {
        $stmtNomeCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = :cliente LIMIT 1");
        $stmtNomeCli->execute([':cliente' => $clienteId]);
        $nomeCliente = $stmtNomeCli->fetchColumn() ?: null;

        $stmt = $pdo->prepare("INSERT INTO clientes_embarcacoes (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em, vinculado_por) VALUES (UUID(), :cliente, :embarcacao, 'ATIVO', concat(:cliente_chave, ':', :embarcacao_chave), NOW(), :usuario)");
        $stmtSyncEmb = $pdo->prepare("UPDATE embarcacoes SET proprietario_id = :prop_id, cliente_id = :cli_id, proprietario = :nome_cliente WHERE id = :embarcacao");
        foreach ($ativar as $embarcacaoId) {
            $stmt->execute([':cliente' => $clienteId, ':embarcacao' => $embarcacaoId, ':cliente_chave'=>$clienteId, ':embarcacao_chave'=>$embarcacaoId, ':usuario' => $usuarioId]);
            $stmtSyncEmb->execute([':prop_id' => $clienteId, ':cli_id' => $clienteId, ':nome_cliente' => $nomeCliente, ':embarcacao' => $embarcacaoId]);
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

/**
 * Vincula uma única embarcação a um cliente sem desvincular as demais embarcações que o cliente já possui.
 * Utilizado ao cadastrar ou editar uma embarcação individualmente no módulo de Embarcações.
 */
function vincularEmbarcacaoAoCliente(PDO $pdo, string $embarcacaoId, ?string $novoClienteId, ?string $usuarioId): void
{
    $embarcacaoId = trim($embarcacaoId);
    $novoClienteId = $novoClienteId ? trim($novoClienteId) : null;
    if ($embarcacaoId === '') {
        return;
    }

    if (!empty($novoClienteId)) {
        // Desativar vínculo desta embarcação apenas se estiver com outro cliente
        $stmtDesvOutros = $pdo->prepare("
            UPDATE clientes_embarcacoes 
            SET status = 'INATIVO', vinculo_ativo_chave = NULL, desvinculado_em = NOW(), desvinculado_por = :usuario 
            WHERE embarcacao_id = :embarcacao AND cliente_id != :novo_cliente AND status = 'ATIVO'
        ");
        $stmtDesvOutros->execute([
            ':usuario' => $usuarioId,
            ':embarcacao' => $embarcacaoId,
            ':novo_cliente' => $novoClienteId
        ]);

        // Verificar se já existe registro de vínculo para este cliente e embarcação
        $stmtExiste = $pdo->prepare("
            SELECT id, status FROM clientes_embarcacoes 
            WHERE cliente_id = :cliente AND embarcacao_id = :embarcacao 
            ORDER BY vinculado_em DESC LIMIT 1
        ");
        $stmtExiste->execute([':cliente' => $novoClienteId, ':embarcacao' => $embarcacaoId]);
        $vinculo = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        if ($vinculo) {
            if ($vinculo['status'] !== 'ATIVO') {
                $stmtReativa = $pdo->prepare("
                    UPDATE clientes_embarcacoes 
                    SET status = 'ATIVO', vinculo_ativo_chave = concat(:cliente, ':', :embarcacao), 
                        desvinculado_em = NULL, desvinculado_por = NULL, vinculado_em = NOW(), vinculado_por = :usuario 
                    WHERE id = :id
                ");
                $stmtReativa->execute([
                    ':cliente' => $novoClienteId,
                    ':embarcacao' => $embarcacaoId,
                    ':usuario' => $usuarioId,
                    ':id' => $vinculo['id']
                ]);
            }
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO clientes_embarcacoes 
                    (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em, vinculado_por) 
                VALUES 
                    (UUID(), :cliente, :embarcacao, 'ATIVO', concat(:cliente, ':', :embarcacao), NOW(), :usuario)
            ");
            $stmtIns->execute([
                ':cliente' => $novoClienteId,
                ':embarcacao' => $embarcacaoId,
                ':usuario' => $usuarioId
            ]);
        }

        // Sincronizar dados do proprietário na tabela embarcacoes
        $stmtNomeCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = :cliente LIMIT 1");
        $stmtNomeCli->execute([':cliente' => $novoClienteId]);
        $nomeCliente = $stmtNomeCli->fetchColumn() ?: null;

        $stmtUpdEmb = $pdo->prepare("
            UPDATE embarcacoes 
            SET proprietario_id = :prop_id, cliente_id = :cli_id, proprietario = :nome_cli 
            WHERE id = :embarcacao
        ");
        $stmtUpdEmb->execute([
            ':prop_id' => $novoClienteId,
            ':cli_id' => $novoClienteId,
            ':nome_cli' => $nomeCliente,
            ':embarcacao' => $embarcacaoId
        ]);
    } else {
        // Proprietário foi removido desta embarcação
        $stmtDesv = $pdo->prepare("
            UPDATE clientes_embarcacoes 
            SET status = 'INATIVO', vinculo_ativo_chave = NULL, desvinculado_em = NOW(), desvinculado_por = :usuario 
            WHERE embarcacao_id = :embarcacao AND status = 'ATIVO'
        ");
        $stmtDesv->execute([
            ':usuario' => $usuarioId,
            ':embarcacao' => $embarcacaoId
        ]);

        $stmtLimpaEmb = $pdo->prepare("
            UPDATE embarcacoes 
            SET proprietario_id = NULL, cliente_id = NULL, proprietario = NULL 
            WHERE id = :embarcacao
        ");
        $stmtLimpaEmb->execute([':embarcacao' => $embarcacaoId]);
    }
}

