<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: actions.php - Processar criação de propostas + endpoint AJAX
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/financeiro_escritorios.php';
require_once __DIR__ . '/../../../includes/analise_planos.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// === ENDPOINT AJAX: Carregar embarcações do cliente ===
if ($action === 'embarcacoes_cliente') {
    verificar_sessao();
    header('Content-Type: application/json; charset=utf-8');

    if (!podeAcessar('comercial')) {
        echo json_encode(['error' => 'Acesso negado.']);
        exit;
    }

    $cliente_id = $_GET['cliente_id'] ?? '';

    if (empty($cliente_id)) {
        echo json_encode(['error' => 'cliente_id não informado.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT e.id, e.nome, e.registro
            FROM embarcacoes e
            INNER JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.status='ATIVO'
            WHERE ce.cliente_id = :cliente_id AND e.ativo = 1
            ORDER BY e.nome ASC
        ");
        $stmt->execute([':cliente_id' => $cliente_id]);
        $embarcacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['embarcacoes' => $embarcacoes]);
        exit;
    } catch (Exception $e) {
        error_log('Erro ao buscar embarcações do cliente: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro ao carregar embarcações.']);
        exit;
    }
}

// === DAQUI EM DIANTE: Ações via POST ===
verificar_sessao();
exigirAcesso('comercial');

// Validar CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'comercial/propostas');
    }
}

function normalizarTextoLog(?string $texto, int $limite = 2000): ?string
{
    if ($texto === null) {
        return null;
    }

    $texto = (string) $texto;

    if (function_exists('iconv')) {
        $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $texto);
        if ($convertido !== false) {
            $texto = $convertido;
        }
    }

    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $texto);
    if (!is_string($texto)) {
        $texto = '';
    }

    $texto = trim(preg_replace('/\s+/u', ' ', $texto));
    if ($texto === '') {
        return null;
    }

    return mb_substr($texto, 0, $limite, 'UTF-8');
}

function gerarEfeitosPropostaAssinada(PDO $pdo, array $prop, ?string $criado_por, bool $manual = false): void
{
    $descricaoFinanceiro = 'Referente à Proposta Comercial nº ' . $prop['numero'];
    $stmtFinExiste = $pdo->prepare("
        SELECT COUNT(*)
        FROM financeiro_lancamentos
        WHERE cliente_id = :cliente_id
          AND tipo = 'RECEITA'
          AND descricao = :descricao
          AND ativo = 1
    ");
    $stmtFinExiste->execute([
        ':cliente_id' => $prop['cliente_id'],
        ':descricao' => $descricaoFinanceiro,
    ]);

    if ((int)$stmtFinExiste->fetchColumn() === 0) {
        $escritorioLancamento = (string)($prop['escritorio_id'] ?? '');
        if ($escritorioLancamento === '') {
            throw new RuntimeException('A proposta não possui um escritório responsável.');
        }
        $responsavelVendaId = financeiroResponsavelVenda($pdo, $prop['criado_por'] ?? null);
        $stmtFin = $pdo->prepare("INSERT INTO financeiro_lancamentos
            (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, valor_original, saldo_devedor, data, categoria, observacoes, criado_por, escritorio_id, responsavel_usuario_id, proposta_id)
            VALUES (UUID(), 'RECEITA', 'unica', 'PENDENTE', DATE_ADD(CURDATE(), INTERVAL 15 DAY), :cliente_id, :descricao, :valor, :valor_original, :saldo_devedor, CURDATE(), 'SERVIÇOS', :observacoes, :criado_por, :escritorio, :responsavel, :proposta)");
        $stmtFin->execute([
            ':cliente_id' => $prop['cliente_id'],
            ':descricao' => $descricaoFinanceiro,
            ':valor' => $prop['valor_total'],
            ':valor_original' => $prop['valor_total'],
            ':saldo_devedor' => $prop['valor_total'],
            ':observacoes' => $manual
                ? 'Lançamento gerado automaticamente após aprovação interna da proposta.'
                : 'Lançamento gerado automaticamente após assinatura da proposta.',
            ':criado_por' => $criado_por,
            ':escritorio' => $escritorioLancamento,
            ':responsavel' => $responsavelVendaId,
            ':proposta' => $prop['id'],
        ]);
    }

    $stmtEmb = $pdo->prepare("
        SELECT pe.embarcacao_id,
               GROUP_CONCAT(CASE WHEN COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2') THEN s.nome END SEPARATOR ', ') AS servicos_nomes,
               SUM(CASE WHEN COALESCE(s.codigo_operacional,'') NOT IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2') THEN 1 ELSE 0 END) AS servicos_vistoria
        FROM propostas_embarcacoes pe
        LEFT JOIN propostas_servicos ps ON ps.proposta_id = pe.proposta_id AND ps.embarcacao_id = pe.embarcacao_id
        LEFT JOIN servicos s ON ps.servico_id = s.id
        WHERE pe.proposta_id = :proposta_id
        GROUP BY pe.embarcacao_id
        HAVING servicos_vistoria > 0
    ");
    $stmtEmb->execute([':proposta_id' => $prop['id']]);
    $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

    $stmtAgExiste = $pdo->prepare("
        SELECT COUNT(*)
        FROM agendamentos
        WHERE proposta_id = :proposta_id
          AND embarcacao_id = :embarcacao_id
    ");
    $stmtAgendamento = $pdo->prepare("
        INSERT INTO agendamentos (
            id, proposta_id, embarcacao_id, cliente_id, vendedor_id,
            tipo_vistoria, contato_nome, contato_telefone, status, observacoes, criado_por, data_vistoria, hora_vistoria
        ) VALUES (
            UUID(), :proposta_id, :embarcacao_id, :cliente_id, :vendedor_id,
            :tipo_vistoria, :contato_nome, :contato_telefone, 'pendente', :observacoes, :criado_por, NULL, NULL
        )
    ");

    foreach ($embarcacoes as $emb) {
        $stmtAgExiste->execute([
            ':proposta_id' => $prop['id'],
            ':embarcacao_id' => $emb['embarcacao_id'],
        ]);
        if ((int)$stmtAgExiste->fetchColumn() > 0) {
            continue;
        }

        $stmtAgendamento->execute([
            ':proposta_id' => $prop['id'],
            ':embarcacao_id' => $emb['embarcacao_id'],
            ':cliente_id' => $prop['cliente_id'],
            ':vendedor_id' => $prop['criado_por'] ?? null,
            ':tipo_vistoria' => !empty($emb['servicos_nomes']) ? $emb['servicos_nomes'] : 'Vistoria Geral',
            ':contato_nome' => $prop['responsavel_fechamento_nome'] ?? null,
            ':contato_telefone' => $prop['responsavel_fechamento_telefone'] ?? null,
            ':observacoes' => $manual
                ? 'Agendamento gerado automaticamente a partir da aprovação interna da proposta. Favor definir data e vistoriador.'
                : 'Agendamento gerado automaticamente a partir da proposta assinada. Favor definir data e vistoriador.',
            ':criado_por' => $criado_por,
        ]);
    }

    analisePlanosCriarDemandasProposta($pdo, $prop, $criado_por);
}

switch ($action) {

    case 'criar':
        try {
            $escritorioId = financeiroResolverEscritorio($pdo, $_POST['escritorio_id'] ?? null);
            if ($escritorioId === 'todos') throw new RuntimeException('Selecione o escritório da proposta.');
            $cliente_json = $_POST['dados_cliente'] ?? '{}';
            $clienteData  = json_decode($cliente_json, true);
            $cliente_id   = $clienteData['id'] ?? '';
            $cliente_nome = $clienteData['nome'] ?? 'Desconhecido';
            $responsavel_fechamento_nome = trim(sanitizar($_POST['responsavel_fechamento_nome'] ?? ''));
            $responsavel_fechamento_telefone = trim(sanitizar($_POST['responsavel_fechamento_telefone'] ?? ''));

            // Novo formato: serviços por embarcação via JSON
            $dados_servicos_json = $_POST['dados_servicos_json'] ?? '[]';
            $dadosServicos       = json_decode($dados_servicos_json, true);
            if (!is_array($dadosServicos)) $dadosServicos = [];

            $tipo_desconto       = $_POST['tipo_desconto'] ?? 'perc';
            $desconto_input      = (float)($_POST['desconto_global'] ?? 0);
            $parcelas            = max(1, min(12, (int)($_POST['parcelas'] ?? 3)));
            $valor_entrada_input = (float)($_POST['valor_entrada'] ?? 0);

            if (empty($cliente_id)) {
                setMensagem('error', 'Cliente não selecionado.');
                redirecionar(APP_URL . 'comercial/nova');
            }

            if (empty($dadosServicos)) {
                setMensagem('error', 'Selecione pelo menos uma embarcação com serviços.');
                redirecionar(APP_URL . 'comercial/nova');
            }

            $pdo->beginTransaction();

            // 1. Gerar número da proposta
            $numero = gerarNumeroDocumento('ORC', 'AM-ORC');

            // 2. Calcular valores com base no JSON recebido
            $subtotal_geral  = 0;
            $embarcacoes_ids = [];
            $servicos_a_inserir = []; // [{ servico_id, embarcacao_id, preco_aplicado, quantidade }]

            $stmtPreco = $pdo->prepare("SELECT id, preco_padrao FROM servicos WHERE id = :id AND ativo = 1");

            foreach ($dadosServicos as $embData) {
                $emb_id = $embData['embarcacao_id'] ?? '';
                if (empty($emb_id)) continue;
                $embarcacoes_ids[] = $emb_id;

                $servicos = $embData['servicos'] ?? [];
                foreach ($servicos as $sv) {
                    // Buscar preço real do banco (não confiar no frontend)
                    $stmtPreco->execute([':id' => $sv['servico_id'] ?? '']);
                    $servDb = $stmtPreco->fetch(PDO::FETCH_ASSOC);
                    if (!$servDb) continue;

                    $qtd    = max(1, (int)($sv['quantidade'] ?? 1));
                    $preco  = (float)$servDb['preco_padrao'];
                    $sub    = round($preco * $qtd, 2);
                    $subtotal_geral += $sub;

                    $servicos_a_inserir[] = [
                        'servico_id'     => $servDb['id'],
                        'embarcacao_id'  => $emb_id,
                        'preco_aplicado' => $preco,
                        'quantidade'     => $qtd,
                    ];
                }
            }

            if ($subtotal_geral <= 0) {
                throw new Exception('Nenhum serviço com preço válido foi selecionado.');
            }

            // Calcular desconto e total
            if ($tipo_desconto === 'valor') {
                $desconto_valor = max(0, min($subtotal_geral, round($desconto_input, 2)));
                $desconto_percentual = ($subtotal_geral > 0) ? round(($desconto_valor / $subtotal_geral) * 100, 2) : 0;
            } else {
                $desconto_percentual = max(0, min(100, $desconto_input));
                $desconto_valor = round($subtotal_geral * ($desconto_percentual / 100), 2);
            }
            $valor_total = round($subtotal_geral - $desconto_valor, 2);
            $valor_entrada = round(max(0, min($valor_total, $valor_entrada_input)), 2);

            // Forma de pagamento e observações
            $forma_pagamento = $_POST['forma_pagamento'] ?? 'parcelado';
            $formas_validas   = ['a_vista', 'parcelado', 'boleto', 'pix'];
            if (!in_array($forma_pagamento, $formas_validas, true)) {
                $forma_pagamento = 'parcelado';
            }
            $observacoes = trim(sanitizar($_POST['observacoes'] ?? '')) ?: null;

            // 3. Inserir a proposta
            $token_assinatura = md5(uniqid(rand(), true)) . uniqid();
            
            $stmtProp = $pdo->prepare("
                INSERT INTO propostas (id, numero, cliente_id, responsavel_fechamento_nome, responsavel_fechamento_telefone, data_emissao, data_validade, parcelas, forma_pagamento, valor_total, valor_entrada, desconto_percentual, desconto_valor, observacoes, status, criado_por, token_assinatura, escritorio_id)
                VALUES (UUID(), :numero, :cliente_id, :responsavel_fechamento_nome, :responsavel_fechamento_telefone, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), :parcelas, :forma_pagamento, :valor_total, :valor_entrada, :desconto_percentual, :desconto_valor, :observacoes, 'rascunho', :criado_por, :token_assinatura, :escritorio_id)
            ");
            $stmtProp->execute([
                ':numero'              => $numero,
                ':cliente_id'          => $cliente_id,
                ':responsavel_fechamento_nome' => $responsavel_fechamento_nome ?: null,
                ':responsavel_fechamento_telefone' => $responsavel_fechamento_telefone ?: null,
                ':parcelas'            => $parcelas,
                ':forma_pagamento'     => $forma_pagamento,
                ':valor_total'         => $valor_total,
                ':valor_entrada'       => $valor_entrada,
                ':desconto_percentual' => $desconto_percentual,
                ':desconto_valor'      => $desconto_valor,
                ':observacoes'         => $observacoes,
                ':criado_por'          => $_SESSION['usuario_id'],
                ':token_assinatura'    => $token_assinatura,
                ':escritorio_id'       => $escritorioId,
            ]);

            // Pegar o ID da proposta recém-criada
            $proposta_id_stmt = $pdo->prepare("SELECT id FROM propostas WHERE numero = :numero");
            $proposta_id_stmt->execute([':numero' => $numero]);
            $proposta_id = $proposta_id_stmt->fetchColumn();

            if (!$proposta_id) {
                throw new Exception('Erro ao recuperar ID da proposta.');
            }
            // 4. Vincular embarcações (únicas)
            $stmtEmb = $pdo->prepare("INSERT INTO propostas_embarcacoes (id, proposta_id, embarcacao_id) VALUES (UUID(), :proposta_id, :embarcacao_id)");
            foreach (array_unique($embarcacoes_ids) as $emb_id) {
                $stmtEmb->execute([
                    ':proposta_id'   => $proposta_id,
                    ':embarcacao_id' => $emb_id,
                ]);
            }

            // 5. Vincular serviços com preços aplicados + embarcacao_id
            $stmtServ = $pdo->prepare("
                INSERT INTO propostas_servicos (id, proposta_id, servico_id, embarcacao_id, preco_aplicado, quantidade)
                VALUES (UUID(), :proposta_id, :servico_id, :embarcacao_id, :preco_aplicado, :quantidade)
            ");
            foreach ($servicos_a_inserir as $sp) {
                $stmtServ->execute([
                    ':proposta_id'    => $proposta_id,
                    ':servico_id'     => $sp['servico_id'],
                    ':embarcacao_id'  => $sp['embarcacao_id'],
                    ':preco_aplicado' => $sp['preco_aplicado'],
                    ':quantidade'     => $sp['quantidade'],
                ]);
            }

            $pdo->commit();

            log_atividade('proposta_criada', "Proposta {$numero} criada para cliente '{$cliente_nome}'. Subtotal: R$ " . number_format($subtotal_geral, 2, ',', '.') . " | Desconto: {$desconto_percentual}% | Entrada: R$ " . number_format($valor_entrada, 2, ',', '.') . " | Total: R$ " . number_format($valor_total, 2, ',', '.'));
            setMensagem('success', "Proposta {$numero} criada com sucesso!");
            redirecionar(APP_URL . 'comercial?nova_proposta=' . urlencode($proposta_id));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao criar proposta: ' . $e->getMessage());
            $mensagem = $e instanceof PDOException
                ? 'Não foi possível salvar a proposta. Verifique os dados e tente novamente.'
                : $e->getMessage();
            setMensagem('error', 'Erro ao criar proposta: ' . $mensagem);
            redirecionar(APP_URL . 'comercial/nova');
        }
        break;

    case 'enviar_proposta':
        try {
            $proposta_id = $_POST['id'] ?? '';
            if (empty($proposta_id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial/propostas');
            }

            // Buscar dados da proposta + cliente
            $stmt = $pdo->prepare("
                SELECT p.*, c.nome AS cliente_nome, c.email AS cliente_email,
                       c.cpf_cnpj AS cliente_cpfcnpj, c.telefone AS cliente_telefone
                FROM propostas p
                INNER JOIN clientes c ON c.id = p.cliente_id
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $proposta_id]);
            $proposta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$proposta) {
                setMensagem('error', 'Proposta não encontrada.');
                redirecionar(APP_URL . 'comercial/propostas');
            }

            if (empty($proposta['cliente_email'])) {
                setMensagem('error', 'Cliente não possui e-mail cadastrado para envio.');
                redirecionar(APP_URL . 'comercial/propostas');
            }

            // Carregar template HTML
            $templatePath = __DIR__ . '/../../../templates/email/proposta.html';
            if (!file_exists($templatePath)) {
                throw new Exception('Template de e-mail não encontrado.');
            }
            $htmlBody = file_get_contents($templatePath);

            // Substituir placeholders
            $valorTotal = 'R$ ' . number_format((float)$proposta['valor_total'], 2, ',', '.');
            $valorEntrada = (float)($proposta['valor_entrada'] ?? 0);
            $saldoRestante = max(0, (float)$proposta['valor_total'] - $valorEntrada);
            $parcelasQtd = max(1, (int)$proposta['parcelas']);
            $parcelasTexto = $parcelasQtd . 'x de R$ ' . number_format($saldoRestante / $parcelasQtd, 2, ',', '.');
            if ($valorEntrada > 0) {
                $parcelasTexto = 'Entrada de R$ ' . number_format($valorEntrada, 2, ',', '.') . ' + ' . $parcelasTexto;
            }

            $replacements = [
                '{{NOME_CLIENTE}}'    => h($proposta['cliente_nome']),
                '{{NUMERO_PROPOSTA}}' => h($proposta['numero']),
                '{{VALOR_TOTAL}}'     => $valorTotal,
                '{{PARCELAS}}'        => $parcelasTexto,
                '{{PROPOSTA_ID}}'     => h($proposta_id),
                '{{APP_URL}}'         => APP_URL,
                '{{BANCO_NOME}}'      => BANCO_NOME,
                '{{BANCO_AGENCIA}}'   => BANCO_AGENCIA,
                '{{BANCO_CONTA}}'     => BANCO_CONTA,
                '{{PIX_CHAVE}}'       => PIX_CHAVE,
                '{{EMAIL_CONTATO}}'   => EMAIL_CONTATO,
                '{{TELEFONE_CONTATO}}'=> TELEFONE_CONTATO,
            ];
            $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

            // Gerar PDF para anexar
            require_once __DIR__ . '/../../../includes/proposta_pdf.php';
            $pdfFile = salvarPropostaPdfTemporario($proposta_id, __DIR__ . '/../../../temp_pdf/');
            $pdfContent = null;
            if (!file_exists($pdfFile)) {

            // Gerar PDF via requisição HTTP interna com cookie de sessão
            $pdfUrl = APP_URL . 'comercial/pdf?id=' . urlencode($proposta_id);
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Cookie: " . session_name() . "=" . session_id() . "\r\n",
                    'timeout' => 30,
                ]
            ];
            $context = stream_context_create($opts);
            $pdfContent = @file_get_contents($pdfUrl, false, $context);
            }
            if (file_exists($pdfFile)) {
                $pdfContent = file_get_contents($pdfFile);
            }

            if ($pdfContent && strlen($pdfContent) > 200) {
                file_put_contents($pdfFile, $pdfContent);
            } else {
                throw new Exception('Não foi possível gerar o PDF da proposta para anexar.');
            }

            // Incluir mailer e enviar
            require_once __DIR__ . '/../../../includes/mailer.php';

            $resultado = enviarEmail(
                $proposta['cliente_email'],
                $proposta['cliente_nome'],
                'Proposta Comercial - ' . $proposta['numero'],
                $htmlBody,
                [$pdfFile]
            );

            // Registrar no log de e-mails sem interromper o fluxo principal
            try {
                $stmtLog = $pdo->prepare("
                    INSERT INTO email_logs (id, destinatario, assunto, tipo, referencia_tipo, referencia_id, status, mensagem_erro, enviado_por)
                    VALUES (UUID(), :destinatario, :assunto, 'proposta', 'propostas', :referencia_id, :status, :mensagem_erro, :enviado_por)
                ");
                $stmtLog->execute([
                    ':destinatario'  => $proposta['cliente_email'],
                    ':assunto'       => 'Proposta Comercial - ' . $proposta['numero'],
                    ':referencia_id' => $proposta_id,
                    ':status'        => $resultado['success'] ? 'enviado' : 'erro',
                    ':mensagem_erro' => $resultado['success'] ? null : normalizarTextoLog($resultado['message']),
                    ':enviado_por'   => $_SESSION['usuario_id'],
                ]);
            } catch (Throwable $e) {
                error_log('Erro ao registrar log de e-mail da proposta ' . $proposta_id . ': ' . $e->getMessage());
            }

            // Limpar PDF temporário
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }

            // Atualizar status da proposta para 'enviada'
            if ($resultado['success']) {
                $stmtUp = $pdo->prepare("UPDATE propostas SET status = 'enviada' WHERE id = :id AND status = 'rascunho'");
                $stmtUp->execute([':id' => $proposta_id]);
            }

            if ($resultado['success']) {
                log_atividade('proposta_enviada_email', "Proposta {$proposta['numero']} enviada por e-mail para {$proposta['cliente_email']}");
                setMensagem('success', "Proposta {$proposta['numero']} enviada com sucesso para {$proposta['cliente_email']}!");
            } else {
                setMensagem('error', 'Erro ao enviar: ' . $resultado['message']);
            }

            redirecionar(APP_URL . 'comercial');

        } catch (Exception $e) {
            error_log('Erro ao enviar proposta por e-mail: ' . $e->getMessage());
            setMensagem('error', 'Erro ao enviar proposta: ' . $e->getMessage());
            redirecionar(APP_URL . 'comercial');
        }
        break;

    case 'aprovar_proposta':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial/propostas');
            }
            $stmt = $pdo->prepare("UPDATE propostas SET status = 'aprovada' WHERE id = :id AND status IN ('rascunho','enviada')");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                setMensagem('warning', 'Proposta não encontrada ou já está em outro status.');
            } else {
                log_atividade('proposta_aprovada', "Proposta ID: {$id} marcada como aprovada.");
                setMensagem('success', 'Proposta aprovada com sucesso!');
            }
            redirecionar(APP_URL . 'comercial/propostas');
        } catch (Exception $e) {
            error_log('Erro ao aprovar proposta: ' . $e->getMessage());
            setMensagem('error', 'Erro ao aprovar proposta.');
            redirecionar(APP_URL . 'comercial/propostas');
        }
        break;

    case 'aprovar_assinatura_manual':
        try {
            $cargoAtual = getCargo();
            $usuarioAtualId = (string)($_SESSION['usuario_id'] ?? '');
            if (!in_array($cargoAtual, ['ADMIN', 'VENDEDOR'], true)) {
                setMensagem('error', 'Seu perfil não pode autorizar propostas sem assinatura digital.');
                redirecionar(APP_URL . 'comercial');
            }

            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial');
            }

            $stmt = $pdo->prepare("
                SELECT p.*, c.nome AS cliente_nome
                FROM propostas p
                INNER JOIN clientes c ON c.id = p.cliente_id
                WHERE p.id = :id
                FOR UPDATE
            ");

            $pdo->beginTransaction();
            $stmt->execute([':id' => $id]);
            $prop = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prop) {
                throw new RuntimeException('Proposta não encontrada.');
            }
            if ($cargoAtual === 'VENDEDOR' && ($prop['criado_por'] ?? '') !== $usuarioAtualId) {
                throw new RuntimeException('O vendedor só pode autorizar propostas criadas por ele.');
            }
            if (empty($prop['escritorio_id']) || !financeiroPodeAcessarEscritorio($pdo, (string)$prop['escritorio_id'])) {
                throw new RuntimeException('Proposta não encontrada ou sem acesso ao escritório responsável.');
            }
            if (!empty($prop['assinado']) || ($prop['status'] ?? '') === 'assinada') {
                throw new RuntimeException('Esta proposta já está assinada.');
            }
            if (in_array(($prop['status'] ?? ''), ['cancelada', 'recusada'], true)) {
                throw new RuntimeException('Esta proposta não pode ser aprovada nesse status.');
            }

            $assinanteNome = 'Autorização interna - ' . ($_SESSION['usuario_nome'] ?? 'Usuário responsável');
            $assinaturaIp = obterIpCliente();
            $dataAssinatura = date('Y-m-d H:i:s');

            $stmtUpdate = $pdo->prepare("
                UPDATE propostas
                SET assinatura_ip = :ip,
                    assinatura_em = :data,
                    assinante_nome = :nome,
                    assinante_documento = :doc,
                    assinado = 1,
                    status = 'assinada'
                WHERE id = :id AND assinado = 0
            ");
            $stmtUpdate->execute([
                ':ip' => $assinaturaIp,
                ':data' => $dataAssinatura,
                ':nome' => $assinanteNome,
                ':doc' => 'Autorização interna sem assinatura digital',
                ':id' => $id,
            ]);

            if ($stmtUpdate->rowCount() !== 1) {
                throw new RuntimeException('Esta proposta já foi assinada ou alterada por outro acesso.');
            }

            $prop['assinado'] = 1;
            $prop['status'] = 'assinada';
            gerarEfeitosPropostaAssinada($pdo, $prop, $_SESSION['usuario_id'] ?? null, true);
            $proximoAgendamentoId = proximoAgendamentoPendenteProposta($pdo, (string)$prop['id']);

            $pdo->commit();

            log_atividade('proposta_aprovada_assinatura_manual', "Proposta {$prop['numero']} autorizada internamente sem assinatura digital por {$assinanteNome}.");
            if ($proximoAgendamentoId !== null) {
                setMensagem('success', "Proposta {$prop['numero']} autorizada e marcada como assinada. Complete agora os dados do agendamento.");
                redirecionar(
                    APP_URL . 'agendamentos/form?id=' . urlencode($proximoAgendamentoId) . '&fluxo_proposta=1'
                );
            }

            setMensagem('success', "Proposta {$prop['numero']} autorizada e marcada como assinada. Não há vistoria pendente para agendar.");
            redirecionar(APP_URL . 'comercial?proposta=' . urlencode($id));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao aprovar proposta como assinada: ' . $e->getMessage());
            $mensagem = $e instanceof PDOException
                ? 'Não foi possível gerar o financeiro e os agendamentos da proposta.'
                : ($e instanceof RuntimeException ? $e->getMessage() : 'Erro ao aprovar proposta como assinada.');
            setMensagem('error', $mensagem);
            redirecionar(APP_URL . 'comercial');
        }
        break;

    case 'recusar_proposta':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial/propostas');
            }
            $stmt = $pdo->prepare("UPDATE propostas SET status = 'recusada' WHERE id = :id AND status IN ('rascunho','enviada')");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                setMensagem('warning', 'Proposta não encontrada ou já está em outro status.');
            } else {
                log_atividade('proposta_recusada', "Proposta ID: {$id} marcada como recusada.");
                setMensagem('success', 'Proposta recusada.');
            }
            redirecionar(APP_URL . 'comercial/propostas');
        } catch (Exception $e) {
            error_log('Erro ao recusar proposta: ' . $e->getMessage());
            setMensagem('error', 'Erro ao recusar proposta.');
            redirecionar(APP_URL . 'comercial/propostas');
        }
        break;

    case 'cancelar_proposta':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial/propostas');
            }
            $stmt = $pdo->prepare("UPDATE propostas SET status = 'cancelada' WHERE id = :id AND status = 'aprovada'");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                setMensagem('warning', 'Proposta não encontrada ou não está aprovada.');
            } else {
                log_atividade('proposta_cancelada', "Proposta ID: {$id} cancelada.");
                setMensagem('success', 'Proposta cancelada!');
            }
            redirecionar(APP_URL . 'comercial/propostas');
        } catch (Exception $e) {
            error_log('Erro ao cancelar proposta: ' . $e->getMessage());
            setMensagem('error', 'Erro ao cancelar proposta.');
            redirecionar(APP_URL . 'comercial/propostas');
        }
        break;

    case 'reabrir_proposta':
        try {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                setMensagem('error', 'ID da proposta não informado.');
                redirecionar(APP_URL . 'comercial/propostas');
            }
            $stmt = $pdo->prepare("UPDATE propostas SET status = 'rascunho' WHERE id = :id AND status IN ('recusada','cancelada')");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                setMensagem('warning', 'Proposta não encontrada ou não está recusada/cancelada.');
            } else {
                log_atividade('proposta_reaberta', "Proposta ID: {$id} reaberta como rascunho.");
                setMensagem('success', 'Proposta reaberta como rascunho!');
            }
            redirecionar(APP_URL . 'comercial/propostas');
        } catch (Exception $e) {
            error_log('Erro ao reabrir proposta: ' . $e->getMessage());
            setMensagem('error', 'Erro ao reabrir proposta.');
            redirecionar(APP_URL . 'comercial/propostas');
        }
        break;

    default:
        // Verificar se é uma requisição AJAX não reconhecida
        if (isset($_GET['action'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Ação não reconhecida.']);
            exit;
        }
        setMensagem('error', 'Ação inválida.');
        redirecionar(APP_URL . 'comercial/propostas');
        break;
}

