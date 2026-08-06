import { describe, it, expect, beforeEach } from 'vitest';
import { createNavigationComponent, type NavigationComponent } from './app';

/**
 * Focus trap tests for the mobile navigation.
 *
 * These exist because the trap was inert in production while navigation.test.ts
 * stayed green: that file passes a bare document.createElement('div') as $el,
 * never calls init(), and asserts nothing about focus. The bug was a selector
 * pointing at an element that does not exist in the markup, which is invisible
 * to any test that does not use the real markup.
 *
 * So every test here runs against a fixture mirroring the structure of
 * templates/partials/header-menu.blade.php. The two details that matter:
 *
 *   1. x-show sits on the wrapping div, not on <nav>. A selector written as
 *      nav[x-show="isOpen"] matches nothing.
 *   2. The CTA button lives inside .mobile-nav-container but OUTSIDE <nav>,
 *      so scoping the trap to the <nav> silently drops it from the boundaries.
 */

// Mirrors templates/partials/header-menu.blade.php. Keep in sync with it —
// if the container class or the CTA's position changes there, change it here.
const HEADER_MARKUP = `
  <div class="relative">
    <button data-nav-toggle aria-expanded="false">Menu</button>

    <div class="mobile-nav-container absolute top-full left-0 right-0 md:hidden z-50">
      <nav id="mobile-navigation" class="mobile-nav" aria-label="Mobile Navigation">
        <ul class="py-2">
          <li class="relative px-4 py-2"><a href="/">Start</a></li>
          <li class="relative px-4 py-2 menu-item-has-children">
            <a href="/leistungen">Leistungen</a>
            <ul class="sub-menu">
              <li><a href="/leistungen/beratung">Beratung</a></li>
            </ul>
          </li>
          <li class="relative px-4 py-2"><a href="/kontakt">Kontakt</a></li>
        </ul>
      </nav>

      <div class="px-4 py-4 border-t border-line">
        <a href="/termin" class="button">Termin vereinbaren</a>
      </div>
    </div>
  </div>
`;

/**
 * happy-dom reports a non-null offsetParent for display:none elements, so the
 * production filter cannot be exercised by hiding an element in the fixture.
 * This forces the value the browser would report, which is what the filter
 * actually reads. Without it a test asserting that collapsed submenu links are
 * excluded would fail against correct code.
 */
function simulateHidden(el: HTMLElement): void {
  Object.defineProperty(el, 'offsetParent', { value: null, configurable: true });
}

/**
 * WordPress injects themeStrings via wp_localize_script. init() reads it while
 * building the submenu toggles, so it has to exist before init() runs — which
 * navigation.test.ts never noticed, because it never calls init().
 */
function stubThemeStrings(): void {
  (globalThis as Record<string, unknown>).themeStrings = {
    submenuOpen: 'Untermenü öffnen',
    submenuClose: 'Untermenü schließen',
    image: 'Bild',
    imageZoomInstruction: 'zum Vergrößern klicken',
  };
}

function mountWith(markup: string): NavigationComponent {
  stubThemeStrings();
  document.body.innerHTML = markup;
  const navigation = createNavigationComponent() as NavigationComponent;
  navigation.$nextTick = (callback?: () => void) => {
    callback?.();
    return Promise.resolve();
  };
  navigation.$el = document.body.firstElementChild as HTMLElement;
  navigation.init();
  return navigation;
}

function mount(): NavigationComponent {
  return mountWith(HEADER_MARKUP);
}

function tab(shift = false): KeyboardEvent {
  return new KeyboardEvent('keydown', { key: 'Tab', shiftKey: shift, cancelable: true });
}

describe('Mobile navigation focus trap', () => {
  let navigation: NavigationComponent;

  beforeEach(() => {
    navigation = mount();
  });

  describe('init', () => {
    it('finds the toggle button in the real markup', () => {
      expect(navigation.toggleButton).not.toBeNull();
      expect(navigation.toggleButton?.hasAttribute('data-nav-toggle')).toBe(true);
    });

    it('finds the mobile nav container in the real markup', () => {
      expect(navigation.mobileNavContainer).not.toBeNull();
      expect(navigation.mobileNavContainer?.classList.contains('mobile-nav-container')).toBe(true);
    });

    it('the container is the element x-show toggles, not the nav', () => {
      // Regression guard for the original bug: the trap queried
      // nav[x-show="isOpen"], and no <nav> in this markup carries x-show.
      expect(document.querySelector('nav[x-show="isOpen"]')).toBeNull();
      expect(document.querySelector('.mobile-nav-container')).not.toBeNull();
    });
  });

  describe('getFocusableElements', () => {
    it('returns the menu links', () => {
      const focusable = navigation.getFocusableElements();
      const hrefs = focusable.map((el) => el.getAttribute('href'));

      expect(hrefs).toContain('/');
      expect(hrefs).toContain('/leistungen');
      expect(hrefs).toContain('/kontakt');
    });

    it('includes the CTA button that sits outside the nav element', () => {
      // Second regression guard: scoping the trap to #mobile-navigation
      // compiles, runs, and drops this element from the trap boundaries.
      const focusable = navigation.getFocusableElements();

      expect(focusable.map((el) => el.getAttribute('href'))).toContain('/termin');
    });

    it('returns a non-empty list — the failure mode of the original bug', () => {
      // The broken selector made this unconditionally [], which disables the
      // trap entirely: trapFocus returns early and Tab walks out of the menu.
      expect(navigation.getFocusableElements().length).toBeGreaterThan(0);
    });

    it('excludes links inside a collapsed submenu', () => {
      const hidden = document.querySelector<HTMLElement>('.sub-menu a');
      simulateHidden(hidden as HTMLElement);

      const hrefs = navigation.getFocusableElements().map((el) => el.getAttribute('href'));

      expect(hrefs).not.toContain('/leistungen/beratung');
    });

    it('keeps Tab wrapping when a hidden element trails the list', () => {
      // This is what the filter actually protects. trapFocus compares
      // document.activeElement against the LAST entry. If a hidden element
      // trails the list, the last visible element the user can reach is no
      // longer that entry, the comparison never matches, and Tab leaves the
      // open menu — the same escape the original bug caused, by a different
      // route.
      //
      // It needs its own fixture: in HEADER_MARKUP the collapsed submenu sits
      // in the middle, so dropping the filter leaves the boundaries on visible
      // elements and no assertion here could fail. A test that cannot fail is
      // how the original bug stayed invisible for so long.
      const trailing = mountWith(`
        <div class="relative">
          <button data-nav-toggle>Menu</button>
          <div class="mobile-nav-container">
            <nav id="mobile-navigation" class="mobile-nav">
              <ul>
                <li><a href="/">Start</a></li>
                <li><a href="/kontakt">Kontakt</a></li>
                <li><a href="/versteckt" class="trailing">Ausgeblendet</a></li>
              </ul>
            </nav>
          </div>
        </div>
      `);
      simulateHidden(document.querySelector<HTMLElement>('a.trailing') as HTMLElement);
      trailing.isOpen = true;

      const focusable = trailing.getFocusableElements();
      expect(focusable.map((el) => el.getAttribute('href'))).toEqual(['/', '/kontakt']);

      focusable[focusable.length - 1].focus();
      const event = tab();
      trailing.trapFocus(event);

      expect(event.defaultPrevented).toBe(true);
      expect(document.activeElement).toBe(focusable[0]);
    });

    it('returns an empty list when the container is missing', () => {
      const detached = createNavigationComponent() as NavigationComponent;
      detached.$nextTick = (cb?: () => void) => {
        cb?.();
        return Promise.resolve();
      };
      detached.$el = document.createElement('div');
      detached.init();

      expect(detached.getFocusableElements()).toEqual([]);
    });
  });

  describe('trapFocus', () => {
    it('wraps from the last element to the first on Tab', () => {
      navigation.isOpen = true;
      const focusable = navigation.getFocusableElements();
      const last = focusable[focusable.length - 1];
      last.focus();

      const event = tab();
      navigation.trapFocus(event);

      expect(event.defaultPrevented).toBe(true);
      expect(document.activeElement).toBe(focusable[0]);
    });

    it('wraps from the first element to the last on Shift+Tab', () => {
      navigation.isOpen = true;
      const focusable = navigation.getFocusableElements();
      focusable[0].focus();

      const event = tab(true);
      navigation.trapFocus(event);

      expect(event.defaultPrevented).toBe(true);
      expect(document.activeElement).toBe(focusable[focusable.length - 1]);
    });

    it('leaves Tab alone in the middle of the list', () => {
      navigation.isOpen = true;
      const focusable = navigation.getFocusableElements();
      focusable[1].focus();

      const event = tab();
      navigation.trapFocus(event);

      expect(event.defaultPrevented).toBe(false);
    });

    it('does nothing while the menu is closed', () => {
      navigation.isOpen = false;
      const focusable = navigation.getFocusableElements();
      focusable[focusable.length - 1].focus();

      const event = tab();
      navigation.trapFocus(event);

      expect(event.defaultPrevented).toBe(false);
    });

    it('ignores keys other than Tab', () => {
      navigation.isOpen = true;
      const focusable = navigation.getFocusableElements();
      focusable[focusable.length - 1].focus();

      const event = new KeyboardEvent('keydown', { key: 'a', cancelable: true });
      navigation.trapFocus(event);

      expect(event.defaultPrevented).toBe(false);
    });
  });

  describe('focus handover', () => {
    it('moves focus into the menu when it opens', () => {
      navigation.toggle();

      expect(navigation.isOpen).toBe(true);
      expect(document.activeElement).toBe(navigation.getFocusableElements()[0]);
    });

    it('returns focus to the toggle button when it closes', () => {
      navigation.toggle();

      navigation.close();

      expect(document.activeElement).toBe(navigation.toggleButton);
    });
  });
});
