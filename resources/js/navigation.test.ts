import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { createNavigationComponent, type NavigationComponent } from './app';

// TestableNavigationComponent is NavigationComponent — magic properties already included via AlpineMagics
type TestableNavigationComponent = NavigationComponent;

/**
 * Tests for the navigation Alpine.js component.
 */
describe('Navigation Component', () => {
  let navigation: TestableNavigationComponent;

  beforeEach(() => {
    navigation = createNavigationComponent() as TestableNavigationComponent;
    // Mock Alpine.js magic properties
    navigation.$watch = () => {};
    navigation.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };
    navigation.$el = document.createElement('div');
  });

  it('has initial state closed', () => {
    expect(navigation.isOpen).toBe(false);
  });

  it('opens when toggle is called while closed', () => {
    navigation.toggle();

    expect(navigation.isOpen).toBe(true);
  });

  it('closes when toggle is called while open', () => {
    navigation.isOpen = true;

    navigation.toggle();

    expect(navigation.isOpen).toBe(false);
  });

  it('sets isOpen to false when close is called', () => {
    navigation.isOpen = true;

    navigation.close();

    expect(navigation.isOpen).toBe(false);
  });

  it('close is idempotent - multiple calls have same result', () => {
    navigation.isOpen = true;

    navigation.close();
    navigation.close();
    navigation.close();

    expect(navigation.isOpen).toBe(false);
  });

  it('can toggle multiple times', () => {
    expect(navigation.isOpen).toBe(false);

    navigation.toggle();
    expect(navigation.isOpen).toBe(true);

    navigation.toggle();
    expect(navigation.isOpen).toBe(false);

    navigation.toggle();
    expect(navigation.isOpen).toBe(true);
  });

  it('close works regardless of current state', () => {
    // When already closed
    navigation.isOpen = false;
    navigation.close();
    expect(navigation.isOpen).toBe(false);

    // When open
    navigation.isOpen = true;
    navigation.close();
    expect(navigation.isOpen).toBe(false);
  });
});

/**
 * init() liest `$el.querySelector(...)` und legt Submenu-Toggles an. Ein
 * losgeloestes `$el` ohne `init()`-Aufruf laesst diese Selektoren nie einen
 * Treffer finden, ohne dass ein Test das bemerkt. Diese Suite haengt die
 * Fixture an `document.body` und ruft `init()` tatsaechlich auf, damit ein
 * kaputter Selektor den Test auch rot machen kann.
 */
const SUBMENU_FIXTURE = `
  <button data-nav-toggle>Menu</button>
  <div class="mobile-nav-container">
    <div class="menu-item-has-children">
      <a href="/parent">Parent</a>
      <ul class="sub-menu">
        <li><a href="/child">Child</a></li>
      </ul>
    </div>
  </div>
`;

describe('Navigation Component init()', () => {
  let navigation: TestableNavigationComponent;
  let root: HTMLElement;

  beforeEach(() => {
    // WordPress injects themeStrings via wp_localize_script; init() reads it
    // while building the submenu toggles, so it must exist before init() runs.
    // Set here and removed in afterEach, so it never leaks into a test file
    // that expects it to be absent (see the "without themeStrings" suite
    // below, which relies on exactly that).
    (globalThis as Record<string, unknown>).themeStrings = {
      submenuOpen: 'Untermenü öffnen',
      submenuClose: 'Untermenü schließen',
      image: 'Bild',
      imageZoomInstruction: 'zum Vergrößern klicken',
    };

    root = document.createElement('div');
    root.innerHTML = SUBMENU_FIXTURE;
    document.body.appendChild(root);

    navigation = createNavigationComponent() as TestableNavigationComponent;
    navigation.$watch = () => {};
    navigation.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };
    navigation.$el = root;
    navigation.init();
  });

  afterEach(() => {
    root.remove();
    delete (globalThis as Record<string, unknown>).themeStrings;
  });

  it('finds the toggle button and mobile nav container via the real DOM', () => {
    expect(navigation.toggleButton).toBe(root.querySelector('[data-nav-toggle]'));
    expect(navigation.mobileNavContainer).toBe(root.querySelector('.mobile-nav-container'));
  });

  it('creates a submenu toggle and expands the submenu on click', () => {
    const submenuToggle = root.querySelector<HTMLButtonElement>('.submenu-toggle')!;
    const submenu = root.querySelector<HTMLElement>('.sub-menu')!;

    expect(submenuToggle.getAttribute('aria-expanded')).toBe('false');
    expect(submenu.classList.contains('is-open')).toBe(false);

    submenuToggle.click();

    expect(submenuToggle.getAttribute('aria-expanded')).toBe('true');
    expect(submenu.classList.contains('is-open')).toBe(true);

    submenuToggle.click();

    expect(submenuToggle.getAttribute('aria-expanded')).toBe('false');
    expect(submenu.classList.contains('is-open')).toBe(false);
  });
});

/**
 * app.ts declares `themeStrings` as an ambient global that WordPress injects
 * via wp_localize_script. A cached page fragment can be served without that
 * script data, so init() -> initMobileSubmenus() falls back to German
 * default labels instead of throwing when `themeStrings` is missing.
 */
describe('Navigation Component init() without themeStrings', () => {
  let root: HTMLElement;

  beforeEach(() => {
    delete (globalThis as Record<string, unknown>).themeStrings;
    root = document.createElement('div');
    root.innerHTML = SUBMENU_FIXTURE;
    document.body.appendChild(root);
  });

  afterEach(() => {
    root.remove();
  });

  it('falls back to German default labels instead of throwing', () => {
    const navigation = createNavigationComponent() as TestableNavigationComponent;
    navigation.$watch = () => {};
    navigation.$nextTick = (callback?: () => void) => {
      callback?.();
      return Promise.resolve();
    };
    navigation.$el = root;

    expect(() => navigation.init()).not.toThrow();

    const toggle = root.querySelector<HTMLButtonElement>('.submenu-toggle')!;
    expect(toggle.getAttribute('aria-label')).toBe('Untermenü öffnen');
  });
});

describe('Navigation collapse measuring', () => {
  const originalMatchMedia = window.matchMedia;
  let navigation: NavigationComponent;
  let root: HTMLElement;
  let row: HTMLElement;
  let child: HTMLElement;

  function setMd(matches: boolean) {
    window.matchMedia = (() => ({ matches })) as unknown as typeof window.matchMedia;
  }

  // The overflow check reads the last rendered child of the row against the row itself
  function rect(right: number, width = 100) {
    return () => ({ right, width }) as DOMRect;
  }

  function setRowWidths(scrollWidth: number, clientWidth: number) {
    child.getBoundingClientRect = rect(scrollWidth);
    row.getBoundingClientRect = rect(clientWidth, clientWidth);
  }

  beforeEach(() => {
    root = document.createElement('div');
    root.innerHTML = '<div data-nav-row><div></div></div>';
    row = root.firstElementChild as HTMLElement;
    child = row.firstElementChild as HTMLElement;
    navigation = createNavigationComponent() as NavigationComponent;
    navigation.$el = root;
    navigation.navRow = row;
    setMd(true);
  });

  afterEach(() => {
    window.matchMedia = originalMatchMedia;
  });

  it('stays expanded when the row fits', () => {
    setRowWidths(900, 900);

    navigation.measure();

    expect(navigation.collapsed).toBe(false);
    expect(root.classList.contains('nav-collapsed')).toBe(false);
  });

  it('collapses when the row overflows', () => {
    setRowWidths(1000, 900);

    navigation.measure();

    expect(navigation.collapsed).toBe(true);
    expect(root.classList.contains('nav-collapsed')).toBe(true);
  });

  it('does not collapse below md even when the row overflows', () => {
    setMd(false);
    setRowWidths(1000, 900);

    navigation.measure();

    expect(navigation.collapsed).toBe(false);
    expect(root.classList.contains('nav-collapsed')).toBe(false);
  });

  it('closes a panel opened below the breakpoint once desktop returns without overflow', () => {
    setMd(false);
    navigation.isOpen = true;
    setRowWidths(900, 900);

    navigation.measure();
    expect(navigation.isOpen).toBe(true);

    setMd(true);
    navigation.measure();

    expect(navigation.collapsed).toBe(false);
    expect(navigation.isOpen).toBe(false);
  });

  it('leaves an open collapsed panel alone and measures again after it closes', () => {
    let onOpenChange: ((open: boolean) => void) | undefined;
    navigation.$watch = ((_property: string, callback: (open: boolean) => void) => {
      onOpenChange = callback;
    }) as unknown as NavigationComponent['$watch'];
    navigation.$nextTick = (() => Promise.resolve()) as unknown as NavigationComponent['$nextTick'];
    setRowWidths(900, 900);
    navigation.init();

    navigation.collapsed = true;
    navigation.isOpen = true;
    root.classList.add('nav-collapsed');

    navigation.measure();

    expect(navigation.collapsed).toBe(true);
    expect(navigation.isOpen).toBe(true);
    expect(root.classList.contains('nav-collapsed')).toBe(true);

    navigation.isOpen = false;
    onOpenChange?.(false);

    expect(navigation.collapsed).toBe(false);
    expect(root.classList.contains('nav-collapsed')).toBe(false);
  });
});
