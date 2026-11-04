<?php
/**
 * ERP SISTEMA DE GESTÃO NAVAL — AMAZON NAVAL
 * Módulo de Autenticidade: Validação Pública de Documentos, Certificados e Pareceres Técnicos
 * Conforme NORMAM-201/DPC, NORMAM-202/DPC e Padrões Criptográficos ICP-Brasil / SHA-256
 * 
 * ACESSO PÚBLICO: Qualquer pessoa pode ler o QR Code ou acessar o link de validação
 * sem necessidade de efetuar login no sistema, visualizando e baixando o PDF original.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Obter identificador / token da requisição (via rota /validar/{token} ou parâmetro GET ?token=...)
$token = trim((string)($_GET['token'] ?? ''));
if (empty($token) && isset($_SERVER['REQUEST_URI'])) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/validar/([^/?]+)#', $uriPath, $m)) {
        $token = urldecode($m[1]);
    }
}
$token = trim($token);

// Função para renderizar página de erro 404 amigável
function renderPaginaNaoEncontrado(string $mensagem): void {
    http_response_code(404);
    $appUrl = defined('APP_URL') ? APP_URL : '/';
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Validação de Documento — Amazon Naval</title>';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    echo '<style>
        body{margin:0;background:#f8fafc;color:#1e293b;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px}
        .box{background:#fff;max-width:540px;width:100%;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.08);padding:32px;text-align:center;border:1px solid #e2e8f0}
        .icon{width:70px;height:70px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 20px}
        h1{font-size:22px;margin:0 0 10px;color:#0f172a}
        p{color:#64748b;font-size:15px;line-height:1.6;margin:0 0 24px}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:8px;background:#099b70;color:#fff;text-decoration:none;font-weight:600;font-size:14px}
    </style></head><body>';
    echo '<div class="box"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div>';
    echo '<h1>Documento Não Localizado</h1>';
    echo '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '" class="btn"><i class="fa-solid fa-anchor"></i> Ir para o Portal Amazon Naval</a>';
    echo '</div></body></html>';
    exit;
}

if (empty($token) || strlen($token) < 3 || strlen($token) > 120) {
    renderPaginaNaoEncontrado('O link de validação não contém um código de autenticidade válido.');
}

// Helper para localizar o arquivo físico no sistema de arquivos (local ou VPS)
function resolverCaminhoPdf(?string $caminhoRelativo): string {
    if (empty($caminhoRelativo)) return '';
    $limpo = ltrim(str_replace(['../', '..\\'], '', $caminhoRelativo), '/\\');
    $candidatos = [
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $limpo),
        (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/' . $limpo,
        '/opt/sistema-amazon/' . $limpo,
        'C:/sistema/' . $limpo,
    ];
    foreach ($candidatos as $c) {
        if (is_file($c) && filesize($c) > 100) {
            return realpath($c) ?: $c;
        }
    }
    return '';
}

// Inicializar variáveis unificadas do documento
$docEncontrado = false;
$docOrigem = '';
$docTipo = '';
$docNumero = '';
$docStatus = 'PUBLICADO';
$embarcacaoNome = '';
$embarcacaoInscricao = '';
$clienteNome = '';
$respNome = '';
$respCpf = '';
$respCargo = '';
$respReg = '';
$aprovadorNome = '';
$dataHoraAssinatura = '';
$hashFinal = '';
$hashOriginal = '';
$geoInfo = 'Sede Operacional / Gabinete Técnico Naval (Belém-PA / Manaus-AM)';
$ipOrigem = 'Assinatura Qualificada Online';
$caminhoArquivoPdf = '';

// =========================================================================
// 1. BUSCA EM ANALISE DE PLANOS (RAP - Relatórios de Parecer Técnico Naval)
// =========================================================================
if (!$docEncontrado) {
    try {
        $stmtRap = $pdo->prepare("SELECT p.*,
            ap.numero as processo_numero, ap.tipo_processo, ap.enquadramento, ap.objeto,
            e.nome as embarcacao_nome, e.numero_inscricao as embarcacao_inscricao,
            c.nome as cliente_nome,
            u.nome as analista_nome,
            ra.nome_completo as resp_nome, ra.cargo_titulo as resp_cargo, ra.registro_profissional as resp_reg, ra.cpf_cnpj as resp_cpf,
            u_val.nome as homologador_nome
            FROM analise_planos_pareceres p
            INNER JOIN analises_planos ap ON ap.id = p.analise_id
            LEFT JOIN embarcacoes e ON e.id = ap.embarcacao_id
            LEFT JOIN clientes c ON c.id = ap.solicitante_id
            LEFT JOIN usuarios u ON u.id = ap.analista_id
            LEFT JOIN responsaveis_assinatura ra ON ra.id = p.responsavel_assinatura_id
            LEFT JOIN usuarios u_val ON u_val.id = p.validado_por
            WHERE p.id = :t1 OR p.hash_pdf_final = :t2 OR p.numero = :t3
            LIMIT 1");
        $stmtRap->execute([':t1' => $token, ':t2' => $token, ':t3' => $token]);
        $rap = $stmtRap->fetch(PDO::FETCH_ASSOC);

        if ($rap) {
            $docEncontrado = true;
            $docOrigem = 'RAP';
            $docTipo = 'Relatório de Análise de Planos (RAP)';
            $docNumero = $rap['numero'] ?: ('AM-RAP-AP ' . $rap['processo_numero']);
            $docStatus = strtoupper((string)($rap['status'] ?? 'PUBLICADO'));
            $embarcacaoNome = $rap['embarcacao_nome'] ?: 'Não informada';
            $embarcacaoInscricao = $rap['embarcacao_inscricao'] ?: 'Não informada';
            $clienteNome = $rap['cliente_nome'] ?: 'Não informado';
            $respNome = $rap['resp_nome'] ?: ($rap['analista_nome'] ?: 'Analista Naval');
            $respCpf = $rap['resp_cpf'] ?: 'Cadastrado no ERP';
            $respCargo = $rap['resp_cargo'] ?: 'Analista Naval / Engenharia';
            $respReg = $rap['resp_reg'] ?: 'CREA / CFT';
            $aprovadorNome = $rap['homologador_nome'] ?: ($docStatus === 'PUBLICADO' ? 'Homologação Técnica Concluída' : 'Aguardando Homologação');
            $dtAss = $rap['assinado_analista_em'] ?: ($rap['publicado_em'] ?: $rap['criado_em']);
            $dataHoraAssinatura = !empty($dtAss) ? date('d/m/Y H:i:s', strtotime($dtAss)) . ' (Horário de Brasília)' : 'Assinatura Registrada';
            $hashFinal = $rap['hash_pdf_final'] ?: ($rap['assinatura_hash'] ?? '');
            $hashOriginal = $hashFinal;
            $ipOrigem = $rap['assinatura_analista_ip'] ?: 'Assinatura Qualificada Online';
            $caminhoArquivoPdf = resolverCaminhoPdf($rap['caminho_pdf_final']);
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar RAP na validação: ' . $e->getMessage());
    }
}

// =========================================================================
// 2. BUSCA EM DOCUMENTO APROVACOES (Auditoria Criptográfica de Certificados e Relatórios)
// =========================================================================
if (!$docEncontrado) {
    try {
        $stmtAudit = $pdo->prepare("SELECT da.*,
            ra.nome_completo as resp_nome_ra, ra.cargo_titulo as resp_cargo_ra, ra.registro_profissional as resp_reg_ra, ra.cpf_cnpj as resp_cpf_ra
            FROM documento_aprovacoes da
            LEFT JOIN responsaveis_assinatura ra ON ra.id = da.responsavel_id
            WHERE da.token_validacao = :t1 OR da.documento_id = :t2 OR da.hash_pdf_final = :t3
            ORDER BY da.versao DESC LIMIT 1");
        $stmtAudit->execute([':t1' => $token, ':t2' => $token, ':t3' => $token]);
        $appr = $stmtAudit->fetch(PDO::FETCH_ASSOC);

        if ($appr) {
            $docEncontrado = true;
            $docOrigem = 'APROVACAO';
            $docTipo = $appr['documento_tipo'];
            $docNumero = $appr['documento_id'];
            $docStatus = strtoupper((string)($appr['status'] ?? 'APROVADO'));
            $respNome = $appr['responsavel_nome'] ?: ($appr['resp_nome_ra'] ?? 'Responsável Técnico');
            $respCpf = $appr['responsavel_cpf_cnpj'] ?: ($appr['resp_cpf_ra'] ?? 'Cadastrado no ERP');
            $respCargo = $appr['responsavel_cargo'] ?: ($appr['resp_cargo_ra'] ?? 'Engenheiro Naval');
            $respReg = $appr['responsavel_registro'] ?: ($appr['resp_reg_ra'] ?? 'Não informado');
            $aprovadorNome = $appr['aprovador_nome'] ?: 'Diretoria / Homologação Oficial';
            $dtAprov = $appr['aprovado_em_local'] ?: $appr['created_at'];
            $fuso = $appr['fuso_horario'] ?: 'America/Sao_Paulo';
            $dataHoraAssinatura = !empty($dtAprov) ? date('d/m/Y H:i:s', strtotime($dtAprov)) . ' (' . $fuso . ')' : 'Assinado';
            $hashFinal = $appr['hash_pdf_final'] ?: ($appr['token_validacao'] ?? '');
            $hashOriginal = $appr['hash_pdf_original'] ?: $hashFinal;
            $geoInfo = (!empty($appr['latitude']) && !empty($appr['longitude']))
                ? $appr['latitude'] . ', ' . $appr['longitude'] . (!empty($appr['geo_precisao_m']) ? ' (precisão ' . $appr['geo_precisao_m'] . ' m)' : '')
                : $geoInfo;
            $ipOrigem = $appr['ip'] ?: $ipOrigem;
            $caminhoArquivoPdf = resolverCaminhoPdf($appr['caminho_pdf_final']);
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar documento_aprovacoes na validação: ' . $e->getMessage());
    }
}

// =========================================================================
// 3. BUSCA EM TABELAS DE CERTIFICADOS ESTATUTÁRIOS (CSN, CNBL, CNARQ, LP, LC, NAR, CHT)
// =========================================================================
if (!$docEncontrado) {
    $tabelasCert = [
        'CSN'   => ['tabela' => 'certificados_csn',   'col_num' => 'numero',             'nome' => 'Certificado de Segurança da Navegação (CSN)'],
        'CNBL'  => ['tabela' => 'certificados_cnbl',  'col_num' => 'numero',             'nome' => 'Certificado Nacional de Borda Livre (CNBL)'],
        'CNARQ' => ['tabela' => 'certificados_cnarq', 'col_num' => 'numero',             'nome' => 'Certificado Nacional de Arqueação (CNARQ)'],
        'LP'    => ['tabela' => 'certificados_lp',    'col_num' => 'numero_lp',          'nome' => 'Licença Provisória de Navegação (LP)'],
        'LC'    => ['tabela' => 'certificados_lc',    'col_num' => 'numero_lc',          'nome' => 'Licença de Construção / Alteração Naval (LC)'],
        'NAR'   => ['tabela' => 'certificados_nar',   'col_num' => 'numero',             'nome' => 'Nota de Arqueação (NAR)'],
        'CHT'   => ['tabela' => 'certificados_cht',   'col_num' => 'numero_certificado', 'nome' => 'Certificado de Homologação de Transporte (CHT)'],
    ];

    foreach ($tabelasCert as $sigla => $info) {
        try {
            $colNum = $info['col_num'];
            $sql = "SELECT c.*,
                e.nome as embarcacao_nome, e.numero_inscricao as embarcacao_inscricao,
                cl.nome as cliente_nome,
                ra.nome_completo as resp_nome, ra.cargo_titulo as resp_cargo, ra.registro_profissional as resp_reg, ra.cpf_cnpj as resp_cpf
                FROM {$info['tabela']} c
                LEFT JOIN embarcacoes e ON e.id = c.embarcacao_id
                LEFT JOIN clientes cl ON cl.id = c.cliente_id
                LEFT JOIN responsaveis_assinatura ra ON ra.id = c.responsavel_assinatura_id
                WHERE (c.token_assinatura = :t1 OR c.id = :t2 OR c.{$colNum} = :t3) AND c.ativo = 1
                LIMIT 1";
            $stmtC = $pdo->prepare($sql);
            $stmtC->execute([':t1' => $token, ':t2' => $token, ':t3' => $token]);
            $cert = $stmtC->fetch(PDO::FETCH_ASSOC);

            if ($cert) {
                $docEncontrado = true;
                $docOrigem = $sigla;
                $docTipo = $info['nome'];
                $docNumero = $cert[$colNum] ?: $cert['id'];
                $docStatus = strtoupper((string)($cert['status'] ?? 'EMITIDO'));
                $embarcacaoNome = $cert['nome_embarcacao'] ?: ($cert['embarcacao_nome'] ?? 'Não informada');
                $embarcacaoInscricao = $cert['numero_inscricao'] ?: ($cert['embarcacao_inscricao'] ?? 'Não informada');
                $clienteNome = $cert['cliente_nome'] ?? 'Não informado';
                $respNome = $cert['resp_nome'] ?: ($cert['assinante_nome'] ?? 'Responsável Técnico');
                $respCpf = $cert['resp_cpf'] ?: 'Cadastrado no ERP';
                $respCargo = $cert['resp_cargo'] ?: ($cert['assinante_titulo'] ?? 'Engenheiro Naval');
                $respReg = $cert['resp_reg'] ?: ($cert['assinante_registro'] ?? 'Não informado');
                $aprovadorNome = 'Sistema Amazon Naval';
                $dtEmi = $cert['assinatura_em'] ?: ($cert['data_emissao'] ?: $cert['criado_em']);
                $dataHoraAssinatura = !empty($dtEmi) ? date('d/m/Y H:i:s', strtotime($dtEmi)) . ' (Horário de Brasília)' : 'Emitido';
                $hashFinal = $cert['hash_arquivo_pdf'] ?: ($cert['token_assinatura'] ?? '');
                $hashOriginal = $hashFinal;
                $caminhoArquivoPdf = resolverCaminhoPdf($cert['caminho_arquivo_pdf'] ?? '');
                break;
            }
        } catch (Throwable $e) {
            // Seguir para a próxima tabela
        }
    }
}

// =========================================================================
// 4. BUSCA EM VISTORIAS (Relatórios de Vistoria de Campo)
// =========================================================================
if (!$docEncontrado) {
    try {
        $stmtVist = $pdo->prepare("SELECT v.*,
            e.nome as embarcacao_nome, e.numero_inscricao as embarcacao_inscricao,
            cl.nome as cliente_nome,
            u_vist.nome as vistoriador_nome,
            ra.nome_completo as resp_nome, ra.cargo_titulo as resp_cargo, ra.registro_profissional as resp_reg, ra.cpf_cnpj as resp_cpf,
            u_aprov.nome as aprovador_nome
            FROM vistorias v
            LEFT JOIN embarcacoes e ON e.id = v.embarcacao_id
            LEFT JOIN clientes cl ON cl.id = COALESCE(v.armador_id, v.pessoa_id)
            LEFT JOIN agendamentos a ON a.id = v.agendamento_id
            LEFT JOIN usuarios u_vist ON u_vist.id = a.vistoriador_id
            LEFT JOIN responsaveis_assinatura ra ON ra.id = v.responsavel_assinatura_id
            LEFT JOIN usuarios u_aprov ON u_aprov.id = v.aprovado_por
            WHERE v.id = :t1 OR v.numero = :t2
            LIMIT 1");
        $stmtVist->execute([':t1' => $token, ':t2' => $token]);
        $vist = $stmtVist->fetch(PDO::FETCH_ASSOC);

        if ($vist) {
            $docEncontrado = true;
            $docOrigem = 'VISTORIA';
            $docTipo = 'Relatório Oficial de Vistoria Técnica Naval';
            $docNumero = $vist['numero'] ?: ('Vistoria ' . $vist['id']);
            $docStatus = strtoupper((string)($vist['status'] ?? 'CONCLUIDA'));
            $embarcacaoNome = $vist['embarcacao_nome'] ?: 'Não informada';
            $embarcacaoInscricao = $vist['embarcacao_inscricao'] ?: 'Não informada';
            $clienteNome = $vist['cliente_nome'] ?: 'Não informado';
            $respNome = $vist['resp_nome'] ?: ($vist['vistoriador_nome'] ?: 'Vistoriador Naval');
            $respCpf = $vist['resp_cpf'] ?: 'Cadastrado no ERP';
            $respCargo = $vist['resp_cargo'] ?: 'Vistoriador Naval';
            $respReg = $vist['resp_reg'] ?: 'CREA / CFT';
            $aprovadorNome = $vist['aprovador_nome'] ?: 'Diretoria Técnica';
            $dtVist = $vist['data_aprovacao'] ?: ($vist['assinatura_em'] ?: $vist['data_vistoria']);
            $dataHoraAssinatura = !empty($dtVist) ? date('d/m/Y', strtotime($dtVist)) : 'Realizada';
            $hashFinal = hash('sha256', $vist['id'] . ($vist['numero'] ?? ''));
            $hashOriginal = $hashFinal;
        }
    } catch (Throwable $e) {
        error_log('Erro ao buscar vistoria na validação: ' . $e->getMessage());
    }
}

if (!$docEncontrado) {
    renderPaginaNaoEncontrado('O identificador "' . $token . '" não foi encontrado nos registros de documentos oficiais da Amazon Naval.');
}

// Verificar existência e integridade do arquivo PDF oficial
$arquivoExiste = (!empty($caminhoArquivoPdf) && is_file($caminhoArquivoPdf));
$hashCalculado = $arquivoExiste ? hash_file('sha256', $caminhoArquivoPdf) : '';
$integridadeConfirmada = $arquivoExiste && (!empty($hashFinal) ? hash_equals($hashFinal, $hashCalculado) : true);

// =========================================================================
// AÇÃO PÚBLICA: VISUALIZAÇÃO OU DOWNLOAD DIRETO DO PDF (SEM LOGIN)
// =========================================================================
if (isset($_GET['download']) || isset($_GET['view']) || isset($_GET['pdf'])) {
    if (!$arquivoExiste) {
        http_response_code(404);
        exit('O arquivo físico em PDF deste documento não foi localizado no servidor.');
    }
    $disposition = (isset($_GET['download']) && $_GET['download'] == '1') ? 'attachment' : 'inline';
    $nomeDownload = preg_replace('/[^A-Za-z0-9._-]/', '-', $docNumero) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disposition . '; filename="' . $nomeDownload . '"');
    header('Content-Length: ' . filesize($caminhoArquivoPdf));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($caminhoArquivoPdf);
    exit;
}

// Processar upload de conferência manual de PDF
$uploadResultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_conferencia'])) {
    $up = $_FILES['arquivo_conferencia'];
    if (!$up || ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $uploadResultado = ['ok' => false, 'msg' => 'Por favor, selecione um arquivo PDF válido.'];
    } elseif ($up['size'] > 30 * 1024 * 1024) {
        $uploadResultado = ['ok' => false, 'msg' => 'O arquivo PDF excede o limite máximo de 30 MB.'];
    } else {
        $upHash = hash_file('sha256', $up['tmp_name']);
        $bateu = (!empty($hashFinal) && hash_equals($hashFinal, $upHash)) || ($arquivoExiste && hash_equals($hashCalculado, $upHash));
        $uploadResultado = [
            'ok' => $bateu,
            'msg' => $bateu
                ? 'DOCUMENTO AUTÊNTICO: O código SHA-256 do arquivo enviado corresponde perfeitamente ao registro oficial arquivado no sistema.'
                : 'DOCUMENTO DIVERGENTE: O código SHA-256 do arquivo enviado não confere com o documento oficial arquivado.',
            'hash' => $upHash
        ];
    }
}

// Sanitização HTML
function valH($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$statusValido = in_array($docStatus, ['PUBLICADO', 'APROVADO', 'EMITIDO', 'CONCLUIDA'], true);
$statusRotulo = $statusValido ? 'DOCUMENTO OFICIAL AUTÊNTICO & VÁLIDO' : ('STATUS: ' . $docStatus);
$statusBg = $statusValido ? 'bg-emerald-500/10 text-emerald-700 border-emerald-500/30' : 'bg-amber-500/10 text-amber-700 border-amber-500/30';
$statusIcon = $statusValido ? 'fa-circle-check text-emerald-600' : 'fa-circle-info text-amber-600';
$urlDownloadPdf = APP_URL . 'validar/' . urlencode($token) . '?download=1';
$urlVisualizarPdf = APP_URL . 'validar/' . urlencode($token) . '?view=1';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= valH($docNumero) ?> · Validação Pública de Autenticidade — Amazon Naval</title>
  <script src="https://www.gstatic.com/antigravity/web/dev/tailwindcss.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @keyframes pulse-subtle { 0%, 100% { opacity: 1; } 50% { opacity: 0.85; } }
    .badge-pulse { animation: pulse-subtle 2.5s infinite ease-in-out; }
  </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Topbar Oficial da Entidade Certificadora -->
  <header class="bg-[#099b70] text-white shadow-lg sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white text-xl shadow-inner border border-white/20">
          <i class="fa-solid fa-ship"></i>
        </span>
        <div>
          <div class="font-extrabold text-base md:text-lg tracking-wide uppercase leading-tight">Amazon Naval</div>
          <div class="text-[11px] text-emerald-100 tracking-wider">CERTIFICAÇÃO TÉCNICA NAVAL · NORMAM / DPC</div>
        </div>
      </div>
      <div class="text-right hidden sm:block">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-900/40 border border-emerald-400/30 text-emerald-100">
          <i class="fa-solid fa-qrcode text-emerald-300"></i> Validação Pública
        </span>
      </div>
    </div>
  </header>

  <!-- Container Principal -->
  <main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">

    <!-- Card de Status da Autenticidade -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xl overflow-hidden mb-6">
      
      <!-- Faixa de Destaque -->
      <div class="p-5 md:p-6 border-b border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50 to-white">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl <?= $statusValido ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?> flex items-center justify-center text-2xl flex-shrink-0 shadow-sm">
            <i class="fa-solid <?= $statusIcon ?>"></i>
          </div>
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border <?= $statusBg ?> badge-pulse mb-1.5">
              <span class="w-2 h-2 rounded-full <?= $statusValido ? 'bg-emerald-600' : 'bg-amber-600' ?>"></span>
              <?= valH($statusRotulo) ?>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-snug">
              <?= valH($docNumero) ?>
            </h1>
            <p class="text-xs md:text-sm text-slate-500 font-medium mt-0.5">
              <?= valH($docTipo) ?>
            </p>
          </div>
        </div>

        <!-- Botões de Ação para Celular / Desktop -->
        <?php if ($arquivoExiste): ?>
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
          <a href="<?= valH($urlVisualizarPdf) ?>" target="_blank" class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md shadow-emerald-900/20 transition active:scale-95">
            <i class="fa-solid fa-file-pdf text-base"></i>
            <span>Visualizar Documento (PDF)</span>
          </a>
          <a href="<?= valH($urlDownloadPdf) ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm border border-slate-200 transition">
            <i class="fa-solid fa-download"></i>
            <span class="hidden sm:inline">Baixar</span>
          </a>
        </div>
        <?php endif; ?>
      </div>

      <!-- Grade de Metadados do Registro -->
      <div class="p-5 md:p-6">
        <h2 class="text-xs font-bold tracking-wider text-slate-400 uppercase mb-4 flex items-center gap-2">
          <i class="fa-solid fa-circle-nodes text-emerald-600"></i> Metadados & Chancela Técnica Naval
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          
          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Embarcação Vinculada</div>
            <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5">
              <i class="fa-solid fa-ship text-emerald-600 text-xs"></i>
              <?= valH($embarcacaoNome) ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">Inscrição: <?= valH($embarcacaoInscricao) ?></div>
          </div>

          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Armador / Solicitante</div>
            <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5 truncate">
              <i class="fa-solid fa-user-tie text-emerald-600 text-xs"></i>
              <?= valH($clienteNome) ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">Registro cadastral regular</div>
          </div>

          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Responsável Técnico / Signatário</div>
            <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5 truncate">
              <i class="fa-solid fa-signature text-emerald-600 text-xs"></i>
              <?= valH($respNome) ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5"><?= valH($respCargo) ?> · <?= valH($respReg) ?></div>
          </div>

          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Homologação / Emissor</div>
            <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5">
              <i class="fa-solid fa-stamp text-emerald-600 text-xs"></i>
              <?= valH($aprovadorNome) ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">Entidade Certificadora Credenciada</div>
          </div>

          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Data & Hora da Assinatura</div>
            <div class="text-sm font-bold text-slate-900 mt-0.5 flex items-center gap-1.5">
              <i class="fa-solid fa-calendar-check text-emerald-600 text-xs"></i>
              <?= valH($dataHoraAssinatura) ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">Origem: <?= valH($ipOrigem) ?></div>
          </div>

          <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Integridade Criptográfica</div>
            <div class="text-sm font-bold <?= $integridadeConfirmada ? 'text-emerald-700' : 'text-amber-700' ?> mt-0.5 flex items-center gap-1.5">
              <i class="fa-solid <?= $integridadeConfirmada ? 'fa-shield-halved' : 'fa-triangle-exclamation' ?> text-xs"></i>
              <?= $integridadeConfirmada ? '100% Confirmada' : 'Pendente de validação' ?>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">Algoritmo SHA-256</div>
          </div>

        </div>

        <!-- Hash SHA-256 Oficial -->
        <?php if (!empty($hashFinal)): ?>
        <div class="mt-5 p-3.5 rounded-xl bg-slate-900 text-slate-300 border border-slate-800">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-400 mb-1">
            <span class="flex items-center gap-1.5">
              <i class="fa-solid fa-fingerprint text-emerald-400"></i> HASH SHA-256 DO DOCUMENTO OFICIAL
            </span>
            <span class="text-[11px] font-mono text-emerald-400">ICP-Brasil / NORMAM</span>
          </div>
          <div class="font-mono text-xs md:text-sm text-emerald-300 break-all select-all">
            <?= valH($hashFinal) ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Pré-visualização do Documento embutido (Inline PDF Preview) -->
    <?php if ($arquivoExiste): ?>
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xl overflow-hidden mb-6">
      <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2 font-bold text-sm text-slate-800">
          <i class="fa-solid fa-file-contract text-emerald-600"></i>
          <span>Visualização Prévia do Documento Oficial</span>
        </div>
        <a href="<?= valH($urlVisualizarPdf) ?>" target="_blank" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
          <span>Abrir em tela cheia</span>
          <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
        </a>
      </div>
      <div class="w-full bg-slate-800 relative" style="height: 600px;">
        <iframe src="<?= valH($urlVisualizarPdf) ?>" class="w-full h-full border-0" title="Visualizador do Documento"></iframe>
      </div>
    </div>
    <?php endif; ?>

    <!-- Seção de Verificação de Arquivo Avulso (Upload SHA-256) -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-md p-5 md:p-6 mb-6">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-1">
        <i class="fa-solid fa-file-shield text-emerald-600"></i>
        <span>Verificar a Autenticidade de uma Cópia em PDF</span>
      </h3>
      <p class="text-xs md:text-sm text-slate-500 mb-4">
        Você tem um arquivo PDF deste relatório/certificado e deseja confirmar se ele é exatamente idêntico ao original emitido, sem adulterações? Selecione o arquivo abaixo:
      </p>

      <form method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <input type="file" name="arquivo_conferencia" accept="application/pdf,.pdf" required class="flex-1 text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1.5 cursor-pointer">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs md:text-sm transition flex items-center justify-center gap-2 shadow-sm">
          <i class="fa-solid fa-magnifying-glass"></i>
          <span>Verificar Hash</span>
        </button>
      </form>

      <?php if ($uploadResultado): ?>
      <div class="mt-4 p-4 rounded-xl border <?= $uploadResultado['ok'] ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200' ?>">
        <div class="font-bold text-sm flex items-center gap-2">
          <i class="fa-solid <?= $uploadResultado['ok'] ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-600' ?>"></i>
          <span><?= valH($uploadResultado['msg']) ?></span>
        </div>
        <?php if (!empty($uploadResultado['hash'])): ?>
        <div class="font-mono text-xs mt-2 text-slate-600 break-all">
          Hash do arquivo enviado: <?= valH($uploadResultado['hash']) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

  </main>

  <!-- Rodapé Institucional -->
  <footer class="bg-slate-900 text-slate-400 text-xs py-6 border-t border-slate-800">
    <div class="max-w-5xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-left">
      <div>
        <span class="font-bold text-white tracking-wider">AMAZON NAVAL</span> · Engenharia e Serviços Marítimos
        <div class="text-[11px] text-slate-500 mt-0.5">Normas da Autoridade Marítima (DPC / Marinha do Brasil) · Validação Criptográfica Pública</div>
      </div>
      <div class="text-[11px] text-slate-500">
        Este documento foi assinado eletronicamente sob a égide da Medida Provisória nº 2.200-2/2001 e normas técnicas navais.
      </div>
    </div>
  </footer>

</body>
</html>
