<?php
/**
 * Teste de Validação: Busca no Catálogo NORMAM-202 e Eliminação de HY093 em Módulos
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function assertBusca(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException("FALHA: " . $mensagem);
    }
}

echo "=== TESTE: BUSCA NORMAM-202 E HIGIENIZAÇÃO DE PARÂMETROS PDO ===\n\n";

// 1. Validar busca SQL por 'prumo' em exigencias_catalogo
$filtro_busca = 'prumo';
$where = ["1 = 1", "e.ativo = 1"];
$params = [];
$where[] = "(e.codigo_interno LIKE :busca1 OR e.descricao LIKE :busca2 OR e.item_normam LIKE :busca3)";
$termo = '%' . $filtro_busca . '%';
$params[':busca1'] = $termo;
$params[':busca2'] = $termo;
$params[':busca3'] = $termo;

$sql = "SELECT e.*, c.nome AS categoria_nome 
        FROM exigencias_catalogo e
        LEFT JOIN exigencias_categorias c ON e.categoria_id = c.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY e.obrigatoria DESC, e.exige_foto DESC, e.codigo_interno ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

assertBusca(count($itens) >= 1, "A busca por 'prumo' deveria retornar pelo menos 1 item.");
assertBusca($itens[0]['codigo_interno'] === 'EX-320', "O item encontrado deveria ser EX-320.");
assertBusca(str_contains(mb_strtolower($itens[0]['descricao']), 'prumo'), "A descrição de EX-320 deveria conter 'prumo'.");
echo "[OK] Consulta SQL de busca por 'prumo' executada com sucesso (Item EX-320: {$itens[0]['descricao']}).\n";

// 2. Validar que o arquivo normam202.php não possui o erro de placeholder duplicado :busca
$normamCode = file_get_contents(__DIR__ . '/../modules/configuracoes/normam202.php');
assertBusca(!str_contains($normamCode, 'e.codigo_interno LIKE :busca OR e.descricao LIKE :busca'), "normam202.php ainda reutiliza o mesmo :busca sem índice.");
assertBusca(str_contains($normamCode, ':busca1') && str_contains($normamCode, ':busca2') && str_contains($normamCode, ':busca3'), "normam202.php não utiliza parâmetros distintos :busca1, :busca2, :busca3.");
assertBusca(str_contains($normamCode, 'id="filtro_busca_input"'), "Campo de busca não possui o ID para filtro dinâmico.");
assertBusca(str_contains($normamCode, 'class="linha-exigencia"'), "Linhas da tabela não possuem a classe de filtro dinâmico.");
assertBusca(str_contains($normamCode, 'data-busca='), "Linhas da tabela não possuem o atributo data-busca.");
assertBusca(str_contains($normamCode, 'removerAcentos'), "Script de filtro instantâneo sem tratamento de acentuação.");
echo "[OK] normam202.php validado: parâmetros PDO corrigidos e suporte a filtro dinâmico em tempo real ativo.\n";

// 3. Validar analises_planos/index.php
$analisesCode = file_get_contents(__DIR__ . '/../modules/analises_planos/index.php');
assertBusca(!str_contains($analisesCode, 'ap.numero LIKE :busca OR e.nome LIKE :busca'), "analises_planos/index.php ainda reutiliza :busca.");
assertBusca(str_contains($analisesCode, ':busca1') && str_contains($analisesCode, ':busca4'), "analises_planos/index.php não utiliza :busca1 a :busca4.");
echo "[OK] analises_planos/index.php validado com parâmetros distintos.\n";

// 4. Validar módulos SGQ e confirmação de remoção do módulo órfão Contratos
$auditoriaCode = file_get_contents(__DIR__ . '/../modules/sgq/auditoria.php');
assertBusca(!str_contains($auditoriaCode, 'usuario_nome LIKE :busca OR motivo_justificativa LIKE :busca'), "sgq/auditoria.php ainda reutiliza :busca.");
$rncCode = file_get_contents(__DIR__ . '/../modules/sgq/nao_conformidades.php');
assertBusca(!str_contains($rncCode, 'r.numero_rnc LIKE :busca OR r.titulo LIKE :busca'), "sgq/nao_conformidades.php ainda reutiliza :busca.");
$riscosCode = file_get_contents(__DIR__ . '/../modules/sgq/riscos.php');
assertBusca(!str_contains($riscosCode, 'codigo_risco LIKE :busca OR descricao_risco LIKE :busca'), "sgq/riscos.php ainda reutiliza :busca.");
assertBusca(!is_dir(__DIR__ . '/../modules/contratos'), "Diretório órfão modules/contratos não foi removido.");
echo "[OK] Módulos SGQ higienizados e módulo legado contratos devidamente removido.\n\n";

echo "TODOS OS TESTES DE BUSCA E HIGIENIZAÇÃO PDO PASSARAM COM SUCESSO!\n";
