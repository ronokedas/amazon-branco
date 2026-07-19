<?php
/**
 * Autenticacao e consultas do Portal do Cliente.
 */

function clienteEstaLogado(): bool
{
    return isset($_SESSION['cliente_logado']) && $_SESSION['cliente_logado'] === true;
}

function clientePortalId(): ?string
{
    return $_SESSION['cliente_id'] ?? null;
}

function clientePortalNome(): string
{
    return $_SESSION['cliente_nome'] ?? 'Cliente';
}

function clientePortalPerfil(): string
{
    return $_SESSION['cliente_perfil'] ?? 'proprietario';
}

function clientePortalForcarTrocaSenha(): bool
{
    return !empty($_SESSION['cliente_forcar_troca_senha']);
}

function loginCliente(array $cliente, array $acesso): void
{
    session_regenerate_id(true);
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'];
    $_SESSION['cliente_email'] = $cliente['email'];
    $_SESSION['cliente_perfil'] = $cliente['perfil'] ?? 'proprietario';
    $_SESSION['cliente_logado'] = true;
    $_SESSION['cliente_login_time'] = time();
    $_SESSION['cliente_forcar_troca_senha'] = (int)($acesso['forcar_troca_senha'] ?? 0) === 1;
}

function logoutCliente(): void
{
    unset(
        $_SESSION['cliente_id'],
        $_SESSION['cliente_nome'],
        $_SESSION['cliente_email'],
        $_SESSION['cliente_perfil'],
        $_SESSION['cliente_logado'],
        $_SESSION['cliente_login_time'],
        $_SESSION['cliente_forcar_troca_senha']
    );
    header('Location: ' . APP_URL . 'portal/login');
    exit;
}

function clientePortalAuditar(PDO $pdo, string $evento, ?string $clienteId = null, ?string $embarcacaoId = null, ?string $documentoTipo = null, ?string $documentoId = null, bool $sucesso = true, string $detalhe = ''): void
{
    try {
        $stmt=$pdo->prepare('INSERT INTO portal_auditoria (cliente_id,perfil,evento,embarcacao_id,documento_tipo,documento_id,sucesso,detalhe,ip,user_agent) VALUES (:cliente,:perfil,:evento,:embarcacao,:tipo,:documento,:sucesso,:detalhe,:ip,:ua)');
        $stmt->execute([':cliente'=>$clienteId ?: clientePortalId(), ':perfil'=>$clienteId ? null : clientePortalPerfil(), ':evento'=>$evento, ':embarcacao'=>$embarcacaoId, ':tipo'=>$documentoTipo, ':documento'=>$documentoId, ':sucesso'=>$sucesso?1:0, ':detalhe'=>$detalhe?substr($detalhe,0,500):null, ':ip'=>substr($_SERVER['REMOTE_ADDR']??'',0,45), ':ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);
    } catch (Throwable $e) { error_log('Falha na auditoria do portal: '.$e->getMessage()); }
}

function verificarSessaoCliente(): void
{
    if (!clienteEstaLogado() || (time() - ($_SESSION['cliente_login_time'] ?? 0)) > 3600) {
        logoutCliente();
    }

    $_SESSION['cliente_login_time'] = time();
}

function requireClienteLogin(): void
{
    if (!clienteEstaLogado()) {
        header('Location: ' . APP_URL . 'portal/login');
        exit;
    }

    verificarSessaoCliente();
}

function requireClienteSenhaDefinitiva(): void
{
    requireClienteLogin();
    if (clientePortalForcarTrocaSenha()) {
        header('Location: ' . APP_URL . 'portal/trocar-senha');
        exit;
    }
}

function clientePortalConfigDocumentos(): array
{
    return [
        'csn' => [
            'label' => 'CSN',
            'table' => 'certificados_csn',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/certificados/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'cnbl' => [
            'label' => 'CNBL',
            'table' => 'certificados_cnbl',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/cnbl/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'cnarq' => [
            'label' => 'CNARQ',
            'table' => 'certificados_cnarq',
            'numero' => 'numero',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/cnarq/pdf',
            'has_embarcacao_id' => false,
            'has_numero_inscricao' => true,
        ],
        'lc' => [
            'label' => 'LC',
            'table' => 'certificados_lc',
            'numero' => 'numero_lc',
            'validade' => 'data_validade',
            'pdf' => 'documentacao/lc/pdf',
            'has_embarcacao_id' => true,
            'has_numero_inscricao' => false,
        ],
        'lp' => [
            'label' => 'LP',
            'table' => 'certificados_lp',
            'numero' => 'numero_lp',
            'validade' => 'validade_data',
            'pdf' => 'documentacao/lp/pdf',
            'has_embarcacao_id' => true,
            'has_numero_inscricao' => false,
        ],
    ];
}

function clientePortalEmbarcacoes(PDO $pdo, string $clienteId, ?string $perfil = null): array
{
    if (($perfil ?: clientePortalPerfil()) === 'despachante') {
        $stmt=$pdo->prepare("SELECT DISTINCT e.id,e.nome,e.registro,e.numero_inscricao,e.tipo_embarcacao FROM embarcacoes e INNER JOIN clientes_embarcacoes ce ON ce.embarcacao_id=e.id AND ce.cliente_id=:cliente AND ce.status='ATIVO' WHERE e.ativo=1 ORDER BY e.nome");
        $stmt->execute([':cliente'=>$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.id, e.nome, e.registro, e.numero_inscricao, e.tipo_embarcacao
        FROM embarcacoes e
        LEFT JOIN clientes_embarcacoes ce ON ce.embarcacao_id = e.id AND ce.cliente_id = :cliente_ce AND ce.status = 'ATIVO'
        WHERE e.ativo = 1
          AND (
            e.proprietario_id = :cliente_prop
            OR e.cliente_id = :cliente_cad
            OR ce.cliente_id IS NOT NULL
          )
        ORDER BY e.nome ASC
    ");
    $stmt->execute([
        ':cliente_ce' => $clienteId,
        ':cliente_prop' => $clienteId,
        ':cliente_cad' => $clienteId,
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clientePortalEmbarcacaoIds(PDO $pdo, string $clienteId): array
{
    return array_values(array_unique(array_column(clientePortalEmbarcacoes($pdo, $clienteId), 'id')));
}

function clientePortalSqlIn(array $ids, string $prefixo, array &$params): string
{
    $placeholders = [];
    foreach ($ids as $index => $id) {
        $key = ':' . $prefixo . $index;
        $placeholders[] = $key;
        $params[$key] = $id;
    }
    return implode(',', $placeholders);
}

function clientePortalSelectDocumentos(PDO $pdo, string $clienteId, array $filtros = []): array
{
    $embarcacaoIds = clientePortalEmbarcacaoIds($pdo, $clienteId);
    if (empty($embarcacaoIds)) {
        return [];
    }

    $configs = clientePortalConfigDocumentos();
    $tipoSolicitado = trim((string)($filtros['tipo'] ?? ''));
    $tipos = $tipoSolicitado !== ''
        ? (isset($configs[$tipoSolicitado]) ? [$tipoSolicitado => $configs[$tipoSolicitado]] : [])
        : $configs;

    $documentos = [];
    foreach ($tipos as $tipo => $cfg) {
        $params = [];
        $in = clientePortalSqlIn($embarcacaoIds, 'emb_', $params);
        $embJoin = $cfg['has_embarcacao_id']
            ? "LEFT JOIN embarcacoes ed ON ed.id = c.embarcacao_id AND ed.ativo = 1"
            : "LEFT JOIN embarcacoes ed ON 1 = 0";
        $fallbackInscricao = $cfg['has_numero_inscricao']
            ? "AND (
                    c.numero_inscricao IS NULL
                    OR c.numero_inscricao = ''
                    OR en.numero_inscricao = c.numero_inscricao
                    OR en.registro = c.numero_inscricao
                )"
            : "";
        $numeroCampo = $cfg['numero'];
        $validadeCampo = $cfg['validade'];

        $sql = "
            SELECT
                c.id,
                '{$tipo}' AS tipo,
                '{$cfg['label']}' AS tipo_label,
                c.{$numeroCampo} AS numero,
                c.nome_embarcacao,
                c.data_emissao,
                c.{$validadeCampo} AS data_validade,
                c.status,
                c.assinado,
                c.criado_em,
                COALESCE(ed.id, ev.id, en.id) AS embarcacao_id,
                COALESCE(ed.nome, ev.nome, en.nome, c.nome_embarcacao) AS embarcacao_nome
            FROM {$cfg['table']} c
            {$embJoin}
            LEFT JOIN vistorias v ON v.id = c.vistoria_id
            LEFT JOIN embarcacoes ev ON ev.id = v.embarcacao_id AND ev.ativo = 1
            LEFT JOIN embarcacoes en ON en.ativo = 1
                AND en.nome = c.nome_embarcacao
                {$fallbackInscricao}
            WHERE c.ativo = 1
              AND c.status IN ('emitido', 'assinado')
              AND (c.{$validadeCampo} IS NULL OR c.{$validadeCampo} >= CURDATE())
              AND COALESCE(ed.id, ev.id, en.id) IN ({$in})
        ";

        if (!empty($filtros['embarcacao_id'])) {
            $sql .= " AND COALESCE(ed.id, ev.id, en.id) = :filtro_embarcacao";
            $params[':filtro_embarcacao'] = $filtros['embarcacao_id'];
        }

        if (!empty($filtros['status']) && in_array($filtros['status'], ['emitido', 'assinado'], true)) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['busca'])) {
            $sql .= " AND (c.{$numeroCampo} LIKE :busca_numero OR c.nome_embarcacao LIKE :busca_embarcacao)";
            $params[':busca_numero'] = '%' . $filtros['busca'] . '%';
            $params[':busca_embarcacao'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['vencendo_dias'])) {
            $dias = max(1, (int)$filtros['vencendo_dias']);
            $sql .= " AND c.{$validadeCampo} IS NOT NULL AND c.{$validadeCampo} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dias} DAY)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $documentos = array_merge($documentos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    $tipoFiltro=$filtros['tipo']??'';$params=[];$in=clientePortalSqlIn($embarcacaoIds,'extra_emb_',$params);
    if($tipoFiltro===''||$tipoFiltro==='rel_vistoria'){
        $sql="SELECT v.id,'rel_vistoria' tipo,'Relatório de Vistoria' tipo_label,v.numero,v.data_vistoria data_emissao,NULL data_validade,v.status,1 assinado,v.criado_em,v.embarcacao_id,e.nome embarcacao_nome FROM vistorias v INNER JOIN embarcacoes e ON e.id=v.embarcacao_id WHERE v.embarcacao_id IN ({$in}) AND v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')";
        if(!empty($filtros['embarcacao_id'])){$sql.=' AND v.embarcacao_id=:extra_vist_emb';$params[':extra_vist_emb']=$filtros['embarcacao_id'];}
        if(!empty($filtros['busca'])){$sql.=' AND (v.numero LIKE :extra_vist_busca OR e.nome LIKE :extra_vist_busca)';$params[':extra_vist_busca']='%'.$filtros['busca'].'%';}
        $st=$pdo->prepare($sql);$st->execute($params);$documentos=array_merge($documentos,$st->fetchAll(PDO::FETCH_ASSOC));
    }
    if($tipoFiltro===''||$tipoFiltro==='parecer_planos'){
        $params2=[];$in2=clientePortalSqlIn($embarcacaoIds,'plan_emb_',$params2);
        $sql="SELECT p.id,'parecer_planos' tipo,'Análise de Planos' tipo_label,CONCAT(ap.numero,' v',p.versao) numero,p.publicado_em data_emissao,NULL data_validade,p.resultado status,1 assinado,p.criado_em,ap.embarcacao_id,e.nome embarcacao_nome FROM analise_planos_pareceres p INNER JOIN analises_planos ap ON ap.id=p.analise_id INNER JOIN embarcacoes e ON e.id=ap.embarcacao_id WHERE ap.embarcacao_id IN ({$in2}) AND p.status='PUBLICADO'";
        if(!empty($filtros['embarcacao_id'])){$sql.=' AND ap.embarcacao_id=:plan_filtro_emb';$params2[':plan_filtro_emb']=$filtros['embarcacao_id'];}
        if(!empty($filtros['busca'])){$sql.=' AND (ap.numero LIKE :plan_busca OR e.nome LIKE :plan_busca)';$params2[':plan_busca']='%'.$filtros['busca'].'%';}
        $st=$pdo->prepare($sql);$st->execute($params2);$documentos=array_merge($documentos,$st->fetchAll(PDO::FETCH_ASSOC));
    }

    usort($documentos, function ($a, $b) {
        return strcmp((string)($b['data_validade'] ?? ''), (string)($a['data_validade'] ?? ''));
    });

    return $documentos;
}

function clientePortalTiposDocumentos(): array
{
    $tipos=[];foreach(clientePortalConfigDocumentos() as $tipo=>$cfg)$tipos[$tipo]=$cfg['label'];
    $tipos['rel_vistoria']='Relatório de Vistoria';$tipos['parecer_planos']='Análise de Planos';return $tipos;
}

function clientePortalDocumento(PDO $pdo, string $clienteId, string $tipo, string $documentoId): ?array
{
    if ($documentoId === '') {
        return null;
    }

    $docs = clientePortalSelectDocumentos($pdo, $clienteId, ['tipo' => $tipo]);
    foreach ($docs as $doc) {
        if (hash_equals((string)$doc['id'], $documentoId)) {
            return $doc;
        }
    }
    return null;
}

function clientePortalTemplate(string $nome, array $replacements): string
{
    $path = __DIR__ . '/../templates/email/' . $nome . '.html';
    if (!is_file($path)) {
        throw new RuntimeException('Template de e-mail nao encontrado.');
    }

    $html = file_get_contents($path);
    return str_replace(array_keys($replacements), array_values($replacements), $html);
}

function clientePortalGerarSenhaFacil(int $tamanho = 8): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $senha;
}
