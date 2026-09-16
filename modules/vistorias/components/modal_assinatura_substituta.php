<?php if ($admin_review_mode
    && $cargo === 'ADMIN'
    && $eh_relatorio_vigente
    && in_array(($vistoria['status'] ?? ''), ['APROVADA','APROVADA_COM_EXIGENCIAS'], true)
    && ($vistoria['assinatura_status'] ?? '') !== 'ASSINADO'): ?>
<div id="modalAssinaturaSubstituta" role="dialog" aria-modal="true" aria-labelledby="tituloAssinaturaSubstituta"
     style="display:none;position:fixed;inset:0;background:rgba(4,35,28,.68);z-index:10050;align-items:center;justify-content:center;padding:18px;">
    <div style="background:#fff;border-radius:14px;width:min(560px,100%);overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.28);">
        <div style="padding:20px 22px;background:#073f34;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <strong id="tituloAssinaturaSubstituta"><i class="fas fa-file-signature"></i> Autorizar assinatura substituta</strong>
            <button type="button" id="fecharAssinaturaSubstituta" aria-label="Fechar"
                    style="border:0;background:transparent;color:#fff;font-size:1.25rem;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:22px;">
            <p style="margin-top:0;">Será aplicada ao relatório <strong><?= h($vistoria['numero'] ?? '') ?></strong> a assinatura cadastrada do vistoriador atribuído <strong><?= h($ag['vistoriador_nome'] ?? 'Não atribuído') ?></strong>.</p>
            <p style="margin-bottom:14px;color:#52635e;">
                O vistoriador continuará identificado como responsável técnico. Seu usuário administrativo, localização, IP, data e hora serão registrados como executor da assinatura substituta. Depois da assinatura, certificados, OS e agendamento serão liberados.
            </p>
            <div id="mensagemAssinaturaSubstituta" class="alert" aria-live="polite" style="display:none;margin:0;"></div>
        </div>
        <div style="padding:15px 22px;background:#f4f7f6;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary" id="cancelarAssinaturaSubstituta">Cancelar</button>
            <button type="button" class="btn btn-success" id="confirmarAssinaturaSubstituta">
                <i class="fas fa-location-dot"></i> Permitir localização e assinar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
