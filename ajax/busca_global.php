<?php
/**
 * AJAX: Busca global unificada e multi-entidade do ERP Naval.
 * Pesquisa em tempo real:
 * - Certificados Navais (CSN, LC, LP, CNBL, CNARQ, CHT)
 * - Documentos & Protocolos (Dossiês, Ofícios, Trâmites)
 * - Análises de Planos Navais (RAP NORMAM-202)
 * - Embarcações (Nome, Inscrição na Capitania, Tipo)
 * - Clientes / Armadores / Despachantes (Nome, CPF/CNPJ, E-mail)
 * - Vistorias Técnicas (Número, Embarcação, Finalidade)
 * - Propostas Comerciais (Número, Cliente, Valor)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Nao autenticado']);
    exit;
}

$termo = trim($_GET['q'] ?? '');
if (mb_strlen($termo) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$resultados = [];
$like = '%' . $termo . '%';
$digits = preg_replace('/\D+/', '', $termo);
$likeDigits = '%' . $digits . '%';

// Permissões de acesso
$podeCertificados = podeAcessar('certificados') || podeAcessar('documentacao');
$podeProtocolos   = podeAcessar('protocolos') || podeAcessar('protocolos_documentais');
$podeAnalises     = podeAcessar('analises_planos') || podeAcessar('analise_planos');
$podeEmbarcacoes  = podeAcessar('embarcacoes');
$podeClientes     = podeAcessar('clientes') || podeAcessar('armadores') || podeAcessar('proprietarios') || podeAcessar('despachantes');
$podeVistorias    = podeAcessar('vistorias') || podeAcessar('relatorios_aprovacao');
$podeComercial    = podeAcessar('comercial');

// Formatação amigável de status
function formatarBadgeStatus(?string $status): array {
    $status = strtoupper(trim((string)$status));
    return match ($status) {
        'EMITIDO', 'ASSINADO', 'CONCLUIDA', 'APROVADO', 'ASSINADA', 'ATIVO', '1' => ['label' => 'Emitido / Ativo', 'class' => 'badge-success'],
        'EM_ANDAMENTO', 'EM_ANALISE', 'AGENDADA', 'PENDENTE' => ['label' => 'Em andamento', 'class' => 'badge-info'],
        'AGUARDANDO_ASSINATURA_ANALISTA', 'AGUARDANDO_APROVACAO_ADMIN', 'AGUARDANDO_DOCUMENTOS', 'EXIGENCIAS', 'RASCUNHO' => ['label' => 'Pendente', 'class' => 'badge-warning'],
        'CANCELADO', 'CANCELADA', 'REPROVADA', '0' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        default => ['label' => ucfirst(strtolower(str_replace('_', ' ', $status ?: 'Pendente'))), 'class' => 'badge-neutral']
    };
}

try {
    // ---------------------------------------------------------
    // 1. CERTIFICADOS NAVAIS (CSN, LC, LP, CNBL, CNARQ, CHT)
    // ---------------------------------------------------------
    if ($podeCertificados) {
        // CSN
        $stmt = $pdo->prepare("
            SELECT id, numero, 'CSN' AS modelo, 'Certificado de Segurança da Navegação' AS modelo_desc,
                   nome_embarcacao, status, CONCAT('documentacao/certificados/form?id=', id) AS url
            FROM certificados_csn
            WHERE ativo = 1 AND (numero LIKE ? OR nome_embarcacao LIKE ? OR numero_inscricao LIKE ? OR token_assinatura LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 4
        ");
        $stmt->execute([$like, $like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $embarcacao = !empty($row['nome_embarcacao']) ? $row['nome_embarcacao'] : 'Embarcação não inf.';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero'] . ' · ' . $embarcacao,
                'titulo' => $row['numero'],
                'subtitulo' => 'CSN · ' . $embarcacao,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }

        // LC (Licença de Construção/Alteração/Reclassificação)
        $stmt = $pdo->prepare("
            SELECT id, numero_lc, 'LC' AS modelo, 'Licença de Construção/Alteração' AS modelo_desc,
                   nome_embarcacao, status, CONCAT('documentacao/lc/form?id=', id) AS url
            FROM certificados_lc
            WHERE ativo = 1 AND (numero_lc LIKE ? OR nome_embarcacao LIKE ? OR token_assinatura LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 4
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $embarcacao = !empty($row['nome_embarcacao']) ? $row['nome_embarcacao'] : 'Embarcação não inf.';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero_lc'] . ' · ' . $embarcacao,
                'titulo' => $row['numero_lc'],
                'subtitulo' => 'LC · ' . $embarcacao,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }

        // LP (Licença Provisória)
        $stmt = $pdo->prepare("
            SELECT id, numero_lp, 'LP' AS modelo, nome_embarcacao, status,
                   CONCAT('documentacao/lp/form?id=', id) AS url
            FROM certificados_lp
            WHERE ativo = 1 AND (numero_lp LIKE ? OR nome_embarcacao LIKE ? OR token_assinatura LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 3
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $embarcacao = !empty($row['nome_embarcacao']) ? $row['nome_embarcacao'] : 'Embarcação não inf.';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero_lp'] . ' · ' . $embarcacao,
                'titulo' => $row['numero_lp'],
                'subtitulo' => 'LP · ' . $embarcacao,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }

        // CNBL (Certificado Nacional de Borda Livre)
        $stmt = $pdo->prepare("
            SELECT id, numero, 'CNBL' AS modelo, nome_embarcacao, status,
                   CONCAT('documentacao/cnbl/form?id=', id) AS url
            FROM certificados_cnbl
            WHERE ativo = 1 AND (numero LIKE ? OR nome_embarcacao LIKE ? OR token_assinatura LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 3
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $embarcacao = !empty($row['nome_embarcacao']) ? $row['nome_embarcacao'] : 'Embarcação não inf.';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero'] . ' · ' . $embarcacao,
                'titulo' => $row['numero'],
                'subtitulo' => 'CNBL · ' . $embarcacao,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }

        // CNARQ (Certificado Nacional de Arqueação)
        $stmt = $pdo->prepare("
            SELECT id, numero, 'CNARQ' AS modelo, nome_embarcacao, status,
                   CONCAT('documentacao/cnarq/form?id=', id) AS url
            FROM certificados_cnarq
            WHERE ativo = 1 AND (numero LIKE ? OR nome_embarcacao LIKE ? OR token_assinatura LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 3
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $embarcacao = !empty($row['nome_embarcacao']) ? $row['nome_embarcacao'] : 'Embarcação não inf.';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero'] . ' · ' . $embarcacao,
                'titulo' => $row['numero'],
                'subtitulo' => 'CNARQ · ' . $embarcacao,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }

        // CHT (Certificado de Homologação Técnica)
        $stmt = $pdo->prepare("
            SELECT id, numero_certificado, 'CHT' AS modelo, profissional_empresa, status,
                   CONCAT('documentacao/cht/form?id=', id) AS url
            FROM certificados_cht
            WHERE ativo = 1 AND (numero_certificado LIKE ? OR profissional_empresa LIKE ?)
            ORDER BY criado_em DESC
            LIMIT 3
        ");
        $stmt->execute([$like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $empresa = !empty($row['profissional_empresa']) ? $row['profissional_empresa'] : 'Empresa/Profissional';
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'certificado',
                'categoria' => 'Certificados Navais',
                'categoria_slug' => 'certificados',
                'nome' => $row['numero_certificado'] . ' · ' . $empresa,
                'titulo' => $row['numero_certificado'],
                'subtitulo' => 'CHT · ' . $empresa,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-shield',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 2. DOCUMENTOS & PROTOCOLOS / DOSSIÊS
    // ---------------------------------------------------------
    if ($podeProtocolos) {
        $stmt = $pdo->prepare("
            SELECT d.id, d.numero, d.assunto, d.status, d.protocolo_externo_numero,
                   e.nome AS embarcacao_nome,
                   CONCAT('protocolos/form?id=', d.id) AS url
            FROM protocolo_dossies d
            LEFT JOIN embarcacoes e ON e.id = d.embarcacao_id
            WHERE d.numero LIKE ? OR d.assunto LIKE ? OR d.protocolo_externo_numero LIKE ? OR e.nome LIKE ?
            ORDER BY d.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $detalhe = !empty($row['assunto']) ? $row['assunto'] : 'Dossiê Naval';
            if (!empty($row['embarcacao_nome'])) {
                $detalhe .= ' · ' . $row['embarcacao_nome'];
            }
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'protocolo',
                'categoria' => 'Protocolos & Dossiês',
                'categoria_slug' => 'protocolos',
                'nome' => $row['numero'] . ' · ' . $detalhe,
                'titulo' => $row['numero'],
                'subtitulo' => $detalhe,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-folder-open',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 3. ANÁLISES DE PLANOS NAVAIS (RAP NORMAM-202)
    // ---------------------------------------------------------
    if ($podeAnalises) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.numero, a.objeto, a.tipo_processo, a.status,
                   e.nome AS embarcacao_nome,
                   CONCAT('analises-planos/form?id=', a.id) AS url
            FROM analises_planos a
            LEFT JOIN embarcacoes e ON e.id = a.embarcacao_id
            WHERE a.numero LIKE ? OR a.objeto LIKE ? OR a.responsavel_projeto_nome LIKE ? OR a.art_numero LIKE ? OR e.nome LIKE ?
            ORDER BY a.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $detalhe = (!empty($row['tipo_processo']) ? $row['tipo_processo'] . ': ' : '') . ($row['objeto'] ?: 'Análise Técnica Naval');
            if (!empty($row['embarcacao_nome'])) {
                $detalhe .= ' · ' . $row['embarcacao_nome'];
            }
            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'analise',
                'categoria' => 'Análises de Planos (RAP)',
                'categoria_slug' => 'analises',
                'nome' => $row['numero'] . ' · ' . $detalhe,
                'titulo' => $row['numero'],
                'subtitulo' => $detalhe,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-compass-drafting',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 4. EMBARCAÇÕES
    // ---------------------------------------------------------
    if ($podeEmbarcacoes) {
        $stmt = $pdo->prepare("
            SELECT id, nome, numero_inscricao, tipo_embarcacao, porto_inscricao,
                   CONCAT('embarcacoes/form?id=', id) AS url
            FROM embarcacoes
            WHERE ativo = 1 AND (nome LIKE ? OR numero_inscricao LIKE ? OR porto_inscricao LIKE ?)
            ORDER BY atualizado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $infoSub = [];
            if (!empty($row['numero_inscricao'])) $infoSub[] = 'Inscrição: ' . $row['numero_inscricao'];
            if (!empty($row['tipo_embarcacao'])) $infoSub[] = $row['tipo_embarcacao'];
            if (!empty($row['porto_inscricao'])) $infoSub[] = $row['porto_inscricao'];
            $subtitulo = !empty($infoSub) ? implode(' · ', $infoSub) : 'Cadastro Naval Ativo';

            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'embarcacao',
                'categoria' => 'Embarcações',
                'categoria_slug' => 'embarcacoes',
                'nome' => $row['nome'] . ' · ' . $subtitulo,
                'titulo' => $row['nome'],
                'subtitulo' => $subtitulo,
                'badge' => $row['tipo_embarcacao'] ?: 'Embarcação',
                'badge_class' => 'badge-info',
                'icone' => 'fa-ship',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 5. CLIENTES / ARMADORES / DESPACHANTES
    // ---------------------------------------------------------
    if ($podeClientes) {
        $sqlClientes = "
            SELECT id, nome, perfil, cpf_cnpj, email, status,
                   CONCAT('clientes/form?id=', id) AS url
            FROM clientes
            WHERE status = 'ATIVO' AND (nome LIKE ? OR email LIKE ? OR cpf_cnpj LIKE ?
        ";
        $paramsClientes = [$like, $like, $like];
        if (!empty($digits) && strlen($digits) >= 3) {
            $sqlClientes .= " OR REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), ' ', '') LIKE ?";
            $paramsClientes[] = $likeDigits;
        }
        $sqlClientes .= ") ORDER BY nome ASC LIMIT 5";

        $stmt = $pdo->prepare($sqlClientes);
        $stmt->execute($paramsClientes);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $perfilFormatado = match(strtolower((string)$row['perfil'])) {
                'armador' => 'Armador',
                'despachante' => 'Despachante',
                'proprietario' => 'Proprietário',
                default => 'Cliente'
            };

            $infoSub = [$perfilFormatado];
            if (!empty($row['cpf_cnpj'])) $infoSub[] = $row['cpf_cnpj'];
            if (!empty($row['email'])) $infoSub[] = $row['email'];
            $subtitulo = implode(' · ', $infoSub);

            $resultados[] = [
                'id' => $row['id'],
                'tipo' => $row['perfil'] ?: 'cliente',
                'categoria' => 'Clientes / Armadores',
                'categoria_slug' => 'clientes',
                'nome' => $row['nome'] . ' · ' . $subtitulo,
                'titulo' => $row['nome'],
                'subtitulo' => $subtitulo,
                'badge' => $perfilFormatado,
                'badge_class' => 'badge-neutral',
                'icone' => 'fa-user-tie',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 6. VISTORIAS TÉCNICAS
    // ---------------------------------------------------------
    if ($podeVistorias) {
        $stmt = $pdo->prepare("
            SELECT v.id, v.numero, v.finalidade, v.status, e.nome AS embarcacao_nome,
                   CONCAT('vistorias/detalhe?id=', v.id) AS url
            FROM vistorias v
            INNER JOIN embarcacoes e ON e.id = v.embarcacao_id
            WHERE v.numero LIKE ? OR e.nome LIKE ? OR v.finalidade LIKE ?
            ORDER BY v.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $titulo = !empty($row['numero']) ? $row['numero'] : 'Vistoria: ' . $row['embarcacao_nome'];
            $subtitulo = 'Embarcação: ' . $row['embarcacao_nome'] . (!empty($row['finalidade']) ? ' · ' . $row['finalidade'] : '');

            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'vistoria',
                'categoria' => 'Vistorias Técnicas',
                'categoria_slug' => 'vistorias',
                'nome' => $titulo . ' · ' . $subtitulo,
                'titulo' => $titulo,
                'subtitulo' => $subtitulo,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-clipboard-check',
                'url' => $row['url']
            ];
        }
    }

    // ---------------------------------------------------------
    // 7. PROPOSTAS COMERCIAIS
    // ---------------------------------------------------------
    if ($podeComercial) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.numero, p.valor_total, p.status, c.nome AS cliente_nome,
                   CONCAT('comercial/propostas?busca=', p.numero) AS url
            FROM propostas p
            LEFT JOIN clientes c ON c.id = p.cliente_id
            WHERE p.numero LIKE ? OR c.nome LIKE ?
            ORDER BY p.data_emissao DESC
            LIMIT 5
        ");
        $stmt->execute([$like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $badge = formatarBadgeStatus($row['status']);
            $val = !empty($row['valor_total']) ? 'R$ ' . number_format((float)$row['valor_total'], 2, ',', '.') : 'Valor sob consulta';
            $cliente = !empty($row['cliente_nome']) ? 'Cliente: ' . $row['cliente_nome'] . ' · ' : '';
            $subtitulo = $cliente . $val;

            $resultados[] = [
                'id' => $row['id'],
                'tipo' => 'proposta',
                'categoria' => 'Propostas Comerciais',
                'categoria_slug' => 'comercial',
                'nome' => $row['numero'] . ' · ' . $subtitulo,
                'titulo' => $row['numero'],
                'subtitulo' => $subtitulo,
                'badge' => $badge['label'],
                'badge_class' => $badge['class'],
                'icone' => 'fa-file-invoice-dollar',
                'url' => $row['url']
            ];
        }
    }
} catch (Throwable $e) {
    error_log('Erro na busca global: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno ao realizar a pesquisa'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
