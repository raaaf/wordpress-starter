// Import Alpine.js
import Alpine from 'alpinejs';

// ============================================
// Navigation Component
// ============================================

export interface NavigationComponent {
  isOpen: boolean;
  toggle(): void;
  close(): void;
}

export function createNavigationComponent(): NavigationComponent {
  return {
    isOpen: false,
    toggle() {
      this.isOpen = !this.isOpen;
    },
    close() {
      this.isOpen = false;
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
