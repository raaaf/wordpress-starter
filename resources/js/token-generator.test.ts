import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';
import {
  applyColors,
  currentColor,
  generateFiles,
  generatePalette,
  hexToRgb,
  pageVariables,
  paletteTokens,
  previewVariables,
  semanticColor,
  semanticOverrides,
  toJson,
  type TokenFiles,
} from './token-generator';
import { normalizeHex } from './token-generator-component';
import { contrastRatio } from './token-playground';

const root = join(__dirname, '..', '..');
const readJson = (path: string) => JSON.parse(readFileSync(join(root, path), 'utf8'));

const fixture = readJson('tests/fixtures/color-palette-parity.json') as {
  cases: Array<{ input: string; palette: Record<string, string> }>;
};

const base = (): TokenFiles => ({
  primitives: readJson('config/design-tokens/primitives.tokens.json'),
  light: readJson('config/design-tokens/light.tokens.json'),
  dark: readJson('config/design-tokens/dark.tokens.json'),
});

type Node = Record<string, unknown>;
const at = (node: unknown, ...path: string[]): Node =>
  path.reduce((n, key) => (n as Node)[key], node) as Node;
const hexOf = (token: Node) => (token.$value as Node).hex;

describe('generatePalette', () => {
  it('matches ColorPaletteGenerator::generate() for every fixture case', () => {
    expect(fixture.cases.length).toBeGreaterThan(10);
    for (const { input, palette } of fixture.cases) {
      expect(generatePalette(input), input).toEqual(palette);
    }
  });

  it('returns nothing for an invalid colour', () => {
    expect(generatePalette('not a colour')).toEqual({});
    expect(hexToRgb('#12345')).toBeNull();
  });
});

describe('paletteTokens', () => {
  it('writes Figma variable tokens with a generated id', () => {
    const tokens = paletteTokens(generatePalette('#2563EB'), 'accent');
    expect(Object.keys(tokens)).toHaveLength(11);
    expect(tokens['500']).toEqual({
      $type: 'color',
      $value: {
        colorSpace: 'srgb',
        components: [0x25 / 255, 0x63 / 255, 0xeb / 255],
        alpha: 1,
        hex: '#2563EB',
      },
      $extensions: { 'com.figma.variableId': 'generated-accent-500' },
    });
  });
});

describe('applyColors', () => {
  it('leaves the base files untouched', () => {
    const files = base();
    const before = JSON.stringify(files);
    applyColors(files, { accent: '#2563EB' }, { error: '#E11D48' });
    expect(JSON.stringify(files)).toBe(before);
  });

  it('replaces the full palette in the primitives', () => {
    const result = applyColors(base(), { accent: '#2563EB' }, {});
    const palette = generatePalette('#2563EB');
    for (const [shade, hex] of Object.entries(palette)) {
      expect(hexOf(at(result.primitives, 'color', 'accent', shade))).toBe(hex);
    }
    expect(currentColor(result, 'accent')).toBe('#2563EB');
  });

  it('updates every semantic token that aliases a changed palette shade', () => {
    const result = applyColors(base(), { accent: '#2563EB' }, {});
    let aliased = 0;
    for (const mode of ['light', 'dark'] as const) {
      const visit = (node: Node) => {
        for (const [key, value] of Object.entries(node)) {
          if (key.startsWith('$') || !value || typeof value !== 'object') continue;
          const token = value as Node;
          const target = (
            (token.$extensions as Node | undefined)?.['com.figma.aliasData'] as Node | undefined
          )?.targetVariableName;
          const match = typeof target === 'string' && /^color\/accent\/(\d+)$/.exec(target);
          if (match) {
            aliased++;
            expect(hexOf(token)).toBe(generatePalette('#2563EB')[match[1]]);
          } else if (!('$value' in token)) {
            visit(token);
          }
        }
      };
      visit(result[mode]);
    }
    expect(aliased).toBeGreaterThan(0);
  });

  it('writes status colours with light and dark variants and maps them', () => {
    const result = applyColors(base(), {}, { error: '#E11D48' });
    const shades = generatePalette('#E11D48');
    expect(hexOf(at(result.primitives, 'color', 'error'))).toBe('#E11D48');
    expect(hexOf(at(result.primitives, 'color', 'error-light'))).toBe(shades['100']);
    expect(hexOf(at(result.primitives, 'color', 'error-dark'))).toBe(shades['700']);
    expect(currentColor(result, 'error')).toBe('#E11D48');

    for (const mode of ['light', 'dark'] as const) {
      const tokens = result[mode];
      if (at(tokens, 'bg', 'error')) expect(hexOf(at(tokens, 'bg', 'error'))).toBe(shades['100']);
      if (at(tokens, 'text', 'error'))
        expect(hexOf(at(tokens, 'text', 'error'))).toBe(shades['700']);
      if (at(tokens, 'border', 'error'))
        expect(hexOf(at(tokens, 'border', 'error'))).toBe('#E11D48');
    }
  });

  it('skips invalid input', () => {
    const files = base();
    const result = applyColors(files, { accent: 'nope' }, { error: '#12' });
    expect(result).toEqual(files);
  });
});

describe('toJson', () => {
  it('keeps the layout of the shipped token files', () => {
    const raw = readFileSync(join(root, 'config/design-tokens/light.tokens.json'), 'utf8');
    // JSON.parse cannot keep "1.0" apart from "1"; the files mix both spellings.
    expect(toJson(JSON.parse(raw))).toBe(raw.replace(/^(\s+-?\d+)\.0(,?)$/gm, '$1$2'));
  });
});

describe('generateFiles', () => {
  const initial = { accent: '#FF6B35', gray: '#737373', error: '#EF4444' };

  it('applies only colours that differ from the site', () => {
    const files = base();
    const result = generateFiles(files, { ...initial, gray: '#737373' }, initial);
    expect(result).toEqual(files);
  });

  it('moves the design-system accent roles with the accent', () => {
    const result = generateFiles(base(), { ...initial, accent: '#2563EB' }, initial);
    const ds = (path: string[]) => hexOf(at(result.primitives, 'color', 'ds', 'accent', ...path));
    const shades = generatePalette('#2563EB');
    expect(ds(['base'])).toBe('#2563EB');
    expect(ds(['deep'])).toBe(shades['600']);
    // Dark ink on dark blue would fail 4.5:1, so the ink turns white.
    expect(ds(['ink'])).toBe('#FFFFFF');
    // Text accent in dark mode keeps 4.5:1 on the dark ground.
    expect(contrastRatio(ds(['text', 'dark']) as string, '#000000')).toBeGreaterThanOrEqual(4.5);
  });

  it('keeps the dark ink on a light accent', () => {
    const result = generateFiles(base(), { ...initial, accent: '#FACC15' }, initial);
    expect(hexOf(at(result.primitives, 'color', 'ds', 'accent', 'ink'))).toBe('#1C1917');
  });
});

describe('pageVariables', () => {
  it('lists exactly the primitives that changed, as tokens-core names them', () => {
    const files = base();
    const vars = pageVariables(files, applyColors(files, { accent: '#2563EB' }, {}));
    expect(vars['--rafael-color-accent-500']).toBe('#2563EB');
    expect(vars['--rafael-color-accent-950']).toBe(generatePalette('#2563EB')['950']);
    expect(Object.keys(vars).every((name) => name.startsWith('--rafael-color-accent-'))).toBe(true);
    expect(pageVariables(files, files)).toEqual({});
  });
});

describe('semanticColor and previewVariables', () => {
  it('resolves semantic tokens through their alias', () => {
    const result = applyColors(base(), { accent: '#2563EB' }, {});
    expect(semanticColor(result, 'light', 'bg.brand')).toBe('#2563EB');
    expect(semanticColor(result, 'dark', 'bg.brand')).toBe('#2563EB');
    expect(semanticColor(result, 'light', 'border.default')).toMatch(/^rgb\(0 0 0 \/ 0\.1\)$/);
  });

  it('gives every fill a readable ink', () => {
    const vars = previewVariables(base(), 'dark');
    expect(vars['--gp-bg']).toBe('#000000');
    for (const fill of ['--gp-success', '--gp-warning', '--gp-error']) {
      expect(contrastRatio(vars[fill], vars[`${fill}-ink`])).toBeGreaterThanOrEqual(4.5);
    }
  });
});

describe('normalizeHex', () => {
  it('accepts short and unprefixed hex and rejects the rest', () => {
    expect(normalizeHex('#0f0')).toBe('#00FF00');
    expect(normalizeHex(' 2563eb ')).toBe('#2563EB');
    expect(normalizeHex('blue')).toBeNull();
  });
});

describe('primary buttons in the accent', () => {
  const initial = { accent: '#FF6B35' };

  it('stays neutral by default', () => {
    const files = base();
    expect(generateFiles(files, initial, initial)).toEqual(files);
  });

  it('fills the buttons with the accent and keeps AA for the label, rest and hover', () => {
    for (const accent of ['#FF6B35', '#2563EB', '#FACC15', '#059669', '#808080']) {
      const result = generateFiles(base(), { accent }, initial, 'accent');
      for (const mode of ['light', 'dark'] as const) {
        const fill = semanticColor(result, mode, 'bg.button-primary') as string;
        const hover = semanticColor(result, mode, 'bg.button-primary-hover') as string;
        const label = semanticColor(result, mode, 'text.button-primary') as string;
        expect(fill, accent).toBe(accent);
        expect(['#000000', '#FFFFFF']).toContain(label);
        expect(contrastRatio(fill, label), accent).toBeGreaterThanOrEqual(4.5);
        expect(contrastRatio(hover, label), accent).toBeGreaterThanOrEqual(
          contrastRatio(fill, label)
        );
      }
    }
  });

  it('leaves checked controls, accent text and the design-system primitives alone', () => {
    const files = base();
    const result = generateFiles(files, initial, initial, 'accent');
    expect(result.primitives).toEqual(files.primitives);
    for (const mode of ['light', 'dark'] as const) {
      for (const path of ['bg.accent', 'text.accent', 'icon.on-accent']) {
        expect(semanticColor(result, mode, path)).toBe(semanticColor(files, mode, path));
      }
    }
    const overrides = semanticOverrides(files, result);
    expect(overrides.light).toEqual({
      '--bg-button-primary': 'var(--rafael-color-accent-500)',
      '--bg-button-primary-hover': 'var(--rafael-color-accent-400)',
      '--text-button-primary': 'var(--rafael-color-black)',
    });
    expect(overrides.dark).toEqual(overrides.light);
    expect(semanticOverrides(files, files)).toEqual({ light: {}, dark: {} });
  });
});
