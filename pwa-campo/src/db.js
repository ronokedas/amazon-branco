const DB_NAME = 'amazon-campo'
const DB_VERSION = 3
const conexoesAbertas = new Set()

function fecharConexao(db) {
  if (!db) return
  db.close()
  conexoesAbertas.delete(db)
}

function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION)
    request.onupgradeneeded = () => {
      const db = request.result
      if (!db.objectStoreNames.contains('pacotes')) db.createObjectStore('pacotes', { keyPath: 'agendamento_id' })
      if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta', { keyPath: 'chave' })
      if (!db.objectStoreNames.contains('fila')) {
        const store = db.createObjectStore('fila', { keyPath: 'operacao_id' })
        store.createIndex('criado_em', 'criado_em')
      }
    }
    request.onsuccess = () => {
      conexoesAbertas.add(request.result)
      request.result.onversionchange = () => fecharConexao(request.result)
      resolve(request.result)
    }
    request.onerror = () => reject(request.error)
  })
}

async function transaction(storeName, mode, action) {
  const db = await openDatabase()
  return new Promise((resolve, reject) => {
    const tx = db.transaction(storeName, mode)
    const store = tx.objectStore(storeName)
    const result = action(store)
    tx.oncomplete = () => { fecharConexao(db); resolve(result?.result) }
    tx.onerror = () => { fecharConexao(db); reject(tx.error) }
  })
}

export function salvarPacote(pacote) {
  return transaction('pacotes', 'readwrite', store => store.put({ ...pacote, atualizado_em: Date.now() }))
}

export function salvarMeta(chave, valor) {
  return transaction('meta', 'readwrite', store => store.put({ chave, valor, atualizado_em: Date.now() }))
}

export async function obterMeta(chave) {
  const db = await openDatabase()
  return new Promise((resolve, reject) => {
    const req = db.transaction('meta', 'readonly').objectStore('meta').get(chave)
    req.onsuccess = () => { const valor = req.result?.valor ?? null; fecharConexao(db); resolve(valor) }
    req.onerror = () => { fecharConexao(db); reject(req.error) }
  })
}

export async function obterPacote(agendamentoId) {
  const db = await openDatabase()
  return new Promise((resolve, reject) => {
    const req = db.transaction('pacotes', 'readonly').objectStore('pacotes').get(agendamentoId)
    req.onsuccess = () => { const valor = req.result || null; fecharConexao(db); resolve(valor) }
    req.onerror = () => { fecharConexao(db); reject(req.error) }
  })
}

function usuarioDoPacote(pacote) {
  return pacote?.usuario_id || pacote?.agendamento?.vistoriador_id || null
}

export async function listarPacotes(usuarioId = null) {
  const db = await openDatabase()
  return new Promise((resolve, reject) => {
    const req = db.transaction('pacotes', 'readonly').objectStore('pacotes').getAll()
    req.onsuccess = () => {
      const itens = [...req.result]
        .filter(item => !usuarioId || usuarioDoPacote(item) === usuarioId)
        .sort((a, b) => Number(b.atualizado_em || 0) - Number(a.atualizado_em || 0))
      fecharConexao(db); resolve(itens)
    }
    req.onerror = () => { fecharConexao(db); reject(req.error) }
  })
}

export async function enfileirar(operacao, substituirTipo = false) {
  const db = await openDatabase()
  const tx = db.transaction('fila', 'readwrite')
  const store = tx.objectStore('fila')
  if (substituirTipo) {
    const todos = await new Promise((resolve, reject) => {
      const req = store.getAll()
      req.onsuccess = () => resolve(req.result)
      req.onerror = () => reject(req.error)
    })
    todos
      .filter(item => item.tipo === operacao.tipo && item.agendamento_id === operacao.agendamento_id)
      .forEach(item => store.delete(item.operacao_id))
  }
  store.put({ ...operacao, criado_em: operacao.criado_em || Date.now() })
  return new Promise((resolve, reject) => {
    tx.oncomplete = () => { fecharConexao(db); resolve() }
    tx.onerror = () => { fecharConexao(db); reject(tx.error) }
  })
}

export async function listarFila(usuarioId = null) {
  const db = await openDatabase()
  return new Promise((resolve, reject) => {
    const tx = db.transaction(['fila', 'pacotes'], 'readonly')
    const filaReq = tx.objectStore('fila').getAll()
    const pacotesReq = tx.objectStore('pacotes').getAll()
    tx.oncomplete = () => {
      const donosPorAgendamento = new Map(
        (pacotesReq.result || []).map(item => [item.agendamento_id, usuarioDoPacote(item)]),
      )
      const prioridade = { foto_embarcacao: 0, rascunho: 1, anexo: 2, finalizacao: 3 }
      const itens = [...(filaReq.result || [])]
        .filter(item => !usuarioId || (item.usuario_id || donosPorAgendamento.get(item.agendamento_id)) === usuarioId)
        .sort((a, b) => (prioridade[a.tipo] - prioridade[b.tipo]) || (a.criado_em - b.criado_em))
      fecharConexao(db); resolve(itens)
    }
    tx.onerror = () => { fecharConexao(db); reject(tx.error) }
  })
}

export function removerDaFila(operacaoId) {
  return transaction('fila', 'readwrite', store => store.delete(operacaoId))
}

export async function contarFila(usuarioId = null) {
  return (await listarFila(usuarioId)).length
}

export async function limparDadosLocais() {
  for (const db of [...conexoesAbertas]) fecharConexao(db)
  await new Promise((resolve, reject) => {
    const request = indexedDB.deleteDatabase(DB_NAME)
    request.onsuccess = resolve
    request.onerror = () => reject(request.error)
    request.onblocked = () => reject(new Error('Não foi possível limpar os dados locais. Feche outras abas do aplicativo e tente novamente.'))
  })
  if ('caches' in window) {
    const keys = await caches.keys()
    await Promise.all(keys.map(key => caches.delete(key)))
  }
}
