<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: email.php - Configuração do servidor de e-mail (SMTP) e teste de disparo
 * Acesso: exclusivo de administradores (permissão 'configuracoes')
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mailer.php';

verificar_sessao();
exigirAcessoOuSub('configuracoes', 'configuracoes_email');

$resultadoTeste = null;

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'configuracoes/email');
    }

    if ($action === 'salvar_smtp') {
        try {
            $dados = [
                'mail_host'        => trim($_POST['mail_host'] ?? ''),
                'mail_port'        => (int)($_POST['mail_port'] ?? 587),
                'mail_username'    => trim($_POST['mail_username'] ?? ''),
                'mail_password'    => trim($_POST['mail_password'] ?? ''),
                'mail_from_name'   => trim($_POST['mail_from_name'] ?? 'Amazon Naval'),
                'mail_encryption'  => strtolower(trim($_POST['mail_encryption'] ?? 'tls')),
                'mail_verify_peer' => !empty($_POST['mail_verify_peer']) ? '1' : '0',
                'email_contato'    => trim($_POST['email_contato'] ?? ''),
            ];

            if (empty($dados['mail_host'])) {
                throw new RuntimeException('O servidor SMTP (Host) é obrigatório.');
            }
            if ($dados['mail_port'] <= 0) {
                throw new RuntimeException('A porta do servidor SMTP deve ser um número válido (ex: 587 ou 465).');
            }

            salvarConfiguracaoSMTP($pdo, $dados);
            setMensagem('success', 'Configurações de e-mail SMTP salvas com sucesso!');
        } catch (Throwable $e) {
            error_log('Erro ao salvar SMTP: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar configurações: ' . $e->getMessage());
        }
        redirecionar(APP_URL . 'configuracoes/email');
    }

    if ($action === 'testar_smtp') {
        $emailDestino = trim($_POST['email_teste'] ?? '');
        $override = [
            'mail_host'        => trim($_POST['mail_host'] ?? ''),
            'mail_port'        => (int)($_POST['mail_port'] ?? 587),
            'mail_username'    => trim($_POST['mail_username'] ?? ''),
            'mail_password'    => trim($_POST['mail_password'] ?? ''),
            'mail_from_name'   => trim($_POST['mail_from_name'] ?? 'Amazon Naval'),
            'mail_encryption'  => strtolower(trim($_POST['mail_encryption'] ?? 'tls')),
            'mail_verify_peer' => !empty($_POST['mail_verify_peer']) ? 1 : 0,
            'email_contato'    => trim($_POST['email_contato'] ?? ''),
        ];

        // Se o usuário marcou para salvar antes de testar
        if (!empty($_POST['salvar_junto'])) {
            salvarConfiguracaoSMTP($pdo, $override);
        }

        $resultadoTeste = testarConexaoSMTP($emailDestino, $override, $pdo);
        if ($resultadoTeste['success']) {
            setMensagem('success', $resultadoTeste['message']);
        } else {
            setMensagem('error', $resultadoTeste['message']);
        }
    }
}

// Carregar configurações ativas
$cfg = obterConfiguracaoSMTP($pdo);

// E-mail sugerido para teste
$emailSugeridoTeste = $_SESSION['usuario_email'] ?? $cfg['email_contato'] ?? 'contato@amazonnaval.com.br';

$titulo_page = 'Configurações de E-mail / SMTP - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 25px;">
        <div>
            <h1><i class="fas fa-envelope-open-text" style="color: #0d9b70;"></i> Configurações de E-mail (SMTP)</h1>
            <p>Gerencie o servidor de envio para propostas, agendamentos, certificados, convites e alertas da frota.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="btn btn-secondary" href="<?= APP_URL ?>configuracoes" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Painel de Configurações
            </a>
            <a class="btn btn-secondary" href="<?= APP_URL ?>emails" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-history"></i> Histórico de E-mails
            </a>
        </div>
    </div>

    <!-- Card de Status do Servidor de E-mail -->
    <div class="card" style="margin-bottom: 24px; border-left: 4px solid #0d9b70;">
        <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(13,155,112,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-server" style="font-size: 1.6rem; color: #0d9b70;"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                        <strong style="font-size: 1.1rem; color: var(--cor-texto);">Servidor SMTP Ativo:</strong>
                        <span class="badge badge-success" style="padding: 4px 10px; border-radius: 12px; font-weight: 700;">
                            <i class="fas fa-check-circle"></i> Configurado
                        </span>
                    </div>
                    <div style="color: var(--cor-texto-secundario); font-size: 0.95rem;">
                        <strong><?= h($cfg['mail_host']) ?>:<?= h($cfg['mail_port']) ?></strong>
                        <span style="opacity: 0.7;">(<?= strtoupper(h($cfg['mail_encryption'])) ?>)</span>
                        &bull; Remetente: <strong><?= h($cfg['mail_from_name']) ?></strong> &lt;<?= h($cfg['email_contato']) ?>&gt;
                    </div>
                </div>
            </div>
            <a href="#blocoTeste" class="btn btn-success btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-paper-plane"></i> Testar Envio Agora
            </a>
        </div>
    </div>

    <?php if ($resultadoTeste !== null && !$resultadoTeste['success'] && !empty($resultadoTeste['debug'])): ?>
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid #dc3545; background: #fff8f8;">
            <div class="card-body">
                <h4 style="color: #dc3545; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-bug"></i> Diagnóstico Técnico de Conexão SMTP
                </h4>
                <p style="margin: 0 0 10px 0; color: #555; font-size: 0.95rem;">
                    O servidor retornou o log abaixo durante a tentativa de conexão. Verifique se o login, a senha/chave de API ou o bloqueio de portas do provedor estão corretos:
                </p>
                <pre style="background: #212529; color: #f8f9fa; padding: 14px; border-radius: 6px; font-size: 0.82rem; overflow-x: auto; max-height: 240px; margin: 0; white-space: pre-wrap; word-break: break-all;"><?= h($resultadoTeste['debug']) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 24px; align-items: start;">

        <!-- Formulário Principal de Configuração -->
        <section class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0;"><i class="fas fa-sliders"></i> Parâmetros do Servidor SMTP</h3>
                <span class="text-muted" style="font-size: 0.85rem;">Alterações têm efeito imediato em todo o sistema</span>
            </div>
            <div class="card-body">

                <!-- Pills / Chips de Preenchimento Rápido -->
                <div style="margin-bottom: 22px; padding: 14px; background: rgba(0,0,0,0.02); border: 1px dashed var(--cor-borda); border-radius: 8px;">
                    <label style="display: block; font-weight: 700; margin-bottom: 8px; font-size: 0.88rem; color: var(--cor-texto);">
                        <i class="fas fa-bolt" style="color: #ffc107;"></i> Preenchimento rápido de provedores conhecidos:
                    </label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="aplicarPreset('brevo')" style="border-radius: 20px; font-size: 0.82rem;">
                            <i class="fas fa-star" style="color: #ffc107;"></i> Brevo (smtp-relay.brevo.com)
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="aplicarPreset('gmail')" style="border-radius: 20px; font-size: 0.82rem;">
                            <i class="fab fa-google" style="color: #ea4335;"></i> Gmail / Google Workspace
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="aplicarPreset('hostinger')" style="border-radius: 20px; font-size: 0.82rem;">
                            <i class="fas fa-cloud"></i> Hostinger
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="aplicarPreset('uol')" style="border-radius: 20px; font-size: 0.82rem;">
                            <i class="fas fa-envelope"></i> UOL Host
                        </button>
                    </div>
                    <small class="text-muted" style="display: block; margin-top: 6px;">
                        Clique em um botão acima para carregar automaticamente o host, porta e criptografia recomendados.
                    </small>
                </div>

                <form method="POST" id="formSmtp">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="salvar_smtp">

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="mail_host" style="font-weight: 700; display: block; margin-bottom: 6px;">
                            <i class="fas fa-server"></i> Servidor SMTP (Host) *
                        </label>
                        <input type="text"
                               id="mail_host"
                               name="mail_host"
                               class="form-control"
                               required
                               maxlength="200"
                               value="<?= h($cfg['mail_host']) ?>"
                               placeholder="Ex: smtp-relay.brevo.com">
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            Endereço de rede do servidor de saída SMTP fornecido pela sua hospedagem ou serviço de disparo.
                        </small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="mail_port" style="font-weight: 700; display: block; margin-bottom: 6px;">
                                <i class="fas fa-network-wired"></i> Porta SMTP *
                            </label>
                            <input type="number"
                                   id="mail_port"
                                   name="mail_port"
                                   class="form-control"
                                   required
                                   min="1"
                                   max="65535"
                                   value="<?= h($cfg['mail_port']) ?>"
                                   placeholder="587">
                            <small class="text-muted" style="display: block; margin-top: 4px;">
                                Padrão: <strong>587</strong> (TLS) ou <strong>465</strong> (SSL).
                            </small>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="mail_encryption" style="font-weight: 700; display: block; margin-bottom: 6px;">
                                <i class="fas fa-lock"></i> Criptografia *
                            </label>
                            <select id="mail_encryption" name="mail_encryption" class="form-control" required>
                                <option value="tls" <?= strtolower($cfg['mail_encryption']) === 'tls' ? 'selected' : '' ?>>TLS (Recomendado na porta 587)</option>
                                <option value="ssl" <?= strtolower($cfg['mail_encryption']) === 'ssl' ? 'selected' : '' ?>>SSL (Porta 465)</option>
                                <option value="none" <?= strtolower($cfg['mail_encryption']) === 'none' ? 'selected' : '' ?>>Nenhuma / Texto Simples (Porta 25)</option>
                            </select>
                            <small class="text-muted" style="display: block; margin-top: 4px;">
                                Protocolo de segurança exigido pelo servidor.
                            </small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="mail_username" style="font-weight: 700; display: block; margin-bottom: 6px;">
                            <i class="fas fa-user"></i> Usuário / Login SMTP *
                        </label>
                        <input type="text"
                               id="mail_username"
                               name="mail_username"
                               class="form-control"
                               required
                               maxlength="200"
                               value="<?= h($cfg['mail_username']) ?>"
                               placeholder="Ex: b157d8001@smtp-brevo.com ou seu email">
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            No Brevo é a chave de login SMTP (ex: b157d8001@smtp-brevo.com). No Gmail ou cPanel é o e-mail completo.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="mail_password" style="font-weight: 700; display: block; margin-bottom: 6px;">
                            <i class="fas fa-key"></i> Senha ou Chave de API SMTP *
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="password"
                                   id="mail_password"
                                   name="mail_password"
                                   class="form-control"
                                   required
                                   value="<?= h($cfg['mail_password']) ?>"
                                   placeholder="Chave SMTP (xsmtpsib-...) ou senha da caixa"
                                   style="flex: 1;">
                            <button type="button"
                                    class="btn btn-secondary"
                                    onclick="alternarVisibilidadeSenha()"
                                    id="btnToggleSenha"
                                    title="Exibir ou ocultar senha">
                                <i class="fas fa-eye" id="iconeOlho"></i>
                            </button>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            No Brevo utilize a chave gerada em <em>SMTP &amp; API &gt; Chaves SMTP</em>. No Gmail utilize uma <em>Senha de App</em> (16 dígitos).
                        </small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="mail_from_name" style="font-weight: 700; display: block; margin-bottom: 6px;">
                                <i class="fas fa-id-card"></i> Nome do Remetente *
                            </label>
                            <input type="text"
                                   id="mail_from_name"
                                   name="mail_from_name"
                                   class="form-control"
                                   required
                                   maxlength="150"
                                   value="<?= h($cfg['mail_from_name']) ?>"
                                   placeholder="Amazon Naval">
                            <small class="text-muted" style="display: block; margin-top: 4px;">
                                Nome visível para o cliente no leitor de e-mail.
                            </small>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="email_contato" style="font-weight: 700; display: block; margin-bottom: 6px;">
                                <i class="fas fa-at"></i> E-mail de Envio e Resposta *
                            </label>
                            <input type="email"
                                   id="email_contato"
                                   name="email_contato"
                                   class="form-control"
                                   required
                                   maxlength="150"
                                   value="<?= h($cfg['email_contato']) ?>"
                                   placeholder="contato@amazonnaval.com.br">
                            <small class="text-muted" style="display: block; margin-top: 4px;">
                                Endereço remetente oficial (From) e de retorno (Reply-To).
                            </small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 22px;">
                        <label for="mail_verify_peer" style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox"
                                   id="mail_verify_peer"
                                   name="mail_verify_peer"
                                   value="1"
                                   <?= !empty($cfg['mail_verify_peer']) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>Verificar estritamente certificado SSL do servidor SMTP (Verify Peer)</span>
                        </label>
                        <small class="text-muted" style="display: block; margin-left: 28px; margin-top: 2px;">
                            Recomendado manter <strong>desmarcado</strong> para evitar falhas de handshake em certificados autoassinados ou conexões em nuvem.
                        </small>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-top: 1px solid var(--cor-borda); padding-top: 18px;">
                        <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-save"></i> Salvar Configurações SMTP
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Coluna Lateral: Teste de Disparo em Tempo Real -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <section class="card" id="blocoTeste" style="border-top: 4px solid #28a745;">
                <div class="card-header">
                    <h3 style="margin: 0;"><i class="fas fa-paper-plane" style="color: #28a745;"></i> Testar Envio em Tempo Real</h3>
                </div>
                <div class="card-body">
                    <p style="color: var(--cor-texto-secundario); font-size: 0.95rem; margin-top: 0; line-height: 1.5;">
                        Dispare um e-mail de teste agora mesmo para validar se o servidor SMTP está autenticando e entregando as mensagens corretamente.
                    </p>

                    <form method="POST" id="formTeste" onsubmit="prepararTeste(this)">
                        <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                        <input type="hidden" name="action" value="testar_smtp">
                        <!-- Campos ocultos clonados dinamicamente para permitir testar valores digitados sem ter salvo antes -->
                        <input type="hidden" name="mail_host" id="teste_host">
                        <input type="hidden" name="mail_port" id="teste_port">
                        <input type="hidden" name="mail_encryption" id="teste_encryption">
                        <input type="hidden" name="mail_username" id="teste_username">
                        <input type="hidden" name="mail_password" id="teste_password">
                        <input type="hidden" name="mail_from_name" id="teste_from_name">
                        <input type="hidden" name="email_contato" id="teste_email_contato">
                        <input type="hidden" name="mail_verify_peer" id="teste_verify_peer">

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="email_teste" style="font-weight: 700; display: block; margin-bottom: 6px;">
                                <i class="fas fa-inbox"></i> Enviar e-mail de teste para:
                            </label>
                            <input type="email"
                                   id="email_teste"
                                   name="email_teste"
                                   class="form-control"
                                   required
                                   value="<?= h($emailSugeridoTeste) ?>"
                                   placeholder="seu.email@exemplo.com">
                            <small class="text-muted" style="display: block; margin-top: 4px;">
                                Digite a caixa postal onde você deseja conferir o recebimento do e-mail.
                            </small>
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                                <input type="checkbox" name="salvar_junto" value="1" checked style="width: 16px; height: 16px;">
                                <span>Salvar as configurações do formulário antes de disparar o teste</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success" id="btnDispararTeste" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px;">
                            <i class="fas fa-paper-plane"></i> Disparar E-mail de Teste
                        </button>
                    </form>
                </div>
            </section>

            <!-- Orientações Regulamentares e de Infraestrutura -->
            <section class="card" style="border-left: 4px solid #17a2b8;">
                <div class="card-body">
                    <h4 style="margin: 0 0 10px 0; color: #17a2b8; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i> Orientações Importantes
                    </h4>
                    <ul style="margin: 0; padding-left: 18px; color: var(--cor-texto-secundario); font-size: 0.9rem; line-height: 1.6;">
                        <li><strong>Disparos Automáticos:</strong> O sistema utiliza este servidor para enviar links de propostas com token seguro, confirmações de vistorias navais e avisos de vencimento.</li>
                        <li><strong>Brevo / Sendinblue:</strong> Certifique-se de que o remetente (<code>contato@amazonnaval.com.br</code>) esteja validado no painel da Brevo em <em>Senders &amp; IPs</em>.</li>
                        <li><strong>Gmail / Google:</strong> Exige a ativação de verificação em duas etapas e a criação de uma <em>Senha de Aplicativo</em> na conta Google.</li>
                        <li><strong>Spam / Entregabilidade:</strong> Para garantir entrega na caixa de entrada, mantenha os registros DNS <strong>SPF</strong> e <strong>DKIM</strong> configurados no domínio <code>amazonnaval.com.br</code>.</li>
                    </ul>
                </div>
            </section>

        </div>

    </div>
</div>

<script>
function alternarVisibilidadeSenha() {
    const input = document.getElementById('mail_password');
    const icone = document.getElementById('iconeOlho');
    if (input.type === 'password') {
        input.type = 'text';
        icone.classList.remove('fa-eye');
        icone.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icone.classList.remove('fa-eye-slash');
        icone.classList.add('fa-eye');
    }
}

function aplicarPreset(tipo) {
    const host = document.getElementById('mail_host');
    const port = document.getElementById('mail_port');
    const enc = document.getElementById('mail_encryption');
    const user = document.getElementById('mail_username');
    const fromName = document.getElementById('mail_from_name');
    const contato = document.getElementById('email_contato');

    if (tipo === 'brevo') {
        host.value = 'smtp-relay.brevo.com';
        port.value = '587';
        enc.value = 'tls';
        if (!user.value || user.value.includes('gmail') || user.value.includes('uhserver')) {
            user.value = 'b157d8001@smtp-brevo.com';
        }
        fromName.value = 'Amazon Naval';
        contato.value = 'contato@amazonnaval.com.br';
    } else if (tipo === 'gmail') {
        host.value = 'smtp.gmail.com';
        port.value = '587';
        enc.value = 'tls';
    } else if (tipo === 'hostinger') {
        host.value = 'smtp.hostinger.com';
        port.value = '465';
        enc.value = 'ssl';
    } else if (tipo === 'uol') {
        host.value = 'smtps.uhserver.com';
        port.value = '465';
        enc.value = 'ssl';
    }
}

function prepararTeste(form) {
    document.getElementById('teste_host').value = document.getElementById('mail_host').value;
    document.getElementById('teste_port').value = document.getElementById('mail_port').value;
    document.getElementById('teste_encryption').value = document.getElementById('mail_encryption').value;
    document.getElementById('teste_username').value = document.getElementById('mail_username').value;
    document.getElementById('teste_password').value = document.getElementById('mail_password').value;
    document.getElementById('teste_from_name').value = document.getElementById('mail_from_name').value;
    document.getElementById('teste_email_contato').value = document.getElementById('email_contato').value;
    document.getElementById('teste_verify_peer').value = document.getElementById('mail_verify_peer').checked ? '1' : '0';

    const btn = document.getElementById('btnDispararTeste');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando e Enviando...';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
