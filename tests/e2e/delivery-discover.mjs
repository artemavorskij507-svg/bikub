import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
await page.goto('http://136.119.84.22/category/delivery', { waitUntil: 'domcontentloaded' });

const addBtn = page.getByRole('button', { name: /Add to cart|\+\s*Â êîðçèíó/i }).first();
if (await addBtn.count()) await addBtn.click();
const cartBtn = page.getByRole('button', { name: /Cart|ÊÎÐÇÈÍÀ/i }).first();
if (await cartBtn.count()) await cartBtn.click();
await page.waitForTimeout(1500);

const labels = await page.locator('label').allTextContents();
console.log('LABELS:', labels.map((x) => x.replace(/\s+/g, ' ').trim()).slice(0, 40));

const placeholders = await page.locator('input[placeholder], textarea[placeholder]').evaluateAll((els) =>
  els.map((el) => el.getAttribute('placeholder')).filter(Boolean)
);
console.log('PLACEHOLDERS:', placeholders.slice(0, 50));

const buttons = await page.getByRole('button').allTextContents();
console.log('BUTTONS:', buttons.map((x) => x.replace(/\s+/g, ' ').trim()).filter(Boolean).slice(0, 80));

await page.screenshot({ path: 'C:/home/vscode/output/playwright/delivery-e2e-cart-open.png', fullPage: true });
await browser.close();
