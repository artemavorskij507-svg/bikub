const { chromium } = require('@playwright/test');
const fs = require('fs');

const groups = {
  operations: [
    'http://136.119.84.22/admin/operations-core',
    'http://136.119.84.22/admin/service-jobs',
    'http://136.119.84.22/admin/live-operations-map',
    'http://136.119.84.22/admin/operation-exceptions',
    'http://136.119.84.22/admin/executor-shifts',
    'http://136.119.84.22/admin/executor-breaks',
    'http://136.119.84.22/admin/dispatch-rule-sets',
    'http://136.119.84.22/admin/dispatch-rule-preview'
  ],
  delivery: [
    'http://136.119.84.22/admin/errand-tasks',
    'http://136.119.84.22/admin/restaurants',
    'http://136.119.84.22/admin/retail-stores',
    'http://136.119.84.22/admin/delivery/delivery-orders',
    'http://136.119.84.22/admin/orders',
    'http://136.119.84.22/admin/errand-order-details',
    'http://136.119.84.22/admin/delivery-zones'
  ],
  moving: [
    'http://136.119.84.22/admin/moving/executor-profiles',
    'http://136.119.84.22/admin/moving/moving-items',
    'http://136.119.84.22/admin/moving/moving-orders',
    'http://136.119.84.22/admin/moving/teams',
    'http://136.119.84.22/admin/moving/moving-order-photos',
    'http://136.119.84.22/admin/moving/moving-order-tasks'
  ],
  handyman: [
    'http://136.119.84.22/admin/claims',
    'http://136.119.84.22/admin/handyman-assignments',
    'http://136.119.84.22/admin/handyman-materials-entries',
    'http://136.119.84.22/admin/repair-projects',
    'http://136.119.84.22/admin/repair-stages',
    'http://136.119.84.22/admin/repair-team-members',
    'http://136.119.84.22/admin/work-warranties'
  ],
  roadside: [
    'http://136.119.84.22/admin/roadside-dashboard',
    'http://136.119.84.22/admin/roadside-dispatch-board',
    'http://136.119.84.22/admin/roadside-presets',
    'http://136.119.84.22/admin/road-helper-profiles',
    'http://136.119.84.22/admin/vehicle-inspection-requests',
    'http://136.119.84.22/admin/roadside-partners',
    'http://136.119.84.22/admin/vehicle-inspection-presets',
    'http://136.119.84.22/admin/roadside-emergencies'
  ],
  eco: [
    'http://136.119.84.22/admin/disposal-items',
    'http://136.119.84.22/admin/disposal-partners',
    'http://136.119.84.22/admin/eco-certificates',
    'http://136.119.84.22/admin/eco-teams',
    'http://136.119.84.22/admin/eco-disposal-dashboard'
  ],
  social_care: [
    'http://136.119.84.22/admin/analitika-social-care',
    'http://136.119.84.22/admin/community-points-balances',
    'http://136.119.84.22/admin/care-plans',
    'http://136.119.84.22/admin/social-helper-profiles',
    'http://136.119.84.22/admin/pultkoordinatora-social-care',
    'http://136.119.84.22/admin/social-care-orders',
    'http://136.119.84.22/admin/care-services'
  ]
};

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.setDefaultTimeout(60000);
  await page.goto('http://136.119.84.22/admin/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'keks@glf.no');
  await page.fill('input[type="password"]', '6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);

  const rows = [];
  for (const [group, urls] of Object.entries(groups)) {
    for (const url of urls) {
      const row = { group, url };
      try {
        const r = await page.goto(url, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(500);
        const body = (await page.locator('body').innerText()).toLowerCase();
        row.status = r ? r.status() : null;
        row.title = await page.title();
        row.has500 = body.includes('status code: 500') || body.includes('whoops') || (body.includes('server error') && row.status >= 500);
        row.denied = body.includes('access denied');
        row.hasTable = (await page.locator('table, [role="table"], .fi-ta-table').count()) > 0;
        row.hasForm = (await page.locator('form').count()) > 0;

        const createSelectors = [
          'a:has-text("Create")',
          'a:has-text("New")',
          'button:has-text("Create")',
          'button:has-text("New")',
          'a:has-text("Додати")',
          'button:has-text("Додати")',
          'a:has-text("Добавить")',
          'button:has-text("Добавить")'
        ];

        let createNav = null;
        for (const sel of createSelectors) {
          const loc = page.locator(sel).first();
          if (await loc.count()) {
            const href = await loc.getAttribute('href');
            if (href) { createNav = href.startsWith('http') ? href : `http://136.119.84.22${href}`; break; }
          }
        }

        if (createNav) {
          try {
            const cr = await page.goto(createNav, { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(300);
            const cbody = (await page.locator('body').innerText()).toLowerCase();
            row.createUrl = createNav;
            row.createStatus = cr ? cr.status() : null;
            row.createHas500 = cbody.includes('status code: 500') || cbody.includes('whoops') || (cbody.includes('server error') && row.createStatus >= 500);
            row.createDenied = cbody.includes('access denied');
            row.createHasForm = (await page.locator('form').count()) > 0;
          } catch (e) {
            row.createUrl = createNav;
            row.createStatus = 'ERR';
            row.createError = String(e.message || e);
          }
        }
      } catch (e) {
        row.status = 'ERR';
        row.error = String(e.message || e);
      }
      rows.push(row);
    }
  }

  const summary = {};
  for (const g of Object.keys(groups)) {
    const items = rows.filter(r => r.group === g);
    summary[g] = {
      total: items.length,
      bad: items.filter(r => r.status === 'ERR' || (typeof r.status === 'number' && r.status >= 500) || r.denied || r.has500 || r.createStatus === 'ERR' || (typeof r.createStatus === 'number' && r.createStatus >= 500) || r.createDenied || r.createHas500).length
    };
  }

  fs.writeFileSync('audit/_admin_domain_modules_audit.json', JSON.stringify({ summary, rows }, null, 2));
  console.log(JSON.stringify({ summary }, null, 2));
  await browser.close();
})();
