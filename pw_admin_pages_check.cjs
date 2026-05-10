const { chromium } = require('@playwright/test');

(async () => {
  const pagesToCheck = [
    '/admin',
    '/admin/service-jobs',
    '/admin/operation-exceptions',
    '/admin/live-operations-map',
    '/admin/unified-operations-core',
    '/admin/dispatch-rule-sets',
    '/admin/dispatch-rule-preview'
  ];

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

  const results = [];
  for (const p of pagesToCheck) {
    const url = `http://136.119.84.22${p}`;
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1200);
    const content = await page.content();
    const title = await page.title();
    const text = (await page.locator('body').innerText()).toLowerCase();
    results.push({
      path: p,
      status: resp ? resp.status() : null,
      title,
      has500Text: text.includes('server error') || text.includes('500') || content.toLowerCase().includes('whoops'),
      hasAccessDenied: text.includes('access denied')
    });
  }

  console.log(JSON.stringify(results, null, 2));
  await page.screenshot({ path: 'C:/home/vscode/admin-pages-check.png', fullPage: true });
  await browser.close();
})();
