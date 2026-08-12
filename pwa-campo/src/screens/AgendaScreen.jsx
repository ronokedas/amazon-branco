import { CalendarDays, Camera, ChevronRight, Clock3, CloudDownload, MapPin, Menu, Ship, UserRound } from 'lucide-react'
import { AppShell } from '../components/AppShell'
import { BottomNav } from '../components/BottomNav'
import { useEffect, useState } from 'react'

function formatarData(data) {
  if (!data) return 'Data a definir'
  return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${data}T12:00:00Z`))
}

function VesselPhoto({ item, onPhoto }) {
  const [localUrl, setLocalUrl] = useState('')
  useEffect(() => {
    if (!item.foto_local_blob) { setLocalUrl(''); return undefined }
    const url = URL.createObjectURL(item.foto_local_blob)
    setLocalUrl(url)
    return () => URL.revokeObjectURL(url)
  }, [item.foto_local_blob])
  const src = localUrl || item.foto_url || '/assets/img/portal-hero-ship.png'
  const actionLabel = item.foto_status === 'pendente'
      ? 'Salva no aparelho'
      : item.foto_url || localUrl ? 'Trocar' : 'Adicionar'
  return <label className={`vessel-photo-control ${item.foto_url || localUrl ? 'has-photo' : ''}`}>
    <img src={src} alt={`Foto da embarcação ${item.embarcacao}`} />
    <span><Camera size={14} /> {actionLabel}</span>
    <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" aria-label={`${item.foto_url || localUrl ? 'Atualizar' : 'Adicionar'} foto da embarcação ${item.embarcacao}`} onChange={event => { const file = event.target.files?.[0]; event.target.value = ''; if (file) onPhoto(item, file) }} />
  </label>
}

function AgendaCard({ item, onOpen, onVesselPhoto }) {
  const cumprimento = Number(item.tarefa_cumprimento) === 1 || item.finalidade === 'CUMPRIMENTO_EXIGENCIAS'
  const progresso = Number(item.total_itens) ? Math.round((Number(item.respondidos) / Number(item.total_itens)) * 100) : 0
  const iniciado = progresso > 0 || item.vistoria_id
  return (
    <article className={`agenda-card ${item.baixada ? 'is-downloaded' : 'needs-download'}`}>
      <div className="agenda-card-top">
        <VesselPhoto item={item} onPhoto={onVesselPhoto} />
        <button className="agenda-card-main" onClick={() => onOpen(item)}>
        <span className="agenda-details">
          <strong>{item.embarcacao}</strong>
          <span><CalendarDays size={15} /> {formatarData(item.data_vistoria)}</span>
          {cumprimento ? <span className="status-label red">Verificar cumprimento A/S</span> : null}
          <span><Clock3 size={15} /> {item.hora_vistoria ? item.hora_vistoria.slice(0, 5) : 'Horário a definir'}</span>
          <span><MapPin size={15} /> {item.local || 'Local a definir'}</span>
          <span><Ship size={15} /> {item.registro || 'Registro não informado'} · {item.tipo_vistoria || 'Tipo não informado'}</span>
          <span><UserRound size={15} /> {item.cliente || 'Cliente não informado'}</span>
        </span>
        <ChevronRight size={20} />
        </button>
      </div>
      <div className="agenda-meta">
        <span className={`status-label ${cumprimento ? 'red' : iniciado ? 'blue' : 'green'}`}>{cumprimento ? 'Certificação bloqueada' : iniciado ? `Em andamento · ${progresso}%` : 'Pronta para iniciar'}</span>
        <span className="offline-ready"><CloudDownload size={15} /> {item.baixada ? 'Disponível offline' : 'Abra para baixar'}</span>
      </div>
      <button className="primary-button" onClick={() => onOpen(item)}>{cumprimento ? 'Verificar cumprimento' : iniciado ? 'Continuar vistoria' : 'Iniciar vistoria'} <ChevronRight size={18} /></button>
    </article>
  )
}

export function AgendaScreen({ session, agenda, online, onOpen, onVesselPhoto, onInstall, onNavigate }) {
  const [installHelp, setInstallHelp] = useState(false)
  const instalar = async () => {
    const instalado = await onInstall?.()
    if (!instalado) setInstallHelp(true)
  }
  const hoje = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'America/Sao_Paulo',
  }).format(new Date())
  const grupos = [
    ['Atrasadas', agenda.filter(item => item.data_vistoria && item.data_vistoria < hoje)],
    ['Vistorias de hoje', agenda.filter(item => item.data_vistoria === hoje)],
    ['Próximas vistorias', agenda.filter(item => !item.data_vistoria || item.data_vistoria > hoje)],
  ].filter(([, itens]) => itens.length)
  return (
    <AppShell title="Amazon Campo" online={online} footer={false} header={false}>
      <section className="brand-header">
        <button className="brand-menu" type="button" onClick={() => onNavigate('settings')} aria-label="Abrir menu"><Menu size={24} /></button>
        <img className="brand-logo" src="/campo/brand-horizontal.svg" alt="Amazon Certificadora" />
        <button className="profile-initial" type="button" onClick={() => onNavigate('settings')} aria-label="Abrir perfil e ajustes">{session?.usuario?.nome?.slice(0, 1)?.toUpperCase() || 'A'}</button>
      </section>
      <section className="agenda-heading">
        <h1>Minhas vistorias</h1>
        <p><CalendarDays size={17} /> {formatarData(new Date().toISOString().slice(0, 10))}</p>
      </section>
      {grupos.map(([titulo, itens]) => <section className="agenda-group" key={titulo}><div className="section-title"><strong>{titulo}</strong><span>{itens.length} atribuída{itens.length === 1 ? '' : 's'}</span></div><div className="agenda-list">{itens.map(item => <AgendaCard key={item.id} item={item} onOpen={onOpen} onVesselPhoto={onVesselPhoto} />)}</div></section>)}
      {agenda.length === 0 ? <section className="agenda-list"><div className="empty-state"><CalendarDays size={34} /><strong>Nenhuma vistoria pendente</strong><span>Quando houver uma nova atribuição, ela aparecerá aqui.</span></div></section> : null}
      <div className="mode-banner"><CloudDownload size={22} /><span><strong>{online ? 'Pronto para trabalhar offline' : 'Modo offline ativo'}</strong><small>As vistorias ficam salvas neste aparelho e são enviadas somente ao finalizar.</small></span><button className="install-button" onClick={instalar}>Instalar no Android</button>{installHelp ? <small className="install-help" role="status">No Chrome, toque no menu ⋮ e escolha <strong>Adicionar à tela inicial</strong>.</small> : null}</div>
      <BottomNav active="agenda" onNavigate={onNavigate} />
    </AppShell>
  )
}
