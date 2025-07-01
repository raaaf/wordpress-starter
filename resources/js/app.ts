// Import Alpine.js
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Alpine.js components
Alpine.data('navigation', () => ({
  isOpen: false,
  toggle() {
    this.isOpen = !this.isOpen;
  },
  close() {
    this.isOpen = false;
  },
}));

// Start Alpine
Alpine.start();

// Pirsch Analytics tracking for dynamic content links
document.addEventListener('DOMContentLoaded', () => {
  // Track links in prose content areas
  const contentBlocks = document.querySelectorAll<HTMLAnchorElement>(
    '.prose a, .one-column a, .two-columns a, .three-columns a, .four-columns a, .two-columns-images a, .one-third-columns a'
  );

  contentBlocks.forEach((link) => {
    // Skip if already has Pirsch tracking
    if (link.hasAttribute('pirsch-event')) return;

    // Determine if it's an external link
    const isExternal = link.hostname && link.hostname !== window.location.hostname;
    const linkText = link.textContent?.trim() || 'Unknown';

    // Add Pirsch tracking attributes
    link.setAttribute('pirsch-event', isExternal ? 'External_Link_Click' : 'Internal_Link_Click');
    link.setAttribute('pirsch-meta-key', 'content_link');
    link.setAttribute('pirsch-meta-link-text', linkText);
    link.setAttribute('pirsch-meta-link-url', link.href);

    // Find the parent block type
    const parentBlock = link.closest(
      '[class*="-column"], .hero, .cta-block, .video, .accordion, .two-columns-images, .one-third-columns'
    );
    if (parentBlock) {
      const blockType =
        parentBlock.className.match(
          /(one|two|three|four)-column(?:s)?(?:-images)?|one-third-columns|hero|cta-block|video|accordion/
        )?.[0] || 'unknown';
      link.setAttribute('pirsch-meta-block-type', blockType);
    }
  });

  // Track image links if any
  const imageLinks = document.querySelectorAll<HTMLAnchorElement>('.image a');
  imageLinks.forEach((link) => {
    if (link.hasAttribute('pirsch-event')) return;

    link.setAttribute('pirsch-event', 'Image_Link_Click');
    link.setAttribute('pirsch-meta-key', 'image_block');
    link.setAttribute('pirsch-meta-link-url', link.href);
  });
});
