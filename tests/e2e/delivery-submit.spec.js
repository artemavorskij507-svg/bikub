import { test, expect } from '@playwright/test';

test('delivery checkout flow: add-to-cart -> slot -> submit', async ({ page }) => {
  const baseUrl = process.env.BASE_URL || 'http://136.119.84.22';
  await page.goto(`${baseUrl}/category/delivery`, { waitUntil: 'domcontentloaded' });
  await expect(page.getByText('Fresh picks for today')).toBeVisible({ timeout: 15000 });

  await page.getByRole('button', { name: /^Add to cart$/ }).first().click();
  await page.getByRole('button', { name: /Cart/i }).first().click();

  await expect(page.getByRole('heading', { name: /Checkout/i })).toBeVisible({ timeout: 10000 });

  await page.getByLabel('Full name').fill('QA Narvik');
  await page.getByLabel('Email').fill(`qa+${Date.now()}@bikube.no`);
  await page.getByLabel('Phone').fill('+4799990000');
  await page.getByLabel('Street address').fill('Kongens gate 1');
  await page.getByLabel('City').fill('Narvik');
  await page.getByLabel('Postal code').fill('8514');

  const slot = page.getByLabel('Slot');
  await expect(slot).toBeVisible();
  await page.waitForTimeout(1800);

  const values = await slot.locator('option').evaluateAll((opts) => opts.map((o) => o.value).filter(Boolean));
  expect(values.length).toBeGreaterThan(0);
  await slot.selectOption(values[0]);

  await page.getByRole('button', { name: /^Place order$/ }).click();

  await expect(page.getByText(/Order .* created successfully\./)).toBeVisible({ timeout: 25000 });
});
