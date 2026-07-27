/**
 * Column heading alignment.
 *
 * In an equal-width column layout every column is its own text flow, so the
 * body copy starts one line lower wherever a headline wraps. CSS alone cannot
 * fix this here: headline and body come from a single WYSIWYG field, so they
 * are not siblings, and the headline sits three levels below the grid item,
 * out of reach of subgrid.
 *
 * The stylesheet reserves a fixed number of lines as a baseline that works
 * without JavaScript. This module refines that to the height actually needed,
 * which also reclaims the reserved space when no headline wraps at all.
 */

const LAYOUTS = [
  '.two-columns',
  '.two-columns-images',
  '.three-columns',
  '.three-columns-images',
  '.four-columns',
  '.four-columns-images',
];

const GRID_SELECTOR = LAYOUTS.map((layout) => `${layout} .grid`).join(', ');
const HEADING_SELECTOR = ':is(h2, h3, h4, h5)';

/** Rounding tolerance in px when deciding whether two items share a grid row. */
const ROW_TOLERANCE = 2;

/**
 * The leading headline of each column, paired with the grid item it sits in.
 * Grouping uses the grid item rather than the headline itself, because images
 * of differing height would otherwise scatter headlines of the same row.
 */
function collectHeadings(grid: HTMLElement): { heading: HTMLElement; item: HTMLElement }[] {
  const pairs: { heading: HTMLElement; item: HTMLElement }[] = [];

  for (const item of Array.from(grid.children)) {
    if (!(item instanceof HTMLElement)) continue;

    const prose = item.querySelector('.prose');
    const heading = prose?.querySelector<HTMLElement>(`:scope > ${HEADING_SELECTOR}`);

    if (heading) {
      pairs.push({ heading, item });
    }
  }

  return pairs;
}

/** Group by the top edge of the grid item, so each visual row is one group. */
function groupByRow(
  pairs: { heading: HTMLElement; item: HTMLElement }[]
): { heading: HTMLElement; item: HTMLElement }[][] {
  const rows: { top: number; entries: { heading: HTMLElement; item: HTMLElement }[] }[] = [];

  for (const pair of pairs) {
    const top = pair.item.getBoundingClientRect().top;
    const row = rows.find((candidate) => Math.abs(candidate.top - top) <= ROW_TOLERANCE);

    if (row) {
      row.entries.push(pair);
    } else {
      rows.push({ top, entries: [pair] });
    }
  }

  return rows.map((row) => row.entries);
}

export function alignGrid(grid: HTMLElement): void {
  const pairs = collectHeadings(grid);
  if (pairs.length < 2) return;

  // Clear first so the measurement reflects the natural height, not a value
  // left over from the stylesheet or from an earlier run.
  for (const { heading } of pairs) {
    heading.style.minHeight = '0px';
  }

  const rows = groupByRow(pairs);
  const assignments: { heading: HTMLElement; value: string }[] = [];

  for (const row of rows) {
    // A row of one is either a single column or a mobile layout. Nothing to
    // align, and forcing a height there would only add empty space.
    if (row.length < 2) continue;

    const tallest = Math.max(...row.map(({ heading }) => heading.getBoundingClientRect().height));
    const value = `${Math.ceil(tallest)}px`;

    for (const { heading } of row) {
      assignments.push({ heading, value });
    }
  }

  for (const { heading, value } of assignments) {
    heading.style.minHeight = value;
  }
}

export function initColumnHeadingAlignment(): void {
  const grids = document.querySelectorAll<HTMLElement>(GRID_SELECTOR);
  if (!grids.length) return;

  // Writing min-height resizes the grid, which would retrigger the observer.
  // Skipping the callback for our own writes keeps that from looping.
  let applying = false;

  const observer = new ResizeObserver((entries) => {
    if (applying) return;
    applying = true;

    for (const entry of entries) {
      alignGrid(entry.target as HTMLElement);
    }

    requestAnimationFrame(() => {
      applying = false;
    });
  });

  for (const grid of grids) {
    alignGrid(grid);
    observer.observe(grid);
  }

  // Web fonts change the line height, and sections revealed on scroll are
  // display:none until then, so both need a second pass.
  void document.fonts?.ready.then(() => {
    for (const grid of grids) {
      alignGrid(grid);
    }
  });
}
