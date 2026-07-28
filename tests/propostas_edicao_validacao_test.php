<?php

function assertPropostaEdicao(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

$wizard = file_get_contents(__DIR__ . '/../modules/comercial/nova.php');
$actions = file_get_contents(__DIR__ . '/../modules/comercial/propostas/actions.php');
$comercial = file_get_contents(__DIR__ . '/../modules/comercial/index.php');
$detalhes = file_get_contents(__DIR__ . '/../modules/comercial/propostas/index.php');

assertPropostaEdicao($wizard !== false, 'Wizard de propostas não encontrado.');
assertPropostaEdicao($actions !== false, 'Actions de propostas não encontrado.');

assertPropostaEdicao(
    str_contains($wizard, 'id="descontoGlobalDisplay"')
    && str_contains($wizard, 'type="hidden" id="descontoGlobal" name="desconto_global"'),
    'O desconto não separa o campo formatado do valor normalizado.'
);
assertPropostaEdicao(
    str_contains($wizard, 'id="valorEntradaDisplay"')
    && str_contains($wizard, 'type="hidden" id="valorEntrada" name="valor_entrada"'),
    'A entrada não separa o campo formatado do valor normalizado.'
);
assertPropostaEdicao(
    str_contains($wizard, 'valor >= 100')
    && str_contains($wizard, 'O desconto percentual deve ser menor que 100%'),
    'A interface não bloqueia desconto percentual de 100% ou mais.'
);
assertPropostaEdicao(
    str_contains($actions, "case 'atualizar':")
    && str_contains($actions, "status = 'rascunho' AND assinado = 0")
    && str_contains($actions, 'FOR UPDATE'),
    'A atualização não protege o rascunho contra status/assinatura e concorrência.'
);
assertPropostaEdicao(
    str_contains($actions, "if (\$tipo === 'perc' && \$valor >= 100)")
    && str_contains($actions, 'validarDescontoProposta($tipoDesconto, $descontoInput)'),
    'O servidor não rejeita desconto percentual de 100% ou mais.'
);
assertPropostaEdicao(
    str_contains($actions, '$precosAntigos[$chave]')
    && str_contains($actions, 'array_key_exists($chave, $precosAntigos)'),
    'A edição não preserva os preços aplicados aos serviços existentes.'
);
assertPropostaEdicao(
    str_contains($comercial, 'comercial/nova?id=')
    && str_contains($detalhes, 'comercial/nova?id='),
    'As telas de propostas não expõem a edição do rascunho.'
);

echo "propostas_edicao_validacao_test: OK\n";
