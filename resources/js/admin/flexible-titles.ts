/**
 * Auto-generate layout titles for ACF Extended Flexible Content
 * Extracts text from content fields and displays as layout subtitle
 */

declare const acf: {
  addAction: (action: string, callback: (field?: AcfField) => void) => void;
};

declare const tinymce: {
  on: (event: string, callback: (e: { editor: TinyMCEEditor }) => void) => void;
};

interface AcfField {
  $el?: JQuery<HTMLElement>;
}

interface JQuery<T> {
  [index: number]: T;
}

interface TinyMCEEditor {
  on: (events: string, callback: () => void) => void;
}

// Fields to check for title content, in priority order
const TITLE_FIELDS: readonly string[] = ['title', 'heading', 'headline', 'name', 'label'];
const CONTENT_FIELDS: readonly string[] = [
  'content',
  'text',
  'description',
  'wysiwyg',
  'message',
  'column_1',
  'column_2',
  'column_left',
  'column_right',
  'copy',
];
const MAX_LENGTH = 40;

/**
 * Truncate text to max length with ellipsis
 */
function truncate(text: string, maxLength: number): string {
  const normalized = text.trim().replace(/\s+/g, ' ');
  if (normalized.length <= maxLength) return normalized;
  return normalized.substring(0, maxLength) + '…';
}

/**
 * Strip HTML tags from string
 */
function stripTags(html: string): string {
  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}

/**
 * Get preview text from a layout element
 */
function getLayoutPreview(layout: HTMLElement): string | null {
  // Try title fields first
  for (const fieldName of TITLE_FIELDS) {
    const field = layout.querySelector<HTMLInputElement | HTMLTextAreaElement>(
      `[data-name="${fieldName}"] input[type="text"], [data-name="${fieldName}"] textarea`
    );
    if (field && field.value && field.value.trim()) {
      return truncate(stripTags(field.value), MAX_LENGTH);
    }
  }

  // Try content fields (including WYSIWYG)
  for (const fieldName of CONTENT_FIELDS) {
    // Check regular inputs/textareas
    const field = layout.querySelector<HTMLInputElement | HTMLTextAreaElement>(
      `[data-name="${fieldName}"] input[type="text"], [data-name="${fieldName}"] textarea:not(.wp-editor-area)`
    );
    if (field && field.value && field.value.trim()) {
      return truncate(stripTags(field.value), MAX_LENGTH);
    }

    // Check WYSIWYG textarea (source mode)
    const wysiwygTextarea = layout.querySelector<HTMLTextAreaElement>(
      `[data-name="${fieldName}"] textarea.wp-editor-area`
    );
    if (wysiwygTextarea && wysiwygTextarea.value && wysiwygTextarea.value.trim()) {
      return truncate(stripTags(wysiwygTextarea.value), MAX_LENGTH);
    }

    // Check WYSIWYG iframe content (visual mode)
    const wysiwyg = layout.querySelector<HTMLIFrameElement>(`[data-name="${fieldName}"] iframe`);
    if (wysiwyg) {
      try {
        const body = wysiwyg.contentDocument?.body;
        if (body && body.textContent && body.textContent.trim()) {
          return truncate(body.textContent, MAX_LENGTH);
        }
      } catch {
        // Cross-origin iframe, skip
      }
    }
  }

  // Count repeater items if no text content found
  const repeater = layout.querySelector('.acf-repeater');
  if (repeater) {
    const rows = repeater.querySelectorAll(':scope > table > tbody > tr.acf-row:not(.acf-clone)');
    if (rows.length > 0) {
      const label = rows.length === 1 ? 'Eintrag' : 'Einträge';
      return `${rows.length} ${label}`;
    }
  }

  return null;
}

/**
 * Update a single layout's title
 */
function updateLayoutTitle(layout: HTMLElement): void {
  // Skip clone/template layouts
  if (layout.classList.contains('acf-clone') || layout.style.display === 'none') {
    return;
  }

  // Find the title element - ACF uses .acf-fc-layout-title
  const titleEl = layout.querySelector<HTMLElement>('.acf-fc-layout-title');
  if (!titleEl) {
    return;
  }

  const preview = getLayoutPreview(layout);

  if (!preview) {
    // Remove existing preview if no content
    const existingPreview = titleEl.querySelector('.layout-preview-text');
    if (existingPreview) existingPreview.remove();
    return;
  }

  // Find or create the preview span (as sibling after title, not inside)
  const handle = titleEl.parentElement;
  let previewSpan = handle?.querySelector<HTMLSpanElement>('.layout-preview-text');

  if (!previewSpan && handle) {
    previewSpan = document.createElement('span');
    previewSpan.className = 'layout-preview-text';
    previewSpan.style.cssText =
      'color: #888; font-weight: normal; margin-left: 5px; font-size: 13px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-shrink: 1; min-width: 0;';
    titleEl.after(previewSpan);
  }
  if (previewSpan) {
    previewSpan.textContent = `– ${preview}`;
  }
}

/**
 * Update all layout titles on the page
 */
function updateAllLayoutTitles(): void {
  // ACF Extended layout selectors
  const layoutSelectors = [
    '.acf-flexible-content .layout',
    '.acf-flexible-content .acf-layout',
    '[data-layout]',
  ];

  const allLayouts = new Set<HTMLElement>();
  for (const selector of layoutSelectors) {
    document.querySelectorAll<HTMLElement>(selector).forEach((l) => allLayouts.add(l));
  }

  allLayouts.forEach(updateLayoutTitle);
}

/**
 * Debounce function
 */
function debounce<T extends (...args: unknown[]) => void>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout> | undefined;
  return function (...args: Parameters<T>): void {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
}

const debouncedUpdate = debounce(updateAllLayoutTitles, 300);

/**
 * Initialize when ACF is ready
 */
function init(): void {
  // Check if ACF is available
  if (typeof acf !== 'undefined') {
    // Update when layouts are reordered, added, or duplicated
    acf.addAction('sortstop', debouncedUpdate);
    acf.addAction('append', debouncedUpdate);
    acf.addAction('duplicate', debouncedUpdate);
    acf.addAction('show', debouncedUpdate);
    acf.addAction('ready', debouncedUpdate);
    acf.addAction('load', debouncedUpdate);

    // Update on field changes
    acf.addAction('change', function () {
      debouncedUpdate();
    });
  }

  // Also listen for regular input events (for faster feedback)
  document.addEventListener('input', debouncedUpdate, true);
  document.addEventListener('change', debouncedUpdate, true);

  // Watch for TinyMCE
  if (typeof tinymce !== 'undefined') {
    tinymce.on('AddEditor', function (e) {
      e.editor.on('keyup change input', debouncedUpdate);
    });
  }

  // Also set up a MutationObserver to catch dynamic content
  const observer = new MutationObserver(function (mutations) {
    let shouldUpdate = false;
    for (const mutation of mutations) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        shouldUpdate = true;
        break;
      }
    }
    if (shouldUpdate) {
      debouncedUpdate();
    }
  });

  const flexibleContainers = document.querySelectorAll('.acf-flexible-content');
  flexibleContainers.forEach((container) => {
    observer.observe(container, { childList: true, subtree: true });
  });
}

// Wait for DOM and ACF to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// Also initialize when ACF is ready
if (typeof acf !== 'undefined') {
  acf.addAction('ready', init);
}
