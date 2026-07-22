<?php

require_once __DIR__ . '/aprovacao_documentos.php';

function aprovacaoUiResponsaveis(PDO $pdo): array
{
    $stmt=$pdo->query("SELECT id,nome_completo,cargo_titulo,cpf_cnpj FROM responsaveis_assinatura WHERE ativo=1 AND cpf_cnpj IS NOT NULL AND cpf_cnpj<>'' AND assinatura_arquivo IS NOT NULL AND assinatura_arquivo<>'' AND assinatura_hash IS NOT NULL ORDER BY nome_completo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderBotaoAprovacaoDocumento(PDO $pdo,string $tipo,string $id,string $status,bool $assinado=false,?int $responsavelId=null): void
{
    if(getCargo()!=='ADMIN')return;
    $stmt=$pdo->prepare("SELECT token_validacao,status FROM documento_aprovacoes WHERE documento_tipo=? AND documento_id=? AND status IN ('APROVADO','CANCELADO') ORDER BY versao DESC LIMIT 1");$stmt->execute([$tipo,$id]);$audit=$stmt->fetch(PDO::FETCH_ASSOC);
    if($audit&&$audit['status']==='APROVADO'){
        echo '<a class="btn btn-sm btn-success" target="_blank" title="Validar documento aprovado" aria-label="Validar documento aprovado" href="'.h(APP_URL.'validar/'.$audit['token_validacao']).'"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></a>';
        echo '<button type="button" class="btn btn-sm btn-outline-danger js-cancelar-aprovacao" title="Cancelar documento aprovado" data-tipo="'.h($tipo).'" data-id="'.h($id).'"><i class="fas fa-ban"></i></button>';return;
    }
    $allowed=$tipo==='RELATORIO'?false:($tipo==='PARECER_PLANOS'?($status==='AGUARDANDO_APROVACAO'):($status==='emitido'&&!$assinado));
    if(!$allowed)return;
    $label=$tipo==='PARECER_PLANOS'?' Aprovar e assinar':'';
    echo '<button type="button" class="btn btn-sm btn-warning js-aprovar-documento" title="Aprovar e assinar" data-tipo="'.h($tipo).'" data-id="'.h($id).'" data-responsavel="'.h((string)($responsavelId?:'')).'"><i class="fas fa-file-signature"></i>'.$label.'</button>';
}

function renderAprovacaoUi(PDO $pdo): void
{
    static $rendered=false;if($rendered||getCargo()!=='ADMIN')return;$rendered=true;$responsaveis=aprovacaoUiResponsaveis($pdo);
    ?>
    <div id="auditApprovalModal" style="display:none;position:fixed;inset:0;background:rgba(4,35,28,.62);z-index:10050;align-items:center;justify-content:center;padding:18px">
      <div style="background:#fff;border-radius:14px;width:min(560px,100%);box-shadow:0 24px 70px rgba(0,0,0,.28);overflow:hidden">
        <div style="padding:20px 22px;background:#073f34;color:#fff"><strong style="font-size:18px">Aprovar e assinar eletronicamente</strong><div style="font-size:13px;opacity:.84;margin-top:4px">A localização é obrigatória e será registrada na auditoria.</div></div>
        <div style="padding:22px">
          <?php if(!$responsaveis): ?><div class="alert alert-warning">Nenhum responsavel ativo possui CPF/CNPJ e assinatura completos. <a href="<?= APP_URL ?>responsaveis_assinatura">Completar cadastro</a>.</div><?php else: ?>
          <label for="auditResponsavel"><strong>Responsável técnico</strong></label>
          <select id="auditResponsavel" class="form-control" style="margin-top:7px"><option value="">Selecione...</option><?php foreach($responsaveis as $r): ?><option value="<?= h($r['id']) ?>"><?= h($r['nome_completo'].' - '.$r['cargo_titulo'].' - '.$r['cpf_cnpj']) ?></option><?php endforeach; ?></select>
          <div id="auditApprovalMessage" style="display:none;margin-top:14px;padding:11px;border-radius:7px"></div>
          <p style="font-size:12px;color:#63756e;line-height:1.45;margin:16px 0 0">Ao continuar, o ERP usará o horário do servidor, IP, coordenadas e hash SHA-256. O PDF aprovado ficará imutável.</p>
          <?php endif; ?>
        </div>
        <div style="padding:15px 22px;background:#f4f7f6;display:flex;justify-content:flex-end;gap:10px"><button type="button" class="btn btn-secondary" id="auditCancel">Cancelar</button><?php if($responsaveis): ?><button type="button" class="btn btn-success" id="auditConfirm"><i class="fas fa-location-dot"></i> Permitir localizacao e aprovar</button><?php endif; ?></div>
      </div>
    </div>
    <script>
    (function(){
      document.querySelectorAll('form input[name="action"][value="enviar_assinatura"]').forEach(function(i){i.closest('form')?.remove()});
      document.querySelectorAll('button[title*="Link de Assinatura"],button[title="Copiar Link"],button[title="Copiar Link de Assinatura"]').forEach(function(b){b.remove()});
      const modal=document.getElementById('auditApprovalModal'),select=document.getElementById('auditResponsavel'),msg=document.getElementById('auditApprovalMessage'),confirm=document.getElementById('auditConfirm');let current=null;
      function showMessage(text,error){if(!msg)return;msg.style.display='block';msg.style.background=error?'#fff0ef':'#e8f7ef';msg.style.color=error?'#a52b22':'#087342';msg.textContent=text}
      document.addEventListener('click',function(e){const btn=e.target.closest('.js-aprovar-documento');if(!btn)return;current=btn;if(select){select.value=btn.dataset.responsavel||'';if(!select.value&&select.options.length===2)select.selectedIndex=1}if(msg)msg.style.display='none';modal.style.display='flex'});
      document.getElementById('auditCancel')?.addEventListener('click',function(){modal.style.display='none';current=null});
      document.addEventListener('click',function(e){const btn=e.target.closest('.js-cancelar-aprovacao');if(!btn)return;const motivo=window.prompt('Informe o motivo do cancelamento. O PDF e a auditoria serao preservados:');if(!motivo||!motivo.trim())return;const fd=new FormData();fd.append('csrf_token',<?= json_encode(gerarCSRF()) ?>);fd.append('documento_tipo',btn.dataset.tipo);fd.append('documento_id',btn.dataset.id);fd.append('motivo',motivo.trim());btn.disabled=true;fetch(<?= json_encode(APP_URL.'documentos/cancelar') ?>,{method:'POST',body:fd,credentials:'same-origin'}).then(async r=>{const d=await r.json().catch(()=>({success:false,message:'Resposta invalida do servidor.'}));if(!r.ok||!d.success)throw new Error(d.message||'Falha no cancelamento.');window.alert(d.message);window.location.reload()}).catch(err=>{window.alert(err.message);btn.disabled=false})});
      confirm?.addEventListener('click',function(){if(!current)return;if(!select.value){showMessage('Selecione o responsavel tecnico.',true);return}if(!navigator.geolocation){showMessage('Este navegador nao oferece geolocalizacao.',true);return}confirm.disabled=true;showMessage('Obtendo localizacao...',false);
        navigator.geolocation.getCurrentPosition(function(pos){const fd=new FormData();fd.append('csrf_token',<?= json_encode(gerarCSRF()) ?>);fd.append('documento_tipo',current.dataset.tipo);fd.append('documento_id',current.dataset.id);fd.append('responsavel_id',select.value);fd.append('latitude',pos.coords.latitude);fd.append('longitude',pos.coords.longitude);fd.append('geo_precisao_m',pos.coords.accuracy||'');showMessage('Gerando e protegendo o PDF...',false);
          fetch(<?= json_encode(APP_URL.'documentos/aprovar') ?>,{method:'POST',body:fd,credentials:'same-origin'}).then(async r=>{const d=await r.json().catch(()=>({success:false,message:'Resposta invalida do servidor.'}));if(!r.ok||!d.success)throw new Error(d.message||'Falha na aprovacao.');showMessage(d.message,false);if(d.data&&d.data.token){try{sessionStorage.setItem('amazon-document-return:'+d.data.token,window.location.pathname+window.location.search+window.location.hash)}catch(ignored){}}setTimeout(()=>{window.location.href=d.data.validation_url},650)}).catch(err=>{showMessage(err.message,true);confirm.disabled=false});
        },function(err){showMessage(err.code===1?'A permissao de localizacao foi negada. Ela e obrigatoria para assinar.':'Nao foi possivel obter a localizacao com precisao.',true);confirm.disabled=false},{enableHighAccuracy:true,timeout:15000,maximumAge:0});
      });
    })();
    </script>
    <?php
}
