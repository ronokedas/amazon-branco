<?php
/**
 * Teste Automatizado: Busca Global Multi-Entidade
 * Valida a pesquisa unificada de Certificados, Protocolos, Análises,
 * Embarcações, Clientes, Vistorias e Propostas.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function assertBusca(bool $condicao, string $mensagem): void {
    if (!$condicao) {
        echo " [FALHA] $mensagem\n";
        exit(1);
    }
    echo " [OK] $mensagem\n";
}

echo "=== TESTE: BUSCA GLOBAL MULTI-ENTIDADE (CERTIFICADOS, DOCS, CLIENTES) ===\n\n";

@unlink('/tmp/test_busca_cookies.txt');

// 1. Simular requisição HTTP direta ao endpoint via curl local
$baseUrl = 'http://localhost';
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test_busca_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test_busca_cookies.txt');

// Teste 1.1: Sem autenticação deve responder 401
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=teste");
$resAnon = curl_exec($ch);
$codeAnon = curl_getinfo($ch, CURLINFO_HTTP_CODE);
assertBusca($codeAnon === 401 || str_contains($resAnon, 'login'), "Acesso não autenticado deve ser negado (código $codeAnon)");

// Teste 1.2: Fazer login como Administrador
curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
$loginPage = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $m);
$csrf = $m[1] ?? '';
assertBusca(!empty($csrf), "Token CSRF capturado na página de login");

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'teste@teste.com',
    'senha' => 'teste123',
    'csrf_token' => $csrf
]));
curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
assertBusca($loginCode === 302 || $loginCode === 200, "Login do Administrador realizado com sucesso ($loginCode)");

// Teste 1.3: Termo com menos de 2 caracteres deve retornar vazio
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=a");
$resCurto = json_decode(curl_exec($ch), true);
assertBusca(is_array($resCurto) && count($resCurto) === 0, "Busca com menos de 2 caracteres retorna array vazio");

// Teste 1.4: Busca de Certificados CSN ('AM-CSN')
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-CSN");
$resCsn = json_decode(curl_exec($ch), true);
assertBusca(is_array($resCsn) && count($resCsn) > 0, "Busca 'AM-CSN' retornou resultados de certificados CSN");
assertBusca($resCsn[0]['categoria'] === 'Certificados Navais', "Categoria correta: 'Certificados Navais'");
assertBusca(str_contains($resCsn[0]['url'], 'documentacao/certificados/form?id='), "URL direciona para o formulário do certificado: " . $resCsn[0]['url']);

// Teste 1.5: Busca de Certificados LC ('AM-LC')
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-LC");
$resLc = json_decode(curl_exec($ch), true);
assertBusca(is_array($resLc) && count($resLc) > 0, "Busca 'AM-LC' retornou resultados de certificados LC");
assertBusca(str_contains($resLc[0]['url'], 'documentacao/lc/form?id='), "URL direciona para o formulário LC: " . $resLc[0]['url']);

// Teste 1.6: Busca de Protocolos / Dossiês ('AM-PROT')
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-PROT");
$resProt = json_decode(curl_exec($ch), true);
assertBusca(is_array($resProt) && count($resProt) > 0, "Busca 'AM-PROT' retornou resultados de protocolos/dossiês");
assertBusca($resProt[0]['categoria'] === 'Protocolos & Dossiês', "Categoria correta: 'Protocolos & Dossiês'");
assertBusca(str_contains($resProt[0]['url'], 'protocolos/form?id='), "URL direciona para o protocolo: " . $resProt[0]['url']);

// Teste 1.7: Busca de Análises de Planos ('AM-RAP')
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-RAP");
$resRap = json_decode(curl_exec($ch), true);
assertBusca(is_array($resRap) && count($resRap) > 0, "Busca 'AM-RAP' retornou resultados de análises de planos");
$catsRap = array_column($resRap, 'categoria');
assertBusca(in_array('Análises de Planos (RAP)', $catsRap, true), "Categoria 'Análises de Planos (RAP)' encontrada nos resultados de AM-RAP");
$itemRap = null;
foreach ($resRap as $r) {
    if ($r['categoria'] === 'Análises de Planos (RAP)') {
        $itemRap = $r;
        break;
    }
}
assertBusca($itemRap !== null && str_contains($itemRap['url'], 'analises-planos/form?id='), "URL direciona para a análise de planos: " . ($itemRap['url'] ?? ''));

// Teste 1.8: Busca por nome de barco com múltiplos vínculos ('barco')
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=barco");
$resBarco = json_decode(curl_exec($ch), true);
$categoriasEncontradas = array_unique(array_column($resBarco, 'categoria'));
echo "   Categorias vinculadas encontradas para 'barco': " . implode(', ', $categoriasEncontradas) . "\n";
assertBusca(in_array('Certificados Navais', $categoriasEncontradas, true), "Certificados da embarcação foram encontrados");
assertBusca(in_array('Protocolos & Dossiês', $categoriasEncontradas, true), "Protocolos da embarcação foram encontrados");
assertBusca(in_array('Embarcações', $categoriasEncontradas, true), "Cadastro da embarcação foi encontrado");

// Teste 1.9: Busca por CPF/CNPJ sem máscara
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=77327");
$resCpf = json_decode(curl_exec($ch), true);
assertBusca(is_array($resCpf) && count($resCpf) > 0, "Busca por dígitos puros do CNPJ (77327) encontrou o cliente");
assertBusca($resCpf[0]['categoria'] === 'Clientes / Armadores', "Categoria do cliente correta");
assertBusca(str_contains($resCpf[0]['subtitulo'], '77.327.405/0001-37'), "Subtítulo do cliente exibe o CNPJ formatado");

// Teste 1.10: Validação de estrutura completa do JSON
$itemExemplo = $resBarco[0];
$camposObrigatorios = ['id', 'tipo', 'categoria', 'categoria_slug', 'nome', 'titulo', 'subtitulo', 'badge', 'badge_class', 'icone', 'url'];
foreach ($camposObrigatorios as $campo) {
    assertBusca(array_key_exists($campo, $itemExemplo), "Item do resultado contém o campo obrigatório '$campo'");
}

// 2. Testar comportamento com login do perfil ANALISTA
curl_setopt($ch, CURLOPT_URL, "$baseUrl/login?action=logout");
curl_exec($ch);

curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
$loginPage2 = curl_exec($ch);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage2, $m2);
$csrf2 = $m2[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'analista@teste.com',
    'senha' => 'teste123',
    'csrf_token' => $csrf2
]));
curl_exec($ch);

curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-CSN");
$resAnalista = json_decode(curl_exec($ch), true);
assertBusca(is_array($resAnalista) && count($resAnalista) > 0, "Perfil ANALISTA consegue pesquisar Certificados com sucesso");

curl_setopt($ch, CURLOPT_URL, "$baseUrl/busca-global?q=AM-PROT");
$resAnalistaProt = json_decode(curl_exec($ch), true);
assertBusca(is_array($resAnalistaProt) && count($resAnalistaProt) > 0, "Perfil ANALISTA consegue pesquisar Protocolos e Dossiês");

curl_close($ch);
@unlink('/tmp/test_busca_cookies.txt');

echo "\n TODOS OS TESTES DA BUSCA GLOBAL MULTI-ENTIDADE PASSARAM COM 100% DE SUCESSO!\n";
