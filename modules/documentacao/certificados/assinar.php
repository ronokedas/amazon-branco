<?php
/**
 * MÓDULO: Documentação > Certificados CSN
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
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Link Inválido</title></head><body style="font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f0f0;"><div style="text-align:center;padding:40px;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);"><h1 style="color:#d32f2f;">Link Inválido</h1><p>O link de assinatura não é válido ou está incompleto.</p></div></body></html>';
    exit;
}

// Buscar certificado pelo token
try {
    require_once __DIR__ . '/../../../config.php'; // Garantir $pdo
    $stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE token_assinatura = :token AND ativo = 1");
    $stmt->execute([':token' => $token]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao buscar certificado.");
}

if (!$cert) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Certificado Não Encontrado</title></head><body style="font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f0f0;"><div style="text-align:center;padding:40px;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);"><h1 style="color:#d32f2f;">Certificado Não Encontrado</h1><p>O certificado associado a este link não foi encontrado ou foi removido.</p></div></body></html>';
    exit;
}

// Processar assinatura (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_assinatura') {
    header('Content-Type: application/json');

    if ($cert['assinado']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Este certificado já foi assinado.']);
        exit;
    }

    $assinatura_imagem = $_POST['assinatura_imagem'] ?? '';

    if (empty($assinatura_imagem)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma assinatura foi desenhada.']);
        exit;
    }

    // Validar que é base64 válido
    if (!preg_match('/^data:image\/\w+;base64,/', $assinatura_imagem)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de assinatura inválido.']);
        exit;
    }

    $ip_assinatura = obterIpCliente();
    $data_assinatura = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("UPDATE certificados_csn SET 
                                    assinatura_imagem = :img,
                                    assinatura_ip = :ip,
                                    assinatura_em = :data,
                                    assinado = 1,
                                    status = 'assinado'
                                WHERE id = :id AND assinado = 0");
        $stmt->execute([
            ':img'  => $assinatura_imagem,
            ':ip'   => $ip_assinatura,
            ':data' => $data_assinatura,
            ':id'   => $cert['id'],
        ]);

        // Gerar e salvar PDF imediatamente
        $dir_ano = date('Y');
        $nome_arquivo_pdf = 'CSN_' . str_replace('/', '-', $cert['numero']) . '.pdf';
        $caminho_relativo = 'storage/certificados/' . $dir_ano . '/csn/' . $nome_arquivo_pdf;
        $salvar_pdf_caminho = __DIR__ . '/../../../' . $caminho_relativo;
        
        $dir_pdf = dirname($salvar_pdf_caminho);
        if (!is_dir($dir_pdf)) {
            mkdir($dir_pdf, 0777, true);
        }

        // Variáveis para o pdf.php
        $_GET['id'] = $cert['id'];
        $id = $cert['id'];
        
        // Fazer include para gerar o PDF
        ob_start();
        require __DIR__ . '/pdf.php';
        ob_end_clean();

        // Salvar hash e caminho no banco
        if (file_exists($salvar_pdf_caminho)) {
            $hash_pdf = hash_file('sha256', $salvar_pdf_caminho);
            $stmt_pdf = $pdo->prepare("UPDATE certificados_csn SET caminho_arquivo_pdf = :caminho, hash_arquivo_pdf = :hash WHERE id = :id");
            $stmt_pdf->execute([
                ':caminho' => $caminho_relativo,
                ':hash' => $hash_pdf,
                ':id' => $cert['id']
            ]);
        }

        // Log
        if (function_exists('log_atividade')) {
            log_atividade('certificado_csn_assinado', "Certificado {$cert['numero']} assinado por {$cert['assinante_nome']} via link público");
        }

        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Assinatura confirmada com sucesso!',
            'nome'     => $cert['assinante_nome'],
            'data'     => formatarDataCompleta($data_assinatura),
            'ip'       => $ip_assinatura,
        ]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar assinatura: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Certificado CSN - <?php echo h($cert['numero']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0d3b2e 0%, #1a5c48 50%, #0d3b2e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0d3b2e, #1a5c48);
            color: #fff;
            padding: 25px 30px;
            text-align: center;
        }
        .header h1 { font-size: 1.3rem; margin-bottom: 5px; }
        .header .numero { font-size: 1rem; opacity: 0.9; }
        .body { padding: 30px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #333; }
        .info-valor { color: #555; }

        /* Já assinado */
        .assinado-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin-top: 20px;
        }
        .assinado-box h2 { color: #2e7d32; margin-bottom: 10px; }
        .assinado-box .assinatura-img {
            max-width: 250px;
            margin: 15px auto;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fff;
        }
        .assinado-box .detalhes { font-size: 0.9rem; color: #555; margin-top: 10px; }

        /* Canvas de assinatura */
        .canvas-area {
            margin-top: 20px;
            text-align: center;
        }
        .canvas-area h3 {
            color: #0d3b2e;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .canvas-wrapper {
            display: inline-block;
            border: 2px dashed #1a5c48;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
        }
        #canvasAssinatura {
            cursor: crosshair;
            display: block;
            border-radius: 4px;
            background: #fff;
        }
        .canvas-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #0d3b2e;
            color: #fff;
        }
        .btn-primary:hover { background: #1a5c48; }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .btn-secondary:hover { background: #d0d0d0; }
        .btn-success {
            background: #4caf50;
            color: #fff;
        }
        .btn-success:hover { background: #388e3c; }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .msg-sucesso {
            display: none;
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin-top: 20px;
        }
        .msg-sucesso h2 { color: #2e7d32; }
        .msg-sucesso .check { font-size: 3rem; color: #4caf50; }
        .msg-erro {
            display: none;
            background: #ffebee;
            border: 2px solid #f44336;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-top: 15px;
            color: #c62828;
        }
        .footer {
            background: #f5f5f5;
            padding: 15px 30px;
            text-align: center;
            font-size: 0.8rem;
            color: #888;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Assinatura Digital - Certificado CSN</h1>
            <div class="numero">Certificado: <?php echo h($cert['numero']); ?></div>
        </div>

        <div class="body">
            <!-- Informações do certificado -->
            <div class="info-row">
                <span class="info-label">Embarcação:</span>
                <span class="info-valor"><?php echo h($cert['nome_embarcacao']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Emissão:</span>
                <span class="info-valor"><?php echo formatarData($cert['data_emissao']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Validade:</span>
                <span class="info-valor"><?php echo formatarData($cert['data_validade']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Assinante:</span>
                <span class="info-valor">
                    <?php echo h($cert['assinante_nome']); ?>
                    <?php if (!empty($cert['assinante_titulo'])): ?>
                        — <?php echo h($cert['assinante_titulo']); ?>
                    <?php endif; ?>
                    <?php if (!empty($cert['assinante_registro'])): ?>
                        (<?php echo h($cert['assinante_registro']); ?>)
                    <?php endif; ?>
                </span>
            </div>

            <?php if ($cert['assinado']): ?>
                <!-- JÁ ASSINADO -->
                <div class="assinado-box">
                    <h2><i class="fas fa-check-circle"></i> Documento Já Assinado</h2>
                    <p>Este certificado já foi assinado digitalmente.</p>
                    <?php if (!empty($cert['assinatura_imagem'])): ?>
                        <img src="<?php echo h($cert['assinatura_imagem']); ?>" alt="Assinatura" class="assinatura-img">
                    <?php endif; ?>
                    <div class="detalhes">
                        <p><strong>Assinado por:</strong> <?php echo h($cert['assinante_nome']); ?></p>
                        <p><strong>Data/Hora:</strong> <?php echo formatarDataCompleta($cert['assinatura_em']); ?></p>
                        <p><strong>IP:</strong> <?php echo h($cert['assinatura_ip']); ?></p>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>documentacao/certificados/pdf?token=<?php echo h($token); ?>" 
                           class="btn btn-success" target="_blank" style="text-decoration:none;">
                            <i class="fas fa-file-pdf"></i> Ver PDF Assinado
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- ASSINATURA -->
                <div class="canvas-area" id="areaAssinatura">
                    <h3>Desenhe sua assinatura abaixo</h3>
                    <div class="canvas-wrapper">
                        <canvas id="canvasAssinatura" width="400" height="150"></canvas>
                    </div>
                    <div class="canvas-actions">
                        <button type="button" class="btn btn-secondary" onclick="limparCanvas()">
                            <i class="fas fa-eraser"></i> Limpar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnConfirmar" onclick="confirmarAssinatura()">
                            <i class="fas fa-check"></i> Confirmar Assinatura
                        </button>
                    </div>
                    <div class="msg-erro" id="msgErro"></div>
                </div>

                <!-- Mensagem de sucesso (inicialmente oculta) -->
                <div class="msg-sucesso" id="msgSucesso">
                    <div class="check"><i class="fas fa-check-circle"></i></div>
                    <h2>Assinatura Confirmada!</h2>
                    <p>O certificado foi assinado com sucesso.</p>
                    <div id="detalhesAssinatura" style="margin-top: 15px;"></div>
                    <div style="margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>documentacao/certificados/pdf?token=<?php echo h($token); ?>" 
                           class="btn btn-success" target="_blank" style="text-decoration:none;">
                            <i class="fas fa-file-pdf"></i> Ver PDF Assinado
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Amazon Naval Ltda — Certificado de Segurança da Navegação</p>
        </div>
    </div>

    <?php if (!$cert['assinado']): ?>
    <script>
    // ============================================
    // CANVAS DE ASSINATURA
    // ============================================
    const canvas = document.getElementById('canvasAssinatura');
    const ctx = canvas.getContext('2d');
    let desenhando = false;
    let ultimaX = 0;
    let ultimaY = 0;
    let assinaturaFeita = false;

    // Configuração do traço
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    // Eventos de mouse
    canvas.addEventListener('mousedown', function(e) {
        desenhando = true;
        [ultimaX, ultimaY] = getPos(e);
    });

    canvas.addEventListener('mousemove', function(e) {
        if (!desenhando) return;
        const [x, y] = getPos(e);
        ctx.beginPath();
        ctx.moveTo(ultimaX, ultimaY);
        ctx.lineTo(x, y);
        ctx.stroke();
        [ultimaX, ultimaY] = [x, y];
        assinaturaFeita = true;
    });

    canvas.addEventListener('mouseup', function() { desenhando = false; });
    canvas.addEventListener('mouseleave', function() { desenhando = false; });

    // Eventos de touch (mobile)
    canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        desenhando = true;
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        ultimaX = touch.clientX - rect.left;
        ultimaY = touch.clientY - rect.top;
    }, { passive: false });

    canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        if (!desenhando) return;
        const touch = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        ctx.beginPath();
        ctx.moveTo(ultimaX, ultimaY);
        ctx.lineTo(x, y);
        ctx.stroke();
        ultimaX = x;
        ultimaY = y;
        assinaturaFeita = true;
    }, { passive: false });

    canvas.addEventListener('touchend', function() { desenhando = false; });

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        return [
            e.clientX - rect.left,
            e.clientY - rect.top
        ];
    }

    function limparCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        assinaturaFeita = false;
    }

    function confirmarAssinatura() {
        if (!assinaturaFeita) {
            showError('Por favor, desenhe sua assinatura antes de confirmar.');
            return;
        }

        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

        const imagem = canvas.toDataURL('image/png');

        const formData = new FormData();
        formData.append('acao', 'confirmar_assinatura');
        formData.append('assinatura_imagem', imagem);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Ocultar área de assinatura e mostrar sucesso
                document.getElementById('areaAssinatura').style.display = 'none';
                const msgSucesso = document.getElementById('msgSucesso');
                msgSucesso.style.display = 'block';
                document.getElementById('detalhesAssinatura').innerHTML = 
                    '<p><strong>Assinado por:</strong> ' + data.nome + '</p>' +
                    '<p><strong>Data/Hora:</strong> ' + data.data + '</p>' +
                    '<p><strong>IP:</strong> ' + data.ip + '</p>';
            } else {
                showError(data.mensagem);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Assinatura';
            }
        })
        .catch(error => {
            showError('Erro ao comunicar com o servidor. Tente novamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Assinatura';
        });
    }

    function showError(msg) {
        const el = document.getElementById('msgErro');
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 5000);
    }
    </script>
    <?php endif; ?>
</body>
</html>
