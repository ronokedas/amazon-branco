<?php
/**
 * MODULO: PERFIL DO USUÁRIO
 * Arquivo: index.php - Edição do próprio perfil e senha
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/assinaturas_usuarios.php';

// Exigir login (qualquer usuário logado pode acessar)
verificar_sessao();

$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do usuário logado
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, cargo, ativo, criado_em, atualizado_em FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        setMensagem('error', 'Usuário não encontrado.');
        redirecionar(APP_URL . 'login?action=logout');
    }
} catch (Exception $e) {
    error_log('Erro ao buscar perfil: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar dados do perfil.');
    redirecionar(APP_URL . 'dashboard');
}

$podeAssinar = in_array($usuario['cargo'], ['ADMIN', 'VISTORIADOR', 'ANALISTA'], true);
$responsavel = null;
if ($podeAssinar) {
    try {
        $stmtResp = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE usuario_id = :uid LIMIT 1");
        $stmtResp->execute([':uid' => $usuario_id]);
        $responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log('Erro ao buscar dados de assinatura do perfil: ' . $e->getMessage());
    }
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verificarCSRF($csrf)) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'perfil');
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email)) {
        setMensagem('error', 'Nome e email são obrigatórios.');
        redirecionar(APP_URL . 'perfil');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMensagem('error', 'Email inválido.');
        redirecionar(APP_URL . 'perfil');
    }

    // Se quiser alterar senha, precisa informar a senha atual
    if (!empty($nova_senha) || !empty($confirma_senha)) {
        if (empty($senha_atual)) {
            setMensagem('error', 'Informe a senha atual para alterar a senha.');
            redirecionar(APP_URL . 'perfil');
        }

        // Verificar senha atual
        $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $usuario_id]);
        $hash_salvo = $stmt->fetchColumn();

        if (!password_verify($senha_atual, $hash_salvo)) {
            setMensagem('error', 'Senha atual incorreta.');
            redirecionar(APP_URL . 'perfil');
        }

        if (strlen($nova_senha) < 6) {
            setMensagem('error', 'A nova senha deve ter no mínimo 6 caracteres.');
            redirecionar(APP_URL . 'perfil');
        }

        if ($nova_senha !== $confirma_senha) {
            setMensagem('error', 'A confirmação da senha não confere.');
            redirecionar(APP_URL . 'perfil');
        }

        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    } else {
        $senha_hash = null;
    }

    // Atualizar dados
    try {
        $pdo->beginTransaction();

        if ($senha_hash) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha_hash = :senha, atualizado_em = NOW() WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senha_hash,
                ':id' => $usuario_id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, atualizado_em = NOW() WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':id' => $usuario_id
            ]);
        }

        if ($podeAssinar) {
            $cargo_titulo = trim($_POST['cargo_titulo'] ?? '');
            $registro_profissional = trim($_POST['registro_profissional'] ?? '');
            $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');

            if (!empty($cpf_cnpj)) {
                $digits = preg_replace('/\D+/', '', $cpf_cnpj);
                $validDoc = (strlen($digits) === 11 && validarCPF($digits)) || (strlen($digits) === 14 && validarCNPJ($digits));
                if (!$validDoc) {
                    throw new RuntimeException('CPF ou CNPJ inválido informado para a assinatura.');
                }
            }

            $arquivoAssinatura = $_FILES['assinatura_imagem'] ?? [];
            $temArquivo = !empty($arquivoAssinatura['tmp_name']) && ($arquivoAssinatura['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($responsavel) {
                $respId = (int)$responsavel['id'];
                $image = $temArquivo ? salvarImagemAssinaturaResponsavel($arquivoAssinatura, $respId) : [];

                $stmtUpResp = $pdo->prepare("UPDATE responsaveis_assinatura 
                    SET nome_completo = :nome,
                        email = :email,
                        cargo_titulo = :cargo_titulo,
                        registro_profissional = :reg,
                        cpf_cnpj = :cpf,
                        assinatura_arquivo = COALESCE(:arq, assinatura_arquivo),
                        assinatura_hash = COALESCE(:hash, assinatura_hash),
                        assinatura_atualizada_em = IF(:arq IS NULL, assinatura_atualizada_em, NOW()),
                        ativo = 1
                    WHERE id = :id");
                $stmtUpResp->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':cargo_titulo' => $cargo_titulo ?: ($responsavel['cargo_titulo'] ?: $usuario['cargo']),
                    ':reg' => $registro_profissional,
                    ':cpf' => $cpf_cnpj ?: null,
                    ':arq' => $image['path'] ?? null,
                    ':hash' => $image['hash'] ?? null,
                    ':id' => $respId
                ]);
            } elseif (!empty($cargo_titulo) || !empty($registro_profissional) || !empty($cpf_cnpj) || $temArquivo) {
                $stmtInsResp = $pdo->prepare("INSERT INTO responsaveis_assinatura 
                    (nome_completo, email, cpf_cnpj, usuario_id, cargo_titulo, registro_profissional, ativo)
                    VALUES (:nome, :email, :cpf, :uid, :cargo_titulo, :reg, 1)");
                $stmtInsResp->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':cpf' => $cpf_cnpj ?: null,
                    ':uid' => $usuario_id,
                    ':cargo_titulo' => $cargo_titulo ?: $usuario['cargo'],
                    ':reg' => $registro_profissional
                ]);
                $newRespId = (int)$pdo->lastInsertId();
                if ($temArquivo) {
                    $image = salvarImagemAssinaturaResponsavel($arquivoAssinatura, $newRespId);
                    if (!empty($image)) {
                        $pdo->prepare("UPDATE responsaveis_assinatura SET assinatura_arquivo = :arq, assinatura_hash = :hash, assinatura_atualizada_em = NOW() WHERE id = :id")
                            ->execute([':arq' => $image['path'], ':hash' => $image['hash'], ':id' => $newRespId]);
                    }
                }
            }
        }

        $pdo->commit();

        // Atualizar sessão
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;

        setMensagem('success', 'Perfil e credenciais de assinatura atualizados com sucesso!');
        redirecionar(APP_URL . 'perfil');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro ao atualizar perfil: ' . $e->getMessage());
        setMensagem('error', $e instanceof RuntimeException ? $e->getMessage() : 'Erro ao atualizar perfil. Tente novamente.');
        redirecionar(APP_URL . 'perfil');
    }
}

$csrf = gerarCSRF();
$titulo_page = 'Meu Perfil - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--accent); margin: 0;">
                <i class="fas fa-user-circle"></i> Meu Perfil
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formPerfil" enctype="multipart/form-data" onsubmit="return validarFormulario('formPerfil')">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="atualizar_perfil" value="1">

                <!-- Nome -->
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome completo *
                    </label>
                    <input type="text" 
                           id="nome" 
                           name="nome" 
                           placeholder="Seu nome completo" 
                           required 
                           maxlength="150"
                           value="<?php echo h($usuario['nome']); ?>">
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="seu@email.com" 
                           required 
                           maxlength="150"
                           value="<?php echo h($usuario['email']); ?>">
                </div>

                <hr style="border-color: var(--border); margin: 24px 0;">

                <!-- Senha atual (obrigatória para alterar senha) -->
                <div class="form-group">
                    <label for="senha_atual">
                        <i class="fas fa-lock"></i> Senha atual
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="senha_atual" 
                               name="senha_atual" 
                               placeholder="Digite sua senha atual para alterá-la"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('senha_atual', 'icone-senha-atual')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-senha-atual"></i>
                        </button>
                    </div>
                    <small class="text-muted">Preencha apenas se desejar alterar sua senha.</small>
                </div>

                <!-- Nova senha -->
                <div class="form-group">
                    <label for="nova_senha">
                        <i class="fas fa-key"></i> Nova senha
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="nova_senha" 
                               name="nova_senha" 
                               placeholder="Mínimo 6 caracteres"
                               minlength="6"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('nova_senha', 'icone-nova-senha')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-nova-senha"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirmar nova senha -->
                <div class="form-group">
                    <label for="confirma_senha">
                        <i class="fas fa-check-double"></i> Confirmar nova senha
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="confirma_senha" 
                               name="confirma_senha" 
                               placeholder="Repita a nova senha"
                               minlength="6"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('confirma_senha', 'icone-confirma-senha')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-confirma-senha"></i>
                        </button>
                    </div>
                <?php if ($podeAssinar): ?>
                <hr style="border-color: var(--border); margin: 28px 0;">
                
                <div id="assinatura" style="scroll-margin-top: 80px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <h4 style="margin: 0; color: var(--accent); font-size: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-signature"></i> Assinatura Manuscrita & Credenciais Técnicas
                        </h4>
                        <?php if (!empty($responsavel['assinatura_arquivo'])): ?>
                            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-check-circle"></i> Assinatura Ativa
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-clock"></i> Pendente de Cadastro
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <small class="text-muted" style="display: block; margin-bottom: 16px; line-height: 1.5;">
                        <i class="fas fa-info-circle"></i> <strong>Exigência NORMAM / DPC:</strong> Conforme normas da Autoridade Marítima, relatórios de vistoria, laudos periciais e certificados exigem a identificação profissional (CREA/CFT) e a rubrica/assinatura do responsável técnico habilitado.
                    </small>

                    <!-- Cargo / Título Profissional com Pills/Chips -->
                    <div class="form-group">
                        <label for="cargo_titulo">
                            <i class="fas fa-user-tie"></i> Cargo / Título Profissional no Documento *
                        </label>
                        <input type="text" 
                               id="cargo_titulo" 
                               name="cargo_titulo" 
                               placeholder="Ex: Engenheiro Naval, Vistoriador Naval, Analista Técnico" 
                               maxlength="255"
                               value="<?php echo h($responsavel['cargo_titulo'] ?? ($usuario['cargo'] === 'VISTORIADOR' ? 'Vistoriador Naval' : ($usuario['cargo'] === 'ANALISTA' ? 'Analista Técnico de Projetos' : 'Responsável Técnico'))); ?>">
                        <small class="text-muted">Como sua qualificação técnica será impressa no rodapé e no termo de assinatura dos documentos.</small>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                            <span style="font-size: 11px; color: var(--text-secondary); align-self: center;">Sugestões:</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px; font-size: 11px;" onclick="document.getElementById('cargo_titulo').value='Engenheiro Naval';">Engenheiro Naval</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px; font-size: 11px;" onclick="document.getElementById('cargo_titulo').value='Vistoriador Naval';">Vistoriador Naval</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px; font-size: 11px;" onclick="document.getElementById('cargo_titulo').value='Analista Técnico de Projetos';">Analista Técnico</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px; font-size: 11px;" onclick="document.getElementById('cargo_titulo').value='Perito Naval / Vistoriador';">Perito Naval</button>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                        <!-- Registro Profissional -->
                        <div class="form-group">
                            <label for="registro_profissional">
                                <i class="fas fa-id-card"></i> Registro Profissional (CREA / CFT)
                            </label>
                            <input type="text" 
                                   id="registro_profissional" 
                                   name="registro_profissional" 
                                   placeholder="Ex: CREA PA-12345/D ou CFT-BR" 
                                   maxlength="100"
                                   value="<?php echo h($responsavel['registro_profissional'] ?? ''); ?>">
                            <small class="text-muted">Número do conselho regional de classe ou matrícula técnica.</small>
                        </div>

                        <!-- CPF / CNPJ -->
                        <div class="form-group">
                            <label for="cpf_cnpj">
                                <i class="fas fa-fingerprint"></i> CPF do Responsável Técnico
                            </label>
                            <?php
                            $cpfValorExibicao = $responsavel['cpf_cnpj'] ?? '';
                            $cpfDigitos = preg_replace('/\D/', '', (string)$cpfValorExibicao);
                            if (strlen($cpfDigitos) === 11) {
                                $cpfValorExibicao = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfDigitos);
                            } elseif (strlen($cpfDigitos) === 14) {
                                $cpfValorExibicao = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cpfDigitos);
                            }
                            ?>
                            <input type="text" 
                                   id="cpf_cnpj" 
                                   name="cpf_cnpj" 
                                   placeholder="000.000.000-00" 
                                   maxlength="18"
                                   autocomplete="off"
                                   oninput="mascararCpfCnpj(this)"
                                   value="<?php echo h($cpfValorExibicao); ?>">
                            <small class="text-muted">Formatação automática. Utilizado na composição do hash criptográfico e termo de fé pública.</small>
                        </div>
                    </div>

                    <!-- Upload da Imagem da Assinatura -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label for="assinatura_imagem">
                            <i class="fas fa-pen-nib"></i> Arquivo da Assinatura Manuscrita (PNG ou JPEG)
                        </label>
                        <input type="file" 
                               id="assinatura_imagem" 
                               name="assinatura_imagem" 
                               accept="image/png,image/jpeg"
                               style="background: var(--bg-surface-2); border: 1px dashed var(--border); padding: 10px; border-radius: 6px; width: 100%;">
                        <small class="text-muted">
                            Envie a imagem da sua assinatura com <strong>fundo transparente (PNG)</strong> ou papel branco bem iluminado (máx. 2 MB). O sistema ajustará o contraste e dimensionamento automaticamente.
                        </small>
                    </div>

                    <!-- Pré-visualização da Assinatura Cadastrada -->
                    <?php if (!empty($responsavel['assinatura_arquivo'])): ?>
                        <div style="margin-top: 14px; padding: 14px; background: var(--bg-surface-2); border-radius: 8px; border: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="font-size: 13px; color: var(--text-primary);"><i class="fas fa-stamp"></i> Assinatura Cadastrada no Sistema:</strong>
                                <span style="font-size: 11px; color: var(--text-secondary);">
                                    Atualizada em: <?php echo !empty($responsavel['assinatura_atualizada_em']) ? date('d/m/Y H:i', strtotime($responsavel['assinatura_atualizada_em'])) : '—'; ?>
                                </span>
                            </div>
                            <div style="background: repeating-conic-gradient(#e5e7eb 0% 25%, #ffffff 0% 50%) 50% / 16px 16px; border-radius: 6px; padding: 14px; text-align: center; border: 1px dashed var(--border);">
                                <img src="<?php echo APP_URL; ?>responsaveis_assinatura/assinatura?id=<?php echo (int)$responsavel['id']; ?>" 
                                     alt="Assinatura de <?php echo h($usuario['nome']); ?>" 
                                     style="max-height: 80px; max-width: 100%; object-fit: contain;">
                            </div>
                            <div style="margin-top: 8px; font-size: 11px; color: var(--text-secondary); font-family: monospace; word-break: break-all;">
                                <i class="fas fa-shield-halved"></i> Hash SHA-256: <?php echo h($responsavel['assinatura_hash'] ?? '—'); ?>
                            </div>
                            <small class="text-muted" style="display: block; margin-top: 6px;">
                                <em>Dica: Caso queira alterar ou recadastrar uma nova assinatura, basta selecionar outro arquivo acima e salvar.</em>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Informações da conta -->
                <div style="background: var(--bg-surface-2); border-radius: 8px; padding: 14px 18px; margin-top: 20px; font-size: 13px; color: var(--text-secondary);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span><i class="fas fa-user-tag"></i> Cargo:</span>
                        <strong style="color: var(--text-primary);"><?php echo h($usuario['cargo']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span><i class="fas fa-calendar-plus"></i> Criado em:</span>
                        <strong style="color: var(--text-primary);"><?php echo formatarDataCompleta($usuario['criado_em']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><i class="fas fa-calendar-check"></i> Atualizado:</span>
                        <strong style="color: var(--text-primary);"><?php echo formatarDataCompleta($usuario['atualizado_em']); ?></strong>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-flex gap-2" style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="<?php echo APP_URL; ?>dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('cpf_cnpj');
    if (el) {
        if (typeof window.mascararCpfCnpj === 'function') {
            window.mascararCpfCnpj(el);
        }
        el.addEventListener('input', function() {
            if (typeof window.mascararCpfCnpj === 'function') {
                window.mascararCpfCnpj(this);
            }
        });
        el.addEventListener('paste', function() {
            setTimeout(() => {
                if (typeof window.mascararCpfCnpj === 'function') {
                    window.mascararCpfCnpj(this);
                }
            }, 10);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>