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

  const bad = [];
  for (const u of adminLinks) {
    try {
      const r = await page.goto(u, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(250);
      const status = r ? r.status() : null;
      const body = (await page.locator('body').innerText()).toLowerCase();
      const denied = body.includes('access denied');
      if ((typeof status === 'number' && status >= 500) || denied) {
        bad.push({ url: u, status, title: await page.title(), denied });
      }
    } catch (e) {
      bad.push({ url: u, status: 'ERR', title: '', denied: false, error: String(e.message || e) });
    }
  }

  console.log(JSON.stringify({ checked: adminLinks.length, critical_bad_count: bad.length, critical_bad: bad }, null, 2));
  await browser.close();
})();
