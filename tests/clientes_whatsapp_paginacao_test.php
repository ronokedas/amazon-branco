<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== TESTE: BOTÃO WHATSAPP E PAGINAÇÃO EM CLIENTES ===\n\n";

function assercao($cond, $msg) {
    if (!$cond) {
        throw new RuntimeException("FALHA: {$msg}");
    }
    echo "  [✓] {$msg}\n";
}

try {
    $admin = $pdo->query("SELECT id, cargo, versao_sessao FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 AND excluido_em IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        throw new RuntimeException("Admin não encontrado no banco.");
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_cargo'] = $admin['cargo'];
    $_SESSION['usuario_id'] = $admin['id'];
    $_SESSION['versao_sessao'] = (int)($admin['versao_sessao'] ?? 0);

    // 1. Testar renderização padrão com WhatsApp e Paginação
    echo "1. Validando renderização com WhatsApp e controles de paginação...\n";
    $_GET = ['perfil' => 'todos', 'por_pagina' => '10', 'pagina' => '1'];
    ob_start();
    require __DIR__ . '/../modules/clientes/index.php';
    $html1 = ob_get_clean();

    assercao(str_contains($html1, 'btn-whatsapp'), "Botão .btn-whatsapp presente nas ações da tabela.");
    assercao(str_contains($html1, 'fa-whatsapp'), "Ícone fa-whatsapp presente.");
    assercao(str_contains($html1, 'api.whatsapp.com/send?phone='), "Link oficial do WhatsApp gerado para contatos válidos.");
    assercao(str_contains($html1, 'selectPorPagina'), "Seletor de quantidade por página presente.");
    assercao(str_contains($html1, 'filtros-busca-form'), "Formulário de busca de clientes presente.");

    // 2. Testar paginação reduzida (ex.: 5 itens por página) para forçar múltiplas páginas
    echo "\n2. Validando navegação entre páginas com por_pagina=5...\n";
    $_GET = ['perfil' => 'todos', 'por_pagina' => '5', 'pagina' => '1'];
    ob_start();
    require __DIR__ . '/../modules/clientes/index.php';
    $htmlPaginado = ob_get_clean();

    assercao(str_contains($htmlPaginado, 'paginacao-clientes'), "Barra de paginação .paginacao-clientes renderizada com sucesso.");
    assercao(str_contains($htmlPaginado, 'pagina=2'), "Link para a página 2 gerado corretamente.");
    assercao(str_contains($htmlPaginado, 'Mostrando <strong>1</strong> a <strong>5</strong>'), "Contador de registros da página 1 coerente.");

    // 3. Testar busca no banco
    echo "\n3. Validando busca integrada no banco de dados...\n";
    $_GET = ['perfil' => 'todos', 'busca' => 'Solimões', 'pagina' => '1'];
    ob_start();
    require __DIR__ . '/../modules/clientes/index.php';
    $htmlBusca = ob_get_clean();

    assercao(str_contains($htmlBusca, 'filtrando por') || str_contains($htmlBusca, 'Solimões'), "Busca por termo aplicada com sucesso no backend.");
    assercao(str_contains($htmlBusca, 'Limpar'), "Botão para limpar busca exibido quando há termo pesquisado.");

    echo "\n====================================================\n";
    echo "TODOS OS TESTES DE WHATSAPP E PAGINAÇÃO PASSARAM (100% OK)!\n";
    echo "====================================================\n";

} catch (Throwable $e) {
    echo "\n[ERRO]: " . $e->getMessage() . "\n";
    exit(1);
}
