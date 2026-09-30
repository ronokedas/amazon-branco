<?php

/** Sincroniza vinculos sem apagar o historico e sem colisoes de chave unica. */
function sincronizarClienteEmbarcacoes(PDO $pdo, string $clienteId, array $embarcacaoIds, ?string $usuarioId): void
{
    $embarcacaoIds = array_values(array_unique(array_filter(array_map('trim', $embarcacaoIds))));

    // Sanitizar registros inativos pré-existentes para garantir que vinculo_ativo_chave seja NULL
    $pdo->prepare("
        UPDATE clientes_embarcacoes 
        SET vinculo_ativo_chave = NULL 
        WHERE cliente_id = :cliente AND status = 'INATIVO' AND vinculo_ativo_chave IS NOT NULL
    ")->execute([':cliente' => $clienteId]);

    $validos = [];
    if ($embarcacaoIds) {
        $stmtValida = $pdo->prepare('SELECT id FROM embarcacoes WHERE id = :id AND excluido_em IS NULL');
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
        $stmtDesv = $pdo->prepare("UPDATE clientes_embarcacoes SET status='INATIVO', vinculo_ativo_chave=NULL, desvinculado_em=NOW(), desvinculado_por=:usuario WHERE cliente_id=:cliente AND embarcacao_id=:embarcacao");
        $stmtDesvEmb = $pdo->prepare("UPDATE embarcacoes SET proprietario_id = NULL, cliente_id = NULL WHERE id = :embarcacao AND (proprietario_id = :cliente1 OR cliente_id = :cliente2)");
        foreach ($desativar as $embarcacaoId) {
            $stmtDesv->execute([':usuario' => $usuarioId, ':cliente' => $clienteId, ':embarcacao' => $embarcacaoId]);
            $stmtDesvEmb->execute([':cliente1' => $clienteId, ':cliente2' => $clienteId, ':embarcacao' => $embarcacaoId]);
        }
    }

    $ativar = array_diff($validos, array_keys($atuaisPorEmbarcacao));
    if ($ativar) {
        $stmtNomeCli = $pdo->prepare("SELECT nome FROM clientes WHERE id = :cliente LIMIT 1");
        $stmtNomeCli->execute([':cliente' => $clienteId]);
        $nomeCliente = $stmtNomeCli->fetchColumn() ?: null;

        $stmtCheckExiste = $pdo->prepare("
            SELECT id FROM clientes_embarcacoes 
            WHERE cliente_id = :cliente AND embarcacao_id = :embarcacao 
            ORDER BY vinculado_em DESC LIMIT 1
        ");
        $stmtUpdateAtivar = $pdo->prepare("
            UPDATE clientes_embarcacoes 
            SET status = 'ATIVO', vinculo_ativo_chave = concat(:cliente_chave, ':', :embarcacao_chave), 
                desvinculado_em = NULL, desvinculado_por = NULL, vinculado_em = NOW(), vinculado_por = :usuario 
            WHERE id = :id
        ");
        $stmtInsert = $pdo->prepare("
            INSERT INTO clientes_embarcacoes 
                (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em, vinculado_por) 
            VALUES 
                (UUID(), :cliente, :embarcacao, 'ATIVO', concat(:cliente_chave, ':', :embarcacao_chave), NOW(), :usuario)
        ");
        $stmtSyncEmb = $pdo->prepare("
            UPDATE embarcacoes 
            SET proprietario_id = :prop_id, cliente_id = :cli_id, proprietario = :nome_cliente, ativo = 1, excluido_em = NULL 
            WHERE id = :embarcacao
        ");

        foreach ($ativar as $embarcacaoId) {
            // Garantir que nenhum registro antigo ou inativo com essa mesma chave bloqueie a ativação
            $pdo->prepare("
                UPDATE clientes_embarcacoes 
                SET vinculo_ativo_chave = NULL 
                WHERE embarcacao_id = :emb AND vinculo_ativo_chave = concat(:cliente, ':', :emb2)
            ")->execute([':emb' => $embarcacaoId, ':cliente' => $clienteId, ':emb2' => $embarcacaoId]);

            $stmtCheckExiste->execute([':cliente' => $clienteId, ':embarcacao' => $embarcacaoId]);
            $existenteId = $stmtCheckExiste->fetchColumn();

            if ($existenteId) {
                $stmtUpdateAtivar->execute([
                    ':cliente_chave' => $clienteId,
                    ':embarcacao_chave' => $embarcacaoId,
                    ':usuario' => $usuarioId,
                    ':id' => $existenteId
                ]);
            } else {
                $stmtInsert->execute([
                    ':cliente' => $clienteId,
                    ':embarcacao' => $embarcacaoId,
                    ':cliente_chave' => $clienteId,
                    ':embarcacao_chave' => $embarcacaoId,
                    ':usuario' => $usuarioId
                ]);
            }

            $stmtSyncEmb->execute([
                ':prop_id' => $clienteId,
                ':cli_id' => $clienteId,
                ':nome_cliente' => $nomeCliente,
                ':embarcacao' => $embarcacaoId
            ]);
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
        $vinculoChave = $novoClienteId . ':' . $embarcacaoId;

        // Desativar vínculo desta embarcação se estiver ativa com outro cliente
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

        // Sanitizar qualquer registro pré-existente com essa mesma chave ativa para evitar erro 1062 de duplicidade
        $pdo->prepare("
            UPDATE clientes_embarcacoes 
            SET vinculo_ativo_chave = NULL 
            WHERE embarcacao_id = :embarcacao AND vinculo_ativo_chave = :chave
        ")->execute([
            ':embarcacao' => $embarcacaoId,
            ':chave' => $vinculoChave
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
            $stmtReativa = $pdo->prepare("
                UPDATE clientes_embarcacoes 
                SET status = 'ATIVO', vinculo_ativo_chave = :chave, 
                    desvinculado_em = NULL, desvinculado_por = NULL, vinculado_em = NOW(), vinculado_por = :usuario 
                WHERE id = :id
            ");
            $stmtReativa->execute([
                ':chave' => $vinculoChave,
                ':usuario' => $usuarioId,
                ':id' => $vinculo['id']
            ]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO clientes_embarcacoes 
                    (id, cliente_id, embarcacao_id, status, vinculo_ativo_chave, vinculado_em, vinculado_por) 
                VALUES 
                    (UUID(), :cliente, :embarcacao, 'ATIVO', :chave, NOW(), :usuario)
            ");
            $stmtIns->execute([
                ':cliente' => $novoClienteId,
                ':embarcacao' => $embarcacaoId,
                ':chave' => $vinculoChave,
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

