const { chromium } = require('@playwright/test');
const fs = require('fs');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('http://136.119.84.22/admin/login', {waitUntil:'domcontentloaded'});
  await page.fill('input[type="email"]','keks@glf.no');
  await page.fill('input[type="password"]','6636');
  await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type="submit"]')]);
  const r = await page.goto('http://136.119.84.22/admin/a-i-agent-team-chat', {waitUntil:'domcontentloaded'});
  await page.waitForTimeout(1000);
  const title = await page.title();
  const text = await page.locator('body').innerText();
  const snippet = text.slice(0, 3000);
  await page.screenshot({path:'audit/_agent_team_chat_page.png', fullPage:true});
  fs.writeFileSync('audit/_agent_team_chat_body.txt', snippet);
  console.log(JSON.stringify({status:r?r.status():null, title, hasServerError: text.toLowerCase().includes('server error'), hasWhoops:text.toLowerCase().includes('whoops'), hasStatus500:text.toLowerCase().includes('status code: 500')}, null, 2));
  await browser.close();
})();
