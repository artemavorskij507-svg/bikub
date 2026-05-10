const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);

  const url = page.url();
  const title = await page.title();
  const body = await page.content();
  const hasAccessDenied = body.toLowerCase().includes('access denied');
  const hasDashboard = body.toLowerCase().includes('dashboard') || body.toLowerCase().includes('информационная панель');

  console.log(JSON.stringify({ url, title, hasAccessDenied, hasDashboard }, null, 2));
  await page.screenshot({ path: 'C:/home/vscode/admin-login-check.png', fullPage: true });
  await browser.close();
})();
