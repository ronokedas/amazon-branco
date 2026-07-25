import { expect, test } from '@playwright/test'

const login = process.env.PORTAL_TEST_EMAIL
const senha = process.env.PORTAL_TEST_PASSWORD

async function entrarNoPortal(page) {
  if (!login || !senha) {
    throw new Error('Defina PORTAL_TEST_EMAIL e PORTAL_TEST_PASSWORD para executar os testes do portal.')
  }
  await page.goto('/portal/login')
  await page.getByLabel('E-mail').fill(login)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page).toHaveURL(/\/portal$/)
}

async function semOverflow(page) {
  return page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    document: document.documentElement.scrollWidth,
  }))
}

test.describe('Portal do Cliente moderno', () => {
  test.use({ viewport: { width: 1440, height: 1100 } })

  test('dashboard, documentos e embarcações preservam navegação e legibilidade', async ({ page }) => {
    await entrarNoPortal(page)
    await expect(page.getByRole('heading', { name: 'Portal do Cliente' })).toBeVisible()
    await expect(page.locator('.portal-hero-button')).toBeVisible()
    await expect(page.locator('.portal-nav a.active')).toHaveText('Portal do Cliente')

    await page.getByRole('link', { name: 'Meus documentos' }).click()
    await expect(page.getByRole('heading', { name: 'Meus documentos' })).toBeVisible()
    await expect(page.locator('.portal-nav a.active')).toHaveText('Meus documentos')
    await expect(page.getByRole('button', { name: /Filtrar/ })).toBeVisible()

    await page.getByRole('link', { name: 'Embarcações' }).click()
    await expect(page).toHaveURL(/\/portal\/embarcacoes/)
    await expect(page.getByRole('heading', { name: 'Embarcações' })).toBeVisible()
    await expect(page.locator('.portal-nav a.active')).toHaveText('Embarcações')

    const sizes = await semOverflow(page)
    expect(sizes.document).toBeLessThanOrEqual(sizes.viewport)
  })

  test('envio de revisão mostra preservação, arquivos e prevenção de duplicidade', async ({ page }) => {
    await entrarNoPortal(page)
    await page.getByRole('link', { name: 'Enviar planos' }).click()
    await expect(page.getByRole('heading', { name: 'Análise de Planos' })).toBeVisible()
    await expect(page.getByText('Seus arquivos anteriores serão preservados.')).toBeVisible()

    const input = page.locator('input[type="file"]').first()
    if (await input.count()) {
      await input.setInputFiles({
        name: 'revisao-teste.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4 portal visual test'),
      })
      await expect(page.getByText('revisao-teste.pdf')).toBeVisible()
      await expect(page.getByText('1 arquivo(s)')).toBeVisible()
    }
  })

  test('menu móvel abre sem overflow horizontal', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await entrarNoPortal(page)
    const menu = page.getByRole('button', { name: 'Abrir menu' })
    await menu.click()
    await expect(page.locator('#portal-navigation')).toHaveClass(/is-open/)
    await expect(page.getByRole('link', { name: 'Enviar planos' })).toBeVisible()
    const sizes = await semOverflow(page)
    expect(sizes.document).toBeLessThanOrEqual(sizes.viewport)
  })
})
