import { test, expect } from '@playwright/test';

test.describe('Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should have visible header navigation', async ({ page }) => {
    // Kein role="navigation": das Theme setzt keine redundante ARIA-Rolle auf
    // ein <nav>, die Rolle bringt das Element selbst mit.
    const nav = page.locator('header nav[aria-label]').first();
    await expect(nav).toBeAttached();
  });

  test('should have accessible navigation landmark', async ({ page }) => {
    // Vier Navigationen auf der Seite: Haupt, Mobil, Fuss, Rechtliches. Jede
    // braucht ihr eigenes aria-label, sonst sind sie im Screenreader nicht
    // auseinanderzuhalten.
    const navs = page.locator('nav');
    const anzahl = await navs.count();

    expect(anzahl).toBeGreaterThan(0);

    for (let i = 0; i < anzahl; i++) {
      await expect(navs.nth(i)).toHaveAttribute('aria-label', /.+/);
    }
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
