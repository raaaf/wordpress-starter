import { test, expect } from '@playwright/test';

test.describe('Homepage', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should load successfully', async ({ page }) => {
    // Check page title exists
    await expect(page).toHaveTitle(/.+/);

    // Check main content area exists
    const main = page.locator('#main-content');
    await expect(main).toBeVisible();
  });

  test('should have skip link for accessibility', async ({ page }) => {
    const skipLink = page.locator('a[href="#main-content"]');
    await expect(skipLink).toBeAttached();

    // Focus the skip link (it should become visible)
    await skipLink.focus();
    await expect(skipLink).toBeVisible();
  });

  test('should have proper heading structure', async ({ page }) => {
    // Check for h1 on the page
    const h1 = page.locator('h1').first();
    await expect(h1).toBeVisible();

    // Ensure there's only one h1
    const h1Count = await page.locator('h1').count();
    expect(h1Count).toBeLessThanOrEqual(1);
  });

  test('should have meta viewport tag', async ({ page }) => {
    const viewport = page.locator('meta[name="viewport"]');
    await expect(viewport).toHaveAttribute('content', /width=device-width/);
  });

  test('should have lang attribute on html element', async ({ page }) => {
    const html = page.locator('html');
    const lang = await html.getAttribute('lang');
    expect(lang).toBeTruthy();
  });
});
