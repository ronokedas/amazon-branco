import { expect, test } from '@playwright/test'
import path from 'node:path'

async function entrar(page) {
  const email = process.env.CAMPO_TEST_EMAIL
  const senha = process.env.CAMPO_TEST_PASSWORD
  if (!email || !senha) throw new Error('Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.')
  await page.goto('/login?return_to=campo')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page).toHaveURL(/campo/)
  await expect(page.getByRole('heading', { name: 'Minhas vistorias' })).toBeVisible()
}

test('preserva o rascunho offline e só envia ao finalizar', async ({ page, context }) => {
  const consoleErrors = []
  let enviosAntesDaFinalizacao = 0
  page.on('console', message => {
    if (message.type() === 'error') consoleErrors.push(message.text())
  })
  page.on('request', request => {
    if (request.method() === 'POST' && /\/api\/campo\/v1\/vistorias\/[^/]+\/(rascunho|anexos|foto-embarcacao|finalizar)/.test(request.url())) enviosAntesDaFinalizacao += 1
  })

  await entrar(page)
  const vistoria = page.locator('.agenda-card').first()
  await expect(vistoria).toBeVisible()
  await expect(vistoria).toContainText(/Disponível offline|Abra para baixar/)
  await page.screenshot({ path: 'test-results/agenda-android.png', fullPage: true })

  const manifest = await page.request.get('/campo/manifest.webmanifest')
  expect(manifest.ok()).toBeTruthy()
  await expect.poll(() => page.evaluate(() => navigator.serviceWorker?.getRegistration('/campo/').then(Boolean))).toBeTruthy()

  await vistoria.getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  await expect(page.getByText('Itens selecionados')).toBeVisible()
  await page.getByRole('button', { name: /Dados da vistoria/ }).click()
  await expect(page.getByLabel('Data da realização da vistoria *')).toBeVisible()
  await page.getByRole('textbox', { name: 'Pesquisar exigência' }).fill('NORMAM')
  await expect(page.getByText(/exigências? encontradas?/)).toBeVisible()
  await page.getByRole('button', { name: 'Limpar pesquisa' }).click()
  await page.getByLabel('Responsável presente').fill('Responsável do teste em campo')
  await page.getByLabel('Observações técnicas').fill('Observação técnica preservada no rascunho offline.')
  const prazoCorrecao = page.getByRole('combobox', { name: 'Prazo para correção' })
  await expect(prazoCorrecao.locator('option')).toHaveText(['Selecione', '60 dias', '90 dias'])
  await prazoCorrecao.selectOption('90')
  await expect(page.getByText(/Vencimento:/)).toBeVisible()
  await prazoCorrecao.selectOption('60')
  await page.locator('.standalone-heading').getByRole('button', { name: 'Adicionar' }).click()
  await page.locator('textarea[placeholder^="Descreva o item"]').last().fill('Exigência avulsa criada no aplicativo.')
  const primeiroConforme = page.locator('.check-item .status-button.conforme').first()
  if (!(await primeiroConforme.getAttribute('class')).includes('selected')) {
    await primeiroConforme.click()
    await expect(page.getByRole('heading', { name: 'Evidências do item' })).toBeVisible()
    await page.getByRole('button', { name: 'Salvar evidências' }).click()
  }
  await expect(page.getByText('Salvo automaticamente neste aparelho')).toBeVisible()
  await expect(page.getByRole('button', { name: /Sincronizar|Enviar agora|Salvar rascunho/ })).toHaveCount(0)
  await page.getByRole('button', { name: 'Revisar e enviar' }).click()
  await expect(page.getByText('Ao enviar, os dados e as fotos serão gravados no servidor e o PDF será gerado.')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Enviar para aprovação' })).toBeVisible()
  await page.getByRole('button', { name: 'Voltar' }).click()

  await context.setOffline(true)
  const segundoConforme = page.locator('.check-item .status-button.conforme').nth(1)
  if (!(await segundoConforme.getAttribute('class')).includes('selected')) {
    await segundoConforme.click()
    await expect(page.getByRole('heading', { name: 'Evidências do item' })).toBeVisible()
    await page.getByRole('button', { name: 'Salvar evidências' }).click()
  }
  await expect(page.getByText('Modo offline · salvo neste aparelho')).toBeVisible()

  await page.reload()
  await expect(page.getByRole('heading', { name: 'Minhas vistorias' })).toBeVisible()
  await page.locator('.agenda-card').first().getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  await expect(page.locator('.check-item').nth(0).locator('.status-button.conforme')).toHaveClass(/selected/)
  await expect(page.locator('.check-item').nth(1).locator('.status-button.conforme')).toHaveClass(/selected/)
  await expect(page.getByText('Itens selecionados')).toBeVisible()
  await page.getByRole('button', { name: /Dados da vistoria/ }).click()
  await expect(page.getByLabel('Responsável presente')).toHaveValue('Responsável do teste em campo')
  await expect(page.getByLabel('Observações técnicas')).toHaveValue('Observação técnica preservada no rascunho offline.')
  await expect(page.locator('textarea[placeholder^="Descreva o item"]').first()).toHaveValue('Exigência avulsa criada no aplicativo.')
  await page.screenshot({ path: 'test-results/checklist-offline-android.png', fullPage: true })

  await context.setOffline(false)
  await expect(page.getByText('Salvo automaticamente neste aparelho')).toBeVisible()
  await page.waitForTimeout(2000)
  expect(enviosAntesDaFinalizacao).toBe(0)
  await expect(page.locator('.check-item').nth(0).locator('.status-button.conforme')).toHaveClass(/selected/)
  await expect(page.locator('.check-item').nth(1).locator('.status-button.conforme')).toHaveClass(/selected/)

  const pequenos = await page.locator('button:visible').evaluateAll(buttons => buttons
    .map(button => ({ label: button.getAttribute('aria-label') || button.textContent.trim(), box: button.getBoundingClientRect().toJSON() }))
    .filter(item => item.box.width < 40 || item.box.height < 40))
  expect(pequenos).toEqual([])
  expect(consoleErrors.filter(message => !message.includes('ERR_INTERNET_DISCONNECTED') && !message.includes('status of 401'))).toEqual([])
})

test('IndexedDB preserva 50 respostas e 20 fotos pendentes offline', async ({ page, context }) => {
  await entrar(page)
  await context.setOffline(true)
  const antes = await page.evaluate(async () => {
    const request = indexedDB.open('amazon-campo', 3)
    const db = await new Promise((resolve, reject) => { request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error) })
    const tx = db.transaction(['pacotes', 'fila'], 'readwrite')
    const respostas = Object.fromEntries(Array.from({ length: 50 }, (_, i) => [`stress-${i}`, { catalogo_id: `stress-${i}`, status: 'CONFORME' }]))
    tx.objectStore('pacotes').put({ agendamento_id: 'stress-offline', respostas_locais: respostas, atualizado_em: Date.now() })
    for (let i = 0; i < 20; i += 1) tx.objectStore('fila').put({ operacao_id: crypto.randomUUID(), tipo: 'anexo', agendamento_id: 'stress-offline', criado_em: Date.now() + i, payload: { arquivo: new Blob(['foto-original'], { type: 'image/webp' }), nome_original: `foto-${i}.webp`, mime_type: 'image/webp' } })
    await new Promise((resolve, reject) => { tx.oncomplete = resolve; tx.onerror = () => reject(tx.error) })
    return { respostas: Object.keys(respostas).length, fotos: 20 }
  })
  expect(antes).toEqual({ respostas: 50, fotos: 20 })

  await page.reload()
  const depois = await page.evaluate(async () => {
    const request = indexedDB.open('amazon-campo', 3)
    const db = await new Promise((resolve, reject) => { request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error) })
    const tx = db.transaction(['pacotes', 'fila'], 'readonly')
    const pacoteReq = tx.objectStore('pacotes').get('stress-offline')
    const filaReq = tx.objectStore('fila').getAll()
    const pacote = await new Promise((resolve, reject) => { pacoteReq.onsuccess = () => resolve(pacoteReq.result); pacoteReq.onerror = () => reject(pacoteReq.error) })
    const fila = await new Promise((resolve, reject) => { filaReq.onsuccess = () => resolve(filaReq.result); filaReq.onerror = () => reject(filaReq.error) })
    return { respostas: Object.keys(pacote.respostas_locais).length, fotos: fila.filter(item => item.agendamento_id === 'stress-offline').length }
  })
  expect(depois).toEqual({ respostas: 50, fotos: 20 })
})

test('foto oficial da embarcação fica local até o envio final', async ({ page }) => {
  await entrar(page)
  const card = page.locator('.agenda-card').first()
  let uploads = 0
  page.on('request', request => {
    if (request.url().includes('/foto-embarcacao') && request.method() === 'POST') uploads += 1
  })

  await card.locator('input[type="file"]').setInputFiles(
    path.resolve(process.cwd(), '..', 'assets', 'img', 'portal-hero-ship.png'),
  )
  await expect(card.locator('.vessel-photo-control > span')).toContainText('Salva no aparelho')
  await page.waitForTimeout(1500)
  expect(uploads).toBe(0)
  const fotoCampo = await card.locator('.vessel-photo-control img').getAttribute('src')
  expect(fotoCampo).toContain('blob:')
})

test('item conforme aceita evidência fotográfica e preserva a foto no aparelho', async ({ page }) => {
  await entrar(page)
  await page.locator('.agenda-card').first().getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()

  const prazoCorrecao = page.getByRole('combobox', { name: 'Prazo para correção' })
  await prazoCorrecao.selectOption('60')

  const item = page.locator('.check-item').first()
  const conforme = item.locator('.status-button.conforme')
  if ((await conforme.getAttribute('class')).includes('selected')) await conforme.click()
  await conforme.click()

  await expect(page.getByRole('heading', { name: 'Evidências do item' })).toBeVisible()
  await page.locator('.photo-add input[type="file"]').setInputFiles(
    path.resolve(process.cwd(), '..', 'assets', 'img', 'portal-hero-ship.png'),
  )
  await expect(page.locator('.photo-grid figure')).toHaveCount(1)
  await expect(page.getByText('Foto protegida neste aparelho')).toBeVisible()
  await page.getByRole('button', { name: 'Salvar evidências' }).click()

  await expect(item.locator('.status-button.conforme')).toHaveClass(/selected/)
  await expect(item.getByRole('button', { name: /1 foto adicionada/ })).toBeVisible()
  await page.reload()
  await page.locator('.agenda-card').first().getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  await expect(page.locator('.check-item').first().locator('.status-button.conforme')).toHaveClass(/selected/)
  await expect(page.locator('.check-item').first().getByRole('button', { name: /1 foto adicionada/ })).toBeVisible()
})

test('navegação principal abre áreas funcionais sem controles inertes', async ({ page, context }) => {
  await entrar(page)

  await page.locator('.agenda-card').first().getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  await expect(page.getByRole('button', { name: 'Voltar' })).toBeVisible()
  await page.getByRole('button', { name: 'Voltar' }).click()

  await page.getByRole('button', { name: 'Vistorias' }).click()
  await expect(page.getByRole('heading', { name: 'Vistorias neste aparelho' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Vistorias' })).toHaveAttribute('aria-current', 'page')
  await expect(page.getByRole('button', { name: 'Continuar vistoria' }).first()).toBeVisible()
  await page.screenshot({ path: 'test-results/vistorias-baixadas-android.png', fullPage: true })

  await page.getByRole('button', { name: 'Relatórios' }).click()
  await expect(page.getByRole('heading', { name: 'Relatórios de campo' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Atualizar' })).toBeEnabled()
  await page.screenshot({ path: 'test-results/relatorios-campo-android.png', fullPage: true })

  await page.getByRole('button', { name: 'Ajustes', exact: true }).click()
  await expect(page.locator('.tab-heading h1')).toBeVisible()
  await expect(page.getByText('Dados neste aparelho')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Sair do ERP neste navegador' })).toBeEnabled()
  await page.screenshot({ path: 'test-results/ajustes-campo-android.png', fullPage: true })

  await page.getByRole('button', { name: 'Agenda' }).click()
  await expect(page.getByRole('heading', { name: 'Minhas vistorias' })).toBeVisible()

  await context.setOffline(true)
  await page.getByRole('button', { name: 'Relatórios' }).click()
  await expect(page.getByRole('heading', { name: 'Relatórios de campo' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Atualizar' })).toBeDisabled()
})

test('logout do ERP remove os dados locais do Campo após confirmação', async ({ page }) => {
  await entrar(page)
  await page.evaluate(async () => {
    const db = await new Promise((resolve, reject) => {
      const request = indexedDB.open('amazon-campo', 3)
      request.onsuccess = () => resolve(request.result)
      request.onerror = () => reject(request.error)
    })
    const tx = db.transaction('meta', 'readwrite')
    tx.objectStore('meta').put({ chave: 'teste-logout', valor: true })
    await new Promise((resolve, reject) => { tx.oncomplete = resolve; tx.onerror = () => reject(tx.error) })
    db.close()
    await caches.open('amazon-campo-teste-logout')
  })

  await page.getByRole('button', { name: 'Ajustes', exact: true }).click()
  page.on('dialog', dialog => dialog.accept())
  await page.getByRole('button', { name: 'Sair do ERP neste navegador' }).click()
  await expect(page).toHaveURL(/login/)
  const estadoLocal = await page.evaluate(async () => {
    const existe = (await indexedDB.databases()).some(item => item.name === 'amazon-campo')
    if (!existe) return { registros: 0, cacheTeste: (await caches.keys()).includes('amazon-campo-teste-logout') }
    const request = indexedDB.open('amazon-campo', 3)
    const db = await new Promise((resolve, reject) => { request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error) })
    const lojas = ['meta', 'pacotes', 'fila'].filter(store => db.objectStoreNames.contains(store))
    if (!lojas.length) { db.close(); return { registros: 0, cacheTeste: (await caches.keys()).includes('amazon-campo-teste-logout') } }
    const tx = db.transaction(lojas, 'readonly')
    const counts = await Promise.all(lojas.map(store => new Promise((resolve, reject) => {
      const count = tx.objectStore(store).count(); count.onsuccess = () => resolve(count.result); count.onerror = () => reject(count.error)
    })))
    db.close()
    return { registros: counts.reduce((total, value) => total + value, 0), cacheTeste: (await caches.keys()).includes('amazon-campo-teste-logout') }
  })
  expect(estadoLocal).toEqual({ registros: 0, cacheTeste: false })
})
