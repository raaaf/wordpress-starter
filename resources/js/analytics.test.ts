import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  initRybbitTracking,
  addContentLinkTracking,
  addImageLinkTracking,
  extractBlockType,
  CONTENT_SELECTORS,
  BLOCK_TYPE_REGEX,
  initVideoConsent,
} from './app';

/**
 * Tests for Rybbit Analytics tracking functionality.
 */
describe('Rybbit Analytics Tracking', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('initRybbitTracking', () => {
    it('skips links with existing data-rybbit-event attribute', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="https://example.com" data-rybbit-event="Custom_Event">Already tracked</a>
        </div>
      `;

      initRybbitTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('data-rybbit-event')).toBe('Custom_Event');
    });

    it('adds External_Link_Click for external links', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="https://external-site.com/page">External Link</a>
        </div>
      `;

      initRybbitTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('data-rybbit-event')).toBe('External_Link_Click');
    });

    it('adds Internal_Link_Click for internal links', () => {
      document.body.innerHTML = `
        <div class="two-columns">
          <a href="/internal-page">Internal Link</a>
        </div>
      `;

      initRybbitTracking();

      const link = document.querySelector('a')!;
      expect(link.getAttribute('data-rybbit-event')).toBe('Internal_Link_Click');
    });

    it('handles links with no parent block', () => {
      document.body.innerHTML = `
        <div class="untracked-container">
          <a href="/page">Orphan Link</a>
        </div>
      `;

      initRybbitTracking();

      const link = document.querySelector('a')!;
      expect(link.hasAttribute('data-rybbit-event')).toBe(false);
    });
  });

  describe('addContentLinkTracking', () => {
    it('extracts link text for prop attribute', () => {
      document.body.innerHTML = `<a href="/page">Click Here for More</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-text')).toBe('Click Here for More');
    });

    it('handles links with no text content using Unknown', () => {
      document.body.innerHTML = `<a href="/page"></a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-text')).toBe('Unknown');
    });

    it('sets data-rybbit-prop-link-url attribute', () => {
      document.body.innerHTML = `<a href="https://example.com/specific-page">Test</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-url')).toBe(
        'https://example.com/specific-page'
      );
    });

    it('sets data-rybbit-prop-key to content_link', () => {
      document.body.innerHTML = `<a href="/page">Content Link</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-key')).toBe('content_link');
    });

    it('trims whitespace from link text', () => {
      document.body.innerHTML = `<a href="/page">
        Link with whitespace
      </a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-text')).toBe('Link with whitespace');
    });

    it('skips if already has data-rybbit-event', () => {
      document.body.innerHTML = `<a href="/page" data-rybbit-event="Existing">Link</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-event')).toBe('Existing');
      expect(link.hasAttribute('data-rybbit-prop-key')).toBe(false);
    });

    it('does not forward a mailto address as an analytics property, even when the visible text is the address itself', () => {
      document.body.innerHTML = `<a href="mailto:jane.doe@example.com">jane.doe@example.com</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.hasAttribute('data-rybbit-prop-link-url')).toBe(false);
      expect(link.hasAttribute('data-rybbit-prop-link-text')).toBe(false);
      expect(link.getAttribute('data-rybbit-prop-link-type')).toBe('mailto');
    });

    it('does not forward a tel number as an analytics property, even when the visible text is the number itself', () => {
      document.body.innerHTML = `<a href="tel:+491234567890">+491234567890</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.hasAttribute('data-rybbit-prop-link-url')).toBe(false);
      expect(link.hasAttribute('data-rybbit-prop-link-text')).toBe(false);
      expect(link.getAttribute('data-rybbit-prop-link-type')).toBe('tel');
    });

    it('does not forward an sms number as an analytics property, even when the visible text is the number itself', () => {
      document.body.innerHTML = `<a href="sms:+491234567890">+491234567890</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.hasAttribute('data-rybbit-prop-link-url')).toBe(false);
      expect(link.hasAttribute('data-rybbit-prop-link-text')).toBe(false);
      expect(link.getAttribute('data-rybbit-prop-link-type')).toBe('sms');
    });

    it('keeps url and text for a normal https link', () => {
      document.body.innerHTML = `<a href="https://example.com/page">Read more</a>`;
      const link = document.querySelector('a')!;

      addContentLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-url')).toBe('https://example.com/page');
      expect(link.getAttribute('data-rybbit-prop-link-text')).toBe('Read more');
      expect(link.hasAttribute('data-rybbit-prop-link-type')).toBe(false);
    });
  });

  describe('addImageLinkTracking', () => {
    it('sets Image_Link_Click event', () => {
      document.body.innerHTML = `<a href="/image-page"><img src="/image.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('data-rybbit-event')).toBe('Image_Link_Click');
    });

    it('sets data-rybbit-prop-key to image_block', () => {
      document.body.innerHTML = `<a href="/image-page"><img src="/image.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-key')).toBe('image_block');
    });

    it('sets data-rybbit-prop-link-url', () => {
      document.body.innerHTML = `<a href="https://example.com/image"><img src="/img.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('data-rybbit-prop-link-url')).toBe('https://example.com/image');
    });

    it('skips if already has data-rybbit-event', () => {
      document.body.innerHTML = `<a href="/page" data-rybbit-event="Custom"><img /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.getAttribute('data-rybbit-event')).toBe('Custom');
    });

    it('does not forward the address for an image wrapped in a mailto link', () => {
      document.body.innerHTML = `<a href="mailto:jane.doe@example.com"><img src="/img.jpg" /></a>`;
      const link = document.querySelector('a')!;

      addImageLinkTracking(link);

      expect(link.hasAttribute('data-rybbit-prop-link-url')).toBe(false);
      expect(link.getAttribute('data-rybbit-prop-link-type')).toBe('mailto');
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

  describe('initVideoConsent', () => {
    let stderrWriteSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
      // Prevents happy-dom from actually fetching the iframe src over the
      // network once it is set; only the attribute value matters for these
      // tests, not the real page load.
      const happyDOM = (window as unknown as { happyDOM?: { settings?: Record<string, boolean> } })
        .happyDOM;
      if (happyDOM?.settings) {
        happyDOM.settings.disableIframePageLoading = true;
      }

      // happy-dom reports the loading-disabled setting above via its own
      // internal console reference, which writes straight to process.stderr
      // and bypasses vitest's per-test console capture (spying on `console`
      // here has no effect on it). That output is expected fallout of
      // disabling iframe loading in this fixture, not a real test failure,
      // so swallow only that specific message; anything else still reaches
      // the real stderr.
      const originalWrite = process.stderr.write.bind(process.stderr);
      stderrWriteSpy = vi
        .spyOn(process.stderr, 'write')
        .mockImplementation((chunk: unknown, ...rest: unknown[]) => {
          if (typeof chunk === 'string' && chunk.includes('Iframe page loading is disabled')) {
            return true;
          }
          return (originalWrite as (...args: unknown[]) => boolean)(chunk, ...rest);
        });
    });

    afterEach(() => {
      stderrWriteSpy.mockRestore();
    });

    it('does not load the embed before consent is given', () => {
      document.body.innerHTML = `
        <div class="video">
          <button class="video-consent-btn">Consent</button>
          <iframe data-src="https://example.com/embed"></iframe>
        </div>
      `;

      initVideoConsent();

      const iframe = document.querySelector('iframe')!;
      expect(iframe.getAttribute('src')).toBeNull();
    });

    it('loads the embed only after the consent button is clicked', () => {
      document.body.innerHTML = `
        <div class="video">
          <button class="video-consent-btn">Consent</button>
          <iframe data-src="https://example.com/embed"></iframe>
        </div>
      `;

      initVideoConsent();

      const btn = document.querySelector<HTMLButtonElement>('.video-consent-btn')!;
      btn.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));

      const iframe = document.querySelector('iframe')!;
      expect(iframe.getAttribute('src')).toBe('https://example.com/embed');
    });
  });
});
