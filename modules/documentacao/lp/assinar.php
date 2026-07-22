<?php
/**
 * MÓDULO: Documentação > Licença Provisória (LP)
 * Página PÚBLICA de Assinatura Digital
 * Acessada via link: sistema/assinar/{token_assinatura}
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Esta página é PÚBLICA — não requer login
$token = $_GET['token'] ?? '';

// Se o token veio do roteador (via segmento de URL)
if (empty($token) && isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $app_folder = '/' . basename(__DIR__ . '/../../..');
    if (strpos($uri, $app_folder) === 0) {
        $uri = substr($uri, strlen($app_folder));
    }
    $uri = ltrim($uri, '/');
    // Formato: assinar/{token}
    $parts = explode('/', $uri);
    if (count($parts) >= 2 && $parts[0] === 'assinar') {
        $token = $parts[1];
    }
}

if (empty($token)) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title>Link inválido</title>
    <style>body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
    .card{background:#fff;padding:40px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;max-width:400px}
    h2{color:#d32f2f} p{color:#666}</style></head>
    <body><div class="card"><h2>Link Inválido</h2>
    <p>O link de assinatura que você acessou é inválido ou expirou.</p>
    <p>Entre em contato com a Amazon Naval Ltda para solicitar um novo link.</p></div></body></html>
    <?php
    exit;
}

// Buscar licença pelo token
$stmt = $pdo->prepare("SELECT * FROM certificados_lp WHERE token_assinatura = :token AND ativo = 1");
$stmt->execute([':token' => $token]);
$licenca = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$licenca) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title>Documento não encontrado</title>
    <style>body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
    .card{background:#fff;padding:40px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;max-width:400px}
    h2{color:#d32f2f} p{color:#666}</style></head>
    <body><div class="card"><h2>Documento não encontrado</h2>
    <p>O documento que você está tentando assinar não foi encontrado ou foi removido.</p></div></body></html>
    <?php
    exit;
}

// Se já estiver assinado, mostrar mensagem
if ($licenca['assinado']) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title>Documento já assinado</title>
    <style>body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
    .card{background:#fff;padding:40px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;max-width:500px}
    h2{color:#2e7d32} .assinado{color:#2e7d32;font-size:48px} p{color:#666}</style></head>
    <body><div class="card"><div class="assinado">&#10003;</div>
    <h2>Documento já assinado</h2>
    <p>Este documento já foi assinado digitalmente por <strong><?php echo h($licenca['assinante_nome']); ?></strong>.</p>
    <p>Data da assinatura: <?php echo formatarDataCompleta($licenca['assinatura_em']); ?></p>
    <p><a href="<?php echo APP_URL; ?>documentacao/lp/pdf?token=<?php echo h($token); ?>" target="_blank" 
          style="display:inline-block;padding:10px 20px;background:#0891b2;color:#fff;text-decoration:none;border-radius:5px;margin-top:10px">
        <i class="fas fa-file-pdf"></i> Visualizar Documento</a></p>
    </div></body></html>
    <?php
    exit;
}

// Processar assinatura (POST)
$mensagem = '';
$erro = '';
$assinado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_assinante   = trim($_POST['nome'] ?? '');
    $cpf_assinante    = trim(preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''));
    $termos           = isset($_POST['termos']) && $_POST['termos'] === '1';
    
    // Capturar assinatura do canvas
    $assinatura_dados = $_POST['assinatura_dados'] ?? '';
    
    // Validações
    if (empty($nome_assinante)) {
        $erro = 'Informe seu nome completo.';
    } elseif (strlen($cpf_assinante) !== 11) {
        $erro = 'CPF inválido. Informe um CPF com 11 dígitos.';
    } elseif (!$termos) {
        $erro = 'Você precisa aceitar os termos para assinar.';
    } elseif (empty($assinatura_dados)) {
        $erro = 'Desenhe sua assinatura no campo acima.';
    }
    
    if (empty($erro)) {
        try {
            $ip = obterIpCliente();
            $data_assinatura = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE certificados_lp SET 
                assinante_nome = :nome,
                assinatura_imagem = :imagem,
                assinatura_ip = :ip,
                assinatura_em = :data,
                assinado = 1,
                status = 'assinado'
                WHERE id = :id AND ativo = 1");
            
            $stmt->execute([
                ':nome'   => $nome_assinante,
                ':imagem' => $assinatura_dados,
                ':ip'     => $ip,
                ':data'   => $data_assinatura,
                ':id'     => $licenca['id'],
            ]);
            
            // Gerar e salvar PDF imediatamente
            $dir_ano = date('Y');
            $nome_arquivo_pdf = 'LP_' . str_replace('/', '-', $licenca['numero_lp']) . '.pdf';
            $caminho_relativo = 'storage/certificados/' . $dir_ano . '/lp/' . $nome_arquivo_pdf;
            $salvar_pdf_caminho = __DIR__ . '/../../../' . $caminho_relativo;
            
            $dir_pdf = dirname($salvar_pdf_caminho);
            if (!is_dir($dir_pdf)) {
                mkdir($dir_pdf, 0777, true);
            }

            // Variáveis para o pdf.php
            $_GET['id'] = $licenca['id'];
            $id = $licenca['id'];
            
            // Fazer include para gerar o PDF
            ob_start();
            require __DIR__ . '/pdf.php';
            ob_end_clean();

            // Salvar hash e caminho no banco
            if (file_exists($salvar_pdf_caminho)) {
                $hash_pdf = hash_file('sha256', $salvar_pdf_caminho);
                $stmt_pdf = $pdo->prepare("UPDATE certificados_lp SET caminho_arquivo_pdf = :caminho, hash_arquivo_pdf = :hash WHERE id = :id");
                $stmt_pdf->execute([
                    ':caminho' => $caminho_relativo,
                    ':hash' => $hash_pdf,
                    ':id' => $licenca['id']
                ]);
            }

            // Log
            log_atividade('licenca_lp_assinada', "Licença LP {$licenca['numero_lp']} assinada por {$nome_assinante}");
            
            $assinado = true;
            $mensagem = 'Documento assinado com sucesso!';
            
        } catch (Exception $e) {
            $erro = 'Erro ao processar assinatura: ' . $e->getMessage();
        }
    }
}

// Título
$titulo_page = 'Assinar Licença Provisória - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_page; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 700px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0891b2, #065f73); color: #fff; padding: 30px; text-align: center; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #0891b2; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .info-box h3 { font-size: 14px; color: #0891b2; margin-bottom: 8px; }
        .info-box p { font-size: 13px; color: #555; margin-bottom: 3px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #333; }
        .form-group input[type="text"], .form-group input[type="text"] { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; transition: border-color 0.3s; }
        .form-group input:focus { border-color: #0891b2; outline: none; }
        .canvas-wrapper { border: 2px solid #ddd; border-radius: 6px; overflow: hidden; background: #fff; }
        .canvas-wrapper canvas { display: block; width: 100%; height: 150px; cursor: crosshair; }
        .canvas-tools { display: flex; gap: 8px; margin-top: 8px; }
        .canvas-tools button { padding: 6px 14px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .canvas-tools button:hover { background: #e9ecef; }
        .checkbox-group { display: flex; align-items: flex-start; gap: 10px; }
        .checkbox-group input[type="checkbox"] { margin-top: 3px; width: 18px; height: 18px; }
        .checkbox-group label { font-size: 14px; color: #555; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-assinar { width: 100%; padding: 14px; background: linear-gradient(135deg, #0891b2, #065f73); color: #fff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-assinar:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(8,145,178,0.3); }
        .btn-assinar:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .btn-visualizar { display: inline-block; padding: 12px 24px; background: #0891b2; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; margin-top: 10px; }
        .btn-visualizar:hover { background: #065f73; }
        .success-box { text-align: center; padding: 30px 0; }
        .success-icon { font-size: 64px; color: #2e7d32; margin-bottom: 15px; }
        @media (max-width: 600px) { .header { padding: 20px; } .content { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-signature"></i> Assinatura Digital</h1>
            <p>Licença Provisória - <?php echo h($licenca['numero_lp']); ?></p>
        </div>
        
        <div class="content">
            <?php if ($assinado): ?>
                <div class="success-box">
                    <div class="success-icon">&#10003;</div>
                    <h2 style="color:#2e7d32;margin-bottom:10px;">Documento Assinado!</h2>
                    <p style="color:#666;margin-bottom:20px;"><?php echo h($mensagem); ?></p>
                    <p style="font-size:14px;color:#666;margin-bottom:15px;">
                        Assinado por: <strong><?php echo h($nome_assinante); ?></strong><br>
                        CPF: <?php echo substr($cpf_assinante, 0, 3) . '.***.***-' . substr($cpf_assinante, -2); ?><br>
                        Data: <?php echo date('d/m/Y H:i'); ?>
                    </p>
                    <a href="<?php echo APP_URL; ?>documentacao/lp/pdf?token=<?php echo h($token); ?>" 
                       class="btn-visualizar" target="_blank">
                        <i class="fas fa-file-pdf"></i> Visualizar Documento Assinado
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo h($erro); ?></div>
                <?php endif; ?>
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo h($mensagem); ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Documento para assinar</h3>
                    <p><strong>Número:</strong> <?php echo h($licenca['numero_lp']); ?></p>
                    <p><strong>Tipo:</strong> <?php 
                        $tipo_labels = ['construção'=>'Construção','alteração'=>'Alteração','reclassificação'=>'Reclassificação','lcec'=>'LCEC'];
                        echo h($tipo_labels[$licenca['tipo_licenca']] ?? $licenca['tipo_licenca']); 
                    ?></p>
                    <p><strong>Embarcação:</strong> <?php echo h($licenca['nome_embarcacao']); ?></p>
                    <p><strong>Data de Emissão:</strong> <?php echo formatarData($licenca['data_emissao']); ?></p>
                    <a href="<?php echo APP_URL; ?>documentacao/lp/pdf?token=<?php echo h($token); ?>" 
                       target="_blank" style="display:inline-block;margin-top:8px;color:#0891b2;text-decoration:none;font-weight:600;">
                        <i class="fas fa-file-pdf"></i> Visualizar documento antes de assinar
                    </a>
                </div>
                
                <form method="POST" id="formAssinatura" onsubmit="return validarForm()">
                    <input type="hidden" name="_submission_token" value="<?= h(bin2hex(random_bytes(24))) ?>">
                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required placeholder="Digite seu nome completo"
                               value="<?php echo h($_POST['nome'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf">CPF *</label>
                        <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00"
                               maxlength="14" oninput="mascararCPF(this)"
                               value="<?php echo h($_POST['cpf'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Desenhe sua Assinatura *</label>
                        <div class="canvas-wrapper">
                            <canvas id="canvasAssinatura" width="640" height="150"></canvas>
                        </div>
                        <div class="canvas-tools">
                            <button type="button" onclick="limparAssinatura()"><i class="fas fa-eraser"></i> Limpar</button>
                            <button type="button" onclick="mudarCorAssinatura()"><i class="fas fa-palette"></i> Cor</button>
                            <span style="margin-left:auto;font-size:12px;color:#999;">Desenhe com o mouse ou touch</span>
                        </div>
                        <input type="hidden" name="assinatura_dados" id="assinatura_dados">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="termos" name="termos" value="1" 
                                   <?php echo isset($_POST['termos']) ? 'checked' : ''; ?>>
                            <label for="termos">
                                Declaro que sou o signatário e que li e concordo com os termos deste documento. 
                                Esta assinatura eletrônica tem validade jurídica conforme a legislação vigente.
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-assinar" id="btnAssinar">
                        <i class="fas fa-pen"></i> Assinar Documento
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Máscara de CPF
    function mascararCPF(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        let f = v;
        if (v.length > 3) f = v.substring(0, 3) + '.' + v.substring(3);
        if (v.length > 6) f = f.substring(0, 7) + '.' + f.substring(7);
        if (v.length > 9) f = f.substring(0, 11) + '-' + f.substring(11);
        input.value = f;
    }
    
    // Canvas de assinatura
    const canvas = document.getElementById('canvasAssinatura');
    const ctx = canvas.getContext('2d');
    let desenhando = false;
    let corAssinatura = '#000000';
    
    // Fundo branco
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = corAssinatura;
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // Linha guia
    ctx.beginPath();
    ctx.strokeStyle = '#ddd';
    ctx.lineWidth = 1;
    ctx.setLineDash([5, 5]);
    ctx.moveTo(10, canvas.height / 2);
    ctx.lineTo(canvas.width - 10, canvas.height / 2);
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.strokeStyle = corAssinatura;
    ctx.lineWidth = 2;
    
    canvas.addEventListener('mousedown', iniciarDesenho);
    canvas.addEventListener('mousemove', desenhar);
    canvas.addEventListener('mouseup', pararDesenho);
    canvas.addEventListener('mouseleave', pararDesenho);
    
    // Touch support
    canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        const x = (touch.clientX - rect.left) * (canvas.width / rect.width);
        const y = (touch.clientY - rect.top) * (canvas.height / rect.height);
        iniciarDesenho({ offsetX: x, offsetY: y });
    });
    canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        const x = (touch.clientX - rect.left) * (canvas.width / rect.width);
        const y = (touch.clientY - rect.top) * (canvas.height / rect.height);
        desenhar({ offsetX: x, offsetY: y });
    });
    canvas.addEventListener('touchend', pararDesenho);
    
    function iniciarDesenho(e) {
        desenhando = true;
        ctx.beginPath();
        ctx.moveTo(e.offsetX, e.offsetY);
    }
    
    function desenhar(e) {
        if (!desenhando) return;
        ctx.lineTo(e.offsetX, e.offsetY);
        ctx.stroke();
    }
    
    function pararDesenho() {
        desenhando = false;
    }
    
    function limparAssinatura() {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = corAssinatura;
        ctx.lineWidth = 2;
        // Linha guia
        ctx.beginPath();
        ctx.strokeStyle = '#ddd';
        ctx.lineWidth = 1;
        ctx.setLineDash([5, 5]);
        ctx.moveTo(10, canvas.height / 2);
        ctx.lineTo(canvas.width - 10, canvas.height / 2);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.strokeStyle = corAssinatura;
        ctx.lineWidth = 2;
    }
    
    function mudarCorAssinatura() {
        const cores = ['#000000', '#0891b2', '#1a5276', '#2e7d32'];
        const atual = cores.indexOf(corAssinatura);
        corAssinatura = cores[(atual + 1) % cores.length];
        ctx.strokeStyle = corAssinatura;
    }
    
    function validarForm() {
        const nome = document.getElementById('nome').value.trim();
        const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
        const canvas = document.getElementById('canvasAssinatura');
        
        if (!nome) { alert('Informe seu nome completo.'); return false; }
        if (cpf.length !== 11) { alert('CPF inválido.'); return false; }
        if (!document.getElementById('termos').checked) { alert('Você precisa aceitar os termos.'); return false; }
        
        // Verificar se desenhou algo no canvas
        const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
        const pixels = imageData.data;
        let temPixel = false;
        for (let i = 0; i < pixels.length; i += 4) {
            if (pixels[i + 3] > 0) { temPixel = true; break; }
        }
        
        if (!temPixel) { alert('Desenhe sua assinatura no campo acima.'); return false; }
        
        // Salvar assinatura como base64
        document.getElementById('assinatura_dados').value = canvas.toDataURL('image/png');
        
        document.getElementById('btnAssinar').disabled = true;
        document.getElementById('btnAssinar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assinando...';
        return true;
    }
    </script>
</body>
</html>
