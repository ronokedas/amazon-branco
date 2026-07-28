<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertRetornoExigenciasIntegracao(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$base = $pdo->query("SELECT * FROM agendamentos ORDER BY created_at LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$base) {
    echo "fluxo_retornos_exigencias_integracao_test: SKIP (sem agendamento base)\n";
    exit(0);
}

$raizId = gerarUUID();
$filhoId = gerarUUID();
$agendaRaizId = gerarUUID();
$agendaFilhoId = gerarUUID();
$exigenciaRaizId = gerarUUID();
$exigenciaFilhoId = gerarUUID();
$sufixo = substr(str_replace('-', '', $raizId), 0, 8);

$pdo->beginTransaction();
try {
    $stmtAgenda = $pdo->prepare("INSERT INTO agendamentos
        (id,proposta_id,relatorio_origem_id,embarcacao_id,cliente_id,armador_id,operador_nome,
         vistoriador_id,vendedor_id,tipo_vistoria,data_vistoria,hora_vistoria,local,
         contato_nome,contato_telefone,status,observacoes,criado_por)
        SELECT :id,proposta_id,:origem,embarcacao_id,cliente_id,armador_id,operador_nome,
               vistoriador_id,vendedor_id,:tipo,CURDATE(),hora_vistoria,local,
               contato_nome,contato_telefone,'confirmado','Teste retorno comum',criado_por
        FROM agendamentos WHERE id=:base");
    $stmtAgenda->execute([':id'=>$agendaRaizId, ':origem'=>null, ':tipo'=>'Vistoria teste', ':base'=>$base['id']]);

    $stmtVistoria = $pdo->prepare("INSERT INTO vistorias
        (id,numero,embarcacao_id,pessoa_id,agendamento_id,finalidade,data_vistoria,status,
         assinatura_status,assinatura_em)
        VALUES (:id,:numero,:embarcacao,:pessoa,:agenda,'VISTORIA',CURDATE(),
                'APROVADA_COM_EXIGENCIAS','ASSINADO',NOW())");
    $stmtVistoria->execute([
        ':id'=>$raizId, ':numero'=>"TEST-EX-{$sufixo}-0",
        ':embarcacao'=>$base['embarcacao_id'], ':pessoa'=>$base['cliente_id'],
        ':agenda'=>$agendaRaizId,
    ]);
    $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,item,descricao,conforme,antes_de_suspender,status_item)
        VALUES (:id,:vistoria,1,'Comum teste','Exigencia comum teste','nao',0,'pendente')")
        ->execute([':id'=>$exigenciaRaizId, ':vistoria'=>$raizId]);

    $retornoId = criarPendenciaRetorno($pdo, $raizId, null, 'EXIGENCIAS');
    assertRetornoExigenciasIntegracao($retornoId !== '', 'Pendencia comum nao foi criada.');
    assertRetornoExigenciasIntegracao(
        criarPendenciaRetorno($pdo, $raizId, null, 'EXIGENCIAS') === $retornoId,
        'Pendencias comuns duplicadas foram criadas.'
    );

    $stmtAgenda->execute([
        ':id'=>$agendaFilhoId, ':origem'=>$raizId,
        ':tipo'=>'Retorno de exigencias', ':base'=>$base['id'],
    ]);
    $pdo->prepare("UPDATE vistoria_retornos
        SET status='AGENDADO',agendamento_id=:agenda WHERE id=:id")
        ->execute([':agenda'=>$agendaFilhoId, ':id'=>$retornoId]);
    $pdo->prepare("INSERT INTO vistorias
        (id,numero,embarcacao_id,pessoa_id,agendamento_id,relatorio_anterior_id,
         finalidade,data_vistoria,status)
        VALUES (:id,:numero,:embarcacao,:pessoa,:agenda,:anterior,
                'CUMPRIMENTO_EXIGENCIAS',CURDATE(),'PENDENTE')")
        ->execute([
            ':id'=>$filhoId, ':numero'=>"TEST-EX-{$sufixo}-1",
            ':embarcacao'=>$base['embarcacao_id'], ':pessoa'=>$base['cliente_id'],
            ':agenda'=>$agendaFilhoId, ':anterior'=>$raizId,
        ]);
    $pdo->prepare("UPDATE vistoria_retornos SET relatorio_resultado_id=:filho WHERE id=:id")
        ->execute([':filho'=>$filhoId, ':id'=>$retornoId]);
    $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,numero_origem,numero_sequencial,item,descricao,conforme,
         antes_de_suspender,status_item,exigencia_origem_id)
        VALUES (:id,:vistoria,1,1,1,'Comum teste','Exigencia comum teste','nao',0,'pendente',:origem)")
        ->execute([
            ':id'=>$exigenciaFilhoId, ':vistoria'=>$filhoId, ':origem'=>$exigenciaRaizId,
        ]);

    $duranteRetorno = avaliarLiberacaoCertificacao($pdo, $raizId);
    assertRetornoExigenciasIntegracao(
        $duranteRetorno['permitido'] && $duranteRetorno['vistoria_id'] === $raizId,
        'O retorno comum pendente bloqueou o ultimo relatorio aprovado.'
    );

    $pdo->prepare("UPDATE vistoria_exigencias
        SET conforme='sim',status_item='cumprida',numero_sequencial=NULL WHERE id=:id")
        ->execute([':id'=>$exigenciaFilhoId]);
    $pdo->prepare("UPDATE vistorias
        SET status='APROVADA',assinatura_status='ASSINADO',assinatura_em=NOW() WHERE id=:id")
        ->execute([':id'=>$filhoId]);
    $aposRetorno = avaliarLiberacaoCertificacao($pdo, $filhoId);
    assertRetornoExigenciasIntegracao(
        $aposRetorno['permitido'] && $aposRetorno['vistoria_id'] === $filhoId,
        'O retorno comum aprovado e assinado nao se tornou certificavel.'
    );

    $pdo->rollBack();
    echo "fluxo_retornos_exigencias_integracao_test: OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
