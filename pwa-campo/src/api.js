let csrfToken = ''

export function setCsrfToken(token) {
  csrfToken = token || ''
}

export class ApiError extends Error {
  constructor(message, status, code, details = []) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.details = details
  }
}

export async function api(path, options = {}) {
  const headers = new Headers(options.headers || {})
  if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json')
  if (options.method && options.method !== 'GET') headers.set('X-CSRF-Token', csrfToken)
  const response = await fetch(`/api/campo/v1/${path}`, {
    credentials: 'same-origin',
    ...options,
    headers,
  })
  const payload = await response.json().catch(() => ({}))
  if (!response.ok || payload.ok === false) {
    const error = payload.erro || {}
    throw new ApiError(error.mensagem || 'Não foi possível acessar o servidor.', response.status, error.codigo, error.detalhes)
  }
  return payload.dados
}
