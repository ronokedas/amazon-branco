<?php
/**
 * MÓDULO: Documentação > Certificados CSN
 * Actions: Salvar e enviar documentos
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
        redirecionar(APP_URL . 'documentacao/certificados');
    }
    $id = trim((string)($_POST['id'] ?? ''));
    if (!documentoEstaAprovadoOuAssinado($pdo, 'certificados_csn', 'CSN', $id)) {
        setMensagem('error', 'Esta exceção de edição existe apenas para certificado aprovado ou assinado.');
        redirecionar(APP_URL . 'documentacao/certificados/form?id=' . urlencode($id));
    }
    $ids = $_POST['conv_id'] ?? [];
    $locais = $_POST['conv_local'] ?? [];
    $datas = $_POST['conv_data_realizacao'] ?? [];
    $vistoriadores = $_POST['conv_vistoriador_id'] ?? [];
    try {
        $pdo->beginTransaction();
        $buscarVistoriador = $pdo->prepare("SELECT nome FROM usuarios WHERE id=:id AND cargo='VISTORIADOR'");
        $atualizar = $pdo->prepare('UPDATE csn_convalidacoes SET local_data=:local_data, vistoriador=:vistoriador WHERE id=:conv_id AND certificado_id=:certificado_id');
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
        log_atividade('convalidacoes_csn_atualizadas', "Convalidações do certificado CSN {$id} atualizadas sem alterar os dados assinados.");
        setMensagem('success', 'Convalidações atualizadas com sucesso. Os demais dados assinados permaneceram inalterados.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setMensagem('error', 'Erro ao atualizar convalidações: ' . $e->getMessage());
    }
    redirecionar(APP_URL . 'documentacao/certificados/form?id=' . urlencode($id));
}

// ============================================
// SALVAR CERTIFICADO
// ============================================
if ($action === 'salvar') {
    // Verificar CSRF
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    $id = $_POST['id'] ?? null;
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo,'certificados_csn',$id,APP_URL.'documentacao/certificados');

    // Dados principais
    $nome_embarcacao    = trim($_POST['nome_embarcacao'] ?? '');
    $numero_inscricao   = trim($_POST['numero_inscricao'] ?? '');
    $indicativo_chamada = trim($_POST['indicativo_chamada'] ?? '');
    $atividades_servicos = trim($_POST['atividades_servicos'] ?? '');
    $tipo_embarcacao    = trim($_POST['tipo_embarcacao'] ?? '');
    $ano_construcao     = trim($_POST['ano_construcao'] ?? '');
    $comprimento_m      = $_POST['comprimento_m'] !== '' ? $_POST['comprimento_m'] : null;
    $arqueacao_bruta    = trim($_POST['arqueacao_bruta'] ?? '');
    $material_casco     = trim($_POST['material_casco'] ?? '');
    $fabricante_motor   = trim($_POST['fabricante_motor'] ?? '');
    $potencia_kw        = trim($_POST['potencia_kw'] ?? '');
    $autorizado_carga   = isset($_POST['autorizado_carga']) ? (int)$_POST['autorizado_carga'] : 0;
    $qtd_passageiros    = (int)($_POST['qtd_passageiros'] ?? 0);
    $obs_passageiros    = trim($_POST['obs_passageiros'] ?? '');
    $emitente           = 'AMAZON NAVAL';
    $normam_aplicavel   = 'NORMAM-202';
    $tipo_vistoria_certificado = trim($_POST['tipo_vistoria_certificado'] ?? '');
    $observacoes_verso  = trim($_POST['observacoes_verso'] ?? '');

    // Checkboxes (arrays)
    $tipo_navegacao = isset($_POST['tipo_navegacao']) ? implode(',', $_POST['tipo_navegacao']) : '';
    $area_navegacao = isset($_POST['area_navegacao']) ? implode(',', $_POST['area_navegacao']) : '';

    // Vistoria
    $relatorio_numero        = trim($_POST['relatorio_numero'] ?? '');
    $local_vistoria          = trim($_POST['local_vistoria'] ?? '');
    $data_vistoria_seco      = $_POST['data_vistoria_seco'] ?: null;
    $data_vistoria_flutuando = $_POST['data_vistoria_flutuando'] ?: null;
    $acessibilidade         = $_POST['acessibilidade'] ?? 'nao';
    $acessibilidade_sim     = ($acessibilidade === 'sim') ? 1 : 0;
    $acessibilidade_nao     = ($acessibilidade === 'nao') ? 1 : 0;

    // Datas e local
    $data_emissao   = $_POST['data_emissao'] ?? date('Y-m-d');
    $data_validade  = $_POST['data_validade'] ?? '';
    $local_emissao  = trim($_POST['local_emissao'] ?? 'Belém-PA');

    // Assinante
    $assinante_nome     = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo   = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');

    // Status
    $despachante_id = $_POST['despachante_id'] ?? null;
    if(empty($despachante_id)) $despachante_id = null;

    $tipo = trim($_POST['tipo'] ?? 'Definitivo');

    $status = $_POST['status'] ?? 'rascunho';
    if (!in_array($status, ['rascunho', 'emitido', 'cancelado'])) {
        $status = 'rascunho';
    }

    // Validações
    $vistoria_id = $_POST['vistoria_id'] ?? null;
    if (!$editando && $vistoria_id && !certificadoModeloPermitidoPorVistoria($pdo, (string)$vistoria_id, 'CSN')) {
        setMensagem('error', certificadoMensagemServicoObrigatorio('CSN'));
        redirecionar(APP_URL . 'certificados');
    }
    if ($vistoria_id) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
        if (empty($liberacao['permitido'])) {
            setMensagem('error', $liberacao['mensagem']);
            redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
        }
        $relatorio_numero = relatorioNumerosReferenciaCertificado($pdo, (string)$vistoria_id);
        if ($tipo === 'Definitivo' && ($liberacao['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS') {
            setMensagem('error', 'Certificado Definitivo bloqueado enquanto houver exigência comum pendente no relatório técnico original.');
            redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
        }
    }
    if (empty($vistoria_id)) {
        setMensagem('error', 'É obrigatório selecionar um relatório aprovado para emitir o certificado.');
        redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
    } else {
        $stmtStatus = $pdo->prepare("SELECT status FROM vistorias WHERE id = :vid");
        $stmtStatus->execute([':vid' => $vistoria_id]);
        $vistData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        if (!$vistData || !in_array($vistData['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'])) {
            setMensagem('error', 'Não é possível emitir certificado. O relatório selecionado não está aprovado.');
            redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
        }
    }

    if (empty($nome_embarcacao)) {
        setMensagem('error', 'O nome da embarcação é obrigatório.');
        redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_emissao)) {
        setMensagem('error', 'A data de emissão é obrigatória.');
        redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
    }

    if (empty($data_validade)) {
        setMensagem('error', 'A data de validade é obrigatória.');
        redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
    }

    // Verificar se já está assinado
    $ja_assinado = false;
    $cert_existente = null;
    if ($editando) {
        $stmtCheck = $pdo->prepare("SELECT assinado, criado_em, numero FROM certificados_csn WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $cert_existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($cert_existente && $cert_existente['assinado'] == 1) {
            $ja_assinado = true;
        }
    }

    try {
        $pdo->beginTransaction();

        if ($editando) {
            if ($ja_assinado) {
                // Se já estiver assinado, atualiza apenas o status e não altera dados imutáveis assinados
                $sql = "UPDATE certificados_csn SET status = :status WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':status' => $status,
                    ':id'     => $id
                ]);

                // Deletar apenas convalidações para reinserção (não mexe na distribuição de passageiros de docs assinados)
                $stmt_del2 = $pdo->prepare("DELETE FROM csn_convalidacoes WHERE certificado_id = :cert_id");
                $stmt_del2->execute([':cert_id' => $id]);
            } else {
                // ATUALIZAR COMPLETAMENTE
                $sql = "UPDATE certificados_csn SET
                            tipo = :tipo,
                            nome_embarcacao = :nome_embarcacao,
                            numero_inscricao = :numero_inscricao,
                            indicativo_chamada = :indicativo_chamada,
                            atividades_servicos = :atividades_servicos,
                            tipo_embarcacao = :tipo_embarcacao,
                            ano_construcao = :ano_construcao,
                            comprimento_m = :comprimento_m,
                            arqueacao_bruta = :arqueacao_bruta,
                            tipo_navegacao = :tipo_navegacao,
                            area_navegacao = :area_navegacao,
                            fabricante_motor = :fabricante_motor,
                            potencia_kw = :potencia_kw,
                            material_casco = :material_casco,
                            autorizado_carga = :autorizado_carga,
                            qtd_passageiros = :qtd_passageiros,
                            obs_passageiros = :obs_passageiros,
                            emitente = :emitente,
                            normam_aplicavel = :normam_aplicavel,
                            tipo_vistoria_certificado = :tipo_vistoria_certificado,
                            observacoes_verso = :observacoes_verso,
                            relatorio_numero = :relatorio_numero,
                            data_vistoria_seco = :data_vistoria_seco,
                            data_vistoria_flutuando = :data_vistoria_flutuando,
                            local_vistoria = :local_vistoria,
                            acessibilidade_sim = :acessibilidade_sim,
                            acessibilidade_nao = :acessibilidade_nao,
                            data_emissao = :data_emissao,
                            data_validade = :data_validade,
                            local_emissao = :local_emissao,
                            assinante_nome = :assinante_nome,
                            assinante_titulo = :assinante_titulo,
                            assinante_registro = :assinante_registro,
                            status = :status, vistoria_id = :vistoria_id, despachante_id = :despachante_id WHERE id = :id";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':tipo'                 => $tipo,
                    ':nome_embarcacao'      => $nome_embarcacao,
                    ':numero_inscricao'     => $numero_inscricao,
                    ':indicativo_chamada'   => $indicativo_chamada,
                    ':atividades_servicos'  => $atividades_servicos,
                    ':tipo_embarcacao'      => $tipo_embarcacao,
                    ':ano_construcao'       => $ano_construcao,
                    ':comprimento_m'        => $comprimento_m,
                    ':arqueacao_bruta'      => $arqueacao_bruta,
                    ':tipo_navegacao'       => $tipo_navegacao,
                    ':area_navegacao'       => $area_navegacao,
                    ':fabricante_motor'     => $fabricante_motor,
                    ':potencia_kw'          => $potencia_kw,
                    ':material_casco'       => $material_casco,
                    ':autorizado_carga'     => $autorizado_carga,
                    ':qtd_passageiros'      => $qtd_passageiros,
                    ':obs_passageiros'      => $obs_passageiros,
                    ':emitente'             => $emitente,
                    ':normam_aplicavel'     => $normam_aplicavel,
                    ':tipo_vistoria_certificado' => $tipo_vistoria_certificado,
                    ':observacoes_verso'    => $observacoes_verso,
                    ':relatorio_numero'     => $relatorio_numero,
                    ':data_vistoria_seco'   => $data_vistoria_seco,
                    ':data_vistoria_flutuando' => $data_vistoria_flutuando,
                    ':local_vistoria'       => $local_vistoria,
                    ':acessibilidade_sim'   => $acessibilidade_sim,
                    ':acessibilidade_nao'   => $acessibilidade_nao,
                    ':data_emissao'         => $data_emissao,
                    ':data_validade'        => $data_validade,
                    ':local_emissao'        => $local_emissao,
                    ':assinante_nome'       => $assinante_nome,
                    ':assinante_titulo'     => $assinante_titulo,
                    ':assinante_registro'   => $assinante_registro,
                    ':status'               => $status,
                    ':vistoria_id'          => $vistoria_id,
                    ':despachante_id'       => $despachante_id,
                    ':id'                   => $id,
                ]);

                // Deletar registros antigos e reinserir
                $stmt_del1 = $pdo->prepare("DELETE FROM csn_distribuicao_passageiros WHERE certificado_id = :cert_id");
                $stmt_del1->execute([':cert_id' => $id]);

                $stmt_del2 = $pdo->prepare("DELETE FROM csn_convalidacoes WHERE certificado_id = :cert_id");
                $stmt_del2->execute([':cert_id' => $id]);
            }

        } else {
            // INSERIR - Gerar número e token
            $ano = date('y');
            $ano4 = date('Y');
            $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_csn WHERE YEAR(criado_em) = :ano");
            $stmt_num->execute([':ano' => $ano4]);
            $total = $stmt_num->fetch()['total'];
            $seq = $total + 1;
            $numero = "AM-CSN-{$seq}/{$ano}";
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO certificados_csn (
                        id, numero, tipo, token_assinatura,
                        nome_embarcacao, numero_inscricao, indicativo_chamada,
                        atividades_servicos, tipo_embarcacao, ano_construcao,
                        comprimento_m, arqueacao_bruta, tipo_navegacao, area_navegacao,
                        fabricante_motor, potencia_kw, material_casco,
                        autorizado_carga, qtd_passageiros, obs_passageiros,
                        emitente, normam_aplicavel, tipo_vistoria_certificado, observacoes_verso,
                        relatorio_numero, data_vistoria_seco, data_vistoria_flutuando,
                        local_vistoria, acessibilidade_sim, acessibilidade_nao,
                        data_emissao, data_validade, local_emissao,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, criado_por, vistoria_id, despachante_id) VALUES (
                        :id, :numero, :tipo, :token_assinatura,
                        :nome_embarcacao, :numero_inscricao, :indicativo_chamada,
                        :atividades_servicos, :tipo_embarcacao, :ano_construcao,
                        :comprimento_m, :arqueacao_bruta, :tipo_navegacao, :area_navegacao,
                        :fabricante_motor, :potencia_kw, :material_casco,
                        :autorizado_carga, :qtd_passageiros, :obs_passageiros,
                        :emitente, :normam_aplicavel, :tipo_vistoria_certificado, :observacoes_verso,
                        :relatorio_numero, :data_vistoria_seco, :data_vistoria_flutuando,
                        :local_vistoria, :acessibilidade_sim, :acessibilidade_nao,
                        :data_emissao, :data_validade, :local_emissao,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        :status, :criado_por, :vistoria_id, :despachante_id)";

            $id = gerarUUID();

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'                   => $id,
                ':numero'               => $numero,
                ':tipo'                 => $tipo,
                ':token_assinatura'     => $token,
                ':nome_embarcacao'      => $nome_embarcacao,
                ':numero_inscricao'     => $numero_inscricao,
                ':indicativo_chamada'   => $indicativo_chamada,
                ':atividades_servicos'  => $atividades_servicos,
                ':tipo_embarcacao'      => $tipo_embarcacao,
                ':ano_construcao'       => $ano_construcao,
                ':comprimento_m'        => $comprimento_m,
                ':arqueacao_bruta'      => $arqueacao_bruta,
                ':tipo_navegacao'       => $tipo_navegacao,
                ':area_navegacao'       => $area_navegacao,
                ':fabricante_motor'     => $fabricante_motor,
                ':potencia_kw'          => $potencia_kw,
                ':material_casco'       => $material_casco,
                ':autorizado_carga'     => $autorizado_carga,
                ':qtd_passageiros'      => $qtd_passageiros,
                ':obs_passageiros'      => $obs_passageiros,
                ':emitente'             => $emitente,
                ':normam_aplicavel'     => $normam_aplicavel,
                ':tipo_vistoria_certificado' => $tipo_vistoria_certificado,
                ':observacoes_verso'    => $observacoes_verso,
                ':relatorio_numero'     => $relatorio_numero,
                ':data_vistoria_seco'   => $data_vistoria_seco,
                ':data_vistoria_flutuando' => $data_vistoria_flutuando,
                ':local_vistoria'       => $local_vistoria,
                ':acessibilidade_sim'   => $acessibilidade_sim,
                ':acessibilidade_nao'   => $acessibilidade_nao,
                ':data_emissao'         => $data_emissao,
                ':data_validade'        => $data_validade,
                ':local_emissao'        => $local_emissao,
                ':assinante_nome'       => $assinante_nome,
                ':assinante_titulo'     => $assinante_titulo,
                ':assinante_registro'   => $assinante_registro,
                ':status'               => $status,
                ':criado_por'           => $_SESSION['usuario_id'] ?? null,
                ':vistoria_id'          => $vistoria_id,
                ':despachante_id'       => $despachante_id,
            ]);
        }

        if (!$ja_assinado) {
            // Salvar distribuição de passageiros
            $passageiros_local = $_POST['passageiro_local'] ?? [];
            $passageiros_qtd   = $_POST['passageiro_qtd'] ?? [];
            $passageiros_codigo = $_POST['passageiro_codigo'] ?? [];
            $passageiros_conves_principal = $_POST['passageiro_conves_principal'] ?? [];
            $passageiros_conves_superior = $_POST['passageiro_conves_superior'] ?? [];
            $passageiros_area_lazer = $_POST['passageiro_area_lazer'] ?? [];
            $passageiros_unidade = $_POST['passageiro_unidade'] ?? [];

            $stmt_dist = $pdo->prepare("INSERT INTO csn_distribuicao_passageiros 
                                        (id, certificado_id, item_codigo, local_nome, quantidade, conves_principal, conves_superior, area_lazer, unidade) 
                                        VALUES (:id, :cert_id, :codigo, :local, :qtd, :conves_principal, :conves_superior, :area_lazer, :unidade)");

            for ($i = 0; $i < count($passageiros_local); $i++) {
                $local = trim($passageiros_local[$i] ?? '');
                $qtd   = (int)($passageiros_qtd[$i] ?? 0);
                $principal = trim($passageiros_conves_principal[$i] ?? '');
                $superior = trim($passageiros_conves_superior[$i] ?? '');
                $lazer = trim($passageiros_area_lazer[$i] ?? '');
                if (!empty($local) || $qtd > 0 || $principal !== '' || $superior !== '' || $lazer !== '') {
                    $stmt_dist->execute([
                        ':id'      => gerarUUID(),
                        ':cert_id' => $id,
                        ':codigo'  => trim($passageiros_codigo[$i] ?? ''),
                        ':local'   => $local,
                        ':qtd'     => $qtd,
                        ':conves_principal' => $principal,
                        ':conves_superior' => $superior,
                        ':area_lazer' => $lazer,
                        ':unidade' => trim($passageiros_unidade[$i] ?? ''),
                    ]);
                }
            }
        }

        // Salvar convalidações
        $conv_numero      = $_POST['conv_numero'] ?? [];
        $conv_data_inicio = $_POST['conv_data_inicio'] ?? [];
        $conv_data_fim    = $_POST['conv_data_fim'] ?? [];
        $conv_local       = $_POST['conv_local'] ?? [];
        $conv_realizacao  = $_POST['conv_data_realizacao'] ?? [];
        $conv_vistoriador = $_POST['conv_vistoriador_id'] ?? [];
        $stmt_nome_vistoriador = $pdo->prepare("SELECT nome FROM usuarios WHERE id=:id AND cargo='VISTORIADOR'");

        $stmt_conv = $pdo->prepare("INSERT INTO csn_convalidacoes 
                                    (id, certificado_id, numero_vistoria, data_inicio, data_fim, local_data, vistoriador) 
                                    VALUES (:id, :cert_id, :numero, :data_inicio, :data_fim, :local_data, :vistoriador)");

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
                ':id'          => gerarUUID(),
                ':cert_id'     => $id,
                ':numero'      => $numero,
                ':data_inicio' => $dt_inicio,
                ':data_fim'    => $dt_fim,
                ':local_data'  => $local_dt,
                ':vistoriador' => $vist,
            ]);
        }

        if ($ja_assinado) {
            // Se já estiver assinado, regenerar o PDF para incluir as convalidações atualizadas!
            $dir_ano = date('Y', strtotime($cert_existente['criado_em']));
            $nome_arquivo_pdf = 'CSN_' . str_replace('/', '-', $cert_existente['numero']) . '.pdf';
            $caminho_relativo = 'storage/certificados/' . $dir_ano . '/csn/' . $nome_arquivo_pdf;
            $salvar_pdf_caminho = __DIR__ . '/../../../' . $caminho_relativo;
            
            $dir_pdf = dirname($salvar_pdf_caminho);
            if (!is_dir($dir_pdf)) {
                mkdir($dir_pdf, 0777, true);
            }

            // Variáveis para o pdf.php
            $_GET['id'] = $id;
            
            // Fazer include para gerar o PDF
            ob_start();
            require __DIR__ . '/pdf.php';
            ob_end_clean();

            // Salvar hash atualizado no banco
            if (file_exists($salvar_pdf_caminho)) {
                $hash_pdf = hash_file('sha256', $salvar_pdf_caminho);
                $stmt_pdf = $pdo->prepare("UPDATE certificados_csn SET hash_arquivo_pdf = :hash WHERE id = :id");
                $stmt_pdf->execute([
                    ':hash' => $hash_pdf,
                    ':id'   => $id
                ]);
            }
        }

        $pdo->commit();

        // Log de atividade
        log_atividade($editando ? 'certificado_csn_editado' : 'certificado_csn_criado', 
                      "Certificado " . ($ja_assinado ? $cert_existente['numero'] : $numero) . " - " . ($ja_assinado ? '' : $nome_embarcacao));

        setMensagem('success', 'Certificado CSN ' . ($editando ? 'atualizado' : 'criado') . ' com sucesso.');
        redirecionar(APP_URL . 'documentacao/certificados');

    } catch (Exception $e) {
        $pdo->rollBack();
        setMensagem('error', 'Erro ao salvar certificado: ' . $e->getMessage());
        redirecionar(APP_URL . 'documentacao/certificados/form' . ($editando ? "?id={$id}" : ''));
    }
}

// ============================================
// ENVIAR LINK DE ASSINATURA POR E-MAIL
// ============================================
if ($action === 'enviar_assinatura') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    require_once __DIR__ . '/../../../includes/assinaturas_usuarios.php';
    try { $resultado = assinaturaEnviarConviteCertificado($pdo, 'CSN', $id); }
    catch (Throwable $e) { $resultado = ['success'=>false,'message'=>$e->getMessage()]; }

    if ($resultado['success']) {
        log_atividade('certificado_csn_assinatura_enviada', "Link de assinatura CSN ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/certificados');
}

// ============================================
// ENVIAR CERTIFICADO POR E-MAIL
// ============================================
if ($action === 'enviar_certificado') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        setMensagem('error', 'ID do certificado não informado.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    require_once __DIR__ . '/../../../includes/enviar_certificado.php';

    $resultado = enviarCertificadoEmail(
        $pdo,
        $id,
        'certificados_csn',
        'CSN',
        'documentacao/certificados/pdf'
    );

    if ($resultado['success']) {
        log_atividade('certificado_csn_enviado_email', "Certificado CSN ID: {$id} enviado por e-mail.");
        setMensagem('success', $resultado['message']);
    } else {
        setMensagem('error', $resultado['message']);
    }

    redirecionar(APP_URL . 'documentacao/certificados');
}

// Ação inválida
setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/certificados');
