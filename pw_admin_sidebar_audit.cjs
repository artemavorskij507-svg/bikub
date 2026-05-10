const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  // Expand navigation by scrolling sidebar to load all links
  for (let i = 0; i < 8; i++) {
    await page.mouse.wheel(0, 1200);
    await page.waitForTimeout(300);
  }

  const links = await page.$$eval('a[href^="/admin"]', as => {
    const unique = new Map();
    for (const a of as) {
      const href = a.getAttribute('href');
      const text = (a.textContent || '').trim();
      if (href && !unique.has(href)) unique.set(href, text);
    }
    return Array.from(unique.entries()).map(([href, text]) => ({ href, text }));
  });

  const results = [];
  for (const item of links) {
    const u = 'http://136.119.84.22' + item.href;
    try {
      const r = await page.goto(u, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(700);
      const t = await page.title();
      const body = (await page.locator('body').innerText()).toLowerCase();
      const has500 = body.includes('server error') || body.includes('whoops') || body.includes('status code: 500');
      const denied = body.includes('access denied');
      results.push({ href: item.href, nav_text: item.text, status: r ? r.status() : null, title: t, has500, denied });
    } catch (e) {
      results.push({ href: item.href, nav_text: item.text, status: 'ERR', title: '', has500: true, denied: false, err: String(e.message || e) });
    }
  }

  const bad = results.filter(x => x.status === 'ERR' || x.status >= 500 || x.has500 || x.denied);
  console.log(JSON.stringify({ total_links: links.length, checked: results.length, bad_count: bad.length, bad }, null, 2));
  await browser.close();
})();
