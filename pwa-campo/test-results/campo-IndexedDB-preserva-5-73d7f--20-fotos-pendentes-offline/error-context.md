# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: campo.spec.js >> IndexedDB preserva 50 respostas e 20 fotos pendentes offline
- Location: tests\campo.spec.js:99:1

# Error details

```
Error: Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.
```

# Test source

```ts
  1   | import { expect, test } from '@playwright/test'
  2   | import path from 'node:path'
  3   | 
  4   | async function entrar(page) {
  5   |   const email = process.env.CAMPO_TEST_EMAIL
  6   |   const senha = process.env.CAMPO_TEST_PASSWORD
> 7   |   if (!email || !senha) throw new Error('Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.')
      |                               ^ Error: Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.
  8   |   await page.goto('/campo/')
  9   |   await page.getByLabel('E-mail').fill(email)
  10  |   await page.locator('#campo-senha').fill(senha)
  11  |   await page.getByRole('button', { name: 'Entrar no aplicativo' }).click()
  12  |   await expect(page.getByRole('heading', { name: 'Minhas vistorias' })).toBeVisible()
  13  | }
  14  | 
  15  | test('preserva o rascunho offline e só envia ao finalizar', async ({ page, context }) => {
  16  |   const consoleErrors = []
  17  |   let enviosAntesDaFinalizacao = 0
  18  |   page.on('console', message => {
  19  |     if (message.type() === 'error') consoleErrors.push(message.text())
  20  |   })
  21  |   page.on('request', request => {
  22  |     if (request.method() === 'POST' && /\/api\/campo\/v1\/vistorias\/[^/]+\/(rascunho|anexos|foto-embarcacao|finalizar)/.test(request.url())) enviosAntesDaFinalizacao += 1
  23  |   })
  24  | 
  25  |   await entrar(page)
  26  |   const vistoria = page.locator('.agenda-card').first()
  27  |   await expect(vistoria).toBeVisible()
  28  |   await expect(vistoria).toContainText(/Disponível offline|Abra para baixar/)
  29  |   await page.screenshot({ path: 'test-results/agenda-android.png', fullPage: true })
  30  | 
  31  |   const manifest = await page.request.get('/campo/manifest.webmanifest')
  32  |   expect(manifest.ok()).toBeTruthy()
  33  |   await expect.poll(() => page.evaluate(() => navigator.serviceWorker?.getRegistration('/campo/').then(Boolean))).toBeTruthy()
  34  | 
  35  |   await vistoria.getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  36  |   await expect(page.getByText('Itens selecionados')).toBeVisible()
  37  |   await page.getByRole('button', { name: /Dados da vistoria/ }).click()
  38  |   await expect(page.getByLabel('Data da realização da vistoria *')).toBeVisible()
  39  |   await page.getByRole('textbox', { name: 'Pesquisar exigência' }).fill('NORMAM')
  40  |   await expect(page.getByText(/exigências? encontradas?/)).toBeVisible()
  41  |   await page.getByRole('button', { name: 'Limpar pesquisa' }).click()
  42  |   await page.getByLabel('Responsável presente').fill('Responsável do teste em campo')
  43  |   await page.getByLabel('Observações técnicas').fill('Observação técnica preservada no rascunho offline.')
  44  |   const prazoCorrecao = page.getByRole('combobox', { name: 'Prazo para correção' })
  45  |   await expect(prazoCorrecao.locator('option')).toHaveText(['Selecione', '60 dias', '90 dias'])
  46  |   await prazoCorrecao.selectOption('90')
  47  |   await expect(page.getByText(/Vencimento:/)).toBeVisible()
  48  |   await prazoCorrecao.selectOption('60')
  49  |   await page.locator('.standalone-heading').getByRole('button', { name: 'Adicionar' }).click()
  50  |   await page.locator('textarea[placeholder^="Descreva o item"]').last().fill('Exigência avulsa criada no aplicativo.')
  51  |   const primeiroConforme = page.locator('.check-item .status-button.conforme').first()
  52  |   if (!(await primeiroConforme.getAttribute('class')).includes('selected')) {
  53  |     await primeiroConforme.click()
  54  |     await expect(page.getByRole('heading', { name: 'Evidências do item' })).toBeVisible()
  55  |     await page.getByRole('button', { name: 'Salvar evidências' }).click()
  56  |   }
  57  |   await expect(page.getByText('Salvo automaticamente neste aparelho')).toBeVisible()
  58  |   await expect(page.getByRole('button', { name: /Sincronizar|Enviar agora|Salvar rascunho/ })).toHaveCount(0)
  59  |   await page.getByRole('button', { name: 'Revisar e enviar' }).click()
  60  |   await expect(page.getByText('Ao enviar, os dados e as fotos serão gravados no servidor e o PDF será gerado.')).toBeVisible()
  61  |   await expect(page.getByRole('button', { name: 'Enviar para aprovação' })).toBeVisible()
  62  |   await page.getByRole('button', { name: 'Voltar' }).click()
  63  | 
  64  |   await context.setOffline(true)
  65  |   const segundoConforme = page.locator('.check-item .status-button.conforme').nth(1)
  66  |   if (!(await segundoConforme.getAttribute('class')).includes('selected')) {
  67  |     await segundoConforme.click()
  68  |     await expect(page.getByRole('heading', { name: 'Evidências do item' })).toBeVisible()
  69  |     await page.getByRole('button', { name: 'Salvar evidências' }).click()
  70  |   }
  71  |   await expect(page.getByText('Modo offline · salvo neste aparelho')).toBeVisible()
  72  | 
  73  |   await page.reload()
  74  |   await expect(page.getByRole('heading', { name: 'Minhas vistorias' })).toBeVisible()
  75  |   await page.locator('.agenda-card').first().getByRole('button', { name: /Iniciar vistoria|Continuar vistoria/ }).click()
  76  |   await expect(page.locator('.check-item').nth(0).locator('.status-button.conforme')).toHaveClass(/selected/)
  77  |   await expect(page.locator('.check-item').nth(1).locator('.status-button.conforme')).toHaveClass(/selected/)
  78  |   await expect(page.getByText('Itens selecionados')).toBeVisible()
  79  |   await page.getByRole('button', { name: /Dados da vistoria/ }).click()
  80  |   await expect(page.getByLabel('Responsável presente')).toHaveValue('Responsável do teste em campo')
  81  |   await expect(page.getByLabel('Observações técnicas')).toHaveValue('Observação técnica preservada no rascunho offline.')
  82  |   await expect(page.locator('textarea[placeholder^="Descreva o item"]').first()).toHaveValue('Exigência avulsa criada no aplicativo.')
  83  |   await page.screenshot({ path: 'test-results/checklist-offline-android.png', fullPage: true })
  84  | 
  85  |   await context.setOffline(false)
  86  |   await expect(page.getByText('Salvo automaticamente neste aparelho')).toBeVisible()
  87  |   await page.waitForTimeout(2000)
  88  |   expect(enviosAntesDaFinalizacao).toBe(0)
  89  |   await expect(page.locator('.check-item').nth(0).locator('.status-button.conforme')).toHaveClass(/selected/)
  90  |   await expect(page.locator('.check-item').nth(1).locator('.status-button.conforme')).toHaveClass(/selected/)
  91  | 
  92  |   const pequenos = await page.locator('button:visible').evaluateAll(buttons => buttons
  93  |     .map(button => ({ label: button.getAttribute('aria-label') || button.textContent.trim(), box: button.getBoundingClientRect().toJSON() }))
  94  |     .filter(item => item.box.width < 40 || item.box.height < 40))
  95  |   expect(pequenos).toEqual([])
  96  |   expect(consoleErrors.filter(message => !message.includes('ERR_INTERNET_DISCONNECTED') && !message.includes('status of 401'))).toEqual([])
  97  | })
  98  | 
  99  | test('IndexedDB preserva 50 respostas e 20 fotos pendentes offline', async ({ page, context }) => {
  100 |   await entrar(page)
  101 |   await context.setOffline(true)
  102 |   const antes = await page.evaluate(async () => {
  103 |     const request = indexedDB.open('amazon-campo', 3)
  104 |     const db = await new Promise((resolve, reject) => { request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error) })
  105 |     const tx = db.transaction(['pacotes', 'fila'], 'readwrite')
  106 |     const respostas = Object.fromEntries(Array.from({ length: 50 }, (_, i) => [`stress-${i}`, { catalogo_id: `stress-${i}`, status: 'CONFORME' }]))
  107 |     tx.objectStore('pacotes').put({ agendamento_id: 'stress-offline', respostas_locais: respostas, atualizado_em: Date.now() })
```