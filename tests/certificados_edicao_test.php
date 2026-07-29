<?php

require_once __DIR__ . '/../includes/functions.php';

function assertCertificadoEdicao(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach (['certificados_csn', 'certificados_cnbl', 'certificados_cnarq'] as $tabela) {
    $pdo->exec("CREATE TABLE {$tabela} (id TEXT PRIMARY KEY, assinado INTEGER NOT NULL DEFAULT 0)");
    $pdo->exec("INSERT INTO {$tabela} (id, assinado) VALUES ('nao-assinado', 0), ('assinado', 1)");
    assertCertificadoEdicao(
        !documentoEstaAssinado($pdo, $tabela, 'nao-assinado'),
        "{$tabela}: certificado não assinado foi bloqueado."
    );
    assertCertificadoEdicao(
        documentoEstaAssinado($pdo, $tabela, 'assinado'),
        "{$tabela}: certificado assinado não foi bloqueado."
    );
}

assertCertificadoEdicao(normalizarDecimalFormulario('324,60') === '324.60', 'Decimal com vírgula não foi normalizado.');
assertCertificadoEdicao(normalizarDecimalFormulario('24.60') === '24.60', 'Decimal com ponto foi alterado.');
assertCertificadoEdicao(normalizarDecimalFormulario('') === null, 'Decimal vazio não retornou null.');
assertCertificadoEdicao(
    certificadoResolverVistoriaParaSalvar('certificado', 'vistoria-original', null) === 'vistoria-original',
    'A edição do CNARQ perdeu a vistoria persistida quando o POST não a informou.'
);
assertCertificadoEdicao(
    certificadoResolverVistoriaParaSalvar('certificado', 'vistoria-original', 'vistoria-adulterada') === 'vistoria-original',
    'A edição do CNARQ aceitou uma vistoria adulterada pelo POST.'
);
assertCertificadoEdicao(
    certificadoResolverVistoriaParaSalvar(null, null, 'vistoria-nova') === 'vistoria-nova',
    'A criação de CNARQ não preservou a vistoria informada pelo wizard.'
);

$funcoes = file_get_contents(__DIR__ . '/../includes/functions.php');
$formCsn = file_get_contents(__DIR__ . '/../modules/documentacao/certificados/form.php');
$acaoCsn = file_get_contents(__DIR__ . '/../modules/documentacao/certificados/actions.php');
$formCnbl = file_get_contents(__DIR__ . '/../modules/documentacao/cnbl/form.php');
$acaoCnbl = file_get_contents(__DIR__ . '/../modules/documentacao/cnbl/actions.php');
$formCnarq = file_get_contents(__DIR__ . '/../modules/documentacao/cnarq/form.php');
$acaoCnarq = file_get_contents(__DIR__ . '/../modules/documentacao/cnarq/actions.php');
$migration = file_get_contents(__DIR__ . '/../migrations/099_despachante_certificado_csn.sql');

assertCertificadoEdicao(!str_contains($funcoes, 'function documentoEstaAprovadoOuAssinado'), 'A regra antiga de aprovação ainda está ativa.');
assertCertificadoEdicao(str_contains($formCsn, "(\$_SESSION['usuario_cargo'] ?? '') !== 'ADMIN'"), 'A condição de permissão do administrador no CSN continua ambígua.');
assertCertificadoEdicao(str_contains($acaoCsn, 'documentoEstaAssinado'), 'O POST do CSN não protege certificados assinados.');
assertCertificadoEdicao(str_contains($migration, 'ADD COLUMN IF NOT EXISTS despachante_id CHAR(36) NULL'), 'A migration não adiciona despachante ao CSN.');

assertCertificadoEdicao(substr_count($formCnbl, 'name="arqueacao_bruta"') === 1, 'O CNBL ainda possui campos de arqueação bruta duplicados.');
assertCertificadoEdicao(str_contains($formCnbl, 'required min="0" step="0.01"'), 'O CNBL não aceita arqueação decimal.');
assertCertificadoEdicao(str_contains($acaoCnbl, 'normalizarDecimalFormulario'), 'O CNBL não normaliza arqueação com vírgula.');

assertCertificadoEdicao(str_contains($formCnarq, 'name="vistoria_id"'), 'O CNARQ não preserva o vínculo da vistoria no formulário.');
assertCertificadoEdicao(
    str_contains($formCnarq, 'id="relatorio_numero" class="form-control" readonly'),
    'Os relatórios do CNARQ continuam editáveis.'
);
assertCertificadoEdicao(
    str_contains($acaoCnarq, 'certificadoResolverVistoriaParaSalvar'),
    'O CNARQ não recupera a vistoria persistida durante a edição.'
);
assertCertificadoEdicao(substr_count($acaoCnarq, "':vistoria_id'") === 2, 'Os binds de vistoria do UPDATE/INSERT do CNARQ estão incorretos.');
assertCertificadoEdicao(substr_count($acaoCnarq, "':despachante_id'") === 2, 'O CNARQ mantém bind duplicado de despachante.');

echo "certificados_edicao_test: OK\n";
