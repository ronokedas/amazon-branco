import { expect, test } from '@playwright/test'
import { readFile } from 'node:fs/promises'

test('administrador acessa a Central de Exportações e seus filtros', async ({ page }) => {
  test.setTimeout(120_000)
  const email = process.env.ADMIN_TEST_EMAIL
  const senha = process.env.ADMIN_TEST_PASSWORD
  if (!email || !senha) throw new Error('Defina ADMIN_TEST_EMAIL e ADMIN_TEST_PASSWORD para executar este teste.')

  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await page.goto('/configuracoes/exportacoes')

  await expect(page.getByRole('heading', { name: 'Exportação de documentos' })).toBeVisible()
  await expect(page.getByText('Relatórios de vistoria e fotos')).toBeVisible()
  await expect(page.getByLabel('Selecionar tudo')).toBeVisible()
  await expect(page.getByLabel('Data inicial')).toBeVisible()
  await expect(page.getByLabel('Cliente')).toBeVisible()
  await expect(page.getByLabel('Embarcação')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Preparar ZIP' })).toBeEnabled()

  await page.locator('input[name="categorias[]"][value="VISTORIAS"]').check()
  await page.getByRole('button', { name: 'Preparar ZIP' }).click()

  const primeiraLinha = page.locator('tbody tr').first()
  await expect(primeiraLinha).toContainText('VISTORIAS')
  await expect(primeiraLinha.getByText('CONCLUIDA', { exact: true })).toBeVisible({ timeout: 100_000 })

  const download = primeiraLinha.getByRole('link', { name: 'Baixar' })
  await expect(download).toBeVisible()
  const dimensoes = await download.evaluate(element => {
    const style = getComputedStyle(element)
    return {
      width: element.getBoundingClientRect().width,
      scrollWidth: element.scrollWidth,
      clientWidth: element.clientWidth,
      whiteSpace: style.whiteSpace,
    }
  })
  expect(dimensoes.width).toBeGreaterThanOrEqual(90)
  expect(dimensoes.scrollWidth).toBeLessThanOrEqual(dimensoes.clientWidth)
  expect(dimensoes.whiteSpace).toBe('nowrap')

  const dataLocal = new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(new Date())
  await expect(primeiraLinha).toContainText(dataLocal)

  const downloadEvent = page.waitForEvent('download')
  await download.click()
  const arquivo = await downloadEvent
  expect(arquivo.suggestedFilename()).toMatch(/\.zip$/i)
  const caminho = await arquivo.path()
  expect(caminho).toBeTruthy()
  const conteudo = await readFile(caminho)
  expect(conteudo.length).toBeGreaterThan(200)
  expect(conteudo.subarray(0, 2).toString('ascii')).toBe('PK')
})
