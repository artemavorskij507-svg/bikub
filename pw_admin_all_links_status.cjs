const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  const links = await page.$$eval('a[href]', as => as.map(a => a.getAttribute('href')).filter(Boolean));
  const adminLinks = [...new Set(links)].filter(h => h.includes('/admin'));

  const results = [];
  for (const u of adminLinks) {
    try {
      const r = await page.goto(u, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(400);
      const text = (await page.locator('body').innerText()).toLowerCase();
      results.push({
        url: u,
        status: r ? r.status() : null,
        title: await page.title(),
        has500: text.includes('server error') || text.includes('whoops'),
        hasDenied: text.includes('access denied')
      });
    } catch (e) {
      results.push({ url: u, status: 'ERR', title: '', has500: true, hasDenied: false, error: String(e.message || e) });
    }
  }

  const bad = results.filter(x => x.status === 'ERR' || (typeof x.status === 'number' && x.status >= 400) || x.has500 || x.hasDenied);
  console.log(JSON.stringify({ total: results.length, bad_count: bad.length, bad }, null, 2));
  await browser.close();
})();
