import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const baseUrl = process.env.QA_BASE_URL || 'http://localhost:8080';
const qaEmail = process.env.QA_EMAIL || 'qa-admin@local.test';
const qaPassword = process.env.QA_PASSWORD || 'password';
const outputDir = path.resolve('storage/app/qa-visual/micro-admin-final');
const screenshotsDir = path.join(outputDir, 'screenshots');
const execFileAsync = promisify(execFile);

const viewports = [
  { name: 'desktop-es-light', width: 1440, height: 900, locale: 'es', theme: 'light', font_scale: '100' },
  { name: 'desktop-en-dark', width: 1440, height: 900, locale: 'en', theme: 'dark', font_scale: '100' },
  { name: 'mobile-es-light', width: 390, height: 844, locale: 'es', theme: 'light', font_scale: '100' },
  { name: 'tablet-en-contrast-font150', width: 768, height: 1024, locale: 'en', theme: 'high_contrast', font_scale: '150' },
];

const screens = [
  { key: 'customers', url: '/sistema/customers', create: { es: 'Crear cliente', en: 'Create customer' } },
  { key: 'categories', url: '/sistema/categories', create: { es: 'Crear categoría', en: 'Create category' } },
  { key: 'unit-types', url: '/sistema/unit-types', create: { es: 'Crear unidad', en: 'Create unit' } },
  { key: 'suppliers', url: '/sistema/suppliers', create: { es: 'Crear proveedor', en: 'Create supplier' } },
  { key: 'employees', url: '/sistema/employees', create: { es: 'Crear cajero', en: 'Create cashier' } },
  { key: 'salaries', url: '/sistema/salaries', create: { es: 'Registrar pago', en: 'Register payment' } },
  { key: 'profile', url: '/sistema/profile' },
];

const evidence = [];
const issues = [];
const consoleMessages = [];
const networkErrors = [];

await fs.mkdir(screenshotsDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();

page.on('console', (message) => {
  if (['error', 'warning'].includes(message.type())) {
    consoleMessages.push(`${message.type()}: ${message.text()}`);
  }
});

page.on('response', (response) => {
  if (response.status() >= 400) {
    networkErrors.push(`${response.status()}: ${response.url()}`);
  }
});

async function login() {
  const loginPage = await context.request.get(`${baseUrl}/login`);
  const html = await loginPage.text();
  const token = html.match(/name="csrf-token" content="([^"]+)"/)?.[1];

  if (!token) {
    throw new Error('No se encontro token CSRF en login.');
  }

  await context.request.post(`${baseUrl}/login`, {
    form: {
      _token: token,
      email: qaEmail,
      password: qaPassword,
      remember: 'on',
    },
    headers: {
      Referer: `${baseUrl}/login`,
    },
  });

  await page.goto(`${baseUrl}/sistema/dashboard`, { waitUntil: 'networkidle' });
  if (page.url().includes('/login')) {
    throw new Error(`No se pudo iniciar sesion con ${qaEmail}.`);
  }
}

async function ensureQaUser() {
  const code = [
    `App\\Models\\User::updateOrCreate(['email' => '${qaEmail}'], [`,
    `'name' => 'QA Admin',`,
    `'role' => 'admin',`,
    `'password' => Illuminate\\Support\\Facades\\Hash::make('${qaPassword}'),`,
    `]);`,
  ].join(' ');

  await execFileAsync('docker', ['compose', 'exec', '-T', 'app', 'php', 'artisan', 'tinker', '--execute', code], {
    cwd: process.cwd(),
  });
}

async function setPreferences(preferences) {
  const payload = Buffer.from(JSON.stringify({
    theme: preferences.theme,
    high_contrast: false,
    reduced_motion: false,
    font_scale: preferences.font_scale,
    compact_mode: false,
    sidebar_collapsed: false,
    locale: preferences.locale,
  })).toString('base64');
  const code = [
    `$prefs = json_decode(base64_decode('${payload}'), true);`,
    `App\\Models\\User::where('email', '${qaEmail}')->firstOrFail()->forceFill(['preferences' => $prefs])->save();`,
  ].join(' ');

  await execFileAsync('docker', ['compose', 'exec', '-T', 'app', 'php', 'artisan', 'tinker', '--execute', code], {
    cwd: process.cwd(),
  });
}

async function inspect(screen, viewport, modal = false) {
  const metrics = await page.evaluate(() => ({
    width: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    unnamedIconButtons: Array.from(document.querySelectorAll('button')).filter((button) => {
      const hasIcon = Boolean(button.querySelector('i, svg'));
      const hasName = Boolean(button.getAttribute('aria-label') || button.textContent.trim() || button.getAttribute('title'));
      return hasIcon && !hasName;
    }).length,
    dialogs: document.querySelectorAll('[role="dialog"][aria-modal="true"]').length,
    lang: document.documentElement.lang,
    theme: document.documentElement.dataset.theme,
    fontScale: document.documentElement.dataset.fontScale,
  }));
  const overflow = metrics.scrollWidth > metrics.width + 2;

  if (overflow) {
    issues.push({
      screen: `${screen.key}${modal ? '-modal' : ''}`,
      viewport: viewport.name,
      severity: 'media',
      evidence: `scrollWidth ${metrics.scrollWidth}px > viewport ${metrics.width}px`,
      solution: 'Revisar ancho de filtros, tablas o footer de modal en este breakpoint.',
      status: 'pendiente',
    });
  }

  if (metrics.unnamedIconButtons > 0) {
    issues.push({
      screen: `${screen.key}${modal ? '-modal' : ''}`,
      viewport: viewport.name,
      severity: 'media',
      evidence: `${metrics.unnamedIconButtons} boton(es) con icono sin nombre accesible.`,
      solution: 'Agregar texto, title o aria-label al boton.',
      status: 'pendiente',
    });
  }

  if (metrics.lang !== viewport.locale || metrics.theme !== viewport.theme || metrics.fontScale !== viewport.font_scale) {
    issues.push({
      screen: `${screen.key}${modal ? '-modal' : ''}`,
      viewport: viewport.name,
      severity: 'media',
      evidence: `lang/theme/font = ${metrics.lang}/${metrics.theme}/${metrics.fontScale}`,
      solution: 'Revisar persistencia de preferencias de usuario.',
      status: 'pendiente',
    });
  }

  return { ...metrics, overflow };
}

async function capture(screen, viewport, modal = false) {
  const suffix = modal ? 'modal' : 'index';
  const file = path.join(screenshotsDir, `${viewport.name}-${screen.key}-${suffix}.png`);
  await page.screenshot({ path: file, fullPage: true });
  const metrics = await inspect(screen, viewport, modal);
  evidence.push({
    screen: screen.key,
    state: suffix,
    viewport: viewport.name,
    file: path.relative(process.cwd(), file),
    overflow: metrics.overflow,
    dialogs: metrics.dialogs,
    lang: metrics.lang,
    theme: metrics.theme,
    fontScale: metrics.fontScale,
  });
}

await ensureQaUser();
await login();

for (const viewport of viewports) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await setPreferences(viewport);

  for (const screen of screens) {
    await page.goto(`${baseUrl}${screen.url}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(250);
    await capture(screen, viewport);

    const createLabel = screen.create?.[viewport.locale];
    if (!createLabel) continue;

    const createButton = page.getByRole('button', { name: createLabel });
    if (await createButton.count()) {
      await createButton.first().click();
      await page.waitForTimeout(250);
      await capture(screen, viewport, true);
      await page.keyboard.press('Escape');
    } else {
      issues.push({
        screen: screen.key,
        viewport: viewport.name,
        severity: 'baja',
        evidence: `No se encontro boton "${createLabel}".`,
        solution: 'Validar traduccion o visibilidad de la accion principal.',
        status: 'pendiente',
      });
    }
  }
}

await browser.close();

const report = [
  '# QA visual - micro admin final',
  '',
  `Base URL: ${baseUrl}`,
  `Fecha: ${new Date().toISOString()}`,
  '',
  '## Evidencia',
  ...evidence.map((item) => `- ${item.viewport} · ${item.screen} · ${item.state} · ${item.file} · lang=${item.lang} · theme=${item.theme} · font=${item.fontScale} · overflow=${item.overflow ? 'si' : 'no'} · dialogs=${item.dialogs}`),
  '',
  '## Problemas detectados',
  ...(issues.length
    ? issues.map((issue) => `- ${issue.viewport} · ${issue.screen} · ${issue.severity} · ${issue.evidence} · ${issue.solution}`)
    : ['- Sin problemas criticos detectados por inspeccion automatizada.']),
  '',
  '## Consola y red',
  ...(
    consoleMessages.length || networkErrors.length
      ? [...new Set([...consoleMessages, ...networkErrors])].map((message) => `- ${message}`)
      : ['- Sin errores de consola ni respuestas HTTP >= 400.']
  ),
  '',
].join('\n');

await fs.writeFile(path.join(outputDir, 'qa-report.md'), report);
await fs.writeFile(path.join(outputDir, 'qa-report.json'), JSON.stringify({ evidence, issues, consoleMessages, networkErrors }, null, 2));

console.log(`QA visual terminado: ${path.join(outputDir, 'qa-report.md')}`);
