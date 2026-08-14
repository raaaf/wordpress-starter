import { test, expect, type Browser, type BrowserContext, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * Styleguide page.
 *
 * The styleguide is the only page that renders every flexible layout at once,
 * which makes it the cheapest place to catch a broken template. It is also the
 * page no automated test covered so far, because it is published as `private`
 * and needs a logged-in editor.
 *
 * Credentials come from the environment. Without them the suite skips instead
 * of failing: a CI job without a WordPress install should not report a red
 * build for a page it cannot reach.
 */

const USER = process.env.WP_USER;
const PASSWORD = process.env.WP_PASSWORD;
const PATH = process.env.WP_STYLEGUIDE_PATH || '/styleguide/';

test.describe('Styleguide', () => {
  test.skip(
    !USER || !PASSWORD,
    'WP_USER and WP_PASSWORD required - the styleguide is a private page.'
  );

  // Seriell und mit einer einzigen Seite: die Styleguide-Seite haelt ueber 80
  // Sektionen samt Bildern. Pro Test neu einloggen und neu laden hiess fuenf
  // Vollaufbauten parallel gegen dieselbe lokale Instanz, was regelmaessig in
  // Timeouts lief. Die Tests lesen nur, bis auf den Umschalter, und der raeumt
  // hinter sich auf.
  test.describe.configure({ mode: 'serial' });

  let context: BrowserContext;
  let page: Page;

  test.beforeAll(async ({ browser }: { browser: Browser }) => {
    // Eigener Kontext statt browser.newPage(): axe-core verweigert die Arbeit
    // auf einer Seite ohne expliziten Kontext.
    context = await browser.newContext();
    page = await context.newPage();

    await page.goto('/wp-login.php');
    await page.fill('#user_login', USER as string);
    await page.fill('#user_pass', PASSWORD as string);
    await Promise.all([page.waitForURL(/wp-admin/, { timeout: 60_000 }), page.click('#wp-submit')]);

    await page.goto(PATH, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  });

  test.afterAll(async () => {
    await context?.close();
  });

  test('renders without a PHP error', async () => {
    const body = await page.locator('body').innerText();

    expect(body).not.toMatch(/Fatal error|Warning:|Notice:|Uncaught/);
    expect(await page.locator('section').count()).toBeGreaterThan(50);
  });

  test('every jump navigation link points at an existing section', async () => {
    const nav = page.locator('nav[aria-label="Module"]');
    await page.locator('summary', { hasText: 'Springe zu' }).click();
    await expect(nav).toBeVisible();

    const targets = await nav
      .locator('a')
      .evaluateAll((links) => links.map((link) => link.getAttribute('href') || ''));

    // A jump list that lost its entries would pass a "no dead links" check
    // trivially, so pin the lower bound too.
    expect(targets.length).toBeGreaterThan(25);

    const dead = await page.evaluate(
      (hrefs: string[]) => hrefs.filter((href) => !document.querySelector(href)),
      targets
    );

    expect(dead).toEqual([]);
  });

  test('section anchors are unique', async () => {
    const duplicates = await page.evaluate(() => {
      const seen = new Set<string>();
      const twice: string[] = [];

      document.querySelectorAll('section[id]').forEach((section) => {
        const id = section.id;
        if (seen.has(id)) {
          twice.push(id);
        }
        seen.add(id);
      });

      return twice;
    });

    expect(duplicates).toEqual([]);
  });

  test('theme switcher sets and clears the explicit mode', async () => {
    const switcher = page.locator('[data-theme-switcher]');

    // Only rendered when the theme option is set to "system" — an install
    // pinned to light or dark has nothing to switch.
    test.skip((await switcher.count()) === 0, 'Farbschema ist nicht auf "System" gestellt.');

    await switcher.getByRole('radio', { name: 'Dunkel' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');

    await switcher.getByRole('radio', { name: 'Hell' }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');

    await switcher.getByRole('radio', { name: 'System' }).click();
    expect(await page.locator('html').getAttribute('data-theme')).toBeNull();
  });

  test('variant switcher shows exactly one instance per module', async () => {
    const module = page.locator('.styleguide-module').filter({
      has: page.locator('[role="radio"]'),
    });

    const anzahl = await module.count();
    expect(anzahl).toBeGreaterThan(10);

    const erstes = module.first();
    const chips = erstes.locator('[role="radio"]');
    const panels = erstes.locator('[data-variant]');

    // Genau eine Instanz sichtbar, und zwar die erste.
    await expect(panels.first()).toBeVisible();
    await expect(panels.nth(1)).toBeHidden();
    await expect(chips.first()).toHaveAttribute('aria-checked', 'true');

    await chips.nth(1).click();

    await expect(panels.first()).toBeHidden();
    await expect(panels.nth(1)).toBeVisible();
    await expect(chips.nth(1)).toHaveAttribute('aria-checked', 'true');

    // Rovender Fokus: nur der aktive Schalter liegt im Tab-Verlauf.
    await expect(chips.first()).toHaveAttribute('tabindex', '-1');
    await expect(chips.nth(1)).toHaveAttribute('tabindex', '0');

    await chips.first().click();
  });

  test('a deep link opens the variant it points at', async ({ browser }) => {
    // Eigene Seite: der Anker muss beim Laden gesetzt sein, ein Sprung im
    // laufenden Dokument startet Alpine nicht neu.
    const eigene = await context.newPage();
    await eigene.goto(`${PATH}#cards-3`, { waitUntil: 'domcontentloaded' });

    const ziel = eigene.locator('#cards-3');
    await expect(ziel).toBeVisible();

    await eigene.close();
  });

  test('?variants=all hides nothing', async () => {
    const eigene = await context.newPage();
    await eigene.goto(`${PATH}?variants=all`, { waitUntil: 'domcontentloaded' });

    // Ohne Vollansicht waeren Kontrastscanner und Screenshot-Diffs blind fuer
    // jede Variante ausser der ersten.
    const versteckt = await eigene.evaluate(
      () =>
        Array.from(document.querySelectorAll('[data-variant]')).filter(
          (panel) => (panel as HTMLElement).offsetParent === null
        ).length
    );

    expect(await eigene.locator('[data-variant]').count()).toBeGreaterThan(60);
    expect(versteckt).toBe(0);
    expect(await eigene.locator('.styleguide-module [role="radio"]').count()).toBe(0);

    await eigene.close();
  });

  test('the two views render only their own half', async () => {
    const eigene = await context.newPage();

    // Standardansicht: Module, kein Design-System.
    await eigene.goto(PATH, { waitUntil: 'domcontentloaded' });
    expect(await eigene.locator('.styleguide-module').count()).toBeGreaterThan(25);
    await expect(eigene.locator('#tokens')).toHaveCount(0);
    await expect(eigene.locator('#komponenten')).toHaveCount(0);
    await expect(eigene.locator('nav[aria-label="Ansicht"] a[aria-current="page"]')).toHaveText('Module');

    await eigene.getByRole('link', { name: 'Design-System', exact: true }).click();

    await expect(eigene.locator('#tokens')).toBeVisible();
    await expect(eigene.locator('#komponenten')).toBeVisible();
    expect(await eigene.locator('.styleguide-module').count()).toBe(0);

    await eigene.close();
  });

  test('an anchor from the other view corrects the view once', async () => {
    const eigene = await context.newPage();

    // #komponenten gibt es nur im Design-System, die Standardansicht sind aber
    // die Module. Ohne Korrektur liefe jeder geteilte Link dorthin ins Leere.
    await eigene.goto(`${PATH}#komponenten`, { waitUntil: 'domcontentloaded' });
    await eigene.waitForURL(/ansicht=design-system/, { timeout: 15_000 });
    await expect(eigene.locator('#komponenten')).toBeVisible();

    // Ein Anker, den es nirgends gibt, darf nicht zwischen beiden pendeln.
    await eigene.goto(`${PATH}?ansicht=design-system#gibt-es-nicht`, {
      waitUntil: 'domcontentloaded',
    });
    await eigene.waitForTimeout(1500);
    expect(eigene.url()).toContain('ansicht=design-system');

    await eigene.close();
  });

  test('has no critical accessibility violations', async () => {
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      // The admin bar ships with WordPress and is not ours to fix.
      .exclude('#wpadminbar')
      .analyze();

    const critical = results.violations.filter(
      (v) => v.impact === 'critical' || v.impact === 'serious'
    );

    if (critical.length > 0) {
      console.log('Accessibility violations on the styleguide:');
      critical.forEach((violation) => {
        console.log(`- ${violation.id} (${violation.nodes.length}x): ${violation.help}`);
        violation.nodes.slice(0, 3).forEach((node) => {
          console.log(`  Target: ${node.target}`);
        });
      });
    }

    expect(critical).toHaveLength(0);
  });
});
