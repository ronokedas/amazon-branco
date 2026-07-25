<?php

function analisePlanosPodeGerenciar(): bool
{
    return podeAcessar('analise_planos');
}

function analisePlanosExigirAcesso(): void
{
    verificar_sessao();
    if (!analisePlanosPodeGerenciar()) {
        setMensagem('error', 'Acesso negado ao módulo de Análise de Planos.');
        redirecionar(APP_URL . 'dashboard');
    }
}

function analisePlanosTiposPermitidos(): array
{
    return ['LC', 'LCEC', 'LA', 'LR'];
}

function analisePlanosNormasPermitidas(): array
{
    return ['NORMAM-202'];
}

function analisePlanosEhLegadoForaEscopo(array $analise): bool
{
    return ($analise['enquadramento'] ?? null) === 'NORMAM-201'
        || (int)($analise['legado_fora_escopo'] ?? 0) === 1;
}

function analisePlanosExigirNormam202(array $analise): void
{
    if (analisePlanosEhLegadoForaEscopo($analise)) {
        throw new RuntimeException('Registro histórico NORMAM-201: disponível somente para consulta e auditoria.');
    }
    if (($analise['enquadramento'] ?? null) !== 'NORMAM-202') {
        throw new RuntimeException('A Amazon Naval aceita novos processos exclusivamente pela NORMAM-202.');
    }
}

function analisePlanosAvaliarAplicabilidade(array $analise, ?DateTimeImmutable $referencia = null): array
{
    if (($analise['enquadramento'] ?? null) !== 'NORMAM-202') {
        return ['permitido' => false, 'fundamento' => 'Somente a NORMAM-202 integra o escopo operacional da Amazon Naval.'];
    }
    if (!in_array((string)($analise['tipo_processo'] ?? ''), analisePlanosTiposPermitidos(), true)) {
        return ['permitido' => false, 'fundamento' => 'Tipo documental inválido.'];
    }
    if (($analise['classe_certificacao'] ?? '') === 'EC1') {
        return ['permitido' => true, 'fundamento' => 'Embarcação enquadrada como EC1 na NORMAM-202.'];
    }
    $tipo = mb_strtoupper((string)($analise['embarcacao_tipo'] ?? $analise['tipo_embarcacao'] ?? ''), 'UTF-8');
    $ab = (float)($analise['arqueacao_bruta'] ?? 0);
    $rebocador = str_contains($tipo, 'REBOCADOR') || str_contains($tipo, 'EMPURRADOR');
    $referencia ??= new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    if ($rebocador && $ab >= 20 && $ab <= 50 && $referencia >= new DateTimeImmutable('2026-11-01', new DateTimeZone('America/Sao_Paulo'))) {
        return ['permitido' => true, 'fundamento' => 'Regra NORMAM-202 para rebocador/empurrador com AB de 20 a 50, vigente desde 01/11/2026.'];
    }
    return ['permitido' => false, 'fundamento' => 'Embarcação EC2 dispensada de LC, LCEC, LA e LR pela NORMAM-202.'];
}

function analisePlanosStatusAtivos(): array
{
    return ['AGUARDANDO_AGENDAMENTO', 'AGENDADA', 'EM_ANALISE', 'AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'AGUARDANDO_APROVACAO_ADMIN'];
}

function analisePlanosUsuarioPodeVisualizar(array $analise): bool
{
    $cargo = getCargo();
    $usuario = (string)($_SESSION['usuario_id'] ?? '');
    if ($cargo === 'ADMIN') return true;
    if ($cargo === 'VENDEDOR') return $usuario !== '' && hash_equals($usuario, (string)($analise['vendedor_origem_id'] ?? ''));
    if ($cargo === 'ANALISTA') return $usuario !== '' && hash_equals($usuario, (string)($analise['analista_id'] ?? ''));
    return false;
}

function analisePlanosNotificar(PDO $pdo, ?string $usuarioId, string $evento, string $titulo, string $mensagem, ?string $referenciaId = null, ?string $url = null): void
{
    if (!$usuarioId) return;
    $stmt = $pdo->prepare("INSERT INTO notificacoes
        (id,usuario_id,evento,titulo,mensagem,referencia_tipo,referencia_id,url)
        VALUES (:id,:usuario,:evento,:titulo,:mensagem,'ANALISE_PLANOS',:referencia,:url)");
    $stmt->execute([
        ':id' => gerarUUID(), ':usuario' => $usuarioId, ':evento' => $evento,
        ':titulo' => mb_substr($titulo, 0, 180), ':mensagem' => mb_substr($mensagem, 0, 500),
        ':referencia' => $referenciaId, ':url' => $url,
    ]);
}

function analisePlanosNotificarAdmins(PDO $pdo, string $evento, string $titulo, string $mensagem, string $analiseId): void
{
    $ids = $pdo->query("SELECT id FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND cargo='ADMIN'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        analisePlanosNotificar($pdo, (string)$id, $evento, $titulo, $mensagem, $analiseId, 'analises-planos/form?id=' . urlencode($analiseId));
    }
}

function analisePlanosChecklist(string $tipo, string $norma, string $classe): array
{
    if (!in_array($tipo, analisePlanosTiposPermitidos(), true) || !in_array($norma, analisePlanosNormasPermitidas(), true)) return [];
    $base = [
        ['Requerimento do interessado', "{$norma}, Cap. 3", true],
        ['Anotação de Responsabilidade Técnica (ART)', "{$norma}, Cap. 3", true],
    ];
    if (in_array($tipo, ['LC', 'LCEC'], true)) {
        $base = array_merge($base, [
            ['Memorial Descritivo', "{$norma}, Anexo 3-G", true],
            ['Plano de Arranjo Geral', "{$norma}, Anexo 3-F", true],
            ['Plano de Linhas', "{$norma}, Anexo 3-F", true],
            ['Curvas Hidrostáticas e Cruzadas', "{$norma}, Anexo 3-F", true],
            ['Plano de Segurança', "{$norma}, Anexo 3-F", true],
            ['Plano de Arranjo de Luzes de Navegação', "{$norma}, Anexo 3-F", true],
            ['Plano de Capacidade', "{$norma}, Anexo 3-F", true],
            ['Plano de Seção Mestra e Perfil Estrutural', "{$norma}, Anexo 3-F", true],
            ['Relatório de Prova de Inclinação ou Medição de Porte Bruto', "{$norma}, Cap. 3", true],
            ['Folheto de Trim e Estabilidade', "{$norma}, Cap. 3", true],
            ['Proposta de Cartão de Tripulação de Segurança', "{$norma}, Cap. 3", false],
        ]);
    } elseif ($tipo === 'LA') {
        $base = array_merge($base, [
            ['Relatório da natureza e extensão das alterações', "{$norma}, Cap. 3 - Licença de Alteração", true],
            ['Planos e documentos anteriormente endossados', "{$norma}, Cap. 3 - Licença de Alteração", true],
            ['Novos planos e documentos modificados', "{$norma}, Cap. 3 - Licença de Alteração", true],
        ]);
    } else {
        $base = array_merge($base, [
            ['Memorial Descritivo da nova classificação', "{$norma}, Cap. 3 - Reclassificação", true],
            ['Declaração das condições de carregamento', "{$norma}, Anexo 3-H", true],
            ['Planos alterados pela nova classificação', "{$norma}, Cap. 3 - Reclassificação", false],
        ]);
    }
    if ($classe === 'EC2') {
        foreach ($base as &$item) {
            if (str_contains($item[0], 'Curvas Hidrostáticas') || str_contains($item[0], 'Seção Mestra')) $item[2] = false;
        }
        unset($item);
    }
    return $base;
}

function analisePlanosSemearChecklist(PDO $pdo, string $analiseId, string $tipo, string $norma, string $classe, string $usuarioId): void
{
    $count = $pdo->prepare('SELECT COUNT(*) FROM analise_planos_itens WHERE analise_id=:id');
    $count->execute([':id' => $analiseId]);
    if ((int)$count->fetchColumn() > 0) return;
    $stmt = $pdo->prepare("INSERT INTO analise_planos_itens
        (id,analise_id,ordem,documento,referencia_normativa,versao_normativa,obrigatorio,aplicavel,impeditivo_emissao,criado_por)
        VALUES (UUID(),:analise,:ordem,:documento,:referencia,'REV.1',:obrigatorio,1,:impeditivo,:usuario)");
    foreach (analisePlanosChecklist($tipo, $norma, $classe) as $i => $item) {
        $stmt->execute([
            ':analise' => $analiseId, ':ordem' => $i + 1, ':documento' => $item[0],
            ':referencia' => $item[1], ':obrigatorio' => $item[2] ? 1 : 0,
            ':impeditivo' => $item[2] ? 1 : 0, ':usuario' => $usuarioId,
        ]);
    }
}

function analisePlanosCriarDemandasProposta(PDO $pdo, array $proposta, ?string $criadoPor = null): int
{
    $stmt = $pdo->prepare("SELECT ps.servico_id,ps.embarcacao_id,s.codigo_operacional
        FROM propostas_servicos ps INNER JOIN servicos s ON s.id=ps.servico_id
        WHERE ps.proposta_id=:proposta
          AND s.codigo_operacional IN ('ANALISE_PLANOS_EC1','ANALISE_PLANOS_EC2')
          AND ps.embarcacao_id IS NOT NULL");
    $stmt->execute([':proposta' => $proposta['id']]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$servicos) return 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO analises_planos
        (id,numero,proposta_id,servico_id,vendedor_origem_id,embarcacao_id,solicitante_id,
         tipo_processo,enquadramento,classe_certificacao,objeto,analista_id,status,criado_por)
        VALUES (:id,:numero,:proposta,:servico,:vendedor,:embarcacao,:cliente,
                NULL,NULL,:classe,:objeto,NULL,'AGUARDANDO_AGENDAMENTO',:usuario)");
    $criados = 0;
    foreach ($servicos as $servico) {
        $numero = gerarNumeroDocumento('RAP', 'AM-RAP');
        $id = gerarUUID();
        $classe = $servico['codigo_operacional'] === 'ANALISE_PLANOS_EC1' ? 'EC1' : 'EC2';
        $insert->execute([
            ':id' => $id, ':numero' => $numero, ':proposta' => $proposta['id'],
            ':servico' => $servico['servico_id'], ':vendedor' => $proposta['criado_por'] ?? null,
            ':embarcacao' => $servico['embarcacao_id'], ':cliente' => $proposta['cliente_id'],
            ':classe' => $classe, ':objeto' => 'Análise de planos ' . $classe,
            ':usuario' => $criadoPor ?: ($proposta['criado_por'] ?? null),
        ]);
        if ($insert->rowCount() === 1) {
            $criados++;
            analisePlanosHistorico($pdo, $id, 'DEMANDA_CRIADA', null, 'AGUARDANDO_AGENDAMENTO', 'Criada automaticamente pela proposta ' . ($proposta['numero'] ?? ''));
            analisePlanosNotificar($pdo, $proposta['criado_por'] ?? null, 'ANALISE_AGUARDANDO_AGENDAMENTO', 'Análise aguardando agendamento', 'A proposta ' . ($proposta['numero'] ?? '') . ' gerou uma demanda ' . $classe . '.', $id, 'analises-planos/form?id=' . urlencode($id));
        }
    }
    return $criados;
}

function analisePlanosCarregar(PDO $pdo, string $id, bool $lock = false): array
{
    $stmt = $pdo->prepare("SELECT ap.*, e.nome AS embarcacao_nome, e.registro, e.numero_inscricao,
                                  c.nome AS solicitante_nome, u.nome AS analista_nome,
                                  ra.nome_completo AS responsavel_assinatura_nome,
                                  p.numero AS proposta_numero, vo.nome AS vendedor_origem_nome,
                                  s.nome AS servico_nome, e.tipo AS embarcacao_tipo
                             FROM analises_planos ap
                             INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
                             LEFT JOIN clientes c ON c.id=ap.solicitante_id
                             LEFT JOIN usuarios u ON u.id=ap.analista_id
                             LEFT JOIN responsaveis_assinatura ra ON ra.id=ap.responsavel_assinatura_id
                             LEFT JOIN propostas p ON p.id=ap.proposta_id
                             LEFT JOIN usuarios vo ON vo.id=ap.vendedor_origem_id
                             LEFT JOIN servicos s ON s.id=ap.servico_id
                            WHERE ap.id=:id" . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Análise de planos não encontrada.');
    if (!analisePlanosUsuarioPodeVisualizar($row)) throw new RuntimeException('Você não possui acesso a esta análise.');
    return $row;
}

function analisePlanosHistorico(PDO $pdo, string $analiseId, string $evento, ?string $anterior, ?string $novo, string $detalhe = '', ?string $usuarioId = null): void
{
    $usuarioId = $usuarioId ?: ($_SESSION['usuario_id'] ?? null);
    if (!$usuarioId) {
        $q = $pdo->prepare('SELECT criado_por FROM analises_planos WHERE id=:id');
        $q->execute([':id' => $analiseId]);
        $usuarioId = $q->fetchColumn() ?: null;
    }
    if (!$usuarioId) return;
    $stmt = $pdo->prepare('INSERT INTO analise_planos_historico (analise_id, usuario_id, evento, status_anterior, status_novo, detalhe) VALUES (:analise,:usuario,:evento,:anterior,:novo,:detalhe)');
    $stmt->execute([
        ':analise' => $analiseId,
        ':usuario' => $usuarioId,
        ':evento' => $evento,
        ':anterior' => $anterior,
        ':novo' => $novo,
        ':detalhe' => $detalhe ?: null,
    ]);
}

function analisePlanosAuditarNorma(PDO $pdo, string $analiseId, string $evento, ?string $anterior, ?string $novo, string $fundamento = ''): void
{
    $stmt = $pdo->prepare("INSERT INTO auditoria_fluxo_normativo
        (entidade,entidade_id,evento,usuario_id,perfil,ip,estado_anterior,estado_novo,norma_versao_id,fundamento)
        SELECT 'ANALISE_PLANOS',ap.id,:evento,:usuario,:perfil,:ip,:anterior,:novo,ap.norma_versao_id,:fundamento
        FROM analises_planos ap WHERE ap.id=:id");
    $stmt->execute([
        ':evento'=>$evento, ':usuario'=>$_SESSION['usuario_id']??null,
        ':perfil'=>function_exists('getCargo')?getCargo():null,
        ':ip'=>function_exists('obterIpCliente')?obterIpCliente():($_SERVER['REMOTE_ADDR']??null),
        ':anterior'=>$anterior, ':novo'=>$novo, ':fundamento'=>$fundamento?:null, ':id'=>$analiseId,
    ]);
}

function analisePlanosTransicaoPermitida(string $atual, string $novo): bool
{
    $mapa = [
        'AGUARDANDO_AGENDAMENTO' => ['AGENDADA', 'CANCELADA'],
        'AGENDADA' => ['EM_ANALISE', 'CANCELADA'],
        'EM_ANALISE' => ['AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'REPROVADA', 'CANCELADA'],
        'AGUARDANDO_DOCUMENTOS' => ['EM_ANALISE', 'CANCELADA'],
        'AGUARDANDO_ASSINATURA_ANALISTA' => ['AGUARDANDO_APROVACAO_ADMIN', 'EM_ANALISE', 'CANCELADA'],
        'AGUARDANDO_APROVACAO_ADMIN' => ['EM_ANALISE', 'CONCLUIDA', 'CANCELADA'],
        'CONCLUIDA' => [], 'REPROVADA' => [], 'CANCELADA' => [],
    ];
    return in_array($novo, $mapa[$atual] ?? [], true);
}

function analisePlanosSaldoExigencias(PDO $pdo, string $analiseId): array
{
    $stmt = $pdo->prepare("SELECT status,COUNT(*) quantidade
        FROM analise_planos_exigencias WHERE analise_id=:id GROUP BY status");
    $stmt->execute([':id' => $analiseId]);
    $saldo = ['total'=>0,'cumpridas'=>0,'pendentes'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $quantidade = (int)$row['quantidade'];
        $saldo['total'] += $quantidade;
        if ($row['status'] === 'CUMPRIDA') $saldo['cumpridas'] += $quantidade;
        else $saldo['pendentes'] += $quantidade;
    }
    return $saldo;
}

function analisePlanosValidarConclusao(PDO $pdo, string $analiseId): void
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_itens
        WHERE analise_id=:id AND aplicavel=1 AND impeditivo_emissao=1
          AND resultado NOT IN ('CONFORME','NAO_APLICA')");
    $q->execute([':id'=>$analiseId]);
    if ((int)$q->fetchColumn() > 0) {
        throw new RuntimeException('Existem itens impeditivos ainda não conformes.');
    }
    $saldo = analisePlanosSaldoExigencias($pdo, $analiseId);
    if ($saldo['pendentes'] > 0) {
        throw new RuntimeException('A licença permanece bloqueada até todas as exigências serem cumpridas.');
    }
    $q = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_arquivos ar
        INNER JOIN analise_planos_submissoes s ON s.id=ar.submissao_id
        WHERE s.analise_id=:id AND ar.classificacao IN ('RECEBIDO','REJEITADO')");
    $q->execute([':id'=>$analiseId]);
    if ((int)$q->fetchColumn() > 0) {
        throw new RuntimeException('Existem arquivos recebidos ou rejeitados aguardando resolução.');
    }
}

function analisePlanosSnapshot(PDO $pdo, array $analise, string $submissaoId): array
{
    $q=$pdo->prepare('SELECT id,ordem,documento,referencia_normativa,versao_normativa,obrigatorio,aplicavel,impeditivo_emissao,resultado,observacao FROM analise_planos_itens WHERE analise_id=:id ORDER BY ordem,id');
    $q->execute([':id'=>$analise['id']]);
    $itens=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare('SELECT ar.id,ar.nome_original,ar.mime_type,ar.tamanho_bytes,ar.sha256,ar.classificacao,ar.item_id FROM analise_planos_arquivos ar WHERE ar.submissao_id=:id ORDER BY ar.criado_em,ar.id');
    $q->execute([':id'=>$submissaoId]);
    $arquivos=$q->fetchAll(PDO::FETCH_ASSOC);
    return [
        'processo_numero'=>$analise['numero'],
        'tipo_processo'=>$analise['tipo_processo'],
        'enquadramento'=>$analise['enquadramento'],
        'norma_versao_id'=>$analise['norma_versao_id'] ?? null,
        'classe_certificacao'=>$analise['classe_certificacao'],
        'submissao_id'=>$submissaoId,
        'matriz'=>$itens,
        'arquivos'=>$arquivos,
        'gerado_em'=>date(DATE_ATOM),
    ];
}

function analisePlanosS3(): ?\Aws\S3\S3Client
{
    if (!class_exists('Aws\\S3\\S3Client')) return null;
    return new Aws\S3\S3Client([
        'version' => 'latest', 'region' => 'us-east-1',
        'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => defined('MINIO_ACCESS_KEY') ? MINIO_ACCESS_KEY : 'erp_minio_admin',
            'secret' => defined('MINIO_SECRET_KEY') ? MINIO_SECRET_KEY : 'erp_minio_pass_2026',
        ],
    ]);
}

function analisePlanosBucket(): string
{
    return defined('MINIO_PLANOS_BUCKET') ? MINIO_PLANOS_BUCKET : 'erp-planos-private';
}

function analisePlanosValidarUpload(array $arquivo): array
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($arquivo['tmp_name'])) {
        throw new RuntimeException('Selecione um arquivo válido.');
    }
    $tamanho = (int)($arquivo['size'] ?? 0);
    if ($tamanho < 1 || $tamanho > 50 * 1024 * 1024) throw new RuntimeException('O arquivo deve ter no máximo 50 MB.');

    $nome = basename((string)($arquivo['name'] ?? 'arquivo'));
    $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    $permitidas = ['pdf','jpg','jpeg','png','dwg','dxf','doc','docx','xls','xlsx'];
    if (!in_array($ext, $permitidas, true)) throw new RuntimeException('Formato não permitido. Use PDF, imagem, DWG/DXF, Word ou Excel sem macros.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($arquivo['tmp_name']);
    $porExtensao = [
        'pdf'=>['application/pdf'], 'jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'],
        'dwg'=>['application/acad','application/x-acad','application/autocad_dwg','image/vnd.dwg','application/octet-stream'],
        'dxf'=>['image/vnd.dxf','application/dxf','application/x-dxf','text/plain','application/octet-stream'],
        'doc'=>['application/msword','application/octet-stream'],
        'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'],
        'xls'=>['application/vnd.ms-excel','application/octet-stream'],
        'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'],
    ];
    if (!in_array($mime, $porExtensao[$ext], true)) throw new RuntimeException('O conteúdo do arquivo não corresponde à extensão informada.');
    $inicio = file_get_contents($arquivo['tmp_name'], false, null, 0, 4096) ?: '';
    if (preg_match('/<\?(?:php|=)|<script\b|MZ\x90/i', $inicio)) throw new RuntimeException('O arquivo contém conteúdo executável e foi bloqueado.');

    return ['nome' => mb_substr($nome, 0, 255), 'extensao' => $ext, 'mime' => $mime, 'tamanho' => $tamanho, 'sha256' => hash_file('sha256', $arquivo['tmp_name'])];
}

function analisePlanosGuardarUpload(array $arquivo, string $analiseId, array $meta): string
{
    $chave = 'analises-planos/' . $analiseId . '/' . gerarUUID() . '.' . $meta['extensao'];
    $s3 = analisePlanosS3();
    if ($s3) {
        $bucket = analisePlanosBucket();
        try { $s3->headBucket(['Bucket' => $bucket]); }
        catch (Throwable $e) { $s3->createBucket(['Bucket' => $bucket]); }
        $s3->putObject(['Bucket'=>$bucket, 'Key'=>$chave, 'SourceFile'=>$arquivo['tmp_name'], 'ContentType'=>$meta['mime']]);
        return $chave;
    }
    $destino = __DIR__ . '/../storage/private/' . $chave;
    $dir = dirname($destino);
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) throw new RuntimeException('Falha ao preparar armazenamento privado.');
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) throw new RuntimeException('Falha ao armazenar o arquivo.');
    return 'local:' . $chave;
}

function analisePlanosEmitirArquivo(array $registro, bool $download = false): never
{
    $disposicao = $download || !in_array($registro['extensao'], ['pdf','jpg','jpeg','png'], true) ? 'attachment' : 'inline';
    header('Content-Type: ' . $registro['mime_type']);
    header('Content-Disposition: ' . $disposicao . '; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $registro['nome_original']) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    $chave = (string)$registro['chave_arquivo'];
    if (str_starts_with($chave, 'local:')) {
        $path = __DIR__ . '/../storage/private/' . substr($chave, 6);
        if (!is_file($path) || !hash_equals($registro['sha256'], hash_file('sha256', $path))) {
            http_response_code(404); die('Arquivo não encontrado ou inválido.');
        }
        header('Content-Length: ' . filesize($path)); readfile($path); exit;
    }
    $s3 = analisePlanosS3();
    if (!$s3) { http_response_code(503); die('Armazenamento indisponível.'); }
    $obj = $s3->getObject(['Bucket'=>analisePlanosBucket(), 'Key'=>$chave]);
    echo $obj['Body']; exit;
}

function analisePlanosCategoriasPadrao(): array
{
    return ['Memorial Descritivo','Arranjo Geral','Plano de Linhas','Seção Mestra e Perfil Estrutural','Curvas Hidrostáticas','Estabilidade','Capacidade','Segurança','ART','Outros'];
}
