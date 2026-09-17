<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('responsaveis_assinatura');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$responsavel = null;
$usuariosDisponiveis = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE id = ?");
    $stmt->execute([$id]);
    $responsavel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$responsavel) {
        header("Location: " . APP_URL . "responsaveis_assinatura?error=" . urlencode("Responsável não encontrado."));
        exit;
    }
}

$stmtUsuarios = $pdo->prepare("SELECT u.id,u.nome,u.email,u.cargo
    FROM usuarios u
    LEFT JOIN responsaveis_assinatura ra ON ra.usuario_id=u.id AND ra.id<>:responsavel
    WHERE u.ativo=1 AND u.excluido_em IS NULL
      AND u.cargo IN ('ADMIN','VISTORIADOR','ANALISTA')
      AND ra.id IS NULL
    ORDER BY u.nome");
$stmtUsuarios->execute([':responsavel'=>$id]);
$usuariosDisponiveis = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

$page_title = $id ? "Editar Responsável" : "Novo Responsável";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $page_title ?></h1>
        <a href="<?= APP_URL ?>responsaveis_assinatura" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= APP_URL ?>responsaveis_assinatura/actions" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="<?= $id ? 'update' : 'create' ?>">
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome_completo" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome_completo" name="nome_completo" 
                               value="<?= htmlspecialchars($responsavel['nome_completo'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cargo_titulo" class="form-label">Cargo/Título *</label>
                        <input type="text" class="form-control" id="cargo_titulo" name="cargo_titulo" 
                               value="<?= htmlspecialchars($responsavel['cargo_titulo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="usuario_id" class="form-label">Usuário vinculado *</label>
                        <select class="form-control" id="usuario_id" name="usuario_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($usuariosDisponiveis as $usuario): ?>
                                <option value="<?= h($usuario['id']) ?>" data-email="<?= h($usuario['email']) ?>" <?= ($responsavel['usuario_id'] ?? '') === $usuario['id'] ? 'selected' : '' ?>>
                                    <?= h($usuario['nome'].' · '.$usuario['cargo'].' · '.$usuario['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Este usuário será o único autorizado a usar esta assinatura.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-mail para notificações *</label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="190"
                               value="<?= h($responsavel['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cpf_cnpj" class="form-label">CPF/CNPJ *</label>
                        <?php
                        $cpfRespExibicao = $responsavel['cpf_cnpj'] ?? '';
                        $cpfRespDigitos = preg_replace('/\D/', '', (string)$cpfRespExibicao);
                        if (strlen($cpfRespDigitos) === 11) {
                            $cpfRespExibicao = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfRespDigitos);
                        } elseif (strlen($cpfRespDigitos) === 14) {
                            $cpfRespExibicao = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cpfRespDigitos);
                        }
                        ?>
                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj"
                               value="<?= htmlspecialchars($cpfRespExibicao) ?>"
                               placeholder="000.000.000-00 ou 00.000.000/0000-00"
                               maxlength="18"
                               autocomplete="off"
                               oninput="mascararCpfCnpj(this)" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="registro_profissional" class="form-label">Registro Profissional</label>
                        <input type="text" class="form-control" id="registro_profissional" name="registro_profissional" 
                               value="<?= htmlspecialchars($responsavel['registro_profissional'] ?? '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ativo" class="form-label">Status *</label>
                        <select class="form-control" id="ativo" name="ativo" required>
                            <option value="1" <?= ($responsavel['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= isset($responsavel['ativo']) && $responsavel['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="assinatura_imagem" class="form-label">Assinatura manuscrita <?= $id ? '' : '*' ?></label>
                        <input type="file" class="form-control" id="assinatura_imagem" name="assinatura_imagem"
                               accept="image/png,image/jpeg,.png,.jpg,.jpeg" onchange="previewAssinaturaInstantanea(this)" <?= $id ? '' : 'required' ?>>
                        <small class="form-text text-muted">PNG ou JPEG, até 2 MB. Use fundo branco ou transparente, boa resolução e proporção aproximada de 3:1.</small>

                        <!-- Preview Instantaneo -->
                        <div id="box_preview_nova_assinatura" style="display: none; margin-top: 12px; padding: 14px; background: rgba(56, 189, 248, 0.08); border-radius: 8px; border: 1px solid rgba(56, 189, 248, 0.35);">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="font-size: 13px; color: #0284c7;"><i class="fas fa-eye"></i> Prévia da nova imagem selecionada (antes de salvar):</strong>
                                <span class="badge badge-success" id="badge_preview_info">Pronta para salvar</span>
                            </div>
                            <div style="background: repeating-conic-gradient(#e5e7eb 0% 25%, #ffffff 0% 50%) 50% / 16px 16px; border-radius: 6px; padding: 12px; text-align: center; border: 1px dashed #0284c7;">
                                <img id="img_preview_nova_assinatura" src="" alt="Prévia" style="max-height: 80px; max-width: 100%; object-fit: contain;">
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                                <small id="txt_preview_detalhes" class="text-muted"></small>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerSelecaoAssinatura()"><i class="fas fa-times"></i> Cancelar seleção</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info" role="note">
                    <strong>Como preparar:</strong> assine com caneta azul ou preta em uma folha limpa, fotografe ou digitalize com boa iluminação e recorte deixando pouca margem. A imagem será redimensionada sem distorção. Ela é a representação visual; a camada qualificada ICP-Brasil será integrada separadamente.
                </div>

                <?php if ($id && !empty($responsavel['assinatura_arquivo'])): ?>
                    <div class="card bg-light mb-3"><div class="card-body">
                        <div class="d-flex align-items-center flex-wrap" style="gap:18px">
                            <img src="<?= APP_URL ?>responsaveis_assinatura/assinatura?id=<?= $id ?>" alt="Assinatura cadastrada" style="width:270px;height:90px;object-fit:contain;background:#fff;border:1px solid #ccd7d2;border-radius:6px;padding:8px">
                            <div><strong>Assinatura atual</strong><br><small>Atualizada em <?= !empty($responsavel['assinatura_atualizada_em']) ? date('d/m/Y H:i:s', strtotime($responsavel['assinatura_atualizada_em'])) : 'data não informada' ?></small><br><small>Envie outro arquivo para criar uma nova versão.</small></div>
                        </div>
                    </div></div>
                <?php endif; ?>

                <div class="text-right mt-4">
                    <a href="<?= APP_URL ?>responsaveis_assinatura" class="btn btn-secondary mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewAssinaturaInstantanea(input) {
    const box = document.getElementById('box_preview_nova_assinatura');
    const img = document.getElementById('img_preview_nova_assinatura');
    const detalhes = document.getElementById('txt_preview_detalhes');
    if (!input.files || !input.files[0]) {
        if (box) box.style.display = 'none';
        return;
    }
    const file = input.files[0];
    if (!file.type.match(/^image\/(png|jpeg|jpg)$/i)) {
        alert('Selecione uma imagem PNG ou JPEG.');
        input.value = '';
        if (box) box.style.display = 'none';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        alert('O arquivo selecionado excede 2 MB.');
        input.value = '';
        if (box) box.style.display = 'none';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
        box.style.display = 'block';
        detalhes.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    };
    reader.readAsDataURL(file);
}

function removerSelecaoAssinatura() {
    const input = document.getElementById('assinatura_imagem');
    const box = document.getElementById('box_preview_nova_assinatura');
    const img = document.getElementById('img_preview_nova_assinatura');
    if (input) input.value = '';
    if (img) img.src = '';
    if (box) box.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
