<?php

require_once __DIR__ . '/../includes/functions.php';

function assertFluxoAutorizacao(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdoTeste = new PDO('sqlite::memory:');
$pdoTeste->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdoTeste->exec("CREATE TABLE agendamentos (
    id TEXT PRIMARY KEY,
    proposta_id TEXT NOT NULL,
    status TEXT NOT NULL,
    data_vistoria TEXT NULL,
    vistoriador_id TEXT NULL,
    created_at TEXT NOT NULL
)");

$insert = $pdoTeste->prepare("INSERT INTO agendamentos
    (id, proposta_id, status, data_vistoria, vistoriador_id, created_at)
    VALUES (:id, :proposta, :status, :data, :vistoriador, :criado)");
$registros = [
    ['a1', 'p1', 'pendente', null, null, '2026-07-28 08:00:00'],
    ['a2', 'p1', 'pendente', '2026-08-10', null, '2026-07-28 08:01:00'],
    ['a3', 'p1', 'pendente', '2026-08-11', 'v1', '2026-07-28 08:02:00'],
    ['a4', 'p1', 'confirmado', null, null, '2026-07-28 08:03:00'],
    ['b1', 'p2', 'pendente', null, null, '2026-07-28 08:04:00'],
];
foreach ($registros as [$id, $proposta, $status, $data, $vistoriador, $criado]) {
    $insert->execute([
        ':id' => $id,
        ':proposta' => $proposta,
        ':status' => $status,
        ':data' => $data,
        ':vistoriador' => $vistoriador,
        ':criado' => $criado,
    ]);
}

assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'p1') === 'a1',
    'O fluxo nao selecionou o primeiro agendamento incompleto.'
);
assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'p1', 'a1') === 'a2',
    'O fluxo nao avancou para o proximo agendamento incompleto.'
);
assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'p1', 'a2') === 'a1',
    'O helper ignorou mais agendamentos do que o solicitado.'
);
assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'sem-agendamento') === null,
    'Uma proposta sem vistoria pendente recebeu um destino incorreto.'
);
$pdoTeste->exec("UPDATE agendamentos SET data_vistoria='2026-08-09', vistoriador_id='v1' WHERE id='a1'");
assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'p1') === 'a2',
    'O fluxo nao avancou depois do preenchimento do primeiro agendamento.'
);
$pdoTeste->exec("UPDATE agendamentos SET vistoriador_id='v2' WHERE id='a2'");
assertFluxoAutorizacao(
    proximoAgendamentoPendenteProposta($pdoTeste, 'p1') === null,
    'O fluxo nao terminou depois que todos os agendamentos foram preenchidos.'
);

$actionsPropostas = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
$formAgendamento = file_get_contents(__DIR__ . '/../modules/agendamentos/form.php');
$actionsAgendamento = file_get_contents(__DIR__ . '/../modules/agendamentos/actions.php');
assertFluxoAutorizacao(
    $actionsPropostas !== false
    && str_contains($actionsPropostas, "'agendamentos/form?id='")
    && str_contains($actionsPropostas, "'&fluxo_proposta=1'"),
    'A autorizacao manual nao redireciona para o formulario de agendamento.'
);
assertFluxoAutorizacao(
    $formAgendamento !== false
    && str_contains($formAgendamento, 'name="fluxo_proposta"'),
    'O formulario nao preserva o indicador do fluxo sequencial.'
);
assertFluxoAutorizacao(
    $actionsAgendamento !== false
    && str_contains($actionsAgendamento, 'Continue com o próximo agendamento desta proposta.')
    && str_contains($actionsAgendamento, 'Todos os agendamentos desta proposta foram preenchidos.'),
    'O salvamento nao encadeia os agendamentos da proposta.'
);

echo "fluxo_autorizacao_agendamento_test: OK\n";
