const { chromium } = require('@playwright/test');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(45000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);

  const scrollers = [
    '[data-filament-sidebar-nav]','aside nav','aside'
  ];
  for (const s of scrollers) {
    const loc = page.locator(s).first();
    if (await loc.count()) {
      for (let i = 0; i < 20; i++) {
        await loc.evaluate((el) => { el.scrollBy(0, 800); });
        await page.waitForTimeout(120);
      }
    }
  }

  const links = await page.$$eval('a[href^="/admin"]', as => {
    const map = new Map();
    for (const a of as) {
      const href = a.getAttribute('href');
      const text = (a.textContent || '').replace(/\s+/g, ' ').trim();
      if (!href) continue;
      if (!map.has(href)) map.set(href, text);
      else if (!map.get(href) && text) map.set(href, text);
    }
    return Array.from(map.entries()).map(([href, text]) => ({ href, nav_text: text || null }));
  });

  const results = [];
  for (const l of links) {
    const url = `http://136.119.84.22${l.href}`;
    try {
      const r = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
      await page.waitForTimeout(450);
      const title = await page.title();
      const body = (await page.locator('body').innerText()).toLowerCase();
      const has500 = body.includes('server error') || body.includes('status code: 500') || body.includes('whoops');
      const denied = body.includes('access denied');
      const hasTable = await page.locator('table, [role="table"], .fi-ta-table').count();
      const hasForm = await page.locator('form').count();
      const hasCreateBtn = await page.locator('a,button').filter({ hasText: /create|new|додати|добавить/i }).count();
      results.push({
        href: l.href,
        nav_text: l.nav_text,
        status: r ? r.status() : null,
        title,
        has500,
        denied,
        hasTable: hasTable > 0,
        hasForm: hasForm > 0,
        hasCreateBtn: hasCreateBtn > 0
      });
    } catch (e) {
      results.push({ href: l.href, nav_text: l.nav_text, status: 'ERR', title: '', has500: true, denied: false, error: String(e.message || e) });
    }
  }

  const bad = results.filter(x => x.status === 'ERR' || (typeof x.status === 'number' && x.status >= 500) || x.has500 || x.denied);
  const out = { checked: results.length, bad_count: bad.length, bad, results };
  fs.writeFileSync('audit/_admin_links_full_status.json', JSON.stringify(out, null, 2));
  console.log(JSON.stringify({ checked: results.length, bad_count: bad.length }, null, 2));
  await browser.close();
})();
