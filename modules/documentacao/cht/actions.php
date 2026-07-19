<?php
/**
 * CHT — Actions: Salvar e enviar documentos
 * Numeração: AM-REL-HT:{n}/{ano}
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

if ($action === 'salvar') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) { setMensagem('error','Token inválido.'); redirecionar(APP_URL.'documentacao/cht'); }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo,'certificados_cht',$id,APP_URL.'documentacao/cht');

    $profissional_empresa = trim($_POST['profissional_empresa'] ?? '');
    $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
    $email_destinatario = trim($_POST['email_destinatario'] ?? '');
    $atividade_homologada = trim($_POST['atividade_homologada'] ?? '');
    $relatorio_homologacao_numero = trim($_POST['relatorio_homologacao_numero'] ?? '');
    if ($relatorio_homologacao_numero !== '') {
        $stmtRelatorio = $pdo->prepare("SELECT id FROM vistorias WHERE numero = :numero ORDER BY criado_em DESC, id DESC LIMIT 1");
        $stmtRelatorio->execute([':numero' => $relatorio_homologacao_numero]);
        $vistoriaRelacionada = $stmtRelatorio->fetchColumn();
        if ($vistoriaRelacionada) {
            $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoriaRelacionada);
            if (empty($liberacao['permitido'])) {
                setMensagem('error', $liberacao['mensagem']);
                redirecionar(APP_URL . 'documentacao/cht/form' . ($editando ? "?id={$id}" : ''));
            }
        }
    }
    $observacoes = trim($_POST['observacoes'] ?? '');
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_validade = $_POST['data_validade'] ?? '';
    $local_emissao = trim($_POST['local_emissao'] ?? '');
    $assinante_nome = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');
    $despachante_id = $_POST['despachante_id'] ?? null;
    if(empty($despachante_id)) $despachante_id = null;

    $status = $_POST['status'] ?? 'rascunho';
    if (!in_array($status,['rascunho','emitido','cancelado'])) $status='rascunho';

    if (empty($profissional_empresa)) { setMensagem('error','Nome do profissional/empresa é obrigatório.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($cpf_cnpj)) { setMensagem('error','CPF/CNPJ é obrigatório.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (!filter_var($email_destinatario, FILTER_VALIDATE_EMAIL)) { setMensagem('error','E-mail válido é obrigatório.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($atividade_homologada)) { setMensagem('error','Atividade homologada é obrigatória.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($relatorio_homologacao_numero)) { setMensagem('error','Número do Relatório de Homologação Técnica é obrigatório.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($data_emissao)) { setMensagem('error','Data de emissão é obrigatória.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($data_validade)) { setMensagem('error','Data de validade é obrigatória.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    if (empty($local_emissao)) { setMensagem('error','Local de emissão é obrigatório.'); redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":'')); }
    
    try {
        if ($editando) {
            $sql = "UPDATE certificados_cht SET profissional_empresa=:profissional_empresa, cpf_cnpj=:cpf_cnpj, email_destinatario=:email_destinatario, atividade_homologada=:atividade_homologada, relatorio_homologacao_numero=:relatorio_homologacao_numero, numero_relatorio_ht=:numero_relatorio_ht, observacoes=:observacoes, data_emissao=:data_emissao, data_validade=:data_validade, local_emissao=:local_emissao, assinante_nome=:assinante_nome, assinante_titulo=:assinante_titulo, assinante_registro=:assinante_registro, status=:status, despachante_id=:despachante_id WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':profissional_empresa' => $profissional_empresa,
                ':cpf_cnpj' => $cpf_cnpj,
                ':email_destinatario' => $email_destinatario,
                ':atividade_homologada' => $atividade_homologada,
                ':relatorio_homologacao_numero' => $relatorio_homologacao_numero,
                ':numero_relatorio_ht' => $relatorio_homologacao_numero,
                ':observacoes' => $observacoes,
                ':data_emissao' => $data_emissao,
                ':data_validade' => $data_validade,
                ':local_emissao' => $local_emissao,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':despachante_id' => $despachante_id,
                ':id' => $id
            ]);
            $numero = $pdo->prepare("SELECT numero_relatorio_ht FROM certificados_cht WHERE id=:id");
            $numero->execute([':id'=>$id]); $num = $numero->fetch()['numero_relatorio_ht'];
        } else {
            $num = gerarNumeroDocumento('CHT', 'AM-CHT');
            $token = bin2hex(random_bytes(32));
            $id = gerarUUID();
            $sql = "INSERT INTO certificados_cht (id, numero_certificado, numero_relatorio_ht, token_assinatura, profissional_empresa, cpf_cnpj, email_destinatario, atividade_homologada, relatorio_homologacao_numero, observacoes, data_emissao, data_validade, local_emissao, assinante_nome, assinante_titulo, assinante_registro, status, criado_por, despachante_id) VALUES (:id, :numero_certificado, :numero_relatorio_ht, :token_assinatura, :profissional_empresa, :cpf_cnpj, :email_destinatario, :atividade_homologada, :relatorio_homologacao_numero, :observacoes, :data_emissao, :data_validade, :local_emissao, :assinante_nome, :assinante_titulo, :assinante_registro, :status, :criado_por, :despachante_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':numero_certificado' => $num,
                ':numero_relatorio_ht' => $relatorio_homologacao_numero,
                ':token_assinatura' => $token,
                ':profissional_empresa' => $profissional_empresa,
                ':cpf_cnpj' => $cpf_cnpj,
                ':email_destinatario' => $email_destinatario,
                ':atividade_homologada' => $atividade_homologada,
                ':relatorio_homologacao_numero' => $relatorio_homologacao_numero,
                ':observacoes' => $observacoes,
                ':data_emissao' => $data_emissao,
                ':data_validade' => $data_validade,
                ':local_emissao' => $local_emissao,
                ':assinante_nome' => $assinante_nome,
                ':assinante_titulo' => $assinante_titulo,
                ':assinante_registro' => $assinante_registro,
                ':status' => $status,
                ':despachante_id' => $despachante_id,
                ':criado_por' => $_SESSION['usuario_id'] ?? null
            ]);
        }

        setMensagem('success','CHT '.($editando?'atualizado':'criado').' com sucesso.');
        redirecionar(APP_URL.'documentacao/cht');

    } catch (Exception $e) {
        setMensagem('error','Erro: '.$e->getMessage());
        redirecionar(APP_URL.'documentacao/cht/form'.($editando?"?id={$id}":''));
    }
}

if ($action === 'enviar_assinatura') {
    if(!verificarCSRF($_POST['csrf_token']??'')){ setMensagem('error','Token inválido.'); redirecionar(APP_URL.'documentacao/cht'); }
    $id=$_POST['id']??''; if(empty($id)){ setMensagem('error','ID não informado.'); redirecionar(APP_URL.'documentacao/cht'); }
    require_once __DIR__.'/../../../includes/enviar_assinatura.php';
    $r=enviarAssinaturaEmail($pdo,$id,'certificados_cht','CHT');
    if($r['success']){ log_atividade('cht_assinatura_enviada',"Link CHT ID: {$id}"); setMensagem('success',$r['message']); }else{ setMensagem('error',$r['message']); }
    redirecionar(APP_URL.'documentacao/cht');
}

if ($action === 'enviar_certificado') {
    if(!verificarCSRF($_POST['csrf_token']??'')){ setMensagem('error','Token inválido.'); redirecionar(APP_URL.'documentacao/cht'); }
    $id=$_POST['id']??''; if(empty($id)){ setMensagem('error','ID não informado.'); redirecionar(APP_URL.'documentacao/cht'); }
    require_once __DIR__.'/../../../includes/enviar_certificado.php';
    $r=enviarCertificadoEmail($pdo,$id,'certificados_cht','CHT','documentacao/cht/pdf');
    if($r['success']){ log_atividade('cht_enviado_email',"CHT ID: {$id}"); setMensagem('success',$r['message']); }else{ setMensagem('error',$r['message']); }
    redirecionar(APP_URL.'documentacao/cht');
}

setMensagem('error','Ação inválida.');
redirecionar(APP_URL.'documentacao/cht');
