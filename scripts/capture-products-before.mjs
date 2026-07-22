import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);
const baseUrl = process.env.QA_BASE_URL || 'http://localhost:8080';
const email = 'qa-before@local.test';
const password = 'password';
const outputDir = path.resolve('storage/app/qa-visual/redesign-final/before');

await fs.mkdir(outputDir, { recursive: true });

await execFileAsync('docker', ['compose', 'exec', '-T', 'app', 'php', 'artisan', 'tinker', '--execute', [
  `App\\Models\\User::updateOrCreate(['email' => '${email}'], [`,
  `'name' => 'QA Before',`,
  `'role' => 'admin',`,
  `'password' => Illuminate\\Support\\Facades\\Hash::make('${password}'),`,
  `'preferences' => ['theme' => 'system', 'high_contrast' => false, 'reduced_motion' => false, 'font_scale' => '100', 'compact_mode' => false, 'sidebar_collapsed' => false, 'locale' => 'es'],`,
  `]);`,
].join(' ')], { cwd: process.cwd() });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();

const loginPage = await context.request.get(`${baseUrl}/login`);
const token = (await loginPage.text()).match(/name="csrf-token" content="([^"]+)"/)?.[1];
await context.request.post(`${baseUrl}/login`, {
  form: { _token: token, email, password },
  headers: { Referer: `${baseUrl}/login` },
});

for (const viewport of [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 390, height: 844 },
]) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await page.goto(`${baseUrl}/sistema/products`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: path.join(outputDir, `products-${viewport.name}.png`), fullPage: true });
}

await browser.close();
console.log(`Before capturado en ${outputDir}`);
