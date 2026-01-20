import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import {
  initPirschTracking,
  addContentLinkTracking,
  addImageLinkTracking,
  extractBlockType,
  CONTENT_SELECTORS,
  BLOCK_TYPE_REGEX,
} from './app';

/**
 * Tests for Pirsch Analytics tracking functionality.
 */
describe('Pirsch Analytics Tracking', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('initPirschTracking', () => {
    it('skips links with existing pirsch-event attribute', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="https://example.com" pirsch-event="Custom_Event">Already tracked</a>
        </div>
      `;

      initPirschTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('pirsch-event')).toBe('Custom_Event');
    });

    it('adds External_Link_Click for external links', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="https://external-site.com/page">External Link</a>
        </div>
      `;

      initPirschTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('pirsch-event')).toBe('External_Link_Click');
    });

    it('adds Internal_Link_Click for internal links', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="/internal-page">Internal Link</a>
        </div>
      `;

      initPirschTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('pirsch-event')).toBe('Internal_Link_Click');
    });

    it('handles links with no parent block', () => {
      document.body.innerHTML = `
        <div class="untracked-container">
          <a href="/page">Orphan Link</a>
        </div>
      `;

      initPirschTracking();

      const link = document.querySelector('a')!;
      expect(link.hasAttribute('pirsch-event')).toBe(false);
    });
  });

  describe('addContentLinkTracking', () => {
    it('extracts link text for meta attribute', () => {
      document.body.innerHTML = `<a href="/page">Click Here for More</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-link-text')).toBe('Click Here for More');
    });

    it('handles links with no text content using Unknown', () => {
      document.body.innerHTML = `<a href="/page"></a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-link-text')).toBe('Unknown');
    });

    it('sets pirsch-meta-link-url attribute', () => {
      document.body.innerHTML = `<a href="https://example.com/specific-page">Test</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-link-url')).toBe('https://example.com/specific-page');
    });

    it('sets pirsch-meta-key to content_link', () => {
      document.body.innerHTML = `<a href="/page">Content Link</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-key')).toBe('content_link');
    });

    it('trims whitespace from link text', () => {
      document.body.innerHTML = `<a href="/page">
        Link with whitespace
      </a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-link-text')).toBe('Link with whitespace');
    });

    it('skips if already has pirsch-event', () => {
      document.body.innerHTML = `<a href="/page" pirsch-event="Existing">Link</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('pirsch-event')).toBe('Existing');
      expect(link.hasAttribute('pirsch-meta-key')).toBe(false);
    });
  });

  describe('addImageLinkTracking', () => {
    it('sets Image_Link_Click event', () => {
      document.body.innerHTML = `<a href="/image-page"><img src="/image.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('pirsch-event')).toBe('Image_Link_Click');
    });

    it('sets pirsch-meta-key to image_block', () => {
      document.body.innerHTML = `<a href="/image-page"><img src="/image.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-key')).toBe('image_block');
    });

    it('sets pirsch-meta-link-url', () => {
      document.body.innerHTML = `<a href="https://example.com/image"><img src="/img.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('pirsch-meta-link-url')).toBe('https://example.com/image');
    });

    it('skips if already has pirsch-event', () => {
      document.body.innerHTML = `<a href="/page" pirsch-event="Custom"><img /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('pirsch-event')).toBe('Custom');
    });
  });

  describe('extractBlockType', () => {
    it('extracts block type from parent element', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="/page">Link</a>
        </div>
      `;
      const link = document.querySelector('a')!;

      const blockType = extractBlockType(link);

      expect(blockType).toBe('two-columns');
    });

    it('returns null for elements without parent block', () => {
      document.body.innerHTML = `
        <div class="untracked">
          <a href="/page">Link</a>
        </div>
      `;
      const link = document.querySelector('a')!;

      const blockType = extractBlockType(link);

      expect(blockType).toBeNull();
    });

    it('correctly matches various column block types', () => {
      const testCases = [
        { className: 'one-column', expected: 'one-column' },
        { className: 'two-columns', expected: 'two-columns' },
        { className: 'three-columns', expected: 'three-columns' },
        { className: 'four-columns', expected: 'four-columns' },
        { className: 'two-columns-images', expected: 'two-columns-images' },
        { className: 'one-third-columns', expected: 'one-third-columns' },
      ];

      testCases.forEach(({ className, expected }) => {
        document.body.innerHTML = `
          <div class="${className}">
            <a href="/page">Test Link</a>
          </div>
        `;
        const link = document.querySelector('a')!;

        const blockType = extractBlockType(link);

        expect(blockType).toBe(expected);
      });
    });
  });

  describe('BLOCK_TYPE_REGEX', () => {
    it('matches column patterns', () => {
      expect('one-column'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('one-column');
      expect('two-columns'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('two-columns');
      expect('three-columns'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('three-columns');
      expect('four-columns'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('four-columns');
    });

    it('matches special block types', () => {
      expect('hero'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('hero');
      expect('cta-block'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('cta-block');
      expect('video'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('video');
      expect('accordion'.match(BLOCK_TYPE_REGEX)?.[0]).toBe('accordion');
    });
  });

  describe('CONTENT_SELECTORS', () => {
    it('includes all expected selectors', () => {
      expect(CONTENT_SELECTORS).toContain('.prose a');
      expect(CONTENT_SELECTORS).toContain('.one-column a');
      expect(CONTENT_SELECTORS).toContain('.two-columns a');
      expect(CONTENT_SELECTORS).toContain('.three-columns a');
      expect(CONTENT_SELECTORS).toContain('.four-columns a');
    });
  });
});
