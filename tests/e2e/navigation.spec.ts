import { test, expect } from '@playwright/test';

test.describe('Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should have visible header navigation', async ({ page }) => {
    const nav = page.locator('nav[role="navigation"]');
    await expect(nav).toBeVisible();
  });

  test('should have accessible navigation landmark', async ({ page }) => {
    const nav = page.locator('nav[aria-label]');
    await expect(nav).toBeAttached();
  });

  test('mobile menu should toggle on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    // Look for mobile menu button
    const menuButton = page.locator('[x-data*="navigation"] button').first();

    if (await menuButton.isVisible()) {
      // Click to open menu
      await menuButton.click();

      // Check menu expanded
      await expect(menuButton).toHaveAttribute('aria-expanded', 'true');

      // Click to close menu
      await menuButton.click();

      // Check menu collapsed
      await expect(menuButton).toHaveAttribute('aria-expanded', 'false');
    }
  });

  test('navigation links should be keyboard accessible', async ({ page }) => {
    // Find all nav links
    const navLinks = page.locator('nav a');
    const linkCount = await navLinks.count();

    if (linkCount > 0) {
      // Tab to first link
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab'); // Skip skip-link

      // Check a link is focused
      const focusedElement = page.locator(':focus');
      await expect(focusedElement).toBeVisible();
    }
  });

  test('header should have proper role', async ({ page }) => {
    const header = page.locator('header[role="banner"]');
    await expect(header).toBeAttached();
  });
});
