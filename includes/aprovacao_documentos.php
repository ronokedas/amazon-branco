<?php

require_once __DIR__ . '/documento_pdf.php';
require_once __DIR__ . '/aprovacao_pdf.php';
require_once __DIR__ . '/pdf_signature_provider.php';

function aprovacaoDocumentoMapas(): array
{
    return [
        'CSN' => ['table' => 'certificados_csn', 'number' => 'numero', 'pdf' => 'documentacao/certificados/pdf', 'label' => 'Certificado CSN'],
        'CNBL' => ['table' => 'certificados_cnbl', 'number' => 'numero', 'pdf' => 'documentacao/cnbl/pdf', 'label' => 'Certificado CNBL'],
        'CNARQ' => ['table' => 'certificados_cnarq', 'number' => 'numero', 'pdf' => 'documentacao/cnarq/pdf', 'label' => 'Certificado CNARQ'],
        'LP' => ['table' => 'certificados_lp', 'number' => 'numero_lp', 'pdf' => 'documentacao/lp/pdf', 'label' => 'Licenca Provisoria'],
        'LC' => ['table' => 'certificados_lc', 'number' => 'numero_lc', 'pdf' => 'documentacao/lc/pdf', 'label' => 'Licenca de Construcao'],
        'CHT' => ['table' => 'certificados_cht', 'number' => 'numero_certificado', 'pdf' => 'documentacao/cht/pdf', 'label' => 'Certificado CHT'],
        'RELATORIO' => ['table' => 'vistorias', 'number' => 'numero', 'pdf' => 'vistorias/relatorio_pdf', 'label' => 'Relatorio de Vistoria'],
        'PARECER_PLANOS' => ['table' => 'analise_planos_pareceres', 'number' => 'versao', 'pdf' => 'analises_planos/parecer_pdf', 'label' => 'Parecer de Analise de Planos'],
    ];
}

function aprovacaoDocumentoMapa(string $tipo): array
{
    $tipo = strtoupper(trim($tipo));
    $mapa = aprovacaoDocumentoMapas()[$tipo] ?? null;
    if (!$mapa) {
        throw new InvalidArgumentException('Tipo de documento nao suportado para aprovacao.');
    }
    return $mapa + ['type' => $tipo];
}

function aprovacaoDocumentoIp(): string
{
    return obterIpCliente();
}

function aprovacaoDocumentoGerarOriginal(string $tipo, string $id, string $destino): void
{
    global $pdo;
    $mapa = aprovacaoDocumentoMapa($tipo);
    $script = __DIR__ . '/../modules/' . $mapa['pdf'] . '.php';
    if (!is_file($script)) {
        throw new RuntimeException('Gerador de PDF nao encontrado.');
    }

    $oldGet = $_GET;
    $_GET = ['id' => $id];
    $salvar_pdf_caminho = $destino;
    $return_pdf_string = false;
    ob_start();
    require $script;
    $unexpected = ob_get_clean();
    $_GET = $oldGet;

    if ($unexpected !== '') {
        error_log('Saida inesperada ao gerar PDF para aprovacao: ' . substr($unexpected, 0, 300));
    }
    if (!is_file($destino) || filesize($destino) < 200) {
        throw new RuntimeException('Nao foi possivel gerar o PDF original.');
    }
}

function aprovacaoDocumentoCarregar(PDO $pdo, string $tipo, string $id, bool $lock = false): array
{
    $mapa = aprovacaoDocumentoMapa($tipo);
    $whereAtivo = in_array($tipo, ['RELATORIO','PARECER_PLANOS'], true) ? '' : ' AND ativo = 1';
    $sql = "SELECT * FROM {$mapa['table']} WHERE id = :id{$whereAtivo}" . ($lock ? ' FOR UPDATE' : '');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Documento nao encontrado.');
    }
    return $row;
}

function aprovacaoDocumentoValidarEstado(string $tipo, array $documento): void
{
    if ($tipo === 'RELATORIO') {
        $permitidos = ['AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS'];
        if (!in_array((string)$documento['status'], $permitidos, true)) {
            throw new RuntimeException('O relatorio nao esta disponivel para aprovacao.');
        }
        return;
    }
    if ($tipo === 'PARECER_PLANOS') {
        if ((string)$documento['status'] !== 'AGUARDANDO_APROVACAO') {
            throw new RuntimeException('O parecer nao esta aguardando aprovacao.');
        }
        return;
    }
    if (!empty($documento['assinado']) || (string)$documento['status'] === 'assinado') {
        throw new RuntimeException('Este documento ja foi aprovado e assinado.');
    }
    if ((string)$documento['status'] === 'cancelado') {
        throw new RuntimeException('Documento cancelado nao pode ser aprovado.');
    }
    if ((string)$documento['status'] !== 'emitido') {
        throw new RuntimeException('Somente documentos emitidos podem ser aprovados.');
    }
}

function aprovacaoRelatorioResumoExigencias(PDO $pdo, string $vistoriaId): array
{
    $stmtVistoria = $pdo->prepare("SELECT * FROM vistorias WHERE id = :id");
    $stmtVistoria->execute([':id' => $vistoriaId]);
    $vistoria = $stmtVistoria->fetch(PDO::FETCH_ASSOC);
    if (!$vistoria) {
        throw new RuntimeException('Relatorio nao encontrado.');
    }

    $stmt = $pdo->prepare("SELECT id, catalogo_id, bloco_vistoria, ordem, item, descricao, conforme,
                                  observacao, item_normam, vencimento, antes_de_suspender, status_item
                             FROM vistoria_exigencias
                            WHERE vistoria_id = :id
                            ORDER BY id");
    $stmt->execute([':id' => $vistoriaId]);
    $exigencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pendentes = 0;
    $pendentesAs = 0;

    foreach ($exigencias as $exigencia) {
        $aberta = (string)($exigencia['conforme'] ?? '') === 'nao'
            && (string)($exigencia['status_item'] ?? '') !== 'cumprida';
        if (!$aberta) continue;
        $pendentes++;
        if ((int)($exigencia['antes_de_suspender'] ?? 0) === 1) $pendentesAs++;
    }

    return [
        'pendentes' => $pendentes,
        'pendentes_as' => $pendentesAs,
        'pendentes_comuns' => $pendentes - $pendentesAs,
        'status_esperado' => $pendentes > 0 ? 'APROVADA_COM_EXIGENCIAS' : 'APROVADA',
        'versao' => hash('sha256', json_encode(
            ['vistoria' => $vistoria, 'exigencias' => $exigencias],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )),
    ];
}

function aprovacaoRelatorioValidarResultado(array $resumo, string $resultado, string $versao): void
{
    $permitidos = ['APROVADA', 'APROVADA_COM_EXIGENCIAS'];
    if (!in_array($resultado, $permitidos, true)) {
        throw new InvalidArgumentException('Selecione um resultado valido para aprovar o relatorio.');
    }
    if ($versao === '' || !hash_equals((string)$resumo['versao'], $versao)) {
        throw new RuntimeException('O relatorio ou suas exigencias foram alterados. Atualize a pagina e revise novamente antes de aprovar.');
    }
    if ($resultado !== (string)$resumo['status_esperado']) {
        $mensagem = ((int)$resumo['pendentes'] > 0)
            ? 'O relatorio possui exigencias abertas e deve ser aprovado com exigencias.'
            : 'O relatorio nao possui exigencias abertas e deve ser aprovado sem exigencias.';
        throw new RuntimeException($mensagem);
    }
}

function aprovacaoDocumentoFinalizarEstado(PDO $pdo, string $tipo, string $id, int $responsavelId, array $audit, string $relativeFinal, ?string $resultadoRelatorio = null, ?string $versaoRelatorio = null): ?string
{
    $mapa = aprovacaoDocumentoMapa($tipo);
    if ($tipo === 'RELATORIO') {
        $resumo = aprovacaoRelatorioResumoExigencias($pdo, $id);
        aprovacaoRelatorioValidarResultado($resumo, (string)$resultadoRelatorio, (string)$versaoRelatorio);
        $status = (string)$resumo['status_esperado'];
        $stmt = $pdo->prepare("UPDATE vistorias SET status = :status, aprovado_por = :usuario, responsavel_assinatura_id = :responsavel, data_aprovacao = :data WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':usuario' => $audit['aprovador_usuario_id'],
            ':responsavel' => $responsavelId,
            ':data' => $audit['aprovado_em_local'],
            ':id' => $id,
        ]);

        $doc = aprovacaoDocumentoCarregar($pdo, $tipo, $id, false);
        if (!empty($doc['agendamento_id'])) {
            $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :id AND status IN ('pendente','em_andamento')")->execute([':id' => $doc['agendamento_id']]);
            $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $doc['agendamento_id']]);
        }
        return $status;
    }
    if ($tipo === 'PARECER_PLANOS') {
        $stmt = $pdo->prepare("UPDATE analise_planos_pareceres SET status='PUBLICADO', responsavel_assinatura_id=:responsavel, publicado_em=:data WHERE id=:id AND status='AGUARDANDO_APROVACAO'");
        $stmt->execute([':responsavel'=>$responsavelId, ':data'=>$audit['aprovado_em_local'], ':id'=>$id]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('O parecer foi alterado durante a aprovacao.');
        $parecer = aprovacaoDocumentoCarregar($pdo, $tipo, $id, false);
        $novoStatus = match ($parecer['resultado']) {
            'EXIGENCIAS' => 'AGUARDANDO_CORRECAO',
            'REPROVADO' => 'REPROVADA',
            default => 'CONCLUIDA',
        };
        $pdo->prepare('UPDATE analises_planos SET status=:status, responsavel_assinatura_id=:responsavel WHERE id=:id')->execute([':status'=>$novoStatus, ':responsavel'=>$responsavelId, ':id'=>$parecer['analise_id']]);
        $pdo->prepare("INSERT INTO analise_planos_historico (analise_id,usuario_id,evento,status_anterior,status_novo,detalhe) VALUES (:analise,:usuario,'PARECER_PUBLICADO','AGUARDANDO_APROVACAO',:novo,:detalhe)")->execute([':analise'=>$parecer['analise_id'], ':usuario'=>$audit['aprovador_usuario_id'], ':novo'=>$novoStatus, ':detalhe'=>'Parecer v'.$parecer['versao'].' aprovado e publicado.']);
        return null;
    }

    $stmt = $pdo->prepare("UPDATE {$mapa['table']} SET responsavel_assinatura_id = :responsavel, assinatura_imagem = :assinatura, assinatura_ip = :ip, assinatura_em = :data, assinado = 1, status = 'assinado', caminho_arquivo_pdf = :caminho, hash_arquivo_pdf = :hash WHERE id = :id AND assinado = 0 AND status = 'emitido'");
    $stmt->execute([
        ':responsavel' => $responsavelId,
        ':assinatura' => $audit['assinatura_imagem_data'],
        ':ip' => $audit['ip'],
        ':data' => $audit['aprovado_em_local'],
        ':caminho' => $relativeFinal,
        ':hash' => $audit['hash_pdf_final'],
        ':id' => $id,
    ]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('O documento foi alterado durante a aprovacao.');
    }
    return null;
}

function aprovarDocumentoEletronicamente(PDO $pdo, array $input): array
{
    $tipo = strtoupper(trim((string)($input['documento_tipo'] ?? '')));
    if ($tipo === 'RELATORIO') {
        throw new RuntimeException('Relatorios de vistoria nao exigem assinatura eletronica. Use a decisao administrativa da revisao.');
    }
    $id = trim((string)($input['documento_id'] ?? ''));
    $responsavelId = (int)($input['responsavel_id'] ?? 0);
    $latitude = filter_var($input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $precisao = filter_var($input['geo_precisao_m'] ?? null, FILTER_VALIDATE_FLOAT);
    $resultadoRelatorio = strtoupper(trim((string)($input['resultado_relatorio'] ?? '')));
    $versaoRelatorio = trim((string)($input['versao_relatorio'] ?? ''));

    if ($id === '' || $responsavelId < 1 || $latitude === false || $longitude === false) {
        throw new InvalidArgumentException('Documento, responsavel e geolocalizacao sao obrigatorios.');
    }
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        throw new InvalidArgumentException('Coordenadas de geolocalizacao invalidas.');
    }

    $mapa = aprovacaoDocumentoMapa($tipo);
    $userId = (string)($_SESSION['usuario_id'] ?? '');
    $userName = trim((string)($_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Administrador'));
    if ($userId === '') {
        throw new RuntimeException('Sessao administrativa invalida.');
    }

    $tz = new DateTimeZone('America/Sao_Paulo');
    $nowLocal = new DateTimeImmutable('now', $tz);
    $nowUtc = $nowLocal->setTimezone(new DateTimeZone('UTC'));
    $token = bin2hex(random_bytes(32));
    $approvalId = function_exists('gerarUUID') ? gerarUUID() : sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', random_int(0,65535),random_int(0,65535),random_int(0,65535),random_int(0,4095)|0x4000,random_int(0,16383)|0x8000,random_int(0,65535),random_int(0,65535),random_int(0,65535));
    $ip = aprovacaoDocumentoIp();

    $pdo->beginTransaction();
    try {
        $documento = aprovacaoDocumentoCarregar($pdo, $tipo, $id, true);
        aprovacaoDocumentoValidarEstado($tipo, $documento);
        if ($tipo === 'RELATORIO') {
            aprovacaoRelatorioValidarResultado(
                aprovacaoRelatorioResumoExigencias($pdo, $id),
                $resultadoRelatorio,
                $versaoRelatorio
            );
        }

        $existing = $pdo->prepare("SELECT id FROM documento_aprovacoes WHERE documento_tipo = :tipo AND documento_id = :id AND status IN ('PROCESSANDO','APROVADO') LIMIT 1 FOR UPDATE");
        $existing->execute([':tipo' => $tipo, ':id' => $id]);
        if ($existing->fetchColumn()) {
            throw new RuntimeException('Este documento ja possui uma aprovacao em andamento ou concluida.');
        }

        $stmtResp = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE id = :id AND ativo = 1 FOR UPDATE");
        $stmtResp->execute([':id' => $responsavelId]);
        $resp = $stmtResp->fetch(PDO::FETCH_ASSOC);
        if (!$resp || trim((string)($resp['cpf_cnpj'] ?? '')) === '' || trim((string)($resp['assinatura_arquivo'] ?? '')) === '') {
            throw new RuntimeException('O responsavel precisa ter CPF/CNPJ e assinatura cadastrados.');
        }
        $signatureAbs = __DIR__ . '/../' . ltrim(str_replace(['../', '..\\'], '', (string)$resp['assinatura_arquivo']), '/\\');
        if (!is_file($signatureAbs) || !hash_equals((string)$resp['assinatura_hash'], hash_file('sha256', $signatureAbs))) {
            throw new RuntimeException('A assinatura cadastrada nao esta disponivel ou falhou na verificacao de integridade.');
        }

        $versionStmt = $pdo->prepare('SELECT COALESCE(MAX(versao),0)+1 FROM documento_aprovacoes WHERE documento_tipo = :tipo AND documento_id = :id');
        $versionStmt->execute([':tipo' => $tipo, ':id' => $id]);
        $version = (int)$versionStmt->fetchColumn();

        $insert = $pdo->prepare("INSERT INTO documento_aprovacoes (id, documento_tipo, documento_id, versao, responsavel_id, aprovador_usuario_id, responsavel_nome, responsavel_cpf_cnpj, responsavel_cargo, responsavel_registro, aprovador_nome, assinatura_arquivo, assinatura_hash, aprovado_em_utc, aprovado_em_local, fuso_horario, utc_offset, latitude, longitude, geo_precisao_m, ip, user_agent, token_validacao, status) VALUES (:id,:tipo,:documento,:versao,:responsavel,:usuario,:nome,:cpf,:cargo,:registro,:aprovador,:assinatura,:assinatura_hash,:utc,:local,'America/Sao_Paulo',:offset,:lat,:lng,:precisao,:ip,:ua,:token,'PROCESSANDO')");
        $insert->execute([
            ':id'=>$approvalId, ':tipo'=>$tipo, ':documento'=>$id, ':versao'=>$version,
            ':responsavel'=>$responsavelId, ':usuario'=>$userId, ':nome'=>$resp['nome_completo'],
            ':cpf'=>$resp['cpf_cnpj'], ':cargo'=>$resp['cargo_titulo'], ':registro'=>$resp['registro_profissional'] ?: null,
            ':aprovador'=>$userName, ':assinatura'=>$resp['assinatura_arquivo'], ':assinatura_hash'=>$resp['assinatura_hash'],
            ':utc'=>$nowUtc->format('Y-m-d H:i:s'), ':local'=>$nowLocal->format('Y-m-d H:i:s'), ':offset'=>$nowLocal->format('P'),
            ':lat'=>$latitude, ':lng'=>$longitude, ':precisao'=>$precisao === false ? null : $precisao,
            ':ip'=>$ip, ':ua'=>substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,500), ':token'=>$token,
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    $year = $nowLocal->format('Y');
    $baseRelative = 'storage/documentos_aprovados/' . $year . '/' . strtolower($tipo) . '/';
    $baseAbsolute = __DIR__ . '/../' . $baseRelative;
    $tmpDir = __DIR__ . '/../tmp/pdfs/';
    if (!is_dir($tmpDir) && !mkdir($tmpDir, 0750, true) && !is_dir($tmpDir)) throw new RuntimeException('Falha ao criar diretorio temporario.');
    if (!is_dir($baseAbsolute) && !mkdir($baseAbsolute, 0750, true) && !is_dir($baseAbsolute)) throw new RuntimeException('Falha ao criar diretorio de documentos.');

    $originalTmp = $tmpDir . $approvalId . '_original.pdf';
    $visualTmp = $tmpDir . $approvalId . '_audit.pdf';
    $finalTmp = $tmpDir . $approvalId . '_final.pdf';
    $originalRelative = $baseRelative . $approvalId . '_original.pdf';
    $finalRelative = $baseRelative . $approvalId . '.pdf';
    $originalAbsolute = __DIR__ . '/../' . $originalRelative;
    $finalAbsolute = __DIR__ . '/../' . $finalRelative;

    try {
        $GLOBALS['APROVACAO_RESPONSAVEL_PDF'] = $resp;
        aprovacaoDocumentoGerarOriginal($tipo, $id, $originalTmp);
        unset($GLOBALS['APROVACAO_RESPONSAVEL_PDF']);
        $hashOriginal = hash_file('sha256', $originalTmp);
        $context = [
            'documento_tipo'=>$tipo,
            'token_validacao'=>$token, 'responsavel_nome'=>$resp['nome_completo'], 'responsavel_cpf_cnpj'=>$resp['cpf_cnpj'],
            'responsavel_cargo'=>$resp['cargo_titulo'], 'responsavel_registro'=>$resp['registro_profissional'],
            'aprovador_nome'=>$userName, 'data_hora_formatada'=>$nowLocal->format('d/m/Y H:i:s') . ' (America/Sao_Paulo, UTC' . $nowLocal->format('P') . ')',
            'latitude'=>number_format((float)$latitude,8,'.',''), 'longitude'=>number_format((float)$longitude,8,'.',''),
            'geo_precisao_m'=>$precisao === false ? null : number_format((float)$precisao,2,'.',''), 'ip'=>$ip,
            'hash_pdf_original'=>$hashOriginal, 'assinatura_caminho_absoluto'=>$signatureAbs,
        ];
        aprovacaoPdfCriarComBloco($originalTmp, $visualTmp, $context);
        $provider = new AuditOnlyPdfSignatureProvider();
        $providerResult = $provider->sign($visualTmp, $finalTmp, $context);
        $hashFinal = hash_file('sha256', $finalTmp);
        if (!$hashFinal) throw new RuntimeException('Falha ao calcular o hash do PDF final.');

        if (!rename($finalTmp, $finalAbsolute) || !rename($originalTmp, $originalAbsolute)) {
            throw new RuntimeException('Falha ao persistir os PDFs aprovados.');
        }
        @unlink($visualTmp);

        $signatureBytes = file_get_contents($signatureAbs);
        if ($signatureBytes === false) throw new RuntimeException('Falha ao carregar a assinatura para compatibilidade.');
        $audit = [
            'aprovador_usuario_id'=>$userId, 'aprovado_em_local'=>$nowLocal->format('Y-m-d H:i:s'),
            'assinatura_imagem_data'=>'data:image/png;base64,'.base64_encode($signatureBytes), 'ip'=>$ip, 'hash_pdf_final'=>$hashFinal,
        ];
        $pdo->beginTransaction();
        $locked = aprovacaoDocumentoCarregar($pdo, $tipo, $id, true);
        aprovacaoDocumentoValidarEstado($tipo, $locked);
        $statusRelatorio = aprovacaoDocumentoFinalizarEstado(
            $pdo,
            $tipo,
            $id,
            $responsavelId,
            $audit,
            $finalRelative,
            $resultadoRelatorio,
            $versaoRelatorio
        );
        $update = $pdo->prepare("UPDATE documento_aprovacoes SET hash_pdf_original=:original, hash_pdf_final=:final, caminho_pdf_original=:caminho_original, caminho_pdf_final=:caminho_final, status='APROVADO', padrao_assinatura=:padrao, status_pades=:pades, provedor_assinatura=:provedor WHERE id=:id AND status='PROCESSANDO'");
        $update->execute([':original'=>$hashOriginal, ':final'=>$hashFinal, ':caminho_original'=>$originalRelative, ':caminho_final'=>$finalRelative, ':padrao'=>$providerResult['standard'], ':pades'=>$providerResult['pades_status'], ':provedor'=>$providerResult['provider'], ':id'=>$approvalId]);
        if ($update->rowCount() !== 1) throw new RuntimeException('A aprovacao perdeu seu estado de processamento.');
        $pdo->commit();

        if (function_exists('log_atividade')) log_atividade('documento_aprovado_eletronicamente', "{$mapa['label']} {$id} aprovado. Hash final: {$hashFinal}");
        return ['id'=>$approvalId, 'token'=>$token, 'validation_url'=>aprovacaoPdfUrlValidacao($token), 'hash_final'=>$hashFinal, 'status_relatorio'=>$statusRelatorio];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        @unlink($originalTmp); @unlink($visualTmp); @unlink($finalTmp); @unlink($originalAbsolute); @unlink($finalAbsolute);
        try {
            $stmtFail = $pdo->prepare("UPDATE documento_aprovacoes SET status='FALHA', erro_processamento=:erro WHERE id=:id AND status='PROCESSANDO'");
            $stmtFail->execute([':erro'=>substr($e->getMessage(),0,1000), ':id'=>$approvalId]);
        } catch (Throwable $ignored) {}
        throw $e;
    }
}

function cancelarAprovacaoDocumento(PDO $pdo, string $tipo, string $documentoId, string $motivo): void
{
    $tipo = strtoupper(trim($tipo));
    $motivo = trim($motivo);
    if ($documentoId === '' || $motivo === '') {
        throw new InvalidArgumentException('Documento e motivo do cancelamento sao obrigatorios.');
    }
    $mapa = aprovacaoDocumentoMapa($tipo);
    $pdo->beginTransaction();
    try {
        $documento = aprovacaoDocumentoCarregar($pdo, $tipo, $documentoId, true);
        $stmt = $pdo->prepare("SELECT id FROM documento_aprovacoes WHERE documento_tipo=:tipo AND documento_id=:documento AND status='APROVADO' ORDER BY versao DESC LIMIT 1 FOR UPDATE");
        $stmt->execute([':tipo'=>$tipo, ':documento'=>$documentoId]);
        $aprovacaoId = $stmt->fetchColumn();
        if (!$aprovacaoId || (!in_array($tipo, ['RELATORIO','PARECER_PLANOS'], true) && empty($documento['assinado']))) throw new RuntimeException('O documento nao possui aprovacao ativa para cancelar.');
        $erro = 'Cancelado por ' . (string)($_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Administrador') . ': ' . $motivo;
        $update = $pdo->prepare("UPDATE documento_aprovacoes SET status='CANCELADO', erro_processamento=:motivo WHERE id=:id AND status='APROVADO'");
        $update->execute([':motivo'=>substr($erro, 0, 1000), ':id'=>$aprovacaoId]);
        if ($update->rowCount() !== 1) throw new RuntimeException('A aprovacao foi alterada durante o cancelamento.');
        $sql = match ($tipo) {
            'RELATORIO' => "UPDATE vistorias SET status='CANCELADA' WHERE id=:id",
            'PARECER_PLANOS' => "UPDATE analise_planos_pareceres SET status='CANCELADO' WHERE id=:id AND status='PUBLICADO'",
            default => "UPDATE {$mapa['table']} SET status='cancelado' WHERE id=:id AND assinado=1",
        };
        $docUpdate = $pdo->prepare($sql);
        $docUpdate->execute([':id'=>$documentoId]);
        if ($docUpdate->rowCount() !== 1) throw new RuntimeException('Nao foi possivel cancelar o documento aprovado.');
        if ($tipo === 'PARECER_PLANOS') {
            $pdo->prepare("UPDATE analises_planos SET status='CANCELADA' WHERE id=:id")->execute([':id'=>$documento['analise_id']]);
        }
        $pdo->commit();
        if (function_exists('log_atividade')) log_atividade('documento_aprovacao_cancelada', "{$mapa['label']} {$documentoId}: {$motivo}");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
