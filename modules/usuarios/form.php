<?php
/**
 * MODULO: USUARIOS
 * Arquivo: form.php - Formulario para criar / editar usuario
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

// Exigir login e cargo ADMIN
verificar_sessao();
exigirAcesso('usuarios');

// Buscar usuario se for edicao
$id = $_GET['id'] ?? '';
$usuario = null;
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id AND excluido_em IS NULL LIMIT 1");
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            setMensagem('error', 'Usuario nao encontrado.');
            redirecionar(APP_URL . 'usuarios');
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar usuario: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do usuario.');
        redirecionar(APP_URL . 'usuarios');
    }
}

$escritorios = financeiroEscritorios($pdo);
$gestoresDisponiveis = $pdo->prepare("SELECT id,nome,cargo FROM usuarios WHERE ativo=1 AND excluido_em IS NULL AND id<>:id ORDER BY nome");
$gestoresDisponiveis->execute([':id'=>$id ?: '']);
$gestoresDisponiveis = $gestoresDisponiveis->fetchAll();
$escritoriosSelecionados = [];
$escritorioPrincipal = '';
if ($usuario) {
    $vinculos = financeiroEscritoriosUsuario($pdo, $usuario['id'], false);
    $escritoriosSelecionados = array_column($vinculos, 'id');
    $escritorioPrincipal = financeiroEscritorioUsuario($pdo, $usuario['id']) ?? '';
    foreach ($vinculos as $vinculo) {
        if (!(int)$vinculo['ativo'] && !in_array($vinculo['id'], array_column($escritorios, 'id'), true)) $escritorios[] = $vinculo;
    }
}

// Gerar CSRF token
$csrf = gerarCSRF();

$titulo_page = ($isEdicao ? 'Editar' : 'Novo') . ' Usuario - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Usuario' : 'Novo Usuario'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" 
                  action="<?php echo APP_URL; ?>usuarios/actions?action=salvar" 
                  id="formUsuario"
                  onsubmit="return validarFormulario('formUsuario')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="id" value="<?php echo h($usuario['id'] ?? ''); ?>">

                <div class="grid-2">
                    <!-- Nome -->
                    <div class="form-group">
                        <label for="nome">
                            <i class="fas fa-user"></i> Nome completo *
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               placeholder="Nome do usuario" 
                               required 
                               maxlength="150"
                               value="<?php echo h($usuario['nome'] ?? ''); ?>">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="usuario@email.com" 
                               required 
                               maxlength="150"
                               value="<?php echo h($usuario['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Cargo -->
                    <div class="form-group">
                        <label for="cargo">
                            <i class="fas fa-user-tag"></i> Cargo *
                        </label>
                        <select id="cargo" name="cargo" required>
                            <option value="VISTORIADOR" <?php echo ($usuario['cargo'] ?? '') === 'VISTORIADOR' ? 'selected' : ''; ?>>
                                Vistoriador
                            </option>
                            <option value="ANALISTA" <?php echo ($usuario['cargo'] ?? '') === 'ANALISTA' ? 'selected' : ''; ?>>
                                Analista
                            </option>
                            <option value="VENDEDOR" <?php echo ($usuario['cargo'] ?? '') === 'VENDEDOR' ? 'selected' : ''; ?>>
                                Vendedor
                            </option>

                            <option value="ADMIN" <?php echo ($usuario['cargo'] ?? '') === 'ADMIN' ? 'selected' : ''; ?>>
                                Administrador
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-toggle-on"></i> Status
                        </label>
                        <div style="display: flex; align-items: center; gap: 10px; padding-top: 6px;">
                            <label class="toggle-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" 
                                       name="ativo" 
                                       value="1" 
                                       <?php echo ($usuario['ativo'] ?? 1) ? 'checked' : ''; ?>
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span>Usuario ativo</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="gestor_id"><i class="fas fa-user-tie"></i> Gestor direto</label>
                    <select id="gestor_id" name="gestor_id">
                        <option value="">Sem gestor direto</option>
                        <?php foreach ($gestoresDisponiveis as $gestor): ?>
                        <option value="<?= h($gestor['id']) ?>" <?= ($usuario['gestor_id'] ?? '')===$gestor['id']?'selected':'' ?>><?= h($gestor['nome'].' · '.$gestor['cargo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Este vínculo define equipe e gestor nas regras de mensagens internas.</small>
                </div>

                <!-- Seção SGQ: Competências e Credenciais Técnicas (ISO 7.2 e NORMAM) -->
                <div style="margin: 20px 0; padding: 16px; border: 1px solid rgba(88, 166, 255, 0.3); border-radius: 8px; background: rgba(56, 139, 253, 0.04);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="margin: 0; color: var(--cor-destaque); font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-award"></i> Competências e Credenciais Técnicas (ISO 9001:2015 & NORMAM)
                        </h4>
                        <span class="badge" style="background: rgba(56, 139, 253, 0.2); color: #58a6ff; font-size: 0.75rem;">Requisito ISO 7.2</span>
                    </div>
                    <p class="text-muted" style="font-size: 0.82rem; margin-bottom: 14px;">
                        Credenciais obrigatórias para atuação técnica em vistorias navais e emissão de laudos. Alterações nestes campos são auditadas compulsoriamente no SGQ.
                    </p>

                    <div class="grid-2">
                        <!-- Status no SGQ -->
                        <div class="form-group">
                            <label for="status_sgq">
                                <i class="fas fa-shield-halved"></i> Status Qualificação SGQ *
                            </label>
                            <select id="status_sgq" name="status_sgq">
                                <option value="QUALIFICADO" <?= ($usuario['status_sgq'] ?? 'QUALIFICADO') === 'QUALIFICADO' ? 'selected' : '' ?>>QUALIFICADO (Apto para Vistorias)</option>
                                <option value="SUSPENSO_RECICLAGEM" <?= ($usuario['status_sgq'] ?? '') === 'SUSPENSO_RECICLAGEM' ? 'selected' : '' ?>>SUSPENSO_RECICLAGEM (Treinamento Pendente)</option>
                                <option value="DESQUALIFICADO" <?= ($usuario['status_sgq'] ?? '') === 'DESQUALIFICADO' ? 'selected' : '' ?>>DESQUALIFICADO (Bloqueado no Agendamento)</option>
                                <option value="INATIVO" <?= ($usuario['status_sgq'] ?? '') === 'INATIVO' ? 'selected' : '' ?>>INATIVO</option>
                            </select>
                        </div>

                        <!-- Data da última avaliação de competência -->
                        <div class="form-group">
                            <label for="data_ultima_avaliacao_competencia">
                                <i class="fas fa-calendar-check"></i> Última Avaliação de Competência
                            </label>
                            <input type="date" 
                                   id="data_ultima_avaliacao_competencia" 
                                   name="data_ultima_avaliacao_competencia" 
                                   value="<?= h($usuario['data_ultima_avaliacao_competencia'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="grid-3" style="display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 12px; margin-top: 10px;">
                        <!-- Conselho Profissional (Tipo) -->
                        <div class="form-group">
                            <label for="registro_conselho_tipo">Conselho de Classe</label>
                            <select id="registro_conselho_tipo" name="registro_conselho_tipo">
                                <option value="">Nenhum / Não aplicável</option>
                                <option value="CREA" <?= ($usuario['registro_conselho_tipo'] ?? '') === 'CREA' ? 'selected' : '' ?>>CREA (Engenheiro Naval)</option>
                                <option value="CFT" <?= ($usuario['registro_conselho_tipo'] ?? '') === 'CFT' ? 'selected' : '' ?>>CFT (Técnico Naval)</option>
                                <option value="OUTRO" <?= ($usuario['registro_conselho_tipo'] ?? '') === 'OUTRO' ? 'selected' : '' ?>>OUTRO</option>
                            </select>
                        </div>

                        <!-- Registro Conselho (Número) -->
                        <div class="form-group">
                            <label for="registro_conselho_numero">Nº Registro Conselho</label>
                            <input type="text" 
                                   id="registro_conselho_numero" 
                                   name="registro_conselho_numero" 
                                   placeholder="Ex: 12345/D-PA" 
                                   value="<?= h($usuario['registro_conselho_numero'] ?? '') ?>">
                        </div>

                        <!-- Validade Conselho -->
                        <div class="form-group">
                            <label for="registro_conselho_validade">Validade Conselho</label>
                            <input type="date" 
                                   id="registro_conselho_validade" 
                                   name="registro_conselho_validade" 
                                   value="<?= h($usuario['registro_conselho_validade'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="margin-top: 10px;">
                        <!-- Credencial Marinha (Número) -->
                        <div class="form-group">
                            <label for="credencial_marinha_numero">
                                <i class="fas fa-anchor"></i> Nº Credencial Marinha (DPC)
                            </label>
                            <input type="text" 
                                   id="credencial_marinha_numero" 
                                   name="credencial_marinha_numero" 
                                   placeholder="Ex: DPC/CP-2026/045" 
                                   value="<?= h($usuario['credencial_marinha_numero'] ?? '') ?>">
                        </div>

                        <!-- Validade Marinha -->
                        <div class="form-group">
                            <label for="credencial_marinha_validade">
                                <i class="fas fa-calendar-day"></i> Validade Credencial Marinha
                            </label>
                            <input type="date" 
                                   id="credencial_marinha_validade" 
                                   name="credencial_marinha_validade" 
                                   value="<?= h($usuario['credencial_marinha_validade'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Escopo de Habilitação -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label for="escopo_habilitacao">
                            <i class="fas fa-list-check"></i> Escopo de Habilitação Técnica / NORMAM
                        </label>
                        <input type="text" 
                               id="escopo_habilitacao" 
                               name="escopo_habilitacao" 
                               placeholder="Ex: Arqueação NORMAM-202, Borda Livre NORMAM-201, Vistorias CSN Interior" 
                               value="<?= h($usuario['escopo_habilitacao'] ?? '') ?>">
                    </div>

                    <?php if ($isEdicao): ?>
                    <!-- Justificativa de alteração para auditoria ISO 7.5 -->
                    <div class="form-group" style="margin-top: 10px;">
                        <label for="motivo_alteracao_sgq" style="color: #e3b341;">
                            <i class="fas fa-pen-to-square"></i> Justificativa de Alteração / Renovação de Credencial (ISO 7.5)
                        </label>
                        <input type="text" 
                               id="motivo_alteracao_sgq" 
                               name="motivo_alteracao_sgq" 
                               placeholder="Ex: Renovação anual de credencial deferida pela Capitania dos Portos conforme Portaria nº 123" 
                               value="">
                        <small class="text-muted">Se você alterou datas ou status de qualificação, informe a justificativa para a trilha de auditoria.</small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-building"></i> Escritório(s) do funcionário *</label>
                    <small class="text-muted" style="display:block;margin-bottom:10px">Selecione todos os escritórios em que este funcionário pode atuar e marque um como principal.</small>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px">
                        <?php foreach ($escritorios as $e): $selecionado=in_array($e['id'],$escritoriosSelecionados,true); ?>
                        <div style="border:1px solid var(--cor-borda);border-radius:8px;padding:12px;<?= !(int)$e['ativo']?'opacity:.65':'' ?>">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                                <input type="checkbox" name="escritorios_ids[]" value="<?= h($e['id']) ?>" <?= $selecionado?'checked':'' ?> <?= !(int)$e['ativo']?'disabled':'' ?> onchange="atualizarEscritoriosUsuario()">
                                <strong><?= h($e['nome']) ?></strong>
                            </label>
                            <small><?= h($e['cidade'].'/'.$e['uf']) ?><?= !(int)$e['ativo']?' · Inativo':'' ?></small>
                            <?php if((int)$e['ativo']): ?><label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:.85rem"><input type="radio" name="escritorio_principal_id" value="<?= h($e['id']) ?>" <?= $e['id']===$escritorioPrincipal?'checked':'' ?>> Principal</label><?php endif ?>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <div id="erroEscritorios" class="text-danger" style="display:none;margin-top:8px">Selecione ao menos um escritório e defina o principal.</div>
                </div>

                <hr style="border-color: var(--cor-borda); margin: 20px 0;">

                <!-- Senha -->
                <div class="grid-2">
                    <div class="form-group">
                        <label for="senha">
                            <i class="fas fa-lock"></i> Senha <?php echo $isEdicao ? '(deixe vazio para manter)' : '*'; ?>
                        </label>
                        <div class="password-input" style="position: relative;">
                            <input type="password" 
                                   id="senha" 
                                   name="senha" 
                                   placeholder="<?php echo $isEdicao ? '••••••••' : 'Minimo 6 caracteres'; ?>" 
                                   <?php echo $isEdicao ? '' : 'required'; ?>
                                   minlength="6"
                                   style="padding-right: 40px;">
                            <button type="button" 
                                    class="toggle-senha" 
                                    onclick="toggleSenha('senha', 'icone-senha')"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--cor-texto-secundario); cursor: pointer;">
                                <i class="fas fa-eye" id="icone-senha"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="senha_confirma">
                            <i class="fas fa-lock"></i> Confirmar senha <?php echo $isEdicao ? '' : '*'; ?>
                        </label>
                        <div class="password-input" style="position: relative;">
                            <input type="password" 
                                   id="senha_confirma" 
                                   name="senha_confirma" 
                                   placeholder="Repita a senha" 
                                   <?php echo $isEdicao ? '' : 'required'; ?>
                                   minlength="6"
                                   style="padding-right: 40px;">
                            <button type="button" 
                                    class="toggle-senha" 
                                    onclick="toggleSenha('senha_confirma', 'icone-senha-confirma')"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--cor-texto-secundario); cursor: pointer;">
                                <i class="fas fa-eye" id="icone-senha-confirma"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> 
                            Criado em: <?php echo formatarDataCompleta($usuario['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> 
                            Atualizado: <?php echo formatarDataCompleta($usuario['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Criar Usuario'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>usuarios" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function atualizarEscritoriosUsuario() {
    const marcados = [...document.querySelectorAll('input[name="escritorios_ids[]"]:checked')];
    const ids = new Set(marcados.map(c => c.value));
    const radios = [...document.querySelectorAll('input[name="escritorio_principal_id"]')];
    radios.forEach(r => { r.disabled = !ids.has(r.value); if (r.disabled) r.checked = false; });
    if (marcados.length === 1) {
        const unico = radios.find(r => r.value === marcados[0].value);
        if (unico) unico.checked = true;
    }
}
document.getElementById('formUsuario').addEventListener('submit', function(event) {
    const temEscritorio = document.querySelector('input[name="escritorios_ids[]"]:checked');
    const temPrincipal = document.querySelector('input[name="escritorio_principal_id"]:checked');
    document.getElementById('erroEscritorios').style.display = temEscritorio && temPrincipal ? 'none' : 'block';
    if (!temEscritorio || !temPrincipal) event.preventDefault();
});
atualizarEscritoriosUsuario();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
