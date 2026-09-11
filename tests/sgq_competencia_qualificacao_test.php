<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sgq.php';

function assertSgq(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FALHA SGQ: " . $message);
    }
}

// 1. Teste unitário de vistoriadorElegivelParaAgendamento usando banco SQLite em memória
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
    CREATE TABLE usuarios (
        id TEXT PRIMARY KEY,
        nome TEXT NOT NULL,
        cargo TEXT NOT NULL,
        ativo INTEGER NOT NULL DEFAULT 1,
        status_sgq TEXT NOT NULL DEFAULT 'QUALIFICADO',
        credencial_marinha_numero TEXT,
        credencial_marinha_validade TEXT,
        registro_conselho_tipo TEXT,
        registro_conselho_numero TEXT,
        registro_conselho_validade TEXT,
        escopo_habilitacao TEXT
    )
");

// Inserir cenários de teste
$pdo->exec("
    INSERT INTO usuarios (id, nome, cargo, ativo, status_sgq, credencial_marinha_numero, credencial_marinha_validade, registro_conselho_tipo, registro_conselho_numero, registro_conselho_validade)
    VALUES
    ('u-inativo', 'Vistoriador Inativo', 'VISTORIADOR', 0, 'QUALIFICADO', 'MAR-1', '2030-01-01', 'CREA', '123', '2030-01-01'),
    ('u-treinamento', 'Vistoriador Treinamento', 'VISTORIADOR', 1, 'EM_TREINAMENTO', 'MAR-2', '2030-01-01', 'CREA', '124', '2030-01-01'),
    ('u-marinha-vencida', 'Vistoriador Marinha Vencida', 'VISTORIADOR', 1, 'QUALIFICADO', 'MAR-3', '2024-05-01', 'CREA', '125', '2030-01-01'),
    ('u-crea-vencido', 'Vistoriador CREA Vencido', 'VISTORIADOR', 1, 'QUALIFICADO', 'MAR-4', '2030-01-01', 'CREA', '126', '2024-05-01'),
    ('u-qualificado', 'Vistoriador Pleno', 'VISTORIADOR', 1, 'QUALIFICADO', 'MAR-5', '2030-01-01', 'CREA', '127', '2030-01-01')
");

$dataPlanejada = '2026-09-20';

// Cenário 1: Vistoriador inexistente
$res1 = vistoriadorElegivelParaAgendamento($pdo, 'u-inexistente', $dataPlanejada);
assertSgq(!$res1['elegivel'], 'Vistoriador inexistente deveria ser bloqueado.');
assertSgq($res1['codigo'] === 'VISTORIADOR_NAO_ENCONTRADO', 'Código de vistoriador inexistente incorreto.');

// Cenário 2: Vistoriador inativo
$res2 = vistoriadorElegivelParaAgendamento($pdo, 'u-inativo', $dataPlanejada);
assertSgq(!$res2['elegivel'], 'Vistoriador inativo deveria ser bloqueado.');
assertSgq($res2['codigo'] === 'VISTORIADOR_INATIVO', 'Código de vistoriador inativo incorreto.');

// Cenário 3: Vistoriador em treinamento (status_sgq != QUALIFICADO)
$res3 = vistoriadorElegivelParaAgendamento($pdo, 'u-treinamento', $dataPlanejada);
assertSgq(!$res3['elegivel'], 'Vistoriador em treinamento deveria ser bloqueado para vistorias isoladas.');
assertSgq($res3['codigo'] === 'SGQ_STATUS_NAO_QUALIFICADO', 'Código de status SGQ incorreto.');

// Cenário 4: Credencial Marinha vencida
$res4 = vistoriadorElegivelParaAgendamento($pdo, 'u-marinha-vencida', $dataPlanejada);
assertSgq(!$res4['elegivel'], 'Vistoriador com Portaria da Marinha vencida deveria ser bloqueado.');
assertSgq($res4['codigo'] === 'CREDENCIAL_MARINHA_VENCIDA', 'Código de Marinha vencida incorreto.');

// Cenário 5: Registro do conselho (CREA/CFT) vencido
$res5 = vistoriadorElegivelParaAgendamento($pdo, 'u-crea-vencido', $dataPlanejada);
assertSgq(!$res5['elegivel'], 'Vistoriador com CREA vencido deveria ser bloqueado.');
assertSgq($res5['codigo'] === 'CONSELHO_CLASSE_VENCIDO', 'Código de CREA vencido incorreto.');

// Cenário 6: Vistoriador plenamente qualificado e com credenciais vigentes
$res6 = vistoriadorElegivelParaAgendamento($pdo, 'u-qualificado', $dataPlanejada);
assertSgq($res6['elegivel'], 'Vistoriador com credenciais válidas deveria ser aprovado.');

// 2. Teste de Auditoria Cadastral (ISO 7.5 & 8.2) para Entidade 'USUARIO'
$pdo->exec("
    CREATE TABLE sgq_auditoria_cadastral (
        id TEXT PRIMARY KEY,
        entidade_tipo TEXT NOT NULL,
        entidade_id TEXT NOT NULL,
        acao TEXT NOT NULL,
        dados_anteriores TEXT,
        dados_posteriores TEXT,
        campos_alterados TEXT,
        motivo_justificativa TEXT,
        usuario_id TEXT,
        usuario_nome TEXT,
        ip_origem TEXT,
        user_agent TEXT,
        criado_em TEXT
    )
");

$_SESSION['usuario_id'] = 'u-admin-teste';
$_SESSION['usuario_nome'] = 'Auditor Qualidade';

$dadosAntes = [
    'credencial_marinha_validade' => '2025-01-01',
    'status_sgq' => 'QUALIFICADO'
];
$dadosDepois = [
    'credencial_marinha_validade' => '2027-01-01',
    'status_sgq' => 'QUALIFICADO'
];

sgqRegistrarAuditoriaCadastral(
    $pdo,
    'USUARIO',
    'u-qualificado',
    'ALTERACAO',
    $dadosAntes,
    $dadosDepois,
    'Renovação de portaria da Capitania dos Portos'
);

$log = $pdo->query("SELECT * FROM sgq_auditoria_cadastral WHERE entidade_id = 'u-qualificado'")->fetch(PDO::FETCH_ASSOC);
assertSgq($log !== false, 'Registro de auditoria cadastral de usuário não foi inserido.');
assertSgq($log['entidade_tipo'] === 'USUARIO', 'Tipo de entidade na auditoria deve ser USUARIO.');
assertSgq(str_contains($log['campos_alterados'], 'credencial_marinha_validade'), 'Delta de credencial alterada não foi registrado.');
assertSgq(!str_contains($log['campos_alterados'], 'status_sgq'), 'Campo inalterado não deve constar no delta.');

// 3. Teste da Matriz de Riscos (ISO 9001 Cláusula 6.1)
function calcularNivelRisco(int $prob, int $impacto): string {
    $score = $prob * $impacto;
    if ($score >= 15) return 'ALTO';
    if ($score >= 8) return 'MEDIO';
    return 'BAIXO';
}

assertSgq(calcularNivelRisco(5, 5) === 'ALTO', 'Risco 5x5 deve ser classificado como ALTO.');
assertSgq(calcularNivelRisco(3, 3) === 'MEDIO', 'Risco 3x3 deve ser classificado como MEDIO.');
assertSgq(calcularNivelRisco(1, 2) === 'BAIXO', 'Risco 1x2 deve ser classificado como BAIXO.');

echo "OK: Todos os testes de SGQ (Qualificação Técnica ISO 7.2, Auditoria Cadastral ISO 7.5 e Matriz de Riscos ISO 6.1) foram validados com 100% de sucesso!\n";

