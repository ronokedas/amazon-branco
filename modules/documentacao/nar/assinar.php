<?php
/**
 * MÓDULO: Documentação > Notas de Arqueação (AM-NAR)
 * Página de Assinatura Digital do Responsável Técnico
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';

$token = trim($_GET['token'] ?? '');
$id = trim($_GET['id'] ?? '');

if (empty($token) && !empty($id)) {
    $stmt = $pdo->prepare("SELECT token_assinatura FROM certificados_nar WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $token = (string)($stmt->fetchColumn() ?: '');
}

if (empty($token)) {
    die('Token de assinatura não informado ou inválido.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados_nar WHERE token_assinatura = :token AND ativo = 1");
$stmt->execute([':token' => $token]);
$nar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nar) {
    die('Nota de Arqueação não encontrada.');
}

$meuResp = null;
if (!empty($_SESSION['usuario_id'])) {
    $stmtMe = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE usuario_id = :uid AND ativo = 1 LIMIT 1");
    $stmtMe->execute([':uid' => $_SESSION['usuario_id']]);
    $meuResp = $stmtMe->fetch(PDO::FETCH_ASSOC);
}

// Processar assinatura via POST com perfil cadastrado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_assinatura_perfil') {
    header('Content-Type: application/json; charset=utf-8');

    if (!empty($nar['assinado'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta Nota de Arqueação já foi assinada.']);
        exit;
    }

    if (!$meuResp || empty($meuResp['assinatura_arquivo'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Perfil de assinatura digital não localizado ou incompleto.']);
        exit;
    }

    $sigFile = __DIR__ . '/../../../' . ltrim(str_replace(['../', '..\\'], '', (string)$meuResp['assinatura_arquivo']), '/\\');
    if (!is_file($sigFile)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Arquivo de assinatura não encontrado no servidor.']);
        exit;
    }

    $bytes = file_get_contents($sigFile);
    if ($bytes === false) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao ler o arquivo de assinatura digital.']);
        exit;
    }
    $assinatura_imagem = 'data:image/png;base64,' . base64_encode($bytes);

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agora = date('Y-m-d H:i:s');

        $diretorioDestino = __DIR__ . '/../../../storage/private/certificados/nar';
        if (!is_dir($diretorioDestino)) {
            mkdir($diretorioDestino, 0755, true);
        }

        $nomeArquivo = 'AM-NAR-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $nar['numero']) . '-assinado.pdf';
        $salvar_pdf_caminho = $diretorioDestino . '/' . $nomeArquivo;

        $stmtUpdate = $pdo->prepare("UPDATE certificados_nar SET
            status = 'assinado',
            assinado = 1,
            responsavel_assinatura_id = :resp_id,
            assinante_nome = :resp_nome,
            assinante_titulo = :resp_titulo,
            assinante_registro = :resp_reg,
            assinatura_imagem = :img,
            assinatura_ip = :ip,
            assinatura_em = :agora,
            caminho_arquivo_pdf = :caminho
            WHERE id = :id");

        $caminhoRelativo = 'storage/private/certificados/nar/' . $nomeArquivo;
        $stmtUpdate->execute([
            ':resp_id' => $meuResp['id'],
            ':resp_nome' => $meuResp['nome_completo'],
            ':resp_titulo' => $meuResp['cargo_titulo'],
            ':resp_reg' => $meuResp['registro_profissional'],
            ':img' => $assinatura_imagem,
            ':ip' => $ip,
            ':agora' => $agora,
            ':caminho' => $caminhoRelativo,
            ':id' => $nar['id']
        ]);

        ob_start();
        $id = $nar['id'];
        require __DIR__ . '/pdf.php';
        ob_end_clean();

        if (is_file($salvar_pdf_caminho)) {
            $hashPdf = hash_file('sha256', $salvar_pdf_caminho);
            $pdo->prepare("UPDATE certificados_nar SET hash_arquivo_pdf = :hash WHERE id = :id")
                ->execute([':hash' => $hashPdf, ':id' => $nar['id']]);
        }

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Nota de Arqueação assinada digitalmente com seu perfil cadastrado!',
            'url_pdf' => APP_URL . 'documentacao/nar/pdf?id=' . urlencode($nar['id'])
        ]);
        exit;
    } catch (Throwable $e) {
        error_log('Erro ao assinar NAR com perfil cadastrado: ' . $e->getMessage());
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar assinatura: ' . $e->getMessage()]);
        exit;
    }
}

// Processar assinatura via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_assinatura') {
    header('Content-Type: application/json; charset=utf-8');

    if (!empty($nar['assinado'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta Nota de Arqueação já foi assinada.']);
        exit;
    }

    $assinatura_imagem = $_POST['assinatura_imagem'] ?? '';
    if (empty($assinatura_imagem)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Desenhe ou confirme sua assinatura para prosseguir.']);
        exit;
    }

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agora = date('Y-m-d H:i:s');

        // Gerar e congelar o arquivo PDF assinado em disco
        $diretorioDestino = __DIR__ . '/../../../storage/private/certificados/nar';
        if (!is_dir($diretorioDestino)) {
            mkdir($diretorioDestino, 0755, true);
        }

        $nomeArquivo = 'AM-NAR-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $nar['numero']) . '-assinado.pdf';
        $salvar_pdf_caminho = $diretorioDestino . '/' . $nomeArquivo;

        // Atualizar banco
        $stmtUpdate = $pdo->prepare("UPDATE certificados_nar SET
            status = 'assinado',
            assinado = 1,
            assinatura_imagem = :img,
            assinatura_ip = :ip,
            assinatura_em = :agora,
            caminho_arquivo_pdf = :caminho
            WHERE id = :id");

        $caminhoRelativo = 'storage/private/certificados/nar/' . $nomeArquivo;
        $stmtUpdate->execute([
            ':img' => $assinatura_imagem,
            ':ip' => $ip,
            ':agora' => $agora,
            ':caminho' => $caminhoRelativo,
            ':id' => $nar['id']
        ]);

        // Carregar gerador de PDF para gravar o arquivo físico
        ob_start();
        $id = $nar['id'];
        require __DIR__ . '/pdf.php';
        ob_end_clean();

        // Calcular hash do PDF gerado
        if (is_file($salvar_pdf_caminho)) {
            $hashPdf = hash_file('sha256', $salvar_pdf_caminho);
            $pdo->prepare("UPDATE certificados_nar SET hash_arquivo_pdf = :hash WHERE id = :id")
                ->execute([':hash' => $hashPdf, ':id' => $nar['id']]);
        }

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Nota de Arqueação assinada digitalmente com sucesso!',
            'url_pdf' => APP_URL . 'documentacao/nar/pdf?id=' . urlencode($nar['id'])
        ]);
        exit;
    } catch (Throwable $e) {
        error_log('Erro ao assinar NAR: ' . $e->getMessage());
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar assinatura: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Digital - <?= h($nar['numero']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; box-sizing: border-box; }
        .card-sign { background: #1e293b; border: 1px solid #334155; border-radius: 14px; max-width: 600px; width: 100%; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .btn-sign { background: #0f766e; color: #fff; border: none; border-radius: 8px; padding: 12px 24px; font-weight: 700; font-size: 1rem; cursor: pointer; width: 100%; transition: background 0.2s; }
        .btn-sign:hover { background: #115e59; }
        .canvas-container { background: #fff; border-radius: 8px; border: 2px dashed #94a3b8; margin: 15px 0; touch-action: none; }
    </style>
</head>
<body>
    <div class="card-sign">
        <div style="text-align:center; margin-bottom: 20px;">
            <i class="fa-solid fa-file-signature text-primary" style="font-size: 3rem; color: #2dd4bf; margin-bottom: 10px;"></i>
            <h2 style="margin: 0; font-size: 1.4rem; color: #fff;">Assinatura Digital da Nota de Arqueação</h2>
            <p style="margin: 6px 0 0; color: #94a3b8; font-size: 0.9rem;">
                <?= h($nar['numero']) ?> &bull; <?= h($nar['nome_embarcacao']) ?>
            </p>
        </div>

        <?php if (!empty($nar['assinado'])): ?>
            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; border-radius: 8px; padding: 20px; text-align: center;">
                <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: #22c55e; margin-bottom: 10px;"></i>
                <h3 style="margin: 0 0 6px; color: #86efac;">Documento Assinado Digitalmente</h3>
                <p style="margin: 0 0 16px; font-size: 0.85rem; color: #cbd5e1;">
                    Assinado por <strong><?= h($nar['assinante_nome']) ?></strong> em <?= date('d/m/Y H:i', strtotime($nar['assinatura_em'])) ?>.
                </p>
                <a href="<?= APP_URL ?>documentacao/nar/pdf?id=<?= urlencode($nar['id']) ?>" target="_blank" class="btn-sign" style="display:inline-block; text-decoration:none;">
                    <i class="fa-solid fa-file-pdf"></i> Visualizar PDF Autenticado
                </a>
            </div>
        <?php else: ?>
            <div style="background: #0f172a; border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 0.85rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 4px;">
                    <span style="color:#94a3b8;">Embarcação:</span>
                    <strong><?= h($nar['nome_embarcacao']) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 4px;">
                    <span style="color:#94a3b8;">Arqueação:</span>
                    <strong>AB <?= (int)$nar['arqueacao_bruta_ab'] ?> / AL <?= (int)$nar['arqueacao_liquida_al'] ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#94a3b8;">Responsável Técnico:</span>
                    <strong><?= h($nar['assinante_nome'] ?: 'Tecnólogo Naval') ?></strong>
                </div>
            </div>

            <?php if ($meuResp && !empty($meuResp['assinatura_arquivo'])): ?>
                <div style="background: rgba(14, 165, 233, 0.12); border: 1px solid #0284c7; border-radius: 8px; padding: 16px; margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 0.88rem; color: #7dd3fc; margin-bottom: 10px;">
                        <i class="fa-solid fa-id-card-clip"></i> Responsável Técnico Autenticado: <strong><?= h($meuResp['nome_completo']) ?></strong>
                        <div style="font-size: 0.78rem; color: #94a3b8;"><?= h($meuResp['cargo_titulo']) ?> &bull; <?= h($meuResp['registro_profissional']) ?></div>
                    </div>
                    <button type="button" class="btn-sign" id="btnAssinarPerfil" style="background: #0284c7;" onclick="assinarComPerfilCadastrado()">
                        <i class="fa-solid fa-stamp"></i> Assinar com Minha Assinatura Digital Cadastrada
                    </button>
                </div>
                <div style="text-align: center; color: #64748b; font-size: 0.8rem; margin: 12px 0 16px;">— ou desenhe manualmente no quadro abaixo —</div>
            <?php endif; ?>

            <label style="font-size:0.85rem; font-weight:700; color:#e2e8f0;">Desenhe sua assinatura no quadro abaixo:</label>
            <div class="canvas-container">
                <canvas id="signatureCanvas" width="540" height="160" style="width:100%; height:160px;"></canvas>
            </div>

            <div style="display:flex; justify-content:space-between; margin-bottom: 16px;">
                <button type="button" onclick="limparCanvas()" style="background:none; border:none; color:#94a3b8; font-size:0.82rem; cursor:pointer;">
                    <i class="fa-solid fa-eraser"></i> Limpar assinatura
                </button>
            </div>

            <button type="button" class="btn-sign" id="btnConfirmar" onclick="salvarAssinatura()">
                <i class="fa-solid fa-signature"></i> Confirmar e Assinar Nota de Arqueação
            </button>
        <?php endif; ?>
    </div>

    <script>
    const canvas = document.getElementById('signatureCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let painting = false;

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
        }

        function startPosition(e) { painting = true; draw(e); }
        function endPosition() { painting = false; ctx.beginPath(); }
        function draw(e) {
            if (!painting) return;
            e.preventDefault();
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#0f172a';
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        canvas.addEventListener('mousedown', startPosition);
        canvas.addEventListener('mouseup', endPosition);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('touchstart', startPosition);
        canvas.addEventListener('touchend', endPosition);
        canvas.addEventListener('touchmove', draw);
    }

    function limparCanvas() {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function salvarAssinatura() {
        const dataUrl = canvas.toDataURL('image/png');
        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando Assinatura...';

        const formData = new FormData();
        formData.append('acao', 'confirmar_assinatura');
        formData.append('assinatura_imagem', dataUrl);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                alert(res.mensagem);
                window.location.reload();
            } else {
                alert(res.mensagem || 'Falha ao assinar.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-signature"></i> Confirmar e Assinar Nota de Arqueação';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao processar assinatura.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-signature"></i> Confirmar e Assinar Nota de Arqueação';
        });
    }

    function assinarComPerfilCadastrado() {
        const btn = document.getElementById('btnAssinarPerfil');
        if (!confirm('Deseja assinar esta Nota de Arqueação utilizando seu perfil de assinatura digital cadastrado no sistema?')) {
            return;
        }
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Assinando com Perfil...';
        }

        const formData = new FormData();
        formData.append('acao', 'confirmar_assinatura_perfil');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                alert(res.mensagem);
                window.location.reload();
            } else {
                alert(res.mensagem || 'Falha ao assinar.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-stamp"></i> Assinar com Minha Assinatura Digital Cadastrada';
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao processar assinatura.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-stamp"></i> Assinar com Minha Assinatura Digital Cadastrada';
            }
        });
    }
    </script>
</body>
</html>
