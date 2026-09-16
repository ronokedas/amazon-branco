<?php
/**
 * ERP SISTEMA DE GESTÃO NAVAL
 * Motor Central Unificado de Emissão de Certificados e Licenças Navais
 * 
 * Centraliza e unifica a lógica de negócio, validações mandatórias (NORMAM, vistorias, propulsão),
 * regras de cálculo (numeração sequencial, janelas de revalidação anual, distribuição de passageiros)
 * e integridade relacional para todos os modelos: CSN, CNBL, CNARQ, LP, LC e CHT.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/assinaturas_usuarios.php';
require_once __DIR__ . '/aprovacao_documentos.php';

if (!function_exists('buscarDadosVistoriaCertificado')) {
    function buscarDadosVistoriaCertificado(PDO $pdo, string $vistoria_id): ?array
    {
        if (empty($vistoria_id)) {
            return null;
        }

        $stmtDados = $pdo->prepare("
            SELECT v.numero as relatorio_numero, v.data_vistoria, v.prazo_exigencias_dias,
                   v.status as relatorio_status,
                   COALESCE(a.local, '') as local_vistoria,
                   COALESCE(e.id, v.embarcacao_id, a.embarcacao_id) as embarcacao_id,
                   COALESCE(v.pessoa_id, e.proprietario_id, a.cliente_id) as cliente_id,
                   pc.nome as proprietario_nome_cadastro,
                   pc.cpf_cnpj as proprietario_cpf_cnpj_cadastro,
                   pc.endereco as proprietario_endereco_cadastro,
                   e.*,
                   e.nome as nome_embarcacao,
                   e.ano as ano_construcao,
                   te.nome as tipo_embarcacao_nome
            FROM vistorias v
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id
            LEFT JOIN embarcacoes e ON e.id = COALESCE(v.embarcacao_id, a.embarcacao_id)
            LEFT JOIN tipos_embarcacao te ON e.tipo_embarcacao_id = te.id
            LEFT JOIN clientes pc ON pc.id = COALESCE(v.pessoa_id, e.proprietario_id, a.cliente_id)
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

/**
 * Emite qualquer modelo de certificado ou licença de forma unificada e atômica.
 *
 * @param PDO    $pdo
 * @param string $modeloModelo CSN, CNBL, CNARQ, LP, LC ou CHT
 * @param array  $dados        Dados submetidos (do wizard ou do formulário direto)
 * @param string $usuarioId    ID do usuário autenticado emissor
 * @return array Informações do certificado emitido (id, numero, token, modelo, status, convite, etc.)
 * @throws InvalidArgumentException|RuntimeException
 */
function emitirCertificadoUnificado(PDO $pdo, string $modeloModelo, array $dados, string $usuarioId): array
{
    $modelo = strtoupper(trim($modeloModelo));
    if ($modelo === 'LICENÇA PROVISÓRIA' || $modelo === 'LICENCA PROVISORIA') {
        $modelo = 'LP';
    } elseif ($modelo === 'LICENÇA DE CONSTRUÇÃO' || $modelo === 'LICENCA DE CONSTRUCAO' || $modelo === 'LCE' || $modelo === 'LCEC') {
        $modelo = 'LC';
    }

    $modelosValidos = ['CSN', 'CNBL', 'CNARQ', 'LP', 'LC', 'CHT'];
    if (!in_array($modelo, $modelosValidos, true)) {
        throw new InvalidArgumentException("Modelo de certificado inválido: '{$modeloModelo}'. Permitidos: " . implode(', ', $modelosValidos));
    }

    $vistoriaId = trim((string)($dados['vistoria_id'] ?? ''));
    $tipo = trim((string)($dados['tipo'] ?? 'Definitivo'));
    if ($tipo === '') {
        $tipo = 'Definitivo';
    }

    // 1. Resolver e Carregar Dados da Vistoria e Embarcação
    $dadosEmb = [];
    if ($vistoriaId !== '') {
        $dadosEmb = buscarDadosVistoriaCertificado($pdo, $vistoriaId);
        if (!$dadosEmb) {
            throw new RuntimeException('Relatório de vistoria selecionado não foi encontrado ou é inválido.');
        }

        // Validação de status da vistoria
        $statusRelatorio = (string)($dadosEmb['relatorio_status'] ?? $dadosEmb['status'] ?? '');
        if (!in_array($statusRelatorio, ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
            throw new RuntimeException('Não é possível emitir certificado. O relatório selecionado não está aprovado.');
        }

        // Validação de liberação (prazos NORMAM-202, exigências A/S, validade de 60/90 dias)
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoriaId);
        if (empty($liberacao['permitido'])) {
            throw new RuntimeException($liberacao['mensagem'] ?? 'Certificação bloqueada para esta vistoria.');
        }

        // Bloqueio de Definitivo para relatórios com pendências de exigências (CSN, CNBL, CNARQ)
        if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) && $tipo === 'Definitivo' && $statusRelatorio === 'APROVADA_COM_EXIGENCIAS') {
            $msgDefinitivo = (string)($dadosEmb['mensagem_definitivo'] ?? $liberacao['mensagem_definitivo'] ?? '');
            throw new RuntimeException($msgDefinitivo ?: 'O relatório vigente ainda possui exigências comuns pendentes. Conclua a verificação antes de emitir o Certificado Definitivo.');
        }

        // Verificação de escopo contratual da Ordem de Serviço / Proposta
        if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true) && !certificadoModeloPermitidoPorVistoria($pdo, $vistoriaId, $modelo)) {
            throw new RuntimeException(certificadoMensagemServicoObrigatorio($modelo));
        }
    } elseif (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true)) {
        throw new RuntimeException("É obrigatório selecionar um relatório de vistoria aprovado para emitir o certificado {$modelo}.");
    }

    // 2. Validação Específica de Propulsão Náutica para CSN
    if ($modelo === 'CSN') {
        $possuiPropulsao = (int)($dados['possui_propulsao'] ?? $dadosEmb['possui_propulsao'] ?? 0);
        if ($possuiPropulsao === 1) {
            $fabricanteMotor = trim((string)($dados['fabricante_motor'] ?? $dadosEmb['fabricante_motor'] ?? ''));
            $modeloMotor = trim((string)($dados['modelo_motor'] ?? $dadosEmb['modelo_motor'] ?? ''));
            $numeroMotor = trim((string)($dados['numero_motor'] ?? $dadosEmb['numero_motor'] ?? ''));
            $potenciaKw = trim((string)($dados['potencia_kw'] ?? $dadosEmb['potencia_kw'] ?? ''));

            if ($fabricanteMotor === '' || $modeloMotor === '' || $numeroMotor === '' || $potenciaKw === '') {
                throw new RuntimeException('A embarcação possui propulsão. Preencha fabricante, modelo, número do motor e potência antes de gerar o CSN.');
            }
        }
    }

    // 3. Resolução e Validação do Responsável pela Assinatura
    $responsavelId = $dados['responsavel_assinatura_id'] ?? $dados['responsavel_id'] ?? null;
    $respData = null;
    if ($responsavelId !== null && $responsavelId !== '') {
        $respData = obterResponsavelAssinaturaPorId($pdo, $responsavelId);
        if (!$respData) {
            // Tenta busca por ID numérico direto
            $stmtResp = $pdo->prepare("SELECT ra.*, u.cargo usuario_cargo, u.ativo usuario_ativo, u.excluido_em
                FROM responsaveis_assinatura ra 
                JOIN usuarios u ON u.id = ra.usuario_id 
                WHERE ra.id = :id AND ra.ativo = 1 AND u.ativo = 1 AND u.excluido_em IS NULL");
            $stmtResp->execute([':id' => (int)$responsavelId]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);
        }

        if (!$respData || empty($respData['assinatura_arquivo']) || empty($respData['assinatura_hash'])) {
            throw new RuntimeException('Responsável pela assinatura inválido, inativo ou com perfil de assinatura incompleto.');
        }

        if (($respData['usuario_cargo'] ?? '') === 'ANALISTA' && $usuarioId !== '' && ($respData['usuario_id'] ?? '') !== $usuarioId) {
            throw new RuntimeException('Analistas somente podem assinar documentos criados por eles.');
        }
    }

    // 4. Resolução Relacional de Embarcação e Cliente
    $embarcacaoId = $dados['embarcacao_id'] ?? $dadosEmb['embarcacao_id'] ?? $dadosEmb['id'] ?? null;
    $clienteId = $dados['cliente_id'] ?? $dadosEmb['cliente_id'] ?? $dadosEmb['proprietario_id'] ?? null;

    if (empty($embarcacaoId) && !empty($dados['numero_inscricao'])) {
        $stmtInsc = $pdo->prepare("SELECT id, proprietario_id FROM embarcacoes WHERE numero_inscricao = :insc1 OR registro = :insc2 LIMIT 1");
        $inscVal = trim((string)$dados['numero_inscricao']);
        $stmtInsc->execute([':insc1' => $inscVal, ':insc2' => $inscVal]);
        $rowInsc = $stmtInsc->fetch(PDO::FETCH_ASSOC);
        if ($rowInsc) {
            $embarcacaoId = $rowInsc['id'];
            if (empty($clienteId)) {
                $clienteId = $rowInsc['proprietario_id'];
            }
        }
    }
    if (empty($embarcacaoId) && !empty($dados['nome_embarcacao'])) {
        $stmtNome = $pdo->prepare("SELECT id, proprietario_id FROM embarcacoes WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome)) LIMIT 1");
        $stmtNome->execute([':nome' => trim((string)$dados['nome_embarcacao'])]);
        $rowNome = $stmtNome->fetch(PDO::FETCH_ASSOC);
        if ($rowNome) {
            $embarcacaoId = $rowNome['id'];
            if (empty($clienteId)) {
                $clienteId = $rowNome['proprietario_id'];
            }
        }
    }
    if (empty($clienteId) && !empty($dados['profissional_empresa']) && $modelo === 'CHT') {
        $stmtCli = $pdo->prepare("SELECT id FROM clientes WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome)) LIMIT 1");
        $stmtCli->execute([':nome' => trim((string)$dados['profissional_empresa'])]);
        $clienteId = $stmtCli->fetchColumn() ?: null;
    }

    // Identificadores e Tokens Canônicos
    $certificadoId = !empty($dados['id']) ? (string)$dados['id'] : gerarUUID();
    $tokenAssinatura = bin2hex(random_bytes(32));
    $ano2 = date('y');
    $ano4 = date('Y');

    // Funções utilitárias locais de conversão
    $valorDecimal = static fn($v) => ($v === '' || $v === null) ? null : $v;
    $valorInt = static fn($v) => ($v === '' || $v === null) ? null : (int)$v;

    $gerenciouTransacao = false;
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $gerenciouTransacao = true;
    }

    try {
        $numeroCertificado = '';
        $conviteAssinatura = null;
        $envioEmail = null;

        switch ($modelo) {
            case 'CSN':
                $numeroCertificado = trim((string)($dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $stmtNum = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_csn WHERE YEAR(criado_em) = :ano");
                    $stmtNum->execute([':ano' => $ano4]);
                    $seq = ((int)$stmtNum->fetch()['total']) + 1;
                    $numeroCertificado = "AM-CSN-{$seq}/{$ano2}";
                }

                $qtdPassageiros = (int)($dados['qtd_passageiros'] ?? 0);
                if ($qtdPassageiros === 0) {
                    $qtdPassageiros = (int)($dadosEmb['numero_passageiros_n1'] ?? 0) + (int)($dadosEmb['numero_passageiros_n2'] ?? 0);
                }

                $motorTexto = '';
                if ((int)($dados['possui_propulsao'] ?? $dadosEmb['possui_propulsao'] ?? 0) === 1) {
                    $motorTexto = trim((string)($dados['fabricante_motor'] ?? ''));
                    if ($motorTexto === '' || $motorTexto === ($dadosEmb['fabricante_motor'] ?? '')) {
                        $motorTexto = implode(' - ', array_filter([
                            trim((string)($dadosEmb['fabricante_motor'] ?? $dados['fabricante_motor'] ?? '')),
                            trim((string)($dadosEmb['modelo_motor'] ?? $dados['modelo_motor'] ?? '')),
                            trim((string)($dadosEmb['numero_motor'] ?? $dados['numero_motor'] ?? '')),
                        ], static fn($val) => $val !== ''));
                    }
                }

                $tipoNavegacaoCSN = is_array($dados['tipo_navegacao'] ?? null) 
                    ? implode(',', $dados['tipo_navegacao']) 
                    : (string)($dados['tipo_navegacao'] ?? $dadosEmb['tipo_navegacao'] ?? '');

                $areaNavegacaoCSN = is_array($dados['area_navegacao'] ?? null)
                    ? implode(',', $dados['area_navegacao'])
                    : (string)($dados['area_navegacao'] ?? $dadosEmb['cnbl_area_navegacao'] ?? $dadosEmb['area_navegacao'] ?? '');

                $sqlCSN = "INSERT INTO certificados_csn (
                            id, numero, tipo, token_assinatura,
                            embarcacao_id, cliente_id,
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
                            status, ativo, criado_por, vistoria_id, despachante_id, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero, :tipo, :token_assinatura,
                            :embarcacao_id, :cliente_id,
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
                            :status, 1, :criado_por, :vistoria_id, :despachante_id, :responsavel_assinatura_id
                        )";

                $stmtCSN = $pdo->prepare($sqlCSN);
                $stmtCSN->execute([
                    ':id' => $certificadoId,
                    ':numero' => $numeroCertificado,
                    ':tipo' => $tipo,
                    ':token_assinatura' => $tokenAssinatura,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':nome_embarcacao' => trim((string)($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? $dadosEmb['nome_embarcacao'] ?? '')),
                    ':numero_inscricao' => trim((string)($dados['numero_inscricao'] ?? $dadosEmb['numero_inscricao'] ?? $dadosEmb['registro'] ?? '')),
                    ':indicativo_chamada' => trim((string)($dados['indicativo_chamada'] ?? $dadosEmb['indicativo_chamada'] ?? '')),
                    ':atividades_servicos' => trim((string)($dados['atividades_servicos'] ?? $dadosEmb['tipo_servico'] ?? $dadosEmb['atividades_servicos'] ?? '')),
                    ':tipo_embarcacao' => trim((string)($dados['tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? '')),
                    ':ano_construcao' => trim((string)($dados['ano_construcao'] ?? $dadosEmb['ano'] ?? $dadosEmb['ano_construcao'] ?? '')),
                    ':comprimento_m' => $valorDecimal($dados['comprimento_m'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':arqueacao_bruta' => trim((string)($dados['arqueacao_bruta'] ?? $dadosEmb['arqueacao_bruta'] ?? '')),
                    ':tipo_navegacao' => $tipoNavegacaoCSN,
                    ':area_navegacao' => $areaNavegacaoCSN,
                    ':fabricante_motor' => $motorTexto,
                    ':potencia_kw' => trim((string)($dados['potencia_kw'] ?? $dadosEmb['potencia_kw'] ?? '')),
                    ':material_casco' => trim((string)($dados['material_casco'] ?? $dadosEmb['material_casco'] ?? '')),
                    ':autorizado_carga' => (int)($dados['autorizado_carga'] ?? $dadosEmb['autorizado_carga'] ?? 0),
                    ':qtd_passageiros' => $qtdPassageiros,
                    ':obs_passageiros' => trim((string)($dados['obs_passageiros'] ?? $dadosEmb['obs_passageiros'] ?? '')),
                    ':emitente' => trim((string)($dados['emitente'] ?? 'AMAZON NAVAL')),
                    ':normam_aplicavel' => trim((string)($dados['normam_aplicavel'] ?? 'NORMAM-202')),
                    ':tipo_vistoria_certificado' => trim((string)($dados['tipo_vistoria_certificado'] ?? '')),
                    ':observacoes_verso' => trim((string)($dados['observacoes_verso'] ?? '')),
                    ':relatorio_numero' => trim((string)($dados['relatorio_numero'] ?? $dadosEmb['relatorio_numero'] ?? '')),
                    ':data_vistoria_seco' => $dados['data_vistoria_seco'] ?? $dadosEmb['data_vistoria'] ?? date('Y-m-d'),
                    ':data_vistoria_flutuando' => $dados['data_vistoria_flutuando'] ?? $dadosEmb['data_vistoria'] ?? date('Y-m-d'),
                    ':local_vistoria' => trim((string)($dados['local_vistoria'] ?? $dadosEmb['local_vistoria'] ?? '')),
                    ':acessibilidade_sim' => !empty($dados['acessibilidade_sim']) || ($dados['acessibilidade'] ?? '') === 'sim' ? 1 : 0,
                    ':acessibilidade_nao' => empty($dados['acessibilidade_sim']) && ($dados['acessibilidade'] ?? '') !== 'sim' ? 1 : 0,
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':data_validade' => $dados['data_validade'] ?? date('Y-m-d', strtotime('+5 years')),
                    ':local_emissao' => trim((string)($dados['local_emissao'] ?? 'Belém-PA')),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':vistoria_id' => $vistoriaId ?: null,
                    ':despachante_id' => $dados['despachante_id'] ?? null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                // Salvar Distribuição de Passageiros
                $distribuicao = $dados['distribuicao_passageiros'] ?? [];
                $stmtDist = $pdo->prepare("INSERT INTO csn_distribuicao_passageiros
                    (id, certificado_id, item_codigo, local_nome, quantidade, conves_principal, conves_superior, area_lazer, unidade)
                    VALUES (:id, :certificado_id, :item_codigo, :local_nome, :quantidade, :conves_principal, :conves_superior, :area_lazer, :unidade)");

                if (!empty($distribuicao) && is_array($distribuicao)) {
                    foreach ($distribuicao as $item) {
                        $stmtDist->execute([
                            ':id' => gerarUUID(),
                            ':certificado_id' => $certificadoId,
                            ':item_codigo' => trim((string)($item['codigo'] ?? $item['item_codigo'] ?? '')),
                            ':local_nome' => trim((string)($item['local'] ?? $item['local_nome'] ?? '')),
                            ':quantidade' => $valorInt($item['quantidade'] ?? null),
                            ':conves_principal' => trim((string)($item['conves_principal'] ?? '')),
                            ':conves_superior' => trim((string)($item['conves_superior'] ?? '')),
                            ':area_lazer' => trim((string)($item['area_lazer'] ?? '')),
                            ':unidade' => trim((string)($item['unidade'] ?? '')),
                        ]);
                    }
                } else {
                    // Padrão automático com base nos passageiros informados
                    $linhasPadrao = [
                        ['passageiros_sentados', 'Passageiros sentados', 'passageiros', (string)($dadosEmb['numero_passageiros_n1'] ?? '')],
                        ['passageiros_camarote', 'Passageiros em camarote', 'passageiros', ''],
                        ['passageiros_redes', 'Passageiros em redes', 'passageiros', ''],
                        ['passageiros_em_pe', 'Passageiros em pé', 'passageiros', (string)($dadosEmb['numero_passageiros_n2'] ?? '')],
                        ['porao_carga_01', 'Porão de carga 01 (carga geral)', 't', ''],
                        ['paiol_casco', 'Paiol no casco (mantimentos e materiais diversos)', 't', ''],
                        ['almoxarifado_conves_principal', 'Almoxarifado no convés principal', 't', ''],
                        ['deposito_conves_principal', 'Depósito no convés principal', 't', ''],
                        ['deposito_conves_superior', 'Depósito no convés superior', 't', ''],
                    ];
                    foreach ($linhasPadrao as $lp) {
                        $stmtDist->execute([
                            ':id' => gerarUUID(),
                            ':certificado_id' => $certificadoId,
                            ':item_codigo' => $lp[0],
                            ':local_nome' => $lp[1],
                            ':quantidade' => null,
                            ':conves_principal' => $lp[3],
                            ':conves_superior' => '',
                            ':area_lazer' => '',
                            ':unidade' => $lp[2],
                        ]);
                    }
                }

                // Janelas de Revalidação Anual para CSN Definitivo
                if ($tipo === 'Definitivo') {
                    $dataVistoria = $dadosEmb['data_vistoria'] ?? $dados['data_vistoria_flutuando'] ?? date('Y-m-d');
                    $tipoEmbarcacao = $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? $dados['tipo_embarcacao'] ?? '';
                    $anosValidade = certificadoAnosValidadePorTipoEmbarcacao($tipoEmbarcacao);
                    $qtdJanelas = max(1, $anosValidade - 1);

                    $stmtConv = $pdo->prepare("INSERT INTO csn_convalidacoes
                        (id, certificado_id, numero_vistoria, data_inicio, data_fim, local_data, vistoriador)
                        VALUES (:id, :cert_id, :numero, :data_inicio, :data_fim, :local_data, :vistoriador)");

                    for ($i = 1; $i <= $qtdJanelas; $i++) {
                        $dataAniversario = date('Y-m-d', strtotime("+{$i} years", strtotime($dataVistoria)));
                        $dataInicio = date('Y-m-d', strtotime("-3 months", strtotime($dataAniversario)));
                        $dataFim = date('Y-m-d', strtotime("+3 months", strtotime($dataAniversario)));

                        $stmtConv->execute([
                            ':id' => gerarUUID(),
                            ':cert_id' => $certificadoId,
                            ':numero' => "{$i}ª VIST. ANUAL",
                            ':data_inicio' => $dataInicio,
                            ':data_fim' => $dataFim,
                            ':local_data' => '',
                            ':vistoriador' => '',
                        ]);
                    }
                }

                if ($respData) {
                    $conviteAssinatura = assinaturaCriarConviteCertificado($pdo, 'CSN', $certificadoId, (int)$respData['id']);
                }
                log_atividade('certificado_csn_criado', "Certificado {$numeroCertificado} ({$tipo}) - " . ($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? ''));
                break;

            case 'CNBL':
                $numeroCertificado = trim((string)($dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $stmtNum = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano");
                    $stmtNum->execute([':ano' => $ano4]);
                    $seq = ((int)$stmtNum->fetch()['total']) + 1;
                    $numeroCertificado = "AM-CNBL-{$seq}/{$ano2}";
                }

                $tipoNavegacaoCNBL = is_array($dados['tipo_navegacao'] ?? null) 
                    ? implode(',', $dados['tipo_navegacao']) 
                    : (string)($dados['tipo_navegacao'] ?? $dadosEmb['tipo_navegacao'] ?? '');

                $areaNavegacaoCNBL = is_array($dados['area_navegacao'] ?? null)
                    ? implode(',', $dados['area_navegacao'])
                    : (string)($dados['area_navegacao'] ?? $dadosEmb['cnbl_area_navegacao'] ?? $dadosEmb['area_navegacao'] ?? '');

                $sqlCNBL = "INSERT INTO certificados_cnbl (
                            id, numero, tipo, token_assinatura,
                            embarcacao_id, cliente_id,
                            nome_embarcacao, numero_inscricao, porto_inscricao, indicativo_chamada,
                            atividades_servicos, tipo_embarcacao, ano_construcao,
                            comprimento_total, comprimento_casco, boca_moldada, pontal_moldado,
                            arqueacao_bruta, tipo_navegacao, area_navegacao, material_casco,
                            borda_livre_mm, borda_livre_tipo, calado_maximo_m,
                            aresta_superior_linha_conves, centro_disco_situado,
                            dist_linha_conves_bico_proa, dist_linha_conves_abaixo_disco,
                            marca_linha_carga_area1, marca_linha_carga_area2, acrescimo_agua_salgada,
                            relatorio_numero, data_vistoria, local_vistoria,
                            tipo_vistoria_certificado, observacoes_verso,
                            data_emissao, data_validade, local_emissao,
                            assinante_nome, assinante_titulo, assinante_registro,
                            status, ativo, criado_por, vistoria_id, despachante_id, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero, :tipo, :token_assinatura,
                            :embarcacao_id, :cliente_id,
                            :nome_embarcacao, :numero_inscricao, :porto_inscricao, :indicativo_chamada,
                            :atividades_servicos, :tipo_embarcacao, :ano_construcao,
                            :comprimento_total, :comprimento_casco, :boca_moldada, :pontal_moldado,
                            :arqueacao_bruta, :tipo_navegacao, :area_navegacao, :material_casco,
                            :borda_livre_mm, :borda_livre_tipo, :calado_maximo_m,
                            :aresta_superior_linha_conves, :centro_disco_situado,
                            :dist_linha_conves_bico_proa, :dist_linha_conves_abaixo_disco,
                            :marca_linha_carga_area1, :marca_linha_carga_area2, :acrescimo_agua_salgada,
                            :relatorio_numero, :data_vistoria, :local_vistoria,
                            :tipo_vistoria_certificado, :observacoes_verso,
                            :data_emissao, :data_validade, :local_emissao,
                            :assinante_nome, :assinante_titulo, :assinante_registro,
                            :status, 1, :criado_por, :vistoria_id, :despachante_id, :responsavel_assinatura_id
                        )";

                $stmtCNBL = $pdo->prepare($sqlCNBL);
                $stmtCNBL->execute([
                    ':id' => $certificadoId,
                    ':numero' => $numeroCertificado,
                    ':tipo' => $tipo,
                    ':token_assinatura' => $tokenAssinatura,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':nome_embarcacao' => trim((string)($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? $dadosEmb['nome_embarcacao'] ?? '')),
                    ':numero_inscricao' => trim((string)($dados['numero_inscricao'] ?? $dadosEmb['numero_inscricao'] ?? $dadosEmb['registro'] ?? '')),
                    ':porto_inscricao' => trim((string)($dados['porto_inscricao'] ?? $dadosEmb['porto_inscricao'] ?? '')),
                    ':indicativo_chamada' => trim((string)($dados['indicativo_chamada'] ?? $dadosEmb['indicativo_chamada'] ?? '')),
                    ':atividades_servicos' => trim((string)($dados['atividades_servicos'] ?? $dadosEmb['tipo_servico'] ?? $dadosEmb['atividades_servicos'] ?? '')),
                    ':tipo_embarcacao' => trim((string)($dados['tipo_embarcacao'] ?? $dadosEmb['cnbl_tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao_nome'] ?? '')),
                    ':ano_construcao' => trim((string)($dados['ano_construcao'] ?? $dadosEmb['ano'] ?? $dadosEmb['ano_construcao'] ?? '')),
                    ':comprimento_total' => $valorDecimal($dados['comprimento_total'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':comprimento_casco' => $valorDecimal($dados['comprimento_casco'] ?? $dadosEmb['comprimento_casco'] ?? null),
                    ':boca_moldada' => $valorDecimal($dados['boca_moldada'] ?? $dadosEmb['boca_moldada'] ?? null),
                    ':pontal_moldado' => $valorDecimal($dados['pontal_moldado'] ?? $dadosEmb['pontal_moldado'] ?? null),
                    ':arqueacao_bruta' => trim((string)($dados['arqueacao_bruta'] ?? $dadosEmb['arqueacao_bruta'] ?? '')),
                    ':tipo_navegacao' => $tipoNavegacaoCNBL,
                    ':area_navegacao' => $areaNavegacaoCNBL,
                    ':material_casco' => trim((string)($dados['material_casco'] ?? $dadosEmb['material_casco'] ?? '')),
                    ':borda_livre_mm' => $valorInt($dados['borda_livre_mm'] ?? $dadosEmb['borda_livre_mm'] ?? null),
                    ':borda_livre_tipo' => trim((string)($dados['borda_livre_tipo'] ?? $dadosEmb['borda_livre_tipo'] ?? '')),
                    ':calado_maximo_m' => $valorDecimal($dados['calado_maximo_m'] ?? $dadosEmb['calado_maximo_m'] ?? null),
                    ':aresta_superior_linha_conves' => trim((string)($dados['aresta_superior_linha_conves'] ?? $dadosEmb['aresta_superior_linha_conves'] ?? '')),
                    ':centro_disco_situado' => trim((string)($dados['centro_disco_situado'] ?? $dadosEmb['centro_disco_situado'] ?? '')),
                    ':dist_linha_conves_bico_proa' => trim((string)($dados['dist_linha_conves_bico_proa'] ?? $dadosEmb['dist_linha_conves_bico_proa'] ?? '')),
                    ':dist_linha_conves_abaixo_disco' => trim((string)($dados['dist_linha_conves_abaixo_disco'] ?? $dadosEmb['dist_linha_conves_abaixo_disco'] ?? '')),
                    ':marca_linha_carga_area1' => trim((string)($dados['marca_linha_carga_area1'] ?? $dadosEmb['marca_linha_carga_area1'] ?? '')),
                    ':marca_linha_carga_area2' => trim((string)($dados['marca_linha_carga_area2'] ?? $dadosEmb['marca_linha_carga_area2'] ?? '')),
                    ':acrescimo_agua_salgada' => trim((string)($dados['acrescimo_agua_salgada'] ?? $dadosEmb['acrescimo_agua_salgada'] ?? '')),
                    ':relatorio_numero' => trim((string)($dados['relatorio_numero'] ?? $dadosEmb['relatorio_numero'] ?? '')),
                    ':data_vistoria' => $dados['data_vistoria'] ?? $dadosEmb['data_vistoria'] ?? date('Y-m-d'),
                    ':local_vistoria' => trim((string)($dados['local_vistoria'] ?? $dadosEmb['local_vistoria'] ?? '')),
                    ':tipo_vistoria_certificado' => trim((string)($dados['tipo_vistoria_certificado'] ?? '')),
                    ':observacoes_verso' => trim((string)($dados['observacoes_verso'] ?? '')),
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':data_validade' => $dados['data_validade'] ?? date('Y-m-d', strtotime('+5 years')),
                    ':local_emissao' => trim((string)($dados['local_emissao'] ?? 'Belém-PA')),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':vistoria_id' => $vistoriaId ?: null,
                    ':despachante_id' => $dados['despachante_id'] ?? null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                // Janelas de Revalidação Anual para CNBL Definitivo
                if ($tipo === 'Definitivo') {
                    $dataVistoriaCNBL = $dadosEmb['data_vistoria'] ?? $dados['data_vistoria'] ?? date('Y-m-d');
                    $tipoEmbarcacaoCNBL = $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? $dados['tipo_embarcacao'] ?? '';
                    $qtdJanelasCNBL = max(1, certificadoAnosValidadePorTipoEmbarcacao($tipoEmbarcacaoCNBL) - 1);

                    $stmtConvCnbl = $pdo->prepare("INSERT INTO cert_convalidacoes
                        (id, tipo_certificado, certificado_id, numero_vistoria, data_inicio, data_fim, local_data, vistoriador)
                        VALUES (:id, 'CNBL', :cert_id, :numero, :data_inicio, :data_fim, :local_data, :vistoriador)");

                    for ($i = 1; $i <= $qtdJanelasCNBL; $i++) {
                        $dataAniversario = date('Y-m-d', strtotime("+{$i} years", strtotime($dataVistoriaCNBL)));
                        $dataInicio = date('Y-m-d', strtotime("-3 months", strtotime($dataAniversario)));
                        $dataFim = date('Y-m-d', strtotime("+3 months", strtotime($dataAniversario)));

                        $stmtConvCnbl->execute([
                            ':id' => gerarUUID(),
                            ':cert_id' => $certificadoId,
                            ':numero' => "{$i}ª VIST. ANUAL",
                            ':data_inicio' => $dataInicio,
                            ':data_fim' => $dataFim,
                            ':local_data' => '',
                            ':vistoriador' => '',
                        ]);
                    }
                }

                if ($respData) {
                    $conviteAssinatura = assinaturaCriarConviteCertificado($pdo, 'CNBL', $certificadoId, (int)$respData['id']);
                }
                log_atividade('certificado_cnbl_criado', "Certificado {$numeroCertificado} ({$tipo}) - " . ($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? ''));
                break;

            case 'CNARQ':
                $numeroCertificado = trim((string)($dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $stmtNum = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano");
                    $stmtNum->execute([':ano' => $ano4]);
                    $seq = ((int)$stmtNum->fetch()['total']) + 1;
                    $numeroCertificado = "AM-CNARQ-{$seq}/{$ano2}";
                }

                $sqlCNARQ = "INSERT INTO certificados_cnarq (
                            id, numero, tipo, token_assinatura,
                            embarcacao_id, cliente_id,
                            nome_embarcacao, numero_inscricao, indicativo_chamada,
                            tipo_embarcacao, ano_construcao, material_casco,
                            porto_inscricao, local_construcao, data_quilha,
                            comprimento_total, comprimento_casco, comprimento_lpp,
                            boca_moldada, boca_maxima, pontal_moldado,
                            arqueacao_bruta, arqueacao_liquida, metodo_arqueacao,
                            calado_moldado_m, passageiros_camarotes, passageiros_outros,
                            espacos_incluidos_ab, espacos_incluidos_al, espacos_excluidos_m3,
                            data_local_arqueacao_original, data_local_ultima_rearqueacao,
                            relatorio_numero, data_vistoria, local_vistoria,
                            tipo_vistoria_certificado, observacoes_verso,
                            data_emissao, data_validade, local_emissao,
                            assinante_nome, assinante_titulo, assinante_registro,
                            status, ativo, criado_por, vistoria_id, despachante_id, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero, :tipo, :token_assinatura,
                            :embarcacao_id, :cliente_id,
                            :nome_embarcacao, :numero_inscricao, :indicativo_chamada,
                            :tipo_embarcacao, :ano_construcao, :material_casco,
                            :porto_inscricao, :local_construcao, :data_quilha,
                            :comprimento_total, :comprimento_casco, :comprimento_lpp,
                            :boca_moldada, :boca_maxima, :pontal_moldado,
                            :arqueacao_bruta, :arqueacao_liquida, :metodo_arqueacao,
                            :calado_moldado_m, :passageiros_camarotes, :passageiros_outros,
                            :espacos_incluidos_ab, :espacos_incluidos_al, :espacos_excluidos_m3,
                            :data_local_arqueacao_original, :data_local_ultima_rearqueacao,
                            :relatorio_numero, :data_vistoria, :local_vistoria,
                            :tipo_vistoria_certificado, :observacoes_verso,
                            :data_emissao, :data_validade, :local_emissao,
                            :assinante_nome, :assinante_titulo, :assinante_registro,
                            :status, 1, :criado_por, :vistoria_id, :despachante_id, :responsavel_assinatura_id
                        )";

                $stmtCNARQ = $pdo->prepare($sqlCNARQ);
                $stmtCNARQ->execute([
                    ':id' => $certificadoId,
                    ':numero' => $numeroCertificado,
                    ':tipo' => $tipo,
                    ':token_assinatura' => $tokenAssinatura,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':nome_embarcacao' => trim((string)($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? $dadosEmb['nome_embarcacao'] ?? '')),
                    ':numero_inscricao' => trim((string)($dados['numero_inscricao'] ?? $dadosEmb['numero_inscricao'] ?? $dadosEmb['registro'] ?? '')),
                    ':indicativo_chamada' => trim((string)($dados['indicativo_chamada'] ?? $dadosEmb['indicativo_chamada'] ?? '')),
                    ':tipo_embarcacao' => trim((string)($dados['tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? '')),
                    ':ano_construcao' => trim((string)($dados['ano_construcao'] ?? $dadosEmb['ano'] ?? $dadosEmb['ano_construcao'] ?? '')),
                    ':material_casco' => trim((string)($dados['material_casco'] ?? $dadosEmb['material_casco'] ?? '')),
                    ':porto_inscricao' => trim((string)($dados['porto_inscricao'] ?? $dadosEmb['porto_inscricao'] ?? '')),
                    ':local_construcao' => trim((string)($dados['local_construcao'] ?? $dadosEmb['local_construcao'] ?? '')),
                    ':data_quilha' => trim((string)($dados['data_quilha'] ?? $dadosEmb['cnarq_data_quilha'] ?? $dadosEmb['ano'] ?? $dadosEmb['ano_construcao'] ?? '')),
                    ':comprimento_total' => $valorDecimal($dados['comprimento_total'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':comprimento_casco' => $valorDecimal($dados['comprimento_casco'] ?? $dadosEmb['comprimento_casco'] ?? null),
                    ':comprimento_lpp' => $valorDecimal($dados['comprimento_lpp'] ?? $dadosEmb['comprimento_lpp'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':boca_moldada' => $valorDecimal($dados['boca_moldada'] ?? $dadosEmb['boca_moldada'] ?? null),
                    ':boca_maxima' => $valorDecimal($dados['boca_maxima'] ?? $dadosEmb['boca_maxima'] ?? null),
                    ':pontal_moldado' => $valorDecimal($dados['pontal_moldado'] ?? $dadosEmb['pontal_moldado'] ?? null),
                    ':arqueacao_bruta' => $valorDecimal($dados['arqueacao_bruta'] ?? $dadosEmb['arqueacao_bruta'] ?? null),
                    ':arqueacao_liquida' => $valorDecimal($dados['arqueacao_liquida'] ?? $dadosEmb['arqueacao_liquida'] ?? null),
                    ':metodo_arqueacao' => trim((string)($dados['metodo_arqueacao'] ?? $dadosEmb['metodo_arqueacao'] ?? '')),
                    ':calado_moldado_m' => $valorDecimal($dados['calado_moldado_m'] ?? $dadosEmb['cnarq_calado_moldado_m'] ?? $dadosEmb['calado_maximo_m'] ?? null),
                    ':passageiros_camarotes' => $valorInt($dados['passageiros_camarotes'] ?? $dadosEmb['numero_passageiros_n1'] ?? 0),
                    ':passageiros_outros' => $valorInt($dados['passageiros_outros'] ?? $dadosEmb['numero_passageiros_n2'] ?? 0),
                    ':espacos_incluidos_ab' => trim((string)($dados['espacos_incluidos_ab'] ?? $dadosEmb['cnarq_espacos_incluidos_ab'] ?? '')),
                    ':espacos_incluidos_al' => trim((string)($dados['espacos_incluidos_al'] ?? $dadosEmb['cnarq_espacos_incluidos_al'] ?? '')),
                    ':espacos_excluidos_m3' => $valorDecimal($dados['espacos_excluidos_m3'] ?? $dadosEmb['cnarq_espacos_excluidos_m3'] ?? 0),
                    ':data_local_arqueacao_original' => trim((string)($dados['data_local_arqueacao_original'] ?? $dadosEmb['cnarq_data_local_arqueacao_original'] ?? '')),
                    ':data_local_ultima_rearqueacao' => trim((string)($dados['data_local_ultima_rearqueacao'] ?? $dadosEmb['cnarq_data_local_ultima_rearqueacao'] ?? '')),
                    ':relatorio_numero' => trim((string)($dados['relatorio_numero'] ?? $dadosEmb['relatorio_numero'] ?? '')),
                    ':data_vistoria' => $dados['data_vistoria'] ?? $dadosEmb['data_vistoria'] ?? date('Y-m-d'),
                    ':local_vistoria' => trim((string)($dados['local_vistoria'] ?? $dadosEmb['local_vistoria'] ?? '')),
                    ':tipo_vistoria_certificado' => trim((string)($dados['tipo_vistoria_certificado'] ?? '')),
                    ':observacoes_verso' => trim((string)($dados['observacoes_verso'] ?? '')),
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':data_validade' => $dados['data_validade'] ?? date('Y-m-d', strtotime('+10 years')),
                    ':local_emissao' => trim((string)($dados['local_emissao'] ?? 'Belém-PA')),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':vistoria_id' => $vistoriaId ?: null,
                    ':despachante_id' => $dados['despachante_id'] ?? null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                if ($respData) {
                    $conviteAssinatura = assinaturaCriarConviteCertificado($pdo, 'CNARQ', $certificadoId, (int)$respData['id']);
                }
                log_atividade('certificado_cnarq_criado', "Certificado {$numeroCertificado} ({$tipo}) - " . ($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? ''));
                break;

            case 'LP':
                $numeroCertificado = trim((string)($dados['numero_lp'] ?? $dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $numeroCertificado = gerarNumeroDocumento('LP', 'AM-LP');
                }

                $dataValidadeLP = $dados['validade_data'] ?? $dados['data_validade'] ?? date('Y-m-d', strtotime('+180 days'));
                $validadeDias = max(1, (int)ceil((strtotime($dataValidadeLP) - strtotime(date('Y-m-d'))) / 86400));

                $obsLP = trim((string)($dados['observacoes_exigencias'] ?? ''));
                if ($obsLP === '') {
                    $obsLP = "1. A emissão da licença provisória não exime o interessado da obtenção da licença de construção definitiva, prevista na NORMAM aplicável.\n\n";
                    $obsLP .= "2. Licença Provisória emitida com base no relatório de vistoria n.º " . ($dadosEmb['relatorio_numero'] ?? '') . ".";
                }

                $sqlLP = "INSERT INTO certificados_lp (
                            id, numero_lp, embarcacao_id, cliente_id, token_assinatura,
                            tipo_licenca, nome_embarcacao, tipo_embarcacao, numero_casco,
                            material_casco, comprimento_total, boca_moldada, pontal_moldado,
                            proprietario_nome, proprietario_cpf_cnpj, proprietario_endereco,
                            estaleiro_nome, estaleiro_cpf_cnpj, estaleiro_endereco,
                            observacoes_exigencias, data_emissao, validade_dias, validade_data,
                            data_requerimento, assinante_nome, assinante_titulo, assinante_registro,
                            status, ativo, criado_por, vistoria_id, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero_lp, :embarcacao_id, :cliente_id, :token_assinatura,
                            :tipo_licenca, :nome_embarcacao, :tipo_embarcacao, :numero_casco,
                            :material_casco, :comprimento_total, :boca_moldada, :pontal_moldado,
                            :proprietario_nome, :proprietario_cpf_cnpj, :proprietario_endereco,
                            :estaleiro_nome, :estaleiro_cpf_cnpj, :estaleiro_endereco,
                            :observacoes_exigencias, :data_emissao, :validade_dias, :validade_data,
                            :data_requerimento, :assinante_nome, :assinante_titulo, :assinante_registro,
                            :status, 1, :criado_por, :vistoria_id, :responsavel_assinatura_id
                        )";

                $stmtLP = $pdo->prepare($sqlLP);
                $stmtLP->execute([
                    ':id' => $certificadoId,
                    ':numero_lp' => $numeroCertificado,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':token_assinatura' => $tokenAssinatura,
                    ':tipo_licenca' => trim((string)($dados['tipo_licenca'] ?? 'construcao')),
                    ':nome_embarcacao' => trim((string)($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? '')),
                    ':tipo_embarcacao' => trim((string)($dados['tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? '')),
                    ':numero_casco' => trim((string)($dados['numero_casco'] ?? $dadosEmb['numero_casco'] ?? '')),
                    ':material_casco' => trim((string)($dados['material_casco'] ?? $dadosEmb['material_casco'] ?? '')),
                    ':comprimento_total' => $valorDecimal($dados['comprimento_total'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':boca_moldada' => $valorDecimal($dados['boca_moldada'] ?? $dadosEmb['boca_moldada'] ?? null),
                    ':pontal_moldado' => $valorDecimal($dados['pontal_moldado'] ?? $dadosEmb['pontal_moldado'] ?? null),
                    ':proprietario_nome' => trim((string)($dados['proprietario_nome'] ?? $dadosEmb['proprietario_nome_cadastro'] ?? $dadosEmb['proprietario'] ?? '')),
                    ':proprietario_cpf_cnpj' => trim((string)($dados['proprietario_cpf_cnpj'] ?? $dadosEmb['proprietario_cpf_cnpj_cadastro'] ?? '')),
                    ':proprietario_endereco' => trim((string)($dados['proprietario_endereco'] ?? $dadosEmb['proprietario_endereco_cadastro'] ?? '')),
                    ':estaleiro_nome' => trim((string)($dados['estaleiro_nome'] ?? $dadosEmb['estaleiro_nome'] ?? '')),
                    ':estaleiro_cpf_cnpj' => trim((string)($dados['estaleiro_cpf_cnpj'] ?? $dadosEmb['estaleiro_cpf_cnpj'] ?? '')),
                    ':estaleiro_endereco' => trim((string)($dados['estaleiro_endereco'] ?? $dadosEmb['estaleiro_endereco'] ?? $dadosEmb['local_construcao'] ?? '')),
                    ':observacoes_exigencias' => $obsLP,
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':validade_dias' => $validadeDias,
                    ':validade_data' => $dataValidadeLP,
                    ':data_requerimento' => $dados['data_requerimento'] ?? $dadosEmb['data_vistoria'] ?? date('Y-m-d'),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':vistoria_id' => $vistoriaId ?: null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                log_atividade('certificado_lp_criado', "Licença Provisória {$numeroCertificado} - " . ($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? ''));
                break;

            case 'LC':
                $modalidadeLC = trim((string)($dados['tipo_licenca'] ?? 'LC'));
                $tipoSeqLC = $modalidadeLC === 'LCEC' ? 'EC' : $modalidadeLC;
                $numeroCertificado = trim((string)($dados['numero_lc'] ?? $dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $numeroCertificado = gerarNumeroDocumento($tipoSeqLC, 'AM-' . $tipoSeqLC);
                }

                $passageirosLC = (int)($dados['numero_passageiros'] ?? 0);
                if ($passageirosLC === 0) {
                    $passageirosLC = (int)($dadosEmb['numero_passageiros_n1'] ?? 0) + (int)($dadosEmb['numero_passageiros_n2'] ?? 0);
                }

                $propulsaoLC = !empty($dados['propulsao']) ? (string)$dados['propulsao'] : (!empty($dadosEmb['possui_propulsao']) ? 'Com Propulsão' : 'Sem Propulsão');

                $sqlLC = "INSERT INTO certificados_lc (
                            id, numero_lc, embarcacao_id, cliente_id, token_assinatura, tipo_licenca,
                            data_termino_construcao, nome_embarcacao, tipo_embarcacao,
                            numero_casco, material_casco, sociedade_classificadora,
                            comprimento_total, comprimento_pp, boca_moldada, pontal_moldado,
                            calado_maximo, porte_bruto, numero_tripulantes, numero_passageiros,
                            tipo_navegacao, area_navegacao, atividade_servico, propulsao,
                            proprietario_nome, proprietario_cpf_cnpj, proprietario_endereco,
                            estaleiro_nome, estaleiro_cpf_cnpj, estaleiro_endereco,
                            data_emissao, data_validade, local_emissao, relatorio_numero,
                            assinante_nome, assinante_titulo, assinante_registro,
                            status, ativo, criado_por, vistoria_id, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero_lc, :embarcacao_id, :cliente_id, :token_assinatura, :tipo_licenca,
                            :data_termino_construcao, :nome_embarcacao, :tipo_embarcacao,
                            :numero_casco, :material_casco, :sociedade_classificadora,
                            :comprimento_total, :comprimento_pp, :boca_moldada, :pontal_moldado,
                            :calado_maximo, :porte_bruto, :numero_tripulantes, :numero_passageiros,
                            :tipo_navegacao, :area_navegacao, :atividade_servico, :propulsao,
                            :proprietario_nome, :proprietario_cpf_cnpj, :proprietario_endereco,
                            :estaleiro_nome, :estaleiro_cpf_cnpj, :estaleiro_endereco,
                            :data_emissao, :data_validade, :local_emissao, :relatorio_numero,
                            :assinante_nome, :assinante_titulo, :assinante_registro,
                            :status, 1, :criado_por, :vistoria_id, :responsavel_assinatura_id
                        )";

                $stmtLC = $pdo->prepare($sqlLC);
                $stmtLC->execute([
                    ':id' => $certificadoId,
                    ':numero_lc' => $numeroCertificado,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':token_assinatura' => $tokenAssinatura,
                    ':tipo_licenca' => $modalidadeLC,
                    ':data_termino_construcao' => $dados['data_termino_construcao'] ?? null,
                    ':nome_embarcacao' => trim((string)($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? '')),
                    ':tipo_embarcacao' => trim((string)($dados['tipo_embarcacao'] ?? $dadosEmb['tipo_embarcacao_nome'] ?? $dadosEmb['tipo_embarcacao'] ?? '')),
                    ':numero_casco' => trim((string)($dados['numero_casco'] ?? $dadosEmb['numero_casco'] ?? '')),
                    ':material_casco' => trim((string)($dados['material_casco'] ?? $dadosEmb['material_casco'] ?? '')),
                    ':sociedade_classificadora' => trim((string)($dados['sociedade_classificadora'] ?? 'Amazon Naval Ltda')),
                    ':comprimento_total' => $valorDecimal($dados['comprimento_total'] ?? $dadosEmb['comprimento_total'] ?? null),
                    ':comprimento_pp' => $valorDecimal($dados['comprimento_pp'] ?? $dadosEmb['comprimento_lpp'] ?? null),
                    ':boca_moldada' => $valorDecimal($dados['boca_moldada'] ?? $dadosEmb['boca_moldada'] ?? null),
                    ':pontal_moldado' => $valorDecimal($dados['pontal_moldado'] ?? $dadosEmb['pontal_moldado'] ?? null),
                    ':calado_maximo' => $valorDecimal($dados['calado_maximo'] ?? $dadosEmb['calado_maximo_m'] ?? null),
                    ':porte_bruto' => $valorDecimal($dados['porte_bruto'] ?? $dadosEmb['porte_bruto'] ?? null),
                    ':numero_tripulantes' => (int)($dados['numero_tripulantes'] ?? $dadosEmb['numero_tripulantes'] ?? 0),
                    ':numero_passageiros' => $passageirosLC,
                    ':tipo_navegacao' => trim((string)($dados['tipo_navegacao'] ?? $dadosEmb['tipo_navegacao'] ?? '')),
                    ':area_navegacao' => trim((string)($dados['area_navegacao'] ?? $dadosEmb['area_navegacao'] ?? '')),
                    ':atividade_servico' => trim((string)($dados['atividade_servico'] ?? $dadosEmb['tipo_servico'] ?? '')),
                    ':propulsao' => $propulsaoLC,
                    ':proprietario_nome' => trim((string)($dados['proprietario_nome'] ?? $dadosEmb['proprietario_nome_cadastro'] ?? $dadosEmb['proprietario'] ?? '')),
                    ':proprietario_cpf_cnpj' => trim((string)($dados['proprietario_cpf_cnpj'] ?? $dadosEmb['proprietario_cpf_cnpj_cadastro'] ?? '')),
                    ':proprietario_endereco' => trim((string)($dados['proprietario_endereco'] ?? $dadosEmb['proprietario_endereco_cadastro'] ?? '')),
                    ':estaleiro_nome' => trim((string)($dados['estaleiro_nome'] ?? $dadosEmb['estaleiro_nome'] ?? '')),
                    ':estaleiro_cpf_cnpj' => trim((string)($dados['estaleiro_cpf_cnpj'] ?? $dadosEmb['estaleiro_cpf_cnpj'] ?? '')),
                    ':estaleiro_endereco' => trim((string)($dados['estaleiro_endereco'] ?? $dadosEmb['estaleiro_endereco'] ?? $dadosEmb['local_construcao'] ?? '')),
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':data_validade' => $dados['data_validade'] ?? null,
                    ':local_emissao' => trim((string)($dados['local_emissao'] ?? 'Belém-PA')),
                    ':relatorio_numero' => trim((string)($dados['relatorio_numero'] ?? $dadosEmb['relatorio_numero'] ?? '')),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':vistoria_id' => $vistoriaId ?: null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                log_atividade('certificado_lc_criado', "Licença de Construção {$numeroCertificado} - " . ($dados['nome_embarcacao'] ?? $dadosEmb['nome'] ?? ''));
                break;

            case 'CHT':
                $numeroCertificado = trim((string)($dados['numero_certificado'] ?? $dados['numero'] ?? ''));
                if ($numeroCertificado === '') {
                    $stmtNum = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cht WHERE YEAR(criado_em) = :ano");
                    $stmtNum->execute([':ano' => $ano4]);
                    $seq = ((int)$stmtNum->fetch()['total']) + 1;
                    $numeroCertificado = "AM-CHT-{$seq}/{$ano2}";
                }

                $sqlCHT = "INSERT INTO certificados_cht (
                            id, numero_certificado, numero_relatorio_ht, token_assinatura,
                            embarcacao_id, cliente_id,
                            profissional_empresa, cpf_cnpj, email_destinatario, atividade_homologada,
                            relatorio_homologacao_numero, observacoes,
                            data_emissao, data_validade, local_emissao,
                            assinante_nome, assinante_titulo, assinante_registro,
                            status, ativo, criado_por, responsavel_assinatura_id
                        ) VALUES (
                            :id, :numero_certificado, :numero_relatorio_ht, :token_assinatura,
                            :embarcacao_id, :cliente_id,
                            :profissional_empresa, :cpf_cnpj, :email_destinatario, :atividade_homologada,
                            :relatorio_homologacao_numero, :observacoes,
                            :data_emissao, :data_validade, :local_emissao,
                            :assinante_nome, :assinante_titulo, :assinante_registro,
                            :status, 1, :criado_por, :responsavel_assinatura_id
                        )";

                $stmtCHT = $pdo->prepare($sqlCHT);
                $stmtCHT->execute([
                    ':id' => $certificadoId,
                    ':numero_certificado' => $numeroCertificado,
                    ':numero_relatorio_ht' => trim((string)($dados['relatorio_homologacao_numero'] ?? $dados['numero_relatorio_ht'] ?? '')),
                    ':token_assinatura' => $tokenAssinatura,
                    ':embarcacao_id' => $embarcacaoId,
                    ':cliente_id' => $clienteId,
                    ':profissional_empresa' => trim((string)($dados['profissional_empresa'] ?? '')),
                    ':cpf_cnpj' => trim((string)($dados['cpf_cnpj'] ?? '')),
                    ':email_destinatario' => trim((string)($dados['email_destinatario'] ?? '')),
                    ':atividade_homologada' => trim((string)($dados['atividade_homologada'] ?? '')),
                    ':relatorio_homologacao_numero' => trim((string)($dados['relatorio_homologacao_numero'] ?? $dados['numero_relatorio_ht'] ?? '')),
                    ':observacoes' => trim((string)($dados['observacoes'] ?? '')),
                    ':data_emissao' => $dados['data_emissao'] ?? date('Y-m-d'),
                    ':data_validade' => $dados['data_validade'] ?? date('Y-m-d', strtotime('+2 years')),
                    ':local_emissao' => trim((string)($dados['local_emissao'] ?? 'Belém-PA')),
                    ':assinante_nome' => $respData['nome_completo'] ?? trim((string)($dados['assinante_nome'] ?? '')),
                    ':assinante_titulo' => $respData['cargo_titulo'] ?? trim((string)($dados['assinante_titulo'] ?? '')),
                    ':assinante_registro' => $respData['registro_profissional'] ?? trim((string)($dados['assinante_registro'] ?? '')),
                    ':status' => $dados['status'] ?? 'emitido',
                    ':criado_por' => $usuarioId ?: null,
                    ':responsavel_assinatura_id' => $respData ? (int)$respData['id'] : null,
                ]);

                log_atividade('certificado_cht_criado', "{$numeroCertificado} - " . ($dados['profissional_empresa'] ?? ''));
                break;
        }

        // Tentar envio de e-mail de convite se aplicável
        if ($respData && $conviteAssinatura && in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true)) {
            try {
                $envioEmail = assinaturaEnviarConviteCertificado($pdo, $modelo, $certificadoId, $conviteAssinatura);
            } catch (Throwable $mailError) {
                assinaturaRegistrarEmail($pdo, '', $modelo, $certificadoId, false, $mailError->getMessage());
                $envioEmail = ['success' => false, 'message' => $mailError->getMessage()];
            }
        }

        if ($gerenciouTransacao) {
            $pdo->commit();
        }

        return [
            'success' => true,
            'id' => $certificadoId,
            'numero' => $numeroCertificado,
            'modelo' => $modelo,
            'token' => $tokenAssinatura,
            'tipo' => $tipo,
            'status' => $dados['status'] ?? 'emitido',
            'convite_assinatura' => $conviteAssinatura,
            'envio_email' => $envioEmail,
            'responsavel_assinatura' => $respData,
        ];
    } catch (Throwable $e) {
        if ($gerenciouTransacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
