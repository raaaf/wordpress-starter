import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe('Accessibility', () => {
  test('homepage should have no critical accessibility violations', async ({ page }) => {
    await page.goto('/');

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    // Filter to only critical and serious violations
    const criticalViolations = accessibilityScanResults.violations.filter(
      (v) => v.impact === 'critical' || v.impact === 'serious'
    );

    // Log violations for debugging
    if (criticalViolations.length > 0) {
      console.log('Accessibility violations found:');
      criticalViolations.forEach((violation) => {
        console.log(`- ${violation.id}: ${violation.description}`);
        violation.nodes.forEach((node) => {
          console.log(`  Target: ${node.target}`);
          console.log(`  HTML: ${node.html.substring(0, 100)}...`);
        });
      });
    }

    expect(criticalViolations).toHaveLength(0);
  });

  test('blog page should be accessible', async ({ page }) => {
    // Try to navigate to blog - may or may not exist
    const response = await page.goto('/blog');

    if (response?.ok()) {
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

      const criticalViolations = accessibilityScanResults.violations.filter(
        (v) => v.impact === 'critical' || v.impact === 'serious'
      );

      expect(criticalViolations).toHaveLength(0);
    }
  });

  test('all pages should have proper document structure', async ({ page }) => {
    await page.goto('/');

    // Check for main landmark
    const main = page.locator('main, [role="main"]');
    await expect(main).toBeAttached();

    // Check for header landmark
    const header = page.locator('header, [role="banner"]');
    await expect(header).toBeAttached();

    // Check for footer landmark (if exists)
    const footer = page.locator('footer, [role="contentinfo"]');
    await expect(footer).toBeAttached();
  });

  test('images should have alt attributes', async ({ page }) => {
    await page.goto('/');

    // Find all images
    const images = page.locator('img');
    const imageCount = await images.count();

    for (let i = 0; i < imageCount; i++) {
      const img = images.nth(i);
      const alt = await img.getAttribute('alt');

      // Alt attribute must exist (can be empty for decorative images)
      expect(alt).not.toBeNull();
    }
  });

  test('form inputs should have labels', async ({ page }) => {
    await page.goto('/');

    // Find all inputs that require labels
    const inputs = page.locator(
      'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select'
    );
    const inputCount = await inputs.count();

    for (let i = 0; i < inputCount; i++) {
      const input = inputs.nth(i);
      const id = await input.getAttribute('id');
      const ariaLabel = await input.getAttribute('aria-label');
      const ariaLabelledBy = await input.getAttribute('aria-labelledby');

      // Input must have either a label, aria-label, or aria-labelledby
      if (id) {
        const label = page.locator(`label[for="${id}"]`);
        const hasLabel = (await label.count()) > 0;
        const hasAriaLabel = ariaLabel !== null || ariaLabelledBy !== null;

        expect(hasLabel || hasAriaLabel).toBeTruthy();
      }
    }
  });

  test('color contrast should be sufficient', async ({ page }) => {
    await page.goto('/');

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['cat.color'])
      .analyze();

    const colorViolations = accessibilityScanResults.violations.filter(
      (v) => v.impact === 'serious' || v.impact === 'critical'
    );

    expect(colorViolations).toHaveLength(0);
  });

  test('interactive elements should be keyboard accessible', async ({ page }) => {
    await page.goto('/');

    // Get all interactive elements
    const interactiveElements = page.locator('a, button, input, select, textarea, [tabindex]');
    const count = await interactiveElements.count();

    // Check that at least some elements are focusable
    let focusableCount = 0;

    for (let i = 0; i < Math.min(count, 10); i++) {
      const el = interactiveElements.nth(i);
      const tabindex = await el.getAttribute('tabindex');

      // Element is focusable if tabindex is not -1
      if (tabindex !== '-1') {
        focusableCount++;
      }
    }

    expect(focusableCount).toBeGreaterThan(0);
  });
});
