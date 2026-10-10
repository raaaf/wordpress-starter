/**
 * Jump menu (Sprungmenü): section list and in-page search.
 *
 * Collects the top-level sections of a page, searches their whole text (closed
 * tabs and accordions included, because textContent ignores visibility), and
 * reveals a hit by clicking the existing tab or accordion button that holds it.
 * The tab and accordion templates stay untouched: this module only drives
 * their public ARIA contract (role=tab + aria-controls, aria-expanded).
 */
import type { AlpineMagics } from '../../src/types/alpine';

export interface JumpSection {
  id: string;
  label: string;
  text: string;
  /**
   * False when the label is inherited from the preceding chapter. Such a section
   * can only be found through its text, never through the borrowed label.
   */
  ownLabel?: boolean;
}

export interface JumpHit {
  id: string;
  label: string;
  /** before + match + after */
  snippet: string;
  before: string;
  /** The first match in its original casing, '' for a label-only hit. */
  match: string;
  after: string;
  /** Non-overlapping matches in the section text, 0 for a label-only hit. */
  count: number;
}

const MIN_QUERY_LENGTH = 2;
/** Characters of context shown on each side of a text match. */
const SNIPPET_CONTEXT = 40;
/** Characters of body text shown for a hit that only matched the label. */
const LABEL_SNIPPET_LENGTH = 80;
/** Tab height animation is 250 ms plus a 100 ms fallback in tabs.blade.php. */
const TAB_ANIMATION_MS = 360;
const HIGHLIGHT_NAME = 'jump-menu-search';

function collapse(value: string): string {
  return value.replace(/\s+/g, ' ').trim();
}

function lower(value: string): string {
  return value.toLocaleLowerCase('de');
}

/** The trimmed, lowercased needle, or null while the query is too short to search. */
function normalizeQuery(query: string): string | null {
  const needle = lower(collapse(query));
  return needle.length >= MIN_QUERY_LENGTH ? needle : null;
}

const isSpace = (char: string | undefined): boolean => char === ' ';

/** True when a cut between index - 1 and index would split a word. */
function splitsWord(text: string, index: number): boolean {
  return index > 0 && index < text.length && !isSpace(text[index - 1]) && !isSpace(text[index]);
}

function countOccurrences(haystack: string, needle: string): number {
  let count = 0;

  for (
    let from = haystack.indexOf(needle);
    from !== -1;
    from = haystack.indexOf(needle, from + needle.length)
  ) {
    count++;
  }

  return count;
}

/**
 * The text around the first match, widened by SNIPPET_CONTEXT characters on
 * each side and trimmed back to whole words where the window cuts a word. The
 * match itself is never cut.
 */
function textHit(text: string, matchIndex: number, matchLength: number, count: number) {
  const matchEnd = matchIndex + matchLength;
  let start = Math.max(0, matchIndex - SNIPPET_CONTEXT);
  let end = Math.min(text.length, matchEnd + SNIPPET_CONTEXT);

  if (splitsWord(text, start)) {
    const space = text.indexOf(' ', start);
    if (space !== -1 && space < matchIndex) {
      start = space + 1;
    }
  }

  if (splitsWord(text, end)) {
    const space = text.lastIndexOf(' ', end - 1);
    if (space >= matchEnd) {
      end = space;
    }
  }

  return {
    before: (start > 0 ? '…' : '') + text.slice(start, matchIndex),
    match: text.slice(matchIndex, matchEnd),
    after: text.slice(matchEnd, end) + (end < text.length ? '…' : ''),
    count,
  };
}

/** The start of the text for a hit that only matched the label, cut at a word boundary. */
function labelHit(text: string) {
  let end = text.length;

  if (text.length > LABEL_SNIPPET_LENGTH) {
    end = LABEL_SNIPPET_LENGTH;
    if (splitsWord(text, end)) {
      const space = text.lastIndexOf(' ', end - 1);
      if (space > 0) {
        end = space;
      }
    }
  }

  return {
    before: '',
    match: '',
    after: text.slice(0, end) + (end < text.length ? '…' : ''),
    count: 0,
  };
}

/** Sections whose label or text contains the query, in input order. */
export function searchSections(sections: JumpSection[], query: string): JumpHit[] {
  const needle = normalizeQuery(query);
  if (needle === null) {
    return [];
  }

  const hits: JumpHit[] = [];

  for (const section of sections) {
    const text = collapse(section.text);
    const lowerText = lower(text);
    const matchIndex = lowerText.indexOf(needle);

    let parts;
    if (matchIndex !== -1) {
      parts = textHit(text, matchIndex, needle.length, countOccurrences(lowerText, needle));
    } else if (section.ownLabel !== false && lower(section.label).includes(needle)) {
      parts = labelHit(text);
    } else {
      continue;
    }

    hits.push({
      id: section.id,
      label: section.label,
      snippet: parts.before + parts.match + parts.after,
      ...parts,
    });
  }

  return hits;
}

/** Descendants that are page chrome or invisible to readers, not searchable copy. */
const TEXT_EXCLUDED =
  'nav, [aria-hidden="true"]:not([role="tabpanel"]), .sr-only, script, style, template';

const BLOCK_ELEMENTS =
  'h1, h2, h3, h4, h5, h6, p, div, li, dt, dd, td, th, tr, button, summary, blockquote, figcaption, br';

/**
 * The section's text without its label heading and without UI chrome. Tab
 * panels stay in: inactive ones carry aria-hidden but hold searchable copy.
 */
function sectionText(element: Element): string {
  const clone = element.cloneNode(true) as Element;
  const heading = clone.querySelector('h2') ?? clone.querySelector('h3');

  heading?.remove();
  clone.querySelectorAll(TEXT_EXCLUDED).forEach((node) => node.remove());
  // textContent glues adjacent blocks together ("Sub" + "Body" = "SubBody").
  clone.querySelectorAll(BLOCK_ELEMENTS).forEach((node) => node.append(' '));

  return collapse(clone.textContent ?? '');
}

function listedSections(root: ParentNode): HTMLElement[] {
  return Array.from(root.querySelectorAll<HTMLElement>('section.section[id]')).filter(
    (element) => element.dataset.jumpMenu !== 'hidden'
  );
}

/** The section's own first h2 (fallback h3), or '' without a heading. */
function ownLabel(element: Element): string {
  const heading = element.querySelector('h2') ?? element.querySelector('h3');

  return collapse(heading?.textContent ?? '');
}

/**
 * Top-level sections with an id, labelled by their first h2 (fallback h3), in
 * DOM order. Sections without a heading and sections opted out via
 * data-jump-menu="hidden" are skipped.
 */
export function collectSections(root: ParentNode): JumpSection[] {
  const sections: JumpSection[] = [];

  for (const element of listedSections(root)) {
    const label = ownLabel(element);
    if (label !== '') {
      sections.push({ id: element.id, label, text: sectionText(element), ownLabel: true });
    }
  }

  return sections;
}

/**
 * Every listed section, headed or not, for the search. A section without its
 * own heading takes the label of the closest preceding headed section (the
 * chapter it belongs to), or '' when none precedes.
 */
export function collectSearchSections(root: ParentNode): JumpSection[] {
  let chapter = '';

  return listedSections(root).map((element) => {
    const own = ownLabel(element);
    chapter = own || chapter;

    return {
      id: element.id,
      label: chapter,
      text: sectionText(element),
      ownLabel: own !== '',
    };
  });
}

/**
 * The text nodes sectionText() would keep: the same excluded subtrees and the
 * label heading are skipped, so a hit found in the text can be found in the DOM.
 */
function textNodes(root: Element): Text[] {
  const heading = root.querySelector('h2') ?? root.querySelector('h3');
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode: (node) => {
      const parent = node.parentElement;
      const excluded = parent?.closest(TEXT_EXCLUDED);

      return (excluded && root.contains(excluded)) || (heading && heading.contains(node))
        ? NodeFilter.FILTER_REJECT
        : NodeFilter.FILTER_ACCEPT;
    },
  });
  const nodes: Text[] = [];

  while (walker.nextNode()) {
    nodes.push(walker.currentNode as Text);
  }

  return nodes;
}

function clearHighlights(): void {
  if (typeof CSS !== 'undefined' && 'highlights' in CSS) {
    CSS.highlights.delete(HIGHLIGHT_NAME);
  }
}

/**
 * Ranges of every case-insensitive match of `needle` (already normalised) in
 * the section's searchable text, one range per touched text node.
 *
 * Matching runs on the concatenated, whitespace-collapsed text, like
 * searchSections() does, so a match may span several nodes (`<strong>`) and
 * agrees with what the hit list promised. An offset map leads back to the nodes.
 * With `firstOnly` the search stops after the first occurrence.
 */
function matchRanges(root: Element, needle: string, firstOnly = false): Range[] {
  const parts: { node: Text; start: number; end: number }[] = [];
  let raw = '';
  let previousBlock: Element | null = null;

  for (const node of textNodes(root)) {
    // A separator between blocks, mirroring the spaces sectionText() appends.
    const block = node.parentElement?.closest(BLOCK_ELEMENTS) ?? null;
    if (parts.length > 0 && block !== previousBlock) {
      raw += ' ';
    }
    previousBlock = block;

    parts.push({ node, start: raw.length, end: raw.length + node.data.length });
    raw += node.data;
  }

  // Collapse whitespace runs and trim, remembering where each kept character came from.
  const rawIndex: number[] = [];
  let collapsed = '';
  for (let i = 0; i < raw.length; i++) {
    const isSpace = /\s/.test(raw[i]);
    if (isSpace && (collapsed === '' || collapsed.endsWith(' '))) {
      continue;
    }
    collapsed += isSpace ? ' ' : raw[i];
    rawIndex.push(i);
  }

  const haystack = lower(collapsed);
  const ranges: Range[] = [];

  for (
    let from = haystack.indexOf(needle);
    from !== -1;
    from = haystack.indexOf(needle, from + needle.length)
  ) {
    const rawStart = rawIndex[from];
    const rawEnd = rawIndex[from + needle.length - 1] + 1;

    for (const part of parts) {
      const start = Math.max(rawStart, part.start);
      const end = Math.min(rawEnd, part.end);
      if (start < end) {
        const range = document.createRange();
        range.setStart(part.node, start - part.start);
        range.setEnd(part.node, end - part.start);
        ranges.push(range);
      }
    }

    if (firstOnly && ranges.length > 0) {
      break;
    }
  }

  return ranges;
}

/**
 * Ranges of every case-insensitive match inside root. Paints them through the
 * CSS Custom Highlight API where available; elsewhere the ranges are returned
 * and nothing is painted.
 */
export function highlightMatches(root: Element, query: string): Range[] {
  clearHighlights();

  const needle = normalizeQuery(query);
  if (needle === null) {
    return [];
  }

  const ranges = matchRanges(root, needle);

  if (ranges.length > 0 && typeof CSS !== 'undefined' && 'highlights' in CSS) {
    CSS.highlights.set(HIGHLIGHT_NAME, new Highlight(...ranges));
  }

  return ranges;
}

function controllerOf(section: Element, id: string, selector = ''): HTMLElement | null {
  const candidates = section.querySelectorAll<HTMLElement>(`[aria-controls]${selector}`);

  return Array.from(candidates).find((el) => el.getAttribute('aria-controls') === id) ?? null;
}

/**
 * Opens the tab panel or accordion item holding the first match, innermost
 * container first so an accordion inside a tab is handled too. `changed` is
 * true when something was clicked, i.e. layout is still moving.
 */
function reveal(section: Element, query: string): { target: Element | null; changed: boolean } {
  const needle = normalizeQuery(query);
  const first = needle === null ? undefined : matchRanges(section, needle, true)[0];
  const target = first?.startContainer.parentElement ?? null;
  let changed = false;

  for (let el: Element | null = target; el && el !== section; el = el.parentElement) {
    if (el.id === '') {
      continue;
    }

    if (el.getAttribute('role') === 'tabpanel') {
      const tab = controllerOf(section, el.id);
      if (tab && tab.getAttribute('aria-selected') !== 'true') {
        tab.click();
        changed = true;
      }
    } else {
      const toggle = controllerOf(section, el.id, '[aria-expanded="false"]');
      if (toggle) {
        toggle.click();
        changed = true;
      }
    }
  }

  return { target, changed };
}

/**
 * Makes the first match inside a section visible (switches the tab, opens the
 * accordion item) and returns the element holding it, or null without a match.
 */
export function revealMatch(section: Element, query: string): Element | null {
  return reveal(section, query).target;
}

interface JumpMenuComponent extends AlpineMagics {
  entries: JumpSection[];
  searchable: JumpSection[];
  query: string;
  aktiv: string;
  active: boolean;
  open: boolean;
  hits: JumpHit[];
  init(): void;
  go(id: string): void;
  goHit(hit: JumpHit): void;
  toggle(): void;
  close(): void;
  refreshFade(): void;
}

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function createJumpMenuComponent(): JumpMenuComponent {
  // $root inside a method called from x-on is the clicked element, not the
  // component root, so init() keeps the real root here.
  let host: HTMLElement;

  return {
    // Alpine magic properties ($el, $nextTick, etc.) are injected at runtime
    ...({} as AlpineMagics),
    entries: [],
    searchable: [],
    query: '',
    aktiv: '',
    active: false,
    open: false,

    hits: [],

    init(): void {
      host = this.$root;
      // Wait until Alpine has finished: tabs and accordions are initialised by
      // then, and the collected text is the final page.
      this.$nextTick(() => {
        // One jump menu per page: a second instance stays invisible and sets up nothing.
        this.active = document.querySelector('[data-jump-menu-root]') === host;
        if (!this.active) {
          return;
        }

        const root = document.querySelector('main') ?? document;
        this.entries = collectSections(root);
        this.searchable = collectSearchSections(root);

        const observer = new IntersectionObserver(
          (records) => {
            for (const record of records) {
              if (record.isIntersecting) {
                this.aktiv = record.target.id;
                break;
              }
            }
          },
          { rootMargin: '-96px 0px -85% 0px', threshold: 0 }
        );

        for (const entry of this.entries) {
          const target = document.getElementById(entry.id);
          if (target) {
            observer.observe(target);
          }
        }

        // Search once per query change, not once per binding read. Typing also
        // changes the content height, and with it which edge may fade.
        this.$watch('query', () => {
          this.hits = searchSections(this.searchable, this.query);
          this.$nextTick(() => this.refreshFade());
        });

        const stopTrackingTop = host.dataset.jumpMenuPosition?.startsWith('top')
          ? trackTopCorner(host)
          : null;

        // Own outside-click handler: the pill sits inside the wrapper, so
        // clicking it never counts as outside and cannot undo its own toggle.
        const closeOnOutsideClick = (event: MouseEvent): void => {
          const target = event.target as Element | null;
          if (this.open && target?.isConnected && !target.closest('[data-jump-menu-ui]')) {
            this.open = false;
          }
        };
        document.addEventListener('click', closeOnOutsideClick);

        host.addEventListener('alpine:destroyed', () => {
          document.removeEventListener('click', closeOnOutsideClick);
          observer.disconnect();
          stopTrackingTop?.();
          clearHighlights();
        });
      });
    },

    toggle(): void {
      this.open = !this.open;

      if (this.open) {
        // Move focus into the panel once x-show has displayed it.
        // After the next frame: x-show has applied display by then, and an element
        // that is still display:none silently ignores focus().
        this.$nextTick(() =>
          requestAnimationFrame(() => {
            const panel = document.getElementById(floatPanelId(host));
            (
              panel?.querySelector<HTMLElement>('input') ?? panel?.querySelector<HTMLElement>('a')
            )?.focus();
            centerActiveEntry(host);
            this.refreshFade();
          })
        );
      }
    },

    refreshFade(): void {
      const scroll = document.getElementById(`${host.dataset.jumpMenuId}-scroll`);
      if (!scroll) {
        return;
      }

      scroll.toggleAttribute('data-fade-top', scroll.scrollTop > 1);
      scroll.toggleAttribute(
        'data-fade-bottom',
        scroll.scrollTop + scroll.clientHeight < scroll.scrollHeight - 1
      );
    },

    close(): void {
      if (!this.open) {
        return;
      }

      // Escape closes instantly. x-show hides only after the leave transition has
      // waited two animation frames and read its duration (Alpine performTransition,
      // module.esm.js ~1292-1303), so hide the panel inline first; x-show removes
      // the inline display again on the next open.
      const panel = document.getElementById(floatPanelId(host));
      panel?.style.setProperty('display', 'none');
      this.open = false;
      document.getElementById(floatPillId(host))?.focus();
    },

    go(id: string): void {
      const section = document.getElementById(id);
      if (!section) {
        return;
      }

      clearHighlights();
      this.open = false;
      scrollTo(section, 'start');
    },

    goHit(hit: JumpHit): void {
      const section = document.getElementById(hit.id);
      if (!section) {
        return;
      }

      const query = this.query;
      const { target, changed } = reveal(section, query);

      this.open = false;
      setTimeout(
        () => {
          highlightMatches(section, query);
          scrollTo(target ?? section, 'center', section);
        },
        changed ? TAB_ANIMATION_MS : 0
      );
    },
  };
}

/**
 * Scrolls to the target and moves focus to the section. Focus lands on the
 * section itself (tabindex -1, preventScroll) so keyboard users continue
 * reading from there instead of from the menu.
 */
function scrollTo(target: Element, block: ScrollLogicalPosition, section: Element = target): void {
  target.scrollIntoView({ block, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });

  if (section instanceof HTMLElement) {
    section.setAttribute('tabindex', '-1');
    section.focus({ preventScroll: true });
  }
}

/** The panel and pill ids are `{data-jump-menu-id}-panel` / `-pill`. */
function floatPanelId(host: HTMLElement): string {
  return `${host.dataset.jumpMenuId}-panel`;
}

function floatPillId(host: HTMLElement): string {
  return `${host.dataset.jumpMenuId}-pill`;
}

/**
 * Top corners: keeps the pill one rem below the header and publishes
 * --jump-menu-offset (pill height plus a rem above and below) for the scroll
 * margins in app.css, so in-page jumps land below header and pill.
 *
 * The top follows the header's bottom edge on scroll, so it also works when the
 * header is not sticky and scrolls away. Returns the cleanup function.
 */
function trackTopCorner(host: HTMLElement): () => void {
  const ui = document.getElementById(`${host.dataset.jumpMenuId}-ui`);
  const pill = document.getElementById(`${host.dataset.jumpMenuId}-pill`);
  const header = document.querySelector('header');
  if (!ui || !pill) {
    return () => undefined;
  }

  const root = document.documentElement;
  const gap = 16;
  let frame = 0;
  const place = (): void => {
    frame = 0;
    ui.style.top = `${Math.max(0, header?.getBoundingClientRect().bottom ?? 0) + gap}px`;
  };
  const schedule = (): void => {
    if (frame === 0) {
      frame = requestAnimationFrame(place);
    }
  };
  // The pill is 0 high while the menu is hidden, which resets the offset.
  const resize = new ResizeObserver(() => {
    const height = pill.offsetHeight;
    root.style.setProperty('--jump-menu-offset', height > 0 ? `${height + 2 * gap}px` : '0px');
    schedule();
  });

  resize.observe(pill);
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule, { passive: true });
  place();

  return () => {
    resize.disconnect();
    window.removeEventListener('scroll', schedule);
    window.removeEventListener('resize', schedule);
    cancelAnimationFrame(frame);
    root.style.removeProperty('--jump-menu-offset');
  };
}

/** Scrolls the current entry to the middle of the panel, instantly and inside the panel only. */
function centerActiveEntry(host: HTMLElement): void {
  const scroll = document.getElementById(`${host.dataset.jumpMenuId}-scroll`);
  const current = scroll?.querySelector<HTMLElement>('[aria-current="location"]');
  if (!scroll || !current) {
    return;
  }

  scroll.scrollTo({
    top: current.offsetTop - (scroll.clientHeight - current.offsetHeight) / 2,
    behavior: 'instant',
  });
}
