<?php

function assertBackupLimpeza(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

$actions = file_get_contents(__DIR__ . '/../modules/configuracoes/backup_actions.php');
$pagina = file_get_contents(__DIR__ . '/../modules/configuracoes/backup.php');

assertBackupLimpeza($actions !== false, 'Nao foi possivel ler a rotina de limpeza.');
assertBackupLimpeza($pagina !== false, 'Nao foi possivel ler a pagina de backup.');

$encontrouLista = preg_match('/\$preservadas\s*=\s*\[(.*?)\];/s', $actions, $lista);
assertBackupLimpeza($encontrouLista === 1, 'A lista de tabelas preservadas nao foi encontrada.');

preg_match_all("/'([^']+)'/", $lista[1], $nomes);
$preservadas = $nomes[1];

$obrigatorias = [
    'usuarios',
    'usuario_perfis',
    'usuario_permissoes',
    'responsaveis_assinatura',
    'escritorios',
    'usuario_escritorios',
];

foreach ($obrigatorias as $tabela) {
    assertBackupLimpeza(
        in_array($tabela, $preservadas, true),
        "A limpeza nao preserva a tabela obrigatoria {$tabela}."
    );
}

$operacionais = [
    'clientes',
    'propostas',
    'documento_assinaturas',
    'assinatura_convites',
];

foreach ($operacionais as $tabela) {
    assertBackupLimpeza(
        !in_array($tabela, $preservadas, true),
        "A tabela operacional {$tabela} foi preservada indevidamente."
    );
}

foreach (['responsáveis e suas assinaturas', 'escritórios', 'vínculos entre usuários e escritórios'] as $texto) {
    assertBackupLimpeza(
        str_contains($pagina, $texto),
        "A tela de limpeza nao informa a preservacao de {$texto}."
    );
}

assertBackupLimpeza(
    str_contains($actions, 'responsáveis e suas assinaturas')
    && str_contains($actions, 'escritórios')
    && str_contains($actions, 'vínculos'),
    'A mensagem de sucesso nao descreve todos os dados preservados.'
);

echo "backup_limpeza_preservacao_test: OK\n";
