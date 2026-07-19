import { AlertCircle, Camera, CheckCircle2, FileText, RefreshCw, Ship } from 'lucide-react'
import { AppShell } from '../components/AppShell'
import { BottomNav } from '../components/BottomNav'

const statusLabel = { PENDENTE: 'Rascunho', AGUARDANDO_APROVACAO: 'Aguardando aprovação', APROVADA: 'Aprovada', APROVADO: 'Aprovada', REPROVADA: 'Devolvida para correção' }
function dataBr(data) {
  if (!data) return 'Data não informada'
  return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(`${data.slice(0, 10)}T12:00:00Z`))
}

export function ReportsScreen({ reports, online, pending, syncing, loading, onSync, onReload, onNavigate }) {
  return <AppShell title="Relatórios" online={online} pending={pending} syncing={syncing} onSync={onSync} footer={false}>
    <section className="tab-heading"><span className="tab-heading-icon"><FileText /></span><span><h1>Relatórios de campo</h1><p>Acompanhe rascunhos, envios e aprovações.</p></span></section>
    <div className="list-toolbar"><span>{reports.length} relatório{reports.length === 1 ? '' : 's'}</span><button onClick={onReload} disabled={!online || loading}><RefreshCw size={17} className={loading ? 'spin' : ''} /> Atualizar</button></div>
    <section className="operational-list reports-list">
      {reports.map(report => <article className="operational-card" key={report.vistoria_id}>
        <div className="operational-card-title"><span className="vessel-icon small"><Ship /></span><span><strong>{report.embarcacao}</strong><small>{report.numero || 'Relatório em preparação'} · {dataBr(report.data_vistoria)}</small></span></div>
        <div className={`report-status ${report.status === 'PENDENTE' ? 'draft' : ''}`}><CheckCircle2 size={16} /> {statusLabel[report.status] || report.status}</div>
        <div className="report-counts"><span><CheckCircle2 size={16} /> {Number(report.respondidos || 0)} respostas</span><span className={Number(report.nao_conformes) ? 'has-issue' : ''}><AlertCircle size={16} /> {Number(report.nao_conformes || 0)} não conformidades</span><span><Camera size={16} /> {Number(report.fotos || 0)} fotos</span></div>
        {online ? <a className="secondary-button" href={report.relatorio_url} target="_blank" rel="noreferrer">Abrir relatório no ERP <FileText size={17} /></a> : <p className="offline-message">Conecte-se à internet para abrir o relatório administrativo.</p>}
      </article>)}
      {!reports.length && !loading ? <div className="empty-state"><FileText size={34} /><strong>Nenhum relatório criado</strong><span>Quando um rascunho for salvo, ele aparecerá aqui.</span><button className="secondary-button" onClick={() => onNavigate('agenda')}>Iniciar uma vistoria</button></div> : null}
    </section>
    <BottomNav active="reports" onNavigate={onNavigate} />
  </AppShell>
}
