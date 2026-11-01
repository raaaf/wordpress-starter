import { describe, expect, it } from 'vitest';
import {
  BUTTON_PILL,
  contrastRatio,
  DEFAULT_ACCENT,
  inkFor,
  playgroundVariables,
} from './token-playground';

describe('contrastRatio', () => {
  it('is 21 for black on white and 1 for equal colours', () => {
    expect(contrastRatio('#000000', '#FFFFFF')).toBeCloseTo(21, 5);
    expect(contrastRatio('#FF6B35', '#FF6B35')).toBeCloseTo(1, 5);
  });
});

describe('inkFor', () => {
  it('keeps black text on the default orange', () => {
    const { ink, ratio } = inkFor(DEFAULT_ACCENT);
    expect(ink).toBe('#000000');
    expect(ratio).toBeGreaterThan(4.5);
  });

  it('switches to white text on a dark blue', () => {
    expect(inkFor('#2563EB').ink).toBe('#FFFFFF');
  });

  it('keeps black text on yellow', () => {
    expect(inkFor('#FACC15').ink).toBe('#000000');
  });
});

describe('playgroundVariables', () => {
  const sizes = { base: '1rem', '4xl': 'clamp(1.8rem, 1.4rem + 1.6vw, 3rem)' };

  it('sets the accent, its deeper shades and the ink', () => {
    const vars = playgroundVariables('#2563EB', 1, BUTTON_PILL, 1, sizes);
    expect(vars['--rafael-color-accent-500']).toBe('#2563EB');
    expect(vars['--rafael-color-accent-600']).toContain('#2563EB');
    expect(vars['--rafael-color-ds-accent-ink']).toBe('#FFFFFF');
  });

  it('gives cards, inputs and small boxes the card radius in px', () => {
    for (const r of [0, 2, 24, 48]) {
      const vars = playgroundVariables(DEFAULT_ACCENT, r, BUTTON_PILL, 1, sizes);
      for (const name of ['sm', 'md', 'lg', '3xl']) {
        expect(vars[`--rafael-radius-${name}`]).toBe(`${r}px`);
      }
      expect(vars['--rafael-radius-full']).toBe('999px');
    }
    expect(
      playgroundVariables(DEFAULT_ACCENT, 80, BUTTON_PILL, 1, sizes)['--rafael-radius-lg']
    ).toBe('48px');
  });

  it('sets the button radius separately, pill at the top step', () => {
    expect(playgroundVariables(DEFAULT_ACCENT, 12, 0, 1, sizes)['--rafael-radius-full']).toBe(
      '0px'
    );
    expect(playgroundVariables(DEFAULT_ACCENT, 12, 8, 1, sizes)['--rafael-radius-full']).toBe(
      '8px'
    );
    expect(
      playgroundVariables(DEFAULT_ACCENT, 12, BUTTON_PILL, 1, sizes)['--rafael-radius-full']
    ).toBe('999px');
  });

  it('wraps the theme font sizes instead of replacing them', () => {
    const vars = playgroundVariables(DEFAULT_ACCENT, 1, BUTTON_PILL, 1.125, sizes);
    expect(vars['--rafael-font-size-base']).toBe('calc(1rem * 1.125)');
    expect(vars['--rafael-font-size-4xl']).toBe(
      'calc(clamp(1.8rem, 1.4rem + 1.6vw, 3rem) * 1.125)'
    );
    expect(vars).not.toHaveProperty('--rafael-font-size-xs');
  });
});
