import { chromium } from 'playwright';

const url = 'http://136.119.84.22/category/delivery';
const browser = await chromium.launch({ headless: true, channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

try {
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.getByRole('button', { name: /^Add to cart$/ }).first().click();
  await page.getByRole('button', { name: /Cart/i }).first().click();

  await page.getByLabel('Full name').fill('QA Narvik');
  await page.getByLabel('Email').fill(`qa+${Date.now()}@bikube.no`);
  await page.getByLabel('Phone').fill('+4799990000');
  await page.getByLabel('Street address').fill('Kongens gate 1');
  await page.getByLabel('City').fill('Narvik');
  await page.getByLabel('Postal code').fill('8514');

  const slot = page.getByLabel('Slot');
  await slot.waitFor({ timeout: 15000 });
  await page.waitForTimeout(2000);

  const values = await slot.locator('option').evaluateAll((opts) => opts.map((o) => o.value).filter(Boolean));
  if (!values.length) {
    throw new Error('No delivery slots available for E2E run');
  }
  await slot.selectOption(values[0]);

  await page.getByRole('button', { name: /^Place order$/ }).click();
  await page.getByText(/Order .* created successfully\./).waitFor({ timeout: 25000 });

  console.log('E2E_OK: add-to-cart -> slot -> submit passed');
} catch (error) {
  console.error('E2E_FAIL:', error.message);
  await page.screenshot({ path: 'C:/home/vscode/output/playwright/delivery-e2e-fail.png', fullPage: true });
  process.exitCode = 1;
} finally {
  await browser.close();
}
