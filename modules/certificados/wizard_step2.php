<?php
/**
 * MODULO: CERTIFICADOS
 * Arquivo: wizard_step2.php - Relatório, conferência e emissão
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/assinaturas_usuarios.php';
require_once __DIR__ . '/../../includes/emissao_certificados.php';

exigirAcesso('certificados');

$sessao_wizard = $_SESSION['wizard_certificado'] ?? [];
$modelo = $sessao_wizard['modelo'] ?? '';
$modelo_nome = $sessao_wizard['modelo_nome'] ?? $modelo;
$tipo = $sessao_wizard['tipo'] ?? '';
$agendamento_id = $sessao_wizard['agendamento_id'] ?? '';
$modelos_sem_tipo = ['LP', 'LC', 'CHT'];
$modelo_csn = $modelo === 'CSN' || $modelo === 'Certificado de Segurança da Navegação';

if (!empty($modelo) && in_array($modelo, $modelos_sem_tipo, true) && empty($tipo)) {
    $tipo = 'Documento';
    $_SESSION['wizard_certificado']['tipo'] = $tipo;
}

if (empty($modelo) || empty($tipo)) {
    header('Location: ' . APP_URL . 'certificados');
    exit;
}

$vistoria_id_sessao = trim((string)($sessao_wizard['vistoria_id'] ?? ''));
$modelo_permitido_sessao = $vistoria_id_sessao !== ''
    ? certificadoModeloPermitidoPorVistoria($pdo, $vistoria_id_sessao, $modelo)
    : ($agendamento_id !== '' && certificadoModeloPermitidoPorAgendamento($pdo, (string)$agendamento_id, $modelo));
if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) && !$modelo_permitido_sessao) {
    unset($_SESSION['wizard_certificado']);
    setMensagem('error', certificadoMensagemServicoObrigatorio($modelo));
    redirecionar($agendamento_id !== ''
        ? APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode((string)$agendamento_id)
        : APP_URL . 'certificados');
}

if ($modelo === 'CHT') {
    require __DIR__ . '/wizard_cht.php';
    exit;
}

if (!function_exists('buscarDadosVistoriaCertificado')) {
function buscarDadosVistoriaCertificado(PDO $pdo, string $vistoria_id): ?array
{
    if (empty($vistoria_id)) {
        return null;
    }

    $stmtDados = $pdo->prepare("
        SELECT v.numero as relatorio_numero, v.data_vistoria, v.prazo_exigencias_dias,
               v.status as relatorio_status,
               a.local as local_vistoria,
               e.id as embarcacao_id,
               COALESCE(v.pessoa_id, e.proprietario_id) as cliente_id,
               pc.nome as proprietario_nome_cadastro,
               pc.cpf_cnpj as proprietario_cpf_cnpj_cadastro,
               pc.endereco as proprietario_endereco_cadastro,
               e.*,
               e.nome as nome_embarcacao,
               e.ano as ano_construcao,
               te.nome as tipo_embarcacao_nome
        FROM vistorias v
        JOIN agendamentos a ON v.agendamento_id = a.id
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN tipos_embarcacao te ON e.tipo_embarcacao_id = te.id
        LEFT JOIN clientes pc ON pc.id = COALESCE(v.pessoa_id, e.proprietario_id)
        WHERE v.id = :id
    ");
    $stmtDados->execute([':id' => $vistoria_id]);
    $dados = $stmtDados->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
        $dados['relatorio_numero'] = relatorioNumerosReferenciaCertificado($pdo, $vistoria_id);
        $dados['relatorio_status'] = $liberacao['status'] ?? $dados['relatorio_status'];
        $dados['mensagem_definitivo'] = $liberacao['mensagem_definitivo'] ?? '';
    }

    return $dados ?: null;
}
}

if (!function_exists('calcularValidadeCsnDoRelatorio')) {
function calcularValidadeCsnDoRelatorio(array $dados): ?string
{
    $prazo = (int)($dados['prazo_exigencias_dias'] ?? 0);
    $data = (string)($dados['data_vistoria'] ?? '');
    if (!in_array($prazo, [60, 90], true) || $data === '') {
        return null;
    }

    $dataBase = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $dataBase ? $dataBase->modify('+' . $prazo . ' days')->format('Y-m-d') : null;
}
}

$stmtResponsaveis = $pdo->prepare("
    SELECT ra.id, ra.nome_completo as nome, ra.cargo_titulo as cargo, ra.registro_profissional as conselho_classe
    FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id
    WHERE ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL
      AND ra.email IS NOT NULL AND ra.email<>''
      AND ra.assinatura_arquivo IS NOT NULL AND ra.assinatura_arquivo<>''
      AND ra.assinatura_hash IS NOT NULL AND ra.assinatura_hash<>''
      AND (u.cargo<>'ANALISTA' OR u.id=:criador)
    ORDER BY ra.nome_completo ASC
");
$stmtResponsaveis->execute([':criador'=>(string)($_SESSION['usuario_id']??'')]);
$responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);

$erro = '';
$sucesso = '';
$vistoria_id = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['vistoria_id'] ?? '')
    : ($_GET['vistoria_id'] ?? ($sessao_wizard['vistoria_id'] ?? ''));

if (empty($vistoria_id) && !empty($agendamento_id)) {
    $relatorioVigente = obterRelatorioVigenteAgendamento($pdo, $agendamento_id);
    $vistoria_id = (string)($relatorioVigente['id'] ?? '');
}

$responsavel_id_selecionado = $_POST['responsavel_id'] ?? '';
$data_validade_valor = $_POST['data_validade'] ?? '';
$local_emissao_valor = $_POST['local_emissao'] ?? '';
$emitente_valor = $modelo_csn ? 'AMAZON NAVAL' : ($_POST['emitente'] ?? '');
$normam_aplicavel_valor = $modelo_csn ? 'NORMAM-202' : ($_POST['normam_aplicavel'] ?? '');
$tipo_vistoria_certificado_valor = $_POST['tipo_vistoria_certificado'] ?? '';
$observacoes_verso_valor = $_POST['observacoes_verso'] ?? '';
$modalidade_lc = $_POST['modalidade_lc'] ?? 'LC';
$data_termino_construcao = $_POST['data_termino_construcao'] ?? '';

if ($responsavel_id_selecionado === '' && count($responsaveis) === 1) {
    $responsavel_id_selecionado = (string)$responsaveis[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vistoria_id_post = $_POST['vistoria_id'] ?? '';
    if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) && $vistoria_id_post !== '') {
        $dadosValidadeCsn = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);
        $validadeCalculadaCsn = $dadosValidadeCsn ? calcularValidadeCsnDoRelatorio($dadosValidadeCsn) : null;
        if ($validadeCalculadaCsn === null) {
            $erro = 'O relatório precisa ter prazo de validade de 60 ou 90 dias antes de gerar o ' . $modelo . '.';
        } else {
            $data_validade_valor = $validadeCalculadaCsn;
        }
    }
    if ($vistoria_id_post !== '') {
        $liberacaoCertificacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id_post);
        if (empty($liberacaoCertificacao['permitido'])) {
            $erro = $liberacaoCertificacao['mensagem'];
        }
        if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true)
            && !certificadoModeloPermitidoPorVistoria($pdo, (string)$vistoria_id_post, $modelo)) {
            $erro = certificadoMensagemServicoObrigatorio($modelo);
        }
    }

    if ($erro !== '') {
        // A regra central ja bloqueou o relatorio selecionado.
    } elseif (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (empty($vistoria_id_post)) {
        $erro = 'É obrigatório selecionar um relatório aprovado para emitir o certificado.';
    } elseif (empty($responsavel_id_selecionado)) {
        $erro = 'Selecione o responsável pela assinatura.';
    } elseif ($modelo !== 'LC' && empty($data_validade_valor)) {
        $erro = 'Informe a data de validade do certificado.';
    } elseif (empty($local_emissao_valor)) {
        $erro = 'Selecione o local de emissão.';
    } elseif ($modelo_csn) {
        $dados_emb = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);

        if (!$dados_emb) {
            $erro = 'Relatório não encontrado ou inválido.';
        } elseif (!in_array($dados_emb['relatorio_status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            $erro = 'Não é possível emitir certificado. O relatório selecionado não está aprovado.';
        } elseif ($dados_emb['possui_propulsao'] === null) {
            $erro = 'Atualize o cadastro da embarcação e informe se ela possui propulsão antes de gerar o CSN.';
        } elseif ((int)$dados_emb['possui_propulsao'] === 1 && (
            trim((string)($dados_emb['fabricante_motor'] ?? '')) === ''
            || trim((string)($dados_emb['modelo_motor'] ?? '')) === ''
            || trim((string)($dados_emb['numero_motor'] ?? '')) === ''
            || trim((string)($dados_emb['potencia_kw'] ?? '')) === ''
        )) {
            $erro = 'A embarcação possui propulsão. Preencha fabricante, modelo, número do motor e potência antes de gerar o CSN.';
        } elseif ($tipo === 'Definitivo' && $dados_emb['relatorio_status'] === 'APROVADA_COM_EXIGENCIAS') {
            $erro = (string)($dados_emb['mensagem_definitivo'] ?? '')
                ?: 'O relatório vigente ainda possui exigências comuns pendentes. Conclua a verificação antes de emitir o Certificado Definitivo.';
        } else {
            $stmtResp = $pdo->prepare("SELECT ra.nome_completo,ra.cargo_titulo,ra.registro_profissional FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id WHERE ra.id=:id AND ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL AND ra.email<>'' AND ra.assinatura_arquivo<>'' AND ra.assinatura_hash<>'' AND (u.cargo<>'ANALISTA' OR u.id=:criador)");
            $stmtResp->execute([':id' => $responsavel_id_selecionado, ':criador'=>(string)($_SESSION['usuario_id']??'')]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

            if (!$respData) {
                $erro = 'Responsável pela assinatura inválido ou não encontrado.';
            } else {
                try {
                    // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                    $dadosEmissao = [
                        'vistoria_id' => $vistoria_id_post,
                        'tipo' => $tipo,
                        'responsavel_assinatura_id' => $responsavel_id_selecionado,
                        'emitente' => $emitente_valor,
                        'normam_aplicavel' => $normam_aplicavel_valor,
                        'tipo_vistoria_certificado' => $tipo_vistoria_certificado_valor,
                        'observacoes_verso' => $observacoes_verso_valor,
                        'data_validade' => $data_validade_valor,
                        'local_emissao' => $local_emissao_valor,
                    ];
                    $resUnificado = emitirCertificadoUnificado($pdo, 'CSN', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                    $envio = $resUnificado['envio_email'] ?? ['success' => true, 'message' => ''];
                    if (!empty($envio['success'])) {
                        setMensagem('success', "Certificado CSN {$tipo} criado. " . ($envio['message'] ?? ''));
                    } else {
                        setMensagem('warning', "Certificado criado, mas o convite de assinatura não foi enviado: " . ($envio['message'] ?? ''));
                    }
                    // Convite de assinatura: assinaturaEnviarConviteCertificado($pdo, 'CSN', $certificado_id, $convite_assinatura)
                    redirecionar(APP_URL . 'documentacao/certificados');
                } catch (Throwable $e) {
                    $erro = 'Erro ao salvar certificado: ' . $e->getMessage();
                }
            }
        }
    } elseif ($modelo === 'CNBL' || $modelo === 'Certificado Nacional de Borda Livre') {
        $dados_emb = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);

        if (!$dados_emb) {
            $erro = 'Relatório não encontrado ou inválido.';
        } elseif (!in_array($dados_emb['relatorio_status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            $erro = 'Não é possível emitir certificado. O relatório selecionado não está aprovado.';
        } elseif ($tipo === 'Definitivo' && $dados_emb['relatorio_status'] === 'APROVADA_COM_EXIGENCIAS') {
            $erro = (string)($dados_emb['mensagem_definitivo'] ?? '')
                ?: 'O relatório vigente ainda possui exigências comuns pendentes. Conclua a verificação antes de emitir o Certificado Definitivo.';
        } else {
            $stmtResp = $pdo->prepare("SELECT ra.nome_completo,ra.cargo_titulo,ra.registro_profissional FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id WHERE ra.id=:id AND ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL AND ra.email<>'' AND ra.assinatura_arquivo<>'' AND ra.assinatura_hash<>'' AND (u.cargo<>'ANALISTA' OR u.id=:criador)");
            $stmtResp->execute([':id' => $responsavel_id_selecionado, ':criador'=>(string)($_SESSION['usuario_id']??'')]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

            if (!$respData) {
                $erro = 'Responsável pela assinatura inválido ou não encontrado.';
            } else {
                try {
                    // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                    $dadosEmissao = [
                        'vistoria_id' => $vistoria_id_post,
                        'tipo' => $tipo,
                        'responsavel_assinatura_id' => $responsavel_id_selecionado,
                        'tipo_vistoria_certificado' => $tipo_vistoria_certificado_valor,
                        'observacoes_verso' => $observacoes_verso_valor,
                        'data_validade' => $data_validade_valor,
                        'local_emissao' => $local_emissao_valor,
                    ];
                    $resUnificado = emitirCertificadoUnificado($pdo, 'CNBL', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                    $envio = $resUnificado['envio_email'] ?? ['success' => true, 'message' => ''];
                    if (!empty($envio['success'])) {
                        setMensagem('success', "Certificado CNBL {$tipo} criado. " . ($envio['message'] ?? ''));
                    } else {
                        setMensagem('warning', "Certificado criado, mas o convite de assinatura não foi enviado: " . ($envio['message'] ?? ''));
                    }
                    // Convite de assinatura: assinaturaEnviarConviteCertificado($pdo, 'CNBL', $certificado_id, $convite_assinatura)
                    redirecionar(APP_URL . 'documentacao/cnbl');
                } catch (Throwable $e) {
                    $erro = 'Erro ao salvar certificado CNBL: ' . $e->getMessage();
                }
            }
        }
    } elseif ($modelo === 'CNARQ' || $modelo === 'Certificado Nacional de Arqueação') {
        $dados_emb = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);

        if (!$dados_emb) {
            $erro = 'Relatório não encontrado ou inválido.';
        } elseif (!in_array($dados_emb['relatorio_status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            $erro = 'Não é possível emitir certificado. O relatório selecionado não está aprovado.';
        } elseif ($tipo === 'Definitivo' && $dados_emb['relatorio_status'] === 'APROVADA_COM_EXIGENCIAS') {
            $erro = (string)($dados_emb['mensagem_definitivo'] ?? '')
                ?: 'O relatório vigente ainda possui exigências comuns pendentes. Conclua a verificação antes de emitir o Certificado Definitivo.';
        } else {
            $stmtResp = $pdo->prepare("SELECT ra.nome_completo,ra.cargo_titulo,ra.registro_profissional FROM responsaveis_assinatura ra JOIN usuarios u ON u.id=ra.usuario_id WHERE ra.id=:id AND ra.ativo=1 AND u.ativo=1 AND u.excluido_em IS NULL AND ra.email<>'' AND ra.assinatura_arquivo<>'' AND ra.assinatura_hash<>'' AND (u.cargo<>'ANALISTA' OR u.id=:criador)");
            $stmtResp->execute([':id' => $responsavel_id_selecionado, ':criador'=>(string)($_SESSION['usuario_id']??'')]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

            if (!$respData) {
                $erro = 'Responsável pela assinatura inválido ou não encontrado.';
            } else {
                try {
                    // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                    $dadosEmissao = [
                        'vistoria_id' => $vistoria_id_post,
                        'tipo' => $tipo,
                        'responsavel_assinatura_id' => $responsavel_id_selecionado,
                        'tipo_vistoria_certificado' => $tipo_vistoria_certificado_valor,
                        'observacoes_verso' => $observacoes_verso_valor,
                        'data_validade' => $data_validade_valor,
                        'local_emissao' => $local_emissao_valor,
                    ];
                    $resUnificado = emitirCertificadoUnificado($pdo, 'CNARQ', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                    $envio = $resUnificado['envio_email'] ?? ['success' => true, 'message' => ''];
                    if (!empty($envio['success'])) {
                        setMensagem('success', "Certificado CNARQ {$tipo} criado. " . ($envio['message'] ?? ''));
                    } else {
                        setMensagem('warning', "Certificado criado, mas o convite de assinatura não foi enviado: " . ($envio['message'] ?? ''));
                    }
                    // Convite de assinatura: assinaturaEnviarConviteCertificado($pdo, 'CNARQ', $certificado_id, $convite_assinatura)
                    redirecionar(APP_URL . 'documentacao/cnarq');
                } catch (Throwable $e) {
                    $erro = 'Erro ao salvar certificado CNARQ: ' . $e->getMessage();
                }
            }
        }
    } elseif ($modelo === 'LP' || $modelo === 'Licença Provisória') {
        $dados_emb = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);

        if (!$dados_emb) {
            $erro = 'Relatório não encontrado ou inválido.';
        } elseif (!in_array($dados_emb['relatorio_status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            $erro = 'Não é possível emitir licença. O relatório selecionado não está aprovado.';
        } else {
            $stmtResp = $pdo->prepare("SELECT nome_completo, cargo_titulo, registro_profissional FROM responsaveis_assinatura WHERE id = :id");
            $stmtResp->execute([':id' => $responsavel_id_selecionado]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

            if (!$respData) {
                $erro = 'Responsável pela assinatura inválido ou não encontrado.';
            } else {
                try {
                    // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                    $dadosEmissao = [
                        'vistoria_id' => $vistoria_id_post,
                        'responsavel_assinatura_id' => $responsavel_id_selecionado,
                        'data_validade' => $data_validade_valor,
                        'tipo_licenca' => 'construcao',
                    ];
                    $resUnificado = emitirCertificadoUnificado($pdo, 'LP', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                    $numero_lp = $resUnificado['numero'] ?? '';

                    log_atividade('licenca_lp_criada', "Licença {$numero_lp} - " . ($dados_emb['nome'] ?? $dados_emb['nome_embarcacao'] ?? ''));
                    setMensagem('success', "Licença Provisória criada com sucesso! Número: {$numero_lp}");
                    redirecionar(APP_URL . 'documentacao/lp');
                } catch (Throwable $e) {
                    $erro = 'Erro ao salvar Licença Provisória: ' . $e->getMessage();
                }
            }
        }
    } elseif ($modelo === 'LC' || $modelo === 'Licença de Construção') {
        $dados_emb = buscarDadosVistoriaCertificado($pdo, $vistoria_id_post);
        $modalidades_validas = ['LC', 'LA', 'LR', 'LCEC'];

        if (!$dados_emb) {
            $erro = 'Relatório não encontrado ou inválido.';
        } elseif (!in_array($dados_emb['relatorio_status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            $erro = 'Não é possível emitir a licença. O relatório selecionado não está aprovado.';
        } elseif (!in_array($modalidade_lc, $modalidades_validas, true)) {
            $erro = 'Selecione uma modalidade válida para a licença.';
        } elseif ($modalidade_lc === 'LCEC' && empty($data_termino_construcao)) {
            $erro = 'Informe a data do término da construção para a modalidade LCEC.';
        } else {
            $stmtResp = $pdo->prepare("
                SELECT nome_completo, cargo_titulo, registro_profissional
                FROM responsaveis_assinatura
                WHERE id = :id AND ativo = 1
            ");
            $stmtResp->execute([':id' => $responsavel_id_selecionado]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

            if (!$respData) {
                $erro = 'Responsável pela assinatura inválido ou inativo.';
            } else {
                try {
                    // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                    $dadosEmissao = [
                        'vistoria_id' => $vistoria_id_post,
                        'responsavel_assinatura_id' => $responsavel_id_selecionado,
                        'tipo_licenca' => $modalidade_lc,
                        'data_termino_construcao' => $modalidade_lc === 'LCEC' ? $data_termino_construcao : null,
                        'local_emissao' => $local_emissao_valor,
                    ];
                    $resUnificado = emitirCertificadoUnificado($pdo, 'LC', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                    $numero_lc = $resUnificado['numero'] ?? '';

                    log_atividade('licenca_lc_criada', "{$numero_lc} ({$modalidade_lc}) - " . ($dados_emb['nome'] ?? ''));
                    setMensagem('success', "Licença {$modalidade_lc} criada com sucesso! Número: {$numero_lc}");
                    redirecionar(APP_URL . 'documentacao/lc');
                } catch (Throwable $e) {
                    $erro = 'Não foi possível gerar a licença: ' . $e->getMessage();
                }
            }
        }
    } else {
        $sucesso = 'Este modelo está preparado visualmente no assistente, mas a gravação final usa o formulário dedicado do módulo Documentação.';
    }
}

$dados_preenchidos = buscarDadosVistoriaCertificado($pdo, $vistoria_id);
if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) && $dados_preenchidos && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $data_validade_valor = calcularValidadeCsnDoRelatorio($dados_preenchidos) ?? '';
}
$relatorio_label = '';
$relatorio_vinculado = !empty($agendamento_id);

if ($dados_preenchidos) {
    $relatorio_label = trim(($dados_preenchidos['relatorio_numero'] ?? 'Sem número') . ' · ' .
        ($dados_preenchidos['nome_embarcacao'] ?? '') . ' · ' .
        (!empty($dados_preenchidos['data_vistoria']) ? date('d/m/Y', strtotime($dados_preenchidos['data_vistoria'])) : 'Sem data') . ' · ' .
        ($dados_preenchidos['relatorio_status'] ?? ''));
}

$titulo_page = 'Wizard de Emissão - Relatório e dados';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Conferir e gerar certificado</h1>
            <p class="page-subtitle"><?= h($modelo) ?><?= in_array($modelo, $modelos_sem_tipo, true) ? '' : ' · ' . h($tipo) ?></p>
        </div>
        <div class="page-actions">
            <a href="<?= APP_URL ?>certificados<?= in_array($modelo, $modelos_sem_tipo, true) ? '' : '/wizard?modelo=' . urlencode($modelo) . (!empty($agendamento_id) ? '&agendamento_id=' . urlencode($agendamento_id) : '') . (!empty($vistoria_id) ? '&vistoria_id=' . urlencode($vistoria_id) : '') ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> <?= in_array($modelo, $modelos_sem_tipo, true) ? 'Voltar aos modelos' : 'Voltar ao tipo' ?>
            </a>
        </div>
    </div>

    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i> <?= h($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-xmark"></i> <?= h($erro) ?>
        </div>
    <?php endif; ?>

    <div class="cert-workspace cert-workspace--wizard cert-workspace--wide">
        <aside class="cert-flow-sidebar">
            <div class="cert-flow-title">
                <i class="fas fa-route"></i>
                <div>
                    <strong>Etapas da emissão</strong>
                    <span>Relatório e geração</span>
                </div>
            </div>

            <ol class="cert-step-list">
                <li class="is-done">
                    <span><i class="fas fa-check"></i></span>
                    <div>
                        <strong>Modelo</strong>
                        <small><?= h($modelo) ?></small>
                    </div>
                </li>
                <?php if (!in_array($modelo, $modelos_sem_tipo, true)): ?>
                    <li class="is-done">
                        <span><i class="fas fa-check"></i></span>
                        <div>
                            <strong>Tipo</strong>
                            <small><?= h($tipo) ?></small>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="is-active">
                    <span>03</span>
                    <div>
                        <strong>Relatório e dados</strong>
                        <small>Selecionar relatório aprovado.</small>
                    </div>
                </li>
                <li class="<?= $dados_preenchidos ? 'is-active-soft' : '' ?>">
                    <span>04</span>
                    <div>
                        <strong>Gerar certificado</strong>
                        <small>Assinatura, validade e emissão.</small>
                    </div>
                </li>
            </ol>
        </aside>

        <section class="cert-main-panel">
            <div class="cert-panel-header">
                <div>
                    <h2>1. Relatório aprovado</h2>
                    <p><?= $relatorio_vinculado ? 'Relatório vinculado a este certificado. Ele não pode ser alterado nesta etapa.' : 'Escolha o relatório que vai alimentar automaticamente os dados do certificado.' ?></p>
                </div>
            </div>

            <form method="GET" action="" id="formSelectVistoria" class="cert-report-select">
                <label for="busca_relatorio">Relatório de vistoria <span class="text-danger">*</span></label>
                <div class="cert-search-box">
                    <i class="fas fa-search"></i>
                    <input type="search"
                           id="busca_relatorio"
                           class="form-control"
                           placeholder="Pesquise por nome da embarcação, nº do relatório ou inscrição..."
                           value="<?= h($relatorio_label) ?>"
                           autocomplete="off"
                           <?= $relatorio_vinculado ? 'readonly' : '' ?>>
                    <input type="hidden" name="vistoria_id" id="vistoria_id" value="<?= h($vistoria_id) ?>">
                    <?php if (!$relatorio_vinculado): ?>
                        <button type="button" class="btn btn-primary btn-sm" id="abrirRelatorios">
                            <i class="fas fa-list-check"></i> Selecionar
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="limparRelatorio" <?= empty($vistoria_id) ? 'hidden' : '' ?>>
                            <i class="fas fa-xmark"></i> Limpar
                        </button>
                    <?php endif; ?>
                </div>
                <div class="cert-search-results" id="resultadosRelatorio" hidden></div>
                <?php if (!$relatorio_vinculado): ?>
                    <small>Clique em Selecionar para ver os 10 relatórios aprovados mais recentes. Se precisar de um relatório antigo, pesquise por embarcação, nº do relatório ou inscrição.</small>
                <?php endif; ?>
            </form>

            <?php if ($dados_preenchidos): ?>

                <form method="POST" action="" class="cert-issue-form">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="vistoria_id" value="<?= h($vistoria_id) ?>">

                    <div class="cert-panel-header cert-panel-header--compact">
                        <div>
                            <h2>2. Dados de emissão</h2>
                            <p>Preencha somente o que depende da emissão atual.</p>
                        </div>
                    </div>

                    <?php if ($modelo === 'LC'): ?>
                        <div class="form-row">
                            <div class="form-group col-8">
                                <label for="modalidade_lc"><i class="fas fa-file-signature"></i> Modalidade da licença <span class="text-danger">*</span></label>
                                <select name="modalidade_lc" id="modalidade_lc" class="form-control" required>
                                    <option value="LC" <?= $modalidade_lc === 'LC' ? 'selected' : '' ?>>LC — Licença de Construção</option>
                                    <option value="LA" <?= $modalidade_lc === 'LA' ? 'selected' : '' ?>>LA — Licença de Alteração</option>
                                    <option value="LR" <?= $modalidade_lc === 'LR' ? 'selected' : '' ?>>LR — Licença de Reclassificação</option>
                                    <option value="LCEC" <?= $modalidade_lc === 'LCEC' ? 'selected' : '' ?>>LCEC — Construção para embarcação já construída</option>
                                </select>
                                <small>Esta escolha pertence ao próprio modelo LC e não é Provisório/Condicional/Definitivo.</small>
                            </div>
                            <div class="form-group col-4" id="grupoTerminoConstrucao" <?= $modalidade_lc !== 'LCEC' ? 'hidden' : '' ?>>
                                <label for="data_termino_construcao">Término da construção <span class="text-danger">*</span></label>
                                <input type="date" name="data_termino_construcao" id="data_termino_construcao" class="form-control" value="<?= h($data_termino_construcao) ?>" <?= $modalidade_lc === 'LCEC' ? 'required' : '' ?>>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group <?= $modelo === 'LC' ? 'col-6' : 'col-6' ?>">
                            <label for="responsavel_id"><i class="fas fa-signature"></i> Responsável pela assinatura <span class="text-danger">*</span></label>
                            <select name="responsavel_id" id="responsavel_id" class="form-control" required>
                                <option value="">Selecione o responsável...</option>
                                <?php foreach ($responsaveis as $resp): ?>
                                    <option value="<?= h($resp['id']) ?>" <?= (string)$responsavel_id_selecionado === (string)$resp['id'] ? 'selected' : '' ?>>
                                        <?= h($resp['nome']) ?><?= !empty($resp['cargo']) ? ' · ' . h($resp['cargo']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($modelo !== 'LC'): ?>
                            <div class="form-group col-3">
                                <label for="data_validade"><i class="fas fa-calendar-check"></i> Validade <span class="text-danger">*</span></label>
                                <input type="date" name="data_validade" id="data_validade" class="form-control" value="<?= h($data_validade_valor) ?>" required <?= in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) ? 'readonly' : '' ?>>
                                <?php if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true)): ?>
                                    <small>Calculada automaticamente pela data e pelo prazo do relatório de vistoria.</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group <?= $modelo === 'LC' ? 'col-6' : 'col-3' ?>">
                            <label for="local_emissao"><i class="fas fa-location-dot"></i> Local <span class="text-danger">*</span></label>
                            <select name="local_emissao" id="local_emissao" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach (['Belém-PA', 'Manaus-AM', 'Santarém-PA', 'Macapá-AP', 'Porto Velho-RO'] as $local): ?>
                                    <option value="<?= h($local) ?>" <?= $local_emissao_valor === $local ? 'selected' : '' ?>><?= h($local) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ', 'Certificado de Segurança da Navegação'], true)): ?>
                        <?php if ($modelo_csn): ?>
                            <input type="hidden" name="emitente" value="AMAZON NAVAL">
                            <input type="hidden" name="normam_aplicavel" value="NORMAM-202">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="tipo_vistoria_certificado"><i class="fas fa-clipboard-check"></i> Tipo de vistoria</label>
                                <select name="tipo_vistoria_certificado" id="tipo_vistoria_certificado" class="form-control">
                                    <option value="">Selecione...</option>
                                    <option value="Inicial" <?= $tipo_vistoria_certificado_valor === 'Inicial' ? 'selected' : '' ?>>Inicial</option>
                                    <option value="Renovacao" <?= $tipo_vistoria_certificado_valor === 'Renovacao' ? 'selected' : '' ?>>Renovação</option>
                                    <option value="Intermediaria" <?= $tipo_vistoria_certificado_valor === 'Intermediaria' ? 'selected' : '' ?>>Intermediária</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="observacoes_verso"><i class="fas fa-note-sticky"></i> Observações do verso do <?= h($modelo) ?></label>
                                <textarea name="observacoes_verso" id="observacoes_verso" class="form-control" rows="3"><?= h($observacoes_verso_valor) ?></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="cert-action-bar">
                        <a href="<?= APP_URL ?>certificados/wizard?modelo=<?= urlencode($modelo) ?><?= !empty($agendamento_id) ? '&agendamento_id=' . urlencode($agendamento_id) : '' ?><?= !empty($vistoria_id) ? '&vistoria_id=' . urlencode($vistoria_id) : '' ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Salvar e gerar certificado <i class="fa-solid fa-file-pdf"></i>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="cert-empty-guide">
                    <i class="fas fa-clipboard-check"></i>
                    <strong>Selecione um relatório para continuar</strong>
                    <p>Assim que um relatório aprovado for escolhido, os dados da embarcação aparecem aqui para conferência e o botão de geração é liberado.</p>
                </div>
            <?php endif; ?>
        </section>

        <aside class="cert-help-panel">
            <strong>Resumo da emissão</strong>
            <div class="cert-summary-list">
                <span><b>Modelo</b><?= h($modelo) ?></span>
                <?php if (!in_array($modelo, $modelos_sem_tipo, true)): ?>
                    <span><b>Tipo</b><?= h($tipo) ?></span>
                <?php endif; ?>
                <span><b>Relatório</b><?= $dados_preenchidos ? h($dados_preenchidos['relatorio_numero'] ?? 'Selecionado') : 'Pendente' ?></span>
                <span><b>Próximo passo</b><?= $dados_preenchidos ? 'Preencher emissão' : 'Selecionar relatório' ?></span>
            </div>

            <?php if ($tipo === 'Definitivo'): ?>
                <div class="cert-help-note">
                    <i class="fas fa-circle-info"></i>
                    <span>Definitivo não pode ser emitido com relatório aprovado com exigências.</span>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
const modalidadeLc = document.getElementById('modalidade_lc');
const grupoTerminoConstrucao = document.getElementById('grupoTerminoConstrucao');
const dataTerminoConstrucao = document.getElementById('data_termino_construcao');

modalidadeLc?.addEventListener('change', () => {
    const exigeData = modalidadeLc.value === 'LCEC';
    grupoTerminoConstrucao.hidden = !exigeData;
    if (dataTerminoConstrucao) {
        dataTerminoConstrucao.required = exigeData;
        if (!exigeData) dataTerminoConstrucao.value = '';
    }
});

const campoRelatorio = document.getElementById('busca_relatorio');
const campoVistoriaId = document.getElementById('vistoria_id');
const resultadosRelatorio = document.getElementById('resultadosRelatorio');
const limparRelatorio = document.getElementById('limparRelatorio');
const abrirRelatorios = document.getElementById('abrirRelatorios');
let relatorioTimer = null;

function formatarDataRelatorio(data) {
    if (!data) return 'Sem data';
    const partes = data.split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : data;
}

function escaparHtml(valor) {
    return String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function esconderResultadosRelatorio() {
    resultadosRelatorio.hidden = true;
    resultadosRelatorio.innerHTML = '';
}

function renderizarResultadosRelatorio(lista) {
    if (!lista.length) {
        resultadosRelatorio.innerHTML = `
            <div class="cert-search-empty">
                <i class="fas fa-circle-info"></i>
                Nenhum relatório aprovado encontrado para essa busca.
            </div>
        `;
        resultadosRelatorio.hidden = false;
        return;
    }

    resultadosRelatorio.innerHTML = lista.map((item) => {
        const numero = item.numero || 'Sem número';
        const embarcacao = item.nome_embarcacao || 'Embarcação sem nome';
        const inscricao = item.numero_inscricao || 'Sem inscrição';
        const data = formatarDataRelatorio(item.data_vistoria || '');
        const status = item.status || '';
        const label = `${numero} · ${embarcacao} · ${data} · ${status}`;

        return `
            <button type="button" class="cert-search-result" data-id="${escaparHtml(item.id)}" data-label="${escaparHtml(label)}">
                <strong>${escaparHtml(numero)} · ${escaparHtml(embarcacao)}</strong>
                <span>${escaparHtml(inscricao)} · ${escaparHtml(data)} · ${escaparHtml(status)}</span>
            </button>
        `;
    }).join('');
    resultadosRelatorio.hidden = false;
}

async function buscarRelatoriosAprovados(termo, recentes = false) {
    const query = recentes
        ? 'recentes=1'
        : `q=${encodeURIComponent(termo)}`;
    const resposta = await fetch(`<?= APP_URL ?>ajax/busca_relatorios_aprovados.php?${query}`, {
        headers: { 'Accept': 'application/json' }
    });
    if (!resposta.ok) return [];
    return resposta.json();
}

async function carregarRelatoriosRecentes() {
    resultadosRelatorio.innerHTML = '<div class="cert-search-empty"><i class="fas fa-spinner fa-spin"></i> Carregando últimos relatórios aprovados...</div>';
    resultadosRelatorio.hidden = false;

    try {
        const lista = await buscarRelatoriosAprovados('', true);
        renderizarResultadosRelatorio(lista);
    } catch (error) {
        resultadosRelatorio.innerHTML = '<div class="cert-search-empty"><i class="fas fa-triangle-exclamation"></i> Não foi possível carregar os relatórios recentes.</div>';
        resultadosRelatorio.hidden = false;
    }
}

abrirRelatorios?.addEventListener('click', carregarRelatoriosRecentes);

campoRelatorio?.addEventListener('focus', () => {
    if (!campoRelatorio.value.trim() && resultadosRelatorio.hidden) {
        carregarRelatoriosRecentes();
    }
});

campoRelatorio?.addEventListener('input', () => {
    const termo = campoRelatorio.value.trim();
    campoVistoriaId.value = '';
    limparRelatorio.hidden = true;

    clearTimeout(relatorioTimer);

    if (termo.length < 2) {
        esconderResultadosRelatorio();
        return;
    }

    resultadosRelatorio.innerHTML = '<div class="cert-search-empty"><i class="fas fa-spinner fa-spin"></i> Buscando relatórios...</div>';
    resultadosRelatorio.hidden = false;

    relatorioTimer = setTimeout(async () => {
        try {
            const lista = await buscarRelatoriosAprovados(termo);
            renderizarResultadosRelatorio(lista);
        } catch (error) {
            resultadosRelatorio.innerHTML = '<div class="cert-search-empty"><i class="fas fa-triangle-exclamation"></i> Não foi possível buscar agora.</div>';
            resultadosRelatorio.hidden = false;
        }
    }, 280);
});

resultadosRelatorio?.addEventListener('click', (event) => {
    const botao = event.target.closest('.cert-search-result');
    if (!botao) return;

    campoVistoriaId.value = botao.dataset.id || '';
    campoRelatorio.value = botao.dataset.label || botao.innerText.trim();
    limparRelatorio.hidden = false;
    esconderResultadosRelatorio();
    document.getElementById('formSelectVistoria').submit();
});

limparRelatorio?.addEventListener('click', () => {
    campoRelatorio.value = '';
    campoVistoriaId.value = '';
    limparRelatorio.hidden = true;
    esconderResultadosRelatorio();
    window.location.href = '<?= APP_URL ?>certificados/wizard_step2';
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.cert-report-select')) {
        esconderResultadosRelatorio();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
