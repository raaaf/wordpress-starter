// Import Alpine.js and plugins
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import type { AlpineMagics } from '../../src/types/alpine';
import mediumZoom from 'medium-zoom';
import { registerMemberAreaComponents } from './member-area';

// Declare localized strings from WordPress
declare const wpStarterStrings: {
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
  mobileNav: HTMLElement | null;
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
    mobileNav: null,
    mobileNavContainer: null,

    init() {
      this.toggleButton = this.$el.querySelector('[data-nav-toggle]');
      this.mobileNav = this.$el.querySelector('nav[x-show="isOpen"]');
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
        toggle.setAttribute('aria-label', wpStarterStrings.submenuOpen);
        toggle.innerHTML =
          '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';

        const toggleSubmenu = () => {
          const isExpanded = submenu.classList.toggle('is-open');
          toggle.setAttribute('aria-expanded', String(isExpanded));
          toggle.setAttribute(
            'aria-label',
            isExpanded ? wpStarterStrings.submenuClose : wpStarterStrings.submenuOpen
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
      if (!this.mobileNav) return [];
      const selector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
      return Array.from(this.mobileNav.querySelectorAll<HTMLElement>(selector));
    },
  };
}

// ============================================
// Stats Counter Component
// ============================================

export interface StatsCounterComponent extends AlpineMagics {
  target: number;
  current: number;
  duration: number;
  started: boolean;
  observer: IntersectionObserver | null;
  init(): void;
  animate(): void;
}

export function createStatsCounterComponent(target: number): StatsCounterComponent {
  return {
    // Alpine magic properties ($el, $nextTick, etc.) are injected at runtime
    ...({} as AlpineMagics),
    target,
    current: 0,
    duration: 2000,
    started: false,
    observer: null,

    init() {
      // Check for reduced motion preference - WCAG 2.3.3
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      this.observer = new IntersectionObserver(
        (entries) => {
          if (entries[0].isIntersecting && !this.started) {
            this.started = true;
            this.observer?.disconnect();
            // Skip animation if user prefers reduced motion
            if (prefersReducedMotion) {
              this.current = this.target;
            } else {
              this.animate();
            }
          }
        },
        { threshold: 0.5 }
      );
      this.observer.observe(this.$el as Element);
    },

    animate() {
      const start = performance.now();
      const step = (timestamp: number) => {
        const progress = Math.min((timestamp - start) / this.duration, 1);
        this.current = Math.floor(progress * this.target);
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          this.current = this.target;
        }
      };
      requestAnimationFrame(step);
    },
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

export function initGalleryZoom(): void {
  const zoomElements = document.querySelectorAll('.gallery-zoom');
  if (zoomElements.length === 0) return;

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

  // Escape key is handled by medium-zoom by default
  // Add keyboard instruction for screen readers
  zoomElements.forEach((el) => {
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');
    el.setAttribute(
      'aria-label',
      (el.getAttribute('alt') || wpStarterStrings.image) +
        ' - ' +
        wpStarterStrings.imageZoomInstruction
    );

    // Allow Enter key to trigger zoom
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
  init(): void;
  handleMouseDown(event: MouseEvent): void;
  handleTouchStart(event: TouchEvent): void;
}

export function createBeforeAfterComponent(): BeforeAfterComponent {
  return {
    position: 50,

    init() {
      // Component initialized
    },

    handleMouseDown(event: MouseEvent) {
      event.preventDefault();
      // Find the main container (the element with x-data)
      const handle = (event.target as HTMLElement).closest('.before-after-handle');
      const container = handle?.parentElement;
      if (!container) return;

      const rect = container.getBoundingClientRect();

      const onMove = (e: MouseEvent) => {
        this.position = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
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
        this.position = Math.max(
          0,
          Math.min(100, ((touch.clientX - rect.left) / rect.width) * 100)
        );
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
// Logo Slider Component
// ============================================

export interface LogoSliderLogo {
  image: string;
  link: string;
  name: string;
}

export interface LogoSliderComponent {
  logos: LogoSliderLogo[];
  autoplay: boolean;
  currentIndex: number;
  paused: boolean;
  intervalId: ReturnType<typeof setInterval> | null;
  init(): void;
  pause(): void;
  resume(): void;
  destroy(): void;
}

export function createLogoSliderComponent(
  logos: LogoSliderLogo[],
  autoplay: boolean
): LogoSliderComponent {
  return {
    logos,
    autoplay,
    currentIndex: 0,
    paused: false,
    intervalId: null,

    init() {
      if (this.autoplay && this.logos.length > 1) {
        this.intervalId = setInterval(() => {
          if (!this.paused) {
            this.currentIndex = (this.currentIndex + 1) % this.logos.length;
          }
        }, 3000);
      }
    },

    pause() {
      this.paused = true;
    },

    resume() {
      this.paused = false;
    },

    destroy() {
      if (this.intervalId) {
        clearInterval(this.intervalId);
      }
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
Alpine.data('logoSlider', (logos: LogoSliderLogo[], autoplay: boolean) =>
  createLogoSliderComponent(logos, autoplay)
);

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
document.addEventListener('DOMContentLoaded', () => {
  initHeaderHeight();
  initRybbitTracking();
  initVideoConsent();
  initGalleryZoom();
});
