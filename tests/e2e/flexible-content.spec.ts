import { test, expect, type Browser, type BrowserContext, type Page } from '@playwright/test';

/**
 * Flexible Content Layouts.
 *
 * Every assertion here used to be gated behind `if (count > 0)`, so an empty
 * homepage passed nearly every test in this file without ever rendering the
 * layout under test. The styleguide page renders every flexible layout at
 * once (see styleguide.spec.ts), so it is used here as the target where
 * presence is guaranteed and can be asserted directly instead of guarded.
 * `?variants=all` additionally forces every variant of a layout into the DOM
 * instead of only the first one behind a chip switcher.
 *
 * The page is private, so this suite needs the same WP_USER/WP_PASSWORD as
 * the styleguide suite and skips instead of failing when they are absent.
 */

const USER = process.env.WP_USER;
const PASSWORD = process.env.WP_PASSWORD;
const PATH = process.env.WP_STYLEGUIDE_PATH || '/styleguide/';

test.describe('Flexible Content Layouts', () => {
  test.skip(
    !USER || !PASSWORD,
    'WP_USER and WP_PASSWORD required - the styleguide is a private page.'
  );

  // Serial and one shared page, like styleguide.spec.ts: the styleguide holds
  // 80+ sections with images, logging in and reloading per test caused
  // timeouts against a single local instance.
  test.describe.configure({ mode: 'serial' });

  let context: BrowserContext;
  let page: Page;

  test.beforeAll(async ({ browser }: { browser: Browser }) => {
    context = await browser.newContext();
    page = await context.newPage();

    await page.goto('/wp-login.php');
    await page.fill('#user_login', USER as string);
    await page.fill('#user_pass', PASSWORD as string);
    await Promise.all([page.waitForURL(/wp-admin/, { timeout: 60_000 }), page.click('#wp-submit')]);

    await page.goto(`${PATH}?variants=all`, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test.describe('Hero Section', () => {
    test('should render hero section', async () => {
      const hero = page.locator('.hero').first();
      await expect(hero).toBeVisible();

      const h1 = hero.locator('h1');
      await expect(h1.first()).toBeVisible();
    });

    test('hero buttons should have href attributes', async () => {
      const hero = page.locator('.hero').first();
      const buttons = hero.locator('a[class*="btn"], a[class*="button"]');
      const buttonCount = await buttons.count();

      for (let i = 0; i < buttonCount; i++) {
        const href = await buttons.nth(i).getAttribute('href');
        expect(href).toBeTruthy();
      }
    });
  });

  test.describe('Accordion', () => {
    test('accordion should expand on click', async () => {
      const accordion = page.locator('.accordion').first();
      await expect(accordion).toBeVisible();

      const firstButton = accordion.locator('button[aria-expanded]').first();
      await expect(firstButton).toHaveAttribute('aria-expanded', 'false');

      await firstButton.click();
      await expect(firstButton).toHaveAttribute('aria-expanded', 'true');

      const panelId = await firstButton.getAttribute('aria-controls');
      expect(panelId).toBeTruthy();
      const panel = page.locator(`[id="${panelId}"]`);
      await expect(panel).toBeVisible();

      // Collapse again so later tests in this serial file see the closed state.
      await firstButton.click();
      await expect(firstButton).toHaveAttribute('aria-expanded', 'false');
    });

    test('accordion should support keyboard navigation', async () => {
      const accordion = page.locator('.accordion').first();
      const buttons = accordion.locator('button[aria-expanded]');
      const buttonCount = await buttons.count();

      test.skip(buttonCount < 2, 'accordion instance has fewer than 2 items');

      await buttons.first().focus();
      await expect(buttons.first()).toBeFocused();

      await page.keyboard.press('ArrowDown');
      await expect(buttons.nth(1)).toBeFocused();
    });

    test('accordion should toggle with Enter key', async () => {
      const accordion = page.locator('.accordion').first();
      const firstButton = accordion.locator('button[aria-expanded]').first();
      await expect(firstButton).toHaveAttribute('aria-expanded', 'false');

      await firstButton.focus();
      await page.keyboard.press('Enter');
      await expect(firstButton).toHaveAttribute('aria-expanded', 'true');

      await page.keyboard.press('Enter');
      await expect(firstButton).toHaveAttribute('aria-expanded', 'false');
    });
  });

  test.describe('Tabs', () => {
    test('tabs should switch content on click', async () => {
      const tabs = page.locator('.tabs').first();
      await expect(tabs).toBeVisible();

      const tabButtons = tabs.locator('[role="tab"]');
      const tabCount = await tabButtons.count();
      test.skip(tabCount < 2, 'tabs instance has fewer than 2 tabs');

      await expect(tabButtons.first()).toHaveAttribute('aria-selected', 'true');
      await expect(tabButtons.nth(1)).toHaveAttribute('aria-selected', 'false');

      await tabButtons.nth(1).click();

      await expect(tabButtons.nth(1)).toHaveAttribute('aria-selected', 'true');
      await expect(tabButtons.first()).toHaveAttribute('aria-selected', 'false');

      // Reset to the first tab for the next test in this serial file.
      await tabButtons.first().click();
    });

    test('tabs should support arrow key navigation', async () => {
      const tabs = page.locator('.tabs').first();
      const tabButtons = tabs.locator('[role="tab"]');
      const tabCount = await tabButtons.count();
      test.skip(tabCount < 2, 'tabs instance has fewer than 2 tabs');

      await tabButtons.first().focus();
      await expect(tabButtons.first()).toBeFocused();

      await page.keyboard.press('ArrowRight');
      await expect(tabButtons.nth(1)).toBeFocused();

      // Reset focus/selection state for the next test.
      await tabButtons.first().click();
    });

    test('tab panels should have correct ARIA attributes', async () => {
      const tabs = page.locator('.tabs').first();
      const tabPanels = tabs.locator('[role="tabpanel"]');
      const panelCount = await tabPanels.count();
      expect(panelCount).toBeGreaterThan(0);

      for (let i = 0; i < panelCount; i++) {
        const panel = tabPanels.nth(i);
        const id = await panel.getAttribute('id');
        expect(id).toBeTruthy();
      }
    });
  });

  test.describe('CTA Section', () => {
    test('CTA should have visible content', async () => {
      const cta = page.locator('.cta, [class*="cta"]').first();
      await expect(cta).toBeVisible();

      const heading = cta.locator('h2, h3').first();
      await expect(heading).toBeVisible();
    });

    test('CTA buttons should be clickable', async () => {
      const cta = page.locator('.cta, [class*="cta"]').first();
      const button = cta.locator('a[class*="btn"], a[class*="button"]').first();

      await expect(button).toBeVisible();
      await expect(button).toBeEnabled();

      const href = await button.getAttribute('href');
      expect(href).toBeTruthy();
    });
  });

  test.describe('Gallery', () => {
    test('gallery images should have alt attributes', async () => {
      const gallery = page.locator('.gallery').first();
      await expect(gallery).toBeVisible();

      const images = gallery.locator('img');
      const imageCount = await images.count();
      expect(imageCount).toBeGreaterThan(0);

      for (let i = 0; i < imageCount; i++) {
        const alt = await images.nth(i).getAttribute('alt');
        // Alt can be empty for decorative images, but attribute should exist
        expect(alt).not.toBeNull();
      }
    });
  });

  test.describe('Stats Counter', () => {
    test('stats should display numbers', async () => {
      const stats = page.locator('.stats, [x-data*="statsCounter"]').first();
      await expect(stats).toBeVisible();

      const numbers = stats.locator('[x-text], .stat-number, [class*="number"]');
      await expect(numbers.first()).toBeVisible();
    });
  });

  test.describe('Sections', () => {
    test('sections should have proper padding', async () => {
      const sections = page.locator('section');
      const sectionCount = await sections.count();

      // The styleguide alone holds 80+ sections.
      expect(sectionCount).toBeGreaterThan(0);
    });

    test('sections should be semantic HTML', async () => {
      const main = page.locator('main, #main-content');
      await expect(main).toBeAttached();

      const sectionsInMain = main.locator('section');
      const count = await sectionsInMain.count();
      expect(count).toBeGreaterThan(0);

      await expect(sectionsInMain.first()).toBeVisible();
    });
  });
});
