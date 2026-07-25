import { test } from '@playwright/test'
import path from 'node:path'

const login = process.env.PORTAL_TEST_EMAIL
const senha = process.env.PORTAL_TEST_PASSWORD
const output = process.env.PORTAL_QA_OUTPUT

async function entrar(page) {
  if (!login || !senha || !output) {
    throw new Error('Defina PORTAL_TEST_EMAIL, PORTAL_TEST_PASSWORD e PORTAL_QA_OUTPUT.')
  }
  await page.goto('/portal/login')
  await page.getByLabel('E-mail').fill(login)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await page.waitForURL(/\/portal$/)
}

test('capturas visuais desktop', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1100 })
  await entrar(page)
  await page.screenshot({ path: path.join(output, 'portal-dashboard-desktop.png'), fullPage: true })
  await page.goto('/portal/analises-planos')
  await page.screenshot({ path: path.join(output, 'portal-analises-desktop.png'), fullPage: true })
  await page.goto('/portal/documentos')
  await page.screenshot({ path: path.join(output, 'portal-documentos-desktop.png'), fullPage: true })
  await page.goto('/portal/embarcacoes')
  await page.screenshot({ path: path.join(output, 'portal-embarcacoes-desktop.png'), fullPage: true })
})

test('capturas visuais mobile', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await entrar(page)
  await page.screenshot({ path: path.join(output, 'portal-dashboard-mobile.png'), fullPage: true })
  await page.goto('/portal/analises-planos')
  await page.screenshot({ path: path.join(output, 'portal-analises-mobile.png'), fullPage: true })
})
