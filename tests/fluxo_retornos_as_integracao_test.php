<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

function assertRetornoIntegracao(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$base = $pdo->query("SELECT * FROM agendamentos ORDER BY created_at LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$base) {
    echo "fluxo_retornos_as_integracao_test: SKIP (sem agendamento base)\n";
    exit(0);
}

$raizId = gerarUUID();
$filhoId = gerarUUID();
$agendamentoRaizId = gerarUUID();
$agendamentoFilhoId = gerarUUID();
$asRaizId = gerarUUID();
$comumRaizId = gerarUUID();
$asFilhoId = gerarUUID();
$comumFilhoId = gerarUUID();
$sufixo = substr(str_replace('-', '', $raizId), 0, 8);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO agendamentos
        (id,proposta_id,relatorio_origem_id,embarcacao_id,cliente_id,armador_id,operador_nome,
         vistoriador_id,vendedor_id,tipo_vistoria,data_vistoria,hora_vistoria,local,
         contato_nome,contato_telefone,status,observacoes,criado_por)
        SELECT :id,proposta_id,:origem,embarcacao_id,cliente_id,armador_id,operador_nome,
               vistoriador_id,vendedor_id,:tipo,CURDATE(),hora_vistoria,local,
               contato_nome,contato_telefone,'confirmado','Teste transacional A/S',criado_por
        FROM agendamentos WHERE id=:base");
    $stmt->execute([
        ':id'=>$agendamentoRaizId,
        ':origem'=>null,
        ':tipo'=>'Vistoria de teste',
        ':base'=>$base['id'],
    ]);
    $stmt->execute([
        ':id'=>$agendamentoFilhoId,
        ':origem'=>null,
        ':tipo'=>'Cumprimento de A/S',
        ':base'=>$base['id'],
    ]);

    $stmt = $pdo->prepare("INSERT INTO vistorias
        (id,numero,embarcacao_id,pessoa_id,agendamento_id,finalidade,data_vistoria,status)
        VALUES
        (:id,:numero,:embarcacao,:pessoa,:agendamento,'VISTORIA',CURDATE(),'RETORNO_AS')");
    $stmt->execute([
        ':id'=>$raizId, ':numero'=>"TEST-AS-{$sufixo}-0",
        ':embarcacao'=>$base['embarcacao_id'], ':pessoa'=>$base['cliente_id'],
        ':agendamento'=>$agendamentoRaizId,
    ]);
    $pdo->prepare('UPDATE agendamentos SET relatorio_origem_id=:origem WHERE id=:id')
        ->execute([':origem'=>$raizId, ':id'=>$agendamentoFilhoId]);

    $stmt = $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,item,conforme,antes_de_suspender,status_item)
        VALUES
        (:as_id,:vistoria,1,'A/S teste','nao',1,'pendente'),
        (:comum_id,:vistoria2,2,'Comum teste','nao',0,'pendente')");
    $stmt->execute([
        ':as_id'=>$asRaizId, ':vistoria'=>$raizId,
        ':comum_id'=>$comumRaizId, ':vistoria2'=>$raizId,
    ]);

    $stmt = $pdo->prepare("INSERT INTO vistorias
        (id,numero,embarcacao_id,pessoa_id,agendamento_id,relatorio_anterior_id,
         finalidade,data_vistoria,status)
        VALUES
        (:id,:numero,:embarcacao,:pessoa,:agendamento,:anterior,
         'CUMPRIMENTO_EXIGENCIAS',CURDATE(),'APROVADA')");
    $stmt->execute([
        ':id'=>$filhoId, ':numero'=>"TEST-AS-{$sufixo}-1",
        ':embarcacao'=>$base['embarcacao_id'], ':pessoa'=>$base['cliente_id'],
        ':agendamento'=>$agendamentoFilhoId, ':anterior'=>$raizId,
    ]);
    $semAssinatura = avaliarLiberacaoCertificacao($pdo, $filhoId);
    assertRetornoIntegracao(
        !$semAssinatura['permitido'] && !empty($semAssinatura['aguarda_assinatura']),
        'Um relatorio aprovado sem assinatura liberou a certificacao.'
    );
    $pdo->prepare("UPDATE vistorias SET assinatura_status='ASSINADO',assinatura_em=NOW() WHERE id=:id")
        ->execute([':id'=>$filhoId]);
    $stmt = $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,item,conforme,antes_de_suspender,status_item,exigencia_origem_id)
        VALUES (:id,:vistoria,1,'A/S teste','sim',1,'cumprida',:origem)");
    $stmt->execute([':id'=>$asFilhoId, ':vistoria'=>$filhoId, ':origem'=>$asRaizId]);

    $vigente = obterRelatorioVigenteCadeia($pdo, $raizId);
    assertRetornoIntegracao(($vigente['id'] ?? null) === $filhoId, 'A cadeia nao atravessou o novo agendamento.');

    $historico = avaliarLiberacaoCertificacao($pdo, $raizId);
    assertRetornoIntegracao(!$historico['permitido'], 'O relatorio historico conseguiu gerar certificado.');

    $liberacao = avaliarLiberacaoCertificacao($pdo, $filhoId);
    assertRetornoIntegracao($liberacao['permitido'], 'A cadeia continuou bloqueada depois do cumprimento A/S.');
    assertRetornoIntegracao(
        $liberacao['status'] === 'APROVADA',
        'A aprovacao final legada nao encerrou as exigencias comuns que o sistema antigo deixou de copiar.'
    );
    assertRetornoIntegracao(str_contains($liberacao['relatorios_referencia'], ' e '), 'A referencia nao contem original e cumprimento.');

    $stmt = $pdo->prepare("INSERT INTO vistoria_exigencias
        (id,vistoria_id,ordem,item,conforme,antes_de_suspender,status_item,exigencia_origem_id)
        VALUES (:id,:vistoria,2,'Comum teste','nao',0,'pendente',:origem)");
    $stmt->execute([':id'=>$comumFilhoId, ':vistoria'=>$filhoId, ':origem'=>$comumRaizId]);
    $comumPendente = avaliarLiberacaoCertificacao($pdo, $filhoId);
    assertRetornoIntegracao(
        $comumPendente['status'] === 'APROVADA_COM_EXIGENCIAS',
        'Uma exigencia comum aberta no relatorio vigente liberou o definitivo.'
    );

    $pdo->prepare("UPDATE vistoria_exigencias
        SET conforme='sim',status_item='cumprida',observacao='Cumprimento verificado'
        WHERE id=:id")->execute([':id'=>$comumFilhoId]);
    $cumprimentoIntegral = avaliarLiberacaoCertificacao($pdo, $filhoId);
    assertRetornoIntegracao(
        $cumprimentoIntegral['status'] === 'APROVADA',
        'O cumprimento integral da exigencia comum nao liberou o definitivo.'
    );

    $retornoId = criarPendenciaRetornoAS($pdo, $raizId, null);
    assertRetornoIntegracao($retornoId !== '', 'A pendencia auditavel nao foi criada.');
    assertRetornoIntegracao(criarPendenciaRetornoAS($pdo, $raizId, null) === $retornoId, 'Foram criadas pendencias duplicadas.');

    $pdo->rollBack();
    echo "fluxo_retornos_as_integracao_test: OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
