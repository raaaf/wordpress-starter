import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * A ratchet against design values that no token defines.
 *
 * Three times a colour or a shadow has been invented in a file that only
 * consumes tokens, and stayed invisible for years: the component shadows in
 * this generator, the enter and exit easing curves in app.css, the typography
 * scale in `buildTypographyTokens`. Each surfaced only when something outside
 * CSS, Figma, needed the value and found nothing to read.
 *
 * So this counts rather than forbids. Fixing the backlog in one night was not
 * on the table, and an allowlist of thirty entries forbids nothing. The
 * ceilings below may fall, never rise: a new literal fails the build, and
 * every value moved into the token export tightens the bound for the next one.
 */

const root = resolve(import.meta.dirname, '..');
const read = (path) => readFileSync(resolve(root, path), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');

/** `#abc`, `#aabbcc`, `rgb(...)`, `rgba(...)`. A `var()` is not a literal. */
const COLOR = /#[0-9a-fA-F]{3,8}\b|\brgba?\([^)]*\)/g;

const CEILINGS = [
  {
    file: 'resources/css/app.css',
    max: 29,
    debt: [
      'the four sheens and the noise texture, which the design system defines and this file redefines',
      'six page gradients carrying the brand orange at low alpha',
      '--bg-brand-subtle, a colour role written as a hex here',
      'eleven white and black alphas for text and fills on a brand surface',
    ],
  },
  {
    file: 'scripts/transform-tokens.js',
    max: 1,
    debt: ['one fallback alpha in a focus ring'],
  },
];

describe('design values live in the token export', () => {
  for (const { file, max, debt } of CEILINGS) {
    it(`${file} holds at most ${max} colour literals`, () => {
      const found = read(file).match(COLOR) ?? [];
      // Growing means a design value was invented here instead of in the
      // design system. Shrinking means one moved where it belongs: lower the
      // ceiling in the same commit, so the next one cannot slip back in.
      expect(
        found.length,
        `Bekannte Schuld:\n- ${debt.join('\n- ')}\n\nGefunden:\n${found.join('\n')}`
      ).toBeLessThanOrEqual(max);
    });
  }
});
