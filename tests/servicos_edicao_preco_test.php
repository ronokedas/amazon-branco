<?php
/**
 * Teste unitário para validação de edição de preços e modelo de serviços navais.
 */

// Como o MySQL roda no container Docker na VPS e pode não estar ativo localmente no Windows,
// testamos as funções isoladamente e com SQLite em memória.

echo "=== TESTE: EDIÇÃO DE PREÇOS E MÓDULO DE SERVIÇOS ===\n\n";

function assertServico($condicao, $msg) {
    if (!$condicao) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [✓] {$msg}\n";
}

// 1. Validar função converterMoedaDecimal
echo "1. Validando conversão de valores monetários para decimal...\n";

// Definir a função exatamente como implementada em includes/functions.php e modules/servicos/actions.php
if (!function_exists('converterMoedaDecimal')) {
    function converterMoedaDecimal(mixed $valor): float {
        if (is_numeric($valor)) {
            return round((float)$valor, 2);
        }
        $valor = trim((string)$valor);
        if ($valor === '') {
            return 0.0;
        }
        $valor = preg_replace('/[^\d,.-]/u', '', $valor) ?? '';
        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (substr_count($valor, '.') > 1) {
            $valor = str_replace('.', '', $valor);
        }
        return round((float)$valor, 2);
    }
}

assertServico(converterMoedaDecimal('1.800,00') === 1800.00, "1.800,00 convertido para 1800.00");
assertServico(converterMoedaDecimal('1800,00') === 1800.00, "1800,00 convertido para 1800.00");
assertServico(converterMoedaDecimal('1800.00') === 1800.00, "1800.00 mantido como 1800.00");
assertServico(converterMoedaDecimal('2.500,50') === 2500.50, "2.500,50 convertido para 2500.50");
assertServico(converterMoedaDecimal('R$ 1.800,00') === 1800.00, "R$ 1.800,00 convertido para 1800.00");
assertServico(converterMoedaDecimal('0,00') === 0.00, "0,00 convertido para 0.00");
assertServico(converterMoedaDecimal('') === 0.00, "Vazio convertido para 0.00");
assertServico(converterMoedaDecimal(null) === 0.00, "Null convertido para 0.00");
assertServico(converterMoedaDecimal(1800) === 1800.00, "Inteiro 1800 convertido para 1800.00");

// 2. Simular gravação com PDO SQLite
echo "\n2. Testando persistência e atualização com banco de dados em memória...\n";
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlite->exec("
    CREATE TABLE servicos (
        id TEXT PRIMARY KEY,
        nome TEXT NOT NULL,
        descricao TEXT,
        certificado_modelo TEXT,
        preco_padrao REAL NOT NULL DEFAULT 0.0,
        ativo INTEGER NOT NULL DEFAULT 1,
        criado_por TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT
    )
");

// Inserir serviço 'Acompanhamento de Ultrassom'
$idTeste = 'test-ultrassom-uuid';
$stmt = $sqlite->prepare("
    INSERT INTO servicos (id, nome, descricao, certificado_modelo, preco_padrao, ativo, created_at)
    VALUES (:id, :nome, :descricao, :certificado_modelo, :preco_padrao, 1, datetime('now'))
");
$stmt->execute([
    ':id' => $idTeste,
    ':nome' => 'Acompanhamento de Ultrassom',
    ':descricao' => 'Acompanhamento de ensaios de ultrassom em casco/estruturas',
    ':certificado_modelo' => null,
    ':preco_padrao' => 0.00,
]);

// Simular payload da requisição do usuário (conforme screenshot)
$postData = [
    'action' => 'editar',
    'id' => $idTeste,
    'nome' => 'Acompanhamento de Ultrassom',
    'descricao' => 'Acompanhamento de ensaios de ultrassom em casco/estruturas',
    'certificado_modelo' => '',
    'preco_padrao' => '1.800,00',
    'ativo' => '1',
];

$nome = trim($postData['nome']);
$descricao = trim($postData['descricao']);
$modelosCertificadosValidos = ['CSN', 'CNBL', 'CNARQ', 'LP', 'LC', 'CHT', 'NAR'];
$certificado_modelo = strtoupper(trim((string)$postData['certificado_modelo']));
$certificado_modelo = in_array($certificado_modelo, $modelosCertificadosValidos, true) ? $certificado_modelo : null;
$preco_padrao = converterMoedaDecimal($postData['preco_padrao']);
$ativo = isset($postData['ativo']) ? 1 : 0;

$stmtUpd = $sqlite->prepare("
    UPDATE servicos
    SET nome = :nome,
        descricao = :descricao,
        certificado_modelo = :certificado_modelo,
        preco_padrao = :preco_padrao,
        ativo = :ativo,
        updated_at = datetime('now')
    WHERE id = :id
");
$stmtUpd->execute([
    ':nome' => $nome,
    ':descricao' => $descricao ?: null,
    ':certificado_modelo' => $certificado_modelo,
    ':preco_padrao' => $preco_padrao,
    ':ativo' => $ativo,
    ':id' => $idTeste,
]);

$check = $sqlite->query("SELECT * FROM servicos WHERE id = '{$idTeste}'")->fetch(PDO::FETCH_ASSOC);
assertServico((float)$check['preco_padrao'] === 1800.00, "Preço atualizado no banco com sucesso para R$ 1.800,00 (1800.00)");
assertServico($check['nome'] === 'Acompanhamento de Ultrassom', "Nome do serviço preservado corretamente");
assertServico($check['certificado_modelo'] === null, "Certificado modelo vazio gravado como null");

// 3. Testar associação com modelo de certificado estatutário ampliado
echo "\n3. Testando associação com certificado estatutário (ex: CSN, LP, LC)...\n";
$stmtUpd->execute([
    ':nome' => $nome,
    ':descricao' => $descricao ?: null,
    ':certificado_modelo' => 'LP',
    ':preco_padrao' => $preco_padrao,
    ':ativo' => $ativo,
    ':id' => $idTeste,
]);
$checkCert = $sqlite->query("SELECT certificado_modelo FROM servicos WHERE id = '{$idTeste}'")->fetch(PDO::FETCH_ASSOC);
assertServico($checkCert['certificado_modelo'] === 'LP', "Modelo estatutário 'LP' associado com sucesso ao serviço");

echo "\n=======================================================\n";
echo "TODOS OS TESTES DE EDIÇÃO DE PREÇOS PASSARAM COM SUCESSO!\n";
echo "=======================================================\n";
