<?php
/**
 * HELPERS: GESTÃO DE MÚLTIPLOS E-MAILS DE CLIENTES / PROPRIETÁRIOS
 * 
 * Permite cadastrar e gerenciar múltiplos e-mails por cliente (Despachante, Locatário, Projetista, etc.)
 * e obter todos os destinatários elegíveis para alertas e notificações da frota.
 */

if (!function_exists('papelEmailLabel')) {
    function papelEmailLabel(string $papel): string {
        return match (strtoupper($papel)) {
            'DESPACHANTE' => 'Despachante Marítimo',
            'LOCATARIO'   => 'Locatário / Afretador',
            'PROJETISTA'  => 'Projetista / Eng. Naval',
            'FINANCEIRO'  => 'Financeiro / Cobrança',
            'OPERACIONAL' => 'Comandante / Operacional',
            default       => 'Outro Contato',
        };
    }
}

/**
 * Retorna os e-mails adicionais cadastrados para um cliente
 */
function obterEmailsCliente(PDO $pdo, string $clienteId): array
{
    if (empty($clienteId)) {
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT id, cliente_id, email, papel, nome_contato, recebe_alertas, criado_em
            FROM cliente_emails
            WHERE cliente_id = :cid
            ORDER BY criado_em ASC
        ");
        $stmt->execute([':cid' => $clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Erro ao obter emails do cliente: ' . $e->getMessage());
        return [];
    }
}

/**
 * Salva a lista de e-mails adicionais de um cliente (sincronização completa)
 */
function salvarEmailsCliente(PDO $pdo, string $clienteId, array $emails): bool
{
    if (empty($clienteId)) {
        return false;
    }

    $emailsValidos = [];
    $vistos = [];

    foreach ($emails as $item) {
        $email = strtolower(trim((string)($item['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        if (isset($vistos[$email])) {
            continue;
        }
        $vistos[$email] = true;

        $papel = strtoupper(trim((string)($item['papel'] ?? 'OUTRO')));
        $papeisPermitidos = ['DESPACHANTE', 'LOCATARIO', 'PROJETISTA', 'FINANCEIRO', 'OPERACIONAL', 'OUTRO'];
        if (!in_array($papel, $papeisPermitidos, true)) {
            $papel = 'OUTRO';
        }

        $nomeContato = trim((string)($item['nome_contato'] ?? ''));
        $recebeAlertas = !empty($item['recebe_alertas']) ? 1 : 0;

        $emailsValidos[] = [
            'id'             => !empty($item['id']) ? trim($item['id']) : (function_exists('gerarUUID') ? gerarUUID() : bin2hex(random_bytes(16))),
            'email'          => $email,
            'papel'          => $papel,
            'nome_contato'   => $nomeContato !== '' ? $nomeContato : null,
            'recebe_alertas' => $recebeAlertas,
        ];
    }

    // Se estiver vazio, remove todos os emails adicionais
    if (empty($emailsValidos)) {
        $stmt = $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = :cid");
        $stmt->execute([':cid' => $clienteId]);
        return true;
    }

    // Deletar emails que não estão mais na lista
    $idsPreservar = array_column($emailsValidos, 'id');
    $inClause = implode(',', array_fill(0, count($idsPreservar), '?'));
    $paramsDelete = array_merge([$clienteId], $idsPreservar);
    $stmtDel = $pdo->prepare("DELETE FROM cliente_emails WHERE cliente_id = ? AND id NOT IN ($inClause)");
    $stmtDel->execute($paramsDelete);

    // Inserir ou atualizar
    $stmtUpsert = $pdo->prepare("
        INSERT INTO cliente_emails (id, cliente_id, email, papel, nome_contato, recebe_alertas)
        VALUES (:id, :cid, :email, :papel, :nome_contato, :recebe_alertas)
        ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            papel = VALUES(papel),
            nome_contato = VALUES(nome_contato),
            recebe_alertas = VALUES(recebe_alertas)
    ");

    foreach ($emailsValidos as $ev) {
        $stmtUpsert->execute([
            ':id'             => $ev['id'],
            ':cid'            => $clienteId,
            ':email'          => $ev['email'],
            ':papel'          => $ev['papel'],
            ':nome_contato'   => $ev['nome_contato'],
            ':recebe_alertas' => $ev['recebe_alertas'],
        ]);
    }

    return true;
}

/**
 * Retorna todos os destinatários notificáveis de um cliente (E-mail principal + adicionais com recebe_alertas = 1)
 */
function obterDestinatariosNotificaveisCliente(PDO $pdo, string $clienteId): array
{
    if (empty($clienteId)) {
        return [];
    }

    $destinatarios = [];
    $vistos = [];

    // 1. E-mail Principal do Cliente
    try {
        $stmtCli = $pdo->prepare("SELECT id, nome, email, perfil FROM clientes WHERE id = :cid LIMIT 1");
        $stmtCli->execute([':cid' => $clienteId]);
        $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if ($cli && !empty($cli['email']) && filter_var(trim($cli['email']), FILTER_VALIDATE_EMAIL)) {
            $emailNorm = strtolower(trim($cli['email']));
            $destinatarios[] = [
                'email'          => $emailNorm,
                'nome'           => $cli['nome'],
                'papel'          => strtoupper($cli['perfil'] ?? 'PROPRIETARIO'),
                'papel_rotulo'   => 'E-mail Principal (' . ucfirst($cli['perfil'] ?? 'Proprietário') . ')',
                'nome_contato'   => $cli['nome'],
                'e_principal'    => true,
            ];
            $vistos[$emailNorm] = true;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar email principal do cliente: ' . $e->getMessage());
    }

    // 2. E-mails Adicionais com recebe_alertas = 1
    try {
        $stmtSec = $pdo->prepare("
            SELECT email, papel, nome_contato
            FROM cliente_emails
            WHERE cliente_id = :cid AND recebe_alertas = 1
            ORDER BY criado_em ASC
        ");
        $stmtSec->execute([':cid' => $clienteId]);
        $secundarios = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

        foreach ($secundarios as $s) {
            $emailNorm = strtolower(trim($s['email']));
            if (!filter_var($emailNorm, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (isset($vistos[$emailNorm])) {
                continue;
            }
            $vistos[$emailNorm] = true;

            $destinatarios[] = [
                'email'        => $emailNorm,
                'nome'         => !empty($s['nome_contato']) ? $s['nome_contato'] : papelEmailLabel($s['papel']),
                'papel'        => $s['papel'],
                'papel_rotulo' => papelEmailLabel($s['papel']),
                'nome_contato' => $s['nome_contato'],
                'e_principal'  => false,
            ];
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar emails secundarios do cliente: ' . $e->getMessage());
    }

    return $destinatarios;
}

/**
 * Retorna todos os destinatários notificáveis vinculados a uma embarcação
 * Busca por embarcacoes.proprietario_id, embarcacoes.cliente_id e clientes_embarcacoes
 */
function obterDestinatariosNotificaveisEmbarcacao(PDO $pdo, string $embarcacaoId): array
{
    if (empty($embarcacaoId)) {
        return [];
    }

    $clientesIds = [];

    try {
        // Proprietario e cliente direto na embarcação
        $stmtEmb = $pdo->prepare("SELECT proprietario_id, cliente_id FROM embarcacoes WHERE id = :eid LIMIT 1");
        $stmtEmb->execute([':eid' => $embarcacaoId]);
        $emb = $stmtEmb->fetch(PDO::FETCH_ASSOC);
        if ($emb) {
            if (!empty($emb['proprietario_id'])) {
                $clientesIds[] = $emb['proprietario_id'];
            }
            if (!empty($emb['cliente_id'])) {
                $clientesIds[] = $emb['cliente_id'];
            }
        }

        // Vínculos em clientes_embarcacoes
        $stmtVinc = $pdo->prepare("
            SELECT cliente_id 
            FROM clientes_embarcacoes 
            WHERE embarcacao_id = :eid AND status = 'ATIVO'
        ");
        $stmtVinc->execute([':eid' => $embarcacaoId]);
        $vincs = $stmtVinc->fetchAll(PDO::FETCH_COLUMN);
        foreach ($vincs as $cid) {
            if (!empty($cid)) {
                $clientesIds[] = $cid;
            }
        }
    } catch (Throwable $e) {
        error_log('Erro ao mapear clientes da embarcacao: ' . $e->getMessage());
    }

    $clientesIds = array_values(array_unique(array_filter($clientesIds)));

    $todosDestinatarios = [];
    $emailsVistos = [];

    foreach ($clientesIds as $cid) {
        $destCli = obterDestinatariosNotificaveisCliente($pdo, $cid);
        foreach ($destCli as $d) {
            $emailNorm = strtolower(trim($d['email']));
            if (!isset($emailsVistos[$emailNorm])) {
                $emailsVistos[$emailNorm] = true;
                $todosDestinatarios[] = $d;
            }
        }
    }

    return $todosDestinatarios;
}
