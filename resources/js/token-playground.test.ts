import { describe, expect, it } from 'vitest';
import {
  contrastRatio,
  DEFAULT_ACCENT,
  inkFor,
  MAX_RADIUS,
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
    const vars = playgroundVariables('#2563EB', 1, 1, sizes);
    expect(vars['--rafael-color-accent-500']).toBe('#2563EB');
    expect(vars['--rafael-color-accent-600']).toContain('#2563EB');
    expect(vars['--rafael-color-ds-accent-ink']).toBe('#FFFFFF');
  });

  it('scales all radii with the card radius in px, squares everything at 0 and makes pills at the maximum', () => {
    expect(playgroundVariables(DEFAULT_ACCENT, 24, 1, sizes)['--rafael-radius-lg']).toBe('24px');
    const square = playgroundVariables(DEFAULT_ACCENT, 0, 1, sizes);
    expect(square['--rafael-radius-lg']).toBe('0px');
    expect(square['--rafael-radius-full']).toBe('0px');
    const pill = playgroundVariables(DEFAULT_ACCENT, MAX_RADIUS, 1, sizes);
    expect(pill['--rafael-radius-sm']).toBe('999px');
    expect(pill['--rafael-radius-lg']).toBe('48px');
    expect(pill['--rafael-radius-3xl']).toBe('48px');
    expect(pill['--rafael-radius-full']).toBe('999px');
    expect(playgroundVariables(DEFAULT_ACCENT, 24, 1, sizes)['--rafael-radius-sm']).toBe('12px');
  });

  it('wraps the theme font sizes instead of replacing them', () => {
    const vars = playgroundVariables(DEFAULT_ACCENT, 1, 1.125, sizes);
    expect(vars['--rafael-font-size-base']).toBe('calc(1rem * 1.125)');
    expect(vars['--rafael-font-size-4xl']).toBe(
      'calc(clamp(1.8rem, 1.4rem + 1.6vw, 3rem) * 1.125)'
    );
    expect(vars).not.toHaveProperty('--rafael-font-size-xs');
  });
});
