const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(45000);

  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  const afterLoginUrl = page.url();
  await page.waitForTimeout(1500);

  const anchorCount = await page.locator('a[href]').count();
  const allAdminLinks = await page.$$eval('a[href^="/admin"]', as => as.map(a => ({href:a.getAttribute('href'), text:(a.textContent||'').trim()})));

  console.log(JSON.stringify({ afterLoginUrl, anchorCount, adminLinksSample: allAdminLinks.slice(0,20), adminLinksCount: allAdminLinks.length }, null, 2));
  await page.screenshot({ path:'C:/home/vscode/admin-after-login-links.png', fullPage:true });
  await browser.close();
})();
