import { CalendarDays, CheckCircle2, CircleMinus, CircleX, ClipboardPlus, FileText, Image, MessageSquareText, Phone, Send, UserRound } from 'lucide-react'
import { AppShell } from '../components/AppShell'

export function SummaryScreen({ pacote, respostas, detalhes, online, pending, syncing, onSync, onBack, onSubmit, submitting, error }) {
  const itens = pacote.categorias.flatMap(cat => cat.itens)
  const values = Object.values(respostas)
  const counts = {
    CONFORME: values.filter(r => r.status === 'CONFORME').length,
    NAO_CONFORME: values.filter(r => r.status === 'NAO_CONFORME').length,
    NAO_SE_APLICA: values.filter(r => r.status === 'NAO_SE_APLICA').length,
  }
  const fotos = itens.reduce((sum, item) => sum + (item.anexos?.length || 0), 0)
  const selecionados = values.filter(r => r.status).length
  const enviada = pacote.vistoria?.status === 'AGUARDANDO_APROVACAO'
  const pdfUrl = pacote.vistoria?.id ? `/vistorias/relatorio_pdf.php?id=${encodeURIComponent(pacote.vistoria.id)}` : ''
  return (
    <AppShell title="Resumo da vistoria" online={online} pending={pending} syncing={syncing} onSync={onSync} onBack={onBack}>
      <section className="inspection-identity"><span><strong>{pacote.agendamento.embarcacao_nome}</strong><small>{pacote.agendamento.embarcacao_registro || pacote.vistoria?.numero || 'Rascunho de campo'}</small></span><span className="saved-state">{enviada ? 'Enviada para aprovação' : 'Seleção concluída'}</span></section>
      <section className="progress-card"><div><strong>Itens selecionados</strong><span>{selecionados} exigência{selecionados === 1 ? '' : 's'} selecionada{selecionados === 1 ? '' : 's'}</span></div><div className="selection-note">Somente os itens escolhidos pelo vistoriador fazem parte desta vistoria.</div></section>
      <section className="summary-counts">
        <div className="green"><CheckCircle2 /><strong>{counts.CONFORME}</strong><span>Conformes</span></div>
        <div className="red"><CircleX /><strong>{counts.NAO_CONFORME}</strong><span>Não conformes</span></div>
        <div className="gray"><CircleMinus /><strong>{counts.NAO_SE_APLICA}</strong><span>Não se aplicam</span></div>
      </section>
      <dl className="summary-details">
        <div><dt><CalendarDays size={18} /> Data realizada</dt><dd>{detalhes.data_vistoria ? new Date(`${detalhes.data_vistoria}T12:00:00`).toLocaleDateString('pt-BR') : 'Não informada'}</dd></div>
        <div><dt><CalendarDays size={18} /> Prazo para correção</dt><dd>{detalhes.prazo_correcao ? `${detalhes.prazo_exigencias_dias} dias · vence em ${new Date(`${detalhes.prazo_correcao}T12:00:00`).toLocaleDateString('pt-BR')}` : 'Não definido'}</dd></div>
        <div><dt><UserRound size={18} /> Responsável presente</dt><dd>{detalhes.operador_nome || 'Não informado'}</dd></div>
        <div><dt><UserRound size={18} /> Responsável pelo fechamento</dt><dd>{pacote.agendamento.contato_nome || 'Não informado'}</dd></div>
        <div><dt><Phone size={18} /> Telefone do responsável</dt><dd>{pacote.agendamento.contato_telefone || 'Não informado'}</dd></div>
        <div><dt><ClipboardPlus size={18} /> Exigências avulsas</dt><dd>{detalhes.exigencias_avulsas.length}</dd></div>
        <div><dt><Image size={18} /> Fotos anexadas</dt><dd>{fotos} foto{fotos === 1 ? '' : 's'}</dd></div>
        <div><dt>Vistoriador</dt><dd>{pacote.agendamento.vistoriador_nome || 'Responsável atual'}</dd></div>
        <div><dt>Cliente</dt><dd>{pacote.agendamento.cliente_nome}</dd></div>
        <div><dt>Embarcação</dt><dd>{pacote.agendamento.embarcacao_nome}</dd></div>
        <div><dt>Local</dt><dd>{pacote.agendamento.local || 'Não informado'}</dd></div>
      </dl>
      {detalhes.observacoes_tecnicas ? <section className="summary-notes"><strong><MessageSquareText size={17} /> Observações técnicas</strong><p>{detalhes.observacoes_tecnicas}</p></section> : null}
      {error ? <div className="form-error"><strong>Revise antes de enviar</strong><span>{error}</span></div> : null}
      {!pdfUrl ? <p className="pdf-help">{online ? 'O rascunho ainda está aguardando confirmação do servidor. Sincronize para liberar o PDF.' : 'O rascunho está salvo neste aparelho. Conecte-se para liberar o PDF.'}</p> : null}
      <div className="summary-actions summary-actions--sticky">
        {pdfUrl ? <a className="secondary-button" href={pdfUrl} target="_blank" rel="noreferrer"><FileText size={18} /> Ver relatório em PDF</a> : online ? <button className="secondary-button" onClick={() => onSync()} disabled={syncing}><FileText size={18} /> {syncing ? 'Sincronizando…' : 'Sincronizar para gerar PDF'}</button> : <button className="secondary-button" disabled><FileText size={18} /> PDF disponível quando conectar</button>}
        <button className="primary-button" onClick={onSubmit} disabled={submitting || enviada}>{enviada ? <CheckCircle2 size={18} /> : <Send size={18} />} {submitting ? 'Enviando…' : enviada ? 'Enviada para aprovação' : 'Enviar para aprovação'}</button>
      </div>
    </AppShell>
  )
}
