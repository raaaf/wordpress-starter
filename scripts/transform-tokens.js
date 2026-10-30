/**
 * Figma Tokens to CSS Custom Properties Transformer
 *
 * Converts native Figma Variables export format to CSS custom properties
 * for use with TailwindCSS v4's @theme directive.
 *
 * Usage: node scripts/transform-tokens.js
 *
 * Input:  config/design-tokens/{primitives,light,dark}.tokens.json
 * Output: resources/css/tokens.css (auto-generated)
 */

import { readFileSync, writeFileSync, realpathSync } from 'fs';
import { dirname, join, resolve, relative, isAbsolute } from 'path';
import { fileURLToPath } from 'url';
import {
  buildTokenCss,
  fluidClamp,
  fluidLineHeight,
  FLUID_SIZES,
  assertFluidMaxMatchesExport,
  HEADING_LINE_HEIGHTS,
  VIEWPORT_MIN,
  VIEWPORT_MAX,
  ROOT_PX,
  flattenTokens,
  extractColorValue,
  extractStringValue,
  assertSafeVarNameSegment,
  assertSafeCssValue,
  buildTypographyTokens,
} from './tokens-core.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');

// Input/output paths
const TOKENS_DIR = join(ROOT, 'config/design-tokens');

/**
 * Resolve the directory tokens.css/tokens-editor.css are written to.
 *
 * Defaults to resources/css and is verified to stay inside it, since this
 * file is auto-generated and DELETES/overwrites whatever is at OUTPUT_FILE.
 * `TOKENS_OUT_DIR` (env) or an explicit `overrideDir` argument opt out of
 * that containment check deliberately, so tests can point this at a temp
 * directory instead of the real resources/css.
 */
function resolveOutputDir(overrideDir = process.env.TOKENS_OUT_DIR) {
  if (overrideDir) {
    const resolvedOverride = resolve(overrideDir);
    console.log(`transform-tokens: TOKENS_OUT_DIR set, writing to "${resolvedOverride}"`);
    return resolvedOverride;
  }

  // No override: the write target is resources/css, but resources/css could
  // itself be a symlink pointing outside the project. `resolve()` does not
  // follow symlinks, so comparing the un-resolved path against itself would
  // always pass trivially. Resolve the real (symlink-followed) path and
  // check THAT for containment under the real project root.
  const resourcesCss = resolve(ROOT, 'resources/css');
  const realRoot = realpathSync(ROOT);
  const realResourcesCss = realpathSync(resourcesCss);
  const rel = relative(join(realRoot, 'resources', 'css'), realResourcesCss);
  if (rel.startsWith('..') || isAbsolute(rel)) {
    throw new Error(
      `transform-tokens: resolved output dir "${realResourcesCss}" is not resources/css`
    );
  }
  return resourcesCss;
}

const OUTPUT_DIR = resolveOutputDir();
const OUTPUT_FILE = join(OUTPUT_DIR, 'tokens.css');
const OUTPUT_EDITOR_FILE = join(OUTPUT_DIR, 'tokens-editor.css');

/**
 * Write generated CSS and read it back to confirm the file on disk actually
 * matches what was generated (a full disk, a permissions error swallowed by
 * a race, or a symlinked output path can all make writeFileSync succeed while
 * the persisted bytes differ).
 *
 * `readImpl` defaults to the real `readFileSync` and exists only so a test can
 * inject a stub that returns mismatched content, to exercise the mismatch
 * branch without needing a real full-disk/permissions failure.
 */
function writeAndVerify(path, content, readImpl = readFileSync) {
  writeFileSync(path, content, 'utf8');
  const readBack = readImpl(path, 'utf8');
  if (readBack !== content) {
    console.error(
      `transform-tokens: write verification failed for ${path} (content mismatch after write)`
    );
    process.exit(1);
  }
}

/**
 * Main transformation function
 */
function transform() {
  console.log('Reading Figma token files...');

  // Read token files
  const primitives = JSON.parse(readFileSync(join(TOKENS_DIR, 'primitives.tokens.json'), 'utf8'));
  const lightTokens = JSON.parse(readFileSync(join(TOKENS_DIR, 'light.tokens.json'), 'utf8'));
  const darkTokens = JSON.parse(readFileSync(join(TOKENS_DIR, 'dark.tokens.json'), 'utf8'));

  const { css, editorCss, warnings, criticalWarnings, stats } = buildTokenCss(
    primitives,
    lightTokens,
    darkTokens,
    { log: (msg) => console.log(msg) }
  );

  // Write output - Main tokens file (with dark mode)
  writeAndVerify(OUTPUT_FILE, css);
  writeAndVerify(OUTPUT_EDITOR_FILE, editorCss);
  console.log(`Editor tokens written to: ${OUTPUT_EDITOR_FILE}`);

  console.log('\nToken Statistics:');
  console.log(`  Primitive colors: ${stats.primitiveColors}`);
  console.log(`  Spacing:          ${stats.spacing}`);
  console.log(`  Border radius:    ${stats.radius}`);
  console.log(`  Font sizes:       ${stats.fontSize}`);
  console.log(`  Font weights:     ${stats.fontWeight}`);
  console.log(`  Font families:    ${stats.fontFamily}`);
  console.log(`  Border widths:    ${stats.borderWidth}`);
  console.log(`  Opacity:          ${stats.opacity}`);
  console.log(`  Sizing:           ${stats.sizing}`);
  console.log(`  Gradients:        ${stats.gradient}`);
  console.log(`  Light semantic:   ${stats.lightSemanticColors}`);
  console.log(`  Dark semantic:    ${stats.darkSemanticColors}`);
  console.log(`\nOutput written to: ${OUTPUT_FILE}`);

  // Exit with error code only for critical warnings (missing required tokens).
  // Cosmetic warnings (unquoted font-family, px font-size, potentially invalid
  // colors, Tailwind opacity modifiers) stay non-fatal: the first two are
  // already auto-fixed in the written CSS, the latter two are heuristic and
  // routinely non-fatal in normal use.
  if (warnings.length > 0) {
    console.log('\n⚠️  Token generation completed with warnings. Please review above.');
  }
  if (criticalWarnings.length > 0) {
    console.error(`\n❌ ${criticalWarnings.length} required token(s) missing from generated CSS.`);
    process.exitCode = 1;
  }
}

// Run transformation only when invoked directly, not when imported by tests.
// Compared via realpath on both sides so a symlinked invocation still
// resolves to the same file instead of bypassing the guard.
function isDirectInvocation() {
  if (!process.argv[1]) return false;
  try {
    return realpathSync(fileURLToPath(import.meta.url)) === realpathSync(process.argv[1]);
  } catch {
    return false;
  }
}

if (isDirectInvocation()) {
  transform();
}

export {
  fluidClamp,
  fluidLineHeight,
  FLUID_SIZES,
  assertFluidMaxMatchesExport,
  HEADING_LINE_HEIGHTS,
  VIEWPORT_MIN,
  VIEWPORT_MAX,
  ROOT_PX,
  flattenTokens,
  extractColorValue,
  extractStringValue,
  assertSafeVarNameSegment,
  assertSafeCssValue,
  resolveOutputDir,
  writeAndVerify,
  buildTypographyTokens,
};
