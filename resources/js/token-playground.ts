// Token playground on the design system page (templates/styleguide/playground.blade.php).
// Writes CSS custom properties inline on <html>, so every token-driven element on
// the page follows. Nothing is stored: a reload or "Reset" brings the theme back.

import type { AlpineMagics } from '../../src/types/alpine';

export const DEFAULT_ACCENT = '#FF6B35';
// Card radius in px: the slider's unit. The theme's card radius (radius-lg) is 12px.
export const DEFAULT_RADIUS = 12;
export const MAX_RADIUS = 48;
// Button corner radius in px. The top step stands for the design system's pill.
export const BUTTON_PILL = 34;
export const DEFAULT_SCALE = 1;
export const CARD_RADIUS_CAP = 48;

const RADIUS_TOKENS = ['sm', 'default', 'md', 'lg', 'xl', '2xl', '3xl'];

const FONT_SIZE_TOKENS = ['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl'];

function channels(hex: string): [number, number, number] | null {
  const m = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
  if (!m) return null;
  const n = parseInt(m[1], 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

/** WCAG 2 relative luminance of a #rrggbb colour. */
export function luminance(hex: string): number {
  const rgb = channels(hex);
  if (!rgb) return 0;
  const [r, g, b] = rgb.map((c) => {
    const s = c / 255;
    return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrastRatio(a: string, b: string): number {
  const la = luminance(a);
  const lb = luminance(b);
  return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

/** Black or white text on `accent`, whichever contrasts more, with its ratio. */
export function inkFor(accent: string): { ink: '#000000' | '#FFFFFF'; ratio: number } {
  const black = contrastRatio(accent, '#000000');
  const white = contrastRatio(accent, '#FFFFFF');
  return black >= white ? { ink: '#000000', ratio: black } : { ink: '#FFFFFF', ratio: white };
}

/**
 * Inline custom properties for one playground state. `fontSizes` holds the
 * theme's own font-size values, read before any override, so the scale wraps
 * them instead of replacing the fluid clamp() formulas.
 */
export function playgroundVariables(
  accent: string,
  radius: number,
  buttonRadius: number,
  scale: number,
  fontSizes: Record<string, string>
): Record<string, string> {
  const vars: Record<string, string> = {};
  const deep = `color-mix(in oklch, ${accent}, black 18%)`;

  vars['--rafael-color-accent-400'] = `color-mix(in oklch, ${accent}, white 18%)`;
  vars['--rafael-color-accent-500'] = accent;
  vars['--rafael-color-accent-600'] = deep;
  vars['--rafael-color-accent-700'] = `color-mix(in oklch, ${accent}, black 30%)`;
  vars['--rafael-color-accent-800'] = `color-mix(in oklch, ${accent}, black 45%)`;
  vars['--rafael-color-ds-accent-base'] = accent;
  vars['--rafael-color-ds-accent-deep'] = deep;
  vars['--rafael-color-ds-accent-hover-fill-light'] = deep;
  vars['--rafael-color-ds-accent-hover-fill-dark'] = accent;
  vars['--rafael-color-ds-accent-text-dark'] = accent;
  vars['--rafael-color-ds-accent-ink'] = inkFor(accent).ink;

  // Card slider: every radius except the pill (cards, inputs, small boxes).
  const px = `${Math.min(Math.max(radius, 0), CARD_RADIUS_CAP)}px`;
  for (const name of RADIUS_TOKENS) {
    vars[`--rafael-radius-${name}`] = px;
  }
  // Button slider: radius-full drives buttons and pills; badges follow via
  // .token-playground-live in app.css (they use Tailwind's rounded-full).
  vars['--rafael-radius-full'] =
    buttonRadius >= BUTTON_PILL ? '999px' : `${Math.max(buttonRadius, 0)}px`;

  for (const name of FONT_SIZE_TOKENS) {
    const original = fontSizes[name];
    if (original) vars[`--rafael-font-size-${name}`] = `calc(${original} * ${scale})`;
  }

  return vars;
}

export function readFontSizes(style: CSSStyleDeclaration): Record<string, string> {
  const sizes: Record<string, string> = {};
  for (const name of FONT_SIZE_TOKENS) {
    const value = style.getPropertyValue(`--rafael-font-size-${name}`).trim();
    if (value) sizes[name] = value;
  }
  return sizes;
}

export interface TokenPlaygroundComponent extends AlpineMagics {
  accent: string;
  radius: number;
  buttonRadius: number;
  scale: number;
  fontSizes: Record<string, string>;
  applied: string[];
  readonly ink: string;
  readonly contrast: string;
  init(): void;
  update(): void;
  reset(): void;
}

/**
 * Alpine component. `labels` carries the translated strings from the template,
 * so the TypeScript side stays free of copy.
 */
export function createTokenPlaygroundComponent(
  labels: Record<string, string> = {}
): TokenPlaygroundComponent {
  const t = (key: string, fallback: string) => labels[key] ?? fallback;

  return {
    ...({} as AlpineMagics),
    accent: DEFAULT_ACCENT,
    radius: DEFAULT_RADIUS,
    buttonRadius: BUTTON_PILL,
    scale: DEFAULT_SCALE,
    fontSizes: {},
    applied: [],

    get ink() {
      return inkFor(this.accent).ink;
    },

    get contrast() {
      const { ink, ratio } = inkFor(this.accent);
      const colour = ink === '#000000' ? t('black', 'black') : t('white', 'white');
      return t('contrast', '%1$s text, contrast %2$s:1')
        .replace('%1$s', colour)
        .replace('%2$s', ratio.toFixed(1));
    },

    init() {
      this.fontSizes = readFontSizes(getComputedStyle(document.documentElement));
    },

    update() {
      const root = document.documentElement;
      root.classList.add('token-playground-live');
      const vars = playgroundVariables(
        this.accent,
        Number(this.radius),
        Number(this.buttonRadius),
        Number(this.scale),
        this.fontSizes
      );
      for (const [name, value] of Object.entries(vars)) {
        root.style.setProperty(name, value);
      }
      this.applied = Object.keys(vars);
    },

    reset() {
      const root = document.documentElement;
      for (const name of this.applied) {
        root.style.removeProperty(name);
      }
      root.classList.remove('token-playground-live');
      this.applied = [];
      this.accent = DEFAULT_ACCENT;
      this.radius = DEFAULT_RADIUS;
      this.buttonRadius = BUTTON_PILL;
      this.scale = DEFAULT_SCALE;
    },
  };
}
