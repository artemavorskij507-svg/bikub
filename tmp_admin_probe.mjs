import { chromium } from 'playwright';
import fs from 'fs';

const base = 'https://136.119.84.22';
const loginPath = '/admin/login';
const candidates = [
  { email: 'keks@glf.no', password: '6636' },
  { email: 'keks@gfl.no', password: '6636' },
];

const paths = [
  '/admin',
  '/admin/command-center-a-i-agent-team-chat',
  '/admin/operations-core','/admin/service-jobs','/admin/live-operations-map','/admin/operation-exceptions','/admin/executor-shifts','/admin/executor-breaks','/admin/dispatch-rule-sets','/admin/dispatch-rule-preview',
  '/admin/errand-tasks','/admin/restaurants','/admin/retail-stores','/admin/delivery/delivery-orders','/admin/orders','/admin/errand-order-details','/admin/delivery-zones',
  '/admin/moving/executor-profiles','/admin/moving/moving-items','/admin/moving/moving-orders','/admin/moving/teams','/admin/moving/moving-order-photos','/admin/moving/moving-order-tasks',
  '/admin/claims','/admin/handyman-assignments','/admin/handyman-materials-entries','/admin/repair-projects','/admin/repair-stages','/admin/repair-team-members','/admin/work-warranties',
  '/admin/roadside-dashboard','/admin/roadside-dispatch-board','/admin/roadside-presets','/admin/road-helper-profiles','/admin/vehicle-inspection-requests','/admin/roadside-partners','/admin/vehicle-inspection-presets','/admin/roadside-emergencies',
  '/admin/disposal-items','/admin/disposal-partners','/admin/eco-certificates','/admin/eco-teams','/admin/eco-disposal-dashboard',
  '/admin/analitika-social-care','/admin/community-points-balances','/admin/care-plans','/admin/social-helper-profiles','/admin/pultkoordinatora-social-care','/admin/social-care-orders','/admin/care-services',
];

async function tryLogin(page, email, password) {
  const target = `${base}${loginPath}`;
  await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 120000 });

  await page.fill('input[name="email"]', email).catch(() => {});
  await page.fill('input[name="password"]', password).catch(() => {});

  const submit = page.locator('button[type="submit"]');
  if (await submit.count()) {
    await Promise.all([
      page.waitForLoadState('domcontentloaded', { timeout: 120000 }).catch(() => {}),
      submit.first().click(),
    ]);
  } else {
    await page.keyboard.press('Enter');
    await page.waitForLoadState('domcontentloaded', { timeout: 120000 }).catch(() => {});
  }

  await page.waitForTimeout(1500);
  const url = page.url();
  const html = await page.content();
  const loginStill = url.includes('/admin/login') || /These credentials|invalid|невірн|неверн|incorrect|failed/i.test(html);
  return { ok: !loginStill, url };
}

const browser = await chromium.launch({ headless: true, args: ['--ignore-certificate-errors'] });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();
page.setDefaultTimeout(120000);

const report = {
  generated_at: new Date().toISOString(),
  base,
  login_attempts: [],
  login_success: null,
  checks: [],
};

let logged = false;
for (const cred of candidates) {
  try {
    const result = await tryLogin(page, cred.email, cred.password);
    report.login_attempts.push({ email: cred.email, ...result });
    if (result.ok) {
      logged = true;
      report.login_success = cred.email;
      break;
    }
  } catch (e) {
    report.login_attempts.push({ email: cred.email, ok: false, reason: String(e?.message || e) });
  }
}

if (logged) {
  for (const path of paths) {
    const url = `${base}${path}`;
    const row = { path, status: null, location: null, final_url: null, server_error_text: false, access_denied_text: false, exception: null };
    try {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });
      await page.waitForTimeout(800);
      const html = await page.content();
      row.status = response ? response.status() : null;
      row.location = response ? response.headers()['location'] ?? null : null;
      row.final_url = page.url();
      row.server_error_text = /Server Error|Ошибка сервера|500/i.test(html);
      row.access_denied_text = /Access denied|доступ запрещен|forbidden|403/i.test(html);
    } catch (e) {
      row.exception = String(e?.message || e);
    }
    report.checks.push(row);
  }
} else {
  report.error = 'login_failed_all_candidates';
}

const out = 'C:/home/vscode/bikube/audit/_admin_domain_modules_live_probe_20260422.json';
fs.writeFileSync(out, JSON.stringify(report, null, 2));
console.log(out);

await browser.close();
