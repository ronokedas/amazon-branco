<?php

require_once __DIR__ . '/../includes/functions.php';

function assertCertificadoServico(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE agendamentos (id TEXT PRIMARY KEY, proposta_id TEXT, embarcacao_id TEXT NOT NULL)');
$pdo->exec('CREATE TABLE propostas_servicos (proposta_id TEXT NOT NULL, embarcacao_id TEXT, servico_id TEXT NOT NULL)');
$pdo->exec('CREATE TABLE servicos (id TEXT PRIMARY KEY, certificado_modelo TEXT, ativo INTEGER NOT NULL DEFAULT 1)');
$pdo->exec('CREATE TABLE vistorias (id TEXT PRIMARY KEY, agendamento_id TEXT)');

$pdo->exec("INSERT INTO agendamentos VALUES ('a1','p1','e1'),('a2','p1','e2'),('a3',NULL,'e3')");
$pdo->exec("INSERT INTO servicos VALUES ('seco','CSN',0),('arqueacao','CNARQ',1),('borda','CNBL',1),('comum',NULL,1)");
$pdo->exec("INSERT INTO propostas_servicos VALUES ('p1','e1','seco'),('p1','e2','arqueacao'),('p1',NULL,'borda'),('p1','e1','comum')");
$pdo->exec("INSERT INTO vistorias VALUES ('v1','a1'),('v2','a2')");

$a1 = certificadoModelosPermitidosPorAgendamento($pdo, 'a1');
assertCertificadoServico($a1 === ['CSN' => true, 'CNBL' => true, 'CNARQ' => false], 'Servicos de outra embarcacao vazaram para a primeira.');
assertCertificadoServico(certificadoModeloPermitidoPorVistoria($pdo, 'v1', 'CSN'), 'Servico contratado e desativado no catalogo deixou de valer.');
assertCertificadoServico(!certificadoModeloPermitidoPorVistoria($pdo, 'v1', 'CNARQ'), 'CNARQ foi permitido sem arqueacao para a embarcacao.');

$a2 = certificadoModelosPermitidosPorAgendamento($pdo, 'a2');
assertCertificadoServico($a2 === ['CSN' => false, 'CNBL' => true, 'CNARQ' => true], 'Servico global ou especifico nao foi aplicado corretamente.');
assertCertificadoServico(certificadoModelosPermitidosPorAgendamento($pdo, 'a3') === ['CSN' => false, 'CNBL' => false, 'CNARQ' => false], 'Agendamento sem proposta habilitou certificado.');

$selection = file_get_contents(__DIR__ . '/../modules/documentacao/novo_certificado.php');
$wizard = file_get_contents(__DIR__ . '/../modules/certificados/wizard.php');
$step2 = file_get_contents(__DIR__ . '/../modules/certificados/wizard_step2.php');
$legacyActions = [
    'CSN' => file_get_contents(__DIR__ . '/../modules/documentacao/certificados/actions.php'),
    'CNBL' => file_get_contents(__DIR__ . '/../modules/documentacao/cnbl/actions.php'),
    'CNARQ' => file_get_contents(__DIR__ . '/../modules/documentacao/cnarq/actions.php'),
];
$migration = file_get_contents(__DIR__ . '/../migrations/085_servicos_habilitam_certificados.sql');
assertCertificadoServico(str_contains($selection, 'certificadoModelosPermitidosPorAgendamento'), 'A selecao de modelos nao usa a regra central.');
assertCertificadoServico(str_contains($selection, "\$_GET['vistoria_id']"), 'A selecao de modelos nao aceita um relatorio pre-selecionado.');
assertCertificadoServico(str_contains($selection, '&vistoria_id=<?= urlencode($vistoria_id) ?>'), 'A selecao de modelos perde o relatorio ao abrir o wizard.');
assertCertificadoServico(str_contains($wizard, 'certificadoModeloPermitidoPorAgendamento'), 'A URL direta do wizard nao esta protegida.');
assertCertificadoServico(str_contains($wizard, "\$_GET['vistoria_id']"), 'O wizard nao preserva o ID do relatorio escolhido.');
assertCertificadoServico(str_contains($step2, 'certificadoModeloPermitidoPorVistoria'), 'O POST final nao repete a validacao do servico.');
foreach ($legacyActions as $modelo => $source) {
    assertCertificadoServico(str_contains($source, "certificadoModeloPermitidoPorVistoria(\$pdo, (string)\$vistoria_id, '{$modelo}')"), "A criacao direta de {$modelo} nao esta protegida.");
}
foreach (['CSN', 'CNBL', 'CNARQ'] as $modelo) {
    assertCertificadoServico(str_contains($migration, "certificado_modelo = '{$modelo}'"), "A migracao nao classifica os servicos de {$modelo}.");
}

echo "certificados_servicos_contratados_test: OK\n";
