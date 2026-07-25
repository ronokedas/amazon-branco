<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$opcoes = getopt('', ['id::', 'execute']);
$id = trim((string)($opcoes['id'] ?? '7a175152-999a-418d-bd14-30d33881910d'));
$executar = array_key_exists('execute', $opcoes);

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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM vistorias WHERE id=:id FOR UPDATE');
    $stmt->execute([':id' => $id]);
    $relatorio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$relatorio) {
        throw new RuntimeException("Relatorio {$id} nao encontrado.");
    }

    $vigente = obterRelatorioVigenteCadeia($pdo, $id);
    if (!$vigente || (string)$vigente['id'] === $id) {
        throw new RuntimeException('A exclusao foi recusada porque este relatorio ainda e o vigente da cadeia.');
    }

    $stmt = $pdo->prepare('SELECT id FROM vistoria_retornos WHERE relatorio_resultado_id=:resultado OR relatorio_origem_id=:origem LIMIT 1 FOR UPDATE');
    $stmt->execute([':resultado' => $id, ':origem' => $id]);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException('A exclusao foi recusada porque o relatorio esta oficialmente ligado a um retorno A/S.');
    }

    $stmt = $pdo->prepare("SELECT id FROM vistorias WHERE relatorio_anterior_id=:id AND status<>'CANCELADA' LIMIT 1 FOR UPDATE");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException('A exclusao foi recusada porque o relatorio possui descendente ativo.');
    }

    $agendamentoId = trim((string)($relatorio['agendamento_id'] ?? ''));
    $resumo = [
        'excluir_relatorio' => $relatorio['numero'] ?: $id,
        'preservar_vigente' => $vigente['numero'] ?: $vigente['id'],
        'agendamento_orfao' => $agendamentoId ?: null,
    ];
    echo json_encode($resumo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

    if (!$executar) {
        $pdo->rollBack();
        echo "SIMULACAO: nenhuma alteracao foi realizada. Use --execute para confirmar.\n";
        exit(0);
    }

    $tabelasDocumento = [
        'assinatura_convites',
        'documento_assinaturas',
        'documento_aprovacoes',
        'documento_artefatos',
    ];
    foreach ($tabelasDocumento as $tabela) {
        if (!tabelaExisteSaneamento($pdo, $tabela)) continue;
        $stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE documento_tipo='RELATORIO' AND documento_id=:id");
        $stmt->execute([':id' => $id]);
    }

    $stmt = $pdo->prepare('DELETE FROM vistorias WHERE id=:id');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('O relatorio mudou durante o saneamento.');
    }

    if ($agendamentoId !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vistorias WHERE agendamento_id=:id');
        $stmt->execute([':id' => $agendamentoId]);
        $outrosRelatorios = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM vistoria_retornos WHERE agendamento_id=:id');
        $stmt->execute([':id' => $agendamentoId]);
        $retornos = (int)$stmt->fetchColumn();

        if ($outrosRelatorios === 0 && $retornos === 0) {
            $pdo->prepare('DELETE FROM ordens_servico WHERE agendamento_id=:id')->execute([':id' => $agendamentoId]);
            $pdo->prepare('DELETE FROM agendamentos WHERE id=:id')->execute([':id' => $agendamentoId]);
        }
    }

    $pdo->commit();
    echo "Saneamento concluido. O relatorio duplicado foi excluido e o vigente foi preservado.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Saneamento abortado: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
