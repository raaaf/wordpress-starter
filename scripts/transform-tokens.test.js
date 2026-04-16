import { describe, expect, it } from 'vitest';
import {
  fluidClamp,
  fluidLineHeight,
  FLUID_SIZES,
  VIEWPORT_MIN,
  VIEWPORT_MAX,
  ROOT_PX,
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
  it('returns a plain number when mobile equals desktop', () => {
    expect(fluidLineHeight(1.4, 1.4)).toBe('1.4');
  });

  it('produces a clamp() expression for fluid line-heights', () => {
    const result = fluidLineHeight(1.5, 1.1);
    expect(result).toMatch(/^clamp\(/);
  });

  it('evaluates to mobile value at VIEWPORT_MIN', () => {
    const result = fluidLineHeight(1.5, 1.1);
    const lh = evaluate(result, VIEWPORT_MIN);
    expect(lh).toBeCloseTo(1.5, 3);
  });

  it('evaluates to desktop value at VIEWPORT_MAX', () => {
    const result = fluidLineHeight(1.5, 1.1);
    const lh = evaluate(result, VIEWPORT_MAX);
    expect(lh).toBeCloseTo(1.1, 3);
  });

  it('produces clean "- Xvw" syntax when desktop < mobile', () => {
    const result = fluidLineHeight(1.5, 1.1);
    expect(result).toMatch(/- \d/);
    expect(result).not.toContain('+ -');
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
