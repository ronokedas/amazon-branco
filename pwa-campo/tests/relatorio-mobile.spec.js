import { expect, test } from '@playwright/test'

const agendaDemo = '70000000-0000-4000-8000-000000000005'

async function entrar(page) {
  const email = process.env.CAMPO_TEST_EMAIL
  const senha = process.env.CAMPO_TEST_PASSWORD
  if (!email || !senha) throw new Error('Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.')
  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
}

test('relatório usa formulário em cartões no celular e mantém tabela no desktop', async ({ page }) => {
  const erros = []
  const recursos404 = []
  page.on('console', message => { if (message.type() === 'error') erros.push(message.text()) })
  page.on('response', response => { if (response.status() === 404) recursos404.push(response.url()) })
  await entrar(page)
  await page.setViewportSize({ width: 438, height: 900 })
  await page.goto(`/vistorias/relatorio?agendamento_id=${agendaDemo}`)
  await expect(page.getByRole('heading', { level: 1, name: /Relatório técnico de vistoria/i })).toBeVisible()

  await page.getByRole('button', { name: /Adicionar Item Avulso/i }).click()
  const linha = page.locator('.linha-exigencia-avulsa').first()
  await expect(linha).toBeVisible()
  await linha.locator('input[name="exigencia_descricao[]"]').fill('Exigência de teste responsivo')
  await expect(linha.locator('td[data-label="Descrição"]')).toContainText('')

  const mobile = await page.evaluate(() => ({
    viewport: window.innerWidth,
    larguraDocumento: document.documentElement.scrollWidth,
    cabecalhoTabela: getComputedStyle(document.querySelector('#tabelaExigenciasAvulsas thead')).display,
  }))
  expect(mobile.larguraDocumento).toBeLessThanOrEqual(mobile.viewport)
  expect(mobile.cabecalhoTabela).toBe('none')

  if (process.env.RELATORIO_QA_SCREENSHOT) {
    await linha.locator('input[name="exigencia_descricao[]"]').evaluate(element => element.blur())
    await page.locator('.avulsa-section').scrollIntoViewIfNeeded()
    await page.screenshot({ path: process.env.RELATORIO_QA_SCREENSHOT, fullPage: false })
  }

  await linha.getByRole('button', { name: /Remover/i }).click()
  await expect(page.getByText('Nenhuma exigência avulsa adicionada.')).toBeVisible()

  await page.setViewportSize({ width: 1280, height: 900 })
  await page.reload()
  await page.getByRole('button', { name: /Adicionar Item Avulso/i }).click()
  await expect(page.locator('#tabelaExigenciasAvulsas thead')).toBeVisible()
  const falhasRelevantes = recursos404.filter(url => !url.endsWith('/favicon.ico'))
  expect(falhasRelevantes).toEqual([])
  expect(erros.filter(message => !(message.includes('404') && recursos404.every(url => url.endsWith('/favicon.ico'))))).toEqual([])
})

test('salvar relatório pelo celular conclui o envio do formulário', async ({ page }) => {
  await entrar(page)
  await page.setViewportSize({ width: 438, height: 900 })
  await page.goto(`/vistorias/relatorio?agendamento_id=${agendaDemo}`)
  await page.getByRole('button', { name: /Adicionar Item Avulso/i }).click()
  await page.locator('.linha-exigencia-avulsa input[name="exigencia_descricao[]"]').fill('Exigência salva pelo teste móvel')
  await page.getByRole('button', { name: /Salvar Relatorio/i }).click()
  await expect(page).toHaveURL(new RegExp(`vistorias/relatorio\\?agendamento_id=${agendaDemo}`))
  await expect(page.getByText(/Revisao do relatorio enviado/i)).toBeVisible()
})
