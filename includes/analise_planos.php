<?php

function analisePlanosPodeGerenciar(): bool
{
    return estaLogado() && (temPerfil('ANALISTA') || getCargo() === 'ADMIN');
}

function analisePlanosExigirAcesso(): void
{
    verificar_sessao();
    if (!analisePlanosPodeGerenciar()) {
        setMensagem('error', 'Acesso negado ao módulo de Análise de Planos.');
        redirecionar(APP_URL . 'dashboard');
    }
}

function analisePlanosCarregar(PDO $pdo, string $id, bool $lock = false): array
{
    $stmt = $pdo->prepare("SELECT ap.*, e.nome AS embarcacao_nome, e.registro, e.numero_inscricao,
                                  c.nome AS solicitante_nome, u.nome AS analista_nome,
                                  ra.nome_completo AS responsavel_assinatura_nome
                             FROM analises_planos ap
                             INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id
                             LEFT JOIN clientes c ON c.id=ap.solicitante_id
                             LEFT JOIN usuarios u ON u.id=ap.analista_id
                             LEFT JOIN responsaveis_assinatura ra ON ra.id=ap.responsavel_assinatura_id
                            WHERE ap.id=:id" . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Análise de planos não encontrada.');
    if (getCargo() !== 'ADMIN' && $row['analista_id'] !== ($_SESSION['usuario_id'] ?? '')) {
        throw new RuntimeException('Esta análise está atribuída a outro analista.');
    }
    return $row;
}

function analisePlanosHistorico(PDO $pdo, string $analiseId, string $evento, ?string $anterior, ?string $novo, string $detalhe = ''): void
{
    $stmt = $pdo->prepare('INSERT INTO analise_planos_historico (analise_id, usuario_id, evento, status_anterior, status_novo, detalhe) VALUES (:analise,:usuario,:evento,:anterior,:novo,:detalhe)');
    $stmt->execute([
        ':analise' => $analiseId,
        ':usuario' => $_SESSION['usuario_id'],
        ':evento' => $evento,
        ':anterior' => $anterior,
        ':novo' => $novo,
        ':detalhe' => $detalhe ?: null,
    ]);
}

function analisePlanosTransicaoPermitida(string $atual, string $novo): bool
{
    $mapa = [
        'RASCUNHO' => ['EM_ANALISE', 'CANCELADA'],
        'EM_ANALISE' => ['AGUARDANDO_APROVACAO', 'CANCELADA'],
        'AGUARDANDO_CORRECAO' => ['EM_ANALISE', 'CANCELADA'],
        'AGUARDANDO_APROVACAO' => ['EM_ANALISE', 'CANCELADA'],
        'CONCLUIDA' => [], 'REPROVADA' => [], 'CANCELADA' => [],
    ];
    return in_array($novo, $mapa[$atual] ?? [], true);
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

