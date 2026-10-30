// Public token generator on the design system page (templates/styleguide/generator.blade.php).
//
// The colour logic is a port of the admin's "Design tokens" page
// (src/ColorPaletteGenerator.php and DesignTokenServiceProvider::syncColorsToTokens),
// so a palette made here and one made in wp-admin are the same file.
// tests/fixtures/color-palette-parity.json pins both implementations.

import { contrastRatio, inkFor } from './token-playground';

export const PALETTE_COLORS = ['accent', 'primary', 'secondary', 'gray'] as const;
export const STATUS_COLORS = ['success', 'warning', 'error'] as const;

export type PaletteColor = (typeof PALETTE_COLORS)[number];
export type StatusColor = (typeof STATUS_COLORS)[number];

// Positive = towards white, negative = towards black (ColorPaletteGenerator::SHADE_ADJUSTMENTS).
const SHADE_ADJUSTMENTS: Array<[number, number]> = [
  [50, 0.95],
  [100, 0.9],
  [200, 0.8],
  [300, 0.65],
  [400, 0.45],
  [500, 0],
  [600, -0.15],
  [700, -0.3],
  [800, -0.45],
  [900, -0.6],
  [950, -0.75],
];

type Rgb = { r: number; g: number; b: number };

export function hexToRgb(hex: string): Rgb | null {
  let h = hex.replace(/^#/, '');
  if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
  if (!/^[0-9a-fA-F]{6}$/.test(h)) return null;
  return {
    r: parseInt(h.slice(0, 2), 16),
    g: parseInt(h.slice(2, 4), 16),
    b: parseInt(h.slice(4, 6), 16),
  };
}

function rgbToHex(r: number, g: number, b: number): string {
  const part = (n: number) =>
    Math.max(0, Math.min(255, n)).toString(16).toUpperCase().padStart(2, '0');
  return `#${part(r)}${part(g)}${part(b)}`;
}

function rgbToHsl(r255: number, g255: number, b255: number): { h: number; s: number; l: number } {
  const r = r255 / 255;
  const g = g255 / 255;
  const b = b255 / 255;
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const l = (max + min) / 2;
  let h = 0;
  let s = 0;

  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
    else if (max === g) h = ((b - r) / d + 2) / 6;
    else h = ((r - g) / d + 4) / 6;
  }

  return { h, s, l };
}

function hueToRgb(p: number, q: number, t: number): number {
  if (t < 0) t += 1;
  if (t > 1) t -= 1;
  if (t < 1 / 6) return p + (q - p) * 6 * t;
  if (t < 1 / 2) return q;
  if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
  return p;
}

// Same as ColorPaletteGenerator::toChannel(): round to 9 decimals first, so
// float noise like 4.499999999999997 rounds up to 5 on both sides.
const toChannel = (n: number) => Math.round(Number((n * 255).toFixed(9)));

function hslToRgb(h: number, s: number, l: number): Rgb {
  let r = l;
  let g = l;
  let b = l;
  if (s !== 0) {
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hueToRgb(p, q, h + 1 / 3);
    g = hueToRgb(p, q, h);
    b = hueToRgb(p, q, h - 1 / 3);
  }
  return { r: toChannel(r), g: toChannel(g), b: toChannel(b) };
}

/** Shades 50 to 950 from a base colour (500), as ColorPaletteGenerator::generate(). */
export function generatePalette(hex: string): Record<string, string> {
  const rgb = hexToRgb(hex);
  if (!rgb) return {};
  const { h, s, l } = rgbToHsl(rgb.r, rgb.g, rgb.b);
  const palette: Record<string, string> = {};

  for (const [shade, adjustment] of SHADE_ADJUSTMENTS) {
    if (shade === 500) {
      palette[shade] = rgbToHex(rgb.r, rgb.g, rgb.b);
      continue;
    }
    const lightness = adjustment > 0 ? l + (1 - l) * adjustment : l * (1 + adjustment);
    const next = hslToRgb(h, s, lightness);
    palette[shade] = rgbToHex(next.r, next.g, next.b);
  }

  return palette;
}

type Json = Record<string, unknown>;

function colorValue(hex: string): Json | null {
  const rgb = hexToRgb(hex);
  if (!rgb) return null;
  return {
    colorSpace: 'srgb',
    components: [rgb.r / 255, rgb.g / 255, rgb.b / 255],
    alpha: 1,
    hex: rgbToHex(rgb.r, rgb.g, rgb.b),
  };
}

/** Palette as Figma variable tokens (ColorPaletteGenerator::toFigmaTokenFormat). */
export function paletteTokens(
  palette: Record<string, string>,
  colorName: string
): Record<string, Json> {
  const tokens: Record<string, Json> = {};
  for (const [shade, hex] of Object.entries(palette)) {
    const value = colorValue(hex);
    if (!value) continue;
    tokens[shade] = {
      $type: 'color',
      $value: { ...value, hex },
      $extensions: { 'com.figma.variableId': `generated-${colorName}-${shade}` },
    };
  }
  return tokens;
}

// Semantic status tokens and the primitive each one reads
// (DesignTokenServiceProvider::updateSemanticStatusColors).
const STATUS_MAPPINGS: Record<StatusColor, Array<[string, string, string]>> = {
  success: [
    ['bg', 'success', 'success-light'],
    ['bg', 'success-strong', 'success'],
    ['text', 'success', 'success-dark'],
    ['border', 'success', 'success'],
  ],
  warning: [
    ['bg', 'warning', 'warning-light'],
    ['bg', 'warning-strong', 'warning'],
    ['text', 'warning', 'warning-dark'],
    ['border', 'warning', 'warning'],
  ],
  error: [
    ['bg', 'error', 'error-light'],
    ['bg', 'error-strong', 'error'],
    ['text', 'error', 'error-dark'],
    ['border', 'error', 'error'],
  ],
};

export interface TokenFiles {
  primitives: Json;
  light: Json;
  dark: Json;
}

function walkTokens(node: Json, visit: (token: Json) => void): void {
  for (const [key, value] of Object.entries(node)) {
    if (key === '$extensions' || key === '$type' || key === '$value') continue;
    if (!value || typeof value !== 'object' || Array.isArray(value)) continue;
    const child = value as Json;
    if ('$type' in child && '$value' in child) visit(child);
    else walkTokens(child, visit);
  }
}

function validOnly<K extends string>(
  colors: Partial<Record<K, string>>
): Partial<Record<K, string>> {
  return Object.fromEntries(
    Object.entries(colors).filter(([, hex]) => typeof hex === 'string' && hexToRgb(hex) !== null)
  ) as Partial<Record<K, string>>;
}

/**
 * Applies palette and status colours to copies of the three token files,
 * the same steps the admin runs on save: full palettes into primitives,
 * status colours with their -light (100) and -dark (700) variants, then every
 * semantic token that aliases a changed primitive gets its new value.
 */
export function applyColors(
  base: TokenFiles,
  palette: Partial<Record<PaletteColor, string>>,
  status: Partial<Record<StatusColor, string>>
): TokenFiles {
  // Invalid colours are dropped up front, as sanitize_hex_color() does in the admin.
  palette = validOnly(palette);
  status = validOnly(status);
  const files: TokenFiles = structuredClone(base);
  const colors = ((files.primitives.color as Json | undefined) ??= {}) as Record<string, Json>;

  for (const [name, hex] of Object.entries(palette) as Array<[string, string]>) {
    colors[name] = { ...(colors[name] ?? {}), ...paletteTokens(generatePalette(hex), name) };
  }

  for (const [name, hex] of Object.entries(status) as Array<[string, string]>) {
    const shades = generatePalette(hex);
    const variants: Array<[string, string]> = [
      [name, hex],
      [`${name}-light`, shades['100']],
      [`${name}-dark`, shades['700']],
    ];
    for (const [key, value] of variants) {
      const token = colorValue(value);
      if (token) colors[key] = { $type: 'color', $value: token };
    }
  }

  for (const mode of ['light', 'dark'] as const) {
    const tokens = files[mode];

    walkTokens(tokens, (token) => {
      const alias = (token.$extensions as Json | undefined)?.['com.figma.aliasData'] as
        Json | undefined;
      const target = alias?.targetVariableName;
      if (typeof target !== 'string') return;
      const match = /^color\/(\w+)\/(\d+)$/.exec(target);
      if (!match || !palette[match[1] as PaletteColor]) return;
      const value = (colors[match[1]]?.[match[2]] as Json | undefined)?.$value;
      if (value !== undefined) token.$value = structuredClone(value);
    });

    for (const name of Object.keys(status)) {
      for (const [category, tokenName, primitive] of STATUS_MAPPINGS[name as StatusColor]) {
        const target = (tokens[category] as Json | undefined)?.[tokenName] as Json | undefined;
        const value = (colors[primitive] as Json | undefined)?.$value;
        if (target && '$value' in target && value !== undefined) {
          target.$value = structuredClone(value);
        }
      }
    }
  }

  return files;
}

/** Current base (500) colour of a palette or status colour in the token files. */
export function currentColor(files: TokenFiles, name: string): string | null {
  const color = (files.primitives.color as Record<string, Json> | undefined)?.[name];
  if (!color) return null;
  const token = ('$value' in color ? color : color['500']) as Json | undefined;
  const hex = (token?.$value as Json | undefined)?.hex;
  return typeof hex === 'string' ? hex.toUpperCase() : null;
}

export function toJson(data: unknown): string {
  // Same layout as the files in config/design-tokens/ (the admin download
  // streams them as they are): 2-space indent, trailing newline.
  return JSON.stringify(data, null, 2) + '\n';
}

/* ------------------------------------------------------------------------ *
 * Generator-only steps (not in the admin) and the page preview.
 * ------------------------------------------------------------------------ */

function colorTokenAt(primitives: Json, path: string[]): Json | null {
  let node: unknown = primitives.color;
  for (const key of path) {
    if (!node || typeof node !== 'object') return null;
    node = (node as Json)[key];
  }
  return node && typeof node === 'object' && '$value' in (node as Json) ? (node as Json) : null;
}

function hexOfToken(token: Json | null): string | null {
  const hex = (token?.$value as Json | undefined)?.hex;
  return typeof hex === 'string' ? hex.toUpperCase() : null;
}

function setTokenHex(token: Json | null, hex: string): void {
  const value = colorValue(hex);
  if (token && value) token.$value = value;
}

/**
 * The design-system accent roles (color/ds/accent/*) are plain values, not
 * aliases of the accent palette, so the admin's colour sync leaves them
 * orange. The generator moves them with the accent: base and deep fills, the
 * hover fills, the text accent in dark mode (lightest shade that keeps 4.5:1
 * on the dark ground) and the ink on accent fills (black or white by contrast).
 * INTENTIONAL: goes beyond admin parity; without it a blue theme keeps orange
 * focus rings in dark mode and dark text on dark blue buttons.
 */
export function syncAccentRoles(files: TokenFiles, accent: string): void {
  const shades = generatePalette(accent);
  if (!shades['500']) return;
  const p = files.primitives;
  const ground = hexOfToken(colorTokenAt(p, ['ds', 'surface', 'ground', 'dark'])) ?? '#000000';
  const darkText =
    ['500', '400', '300', '200']
      .map((s) => shades[s])
      .find((hex) => contrastRatio(hex, ground) >= 4.5) ?? shades['100'];

  setTokenHex(colorTokenAt(p, ['ds', 'accent', 'base']), shades['500']);
  setTokenHex(colorTokenAt(p, ['ds', 'accent', 'deep']), shades['600']);
  setTokenHex(colorTokenAt(p, ['ds', 'accent', 'hover-fill', 'light']), shades['600']);
  setTokenHex(colorTokenAt(p, ['ds', 'accent', 'hover-fill', 'dark']), shades['500']);
  setTokenHex(colorTokenAt(p, ['ds', 'accent', 'text', 'dark']), darkText);

  const inkToken = colorTokenAt(p, ['ds', 'accent', 'ink']);
  const ink = hexOfToken(inkToken);
  if (ink && contrastRatio(ink, shades['500']) < 4.5) {
    setTokenHex(inkToken, inkFor(shades['500']).ink);
  }
}

/**
 * Token files for one generator state. Only colours that differ from the
 * site's own are applied: regenerating an untouched ramp from its 500 would
 * replace a hand-tuned scale (the greys) with a computed one.
 */
export function generateFiles(
  base: TokenFiles,
  colors: Record<string, string>,
  initial: Record<string, string>
): TokenFiles {
  const changed = (name: string) =>
    !!colors[name] && colors[name].toUpperCase() !== (initial[name] ?? '').toUpperCase();
  const palette: Partial<Record<PaletteColor, string>> = {};
  const status: Partial<Record<StatusColor, string>> = {};
  for (const name of PALETTE_COLORS) if (changed(name)) palette[name] = colors[name];
  for (const name of STATUS_COLORS) if (changed(name)) status[name] = colors[name];

  const files = applyColors(base, palette, status);
  if (palette.accent && hexToRgb(palette.accent)) syncAccentRoles(files, palette.accent);
  return files;
}

function cssColor(value: Json | undefined): string | null {
  if (!value || typeof value.hex !== 'string') return null;
  const alpha = typeof value.alpha === 'number' ? value.alpha : 1;
  if (alpha >= 1) return value.hex.toUpperCase();
  const rgb = hexToRgb(value.hex);
  return rgb ? `rgb(${rgb.r} ${rgb.g} ${rgb.b} / ${Math.round(alpha * 1000) / 1000})` : null;
}

function flattenColors(node: Json, prefix: string, out: Record<string, string>): void {
  for (const [key, value] of Object.entries(node)) {
    if (key.startsWith('$') || !value || typeof value !== 'object') continue;
    const child = value as Json;
    const name = `${prefix}-${key}`;
    if ('$value' in child) {
      const css = cssColor(child.$value as Json);
      if (css) out[name] = css;
    } else {
      flattenColors(child, name, out);
    }
  }
}

/**
 * Inline custom properties that make the page show `files`: every primitive
 * colour that differs from `base`, named as scripts/tokens-core.js names them
 * (color/accent/500 is --rafael-color-accent-500). The semantic tokens are
 * var() references to these, so light and dark mode both follow.
 */
export function pageVariables(base: TokenFiles, files: TokenFiles): Record<string, string> {
  const before: Record<string, string> = {};
  const after: Record<string, string> = {};
  flattenColors((base.primitives.color as Json) ?? {}, '--rafael-color', before);
  flattenColors((files.primitives.color as Json) ?? {}, '--rafael-color', after);
  return Object.fromEntries(
    Object.entries(after).filter(([name, value]) => before[name] !== value)
  );
}

/** Resolves a semantic token the way tokens.css does: through its alias, else its own value. */
export function semanticColor(
  files: TokenFiles,
  mode: 'light' | 'dark',
  path: string
): string | null {
  const token = path
    .split('.')
    .reduce<unknown>((node, key) => (node as Json | undefined)?.[key], files[mode]) as
    Json | undefined;
  if (!token) return null;
  const target = (
    (token.$extensions as Json | undefined)?.['com.figma.aliasData'] as Json | undefined
  )?.targetVariableName;
  if (typeof target === 'string' && target.startsWith('color/')) {
    const primitive = colorTokenAt(files.primitives, target.split('/').slice(1));
    const css = cssColor(primitive?.$value as Json | undefined);
    if (css) return css;
  }
  return cssColor(token.$value as Json | undefined);
}

const PREVIEW_TOKENS: Record<string, string> = {
  '--gp-bg': 'bg.primary',
  '--gp-card': 'bg.secondary',
  '--gp-text': 'text.primary',
  '--gp-muted': 'text.secondary',
  '--gp-border': 'border.default',
  '--gp-brand': 'bg.brand',
  '--gp-on-brand': 'text.on-brand',
  '--gp-brand-2': 'bg.brand-secondary',
  '--gp-accent': 'text.accent',
  '--gp-success': 'bg.success-strong',
  '--gp-warning': 'bg.warning-strong',
  '--gp-error': 'bg.error-strong',
};

/** Custom properties for the light or dark preview card (.token-gen-preview). */
export function previewVariables(
  files: TokenFiles,
  mode: 'light' | 'dark'
): Record<string, string> {
  const vars: Record<string, string> = {};
  for (const [name, path] of Object.entries(PREVIEW_TOKENS)) {
    const value = semanticColor(files, mode, path);
    if (value) vars[name] = value;
  }
  // Fills without a semantic text colour get black or white by contrast.
  for (const name of ['--gp-brand-2', '--gp-success', '--gp-warning', '--gp-error']) {
    if (vars[name]?.startsWith('#')) vars[`${name}-ink`] = inkFor(vars[name]).ink;
  }
  return vars;
}
