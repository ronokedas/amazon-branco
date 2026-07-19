<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Actions: Salvar e enviar documentos
 * Numeração automática AM-CNBL-{n}/{ano}
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

if ($action === 'atualizar_convalidacoes') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }
    $id = trim((string)($_POST['id'] ?? ''));
    if (!documentoEstaAprovadoOuAssinado($pdo, 'certificados_cnbl', 'CNBL', $id)) {
        setMensagem('error', 'Esta exceção de edição existe apenas para certificado aprovado ou assinado.');
        redirecionar(APP_URL . 'documentacao/cnbl/form?id=' . urlencode($id));
    }
    $ids = $_POST['conv_id'] ?? [];
    $locais = $_POST['conv_local'] ?? [];
    $datas = $_POST['conv_data_realizacao'] ?? [];
    $vistoriadores = $_POST['conv_vistoriador_id'] ?? [];
    try {
        $pdo->beginTransaction();
        $buscarVistoriador = $pdo->prepare("SELECT nome FROM usuarios WHERE id=:id AND cargo='VISTORIADOR'");
        $atualizar = $pdo->prepare("UPDATE cert_convalidacoes SET local_data=:local_data, vistoriador=:vistoriador WHERE id=:conv_id AND certificado_id=:certificado_id AND tipo_certificado='CNBL'");
        $locaisPermitidos = certificadoLocaisVistoria($pdo);
        foreach ($ids as $i => $convId) {
            $local = trim((string)($locais[$i] ?? ''));
            $data = trim((string)($datas[$i] ?? ''));
            $vistoriadorId = trim((string)($vistoriadores[$i] ?? ''));
            if ($local === '' && $data === '' && $vistoriadorId === '') {
                continue;
            } else {
                if ($local === '' || $data === '' || $vistoriadorId === '') throw new InvalidArgumentException('Local, data e vistoriador devem ser preenchidos juntos.');
                if (!in_array($local, $locaisPermitidos, true)) throw new InvalidArgumentException('Selecione um local de vistoria cadastrado.');
                $buscarVistoriador->execute([':id'=>$vistoriadorId]);
                $nome=(string)$buscarVistoriador->fetchColumn();
                if ($nome === '') throw new InvalidArgumentException('Selecione um vistoriador cadastrado no sistema.');
            }
            $atualizar->execute([':local_data'=>certificadoComporLocalDataConvalidacao($local,$data),':vistoriador'=>$nome,':conv_id'=>$convId,':certificado_id'=>$id]);
        }
        $pdo->commit();
        log_atividade('convalidacoes_cnbl_atualizadas', "Convalidações do certificado CNBL {$id} atualizadas sem alterar os dados assinados.");
        setMensagem('success', 'Convalidações atualizadas com sucesso. Os demais dados assinados permaneceram inalterados.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setMensagem('error', 'Erro ao atualizar convalidações: ' . $e->getMessage());
    }
    redirecionar(APP_URL . 'documentacao/cnbl/form?id=' . urlencode($id));
}

// ============================================
// SALVAR CERTIFICADO
// ============================================
if ($action === 'salvar') {
    // Verificar CSRF
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo,'certificados_cnbl',$id,APP_URL.'documentacao/cnbl');

    // Dados principais
    $nome_embarcacao      = trim($_POST['nome_embarcacao'] ?? '');
    $numero_inscricao     = trim($_POST['numero_inscricao'] ?? '');
    $porto_inscricao      = trim($_POST['porto_inscricao'] ?? '');
    $indicativo_chamada   = trim($_POST['indicativo_chamada'] ?? '');
    $atividades_servicos  = trim($_POST['atividades_servicos'] ?? '');
    $tipo_embarcacao      = trim($_POST['tipo_embarcacao'] ?? '');
    $ano_construcao       = trim($_POST['ano_construcao'] ?? '');
    $comprimento_total    = $_POST['comprimento_total'] !== '' ? $_POST['comprimento_total'] : null;
    $comprimento_casco    = $_POST['comprimento_casco'] !== '' ? $_POST['comprimento_casco'] : null;
    $boca_moldada         = $_POST['boca_moldada'] !== '' ? $_POST['boca_moldada'] : null;
    $pontal_moldado       = $_POST['pontal_moldado'] !== '' ? $_POST['pontal_moldado'] : null;
    $arqueacao_bruta      = trim($_POST['arqueacao_bruta'] ?? '');
    $material_casco       = trim($_POST['material_casco'] ?? '');

    // Checkboxes (arrays)
    $tipo_navegacao = isset($_POST['tipo_navegacao']) ? implode(',', $_POST['tipo_navegacao']) : '';
    $area_navegacao = $_POST['area_navegacao'] ?? '';

    // Borda livre
    $borda_livre_mm   = $_POST['borda_livre_mm'] !== '' ? (int)$_POST['borda_livre_mm'] : null;

    // Dimensões da embarcação
    $comprimento_total    = $_POST['comprimento_total'] !== '' ? $_POST['comprimento_total'] : null;
    $comprimento_casco    = $_POST['comprimento_casco'] !== '' ? $_POST['comprimento_casco'] : null;
    $boca_moldada         = $_POST['boca_moldada'] !== '' ? $_POST['boca_moldada'] : null;
    $pontal_moldado       = $_POST['pontal_moldado'] !== '' ? $_POST['pontal_moldado'] : null;

    // Marcas de Linha de Carga
    $aresta_superior_linha_conves = trim($_POST['aresta_superior_linha_conves'] ?? '');
    $centro_disco_situado = trim($_POST['centro_disco_situado'] ?? '');
    $dist_linha_conves_bico_proa = trim($_POST['dist_linha_conves_bico_proa'] ?? '');
    $dist_linha_conves_abaixo_disco = trim($_POST['dist_linha_conves_abaixo_disco'] ?? '');
    $marca_linha_carga_area1 = trim($_POST['marca_linha_carga_area1'] ?? '');
    $marca_linha_carga_area2 = trim($_POST['marca_linha_carga_area2'] ?? '');
    $acrescimo_agua_salgada = trim($_POST['acrescimo_agua_salgada'] ?? '');

    // Vistoria
    $relatorio_numero = trim($_POST['relatorio_numero'] ?? '');
    $local_vistoria   = trim($_POST['local_vistoria'] ?? '');
    $data_vistoria    = $_POST['data_vistoria'] ?: null;

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
    $vistoria_id = $_POST['vistoria_id'] ?? null;
    if ($vistoria_id) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
        if (empty($liberacao['permitido'])) {
            setMensagem('error', $liberacao['mensagem']);
            redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
        }
    }
    if (empty($vistoria_id)) {
        setMensagem('error', 'É obrigatório selecionar um relatório aprovado para emitir o certificado.');
        redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
    } else {
        $stmtStatus = $pdo->prepare("SELECT status FROM vistorias WHERE id = :vid");
        $stmtStatus->execute([':vid' => $vistoria_id]);
        $vistData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        if (!$vistData || !in_array($vistData['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'])) {
            setMensagem('error', 'Não é possível emitir certificado. O relatório selecionado não está aprovado.');
            redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
        }
    }

    if (empty($nome_embarcacao)) {
        setMensagem('error', 'O nome da embarcação é obrigatório.');
        redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_emissao)) {
        setMensagem('error', 'A data de emissão é obrigatória.');
        redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_validade)) {
        setMensagem('error', 'A data de validade é obrigatória.');
        redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
    }

    try {
        $pdo->beginTransaction();

        if ($editando) {
            // ATUALIZAR
            $sql = "UPDATE certificados_cnbl SET
                        nome_embarcacao = :nome_embarcacao,
                        numero_inscricao = :numero_inscricao,
                        porto_inscricao = :porto_inscricao,
                        indicativo_chamada = :indicativo_chamada,
                        atividades_servicos = :atividades_servicos,
                        tipo_embarcacao = :tipo_embarcacao,
                        ano_construcao = :ano_construcao,
                        comprimento_total = :comprimento_total,
                        comprimento_casco = :comprimento_casco,
                        boca_moldada = :boca_moldada,
                        pontal_moldado = :pontal_moldado,
                        arqueacao_bruta = :arqueacao_bruta,
                        tipo_navegacao = :tipo_navegacao,
                        area_navegacao = :area_navegacao,
                        material_casco = :material_casco,
                        borda_livre_mm = :borda_livre_mm,
                        aresta_superior_linha_conves = :aresta_superior_linha_conves,
                        centro_disco_situado = :centro_disco_situado,
                        dist_linha_conves_bico_proa = :dist_linha_conves_bico_proa,
                        dist_linha_conves_abaixo_disco = :dist_linha_conves_abaixo_disco,
                        marca_linha_carga_area1 = :marca_linha_carga_area1,
                        marca_linha_carga_area2 = :marca_linha_carga_area2,
                        acrescimo_agua_salgada = :acrescimo_agua_salgada,
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
                ':nome_embarcacao'     => $nome_embarcacao,
                ':numero_inscricao'    => $numero_inscricao,
                ':porto_inscricao'     => $porto_inscricao,
                ':indicativo_chamada'  => $indicativo_chamada,
                ':atividades_servicos' => $atividades_servicos,
                ':tipo_embarcacao'     => $tipo_embarcacao,
                ':ano_construcao'      => $ano_construcao,
                ':comprimento_total'   => $comprimento_total,
                ':comprimento_casco'   => $comprimento_casco,
                ':boca_moldada'        => $boca_moldada,
                ':pontal_moldado'      => $pontal_moldado,
                ':arqueacao_bruta'     => $arqueacao_bruta,
                ':tipo_navegacao'      => $tipo_navegacao,
                ':area_navegacao'      => $area_navegacao,
                ':material_casco'      => $material_casco,
                ':borda_livre_mm'      => $borda_livre_mm,
                ':aresta_superior_linha_conves' => $aresta_superior_linha_conves,
                ':centro_disco_situado' => $centro_disco_situado,
                ':dist_linha_conves_bico_proa' => $dist_linha_conves_bico_proa,
                ':dist_linha_conves_abaixo_disco' => $dist_linha_conves_abaixo_disco,
                ':marca_linha_carga_area1' => $marca_linha_carga_area1,
                ':marca_linha_carga_area2' => $marca_linha_carga_area2,
                ':acrescimo_agua_salgada' => $acrescimo_agua_salgada,
                ':relatorio_numero'    => $relatorio_numero,
                ':data_vistoria'       => $data_vistoria,
                ':local_vistoria'      => $local_vistoria,
                ':data_emissao'        => $data_emissao,
                ':data_validade'       => $data_validade,
                ':local_emissao'       => $local_emissao,
                ':assinante_nome'      => $assinante_nome,
                ':assinante_titulo'    => $assinante_titulo,
                ':assinante_registro'  => $assinante_registro,
                ':status'              => $status,
                ':vistoria_id'          => $vistoria_id,
                ':despachante_id'       => $despachante_id,
                ':id'                  => $id,
            ]);

            // Deletar convalidações antigas e reinserir
            $stmt_del = $pdo->prepare("DELETE FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNBL'");
            $stmt_del->execute([':cert_id' => $id]);

        } else {
            // INSERIR - Gerar número e token
            $ano = date('y');
            $ano4 = date('Y');
            $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano");
            $stmt_num->execute([':ano' => $ano4]);
            $total = $stmt_num->fetch()['total'];
            $seq = $total + 1;
            $numero = "AM-CNBL-{$seq}/{$ano}";
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO certificados_cnbl (
                        id, numero, token_assinatura,
                        nome_embarcacao, numero_inscricao, porto_inscricao, indicativo_chamada,
                        atividades_servicos, tipo_embarcacao, ano_construcao,
                        comprimento_total, comprimento_casco, boca_moldada, pontal_moldado,
                        arqueacao_bruta, tipo_navegacao, area_navegacao, material_casco,
                        borda_livre_mm,
                        aresta_superior_linha_conves, centro_disco_situado, dist_linha_conves_bico_proa,
                        dist_linha_conves_abaixo_disco, marca_linha_carga_area1, marca_linha_carga_area2,
                        acrescimo_agua_salgada,
                        relatorio_numero, data_vistoria, local_vistoria,
                        data_emissao, data_validade, local_emissao,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, criado_por, vistoria_id, despachante_id) VALUES (
                        :id, :numero, :token_assinatura,
                        :nome_embarcacao, :numero_inscricao, :porto_inscricao, :indicativo_chamada,
                        :atividades_servicos, :tipo_embarcacao, :ano_construcao,
                        :comprimento_total, :comprimento_casco, :boca_moldada, :pontal_moldado,
                        :arqueacao_bruta, :tipo_navegacao, :area_navegacao, :material_casco,
                        :borda_livre_mm,
                        :aresta_superior_linha_conves, :centro_disco_situado, :dist_linha_conves_bico_proa,
                        :dist_linha_conves_abaixo_disco, :marca_linha_carga_area1, :marca_linha_carga_area2,
                        :acrescimo_agua_salgada,
                        :relatorio_numero, :data_vistoria, :local_vistoria,
                        :data_emissao, :data_validade, :local_emissao,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        :status, :criado_por, :vistoria_id, :despachante_id)";

            $id = gerarUUID();

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'                  => $id,
                ':numero'              => $numero,
                ':token_assinatura'    => $token,
                ':nome_embarcacao'     => $nome_embarcacao,
                ':numero_inscricao'    => $numero_inscricao,
                ':porto_inscricao'     => $porto_inscricao,
                ':indicativo_chamada'  => $indicativo_chamada,
                ':atividades_servicos' => $atividades_servicos,
                ':tipo_embarcacao'     => $tipo_embarcacao,
                ':ano_construcao'      => $ano_construcao,
                ':comprimento_total'   => $comprimento_total,
                ':comprimento_casco'   => $comprimento_casco,
                ':boca_moldada'        => $boca_moldada,
                ':pontal_moldado'      => $pontal_moldado,
                ':arqueacao_bruta'     => $arqueacao_bruta,
                ':tipo_navegacao'      => $tipo_navegacao,
                ':area_navegacao'      => $area_navegacao,
                ':material_casco'      => $material_casco,
                ':borda_livre_mm'      => $borda_livre_mm,
                ':aresta_superior_linha_conves' => $aresta_superior_linha_conves,
                ':centro_disco_situado' => $centro_disco_situado,
                ':dist_linha_conves_bico_proa' => $dist_linha_conves_bico_proa,
                ':dist_linha_conves_abaixo_disco' => $dist_linha_conves_abaixo_disco,
                ':marca_linha_carga_area1' => $marca_linha_carga_area1,
                ':marca_linha_carga_area2' => $marca_linha_carga_area2,
                ':acrescimo_agua_salgada' => $acrescimo_agua_salgada,
                ':relatorio_numero'    => $relatorio_numero,
                ':data_vistoria'       => $data_vistoria,
                ':local_vistoria'      => $local_vistoria,
                ':data_emissao'        => $data_emissao,
                ':data_validade'       => $data_validade,
                ':local_emissao'       => $local_emissao,
                ':assinante_nome'      => $assinante_nome,
                ':assinante_titulo'    => $assinante_titulo,
                ':assinante_registro'  => $assinante_registro,
                ':status'              => $status,
                ':criado_por'          => $_SESSION['usuario_id'] ?? null,
                ':vistoria_id'          => $vistoria_id,
                ':despachante_id'       => $despachante_id,
            ]);
        }

        // Salvar convalidações
        $conv_numero      = $_POST['conv_numero'] ?? [];
        $conv_data_inicio = $_POST['conv_data_inicio'] ?? [];
        $conv_data_fim    = $_POST['conv_data_fim'] ?? [];
        $conv_local       = $_POST['conv_local'] ?? [];
        $conv_realizacao  = $_POST['conv_data_realizacao'] ?? [];
        $conv_vistoriador = $_POST['conv_vistoriador_id'] ?? [];
        $stmt_nome_vistoriador = $pdo->prepare("SELECT nome FROM usuarios WHERE id=:id AND cargo='VISTORIADOR'");

        $stmt_conv = $pdo->prepare("INSERT INTO cert_convalidacoes 
                                    (id, tipo_certificado, certificado_id, numero_vistoria, data_inicio, data_fim, local_data, vistoriador) 
                                    VALUES (:id, :tipo_certificado, :cert_id, :numero, :data_inicio, :data_fim, :local_data, :vistoriador)");

        for ($i = 0; $i < count($conv_numero); $i++) {
            $numero   = trim($conv_numero[$i] ?? '');
            $dt_inicio = $conv_data_inicio[$i] ?: null;
            $dt_fim    = $conv_data_fim[$i] ?: null;
            $local     = trim($conv_local[$i] ?? '');
            $local_dt  = certificadoComporLocalDataConvalidacao($local, $conv_realizacao[$i] ?? null);
            $vist_id   = trim($conv_vistoriador[$i] ?? '');
            $vist      = '';
            if ($vist_id !== '') {
                $stmt_nome_vistoriador->execute([':id'=>$vist_id]);
                $vist=(string)$stmt_nome_vistoriador->fetchColumn();
                if ($vist === '') throw new InvalidArgumentException('Vistoriador da convalidação inválido.');
            }

            $stmt_conv->execute([
                ':id'                => gerarUUID(),
                ':tipo_certificado'  => 'CNBL',
                ':cert_id'           => $id,
                ':numero'            => $numero,
                ':data_inicio'       => $dt_inicio,
                ':data_fim'          => $dt_fim,
                ':local_data'        => $local_dt,
                ':vistoriador'       => $vist,
            ]);
        }

        $pdo->commit();

        // Log de atividade
        $numero_cert = $editando ? ($certificado['numero'] ?? $numero) : $numero;
        log_atividade($editando ? 'certificado_cnbl_editado' : 'certificado_cnbl_criado', 
                      "Certificado {$numero_cert} - {$nome_embarcacao}");

        setMensagem('success', 'Certificado CNBL ' . ($editando ? 'atualizado' : 'criado') . ' com sucesso.');
        redirecionar(APP_URL . 'documentacao/cnbl');

    } catch (Exception $e) {
        $pdo->rollBack();
        setMensagem('error', 'Erro ao salvar certificado: ' . $e->getMessage());
        redirecionar(APP_URL . 'documentacao/cnbl/form' . ($editando ? "?id={$id}" : ''));
    }
}

// ============================================
// ENVIAR LINK DE ASSINATURA POR E-MAIL
// ============================================
if ($action === 'enviar_assinatura') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    require_once __DIR__ . '/../../../includes/enviar_assinatura.php';

    $resultado = enviarAssinaturaEmail(
        $pdo,
        $id,
        'certificados_cnbl',
        'CNBL'
    );

    if ($resultado['success']) {
        log_atividade('certificado_cnbl_assinatura_enviada', "Link de assinatura CNBL ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/cnbl');
}

// ============================================
// ENVIAR CERTIFICADO POR E-MAIL
// ============================================
if ($action === 'enviar_certificado') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    require_once __DIR__ . '/../../../includes/enviar_certificado.php';

    $resultado = enviarCertificadoEmail(
        $pdo,
        $id,
        'certificados_cnbl',
        'CNBL',
        'documentacao/cnbl/pdf'
    );

    if ($resultado['success']) {
        log_atividade('certificado_cnbl_enviado_email', "Certificado CNBL ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/cnbl');
}

// Ação inválida
setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/cnbl');
