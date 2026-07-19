const TIPOS_PERMITIDOS = new Set(['image/jpeg', 'image/png', 'image/webp'])
const LIMITE_FOTO = 15 * 1024 * 1024

export async function prepararImagemOriginal(file) {
  if (!file || !TIPOS_PERMITIDOS.has(file.type)) throw new Error('Use uma foto JPEG, PNG ou WebP.')
  if (file.size < 1 || file.size > LIMITE_FOTO) throw new Error('A foto original deve ter até 15 MB.')
  if (navigator.storage?.estimate) {
    const { quota = 0, usage = 0 } = await navigator.storage.estimate()
    if (quota && quota - usage < file.size * 1.25) throw new Error('Não há espaço suficiente neste aparelho para guardar a foto offline.')
  }
  return {
    id: crypto.randomUUID(),
    nome: file.name || `evidencia-${Date.now()}`,
    blob: file,
    mime_type: file.type,
    tamanho: file.size,
    capturado_em: new Date().toISOString(),
    local: true,
    status_upload: 'pendente',
  }
}
