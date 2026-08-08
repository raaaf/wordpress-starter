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

/**
 * Split point: the start of Tailwind's theme at-rule.
 *
 * Matched at the start of a line, not by a plain indexOf. A prose mention of the
 * at-rule inside a comment used to move this marker and silently shrink the
 * overrides half of the contract to nothing.
 */
const THEME_AT_RULE = /^@theme\b/m.exec(appCss);
const themeStart = THEME_AT_RULE?.index ?? appCss.length;

/** Everything before Tailwind's theme at-rule: the theme's own overrides. */
const appOverrides = appCss.slice(0, themeStart);

/**
 * Everything from Tailwind's @theme block onwards.
 *
 * This used to be outside the contract, and that is exactly where the next gap
 * opened: the @theme block built --shadow-sm through --shadow-2xl out of six
 * --shadow-color-* properties nothing ever defined, so every plain shadow-*
 * utility rendered no shadow at all while both completeness tests stayed green.
 */
const appTheme = appCss.slice(themeStart);

type Mode = 'light' | 'dark';

/**
 * Drop comments before a selector is classified.
 *
 * The file already learned this once with the @theme marker: prose that quotes
 * CSS syntax is indistinguishable from the syntax itself to a regex. A comment
 * reading "see the [data-theme='dark'] block above", written directly in front
 * of a rule, made that rule classify as a dark selector and quietly dropped it
 * from the mirroring check.
 */
function stripComments(css: string): string {
  return css.replace(/\/\*[\s\S]*?\*\//g, ' ');
}

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

    const selector = stripComments(css.slice(i, open)).split(/[;}]/).pop()?.trim() ?? '';
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
 *
 * The order of the middle two layers is the whole point, and it used to be
 * wrong. app.css imports tokens.css on line 12 and then declares its own
 * unconditional `:root` block. `:root` and `[data-theme='dark']` have the same
 * specificity (0,1,0), so the later source wins: an unscoped correction in
 * app.css beats the export's dark declaration. Reading tokens.css dark before
 * app.css light modelled the opposite and reported the value the export
 * intended rather than the one the browser paints — which is why the contrast
 * contract below stayed green through ten roles that were failing in dark mode.
 */
function resolve_(name: string, mode: Mode): string | null {
  const layers = [
    declarations(blocksFor(appOverrides, mode)),
    ...(mode === 'dark'
      ? [
          declarations(blocksFor(appOverrides, 'light')),
          declarations(blocksFor(tokensCss, mode)),
          declarations(blocksFor(tokensCss, 'light')),
        ]
      : [declarations(blocksFor(tokensCss, mode))]),
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

  it('resolves every unguarded reference inside the @theme block', () => {
    const defined = new Set([
      ...declarations(tokensCss).keys(),
      ...declarations(appOverrides).keys(),
      ...declarations(appTheme).keys(),
    ]);
    const referenced = bareReferences(appTheme);
    const missing = [...new Set(referenced)].filter((name) => !defined.has(name)).sort();

    expect(
      missing,
      `app.css @theme references properties nothing defines: ${missing.join(', ')}`
    ).toEqual([]);
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
  ['--text-on-brand', '--bg-brand', 'text on a brand surface'],
  ['--text-link', '--bg-primary', 'link text'],
  ['--text-link-hover', '--bg-primary', 'link text on hover'],
  ['--text-accent', '--bg-accent-subtle', 'text in a filled accent badge'],
  ['--text-inverse', '--bg-error-strong', 'danger button label on its fill'],
  ['--text-inverse', '--bg-success-strong', 'solid success badge label'],
  ['--text-inverse', '--bg-warning-strong', 'solid warning badge label'],
  ['--text-on-accent', '--bg-accent', 'checkbox mark on the checked fill'],
];

const UI_PAIRS: [string, string, string][] = [
  ['--border-control', '--bg-primary', 'form control border'],
  ['--ring-focus', '--bg-primary', 'focus ring'],
  ['--border-focus', '--bg-primary', 'focused control border'],
  ['--bg-accent', '--bg-primary', 'checked control fill against the page'],
  ['--border-brand', '--bg-primary', 'brand border: spinner arc, card hover edge'],
  ['--icon-secondary', '--bg-primary', 'select chevron and input clear icon'],
  ['--icon-brand', '--bg-primary', 'brand icon'],
  ['--icon-success', '--bg-success', 'status icon on its own surface'],
  ['--icon-warning', '--bg-warning', 'status icon on its own surface'],
  ['--icon-error', '--bg-error', 'status icon on its own surface'],
];

/**
 * The primary button is the one fill built from a gradient, so a single pair
 * cannot describe it: the label has to clear 4.5:1 against BOTH stops, in every
 * interaction state and both schemes. It is also the place the export got wrong
 * — the gradient was declared once and never flipped with the scheme, so in dark
 * mode the fill darkened while --text-inverse was already near-black, and the
 * button lost contrast the more it was used.
 */
const BUTTON_GRADIENTS: [string, string, string][] = [
  ['--gradient-primary-start', '--gradient-primary-end', 'primary button at rest'],
  ['--gradient-primary-hover-start', '--gradient-primary-hover-end', 'primary button on hover'],
  [
    '--gradient-primary-active-start',
    '--gradient-primary-active-end',
    'primary button when pressed',
  ],
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

  it.each(BUTTON_GRADIENTS)(
    '--text-on-accent clears 4.5:1 against both stops of %s..%s (%s)',
    (startName, endName) => {
      const label = resolve_('--text-on-accent', mode);
      const start = resolve_(startName, mode);
      const end = resolve_(endName, mode);
      expect(label, `--text-on-accent does not resolve`).not.toBeNull();
      expect(start, `${startName} does not resolve`).not.toBeNull();
      expect(end, `${endName} does not resolve`).not.toBeNull();

      const worst = Math.min(
        contrast(label as string, start as string),
        contrast(label as string, end as string)
      );
      expect(
        Number(worst.toFixed(2)),
        `--text-on-accent ${label} on ${start}..${end} is ${worst.toFixed(2)}:1 at its worst stop`
      ).toBeGreaterThanOrEqual(4.5);
    }
  );
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

/**
 * Collect the dark blocks separately by how they are selected.
 *
 * blocksFor() deliberately merges both, because for contrast purposes they are
 * one scheme. Telling them apart is what the mirroring test below needs.
 */
function darkBlocksByKind(css: string): { attribute: string; query: string } {
  const out = { attribute: '', query: '' };
  let i = 0;

  while (i < css.length) {
    const open = css.indexOf('{', i);
    if (open === -1) break;

    const selector = stripComments(css.slice(i, open)).split(/[;}]/).pop()?.trim() ?? '';
    let depth = 1;
    let j = open + 1;
    while (j < css.length && depth > 0) {
      if (css[j] === '{') depth++;
      else if (css[j] === '}') depth--;
      j++;
    }
    const body = css.slice(open + 1, j - 1);

    if (/prefers-color-scheme:\s*dark/.test(selector)) {
      out.query += `\n${blocksFor(body, 'light')}`;
    } else if (/\[data-theme=['"]dark['"]\]/.test(selector)) {
      out.attribute += `\n${body}`;
    }

    i = j;
  }

  return out;
}

/**
 * The compensation layer must not silently defeat the export's dark palette.
 *
 * tokens.css is generated from a Figma export and gets overwritten on every
 * sync, so corrections belong in app.css — that part is deliberate. What is not
 * deliberate is the failure mode it invites: a correction meant for light mode
 * written on a bare `:root` also lands in dark, where it beats the generated
 * dark value without anyone noticing. Ten roles sat like that at once, and the
 * comment block above them described the intended dark values correctly the
 * whole time. Nothing measured the difference between intent and cascade.
 */
describe('scheme scoping of the compensation layer', () => {
  it('resets every unscoped :root override that would bury a differing dark value', () => {
    const appLight = declarations(blocksFor(appOverrides, 'light'));
    const appDark = declarations(blocksFor(appOverrides, 'dark'));
    const exportLight = declarations(blocksFor(tokensCss, 'light'));
    const exportDark = declarations(blocksFor(tokensCss, 'dark'));

    const stranded = [...appLight.keys()].filter((name) => {
      if (!isOurs(name)) return false;

      const darkValue = exportDark.get(name);
      // Nothing to bury: the export either omits the token or gives dark the
      // same value as light.
      if (darkValue === undefined || darkValue === exportLight.get(name)) return false;

      return !appDark.has(name);
    });

    expect(stranded).toEqual([]);
  });

  it('declares the same tokens in both dark blocks', () => {
    const { attribute, query } = darkBlocksByKind(appOverrides);
    const inAttribute = declarations(attribute);
    const inQuery = declarations(query);

    // A token in only one of the two means a reader who toggles the theme sees
    // different colours than a reader whose system is set to dark.
    const onlyAttribute = [...inAttribute.keys()].filter((n) => isOurs(n) && !inQuery.has(n));
    const onlyQuery = [...inQuery.keys()].filter((n) => isOurs(n) && !inAttribute.has(n));
    const disagreeing = [...inAttribute.entries()]
      .filter(([n, v]) => isOurs(n) && inQuery.has(n) && inQuery.get(n) !== v)
      .map(([n]) => n);

    expect({ onlyAttribute, onlyQuery, disagreeing }).toEqual({
      onlyAttribute: [],
      onlyQuery: [],
      disagreeing: [],
    });
  });
});
