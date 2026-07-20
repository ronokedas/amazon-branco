<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
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

// Buscar proposta pelo token
try {
    require_once __DIR__ . '/../../../config.php'; // Garantir $pdo
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS cliente_nome 
        FROM propostas p 
        INNER JOIN clientes c ON c.id = p.cliente_id 
        WHERE p.token_assinatura = :token
    ");
    $stmt->execute([':token' => $token]);
    $prop = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao buscar proposta.");
}

if (!$prop) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Proposta Não Encontrada</title></head><body style="font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f0f0;"><div style="text-align:center;padding:40px;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);"><h1 style="color:#d32f2f;">Proposta Não Encontrada</h1><p>A proposta associada a este link não foi encontrada ou foi removida.</p></div></body></html>';
    exit;
}

// Processar assinatura (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_assinatura') {
    header('Content-Type: application/json');

    if ($prop['assinado']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta proposta já foi assinada.']);
        exit;
    }

    $assinatura_imagem = $_POST['assinatura_imagem'] ?? '';
    $assinante_nome = $_POST['assinante_nome'] ?? $prop['cliente_nome'];
    $assinante_documento = $_POST['assinante_documento'] ?? '';

    if (empty($assinatura_imagem)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma assinatura foi desenhada.']);
        exit;
    }

    // Validar e normalizar o PNG real recebido do canvas.
    $imagem_assinatura = carregarImagemAssinatura($assinatura_imagem, '');
    if ($imagem_assinatura === null || $imagem_assinatura['mime'] !== 'image/png') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de assinatura inválido.']);
        exit;
    }
    $assinatura_imagem = 'data:image/png;base64,' . base64_encode($imagem_assinatura['bytes']);

    $ip_assinatura = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $data_assinatura = date('Y-m-d H:i:s');

    // Fazer upload do binário para o Storage (MinIO)
    $url_assinatura = upload_to_storage($assinatura_imagem, 'assinaturas/propostas');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE propostas SET 
                                    assinatura_url = :img_url,
                                    assinatura_imagem = :img_data,
                                    assinatura_ip = :ip,
                                    assinatura_em = :data,
                                    assinante_nome = :nome,
                                    assinante_documento = :doc,
                                    assinado = 1,
                                    status = 'assinada'
                                WHERE id = :id AND assinado = 0");
        $stmt->execute([
            ':img_url' => $url_assinatura !== '' ? $url_assinatura : null,
            ':img_data' => $assinatura_imagem,
            ':ip'      => $ip_assinatura,
            ':data'    => $data_assinatura,
            ':nome'    => $assinante_nome,
            ':doc'     => $assinante_documento,
            ':id'      => $prop['id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Esta proposta ja foi assinada ou alterada por outro acesso.');
        }

        // GATILHO 1: Lançamento no Financeiro
        $stmtFin = $pdo->prepare("INSERT INTO financeiro_lancamentos 
            (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, valor_original, saldo_devedor, data, categoria, observacoes, criado_por)
            VALUES (UUID(), 'RECEITA', 'unica', 'PENDENTE', DATE_ADD(CURDATE(), INTERVAL 15 DAY), :cliente_id, :descricao, :valor, :valor_original, :saldo_devedor, CURDATE(), 'SERVIÇOS', :observacoes, :criado_por)");
        $stmtFin->execute([
            ':cliente_id'  => $prop['cliente_id'],
            ':descricao'   => 'Referente à Proposta Comercial nº ' . $prop['numero'],
            ':valor'       => $prop['valor_total'],
            ':valor_original' => $prop['valor_total'],
            ':saldo_devedor' => $prop['valor_total'],
            ':observacoes' => 'Lançamento gerado automaticamente após assinatura da proposta.',
            ':criado_por'  => $prop['criado_por'] ?? null
        ]);

        // GATILHO 2: Rascunho no Agendamentos
        $stmtEmb = $pdo->prepare("
            SELECT pe.embarcacao_id, GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos_nomes
            FROM propostas_embarcacoes pe
            LEFT JOIN propostas_servicos ps ON ps.proposta_id = pe.proposta_id AND ps.embarcacao_id = pe.embarcacao_id
            LEFT JOIN servicos s ON ps.servico_id = s.id
            WHERE pe.proposta_id = :proposta_id
            GROUP BY pe.embarcacao_id
        ");
        $stmtEmb->execute([':proposta_id' => $prop['id']]);
        $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        $stmtAgendamento = $pdo->prepare("
            INSERT INTO agendamentos (
                id, proposta_id, embarcacao_id, cliente_id, vendedor_id,
                tipo_vistoria, contato_nome, contato_telefone, status, observacoes, criado_por, data_vistoria, hora_vistoria
            ) VALUES (
                :id, :proposta_id, :embarcacao_id, :cliente_id, :vendedor_id,
                :tipo_vistoria, :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por, NULL, NULL
            )
        ");

        foreach ($embarcacoes as $emb) {
            $agend_id = gerarUUID();
            $stmtAgendamento->execute([
                ':id'            => $agend_id,
                ':proposta_id'   => $prop['id'],
                ':embarcacao_id' => $emb['embarcacao_id'],
                ':cliente_id'    => $prop['cliente_id'],
                ':vendedor_id'   => $prop['criado_por'] ?? null,
                ':tipo_vistoria' => !empty($emb['servicos_nomes']) ? $emb['servicos_nomes'] : 'Vistoria Geral',
                ':contato_nome'  => $prop['responsavel_fechamento_nome'] ?? null,
                ':contato_telefone' => $prop['responsavel_fechamento_telefone'] ?? null,
                ':observacoes'   => 'Agendamento gerado automaticamente a partir da proposta assinada. Favor definir data e vistoriador.',
                ':criado_por'    => $prop['criado_por'] ?? null
            ]);
        }

        $pdo->commit();

        // Log
        if (function_exists('log_atividade')) {
            log_atividade('proposta_assinada', "Proposta {$prop['numero']} assinada por {$assinante_nome} via link público");
        }

        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Assinatura confirmada com sucesso!',
            'nome'     => $assinante_nome,
            'data'     => date('d/m/Y H:i:s', strtotime($data_assinatura)),
            'ip'       => $ip_assinatura,
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro ao assinar proposta: ' . $e->getMessage());
        $mensagem = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Nao foi possivel concluir a assinatura. Tente novamente.';
        echo json_encode(['sucesso' => false, 'mensagem' => $mensagem]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Proposta - <?php echo h($prop['numero']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0891b2 0%, #0369a1 50%, #0891b2 100%);
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
            background: linear-gradient(135deg, #0891b2, #0369a1);
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
            color: #0369a1;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .canvas-wrapper {
            display: inline-block;
            border: 2px dashed #0369a1;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
            margin-bottom: 15px;
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
            background: #0369a1;
            color: #fff;
        }
        .btn-primary:hover { background: #0284c7; }
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
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Assinatura Digital - Proposta Comercial</h1>
            <div class="numero">Proposta: <?php echo h($prop['numero']); ?></div>
        </div>

        <div class="body">
            <!-- Informações da proposta -->
            <div class="info-row">
                <span class="info-label">Cliente:</span>
                <span class="info-valor"><?php echo h($prop['cliente_nome']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Emissão:</span>
                <span class="info-valor"><?php echo date('d/m/Y', strtotime($prop['data_emissao'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Valor Total:</span>
                <span class="info-valor">R$ <?php echo number_format($prop['valor_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Entrada:</span>
                <span class="info-valor">R$ <?php echo number_format((float)($prop['valor_entrada'] ?? 0), 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Saldo Restante:</span>
                <span class="info-valor">R$ <?php echo number_format(max(0, (float)$prop['valor_total'] - (float)($prop['valor_entrada'] ?? 0)), 2, ',', '.'); ?></span>
            </div>

            <?php if ($prop['assinado']): ?>
                <?php
                $imagemAssinaturaExibicao = carregarImagemAssinatura(
                    $prop['assinatura_imagem'] ?? '',
                    $prop['assinatura_url'] ?? ''
                );
                $assinaturaSrc = $imagemAssinaturaExibicao !== null
                    ? 'data:' . $imagemAssinaturaExibicao['mime'] . ';base64,' . base64_encode($imagemAssinaturaExibicao['bytes'])
                    : '';
                ?>
                <!-- JÁ ASSINADO -->
                <div class="assinado-box">
                    <h2>✔️ Proposta Já Assinada</h2>
                    <p>Esta proposta já foi assinada digitalmente e encontra-se Aprovada.</p>
                    <?php if ($assinaturaSrc !== ''): ?>
                        <img src="<?php echo h($assinaturaSrc); ?>" alt="Assinatura" class="assinatura-img">
                    <?php else: ?>
                        <p class="msg-erro" style="display:block;">A imagem da assinatura não está disponível. Será necessário coletar a assinatura novamente.</p>
                    <?php endif; ?>
                    <div class="detalhes">
                        <p><strong>Assinado por:</strong> <?php echo h($prop['assinante_nome']); ?></p>
                        <p><strong>Documento:</strong> <?php echo h($prop['assinante_documento']); ?></p>
                        <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s', strtotime($prop['assinatura_em'])); ?></p>
                        <p><strong>IP:</strong> <?php echo h($prop['assinatura_ip']); ?></p>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo h($prop['id']); ?>" 
                           class="btn btn-success" target="_blank" style="text-decoration:none;">
                            📄 Ver PDF Assinado
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- ASSINATURA -->
                <div class="canvas-area" id="areaAssinatura">
                    <h3>Dados do Assinante</h3>
                    <div class="form-group">
                        <label>Nome Completo do Assinante</label>
                        <input type="text" id="assinante_nome" value="<?php echo h($prop['cliente_nome']); ?>">
                    </div>
                    <div class="form-group">
                        <label>CPF/CNPJ do Assinante</label>
                        <input type="text" id="assinante_documento" placeholder="Opcional">
                    </div>

                    <h3>Desenhe sua assinatura abaixo</h3>
                    <div class="canvas-wrapper">
                        <canvas id="canvasAssinatura" width="400" height="150"></canvas>
                    </div>
                    <div class="canvas-actions">
                        <button type="button" class="btn btn-secondary" onclick="limparCanvas()">
                            🗑️ Limpar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnConfirmar" onclick="confirmarAssinatura()">
                            ✔️ Confirmar Assinatura
                        </button>
                    </div>
                    <div class="msg-erro" id="msgErro"></div>
                    
                    <div style="margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo h($prop['id']); ?>" 
                           class="btn btn-secondary" target="_blank" style="text-decoration:none;">
                            📄 Ver PDF da Proposta
                        </a>
                    </div>
                </div>

                <!-- Mensagem de sucesso (inicialmente oculta) -->
                <div class="msg-sucesso" id="msgSucesso">
                    <div class="check">✔️</div>
                    <h2>Assinatura Confirmada!</h2>
                    <p>A proposta foi assinada e aprovada com sucesso.</p>
                    <div id="detalhesAssinatura" style="margin-top: 15px;"></div>
                    <div style="margin-top: 20px;">
                        <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo h($prop['id']); ?>" 
                           class="btn btn-success" target="_blank" style="text-decoration:none;">
                            📄 Ver PDF Assinado
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Amazon Naval Ltda — Proposta de Serviços</p>
        </div>
    </div>

    <?php if (!$prop['assinado']): ?>
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

        const nome = document.getElementById('assinante_nome').value;
        if (!nome.trim()) {
            showError('Por favor, informe o nome do assinante.');
            return;
        }

        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerHTML = 'Processando...';

        const imagem = canvas.toDataURL('image/png');
        const doc = document.getElementById('assinante_documento').value;

        const formData = new FormData();
        formData.append('acao', 'confirmar_assinatura');
        formData.append('assinatura_imagem', imagem);
        formData.append('assinante_nome', nome);
        formData.append('assinante_documento', doc);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Ao invés de mostrar uma msg estática, atualiza a página para carregar o novo estado com o PDF
                window.location.reload();
            } else {
                if (data.mensagem && data.mensagem.includes('já foi assinada')) {
                    // Se já estiver assinada no banco, atualiza a página para mostrar
                    window.location.reload();
                } else {
                    showError(data.mensagem);
                    btn.disabled = false;
                    btn.innerHTML = '✔️ Confirmar Assinatura';
                }
            }
        })
        .catch(error => {
            showError('Erro ao comunicar com o servidor. Tente novamente.');
            btn.disabled = false;
            btn.innerHTML = '✔️ Confirmar Assinatura';
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
