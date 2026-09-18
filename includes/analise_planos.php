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

    // Buscar automaticamente o Analista Naval ativo do sistema
    $stmtAnalista = $pdo->query("
        SELECT DISTINCT u.id, u.nome, u.email
        FROM usuarios u
        LEFT JOIN usuario_perfis up ON up.usuario_id = u.id
        WHERE u.ativo = 1
          AND u.excluido_em IS NULL
          AND (u.cargo = 'ANALISTA' OR up.perfil = 'ANALISTA')
        ORDER BY u.id ASC
        LIMIT 1
    ");
    $analistaPadrao = $stmtAnalista->fetch(PDO::FETCH_ASSOC) ?: null;
    $analistaId = $analistaPadrao['id'] ?? null;
    $statusInicial = $analistaId ? 'AGENDADA' : 'AGUARDANDO_AGENDAMENTO';
    $prazoInicial = $analistaId ? date('Y-m-d 18:00:00', strtotime('+7 days')) : null;

    $insert = $pdo->prepare("INSERT IGNORE INTO analises_planos
        (id,numero,proposta_id,servico_id,vendedor_origem_id,embarcacao_id,solicitante_id,
         tipo_processo,enquadramento,classe_certificacao,objeto,analista_id,prazo_agendado_em,status,criado_por)
        VALUES (:id,:numero,:proposta,:servico,:vendedor,:embarcacao,:cliente,
                'LC','NORMAM-202',:classe,:objeto,:analista_id,:prazo,:status,:usuario)");
    $criados = 0;
    foreach ($servicos as $servico) {
        $numero = gerarNumeroDocumento('RAP', 'AM-RAP');
        $id = gerarUUID();
        $classe = $servico['codigo_operacional'] === 'ANALISE_PLANOS_EC1' ? 'EC1' : 'EC2';
        $usuarioOrigem = $criadoPor ?: ($proposta['criado_por'] ?? null);

        $insert->execute([
            ':id' => $id, ':numero' => $numero, ':proposta' => $proposta['id'],
            ':servico' => $servico['servico_id'], ':vendedor' => $proposta['criado_por'] ?? null,
            ':embarcacao' => $servico['embarcacao_id'], ':cliente' => $proposta['cliente_id'],
            ':classe' => $classe, ':objeto' => 'Análise de planos ' . $classe,
            ':analista_id' => $analistaId,
            ':prazo' => $prazoInicial,
            ':status' => $statusInicial,
            ':usuario' => $usuarioOrigem,
        ]);
        if ($insert->rowCount() === 1) {
            $criados++;
            analisePlanosHistorico($pdo, $id, 'DEMANDA_CRIADA', null, $statusInicial, 'Criada automaticamente pela proposta ' . ($proposta['numero'] ?? ''));

            if ($analistaId) {
                analisePlanosHistorico($pdo, $id, 'ANALISTA_ATRIBUIDO', null, $statusInicial, 'Atribuído automaticamente ao Analista Naval ' . ($analistaPadrao['nome'] ?? ''));
                $pdo->prepare("INSERT INTO analise_planos_agenda_historico (analise_id, analista_anterior_id, analista_novo_id, prazo_anterior_em, prazo_novo_em, motivo, acao, criado_por)
                               VALUES (?, NULL, ?, NULL, ?, 'Atribuição automática pela assinatura da proposta', 'AGENDAMENTO', ?)")
                    ->execute([$id, $analistaId, $prazoInicial, $usuarioOrigem]);
                analisePlanosNotificar($pdo, $analistaId, 'NOVA_ANALISE_ATRIBUIDA', 'Nova Análise de Planos atribuída', 'A proposta ' . ($proposta['numero'] ?? '') . ' gerou a análise ' . $classe . ' (' . $numero . ') sob sua responsabilidade técnica.', $id, 'analises-planos/form?id=' . urlencode($id));
            } else {
                analisePlanosNotificar($pdo, $proposta['criado_por'] ?? null, 'ANALISE_AGUARDANDO_AGENDAMENTO', 'Análise aguardando agendamento', 'A proposta ' . ($proposta['numero'] ?? '') . ' gerou uma demanda ' . $classe . '.', $id, 'analises-planos/form?id=' . urlencode($id));
            }

            if (function_exists('analisePlanosSemearChecklist')) {
                analisePlanosSemearChecklist($pdo, $id, 'LC', 'NORMAM-202', $classe, $usuarioOrigem ?: ($analistaId ?: '00000000-0000-0000-0000-000000000001'));
            }
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
        'EM_ANALISE' => ['AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'CONCLUIDA', 'REPROVADA', 'CANCELADA'],
        'AGUARDANDO_DOCUMENTOS' => ['EM_ANALISE', 'CONCLUIDA', 'REPROVADA', 'CANCELADA'],
        'AGUARDANDO_ASSINATURA_ANALISTA' => ['AGUARDANDO_APROVACAO_ADMIN', 'CONCLUIDA', 'AGUARDANDO_DOCUMENTOS', 'REPROVADA', 'EM_ANALISE', 'CANCELADA'],
        'AGUARDANDO_APROVACAO_ADMIN' => ['EM_ANALISE', 'CONCLUIDA', 'AGUARDANDO_DOCUMENTOS', 'REPROVADA', 'CANCELADA'],
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
    if (!@move_uploaded_file($arquivo['tmp_name'], $destino) && !copy($arquivo['tmp_name'], $destino)) throw new RuntimeException('Falha ao armazenar o arquivo.');
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
    return [
        'ART',
        'FOLHA DE ROSTO',
        'DECLARAÇÃO',
        'MEMORIAL DESCRITO',
        'NOTAS DE ARQUEAÇÃO',
        'NOTAS DE BORDA LIVRE',
        'DADOS DE ENTRADA OU COTAS',
        'CURVAS HIDROSTÁTICAS',
        'CURVAS CRUZADAS',
        'PROVA DE INCLINAÇÃO OU PORTE BRUTO',
        'ESTUDO DE ESTABILIDADE',
        'ESTUDO DE CARGA X CALADOS',
        'MOMENTO FLETOR E ESFORÇO CORTANTE',
        'PLANOS DE LINHAS',
        'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.',
        'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA.',
        'OUTROS',
    ];
}

function analiseAcaoExigirTecnico(array $analise): void
{
    $cargo = getCargo();
    $usuario = (string)($_SESSION['usuario_id'] ?? '');
    if (!($cargo === 'ANALISTA' && $analise['analista_id'] === $usuario) && $cargo !== 'ADMIN') {
        throw new RuntimeException('Somente o analista atribuído ou Administrador pode executar esta ação técnica.');
    }
    if (analisePlanosEhLegadoForaEscopo($analise)) {
        throw new RuntimeException('Processo NORMAM-201 legado: conteúdo preservado somente para consulta.');
    }
}

function analiseAcaoResponsavelDoAnalista(PDO $pdo, array $analise): array
{
    $stmt = $pdo->prepare("SELECT ra.* FROM responsaveis_assinatura ra
        WHERE ra.usuario_id=:usuario AND ra.ativo=1
          AND ra.cpf_cnpj IS NOT NULL AND ra.cpf_cnpj<>''
          AND ra.assinatura_arquivo IS NOT NULL AND ra.assinatura_arquivo<>''
          AND ra.assinatura_hash IS NOT NULL AND ra.assinatura_hash<>''
        LIMIT 1");
    $stmt->execute([':usuario' => $analise['analista_id']]);
    $responsavel = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$responsavel) throw new RuntimeException('O analista precisa ter identidade técnica e assinatura válidas vinculadas à própria conta.');
    return $responsavel;
}

function analiseAcaoCriarLicenca(PDO $pdo, array $analise, array $responsavel): string
{
    analisePlanosExigirNormam202($analise);
    analisePlanosValidarConclusao($pdo, (string)$analise['id']);
    $aplicabilidade = analisePlanosAvaliarAplicabilidade($analise);
    if (!$aplicabilidade['permitido']) {
        throw new RuntimeException('Licença bloqueada: ' . $aplicabilidade['fundamento']);
    }
    $ultimo = $pdo->prepare("SELECT numero FROM analise_planos_pareceres
        WHERE analise_id=:id AND finalidade='CONCLUSIVO' AND resultado='APROVADO'
          AND status='PUBLICADO' ORDER BY versao DESC LIMIT 1");
    $ultimo->execute([':id'=>$analise['id']]);
    $relatorioConclusivo = $ultimo->fetchColumn();
    if (!$relatorioConclusivo) {
        throw new RuntimeException('A licença exige o último relatório conclusivo validado.');
    }
    $existente = $pdo->prepare('SELECT id FROM certificados_lc WHERE analise_id=:id LIMIT 1 FOR UPDATE');
    $existente->execute([':id' => $analise['id']]);
    $idExistente = $existente->fetchColumn();
    if ($idExistente) return (string)$idExistente;

    $tipo = (string)$analise['tipo_processo'];
    $tipoSequencial = $tipo === 'LCEC' ? 'EC' : $tipo;
    $numero = gerarNumeroDocumento($tipoSequencial, $tipo === 'LCEC' ? 'AM-EC' : 'AM-' . $tipo);
    $id = gerarUUID();
    $stmt = $pdo->prepare("SELECT e.*,c.nome proprietario_nome,c.cpf_cnpj proprietario_documento,c.endereco proprietario_endereco
        FROM embarcacoes e LEFT JOIN clientes c ON c.id=:cliente WHERE e.id=:embarcacao LIMIT 1");
    $stmt->execute([':cliente'=>$analise['solicitante_id'], ':embarcacao'=>$analise['embarcacao_id']]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $insert = $pdo->prepare("INSERT INTO certificados_lc
        (id,numero_lc,embarcacao_id,cliente_id,token_assinatura,tipo_licenca,nome_embarcacao,tipo_embarcacao,
         numero_casco,material_casco,porte_bruto,numero_passageiros,tipo_navegacao,propulsao,
         proprietario_nome,proprietario_cpf_cnpj,proprietario_endereco,estaleiro_nome,
         data_emissao,local_emissao,relatorio_numero,responsavel_assinatura_id,status,ativo,criado_por,
         vistoria_id,analise_id,dados_json)
        VALUES (:id,:numero,:embarcacao,:cliente,:token,:tipo,:nome,:tipo_embarcacao,:casco,:material,:porte,
                :passageiros,:navegacao,:propulsao,:proprietario,:documento,:endereco,:estaleiro,
                CURDATE(),'Belém-PA',:relatorio,:responsavel,'emitido',1,:usuario,NULL,:analise,:dados)");
    $insert->execute([
        ':id'=>$id, ':numero'=>$numero, ':embarcacao'=>$analise['embarcacao_id'],
        ':cliente'=>$analise['solicitante_id'] ?? null,
        ':token'=>bin2hex(random_bytes(32)), ':tipo'=>$tipo,
        ':nome'=>$dados['nome'] ?? $analise['embarcacao_nome'], ':tipo_embarcacao'=>$dados['tipo'] ?? null,
        ':casco'=>$analise['numero_casco'] ?: ($dados['numero_casco'] ?? null),
        ':material'=>$dados['material_casco'] ?? null, ':porte'=>$dados['porte_bruto'] ?? null,
        ':passageiros'=>$analise['numero_passageiros'], ':navegacao'=>$analise['tipo_navegacao'],
        ':propulsao'=>$analise['possui_propulsao'] === null ? null : ((int)$analise['possui_propulsao'] ? 'Com propulsão' : 'Sem propulsão'),
        ':proprietario'=>$dados['proprietario_nome'] ?? null, ':documento'=>$dados['proprietario_documento'] ?? null,
        ':endereco'=>$dados['proprietario_endereco'] ?? null, ':estaleiro'=>$analise['estaleiro'],
        ':relatorio'=>mb_substr($analise['numero'].' + '.$relatorioConclusivo, 0, 100), ':responsavel'=>$responsavel['id'],

        ':usuario'=>$_SESSION['usuario_id'] ?? null, ':analise'=>$analise['id'],
        ':dados'=>json_encode(['normam'=>$analise['enquadramento'],'classe'=>$analise['classe_certificacao']], JSON_UNESCAPED_UNICODE),
    ]);
    return $id;
}

function analiseAcaoPersistirParecerPdf(PDO $pdo, string $parecerId, string $analiseId): array
{
    $ano = date('Y');
    $relativo = 'storage/documentos_aprovados/' . $ano . '/parecer_planos/' . $parecerId . '.pdf';
    $absoluto = __DIR__ . '/../' . $relativo;
    $dir = dirname($absoluto);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        @chmod($dir, 0775);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        @chmod($dir, 0775);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        throw new RuntimeException('Não foi possível preparar o armazenamento do parecer (diretório sem permissão de escrita).');
    }
    $oldGet = $_GET;
    $_GET = ['id' => $parecerId];
    $salvar_pdf_caminho = $absoluto;
    ob_start();
    require __DIR__ . '/../modules/analises_planos/parecer_pdf.php';
    ob_end_clean();
    $_GET = $oldGet;
    if (!is_file($absoluto) || filesize($absoluto) < 200) throw new RuntimeException('Não foi possível gerar o PDF definitivo do parecer.');
    $hash = hash_file('sha256', $absoluto);
    $pdo->prepare('UPDATE analise_planos_pareceres SET caminho_pdf_final=:caminho,hash_pdf_final=:hash WHERE id=:id AND analise_id=:analise')
        ->execute([':caminho'=>$relativo, ':hash'=>$hash, ':id'=>$parecerId, ':analise'=>$analiseId]);
    return [$relativo, $hash];
}

function analiseAcaoFinalizarParecer(PDO $pdo, array $analise, array $parecer, array $responsavel, string $usuario): string
{
    $analiseId = (string)$analise['id'];
    $parecerId = (string)$parecer['id'];
    $resultado = $parecer['resultado'] ?? 'APROVADO';
    $novoStatus = match($resultado) {
        'EXIGENCIAS' => 'AGUARDANDO_DOCUMENTOS',
        'REPROVADO' => 'REPROVADA',
        default => 'CONCLUIDA'
    };

    // Baixa das exigências registradas no parecer
    $resultados = $pdo->prepare('SELECT exigencia_id, resultado FROM analise_planos_relatorio_exigencias WHERE relatorio_id=:id');
    $resultados->execute([':id' => $parecerId]);
    $updEx = $pdo->prepare("UPDATE analise_planos_exigencias SET status=:status, saneamento_pendente=0, observacao_cumprimento=CONCAT(COALESCE(observacao_cumprimento,''), :nota) WHERE id=:id AND analise_id=:analise");
    foreach ($resultados->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $updEx->execute([
            ':status' => $r['resultado'] === 'NAO_CUMPRIDA' ? 'NAO_CUMPRIDA' : $r['resultado'],
            ':nota' => "\nBaixa registrada no relatório " . ($parecer['numero'] ?? '') . '.',
            ':id' => $r['exigencia_id'],
            ':analise' => $analiseId
        ]);
    }

    // Publica o parecer e registra assinatura técnica do analista
    $ip = function_exists('obterIpCliente') ? obterIpCliente() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $pdo->prepare("UPDATE analise_planos_pareceres 
        SET status='PUBLICADO', 
            assinado_analista_em=COALESCE(assinado_analista_em, NOW()), 
            assinatura_analista_ip=:ip, 
            publicado_em=COALESCE(publicado_em, NOW()), 
            validado_em=COALESCE(validado_em, NOW()), 
            validado_por=:usuario 
        WHERE id=:id")
        ->execute([
            ':ip' => $ip,
            ':usuario' => $usuario,
            ':id' => $parecerId
        ]);

    // Se aprovado, valida conclusão NORMAM e gera Licença LC
    if ($resultado === 'APROVADO') {
        analisePlanosValidarConclusao($pdo, $analiseId);
        if (!empty($analise['legado_sem_proposta']) && (empty($analise['proposta_id']) || empty($analise['servico_id']) || empty($analise['vendedor_origem_id']))) {
            throw new RuntimeException('Vincule a origem comercial do processo legado antes de publicar uma nova licença.');
        }
        analiseAcaoCriarLicenca($pdo, $analise, $responsavel);
    }

    // Atualiza status da análise
    $pdo->prepare('UPDATE analises_planos SET status=:status, responsavel_assinatura_id=:responsavel WHERE id=:id')
        ->execute([':status' => $novoStatus, ':responsavel' => $responsavel['id'], ':id' => $analiseId]);

    // Histórico e Auditoria NORMAM
    analisePlanosHistorico($pdo, $analiseId, 'RELATORIO_CICLO_PUBLICADO', $analise['status'], $novoStatus, ($parecer['numero'] ?? 'Relatório') . ' assinado e finalizado pelo analista.');
    analisePlanosAuditarNorma($pdo, $analiseId, 'RELATORIO_CICLO_PUBLICADO', $analise['status'], $novoStatus, $parecer['numero'] ?? '');

    // Persiste PDF oficial assinado com SHA-256 e QR Code
    analiseAcaoPersistirParecerPdf($pdo, $parecerId, $analiseId);

    return $novoStatus;
}

function analisePlanosCategoriasNormam(): array
{
    return [
        'GERAL',
        'ART',
        'FOLHA DE ROSTO',
        'DECLARAÇÃO',
        'MEMORIAL DESCRITO',
        'NOTAS DE ARQUEAÇÃO',
        'NOTAS DE BORDA LIVRE',
        'DADOS DE ENTRADA OU COTAS',
        'CURVAS HIDROSTÁTICAS',
        'CURVAS CRUZADAS',
        'PROVA DE INCLINAÇÃO OU PORTE BRUTO',
        'ESTUDO DE ESTABILIDADE',
        'ESTUDO DE CARGA X CALADOS',
        'MOMENTO FLETOR E ESFORÇO CORTANTE',
        'PLANOS DE LINHAS',
        'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE',
        'PLANO DE PERFIL ESTRUTURAL E SEÇÃO MESTRA',
    ];
}

function analisePlanosBuscarReferenciasNormam(PDO $pdo, array $filtros = []): array
{
    $where = ['ativo = 1'];
    $params = [];
    if (!empty($filtros['categoria'])) {
        $where[] = 'categoria = :categoria';
        $params[':categoria'] = trim($filtros['categoria']);
    }
    if (!empty($filtros['busca'])) {
        $where[] = '(referencia_normativa LIKE :busca1 OR titulo LIKE :busca2 OR descricao_padrao LIKE :busca3)';
        $termo = '%' . trim($filtros['busca']) . '%';
        $params[':busca1'] = $termo;
        $params[':busca2'] = $termo;
        $params[':busca3'] = $termo;
    }
    if (isset($filtros['todos']) && $filtros['todos']) {
        array_shift($where);
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT * FROM analise_planos_referencias_normam {$whereSql} ORDER BY categoria ASC, ordem ASC, titulo ASC");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function analisePlanosSalvarReferenciaNormam(PDO $pdo, array $dados, ?string $usuarioId = null): string
{
    $id = trim($dados['id'] ?? '');
    $categoria = trim($dados['categoria'] ?? 'GERAL');
    $norma = trim($dados['norma'] ?? 'NORMAM-202') ?: 'NORMAM-202';
    $referencia = trim($dados['referencia_normativa'] ?? '');
    $titulo = trim($dados['titulo'] ?? '');
    $descricao = trim($dados['descricao_padrao'] ?? '');
    $itemNorma = trim($dados['item_norma'] ?? '') ?: null;
    $ordem = (int)($dados['ordem'] ?? 0);

    if ($referencia === '' || $descricao === '') {
        throw new InvalidArgumentException('Referência normativa e descrição padrão são obrigatórias.');
    }
    if ($titulo === '') {
        $titulo = mb_substr($descricao, 0, 70);
    }

    if ($id !== '') {
        $stmt = $pdo->prepare("UPDATE analise_planos_referencias_normam 
            SET categoria=:cat, norma=:norma, item_norma=:inorma, referencia_normativa=:ref,
                titulo=:titulo, descricao_padrao=:desc, ordem=:ordem
            WHERE id=:id");
        $stmt->execute([
            ':cat' => $categoria,
            ':norma' => $norma,
            ':inorma' => $itemNorma,
            ':ref' => $referencia,
            ':titulo' => $titulo,
            ':desc' => $descricao,
            ':ordem' => $ordem,
            ':id' => $id,
        ]);
        return $id;
    }

    $novoId = gerarUUID();
    $stmt = $pdo->prepare("INSERT INTO analise_planos_referencias_normam 
        (id, categoria, norma, item_norma, referencia_normativa, titulo, descricao_padrao, ativo, ordem, criado_por)
        VALUES (:id, :cat, :norma, :inorma, :ref, :titulo, :desc, 1, :ordem, :usuario)");
    $stmt->execute([
        ':id' => $novoId,
        ':cat' => $categoria,
        ':norma' => $norma,
        ':inorma' => $itemNorma,
        ':ref' => $referencia,
        ':titulo' => $titulo,
        ':desc' => $descricao,
        ':ordem' => $ordem,
        ':usuario' => $usuarioId,
    ]);
    return $novoId;
}

function analisePlanosExcluirReferenciaNormam(PDO $pdo, string $id): void
{
    $stmt = $pdo->prepare("DELETE FROM analise_planos_referencias_normam WHERE id=:id");
    $stmt->execute([':id' => $id]);
}

function analisePlanosObservacoesPadrao(array $analise): array
{
    $classe = $analise['classe_certificacao'] ?? 'EC1';
    $respProj = trim(($analise['responsavel_projeto_nome'] ?? '') . ' ' . ($analise['responsavel_projeto_registro'] ? 'CREA ' . $analise['responsavel_projeto_registro'] : ''));
    if (!$respProj) {
        $respProj = 'Engenheiro Naval Responsável Técnico';
    }
    $artNum = trim($analise['art_numero'] ?? '');
    $artTexto = $artNum ? " sob a ART nº {$artNum}" : "";
    
    $tipoDoc = in_array($analise['tipo_processo'] ?? '', ['LC', 'LCEC', 'LA', 'LR'], true) ? $analise['tipo_processo'] : 'LC';

    return [
        "01" => "Foram apresentados pelo armador: Planos e documentos técnicos para embarcação {$classe} elaborados pelo responsável técnico {$respProj}{$artTexto}.",
        "02" => "Foram analisados os seguintes planos e documentos técnicos, como segue: ART; Memorial Descritivo; Declaração; Notas para Arqueação; Notas para Marcação de Borda Livre; Tabela de Cotas; Tabela de Curvas Hidrostáticas; Tabela de Curvas Cruzadas; Relatório de Porte Bruto / Prova de Inclinação; Estudo de Estabilidade Definitivo; Altura de Carga x Calados; Plano de Linhas; Plano de Arranjo Geral, Segurança, Capacidade e Luzes de Navegação; Plano de Perfil Estrutural e Seção Mestra.",
        "03" => "O Armador fica ciente de que o Responsável Técnico deverá cumprir as \"exigências\" relacionadas aos planos e documentos técnicos em tempo hábil, que permita a verificação, por esta Certificadora, do cumprimento das mesmas e a consequente emissão da licença aplicável ({$tipoDoc}), durante o período de vigência do certificado condicional caso a embarcação possua prazo para tais certificados.",
        "04" => "O armador e o responsável técnico terão de atentar-se, em todos os documentos, a informações e valores que possam sofrer alterações em razão das correções realizadas nos itens solicitados neste relatório e no Relatório de Vistorias, a fim de evitar exigências relacionadas a eles. O Relatório de Vistorias pode ser solicitado para esta Entidade Certificadora.",
        "05" => "Fica evidenciado neste relatório que, assim que for constatado que o projeto apresentado não possui mais exigências, esta Entidade Certificadora solicitará os planos e documentos em via física, devidamente assinados e rubricados pelo Responsável Técnico. A Anotação de Responsabilidade Técnica (ART) deverá estar assinada por ambas as partes interessadas. (Nota: Caso haja intenção de assinatura digital na ART, esta deverá ser assinada digitalmente por ambas as partes, antes da aprovação deste projeto).",
        "06" => "As informações constantes dos planos, documentos, cálculos e estudos apresentados são de responsabilidade do engenheiro naval, que elaborou o projeto e/ou efetuou o levantamento de características, cabendo a esta Entidade Certificadora a verificação quanto ao atendimento dos requisitos estabelecidos nestas Normas (NORMAM 202, Item 3.28, 3.28.1)."
    ];
}

