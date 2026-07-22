<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$token = trim((string)($_GET['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Documento de validacao nao encontrado.');
}

$stmt = $pdo->prepare('SELECT * FROM documento_aprovacoes WHERE token_validacao = :token LIMIT 1');
$stmt->execute([':token'=>$token]);
$approval = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$approval) {
    http_response_code(404);
    exit('Documento de validacao nao encontrado.');
}

$relativeFile = ltrim(str_replace(['../','..\\'], '', (string)($approval['caminho_pdf_final'] ?? '')), '/\\');
$file = $relativeFile !== '' ? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeFile) : '';
$storedExists = $file !== '' && is_file($file);
$actualHash = $storedExists ? hash_file('sha256', $file) : null;
$integrityOk = $storedExists && !empty($approval['hash_pdf_final']) && hash_equals($approval['hash_pdf_final'], $actualHash);

if (isset($_GET['download'])) {
    if (!in_array(($approval['status'] ?? ''), ['APROVADO', 'CANCELADO'], true) || !$integrityOk) {
        http_response_code(409);
        exit('O arquivo oficial nao esta disponivel ou falhou na verificacao de integridade.');
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="documento-aprovado-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $approval['documento_tipo']) . '.pdf"');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

$uploadResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded = $_FILES['arquivo_pdf'] ?? null;
    if (!$uploaded || ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $uploadResult = ['ok'=>false, 'message'=>'Selecione um arquivo PDF valido.'];
    } elseif (($uploaded['size'] ?? 0) > 20 * 1024 * 1024) {
        $uploadResult = ['ok'=>false, 'message'=>'O PDF deve ter no maximo 20 MB.'];
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($uploaded['tmp_name']);
        if ($mime !== 'application/pdf' || file_get_contents($uploaded['tmp_name'], false, null, 0, 5) !== '%PDF-') {
            $uploadResult = ['ok'=>false, 'message'=>'O arquivo enviado nao e um PDF valido.'];
        } else {
            $uploadedHash = hash_file('sha256', $uploaded['tmp_name']);
            $match = !empty($approval['hash_pdf_final']) && hash_equals($approval['hash_pdf_final'], $uploadedHash);
            $uploadResult = [
                'ok'=>$match,
                'message'=>$match ? 'Arquivo autentico: o hash corresponde ao PDF oficial.' : 'Arquivo divergente: o hash nao corresponde ao PDF oficial.',
                'hash'=>$uploadedHash,
            ];
        }
    }
}

function validarDocumentoH($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$statusClass = ($approval['status'] === 'APROVADO' && $integrityOk) ? 'ok' : 'bad';
$statusText = $approval['status'] === 'CANCELADO' ? 'Documento cancelado' : (($approval['status'] === 'APROVADO' && $integrityOk) ? 'Documento válido e íntegro' : 'Documento com validação pendente ou inconsistente');
$usuarioErpLogado = !empty($_SESSION['usuario_logado']);
$rotasRetorno = [
    'CSN' => 'documentacao/certificados',
    'CNBL' => 'documentacao/cnbl',
    'CNARQ' => 'documentacao/cnarq',
    'LP' => 'documentacao/lp',
    'LC' => 'documentacao/lc',
    'CHT' => 'documentacao/cht',
    'PARECER_PLANOS' => 'analises-planos',
    'RELATORIO' => 'vistorias',
];
$rotaRetorno = $rotasRetorno[strtoupper((string)$approval['documento_tipo'])] ?? 'dashboard';
$urlRetorno = rtrim(APP_URL, '/') . '/' . ltrim($rotaRetorno, '/');
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Validação de documento - Amazon Naval</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f2f6f4;color:#18352d;font-family:Inter,Arial,sans-serif}.top{background:#063c32;color:#fff;padding:24px}.top-inner,.wrap{max-width:920px;margin:auto}.brand{font-weight:800;letter-spacing:.08em}.wrap{padding:28px 18px 50px}.card{background:#fff;border:1px solid #d9e4df;border-radius:14px;box-shadow:0 8px 28px rgba(8,55,44,.08);padding:24px;margin-bottom:18px}.status{display:flex;gap:12px;align-items:center;border-radius:10px;padding:14px 16px;font-weight:750}.status.ok{background:#e8f7ef;color:#087342}.status.bad{background:#fff1f0;color:#a82b22}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 28px}.item small{display:block;text-transform:uppercase;letter-spacing:.06em;color:#6d7f78;font-size:11px;margin-bottom:4px}.item strong,.item span{overflow-wrap:anywhere}.hash{font:12px ui-monospace,SFMono-Regular,Consolas,monospace;background:#f5f7f6;padding:10px;border-radius:7px;overflow-wrap:anywhere}.btn{display:inline-flex;padding:11px 16px;border-radius:8px;border:0;background:#07966f;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}.btn.secondary{background:#e7efec;color:#17483b}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}input[type=file]{display:block;width:100%;padding:12px;border:1px solid #cbd9d3;border-radius:8px;margin:12px 0}.result{padding:12px;border-radius:8px;margin-top:12px}.result.ok{background:#e8f7ef;color:#087342}.result.bad{background:#fff1f0;color:#a82b22}.note{color:#60746c;font-size:13px;line-height:1.5}@media(max-width:680px){.grid{grid-template-columns:1fr}.card{padding:18px}}
</style>
</head>
<body>
<header class="top"><div class="top-inner"><div class="brand">AMAZON NAVAL</div><div>Validação pública de documentos</div></div></header>
<main class="wrap">
  <section class="card"><div class="status <?= $statusClass ?>"><?= validarDocumentoH($statusText) ?></div></section>
  <section class="card">
    <h1 style="margin-top:0;font-size:24px">Aprovação eletrônica e auditoria</h1>
    <div class="grid">
      <div class="item"><small>Documento</small><strong><?= validarDocumentoH($approval['documento_tipo']) ?></strong></div>
      <div class="item"><small>Status</small><strong><?= validarDocumentoH($approval['status']) ?></strong></div>
      <div class="item"><small>Responsável técnico</small><strong><?= validarDocumentoH($approval['responsavel_nome']) ?></strong></div>
      <div class="item"><small>CPF/CNPJ</small><strong><?= validarDocumentoH($approval['responsavel_cpf_cnpj']) ?></strong></div>
      <div class="item"><small>Cargo/função</small><span><?= validarDocumentoH($approval['responsavel_cargo']) ?></span></div>
      <div class="item"><small>Registro profissional</small><span><?= validarDocumentoH($approval['responsavel_registro'] ?: 'Não informado') ?></span></div>
      <div class="item"><small>Aprovado por</small><span><?= validarDocumentoH($approval['aprovador_nome']) ?></span></div>
      <div class="item"><small>Data e hora</small><span><?= validarDocumentoH(date('d/m/Y H:i:s', strtotime($approval['aprovado_em_local'])) . ' (' . $approval['fuso_horario'] . ', UTC' . $approval['utc_offset'] . ')') ?></span></div>
      <div class="item"><small>Geolocalização</small><span><?= validarDocumentoH($approval['latitude'] . ', ' . $approval['longitude'] . ($approval['geo_precisao_m'] ? ' - precisão ' . $approval['geo_precisao_m'] . ' m' : '')) ?></span></div>
      <div class="item"><small>Endereço IP</small><span><?= validarDocumentoH($approval['ip']) ?></span></div>
      <div class="item"><small>Integridade do arquivo armazenado</small><span><?= $integrityOk?'Confirmada':'Não confirmada' ?></span></div>
    </div>
    <h2 style="font-size:15px;margin-top:22px">SHA-256 do PDF original</h2><div class="hash"><?= validarDocumentoH($approval['hash_pdf_original']) ?></div>
    <h2 style="font-size:15px">SHA-256 do PDF final</h2><div class="hash"><?= validarDocumentoH($approval['hash_pdf_final']) ?></div>
    <div class="actions">
      <?php if($usuarioErpLogado): ?><a class="btn secondary" id="documentReturnButton" href="<?= validarDocumentoH($urlRetorno) ?>">Voltar para o sistema</a><?php endif; ?>
      <?php if($integrityOk): ?><a class="btn" href="?token=<?= validarDocumentoH($token) ?>&download=1">Baixar PDF oficial</a><?php endif; ?>
    </div>
  </section>
  <section class="card">
    <h2 style="margin-top:0;font-size:19px">Verificar um arquivo recebido</h2>
    <p class="note">O arquivo é usado somente para calcular o SHA-256 durante esta requisição e não é armazenado.</p>
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="_submission_token" value="<?= h(bin2hex(random_bytes(24))) ?>"><input type="file" name="arquivo_pdf" accept="application/pdf,.pdf" required><button class="btn" type="submit">Verificar arquivo</button></form>
    <?php if($uploadResult): ?><div class="result <?= $uploadResult['ok']?'ok':'bad' ?>"><?= validarDocumentoH($uploadResult['message']) ?><?php if(!empty($uploadResult['hash'])): ?><div class="hash" style="margin-top:8px"><?= validarDocumentoH($uploadResult['hash']) ?></div><?php endif; ?></div><?php endif; ?>
  </section>
</main>
<?php if($usuarioErpLogado): ?>
<script>
(function(){
  const button=document.getElementById('documentReturnButton');
  if(!button)return;
  const key=<?= json_encode('amazon-document-return:'.$token) ?>;
  try{
    const saved=sessionStorage.getItem(key);
    if(saved){
      const target=new URL(saved,window.location.origin);
      const appBase=new URL(<?= json_encode(rtrim(APP_URL, '/').'/') ?>,window.location.origin);
      const validationPath=new URL(<?= json_encode(rtrim(APP_URL, '/').'/validar/') ?>,window.location.origin).pathname;
      if(target.origin===window.location.origin&&target.pathname.startsWith(appBase.pathname)&&!target.pathname.startsWith(validationPath)){
        button.href=target.pathname+target.search+target.hash;
      }
    }
    button.addEventListener('click',function(){sessionStorage.removeItem(key)});
  }catch(ignored){}
})();
</script>
<?php endif; ?>
</body></html>
