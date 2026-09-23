<?php
/**
 * MÓDULO: Documentação > Notas de Arqueação (AM-NAR)
 * Actions: Salvar, Emitir, Excluir e Cancelar
 * Numeração oficial: AM-NAR: 0001/2026
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SALVAR NOTA DE ARQUEAÇÃO (NAR)
// ============================================
if ($action === 'salvar') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'documentacao/nar');
    }

    $id = trim($_POST['id'] ?? '');
    $editando = !empty($id);
    bloquearEdicaoDocumentoAssinado($pdo, 'certificados_nar', $id, APP_URL . 'documentacao/nar');

    $narExistente = null;
    if ($editando) {
        $stmtNar = $pdo->prepare('SELECT * FROM certificados_nar WHERE id = :id AND ativo = 1');
        $stmtNar->execute([':id' => $id]);
        $narExistente = $stmtNar->fetch(PDO::FETCH_ASSOC);
        if (!$narExistente) {
            setMensagem('error', 'Nota de Arqueação não encontrada.');
            redirecionar(APP_URL . 'documentacao/nar');
        }
    }

    $embarcacao_id = trim($_POST['embarcacao_id'] ?? '');
    if (empty($embarcacao_id)) {
        setMensagem('error', 'Selecione uma embarcação.');
        redirecionar(APP_URL . 'documentacao/nar/form' . ($editando ? '?id=' . urlencode($id) : ''));
    }

    $cliente_id = trim($_POST['cliente_id'] ?? '') ?: null;
    $analise_id = trim($_POST['analise_id'] ?? '') ?: null;
    $enquadramento_comprimento = trim($_POST['enquadramento_comprimento'] ?? 'L_MAIOR_IGUAL_24');
    if (!in_array($enquadramento_comprimento, ['L_MAIOR_IGUAL_24', 'L_MENOR_24'], true)) {
        $enquadramento_comprimento = 'L_MAIOR_IGUAL_24';
    }

    // Características Gerais
    $nome_embarcacao       = trim($_POST['nome_embarcacao'] ?? '');
    $armador               = trim($_POST['armador'] ?? '');
    $construtor            = trim($_POST['construtor'] ?? '');
    $numero_casco          = trim($_POST['numero_casco'] ?? '');
    $material_casco        = trim($_POST['material_casco'] ?? '');
    $tipo_embarcacao       = trim($_POST['tipo_embarcacao'] ?? '');
    $atividade_servico     = trim($_POST['atividade_servico'] ?? '');
    $classificacao         = trim($_POST['classificacao'] ?? '');
    $porto_inscricao       = trim($_POST['porto_inscricao'] ?? '');
    $data_construcao_quilha = trim($_POST['data_construcao_quilha'] ?? '');

    // Dimensões do Casco
    $toDecimal = fn($val) => ($val !== '' && $val !== null && is_numeric(str_replace(',', '.', (string)$val))) ? (float)str_replace(',', '.', (string)$val) : null;
    $toInt = fn($val) => ($val !== '' && $val !== null && is_numeric($val)) ? (int)$val : 0;

    $comprimento_total_ct   = $toDecimal($_POST['comprimento_total_ct'] ?? null);
    $comprimento_regra_l    = $toDecimal($_POST['comprimento_regra_l'] ?? null);
    $comprimento_lpp        = $toDecimal($_POST['comprimento_lpp'] ?? null);
    $boca_moldada_b         = $toDecimal($_POST['boca_moldada_b'] ?? null);
    $pontal_moldado_p       = $toDecimal($_POST['pontal_moldado_p'] ?? null);

    $calado_leve_av         = $toDecimal($_POST['calado_leve_av'] ?? null);
    $calado_leve_ar         = $toDecimal($_POST['calado_leve_ar'] ?? null);
    $calado_leve_medio      = $toDecimal($_POST['calado_leve_medio'] ?? null);
    $calado_carregado_av    = $toDecimal($_POST['calado_carregado_av'] ?? null);
    $calado_carregado_ar    = $toDecimal($_POST['calado_carregado_ar'] ?? null);
    $calado_carregado_medio = $toDecimal($_POST['calado_carregado_medio'] ?? null);

    // Tripulantes e Passageiros
    $numero_tripulantes     = $toInt($_POST['numero_tripulantes'] ?? 0);
    $n1_passageiros_camarotes = $toInt($_POST['n1_passageiros_camarotes'] ?? 0);
    $n2_demais_passageiros  = $toInt($_POST['n2_demais_passageiros'] ?? 0);

    // Características Calculadas
    $deslocamento_carregado = $toDecimal($_POST['deslocamento_carregado'] ?? null);
    $deslocamento_leve      = $toDecimal($_POST['deslocamento_leve'] ?? null);
    $porte_bruto            = $toDecimal($_POST['porte_bruto'] ?? null);

    $espacos_fechados_abaixo_conves = $toDecimal($_POST['espacos_fechados_abaixo_conves'] ?? 0) ?? 0.0;
    $espacos_fechados_acima_conves  = $toDecimal($_POST['espacos_fechados_acima_conves'] ?? 0) ?? 0.0;
    $espacos_excluidos              = $toDecimal($_POST['espacos_excluidos'] ?? 0) ?? 0.0;
    $volume_espacos_carga_vc        = $toDecimal($_POST['volume_espacos_carga_vc'] ?? 0) ?? 0.0;

    // Cálculo ou ajuste de Volumes, K1, K2, AB e AL
    $volume_total_fechado_v = $espacos_fechados_abaixo_conves + $espacos_fechados_acima_conves;

    $coeficiente_k1 = $volume_total_fechado_v > 0 ? (0.2 + 0.02 * log10($volume_total_fechado_v)) : 0.0;
    $coeficiente_k1 = round($coeficiente_k1, 4);

    $arqueacao_bruta_ab = $toInt($_POST['arqueacao_bruta_ab'] ?? null);
    if ($arqueacao_bruta_ab <= 0 && $volume_total_fechado_v > 0) {
        $arqueacao_bruta_ab = (int)floor($coeficiente_k1 * $volume_total_fechado_v);
    }

    $coeficiente_k2 = $volume_espacos_carga_vc > 0 ? (0.2 + 0.02 * log10($volume_espacos_carga_vc)) : 0.0;
    $coeficiente_k2 = round($coeficiente_k2, 4);

    $arqueacao_liquida_al = $toInt($_POST['arqueacao_liquida_al'] ?? null);
    if ($arqueacao_liquida_al <= 0 && $arqueacao_bruta_ab > 0) {
        // Cálculo padrão NORMAM
        $nTotal = $n1_passageiros_camarotes + $n2_demais_passageiros;
        $fatorN = ($nTotal >= 13) ? (1.25 * ($arqueacao_bruta_ab + 10000) / 10000 * ($n1_passageiros_camarotes + ($n2_demais_passageiros / 10.0))) : 0.0;
        
        $caladoCalc = $calado_carregado_medio ?? $calado_leve_medio ?? 1.0;
        $pontalCalc = $pontal_moldado_p > 0 ? $pontal_moldado_p : 1.0;
        $exp1 = pow((4 * $caladoCalc) / (3 * $pontalCalc), 2);
        $exp1Usar = $exp1 > 1.0 ? 1.0 : $exp1;

        $termoCarga = $coeficiente_k2 * $volume_espacos_carga_vc * $exp1Usar;
        if ($termoCarga <= (0.25 * $arqueacao_bruta_ab)) {
            $termoCarga = 0.25 * $arqueacao_bruta_ab;
        }

        $alCalculada = $termoCarga + $fatorN;
        $alMinima = 0.30 * $arqueacao_bruta_ab;
        $arqueacao_liquida_al = (int)ceil(max($alCalculada, $alMinima));
    }

    // Arrays de Detalhamento dos Volumes (JSON)
    $volumesAbaixoArr = $_POST['volumes_abaixo'] ?? [];
    $volumesAcimaArr  = $_POST['volumes_acima'] ?? [];
    $volumesExcluidosArr = $_POST['volumes_excluidos'] ?? [];
    $volumesCargaArr  = $_POST['volumes_carga'] ?? [];

    $volumes_abaixo_conves_json = is_array($volumesAbaixoArr) ? json_encode(array_values($volumesAbaixoArr), JSON_UNESCAPED_UNICODE) : null;
    $volumes_acima_conves_json  = is_array($volumesAcimaArr) ? json_encode(array_values($volumesAcimaArr), JSON_UNESCAPED_UNICODE) : null;
    $volumes_excluidos_json     = is_array($volumesExcluidosArr) ? json_encode(array_values($volumesExcluidosArr), JSON_UNESCAPED_UNICODE) : null;
    $volumes_carga_json         = is_array($volumesCargaArr) ? json_encode(array_values($volumesCargaArr), JSON_UNESCAPED_UNICODE) : null;

    $metodo_obtencao_abaixo = trim($_POST['metodo_obtencao_abaixo'] ?? '') ?: 'Volume obtido com a utilização de curvas hidrostáticas.';
    $metodo_obtencao_acima  = trim($_POST['metodo_obtencao_acima'] ?? '') ?: 'Volume obtido com a utilização de formas geométricas.';

    $observacoes_notas = trim($_POST['observacoes_notas'] ?? '');

    // Responsável Técnico
    $responsavel_assinatura_id = !empty($_POST['responsavel_assinatura_id']) ? (int)$_POST['responsavel_assinatura_id'] : null;
    $assinante_nome = trim($_POST['assinante_nome'] ?? '');
    $assinante_titulo = trim($_POST['assinante_titulo'] ?? '');
    $assinante_registro = trim($_POST['assinante_registro'] ?? '');

    if ($responsavel_assinatura_id) {
        $stmtResp = $pdo->prepare("SELECT nome_completo, cargo_titulo, registro_profissional FROM responsaveis_assinatura WHERE id = :id");
        $stmtResp->execute([':id' => $responsavel_assinatura_id]);
        $respRow = $stmtResp->fetch(PDO::FETCH_ASSOC);
        if ($respRow) {
            $assinante_nome = $respRow['nome_completo'];
            $assinante_titulo = $respRow['cargo_titulo'];
            $assinante_registro = $respRow['registro_profissional'];
        }
    }

    $local_emissao = trim($_POST['local_emissao'] ?? 'Belém - PA') ?: 'Belém - PA';
    $data_emissao  = trim($_POST['data_emissao'] ?? date('Y-m-d')) ?: date('Y-m-d');
    $status = trim($_POST['status'] ?? 'emitido');
    if (!in_array($status, ['rascunho', 'emitido', 'assinado', 'cancelado'], true)) {
        $status = 'emitido';
    }

    $usuarioId = (string)($_SESSION['usuario_id'] ?? '');

    $pdo->beginTransaction();
    try {
        if ($editando) {
            $sql = "UPDATE certificados_nar SET
                enquadramento_comprimento = :enquadramento,
                embarcacao_id = :embarcacao,
                cliente_id = :cliente,
                analise_id = :analise,
                status = :status,
                nome_embarcacao = :nome_emb,
                armador = :armador,
                construtor = :construtor,
                numero_casco = :num_casco,
                material_casco = :mat_casco,
                tipo_embarcacao = :tipo_emb,
                atividade_servico = :atv_servico,
                classificacao = :classificacao,
                porto_inscricao = :porto_inscricao,
                data_construcao_quilha = :data_const,
                comprimento_total_ct = :ct,
                comprimento_regra_l = :l,
                comprimento_lpp = :lpp,
                boca_moldada_b = :b,
                pontal_moldado_p = :p,
                calado_leve_av = :cl_av,
                calado_leve_ar = :cl_ar,
                calado_leve_medio = :cl_med,
                calado_carregado_av = :cc_av,
                calado_carregado_ar = :cc_ar,
                calado_carregado_medio = :cc_med,
                numero_tripulantes = :trip,
                n1_passageiros_camarotes = :n1,
                n2_demais_passageiros = :n2,
                deslocamento_carregado = :desl_carr,
                deslocamento_leve = :desl_leve,
                porte_bruto = :tp_bruto,
                espacos_fechados_abaixo_conves = :esp_abaixo,
                espacos_fechados_acima_conves = :esp_acima,
                espacos_excluidos = :esp_excluidos,
                volume_total_fechado_v = :vol_tot,
                volume_espacos_carga_vc = :vol_carga,
                coeficiente_k1 = :k1,
                coeficiente_k2 = :k2,
                arqueacao_bruta_ab = :ab,
                arqueacao_liquida_al = :al,
                volumes_abaixo_conves_json = :v_abaixo_json,
                volumes_acima_conves_json = :v_acima_json,
                volumes_excluidos_json = :v_excl_json,
                volumes_carga_json = :v_carga_json,
                metodo_obtencao_abaixo = :met_abaixo,
                metodo_obtencao_acima = :met_acima,
                observacoes_notas = :obs_notas,
                responsavel_assinatura_id = :resp_id,
                assinante_nome = :ass_nome,
                assinante_titulo = :ass_titulo,
                assinante_registro = :ass_reg,
                local_emissao = :loc_emissao,
                data_emissao = :dt_emissao
            WHERE id = :id AND ativo = 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':enquadramento' => $enquadramento_comprimento,
                ':embarcacao' => $embarcacao_id,
                ':cliente' => $cliente_id,
                ':analise' => $analise_id,
                ':status' => $status,
                ':nome_emb' => $nome_embarcacao,
                ':armador' => $armador,
                ':construtor' => $construtor,
                ':num_casco' => $numero_casco,
                ':mat_casco' => $material_casco,
                ':tipo_emb' => $tipo_embarcacao,
                ':atv_servico' => $atividade_servico,
                ':classificacao' => $classificacao,
                ':porto_inscricao' => $porto_inscricao,
                ':data_const' => $data_construcao_quilha,
                ':ct' => $comprimento_total_ct,
                ':l' => $comprimento_regra_l,
                ':lpp' => $comprimento_lpp,
                ':b' => $boca_moldada_b,
                ':p' => $pontal_moldado_p,
                ':cl_av' => $calado_leve_av,
                ':cl_ar' => $calado_leve_ar,
                ':cl_med' => $calado_leve_medio,
                ':cc_av' => $calado_carregado_av,
                ':cc_ar' => $calado_carregado_ar,
                ':cc_med' => $calado_carregado_medio,
                ':trip' => $numero_tripulantes,
                ':n1' => $n1_passageiros_camarotes,
                ':n2' => $n2_demais_passageiros,
                ':desl_carr' => $deslocamento_carregado,
                ':desl_leve' => $deslocamento_leve,
                ':tp_bruto' => $porte_bruto,
                ':esp_abaixo' => $espacos_fechados_abaixo_conves,
                ':esp_acima' => $espacos_fechados_acima_conves,
                ':esp_excluidos' => $espacos_excluidos,
                ':vol_tot' => $volume_total_fechado_v,
                ':vol_carga' => $volume_espacos_carga_vc,
                ':k1' => $coeficiente_k1,
                ':k2' => $coeficiente_k2,
                ':ab' => $arqueacao_bruta_ab,
                ':al' => $arqueacao_liquida_al,
                ':v_abaixo_json' => $volumes_abaixo_conves_json,
                ':v_acima_json' => $volumes_acima_conves_json,
                ':v_excl_json' => $volumes_excluidos_json,
                ':v_carga_json' => $volumes_carga_json,
                ':met_abaixo' => $metodo_obtencao_abaixo,
                ':met_acima' => $metodo_obtencao_acima,
                ':obs_notas' => $observacoes_notas,
                ':resp_id' => $responsavel_assinatura_id,
                ':ass_nome' => $assinante_nome,
                ':ass_titulo' => $assinante_titulo,
                ':ass_reg' => $assinante_registro,
                ':loc_emissao' => $local_emissao,
                ':dt_emissao' => $data_emissao,
                ':id' => $id,
            ]);

            $narId = $id;
            setMensagem('success', 'Nota de Arqueação (NAR) atualizada com sucesso.');
        } else {
            // Gerar Sequencial e Número Oficial
            $ano = (int)date('Y', strtotime($data_emissao));
            $stmtSeq = $pdo->prepare("SELECT COALESCE(MAX(sequencial), 0) + 1 FROM certificados_nar WHERE ano = :ano FOR UPDATE");
            $stmtSeq->execute([':ano' => $ano]);
            $sequencial = (int)$stmtSeq->fetchColumn();

            $numeroOficial = sprintf('AM-NAR: %04d/%d', $sequencial, $ano);
            $tokenAssinatura = hash('sha256', uniqid('nar_' . $numeroOficial, true) . random_bytes(16));
            $narId = gerarUUID();

            $sql = "INSERT INTO certificados_nar (
                id, numero, ano, sequencial, enquadramento_comprimento, embarcacao_id, cliente_id, analise_id,
                token_assinatura, status, nome_embarcacao, armador, construtor, numero_casco, material_casco,
                tipo_embarcacao, atividade_servico, classificacao, porto_inscricao, data_construcao_quilha,
                comprimento_total_ct, comprimento_regra_l, comprimento_lpp, boca_moldada_b, pontal_moldado_p,
                calado_leve_av, calado_leve_ar, calado_leve_medio, calado_carregado_av, calado_carregado_ar, calado_carregado_medio,
                numero_tripulantes, n1_passageiros_camarotes, n2_demais_passageiros,
                deslocamento_carregado, deslocamento_leve, porte_bruto,
                espacos_fechados_abaixo_conves, espacos_fechados_acima_conves, espacos_excluidos,
                volume_total_fechado_v, volume_espacos_carga_vc, coeficiente_k1, coeficiente_k2,
                arqueacao_bruta_ab, arqueacao_liquida_al,
                volumes_abaixo_conves_json, volumes_acima_conves_json, volumes_excluidos_json, volumes_carga_json,
                metodo_obtencao_abaixo, metodo_obtencao_acima, observacoes_notas,
                responsavel_assinatura_id, assinante_nome, assinante_titulo, assinante_registro,
                local_emissao, data_emissao, criado_por
            ) VALUES (
                :id, :numero, :ano, :sequencial, :enquadramento, :embarcacao, :cliente, :analise,
                :token, :status, :nome_emb, :armador, :construtor, :num_casco, :mat_casco,
                :tipo_emb, :atv_servico, :classificacao, :porto_inscricao, :data_const,
                :ct, :l, :lpp, :b, :p,
                :cl_av, :cl_ar, :cl_med, :cc_av, :cc_ar, :cc_med,
                :trip, :n1, :n2,
                :desl_carr, :desl_leve, :tp_bruto,
                :esp_abaixo, :esp_acima, :esp_excluidos,
                :vol_tot, :vol_carga, :k1, :k2,
                :ab, :al,
                :v_abaixo_json, :v_acima_json, :v_excl_json, :v_carga_json,
                :met_abaixo, :met_acima, :obs_notas,
                :resp_id, :ass_nome, :ass_titulo, :ass_reg,
                :loc_emissao, :dt_emissao, :criado_por
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $narId,
                ':numero' => $numeroOficial,
                ':ano' => $ano,
                ':sequencial' => $sequencial,
                ':enquadramento' => $enquadramento_comprimento,
                ':embarcacao' => $embarcacao_id,
                ':cliente' => $cliente_id,
                ':analise' => $analise_id,
                ':token' => $tokenAssinatura,
                ':status' => $status,
                ':nome_emb' => $nome_embarcacao,
                ':armador' => $armador,
                ':construtor' => $construtor,
                ':num_casco' => $numero_casco,
                ':mat_casco' => $material_casco,
                ':tipo_emb' => $tipo_embarcacao,
                ':atv_servico' => $atividade_servico,
                ':classificacao' => $classificacao,
                ':porto_inscricao' => $porto_inscricao,
                ':data_const' => $data_construcao_quilha,
                ':ct' => $comprimento_total_ct,
                ':l' => $comprimento_regra_l,
                ':lpp' => $comprimento_lpp,
                ':b' => $boca_moldada_b,
                ':p' => $pontal_moldado_p,
                ':cl_av' => $calado_leve_av,
                ':cl_ar' => $calado_leve_ar,
                ':cl_med' => $calado_leve_medio,
                ':cc_av' => $calado_carregado_av,
                ':cc_ar' => $calado_carregado_ar,
                ':cc_med' => $calado_carregado_medio,
                ':trip' => $numero_tripulantes,
                ':n1' => $n1_passageiros_camarotes,
                ':n2' => $n2_demais_passageiros,
                ':desl_carr' => $deslocamento_carregado,
                ':desl_leve' => $deslocamento_leve,
                ':tp_bruto' => $porte_bruto,
                ':esp_abaixo' => $espacos_fechados_abaixo_conves,
                ':esp_acima' => $espacos_fechados_acima_conves,
                ':esp_excluidos' => $espacos_excluidos,
                ':vol_tot' => $volume_total_fechado_v,
                ':vol_carga' => $volume_espacos_carga_vc,
                ':k1' => $coeficiente_k1,
                ':k2' => $coeficiente_k2,
                ':ab' => $arqueacao_bruta_ab,
                ':al' => $arqueacao_liquida_al,
                ':v_abaixo_json' => $volumes_abaixo_conves_json,
                ':v_acima_json' => $volumes_acima_conves_json,
                ':v_excl_json' => $volumes_excluidos_json,
                ':v_carga_json' => $volumes_carga_json,
                ':met_abaixo' => $metodo_obtencao_abaixo,
                ':met_acima' => $metodo_obtencao_acima,
                ':obs_notas' => $observacoes_notas,
                ':resp_id' => $responsavel_assinatura_id,
                ':ass_nome' => $assinante_nome,
                ':ass_titulo' => $assinante_titulo,
                ':ass_reg' => $assinante_registro,
                ':loc_emissao' => $local_emissao,
                ':dt_emissao' => $data_emissao,
                ':criado_por' => $usuarioId ?: null,
            ]);

            if (!empty($analise_id)) {
                analisePlanosHistorico($pdo, $analise_id, 'NAR_EMITIDA', 'EM_ANALISE', 'EM_ANALISE', "Nota de Arqueação {$numeroOficial} gerada e vinculada à análise.");
            }

            setMensagem('success', "Nota de Arqueação {$numeroOficial} gerada com sucesso!");
        }

        $pdo->commit();
        redirecionar(APP_URL . 'documentacao/nar/form?id=' . urlencode($narId));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erro ao salvar NAR: ' . $e->getMessage());
        setMensagem('error', 'Erro ao salvar Nota de Arqueação: ' . $e->getMessage());
        redirecionar(APP_URL . 'documentacao/nar' . ($editando ? '/form?id=' . urlencode($id) : ''));
    }
}

// ============================================
// EXCLUIR / CANCELAR NOTA DE ARQUEAÇÃO
// ============================================
if ($action === 'excluir') {
    $id = trim($_POST['id'] ?? $_GET['id'] ?? '');
    bloquearEdicaoDocumentoAssinado($pdo, 'certificados_nar', $id, APP_URL . 'documentacao/nar');

    $stmt = $pdo->prepare("UPDATE certificados_nar SET ativo = 0 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    setMensagem('success', 'Nota de Arqueação removida com sucesso.');
    redirecionar(APP_URL . 'documentacao/nar');
}

setMensagem('error', 'Ação inválida.');
redirecionar(APP_URL . 'documentacao/nar');
