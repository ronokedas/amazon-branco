import { CalendarDays, Check, CheckCircle2, ChevronDown, ChevronRight, CircleMinus, CircleX, ClipboardPlus, MessageSquareText, Paperclip, Phone, Plus, Search, Trash2, UserRound, X } from 'lucide-react'
import { useDeferredValue, useMemo, useState } from 'react'
import { AppShell } from '../components/AppShell'

function StatusButton({ kind, active, onClick, children }) {
  const Icon = kind === 'conforme' ? CheckCircle2 : kind === 'nao-conforme' ? CircleX : CircleMinus
  return <button className={`status-button ${kind} ${active ? 'selected' : ''}`} onClick={onClick}><Icon size={21} /><span>{children}</span></button>
}

function ChecklistItem({ item, resposta, onChange, onEvidence }) {
  const anexos = item.anexos || []
  return (
    <article className="check-item">
      <div className="item-heading"><strong>{item.descricao}</strong>{item.item_normam ? <small>{item.item_normam}</small> : null}</div>
      <div className="status-grid">
        <StatusButton kind="conforme" active={resposta?.status === 'CONFORME'} onClick={() => { if (onChange(item, 'CONFORME') !== false) onEvidence(item) }}>Conforme</StatusButton>
        <StatusButton kind="nao-conforme" active={resposta?.status === 'NAO_CONFORME'} onClick={() => { if (onChange(item, 'NAO_CONFORME') !== false) onEvidence(item) }}>Não conforme</StatusButton>
        <StatusButton kind="na" active={resposta?.status === 'NAO_SE_APLICA'} onClick={() => onChange(item, 'NAO_SE_APLICA')}>Não se aplica</StatusButton>
      </div>
      {['CONFORME', 'NAO_CONFORME'].includes(resposta?.status) ? (
        <button className="evidence-summary" onClick={() => onEvidence(item)}>
          <span><Paperclip size={17} /> {anexos.length ? `${anexos.length} foto${anexos.length > 1 ? 's' : ''} adicionada${anexos.length > 1 ? 's' : ''}` : 'Adicionar evidências'}</span>
          <ChevronRight size={18} />
        </button>
      ) : null}
    </article>
  )
}

function InspectionDetails({ detalhes, cumprimento, aberta, onToggle, onChange, onAddRequirement, onRequirementChange, onRemoveRequirement }) {
  return (
    <section className={`field-details-card ${aberta ? 'open' : ''}`}>
      <button className="field-details-toggle" onClick={onToggle} aria-expanded={aberta}>
        <span><ClipboardPlus size={19} /><span><strong>Dados da vistoria</strong><small>Data, responsável, exigências e observações</small></span></span>
        {aberta ? <ChevronDown size={19} /> : <ChevronRight size={19} />}
      </button>
      {aberta ? <div className="field-details-body">
        <label className="field-control">
          <span><CalendarDays size={16} /> Data da realização da vistoria *</span>
          <input type="date" value={detalhes.data_vistoria} onChange={event => onChange('data_vistoria', event.target.value)} required />
        </label>
        <label className="field-control">
          <span><UserRound size={16} /> Responsável presente</span>
          <input type="text" value={detalhes.operador_nome} onChange={event => onChange('operador_nome', event.target.value)} maxLength={255} placeholder="Nome de quem acompanhará a vistoria" />
        </label>
        <label className="field-control">
          <span><MessageSquareText size={16} /> Observações técnicas</span>
          <textarea value={detalhes.observacoes_tecnicas} onChange={event => onChange('observacoes_tecnicas', event.target.value)} maxLength={10000} rows={4} placeholder="Recomendações, restrições e observações gerais…" />
          <small>{detalhes.observacoes_tecnicas.length}/10.000</small>
        </label>

        <div className="standalone-requirements">
          <div className="standalone-heading"><span><strong>{cumprimento ? 'Exigências a verificar' : 'Exigências avulsas'}</strong><small>{cumprimento ? 'Somente pendências do relatório anterior' : 'Itens encontrados fora do checklist'}</small></span>{!cumprimento ? <button onClick={onAddRequirement}><Plus size={17} /> Adicionar</button> : null}</div>
          {detalhes.exigencias_avulsas.length === 0 ? <div className="standalone-empty">Nenhuma exigência avulsa adicionada.</div> : null}
          {detalhes.exigencias_avulsas.map((item, index) => (
            <article className="standalone-card" key={item.id || item.id_local || index}>
              <header><strong>Exigência {index + 1}{item.antes_de_suspender ? ' · A/S — Antes de suspender' : ''}</strong>{!cumprimento ? <button onClick={() => onRemoveRequirement(index)} aria-label={`Remover exigência ${index + 1}`}><Trash2 size={17} /></button> : null}</header>
              <div className="field-grid">
                <label className="field-control"><span>Tipo de vistoria</span><select value={item.bloco_vistoria || 'flutuando'} onChange={event => onRequirementChange(index, 'bloco_vistoria', event.target.value)}><option value="seco">Seco</option><option value="flutuando">Flutuando</option><option value="borda_livre">Borda livre</option><option value="arqueacao">Arqueação</option></select></label>
                <label className="field-control"><span>Situação</span><select value={item.status_item || 'pendente'} onChange={event => onRequirementChange(index, 'status_item', event.target.value)}>{!cumprimento ? <option value="inserida">Inserida / N/A</option> : null}<option value="pendente">Pendente</option><option value="cumprida">Cumprida</option>{cumprimento ? <><option value="cumprida_parcial_reescrita">Parcialmente cumprida / reescrita</option><option value="nao_cumprida_transcrita">Não cumprida / transcrita</option></> : null}</select></label>
              </div>
              <label className="field-control"><span>Descrição da exigência *</span><textarea value={item.descricao || ''} onChange={event => onRequirementChange(index, 'descricao', event.target.value)} rows={3} placeholder="Descreva o item encontrado…" /></label>
              <label className="field-control"><span>Item da NORMAM</span><input type="text" value={item.item_normam || ''} onChange={event => onRequirementChange(index, 'item_normam', event.target.value)} maxLength={200} placeholder="Ex.: NORMAM-202/DPC, item 3.2" /></label>
              <label className="field-control"><span>Observação / justificativa</span><input type="text" value={item.observacao || ''} onChange={event => onRequirementChange(index, 'observacao', event.target.value)} placeholder="Informação complementar" /></label>
              <div className="fixed-deadline-note"><CalendarDays size={16} /><span><strong>{item.sem_prazo ? 'A/S — Antes de suspender' : 'Prazo geral para correção'}</strong>{item.sem_prazo ? 'Bloqueia a embarcação e os certificados' : detalhes.prazo_correcao ? new Date(`${detalhes.prazo_correcao}T12:00:00`).toLocaleDateString('pt-BR') : 'Defina acima'}</span></div>
              <label className="field-checkbox"><input type="checkbox" checked={Boolean(item.sem_prazo)} disabled={cumprimento} onChange={event => onRequirementChange(index, 'sem_prazo', event.target.checked)} /> {item.sem_prazo ? 'A/S — Antes de suspender' : 'Exigência com prazo'}</label>
            </article>
          ))}
        </div>
      </div> : null}
    </section>
  )
}

export function ChecklistScreen({ pacote, respostas, detalhes, online, saving, error, onBack, onChange, onEvidence, onDetailChange, onAddRequirement, onRequirementChange, onRemoveRequirement, onSummary }) {
  const categorias = pacote.categorias || []
  const cumprimento = pacote.vistoria?.finalidade === 'CUMPRIMENTO_EXIGENCIAS'
  const total = categorias.reduce((sum, cat) => sum + cat.itens.length, 0)
  const respondidos = Object.values(respostas).filter(r => r?.status).length
  const percentual = respondidos ? 100 : 0
  const primeiraPendente = categorias.find(cat => cat.itens.some(item => !respostas[item.id]?.status))?.id
  const [aberta, setAberta] = useState(primeiraPendente || categorias[0]?.id)
  const [dadosAbertos, setDadosAbertos] = useState(false)
  const [busca, setBusca] = useState('')
  const buscaAdiada = useDeferredValue(busca)
  const termo = buscaAdiada.trim().toLocaleLowerCase('pt-BR').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  const categoriasVisiveis = useMemo(() => {
    if (!termo) return categorias
    return categorias.flatMap(categoria => {
      const itens = categoria.itens.filter(item => `${item.descricao || ''} ${item.item_normam || ''} ${categoria.nome || ''}`.toLocaleLowerCase('pt-BR').normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(termo))
      return itens.length ? [{ ...categoria, itens }] : []
    })
  }, [categorias, termo])
  const totalEncontrado = categoriasVisiveis.reduce((sum, categoria) => sum + categoria.itens.length, 0)

  return (
    <AppShell title="Vistoria em campo" online={online} onBack={onBack}>
      <section className="inspection-identity">
        <span><strong>{pacote.agendamento.embarcacao_nome}</strong><small>{pacote.agendamento.embarcacao_registro || pacote.vistoria?.numero || 'Rascunho de campo'}</small></span>
        <span className={online ? 'saved-state' : 'offline-state'}>{online ? 'salvo automaticamente' : 'salvo no aparelho'}</span>
      </section>
      <section className="summary-notes">
        <strong><UserRound size={17} /> Responsável pelo fechamento da proposta</strong>
        <p>{pacote.agendamento.contato_nome || 'Não informado'}</p>
        <small><Phone size={15} /> {pacote.agendamento.contato_telefone || 'Telefone não informado'}</small>
      </section>
      <section className="progress-card">
        <div><strong>Itens selecionados</strong><span>{respondidos} exigência{respondidos === 1 ? '' : 's'} selecionada{respondidos === 1 ? '' : 's'}</span></div>
        <div className="progress-row"><span className="progress-track"><i style={{ width: `${percentual}%` }} /></span><b>Seleção livre</b></div>
      </section>
      <label className={`correction-deadline ${detalhes.prazo_correcao ? 'defined' : ''}`}>
        <span><CalendarDays size={20} /><span><strong>Prazo para correção *</strong><small>Igual ao ERP: selecione 60 ou 90 dias. O vencimento será calculado pela data da vistoria.</small></span></span>
        <select aria-label="Prazo para correção" value={detalhes.prazo_exigencias_dias || ''} onChange={event => onDetailChange('prazo_exigencias_dias', event.target.value)} required>
          <option value="">Selecione</option>
          <option value="60">60 dias</option>
          <option value="90">90 dias</option>
        </select>
        {detalhes.prazo_correcao ? <small className="correction-deadline-date">Vencimento: {new Date(`${detalhes.prazo_correcao}T12:00:00`).toLocaleDateString('pt-BR')}</small> : null}
      </label>
      <section className="checklist-search" aria-label="Pesquisar exigências">
        <Search size={19} />
        <input value={busca} onChange={event => setBusca(event.target.value)} placeholder="Pesquisar exigência ou item da NORMAM" aria-label="Pesquisar exigência" />
        {busca ? <button onClick={() => setBusca('')} aria-label="Limpar pesquisa"><X size={18} /></button> : <span>{total} itens</span>}
      </section>
      {termo ? <div className="search-result-count">{totalEncontrado} exigência{totalEncontrado === 1 ? '' : 's'} encontrada{totalEncontrado === 1 ? '' : 's'}</div> : null}
      <InspectionDetails detalhes={detalhes} cumprimento={cumprimento} aberta={dadosAbertos} onToggle={() => setDadosAbertos(current => !current)} onChange={onDetailChange} onAddRequirement={onAddRequirement} onRequirementChange={onRequirementChange} onRemoveRequirement={onRemoveRequirement} />
      {error ? <div className="form-error checklist-error" role="alert"><strong>Verifique os dados</strong><span>{error}</span></div> : null}
      <section className="checklist-sections">
        {categoriasVisiveis.map((categoria, index) => {
          const completos = categoria.itens.filter(item => respostas[item.id]?.status).length
          const concluida = completos === categoria.itens.length && categoria.itens.length > 0
          const isOpen = Boolean(termo) || aberta === categoria.id
          return (
            <article className={`check-section ${isOpen ? 'open' : ''}`} key={categoria.id}>
              <button className="section-toggle" onClick={() => setAberta(isOpen ? null : categoria.id)}>
                <span>{index + 1}. {categoria.nome}</span>
                <span className={concluida ? 'section-done' : 'section-pending'}>{concluida ? <Check size={17} /> : `${completos}/${categoria.itens.length}`}</span>
                {isOpen ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
              </button>
              {isOpen ? <div className="section-body">{categoria.itens.map(item => <ChecklistItem key={item.id} item={item} resposta={respostas[item.id]} onChange={onChange} onEvidence={onEvidence} />)}</div> : null}
            </article>
          )
        })}
        {termo && totalEncontrado === 0 ? <div className="checklist-search-empty"><Search size={25} /><strong>Nenhuma exigência encontrada</strong><span>Tente outro termo ou número da NORMAM.</span></div> : null}
      </section>
      <div className="sticky-actions single-action"><button className="primary-button" onClick={onSummary} disabled={saving}>{saving ? 'Preparando…' : 'Revisar e enviar'}</button></div>
    </AppShell>
  )
}
