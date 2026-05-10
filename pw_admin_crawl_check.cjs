const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  // Collect admin links from the current page and a few navigation interactions.
  const collected = new Set();
  const collectLinks = async () => {
    const hrefs = await page.$$eval('a[href]', els => els.map(e => e.getAttribute('href')).filter(Boolean));
    for (const h of hrefs) {
      if (h.startsWith('/admin')) collected.add(h);
    }
  };

  await collectLinks();
  for (const p of ['/admin', '/admin/live-operations-map', '/admin/service-jobs', '/admin/operation-exceptions']) {
    await page.goto(`http://136.119.84.22${p}`, { waitUntil: 'domcontentloaded' }).catch(() => null);
    await page.waitForTimeout(500);
    await collectLinks();
  }

  const links = Array.from(collected).slice(0, 120);
  const results = [];

  for (const path of links) {
    const url = `http://136.119.84.22${path}`;
    let status = null;
    try {
      const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
      status = resp ? resp.status() : null;
      const text = (await page.locator('body').innerText()).toLowerCase();
      const title = await page.title();
      results.push({ path, status, title, has500: text.includes('server error') || text.includes('whoops') });
    } catch (e) {
      results.push({ path, status: 'ERR', title: '', has500: true, error: String(e.message || e) });
    }
  }

  const bad = results.filter(r => r.status >= 500 || r.has500 || r.status === 'ERR');
  console.log(JSON.stringify({ checked: results.length, bad_count: bad.length, bad: bad.slice(0, 40) }, null, 2));
  await browser.close();
})();
