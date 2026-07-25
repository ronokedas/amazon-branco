<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertCadeiaIntegridade(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$base = $pdo->query('SELECT * FROM agendamentos ORDER BY created_at LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$base) {
    echo "relatorios_cadeia_integridade_test: SKIP (sem agendamento base)\n";
    exit(0);
}

$agendamentoRaiz = gerarUUID();
$agendamentoRetorno = gerarUUID();
$raizId = gerarUUID();
$exigenciaId = gerarUUID();
$retornoId = gerarUUID();
$sufixo = substr(str_replace('-', '', $raizId), 0, 8);
$usuarioTeste = (string)($base['criado_por'] ?: $base['vistoriador_id'] ?: $pdo->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn());

$pdo->beginTransaction();
try {
    $stmtAgendamento = $pdo->prepare("INSERT INTO agendamentos
        (id,proposta_id,relatorio_origem_id,embarcacao_id,cliente_id,armador_id,operador_nome,
         vistoriador_id,vendedor_id,tipo_vistoria,data_vistoria,hora_vistoria,local,
         contato_nome,contato_telefone,status,observacoes,criado_por)
        SELECT :id,proposta_id,:origem,embarcacao_id,cliente_id,armador_id,operador_nome,
               vistoriador_id,vendedor_id,:tipo,CURDATE(),hora_vistoria,local,
               contato_nome,contato_telefone,'confirmado','Teste de integridade A/S',criado_por
        FROM agendamentos WHERE id=:base");
    $stmtAgendamento->execute([
        ':id' => $agendamentoRaiz,
        ':origem' => null,
        ':tipo' => 'Vistoria de teste',
        ':base' => $base['id'],
    ]);

    $stmt = $pdo->prepare("INSERT INTO vistorias
        (id,numero,embarcacao_id,pessoa_id,agendamento_id,relatorio_anterior_id,
         finalidade,data_vistoria,status)
        VALUES (:id,:numero,:embarcacao,:pessoa,:agendamento,NULL,'VISTORIA',CURDATE(),'APROVADA_COM_EXIGENCIAS')");
    $stmt->execute([
        ':id' => $raizId,
        ':numero' => "TEST-INTEGRIDADE-{$sufixo}-0",
        ':embarcacao' => $base['embarcacao_id'],
        ':pessoa' => $base['cliente_id'],
        ':agendamento' => $agendamentoRaiz,
    ]);
    $stmt = $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,item,conforme,antes_de_suspender,status_item)
        VALUES (:id,:vistoria,1,'A/S teste','nao',1,'pendente')");
    $stmt->execute([':id' => $exigenciaId, ':vistoria' => $raizId]);
    $stmt = $pdo->prepare("INSERT INTO vistoria_retornos
        (id,relatorio_origem_id,status)
        VALUES (:id,:origem,'PENDENTE_AGENDAMENTO')");
    $stmt->execute([':id' => $retornoId, ':origem' => $raizId]);

    $stmtAgendamento->execute([
        ':id' => $agendamentoRetorno,
        ':origem' => $raizId,
        ':tipo' => 'Cumprimento de A/S',
        ':base' => $base['id'],
    ]);
    $pdo->prepare("UPDATE vistoria_retornos SET status='AGENDADO',agendamento_id=:agendamento WHERE id=:id")
        ->execute([':agendamento' => $agendamentoRetorno, ':id' => $retornoId]);

    $agendamento = $pdo->query("SELECT * FROM agendamentos WHERE id=" . $pdo->quote($agendamentoRetorno))->fetch(PDO::FETCH_ASSOC);
    $filhoId = criarRelatorioCumprimentoAgendamento($pdo, $agendamento, $usuarioTeste);
    assertCadeiaIntegridade($filhoId !== null, 'O relatorio de cumprimento nao foi criado.');
    assertCadeiaIntegridade(
        criarRelatorioCumprimentoAgendamento($pdo, $agendamento, $usuarioTeste) === $filhoId,
        'A criacao idempotente devolveu outro relatorio.'
    );

    $filho = $pdo->query("SELECT * FROM vistorias WHERE id=" . $pdo->quote($filhoId))->fetch(PDO::FETCH_ASSOC);
    assertCadeiaIntegridade($filho['relatorio_anterior_id'] === $raizId, 'O retorno nao preservou a origem formal.');
    assertCadeiaIntegridade($filho['finalidade'] === 'CUMPRIMENTO_EXIGENCIAS', 'Finalidade incorreta no retorno.');

    $duplicouAgendamento = false;
    try {
        $pdo->prepare("INSERT INTO vistorias
            (id,numero,embarcacao_id,pessoa_id,agendamento_id,finalidade,data_vistoria,status)
            VALUES (UUID(),:numero,:embarcacao,:pessoa,:agendamento,'VISTORIA',CURDATE(),'PENDENTE')")
            ->execute([
                ':numero' => "TEST-INTEGRIDADE-{$sufixo}-DUP-AG",
                ':embarcacao' => $base['embarcacao_id'],
                ':pessoa' => $base['cliente_id'],
                ':agendamento' => $agendamentoRaiz,
            ]);
        $duplicouAgendamento = true;
    } catch (PDOException $esperado) {
    }
    assertCadeiaIntegridade(!$duplicouAgendamento, 'O banco permitiu dois relatorios no mesmo agendamento.');

    $duplicouFilho = false;
    try {
        $pdo->prepare("INSERT INTO vistorias
            (id,numero,embarcacao_id,pessoa_id,relatorio_anterior_id,finalidade,data_vistoria,status)
            VALUES (UUID(),:numero,:embarcacao,:pessoa,:origem,'CUMPRIMENTO_EXIGENCIAS',CURDATE(),'PENDENTE')")
            ->execute([
                ':numero' => "TEST-INTEGRIDADE-{$sufixo}-DUP-FILHO",
                ':embarcacao' => $base['embarcacao_id'],
                ':pessoa' => $base['cliente_id'],
                ':origem' => $raizId,
            ]);
        $duplicouFilho = true;
    } catch (PDOException $esperado) {
    }
    assertCadeiaIntegridade(!$duplicouFilho, 'O banco permitiu dois filhos ativos para a mesma origem.');

    $relatorioTela = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
    $fila = file_get_contents(__DIR__ . '/../modules/documentacao/aprovacao_relatorios.php');
    $acoes = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
    assertCadeiaIntegridade(str_contains($relatorioTela, '$eh_relatorio_vigente'), 'A tela nao bloqueia decisoes historicas.');
    assertCadeiaIntegridade(str_contains($fila, 'vf.relatorio_anterior_id=v.id'), 'A fila ainda inclui relatorios substituidos.');
    assertCadeiaIntegridade(str_contains($acoes, "SET status='CANCELADO'"), 'O cancelamento nao atualiza o retorno A/S.');

    $pdo->rollBack();
    echo "relatorios_cadeia_integridade_test: OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
