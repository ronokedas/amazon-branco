<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../modules/dashboard/data.php';

echo "=== TESTE: PAINEL EXCLUSIVO DO VENDEDOR (COMERCIAL, OPERAÇÃO E FINANCEIRO) ===\n\n";

function assercao($cond, $msg) {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [✓] {$msg}\n";
}

try {
    $vendedorId = 'ab8d4e66-d57a-44b1-8d8c-9c928a2e68c5'; // Usuário any (VENDEDOR)
    $vendedorUser = $pdo->query("SELECT id, nome, email, cargo, versao_sessao FROM usuarios WHERE id = '{$vendedorId}'")->fetch(PDO::FETCH_ASSOC);
    if (!$vendedorUser) {
        throw new RuntimeException("Usuário vendedor não encontrado.");
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_cargo'] = 'VENDEDOR';
    $_SESSION['usuario_id'] = $vendedorUser['id'];
    $_SESSION['usuario_nome'] = $vendedorUser['nome'];
    $_SESSION['versao_sessao'] = (int)($vendedorUser['versao_sessao'] ?? 0);

    // 1. Validar dados do dashboard do vendedor
    echo "1. Validando agregação de dados comerciais em data.php...\n";
    $dashboard = dashboardGetCachedData($pdo, 'VENDEDOR', $vendedorId, true, 0);

    assercao(isset($dashboard['kpis']), "KPIs presentes no array do dashboard.");
    assercao(isset($dashboard['funil']), "Funil comercial presente.");
    assercao(isset($dashboard['fila_agendamentos']), "Fila de agendamentos prioritários presente.");
    assercao(isset($dashboard['proximos_agendamentos']), "Próximos agendamentos operacionais presentes.");
    assercao(isset($dashboard['financeiro_recentes']), "Controle financeiro de recebíveis presente.");
    assercao(isset($dashboard['recentes']), "Minhas propostas recentes presentes.");
    assercao(isset($dashboard['carteira_clientes']), "Carteira rápida de clientes presente.");

    // 2. Validar renderização do template vendedor.php
    echo "\n2. Validando renderização HTML da view modules/dashboard/views/vendedor.php...\n";
    ob_start();
    require __DIR__ . '/../modules/dashboard/views/vendedor.php';
    $html = ob_get_clean();

    assercao(str_contains($html, 'dash-vendedor-container'), "Container principal do vendedor renderizado.");
    assercao(str_contains($html, 'Nova Proposta') && str_contains($html, 'comercial/nova'), "Botão '+ Nova Proposta' presente.");
    assercao(str_contains($html, 'Agendar Vistoria') && str_contains($html, 'agendamentos/form'), "Botão '+ Agendar Vistoria' presente.");
    assercao(str_contains($html, 'Novo Cliente') && str_contains($html, 'clientes/form'), "Botão '+ Novo Cliente' presente.");
    assercao(str_contains($html, 'vendedor-kpi-card'), "Cards de KPIs comerciais estilizados presentes.");
    assercao(str_contains($html, 'Aguardando Agendamento'), "Indicador de propostas aguardando agendamento presente.");
    assercao(str_contains($html, 'btn-whats-mini') || str_contains($html, 'fa-whatsapp'), "Ações de contato direto via WhatsApp integradas no painel.");
    assercao(str_contains($html, 'Funil Comercial'), "Seção de funil comercial renderizada.");
    assercao(str_contains($html, 'Controle Financeiro'), "Seção de controle financeiro comercial renderizada.");
    assercao(str_contains($html, 'Próximos Agendamentos de Vistoria'), "Seção de agendamentos operacionais de vistoria renderizada.");

    echo "\n====================================================\n";
    echo "TODOS OS TESTES DO PAINEL DO VENDEDOR PASSARAM (100% OK)!\n";
    echo "====================================================\n";

} catch (Throwable $e) {
    echo "\n[ERRO]: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
