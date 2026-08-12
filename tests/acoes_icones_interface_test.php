<?php

function assertAcoesIcones(bool $condicao, string $mensagem): void
{
    if (!$condicao) throw new RuntimeException($mensagem);
}

$css = file_get_contents(__DIR__ . '/../assets/css/style.css');
$analises = file_get_contents(__DIR__ . '/../modules/analises_planos/index.php');
$relatorios = file_get_contents(__DIR__ . '/../modules/documentacao/aprovacao_relatorios.php');
$assinaturas = file_get_contents(__DIR__ . '/../modules/minhas_assinaturas/index.php');
$responsaveis = file_get_contents(__DIR__ . '/../modules/responsaveis_assinatura/index.php');
$usuarios = file_get_contents(__DIR__ . '/../modules/usuarios/index.php');

assertAcoesIcones(str_contains($css, '.btn.btn-icon-action'), 'O padrão visual de ações por ícone não foi criado.');
assertAcoesIcones(str_contains($css, 'color: #fff !important'), 'Os ícones sobre fundo verde não possuem contraste claro.');

assertAcoesIcones(str_contains($analises, 'btn-icon-action'), 'Análise de Planos não usa o botão compacto.');
assertAcoesIcones(!str_contains($analises, 'fa-eye"></i> Abrir'), 'Análise de Planos ainda exibe o texto Abrir.');
assertAcoesIcones(str_contains($analises, 'aria-label="Abrir análise'), 'Análise de Planos perdeu o nome acessível da ação.');

assertAcoesIcones(str_contains($relatorios, 'aria-label="Visualizar relatório'), 'Relatórios não possui ação acessível por ícone.');
assertAcoesIcones(!str_contains($relatorios, 'btn btn-sm btn-secondary">Ver</a>'), 'Relatórios ainda exibe o texto Ver.');

assertAcoesIcones(str_contains($assinaturas, 'btn-icon-action js-assinar'), 'Minhas assinaturas não usa o botão compacto.');
assertAcoesIcones(!str_contains($assinaturas, 'fa-pen-nib"></i> Assinar'), 'Minhas assinaturas ainda exibe o texto Assinar.');

assertAcoesIcones(str_contains($responsaveis, 'btn-info btn-icon-action'), 'Responsáveis pela assinatura não usa o padrão de contraste.');
assertAcoesIcones(!str_contains($usuarios, 'name="action" value="excluir"'), 'A tela de usuários ainda oferece exclusão.');
assertAcoesIcones(!str_contains($usuarios, 'fa-trash'), 'O ícone de exclusão ainda aparece na tela de usuários.');

echo "acoes_icones_interface_test: OK\n";
