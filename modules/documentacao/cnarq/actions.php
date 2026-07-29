<?php
/**
 * MÓDULO: Documentação > Certificados CNARQ
 * Actions: Salvar e enviar documentos
 * Numeração automática AM-CNARQ-{n}/{ano}
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar permissão
verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$action = $_POST['action'] ?? '';

// ============================================
// SALVAR CERTIFICADO
// ============================================
if ($action === 'salvar') {
    // Verificar CSRF
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo,'certificados_cnarq',$id,APP_URL.'documentacao/cnarq');
    $certificadoExistente = null;
    if ($editando) {
        $stmtCertificado = $pdo->prepare('SELECT id, vistoria_id FROM certificados_cnarq WHERE id = :id AND ativo = 1');
        $stmtCertificado->execute([':id' => $id]);
        $certificadoExistente = $stmtCertificado->fetch(PDO::FETCH_ASSOC);
        if (!$certificadoExistente) {
            setMensagem('error', 'Certificado não encontrado.');
            redirecionar(APP_URL . 'documentacao/cnarq');
        }
    }

    // Dados principais
    $nome_embarcacao     = trim($_POST['nome_embarcacao'] ?? '');
    $numero_inscricao    = trim($_POST['numero_inscricao'] ?? '');
    $indicativo_chamada  = trim($_POST['indicativo_chamada'] ?? '');
    $tipo_embarcacao     = trim($_POST['tipo_embarcacao'] ?? '');
    $ano_construcao      = trim($_POST['ano_construcao'] ?? '');
    $material_casco      = trim($_POST['material_casco'] ?? '');
    $porto_inscricao     = trim($_POST['porto_inscricao'] ?? '');
    $local_construcao    = trim($_POST['local_construcao'] ?? '');
    $tipo                = trim($_POST['tipo'] ?? 'Condicional');
    $data_quilha         = trim($_POST['data_quilha'] ?? '');

    // Dimensões
    $comprimento_total   = ($_POST['comprimento_total'] ?? '') !== '' ? $_POST['comprimento_total'] : null;
    $comprimento_casco   = ($_POST['comprimento_casco'] ?? '') !== '' ? $_POST['comprimento_casco'] : null;
    $comprimento_lpp     = ($_POST['comprimento_lpp'] ?? '') !== '' ? $_POST['comprimento_lpp'] : null;
    $boca_moldada        = ($_POST['boca_moldada'] ?? '') !== '' ? $_POST['boca_moldada'] : null;
    $boca_maxima         = ($_POST['boca_maxima'] ?? '') !== '' ? $_POST['boca_maxima'] : null;
    $pontal_moldado      = ($_POST['pontal_moldado'] ?? '') !== '' ? $_POST['pontal_moldado'] : null;

    // Arqueação
    $arqueacao_bruta     = ($_POST['arqueacao_bruta'] ?? '') !== '' ? $_POST['arqueacao_bruta'] : null;
    $arqueacao_liquida   = ($_POST['arqueacao_liquida'] ?? '') !== '' ? $_POST['arqueacao_liquida'] : null;
    $metodo_arqueacao    = trim($_POST['metodo_arqueacao'] ?? '');
    $calado_moldado_m    = ($_POST['calado_moldado_m'] ?? '') !== '' ? $_POST['calado_moldado_m'] : null;
    $passageiros_camarotes = ($_POST['passageiros_camarotes'] ?? '') !== '' ? (int)$_POST['passageiros_camarotes'] : 0;
    $passageiros_outros  = ($_POST['passageiros_outros'] ?? '') !== '' ? (int)$_POST['passageiros_outros'] : 0;
    $espacos_incluidos_ab = trim($_POST['espacos_incluidos_ab'] ?? '');
    $espacos_incluidos_al = trim($_POST['espacos_incluidos_al'] ?? '');
    $espacos_excluidos_m3 = ($_POST['espacos_excluidos_m3'] ?? '') !== '' ? $_POST['espacos_excluidos_m3'] : 0;
    $data_local_arqueacao_original = trim($_POST['data_local_arqueacao_original'] ?? '');
    $data_local_ultima_rearqueacao = trim($_POST['data_local_ultima_rearqueacao'] ?? '');

    // Vistoria
    $relatorio_numero    = '';
    $local_vistoria      = trim($_POST['local_vistoria'] ?? '');
    $data_vistoria       = ($_POST['data_vistoria'] ?? '') ?: null;

    // Datas e local
    $data_emissao  = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_validade = $_POST['data_validade'] ?? '';
    $local_emissao = trim($_POST['local_emissao'] ?? 'Belém-PA');

    // Assinante
    $assinante_nome     = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo   = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');

    // Status
    $despachante_id = $_POST['despachante_id'] ?? null;
    if(empty($despachante_id)) $despachante_id = null;

    $status = $_POST['status'] ?? 'rascunho';
    if (!in_array($status, ['rascunho', 'emitido', 'cancelado'])) {
        $status = 'rascunho';
    }

    // Validações
    $vistoria_id = certificadoResolverVistoriaParaSalvar(
        $id,
        $certificadoExistente['vistoria_id'] ?? null,
        $_POST['vistoria_id'] ?? null
    );
    if (!$editando && $vistoria_id && !certificadoModeloPermitidoPorVistoria($pdo, (string)$vistoria_id, 'CNARQ')) {
        setMensagem('error', certificadoMensagemServicoObrigatorio('CNARQ'));
        redirecionar(APP_URL . 'certificados');
    }
    if ($vistoria_id) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
        if (empty($liberacao['permitido'])) {
            setMensagem('error', $liberacao['mensagem']);
            redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
        }
        $relatorio_numero = relatorioNumerosReferenciaCertificado($pdo, (string)$vistoria_id);
        if ($tipo === 'Definitivo' && ($liberacao['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS') {
            setMensagem(
                'error',
                (string)($liberacao['mensagem_definitivo'] ?? '')
                    ?: 'O relatório vigente ainda possui exigências comuns pendentes. Conclua a verificação antes de emitir o Certificado Definitivo.'
            );
            redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
        }
    }
    if (empty($vistoria_id)) {
        setMensagem('error', 'É obrigatório selecionar um relatório aprovado para emitir o certificado.');
        redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
    } else {
        $stmtStatus = $pdo->prepare("SELECT status FROM vistorias WHERE id = :vid");
        $stmtStatus->execute([':vid' => $vistoria_id]);
        $vistData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        if (!$vistData || !in_array($vistData['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'])) {
            setMensagem('error', 'Não é possível emitir certificado. O relatório selecionado não está aprovado.');
            redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
        }
    }

    if (empty($nome_embarcacao)) {
        setMensagem('error', 'O nome da embarcação é obrigatório.');
        redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_emissao)) {
        setMensagem('error', 'A data de emissão é obrigatória.');
        redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_validade)) {
        setMensagem('error', 'A data de validade é obrigatória.');
        redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
    }

    try {
        $pdo->beginTransaction();

        if ($editando) {
            // ATUALIZAR
            $sql = "UPDATE certificados_cnarq SET
                        nome_embarcacao = :nome_embarcacao,
                        numero_inscricao = :numero_inscricao,
                        indicativo_chamada = :indicativo_chamada,
                        tipo_embarcacao = :tipo_embarcacao,
                        ano_construcao = :ano_construcao,
                        material_casco = :material_casco,
                        porto_inscricao = :porto_inscricao,
                        local_construcao = :local_construcao,
                        comprimento_total = :comprimento_total,
                        comprimento_casco = :comprimento_casco,
                        comprimento_lpp = :comprimento_lpp,
                        boca_moldada = :boca_moldada,
                        boca_maxima = :boca_maxima,
                        pontal_moldado = :pontal_moldado,
                        arqueacao_bruta = :arqueacao_bruta,
                        arqueacao_liquida = :arqueacao_liquida,
                        metodo_arqueacao = :metodo_arqueacao,
                        relatorio_numero = :relatorio_numero,
                        data_vistoria = :data_vistoria,
                        local_vistoria = :local_vistoria,
                        data_emissao = :data_emissao,
                        data_validade = :data_validade,
                        local_emissao = :local_emissao,
                        assinante_nome = :assinante_nome,
                        assinante_titulo = :assinante_titulo,
                        assinante_registro = :assinante_registro,
                        status = :status, vistoria_id = :vistoria_id, despachante_id = :despachante_id WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome_embarcacao'    => $nome_embarcacao,
                ':numero_inscricao'   => $numero_inscricao,
                ':indicativo_chamada' => $indicativo_chamada,
                ':tipo_embarcacao'    => $tipo_embarcacao,
                ':ano_construcao'     => $ano_construcao,
                ':material_casco'     => $material_casco,
                ':porto_inscricao'    => $porto_inscricao,
                ':local_construcao'   => $local_construcao,
                ':comprimento_total'  => $comprimento_total,
                ':comprimento_casco'  => $comprimento_casco,
                ':comprimento_lpp'    => $comprimento_lpp,
                ':boca_moldada'       => $boca_moldada,
                ':boca_maxima'        => $boca_maxima,
                ':pontal_moldado'     => $pontal_moldado,
                ':arqueacao_bruta'    => $arqueacao_bruta,
                ':arqueacao_liquida'  => $arqueacao_liquida,
                ':metodo_arqueacao'   => $metodo_arqueacao,
                ':relatorio_numero'   => $relatorio_numero,
                ':data_vistoria'      => $data_vistoria,
                ':local_vistoria'     => $local_vistoria,
                ':data_emissao'       => $data_emissao,
                ':data_validade'      => $data_validade,
                ':local_emissao'      => $local_emissao,
                ':assinante_nome'     => $assinante_nome,
                ':assinante_titulo'   => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status'             => $status,
                ':vistoria_id'        => $vistoria_id,
                ':despachante_id'     => $despachante_id,
                ':id'                 => $id,
            ]);

        } else {
            // INSERIR - Gerar número e token
            $ano = date('y');
            $ano4 = date('Y');
            $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano");
            $stmt_num->execute([':ano' => $ano4]);
            $total = $stmt_num->fetch()['total'];
            $seq = $total + 1;
            $numero = "AM-CNARQ-{$seq}/{$ano}";
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO certificados_cnarq (
                        id, numero, token_assinatura,
                        nome_embarcacao, numero_inscricao, indicativo_chamada,
                        tipo_embarcacao, ano_construcao, material_casco,
                        porto_inscricao, local_construcao,
                        comprimento_total, comprimento_casco, comprimento_lpp,
                        boca_moldada, boca_maxima, pontal_moldado,
                        arqueacao_bruta, arqueacao_liquida, metodo_arqueacao,
                        relatorio_numero, data_vistoria, local_vistoria,
                        data_emissao, data_validade, local_emissao,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, criado_por, vistoria_id, despachante_id) VALUES (
                        :id, :numero, :token_assinatura,
                        :nome_embarcacao, :numero_inscricao, :indicativo_chamada,
                        :tipo_embarcacao, :ano_construcao, :material_casco,
                        :porto_inscricao, :local_construcao,
                        :comprimento_total, :comprimento_casco, :comprimento_lpp,
                        :boca_moldada, :boca_maxima, :pontal_moldado,
                        :arqueacao_bruta, :arqueacao_liquida, :metodo_arqueacao,
                        :relatorio_numero, :data_vistoria, :local_vistoria,
                        :data_emissao, :data_validade, :local_emissao,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        :status, :criado_por, :vistoria_id, :despachante_id)";

            $id = gerarUUID();

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'                 => $id,
                ':numero'             => $numero,
                ':token_assinatura'   => $token,
                ':nome_embarcacao'    => $nome_embarcacao,
                ':numero_inscricao'   => $numero_inscricao,
                ':indicativo_chamada' => $indicativo_chamada,
                ':tipo_embarcacao'    => $tipo_embarcacao,
                ':ano_construcao'     => $ano_construcao,
                ':material_casco'     => $material_casco,
                ':porto_inscricao'    => $porto_inscricao,
                ':local_construcao'   => $local_construcao,
                ':comprimento_total'  => $comprimento_total,
                ':comprimento_casco'  => $comprimento_casco,
                ':comprimento_lpp'    => $comprimento_lpp,
                ':boca_moldada'       => $boca_moldada,
                ':boca_maxima'        => $boca_maxima,
                ':pontal_moldado'     => $pontal_moldado,
                ':arqueacao_bruta'    => $arqueacao_bruta,
                ':arqueacao_liquida'  => $arqueacao_liquida,
                ':metodo_arqueacao'   => $metodo_arqueacao,
                ':relatorio_numero'   => $relatorio_numero,
                ':data_vistoria'      => $data_vistoria,
                ':local_vistoria'     => $local_vistoria,
                ':data_emissao'       => $data_emissao,
                ':data_validade'      => $data_validade,
                ':local_emissao'      => $local_emissao,
                ':assinante_nome'     => $assinante_nome,
                ':assinante_titulo'   => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status'             => $status,
                ':despachante_id'     => $despachante_id,
                ':criado_por'         => $_SESSION['usuario_id'] ?? null,
                ':vistoria_id'        => $vistoria_id,
            ]);
        }

        $pdo->commit();

        // Log de atividade
        $numero_cert = $editando ? ($certificado['numero'] ?? $numero) : $numero;
        log_atividade($editando ? 'certificado_cnarq_editado' : 'certificado_cnarq_criado', 
                      "Certificado {$numero_cert} - {$nome_embarcacao}");

        setMensagem('success', 'Certificado CNARQ ' . ($editando ? 'atualizado' : 'criado') . ' com sucesso.');
        redirecionar(APP_URL . 'documentacao/cnarq');

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Falha ao salvar certificado CNARQ: ' . $e->getMessage());
        setMensagem('error', 'Não foi possível salvar o certificado. Tente novamente ou contate o suporte.');
        redirecionar(APP_URL . 'documentacao/cnarq/form' . ($editando ? "?id={$id}" : ''));
    }
}

// ============================================
// ENVIAR LINK DE ASSINATURA POR E-MAIL
// ============================================
if ($action === 'enviar_assinatura') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    require_once __DIR__ . '/../../../includes/assinaturas_usuarios.php';
    try { $resultado = assinaturaEnviarConviteCertificado($pdo, 'CNARQ', $id); }
    catch (Throwable $e) { $resultado = ['success'=>false,'message'=>$e->getMessage()]; }

    if ($resultado['success']) {
        log_atividade('certificado_cnarq_assinatura_enviada', "Link de assinatura CNARQ ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/cnarq');
}

// ============================================
// ENVIAR CERTIFICADO POR E-MAIL
// ============================================
if ($action === 'enviar_certificado') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    require_once __DIR__ . '/../../../includes/enviar_certificado.php';

    $resultado = enviarCertificadoEmail(
        $pdo,
        $id,
        'certificados_cnarq',
        'CNARQ',
        'documentacao/cnarq/pdf'
    );

    if ($resultado['success']) {
        log_atividade('certificado_cnarq_enviado_email', "Certificado CNARQ ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/cnarq');
}

// Ação inválida
setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/cnarq');
