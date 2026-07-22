import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);
const baseUrl = process.env.QA_BASE_URL || 'http://localhost:8080';
const qaEmail = process.env.QA_EMAIL || 'qa-admin@local.test';
const qaPassword = process.env.QA_PASSWORD || 'password';
const outputDir = path.resolve('storage/app/qa-visual/cash-documents');
const screenshotsDir = path.join(outputDir, 'screenshots');
const downloadsDir = path.join(outputDir, 'downloads');
const viewports = [
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1366x768', width: 1366, height: 768 },
  { name: '1024x768', width: 1024, height: 768 },
  { name: '768x1024', width: 768, height: 1024 },
  { name: '390x844', width: 390, height: 844 },
];

const evidence = [];
const issues = [];
const consoleErrors = [];
const networkErrors = [];

await fs.mkdir(screenshotsDir, { recursive: true });
await fs.mkdir(downloadsDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ acceptDownloads: true });
const page = await context.newPage();
page.on('console', (message) => {
  if (['error', 'warning'].includes(message.type())) {
    const location = message.location();
    const source = location.url ? ` @ ${location.url}` : '';
    consoleErrors.push(`${message.type()}: ${message.text()}${source}`);
  }
});
page.on('response', (response) => {
  if (response.status() >= 400) {
    networkErrors.push(`${response.status()}: ${response.url()}`);
  }
});

async function login() {
  await page.goto(`${baseUrl}/sistema/login`, { waitUntil: 'networkidle' });
  if (page.url().includes('/sistema/login')) {
    await page.fill('#email', qaEmail);
    await page.fill('#password', qaPassword);
    await page.getByRole('button', { name: /Ingresar/i }).click();
    await page.waitForLoadState('networkidle');
    await page.waitForFunction(() => !window.location.pathname.includes('/login'), null, { timeout: 15000 });
  }
}

async function capture(viewport, screen, url, options = {}) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.goto(`${baseUrl}${url}`, { waitUntil: 'networkidle' });
  if (options.beforeShot) {
    await options.beforeShot();
  }
  const file = path.join(screenshotsDir, `${viewport.name}-${screen}.png`);
  await page.screenshot({ path: file, fullPage: true });
  const metrics = await page.evaluate(() => ({
    width: window.innerWidth,
    height: window.innerHeight,
    scrollWidth: document.documentElement.scrollWidth,
    scrollHeight: document.documentElement.scrollHeight,
    activeDialogs: document.querySelectorAll('[role="dialog"][aria-modal="true"]').length,
    unnamedIconButtons: Array.from(document.querySelectorAll('button')).filter((button) => {
      const hasIcon = Boolean(button.querySelector('i, svg'));
      const hasName = Boolean(button.getAttribute('aria-label') || button.textContent.trim() || button.getAttribute('title'));
      return hasIcon && !hasName;
    }).length,
  }));

  const overflow = metrics.scrollWidth > metrics.width + 2;
  if (overflow) {
    issues.push({
      screen,
      viewport: viewport.name,
      heuristic: 'Diseño minimalista y responsive',
      severity: 'menor',
      evidence: `scrollWidth ${metrics.scrollWidth}px > viewport ${metrics.width}px`,
      solution: 'Pendiente: revisar ancho de tabla/contenedor en esta resolución.',
      status: 'pendiente',
    });
  }
  if (metrics.unnamedIconButtons > 0) {
    issues.push({
      screen,
      viewport: viewport.name,
      heuristic: 'Accesibilidad y reconocimiento',
      severity: 'menor',
      evidence: `${metrics.unnamedIconButtons} botón(es) con icono sin nombre accesible detectable.`,
      solution: 'Agregar aria-label o texto visible al botón.',
      status: 'pendiente',
    });
  }
  evidence.push({ screen, viewport: viewport.name, file, overflow, dialogs: metrics.activeDialogs });
}

async function safeClickByText(text) {
  const locator = page.getByText(text, { exact: false });
  if (await locator.count()) {
    await locator.first().click();
    await page.waitForTimeout(250);
    return true;
  }
  return false;
}

async function downloadPdf(label, url) {
  const response = await context.request.get(`${baseUrl}${url}`);
  const pdfPath = path.join(downloadsDir, `${label}.pdf`);
  if (!response.ok()) {
    throw new Error(`No se pudo descargar ${url}: HTTP ${response.status()}`);
  }
  await fs.writeFile(pdfPath, await response.body());
  const pngPrefix = path.join(screenshotsDir, `${label}`);
  await execFileAsync('pdftoppm', ['-png', '-singlefile', '-f', '1', '-r', '120', pdfPath, pngPrefix]);
  evidence.push({ screen: label, viewport: 'PDF render', file: `${pngPrefix}.png`, overflow: false, dialogs: 0 });
}

await login();

const apiResponse = await context.request.get(`${baseUrl}/sistema/cash-registers?inertia=disabled`);
const api = await apiResponse.json();
const registerId = api.currentRegister?.id;
const openingMovement = api.timeline?.data?.find((item) => item.type === 'opening');
const firstMovement = api.timeline?.data?.find((item) => item.kind === 'movement' && item.type !== 'opening') || openingMovement;

for (const viewport of viewports) {
  await capture(viewport, 'pos', '/sistema/pos');
  await capture(viewport, 'pos-cobro', '/sistema/pos', {
    beforeShot: async () => {
      await safeClickByText('PAGAR');
    },
  });
  await capture(viewport, 'cliente-rapido', '/sistema/pos', {
    beforeShot: async () => {
      await safeClickByText('PAGAR');
      await safeClickByText('Crear cliente');
    },
  });
  await capture(viewport, 'ventas', '/sistema/sales');
  await capture(viewport, 'caja', '/sistema/cash-registers');
  await capture(viewport, 'caja-ingreso', '/sistema/cash-registers', {
    beforeShot: async () => {
      await page.getByRole('button', { name: /Ingreso/i }).first().click();
      await page.waitForTimeout(250);
    },
  });
  await capture(viewport, 'caja-retiro', '/sistema/cash-registers', {
    beforeShot: async () => {
      await page.getByRole('button', { name: /Retiro/i }).first().click();
      await page.waitForTimeout(250);
    },
  });
  await capture(viewport, 'caja-cierre-denominaciones', '/sistema/cash-registers', {
    beforeShot: async () => {
      await page.getByRole('button', { name: /Cerrar caja/i }).first().click();
      await page.waitForTimeout(250);
    },
  });
  await capture(viewport, 'conciliacion', '/sistema/cash-registers/reconciliation');
  await capture(viewport, 'reportes', '/sistema/reports');
  if (registerId && firstMovement?.id) {
    await capture(viewport, 'ticket-movimiento-termico', `/sistema/cash-registers/${registerId}/movements/${firstMovement.id}/thermal`);
  }
  if (registerId) {
    await capture(viewport, 'cierre-termico', `/sistema/cash-registers/${registerId}/thermal`);
  }
}

if (registerId && openingMovement?.id) {
  await downloadPdf(`apertura-pdf-caja-${registerId}`, `/sistema/cash-registers/${registerId}/movements/${openingMovement.id}/pdf`);
}
if (registerId) {
  await downloadPdf(`cierre-pdf-caja-${registerId}`, `/sistema/cash-registers/${registerId}/pdf`);
  await downloadPdf(`admin-report-pdf-caja-${registerId}`, `/sistema/cash-registers/${registerId}/admin-report/pdf`);
}

const report = [
  '# QA visual - documentos de caja',
  '',
  `Base URL: ${baseUrl}`,
  `Fecha: ${new Date().toISOString()}`,
  '',
  '## Evidencia',
  ...evidence.map((item) => `- ${item.viewport} · ${item.screen} · ${path.relative(process.cwd(), item.file)} · overflow: ${item.overflow ? 'sí' : 'no'} · dialogs: ${item.dialogs}`),
  '',
  '## Problemas Nielsen / responsive',
  ...(issues.length
    ? issues.map((issue) => `- ${issue.viewport} · ${issue.screen} · ${issue.heuristic} · ${issue.severity} · ${issue.evidence} · ${issue.solution} · ${issue.status}`)
    : ['- Sin problemas críticos detectados por inspección automatizada de overflow/nombres de botones.']),
  '',
  '## Consola',
  ...(
    consoleErrors.length || networkErrors.length
      ? [...new Set([...consoleErrors, ...networkErrors])].map((message) => `- ${message}`)
      : ['- Sin errores de consola capturados.']
  ),
  '',
].join('\n');

await fs.writeFile(path.join(outputDir, 'qa-report.md'), report);
await fs.writeFile(path.join(outputDir, 'qa-report.json'), JSON.stringify({ evidence, issues, consoleErrors, networkErrors }, null, 2));
await browser.close();

console.log(`QA visual terminado: ${path.join(outputDir, 'qa-report.md')}`);
