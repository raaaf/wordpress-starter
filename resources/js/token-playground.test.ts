import { describe, expect, it } from 'vitest';
import {
  BUTTON_PILL,
  buttonRadiusParam,
  contrastRatio,
  createTokenPlaygroundComponent,
  DEFAULT_ACCENT,
  generatorUrl,
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

  it('sets the light steps and the darkest step from the accent', () => {
    const vars = playgroundVariables('#2563EB', 1, BUTTON_PILL, 1, sizes);
    for (const [step, mix] of [
      [50, 'white 92%'],
      [100, 'white 84%'],
      [200, 'white 68%'],
      [300, 'white 45%'],
      [900, 'black 60%'],
    ]) {
      expect(vars[`--rafael-color-accent-${step}`]).toBe(`color-mix(in oklch, #2563EB, ${mix})`);
    }
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

describe('font pairing variables', () => {
  const pairing = {
    headline: 'playfair-display',
    body: 'inter',
    label: 'Playfair Display + Inter',
    headlineFace: 'wpsg-playfair-display',
    bodyFace: 'wpsg-inter',
  };

  it('sets headline, body and ui families from the faces', () => {
    const vars = playgroundVariables(DEFAULT_ACCENT, 12, BUTTON_PILL, 1, {}, pairing);
    expect(vars['--rafael-font-family-headline']).toBe("'wpsg-playfair-display', sans-serif");
    expect(vars['--rafael-font-family-body']).toBe("'wpsg-inter', sans-serif");
    expect(vars['--rafael-font-family-ui']).toBe("'wpsg-inter', sans-serif");
  });

  it('leaves the families out without a pairing', () => {
    const vars = playgroundVariables(DEFAULT_ACCENT, 12, BUTTON_PILL, 1, {});
    expect(Object.keys(vars).some((name) => name.includes('font-family'))).toBe(false);
  });

  it('removes the families again on "Standard" and on reset', () => {
    const root = document.documentElement;
    const c = createTokenPlaygroundComponent({}, { fonts: [pairing] });
    c.font = '0';
    c.update();
    expect(root.style.getPropertyValue('--rafael-font-family-headline')).toContain('wpsg-playfair');

    c.font = '';
    c.update();
    expect(root.style.getPropertyValue('--rafael-font-family-headline')).toBe('');
    expect(root.style.getPropertyValue('--rafael-color-accent-500')).not.toBe('');

    c.font = '0';
    c.update();
    c.reset();
    expect(root.style.getPropertyValue('--rafael-font-family-body')).toBe('');
    expect(c.font).toBe('');
  });

  it('ignores an index outside the list', () => {
    const c = createTokenPlaygroundComponent({}, { fonts: [pairing] });
    c.font = '7';
    expect(c.pairing).toBeNull();
  });
});

describe('generatorUrl', () => {
  const base = 'https://example.test/generate/';
  const unchanged = {
    accent: DEFAULT_ACCENT,
    radius: 12,
    buttonRadius: BUTTON_PILL,
    scale: 1,
    pairing: null,
  };
  const pairing = {
    headline: 'lora',
    body: 'inter',
    label: 'Lora + Inter',
    headlineFace: 'wpsg-lora',
    bodyFace: 'wpsg-inter',
  };
  const full = 'accent=%23ff6b35&card_radius=12&button_radius=pill';

  it('is the plain URL for an untouched playground, whatever the case of the accent', () => {
    expect(generatorUrl(base, unchanged)).toBe(base);
    expect(generatorUrl(base, { ...unchanged, accent: '#ff6b35' })).toBe(base);
  });

  it('sends the full look without fonts when only the scale changed', () => {
    expect(generatorUrl(base, { ...unchanged, scale: 1.25 })).toBe(`${base}?${full}`);
  });

  it('sends the accent lowercase with the hash encoded', () => {
    expect(generatorUrl(base, { ...unchanged, accent: '#2563EB' })).toBe(
      `${base}?accent=%232563eb&card_radius=12&button_radius=pill`
    );
  });

  it('sends the other values too when one radius changed, pill as pill', () => {
    expect(generatorUrl(base, { ...unchanged, radius: 24 })).toBe(
      `${base}?accent=%23ff6b35&card_radius=24&button_radius=pill`
    );
    expect(generatorUrl(base, { ...unchanged, buttonRadius: 6 })).toBe(
      `${base}?accent=%23ff6b35&card_radius=12&button_radius=6`
    );
  });

  it('maps the top button step to pill', () => {
    expect(buttonRadiusParam(BUTTON_PILL)).toBe('pill');
    expect(buttonRadiusParam(0)).toBe('0');
  });

  it('adds both fonts only when a pairing is chosen', () => {
    expect(generatorUrl(base, { ...unchanged, pairing })).toBe(
      `${base}?${full}&font_headline=lora&font_body=inter`
    );
  });

  it('extends an existing query', () => {
    expect(generatorUrl('https://x.test/?p=1', { ...unchanged, radius: 0 })).toBe(
      'https://x.test/?p=1&accent=%23ff6b35&card_radius=0&button_radius=pill'
    );
  });
});
