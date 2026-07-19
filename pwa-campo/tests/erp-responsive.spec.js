import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'

async function entrar(page) {
  const email = process.env.CAMPO_TEST_EMAIL
  const senha = process.env.CAMPO_TEST_PASSWORD
  if (!email || !senha) throw new Error('Defina CAMPO_TEST_EMAIL e CAMPO_TEST_PASSWORD para executar os testes.')
  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page).toHaveURL(/dashboard/)
}

async function semOverflowHorizontal(page) {
  return page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    document: document.documentElement.scrollWidth,
    body: document.body.scrollWidth,
  }))
}

async function interativosCortadosHorizontalmente(page) {
  return page.evaluate(() => Array.from(document.querySelectorAll('.page-content a, .page-content button, .page-content input, .page-content select, .page-content textarea, .page-content [role="button"]')).flatMap(element => {
    const rect = element.getBoundingClientRect()
    const style = getComputedStyle(element)
    if (rect.width < 1 || rect.height < 1 || style.display === 'none' || style.visibility === 'hidden') return []

    let ancestor = element.parentElement
    while (ancestor && ancestor !== document.body) {
      const ancestorStyle = getComputedStyle(ancestor)
      if (/(auto|scroll|hidden|clip)/.test(ancestorStyle.overflowX)) {
        const boundary = ancestor.getBoundingClientRect()
        if (rect.left < boundary.left - 1 || rect.right > boundary.right + 1) {
          return [{
            element: element.tagName.toLowerCase(),
            text: element.textContent.replace(/\s+/g, ' ').trim().slice(0, 50),
            ancestor: String(ancestor.className).slice(0, 80),
            excessLeft: Math.max(0, Math.round(boundary.left - rect.left)),
            excessRight: Math.max(0, Math.round(rect.right - boundary.right)),
          }]
        }
      }
      ancestor = ancestor.parentElement
    }
    return []
  }).slice(0, 12))
}

function rotasVisuaisDoRouter() {
  const source = readFileSync(new URL('../../index.php', import.meta.url), 'utf8')
  const routes = Array.from(source.matchAll(/'([^']+)'\s*=>\s*'modules\/([^']+\.php)'/g), match => ({
    route: `/${match[1]}`,
    file: match[2],
  }))

  return routes.filter(({ route, file }) => {
    if (/\/(actions?|logout|pdf|relatorio_pdf)(?:\.php)?$/i.test(route)) return false
    if (/(?:actions?|logout|pdf)\.php$/i.test(file)) return false
    if (/^\/portal(?:\/|$)/.test(route)) return false
    return true
  }).map(({ route }) => route)
}

async function blocosEscurosGrandes(page) {
  return page.evaluate(() => Array.from(document.querySelectorAll('.page-content *')).flatMap(element => {
    const style = getComputedStyle(element)
    const rect = element.getBoundingClientRect()
    if (style.display === 'none' || style.visibility === 'hidden' || rect.width * rect.height < 5000) return []
    if (element.matches('a, button, .btn, .badge, .status-badge, canvas, svg, iframe, embed, object') || element.closest('.sidebar, .topbar')) return []
    const rgb = style.backgroundColor.match(/[\d.]+/g)?.map(Number)
    if (!rgb || rgb.length < 3 || (rgb[3] ?? 1) < .45) return []
    const [r, g, b] = rgb
    if (g >= 100 && g > r * 1.3 && g > b * 1.15) return []
    if ((r + g + b) / 3 >= 105) return []
    return [{
      tag: element.tagName.toLowerCase(),
      className: String(element.className).slice(0, 90),
      background: style.backgroundColor,
      size: `${Math.round(rect.width)}x${Math.round(rect.height)}`,
      text: element.textContent.replace(/\s+/g, ' ').trim().slice(0, 60),
    }]
  }).slice(0, 8))
}

test.describe('ERP responsivo v2', () => {
  test('dashboard desktop usa o novo sistema visual', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1600, height: 980 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()) })

    await entrar(page)
    await expect(page.locator('html')).toHaveClass(/erp-v2-ready/)
    await expect(page.locator('.sidebar')).toBeVisible()
    await expect(page.locator('.dashboard-kpi')).toHaveCount(4)
    await expect(page.locator('.dashboard-agenda')).toBeVisible()
    await expect(page.locator('.dashboard-priorities')).toBeVisible()
    await expect(page.locator('#dashboardVistoriasChart')).toBeVisible()
    await expect(page.getByRole('link', { name: /Novo agendamento/i })).toHaveAttribute('href', /agendamentos\/form/)
    await expect(page.getByRole('link', { name: /Nova vistoria/i })).toHaveAttribute('href', /vistorias\/nova/)
    await page.locator('#dashboardChartMode').selectOption('cumulative')
    await expect(page.locator('#dashboardChartMode')).toHaveValue('cumulative')
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.document).toBe(1600)
    await page.locator('input[name="inicio"]').fill('01/07/2026')
    await page.locator('input[name="fim"]').fill('14/07/2026')
    await page.locator('.dashboard-period button').click()
    await expect(page).toHaveURL(/inicio=2026-07-01&fim=2026-07-14/)
    await page.screenshot({ path: 'test-results/erp-dashboard-desktop.png', fullPage: true })
    expect(errors).toEqual([])
    await context.close()
  })

  test('comercial separa propostas emitidas de vendas assinadas e calcula a meta corretamente', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()) })
    page.on('pageerror', error => errors.push(error.message))

    await entrar(page)
    await page.goto('/comercial')

    await expect(page.getByText('Vendas Assinadas no Mês', { exact: true })).toBeVisible()
    await expect(page.getByText('Valor Total do Mês', { exact: true })).toHaveCount(0)
    await expect(page.getByTestId('conversao-propostas-mes')).toContainText('assinadas')

    const indicadores = await page.getByTestId('indicadores-comerciais').evaluate(element => ({
      total: Number(element.dataset.totalPropostas),
      assinadas: Number(element.dataset.assinadasEmitidas),
      emitido: Number(element.dataset.valorEmitido),
      assinado: Number(element.dataset.valorAssinado),
      meta: Number(element.dataset.meta),
      percentual: Number(element.dataset.percentualMeta),
    }))

    expect(indicadores.assinado).toBeLessThanOrEqual(indicadores.emitido)
    expect(indicadores.percentual).toBe(Math.round((indicadores.assinado / indicadores.meta) * 1000) / 10)
    const conversaoEsperada = Math.round((indicadores.assinadas / indicadores.total) * 1000) / 10
    await expect(page.getByTestId('conversao-propostas-mes')).toContainText(`${String(conversaoEsperada).replace('.', ',')}%`)

    if (process.env.COMMERCIAL_SCREENSHOT_DESKTOP) {
      await page.screenshot({ path: process.env.COMMERCIAL_SCREENSHOT_DESKTOP, fullPage: true })
    }

    await page.locator('#filtroStatus').selectOption('assinada')
    await expect(page).toHaveURL(/status=assinada/)
    await expect(page.locator('#filtroStatus')).toHaveValue('assinada')
    expect(errors).toEqual([])
    await context.close()

    const mobileContext = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 })
    const mobilePage = await mobileContext.newPage()
    await entrar(mobilePage)
    await mobilePage.goto('/comercial')
    await expect(mobilePage.getByText('Vendas Assinadas no Mês', { exact: true })).toBeVisible()
    const mobileOverflow = await semOverflowHorizontal(mobilePage)
    expect(mobileOverflow.document).toBeLessThanOrEqual(mobileOverflow.viewport)
    expect(mobileOverflow.body).toBeLessThanOrEqual(mobileOverflow.viewport)
    if (process.env.COMMERCIAL_SCREENSHOT_MOBILE) {
      await mobilePage.screenshot({ path: process.env.COMMERCIAL_SCREENSHOT_MOBILE, fullPage: true })
    }
    await mobileContext.close()
  })

  test('vistorias mantem tabela profissional no desktop e cabecalho claro', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()) })
    page.on('pageerror', error => errors.push(error.message))
    await entrar(page)
    await page.goto('/vistorias')

    await expect(page.getByRole('heading', { name: 'Vistorias', exact: true })).toBeVisible()
    await expect(page.locator('.inspection-desktop-list')).toBeVisible()
    await expect(page.locator('.inspection-mobile-list')).toBeHidden()
    await expect(page.locator('.inspection-desktop-list tbody tr').first()).toBeVisible()
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    expect(errors).toEqual([])
    await page.screenshot({ path: 'test-results/erp-vistorias-desktop.png', fullPage: true })
    await context.close()
  })

  test('cabecalhos antigos restantes usam superficie clara no desktop', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    await entrar(page)
    const rotas = [
      '/embarcacoes', '/armadores', '/proprietarios', '/despachantes',
      '/usuarios', '/emails', '/financeiro', '/comercial', '/comercial/propostas',
      '/comercial/servicos', '/documentacao/certificados', '/documentacao/cnbl',
      '/documentacao/cnarq', '/documentacao/lp', '/documentacao/lc', '/documentacao/cht',
      '/portal-clientes',
    ]

    for (const rota of rotas) {
      const response = await page.goto(rota)
      expect(response?.status(), `status de ${rota}`).toBeLessThan(400)
      const header = page.locator('.tabela-header').first()
      await expect(header, `cabecalho de ${rota}`).toBeVisible()
      const visual = await header.evaluate(element => ({
        background: getComputedStyle(element).backgroundColor,
        color: getComputedStyle(element.querySelector('h1, h2, h3')).color,
      }))
      expect(visual.background, `fundo de ${rota}`).toBe('rgb(255, 255, 255)')
      expect(visual.color, `texto de ${rota}`).toBe('rgb(27, 51, 44)')
      const overflow = await semOverflowHorizontal(page)
      expect(overflow.document, `documento em ${rota}`).toBeLessThanOrEqual(overflow.viewport)
    }
    await page.goto('/embarcacoes')
    await page.screenshot({ path: 'test-results/erp-lista-legada-desktop.png', fullPage: true })
    await context.close()
  })

  test('todas as rotas visuais declaradas no sistema rejeitam o tema escuro antigo', async ({ browser }) => {
    test.setTimeout(180_000)
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    let currentRoute = '/login'
    page.on('console', message => { if (message.type() === 'error') errors.push(`${currentRoute}: ${message.text()}`) })
    page.on('pageerror', error => errors.push(`${currentRoute}: ${error.message}`))
    await entrar(page)

    const queue = [...rotasVisuaisDoRouter(), '/certificados/wizard?modelo=CSN']
    const audited = new Set()
    const queued = new Set(queue)
    const appOrigin = new URL(page.url()).origin

    while (queue.length && audited.size < 120) {
      currentRoute = queue.shift()
      const response = await page.goto(currentRoute)
      expect(response?.status(), `status de ${currentRoute}`).toBeLessThan(400)
      await expect(page.locator('body')).not.toBeEmpty()

      const finalUrl = new URL(page.url())
      const finalRoute = `${finalUrl.pathname}${finalUrl.search}`
      audited.add(finalRoute)

      if (await page.locator('.sidebar').count()) {
        const dark = await blocosEscurosGrandes(page)
        expect(dark, `tema escuro ainda presente em ${currentRoute}`).toEqual([])
        const overflow = await semOverflowHorizontal(page)
        expect(overflow.document, `documento em ${currentRoute}`).toBeLessThanOrEqual(overflow.viewport)
        expect(overflow.body, `body em ${currentRoute}`).toBeLessThanOrEqual(overflow.viewport)
        const interativosCortados = await interativosCortadosHorizontalmente(page)
        expect(interativosCortados, `acoes cortadas em ${currentRoute}`).toEqual([])

        const discovered = await page.locator('a[href]').evaluateAll((links) => links.map(link => link.href))
        for (const href of discovered) {
          const url = new URL(href)
          const candidate = `${url.pathname}${url.search}`
          if (url.origin !== appOrigin || queued.has(candidate) || audited.has(candidate)) continue
          if (/\/(actions?|logout|pdf|relatorio_pdf)(?:\.php)?(?:\?|$)/i.test(candidate)) continue
          if (/\b(action|token)=/i.test(url.search)) continue
          if (url.pathname.startsWith('/campo') || url.pathname.startsWith('/portal')) continue
          queued.add(candidate)
          queue.push(candidate)
        }
      }
    }

    expect(audited.size).toBeGreaterThanOrEqual(45)
    console.log(`Auditoria visual desktop: ${audited.size} rotas e estados reais verificados`)
    expect(errors).toEqual([])
    await page.goto('/certificados')
    await page.screenshot({ path: 'test-results/erp-certificados-desktop.png', fullPage: true })
    await context.close()
  })

  test('portal de clientes mantem todas as acoes visiveis em tela larga e no celular', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()) })
    page.on('pageerror', error => errors.push(error.message))
    await entrar(page)
    await page.goto('/portal-clientes')

    const lista = page.getByTestId('portal-clientes-lista')
    const selecionar = lista.getByRole('link', { name: /Selecionar/ })
    await expect(selecionar).toHaveCount(4)
    const medidas = await lista.evaluate(element => ({ clientWidth: element.clientWidth, scrollWidth: element.scrollWidth }))
    expect(medidas.scrollWidth).toBeLessThanOrEqual(medidas.clientWidth)
    expect(await interativosCortadosHorizontalmente(page)).toEqual([])

    if (process.env.PORTAL_CLIENTES_SCREENSHOT_DESKTOP) {
      await page.screenshot({ path: process.env.PORTAL_CLIENTES_SCREENSHOT_DESKTOP, fullPage: true })
    }

    await selecionar.first().click()
    await expect(page).toHaveURL(/portal-clientes\?id=/)
    await expect(page.getByTestId('portal-clientes-detalhe').getByRole('button', { name: /Enviar dados/ })).toBeVisible()
    expect(errors).toEqual([])
    await context.close()

    const mobileContext = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 })
    const mobilePage = await mobileContext.newPage()
    await entrar(mobilePage)
    await mobilePage.goto('/portal-clientes')
    const mobileOverflow = await semOverflowHorizontal(mobilePage)
    expect(mobileOverflow.document).toBeLessThanOrEqual(mobileOverflow.viewport)
    expect(mobileOverflow.body).toBeLessThanOrEqual(mobileOverflow.viewport)
    expect(await interativosCortadosHorizontalmente(mobilePage)).toEqual([])
    if (process.env.PORTAL_CLIENTES_SCREENSHOT_MOBILE) {
      await mobilePage.screenshot({ path: process.env.PORTAL_CLIENTES_SCREENSHOT_MOBILE, fullPage: true })
    }
    await mobileContext.close()
  })

  test('todas as rotas visuais declaradas permanecem claras e responsivas no celular', async ({ browser }) => {
    test.setTimeout(180_000)
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    let currentRoute = '/login'
    page.on('console', message => { if (message.type() === 'error') errors.push(`${currentRoute}: ${message.text()}`) })
    page.on('pageerror', error => errors.push(`${currentRoute}: ${error.message}`))
    await entrar(page)

    const routes = [...new Set([...rotasVisuaisDoRouter(), '/certificados/wizard?modelo=CSN'])]
    let rendered = 0
    for (const route of routes) {
      currentRoute = route
      const response = await page.goto(route)
      expect(response?.status(), `status de ${route}`).toBeLessThan(400)
      await expect(page.locator('body')).not.toBeEmpty()
      if (!await page.locator('.sidebar').count()) continue
      rendered++
      const dark = await blocosEscurosGrandes(page)
      expect(dark, `tema escuro ainda presente no celular em ${route}`).toEqual([])
      const overflow = await semOverflowHorizontal(page)
      expect(overflow.document, `documento em ${route}`).toBeLessThanOrEqual(overflow.viewport)
      expect(overflow.body, `body em ${route}`).toBeLessThanOrEqual(overflow.viewport)
    }

    expect(rendered).toBeGreaterThanOrEqual(40)
    console.log(`Auditoria visual mobile: ${rendered} rotas declaradas verificadas`)
    expect(errors).toEqual([])

    currentRoute = '/certificados'
    await page.goto(currentRoute)
    await expect(page.getByRole('heading', { name: /Emiss.o de Certificados/i })).toBeVisible()
    await expect(page.locator('.cert-search-result').first()).toBeVisible()
    await page.locator('#busca_relatorio_certificado').fill('BALSA')
    await expect(page.locator('.cert-search-result').first()).toBeVisible()
    await page.screenshot({ path: 'test-results/erp-certificados-mobile.png', fullPage: true })

    currentRoute = '/certificados/wizard?modelo=CSN'
    await page.goto(currentRoute)
    const firstType = page.locator('.cert-type-card input[type="radio"]').first()
    await firstType.check({ force: true })
    await expect(firstType).toBeChecked()
    await expect(firstType.locator('xpath=..')).toHaveClass(/is-selected/)
    await context.close()
  })

  test('listas e menu funcionam no celular sem rolagem lateral', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })
    const page = await context.newPage()
    const errors = []
    let currentRoute = '/login'
    page.on('console', message => { if (message.type() === 'error') errors.push(`${currentRoute}: ${message.text()}`) })
    page.on('pageerror', error => errors.push(`${currentRoute}: ${error.message}`))
    await entrar(page)

    await page.locator('.sidebar-mobile-toggle').click()
    await expect(page.locator('.sidebar')).toHaveClass(/active/)
    await expect.poll(() => page.locator('.sidebar').evaluate(sidebar => Math.round(sidebar.getBoundingClientRect().left))).toBe(0)
    const menuState = await page.locator('.sidebar').evaluate(sidebar => ({
      width: sidebar.getBoundingClientRect().width,
      left: sidebar.getBoundingClientRect().left,
      classes: sidebar.className,
    }))
    expect(menuState.width).toBeGreaterThanOrEqual(280)
    expect(Math.round(menuState.left)).toBe(0)
    await page.screenshot({ path: 'test-results/erp-dashboard-mobile-menu.png', fullPage: false })
    await page.locator('.sidebar-mobile-toggle').click()

    await page.goto('/vistorias')
    await expect(page.getByRole('heading', { name: 'Vistorias', exact: true })).toBeVisible()
    await expect(page.locator('.inspection-desktop-list')).toBeHidden()
    const firstInspection = page.locator('.inspection-card').first()
    await expect(firstInspection).toBeVisible()
    await expect(firstInspection.locator('.inspection-card-primary')).toContainText('Ver detalhes')
    await firstInspection.locator('summary').click()
    await expect(firstInspection.locator('dl')).toBeVisible()
    await page.locator('#buscaVistoria').fill('texto que nao existe')
    await expect(page.locator('.inspection-no-results')).toBeVisible()
    await page.locator('#buscaVistoria').fill('')
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    await page.screenshot({ path: 'test-results/erp-vistorias-mobile.png', fullPage: true })
    expect(errors).toEqual([])
    await context.close()
  })

  test('formularios, modulos criticos e portal permanecem utilizaveis no celular', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })
    const page = await context.newPage()
    const errors = []
    let currentRoute = '/login'
    page.on('console', message => { if (message.type() === 'error') errors.push(`${currentRoute}: ${message.text()}`) })
    page.on('pageerror', error => errors.push(`${currentRoute}: ${error.message}`))
    await entrar(page)

    const rotas = [
      '/agendamentos',
      '/agendamentos/form',
      '/embarcacoes',
      '/embarcacoes/form',
      '/armadores',
      '/proprietarios',
      '/despachantes',
      '/documentacao',
      '/documentacao/aprovacao_relatorios',
      '/documentacao/certificados',
      '/documentacao/cnbl',
      '/documentacao/cnarq',
      '/documentacao/lp',
      '/documentacao/lc',
      '/documentacao/cht',
      '/documentacao/novo_certificado',
      '/documentacao/baixa_exigencias',
      '/certificados',
      '/comercial',
      '/comercial/nova',
      '/comercial/propostas',
      '/comercial/servicos',
      '/comercial/servicos/form',
      '/financeiro',
      '/financeiro/form',
      '/financeiro/relatorios',
      '/relatorios',
      '/emails',
      '/usuarios',
      '/usuarios/form',
      '/portal-clientes',
      '/responsaveis_assinatura',
      '/responsaveis_assinatura/form',
      '/configuracoes',
      '/configuracoes/geral',
      '/configuracoes/basicas',
      '/configuracoes/backup',
      '/perfil',
    ]

    for (const rota of rotas) {
      currentRoute = rota
      const response = await page.goto(rota)
      expect(response?.status(), `status de ${rota}`).toBeLessThan(400)
      await expect(page.locator('html')).toHaveClass(/erp-v2-ready/)
      const legacyHeader = page.locator('.tabela-header').first()
      if (await legacyHeader.count()) {
        const background = await legacyHeader.evaluate(element => getComputedStyle(element).backgroundColor)
        expect(background, `cabecalho claro em ${rota}`).toBe('rgba(0, 0, 0, 0)')
      }
      const overflow = await semOverflowHorizontal(page)
      expect(overflow.document, `documento em ${rota}`).toBeLessThanOrEqual(overflow.viewport)
      expect(overflow.body, `body em ${rota}`).toBeLessThanOrEqual(overflow.viewport)
      const interativosCortados = await interativosCortadosHorizontalmente(page)
      expect(interativosCortados, `acoes cortadas em ${rota}`).toEqual([])
    }

    currentRoute = '/embarcacoes'
    await page.goto(currentRoute)
    const firstVesselRow = page.locator('table.erp-responsive-table tbody tr').first()
    await expect(firstVesselRow).toBeVisible()
    await expect(firstVesselRow.getByText('Editar', { exact: true })).toBeVisible()
    await expect(firstVesselRow.getByText('Desativar', { exact: true })).toBeVisible()
    await page.screenshot({ path: 'test-results/erp-embarcacoes-mobile.png', fullPage: true })

    currentRoute = '/embarcacoes/form'
    await page.goto(currentRoute)
    await expect(page.locator('form').first()).toBeVisible()
    await page.screenshot({ path: 'test-results/erp-embarcacao-form-mobile.png', fullPage: true })

    currentRoute = '/portal/login'
    await page.goto(currentRoute)
    await expect(page.locator('html')).toHaveClass(/erp-v2-ready/)
    await expect(page.locator('form').first()).toBeVisible()
    const portalLoginSurface = await page.evaluate(() => ({
      backgroundImage: getComputedStyle(document.body).backgroundImage,
      viewportWidth: document.documentElement.clientWidth,
      pageWidth: document.querySelector('.portal-page')?.getBoundingClientRect().width || 0,
    }))
    expect(portalLoginSurface.backgroundImage).toContain('linear-gradient')
    expect(portalLoginSurface.backgroundImage).not.toBe('none')
    expect(Math.abs(portalLoginSurface.viewportWidth - portalLoginSurface.pageWidth)).toBeLessThanOrEqual(20)
    const portalOverflow = await semOverflowHorizontal(page)
    expect(portalOverflow.document).toBeLessThanOrEqual(portalOverflow.viewport)
    await page.screenshot({ path: 'test-results/portal-login-mobile.png', fullPage: true })
    expect(errors).toEqual([])
    await context.close()
  })

  test('relatorio de vistoria nao comprime colunas no celular', async ({ browser }) => {
    const agendamentoId = process.env.ERP_TEST_AGENDAMENTO_ID || '3a45f350-7b29-11f1-a8a1-56798194b3af'
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })
    const page = await context.newPage()
    await entrar(page)

    const response = await page.goto(`/vistorias/relatorio?agendamento_id=${agendamentoId}`)
    expect(response?.status()).toBeLessThan(400)
    await expect(page.locator('html')).toHaveClass(/erp-v2-ready/)
    await expect(page.getByRole('heading', { name: /Relat.rio T.cnico de Vistoria/i }).last()).toBeVisible()
    const abrirPdf = page.getByTestId('abrir-pdf-completo')
    await expect(abrirPdf).toBeVisible()
    await expect(abrirPdf).toHaveAttribute('href', /\/vistorias\/relatorio_pdf\.php\?id=/)
    const botaoPdf = await abrirPdf.boundingBox()
    expect(botaoPdf?.height).toBeGreaterThanOrEqual(52)
    expect(botaoPdf?.width).toBeGreaterThanOrEqual(280)
    await expect(page.locator('.admin-review-pdf')).toBeHidden()
    await expect(page.locator('.admin-review-document-mobile-note')).toBeVisible()
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    const interativosCortados = await interativosCortadosHorizontalmente(page)
    expect(interativosCortados).toEqual([])
    await page.screenshot({ path: 'test-results/erp-relatorio-vistoria-mobile.png', fullPage: true })
    await context.close()
  })

  test('relatorio destaca o PDF oficial no desktop', async ({ browser }) => {
    const agendamentoId = process.env.ERP_TEST_AGENDAMENTO_ID || '3a45f350-7b29-11f1-a8a1-56798194b3af'
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } })
    const page = await context.newPage()
    await entrar(page)

    const response = await page.goto(`/vistorias/relatorio?agendamento_id=${agendamentoId}`)
    expect(response?.status()).toBeLessThan(400)
    const abrirPdf = page.getByTestId('abrir-pdf-completo')
    await expect(abrirPdf).toBeVisible()
    await expect(abrirPdf).toContainText('Abrir PDF completo')
    await expect(page.locator('.admin-review-pdf')).toBeVisible()
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    await page.screenshot({ path: 'test-results/erp-relatorio-vistoria-desktop.png', fullPage: true })
    await context.close()
  })

  test('login do portal preenche toda a janela sem fundo branco', async ({ browser }) => {
    for (const viewport of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
      const context = await browser.newContext({ viewport })
      const page = await context.newPage()
      const response = await page.goto('/portal/login')
      expect(response?.status()).toBeLessThan(400)
      await expect(page.getByRole('heading', { name: 'Portal do Cliente', exact: true })).toBeVisible()
      await expect(page.getByRole('button', { name: 'Entrar', exact: true })).toBeVisible()

      const surface = await page.evaluate(() => ({
        backgroundImage: getComputedStyle(document.body).backgroundImage,
        viewportWidth: document.documentElement.clientWidth,
        pageWidth: document.querySelector('.portal-page')?.getBoundingClientRect().width || 0,
        scrollWidth: document.documentElement.scrollWidth,
      }))
      expect(surface.backgroundImage).toContain('linear-gradient')
      expect(surface.backgroundImage).not.toBe('none')
      expect(Math.abs(surface.viewportWidth - surface.pageWidth)).toBeLessThanOrEqual(20)
      expect(surface.scrollWidth).toBeLessThanOrEqual(surface.viewportWidth)
      await context.close()
    }
  })

  test('agenda usa cartões móveis curtos com ações identificadas', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const errors = []
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()) })
    page.on('pageerror', error => errors.push(error.message))
    await entrar(page)
    await page.goto('/agendamentos')

    await expect(page.getByRole('heading', { name: 'Agendamentos', exact: true })).toBeVisible()
    await expect(page.locator('.schedule-desktop-list')).toBeHidden()
    const firstCard = page.locator('.schedule-card').first()
    await expect(firstCard).toBeVisible()
    await expect(firstCard.locator('.schedule-card-primary')).toContainText('Abrir relatório')
    await firstCard.locator('summary').click()
    await expect(firstCard.locator('.schedule-card-actions')).toBeVisible()
    await expect(firstCard.locator('.schedule-card-actions a, .schedule-card-actions button').first()).toBeVisible()

    await page.locator('.schedule-filter-toggle').click()
    await expect(page.locator('#scheduleFilters')).toBeVisible()
    const overflow = await semOverflowHorizontal(page)
    expect(overflow.document).toBeLessThanOrEqual(overflow.viewport)
    expect(overflow.body).toBeLessThanOrEqual(overflow.viewport)
    expect(errors).toEqual([])
    await context.close()
  })

  for (const viewport of [
    { width: 360, height: 800 },
    { width: 768, height: 900 },
    { width: 1024, height: 900 },
  ]) {
    test(`layout principal sem rolagem lateral em ${viewport.width}px`, async ({ browser }) => {
      const context = await browser.newContext({ viewport })
      const page = await context.newPage()
      await entrar(page)
      for (const rota of ['/dashboard', '/vistorias', '/embarcacoes/form']) {
        await page.goto(rota)
        const overflow = await semOverflowHorizontal(page)
        expect(overflow.document, `${rota} em ${viewport.width}px`).toBeLessThanOrEqual(overflow.viewport)
        expect(overflow.body, `${rota} em ${viewport.width}px`).toBeLessThanOrEqual(overflow.viewport)
      }
      await context.close()
    })
  }
})
