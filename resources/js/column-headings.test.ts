import { describe, it, expect, beforeEach } from 'vitest';
import { alignGrid } from './column-headings';

/**
 * happy-dom reports every element as 0x0, so the geometry the module reads has
 * to be stubbed. Each column declares the row it sits in and the natural
 * height of its headline.
 */
function buildGrid(columns: { row: number; headingHeight: number; hasHeading?: boolean }[]): {
  grid: HTMLElement;
  headings: HTMLElement[];
} {
  const grid = document.createElement('div');
  grid.className = 'grid';
  const headings: HTMLElement[] = [];

  for (const column of columns) {
    const item = document.createElement('div');
    const prose = document.createElement('div');
    prose.className = 'prose';

    item.getBoundingClientRect = (): DOMRect => ({ top: column.row * 500 }) as DOMRect;

    if (column.hasHeading !== false) {
      const heading = document.createElement('h3');
      heading.getBoundingClientRect = (): DOMRect =>
        // Once a min-height is applied the element reports at least that value,
        // which is what a browser would do.
        ({
          height: Math.max(column.headingHeight, parseFloat(heading.style.minHeight) || 0),
        }) as DOMRect;
      prose.appendChild(heading);
      headings.push(heading);
    }

    item.appendChild(prose);
    grid.appendChild(item);
  }

  return { grid, headings };
}

describe('alignGrid', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('lifts every headline in a row to the tallest one', () => {
    const { grid, headings } = buildGrid([
      { row: 0, headingHeight: 36 },
      { row: 0, headingHeight: 72 },
      { row: 0, headingHeight: 36 },
    ]);

    alignGrid(grid);

    expect(headings.map((h) => h.style.minHeight)).toEqual(['72px', '72px', '72px']);
  });

  it('treats each visual row separately', () => {
    const { grid, headings } = buildGrid([
      { row: 0, headingHeight: 36 },
      { row: 0, headingHeight: 72 },
      { row: 1, headingHeight: 36 },
      { row: 1, headingHeight: 36 },
    ]);

    alignGrid(grid);

    expect(headings.map((h) => h.style.minHeight)).toEqual(['72px', '72px', '36px', '36px']);
  });

  it('leaves a single-column row untouched so no empty space is added', () => {
    const { grid, headings } = buildGrid([
      { row: 0, headingHeight: 36 },
      { row: 1, headingHeight: 72 },
    ]);

    alignGrid(grid);

    expect(headings.map((h) => h.style.minHeight)).toEqual(['0px', '0px']);
  });

  it('reclaims reserved space when no headline wraps', () => {
    const { grid, headings } = buildGrid([
      { row: 0, headingHeight: 36 },
      { row: 0, headingHeight: 36 },
    ]);

    // A previous run, or the stylesheet baseline, reserved two lines.
    headings.forEach((h) => (h.style.minHeight = '72px'));

    alignGrid(grid);

    expect(headings.map((h) => h.style.minHeight)).toEqual(['36px', '36px']);
  });

  it('ignores columns without a headline', () => {
    const { grid, headings } = buildGrid([
      { row: 0, headingHeight: 36 },
      { row: 0, headingHeight: 0, hasHeading: false },
      { row: 0, headingHeight: 72 },
    ]);

    alignGrid(grid);

    expect(headings).toHaveLength(2);
    expect(headings.map((h) => h.style.minHeight)).toEqual(['72px', '72px']);
  });

  it('does nothing when the grid holds a single headline', () => {
    const { grid, headings } = buildGrid([{ row: 0, headingHeight: 36 }]);

    alignGrid(grid);

    expect(headings[0].style.minHeight).toBe('');
  });
});
