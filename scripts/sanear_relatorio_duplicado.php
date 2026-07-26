<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$opcoes = getopt('', ['id::', 'dry-run', 'apply', 'execute']);
$id = trim((string)($opcoes['id'] ?? '7a175152-999a-418d-bd14-30d33881910d'));
$executar = array_key_exists('apply', $opcoes) || array_key_exists('execute', $opcoes);

if (!preg_match('/^[a-f0-9-]{36}$/i', $id)) {
    fwrite(STDERR, "ID invalido.\n");
    exit(2);
}

function tabelaExisteSaneamento(PDO $pdo, string $tabela): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:tabela');
    $stmt->execute([':tabela' => $tabela]);
    return (bool)$stmt->fetchColumn();
}

function colunaExisteSaneamento(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns
        WHERE table_schema=DATABASE() AND table_name=:tabela AND column_name=:coluna');
    $stmt->execute([':tabela' => $tabela, ':coluna' => $coluna]);
    return (bool)$stmt->fetchColumn();
}

function listarCertificadosASSaneamento(PDO $pdo): array
{
    $mapa = [
        'CSN' => 'certificados_csn',
        'CNBL' => 'certificados_cnbl',
        'CNARQ' => 'certificados_cnarq',
    ];
    $resultado = [];
    foreach ($mapa as $tipo => $tabela) {
        if (!tabelaExisteSaneamento($pdo, $tabela)) continue;
        $stmt = $pdo->query("SELECT '{$tipo}' tipo,c.id,c.numero,c.status,c.ativo,v.id relatorio_id,v.numero relatorio_numero
            FROM {$tabela} c
            JOIN vistorias v ON v.id=c.vistoria_id
            WHERE c.status<>'cancelado' AND c.ativo=1
              AND EXISTS (
                SELECT 1 FROM vistoria_exigencias ve
                WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1
                  AND ve.conforme='nao' AND ve.status_item<>'cumprida'
              )");
        $resultado = array_merge($resultado, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    return $resultado;
}

try {
    $pdo->beginTransaction();

    // A ramificacao conhecida e ficticia pode ser removida por esta propria
    // rotina. Qualquer outra ambiguidade continua sendo motivo de rollback.
    $stmtRamificacoes = $pdo->prepare("SELECT relatorio_anterior_id,COUNT(*) quantidade
        FROM vistorias
        WHERE relatorio_anterior_id IS NOT NULL AND status<>'CANCELADA'
          AND id<>:duplicado
        GROUP BY relatorio_anterior_id HAVING COUNT(*)>1");
    $stmtRamificacoes->execute([':duplicado' => $id]);
    $ramificacoes = $stmtRamificacoes->fetchAll(PDO::FETCH_ASSOC);
    if ($ramificacoes) {
        throw new RuntimeException('Existem ramificacoes ativas ambiguas. Execute primeiro a migration 094.');
    }

    $stmt = $pdo->prepare("SELECT v.id,v.numero,v.status,v.agendamento_id
        FROM vistorias v
        WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')
          AND EXISTS (
            SELECT 1 FROM vistoria_exigencias ve
            WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1
              AND ve.conforme='nao' AND ve.status_item<>'cumprida'
          )
        ORDER BY v.numero FOR UPDATE");
    $stmt->execute();
    $relatoriosReclassificar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $certificadosCancelar = listarCertificadosASSaneamento($pdo);

    $stmtDuplicado = $pdo->prepare('SELECT * FROM vistorias WHERE id=:id FOR UPDATE');
    $stmtDuplicado->execute([':id' => $id]);
    $relatorioDuplicado = $stmtDuplicado->fetch(PDO::FETCH_ASSOC) ?: null;
    $duplicadoResumo = null;

    if ($relatorioDuplicado) {
        $vigente = obterRelatorioVigenteCadeia($pdo, $id);
        if (!$vigente || (string)$vigente['id'] === $id) {
            throw new RuntimeException('A exclusao foi recusada porque o relatorio ficticio ainda e o vigente da cadeia.');
        }
        $stmt = $pdo->prepare('SELECT id FROM vistoria_retornos
            WHERE relatorio_resultado_id=:resultado OR relatorio_origem_id=:origem LIMIT 1 FOR UPDATE');
        $stmt->execute([':resultado' => $id, ':origem' => $id]);
        if ($stmt->fetchColumn()) {
            throw new RuntimeException('A exclusao foi recusada porque o relatorio ficticio esta oficialmente ligado a um retorno A/S.');
        }
        $duplicadoResumo = [
            'excluir_relatorio' => $relatorioDuplicado['numero'] ?: $id,
            'preservar_vigente' => $vigente['numero'] ?: $vigente['id'],
            'agendamento_orfao' => $relatorioDuplicado['agendamento_id'] ?: null,
        ];
    }

    $resumo = [
        'modo' => $executar ? 'apply' : 'dry-run',
        'duplicado_ficticio' => $duplicadoResumo,
        'relatorios_reclassificar' => $relatoriosReclassificar,
        'certificados_cancelar' => $certificadosCancelar,
    ];
    echo json_encode($resumo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if (!$executar) {
        $pdo->rollBack();
        echo "SIMULACAO: nenhuma alteracao foi realizada. Use --apply para confirmar.\n";
        exit(0);
    }

    if (!colunaExisteSaneamento($pdo, 'vistoria_retornos', 'vistoriador_origem_id')) {
        throw new RuntimeException('A migration 095 deve ser aplicada antes do modo --apply.');
    }

    if ($relatorioDuplicado) {
        $agendamentoId = trim((string)($relatorioDuplicado['agendamento_id'] ?? ''));
        foreach (['assinatura_convites','documento_assinaturas','documento_aprovacoes','documento_artefatos'] as $tabela) {
            if (!tabelaExisteSaneamento($pdo, $tabela)) continue;
            $pdo->prepare("DELETE FROM {$tabela} WHERE documento_tipo='RELATORIO' AND documento_id=:id")
                ->execute([':id' => $id]);
        }
        $stmt = $pdo->prepare('DELETE FROM vistorias WHERE id=:id');
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('O relatorio ficticio mudou durante o saneamento.');

        if ($agendamentoId !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM vistorias WHERE agendamento_id=:id');
            $stmt->execute([':id' => $agendamentoId]);
            $outrosRelatorios = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM vistoria_retornos WHERE agendamento_id=:id');
            $stmt->execute([':id' => $agendamentoId]);
            if ($outrosRelatorios === 0 && (int)$stmt->fetchColumn() === 0) {
                $pdo->prepare('DELETE FROM ordens_servico WHERE agendamento_id=:id')->execute([':id' => $agendamentoId]);
                $pdo->prepare('DELETE FROM agendamentos WHERE id=:id')->execute([':id' => $agendamentoId]);
            }
        }
    }

    foreach ($relatoriosReclassificar as $relatorio) {
        $stmt = $pdo->prepare("UPDATE vistorias SET status='RETORNO_AS'
            WHERE id=:id AND status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')");
        $stmt->execute([':id' => $relatorio['id']]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Um relatorio mudou durante a reclassificacao.');
        criarPendenciaRetornoAS($pdo, (string)$relatorio['id'], null);
    }

    $mapaCertificados = ['CSN'=>'certificados_csn','CNBL'=>'certificados_cnbl','CNARQ'=>'certificados_cnarq'];
    foreach ($certificadosCancelar as $certificado) {
        $tabela = $mapaCertificados[$certificado['tipo']];
        $stmt = $pdo->prepare("UPDATE {$tabela} SET status='cancelado',ativo=0
            WHERE id=:id AND status<>'cancelado' AND ativo=1");
        $stmt->execute([':id' => $certificado['id']]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Um certificado mudou durante o saneamento.');
        $pdo->prepare("UPDATE documento_aprovacoes
            SET status='CANCELADO',
                erro_processamento=COALESCE(erro_processamento,'Cancelado: relatorio com A/S pendente.')
            WHERE documento_tipo=:tipo AND documento_id=:id AND status='APROVADO'")
            ->execute([':tipo' => $certificado['tipo'], ':id' => $certificado['id']]);
    }

    $pdo->commit();
    echo "Saneamento concluido com auditoria preservada.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Saneamento abortado: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
