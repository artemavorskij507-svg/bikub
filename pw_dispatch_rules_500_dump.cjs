const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('http://136.119.84.22/admin/login');
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);
  const resp = await page.goto('http://136.119.84.22/admin/dispatch-rule-sets', { waitUntil: 'domcontentloaded' });
  const html = await page.content();
  console.log('status=', resp ? resp.status() : null);
  console.log(html.substring(0, 1200));
  await browser.close();
})();
