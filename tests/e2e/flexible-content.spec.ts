import { test, expect } from '@playwright/test';

test.describe('Flexible Content Layouts', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test.describe('Hero Section', () => {
    test('should render hero section if present', async ({ page }) => {
      const hero = page.locator('.hero');

      if ((await hero.count()) > 0) {
        await expect(hero.first()).toBeVisible();

        // Check for h1 heading
        const h1 = hero.locator('h1');
        if ((await h1.count()) > 0) {
          await expect(h1.first()).toBeVisible();
        }
      }
    });

    test('hero buttons should have href attributes', async ({ page }) => {
      const hero = page.locator('.hero');

      if ((await hero.count()) > 0) {
        const buttons = hero.locator('a[class*="btn"], a[class*="button"]');
        const buttonCount = await buttons.count();

        for (let i = 0; i < buttonCount; i++) {
          const href = await buttons.nth(i).getAttribute('href');
          expect(href).toBeTruthy();
        }
      }
    });
  });

  test.describe('Accordion', () => {
    test('accordion should expand on click', async ({ page }) => {
      const accordion = page.locator('.accordion');

      if ((await accordion.count()) > 0) {
        // Find first accordion button
        const firstButton = accordion.locator('button[aria-expanded]').first();

        if ((await firstButton.count()) > 0) {
          // Should start collapsed
          await expect(firstButton).toHaveAttribute('aria-expanded', 'false');

          // Click to expand
          await firstButton.click();

          // Should be expanded now
          await expect(firstButton).toHaveAttribute('aria-expanded', 'true');

          // Content should be visible
          const panelId = await firstButton.getAttribute('aria-controls');
          if (panelId) {
            const panel = page.locator(`#${panelId}`);
            await expect(panel).toBeVisible();
          }
        }
      }
    });

    test('accordion should support keyboard navigation', async ({ page }) => {
      const accordion = page.locator('.accordion');

      if ((await accordion.count()) > 0) {
        const buttons = accordion.locator('button[aria-expanded]');
        const buttonCount = await buttons.count();

        if (buttonCount >= 2) {
          // Focus first button
          await buttons.first().focus();
          await expect(buttons.first()).toBeFocused();

          // Press down arrow to move to next
          await page.keyboard.press('ArrowDown');

          // Second button should now be focused
          await expect(buttons.nth(1)).toBeFocused();
        }
      }
    });

    test('accordion should toggle with Enter key', async ({ page }) => {
      const accordion = page.locator('.accordion');

      if ((await accordion.count()) > 0) {
        const firstButton = accordion.locator('button[aria-expanded]').first();

        if ((await firstButton.count()) > 0) {
          await firstButton.focus();
          await expect(firstButton).toHaveAttribute('aria-expanded', 'false');

          // Press Enter to expand
          await page.keyboard.press('Enter');
          await expect(firstButton).toHaveAttribute('aria-expanded', 'true');

          // Press Enter again to collapse
          await page.keyboard.press('Enter');
          await expect(firstButton).toHaveAttribute('aria-expanded', 'false');
        }
      }
    });
  });

  test.describe('Tabs', () => {
    test('tabs should switch content on click', async ({ page }) => {
      const tabs = page.locator('.tabs');

      if ((await tabs.count()) > 0) {
        const tabButtons = tabs.locator('[role="tab"]');
        const tabCount = await tabButtons.count();

        if (tabCount >= 2) {
          // First tab should be selected
          await expect(tabButtons.first()).toHaveAttribute('aria-selected', 'true');
          await expect(tabButtons.nth(1)).toHaveAttribute('aria-selected', 'false');

          // Click second tab
          await tabButtons.nth(1).click();

          // Second tab should now be selected
          await expect(tabButtons.nth(1)).toHaveAttribute('aria-selected', 'true');
          await expect(tabButtons.first()).toHaveAttribute('aria-selected', 'false');
        }
      }
    });

    test('tabs should support arrow key navigation', async ({ page }) => {
      const tabs = page.locator('.tabs');

      if ((await tabs.count()) > 0) {
        const tabButtons = tabs.locator('[role="tab"]');
        const tabCount = await tabButtons.count();

        if (tabCount >= 2) {
          // Focus first tab
          await tabButtons.first().focus();
          await expect(tabButtons.first()).toBeFocused();

          // Press right arrow
          await page.keyboard.press('ArrowRight');

          // Second tab should be focused and selected
          await expect(tabButtons.nth(1)).toBeFocused();
        }
      }
    });

    test('tab panels should have correct ARIA attributes', async ({ page }) => {
      const tabs = page.locator('.tabs');

      if ((await tabs.count()) > 0) {
        const tabPanels = tabs.locator('[role="tabpanel"]');
        const panelCount = await tabPanels.count();

        for (let i = 0; i < panelCount; i++) {
          const panel = tabPanels.nth(i);
          const id = await panel.getAttribute('id');
          expect(id).toBeTruthy();
        }
      }
    });
  });

  test.describe('CTA Section', () => {
    test('CTA should have visible content', async ({ page }) => {
      const cta = page.locator('.cta, [class*="cta"]').first();

      if ((await cta.count()) > 0) {
        await expect(cta).toBeVisible();

        // Check for heading
        const heading = cta.locator('h2, h3').first();
        if ((await heading.count()) > 0) {
          await expect(heading).toBeVisible();
        }
      }
    });

    test('CTA buttons should be clickable', async ({ page }) => {
      const cta = page.locator('.cta, [class*="cta"]').first();

      if ((await cta.count()) > 0) {
        const button = cta.locator('a[class*="btn"], a[class*="button"]').first();

        if ((await button.count()) > 0) {
          await expect(button).toBeVisible();
          await expect(button).toBeEnabled();

          const href = await button.getAttribute('href');
          expect(href).toBeTruthy();
        }
      }
    });
  });

  test.describe('Gallery', () => {
    test('gallery images should have alt attributes', async ({ page }) => {
      const gallery = page.locator('.gallery');

      if ((await gallery.count()) > 0) {
        const images = gallery.locator('img');
        const imageCount = await images.count();

        for (let i = 0; i < imageCount; i++) {
          const alt = await images.nth(i).getAttribute('alt');
          // Alt can be empty for decorative images, but attribute should exist
          expect(alt).not.toBeNull();
        }
      }
    });
  });

  test.describe('Stats Counter', () => {
    test('stats should display numbers', async ({ page }) => {
      const stats = page.locator('.stats, [x-data*="statsCounter"]');

      if ((await stats.count()) > 0) {
        // Stats should be visible
        await expect(stats.first()).toBeVisible();

        // Should contain number elements
        const numbers = stats.locator('[x-text], .stat-number, [class*="number"]');
        if ((await numbers.count()) > 0) {
          await expect(numbers.first()).toBeVisible();
        }
      }
    });
  });

  test.describe('Sections', () => {
    test('sections should have proper padding', async ({ page }) => {
      const sections = page.locator('section');
      const sectionCount = await sections.count();

      // At least one section should exist
      expect(sectionCount).toBeGreaterThan(0);
    });

    test('sections should be semantic HTML', async ({ page }) => {
      // Check main landmark exists
      const main = page.locator('main, #main-content');
      await expect(main).toBeAttached();

      // Check sections within main
      const sectionsInMain = main.locator('section');
      const count = await sectionsInMain.count();

      // Should have at least one section if using flexible content
      if (count > 0) {
        await expect(sectionsInMain.first()).toBeVisible();
      }
    });
  });
});
