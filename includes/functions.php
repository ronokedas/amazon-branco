<?php
/**
 * FUNÇÕES UTILITÁRIAS DO SISTEMA ERP
 */

// Sanitizar input
function sanitizar($dados) {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
    return $dados;
}

// Alias para compatibilidade
if (!function_exists('h')) {
    function h($dados) {
        return sanitizar($dados);
    }
}

if (!function_exists('sanitize')) {
    function sanitize($dados) {
        return sanitizar($dados);
    }
}

function certificadoTipoEmbarcacaoEhBalsa($tipo_embarcacao) {
    $tipo = trim((string)$tipo_embarcacao);
    if ($tipo === '') {
        return false;
    }

    if (function_exists('mb_strtolower')) {
        $tipo = mb_strtolower($tipo, 'UTF-8');
    } else {
        $tipo = strtolower($tipo);
    }

    return $tipo === 'balsa';
}

function certificadoAnosValidadePorTipoEmbarcacao($tipo_embarcacao) {
    return certificadoTipoEmbarcacaoEhBalsa($tipo_embarcacao) ? 10 : 5;
}

function certificadoNomesConvalidacoes($tipo_embarcacao) {
    $qtd = certificadoAnosValidadePorTipoEmbarcacao($tipo_embarcacao) - 1;
    $nomes = [];

    for ($i = 1; $i <= $qtd; $i++) {
        $nomes[] = "{$i}ª VIST. ANUAL";
    }

    return $nomes;
}

function certificadoConvalidacoesPorNumero(array $convalidacoes) {
    $mapa = [];

    foreach ($convalidacoes as $conv) {
        if (preg_match('/\d+/', (string)($conv['numero_vistoria'] ?? ''), $m)) {
            $mapa[(int)$m[0]] = $conv;
        }
    }

    ksort($mapa);
    return $mapa;
}


// Gerar CSRF token
function gerarCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar CSRF token
function verificarCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifica se um endereco IP pertence a um IP ou bloco CIDR confiavel.
 */
function ipPertenceAoBloco(string $ip, string $bloco): bool
{
    $ip = trim($ip);
    $bloco = trim($bloco);
    if (!filter_var($ip, FILTER_VALIDATE_IP) || $bloco === '') {
        return false;
    }

    if (!str_contains($bloco, '/')) {
        return filter_var($bloco, FILTER_VALIDATE_IP) !== false && $ip === $bloco;
    }

    [$rede, $prefixo] = array_pad(explode('/', $bloco, 2), 2, null);
    if (!filter_var($rede, FILTER_VALIDATE_IP) || !is_numeric($prefixo)) {
        return false;
    }

    $ipBinario = inet_pton($ip);
    $redeBinaria = inet_pton($rede);
    if ($ipBinario === false || $redeBinaria === false || strlen($ipBinario) !== strlen($redeBinaria)) {
        return false;
    }

    $totalBits = strlen($ipBinario) * 8;
    $prefixo = (int)$prefixo;
    if ($prefixo < 0 || $prefixo > $totalBits) {
        return false;
    }

    $bytesCompletos = intdiv($prefixo, 8);
    if ($bytesCompletos > 0 && substr($ipBinario, 0, $bytesCompletos) !== substr($redeBinaria, 0, $bytesCompletos)) {
        return false;
    }

    $bitsRestantes = $prefixo % 8;
    if ($bitsRestantes === 0) {
        return true;
    }

    $mascara = (0xFF << (8 - $bitsRestantes)) & 0xFF;
    return (ord($ipBinario[$bytesCompletos]) & $mascara) === (ord($redeBinaria[$bytesCompletos]) & $mascara);
}

/**
 * Retorna o IP publico do cliente sem confiar em cabecalhos de proxies arbitrarios.
 * As faixas padrao sao as publicadas pela Cloudflare em https://www.cloudflare.com/ips/.
 */
function obterIpCliente(): string
{
    $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!filter_var($remote, FILTER_VALIDATE_IP)) {
        return '0.0.0.0';
    }

    $cloudflarePadrao = implode(',', [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32',
        '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ]);
    $configurada = trim((string)(getenv('TRUSTED_PROXY_CIDRS') ?: ''));
    $blocos = preg_split('/[\s,;]+/', $configurada !== '' ? $configurada : $cloudflarePadrao, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $proxyConfiavel = false;
    foreach ($blocos as $bloco) {
        if (ipPertenceAoBloco($remote, $bloco)) {
            $proxyConfiavel = true;
            break;
        }
    }

    if (!$proxyConfiavel) {
        return $remote;
    }

    $cfIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
        return $cfIp;
    }

    foreach (explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')) as $encaminhado) {
        $encaminhado = trim($encaminhado);
        if (filter_var($encaminhado, FILTER_VALIDATE_IP)) {
            return $encaminhado;
        }
    }

    return $remote;
}

/**
 * Registra um envio mutavel e rejeita a reutilizacao do mesmo identificador.
 *
 * O token e criado no navegador para cada formulario POST. Como a sessao PHP
 * permanece bloqueada durante a requisicao, dois cliques simultaneos sao
 * serializados e apenas o primeiro consegue registrar o token.
 */
function aceitarEnvioUnico(): bool {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return true;
    }

    $token = trim((string)($_POST['_submission_token'] ?? ''));
    if ($token === '') {
        // Formularios antigos continuam protegidos pela assinatura do payload.
        // APIs sem CSRF ficam fora desta regra para preservar sincronizacoes e
        // integracoes que possuem sua propria estrategia de idempotencia.
        if (empty($_POST['csrf_token'])) {
            return true;
        }

        $normalizar = static function ($valor) use (&$normalizar) {
            if (!is_array($valor)) {
                return is_string($valor) ? trim($valor) : $valor;
            }
            if (!array_is_list($valor)) {
                ksort($valor);
            }
            foreach ($valor as $chave => $item) {
                $valor[$chave] = $normalizar($item);
            }
            return $valor;
        };

        $payload = $_POST;
        unset($payload['csrf_token'], $payload['_submission_token']);
        $arquivos = [];
        foreach ($_FILES as $campo => $arquivo) {
            $arquivos[$campo] = [
                'name' => $arquivo['name'] ?? null,
                'size' => $arquivo['size'] ?? null,
                'error' => $arquivo['error'] ?? null,
            ];
        }

        $assinatura = hash('sha256', serialize([
            'rota' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'payload' => $normalizar($payload),
            'arquivos' => $normalizar($arquivos),
        ]));
        $agora = time();
        $janelaRepeticao = 2 * 60;
        $assinaturas = $_SESSION['_envios_recentes'] ?? [];

        foreach ($assinaturas as $assinaturaRegistrada => $registradaEm) {
            if (!is_int($registradaEm) || ($agora - $registradaEm) > $janelaRepeticao) {
                unset($assinaturas[$assinaturaRegistrada]);
            }
        }

        if (isset($assinaturas[$assinatura])) {
            $_SESSION['_envios_recentes'] = $assinaturas;
            return false;
        }

        $assinaturas[$assinatura] = $agora;
        if (count($assinaturas) > 100) {
            asort($assinaturas);
            $assinaturas = array_slice($assinaturas, -100, null, true);
        }
        $_SESSION['_envios_recentes'] = $assinaturas;
        return true;
    }

    if (!preg_match('/^[A-Za-z0-9_-]{16,100}$/', $token)) {
        return false;
    }

    $agora = time();
    $validade = 6 * 60 * 60;
    $envios = $_SESSION['_envios_processados'] ?? [];

    foreach ($envios as $tokenRegistrado => $registradoEm) {
        if (!is_int($registradoEm) || ($agora - $registradoEm) > $validade) {
            unset($envios[$tokenRegistrado]);
        }
    }

    if (isset($envios[$token])) {
        $_SESSION['_envios_processados'] = $envios;
        return false;
    }

    $envios[$token] = $agora;
    if (count($envios) > 200) {
        asort($envios);
        $envios = array_slice($envios, -200, null, true);
    }
    $_SESSION['_envios_processados'] = $envios;
    return true;
}

/**
 * Encerra uma repeticao sem executar novamente a acao de gravacao.
 */
function responderEnvioDuplicado(): never {
    $mensagem = 'Este envio ja esta sendo processado ou foi concluido. Nenhum registro duplicado foi criado.';
    $aceitaJson = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
        || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

    if ($aceitaJson) {
        http_response_code(409);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'duplicate' => true,
            'message' => $mensagem,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    setMensagem('warning', $mensagem);

    $destino = APP_URL . 'dashboard';
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $hostApp = parse_url(APP_URL, PHP_URL_HOST);
        $hostReferer = parse_url($referer, PHP_URL_HOST);
        if ($hostApp && $hostReferer && strcasecmp($hostApp, $hostReferer) === 0) {
            $destino = $referer;
        }
    }

    header('Location: ' . $destino, true, 303);
    exit;
}

function podeExcluirProprioAdministrador(int $totalAdministradores): bool {
    return $totalAdministradores > 1;
}

// Redirecionar
function redirecionar($url) {
    header('Location: ' . $url);
    exit;
}

// Remover dados sensiveis antes de preservar um formulario com erro.
function filtrarDadosFormulario($dados) {
    $bloqueados = [
        'csrf_token', 'action', 'senha', 'senha_confirma', 'senha_atual',
        'nova_senha', 'confirmar_senha', 'password', 'token', 'assinatura_imagem'
    ];
    $resultado = [];

    foreach ((array)$dados as $chave => $valor) {
        if (in_array((string)$chave, $bloqueados, true)) {
            continue;
        }
        $resultado[$chave] = is_array($valor)
            ? filtrarDadosFormulario($valor)
            : (string)$valor;
    }

    return $resultado;
}

// Definir mensagem e, em erros de POST, preservar valores e erros por campo.
// $campos aceita ['email' => 'Informe um e-mail valido.'] ou ['email', 'nome'].
function setMensagem($tipo, $mensagem, $campos = []) {
    $_SESSION['mensagem'] = [
        'tipo' => $tipo, // success, error, warning, info
        'texto' => $mensagem,
        'campos' => $campos,
    ];

    if ($tipo === 'error' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $_SESSION['mensagem']['valores'] = filtrarDadosFormulario($_POST);
    }
}

// Obter e limpar mensagem
function getMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $msg = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return $msg;
    }
    return null;
}

// Formatar moeda
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Formatador de CPF
function formatarCPF($cpf) {
    if (empty($cpf)) return '';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Validar CPF
function validarCPF($cpf) {
    if (empty($cpf)) return false;
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $i = 0; $i < $t; $i++) {
            $d += $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$t] != $d) return false;
    }
    return true;
}

/**
 * Validar CNPJ
 */
function validarCNPJ($cnpj) {
    if (empty($cnpj)) return false;
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

    // Validar dígitos verificadores
    for ($t = 12; $t < 14; $t++) {
        $d = 0;
        $m = 5;
        for ($i = 0; $i < $t; $i++) {
            $d += $cnpj[$i] * $m;
            $m = ($m == 2) ? 9 : $m - 1;
        }
        $d = (($d % 11) < 2) ? 0 : (11 - ($d % 11));
        if ($cnpj[$t] != $d) return false;
    }
    return true;
}

// Validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Gerar UUID
function gerarUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Upload de arquivo
function uploadArquivo($arquivo, $pasta = 'uploads/') {
    if (!isset($arquivo['tmp_name']) || empty($arquivo['tmp_name'])) {
        return ['success' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
    }
    
    $nomeArquivo = uniqid() . '_' . basename($arquivo['name']);
    $caminho = $pasta . $nomeArquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        return ['success' => true, 'caminho' => $caminho];
    }
    
    return ['success' => false, 'mensagem' => 'Erro ao fazer upload.'];
}

// Upload para Storage S3/MinIO
function upload_to_storage($base64_string, $folder = 'assinaturas') {
    // Retorna string vazia se não for base64
    if (empty($base64_string) || strpos($base64_string, 'data:image') !== 0) {
        return '';
    }

    // Extrair extensão e binário
    preg_match('/^data:image\/(\w+);base64,/', $base64_string, $matches);
    $ext = isset($matches[1]) ? $matches[1] : 'png';
    $binary = base64_decode(substr($base64_string, strpos($base64_string, ',') + 1));

    if (!$binary) return '';

    $filename = $folder . '/' . uniqid() . '_' . time() . '.' . $ext;

    // Carregar o SDK quando esta funcao for chamada por uma pagina publica
    // que nao passou pelo autoloader principal da aplicacao.
    if (!class_exists('Aws\\S3\\S3Client') && defined('BASE_PATH')) {
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }

    // Se estivermos usando AWS SDK
    if (class_exists('Aws\S3\S3Client')) {
        try {
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1', // MinIO default
                'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => defined('MINIO_ACCESS_KEY') ? MINIO_ACCESS_KEY : 'erp_minio_admin',
                    'secret' => defined('MINIO_SECRET_KEY') ? MINIO_SECRET_KEY : 'erp_minio_pass_2026',
                ],
            ]);

            $bucket = defined('MINIO_BUCKET') ? MINIO_BUCKET : 'erp-storage';

            // Criar bucket se não existir
            try {
                $s3->headBucket(['Bucket' => $bucket]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if ($e->getStatusCode() === 404) {
                    $s3->createBucket(['Bucket' => $bucket]);
                    $s3->putBucketPolicy([
                        'Bucket' => $bucket,
                        'Policy' => json_encode([
                            'Version' => '2012-10-17',
                            'Statement' => [
                                [
                                    'Effect' => 'Allow',
                                    'Principal' => '*',
                                    'Action' => 's3:GetObject',
                                    'Resource' => "arn:aws:s3:::$bucket/*"
                                ]
                            ]
                        ])
                    ]);
                }
            }

            $result = $s3->putObject([
                'Bucket'      => $bucket,
                'Key'         => $filename,
                'Body'        => $binary,
                'ContentType' => 'image/' . $ext,
            ]);

            // Se quisermos a URL pública, pegamos do endpoint e do bucket
            // Usando o APP_URL para proxy ou o IP direto se for local
            // No caso docker: http://localhost:9002/erp-storage/...
            $publicUrl = str_replace('http://minio:9000', 'http://localhost:9002', $result['ObjectURL']);
            return $publicUrl;

        } catch (Exception $e) {
            error_log('Erro no upload para S3/MinIO: ' . $e->getMessage());
        }
    }

    // Fallback: salvar localmente em storage local (UPLOADS_PATH)
    if (!is_dir(UPLOADS_PATH . $folder)
        && !mkdir(UPLOADS_PATH . $folder, 0755, true)
        && !is_dir(UPLOADS_PATH . $folder)) {
        error_log('Nao foi possivel criar a pasta local da assinatura: ' . UPLOADS_PATH . $folder);
        return '';
    }
    $local_path = UPLOADS_PATH . $filename;
    if (file_put_contents($local_path, $binary, LOCK_EX) === false) {
        error_log('Nao foi possivel salvar a assinatura localmente: ' . $local_path);
        return '';
    }
    return APP_URL . 'uploads/' . $filename;
}

/**
 * Recupera uma assinatura armazenada como data URI, arquivo local ou objeto S3.
 * O retorno normalizado permite que a pagina publica e o PDF usem exatamente
 * a mesma fonte, sem depender de uma URL acessivel pelo navegador.
 */
function carregarImagemAssinatura($dataUri = '', $url = '') {
    $normalizar = static function ($bytes) {
        if (!is_string($bytes) || $bytes === '' || strlen($bytes) > 5 * 1024 * 1024) {
            return null;
        }

        $info = @getimagesizefromstring($bytes);
        $mime = strtolower((string)($info['mime'] ?? ''));
        $tipos = [
            'image/png' => ['extensao' => 'png', 'tipo_pdf' => 'PNG'],
            'image/jpeg' => ['extensao' => 'jpg', 'tipo_pdf' => 'JPG'],
        ];

        if (!isset($tipos[$mime])) {
            return null;
        }

        return [
            'bytes' => $bytes,
            'mime' => $mime,
            'extensao' => $tipos[$mime]['extensao'],
            'tipo_pdf' => $tipos[$mime]['tipo_pdf'],
        ];
    };

    if (is_string($dataUri) && preg_match('#^data:image/(?:png|jpe?g);base64,(.+)$#is', trim($dataUri), $matches)) {
        $bytes = base64_decode(preg_replace('/\\s+/', '', $matches[1]), true);
        $imagem = $normalizar($bytes);
        if ($imagem !== null) {
            return $imagem;
        }
    }

    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    $pathUrl = (string)(parse_url($url, PHP_URL_PATH) ?? '');
    $marcadorUploads = '/uploads/';
    $posUploads = strpos($pathUrl, $marcadorUploads);
    if ($posUploads !== false && defined('UPLOADS_PATH')) {
        $relativo = rawurldecode(substr($pathUrl, $posUploads + strlen($marcadorUploads)));
        $relativo = str_replace(['../', '..\\'], '', $relativo);
        $arquivo = rtrim(UPLOADS_PATH, '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativo, '/'));

        if (is_file($arquivo)) {
            $imagem = $normalizar(@file_get_contents($arquivo));
            if ($imagem !== null) {
                return $imagem;
            }
        }
    }

    if (!class_exists('Aws\\S3\\S3Client') && defined('BASE_PATH')) {
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }

    $bucket = defined('MINIO_BUCKET') ? MINIO_BUCKET : 'erp-storage';
    $pathObjeto = ltrim($pathUrl, '/');
    if (class_exists('Aws\\S3\\S3Client') && str_starts_with($pathObjeto, $bucket . '/')) {
        try {
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => defined('MINIO_ENDPOINT') ? MINIO_ENDPOINT : 'http://minio:9000',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => defined('MINIO_ACCESS_KEY') ? MINIO_ACCESS_KEY : 'erp_minio_admin',
                    'secret' => defined('MINIO_SECRET_KEY') ? MINIO_SECRET_KEY : 'erp_minio_pass_2026',
                ],
            ]);
            $resultado = $s3->getObject([
                'Bucket' => $bucket,
                'Key' => substr($pathObjeto, strlen($bucket) + 1),
            ]);
            $imagem = $normalizar((string)$resultado['Body']);
            if ($imagem !== null) {
                return $imagem;
            }
        } catch (Exception $e) {
            error_log('Erro ao recuperar assinatura no S3/MinIO: ' . $e->getMessage());
        }
    }

    if (preg_match('#^https?://#i', $url)) {
        $contexto = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 8],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $imagem = $normalizar(@file_get_contents($url, false, $contexto));
        if ($imagem !== null) {
            return $imagem;
        }
    }

    return null;
}

// Data em português
function formatarData($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y');
}

// Data completa em português
function formatarDataCompleta($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y - H:i');
}

// Hook para eventos do sistema
function hook($nome, $dados = []) {
    global $hooks;
    if (isset($hooks[$nome])) {
        foreach ($hooks[$nome] as $callback) {
            $callback($dados);
        }
    }
}

// Register hook callback
function addHook($nome, $callback) {
    global $hooks;
    $hooks[$nome][] = $callback;
}

// H Função auxiliar para echo seguro
if (!function_exists('h')) {
    function h($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}

// Obter total de registros por mês
function getTotalPorMes($tabela, $campo_data, $ano) {
    global $pdo;
    $sql = "SELECT COUNT(*) as total FROM {$tabela} 
            WHERE YEAR({$campo_data}) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ano]);
    return $stmt->fetch()['total'];
}

// Log de atividades do sistema (arquivo)
function log_atividade($acao, $descricao = '', $usuario_id = null) {
    global $pdo;
    $usuario_id = $usuario_id ?? ($_SESSION['usuario_id'] ?? 'sistema');
    $ip = obterIpCliente();
    $data = date('Y-m-d H:i:s');
    
    // Tentar salvar no banco se a tabela existir
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, descricao, ip, criado_em) 
                               VALUES (:usuario, :acao, :descricao, :ip, :data)");
        $stmt->execute([
            ':usuario'  => $usuario_id,
            ':acao'     => $acao,
            ':descricao' => $descricao,
            ':ip'       => $ip,
            ':data'     => $data,
        ]);
    } catch (Exception $e) {
        // Se a tabela não existe, salvar em arquivo
        $logs_dir = __DIR__ . '/../logs/';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
        $arquivo = $logs_dir . 'atividades_' . date('Y-m-d') . '.log';
        $linha = "[{$data}] [{$ip}] [{$usuario_id}] {$acao}: {$descricao}" . PHP_EOL;
        @file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Gera um número sequencial para documentos do sistema.
 * Usa SELECT FOR UPDATE dentro de transação para garantir atomicidade
 * e evitar números duplicados em acesso simultâneo.
 * 
 * @param string $tipo    Tipo do documento (ex: 'CSN')
 * @param string $prefixo Prefixo do número (ex: 'AM-CSN')
 * @param int    $ano     Ano do documento (padrão: ano atual)
 * @return string         Número formatado (ex: 'AM-CSN-7/26')
 */
function gerarNumeroDocumento($tipo, $prefixo, $ano = null) {
    global $pdo;

    if ($ano === null) {
        $ano = (int) date('Y');
    }

    $ano_curto = substr($ano, -2);

    try {
        // Trava a linha com FOR UPDATE para evitar race condition
        // (requer que a transação seja gerenciada pelo código que chama esta função)
        $sql = "SELECT ultimo_numero FROM sequenciais_documentos 
                WHERE tipo_documento = :tipo AND ano = :ano 
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo' => $tipo,
            ':ano'  => $ano,
        ]);
        $row = $stmt->fetch();

        if ($row) {
            // Já existe: incrementa
            $numero = (int)$row['ultimo_numero'] + 1;
            $sqlUpdate = "UPDATE sequenciais_documentos 
                          SET ultimo_numero = :numero 
                          WHERE tipo_documento = :tipo AND ano = :ano";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':numero' => $numero,
                ':tipo'   => $tipo,
                ':ano'    => $ano,
            ]);
        } else {
            // Não existe: insere começando em 1
            $numero = 1;
            $sqlInsert = "INSERT INTO sequenciais_documentos (tipo_documento, ano, ultimo_numero) 
                          VALUES (:tipo, :ano, 1)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':tipo' => $tipo,
                ':ano'  => $ano,
            ]);
        }

        // Formata: PREFIXO-NUMERO/ANO (ex: AM-CSN-7/26)
        return $prefixo . '-' . $numero . '/' . $ano_curto;

    } catch (Exception $e) {
        // Log do erro
        log_atividade('erro_sequencial', 'Erro ao gerar número documento: ' . $e->getMessage());
        
        // Fallback: gera número baseado no timestamp para não quebrar o fluxo
        return $prefixo . '-' . date('mdHis') . '/' . $ano_curto;
    }
}

// Paginação simples
function paginar($tabela, $por_pagina, $pagina_atual) {
    global $pdo;
    
    $total_registros = $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetch()[0];
    $total_paginas = ceil($total_registros / $por_pagina);
    $inicio = ($pagina_atual - 1) * $por_pagina;
    
    return [
        'total' => $total_registros,
        'paginas' => $total_paginas,
        'atual' => $pagina_atual,
        'inicio' => $inicio,
        'por_pagina' => $por_pagina
    ];
}

function certificadoSepararLocalDataConvalidacao(?string $valor): array {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return ['local' => '', 'data' => ''];
    }

    if (preg_match('/^(.*?)\s*[,-]\s*(\d{2})\/(\d{2})\/(\d{4})$/u', $valor, $m)) {
        return ['local' => trim($m[1]), 'data' => "{$m[4]}-{$m[3]}-{$m[2]}"];
    }

    return ['local' => $valor, 'data' => ''];
}

function certificadoComporLocalDataConvalidacao(string $local, ?string $data): string {
    $local = trim($local);
    $data = trim((string)$data);
    if ($local === '' && $data === '') {
        return '';
    }
    if ($data === '') {
        return $local;
    }
    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    if (!$dt || $dt->format('Y-m-d') !== $data) {
        throw new InvalidArgumentException('Data de realização da convalidação inválida.');
    }
    return ($local !== '' ? $local . ', ' : '') . $dt->format('d/m/Y');
}

function certificadoVistoriadoresAtivos(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo = 'VISTORIADOR' AND excluido_em IS NULL ORDER BY ativo DESC, nome");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function certificadoLocaisVistoria(PDO $pdo): array {
    $stmt = $pdo->query("SELECT DISTINCT TRIM(local) AS local FROM agendamentos WHERE local IS NOT NULL AND TRIM(local) <> '' ORDER BY local");
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'local');
}

function certificadoUltimaVistoriaParaConvalidacao(PDO $pdo, ?string $vistoriaBaseId): ?array {
    if (empty($vistoriaBaseId)) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT v2.data_vistoria, TRIM(a2.local) AS local, u.nome AS vistoriador
        FROM vistorias base
        JOIN vistorias v2 ON v2.embarcacao_id = base.embarcacao_id AND v2.id <> base.id
        LEFT JOIN agendamentos a2 ON a2.id = v2.agendamento_id
        LEFT JOIN usuarios u ON u.id = a2.vistoriador_id AND u.cargo = 'VISTORIADOR'
        WHERE base.id = :base
          AND v2.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS')
          AND v2.data_vistoria >= base.data_vistoria
          AND a2.local IS NOT NULL AND TRIM(a2.local) <> ''
          AND u.id IS NOT NULL
        ORDER BY v2.data_vistoria DESC, v2.criado_em DESC
        LIMIT 1");
    $stmt->execute([':base' => $vistoriaBaseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function certificadoAplicarUltimaVistoria(array $convalidacoes, ?array $vistoria): array {
    if (!$vistoria || empty($vistoria['data_vistoria'])) {
        return $convalidacoes;
    }
    foreach ($convalidacoes as &$conv) {
        $inicio = (string)($conv['data_inicio'] ?? '');
        $fim = (string)($conv['data_fim'] ?? '');
        $data = (string)$vistoria['data_vistoria'];
        if ($inicio !== '' && $fim !== '' && $data >= $inicio && $data <= $fim) {
            if (trim((string)($conv['local_data'] ?? '')) === '') {
                $conv['local_data'] = certificadoComporLocalDataConvalidacao((string)$vistoria['local'], $data);
            }
            if (trim((string)($conv['vistoriador'] ?? '')) === '') {
                $conv['vistoriador'] = (string)$vistoria['vistoriador'];
            }
            break;
        }
    }
    unset($conv);
    return $convalidacoes;
}

function bloquearEdicaoDocumentoAssinado(PDO $pdo, string $tabela, ?string $id, string $destino): void
{
    $permitidas=['certificados_csn','certificados_cnbl','certificados_cnarq','certificados_lp','certificados_lc','certificados_cht'];
    if(empty($id)||!in_array($tabela,$permitidas,true))return;
    $stmt=$pdo->prepare("SELECT assinado,status FROM {$tabela} WHERE id=:id");$stmt->execute([':id'=>$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
    $tipos=['certificados_csn'=>'CSN','certificados_cnbl'=>'CNBL','certificados_cnarq'=>'CNARQ','certificados_lp'=>'LP','certificados_lc'=>'LC','certificados_cht'=>'CHT'];
    $aprovado=false;
    try {
        $audit=$pdo->prepare("SELECT 1 FROM documento_aprovacoes WHERE documento_tipo=:tipo AND documento_id=:id AND status='APROVADO' LIMIT 1");
        $audit->execute([':tipo'=>$tipos[$tabela],':id'=>$id]);
        $aprovado=(bool)$audit->fetchColumn();
    } catch (PDOException $e) {
        $aprovado=false;
    }
    if($row&&(!empty($row['assinado'])||($row['status']??'')==='assinado'||$aprovado)){setMensagem('error','Documento aprovado ou assinado é imutável. Cancele e reemita para fazer correções.');redirecionar($destino);}
}

function documentoEstaAprovadoOuAssinado(PDO $pdo, string $tabela, string $tipo, ?string $id): bool
{
    if (empty($id)) return false;
    $stmt=$pdo->prepare("SELECT assinado,status FROM {$tabela} WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    if (!empty($row['assinado']) || ($row['status'] ?? '') === 'assinado') return true;
    try {
        $audit=$pdo->prepare("SELECT 1 FROM documento_aprovacoes WHERE documento_tipo=:tipo AND documento_id=:id AND status='APROVADO' LIMIT 1");
        $audit->execute([':tipo'=>$tipo,':id'=>$id]);
        return (bool)$audit->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/** Retorna o ultimo relatorio do agendamento, que e o unico vigente para certificacao. */
function obterRelatorioVigenteAgendamento(PDO $pdo, string $agendamentoId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM vistorias WHERE agendamento_id = :agendamento_id ORDER BY criado_em DESC, id DESC LIMIT 1");
    $stmt->execute([':agendamento_id' => $agendamentoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** Uma A/S deixa de bloquear apenas quando foi efetivamente marcada como cumprida. */
function relatorioPossuiASPendente(PDO $pdo, string $vistoriaId): bool
{
    $stmt = $pdo->prepare("SELECT EXISTS(
        SELECT 1 FROM vistoria_exigencias
         WHERE vistoria_id = :vistoria_id
           AND antes_de_suspender = 1
           AND conforme = 'nao'
           AND status_item <> 'cumprida'
    )");
    $stmt->execute([':vistoria_id' => $vistoriaId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Decisao unica de dominio para todos os modelos de certificado.
 * Resolve sempre o relatorio vigente para impedir uso manual de relatorio substituido.
 */
function avaliarLiberacaoCertificacao(PDO $pdo, string $vistoriaId): array
{
    $stmt = $pdo->prepare("SELECT id, agendamento_id FROM vistorias WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $vistoriaId]);
    $selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$selecionado || empty($selecionado['agendamento_id'])) {
        return ['permitido' => false, 'mensagem' => 'Relatorio invalido ou sem agendamento vinculado.', 'vistoria_id' => null];
    }

    $vigente = obterRelatorioVigenteAgendamento($pdo, $selecionado['agendamento_id']);
    if (!$vigente) {
        return ['permitido' => false, 'mensagem' => 'Nenhum relatorio vigente foi encontrado.', 'vistoria_id' => null];
    }
    if ($vigente['id'] !== $vistoriaId) {
        return [
            'permitido' => false,
            'mensagem' => 'O relatorio selecionado foi substituido. Selecione o relatorio vigente da cadeia.',
            'vistoria_id' => $vigente['id'],
            'status' => $vigente['status'],
        ];
    }
    if (!in_array($vigente['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)) {
        return [
            'permitido' => false,
            'mensagem' => 'A certificacao aguarda a aprovacao do relatorio vigente.',
            'vistoria_id' => $vigente['id'],
            'status' => $vigente['status'],
        ];
    }
    if (relatorioPossuiASPendente($pdo, $vigente['id'])) {
        return [
            'permitido' => false,
            'mensagem' => 'Certificacao bloqueada por exigencia A/S - Antes de suspender.',
            'vistoria_id' => $vigente['id'],
            'status' => $vigente['status'],
            'possui_as' => true,
        ];
    }

    return [
        'permitido' => true,
        'mensagem' => '',
        'vistoria_id' => $vigente['id'],
        'status' => $vigente['status'],
        'possui_as' => false,
    ];
}
