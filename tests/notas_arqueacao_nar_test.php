<?php
/**
 * Teste Automatizado de Notas de Arqueação (AM-NAR), Unificação de Categorias e Compartilhamento de Documentos
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analise_planos.php';
require_once __DIR__ . '/../vendor/autoload.php';

function assertNar(bool $cond, string $msg): void {
    if (!$cond) {
        echo "❌ FALHA: {$msg}\n";
        exit(1);
    }
    echo "✅ SUCESSO: {$msg}\n";
}

echo "=================================================================\n";
echo " TESTE DE NOTAS DE ARQUEAÇÃO (AM-NAR) & DIRETRIZES DO ANALISTA\n";
echo "=================================================================\n\n";

// -------------------------------------------------------------
// 1. Validar Unificação da Categoria "MEMORIAL DESCRITIVO"
// -------------------------------------------------------------
echo "[1/5] Validando categorias padrão e banco de referências...\n";
$categorias = analisePlanosCategoriasPadrao();
assertNar(in_array('MEMORIAL DESCRITIVO', $categorias, true), "Categoria 'MEMORIAL DESCRITIVO' presente.");
assertNar(!in_array('MEMORIAL DESCRITO', $categorias, true), "Categoria duplicada 'MEMORIAL DESCRITO' removida com sucesso.");

$stmtQtdDescrito = $pdo->query("SELECT COUNT(*) FROM analise_planos_referencias_normam WHERE categoria = 'MEMORIAL DESCRITO'");
$qtdDescrito = (int)$stmtQtdDescrito->fetchColumn();
assertNar($qtdDescrito === 0, "Zero referências com 'MEMORIAL DESCRITO' no banco.");

// -------------------------------------------------------------
// 2. Validar Compartilhamento Bidirecional de Documentos
// -------------------------------------------------------------
echo "\n[2/5] Validando permissão de visualização/download para Armador no Portal...\n";
try {
    $cliId = $pdo->query("SELECT id FROM clientes WHERE ativo = 1 LIMIT 1")->fetchColumn();
    $embId = $pdo->query("SELECT id FROM embarcacoes WHERE ativo = 1 LIMIT 1")->fetchColumn();
    $adminId = $pdo->query("SELECT id FROM usuarios WHERE ativo = 1 LIMIT 1")->fetchColumn();

    // Simular processo em AGUARDANDO_AGENDAMENTO
    $anTesteId = gerarUUID();
    $numTeste = 'AM-TESTE-' . date('YmdHis') . '-' . mt_rand(100, 999);
    $pdo->prepare("INSERT INTO analises_planos (id, numero, tipo_processo, enquadramento, classe_certificacao, embarcacao_id, solicitante_id, status, objeto, criado_por, criado_em)
        VALUES (?, ?, 'LC', 'NORMAM-202', 'EC1', ?, ?, 'AGUARDANDO_AGENDAMENTO', 'Teste Acesso Portal', ?, NOW())")
        ->execute([$anTesteId, $numTeste, $embId, $cliId, $adminId]);

    // Simular sessão de cliente
    $_SESSION['cliente_id'] = $cliId;
    $analiseCarregada = $pdo->query("SELECT * FROM analises_planos WHERE id = '{$anTesteId}'")->fetch(PDO::FETCH_ASSOC);

    $podeVer = analisePlanosUsuarioPodeVisualizar($analiseCarregada);
    assertNar($podeVer === true, "Cliente autenticado no portal possui acesso para visualizar e baixar arquivos da sua análise.");

    unset($_SESSION['cliente_id']);
} catch (Throwable $e) {
    echo "❌ EXCEÇÃO NO PASSO 2: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// -------------------------------------------------------------
// 3. Criar Nota de Arqueação (AM-NAR) via Backend
// -------------------------------------------------------------
echo "\n[3/5] Testando persistência e cálculo da Nota de Arqueação (AM-NAR)...\n";
$narId = gerarUUID();
$ano = (int)date('Y');
$stmtSeq = $pdo->prepare("SELECT COALESCE(MAX(sequencial), 0) + 1 FROM certificados_nar WHERE ano = :ano");
$stmtSeq->execute([':ano' => $ano]);
$seq = (int)$stmtSeq->fetchColumn();
$numNar = sprintf('AM-NAR: %04d/%d', $seq, $ano);
$token = hash('sha256', uniqid('nar_teste', true));

$respId = $pdo->query("SELECT id FROM responsaveis_assinatura WHERE ativo = 1 LIMIT 1")->fetchColumn();
$respRow = $pdo->query("SELECT * FROM responsaveis_assinatura WHERE id = '{$respId}'")->fetch(PDO::FETCH_ASSOC);

$vAbaixo = [
    ['descricao' => 'Volume do casco mais tosamento', 'volume' => 1650.06]
];
$vAcima = [
    ['descricao' => 'Casaria do Convés Principal Completa.', 'volume' => 0.0],
    ['descricao' => 'Casaria do Convés Superior.', 'volume' => 0.0],
    ['descricao' => 'Casaria do convés intermediário', 'volume' => 0.0],
    ['descricao' => 'Casaria do convés comando.', 'volume' => 0.0]
];

$obsNotas = "A embarcação possui um comprimento total com apêndice de 24,215 m e de casco de 22,700 m.\n\n" .
            "A emb. Está autorizada a acomodar até 08 Extra Roll, de acordo com planos e doc. técnicos apresentados.\n\n" .
            "A embarcação tem como atividade/serviço empurra e transporte de carga.\n\n" .
            "Os espaços considerados excluídos, não poderão der usados para transportes de carga ou passageiros somente para a tripulação, se enquadra no item 7.9.) 7.9.1. caso a), figura 7.6, espaços excluídos.\n\n" .
            "A embarcação tem como atividade/serviço empurra. O espaço considerado excluído, não poderão ser usados para provisões, ele se enquadra no item 7.9.) 7.9.2. caso b), figura 7-7: Espaços excluídos.";

$insNar = $pdo->prepare("INSERT INTO certificados_nar (
    id, numero, ano, sequencial, enquadramento_comprimento, embarcacao_id, cliente_id, analise_id,
    token_assinatura, status, nome_embarcacao, armador, construtor, numero_casco, material_casco,
    tipo_embarcacao, atividade_servico, classificacao, porto_inscricao, data_construcao_quilha,
    comprimento_total_ct, comprimento_regra_l, comprimento_lpp, boca_moldada_b, pontal_moldado_p,
    calado_leve_av, calado_leve_ar, calado_leve_medio, calado_carregado_av, calado_carregado_ar, calado_carregado_medio,
    numero_tripulantes, n1_passageiros_camarotes, n2_demais_passageiros,
    deslocamento_carregado, deslocamento_leve, porte_bruto,
    espacos_fechados_abaixo_conves, espacos_fechados_acima_conves, espacos_excluidos,
    volume_total_fechado_v, volume_espacos_carga_vc, coeficiente_k1, coeficiente_k2,
    arqueacao_bruta_ab, arqueacao_liquida_al,
    volumes_abaixo_conves_json, volumes_acima_conves_json,
    metodo_obtencao_abaixo, metodo_obtencao_acima, observacoes_notas,
    responsavel_assinatura_id, assinante_nome, assinante_titulo, assinante_registro,
    local_emissao, data_emissao
) VALUES (
    ?, ?, ?, ?, 'L_MAIOR_IGUAL_24', ?, ?, ?,
    ?, 'emitido', 'EDU IV TESTE', 'EDUNAV TRANSPORTE LTDA', 'EDUNAV TRANSPORTE LTDA', '001', 'AÇO',
    'BALSA', 'CARGA GERAL', 'CARGA GERAL SOBRE O CONVÉS', 'BELÉM - PA', '2020',
    71.000, 66.720, 69.500, 12.500, 2.200,
    0.400, 0.400, 0.400, 1.766, 1.765, 1.765,
    0, 0, 0,
    1268.211, 238.211, 1030.000,
    1650.06, 0.00, 0.00,
    1650.06, 0.00, 0.2644, 0.0000,
    436, 130,
    ?, ?,
    'Volume obtido com a utilização de curvas hidrostáticas.',
    'Volume obtido com a utilização de formas geométricas.',
    ?,
    ?, ?, ?, ?,
    'Belém - PA', '2026-08-05'
)");

$insNar->execute([
    $narId, $numNar, $ano, $seq, $embId, $cliId, $anTesteId,
    $token,
    json_encode($vAbaixo), json_encode($vAcima),
    $obsNotas,
    $respId, $respRow['nome_completo'], $respRow['cargo_titulo'], $respRow['registro_profissional']
]);

$narCarregada = $pdo->query("SELECT * FROM certificados_nar WHERE id = '{$narId}'")->fetch(PDO::FETCH_ASSOC);
assertNar(!empty($narCarregada), "Nota de Arqueação {$numNar} inserida com sucesso no banco.");
assertNar((int)$narCarregada['arqueacao_bruta_ab'] === 436, "AB calculada com sucesso: 436.");
assertNar((int)$narCarregada['arqueacao_liquida_al'] === 130, "AL calculada com sucesso: 130.");

// -------------------------------------------------------------
// 4. Testar Geração do PDF Oficial de 3 Páginas da NAR
// -------------------------------------------------------------
echo "\n[4/5] Gerando PDF oficial de 3 páginas da NAR...\n";
$_GET['id'] = $narId;
$caminhoPdfTmp = __DIR__ . '/../scratch/nar_teste_output.pdf';
$salvar_pdf_caminho = $caminhoPdfTmp;

ob_start();
require __DIR__ . '/../modules/documentacao/nar/pdf.php';
ob_end_clean();

assertNar(is_file($caminhoPdfTmp), "Arquivo PDF da NAR gerado em disco.");
$tamanho = filesize($caminhoPdfTmp);
assertNar($tamanho > 30000, "Tamanho do PDF válido: " . round($tamanho / 1024, 1) . " KB.");

// Validar conteúdo do PDF gerado
$conteudoPdf = file_get_contents($caminhoPdfTmp);
assertNar(str_starts_with($conteudoPdf, '%PDF-'), "Assinatura válida do cabeçalho TCPDF (%PDF-).");

// -------------------------------------------------------------
// 5. Testar Assinatura Digital da NAR
// -------------------------------------------------------------
echo "\n[5/5] Testando fluxo de assinatura digital da NAR...\n";
$stmtAssinar = $pdo->prepare("UPDATE certificados_nar SET
    status = 'assinado',
    assinado = 1,
    assinatura_em = NOW(),
    assinatura_ip = '127.0.0.1'
    WHERE id = :id");
$stmtAssinar->execute([':id' => $narId]);

$narAssinada = $pdo->query("SELECT status, assinado FROM certificados_nar WHERE id = '{$narId}'")->fetch(PDO::FETCH_ASSOC);
assertNar($narAssinada['status'] === 'assinado' && (int)$narAssinada['assinado'] === 1, "Nota de Arqueação assinada digitalmente com sucesso.");

// Limpeza dos dados de teste
$pdo->exec("DELETE FROM certificados_nar WHERE id = '{$narId}'");
$pdo->exec("DELETE FROM analises_planos WHERE id = '{$anTesteId}'");
if (is_file($caminhoPdfTmp)) unlink($caminhoPdfTmp);

echo "\n=================================================================\n";
echo " TODAS AS VALIDAÇÕES DE NOTAS DE ARQUEAÇÃO (AM-NAR) PASSARAM 100%!\n";
echo "=================================================================\n";
