// Import Alpine.js
import Alpine from 'alpinejs';

// ============================================
// Navigation Component
// ============================================

export interface NavigationComponent {
  isOpen: boolean;
  toggleButton: HTMLElement | null;
  mobileNav: HTMLElement | null;
  toggle(): void;
  close(): void;
  init(): void;
  handleKeydown(event: KeyboardEvent): void;
  trapFocus(event: KeyboardEvent): void;
  getFocusableElements(): HTMLElement[];
}

export function createNavigationComponent(): NavigationComponent {
  return {
    isOpen: false,
    toggleButton: null,
    mobileNav: null,

    init() {
      this.toggleButton = this.$el.querySelector('[aria-label="Toggle navigation menu"]');
      this.mobileNav = this.$el.querySelector('nav[x-show="isOpen"]');
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
// Pirsch Analytics Tracking
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
  if (link.hasAttribute('pirsch-event')) return;

  const isExternal = link.hostname && link.hostname !== window.location.hostname;
  const linkText = link.textContent?.trim() || 'Unknown';

  link.setAttribute('pirsch-event', isExternal ? 'External_Link_Click' : 'Internal_Link_Click');
  link.setAttribute('pirsch-meta-key', 'content_link');
  link.setAttribute('pirsch-meta-link-text', linkText);
  link.setAttribute('pirsch-meta-link-url', link.href);

  const blockType = extractBlockType(link);
  if (blockType) {
    link.setAttribute('pirsch-meta-block-type', blockType);
  }
}

export function addImageLinkTracking(link: HTMLAnchorElement): void {
  if (link.hasAttribute('pirsch-event')) return;

  link.setAttribute('pirsch-event', 'Image_Link_Click');
  link.setAttribute('pirsch-meta-key', 'image_block');
  link.setAttribute('pirsch-meta-link-url', link.href);
}

export function initPirschTracking(): void {
  const contentLinks = document.querySelectorAll<HTMLAnchorElement>(CONTENT_SELECTORS);
  contentLinks.forEach(addContentLinkTracking);

  const imageLinks = document.querySelectorAll<HTMLAnchorElement>('.image a');
  imageLinks.forEach(addImageLinkTracking);
}

// ============================================
// Initialize Application
// ============================================

// Make Alpine available globally
window.Alpine = Alpine;

// Register Alpine components
Alpine.data('navigation', createNavigationComponent);

// Start Alpine
Alpine.start();

// Initialize Pirsch tracking on DOM ready
document.addEventListener('DOMContentLoaded', initPirschTracking);
