import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);
const baseUrl = process.env.QA_BASE_URL || 'http://localhost:8080';
const adminEmail = process.env.QA_ADMIN_EMAIL || 'qa-theme-admin@local.test';
const cashierEmail = process.env.QA_CASHIER_EMAIL || 'qa-theme-cashier@local.test';
const password = process.env.QA_PASSWORD || 'password';
const themeDir = path.resolve('storage/app/qa-visual/theme-persistence');
const redesignDir = path.resolve('storage/app/qa-visual/redesign-final/after');

const defaultPrefs = {
  theme: 'system',
  high_contrast: false,
  reduced_motion: false,
  font_scale: '100',
  compact_mode: false,
  sidebar_collapsed: false,
  locale: 'es',
};

const evidence = [];
const issues = [];
const consoleMessages = [];
const networkErrors = [];

await fs.mkdir(themeDir, { recursive: true });
await fs.mkdir(redesignDir, { recursive: true });

async function artisanTinker(code) {
  await execFileAsync('docker', ['compose', 'exec', '-T', 'app', 'php', 'artisan', 'tinker', '--execute', code], {
    cwd: process.cwd(),
    maxBuffer: 1024 * 1024,
  });
}

async function ensureUser(email, role, name) {
  const preferences = Buffer.from(JSON.stringify(defaultPrefs)).toString('base64');
  const code = [
    `$prefs = json_decode(base64_decode('${preferences}'), true);`,
    `App\\Models\\User::updateOrCreate(['email' => '${email}'], [`,
    `'name' => '${name}',`,
    `'role' => '${role}',`,
    `'password' => Illuminate\\Support\\Facades\\Hash::make('${password}'),`,
    `'preferences' => $prefs,`,
    `]);`,
  ].join(' ');

  await artisanTinker(code);
}

async function setPreferences(email, preferences) {
  const payload = Buffer.from(JSON.stringify({ ...defaultPrefs, ...preferences })).toString('base64');
  const code = [
    `$prefs = json_decode(base64_decode('${payload}'), true);`,
    `App\\Models\\User::where('email', '${email}')->firstOrFail()->forceFill(['preferences' => $prefs])->save();`,
  ].join(' ');

  await artisanTinker(code);
}

async function login(context, page, email) {
  await context.clearCookies();
  const loginPage = await context.request.get(`${baseUrl}/login`);
  const token = (await loginPage.text()).match(/name="csrf-token" content="([^"]+)"/)?.[1];

  if (!token) {
    throw new Error('No se encontro token CSRF en login.');
  }

  await context.request.post(`${baseUrl}/login`, {
    form: { _token: token, email, password, remember: 'on' },
    headers: { Referer: `${baseUrl}/login` },
  });

  await page.goto(`${baseUrl}/sistema/dashboard`, { waitUntil: 'networkidle' });

  if (page.url().includes('/login')) {
    throw new Error(`No se pudo iniciar sesion con ${email}.`);
  }
}

async function logout(context) {
  const csrf = await context.request.get(`${baseUrl}/sistema/profile`);
  const token = (await csrf.text()).match(/name="csrf-token" content="([^"]+)"/)?.[1];

  if (token) {
    await context.request.post(`${baseUrl}/logout`, {
      form: { _token: token },
      headers: { Referer: `${baseUrl}/sistema/profile` },
    });
  }

  await context.clearCookies();
}

async function dataset(page) {
  return page.evaluate(() => ({
    theme: document.documentElement.dataset.theme,
    effective: document.documentElement.dataset.themeEffective,
    contrast: document.documentElement.dataset.contrast,
    lang: document.documentElement.lang,
    width: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));
}

async function capture(page, dir, file, fullPage = true) {
  const target = path.join(dir, file);
  await page.screenshot({ path: target, fullPage });
  return path.relative(process.cwd(), target);
}

async function assertTheme(page, expectedTheme, expectedEffective, step) {
  const state = await dataset(page);
  const ok = state.theme === expectedTheme && state.effective === expectedEffective;

  evidence.push({
    area: 'theme-persistence',
    step,
    theme: state.theme,
    effective: state.effective,
    expected: `${expectedTheme}/${expectedEffective}`,
    status: ok ? 'OK' : 'ERROR',
  });

  if (!ok) {
    issues.push(`${step}: se esperaba ${expectedTheme}/${expectedEffective}, se obtuvo ${state.theme}/${state.effective}`);
  }
}

async function clickNav(page, hrefSuffix, label) {
  const locator = page.locator(`a[href$="${hrefSuffix}"]`).first();
  await locator.click();
  await page.waitForLoadState('networkidle');
  await assertTheme(page, currentExpected.theme, currentExpected.effective, `Inertia ${label}`);
}

let currentExpected = { theme: 'system', effective: 'light' };

async function selectThemeViaProfile(page, theme, effective) {
  currentExpected = { theme, effective };
  await page.goto(`${baseUrl}/sistema/profile`, { waitUntil: 'networkidle' });
  const input = page.locator(`input[name="theme"][value="${theme}"]`);
  await input.check({ force: true });
  await page.waitForTimeout(800);
  await assertTheme(page, theme, effective, `Perfil seleccion ${theme}`);
}

async function runPersistenceFlow(context, page, theme, effective) {
  await selectThemeViaProfile(page, theme, effective);
  await clickNav(page, '/sistema/products', 'Productos');
  await clickNav(page, '/sistema/sales', 'Ventas');
  await clickNav(page, '/sistema/cash-registers', 'Caja');
  await clickNav(page, '/sistema/expenses', 'Gastos');
  await page.goto(`${baseUrl}/sistema/products`, { waitUntil: 'networkidle' });
  await assertTheme(page, theme, effective, `Recarga Productos ${theme}`);
  await capture(page, themeDir, `products-${theme}.png`);
  await logout(context);
  await login(context, page, adminEmail);
  await page.goto(`${baseUrl}/sistema/products`, { waitUntil: 'networkidle' });
  await assertTheme(page, theme, effective, `Logout login ${theme}`);
}

async function inspectVisual(page, label) {
  const state = await dataset(page);
  const overflow = state.scrollWidth > state.width + 2;

  evidence.push({
    area: 'redesign-final',
    step: label,
    theme: state.theme,
    effective: state.effective,
    width: state.width,
    scrollWidth: state.scrollWidth,
    status: overflow ? 'WARNING' : 'OK',
  });

  if (overflow) {
    issues.push(`${label}: overflow horizontal ${state.scrollWidth}px sobre ${state.width}px`);
  }
}

await ensureUser(adminEmail, 'admin', 'QA Tema Admin');
await ensureUser(cashierEmail, 'cajero', 'QA Tema Cajero');

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ colorScheme: 'light', viewport: { width: 1440, height: 900 } });
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

await login(context, page, adminEmail);
await runPersistenceFlow(context, page, 'light', 'light');
await runPersistenceFlow(context, page, 'dark', 'dark');

await page.emulateMedia({ colorScheme: 'light' });
await selectThemeViaProfile(page, 'system', 'light');
await page.emulateMedia({ colorScheme: 'dark' });
await page.waitForTimeout(300);
await assertTheme(page, 'system', 'dark', 'Sistema responde a OS oscuro');
await page.emulateMedia({ colorScheme: 'light' });
await page.waitForTimeout(300);
await assertTheme(page, 'system', 'light', 'Sistema responde a OS claro');
await page.emulateMedia({ colorScheme: 'dark' });
await selectThemeViaProfile(page, 'light', 'light');
await assertTheme(page, 'light', 'light', 'Claro ignora OS oscuro');
await selectThemeViaProfile(page, 'dark', 'dark');
await page.emulateMedia({ colorScheme: 'light' });
await page.waitForTimeout(300);
await assertTheme(page, 'dark', 'dark', 'Oscuro ignora OS claro');

for (const theme of ['light', 'dark', 'high_contrast', 'color_accessible']) {
  await selectThemeViaProfile(page, theme, theme);
  await page.goto(`${baseUrl}/sistema/products`, { waitUntil: 'networkidle' });
  await capture(page, themeDir, `products-${theme}.png`);
}

const adminScreens = [
  { key: 'products', url: '/sistema/products' },
  { key: 'pos', url: '/sistema/pos' },
  { key: 'dashboard-admin', url: '/sistema/dashboard' },
  { key: 'cash-registers', url: '/sistema/cash-registers' },
  { key: 'expenses', url: '/sistema/expenses' },
  { key: 'sales', url: '/sistema/sales' },
  { key: 'customers', url: '/sistema/customers' },
  { key: 'categories', url: '/sistema/categories' },
  { key: 'unit-types', url: '/sistema/unit-types' },
  { key: 'suppliers', url: '/sistema/suppliers' },
  { key: 'employees', url: '/sistema/employees' },
  { key: 'reports', url: '/sistema/reports' },
  { key: 'settings', url: '/sistema/settings' },
  { key: 'profile', url: '/sistema/profile' },
];
const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 1024, height: 768 },
  { name: 'mobile', width: 390, height: 844 },
];

for (const theme of ['light', 'dark']) {
  await setPreferences(adminEmail, { theme });
  await login(context, page, adminEmail);

  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });

    for (const screen of adminScreens) {
      await page.goto(`${baseUrl}${screen.url}`, { waitUntil: 'networkidle' });
      await page.waitForTimeout(250);
      await inspectVisual(page, `${screen.key} ${theme} ${viewport.name}`);
      await capture(page, redesignDir, `${screen.key}-${theme}-${viewport.name}.png`);
    }
  }
}

for (const theme of ['light', 'dark']) {
  await setPreferences(cashierEmail, { theme });
  await login(context, page, cashierEmail);

  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto(`${baseUrl}/sistema/dashboard`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(250);
    await inspectVisual(page, `dashboard-cashier ${theme} ${viewport.name}`);
    await capture(page, redesignDir, `dashboard-cashier-${theme}-${viewport.name}.png`);
  }
}

await browser.close();

const report = [
  '# QA visual - theme persistence y redesign final',
  '',
  `Base URL: ${baseUrl}`,
  `Fecha: ${new Date().toISOString()}`,
  '',
  '## Evidencia',
  ...evidence.map((item) => `- ${item.status} · ${item.area} · ${item.step} · theme=${item.theme} · effective=${item.effective}`),
  '',
  '## Capturas clave',
  `- Theme persistence: ${path.relative(process.cwd(), themeDir)}`,
  `- Redesign final after: ${path.relative(process.cwd(), redesignDir)}`,
  '',
  '## Problemas',
  ...(issues.length ? issues.map((issue) => `- ${issue}`) : ['- Sin errores criticos detectados.']),
  '',
  '## Consola y red',
  ...(
    consoleMessages.length || networkErrors.length
      ? [...new Set([...consoleMessages, ...networkErrors])].map((message) => `- ${message}`)
      : ['- Sin errores de consola ni respuestas HTTP >= 400.']
  ),
  '',
].join('\n');

await fs.writeFile(path.join(themeDir, 'qa-report.md'), report);
await fs.writeFile(path.join(themeDir, 'qa-report.json'), JSON.stringify({ evidence, issues, consoleMessages, networkErrors }, null, 2));
await fs.writeFile(path.join(redesignDir, 'qa-report.md'), report);

console.log(`QA terminado: ${path.join(themeDir, 'qa-report.md')}`);
