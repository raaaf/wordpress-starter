import { mkdtempSync, readFileSync, rmSync } from 'fs';
import { tmpdir } from 'os';
import { join } from 'path';
import { describe, expect, it, vi } from 'vitest';
import {
  fluidClamp,
  fluidLineHeight,
  FLUID_SIZES,
  HEADING_LINE_HEIGHTS,
  VIEWPORT_MIN,
  VIEWPORT_MAX,
  ROOT_PX,
  flattenTokens,
  extractColorValue,
  extractStringValue,
  assertSafeVarNameSegment,
  assertSafeCssValue,
  writeAndVerify,
} from './transform-tokens.js';

/**
 * Evaluate a clamp() or calc() string at a given viewport width.
 * Supports: rem, vw, plain numbers and nested calc() expressions.
 */
function evaluate(expr, viewportPx = 1920, rootPx = ROOT_PX) {
  const trimmed = expr.trim();

  // Plain number (unitless line-height)
  if (/^-?\d+(\.\d+)?$/.test(trimmed)) return parseFloat(trimmed);

  // rem: convert to px
  const remMatch = trimmed.match(/^(-?\d+(?:\.\d+)?)rem$/);
  if (remMatch) return parseFloat(remMatch[1]) * rootPx;

  // clamp(min, preferred, max)
  const clampMatch = trimmed.match(/^clamp\((.*)\)$/);
  if (clampMatch) {
    const parts = splitTopLevel(clampMatch[1]);
    const min = evaluate(parts[0], viewportPx, rootPx);
    const preferred = evaluate(parts[1], viewportPx, rootPx);
    const max = evaluate(parts[2], viewportPx, rootPx);
    return Math.min(Math.max(preferred, min), max);
  }

  // calc(expr) - evaluate binary +/- with mixed units
  const calcMatch = trimmed.match(/^calc\((.*)\)$/);
  if (calcMatch) {
    const tokens = calcMatch[1].split(/\s+/);
    let result = 0;
    let op = '+';
    for (const token of tokens) {
      if (token === '+' || token === '-') {
        op = token;
        continue;
      }
      let value;
      if (token.endsWith('vw')) {
        value = (parseFloat(token) * viewportPx) / 100;
      } else if (token.endsWith('rem')) {
        value = parseFloat(token) * rootPx;
      } else {
        value = parseFloat(token);
      }
      result = op === '+' ? result + value : result - value;
    }
    return result;
  }

  throw new Error(`Cannot evaluate: ${expr}`);
}

function splitTopLevel(s) {
  const parts = [];
  let depth = 0;
  let current = '';
  for (const ch of s) {
    if (ch === '(') depth++;
    if (ch === ')') depth--;
    if (ch === ',' && depth === 0) {
      parts.push(current.trim());
      current = '';
    } else {
      current += ch;
    }
  }
  if (current.trim()) parts.push(current.trim());
  return parts;
}

describe('fluidClamp', () => {
  it('returns a static rem when min equals max', () => {
    expect(fluidClamp(16, 16)).toBe('1rem');
    expect(fluidClamp(14, 14)).toBe('0.875rem');
    expect(fluidClamp(12, 12)).toBe('0.75rem');
  });

  it('produces a clamp() expression when min differs from max', () => {
    const result = fluidClamp(38, 60);
    expect(result).toMatch(/^clamp\(/);
    expect(result).toContain('vw');
  });

  it('evaluates to minPx at VIEWPORT_MIN', () => {
    const result = fluidClamp(38, 60);
    const px = evaluate(result, VIEWPORT_MIN);
    expect(px).toBeCloseTo(38, 2);
  });

  it('evaluates to maxPx at VIEWPORT_MAX', () => {
    const result = fluidClamp(38, 60);
    const px = evaluate(result, VIEWPORT_MAX);
    expect(px).toBeCloseTo(60, 2);
  });

  it('interpolates linearly at mid viewport', () => {
    const result = fluidClamp(38, 60);
    const midVw = (VIEWPORT_MIN + VIEWPORT_MAX) / 2;
    const expectedMid = (38 + 60) / 2;
    const px = evaluate(result, midVw);
    expect(px).toBeCloseTo(expectedMid, 2);
  });

  it('clamps below VIEWPORT_MIN', () => {
    const result = fluidClamp(38, 60);
    const px = evaluate(result, 200);
    expect(px).toBeCloseTo(38, 2);
  });

  it('clamps above VIEWPORT_MAX', () => {
    const result = fluidClamp(38, 60);
    const px = evaluate(result, 3000);
    expect(px).toBeCloseTo(60, 2);
  });

  it('produces strictly monotone output across all FLUID_SIZES entries at every viewport', () => {
    const viewports = [VIEWPORT_MIN, 480, 768, 1024, 1280, VIEWPORT_MAX];
    const keys = Object.keys(FLUID_SIZES);
    for (const vw of viewports) {
      const sizes = keys.map((k) => {
        const { min, max } = FLUID_SIZES[k];
        return evaluate(fluidClamp(min, max), vw);
      });
      for (let i = 1; i < sizes.length; i++) {
        expect(sizes[i], `${keys[i]} at ${vw}vw must be >= ${keys[i - 1]}`).toBeGreaterThanOrEqual(
          sizes[i - 1]
        );
      }
    }
  });
});

describe('fluidLineHeight', () => {
  /**
   * The predecessor of this function emitted `clamp(1.2, calc(1.44 - 0.0125vw), 1.4)`,
   * which subtracts a length from a plain number. CSS drops the whole declaration, so
   * every large heading silently inherited the body line-height of 1.5. The old tests
   * did not catch it because they evaluated the arithmetic themselves instead of
   * checking that the expression is something a browser will accept.
   */
  it('never mixes a unitless term with a length inside calc()', () => {
    for (const [key, mobile, desktop] of [
      ['6xl', 1.5, 1.1],
      ['4xl', 1.4, 1.2],
      ['3xl', 1.35, 1.25],
      ['2xl', 1.4, 1.3],
    ]) {
      const result = fluidLineHeight(key, mobile, desktop);
      const calc = result.match(/calc\(([^)]*)\)/);
      expect(calc, `${key}: no calc() in ${result}`).not.toBeNull();
      for (const term of calc[1].split(/\s*[+-]\s*/).filter(Boolean)) {
        expect(term, `${key}: unitless term "${term}" in ${result}`).toMatch(
          /^-?\d+(\.\d+)?(rem|px|em|vw|vh)$/
        );
      }
      for (const bound of [
        result.match(/^clamp\(([^,]+),/)[1],
        result.match(/,\s*([^,]+)\)$/)[1],
      ]) {
        expect(bound.trim(), `${key}: unitless clamp bound in ${result}`).toMatch(/(rem|px|em)$/);
      }
    }
  });

  it('hits the intended ratio at VIEWPORT_MIN', () => {
    // 6xl is FLUID_SIZES['6xl'].min px at VIEWPORT_MIN, so a ratio of 1.5 means
    // that many px of leading.
    const lh = evaluate(fluidLineHeight('6xl', 1.5, 1.1), VIEWPORT_MIN);
    expect(lh / FLUID_SIZES['6xl'].min).toBeCloseTo(1.5, 2);
  });

  it('hits the intended ratio at VIEWPORT_MAX', () => {
    // 6xl is FLUID_SIZES['6xl'].max px at VIEWPORT_MAX, so a ratio of 1.1 means
    // that many px of leading.
    const lh = evaluate(fluidLineHeight('6xl', 1.5, 1.1), VIEWPORT_MAX);
    expect(lh / FLUID_SIZES['6xl'].max).toBeCloseTo(1.1, 2);
  });

  it('keeps the ratio between the two endpoints across the range', () => {
    const expr = fluidLineHeight('4xl', 1.4, 1.2);
    // 4xl min/max per FLUID_SIZES (updated with the rafaelalex.de type scale).
    const sizeExpr = fluidClamp(30, 44);
    for (const vw of [320, 640, 960, 1280, 1600, 1920]) {
      const ratio = evaluate(expr, vw) / evaluate(sizeExpr, vw);
      expect(ratio, `ratio ${ratio} out of range at ${vw}px`).toBeGreaterThanOrEqual(1.2 - 0.001);
      expect(ratio, `ratio ${ratio} out of range at ${vw}px`).toBeLessThanOrEqual(1.4 + 0.001);
    }
  });

  it('rejects an unknown size key instead of emitting nonsense', () => {
    expect(() => fluidLineHeight('nope', 1.4, 1.2)).toThrow(/unknown size key/);
  });

  /**
   * Measured from the shipped headline face via canvas TextMetrics: 1.022em from the
   * top of A-umlaut to the bottom of a descender. Below that, consecutive lines touch;
   * this floor keeps roughly a tenth of an em of air. Re-measure when the face changes.
   */
  const MIN_HEADING_LINE_HEIGHT = 1.1;

  // Only display/h1/h2/h3 are fluid (mobile/desktop ratio endpoints tied to a
  // FLUID_SIZES key). h4/h5/body are static ratios (see HEADING_LINE_HEIGHTS in
  // transform-tokens.js) and are checked separately below.
  const FLUID_HEADING_ENTRIES = Object.values(HEADING_LINE_HEIGHTS).filter((v) => 'key' in v);

  it('keeps every heading above the ink height of the headline face', () => {
    for (const { key, mobile, desktop } of FLUID_HEADING_ENTRIES) {
      const { min: minPx, max: maxPx } = FLUID_SIZES[key];
      const lhExpr = fluidLineHeight(key, mobile, desktop);
      const sizeExpr = fluidClamp(minPx, maxPx);
      for (const vw of [320, 768, 1280, 1920]) {
        const ratio = evaluate(lhExpr, vw) / evaluate(sizeExpr, vw);
        // display/h1 desktop ratio sits exactly on the floor; tolerate float rounding.
        expect(ratio, `${key} at ${vw}px is ${ratio.toFixed(3)}`).toBeGreaterThanOrEqual(
          MIN_HEADING_LINE_HEIGHT - 1e-9
        );
      }
    }
  });

  it('keeps the ratio non-decreasing as the level rises, at both ends of the range', () => {
    // Read straight from the single source of truth so a change to h4/h5/body in
    // transform-tokens.js is caught here instead of being checked against a copy
    // of itself.
    //
    // The rafaelalex.de scale intentionally ties display/h1 and h4/h5 to the same
    // ratio (both pairs read as one visual step, not two), so this uses >= instead
    // of the strict > a fully distinct curve would need. The overall curve still
    // has to be non-decreasing, and body still has to be the loosest.
    const { static: h4 } = HEADING_LINE_HEIGHTS.h4;
    const { static: h5 } = HEADING_LINE_HEIGHTS.h5;
    const { static: body } = HEADING_LINE_HEIGHTS.body;

    for (const vw of [320, 1920]) {
      let previous = 0;
      for (const { key, mobile, desktop } of FLUID_HEADING_ENTRIES) {
        const { min: minPx, max: maxPx } = FLUID_SIZES[key];
        const ratio =
          evaluate(fluidLineHeight(key, mobile, desktop), vw) /
          evaluate(fluidClamp(minPx, maxPx), vw);
        // Ties (display/h1) can land a hair below `previous` due to float rounding.
        expect(ratio, `${key} at ${vw}px breaks the curve`).toBeGreaterThanOrEqual(previous - 1e-9);
        previous = ratio;
      }
      // h4 and h5 are static and sit at the loose end of the curve.
      expect(h4, `h4 at ${vw}px breaks the curve`).toBeGreaterThanOrEqual(previous - 1e-9);
      expect(h5).toBeGreaterThanOrEqual(h4);
      // Body stays clearly looser than every heading.
      expect(body).toBeGreaterThan(h5);
    }
  });
});

describe('FLUID_SIZES table integrity', () => {
  it('covers all primitive font-size keys used in the theme', () => {
    const expectedKeys = ['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl'];
    for (const key of expectedKeys) {
      expect(FLUID_SIZES, `missing key: ${key}`).toHaveProperty(key);
    }
  });

  it('has min <= max for every entry', () => {
    for (const [key, { min, max }] of Object.entries(FLUID_SIZES)) {
      expect(min, `${key}: min must be <= max`).toBeLessThanOrEqual(max);
    }
  });

  it('keeps xs/sm/base non-fluid (min === max) for reading stability', () => {
    expect(FLUID_SIZES.xs.min).toBe(FLUID_SIZES.xs.max);
    expect(FLUID_SIZES.sm.min).toBe(FLUID_SIZES.sm.max);
    expect(FLUID_SIZES.base.min).toBe(FLUID_SIZES.base.max);
  });
});

describe('flattenTokens', () => {
  it('flattens a nested token tree into dash-joined keys', () => {
    const tree = {
      accent: {
        500: { $type: 'color', $value: { hex: '#336699' } },
        600: { $type: 'color', $value: { hex: '#224488' } },
      },
    };
    const flat = flattenTokens(tree, '', extractColorValue);
    expect(flat).toEqual({ 'accent-500': '#336699', 'accent-600': '#224488' });
  });

  it('recurses through multiple nesting levels', () => {
    const tree = {
      brand: {
        accent: {
          500: { $type: 'color', $value: { hex: '#336699' } },
        },
      },
    };
    const flat = flattenTokens(tree, '', extractColorValue);
    expect(flat).toEqual({ 'brand-accent-500': '#336699' });
  });

  it('skips metadata keys and alias values with no resolved color', () => {
    const tree = {
      $description: 'ignored',
      accent: { $type: 'color', $value: '{color.other}' },
    };
    const flat = flattenTokens(tree, '', extractColorValue);
    expect(flat).toEqual({});
  });

  it('resolves a $root entry to the base token name (Figma nested-with-base-value shape)', () => {
    // Mirrors config/design-tokens/primitives.tokens.json spacing.0/1/2: a nested
    // object carrying both a hidden sub-value ("5") and a $root base value.
    const tree = {
      0: {
        5: { $type: 'number', $value: 2 },
        $root: { $type: 'number', $value: 0 },
      },
    };
    const flat = flattenTokens(tree, '', (token) => token.$value);
    expect(flat).toEqual({ 0: 0, '0-5': 2 });
  });
});

describe('assertSafeVarNameSegment (hostile Figma input)', () => {
  it('accepts ordinary token name segments', () => {
    expect(assertSafeVarNameSegment('color-accent-500', 'test')).toBe('color-accent-500');
  });

  it('rejects a name that would break out of the CSS custom property', () => {
    expect(() => assertSafeVarNameSegment('accent}; color: red; --x:{', 'test')).toThrow(
      /unsafe variable name/
    );
  });

  it('rejects a name containing a slash or space left over from a bad alias path', () => {
    expect(() => assertSafeVarNameSegment('color/accent 500', 'test')).toThrow(
      /unsafe variable name/
    );
  });
});

describe('assertSafeCssValue (hostile Figma input)', () => {
  it('accepts ordinary CSS values', () => {
    expect(assertSafeCssValue('var(--color-accent-500)', 'test')).toBe('var(--color-accent-500)');
  });

  it('rejects a value that injects a new declaration via a semicolon', () => {
    expect(() => assertSafeCssValue('red; --evil: 1', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a value that opens a new rule block', () => {
    expect(() => assertSafeCssValue('red} .x{color:blue', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a value that closes an HTML tag to break out of a style block', () => {
    expect(() => assertSafeCssValue('red</style><script>', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a value containing a url() reference', () => {
    expect(() => assertSafeCssValue('url(javascript:alert(1))', 'test')).toThrow(
      /unsafe CSS value/
    );
  });

  it('rejects a value containing an @ rule marker', () => {
    expect(() => assertSafeCssValue('red @import evil.css', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a value that closes a CSS comment early', () => {
    expect(() => assertSafeCssValue('red */ .x{color:blue', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a value containing an expression() call', () => {
    expect(() => assertSafeCssValue('expression(alert(1))', 'test')).toThrow(/unsafe CSS value/);
  });

  it('accepts a raw number (fontWeight/opacity primitives use unit "")', () => {
    expect(assertSafeCssValue(400, 'test')).toBe(400);
  });

  it('rejects a value that opens a CSS comment (the reverse of the existing "*/" check)', () => {
    expect(() => assertSafeCssValue('red /* comment', 'test')).toThrow(/unsafe CSS value/);
  });

  it('rejects a CSS-escaped "url(" that would otherwise slip past the plain-text check', () => {
    // "\75" is the CSS escape for "u", so "\75rl(" spells "url(" once decoded.
    expect(() => assertSafeCssValue('\\75rl(javascript:alert(1))', 'test')).toThrow(
      /unsafe CSS value/
    );
  });
});

describe('extractStringValue (fontFamily quoting)', () => {
  it('escapes embedded double quotes', () => {
    const token = { $type: 'string', $value: 'Font "Name"' };
    expect(extractStringValue(token)).toBe('"Font \\"Name\\""');
  });

  it('escapes a trailing backslash instead of letting it escape the closing quote', () => {
    // Escaping quotes before backslashes would turn `Font\` into `"Font\"`,
    // where the trailing backslash escapes the CSS closing quote instead of
    // itself, leaving the generated declaration unterminated.
    const token = { $type: 'string', $value: 'Font\\' };
    const result = extractStringValue(token);
    expect(result).toBe('"Font\\\\"');
    expect(result.endsWith('\\\\"')).toBe(true);
  });
});

describe('writeAndVerify (write + readback)', () => {
  it('writes into an injected temp directory and confirms the bytes on disk', () => {
    const dir = mkdtempSync(join(tmpdir(), 'transform-tokens-test-'));
    const file = join(dir, 'tokens.css');
    try {
      writeAndVerify(file, ':root { --x: 1; }');
      expect(readFileSync(file, 'utf8')).toBe(':root { --x: 1; }');
    } finally {
      rmSync(dir, { recursive: true, force: true });
    }
  });

  it('exits with an error when the readback does not match what was written', () => {
    // Simulates a full disk / permissions race / symlinked output path: the
    // write "succeeds" but the persisted bytes differ from what was sent.
    // Stubs the readback via writeAndVerify's injectable readImpl parameter
    // instead of mocking the "fs" module, so the real writeFileSync still
    // runs and only the readback is forced to mismatch.
    const dir = mkdtempSync(join(tmpdir(), 'transform-tokens-test-'));
    const file = join(dir, 'tokens.css');
    const fakeRead = vi.fn(() => 'different content on disk');
    const exitSpy = vi.spyOn(process, 'exit').mockImplementation(() => undefined);
    const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => undefined);
    try {
      writeAndVerify(file, ':root { --x: 1; }', fakeRead);
      expect(fakeRead).toHaveBeenCalledWith(file, 'utf8');
      expect(exitSpy).toHaveBeenCalledWith(1);
      expect(errorSpy).toHaveBeenCalledWith(expect.stringContaining('write verification failed'));
    } finally {
      exitSpy.mockRestore();
      errorSpy.mockRestore();
      rmSync(dir, { recursive: true, force: true });
    }
  });
});
