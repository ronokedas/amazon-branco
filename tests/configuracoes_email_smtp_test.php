<?php
/**
 * Teste automatizado para o Módulo de Configuração de E-mail / SMTP
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

echo "=== INICIANDO TESTE DE CONFIGURAÇÕES DE E-MAIL / SMTP ===\n";

// 1. Testar obterConfiguracaoSMTP
$cfg = obterConfiguracaoSMTP($pdo);
assert(!empty($cfg['mail_host']), "mail_host não pode ser vazio");
assert(!empty($cfg['mail_port']), "mail_port não pode ser vazio");
assert(!empty($cfg['mail_username']), "mail_username não pode ser vazio");
assert(!empty($cfg['mail_password']), "mail_password não pode ser vazio");
assert(!empty($cfg['mail_from_name']), "mail_from_name não pode ser vazio");
assert(!empty($cfg['email_contato']), "email_contato não pode ser vazio");
echo "✓ obterConfiguracaoSMTP() carregou as configurações padrão com sucesso.\n";
echo "  Host: {$cfg['mail_host']}:{$cfg['mail_port']} ({$cfg['mail_encryption']})\n";
echo "  User: {$cfg['mail_username']}\n";
echo "  Remetente: {$cfg['mail_from_name']} <{$cfg['email_contato']}>\n";

// 2. Testar salvarConfiguracaoSMTP e recuperar
$dadosTeste = [
    'mail_host'        => 'smtp.teste-unitario.com',
    'mail_port'        => 587,
    'mail_username'    => 'usuario_teste',
    'mail_password'    => 'senha_teste_123',
    'mail_from_name'   => 'Amazon Naval Teste',
    'mail_encryption'  => 'tls',
    'mail_verify_peer' => '0',
    'email_contato'    => 'teste@amazonnaval.com.br',
];
$salvou = salvarConfiguracaoSMTP($pdo, $dadosTeste);
assert($salvou === true, "Falha ao executar salvarConfiguracaoSMTP");

$cfgAtualizado = obterConfiguracaoSMTP($pdo);
assert($cfgAtualizado['mail_host'] === 'smtp.teste-unitario.com', "mail_host não foi atualizado corretamente");
assert((int)$cfgAtualizado['mail_port'] === 587, "mail_port não foi atualizado corretamente");
assert($cfgAtualizado['mail_username'] === 'usuario_teste', "mail_username não foi atualizado corretamente");
echo "✓ salvarConfiguracaoSMTP() e persistência no banco validados com sucesso.\n";

// Restaurar configuração padrão Brevo fornecida pelo usuário
$dadosBrevo = [
    'mail_host'        => 'smtp-relay.brevo.com',
    'mail_port'        => 587,
    'mail_username'    => 'b157d8001@smtp-brevo.com',
    'mail_password'    => 'xsmtpsib-782cfe3f1e6ae55475b72b76140451807a16d512c4cfbf6b24266f56f308fe79-bOlCBIQS6gnPNSaO',
    'mail_from_name'   => 'Amazon Naval',
    'mail_encryption'  => 'tls',
    'mail_verify_peer' => '0',
    'email_contato'    => 'contato@amazonnaval.com.br',
];
salvarConfiguracaoSMTP($pdo, $dadosBrevo);
$cfgRestaurado = obterConfiguracaoSMTP($pdo);
assert($cfgRestaurado['mail_host'] === 'smtp-relay.brevo.com', "Restauro do Brevo falhou");
echo "✓ Configurações padrão do Brevo restauradas e persistidas no banco.\n";

// 3. Testar validação de e-mail inválido em testarConexaoSMTP
$testeInvalido = testarConexaoSMTP('email_invalido_sem_arroba', null, $pdo);
assert($testeInvalido['success'] === false, "E-mail inválido deveria retornar success=false");
echo "✓ Validação de destinatário de teste aprovada: '{$testeInvalido['message']}'.\n";

// 4. Testar presença do card de e-mail em modules/configuracoes/index.php
$conteudoConfiguracoes = file_get_contents(__DIR__ . '/../modules/configuracoes/index.php');
assert(strpos($conteudoConfiguracoes, 'configuracoes/email') !== false, "Link configuracoes/email não encontrado no painel de configurações");
assert(strpos($conteudoConfiguracoes, 'Configuração de E-mail') !== false, "Título 'Configuração de E-mail' não encontrado no painel");
echo "✓ Card de Configuração de E-mail presente em modules/configuracoes/index.php.\n";

// 5. Testar presença do botão SMTP em modules/emails/index.php
$conteudoEmails = file_get_contents(__DIR__ . '/../modules/emails/index.php');
assert(strpos($conteudoEmails, 'configuracoes/email') !== false, "Link configuracoes/email não encontrado no histórico de emails");
assert(strpos($conteudoEmails, 'Configurar Servidor SMTP') !== false, "Botão 'Configurar Servidor SMTP' não encontrado no histórico de emails");
echo "✓ Atalho para Configurar Servidor SMTP presente em modules/emails/index.php.\n";

// 6. Testar roteamento no index.php
$conteudoIndex = file_get_contents(__DIR__ . '/../index.php');
assert(strpos($conteudoIndex, "'configuracoes/email'") !== false, "Rota configuracoes/email não encontrada no roteador principal index.php");
echo "✓ Rota 'configuracoes/email' registrada no roteador principal index.php.\n";

// 7. Testar renderização de modules/configuracoes/email.php
$admin = $pdo->query("SELECT id, nome, email, cargo, versao_sessao FROM usuarios WHERE cargo = 'ADMIN' AND ativo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $admin['id'];
$_SESSION['usuario_nome'] = $admin['nome'];
$_SESSION['usuario_email'] = $admin['email'];
$_SESSION['usuario_cargo'] = $admin['cargo'];
$_SESSION['versao_sessao'] = (int)($admin['versao_sessao'] ?? 0);
$_SESSION['usuario_permissoes'] = json_encode(['configuracoes', 'configuracoes_email']);

ob_start();
require __DIR__ . '/../modules/configuracoes/email.php';
$html = ob_get_clean();

assert(strpos($html, 'Configurações de E-mail (SMTP)') !== false, "Título da página não renderizado");
assert(strpos($html, 'id="mail_host"') !== false, "Campo mail_host não renderizado");
assert(strpos($html, 'id="mail_port"') !== false, "Campo mail_port não renderizado");
assert(strpos($html, 'id="mail_username"') !== false, "Campo mail_username não renderizado");
assert(strpos($html, 'id="mail_password"') !== false, "Campo mail_password não renderizado");
assert(strpos($html, 'id="email_teste"') !== false, "Campo email_teste não renderizado");
assert(strpos($html, 'aplicarPreset(\'brevo\')') !== false, "Preset Brevo não encontrado");
echo "✓ Tela de Configuração de E-mail (email.php) renderizada com todos os campos e presets com sucesso.\n";

echo "\n=======================================================\n";
echo "RESULTADO: TODOS OS TESTES DE E-MAIL / SMTP PASSARAM COM SUCESSO!\n";
echo "=======================================================\n";
