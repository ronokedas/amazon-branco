<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/../config.php';
ini_set('display_errors', '1');
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = 'dd121661-feb4-42f6-895a-68eb0608d1e4';
$_SESSION['usuario_cargo'] = 'ADMIN';
$_SESSION['usuario_nome'] = 'admin';
$_SESSION['versao_sessao'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/documentos-vencidos';

ob_start();
require __DIR__ . '/../modules/documentos_vencidos/index.php';
$html = ob_get_clean();

echo "HTML Length: " . strlen($html) . PHP_EOL;
echo "Contains Documentos Vencidos: " . (strpos($html, 'Documentos Vencidos') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains Kpi Cards: " . (strpos($html, 'kpi-card') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains Subcat Tabs: " . (strpos($html, 'subcat-tabs') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Contains Modal Alerta: " . (strpos($html, 'modalAlertarWrap') !== false ? 'YES' : 'NO') . PHP_EOL;
assert(strlen($html) > 500, "HTML length should be > 500");
assert(strpos($html, 'Documentos Vencidos') !== false, "Must contain Documentos Vencidos");
echo "✓ Renderização do módulo Documentos Vencidos bem-sucedida!\n";
