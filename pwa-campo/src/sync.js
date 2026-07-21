import { api } from './api'
import { listarFila, removerDaFila } from './db'

export async function processarFila(onProgress, usuarioId = null, agendamentoId = null) {
  if (!navigator.onLine) return { processadas: 0, pendentes: (await listarFila(usuarioId, agendamentoId)).length, resultados: [] }
  const fila = await listarFila(usuarioId, agendamentoId)
  let processadas = 0
  const resultados = []
  for (const operacao of fila) {
    try {
      let dados
      if (operacao.tipo === 'rascunho') {
        dados = await api(`vistorias/${operacao.agendamento_id}/rascunho`, { method: 'POST', body: JSON.stringify(operacao.payload) })
      } else if (operacao.tipo === 'anexo') {
        const form = new FormData()
        form.set('operacao_id', operacao.payload.operacao_id)
        form.set('catalogo_id', operacao.payload.catalogo_id || '')
        form.set('capturado_em', operacao.payload.capturado_em || new Date().toISOString())
        form.set('arquivo', operacao.payload.blob, operacao.payload.nome || 'evidencia')
        dados = await api(`vistorias/${operacao.agendamento_id}/anexos`, { method: 'POST', body: form })
      } else if (operacao.tipo === 'foto_embarcacao') {
        const form = new FormData()
        form.set('operacao_id', operacao.payload.operacao_id)
        form.set('arquivo', operacao.payload.blob, operacao.payload.nome || 'embarcacao')
        dados = await api(`vistorias/${operacao.agendamento_id}/foto-embarcacao`, { method: 'POST', body: form })
      } else if (operacao.tipo === 'exclusao_anexo') {
        dados = await api(`anexos/${operacao.payload.anexo_id}`, { method: 'DELETE' })
      } else if (operacao.tipo === 'finalizacao') {
        dados = await api(`vistorias/${operacao.agendamento_id}/finalizar`, { method: 'POST', body: JSON.stringify(operacao.payload) })
      }
      await removerDaFila(operacao.operacao_id)
      processadas += 1
      resultados.push({ operacao, dados })
      onProgress?.({ operacao, dados, processadas })
    } catch (error) {
      error.operacao = operacao
      throw error
    }
  }
  return { processadas, pendentes: (await listarFila(usuarioId, agendamentoId)).length, resultados }
}
