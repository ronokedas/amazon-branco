<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require_once __DIR__ . '/../config.php';

$ids = [
    'cliente' => '70000000-0000-4000-8000-000000000001',
    'balsa' => '70000000-0000-4000-8000-000000000002',
    'agenda_balsa' => '70000000-0000-4000-8000-000000000003',
    'solimoes' => '70000000-0000-4000-8000-000000000004',
    'agenda_solimoes' => '70000000-0000-4000-8000-000000000005',
];

try {
    $pdo->beginTransaction();
    $admin = $pdo->query("SELECT id FROM usuarios WHERE cargo='ADMIN' AND ativo=1 ORDER BY criado_em LIMIT 1")->fetchColumn();
    if (!$admin) throw new RuntimeException('A demo exige um administrador ativo.');
    $vistoriador = $admin;
    $pdo->prepare("INSERT IGNORE INTO usuario_perfis (usuario_id, perfil) VALUES (:usuario, 'VISTORIADOR')")
        ->execute([':usuario' => $admin]);

    if (in_array('--reset', $argv, true)) {
        $placeholders = implode(',', array_fill(0, 2, '?'));
        $pdo->prepare("DELETE FROM vistorias WHERE agendamento_id IN ($placeholders)")
            ->execute([$ids['agenda_balsa'], $ids['agenda_solimoes']]);
    }

    $pdo->prepare("INSERT INTO clientes (id,nome,tipo_pessoa,perfil,telefone,email,status,criado_por)
        VALUES (:id,'Navegação Modelo Amazônia Ltda.','PJ','proprietario','(92) 99999-0000','demo@example.test','ATIVO',:admin)
        ON DUPLICATE KEY UPDATE nome=VALUES(nome),status='ATIVO'")
        ->execute([':id'=>$ids['cliente'], ':admin'=>$admin]);

    $stmtEmb = $pdo->prepare("INSERT INTO embarcacoes
        (id,proprietario_id,nome,tipo,tipo_embarcacao,registro,proprietario,cliente_id,material_casco,ativo,criado_por)
        VALUES (:id,:proprietario,:nome,'Balsa','Balsa',:registro,'Navegação Modelo Amazônia Ltda.',:cliente,'Aço',1,:admin)
        ON DUPLICATE KEY UPDATE nome=VALUES(nome),proprietario_id=VALUES(proprietario_id),cliente_id=VALUES(cliente_id),ativo=1");
    $stmtEmb->execute([':id'=>$ids['balsa'], ':proprietario'=>$ids['cliente'], ':cliente'=>$ids['cliente'], ':nome'=>'BALSA RIO MAR', ':registro'=>'DEMO-BAL-17/26', ':admin'=>$admin]);
    $stmtEmb->execute([':id'=>$ids['solimoes'], ':proprietario'=>$ids['cliente'], ':cliente'=>$ids['cliente'], ':nome'=>'N/M SOLIMÕES', ':registro'=>'DEMO-NMS-08/26', ':admin'=>$admin]);

    $stmtAg = $pdo->prepare("INSERT INTO agendamentos
        (id,embarcacao_id,cliente_id,vistoriador_id,tipo_vistoria,data_vistoria,hora_vistoria,local,status,observacoes,criado_por)
        VALUES (:id,:embarcacao,:cliente,:vistoriador,'DEMONSTRACAO CAMPO',CURDATE(),:hora,:local,'pendente','Registro fictício para demonstração da PWA.',:admin)
        ON DUPLICATE KEY UPDATE data_vistoria=CURDATE(),hora_vistoria=VALUES(hora_vistoria),local=VALUES(local),status='pendente',vistoriador_id=VALUES(vistoriador_id)");
    $stmtAg->execute([':id'=>$ids['agenda_balsa'], ':embarcacao'=>$ids['balsa'], ':cliente'=>$ids['cliente'], ':vistoriador'=>$vistoriador, ':hora'=>'09:30:00', ':local'=>'Ponta Negra - Manaus/AM', ':admin'=>$admin]);
    $stmtAg->execute([':id'=>$ids['agenda_solimoes'], ':embarcacao'=>$ids['solimoes'], ':cliente'=>$ids['cliente'], ':vistoriador'=>$vistoriador, ':hora'=>'14:00:00', ':local'=>'Porto de Manaus - AM', ':admin'=>$admin]);

    $pdo->commit();
    echo "Demo de campo preparada com sucesso.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Erro ao preparar demo: {$e->getMessage()}\n");
    exit(1);
}
