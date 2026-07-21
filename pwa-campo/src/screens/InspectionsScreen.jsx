import { AlertTriangle, CheckCircle2, ChevronRight, ClipboardList, CloudDownload } from 'lucide-react'
import { AppShell } from '../components/AppShell'
import { BottomNav } from '../components/BottomNav'

function progressoDoPacote(pacote) {
  const itens = (pacote.categorias || []).flatMap(categoria => categoria.itens || [])
  const respostas = pacote.respostas_locais || {}
  const respondidos = itens.filter(item => respostas[item.id]?.status || item.resposta?.status).length
  return { total: itens.length, respondidos, percentual: itens.length ? Math.round((respondidos / itens.length) * 100) : 0 }
}

export function InspectionsScreen({ pacotes, agenda, online, error, onOpen, onNavigate }) {
  const validos = pacotes.filter(item => item?.agendamento?.id)
  const idsAtivos = new Set((agenda || []).map(item => item.id))
  return <AppShell title="Vistorias baixadas" online={online} footer={false}>
    <section className="tab-heading"><span className="tab-heading-icon"><ClipboardList /></span><span><h1>Vistorias neste aparelho</h1><p>Continue o trabalho mesmo sem internet.</p></span></section>
    {error ? <div className="form-error" role="alert"><strong>Não foi possível abrir a vistoria</strong><span>{error}</span></div> : null}
    <section className="operational-list">
      {validos.map(pacote => {
        const progresso = progressoDoPacote(pacote)
        const bloqueada = pacote.vistoria?.status && pacote.vistoria.status !== 'PENDENTE'
        const indisponivel = online && !bloqueada && !idsAtivos.has(pacote.agendamento.id)
        return <article className="operational-card" key={pacote.agendamento.id}>
          <div className="operational-card-title"><span className="vessel-thumb" aria-hidden="true"><img src="/assets/img/portal-hero-ship.png" alt="" /></span><span><strong>{pacote.agendamento.embarcacao_nome || pacote.agendamento.embarcacao}</strong><small>{pacote.agendamento.embarcacao_registro || pacote.agendamento.registro || 'Sem registro informado'}</small></span></div>
          <div className="download-state"><CloudDownload size={16} /> Pacote disponível offline</div>
          <div className="progress-track"><i style={{ width: `${progresso.percentual}%` }} /></div>
          <div className="card-stats"><span>{progresso.respondidos} de {progresso.total} itens</span><strong>{progresso.percentual}%</strong></div>
          {bloqueada ? <div className="locked-state"><CheckCircle2 size={17} /> Enviada para aprovação</div> : indisponivel ? <div className="locked-state"><AlertTriangle size={17} /> Agendamento alterado no ERP</div> : <button className="primary-button" onClick={() => onOpen({ id: pacote.agendamento.id })}>Continuar vistoria <ChevronRight size={18} /></button>}
        </article>
      })}
      {!validos.length ? <div className="empty-state"><CloudDownload size={34} /><strong>Nenhuma vistoria baixada</strong><span>Abra uma vistoria pela Agenda para deixá-la disponível offline.</span><button className="secondary-button" onClick={() => onNavigate('agenda')}>Ir para a agenda</button></div> : null}
    </section>
    <BottomNav active="inspections" onNavigate={onNavigate} />
  </AppShell>
}
