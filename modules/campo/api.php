<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/embarcacao_foto.php';
require_once __DIR__ . '/../../includes/campo_storage.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function campoJson(array $dados, int $status = 200): never {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function campoErro(string $codigo, string $mensagem, int $status, array $detalhes = []): never {
    header('Content-Type: application/json; charset=utf-8');
    campoJson(['ok' => false, 'erro' => ['codigo' => $codigo, 'mensagem' => $mensagem, 'detalhes' => $detalhes]], $status);
}

function campoInput(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $dados = json_decode($raw, true);
    if (!is_array($dados)) campoErro('JSON_INVALIDO', 'O conteúdo enviado não é um JSON válido.', 400);
    return $dados;
}

function campoIpHash(): string {
    return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
}

function campoSessaoId(): string {
    return hash('sha256', session_id());
}

function campoRegistrarAuditoria(string $evento, string $detalhe = ''): void {
    if (function_exists('log_atividade')) {
        try { log_atividade($evento, $detalhe); } catch (Throwable $ignored) {}
    }
}

function campoUsuarioId(): string {
    global $pdo;
    if (!estaLogado() || empty($_SESSION['usuario_id'])) {
        campoErro('NAO_AUTENTICADO', 'Sua sessão expirou. Entre novamente.', 401);
    }
    if ((time() - (int)($_SESSION['campo_login_em'] ?? 0)) > 60 * 60 * 24 * 365) {
        session_unset();
        session_destroy();
        campoErro('SESSAO_EXPIRADA', 'Sua sessão expirou. Entre novamente.', 401);
    }
    $stmt = $pdo->prepare("SELECT u.id FROM usuarios u
        INNER JOIN campo_sessoes cs ON cs.usuario_id=u.id
        WHERE u.id=:usuario AND u.ativo=1 AND u.cargo='VISTORIADOR'
          AND cs.id=:sessao AND cs.revogado_em IS NULL AND cs.expira_em>NOW() LIMIT 1");
    $stmt->execute([':usuario'=>$_SESSION['usuario_id'], ':sessao'=>campoSessaoId()]);
    if (!$stmt->fetchColumn()) {
        session_unset();
        session_destroy();
        campoErro('NAO_AUTORIZADO', 'Entre com uma conta ativa de vistoriador.', 403);
    }
    $pdo->prepare("UPDATE campo_sessoes SET ultimo_acesso_em=NOW(),expira_em=DATE_ADD(NOW(),INTERVAL 365 DAY) WHERE id=:id")
        ->execute([':id'=>campoSessaoId()]);
    $_SESSION['campo_login_em'] = time();
    $_SESSION['login_time'] = time();
    return (string)$_SESSION['usuario_id'];
}

function campoExigirCsrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verificarCSRF($token)) campoErro('CSRF_INVALIDO', 'Atualize a tela e tente novamente.', 419);
}

function campoErpPodeVisualizarAnexo(PDO $pdo, array $anexo): bool {
    if (!estaLogado() || empty($_SESSION['usuario_id']) || !podeAcessar('vistorias')) return false;

    $usuarioId = (string)$_SESSION['usuario_id'];
    $stmt = $pdo->prepare("SELECT ativo, excluido_em FROM usuarios WHERE id=:id LIMIT 1");
    $stmt->execute([':id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || (int)$usuario['ativo'] !== 1 || $usuario['excluido_em'] !== null) return false;

    $cargo = getCargo();
    if ($cargo === 'VISTORIADOR') return (string)$anexo['vistoriador_id'] === $usuarioId;
    if ($cargo === 'ANALISTA') return (string)$anexo['vistoria_status'] === 'AGUARDANDO_APROVACAO';
    return true;
}

function campoAgendamento(PDO $pdo, string $id, string $usuarioId): array {
    $sql = "SELECT a.*, e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
                   c.nome AS cliente_nome, c.cpf_cnpj AS cliente_documento,
                   u.nome AS vistoriador_nome
            FROM agendamentos a
            INNER JOIN embarcacoes e ON e.id = a.embarcacao_id
            INNER JOIN clientes c ON c.id = a.cliente_id
            LEFT JOIN usuarios u ON u.id = a.vistoriador_id
            WHERE a.id = :id";
    if (!temPerfil('ADMIN', $usuarioId)) $sql .= " AND a.vistoriador_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $params = [':id' => $id];
    if (!temPerfil('ADMIN', $usuarioId)) $params[':usuario_id'] = $usuarioId;
    $stmt->execute($params);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ag) campoErro('VISTORIA_NAO_ENCONTRADA', 'Vistoria não encontrada ou não atribuída a você.', 404);
    return $ag;
}

function campoVistoriaPorAgendamento(PDO $pdo, string $agendamentoId, bool $bloquear = false): ?array {
    $sql = "SELECT * FROM vistorias WHERE agendamento_id = :id ORDER BY criado_em DESC, id DESC LIMIT 1";
    if ($bloquear) $sql .= " FOR UPDATE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $agendamentoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function campoCriarVistoria(PDO $pdo, array $ag, string $usuarioId): array {
    $id = gerarUUID();
    $numero = gerarNumeroDocumento('REL-V', 'AM-REL-V');
    $stmt = $pdo->prepare("INSERT INTO vistorias
        (id, numero, embarcacao_id, pessoa_id, armador_id, operador_nome, agendamento_id,
         data_vistoria, status, criado_por, mobile_versao)
        VALUES (:id, :numero, :embarcacao_id, :pessoa_id, :armador_id, :operador_nome,
                :agendamento_id, :data_vistoria, 'PENDENTE', :criado_por, 0)");
    $stmt->execute([
        ':id' => $id,
        ':numero' => $numero,
        ':embarcacao_id' => $ag['embarcacao_id'],
        ':pessoa_id' => $ag['cliente_id'],
        ':armador_id' => $ag['armador_id'] ?: null,
        ':operador_nome' => $ag['operador_nome'] ?: null,
        ':agendamento_id' => $ag['id'],
        ':data_vistoria' => $ag['data_vistoria'] ?: date('Y-m-d'),
        ':criado_por' => $usuarioId,
    ]);
    return campoVistoriaPorAgendamento($pdo, (string)$ag['id'], true);
}

function campoOperacaoExistente(PDO $pdo, string $operacaoId): ?array {
    $stmt = $pdo->prepare("SELECT resposta_json FROM vistoria_mobile_sync WHERE operacao_id = :id");
    $stmt->execute([':id' => $operacaoId]);
    $json = $stmt->fetchColumn();
    return $json ? json_decode((string)$json, true) : null;
}

function campoRegistrarOperacao(PDO $pdo, string $operacaoId, ?string $vistoriaId, string $usuarioId, string $tipo, array $payload, array $resposta): void {
    $stmt = $pdo->prepare("INSERT INTO vistoria_mobile_sync
        (operacao_id, vistoria_id, usuario_id, tipo, payload_hash, resposta_json)
        VALUES (:operacao, :vistoria, :usuario, :tipo, :hash, :resposta)");
    $stmt->execute([
        ':operacao' => $operacaoId,
        ':vistoria' => $vistoriaId,
        ':usuario' => $usuarioId,
        ':tipo' => $tipo,
        ':hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ':resposta' => json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

function campoNormalizarDataHoraCaptura(?string $valor): string {
    $valor = trim((string)$valor);
    if ($valor === '') return date('Y-m-d H:i:s');

    try {
        $data = new DateTimeImmutable($valor);
        $fuso = new DateTimeZone(date_default_timezone_get());
        return $data->setTimezone($fuso)->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        // Uma data malformada do aparelho nunca deve impedir o envio da foto.
        error_log('API campo: data de captura invalida recebida: ' . $valor);
        return date('Y-m-d H:i:s');
    }
}

function campoUrlAnexo(string $anexoId): string {
    $basePath = rtrim((string)(parse_url(APP_URL, PHP_URL_PATH) ?: ''), '/');
    return $basePath . '/api/campo/v1/anexos/' . rawurlencode($anexoId);
}

function campoGuardarFotoPrivada(string $binario, string $mime, string $vistoriaId, string $anexoId): string {
    $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime] ?? 'bin';
    $chave = 'vistorias/' . $vistoriaId . '/originais/' . $anexoId . '.' . $ext;
    $s3 = campoStorageS3();
    if ($s3) {
        campoStorageGarantirBucket();
        $s3->putObject(['Bucket'=>campoStorageBucket(), 'Key'=>$chave, 'Body'=>$binario, 'ContentType'=>$mime]);
        return $chave;
    }
    $diretorio = __DIR__ . '/../../storage/private/' . dirname($chave);
    if (!is_dir($diretorio)) mkdir($diretorio, 0750, true);
    $arquivo = __DIR__ . '/../../storage/private/' . $chave;
    if (file_put_contents($arquivo, $binario) === false) throw new RuntimeException('Falha ao armazenar a evidência.');
    return 'local:' . $chave;
}

function campoExcluirFotoPrivada(string $chave): void {
    if (str_starts_with($chave, 'local:')) {
        $arquivo = __DIR__ . '/../../storage/private/' . substr($chave, 6);
        if (is_file($arquivo)) @unlink($arquivo);
        return;
    }
    $s3 = campoStorageS3();
    if ($s3) {
        $s3->deleteObject([
            'Bucket'=>campoStorageBucket(),
            'Key'=>$chave,
        ]);
    }
}

function campoEmitirFotoPrivada(string $chave, string $mime, string $nome): never {
    $nomeSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nome ?: 'evidencia');
    if (str_starts_with($chave, 'local:')) {
        $arquivo = __DIR__ . '/../../storage/private/' . substr($chave, 6);
        if (!is_file($arquivo)) campoErro('ANEXO_NAO_ENCONTRADO', 'Evidência não encontrada.', 404);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $nomeSeguro . '"');
        header('Cache-Control: private, max-age=300');
        header('Content-Length: ' . filesize($arquivo));
        readfile($arquivo);
        exit;
    }
    $s3 = campoStorageS3();
    if (!$s3) campoErro('STORAGE_INDISPONIVEL', 'Armazenamento indisponível.', 503);
    $result = $s3->getObject(['Bucket'=>campoStorageBucket(), 'Key'=>$chave]);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $nomeSeguro . '"');
    header('Cache-Control: private, max-age=300');
    if (!empty($result['ContentLength'])) header('Content-Length: ' . (int)$result['ContentLength']);
    echo $result['Body'];
    exit;
}

function campoListaChecklist(PDO $pdo, ?string $vistoriaId, bool $demonstracao = false): array {
    $stmt = $pdo->query("SELECT ec.id, ec.descricao, ec.item_normam, ec.bloco_vistoria,
                               ec.prazo_padrao_dias, cat.id AS categoria_id, cat.nome AS categoria_nome
                        FROM exigencias_catalogo ec
                        LEFT JOIN exigencias_categorias cat ON cat.id = ec.categoria_id
                        WHERE ec.ativo = 1
                        ORDER BY COALESCE(cat.nome, 'Outros'), ec.codigo_interno, ec.descricao");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Uso real: o app recebe todos os itens ativos para o vistoriador escolher em campo.
    $respostas = [];
    $anexos = [];
    if ($vistoriaId) {
        $r = $pdo->prepare("SELECT catalogo_id, status, observacao, item_normam, vencimento, sem_prazo
                            FROM vistoria_checklist_respostas WHERE vistoria_id = :id");
        $r->execute([':id' => $vistoriaId]);
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $resp) $respostas[$resp['catalogo_id']] = $resp;

        $a = $pdo->prepare("SELECT id, catalogo_id, url_arquivo, mime_type, tamanho_bytes, sha256, capturado_em, nome_original, criado_por
                            FROM vistoria_anexos WHERE vistoria_id = :id AND excluido_em IS NULL ORDER BY criado_em");
        $a->execute([':id' => $vistoriaId]);
        foreach ($a->fetchAll(PDO::FETCH_ASSOC) as $anexo) {
            // Nao reutilizar o host gravado no banco: ele pode mudar em outra VPS.
            $anexo['url_arquivo'] = campoUrlAnexo((string)$anexo['id']);
            $anexos[$anexo['catalogo_id'] ?? 'geral'][] = $anexo;
        }
    }

    $categorias = [];
    foreach ($itens as $item) {
        $catId = $item['categoria_id'] ?: 'outros';
        if (!isset($categorias[$catId])) {
            $categorias[$catId] = ['id' => $catId, 'nome' => $item['categoria_nome'] ?: 'Outros', 'itens' => []];
        }
        $item['resposta'] = $respostas[$item['id']] ?? null;
        $item['anexos'] = $anexos[$item['id']] ?? [];
        $categorias[$catId]['itens'][] = $item;
    }
    return array_values($categorias);
}

function campoExigenciasAvulsas(PDO $pdo, ?string $vistoriaId): array {
    if (!$vistoriaId) return [];
    $stmt = $pdo->prepare("SELECT id, bloco_vistoria, ordem, item, descricao, observacao,
                                 item_normam, vencimento, antes_de_suspender, status_item, exigencia_origem_id
                            FROM vistoria_exigencias
                           WHERE vistoria_id = :id
                             AND (catalogo_id IS NULL OR EXISTS (
                                 SELECT 1 FROM vistorias v WHERE v.id = :vistoria_id AND v.finalidade = 'CUMPRIMENTO_EXIGENCIAS'
                             ))
                           ORDER BY ordem, id");
    $stmt->execute([':id' => $vistoriaId, ':vistoria_id' => $vistoriaId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($itens as &$item) $item['sem_prazo'] = !empty($item['antes_de_suspender']);
    unset($item);
    return $itens;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$requestPath = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
$prefix = 'api/campo/v1';
$rota = trim(substr($requestPath, strpos($requestPath, $prefix) + strlen($prefix)), '/');

try {
    if ($method === 'POST' && $rota === 'login') {
        $payload = campoInput();
        $email = mb_strtolower(trim((string)($payload['email'] ?? '')));
        $senha = (string)($payload['senha'] ?? '');
        $emailHash = hash('sha256', $email);
        $ipHash = campoIpHash();
        $tentativas = $pdo->prepare("SELECT COUNT(*) FROM campo_login_tentativas
            WHERE (email_hash=:email OR ip_hash=:ip) AND sucesso=0
              AND criado_em>=DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $tentativas->execute([':email'=>$emailHash, ':ip'=>$ipHash]);
        if ((int)$tentativas->fetchColumn() >= 5) {
            campoErro('MUITAS_TENTATIVAS', 'Muitas tentativas. Aguarde 15 minutos e tente novamente.', 429);
        }
        $usuario = null;
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $senha !== '') {
            $stmt = $pdo->prepare("SELECT id,nome,email,cargo,senha_hash FROM usuarios
                WHERE email=:email AND ativo=1 AND cargo='VISTORIADOR' LIMIT 1");
            $stmt->execute([':email'=>$email]);
            $candidato = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($candidato && password_verify($senha, (string)$candidato['senha_hash'])) $usuario = $candidato;
        }
        $pdo->prepare("INSERT INTO campo_login_tentativas (email_hash,ip_hash,sucesso)
            VALUES (:email,:ip,:sucesso)")
            ->execute([':email'=>$emailHash, ':ip'=>$ipHash, ':sucesso'=>$usuario ? 1 : 0]);
        if (!$usuario) campoErro('CREDENCIAIS_INVALIDAS', 'E-mail ou senha incorretos para o aplicativo de campo.', 401);

        login($usuario);
        $_SESSION['campo_login_em'] = time();
        $sessaoId = campoSessaoId();
        $pdo->prepare("INSERT INTO campo_sessoes
            (id,usuario_id,expira_em,ip_hash,user_agent_hash)
            VALUES (:id,:usuario,DATE_ADD(NOW(),INTERVAL 365 DAY),:ip,:ua)
            ON DUPLICATE KEY UPDATE revogado_em=NULL,ultimo_acesso_em=NOW(),expira_em=VALUES(expira_em)")
            ->execute([':id'=>$sessaoId, ':usuario'=>$usuario['id'], ':ip'=>$ipHash,
                ':ua'=>hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''))]);
        campoRegistrarAuditoria('campo_login', 'Login no aplicativo de campo.');
        campoJson(['ok'=>true, 'dados'=>[
            'usuario'=>['id'=>$usuario['id'], 'nome'=>$usuario['nome'], 'perfis'=>['VISTORIADOR']],
            'csrf_token'=>gerarCSRF(),
            'expira_em'=>date(DATE_ATOM, time() + 60 * 60 * 24 * 365),
        ]]);
    }

    if ($method === 'GET' && preg_match('#^anexos/([^/]+)$#', $rota, $m)) {
        $stmt = $pdo->prepare("SELECT va.*, a.vistoriador_id, v.status AS vistoria_status
            FROM vistoria_anexos va
            INNER JOIN vistorias v ON v.id=va.vistoria_id
            INNER JOIN agendamentos a ON a.id=v.agendamento_id
            WHERE va.id=:id AND va.excluido_em IS NULL LIMIT 1");
        $stmt->execute([':id'=>$m[1]]);
        $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$anexo) campoErro('ANEXO_NAO_ENCONTRADO', 'Evidencia nao encontrada.', 404);

        $sessaoErp = estaLogado() && empty($_SESSION['campo_login_em']);
        if ($sessaoErp) {
            if (!campoErpPodeVisualizarAnexo($pdo, $anexo)) {
                campoErro('ANEXO_NAO_ENCONTRADO', 'Evidencia nao encontrada.', 404);
            }
        } else {
            // No aplicativo de campo, manter a autorizacao pelo vinculo da agenda.
            $usuarioCampoId = campoUsuarioId();
            if (!temPerfil('ADMIN', $usuarioCampoId) && (string)$anexo['vistoriador_id'] !== $usuarioCampoId) {
                campoErro('ANEXO_NAO_ENCONTRADO', 'Evidencia nao encontrada.', 404);
            }
        }

        campoRegistrarAuditoria('campo_foto_visualizada', 'Foto visualizada na vistoria ' . $anexo['vistoria_id']);
        try {
            campoEmitirFotoPrivada($anexo['chave_arquivo'], $anexo['mime_type'], $anexo['nome_original'] ?? 'evidencia');
        } catch (Throwable $e) {
            error_log('API campo: falha ao ler evidencia ' . $anexo['id'] . ': ' . $e->getMessage());
            campoErro('EVIDENCIA_INDISPONIVEL', 'A evidencia nao pode ser lida no armazenamento.', 503);
        }
    }

    $usuarioId = campoUsuarioId();

    if ($method === 'POST' && $rota === 'logout') {
        campoExigirCsrf();
        $pdo->prepare("UPDATE campo_sessoes SET revogado_em=NOW() WHERE id=:id")
            ->execute([':id'=>campoSessaoId()]);
        campoRegistrarAuditoria('campo_logout', 'Logout do aplicativo de campo.');
        session_unset();
        session_destroy();
        campoJson(['ok'=>true, 'dados'=>['logout'=>true]]);
    }

    if ($method === 'DELETE' && preg_match('#^anexos/([^/]+)$#', $rota, $m)) {
        campoExigirCsrf();
        $stmt = $pdo->prepare("SELECT va.*,v.status,a.vistoriador_id FROM vistoria_anexos va
            INNER JOIN vistorias v ON v.id=va.vistoria_id
            INNER JOIN agendamentos a ON a.id=v.agendamento_id
            WHERE va.id=:id AND va.excluido_em IS NULL LIMIT 1");
        $stmt->execute([':id'=>$m[1]]);
        $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$anexo || $anexo['vistoriador_id'] !== $usuarioId) campoErro('ANEXO_NAO_ENCONTRADO', 'Evidência não encontrada.', 404);
        if ($anexo['status'] !== 'PENDENTE') campoErro('VISTORIA_BLOQUEADA', 'Fotos enviadas para análise são imutáveis.', 409);
        campoExcluirFotoPrivada((string)$anexo['chave_arquivo']);
        $pdo->prepare("UPDATE vistoria_anexos SET excluido_em=NOW(),excluido_por=:usuario WHERE id=:id")
            ->execute([':usuario'=>$usuarioId, ':id'=>$anexo['id']]);
        campoRegistrarAuditoria('campo_foto_excluida', 'Foto removida do rascunho ' . $anexo['vistoria_id']);
        campoJson(['ok'=>true, 'dados'=>['id'=>$anexo['id'], 'excluido'=>true]]);
    }

    if ($method === 'GET' && $rota === 'sessao') {
        $perfis = getPerfisUsuario($usuarioId);
        campoJson(['ok' => true, 'dados' => [
            'usuario' => ['id' => $usuarioId, 'nome' => $_SESSION['usuario_nome'] ?? 'Usuário', 'perfis' => $perfis],
            'csrf_token' => gerarCSRF(),
            'app_url' => APP_URL,
            'expira_em' => date(DATE_ATOM, (int)$_SESSION['campo_login_em'] + 60 * 60 * 24 * 30),
        ]]);
    }

    if ($method === 'GET' && $rota === 'agenda') {
        $sql = "SELECT a.id, a.data_vistoria, a.hora_vistoria, a.local, a.status, a.tipo_vistoria,
                       e.id AS embarcacao_id, e.nome AS embarcacao, e.registro, e.foto_url, e.foto_atualizada_em,
                       c.nome AS cliente,
                       v.id AS vistoria_id, v.status AS vistoria_status, v.finalidade, v.mobile_versao,
                       (v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') AND EXISTS (
                           SELECT 1 FROM vistoria_exigencias ve
                            WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1
                              AND ve.conforme='nao' AND ve.status_item<>'cumprida'
                       )) AS tarefa_cumprimento,
                       (SELECT COUNT(*) FROM vistoria_checklist_respostas r WHERE r.vistoria_id = v.id) AS respondidos,
                       (SELECT COUNT(*) FROM exigencias_catalogo ec WHERE ec.ativo = 1) AS total_itens
                FROM agendamentos a
                INNER JOIN embarcacoes e ON e.id = a.embarcacao_id
                INNER JOIN clientes c ON c.id = a.cliente_id
                LEFT JOIN vistorias v ON v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id = a.id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
                WHERE (
                    (a.status IN ('pendente','confirmado','em_andamento') AND (v.id IS NULL OR v.status = 'PENDENTE'))
                    OR (v.finalidade='CUMPRIMENTO_EXIGENCIAS' AND v.status='PENDENTE')
                    OR (v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS') AND EXISTS (
                        SELECT 1 FROM vistoria_exigencias ve
                         WHERE ve.vistoria_id=v.id AND ve.antes_de_suspender=1
                           AND ve.conforme='nao' AND ve.status_item<>'cumprida'
                    ))
                )";
        $params = [':usuario' => $usuarioId];
        $sql .= " AND a.vistoriador_id = :usuario";
        $sql .= " ORDER BY a.data_vistoria IS NULL, a.data_vistoria, a.hora_vistoria, a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $agenda = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($agenda as &$itemAgenda) {
            $itemAgenda['relatorio_url'] = APP_URL . 'vistorias/relatorio?agendamento_id=' . rawurlencode($itemAgenda['id']) . '&vistoria_id=' . rawurlencode($itemAgenda['vistoria_id'] ?? '');
        }
        unset($itemAgenda);
        campoJson(['ok' => true, 'dados' => ['vistorias' => $agenda]]);
    }

    if ($method === 'GET' && $rota === 'relatorios') {
        $sql = "SELECT
                    v.id AS vistoria_id, v.numero, v.status, v.data_vistoria,
                    v.mobile_finalizada_em, v.mobile_versao,
                    a.id AS agendamento_id, a.tipo_vistoria, a.local,
                    e.nome AS embarcacao, e.registro, c.nome AS cliente,
                    (SELECT COUNT(*) FROM vistoria_checklist_respostas r WHERE r.vistoria_id = v.id) AS respondidos,
                    (SELECT COUNT(*) FROM vistoria_checklist_respostas r WHERE r.vistoria_id = v.id AND r.status = 'NAO_CONFORME') AS nao_conformes,
                    (SELECT COUNT(*) FROM vistoria_anexos va WHERE va.vistoria_id = v.id AND va.excluido_em IS NULL) AS fotos
                FROM vistorias v
                INNER JOIN agendamentos a ON a.id = v.agendamento_id
                INNER JOIN embarcacoes e ON e.id = a.embarcacao_id
                INNER JOIN clientes c ON c.id = a.cliente_id
                WHERE a.vistoriador_id = :usuario
                  AND v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id = a.id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
                ORDER BY COALESCE(v.mobile_finalizada_em, v.atualizado_em, v.criado_em) DESC
                LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario' => $usuarioId]);
        $relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($relatorios as &$relatorio) {
            $relatorio['relatorio_url'] = APP_URL . 'vistorias/relatorio?agendamento_id=' . rawurlencode($relatorio['agendamento_id']);
        }
        unset($relatorio);
        campoJson(['ok' => true, 'dados' => ['relatorios' => $relatorios]]);
    }

    if ($method === 'GET' && preg_match('#^vistorias/([^/]+)/pacote$#', $rota, $m)) {
        $ag = campoAgendamento($pdo, $m[1], $usuarioId);
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1]);
        $ehCumprimento = (($vistoria['finalidade'] ?? '') === 'CUMPRIMENTO_EXIGENCIAS');
        campoJson(['ok' => true, 'dados' => [
            'agendamento' => $ag,
            'vistoria' => $vistoria,
            'categorias' => $ehCumprimento ? [] : campoListaChecklist($pdo, $vistoria['id'] ?? null),
            'exigencias_avulsas' => campoExigenciasAvulsas($pdo, $vistoria['id'] ?? null),
        ]]);
    }

    if ($method === 'POST' && preg_match('#^vistorias/([^/]+)/rascunho$#', $rota, $m)) {
        campoExigirCsrf();
        $payload = campoInput();
        $operacaoId = (string)($payload['operacao_id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $operacaoId)) campoErro('OPERACAO_INVALIDA', 'Identificador da operação inválido.', 422);
        $ag = campoAgendamento($pdo, $m[1], $usuarioId);
        $pdo->beginTransaction();
        $existente = campoOperacaoExistente($pdo, $operacaoId);
        if ($existente) { $pdo->commit(); campoJson($existente); }
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1], true) ?: campoCriarVistoria($pdo, $ag, $usuarioId);
        if ($vistoria['status'] !== 'PENDENTE') {
            $pdo->rollBack();
            campoErro('VISTORIA_BLOQUEADA', 'A vistoria já foi enviada para aprovação.', 409, ['status' => $vistoria['status']]);
        }
        $versaoCliente = (int)($payload['versao'] ?? 0);
        $versaoAtual = (int)$vistoria['mobile_versao'];
        if ($versaoCliente !== $versaoAtual) {
            $pdo->rollBack();
            campoErro('CONFLITO_VERSAO', 'Há uma versão mais recente no servidor.', 409, ['versao_servidor' => $versaoAtual]);
        }

        $dadosVistoria = is_array($payload['dados_vistoria'] ?? null) ? $payload['dados_vistoria'] : [];
        $dataVistoria = trim((string)($dadosVistoria['data_vistoria'] ?? $vistoria['data_vistoria'] ?? $ag['data_vistoria'] ?? ''));
        $prazoExigenciasDias = array_key_exists('prazo_exigencias_dias', $dadosVistoria)
            ? (int)$dadosVistoria['prazo_exigencias_dias']
            : (int)($vistoria['prazo_exigencias_dias'] ?? 0);
        if (!in_array($prazoExigenciasDias, [60, 90], true)) $prazoExigenciasDias = 0;
        $operadorNome = trim((string)($dadosVistoria['operador_nome'] ?? $vistoria['operador_nome'] ?? ''));
        $observacoesTecnicas = trim((string)($dadosVistoria['observacoes_tecnicas'] ?? $vistoria['observacoes_tecnicas'] ?? ''));
        $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $dataVistoria);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $dataVistoria) {
            $pdo->rollBack();
            campoErro('DATA_VISTORIA_INVALIDA', 'Informe uma data de realizaÃ§Ã£o vÃ¡lida.', 422);
        }
        $prazoCorrecao = $prazoExigenciasDias
            ? $dataValida->modify('+' . $prazoExigenciasDias . ' days')->format('Y-m-d')
            : null;
        if (mb_strlen($operadorNome) > 255) {
            $pdo->rollBack();
            campoErro('RESPONSAVEL_INVALIDO', 'O nome do responsÃ¡vel deve ter atÃ© 255 caracteres.', 422);
        }
        if (mb_strlen($observacoesTecnicas) > 10000) {
            $pdo->rollBack();
            campoErro('OBSERVACOES_INVALIDAS', 'As observaÃ§Ãµes devem ter atÃ© 10.000 caracteres.', 422);
        }
        $pdo->prepare("UPDATE vistorias
                          SET data_vistoria=:data_vistoria, operador_nome=:operador_nome,
                              prazo_exigencias_dias=:prazo_dias, observacoes_tecnicas=:observacoes
                        WHERE id=:id")
            ->execute([
                ':data_vistoria'=>$dataVistoria,
                ':prazo_dias'=>$prazoExigenciasDias ?: null,
                ':operador_nome'=>$operadorNome ?: null,
                ':observacoes'=>$observacoesTecnicas ?: null,
                ':id'=>$vistoria['id'],
            ]);

        $stmtItem = $pdo->prepare("SELECT id, descricao, item_normam, bloco_vistoria FROM exigencias_catalogo WHERE id = :id AND ativo = 1");
        $stmtResp = $pdo->prepare("INSERT INTO vistoria_checklist_respostas
            (id, vistoria_id, catalogo_id, status, observacao, item_normam, vencimento, sem_prazo)
            VALUES (UUID(), :vistoria, :catalogo, :status, :observacao, :normam, :vencimento, :sem_prazo)
            ON DUPLICATE KEY UPDATE status=VALUES(status), observacao=VALUES(observacao),
                item_normam=VALUES(item_normam), vencimento=VALUES(vencimento), sem_prazo=VALUES(sem_prazo)");
        $stmtDelEx = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria AND catalogo_id = :catalogo");
        $stmtEx = $pdo->prepare("INSERT INTO vistoria_exigencias
            (id, vistoria_id, catalogo_id, bloco_vistoria, ordem, item, descricao, conforme, observacao, item_normam, vencimento, antes_de_suspender, status_item)
            VALUES (UUID(), :vistoria, :catalogo, :bloco, :ordem, :item, :descricao, 'nao', :observacao, :normam, :vencimento, :antes_de_suspender, 'pendente')");

        // O checklist é de seleção livre: o payload representa exatamente os itens
        // escolhidos pelo vistoriador. Itens desmarcados deixam de fazer parte da vistoria.
        $catalogosSelecionados = [];
        foreach (($payload['respostas'] ?? []) as $resp) {
            $catalogoId = (string)($resp['catalogo_id'] ?? '');
            if (preg_match('/^[a-f0-9-]{36}$/i', $catalogoId)) $catalogosSelecionados[] = $catalogoId;
        }
        $catalogosSelecionados = array_values(array_unique($catalogosSelecionados));
        if ($catalogosSelecionados) {
            $marcadores = implode(',', array_fill(0, count($catalogosSelecionados), '?'));
            $pdo->prepare("DELETE FROM vistoria_checklist_respostas WHERE vistoria_id=? AND catalogo_id NOT IN ({$marcadores})")
                ->execute(array_merge([$vistoria['id']], $catalogosSelecionados));
            $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id=? AND catalogo_id IS NOT NULL AND catalogo_id NOT IN ({$marcadores})")
                ->execute(array_merge([$vistoria['id']], $catalogosSelecionados));
        } else {
            $pdo->prepare("DELETE FROM vistoria_checklist_respostas WHERE vistoria_id=:vistoria")
                ->execute([':vistoria'=>$vistoria['id']]);
            $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id=:vistoria AND catalogo_id IS NOT NULL")
                ->execute([':vistoria'=>$vistoria['id']]);
        }

        $ordem = 1;
        foreach (($payload['respostas'] ?? []) as $resp) {
            $status = (string)($resp['status'] ?? '');
            if (!in_array($status, ['CONFORME','NAO_CONFORME','NAO_SE_APLICA'], true)) continue;
            $catalogoId = (string)($resp['catalogo_id'] ?? '');
            $stmtItem->execute([':id' => $catalogoId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            if (!$item) continue;
            $observacao = trim((string)($resp['observacao'] ?? '')) ?: null;
            $semPrazo = !empty($resp['sem_prazo']) ? 1 : 0;
            $vencimento = (!$semPrazo && $status === 'NAO_CONFORME') ? $prazoCorrecao : null;
            $normam = trim((string)($resp['item_normam'] ?? $item['item_normam'] ?? '')) ?: null;
            $stmtResp->execute([':vistoria'=>$vistoria['id'], ':catalogo'=>$catalogoId, ':status'=>$status,
                ':observacao'=>$observacao, ':normam'=>$normam, ':vencimento'=>$vencimento, ':sem_prazo'=>$semPrazo]);
            $stmtDelEx->execute([':vistoria'=>$vistoria['id'], ':catalogo'=>$catalogoId]);
            if ($status === 'NAO_CONFORME') {
                $stmtEx->execute([':vistoria'=>$vistoria['id'], ':catalogo'=>$catalogoId,
                    ':bloco'=>$item['bloco_vistoria'] ?: 'flutuando', ':ordem'=>$ordem++,
                    ':item'=>$normam ?: 'Item do checklist', ':descricao'=>$item['descricao'],
                    ':observacao'=>$observacao, ':normam'=>$normam, ':vencimento'=>$vencimento,
                    ':antes_de_suspender'=>$semPrazo]);
            }
        }


        $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id=:vistoria AND catalogo_id IS NULL")
            ->execute([':vistoria'=>$vistoria['id']]);
        $stmtAvulsa = $pdo->prepare("INSERT INTO vistoria_exigencias
            (id, vistoria_id, catalogo_id, bloco_vistoria, ordem, item, descricao, conforme,
             observacao, item_normam, vencimento, antes_de_suspender, status_item, exigencia_origem_id)
            VALUES (UUID(), :vistoria, NULL, :bloco, :ordem, :item, :descricao, :conforme,
                    :observacao, :normam, :vencimento, :antes_de_suspender, :status_item, :origem)");
        $blocosValidos = ['seco','flutuando','borda_livre','arqueacao'];
        $statusAvulsos = ['inserida','pendente','cumprida','nao_cumprida_transcrita','cumprida_parcial_reescrita'];
        foreach (($payload['exigencias_avulsas'] ?? []) as $indice => $avulsa) {
            if (!is_array($avulsa)) continue;
            $descricao = trim((string)($avulsa['descricao'] ?? ''));
            $normam = trim((string)($avulsa['item_normam'] ?? $avulsa['item'] ?? ''));
            if ($descricao === '' && $normam === '') continue;
            $bloco = in_array($avulsa['bloco_vistoria'] ?? '', $blocosValidos, true) ? $avulsa['bloco_vistoria'] : 'flutuando';
            $statusItem = in_array($avulsa['status_item'] ?? '', $statusAvulsos, true) ? $avulsa['status_item'] : 'inserida';
            $semPrazo = !empty($avulsa['sem_prazo']);
            $vencimento = $semPrazo ? null : $prazoCorrecao;
            $conforme = $statusItem === 'cumprida' ? 'sim' : ($statusItem === 'inserida' ? 'na' : 'nao');
            $stmtAvulsa->execute([
                ':vistoria'=>$vistoria['id'], ':bloco'=>$bloco, ':ordem'=>$indice + 1,
                ':item'=>$normam ?: $descricao, ':descricao'=>$descricao ?: $normam,
                ':conforme'=>$conforme, ':observacao'=>trim((string)($avulsa['observacao'] ?? '')) ?: null,
                ':normam'=>$normam ?: null, ':vencimento'=>$vencimento ?: null,
                ':antes_de_suspender'=>$semPrazo ? 1 : 0, ':status_item'=>$statusItem,
                ':origem'=>!empty($avulsa['exigencia_origem_id']) ? $avulsa['exigencia_origem_id'] : null,
            ]);
        }
        $novaVersao = $versaoAtual + 1;
        $pdo->prepare("UPDATE vistorias SET mobile_versao = :versao WHERE id = :id")
            ->execute([':versao'=>$novaVersao, ':id'=>$vistoria['id']]);
        $resposta = ['ok'=>true, 'dados'=>['vistoria_id'=>$vistoria['id'], 'versao'=>$novaVersao, 'salvo_em'=>date(DATE_ATOM)]];
        campoRegistrarOperacao($pdo, $operacaoId, $vistoria['id'], $usuarioId, 'RASCUNHO', $payload, $resposta);
        $pdo->commit();
        campoJson($resposta);
    }

    if ($method === 'POST' && preg_match('#^vistorias/([^/]+)/anexos$#', $rota, $m)) {
        campoExigirCsrf();
        $operacaoId = (string)($_POST['operacao_id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $operacaoId)) campoErro('OPERACAO_INVALIDA', 'Identificador da operação inválido.', 422);
        campoAgendamento($pdo, $m[1], $usuarioId);
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1]);
        if (!$vistoria) campoErro('RASCUNHO_NAO_SINCRONIZADO', 'Sincronize o rascunho antes das fotos.', 409);
        $existente = campoOperacaoExistente($pdo, $operacaoId);
        if ($existente) campoJson($existente);
        if ($vistoria['status'] !== 'PENDENTE') campoErro('VISTORIA_BLOQUEADA', 'A vistoria já foi enviada.', 409);

        $upload = $_FILES['arquivo'] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name']))
            campoErro('ANEXO_INVALIDO', 'Selecione uma foto válida.', 422);
        $tamanho = (int)($upload['size'] ?? 0);
        if ($tamanho < 1 || $tamanho > 15 * 1024 * 1024) campoErro('ANEXO_GRANDE', 'A foto original deve ter até 15 MB.', 422);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file((string)$upload['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true))
            campoErro('ANEXO_INVALIDO', 'Envie uma foto JPEG, PNG ou WebP.', 422);
        $binario = file_get_contents((string)$upload['tmp_name']);
        if ($binario === false) campoErro('ANEXO_INVALIDO', 'Não foi possível ler a foto enviada.', 422);
        $hash = hash('sha256', $binario);
        $catalogoId = !empty($_POST['catalogo_id']) ? (string)$_POST['catalogo_id'] : null;
        $id = $operacaoId;
        $q = $pdo->prepare("SELECT id, url_arquivo FROM vistoria_anexos WHERE vistoria_id=:v AND sha256=:h AND excluido_em IS NULL");
        $q->execute([':v'=>$vistoria['id'], ':h'=>$hash]);
        $ja = $q->fetch(PDO::FETCH_ASSOC);
        $capturadoEm = campoNormalizarDataHoraCaptura($_POST['capturado_em'] ?? null);
        if ($ja) {
            $id = $ja['id'];
            $url = campoUrlAnexo((string)$id);
        } else {
            try {
                $chave = campoGuardarFotoPrivada($binario, $mime, $vistoria['id'], $id);
            } catch (Throwable $e) {
                error_log('API campo: falha ao armazenar evidencia: ' . $e->getMessage());
                campoErro(
                    'ARMAZENAMENTO_INDISPONIVEL',
                    'A foto continua salva neste aparelho. Toque em Enviar agora para tentar novamente.',
                    503
                );
            }
            $url = campoUrlAnexo((string)$id);
            $stmt = $pdo->prepare("INSERT INTO vistoria_anexos
                (id, vistoria_id, catalogo_id, url_arquivo, chave_arquivo, nome_original, mime_type,
                 tamanho_bytes, sha256, capturado_em, criado_por)
                VALUES (:id,:vistoria,:catalogo,:url,:chave,:nome,:mime,:tamanho,:hash,:capturado,:usuario)");
            $stmt->execute([':id'=>$id, ':vistoria'=>$vistoria['id'], ':catalogo'=>$catalogoId, ':url'=>$url,
                ':chave'=>$chave, ':nome'=>substr((string)($upload['name'] ?? 'evidencia'), 0, 255),
                ':mime'=>$mime, ':tamanho'=>$tamanho, ':hash'=>$hash,
                ':capturado'=>$capturadoEm, ':usuario'=>$usuarioId]);
        }
        $resposta = ['ok'=>true, 'dados'=>['id'=>$id, 'url'=>$url, 'url_arquivo'=>$url, 'catalogo_id'=>$catalogoId,
            'nome_original'=>$upload['name'] ?? 'evidencia', 'mime_type'=>$mime, 'tamanho_bytes'=>$tamanho,
            'sha256'=>$hash, 'capturado_em'=>$capturadoEm]];
        $registro = ['catalogo_id'=>$catalogoId, 'nome'=>$upload['name'] ?? 'evidencia', 'sha256'=>$hash, 'tamanho'=>$tamanho];
        campoRegistrarOperacao($pdo, $operacaoId, $vistoria['id'], $usuarioId, 'ANEXO', $registro, $resposta);
        campoRegistrarAuditoria('campo_foto_enviada', 'Foto vinculada à vistoria ' . $vistoria['id']);
        campoJson($resposta, 201);
    }

    if ($method === 'POST' && preg_match('#^vistorias/([^/]+)/foto-embarcacao$#', $rota, $m)) {
        campoExigirCsrf();
        $operacaoId = (string)($_POST['operacao_id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $operacaoId)) campoErro('OPERACAO_INVALIDA', 'Identificador da operação inválido.', 422);
        $ag = campoAgendamento($pdo, $m[1], $usuarioId);
        $existente = campoOperacaoExistente($pdo, $operacaoId);
        if ($existente) campoJson($existente);
        $upload = $_FILES['arquivo'] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name'])) {
            campoErro('FOTO_EMBARCACAO_INVALIDA', 'Selecione uma foto válida da embarcação.', 422);
        }
        $tamanho = (int)($upload['size'] ?? 0);
        if ($tamanho < 1 || $tamanho > 15 * 1024 * 1024) campoErro('FOTO_EMBARCACAO_GRANDE', 'A foto da embarcação deve ter até 15 MB.', 422);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file((string)$upload['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            campoErro('FOTO_EMBARCACAO_INVALIDA', 'Use uma foto JPEG, PNG ou WebP.', 422);
        }
        $binario = file_get_contents((string)$upload['tmp_name']);
        if ($binario === false) campoErro('FOTO_EMBARCACAO_INVALIDA', 'Não foi possível ler a foto enviada.', 422);
        $fotoId = $operacaoId;
        $hash = hash('sha256', $binario);
        $stmtAtual = $pdo->prepare("SELECT foto_chave FROM embarcacoes WHERE id=:id AND ativo=1 LIMIT 1");
        $stmtAtual->execute([':id'=>$ag['embarcacao_id']]);
        $chaveAnterior = $stmtAtual->fetchColumn();
        $novaChave = embarcacaoFotoGuardar($binario, $mime, $ag['embarcacao_id'], $fotoId);
        $fotoUrl = APP_URL . 'embarcacoes/foto?id=' . rawurlencode($ag['embarcacao_id']) . '&v=' . substr($hash, 0, 12);
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE embarcacoes
                SET foto_chave=:chave,foto_url=:url,foto_nome_original=:nome,foto_mime_type=:mime,
                    foto_tamanho_bytes=:tamanho,foto_sha256=:hash,foto_atualizada_em=NOW(),foto_atualizada_por=:usuario
                WHERE id=:id AND ativo=1")
                ->execute([
                    ':chave'=>$novaChave, ':url'=>$fotoUrl,
                    ':nome'=>substr((string)($upload['name'] ?? 'embarcacao'), 0, 255),
                    ':mime'=>$mime, ':tamanho'=>$tamanho, ':hash'=>$hash,
                    ':usuario'=>$usuarioId, ':id'=>$ag['embarcacao_id'],
                ]);
            $vistoria = campoVistoriaPorAgendamento($pdo, $m[1]);
            $resposta = ['ok'=>true, 'dados'=>[
                'embarcacao_id'=>$ag['embarcacao_id'], 'foto_url'=>$fotoUrl,
                'foto_sha256'=>$hash, 'foto_tamanho_bytes'=>$tamanho, 'foto_atualizada_em'=>date(DATE_ATOM),
            ]];
            campoRegistrarOperacao($pdo, $operacaoId, $vistoria['id'] ?? null, $usuarioId, 'FOTO_EMBARCACAO',
                ['embarcacao_id'=>$ag['embarcacao_id'], 'sha256'=>$hash, 'tamanho'=>$tamanho], $resposta);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            embarcacaoFotoExcluir($novaChave);
            throw $e;
        }
        if ($chaveAnterior && $chaveAnterior !== $novaChave) {
            try { embarcacaoFotoExcluir((string)$chaveAnterior); } catch (Throwable $e) { error_log('Foto anterior da embarcação: ' . $e->getMessage()); }
        }
        campoRegistrarAuditoria('embarcacao_foto_atualizada', 'Foto oficial atualizada pelo Campo: ' . $ag['embarcacao_id']);
        campoJson($resposta, 201);
    }

    if ($method === 'POST' && preg_match('#^vistorias/([^/]+)/finalizar$#', $rota, $m)) {
        campoExigirCsrf();
        $payload = campoInput();
        $operacaoId = (string)($payload['operacao_id'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $operacaoId)) campoErro('OPERACAO_INVALIDA', 'Identificador da operação inválido.', 422);
        $ag = campoAgendamento($pdo, $m[1], $usuarioId);
        $pdo->beginTransaction();
        $existente = campoOperacaoExistente($pdo, $operacaoId);
        if ($existente) { $pdo->commit(); campoJson($existente); }
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1], true);
        if (!$vistoria) { $pdo->rollBack(); campoErro('RASCUNHO_INEXISTENTE', 'Salve o rascunho antes de finalizar.', 422); }
        if ($vistoria['status'] !== 'PENDENTE') { $pdo->rollBack(); campoErro('VISTORIA_BLOQUEADA', 'A vistoria já foi finalizada.', 409); }
        if (!in_array((int)($vistoria['prazo_exigencias_dias'] ?? 0), [60, 90], true)) {
            $pdo->rollBack();
            campoErro('PRAZO_CORRECAO_OBRIGATORIO', 'Selecione obrigatoriamente o prazo de correção: 60 ou 90 dias.', 422);
        }
        if (($vistoria['finalidade'] ?? '') === 'CUMPRIMENTO_EXIGENCIAS') {
            $qCumprimento = $pdo->prepare("SELECT COUNT(*) total,
                SUM(status_item NOT IN ('cumprida','cumprida_parcial_reescrita','nao_cumprida_transcrita')) sem_decisao
                FROM vistoria_exigencias WHERE vistoria_id=:id");
            $qCumprimento->execute([':id' => $vistoria['id']]);
            $validacaoCumprimento = $qCumprimento->fetch(PDO::FETCH_ASSOC);
            if ((int)($validacaoCumprimento['total'] ?? 0) === 0 || (int)($validacaoCumprimento['sem_decisao'] ?? 0) > 0) {
                $pdo->rollBack();
                campoErro('CUMPRIMENTO_INCOMPLETO', 'Classifique todas as exigências antes de enviar.', 422);
            }
            $pdo->prepare("UPDATE vistorias SET status='AGUARDANDO_APROVACAO', mobile_finalizada_em=NOW(), mobile_versao=mobile_versao+1 WHERE id=:id")
                ->execute([':id'=>$vistoria['id']]);
            $resposta = ['ok'=>true, 'dados'=>['vistoria_id'=>$vistoria['id'], 'status'=>'AGUARDANDO_APROVACAO',
                'relatorio_url'=>APP_URL . 'vistorias/relatorio?agendamento_id=' . rawurlencode($m[1]) . '&vistoria_id=' . rawurlencode($vistoria['id'])]];
            campoRegistrarOperacao($pdo, $operacaoId, $vistoria['id'], $usuarioId, 'FINALIZACAO', $payload, $resposta);
            $pdo->commit();
            campoJson($resposta);
        }
        $escopo = campoListaChecklist($pdo, $vistoria['id']);
        $idsEscopo = [];
        foreach ($escopo as $categoria) foreach ($categoria['itens'] as $item) $idsEscopo[] = $item['id'];
        $placeholders = implode(',', array_fill(0, count($idsEscopo), '?'));
        $q = $pdo->prepare("SELECT
            " . count($idsEscopo) . " total,
            COUNT(*) respondidos,
            SUM(CASE WHEN r.status='NAO_CONFORME' AND (r.observacao IS NULL OR TRIM(r.observacao)='') THEN 1 ELSE 0 END) sem_observacao,
            SUM(CASE WHEN r.status='NAO_CONFORME' AND r.vencimento IS NULL AND r.sem_prazo=0 THEN 1 ELSE 0 END) sem_prazo_definido,
            SUM(CASE WHEN r.status='NAO_CONFORME' AND NOT EXISTS
                (SELECT 1 FROM vistoria_anexos a WHERE a.vistoria_id=r.vistoria_id AND a.catalogo_id=r.catalogo_id AND a.excluido_em IS NULL) THEN 1 ELSE 0 END) sem_foto
            FROM vistoria_checklist_respostas r WHERE r.vistoria_id=? AND r.catalogo_id IN ({$placeholders})");
        $q->execute(array_merge([$vistoria['id']], $idsEscopo));
        $validacao = $q->fetch(PDO::FETCH_ASSOC);
        $erros = [];
        if ((int)$validacao['sem_prazo_definido'] > 0) $erros[] = 'Defina prazo ou marque sem prazo.';
        if ($erros) { $pdo->rollBack(); campoErro('VISTORIA_INCOMPLETA', 'A vistoria ainda não pode ser enviada.', 422, $erros); }
        $pdo->prepare("UPDATE vistorias SET status='AGUARDANDO_APROVACAO', mobile_finalizada_em=NOW(), mobile_versao=mobile_versao+1 WHERE id=:id")
            ->execute([':id'=>$vistoria['id']]);
        $resposta = ['ok'=>true, 'dados'=>['vistoria_id'=>$vistoria['id'], 'status'=>'AGUARDANDO_APROVACAO',
            'relatorio_url'=>APP_URL . 'vistorias/relatorio?agendamento_id=' . rawurlencode($m[1])]];
        campoRegistrarOperacao($pdo, $operacaoId, $vistoria['id'], $usuarioId, 'FINALIZACAO', $payload, $resposta);
        $pdo->commit();
        campoRegistrarAuditoria('campo_vistoria_enviada', 'Vistoria enviada para aprovação: ' . $vistoria['id']);
        campoJson($resposta);
    }

    if ($method === 'GET' && preg_match('#^vistorias/([^/]+)/sync$#', $rota, $m)) {
        campoAgendamento($pdo, $m[1], $usuarioId);
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1]);
        campoJson(['ok'=>true, 'dados'=>['vistoria_id'=>$vistoria['id'] ?? null,
            'versao'=>(int)($vistoria['mobile_versao'] ?? 0), 'status'=>$vistoria['status'] ?? 'RASCUNHO_LOCAL']]);
    }

    if ($method === 'GET' && preg_match('#^vistorias/([^/]+)/previa$#', $rota, $m)) {
        $ag = campoAgendamento($pdo, $m[1], $usuarioId);
        $vistoria = campoVistoriaPorAgendamento($pdo, $m[1]);
        if (!$vistoria) campoErro('RASCUNHO_INEXISTENTE', 'A vistoria ainda não possui dados sincronizados.', 404);
        $stmt = $pdo->prepare("SELECT
                COUNT(*) AS respondidos,
                SUM(status='CONFORME') AS conformes,
                SUM(status='NAO_CONFORME') AS nao_conformes,
                SUM(status='NAO_SE_APLICA') AS nao_se_aplica
            FROM vistoria_checklist_respostas WHERE vistoria_id=:id");
        $stmt->execute([':id' => $vistoria['id']]);
        $resumo = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmtFotos = $pdo->prepare("SELECT COUNT(*) FROM vistoria_anexos WHERE vistoria_id=:id AND excluido_em IS NULL");
        $stmtFotos->execute([':id' => $vistoria['id']]);
        campoJson(['ok'=>true, 'dados'=>[
            'agendamento'=>['id'=>$ag['id'], 'embarcacao'=>$ag['embarcacao_nome'], 'cliente'=>$ag['cliente_nome'], 'local'=>$ag['local']],
            'vistoria'=>['id'=>$vistoria['id'], 'numero'=>$vistoria['numero'], 'status'=>$vistoria['status'], 'versao'=>(int)$vistoria['mobile_versao']],
            'resumo'=>array_merge($resumo ?: [], ['fotos'=>(int)$stmtFotos->fetchColumn()]),
            'relatorio_url'=>APP_URL . 'vistorias/relatorio?agendamento_id=' . rawurlencode($m[1]),
        ]]);
    }

    campoErro('ROTA_NAO_ENCONTRADA', 'Endpoint não encontrado.', 404);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('API campo: ' . $e->getMessage());
    campoErro('ERRO_INTERNO', APP_DEBUG ? $e->getMessage() : 'Não foi possível concluir a operação.', 500);
}
