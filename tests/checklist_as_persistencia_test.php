<?php

require_once __DIR__ . '/../config.php';

function assertChecklistAS(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$formulario = file_get_contents(__DIR__ . '/../modules/vistorias/relatorio.php');
$acoes = file_get_contents(__DIR__ . '/../modules/vistorias/actions.php');
assertChecklistAS($formulario !== false && $acoes !== false, 'Nao foi possivel ler o fluxo web da vistoria.');
assertChecklistAS(
    str_contains($formulario, 'name="checklist_sem_prazo_por_id['),
    'O checkbox A/S nao e enviado diretamente pelo formulario.'
);
assertChecklistAS(
    str_contains($acoes, 'sem_prazo = :sem_prazo_upd'),
    'O marcador A/S nao e atualizado na resposta persistida do checklist.'
);
assertChecklistAS(
    str_contains($acoes, '$checklist_sem_prazo_por_id[$cat_id]'),
    'O servidor nao usa o identificador do item para resolver o marcador A/S.'
);

$base = $pdo->query("
    SELECT r.vistoria_id, r.catalogo_id
    FROM vistoria_checklist_respostas r
    JOIN vistoria_exigencias ve
      ON ve.vistoria_id=r.vistoria_id
     AND ve.catalogo_id=r.catalogo_id
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$base) {
    echo "checklist_as_persistencia_test: OK (sem item para round-trip no banco)\n";
    exit(0);
}

$consultaRecarga = $pdo->prepare("
    SELECT CASE
             WHEN EXISTS (
                 SELECT 1
                 FROM vistoria_exigencias ve
                 WHERE ve.vistoria_id = r.vistoria_id
                   AND ve.catalogo_id = r.catalogo_id
                   AND ve.antes_de_suspender = 1
                   AND ve.conforme = 'nao'
                   AND ve.status_item <> 'cumprida'
             ) THEN 1
             ELSE COALESCE(r.sem_prazo, 0)
           END AS sem_prazo
    FROM vistoria_checklist_respostas r
    WHERE r.vistoria_id=:vistoria AND r.catalogo_id=:catalogo
");

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE vistoria_checklist_respostas
        SET sem_prazo=0 WHERE vistoria_id=:vistoria AND catalogo_id=:catalogo")
        ->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);
    $pdo->prepare("UPDATE vistoria_exigencias
        SET antes_de_suspender=1,conforme='nao',status_item='pendente'
        WHERE vistoria_id=:vistoria AND catalogo_id=:catalogo")
        ->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);

    $consultaRecarga->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);
    assertChecklistAS((int)$consultaRecarga->fetchColumn() === 1, 'A recarga apagou uma A/S existente na exigencia oficial.');

    $pdo->prepare("UPDATE vistoria_checklist_respostas
        SET sem_prazo=1 WHERE vistoria_id=:vistoria AND catalogo_id=:catalogo")
        ->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);
    $pdo->prepare("UPDATE vistoria_exigencias
        SET antes_de_suspender=0 WHERE vistoria_id=:vistoria AND catalogo_id=:catalogo")
        ->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);

    $consultaRecarga->execute([':vistoria'=>$base['vistoria_id'], ':catalogo'=>$base['catalogo_id']]);
    assertChecklistAS((int)$consultaRecarga->fetchColumn() === 1, 'A recarga ignorou uma A/S persistida na resposta do checklist.');

    $pdo->rollBack();
    echo "checklist_as_persistencia_test: OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
