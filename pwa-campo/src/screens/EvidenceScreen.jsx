import { CalendarDays, Camera, CircleX, ImagePlus, Save, Trash2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import { AppShell } from '../components/AppShell'

function Foto({ anexo, onDelete }) {
  const [src, setSrc] = useState(anexo.data_url || anexo.url_arquivo || anexo.url || '')
  useEffect(() => {
    if (!anexo.blob) return undefined
    const url = URL.createObjectURL(anexo.blob)
    setSrc(url)
    return () => URL.revokeObjectURL(url)
  }, [anexo.blob])
  return <figure><img src={src} alt="Evidência da não conformidade" /><figcaption><span>{anexo.local ? 'Aguardando envio' : 'Sincronizada'}</span><button type="button" onClick={() => onDelete(anexo)} aria-label={`Excluir foto ${anexo.nome || anexo.nome_original || ''}`}><Trash2 size={16} /></button></figcaption></figure>
}

export function EvidenceScreen({ item, resposta, prazoCorrecao, online, pending, syncing, error, onSync, onBack, onUpdate, onPhoto, onDeletePhoto, onSave }) {
  const anexos = item.anexos || []
  return (
    <AppShell title="Não conformidade" online={online} pending={pending} syncing={syncing} onSync={onSync} onBack={onBack}>
      <section className="issue-banner"><CircleX size={25} /><span><strong>Item não conforme</strong><small>{item.descricao}</small></span></section>
      <section className="evidence-content">
        <label className="field-label">Evidência fotográfica <small>(opcional)</small></label>
        <div className="photo-grid">
          {anexos.map(anexo => <Foto key={anexo.id} anexo={anexo} onDelete={onDeletePhoto} />)}
          <label className="photo-add"><Camera size={28} /><span>Adicionar foto</span><input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" onChange={event => { const file = event.target.files?.[0]; event.target.value = ''; if (file) onPhoto(file) }} /></label>
        </div>
        {pending ? <div className="sync-safety-note" role="status"><strong>Foto protegida neste aparelho</strong><span>Ela continuará aguardando e será reenviada sem precisar tirar outra foto.</span></div> : null}
        {error ? <div className="form-error evidence-error" role="alert"><strong>Envio pendente</strong><span>{error}</span></div> : null}
        <label className="field-label" htmlFor="observacao">Observação <small>(opcional)</small></label>
        <textarea id="observacao" rows="4" maxLength="500" value={resposta.observacao || ''} onChange={e => onUpdate({ observacao: e.target.value })} placeholder="Descreva o problema encontrado" />
        <small className="char-count">{(resposta.observacao || '').length}/500</small>
        <label className="field-label" htmlFor="vencimento">Prazo geral para correção</label>
        <div className="date-field fixed"><CalendarDays size={18} /><input id="vencimento" type="date" value={resposta.sem_prazo ? '' : (prazoCorrecao || '')} readOnly aria-readonly="true" disabled={Boolean(resposta.sem_prazo)} /></div>
        <label className="checkbox-field"><input type="checkbox" checked={Boolean(resposta.sem_prazo)} onChange={event => onUpdate({ sem_prazo: event.target.checked, vencimento: event.target.checked ? '' : prazoCorrecao })} /> A/S — Antes de suspender</label>
        <small className="field-help">A/S bloqueia a embarcação e todos os certificados até a verificação de cumprimento ser aprovada.</small>
        <label className="field-label" htmlFor="normam">Referência NORMAM</label>
        <input id="normam" value={resposta.item_normam || ''} onChange={e => onUpdate({ item_normam: e.target.value })} placeholder="Ex.: NORMAM-202/DPC, item 2.1" />
      </section>
      <div className="sticky-actions"><label className="secondary-button file-button"><ImagePlus size={18} /> Outra foto<input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" onChange={event => { const file = event.target.files?.[0]; event.target.value = ''; if (file) onPhoto(file) }} /></label><button className="danger-button" onClick={onSave}><Save size={18} /> Salvar exigência</button></div>
    </AppShell>
  )
}
