const { chromium } = require('@playwright/test');
const fs = require('fs');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('http://136.119.84.22/admin/login', {waitUntil:'domcontentloaded'});
  await page.fill('input[type="email"]','keks@glf.no');
  await page.fill('input[type="password"]','6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  const sidebar = page.locator('aside').first();
  if (await sidebar.count()) {
    for (let i=0;i<20;i++) {
      await sidebar.evaluate(el=>el.scrollBy(0,1000));
      await page.waitForTimeout(120);
    }
  }

  const links = await page.$$eval('a[href]', as => {
    const rows = [];
    const seen = new Set();
    for (const a of as) {
      const href = a.getAttribute('href');
      const text = (a.textContent || '').replace(/\s+/g,' ').trim();
      if (!href) continue;
      const abs = href.startsWith('http') ? href : `http://136.119.84.22${href.startsWith('/') ? '' : '/'}${href}`;
      if (!abs.includes('/admin')) continue;
      if (seen.has(abs)) continue;
      seen.add(abs);
      rows.push({ href: abs, text });
    }
    return rows;
  });

  fs.writeFileSync('audit/_admin_sidebar_links.json', JSON.stringify({count:links.length, links}, null, 2));
  console.log(JSON.stringify({count:links.length}, null, 2));
  await browser.close();
})();
