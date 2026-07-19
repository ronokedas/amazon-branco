import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { AlertTriangle, LoaderCircle } from 'lucide-react'
import { api, setCsrfToken } from './api'
import { contarFila, enfileirar, limparDadosLocais, listarPacotes, obterMeta, obterPacote, removerDaFila, salvarMeta, salvarPacote } from './db'
import { prepararImagemOriginal } from './media'
import { processarFila } from './sync'
import { AgendaScreen } from './screens/AgendaScreen'
import { ChecklistScreen } from './screens/ChecklistScreen'
import { EvidenceScreen } from './screens/EvidenceScreen'
import { SummaryScreen } from './screens/SummaryScreen'
import { InspectionsScreen } from './screens/InspectionsScreen'
import { ReportsScreen } from './screens/ReportsScreen'
import { SettingsScreen } from './screens/SettingsScreen'
import { LoginScreen } from './screens/LoginScreen'

function respostasDoPacote(pacote) {
  const respostas = {}
  for (const categoria of pacote?.categorias || []) {
    for (const item of categoria.itens) {
      if (item.resposta) respostas[item.id] = { ...item.resposta }
    }
  }
  return { ...respostas, ...(pacote?.respostas_locais || {}) }
}

function adicionarDias(data, dias) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(data || '') || ![60, 90].includes(Number(dias))) return ''
  const [ano, mes, dia] = data.split('-').map(Number)
  const resultado = new Date(Date.UTC(ano, mes - 1, dia))
  resultado.setUTCDate(resultado.getUTCDate() + Number(dias))
  return resultado.toISOString().slice(0, 10)
}

function inferirPrazoDias(dataBase, vencimento) {
  if (!dataBase || !vencimento) return ''
  const inicio = Date.parse(`${dataBase}T00:00:00Z`)
  const fim = Date.parse(`${vencimento}T00:00:00Z`)
  const dias = Math.round((fim - inicio) / 86400000)
  return [60, 90].includes(dias) ? String(dias) : ''
}

function detalhesDoPacote(pacote) {
  const locais = pacote?.dados_vistoria_locais || {}
  const vistoria = pacote?.vistoria || {}
  const agendamento = pacote?.agendamento || {}
  const prazoResposta = (pacote?.categorias || [])
    .flatMap(categoria => categoria.itens || [])
    .find(item => item.resposta?.status === 'NAO_CONFORME' && item.resposta?.vencimento)?.resposta?.vencimento
  const prazoAvulsa = (pacote?.exigencias_avulsas || []).find(item => item.vencimento)?.vencimento
  const dataVistoria = locais.data_vistoria || vistoria.data_vistoria || agendamento.data_vistoria || new Date().toISOString().slice(0, 10)
  const prazoExistente = locais.prazo_correcao || prazoResposta || prazoAvulsa || ''
  const prazoDias = String(locais.prazo_exigencias_dias || vistoria.prazo_exigencias_dias || inferirPrazoDias(dataVistoria, prazoExistente) || '')
  return {
    data_vistoria: dataVistoria,
    operador_nome: locais.operador_nome ?? vistoria.operador_nome ?? agendamento.operador_nome ?? '',
    observacoes_tecnicas: locais.observacoes_tecnicas ?? vistoria.observacoes_tecnicas ?? '',
    prazo_exigencias_dias: prazoDias,
    prazo_correcao: adicionarDias(dataVistoria, Number(prazoDias)) || prazoExistente,
    exigencias_avulsas: pacote?.exigencias_avulsas_locais || pacote?.exigencias_avulsas || [],
  }
}

function assinaturaDados(respostas, detalhes) {
  return JSON.stringify({ respostas, detalhes })
}

function useOnlineStatus() {
  const [online, setOnline] = useState(navigator.onLine)
  useEffect(() => {
    const update = () => setOnline(navigator.onLine)
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
    return () => {
      window.removeEventListener('online', update)
      window.removeEventListener('offline', update)
    }
  }, [])
  return online
}

export default function App() {
  const online = useOnlineStatus()
  const [session, setSession] = useState(null)
  const [agenda, setAgenda] = useState([])
  const [pacotes, setPacotes] = useState([])
  const [reports, setReports] = useState([])
  const [pacote, setPacote] = useState(null)
  const [respostas, setRespostas] = useState({})
  const [detalhes, setDetalhes] = useState(() => detalhesDoPacote(null))
  const [screen, setScreen] = useState('agenda')
  const [itemEvidencia, setItemEvidencia] = useState(null)
  const [loading, setLoading] = useState(true)
  const [fatalError, setFatalError] = useState('')
  const [authRequired, setAuthRequired] = useState(false)
  const [loginError, setLoginError] = useState('')
  const [formError, setFormError] = useState('')
  const [pending, setPending] = useState(0)
  const [syncing, setSyncing] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [saving, setSaving] = useState(false)
  const [installPrompt, setInstallPrompt] = useState(null)
  const [tabLoading, setTabLoading] = useState(false)
  const autoSyncAttempt = useRef('')
  const autosaveSignature = useRef('')

  useEffect(() => {
    const capture = event => {
      event.preventDefault()
      setInstallPrompt(event)
    }
    window.addEventListener('beforeinstallprompt', capture)
    return () => window.removeEventListener('beforeinstallprompt', capture)
  }, [])

  const instalarAplicativo = useCallback(async () => {
    if (!installPrompt) return false
    await installPrompt.prompt()
    const escolha = await installPrompt.userChoice
    if (escolha.outcome === 'accepted') setInstallPrompt(null)
    return escolha.outcome === 'accepted'
  }, [installPrompt])

  const usuarioId = session?.usuario?.id || null
  const refreshPending = useCallback(async () => setPending(await contarFila(usuarioId)), [usuarioId])

  const carregarAgenda = useCallback(async () => {
    const dados = await api('agenda')
    const vistorias = dados.vistorias || []
    setAgenda(vistorias)
    await salvarMeta('agenda', vistorias)
  }, [])

  const carregarPacotes = useCallback(async () => setPacotes(await listarPacotes(usuarioId)), [usuarioId])

  const autenticar = useCallback(async credenciais => {
    setLoading(true)
    setLoginError('')
    try {
      const sessao = await api('login', { method: 'POST', body: JSON.stringify(credenciais) })
      setSession(sessao)
      setCsrfToken(sessao.csrf_token)
      await salvarMeta('sessao', sessao)
      setPending(await contarFila(sessao.usuario.id))
      setPacotes(await listarPacotes(sessao.usuario.id))
      setAuthRequired(false)
      await carregarAgenda()
    } catch (error) {
      setLoginError(error.message)
    } finally {
      setLoading(false)
    }
  }, [carregarAgenda])

  const carregarRelatorios = useCallback(async () => {
    setTabLoading(true)
    try {
      const dados = await api('relatorios')
      const itens = dados.relatorios || []
      setReports(itens)
      await salvarMeta('relatorios', itens)
    } catch (error) {
      const locais = await obterMeta('relatorios')
      if (locais) setReports(locais)
      else if (navigator.onLine) setFormError(error.message)
    } finally {
      setTabLoading(false)
    }
  }, [])

  const navegar = useCallback(async destino => {
    setFormError('')
    setScreen(destino)
    if (destino === 'inspections') await carregarPacotes()
    if (destino === 'reports') await carregarRelatorios()
  }, [carregarPacotes, carregarRelatorios])

  useEffect(() => {
    let active = true
    api('sessao')
      .then(async sessao => {
        if (!active) return
        setSession(sessao)
        setCsrfToken(sessao.csrf_token)
        const count = await contarFila(sessao.usuario.id)
        setPending(count)
        setPacotes(await listarPacotes(sessao.usuario.id))
        await salvarMeta('sessao', sessao)
        await carregarAgenda()
      })
      .catch(async error => {
        if (!active) return
        if (!navigator.onLine) {
          const [sessaoLocal, agendaLocal] = await Promise.all([obterMeta('sessao'), obterMeta('agenda')])
          if (sessaoLocal && (!sessaoLocal.expira_em || new Date(sessaoLocal.expira_em).getTime() > Date.now())) {
            const count = await contarFila(sessaoLocal.usuario.id)
            setSession(sessaoLocal)
            setCsrfToken(sessaoLocal.csrf_token)
            setAgenda(agendaLocal || [])
            setPending(count)
            return
          }
        }
        if (error.status === 401 || error.status === 403) setAuthRequired(true)
        else setFatalError(error.message)
      })
      .finally(() => active && setLoading(false))
    return () => { active = false }
  }, [carregarAgenda])

  const atualizarPacote = useCallback(async updater => {
    setPacote(current => {
      const next = typeof updater === 'function' ? updater(current) : updater
      salvarPacote(next).catch(() => {})
      return next
    })
  }, [])

  const atualizarAgenda = useCallback(updater => {
    setAgenda(current => {
      const next = typeof updater === 'function' ? updater(current) : updater
      salvarMeta('agenda', next).catch(() => {})
      return next
    })
  }, [])

  const sincronizar = useCallback(async (options = {}) => {
    const propagarErro = options?.propagarErro === true
    if (!navigator.onLine) {
      const error = new Error('Sem conexão. O rascunho continua salvo neste aparelho.')
      if (propagarErro) throw error
      return { ok: false, error }
    }
    if (syncing) {
      const error = new Error('A sincronização já está em andamento. Aguarde alguns segundos.')
      if (propagarErro) throw error
      return { ok: false, error }
    }
    setSyncing(true)
    setFormError('')
    try {
      const aplicarProgresso = ({ operacao, dados }) => {
        if (operacao.tipo === 'rascunho' && dados?.versao) {
          atualizarPacote(current => current ? ({ ...current, vistoria: { ...(current.vistoria || {}), id: dados.vistoria_id, mobile_versao: dados.versao } }) : current)
        }
        if (operacao.tipo === 'finalizacao') {
          atualizarPacote(current => current ? ({ ...current, vistoria: { ...(current.vistoria || {}), status: dados.status } }) : current)
        }
        if (operacao.tipo === 'anexo' && dados?.id) {
          atualizarPacote(current => current ? ({
            ...current,
            categorias: current.categorias.map(categoria => ({
              ...categoria,
              itens: categoria.itens.map(item => ({
                ...item,
                anexos: (item.anexos || []).map(anexo => anexo.id === operacao.operacao_id
                  ? { ...dados, id: dados.id, local: false, status_upload: 'sincronizada' }
                  : anexo),
              })),
            })),
          }) : current)
        }
        if (operacao.tipo === 'foto_embarcacao' && dados?.foto_url) {
          atualizarAgenda(current => current.map(item => item.embarcacao_id === dados.embarcacao_id
            ? { ...item, foto_url: dados.foto_url, foto_local_blob: null, foto_status: 'sincronizada' }
            : item))
        }
      }
      let resultado
      let csrfRenovado = false
      let conflitoCorrigido = false
      for (let tentativa = 0; tentativa < 3; tentativa += 1) {
        try {
          resultado = await processarFila(aplicarProgresso, usuarioId)
          break
        } catch (error) {
          if (error.status === 419 && !csrfRenovado) {
            const sessaoAtual = await api('sessao')
            setCsrfToken(sessaoAtual.csrf_token)
            await salvarMeta('sessao', sessaoAtual)
            csrfRenovado = true
            continue
          }
          if (error.code === 'CONFLITO_VERSAO' && error.operacao?.tipo === 'rascunho' && !conflitoCorrigido) {
            const servidor = await api(`vistorias/${error.operacao.agendamento_id}/sync`)
            const operacaoCorrigida = {
              ...error.operacao,
              payload: { ...error.operacao.payload, versao: Number(servidor.versao || 0) },
            }
            await enfileirar(operacaoCorrigida, true)
            conflitoCorrigido = true
            continue
          }
          throw error
        }
      }
      if (!resultado) throw new Error('Não foi possível concluir a sincronização. Tente novamente.')
      await refreshPending()
      await carregarAgenda()
      await carregarPacotes()
      const finalizou = resultado.resultados?.some(item => item?.operacao?.tipo === 'finalizacao')
      if (screen === 'reports' || finalizou) await carregarRelatorios()
      if (finalizou) setScreen('reports')
      return { ok: true, ...resultado }
    } catch (error) {
      const detail = Array.isArray(error.details) ? error.details.join(' ') : Object.values(error.details || {}).join(' ')
      setFormError(`${error.message}${detail ? ` ${detail}` : ''}`)
      await refreshPending()
      if (propagarErro) throw error
      return { ok: false, error }
    } finally {
      setSyncing(false)
    }
  }, [atualizarAgenda, atualizarPacote, carregarAgenda, carregarPacotes, carregarRelatorios, refreshPending, screen, syncing, usuarioId])

  useEffect(() => {
    const key = `${online ? 'online' : 'offline'}:${pending}`
    if (!pending) {
      autoSyncAttempt.current = ''
      return
    }
    if (online && autoSyncAttempt.current !== key) {
      autoSyncAttempt.current = key
      sincronizar()
    }
  }, [online, pending])

  const abrirVistoria = useCallback(async item => {
    if (Number(item.tarefa_cumprimento) === 1 && item.relatorio_url) {
      window.location.assign(item.relatorio_url)
      return
    }
    setLoading(true)
    setFormError('')
    try {
      let dados
      try {
        dados = await api(`vistorias/${item.id}/pacote`)
      } catch (error) {
        dados = await obterPacote(item.id)
        if (!dados) throw error
      }
      const normalizado = { ...dados, agendamento_id: item.id }
      const respostasIniciais = respostasDoPacote(normalizado)
      const detalhesIniciais = detalhesDoPacote(normalizado)
      setPacote(normalizado)
      setRespostas(respostasIniciais)
      setDetalhes(detalhesIniciais)
      autosaveSignature.current = assinaturaDados(respostasIniciais, detalhesIniciais)
      await salvarPacote(normalizado)
      await carregarPacotes()
      setScreen('checklist')
    } catch (error) {
      setFormError(error.message)
    } finally {
      setLoading(false)
    }
  }, [carregarPacotes])

  const itensPorId = useMemo(() => {
    const map = new Map()
    for (const categoria of pacote?.categorias || []) for (const item of categoria.itens) map.set(item.id, item)
    return map
  }, [pacote])

  const mudarStatus = useCallback((item, status) => {
    if (respostas[item.id]?.status === status) {
      setRespostas(current => {
        const next = { ...current }
        delete next[item.id]
        return next
      })
      setFormError('')
      return false
    }
    if (status === 'NAO_CONFORME' && !detalhes.prazo_correcao) {
      setFormError('Defina primeiro o prazo para correção. Essa data será usada em todas as exigências.')
      return false
    }
    setFormError('')
    setRespostas(current => ({
      ...current,
      [item.id]: {
        catalogo_id: item.id,
        status,
        observacao: current[item.id]?.observacao || '',
        vencimento: status === 'NAO_CONFORME' ? detalhes.prazo_correcao : (current[item.id]?.vencimento || ''),
        sem_prazo: false,
        item_normam: current[item.id]?.item_normam || item.item_normam || '',
      },
    }))
    return true
  }, [detalhes.prazo_correcao, respostas])

  const abrirEvidencia = useCallback(item => {
    setItemEvidencia(item.id)
    setScreen('evidence')
  }, [])

  const atualizarEvidencia = useCallback(changes => {
    setRespostas(current => ({ ...current, [itemEvidencia]: { ...current[itemEvidencia], ...changes } }))
  }, [itemEvidencia])

  const atualizarDetalhe = useCallback((campo, valor) => {
    setDetalhes(current => {
      const dataVistoria = campo === 'data_vistoria' ? valor : current.data_vistoria
      const prazoDias = campo === 'prazo_exigencias_dias' ? String(valor) : current.prazo_exigencias_dias
      const prazoCorrecao = adicionarDias(dataVistoria, Number(prazoDias))
      return {
        ...current,
        [campo]: valor,
        prazo_exigencias_dias: prazoDias,
        prazo_correcao: prazoCorrecao,
        exigencias_avulsas: (campo === 'prazo_exigencias_dias' || campo === 'data_vistoria')
          ? current.exigencias_avulsas.map(item => item.sem_prazo
            ? ({ ...item, vencimento: '' })
            : ({ ...item, vencimento: prazoCorrecao }))
          : current.exigencias_avulsas,
      }
    })
    if (campo === 'prazo_exigencias_dias' || campo === 'data_vistoria') {
      setFormError('')
      setRespostas(current => Object.fromEntries(Object.entries(current).map(([id, resposta]) => [
        id,
        resposta?.status === 'NAO_CONFORME'
          ? {
              ...resposta,
              vencimento: resposta.sem_prazo ? '' : adicionarDias(
                campo === 'data_vistoria' ? valor : detalhes.data_vistoria,
                Number(campo === 'prazo_exigencias_dias' ? valor : detalhes.prazo_exigencias_dias),
              ),
            }
          : resposta,
      ])))
    }
  }, [detalhes.data_vistoria, detalhes.prazo_exigencias_dias])

  const adicionarExigenciaAvulsa = useCallback(() => {
    if (!detalhes.prazo_correcao) {
      setFormError('Defina primeiro o prazo para correção antes de adicionar uma exigência avulsa.')
      return
    }
    setDetalhes(current => ({
      ...current,
      exigencias_avulsas: [...current.exigencias_avulsas, {
        id_local: crypto.randomUUID(), bloco_vistoria: 'flutuando', descricao: '', item_normam: '',
        status_item: 'inserida', observacao: '', vencimento: current.prazo_correcao, sem_prazo: false,
      }],
    }))
  }, [detalhes.prazo_correcao])

  const atualizarExigenciaAvulsa = useCallback((indice, campo, valor) => {
    setDetalhes(current => ({
      ...current,
      exigencias_avulsas: current.exigencias_avulsas.map((item, itemIndice) => {
        if (itemIndice !== indice) return item
        if (campo === 'sem_prazo') return { ...item, sem_prazo: valor, vencimento: valor ? '' : current.prazo_correcao }
        return { ...item, [campo]: valor }
      }),
    }))
  }, [])

  const removerExigenciaAvulsa = useCallback(indice => {
    setDetalhes(current => ({ ...current, exigencias_avulsas: current.exigencias_avulsas.filter((_, itemIndice) => itemIndice !== indice) }))
  }, [])

  const payloadRascunho = useCallback(() => ({
    operacao_id: crypto.randomUUID(),
    versao: Number(pacote?.vistoria?.mobile_versao || 0),
    respostas: Object.values(respostas).filter(item => item.status),
    dados_vistoria: {
      data_vistoria: detalhes.data_vistoria,
      prazo_exigencias_dias: Number(detalhes.prazo_exigencias_dias || 0),
      operador_nome: detalhes.operador_nome,
      observacoes_tecnicas: detalhes.observacoes_tecnicas,
    },
    exigencias_avulsas: detalhes.exigencias_avulsas,
  }), [detalhes, pacote?.vistoria?.mobile_versao, respostas])

  const salvarRascunho = useCallback(async ({ processar = true } = {}) => {
    if (!pacote) return
    const payload = payloadRascunho()
    await enfileirar({ operacao_id: payload.operacao_id, tipo: 'rascunho', agendamento_id: pacote.agendamento.id, usuario_id: usuarioId, payload }, true)
    await atualizarPacote(current => ({
      ...current,
      categorias: current.categorias.map(categoria => ({
        ...categoria,
        itens: categoria.itens.map(item => ({ ...item, resposta: respostas[item.id] || null })),
      })),
      respostas_locais: respostas,
      dados_vistoria_locais: {
        data_vistoria: detalhes.data_vistoria,
        prazo_exigencias_dias: detalhes.prazo_exigencias_dias,
        operador_nome: detalhes.operador_nome,
        observacoes_tecnicas: detalhes.observacoes_tecnicas,
        prazo_correcao: detalhes.prazo_correcao,
      },
      exigencias_avulsas_locais: detalhes.exigencias_avulsas,
    }))
    await refreshPending()
    if (online && processar) {
      const resultado = await sincronizar({ propagarErro: true })
      const rascunhoSincronizado = [...(resultado.resultados || [])].reverse().find(item => item.operacao.tipo === 'rascunho' && item.operacao.agendamento_id === pacote.agendamento.id)
      if (rascunhoSincronizado?.dados?.vistoria_id) {
        const vistoriaAtualizada = {
          ...(pacote.vistoria || {}),
          id: rascunhoSincronizado.dados.vistoria_id,
          mobile_versao: rascunhoSincronizado.dados.versao,
        }
        await atualizarPacote(current => current ? ({ ...current, vistoria: vistoriaAtualizada }) : current)
        return vistoriaAtualizada
      }
      const estadoServidor = await api(`vistorias/${pacote.agendamento.id}/sync`)
      if (estadoServidor?.vistoria_id) {
        const vistoriaAtualizada = {
          ...(pacote.vistoria || {}),
          id: estadoServidor.vistoria_id,
          mobile_versao: estadoServidor.versao,
          status: estadoServidor.status,
        }
        await atualizarPacote(current => current ? ({ ...current, vistoria: vistoriaAtualizada }) : current)
        return vistoriaAtualizada
      }
    }
    return pacote.vistoria || null
  }, [atualizarPacote, detalhes, online, pacote, payloadRascunho, refreshPending, respostas, sincronizar, usuarioId])

  useEffect(() => {
    if (!pacote?.agendamento?.id || pacote?.vistoria?.status && pacote.vistoria.status !== 'PENDENTE') return undefined
    const timer = window.setTimeout(() => {
      atualizarPacote(current => current ? ({
        ...current,
        categorias: current.categorias.map(categoria => ({
          ...categoria,
          itens: categoria.itens.map(item => ({ ...item, resposta: respostas[item.id] || null })),
        })),
        respostas_locais: respostas,
        dados_vistoria_locais: { ...detalhes },
        exigencias_avulsas_locais: detalhes.exigencias_avulsas,
      }) : current)
    }, 200)
    return () => window.clearTimeout(timer)
  }, [atualizarPacote, detalhes, pacote?.agendamento?.id, pacote?.vistoria?.status, respostas])

  useEffect(() => {
    if (!pacote?.agendamento?.id || !['checklist', 'evidence', 'summary'].includes(screen)) return undefined
    if (pacote?.vistoria?.status && pacote.vistoria.status !== 'PENDENTE') return undefined
    const assinatura = assinaturaDados(respostas, detalhes)
    if (assinatura === autosaveSignature.current) return undefined
    autosaveSignature.current = assinatura
    const timer = window.setTimeout(() => {
      salvarRascunho({ processar: online }).catch(error => setFormError(error.message || 'O salvamento automático ficou pendente.'))
    }, 1200)
    return () => window.clearTimeout(timer)
  }, [detalhes, online, pacote?.agendamento?.id, pacote?.vistoria?.status, respostas, salvarRascunho, screen])

  const salvarManual = useCallback(async () => {
    setSaving(true)
    setFormError('')
    try { await salvarRascunho() }
    catch (error) { setFormError(error.message || 'Não foi possível salvar o rascunho.') }
    finally { setSaving(false) }
  }, [salvarRascunho])

  const revisarVistoria = useCallback(async () => {
    setSaving(true)
    setFormError('')
    try {
      const vistoriaSincronizada = await salvarRascunho()
      if (online && !vistoriaSincronizada?.id) throw new Error('O rascunho ainda não foi confirmado pelo servidor. Toque em Enviar agora e tente novamente.')
      setScreen('summary')
    } catch (error) {
      setFormError(error.message || 'Não foi possível preparar a revisão da vistoria.')
    } finally {
      setSaving(false)
    }
  }, [online, salvarRascunho])

  const adicionarFoto = useCallback(async file => {
    if (!file || !itemEvidencia || !pacote) return
    setLoading(true)
    try {
      const foto = await prepararImagemOriginal(file)
      await salvarRascunho({ processar: false })
      await enfileirar({
        operacao_id: foto.id,
        tipo: 'anexo',
        agendamento_id: pacote.agendamento.id,
        usuario_id: usuarioId,
        payload: { operacao_id: foto.id, catalogo_id: itemEvidencia, nome: foto.nome, blob: foto.blob, capturado_em: foto.capturado_em },
      })
      await atualizarPacote(current => ({
        ...current,
        categorias: current.categorias.map(cat => ({ ...cat, itens: cat.itens.map(item => item.id === itemEvidencia ? ({ ...item, anexos: [...(item.anexos || []), { ...foto, local: true }] }) : item) })),
      }))
      await refreshPending()
      if (online) await sincronizar()
    } catch (error) {
      setFormError(error.message || 'Não foi possível guardar esta foto original.')
    } finally {
      setLoading(false)
    }
  }, [atualizarPacote, itemEvidencia, online, pacote, refreshPending, salvarRascunho, sincronizar, usuarioId])

  const adicionarFotoEmbarcacao = useCallback(async (item, file) => {
    if (!item || !file) return
    setLoading(true)
    setFormError('')
    try {
      const foto = await prepararImagemOriginal(file)
      await enfileirar({
        operacao_id: foto.id,
        tipo: 'foto_embarcacao',
        agendamento_id: item.id,
        usuario_id: usuarioId,
        payload: { operacao_id: foto.id, nome: foto.nome, blob: foto.blob },
      }, true)
      atualizarAgenda(current => current.map(agendaItem => agendaItem.embarcacao_id === item.embarcacao_id
        ? { ...agendaItem, foto_local_blob: foto.blob, foto_status: online ? 'enviando' : 'pendente' }
        : agendaItem))
      await refreshPending()
      if (online) await sincronizar()
    } catch (error) {
      setFormError(error.message || 'Não foi possível guardar a foto da embarcação.')
    } finally {
      setLoading(false)
    }
  }, [atualizarAgenda, online, refreshPending, sincronizar, usuarioId])

  const removerFoto = useCallback(async anexo => {
    if (!anexo || !pacote || pacote?.vistoria?.status && pacote.vistoria.status !== 'PENDENTE') return
    try {
      if (anexo.local) await removerDaFila(anexo.id)
      else await api(`anexos/${anexo.id}`, { method: 'DELETE' })
      await atualizarPacote(current => ({
        ...current,
        categorias: current.categorias.map(categoria => ({
          ...categoria,
          itens: categoria.itens.map(item => ({ ...item, anexos: (item.anexos || []).filter(foto => foto.id !== anexo.id) })),
        })),
      }))
      await refreshPending()
    } catch (error) {
      setFormError(error.message || 'Não foi possível excluir a foto.')
    }
  }, [atualizarPacote, pacote, refreshPending])

  const enviarAprovacao = useCallback(async () => {
    if (!pacote) return
    setFormError('')
    const itens = pacote.categorias.flatMap(cat => cat.itens)
    const naoConformesInvalidos = itens.filter(item => {
      const resposta = respostas[item.id]
      if (resposta?.status !== 'NAO_CONFORME') return false
      return !resposta.sem_prazo && !resposta.vencimento
    })
    if (naoConformesInvalidos.length) { setFormError('Defina o prazo geral ou marque individualmente a exigência como A/S — Antes de suspender.'); return }
    if (!detalhes.data_vistoria) { setFormError('Informe a data de realização da vistoria.'); return }
    if (detalhes.exigencias_avulsas.some(item => !item.descricao?.trim() && !item.item_normam?.trim())) {
      setFormError('Preencha a descrição das exigências avulsas adicionadas ou remova as linhas vazias.'); return
    }
    setSubmitting(true)
    try {
      await salvarRascunho({ processar: false })
      const operacaoId = crypto.randomUUID()
      await enfileirar({ operacao_id: operacaoId, tipo: 'finalizacao', agendamento_id: pacote.agendamento.id, usuario_id: usuarioId, payload: { operacao_id: operacaoId } })
      await refreshPending()
      if (online) {
        await sincronizar({ propagarErro: true })
        await carregarRelatorios()
        setScreen('reports')
      }
      else setFormError('Envio preparado. Ele será concluído automaticamente quando a conexão voltar.')
    } catch (error) {
      setFormError(error.message || 'Não foi possível enviar a vistoria para aprovação.')
    } finally {
      setSubmitting(false)
    }
  }, [carregarRelatorios, detalhes, online, pacote, refreshPending, respostas, salvarRascunho, sincronizar, usuarioId])

  const sairAplicativo = useCallback(async () => {
    if (pending && !window.confirm(`Existem ${pending} alterações ainda não sincronizadas. Sair apagará esses dados deste aparelho. Deseja continuar?`)) return
    setLoading(true)
    try { await api('logout', { method: 'POST', body: JSON.stringify({}) }) }
    catch (error) { if (online) setLoginError(error.message) }
    try { await limparDadosLocais() }
    catch (error) { setLoginError(error.message) }
    setCsrfToken('')
    setSession(null)
    setAgenda([])
    setPacote(null)
    setPending(0)
    setAuthRequired(true)
    setScreen('agenda')
    setLoading(false)
  }, [online, pending])

  const agendaComEstado = useMemo(() => {
    const ids = new Set(pacotes.map(item => item.agendamento_id || item.agendamento?.id))
    return agenda.map(item => ({ ...item, baixada: ids.has(item.id) }))
  }, [agenda, pacotes])

  if (authRequired) return <LoginScreen loading={loading} error={loginError} onLogin={autenticar} />
  if (loading && !session) return <div className="app-state"><LoaderCircle className="spin" /><strong>Preparando o modo de campo…</strong></div>
  if (fatalError) return <div className="app-state"><AlertTriangle /><strong>{fatalError}</strong><button className="primary-button" onClick={() => window.location.reload()}>Tentar novamente</button></div>
  if (loading) return <div className="loading-overlay"><LoaderCircle className="spin" /></div>

  if (screen === 'checklist' && pacote) return <ChecklistScreen pacote={pacote} respostas={respostas} detalhes={detalhes} online={online} pending={pending} syncing={syncing} saving={saving} error={formError} onSync={sincronizar} onBack={() => setScreen('agenda')} onChange={mudarStatus} onEvidence={abrirEvidencia} onDetailChange={atualizarDetalhe} onAddRequirement={adicionarExigenciaAvulsa} onRequirementChange={atualizarExigenciaAvulsa} onRemoveRequirement={removerExigenciaAvulsa} onSave={salvarManual} onSummary={revisarVistoria} />
  if (screen === 'evidence' && pacote && itemEvidencia) return <EvidenceScreen item={itensPorId.get(itemEvidencia)} resposta={respostas[itemEvidencia] || { catalogo_id: itemEvidencia, status: 'NAO_CONFORME' }} prazoCorrecao={detalhes.prazo_correcao} online={online} pending={pending} syncing={syncing} onSync={sincronizar} onBack={() => setScreen('checklist')} onUpdate={atualizarEvidencia} onPhoto={adicionarFoto} onDeletePhoto={removerFoto} onSave={async () => { await salvarRascunho({ processar: false }); setScreen('checklist') }} />
  if (screen === 'summary' && pacote) return <SummaryScreen pacote={pacote} respostas={respostas} detalhes={detalhes} online={online} pending={pending} syncing={syncing} onSync={sincronizar} onBack={() => setScreen('checklist')} onSubmit={enviarAprovacao} submitting={submitting} error={formError} />
  if (screen === 'inspections') return <InspectionsScreen pacotes={pacotes} online={online} pending={pending} syncing={syncing} onSync={sincronizar} onOpen={abrirVistoria} onNavigate={navegar} />
  if (screen === 'reports') return <ReportsScreen reports={reports} online={online} pending={pending} syncing={syncing} loading={tabLoading} onSync={sincronizar} onReload={carregarRelatorios} onNavigate={navegar} />
  if (screen === 'settings') return <SettingsScreen session={session} online={online} pending={pending} syncing={syncing} onSync={sincronizar} onInstall={instalarAplicativo} onLogout={sairAplicativo} onNavigate={navegar} />
  return <AgendaScreen session={session} agenda={agendaComEstado} online={online} pending={pending} syncing={syncing} onSync={sincronizar} onOpen={abrirVistoria} onVesselPhoto={adicionarFotoEmbarcacao} onInstall={instalarAplicativo} onNavigate={navegar} />
}
