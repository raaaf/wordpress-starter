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

import { readFileSync, writeFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');

// Input/output paths
const TOKENS_DIR = join(ROOT, 'config/design-tokens');
const OUTPUT_FILE = join(ROOT, 'resources/css/tokens.css');
const OUTPUT_EDITOR_FILE = join(ROOT, 'resources/css/tokens-editor.css');

// Fluid typography configuration
// Mobile min values (at VIEWPORT_MIN). Max values come from Figma primitives.fontSize.
// xs/sm/base stay non-fluid (min === max) for reading stability — see transform-tokens.test.js.

// lg and above scale meaningfully for headline breathing room.
const FLUID_SIZES = {
  xs: { min: 12, max: 12 },
  sm: { min: 14, max: 14 },
  base: { min: 16, max: 16 },
  lg: { min: 17, max: 18 },
  xl: { min: 18, max: 20 },
  '2xl': { min: 20, max: 24 },
  '3xl': { min: 24, max: 30 },
  '4xl': { min: 28, max: 36 },
  '5xl': { min: 32, max: 48 },
  '6xl': { min: 38, max: 60 },
};

const VIEWPORT_MIN = 320;
const VIEWPORT_MAX = 1920;
const ROOT_PX = 16;

/**
 * Generate a fluid clamp() value in rem that scales linearly between
 * VIEWPORT_MIN and VIEWPORT_MAX viewport widths.
 * Returns a static rem string when minPx === maxPx (no scaling needed).
 */
/**
 * Format a number with up to 4 decimal places, trimming trailing zeros.
 * Keeps generated CSS readable: `2.375rem` instead of `2.3750rem`.
 */
function fmt(n) {
  return n.toFixed(4).replace(/\.?0+$/, '');
}

function fluidClamp(minPx, maxPx, minVw = VIEWPORT_MIN, maxVw = VIEWPORT_MAX, rootPx = ROOT_PX) {
  if (minPx === maxPx) {
    return `${fmt(minPx / rootPx)}rem`;
  }
  const minRem = minPx / rootPx;
  const maxRem = maxPx / rootPx;
  const vwCoef = (100 * (maxPx - minPx)) / (maxVw - minVw);
  const remIntercept = (minPx - (vwCoef / 100) * minVw) / rootPx;
  return `clamp(${fmt(minRem)}rem, calc(${fmt(remIntercept)}rem + ${fmt(vwCoef)}vw), ${fmt(maxRem)}rem)`;
}

/**
 * Generate a fluid unitless line-height that shrinks as viewport grows.
 * mobileLh is used at minVw (typically larger for breathing room on small screens),
 * desktopLh is used at maxVw (typically tighter for visual density).
 */
function fluidLineHeight(mobileLh, desktopLh, minVw = VIEWPORT_MIN, maxVw = VIEWPORT_MAX) {
  if (mobileLh === desktopLh) return String(mobileLh);
  const vwCoef = (100 * (desktopLh - mobileLh)) / (maxVw - minVw);
  const intercept = mobileLh - (vwCoef / 100) * minVw;
  const clampMin = Math.min(mobileLh, desktopLh);
  const clampMax = Math.max(mobileLh, desktopLh);
  const op = vwCoef < 0 ? '-' : '+';
  return `clamp(${clampMin}, calc(${fmt(intercept)} ${op} ${fmt(Math.abs(vwCoef))}vw), ${clampMax})`;
}

/**
 * Extract CSS var() reference from Figma token alias data.
 * Converts aliasData.targetVariableName (e.g. "color/accent/500")
 * to var(--color-accent-500). Falls back to resolved hex value.
 */
function extractColorAsReference(token) {
  if (!token || token.$type !== 'color') return null;

  const aliasData = token.$extensions?.['com.figma.aliasData'];
  if (aliasData?.targetVariableName) {
    const varName = aliasData.targetVariableName.replace(/\//g, '-');
    return `var(--${varName})`;
  }

  // Fallback to resolved hex value if no alias
  return extractColorValue(token);
}

/**
 * Extract hex color value from Figma token $value object
 * Handles both simple values and complex color objects with alpha
 */
function extractColorValue(token) {
  if (!token || token.$type !== 'color') return null;

  const value = token.$value;

  // Handle alias references like "{color.accent.500}"
  if (typeof value === 'string' && value.startsWith('{')) {
    return null; // Skip aliases, we use resolved values
  }

  // Handle complex color object with components
  if (typeof value === 'object' && value.hex) {
    const hex = value.hex;
    const alpha = value.alpha ?? 1;

    if (alpha < 1) {
      // Convert hex + alpha to rgba
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha.toFixed(2)})`;
    }

    return hex;
  }

  return null;
}

/**
 * Extract numeric value (spacing, radius, fontSize, etc.)
 */
function extractNumericValue(token, unit = 'px') {
  if (!token) return null;

  const value = token.$value;

  if (typeof value === 'number') {
    return unit ? `${value}${unit}` : value;
  }

  return null;
}

/**
 * Extract string value (fontFamily)
 * Wraps font family names in quotes for CSS compatibility
 */
function extractStringValue(token) {
  if (!token || token.$type !== 'string') return null;
  // Wrap font family names in quotes for CSS
  return `"${token.$value}"`;
}

/**
 * Extract color value or resolve alias references to var().
 * Handles primitive gradient tokens where $value is "{color.accent.500}".
 */
function extractColorOrAlias(token) {
  if (!token || token.$type !== 'color') return null;

  const value = token.$value;

  // Handle alias references like "{color.accent.500}"
  if (typeof value === 'string' && value.startsWith('{') && value.endsWith('}')) {
    const path = value.slice(1, -1); // Remove braces
    const varName = path.replace(/\./g, '-'); // dots to dashes
    return `var(--${varName})`;
  }

  // Fall back to regular color extraction
  return extractColorValue(token);
}

/**
 * Recursively process nested token structure
 * Returns flat object with CSS variable names as keys
 */
function flattenTokens(obj, prefix = '', processor = extractColorValue) {
  const result = {};

  for (const [key, value] of Object.entries(obj)) {
    // Skip metadata keys
    if (key.startsWith('$')) continue;

    // Handle $root special case (Figma uses this for base values in nested objects)
    if (key === '$root') {
      const processed = processor(value);
      if (processed !== null) {
        result[prefix.replace(/-$/, '')] = processed;
      }
      continue;
    }

    const newPrefix = prefix ? `${prefix}-${key}` : key;

    if (value && typeof value === 'object' && !value.$type) {
      // Nested object, recurse
      Object.assign(result, flattenTokens(value, newPrefix, processor));
    } else if (value && value.$type) {
      // Token node
      const processed = processor(value);
      if (processed !== null) {
        result[newPrefix] = processed;
      }
    }
  }

  return result;
}

/**
 * Convert token name to CSS variable name
 * e.g., "gray-50" -> "--color-gray-50"
 */
function toCssVarName(name, prefix = '') {
  const varName = name.toLowerCase().replace(/\s+/g, '-');
  return prefix ? `--${prefix}-${varName}` : `--${varName}`;
}

/**
 * Generate CSS from flattened tokens
 */
function generateCss(tokens, prefix = '') {
  return Object.entries(tokens)
    .map(([name, value]) => `  ${toCssVarName(name, prefix)}: ${value};`)
    .join('\n');
}

/**
 * Generate CSS with !important from flattened tokens
 * Used for editor styles to override dark mode rules
 */
function generateCssImportant(tokens, prefix = '') {
  return Object.entries(tokens)
    .map(([name, value]) => `    ${toCssVarName(name, prefix)}: ${value} !important;`)
    .join('\n');
}

/**
 * Standard typography tokens (not from Figma, but needed for consistency).
 * Large headlines get fluid line-heights to preserve breathing room on mobile
 * where long German compound words need more vertical space.
 */
function buildTypographyTokens() {
  const displayLh = fluidLineHeight(1.5, 1.1);
  const h1Lh = fluidLineHeight(1.4, 1.2);
  const h2Lh = fluidLineHeight(1.35, 1.25);
  const h3Lh = fluidLineHeight(1.4, 1.3);

  return `
  /* ============================================
     TYPOGRAPHY COMPOSITE TOKENS
     Standard typography styles using primitives
     ============================================ */

  /* Display - 6xl / Bold (for hero headlines) */
  --typography-display-size: var(--font-size-6xl);
  --typography-display-weight: var(--font-weight-bold);
  --typography-display-line-height: ${displayLh};
  --typography-display-letter-spacing: -0.02em;

  /* Heading 1 - 4xl / Bold */
  --typography-h1-size: var(--font-size-4xl);
  --typography-h1-weight: var(--font-weight-bold);
  --typography-h1-line-height: ${h1Lh};
  --typography-h1-letter-spacing: -0.01em;

  /* Heading 2 - 3xl / Semibold */
  --typography-h2-size: var(--font-size-3xl);
  --typography-h2-weight: var(--font-weight-semibold);
  --typography-h2-line-height: ${h2Lh};
  --typography-h2-letter-spacing: -0.01em;

  /* Heading 3 - 2xl / Semibold */
  --typography-h3-size: var(--font-size-2xl);
  --typography-h3-weight: var(--font-weight-semibold);
  --typography-h3-line-height: ${h3Lh};
  --typography-h3-letter-spacing: 0;

  /* Heading 4 - xl / Semibold */
  --typography-h4-size: var(--font-size-xl);
  --typography-h4-weight: var(--font-weight-semibold);
  --typography-h4-line-height: 1.4;
  --typography-h4-letter-spacing: 0;

  /* Heading 5 - lg / Medium */
  --typography-h5-size: var(--font-size-lg);
  --typography-h5-weight: var(--font-weight-medium);
  --typography-h5-line-height: 1.4;
  --typography-h5-letter-spacing: 0;

  /* Body Large - lg / Regular */
  --typography-body-large-size: var(--font-size-lg);
  --typography-body-large-weight: var(--font-weight-regular);
  --typography-body-large-line-height: 1.6;
  --typography-body-large-letter-spacing: 0;

  /* Body - base / Regular */
  --typography-body-size: var(--font-size-base);
  --typography-body-weight: var(--font-weight-regular);
  --typography-body-line-height: 1.5;
  --typography-body-letter-spacing: 0;

  /* Body Small - sm / Regular */
  --typography-body-small-size: var(--font-size-sm);
  --typography-body-small-weight: var(--font-weight-regular);
  --typography-body-small-line-height: 1.5;
  --typography-body-small-letter-spacing: 0;

  /* Caption - xs / Regular */
  --typography-caption-size: var(--font-size-xs);
  --typography-caption-weight: var(--font-weight-regular);
  --typography-caption-line-height: 1.4;
  --typography-caption-letter-spacing: 0;

  /* Overline - xs / Semibold / Uppercase */
  --typography-overline-size: var(--font-size-xs);
  --typography-overline-weight: var(--font-weight-semibold);
  --typography-overline-line-height: 1.4;
  --typography-overline-letter-spacing: 0.1em;
  --typography-overline-transform: uppercase;

  /* Code - sm / Regular */
  --typography-code-size: var(--font-size-sm);
  --typography-code-weight: var(--font-weight-regular);
  --typography-code-line-height: 1.5;
  --typography-code-letter-spacing: 0;
`;
}

/**
 * Standard component tokens (shadows, button sizes, etc.)
 * Can be overridden by Figma exports if needed
 */
const COMPONENT_TOKENS = `
  /* ============================================
     COMPONENT TOKENS
     Shadows and component-specific values
     ============================================ */

  /* Shadows */
  /* Shadow color: gray-900 (#171717 = 23,23,23) instead of pure black for warmer, less harsh shadows */
  --shadow-button: 0px 1px 3px 0px rgba(23, 23, 23, 0.1), 0px 1px 2px 0px rgba(23, 23, 23, 0.05);
  --shadow-button-hover: 0px 4px 6px -1px rgba(23, 23, 23, 0.1), 0px 2px 4px -2px rgba(23, 23, 23, 0.1);
  --shadow-inner: inset 0px 2px 4px 0px rgba(23, 23, 23, 0.06);
  --shadow-focus-ring: 0px 0px 0px 2px var(--bg-primary), 0px 0px 0px 4px var(--color-accent-alpha-50);
  --shadow-focus-ring-ghost: 0px 0px 0px 2px var(--color-accent-alpha-50);
  --shadow-focus-ring-error: 0px 0px 0px 2px var(--bg-primary), 0px 0px 0px 4px var(--color-error-alpha-50, rgba(220, 38, 38, 0.5));
  --shadow-input: 0px 1px 2px 0px rgba(23, 23, 23, 0.05);
  --shadow-input-hover: 0px 1px 3px 0px rgba(23, 23, 23, 0.1), 0px 1px 2px -1px rgba(23, 23, 23, 0.1);
  --shadow-card: 0px 1px 2px 0px rgba(23, 23, 23, 0.05);
  --shadow-card-hover: 0px 10px 15px -3px rgba(23, 23, 23, 0.1), 0px 4px 6px -4px rgba(23, 23, 23, 0.1);
  --shadow-dropdown: 0px 10px 15px -3px rgba(23, 23, 23, 0.1), 0px 4px 6px -4px rgba(23, 23, 23, 0.1);
  --shadow-modal: 0px 25px 50px -12px rgba(23, 23, 23, 0.25);

  /* Button Sizes */
  --button-sm-padding-x: var(--spacing-3);
  --button-sm-padding-y: 4px;
  --button-sm-radius: var(--radius-sm);
  --button-sm-min-height: var(--spacing-8);
  --button-sm-gap: var(--spacing-1-5);
  --button-md-padding-x: var(--spacing-5);
  --button-md-padding-y: var(--spacing-2-5);
  --button-md-radius: var(--radius-md);
  --button-md-min-height: var(--spacing-10);
  --button-md-gap: var(--spacing-2);
  --button-lg-padding-x: var(--spacing-6);
  --button-lg-padding-y: var(--spacing-3);
  --button-lg-radius: var(--radius-lg);
  --button-lg-min-height: var(--spacing-12);
  --button-lg-gap: var(--spacing-2-5);

  /* Input Sizes */
  --input-sm-padding-x: var(--spacing-2-5);
  --input-sm-padding-y: var(--spacing-1-5);
  --input-sm-radius: var(--radius-sm);
  --input-md-padding-x: var(--spacing-3);
  --input-md-padding-y: var(--spacing-2-5);
  --input-md-radius: var(--radius-md);
  --input-lg-padding-x: var(--spacing-4);
  --input-lg-padding-y: var(--spacing-3);
  --input-lg-radius: var(--radius-lg);

  /* Badge Sizes */
  --badge-sm-padding-x: var(--spacing-1-5);
  --badge-sm-padding-y: var(--spacing-0-5);
  --badge-sm-radius: var(--radius-sm);
  --badge-sm-gap: var(--spacing-1);
  --badge-md-padding-x: var(--spacing-2-5);
  --badge-md-padding-y: var(--spacing-1);
  --badge-md-radius: var(--radius-default);
  --badge-md-gap: var(--spacing-1-5);
  --badge-lg-padding-x: var(--spacing-3);
  --badge-lg-padding-y: var(--spacing-1-5);
  --badge-lg-radius: var(--radius-md);
  --badge-lg-gap: var(--spacing-2);

  /* Card Tokens */
  --card-bg: var(--bg-primary);
  --card-border: var(--border-default);
  --card-radius: var(--radius-lg);
  --card-padding: var(--spacing-5);
  --card-gap: var(--spacing-4);
  --card-media-radius: var(--radius-md);
  --card-footer-gap: var(--spacing-3);
  --card-footer-padding-top: var(--spacing-4);
`;

/**
 * Validation and auto-fix for generated CSS
 */
function validateAndFix(css) {
  const warnings = [];
  const fixes = [];
  let fixedCss = css;

  // 1. Check for unquoted font-family values (multi-word fonts need quotes)
  const fontFamilyRegex = /--font-family-\w+:\s*([^;]+);/g;
  let match;
  while ((match = fontFamilyRegex.exec(css)) !== null) {
    const value = match[1].trim();
    // Font names with spaces that aren't quoted
    if (/[A-Z][a-z]+[A-Z]/.test(value) && !value.startsWith('"') && !value.startsWith("'")) {
      warnings.push(`Unquoted font family: ${value}`);
      const quotedValue = `"${value}"`;
      fixedCss = fixedCss.replace(match[0], match[0].replace(value, quotedValue));
      fixes.push(`Auto-fixed: Quoted font family "${value}"`);
    }
  }

  // 2. Check for invalid color values (exclude border-width which is not a color)
  const colorRegex =
    /--(?:color|bg|text|border-(?!width)[\w-]*|border-default|icon)-[\w-]*:\s*([^;]+);/g;
  while ((match = colorRegex.exec(css)) !== null) {
    const value = match[1].trim();
    // Skip CSS variable references
    if (value.startsWith('var(')) continue;
    // Skip valid hex colors
    if (/^#[0-9A-Fa-f]{3,8}$/.test(value)) continue;
    // Skip valid rgba/rgb
    if (/^rgba?\([^)]+\)$/.test(value)) continue;
    // Skip valid named colors (for status colors)
    if (/^(transparent|inherit|currentColor)$/.test(value)) continue;

    // If none of the above, it might be invalid
    if (!/^(var\(|#|rgba?|transparent|inherit)/.test(value)) {
      warnings.push(`Potentially invalid color value: ${match[0].substring(0, 50)}...`);
    }
  }

  // 3. Check for px values that should be rem (font-size).
  // Negative lookahead prevents matching inside clamp()/calc() expressions from
  // the fluid typography pipeline. Offset-based replace avoids substring collisions
  // when the same --font-size-* line would appear twice anywhere in the CSS.
  const fontSizeRegex = /--font-size-(\w+):\s*(?!clamp)(\d+)px;/g;
  let fontSizeDelta = 0;
  while ((match = fontSizeRegex.exec(css)) !== null) {
    const pxValue = parseInt(match[2]);
    const remValue = (pxValue / 16).toFixed(4).replace(/\.?0+$/, '');
    const replacement = `--font-size-${match[1]}: ${remValue}rem;`;
    warnings.push(`Font size in px: ${match[2]}px (should be ${remValue}rem for accessibility)`);
    const start = match.index + fontSizeDelta;
    fixedCss = fixedCss.slice(0, start) + replacement + fixedCss.slice(start + match[0].length);
    fontSizeDelta += replacement.length - match[0].length;
    fixes.push(`Auto-fixed: Converted ${match[2]}px to ${remValue}rem`);
  }

  // 4. Check for missing required tokens
  const requiredTokens = [
    '--font-family-headline',
    '--font-family-body',
    '--color-white',
    '--color-gray-50',
    '--color-gray-900',
    '--bg-primary',
    '--text-primary',
    '--border-default',
  ];

  for (const token of requiredTokens) {
    if (!css.includes(token + ':')) {
      warnings.push(`Missing required token: ${token}`);
    }
  }

  // 5. Check for Tailwind opacity modifier syntax that won't work with CSS variables
  const opacityModifierRegex = /[\w-]+\/\d+/g;
  while ((match = opacityModifierRegex.exec(css)) !== null) {
    warnings.push(`Tailwind opacity modifier in CSS won't work: ${match[0]}`);
  }

  return { css: fixedCss, warnings, fixes };
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

  console.log('Processing primitives...');

  // Process primitives - colors
  // NOTE on gray / primary / secondary ramps:
  // All three ramps are neutral-gray-toned and export near-identical hex values in the
  // starter theme. This is INTENTIONAL: primary/secondary are Figma variable aliases
  // providing semantic re-theming hooks for client forks. A client theme swaps
  // --color-primary-* or --color-secondary-* to brand colors without touching gray.
  // app.css references --color-primary-* explicitly, confirming the ramps are in active use.
  // Do NOT deduplicate or remove any of the three ramps.
  const primitiveColors = flattenTokens(primitives.color || {}, '', extractColorValue);

  // Process primitives - spacing (convert to rem)
  const primitiveSpacing = flattenTokens(primitives.spacing || {}, '', (token) =>
    extractNumericValue(token, 'px')
  );

  // Process primitives - radius
  const primitiveRadius = flattenTokens(primitives.radius || {}, '', (token) =>
    extractNumericValue(token, 'px')
  );

  // Process primitives - fontSize as fluid clamp() values between VIEWPORT_MIN and VIEWPORT_MAX.
  // Figma provides the current (desktop) px value. FLUID_SIZES provides both:
  //   - min: mobile floor (must not shrink below this at small viewports)
  //   - max: desktop ceiling (config.max takes precedence; falls back to Figma px so that
  //          for most keys config.max === Figma px, avoiding any unintended drift)
  // xs/sm have min===current and max===current+1 (gentle 1px clamp without shrinking).
  // Unknown keys (not in FLUID_SIZES) fall back to a static rem value.
  const primitiveFontSize = {};
  for (const [key, token] of Object.entries(primitives.fontSize || {})) {
    if (key.startsWith('$')) continue;
    const px = token?.$value;
    if (typeof px !== 'number') continue;
    const config = FLUID_SIZES[key];
    primitiveFontSize[key] = config
      ? fluidClamp(config.min, config.max ?? px)
      : `${(px / ROOT_PX).toFixed(4).replace(/\.?0+$/, '')}rem`;
  }

  // Process primitives - fontWeight
  const primitiveFontWeight = flattenTokens(primitives.fontWeight || {}, '', (token) =>
    extractNumericValue(token, '')
  );

  // Process primitives - fontFamily
  const primitiveFontFamily = {};
  if (primitives.fontFamily) {
    for (const [key, value] of Object.entries(primitives.fontFamily)) {
      if (key.startsWith('$')) continue;
      const fontValue = extractStringValue(value);
      if (fontValue) {
        primitiveFontFamily[key] = fontValue;
      }
    }
  }

  // Process primitives - borderWidth
  const primitiveBorderWidth = flattenTokens(primitives.borderWidth || {}, '', (token) =>
    extractNumericValue(token, 'px')
  );

  // Process primitives - opacity (values 0-100, stored as-is)
  const primitiveOpacity = flattenTokens(primitives.opacity || {}, '', (token) =>
    extractNumericValue(token, '')
  );

  // Process primitives - sizing (icon sizes)
  const primitiveSizing = flattenTokens(primitives.sizing || {}, '', (token) =>
    extractNumericValue(token, 'px')
  );

  // Process primitives - gradient (alias references to color tokens)
  const primitiveGradient = flattenTokens(primitives.gradient || {}, '', extractColorOrAlias);

  console.log('Processing semantic tokens (light mode)...');

  // Process semantic tokens - light mode (use var() references to primitives)
  const lightBg = flattenTokens(lightTokens.bg || {}, '', extractColorAsReference);
  const lightText = flattenTokens(lightTokens.text || {}, '', extractColorAsReference);
  const lightBorder = flattenTokens(lightTokens.border || {}, '', extractColorAsReference);
  const lightIcon = flattenTokens(lightTokens.icon || {}, '', extractColorAsReference);

  console.log('Processing semantic tokens (dark mode)...');

  // Process semantic tokens - dark mode (use var() references to primitives)
  const darkBg = flattenTokens(darkTokens.bg || {}, '', extractColorAsReference);
  const darkText = flattenTokens(darkTokens.text || {}, '', extractColorAsReference);
  const darkBorder = flattenTokens(darkTokens.border || {}, '', extractColorAsReference);
  const darkIcon = flattenTokens(darkTokens.icon || {}, '', extractColorAsReference);

  console.log('Generating CSS...');

  // Generate CSS output
  let css = `/**
 * Design Tokens - Auto-generated from Figma
 *
 * DO NOT EDIT THIS FILE DIRECTLY!
 * Run: node scripts/transform-tokens.js
 *
 * Source: config/design-tokens/*.tokens.json
 * Generated: ${new Date().toISOString()}
 */

/* ============================================
   PRIMITIVE TOKENS
   Base values that semantic tokens reference
   ============================================ */

:root {
  /* Colors - Primitives */
${generateCss(primitiveColors, 'color')}

  /* Spacing */
${generateCss(primitiveSpacing, 'spacing')}

  /* Border Radius */
${generateCss(primitiveRadius, 'radius')}

  /* Font Size */
${generateCss(primitiveFontSize, 'font-size')}

  /* Font Weight */
${generateCss(primitiveFontWeight, 'font-weight')}

  /* Font Family */
${generateCss(primitiveFontFamily, 'font-family')}

  /* Border Width */
${generateCss(primitiveBorderWidth, 'border-width')}

  /* Opacity */
${generateCss(primitiveOpacity, 'opacity')}

  /* Sizing */
${generateCss(primitiveSizing, 'sizing')}

  /* Gradients */
${generateCss(primitiveGradient, 'gradient')}
${buildTypographyTokens()}
${COMPONENT_TOKENS}
}

/* ============================================
   SEMANTIC TOKENS - Light Mode (Default)
   Use these for theming components
   ============================================ */

:root,
[data-theme="light"] {
  /* Background */
${generateCss(lightBg, 'bg')}

  /* Text */
${generateCss(lightText, 'text')}

  /* Border */
${generateCss(lightBorder, 'border')}

  /* Icon */
${generateCss(lightIcon, 'icon')}
}

/* ============================================
   SEMANTIC TOKENS - Dark Mode
   Activated via data-theme or prefers-color-scheme
   ============================================ */

[data-theme="dark"] {
  /* Background */
${generateCss(darkBg, 'bg')}

  /* Text */
${generateCss(darkText, 'text')}

  /* Border */
${generateCss(darkBorder, 'border')}

  /* Icon */
${generateCss(darkIcon, 'icon')}
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    /* Background */
${generateCss(darkBg, 'bg')}

    /* Text */
${generateCss(darkText, 'text')}

    /* Border */
${generateCss(darkBorder, 'border')}

    /* Icon */
${generateCss(darkIcon, 'icon')}
  }
}
`;

  // Validate and auto-fix
  console.log('\nValidating generated CSS...');
  const { css: validatedCss, warnings, fixes } = validateAndFix(css);

  if (fixes.length > 0) {
    console.log('\n✅ Auto-fixes applied:');
    fixes.forEach((fix) => console.log(`   ${fix}`));
    css = validatedCss;
  }

  if (warnings.length > 0) {
    console.log('\n⚠️  Warnings:');
    warnings.forEach((warning) => console.log(`   ${warning}`));
  }

  if (warnings.length === 0 && fixes.length === 0) {
    console.log('✅ All tokens validated successfully!');
  }

  // Write output - Main tokens file (with dark mode)
  writeFileSync(OUTPUT_FILE, css, 'utf8');

  // Generate editor-only tokens (light mode only, no dark mode)
  // Semantic tokens are scoped to .editor-styles-wrapper for higher specificity
  // This ensures they override any dark mode rules from app.css
  const editorCss = `/**
 * Design Tokens (Editor Only) - Auto-generated from Figma
 *
 * DO NOT EDIT THIS FILE DIRECTLY!
 * Run: node scripts/transform-tokens.js
 *
 * This file contains ONLY light mode tokens for the Gutenberg editor.
 * It ensures the editor always displays in light mode regardless of system preferences.
 *
 * Primitives are on :root (referenced by var() everywhere)
 * Semantic tokens are scoped to .editor-styles-wrapper for higher specificity
 * than dark mode rules in app.css
 *
 * Source: config/design-tokens/*.tokens.json
 * Generated: ${new Date().toISOString()}
 */

/* ============================================
   PRIMITIVE TOKENS
   Base values that semantic tokens reference
   ============================================ */

:root {
  /* Colors - Primitives */
${generateCss(primitiveColors, 'color')}

  /* Spacing */
${generateCss(primitiveSpacing, 'spacing')}

  /* Border Radius */
${generateCss(primitiveRadius, 'radius')}

  /* Font Size */
${generateCss(primitiveFontSize, 'font-size')}

  /* Font Weight */
${generateCss(primitiveFontWeight, 'font-weight')}

  /* Font Family */
${generateCss(primitiveFontFamily, 'font-family')}

  /* Border Width */
${generateCss(primitiveBorderWidth, 'border-width')}

  /* Opacity */
${generateCss(primitiveOpacity, 'opacity')}

  /* Sizing */
${generateCss(primitiveSizing, 'sizing')}

  /* Gradients */
${generateCss(primitiveGradient, 'gradient')}
${buildTypographyTokens()}
${COMPONENT_TOKENS}
}

/* ============================================
   SEMANTIC TOKENS - Light Mode Only (on :root)
   Fallback for elements outside .editor-styles-wrapper
   ============================================ */

:root {
  /* Background */
${generateCss(lightBg, 'bg')}

  /* Text */
${generateCss(lightText, 'text')}

  /* Border */
${generateCss(lightBorder, 'border')}

  /* Icon */
${generateCss(lightIcon, 'icon')}
}

/* ============================================
   SEMANTIC TOKENS - Editor Scoped (Force Light Mode)
   Uses doubled selector for higher specificity and !important
   to override any dark mode rules from app.css/tokens.css.
   color-scheme: light signals to the browser that this element
   is always in light mode (affects scrollbars, form controls, etc.).
   The @media block has been removed — the bare doubled selector already
   covers both light and dark system preferences with !important.
   ============================================ */

.editor-styles-wrapper.editor-styles-wrapper {
  color-scheme: light;

  /* Background - Light Mode (forced) */
${generateCssImportant(lightBg, 'bg')}

  /* Text - Light Mode (forced) */
${generateCssImportant(lightText, 'text')}

  /* Border - Light Mode (forced) */
${generateCssImportant(lightBorder, 'border')}

  /* Icon - Light Mode (forced) */
${generateCssImportant(lightIcon, 'icon')}
}
`;

  writeFileSync(OUTPUT_EDITOR_FILE, editorCss, 'utf8');
  console.log(`Editor tokens written to: ${OUTPUT_EDITOR_FILE}`);

  // Stats
  const stats = {
    primitiveColors: Object.keys(primitiveColors).length,
    spacing: Object.keys(primitiveSpacing).length,
    radius: Object.keys(primitiveRadius).length,
    fontSize: Object.keys(primitiveFontSize).length,
    fontWeight: Object.keys(primitiveFontWeight).length,
    fontFamily: Object.keys(primitiveFontFamily).length,
    borderWidth: Object.keys(primitiveBorderWidth).length,
    opacity: Object.keys(primitiveOpacity).length,
    sizing: Object.keys(primitiveSizing).length,
    gradient: Object.keys(primitiveGradient).length,
    lightSemanticColors:
      Object.keys(lightBg).length +
      Object.keys(lightText).length +
      Object.keys(lightBorder).length +
      Object.keys(lightIcon).length,
    darkSemanticColors:
      Object.keys(darkBg).length +
      Object.keys(darkText).length +
      Object.keys(darkBorder).length +
      Object.keys(darkIcon).length,
  };

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

  // Exit with error code if there are warnings (for CI)
  if (warnings.length > 0) {
    console.log('\n⚠️  Token generation completed with warnings. Please review above.');
  }
}

// Run transformation only when invoked directly, not when imported by tests.
// pathToFileURL handles path segments with spaces, special chars, symlinks correctly.
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  transform();
}

export { fluidClamp, fluidLineHeight, FLUID_SIZES, VIEWPORT_MIN, VIEWPORT_MAX, ROOT_PX };
