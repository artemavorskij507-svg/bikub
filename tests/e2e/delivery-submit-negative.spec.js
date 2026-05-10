import { test, expect } from '@playwright/test';

test('delivery checkout negative: place-order blocked with empty cart', async ({ page }) => {
  const baseUrl = process.env.BASE_URL || 'http://136.119.84.22';
  await page.goto(`${baseUrl}/category/delivery`, { waitUntil: 'domcontentloaded' });

  await page.getByRole('button', { name: /Cart/i }).first().click();
  await expect(page.getByRole('heading', { name: /Checkout/i })).toBeVisible({ timeout: 10000 });

  const submit = page.getByRole('button', { name: /^Place order$/ });
  await expect(submit).toBeDisabled();
});

