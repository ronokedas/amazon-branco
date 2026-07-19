<?php
/**
 * MÓDULO: Documentação > LC (Licença de Construção / LCEC)
 * Actions: Salvar e enviar documentos
 * Numeração: AM-LC:{n}/{ano} ou AM-EC:{n}/{ano}
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$action = $_POST['action'] ?? '';

// ============================================
// SALVAR
// ============================================
if ($action === 'salvar') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/lc');
    }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo,'certificados_lc',$id,APP_URL.'documentacao/lc');

    $tipo_licenca = $_POST['tipo_licenca'] ?? 'LC';
    $nome_embarcacao = trim($_POST['nome_embarcacao'] ?? '');
    $tipo_embarcacao = trim($_POST['tipo_embarcacao'] ?? '');
    $numero_casco = trim($_POST['numero_casco'] ?? '');
    $material_casco = trim($_POST['material_casco'] ?? '');
    $sociedade_classificadora = trim($_POST['sociedade_classificadora'] ?? '');
    $comprimento_total = $_POST['comprimento_total'] !== '' ? $_POST['comprimento_total'] : null;
    $comprimento_pp = $_POST['comprimento_pp'] !== '' ? $_POST['comprimento_pp'] : null;
    $boca_moldada = $_POST['boca_moldada'] !== '' ? $_POST['boca_moldada'] : null;
    $pontal_moldado = $_POST['pontal_moldado'] !== '' ? $_POST['pontal_moldado'] : null;
    $calado_maximo = $_POST['calado_maximo'] !== '' ? $_POST['calado_maximo'] : null;
    $porte_bruto = $_POST['porte_bruto'] !== '' ? $_POST['porte_bruto'] : null;
    $numero_tripulantes = $_POST['numero_tripulantes'] !== '' ? (int)$_POST['numero_tripulantes'] : null;
    $numero_passageiros = $_POST['numero_passageiros'] !== '' ? (int)$_POST['numero_passageiros'] : null;
    $tipo_navegacao = trim($_POST['tipo_navegacao'] ?? '');
    $area_navegacao = trim($_POST['area_navegacao'] ?? '');
    $atividade_servico = trim($_POST['atividade_servico'] ?? '');
    $propulsao = trim($_POST['propulsao'] ?? '');
    $proprietario_nome = trim($_POST['proprietario_nome'] ?? '');
    $proprietario_cpf_cnpj = trim($_POST['proprietario_cpf_cnpj'] ?? '');
    $proprietario_endereco = trim($_POST['proprietario_endereco'] ?? '');
    $estaleiro_nome = trim($_POST['estaleiro_nome'] ?? '');
    $estaleiro_cpf_cnpj = trim($_POST['estaleiro_cpf_cnpj'] ?? '');
    $estaleiro_endereco = trim($_POST['estaleiro_endereco'] ?? '');
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_validade = $_POST['data_validade'] ?: null;
    $data_termino_construcao = $_POST['data_termino_construcao'] ?: null;
    $local_emissao = trim($_POST['local_emissao'] ?? 'Belém-PA');
    $assinante_nome = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');
    
    $despachante_id = $_POST['despachante_id'] ?? null;
    if(empty($despachante_id)) $despachante_id = null;

    $status = $_POST['status'] ?? 'rascunho';
    if (!in_array($status, ['rascunho','emitido','cancelado'])) $status = 'rascunho';
    
    if (!in_array($tipo_licenca, ['LC','LA','LR','LCEC'])) {
        setMensagem('error', 'Tipo de licença inválido.');
        redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
    }
    if (empty($nome_embarcacao)) {
        setMensagem('error', 'O nome da embarcação é obrigatório.');
        redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
    }
    if (empty($data_emissao)) {
        setMensagem('error', 'A data de emissão é obrigatória.');
        redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
    }

    $vistoria_id = $_POST['vistoria_id'] ?? null;
    if ($vistoria_id) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
        if (empty($liberacao['permitido'])) {
            setMensagem('error', $liberacao['mensagem']);
            redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
        }
    }
    if (empty($vistoria_id)) {
        setMensagem('error', 'É obrigatório selecionar um relatório aprovado para emitir o certificado.');
        redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
    } else {
        $stmtStatus = $pdo->prepare("SELECT status FROM vistorias WHERE id = :vid");
        $stmtStatus->execute([':vid' => $vistoria_id]);
        $vistData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        if (!$vistData || !in_array($vistData['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'])) {
            setMensagem('error', 'Não é possível emitir certificado. O relatório selecionado não está aprovado.');
            redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
        }
    }
    
    try {
        if ($editando) {
            $sql = "UPDATE certificados_lc SET
                        tipo_licenca = :tipo_licenca,
                        nome_embarcacao = :nome_embarcacao,
                        tipo_embarcacao = :tipo_embarcacao,
                        numero_casco = :numero_casco,
                        material_casco = :material_casco,
                        sociedade_classificadora = :sociedade_classificadora,
                        comprimento_total = :comprimento_total,
                        comprimento_pp = :comprimento_pp,
                        boca_moldada = :boca_moldada,
                        pontal_moldado = :pontal_moldado,
                        calado_maximo = :calado_maximo,
                        porte_bruto = :porte_bruto,
                        numero_tripulantes = :numero_tripulantes,
                        numero_passageiros = :numero_passageiros,
                        tipo_navegacao = :tipo_navegacao,
                        area_navegacao = :area_navegacao,
                        atividade_servico = :atividade_servico,
                        propulsao = :propulsao,
                        proprietario_nome = :proprietario_nome,
                        proprietario_cpf_cnpj = :proprietario_cpf_cnpj,
                        proprietario_endereco = :proprietario_endereco,
                        estaleiro_nome = :estaleiro_nome,
                        estaleiro_cpf_cnpj = :estaleiro_cpf_cnpj,
                        estaleiro_endereco = :estaleiro_endereco,
                        data_emissao = :data_emissao,
                        data_validade = :data_validade,
                        data_termino_construcao = :data_termino_construcao,
                        local_emissao = :local_emissao,
                        assinante_nome = :assinante_nome,
                        assinante_titulo = :assinante_titulo,
                        assinante_registro = :assinante_registro,
                        status = :status, vistoria_id = :vistoria_id, despachante_id = :despachante_id WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo_licenca' => $tipo_licenca,
                ':nome_embarcacao' => $nome_embarcacao,
                ':tipo_embarcacao' => $tipo_embarcacao,
                ':numero_casco' => $numero_casco,
                ':material_casco' => $material_casco,
                ':sociedade_classificadora' => $sociedade_classificadora,
                ':comprimento_total' => $comprimento_total,
                ':comprimento_pp' => $comprimento_pp,
                ':boca_moldada' => $boca_moldada,
                ':pontal_moldado' => $pontal_moldado,
                ':calado_maximo' => $calado_maximo,
                ':porte_bruto' => $porte_bruto,
                ':numero_tripulantes' => $numero_tripulantes,
                ':numero_passageiros' => $numero_passageiros,
                ':tipo_navegacao' => $tipo_navegacao,
                ':area_navegacao' => $area_navegacao,
                ':atividade_servico' => $atividade_servico,
                ':propulsao' => $propulsao,
                ':proprietario_nome' => $proprietario_nome,
                ':proprietario_cpf_cnpj' => $proprietario_cpf_cnpj,
                ':proprietario_endereco' => $proprietario_endereco,
                ':estaleiro_nome' => $estaleiro_nome,
                ':estaleiro_cpf_cnpj' => $estaleiro_cpf_cnpj,
                ':estaleiro_endereco' => $estaleiro_endereco,
                ':data_emissao' => $data_emissao,
                ':data_validade' => $data_validade,
                ':data_termino_construcao' => $data_termino_construcao,
                ':local_emissao' => $local_emissao,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':vistoria_id' => $vistoria_id,
                ':despachante_id' => $despachante_id,
                ':id' => $id,
            ]);
            
            $numero = $pdo->prepare("SELECT numero_lc FROM certificados_lc WHERE id = :id");
            $numero->execute([':id' => $id]);
            $numero_lc = $numero->fetch()['numero_lc'];
        } else {
            $tipo_seq = ($tipo_licenca === 'LCEC') ? 'EC' : $tipo_licenca;
            $prefixo = 'AM-' . $tipo_seq;
            $numero_lc = gerarNumeroDocumento($tipo_seq, $prefixo);
            $token = bin2hex(random_bytes(32));
            $id = gerarUUID();

            $sql = "INSERT INTO certificados_lc (
                        id, numero_lc, token_assinatura,
                        tipo_licenca, nome_embarcacao, tipo_embarcacao, numero_casco,
                        material_casco, sociedade_classificadora,
                        comprimento_total, comprimento_pp, boca_moldada, pontal_moldado, calado_maximo,
                        porte_bruto, numero_tripulantes, numero_passageiros,
                        tipo_navegacao, area_navegacao, atividade_servico, propulsao,
                        proprietario_nome, proprietario_cpf_cnpj, proprietario_endereco,
                        estaleiro_nome, estaleiro_cpf_cnpj, estaleiro_endereco,
                        data_emissao, data_validade, data_termino_construcao, local_emissao,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, criado_por, vistoria_id, despachante_id) VALUES (
                        :id, :numero_lc, :token_assinatura,
                        :tipo_licenca, :nome_embarcacao, :tipo_embarcacao, :numero_casco,
                        :material_casco, :sociedade_classificadora,
                        :comprimento_total, :comprimento_pp, :boca_moldada, :pontal_moldado, :calado_maximo,
                        :porte_bruto, :numero_tripulantes, :numero_passageiros,
                        :tipo_navegacao, :area_navegacao, :atividade_servico, :propulsao,
                        :proprietario_nome, :proprietario_cpf_cnpj, :proprietario_endereco,
                        :estaleiro_nome, :estaleiro_cpf_cnpj, :estaleiro_endereco,
                        :data_emissao, :data_validade, :data_termino_construcao, :local_emissao,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        :status, :criado_por, :vistoria_id, :despachante_id)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':numero_lc' => $numero_lc,
                ':token_assinatura' => $token,
                ':tipo_licenca' => $tipo_licenca,
                ':nome_embarcacao' => $nome_embarcacao,
                ':tipo_embarcacao' => $tipo_embarcacao,
                ':numero_casco' => $numero_casco,
                ':material_casco' => $material_casco,
                ':sociedade_classificadora' => $sociedade_classificadora,
                ':comprimento_total' => $comprimento_total,
                ':comprimento_pp' => $comprimento_pp,
                ':boca_moldada' => $boca_moldada,
                ':pontal_moldado' => $pontal_moldado,
                ':calado_maximo' => $calado_maximo,
                ':porte_bruto' => $porte_bruto,
                ':numero_tripulantes' => $numero_tripulantes,
                ':numero_passageiros' => $numero_passageiros,
                ':tipo_navegacao' => $tipo_navegacao,
                ':area_navegacao' => $area_navegacao,
                ':atividade_servico' => $atividade_servico,
                ':propulsao' => $propulsao,
                ':proprietario_nome' => $proprietario_nome,
                ':proprietario_cpf_cnpj' => $proprietario_cpf_cnpj,
                ':proprietario_endereco' => $proprietario_endereco,
                ':estaleiro_nome' => $estaleiro_nome,
                ':estaleiro_cpf_cnpj' => $estaleiro_cpf_cnpj,
                ':estaleiro_endereco' => $estaleiro_endereco,
                ':data_emissao' => $data_emissao,
                ':data_validade' => $data_validade,
                ':data_termino_construcao' => $data_termino_construcao,
                ':local_emissao' => $local_emissao,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':vistoria_id' => $vistoria_id,
                ':criado_por' => $_SESSION['usuario_id'] ?? null,
                ':despachante_id' => $despachante_id,
            ]);
        }

        setMensagem('success', 'Licença ' . ($editando ? 'atualizada' : 'criada') . ' com sucesso.');
        redirecionar(APP_URL . 'documentacao/lc');

    } catch (Exception $e) {
        setMensagem('error', 'Erro ao salvar: ' . $e->getMessage());
        redirecionar(APP_URL . 'documentacao/lc/form' . ($editando ? "?id={$id}" : ''));
    }
}

// ============================================
// ENVIAR ASSINATURA
// ============================================
if ($action === 'enviar_assinatura') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) { setMensagem('error', 'Token inválido.'); redirecionar(APP_URL . 'documentacao/lc'); }
    $id = $_POST['id'] ?? '';
    if (empty($id)) { setMensagem('error', 'ID não informado.'); redirecionar(APP_URL . 'documentacao/lc'); }
    require_once __DIR__ . '/../../../includes/enviar_assinatura.php';
    $r = enviarAssinaturaEmail($pdo, $id, 'certificados_lc', 'LC');
    if ($r['success']) { log_atividade('licenca_lc_assinatura_enviada', "Link LC ID: {$id}"); setMensagem('success', $r['message']); }
    else { setMensagem('error', $r['message']); }
    redirecionar(APP_URL . 'documentacao/lc');
}

// ============================================
// ENVIAR CERTIFICADO
// ============================================
if ($action === 'enviar_certificado') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) { setMensagem('error', 'Token inválido.'); redirecionar(APP_URL . 'documentacao/lc'); }
    $id = $_POST['id'] ?? '';
    if (empty($id)) { setMensagem('error', 'ID não informado.'); redirecionar(APP_URL . 'documentacao/lc'); }
    require_once __DIR__ . '/../../../includes/enviar_certificado.php';
    $r = enviarCertificadoEmail($pdo, $id, 'certificados_lc', 'LC', 'documentacao/lc/pdf');
    if ($r['success']) { log_atividade('licenca_lc_enviada_email', "LC ID: {$id}"); setMensagem('success', $r['message']); }
    else { setMensagem('error', $r['message']); }
    redirecionar(APP_URL . 'documentacao/lc');
}

setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/lc');
