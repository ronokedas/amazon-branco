<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: actions.php - Processar acoes (salvar, desativar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $tipo_embarcacao_id = trim($_POST['tipo_embarcacao_id'] ?? '');
        $ano = trim($_POST['ano'] ?? '');
        $porto_inscricao = trim($_POST['porto_inscricao'] ?? '');
        $numero_inscricao = trim($_POST['numero_inscricao'] ?? '');
        $indicativo_chamada = trim($_POST['indicativo_chamada'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Tab Tecnicos
        $possui_propulsao = isset($_POST['possui_propulsao']) && $_POST['possui_propulsao'] !== '' ? (int)$_POST['possui_propulsao'] : null;
        $fabricante_motor = trim($_POST['fabricante_motor'] ?? '');
        $modelo_motor = trim($_POST['modelo_motor'] ?? '');
        $numero_motor = trim($_POST['numero_motor'] ?? '');
        $potencia_kw = trim($_POST['potencia_kw'] ?? '');
        $material_casco = trim($_POST['material_casco'] ?? '');
        $tipo_navegacao = trim($_POST['tipo_navegacao'] ?? '');
        $area_navegacao = trim($_POST['area_navegacao'] ?? '');
        $tipo_servico = trim($_POST['tipo_servico'] ?? '');
        $autorizado_carga = isset($_POST['autorizado_carga']) && $_POST['autorizado_carga'] !== '' ? (int)$_POST['autorizado_carga'] : null;
        $numero_tripulantes = trim($_POST['numero_tripulantes'] ?? '');
        $numero_passageiros_n1 = trim($_POST['numero_passageiros_n1'] ?? '');
        $numero_passageiros_n2 = trim($_POST['numero_passageiros_n2'] ?? '');
        $obs_passageiros = trim($_POST['obs_passageiros'] ?? '');
        $acessibilidade = isset($_POST['acessibilidade']) && $_POST['acessibilidade'] !== '' ? (int)$_POST['acessibilidade'] : null;

        // Tab Dimensoes
        $comprimento_total = trim($_POST['comprimento_total'] ?? '');
        $comprimento_casco = trim($_POST['comprimento_casco'] ?? '');
        $comprimento_lpp = trim($_POST['comprimento_lpp'] ?? '');
        $pontal_moldado = trim($_POST['pontal_moldado'] ?? '');
        $boca_moldada = trim($_POST['boca_moldada'] ?? '');
        $boca_maxima = trim($_POST['boca_maxima'] ?? '');
        $arqueacao_bruta = trim($_POST['arqueacao_bruta'] ?? '');
        $arqueacao_liquida = trim($_POST['arqueacao_liquida'] ?? '');
        $metodo_arqueacao = trim($_POST['metodo_arqueacao'] ?? '');
        $cnarq_data_quilha = trim($_POST['cnarq_data_quilha'] ?? '');
        $cnarq_calado_moldado_m = trim($_POST['cnarq_calado_moldado_m'] ?? '');
        $cnarq_espacos_incluidos_ab = trim($_POST['cnarq_espacos_incluidos_ab'] ?? '');
        $cnarq_espacos_incluidos_al = trim($_POST['cnarq_espacos_incluidos_al'] ?? '');
        $cnarq_espacos_excluidos_m3 = trim($_POST['cnarq_espacos_excluidos_m3'] ?? '');
        $cnarq_data_local_arqueacao_original = trim($_POST['cnarq_data_local_arqueacao_original'] ?? '');
        $cnarq_data_local_ultima_rearqueacao = trim($_POST['cnarq_data_local_ultima_rearqueacao'] ?? '');
        $local_construcao = trim($_POST['local_construcao'] ?? '');
        $numero_casco = trim($_POST['numero_casco'] ?? '');
        $porte_bruto = trim($_POST['porte_bruto'] ?? '');
        $estaleiro_nome = trim($_POST['estaleiro_nome'] ?? '');
        $estaleiro_cpf_cnpj = trim($_POST['estaleiro_cpf_cnpj'] ?? '');
        $estaleiro_endereco = trim($_POST['estaleiro_endereco'] ?? '');

        // Tab Borda Livre
        $cnbl_tipo_embarcacao = trim($_POST['cnbl_tipo_embarcacao'] ?? '');
        $cnbl_area_navegacao = trim($_POST['cnbl_area_navegacao'] ?? '');
        $borda_livre_mm = trim($_POST['borda_livre_mm'] ?? '');
        $borda_livre_tipo = trim($_POST['borda_livre_tipo'] ?? '');
        $calado_maximo_m = trim($_POST['calado_maximo_m'] ?? '');
        $aresta_superior_linha_conves = trim($_POST['aresta_superior_linha_conves'] ?? '');
        $centro_disco_situado = trim($_POST['centro_disco_situado'] ?? '');
        $acrescimo_agua_salgada = trim($_POST['acrescimo_agua_salgada'] ?? '');
        $dist_linha_conves_bico_proa = trim($_POST['dist_linha_conves_bico_proa'] ?? '');
        $dist_linha_conves_abaixo_disco = trim($_POST['dist_linha_conves_abaixo_disco'] ?? '');
        $marca_linha_carga_area1 = trim($_POST['marca_linha_carga_area1'] ?? '');
        $marca_linha_carga_area2 = trim($_POST['marca_linha_carga_area2'] ?? '');

        // Validacoes
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome da embarcacao e obrigatorio.';
        } elseif (strlen($nome) < 2) {
            $erros[] = 'O nome deve ter pelo menos 2 caracteres.';
        }

        if (!empty($ano) && ($ano < 1900 || $ano > 2099)) {
            $erros[] = 'O ano deve estar entre 1900 e 2099.';
        }

        foreach ([
            'tripulantes' => $numero_tripulantes,
            'passageiros N1' => $numero_passageiros_n1,
            'passageiros N2' => $numero_passageiros_n2,
        ] as $rotulo => $quantidade) {
            if ($quantidade !== '' && (!ctype_digit($quantidade) || (int)$quantidade < 0)) {
                $erros[] = 'A quantidade de ' . $rotulo . ' deve ser um número inteiro igual ou maior que zero.';
            }
        }

        foreach ([
            'comprimento total' => $comprimento_total,
            'comprimento do casco' => $comprimento_casco,
            'comprimento LPP' => $comprimento_lpp,
            'pontal moldado' => $pontal_moldado,
            'boca moldada' => $boca_moldada,
            'boca máxima' => $boca_maxima,
        ] as $rotulo => $medida) {
            if ($medida !== '' && (!is_numeric($medida) || (float)$medida < 0)) {
                $erros[] = 'O campo ' . $rotulo . ' deve conter um valor igual ou maior que zero.';
            }
        }

        if (!in_array($possui_propulsao, [0, 1], true)) {
            $erros[] = 'A informação se possui propulsão é obrigatória.';
        } elseif ($possui_propulsao === 1) {
            if ($fabricante_motor === '') $erros[] = 'Informe o fabricante do motor.';
            if ($modelo_motor === '') $erros[] = 'Informe o modelo do motor.';
            if ($numero_motor === '') $erros[] = 'Informe o número do motor.';
            if ($potencia_kw === '') $erros[] = 'Informe a potência propulsiva.';
        } else {
            $fabricante_motor = '';
            $modelo_motor = '';
            $numero_motor = '';
            $potencia_kw = '';
        }
        $isEdicao = !empty($id);

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            $url = APP_URL . 'embarcacoes/form';
            if ($isEdicao) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        // Recuperar o nome do tipo para manter a compatibilidade com a coluna string
        $tipo_embarcacao = null;
        if (!empty($tipo_embarcacao_id)) {
            $stmtTipo = $pdo->prepare("SELECT nome FROM tipos_embarcacao WHERE id = :id");
            $stmtTipo->execute([':id' => $tipo_embarcacao_id]);
            if ($rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC)) {
                $tipo_embarcacao = $rowTipo['nome'];
            }
        }

        // Preparar dados
        $dados = [
            ':nome' => $nome,
            ':registro' => null,
            ':tipo_embarcacao_id' => $tipo_embarcacao_id ?: null,
            ':tipo_embarcacao' => $tipo_embarcacao,
            ':cnbl_tipo_embarcacao' => $cnbl_tipo_embarcacao ?: null,
            ':ano' => !empty($ano) ? (int)$ano : null,
            ':porto_inscricao' => $porto_inscricao ?: null,
            ':numero_inscricao' => $numero_inscricao ?: null,
            ':indicativo_chamada' => $indicativo_chamada ?: null,
            ':observacoes' => $observacoes ?: null,
            ':possui_propulsao' => $possui_propulsao,
            ':fabricante_motor' => $fabricante_motor ?: null,
            ':modelo_motor' => $modelo_motor ?: null,
            ':numero_motor' => $numero_motor ?: null,
            ':potencia_kw' => $potencia_kw ?: null,
            ':material_casco' => $material_casco ?: null,
            ':tipo_navegacao' => $tipo_navegacao ?: null,
            ':area_navegacao' => $area_navegacao ?: null,
            ':cnbl_area_navegacao' => $cnbl_area_navegacao ?: null,
            ':tipo_servico' => $tipo_servico ?: null,
            ':autorizado_carga' => $autorizado_carga,
            ':numero_tripulantes' => $numero_tripulantes !== '' ? (int)$numero_tripulantes : null,
            ':numero_passageiros_n1' => $numero_passageiros_n1 !== '' ? (int)$numero_passageiros_n1 : null,
            ':numero_passageiros_n2' => $numero_passageiros_n2 !== '' ? (int)$numero_passageiros_n2 : null,
            ':obs_passageiros' => $obs_passageiros ?: null,
            ':acessibilidade' => $acessibilidade,
            ':comprimento_total' => $comprimento_total !== '' ? (float)$comprimento_total : null,
            ':comprimento_casco' => $comprimento_casco !== '' ? (float)$comprimento_casco : null,
            ':comprimento_lpp' => $comprimento_lpp !== '' ? (float)$comprimento_lpp : null,
            ':pontal_moldado' => $pontal_moldado !== '' ? (float)$pontal_moldado : null,
            ':boca_moldada' => $boca_moldada !== '' ? (float)$boca_moldada : null,
            ':boca_maxima' => $boca_maxima !== '' ? (float)$boca_maxima : null,
            ':arqueacao_bruta' => $arqueacao_bruta ?: null,
            ':arqueacao_liquida' => $arqueacao_liquida !== '' ? (float)$arqueacao_liquida : null,
            ':metodo_arqueacao' => $metodo_arqueacao ?: null,
            ':cnarq_data_quilha' => $cnarq_data_quilha ?: null,
            ':cnarq_calado_moldado_m' => $cnarq_calado_moldado_m !== '' ? (float)$cnarq_calado_moldado_m : null,
            ':cnarq_espacos_incluidos_ab' => $cnarq_espacos_incluidos_ab ?: null,
            ':cnarq_espacos_incluidos_al' => $cnarq_espacos_incluidos_al ?: null,
            ':cnarq_espacos_excluidos_m3' => $cnarq_espacos_excluidos_m3 !== '' ? (float)$cnarq_espacos_excluidos_m3 : null,
            ':cnarq_data_local_arqueacao_original' => $cnarq_data_local_arqueacao_original ?: null,
            ':cnarq_data_local_ultima_rearqueacao' => $cnarq_data_local_ultima_rearqueacao ?: null,
            ':local_construcao' => $local_construcao ?: null,
            ':numero_casco' => $numero_casco ?: null,
            ':porte_bruto' => $porte_bruto !== '' ? (float)$porte_bruto : null,
            ':estaleiro_nome' => $estaleiro_nome ?: null,
            ':estaleiro_cpf_cnpj' => $estaleiro_cpf_cnpj ?: null,
            ':estaleiro_endereco' => $estaleiro_endereco ?: null,
            ':borda_livre_mm' => $borda_livre_mm !== '' ? (int)$borda_livre_mm : null,
            ':borda_livre_tipo' => $borda_livre_tipo ?: null,
            ':calado_maximo_m' => $calado_maximo_m !== '' ? (float)$calado_maximo_m : null,
            ':aresta_superior_linha_conves' => $aresta_superior_linha_conves ?: null,
            ':centro_disco_situado' => $centro_disco_situado ?: null,
            ':acrescimo_agua_salgada' => $acrescimo_agua_salgada ?: null,
            ':dist_linha_conves_bico_proa' => $dist_linha_conves_bico_proa ?: null,
            ':dist_linha_conves_abaixo_disco' => $dist_linha_conves_abaixo_disco ?: null,
            ':marca_linha_carga_area1' => $marca_linha_carga_area1 ?: null,
            ':marca_linha_carga_area2' => $marca_linha_carga_area2 ?: null,
        ];

        try {
            if ($isEdicao) {
                // Atualizar
                $sql = "UPDATE embarcacoes SET 
                    nome = :nome, 
                    registro = :registro, 
                    tipo_embarcacao_id = :tipo_embarcacao_id,
                    tipo_embarcacao = :tipo_embarcacao, 
                    cnbl_tipo_embarcacao = :cnbl_tipo_embarcacao,
                    ano = :ano,
                    porto_inscricao = :porto_inscricao,
                    numero_inscricao = :numero_inscricao,
                    indicativo_chamada = :indicativo_chamada,
                    observacoes = :observacoes,
                    possui_propulsao = :possui_propulsao, 
                    fabricante_motor = :fabricante_motor,
                    modelo_motor = :modelo_motor,
                    numero_motor = :numero_motor,
                    potencia_kw = :potencia_kw, 
                    material_casco = :material_casco, 
                    tipo_navegacao = :tipo_navegacao, 
                    area_navegacao = :area_navegacao, 
                    cnbl_area_navegacao = :cnbl_area_navegacao,
                    tipo_servico = :tipo_servico, 
                    autorizado_carga = :autorizado_carga, 
                    numero_tripulantes = :numero_tripulantes, 
                    numero_passageiros_n1 = :numero_passageiros_n1, 
                    numero_passageiros_n2 = :numero_passageiros_n2, 
                    obs_passageiros = :obs_passageiros, 
                    acessibilidade = :acessibilidade, 
                    comprimento_total = :comprimento_total, 
                    comprimento_casco = :comprimento_casco, 
                    comprimento_lpp = :comprimento_lpp, 
                    pontal_moldado = :pontal_moldado, 
                    boca_moldada = :boca_moldada, 
                    boca_maxima = :boca_maxima, 
                    arqueacao_bruta = :arqueacao_bruta, 
                    arqueacao_liquida = :arqueacao_liquida, 
                    metodo_arqueacao = :metodo_arqueacao, 
                    cnarq_data_quilha = :cnarq_data_quilha,
                    cnarq_calado_moldado_m = :cnarq_calado_moldado_m,
                    cnarq_espacos_incluidos_ab = :cnarq_espacos_incluidos_ab,
                    cnarq_espacos_incluidos_al = :cnarq_espacos_incluidos_al,
                    cnarq_espacos_excluidos_m3 = :cnarq_espacos_excluidos_m3,
                    cnarq_data_local_arqueacao_original = :cnarq_data_local_arqueacao_original,
                    cnarq_data_local_ultima_rearqueacao = :cnarq_data_local_ultima_rearqueacao,
                    local_construcao = :local_construcao,
                    numero_casco = :numero_casco,
                    porte_bruto = :porte_bruto,
                    estaleiro_nome = :estaleiro_nome,
                    estaleiro_cpf_cnpj = :estaleiro_cpf_cnpj,
                    estaleiro_endereco = :estaleiro_endereco,
                    borda_livre_mm = :borda_livre_mm, 
                    borda_livre_tipo = :borda_livre_tipo, 
                    calado_maximo_m = :calado_maximo_m, 
                    aresta_superior_linha_conves = :aresta_superior_linha_conves, 
                    centro_disco_situado = :centro_disco_situado, 
                    acrescimo_agua_salgada = :acrescimo_agua_salgada, 
                    dist_linha_conves_bico_proa = :dist_linha_conves_bico_proa, 
                    dist_linha_conves_abaixo_disco = :dist_linha_conves_abaixo_disco, 
                    marca_linha_carga_area1 = :marca_linha_carga_area1, 
                    marca_linha_carga_area2 = :marca_linha_carga_area2 
                WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $dados[':id'] = $id;
                $stmt->execute($dados);
                setMensagem('success', 'Embarcacao atualizada com sucesso!');
            } else {
                // Criar
                $sql = "INSERT INTO embarcacoes (
                    id, nome, registro, tipo_embarcacao_id, tipo_embarcacao, cnbl_tipo_embarcacao, ano, porto_inscricao, numero_inscricao, indicativo_chamada, observacoes, possui_propulsao, fabricante_motor, modelo_motor, numero_motor, potencia_kw, material_casco, tipo_navegacao, area_navegacao, cnbl_area_navegacao, tipo_servico, autorizado_carga, numero_tripulantes, numero_passageiros_n1, numero_passageiros_n2, obs_passageiros, acessibilidade, comprimento_total, comprimento_casco, comprimento_lpp, pontal_moldado, boca_moldada, boca_maxima, arqueacao_bruta, arqueacao_liquida, metodo_arqueacao, cnarq_data_quilha, cnarq_calado_moldado_m, cnarq_espacos_incluidos_ab, cnarq_espacos_incluidos_al, cnarq_espacos_excluidos_m3, cnarq_data_local_arqueacao_original, cnarq_data_local_ultima_rearqueacao, local_construcao, numero_casco, porte_bruto, estaleiro_nome, estaleiro_cpf_cnpj, estaleiro_endereco, borda_livre_mm, borda_livre_tipo, calado_maximo_m, aresta_superior_linha_conves, centro_disco_situado, acrescimo_agua_salgada, dist_linha_conves_bico_proa, dist_linha_conves_abaixo_disco, marca_linha_carga_area1, marca_linha_carga_area2, criado_por
                ) VALUES (
                    :id, :nome, :registro, :tipo_embarcacao_id, :tipo_embarcacao, :cnbl_tipo_embarcacao, :ano, :porto_inscricao, :numero_inscricao, :indicativo_chamada, :observacoes, :possui_propulsao, :fabricante_motor, :modelo_motor, :numero_motor, :potencia_kw, :material_casco, :tipo_navegacao, :area_navegacao, :cnbl_area_navegacao, :tipo_servico, :autorizado_carga, :numero_tripulantes, :numero_passageiros_n1, :numero_passageiros_n2, :obs_passageiros, :acessibilidade, :comprimento_total, :comprimento_casco, :comprimento_lpp, :pontal_moldado, :boca_moldada, :boca_maxima, :arqueacao_bruta, :arqueacao_liquida, :metodo_arqueacao, :cnarq_data_quilha, :cnarq_calado_moldado_m, :cnarq_espacos_incluidos_ab, :cnarq_espacos_incluidos_al, :cnarq_espacos_excluidos_m3, :cnarq_data_local_arqueacao_original, :cnarq_data_local_ultima_rearqueacao, :local_construcao, :numero_casco, :porte_bruto, :estaleiro_nome, :estaleiro_cpf_cnpj, :estaleiro_endereco, :borda_livre_mm, :borda_livre_tipo, :calado_maximo_m, :aresta_superior_linha_conves, :centro_disco_situado, :acrescimo_agua_salgada, :dist_linha_conves_bico_proa, :dist_linha_conves_abaixo_disco, :marca_linha_carga_area1, :marca_linha_carga_area2, :criado_por
                )";
                $stmt = $pdo->prepare($sql);
                $dados[':id'] = gerarUUID();
                $dados[':criado_por'] = $_SESSION['usuario_id'];
                $stmt->execute($dados);
                setMensagem('success', 'Embarcacao criada com sucesso!');
            }
        } catch (Exception $e) {
            error_log('Erro ao salvar embarcacao: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar embarcacao. Tente novamente.');
        }

        redirecionar(APP_URL . 'embarcacoes');
        break;

    // ==============================
    // DESATIVAR (SOFT DELETE)
    // ==============================
    case 'desativar':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome FROM embarcacoes WHERE id = :id AND ativo = 1");
            $stmt->execute([':id' => $id]);
            $embarcacao = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$embarcacao) {
                setMensagem('error', 'Embarcacao nao encontrada ou ja desativada.');
                redirecionar(APP_URL . 'embarcacoes');
            }

            $stmt = $pdo->prepare("UPDATE embarcacoes SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            setMensagem('success', 'Embarcacao "' . $embarcacao['nome'] . '" desativada com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao desativar embarcacao: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar embarcacao.');
        }

        redirecionar(APP_URL . 'embarcacoes');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'embarcacoes');
}
