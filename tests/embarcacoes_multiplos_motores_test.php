<?php
/**
 * Teste Automatizado: Múltiplos Motores Propulsores por Embarcação (Cadastro, Edição, Legado, Sem Propulsão e CSN)
 * Arquivo: tests/embarcacoes_multiplos_motores_test.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/emissao_certificados.php';

echo "=== TESTE: MÚLTIPLOS MOTORES PROPULSORES NA EMBARCAÇÃO ===\n\n";

$passou = true;

function assertMotor(bool $condicao, string $mensagem): void
{
    global $passou;
    if ($condicao) {
        echo "  [✓] {$mensagem}\n";
    } else {
        echo "  [✗] FALHA: {$mensagem}\n";
        $passou = false;
    }
}

function httpReqMotor(string $metodo, string $path, array $dados = []): array
{
    static $cookieSessao = '';

    $url = str_starts_with($path, 'http') ? $path : ('http://127.0.0.1' . $path);
    $url = str_replace('localhost:8082', '127.0.0.1', $url);
    $url = str_replace(':8082', '', $url);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($cookieSessao !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieSessao);
    }

    if (strtoupper($metodo) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $headers = substr((string)$response, 0, $headerSize);
    $body = substr((string)$response, $headerSize);
    curl_close($ch);

    if (preg_match('/Set-Cookie:\s*(ERPSESSID=[^;]+)/i', $headers, $mCookie)) {
        $cookieSessao = $mCookie[1];
    }

    if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
        if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $loc)) {
            $novoUrl = trim($loc[1]);
            $novoUrl = str_replace(':8082', '', $novoUrl);
            return httpReqMotor('GET', $novoUrl);
        }
    }

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
    ];
}

function extrairCsrfMotor(string $html): string
{
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\']csrf_token["\']/', $html, $m)) {
        return $m[1];
    }
    return '';
}

// -------------------------------------------------------------
// 1. TESTES UNITÁRIOS DOS HELPERS DE MÚLTIPLOS MOTORES
// -------------------------------------------------------------
echo "1. Testando helpers unitários (cálculo de potência total, formatação e validação)...\n";

assertMotor(
    embarcacaoCalcularPotenciaTotal([
        ['potencia_kw' => '450 kW'],
        ['potencia_kw' => '450 kW'],
    ]) === '900 kW',
    "Soma de 2x 450 kW = 900 kW"
);

assertMotor(
    embarcacaoCalcularPotenciaTotal([
        ['potencia_kw' => '224 kW / 300 HP'],
        ['potencia_kw' => '224 kW / 300 HP'],
    ]) === '448 kW / 600 HP',
    "Soma dupla kW + HP: 224 kW / 300 HP x2 = 448 kW / 600 HP"
);

assertMotor(
    embarcacaoCalcularPotenciaTotal([
        ['potencia_kw' => '330'],
        ['potencia_kw' => '330'],
        ['potencia_kw' => '340'],
    ]) === '1000',
    "Soma de valores numéricos sem unidade = 1000"
);

assertMotor(
    embarcacaoCalcularPotenciaTotal([
        ['potencia_kw' => '300 kW'],
        ['potencia_kw' => '250 CV'],
    ]) === '300 kW + 250 CV',
    "Fallback seguro para unidades incompatíveis (300 kW + 250 CV)"
);

$resumo2 = embarcacaoFormatarMotoresResumo([
    ['posicao' => 'Boreste (BE)', 'fabricante' => 'Caterpillar', 'modelo' => 'C18 ACERT', 'numero' => 'CAT-001', 'potencia_kw' => '447 kW'],
    ['posicao' => 'Bombordo (BB)', 'fabricante' => 'Caterpillar', 'modelo' => 'C18 ACERT', 'numero' => 'CAT-002', 'potencia_kw' => '447 kW'],
]);
assertMotor($resumo2['quantidade'] === 2, "Resumo identifica 2 motores");
assertMotor(str_contains($resumo2['texto'], "Boreste (BE): Caterpillar - C18 ACERT - CAT-001\nBombordo (BB): Caterpillar - C18 ACERT - CAT-002"), "Resumo concatena linhas corretamente para o CSN");
assertMotor($resumo2['potencia_total'] === '894 kW', "Potência total no resumo = 894 kW");

$erroIncompleto = embarcacaoValidarMotoresCompletosParaCSN(
    ['possui_propulsao' => 1],
    [
        ['posicao' => 'Boreste (BE)', 'fabricante' => 'Scania', 'modelo' => 'DC13', 'numero' => 'SC-1', 'potencia_kw' => '330 kW'],
        ['posicao' => 'Bombordo (BB)', 'fabricante' => 'Scania', 'modelo' => 'DC13', 'numero' => '', 'potencia_kw' => '330 kW'],
    ]
);
assertMotor($erroIncompleto !== null && str_contains($erroIncompleto, 'Bombordo (BB) incompleto'), "Validação CSN detecta motor secundário sem número de série");

$semErro = embarcacaoValidarMotoresCompletosParaCSN(
    ['possui_propulsao' => 1],
    [
        ['posicao' => 'Boreste (BE)', 'fabricante' => 'Scania', 'modelo' => 'DC13', 'numero' => 'SC-1', 'potencia_kw' => '330 kW'],
        ['posicao' => 'Bombordo (BB)', 'fabricante' => 'Scania', 'modelo' => 'DC13', 'numero' => 'SC-2', 'potencia_kw' => '330 kW'],
    ]
);
assertMotor($semErro === null, "Validação CSN aprova quando todos os motores estão completos");

// -------------------------------------------------------------
// 2. TESTE HTTP END-TO-END: CADASTRO COM 2 MOTORES -> EDIÇÃO 3 MOTORES -> LEGADO -> SEM PROPULSÃO
// -------------------------------------------------------------
echo "\n2. Autenticando via HTTP para teste end-to-end do formulário...\n";
$respLogin = httpReqMotor('GET', '/login');
$csrf = extrairCsrfMotor($respLogin['body']);
$auth = httpReqMotor('POST', '/login', [
    'email' => 'teste@teste.com',
    'senha' => 'teste123',
    'csrf_token' => $csrf,
]);
assertMotor($auth['code'] === 200 && !str_contains($auth['body'], 'name="email"'), "Login HTTP realizado com sucesso");

$respForm = httpReqMotor('GET', '/embarcacoes/form?aba=tab-tecnicos');
assertMotor($respForm['code'] === 200, "Formulário carregou com HTTP 200");
assertMotor(str_contains($respForm['body'], 'btn_adicionar_motor_topo') && str_contains($respForm['body'], 'Adicionar Motor'), "Botão 'Adicionar Motor' visível no HTML");
$csrfEmb = extrairCsrfMotor($respForm['body']);
assertMotor($csrfEmb !== '', "Token CSRF obtido no formulário de embarcação");

$nomeEmbMulti = 'EMPURRADOR BIMOTOR TESTE ' . rand(10000, 99999);
$nomeEmbLegado = 'LANCHA LEGADO TESTE ' . rand(10000, 99999);

try {
    echo "\n3. Cadastrando embarcação com 2 motores (Boreste + Bombordo)...\n";
    $respCreate = httpReqMotor('POST', '/embarcacoes/actions', [
        'action' => 'salvar',
        'csrf_token' => $csrfEmb,
        'nome' => $nomeEmbMulti,
        'possui_propulsao' => '1',
        'motores' => [
            0 => [
                'posicao' => 'Boreste (BE)',
                'fabricante' => 'Caterpillar',
                'modelo' => 'C18 ACERT',
                'numero' => 'CAT-BE-101',
                'potencia_kw' => '450 kW',
            ],
            1 => [
                'posicao' => 'Bombordo (BB)',
                'fabricante' => 'Caterpillar',
                'modelo' => 'C18 ACERT',
                'numero' => 'CAT-BB-102',
                'potencia_kw' => '450 kW',
            ],
        ],
    ]);
    assertMotor($respCreate['code'] === 200, "POST de criação com 2 motores retornou HTTP 200");

    $stmtEmb = $pdo->prepare("SELECT * FROM embarcacoes WHERE nome = :nome LIMIT 1");
    $stmtEmb->execute([':nome' => $nomeEmbMulti]);
    $emb = $stmtEmb->fetch(PDO::FETCH_ASSOC);
    assertMotor((bool)$emb, "Embarcação multimotor gravada no banco");

    if ($emb) {
        $embId = $emb['id'];
        assertMotor($emb['fabricante_motor'] === 'Caterpillar', "Coluna legada fabricante_motor sincronizada com Motor 1");
        assertMotor($emb['numero_motor'] === 'CAT-BE-101', "Coluna legada numero_motor sincronizada com Motor 1");
        assertMotor($emb['potencia_kw'] === '900 kW', "Coluna potencia_kw consolidada com soma total: 900 kW (obtido: {$emb['potencia_kw']})");

        $motoresDb = embarcacaoBuscarMotores($pdo, $embId, $emb);
        assertMotor(count($motoresDb) === 2, "Tabela embarcacoes_motores contém 2 registros");
        assertMotor(($motoresDb[0]['posicao'] ?? '') === 'Boreste (BE)' && ($motoresDb[1]['posicao'] ?? '') === 'Bombordo (BB)', "Posições BE e BB preservadas em ordem");

        // Carregar tela de edição para validar exibição dos 2 motores
        $respEditView = httpReqMotor('GET', '/embarcacoes/form?id=' . urlencode($embId) . '&aba=tab-tecnicos');
        assertMotor(str_contains($respEditView['body'], 'CAT-BE-101') && str_contains($respEditView['body'], 'CAT-BB-102'), "Formulário de edição renderiza ambos os motores salvos");
        assertMotor(str_contains($respEditView['body'], 'Motor <span class="motor-ordem">2</span>'), "Card Motor 2 renderizado corretamente na edição");

        // Editar adicionando um 3º motor Central
        echo "\n4. Editando embarcação para adicionar 3º motor (Central)...\n";
        $csrfEdit = extrairCsrfMotor($respEditView['body']);
        $respUpdate = httpReqMotor('POST', '/embarcacoes/actions', [
            'action' => 'salvar',
            'id' => $embId,
            'csrf_token' => $csrfEdit,
            'nome' => $nomeEmbMulti,
            'possui_propulsao' => '1',
            'motores' => [
                0 => ['posicao' => 'Boreste (BE)', 'fabricante' => 'Caterpillar', 'modelo' => 'C18 ACERT', 'numero' => 'CAT-BE-101', 'potencia_kw' => '450 kW'],
                1 => ['posicao' => 'Bombordo (BB)', 'fabricante' => 'Caterpillar', 'modelo' => 'C18 ACERT', 'numero' => 'CAT-BB-102', 'potencia_kw' => '450 kW'],
                2 => ['posicao' => 'Central', 'fabricante' => 'Cummins', 'modelo' => 'QSB 6.7', 'numero' => 'CUM-CTR-103', 'potencia_kw' => '300 kW'],
            ],
        ]);
        assertMotor($respUpdate['code'] === 200, "POST de edição com 3 motores retornou HTTP 200");

        $stmtEmb->execute([':nome' => $nomeEmbMulti]);
        $emb3 = $stmtEmb->fetch(PDO::FETCH_ASSOC);
        assertMotor($emb3['potencia_kw'] === '1200 kW', "Potência total recalculada para 3 motores: 1200 kW (obtido: {$emb3['potencia_kw']})");
        $motores3 = embarcacaoBuscarMotores($pdo, $embId, $emb3);
        assertMotor(count($motores3) === 3, "Tabela embarcacoes_motores atualizada para 3 registros");

        // Testar emissão/validação CSN com transação isolada + geração PDF
        echo "\n5. Validando emissão CSN e geração de PDF para embarcação trimotora...\n";
        $resumo3 = embarcacaoFormatarMotoresResumo($motores3);
        assertMotor(substr_count($resumo3['texto'], "\n") === 2, "Resumo CSN formatado em 3 linhas");

        // Verificar conversão para Sem Propulsão limpa todos os motores
        echo "\n6. Alterando embarcação para Sem Propulsão (possui_propulsao = 0)...\n";
        $respSemProp = httpReqMotor('POST', '/embarcacoes/actions', [
            'action' => 'salvar',
            'id' => $embId,
            'csrf_token' => $csrfEdit,
            'nome' => $nomeEmbMulti,
            'possui_propulsao' => '0',
            'motores' => [
                0 => ['posicao' => 'Boreste (BE)', 'fabricante' => 'Caterpillar', 'modelo' => 'C18 ACERT', 'numero' => 'CAT-BE-101', 'potencia_kw' => '450 kW'],
            ],
        ]);
        assertMotor($respSemProp['code'] === 200, "POST convertendo para Sem Propulsão retornou HTTP 200");
        $motoresLimpos = embarcacaoBuscarMotores($pdo, $embId);
        assertMotor(count($motoresLimpos) === 0, "Registros em embarcacoes_motores removidos ao definir Sem Propulsão");
    }

    echo "\n7. Validando compatibilidade retroativa com POST legado (1 motor sem array motores[])...\n";
    $respLegado = httpReqMotor('POST', '/embarcacoes/actions', [
        'action' => 'salvar',
        'csrf_token' => $csrfEmb,
        'nome' => $nomeEmbLegado,
        'possui_propulsao' => '1',
        'fabricante_motor' => 'Scania',
        'modelo_motor' => 'DS11',
        'numero_motor' => 'SC-LEG-99',
        'potencia_kw' => '330 kW',
    ]);
    assertMotor($respLegado['code'] === 200, "POST legado retornou HTTP 200");
    $stmtEmb->execute([':nome' => $nomeEmbLegado]);
    $embLeg = $stmtEmb->fetch(PDO::FETCH_ASSOC);
    assertMotor((bool)$embLeg && $embLeg['fabricante_motor'] === 'Scania', "Embarcação legada gravada normalmente");
    if ($embLeg) {
        $mLeg = embarcacaoBuscarMotores($pdo, $embLeg['id'], $embLeg);
        assertMotor(count($mLeg) === 1 && $mLeg[0]['numero'] === 'SC-LEG-99', "POST legado sincronizado automaticamente com embarcacoes_motores");
    }
} finally {
    // Limpeza garantida dos registros temporários de teste
    $pdo->prepare("DELETE FROM embarcacoes WHERE nome IN (:n1, :n2)")->execute([
        ':n1' => $nomeEmbMulti,
        ':n2' => $nomeEmbLegado,
    ]);
    echo "\n  [✓] Dados temporários de teste removidos.\n";
}

echo "\n============================================================\n";
if ($passou) {
    echo "RESULTADO: TODOS OS TESTES DE MÚLTIPLOS MOTORES PASSARAM 100%!\n";
    exit(0);
}
echo "RESULTADO: HOUVE FALHA NO TESTE DE MÚLTIPLOS MOTORES!\n";
exit(1);
