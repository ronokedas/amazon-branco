<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analise_planos.php';

$u = $pdo->query("SELECT id, cargo, versao_sessao FROM usuarios WHERE ativo = 1 AND cargo = 'ADMIN' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$u) {
    die("No admin user found\n");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $u['id'];
$_SESSION['usuario_cargo'] = $u['cargo'];
$_SESSION['usuario_nome'] = 'Administrador Teste';
$_SESSION['versao_sessao'] = (int)($u['versao_sessao'] ?? 0);
$_SESSION['permissoes'] = ['doc_lc', 'analises_planos'];

// TEST 1: EM_ANALISE
$analise = $pdo->query("SELECT id, numero, status FROM analises_planos WHERE status = 'EM_ANALISE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = $analise['id'];
$_GET['aba'] = 'pareceres';
$_SERVER['REQUEST_URI'] = '/analises-planos/form?id=' . $analise['id'] . '&aba=pareceres';

ob_start();
include __DIR__ . '/../modules/analises_planos/form.php';
$html = ob_get_clean();

if (strpos($html, '<div class="banner-iniciar-analise">') !== false) {
    echo "FAIL 1: banner was found for EM_ANALISE!\n";
} else {
    echo "PASS 1: banner is NOT rendered for EM_ANALISE!\n";
}

// TEST 2: AGENDADA
// Temporarily set status to AGENDADA for test
$pdo->prepare("UPDATE analises_planos SET status = 'AGENDADA' WHERE id = ?")->execute([$analise['id']]);

ob_start();
include __DIR__ . '/../modules/analises_planos/form.php';
$htmlAgendada = ob_get_clean();

// Restore status back to EM_ANALISE
$pdo->prepare("UPDATE analises_planos SET status = 'EM_ANALISE' WHERE id = ?")->execute([$analise['id']]);

if (strpos($htmlAgendada, '<div class="banner-iniciar-analise">') !== false) {
    echo "PASS 2: banner IS rendered when status is AGENDADA!\n";
} else {
    echo "FAIL 2: banner was NOT found for AGENDADA!\n";
}

echo "ALL TESTS COMPLETED SUCCESSFULLY!\n";
