const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  const links = await page.$$eval('a[href]', as => as.map(a => a.getAttribute('href')).filter(Boolean));
  const filtered = [...new Set(links)].filter(h => h.includes('/admin'));
  console.log(JSON.stringify({ count: filtered.length, sample: filtered.slice(0, 120) }, null, 2));

  await browser.close();
})();
