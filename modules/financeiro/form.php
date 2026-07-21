<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: form.php - Formulario para criar / editar lancamento
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Buscar lancamento se for edicao
$id = $_GET['id'] ?? '';
$lancamento = null;
$comprovantes = [];
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $lancamento = financeiroExigirAcessoLancamento($pdo, $id);

        if (!$lancamento) {
            setMensagem('error', 'Lancamento nao encontrado.');
            redirecionar(APP_URL . 'financeiro');
        }

        try {
            $stmtComp = $pdo->prepare("SELECT * FROM financeiro_comprovantes WHERE lancamento_id = :lancamento_id ORDER BY criado_em DESC");
            $stmtComp->execute([':lancamento_id' => $id]);
            $comprovantes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erro ao buscar comprovantes financeiros: ' . $e->getMessage());
            $comprovantes = [];
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar lancamento: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do lancamento.');
        redirecionar(APP_URL . 'financeiro');
    }
}

try {
    $escritorioSelecionado = $lancamento['escritorio_id'] ?? financeiroResolverEscritorio($pdo, $_GET['escritorio_id'] ?? null);
    if ($escritorioSelecionado === 'todos') $escritorioSelecionado = financeiroEscritorioUsuario($pdo) ?: (financeiroEscritorios($pdo)[0]['id'] ?? '');
    $escritorios = financeiroEscritoriosPermitidos($pdo);
    $exibirSeletorEscritorio = financeiroEhAdmin() || count($escritorios) > 1;
    $todosVendedores = $pdo->query("SELECT u.id,u.nome,GROUP_CONCAT(ue.escritorio_id) escritorios_ids FROM usuarios u JOIN usuario_escritorios ue ON ue.usuario_id=u.id WHERE u.cargo='VENDEDOR' AND u.ativo=1 AND u.excluido_em IS NULL GROUP BY u.id,u.nome ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
    $responsavelSelecionado = $lancamento['responsavel_usuario_id'] ?? (getCargo()==='VENDEDOR' ? ($_SESSION['usuario_id'] ?? '') : '');
} catch (Throwable $e) {
    setMensagem('error', $e->getMessage());
    redirecionar(APP_URL . 'financeiro');
}

// Gerar CSRF token
$csrf = gerarCSRF();

// Valor formatado para exibicao no form
$valorFormatado = '';
if ($lancamento) {
    $valorFormatado = number_format($lancamento['valor_original'] ?? $lancamento['valor'], 2, ',', '.');
}

$titulo_page = ($isEdicao ? 'Editar' : 'Novo') . ' Lancamento - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Lancamento' : 'Novo Lancamento'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" 
                  action="<?php echo APP_URL; ?>financeiro/actions?action=salvar" 
                  enctype="multipart/form-data"
                  id="formFinanceiro"
                  onsubmit="return validarFormulario('formFinanceiro')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="id" value="<?php echo h($lancamento['id'] ?? ''); ?>">

                <div class="grid-2">
                    <div class="form-group">
                        <label for="escritorio_id"><i class="fas fa-building"></i> Escritório *</label>
                        <?php if ($exibirSeletorEscritorio): ?>
                        <select id="escritorio_id" name="escritorio_id" required>
                            <?php foreach ($escritorios as $e): ?><option value="<?= h($e['id']) ?>" <?= $e['id']===$escritorioSelecionado?'selected':'' ?>><?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></option><?php endforeach ?>
                        </select>
                        <?php else: ?>
                        <input type="hidden" id="escritorio_id" name="escritorio_id" value="<?= h($escritorioSelecionado) ?>">
                        <input value="<?= h(($escritorios[0]['nome']??'Escritório').' · '.($escritorios[0]['cidade']??'').'/'.($escritorios[0]['uf']??'')) ?>" disabled>
                        <?php endif ?>
                    </div>
                    <div class="form-group" id="grupoResponsavel">
                        <label for="responsavel_usuario_id"><i class="fas fa-user-tie"></i> Vendedor responsável</label>
                        <select id="responsavel_usuario_id" name="responsavel_usuario_id" data-original="<?= h($lancamento['responsavel_usuario_id']??'') ?>" data-original-escritorio="<?= h($lancamento['escritorio_id']??'') ?>">
                            <option value="">Sem responsável</option>
                            <?php foreach ($todosVendedores as $v): ?><option value="<?= h($v['id']) ?>" data-escritorios="<?= h($v['escritorios_ids']) ?>" <?= $v['id']===$responsavelSelecionado?'selected':'' ?>><?= h($v['nome']) ?></option><?php endforeach ?>
                        </select>
                        <small class="text-muted">Usado no painel de desempenho das receitas.</small>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Tipo -->
                    <div class="form-group">
                        <label for="tipo">
                            <i class="fas fa-tag"></i> Tipo *
                        </label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecione...</option>
                            <option value="RECEITA" <?php echo ($lancamento['tipo'] ?? '') === 'RECEITA' ? 'selected' : ''; ?>>
                                💰 Receita
                            </option>
                            <option value="DESPESA" <?php echo ($lancamento['tipo'] ?? '') === 'DESPESA' ? 'selected' : ''; ?>>
                                💸 Despesa
                            </option>
                        </select>
                    </div>

                    <!-- Frequência (Recorrência) -->
                    <div class="form-group">
                        <label for="frequencia">
                            <i class="fas fa-sync"></i> Recorrência *
                        </label>
                        <select id="frequencia" name="frequencia" required>
                            <option value="unica" <?php echo ($lancamento['frequencia'] ?? 'unica') === 'unica' ? 'selected' : ''; ?>>Lançamento Único</option>
                            <option value="mensal" <?php echo ($lancamento['frequencia'] ?? '') === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                            <option value="trimestral" <?php echo ($lancamento['frequencia'] ?? '') === 'trimestral' ? 'selected' : ''; ?>>Trimestral</option>
                            <option value="anual" <?php echo ($lancamento['frequencia'] ?? '') === 'anual' ? 'selected' : ''; ?>>Anual</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Vencimento -->
                    <div class="form-group">
                        <label for="data_vencimento">
                            <i class="fas fa-calendar"></i> Vencimento *
                        </label>
                        <input type="date" 
                               id="data_vencimento" 
                               name="data_vencimento" 
                               required
                               value="<?php echo h($lancamento['data_vencimento'] ?? date('Y-m-d')); ?>">
                    </div>

                    <!-- Cliente / Despachante -->
                    <div class="form-group">
                        <label for="cliente_id">
                            <i class="fas fa-user-tie"></i> Pessoa / Despachante
                        </label>
                        <select id="cliente_id" name="cliente_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cor-borda); border-radius: 4px;">
                            <option value="">-- Selecione (Opcional) --</option>
                            <?php
                            try {
                                $stmtCli = $pdo->query("SELECT id, nome, perfil, cpf_cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC");
                                while ($c = $stmtCli->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (($lancamento['cliente_id'] ?? '') == $c['id']) ? 'selected' : '';
                                    // Adiciona badge visual (na listagem) ou sufixo para Despachantes
                                    $sufixo = ($c['perfil'] == 'despachante') ? ' [Despachante]' : '';
                                    echo "<option value='".h($c['id'])."' $selected>".h($c['nome'])." (".h($c['cpf_cnpj']).")" . $sufixo . "</option>";
                                }
                            } catch (Exception $e) {
                                error_log('Erro ao carregar clientes no financeiro: ' . $e->getMessage());
                            }
                            ?>
                        </select>
                        <small class="text-muted">Importante para vincular pagamentos de serviços (ex: Despachantes).</small>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-check-circle"></i> Status *
                        </label>
                        <select id="status" name="status" required>
                            <option value="PAGO" <?php echo ($lancamento['status'] ?? '') === 'PAGO' ? 'selected' : ''; ?>>
                                Pago / Recebido
                            </option>
                            <option value="PENDENTE" <?php echo ($lancamento['status'] ?? 'PENDENTE') === 'PENDENTE' ? 'selected' : ''; ?>>
                                Pendente
                            </option>
                            <?php if (($lancamento['status'] ?? '') === 'PARCIAL'): ?>
                            <option value="PARCIAL" selected>Parcial (controlado pelas baixas)</option>
                            <?php endif; ?>
                            <option value="CANCELADO" <?php echo ($lancamento['status'] ?? '') === 'CANCELADO' ? 'selected' : ''; ?>>
                                Cancelado
                            </option>
                        </select>
                    </div>

                    <!-- Data Pagamento -->
                    <div class="form-group">
                        <label for="data">
                            <i class="fas fa-calendar-check"></i> Data Pagamento
                        </label>
                        <input type="date" 
                               id="data" 
                               name="data" 
                               value="<?php echo h($lancamento['data'] ?? ''); ?>">
                        <small class="text-muted">Se pago, preencha a data</small>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Descricao -->
                    <div class="form-group">
                        <label for="descricao">
                            <i class="fas fa-align-left"></i> Descricao *
                        </label>
                        <input type="text" 
                               id="descricao" 
                               name="descricao" 
                               placeholder="Descricao do lancamento" 
                               required 
                               maxlength="300"
                               value="<?php echo h($lancamento['descricao'] ?? ''); ?>">
                    </div>

                    <!-- Valor -->
                    <div class="form-group">
                        <label for="valor">
                            <i class="fas fa-dollar-sign"></i> Valor (R$) *
                        </label>
                        <input type="text" 
                               id="valor" 
                               name="valor" 
                               placeholder="0,00" 
                               required
                               maxlength="12"
                               value="<?php echo h($valorFormatado); ?>"
                               oninput="formatarMoedaInput(this)">
                    </div>
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <label for="categoria">
                        <i class="fas fa-folder"></i> Categoria
                    </label>
                    <input type="text" 
                           id="categoria" 
                           name="categoria" 
                           placeholder="Ex: Servicos, Material, Frete, Combustivel..." 
                           maxlength="100"
                           value="<?php echo h($lancamento['categoria'] ?? ''); ?>">
                </div>

                <!-- Observacoes -->
                <div class="form-group">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observacoes
                    </label>
                    <textarea id="observacoes" 
                              name="observacoes" 
                              placeholder="Observacoes adicionais" 
                              rows="3"
                              maxlength="1000"><?php echo h($lancamento['observacoes'] ?? ''); ?></textarea>
                </div>

                <!-- Comprovantes / notas -->
                <div class="form-group">
                    <label for="comprovantes">
                        <i class="fas fa-paperclip"></i> Comprovantes / notas
                    </label>
                    <input type="file"
                           id="comprovantes"
                           name="comprovantes[]"
                           accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                           multiple>
                    <div id="arquivosSelecionados" style="display: none; margin-top: 8px; border: 1px solid var(--cor-borda); border-radius: 8px; overflow: hidden;"></div>
                    <small class="text-muted">Voce pode escolher varios arquivos de uma vez ou repetir a selecao antes de atualizar. Envie imagens ou PDFs de ate 10MB cada.</small>
                </div>

                <?php if ($isEdicao): ?>
                <div class="form-group">
                    <label>
                        <i class="fas fa-folder-open"></i> Ver comprovantes
                    </label>
                    <?php if (empty($comprovantes)): ?>
                        <div style="border: 1px dashed var(--cor-borda); border-radius: 8px; padding: 12px; color: var(--cor-texto-secundario);">
                            Nenhum comprovante anexado.
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 8px;">
                            <?php foreach ($comprovantes as $comp): ?>
                                <div style="display: flex; gap: 8px; align-items: center; border: 1px solid var(--cor-borda); border-radius: 8px; padding: 8px;">
                                    <a href="<?php echo APP_URL . h($comp['caminho']); ?>"
                                       target="_blank"
                                       rel="noopener"
                                       class="btn btn-secondary"
                                       style="flex: 1; justify-content: flex-start; text-align: left;">
                                        <i class="fas <?php echo strpos((string)$comp['mime_type'], 'pdf') !== false ? 'fa-file-pdf' : 'fa-file-image'; ?>"></i>
                                        <?php echo h($comp['nome_original']); ?>
                                        <span style="margin-left: auto; color: var(--cor-texto-secundario); font-size: 0.8rem;">
                                            <?php echo number_format(((int)$comp['tamanho']) / 1024, 1, ',', '.'); ?> KB
                                        </span>
                                    </a>
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            name="comprovante_id"
                                            value="<?php echo h($comp['id']); ?>"
                                            formaction="<?php echo APP_URL; ?>financeiro/actions?action=excluir_comprovante"
                                            formmethod="POST"
                                            formenctype="application/x-www-form-urlencoded"
                                            formnovalidate
                                            onclick="return confirm('Excluir este comprovante?')"
                                            title="Excluir comprovante">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> 
                            Criado em: <?php echo formatarDataCompleta($lancamento['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> 
                            Atualizado: <?php echo formatarDataCompleta($lancamento['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Salvar Lancamento'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>financeiro" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Formatar valor monetario em tempo real (formato brasileiro)
function formatarMoedaInput(input) {
    var valor = input.value.replace(/\D/g, '');
    if (valor === '') {
        input.value = '';
        return;
    }
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = valor;
}

var comprovantesInput = document.getElementById('comprovantes');
var arquivosSelecionados = document.getElementById('arquivosSelecionados');
var arquivosParaUpload = window.DataTransfer ? new DataTransfer() : null;

function atualizarListaArquivosSelecionados() {
    if (!comprovantesInput || !arquivosSelecionados) {
        return;
    }

    if (!arquivosParaUpload) {
        return;
    }

    comprovantesInput.files = arquivosParaUpload.files;
    arquivosSelecionados.innerHTML = '';

    if (arquivosParaUpload.files.length === 0) {
        arquivosSelecionados.style.display = 'none';
        return;
    }

    arquivosSelecionados.style.display = 'grid';

    Array.from(arquivosParaUpload.files).forEach(function(file, index) {
        var item = document.createElement('div');
        item.style.cssText = 'display:flex; align-items:center; gap:8px; padding:8px 10px; border-bottom:1px solid var(--cor-borda);';

        var nome = document.createElement('span');
        nome.textContent = file.name + ' (' + (file.size / 1024).toFixed(1).replace('.', ',') + ' KB)';
        nome.style.cssText = 'flex:1; overflow:hidden; text-overflow:ellipsis;';

        var remover = document.createElement('button');
        remover.type = 'button';
        remover.className = 'btn btn-danger btn-sm';
        remover.innerHTML = '<i class="fas fa-times"></i>';
        remover.title = 'Remover da selecao';
        remover.onclick = function() {
            var novaLista = new DataTransfer();
            Array.from(arquivosParaUpload.files).forEach(function(arquivo, arquivoIndex) {
                if (arquivoIndex !== index) {
                    novaLista.items.add(arquivo);
                }
            });
            arquivosParaUpload = novaLista;
            atualizarListaArquivosSelecionados();
        };

        item.appendChild(nome);
        item.appendChild(remover);
        arquivosSelecionados.appendChild(item);
    });
}

if (comprovantesInput && arquivosParaUpload) {
    comprovantesInput.addEventListener('change', function() {
        Array.from(comprovantesInput.files).forEach(function(file) {
            var jaExiste = Array.from(arquivosParaUpload.files).some(function(arquivo) {
                return arquivo.name === file.name && arquivo.size === file.size && arquivo.lastModified === file.lastModified;
            });

            if (!jaExiste) {
                arquivosParaUpload.items.add(file);
            }
        });

        atualizarListaArquivosSelecionados();
    });
}
</script>

<script>
(function () {
    const escritorio = document.getElementById('escritorio_id');
    const tipo = document.getElementById('tipo');
    const responsavel = document.getElementById('responsavel_usuario_id');
    const grupo = document.getElementById('grupoResponsavel');
    if (!escritorio || !tipo || !responsavel) return;
    function atualizarResponsaveis() {
        const escritorioId = escritorio.value;
        Array.from(responsavel.options).forEach(function (opcao, indice) {
            if (indice === 0) return;
            const vinculado = (opcao.dataset.escritorios || '').split(',').includes(escritorioId);
            const historico = opcao.value === responsavel.dataset.original && escritorioId === responsavel.dataset.originalEscritorio;
            const visivel = vinculado || historico;
            opcao.hidden = !visivel;
            opcao.disabled = !visivel;
            if (!visivel && opcao.selected) responsavel.value = '';
        });
        const receita = tipo.value === 'RECEITA';
        grupo.style.display = receita ? '' : 'none';
        responsavel.disabled = !receita;
    }
    escritorio.addEventListener('change', atualizarResponsaveis);
    tipo.addEventListener('change', atualizarResponsaveis);
    atualizarResponsaveis();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
