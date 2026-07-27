/// <reference types="node" />
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Contract for the design tokens.
 *
 * tokens.css is generated from Figma, so nobody reviews it by hand. Two whole
 * classes of defect slipped through that way and were only found by measuring
 * the rendered page: references to variables the export never defines, and
 * colour roles whose pairs do not reach a usable contrast.
 *
 * These tests turn both into a build failure. If a future export reopens a gap,
 * this goes red before anyone ships it.
 */

const CSS_DIR = resolve(__dirname, '../css');
const read = (file: string): string => readFileSync(resolve(CSS_DIR, file), 'utf8');

const tokensCss = read('tokens.css');
const appCss = read('app.css');

/** Everything before Tailwind's @theme block: the theme's own overrides. */
const appOverrides = appCss.slice(0, appCss.indexOf('@theme'));

type Mode = 'light' | 'dark';

/**
 * Collect the declaration bodies that apply in one colour scheme.
 *
 * Splitting the file at the first dark selector does not work: app.css
 * interleaves light and dark blocks, so everything after the first dark block
 * would be misfiled. This walks the braces instead and classifies each block by
 * its own selector.
 *
 * Dark is declared twice, via [data-theme='dark'] and inside a
 * prefers-color-scheme query; both carry the same values.
 */
function blocksFor(css: string, mode: Mode): string {
  const out: string[] = [];
  let i = 0;

  while (i < css.length) {
    const open = css.indexOf('{', i);
    if (open === -1) break;

    const selector = css.slice(i, open).split(/[;}]/).pop()?.trim() ?? '';
    let depth = 1;
    let j = open + 1;
    while (j < css.length && depth > 0) {
      if (css[j] === '{') depth++;
      else if (css[j] === '}') depth--;
      j++;
    }
    const body = css.slice(open + 1, j - 1);

    const isDarkSelector = /\[data-theme=['"]dark['"]\]/.test(selector);
    const isDarkQuery = /prefers-color-scheme:\s*dark/.test(selector);
    const isLightSelector = selector.includes(':root') && !isDarkSelector;

    if (isDarkQuery) {
      // The query wraps its own :root rule; recurse into it.
      if (mode === 'dark') out.push(blocksFor(body, 'light'));
    } else if (mode === 'dark' ? isDarkSelector : isLightSelector) {
      out.push(body);
    }

    i = j;
  }

  return out.join('\n');
}

/** Custom properties declared in a block, last declaration wins. */
function declarations(css: string): Map<string, string> {
  const map = new Map<string, string>();
  for (const match of css.matchAll(/(--[a-z0-9-]+)\s*:\s*([^;]+);/g)) {
    map.set(match[1], match[2].trim());
  }
  return map;
}

/**
 * Resolve a token for one mode.
 *
 * app.css wins over tokens.css, mirroring the cascade: its :root block comes
 * after the tokens import. Dark values fall back to light ones, because dark
 * mode only overrides part of the palette.
 */
function resolve_(name: string, mode: Mode): string | null {
  const layers = [
    declarations(blocksFor(appOverrides, mode)),
    declarations(blocksFor(tokensCss, mode)),
    ...(mode === 'dark'
      ? [
          declarations(blocksFor(appOverrides, 'light')),
          declarations(blocksFor(tokensCss, 'light')),
        ]
      : []),
  ];

  let value: string | undefined;
  for (const layer of layers) {
    value = layer.get(name);
    if (value !== undefined) break;
  }
  if (value === undefined) return null;

  const varMatch = value.match(/^var\((--[a-z0-9-]+)/);
  return varMatch ? resolve_(varMatch[1], mode) : value;
}

function toRgb(value: string): [number, number, number] | null {
  const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hex) {
    const h = hex[1].length === 3 ? [...hex[1]].map((c) => c + c).join('') : hex[1];
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16)) as [number, number, number];
  }
  const rgb = value.match(/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/i);
  return rgb ? [Number(rgb[1]), Number(rgb[2]), Number(rgb[3])] : null;
}

function luminance([r, g, b]: [number, number, number]): number {
  const channel = (c: number): number => {
    const v = c / 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  };
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

function contrast(a: string, b: string): number {
  const [x, y] = [toRgb(a), toRgb(b)];
  if (!x || !y) return 0;
  const [hi, lo] = [luminance(x), luminance(y)].sort((m, n) => n - m);
  return (hi + 0.05) / (lo + 0.05);
}

/** Tailwind declares its own --tw-* properties at runtime; they are not ours. */
const isOurs = (name: string): boolean => !name.startsWith('--tw-');

/**
 * References that would resolve to nothing.
 *
 * var(--x, fallback) is safe by construction, and a few properties are set at
 * runtime rather than in CSS (--header-height comes from initHeaderHeight).
 * Only a bare var(--x) collapses the declaration, which is how the gaps in the
 * spacing scale went unnoticed.
 */
function bareReferences(css: string): string[] {
  return [...css.matchAll(/var\(\s*(--[a-z0-9-]+)\s*\)/g)].map((m) => m[1]).filter(isOurs);
}

describe('token completeness', () => {
  it('resolves every unguarded reference in tokens.css', () => {
    const defined = new Set([
      ...declarations(tokensCss).keys(),
      ...declarations(appOverrides).keys(),
    ]);
    const referenced = bareReferences(tokensCss);
    const missing = [...new Set(referenced)].filter((name) => !defined.has(name)).sort();

    expect(
      missing,
      `tokens.css references properties nothing defines: ${missing.join(', ')}`
    ).toEqual([]);
  });

  it('resolves every unguarded reference in the theme overrides', () => {
    const defined = new Set([
      ...declarations(tokensCss).keys(),
      ...declarations(appOverrides).keys(),
    ]);
    const referenced = bareReferences(appOverrides);
    const missing = [...new Set(referenced)].filter((name) => !defined.has(name)).sort();

    expect(missing, `app.css references properties nothing defines: ${missing.join(', ')}`).toEqual(
      []
    );
  });
});

/**
 * Contrast contracts.
 *
 * --text-tertiary is deliberately absent: it is the de-emphasised role and is
 * not used for text that has to be read. Placeholders left it for that reason.
 */
const TEXT_PAIRS: [string, string, string][] = [
  ['--text-primary', '--bg-primary', 'body text'],
  ['--text-secondary', '--bg-primary', 'secondary text'],
  ['--text-placeholder', '--bg-primary', 'placeholder'],
  ['--text-success', '--bg-success', 'success message'],
  ['--text-warning', '--bg-warning', 'warning message'],
  ['--text-error', '--bg-error', 'error message'],
];

const UI_PAIRS: [string, string, string][] = [
  ['--border-control', '--bg-primary', 'form control border'],
  ['--ring-focus', '--bg-primary', 'focus ring'],
];

describe.each<Mode>(['light', 'dark'])('contrast contract (%s)', (mode) => {
  it.each(TEXT_PAIRS)('%s on %s clears 4.5:1 (%s)', (fg, bg) => {
    const [fgValue, bgValue] = [resolve_(fg, mode), resolve_(bg, mode)];
    expect(fgValue, `${fg} does not resolve`).not.toBeNull();
    expect(bgValue, `${bg} does not resolve`).not.toBeNull();

    const ratio = contrast(fgValue as string, bgValue as string);
    expect(
      Number(ratio.toFixed(2)),
      `${fg} ${fgValue} on ${bg} ${bgValue} is ${ratio.toFixed(2)}:1`
    ).toBeGreaterThanOrEqual(4.5);
  });

  it.each(UI_PAIRS)('%s on %s clears 3:1 (%s)', (fg, bg) => {
    const [fgValue, bgValue] = [resolve_(fg, mode), resolve_(bg, mode)];
    expect(fgValue, `${fg} does not resolve`).not.toBeNull();
    expect(bgValue, `${bg} does not resolve`).not.toBeNull();

    const ratio = contrast(fgValue as string, bgValue as string);
    expect(
      Number(ratio.toFixed(2)),
      `${fg} ${fgValue} on ${bg} ${bgValue} is ${ratio.toFixed(2)}:1`
    ).toBeGreaterThanOrEqual(3);
  });
});

describe('focus ring', () => {
  it('is opaque, so it is not washed out by the surface behind it', () => {
    for (const mode of ['light', 'dark'] as Mode[]) {
      const value = resolve_('--ring-focus', mode);
      expect(value, `--ring-focus does not resolve in ${mode} mode`).not.toBeNull();
      expect(value, `--ring-focus is translucent in ${mode} mode: ${value}`).not.toMatch(
        /rgba|hsla/
      );
    }
  });
});
