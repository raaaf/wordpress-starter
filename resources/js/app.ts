// Import Alpine.js and plugins
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import type { AlpineMagics } from '../../src/types/alpine';
import { registerMemberAreaComponents } from './member-area';
import { createStatsCounterCore, type StatsCounterCore } from './stats-counter';
import { initColumnHeadingAlignment } from './column-headings';

// Declare localized strings from WordPress (object name is fixed as 'themeStrings' for all themes)
declare const themeStrings: {
  submenuOpen: string;
  submenuClose: string;
  image: string;
  imageZoomInstruction: string;
};

// ============================================
// Navigation Component
// ============================================

export interface NavigationComponent extends AlpineMagics {
  isOpen: boolean;
  toggleButton: HTMLElement | null;
  mobileNavContainer: HTMLElement | null;
  toggle(): void;
  close(): void;
  init(): void;
  initMobileSubmenus(): void;
  handleKeydown(event: KeyboardEvent): void;
  trapFocus(event: KeyboardEvent): void;
  getFocusableElements(): HTMLElement[];
}

export function createNavigationComponent(): NavigationComponent {
  return {
    // Alpine magic properties ($el, $nextTick, etc.) are injected at runtime
    ...({} as AlpineMagics),
    isOpen: false,
    toggleButton: null,
    mobileNavContainer: null,

    init() {
      this.toggleButton = this.$el.querySelector('[data-nav-toggle]');
      this.mobileNavContainer = this.$el.querySelector('.mobile-nav-container');
      this.initMobileSubmenus();
    },

    initMobileSubmenus() {
      if (!this.mobileNavContainer) return;

      const menuItems = this.mobileNavContainer.querySelectorAll('.menu-item-has-children');
      menuItems.forEach((item) => {
        const submenu = item.querySelector(':scope > .sub-menu') as HTMLElement;
        if (!submenu) return;

        // Create toggle button
        const toggle = document.createElement('button');
        toggle.className = 'submenu-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', themeStrings.submenuOpen);
        toggle.innerHTML =
          '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';

        const toggleSubmenu = () => {
          const isExpanded = submenu.classList.toggle('is-open');
          toggle.setAttribute('aria-expanded', String(isExpanded));
          toggle.setAttribute(
            'aria-label',
            isExpanded ? themeStrings.submenuClose : themeStrings.submenuOpen
          );
        };

        toggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          toggleSubmenu();
        });

        // Explicit keyboard support for Enter and Space keys
        toggle.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            toggleSubmenu();
          }
        });

        item.appendChild(toggle);
      });
    },

    toggle() {
      this.isOpen = !this.isOpen;

      if (this.isOpen) {
        // Focus first focusable element in mobile nav after transition
        this.$nextTick(() => {
          const focusable = this.getFocusableElements();
          if (focusable.length > 0) {
            focusable[0].focus();
          }
        });
      }
    },

    close() {
      this.isOpen = false;
      // Return focus to toggle button
      if (this.toggleButton) {
        this.toggleButton.focus();
      }
    },

    handleKeydown(event: KeyboardEvent) {
      if (event.key === 'Escape' && this.isOpen) {
        this.close();
      }
    },

    trapFocus(event: KeyboardEvent) {
      if (event.key !== 'Tab' || !this.isOpen) return;

      const focusable = this.getFocusableElements();
      if (focusable.length === 0) return;

      const firstElement = focusable[0];
      const lastElement = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    },

    getFocusableElements(): HTMLElement[] {
      if (!this.mobileNavContainer) return [];
      const selector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
      // offsetParent is null for display:none elements (e.g. collapsed submenus),
      // filtering them out so the trap's boundaries are always elements Tab can reach.
      return Array.from(this.mobileNavContainer.querySelectorAll<HTMLElement>(selector)).filter(
        (el) => el.offsetParent !== null
      );
    },
  };
}

// ============================================
// Stats Counter Component
// ============================================

export type StatsCounterComponent = StatsCounterCore & AlpineMagics;

export function createStatsCounterComponent(target: number): StatsCounterComponent {
  return {
    // Alpine magic properties ($el, $nextTick, etc.) are injected at runtime
    ...({} as AlpineMagics),
    ...createStatsCounterCore(target, {
      respectReducedMotion: true,
      useIntersectionObserver: true,
      preserveDecimals: true,
    }),
  };
}

// ============================================
// Rybbit Analytics Tracking
// ============================================

export const CONTENT_SELECTORS =
  '.prose a, .one-column a, .two-columns a, .three-columns a, .four-columns a, .two-columns-images a, .one-third-columns a';

export const BLOCK_TYPE_SELECTOR =
  '[class*="-column"], .hero, .cta-block, .video, .accordion, .two-columns-images, .one-third-columns';

export const BLOCK_TYPE_REGEX =
  /(one|two|three|four)-column(?:s)?(?:-images)?|one-third-columns|hero|cta-block|video|accordion/;

export function extractBlockType(element: Element): string | null {
  const parentBlock = element.closest(BLOCK_TYPE_SELECTOR);
  if (!parentBlock) return null;

  const match = parentBlock.className.match(BLOCK_TYPE_REGEX);
  return match?.[0] || 'unknown';
}

export function addContentLinkTracking(link: HTMLAnchorElement): void {
  if (link.hasAttribute('data-rybbit-event')) return;

  const isExternal = link.hostname && link.hostname !== window.location.hostname;
  const linkText = link.textContent?.trim() || 'Unknown';

  link.setAttribute(
    'data-rybbit-event',
    isExternal ? 'External_Link_Click' : 'Internal_Link_Click'
  );
  link.setAttribute('data-rybbit-prop-key', 'content_link');
  link.setAttribute('data-rybbit-prop-link-text', linkText);
  link.setAttribute('data-rybbit-prop-link-url', link.href);

  const blockType = extractBlockType(link);
  if (blockType) {
    link.setAttribute('data-rybbit-prop-block-type', blockType);
  }
}

export function addImageLinkTracking(link: HTMLAnchorElement): void {
  if (link.hasAttribute('data-rybbit-event')) return;

  link.setAttribute('data-rybbit-event', 'Image_Link_Click');
  link.setAttribute('data-rybbit-prop-key', 'image_block');
  link.setAttribute('data-rybbit-prop-link-url', link.href);
}

export function initRybbitTracking(): void {
  const contentLinks = document.querySelectorAll<HTMLAnchorElement>(CONTENT_SELECTORS);
  contentLinks.forEach(addContentLinkTracking);

  const imageLinks = document.querySelectorAll<HTMLAnchorElement>('.image a');
  imageLinks.forEach(addImageLinkTracking);
}

// ============================================
// Video Consent Handler
// ============================================

export function initVideoConsent(): void {
  document.querySelectorAll<HTMLElement>('.video-consent-btn').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const container = btn.closest('.video');
      const iframe = container?.querySelector<HTMLIFrameElement>('iframe[data-src]');
      if (iframe) {
        const src = iframe.getAttribute('data-src');
        if (src) {
          iframe.setAttribute('src', src);
        }
      }
    });
  });
}

// ============================================
// Gallery Lightbox
// ============================================

export async function initGalleryZoom(): Promise<void> {
  const zoomElements = document.querySelectorAll('.gallery-zoom');
  if (zoomElements.length === 0) return;

  // Lazy-load medium-zoom only on pages that actually use it.
  const { default: mediumZoom } = await import('medium-zoom');

  let lastFocusedElement: HTMLElement | null = null;

  const zoom = mediumZoom('.gallery-zoom', {
    margin: 24,
    background: 'rgba(0, 0, 0, 0.9)',
    scrollOffset: 40,
  });

  // Focus management: store last focused element before opening
  zoom.on('open', () => {
    lastFocusedElement = document.activeElement as HTMLElement;
    // Focus the zoomed image for screen reader announcement
    const zoomedImage = document.querySelector('.medium-zoom-image--opened') as HTMLElement;
    if (zoomedImage) {
      zoomedImage.setAttribute('tabindex', '-1');
      zoomedImage.focus();
    }
  });

  // Focus management: return focus to trigger element on close
  zoom.on('close', () => {
    if (lastFocusedElement) {
      lastFocusedElement.focus();
      lastFocusedElement = null;
    }
  });

  // Escape wird von medium-zoom selbst behandelt.
  //
  // Das Bild bekommt bewusst KEIN role/tabindex mehr. Es liegt in einem
  // <button> aus templates/flexible/gallery.blade.php; ein zweites
  // fokussierbares Element darin ergab pro Bild zwei Tab-Stopps (gemessen: 12
  // bei 6 Bildern), und interaktiver Inhalt in einem <button> ist ungueltiges
  // HTML. Der Button war zugleich ohne Funktion, weil medium-zoom nur auf dem
  // Bild lauscht: mit der Maus aufs Bild klappte, per Tastatur landete man auf
  // dem Button und Enter tat nichts.
  zoomElements.forEach((el) => {
    el.removeAttribute('role');
    el.removeAttribute('tabindex');
    el.removeAttribute('aria-label');

    const trigger = el.closest('button');
    if (trigger) {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        zoom.open({ target: el as HTMLImageElement });
      });
      return;
    }

    // Bild ohne umgebenden Button: dann traegt es die Rolle selbst.
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');
    el.setAttribute(
      'aria-label',
      (el.getAttribute('alt') || themeStrings.image) + ' - ' + themeStrings.imageZoomInstruction
    );
    el.addEventListener('keydown', (e) => {
      if ((e as KeyboardEvent).key === 'Enter' || (e as KeyboardEvent).key === ' ') {
        e.preventDefault();
        zoom.open({ target: el as HTMLImageElement });
      }
    });
  });
}

// ============================================
// Before/After Slider Component
// ============================================

export interface BeforeAfterComponent {
  position: number;
  handleMouseDown(event: MouseEvent): void;
  handleTouchStart(event: TouchEvent): void;
}

// Shared position math for both mouse and touch dragging.
function calculateSliderPosition(clientX: number, rect: DOMRect): number {
  return Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
}

export function createBeforeAfterComponent(): BeforeAfterComponent {
  return {
    position: 50,

    handleMouseDown(event: MouseEvent) {
      event.preventDefault();
      // Find the main container (the element with x-data)
      const handle = (event.target as HTMLElement).closest('.before-after-handle');
      const container = handle?.parentElement;
      if (!container) return;

      const rect = container.getBoundingClientRect();

      const onMove = (e: MouseEvent) => {
        this.position = calculateSliderPosition(e.clientX, rect);
      };

      const onUp = () => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      };

      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    },

    handleTouchStart(event: TouchEvent) {
      event.preventDefault();
      // Find the main container (the element with x-data)
      const handle = (event.target as HTMLElement).closest('.before-after-handle');
      const container = handle?.parentElement;
      if (!container) return;

      const rect = container.getBoundingClientRect();

      const onMove = (e: TouchEvent) => {
        const touch = e.touches[0];
        this.position = calculateSliderPosition(touch.clientX, rect);
      };

      const onEnd = () => {
        document.removeEventListener('touchmove', onMove as EventListener);
        document.removeEventListener('touchend', onEnd);
      };

      document.addEventListener('touchmove', onMove as EventListener);
      document.addEventListener('touchend', onEnd);
    },
  };
}

// ============================================
// Initialize Application
// ============================================

// Make Alpine available globally
window.Alpine = Alpine;

// Register Alpine plugins
Alpine.plugin(collapse);
Alpine.plugin(intersect);

// Register Alpine components
Alpine.data('navigation', createNavigationComponent);
Alpine.data('statsCounter', (target: number) => createStatsCounterComponent(target));
Alpine.data('beforeAfterSlider', createBeforeAfterComponent);

// Register member area components (only registered when the module is present)
registerMemberAreaComponents(Alpine);

// Start Alpine
Alpine.start();

// ============================================
// Header Height for Hero Block
// ============================================

/**
 * Measures the header height and sets a CSS custom property.
 * Used by Hero block for viewport-relative min-height calculations.
 */
export function initHeaderHeight(): void {
  const header = document.querySelector('header');
  if (!header) return;

  const updateHeight = (): void => {
    document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
  };

  // Set initial value
  updateHeight();

  // Update on resize using ResizeObserver (more efficient than window resize)
  const observer = new ResizeObserver(updateHeight);
  observer.observe(header);
}

// Initialize features on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
  initHeaderHeight();
  initColumnHeadingAlignment();
  initRybbitTracking();
  initVideoConsent();
  await initGalleryZoom();
});
