const { chromium } = require('@playwright/test');

async function testLogin(email, password) {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(25000);
  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]')
  ]);
  const url = page.url();
  const title = await page.title();
  const body = (await page.locator('body').innerText()).toLowerCase();
  const ok = url.includes('/admin') && !url.includes('/admin/login') && !body.includes('access denied');
  await browser.close();
  return { email, ok, url, title };
}

(async () => {
  const urls = [
    'http://136.119.84.22/',
    'http://136.119.84.22/category/delivery',
    'http://136.119.84.22/category/moving',
    'http://136.119.84.22/category/handyman',
    'http://136.119.84.22/category/eco',
    'http://136.119.84.22/category/personal-task',
    'http://136.119.84.22/category/tow',
    'http://136.119.84.22/classifieds',
    'http://136.119.84.22/account',
    'http://136.119.84.22/lk',
    'http://136.119.84.22/admin',
    'http://136.119.84.22/admin/login'
  ];

  const checks = [];
  for (const u of urls) {
    const res = await fetch(u, { redirect: 'manual' });
    checks.push({ url: u, status: res.status, location: res.headers.get('location') });
  }

  const loginGfl = await testLogin('keks@gfl.no', '6636');
  const loginGlf = await testLogin('keks@glf.no', '6636');

  console.log(JSON.stringify({ checks, loginGfl, loginGlf }, null, 2));
})();
