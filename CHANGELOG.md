# [2.5.0](https://github.com/raaaf/starter/compare/v2.4.0...v2.5.0) (2026-02-26)

### Bug Fixes

- correct composer installer-paths and ignore theme-local plugins dir ([288a566](https://github.com/raaaf/starter/commit/288a566853329bcae019e92c081d4b40b65dd3f2))
- disable rate limiting in WP_DEBUG mode ([8789500](https://github.com/raaaf/starter/commit/87895008a27d31f2f0833b309d0ded15e0445378))
- **phpcs:** resolve empty catch and post-increment violations ([77461a3](https://github.com/raaaf/starter/commit/77461a3138db62638bd8edb4713bd66ff8a39c38))
- **tests:** add phpseclib3 to external namespace whitelist ([0002e3f](https://github.com/raaaf/starter/commit/0002e3faa96e8e80f8df940a3dfd006f4ea5ef19))

### Features

- **member-area:** ACF fields, taxonomy, icons, CSS refinements ([a7d4ebc](https://github.com/raaaf/starter/commit/a7d4ebc4d9bb2656291e19155317f1466a5186ce))
- **member-area:** implement iteration 2 — admin clarity, multi-source docs, simplified frontend ([dd00f6a](https://github.com/raaaf/starter/commit/dd00f6a753c16741f136e228e8c4da069e46a1a6))
- **member-area:** SFTP sync, encrypted passwords, download table ([a53c42d](https://github.com/raaaf/starter/commit/a53c42d5f1a21e2cbb59e72cdbf5fb79de5c1736))
- **member-area:** templates, login page, alert component, layout fixes ([ed78150](https://github.com/raaaf/starter/commit/ed78150b239cac7dfcd3ca2476a1c8369eebf461))

# [2.4.0](https://github.com/raaaf/starter/compare/v2.3.2...v2.4.0) (2026-02-25)

### Bug Fixes

- **acf:** reorder section header fields and align button group choices ([01684b1](https://github.com/raaaf/starter/commit/01684b1ff9ce5ae411ee4a14ccbb962531d6dbb5))
- **admin:** use flex layout for demo post action buttons ([ac70b65](https://github.com/raaaf/starter/commit/ac70b6522a88514e93673322000841d0b7d6440a))
- align icons with text, fix logo slider gradient and prose code color ([4e659ab](https://github.com/raaaf/starter/commit/4e659ab0619396f02e247ed3a7770f852b49958c))
- **button:** increase specificity of sm/lg size modifiers to override variant defaults ([276bfe3](https://github.com/raaaf/starter/commit/276bfe34975eb517c3a8e17f8c5690094538136b))
- **button:** move md sizing after variants so sm/lg overrides work correctly ([3949133](https://github.com/raaaf/starter/commit/3949133c76389dcded9c2e8873a18b76ea86dcf1))
- **button:** use correct CSS custom property names in button classes ([78d0d65](https://github.com/raaaf/starter/commit/78d0d65d438e8e5f6e5009433a3ba1caf78a370b))
- **styleguide:** add items-start to button size container to prevent flex stretch ([946477e](https://github.com/raaaf/starter/commit/946477e81dbf8acd1bcd0620b4191a5b4d3c80df))
- **styleguide:** remove .button class to avoid WordPress style conflicts ([d606dd1](https://github.com/raaaf/starter/commit/d606dd1c4b2218aed84506f4cfe42a5a350b9d08))
- **styleguide:** remove esc_attr from button class strings to preserve CSS tokens ([105c3cb](https://github.com/raaaf/starter/commit/105c3cb42aa7b256534ade3a89520bc61c906a08))
- **styleguide:** sync button variants and sizes with Button component ([b847720](https://github.com/raaaf/starter/commit/b8477202b576dad1ca0dfa98cc8464530a1a9299))
- **styleguide:** use semantic button CSS classes instead of inline Tailwind utilities ([4fb4f1a](https://github.com/raaaf/starter/commit/4fb4f1a83f657672267804c4de451808572281fb))
- sync composer.lock after rebase — add missing integrate-rybbit package ([34422fe](https://github.com/raaaf/starter/commit/34422fe085745c55c3a8d4cedae32e918bf712b1))
- **vite:** require .vite-port file before dev server check, set strictPort and CORS origin ([c2fd830](https://github.com/raaaf/starter/commit/c2fd830824bee4c4beb2b7a57d55eab69862531e))

### Features

- add section header and icon shortcode to column layouts ([6233500](https://github.com/raaaf/starter/commit/623350063840467e805910c8432f79f6711b4632))
- **editor:** add TinyMCE editor styles and custom formats ([104da5c](https://github.com/raaaf/starter/commit/104da5c9eb511b221aee290ebc0676d0229632c1))

## [2.3.2](https://github.com/raaaf/starter/compare/v2.3.1...v2.3.2) (2026-02-12)

### Bug Fixes

- **acf:** apply [br] replacement in templates instead of ACF filter ([4d3e6e4](https://github.com/raaaf/starter/commit/4d3e6e4e291fafad16932cd1756e521349710864))

## [2.3.1](https://github.com/raaaf/starter/compare/v2.3.0...v2.3.1) (2026-02-12)

### Bug Fixes

- resolve phpcs warning in DesignTokenServiceProvider and add [br] hint ([302a000](https://github.com/raaaf/starter/commit/302a0005f16718cea8e7183f91c3a09dd7e85b88))

# [2.3.0](https://github.com/raaaf/starter/compare/v2.2.1...v2.3.0) (2026-02-12)

### Bug Fixes

- use [br] shortcode instead of HTML for manual line breaks in titles ([d884cf2](https://github.com/raaaf/starter/commit/d884cf28acad31ba3ac0bc29dc272932f1ba470a))

### Features

- add [br] usage hint to title field instructions ([01e1d63](https://github.com/raaaf/starter/commit/01e1d63aa59a7c31e53e5c7eb5b2fca52a7f3fac))

## [2.2.1](https://github.com/raaaf/starter/compare/v2.2.0...v2.2.1) (2026-02-12)

### Bug Fixes

- use placeholder approach for br tag preservation in title fields ([a84fa4b](https://github.com/raaaf/starter/commit/a84fa4bfbfd56e150564efa9065c63ac62aca723))

# [2.2.0](https://github.com/raaaf/starter/compare/v2.1.0...v2.2.0) (2026-02-12)

### Features

- allow manual line breaks in headings and fix badge dark mode text color ([f21d4bf](https://github.com/raaaf/starter/commit/f21d4bfa6dc650dcce7c12fe657eee8ca02a3370))
- **css:** add Contact Form 7 design system integration styles ([13b9a91](https://github.com/raaaf/starter/commit/13b9a91f08ecaa60c4ddf017927e7207c0818d59))

# [2.1.0](https://github.com/raaaf/starter/compare/v2.0.3...v2.1.0) (2026-02-11)

### Bug Fixes

- dark mode variant, WCAG contrast overrides, and semantic token usage in templates ([0d3dee7](https://github.com/raaaf/starter/commit/0d3dee70500b0e7e9ebc19d2b283191ece4b0241))

### Features

- **tokens:** add borderWidth, opacity, sizing, and gradient token processing ([888b2b6](https://github.com/raaaf/starter/commit/888b2b638980dda07df2ac7d1a4f0a5afb6e1f40))

## [2.0.3](https://github.com/raaaf/starter/compare/v2.0.2...v2.0.3) (2026-02-11)

### Bug Fixes

- use var() references for semantic tokens and bind prose colors to design tokens ([c41a8b0](https://github.com/raaaf/starter/commit/c41a8b0a66ec6d7144b93bf7831b0e69a93cf198))

## [2.0.2](https://github.com/raaaf/starter/compare/v2.0.1...v2.0.2) (2026-02-11)

### Bug Fixes

- resolve namespace from composer.json in NamespaceConsistencyTest ([b8895a2](https://github.com/raaaf/starter/commit/b8895a2a624bdf7164348f81e5e0f6a3c0a9b1ab))

## [2.0.1](https://github.com/raaaf/starter/compare/v2.0.0...v2.0.1) (2026-02-11)

### Bug Fixes

- use correct wpackagist slug for Rybbit plugin (integrate-rybbit) ([181ce2e](https://github.com/raaaf/starter/commit/181ce2e18c17748c4ab207a80be8f996aef506b7))

# [2.0.0](https://github.com/raaaf/starter/compare/v1.6.1...v2.0.0) (2026-01-30)

### Features

- migrate analytics from Pirsch to Rybbit ([1bfb963](https://github.com/raaaf/starter/commit/1bfb963a6958b565c46a808fc1d21d3c70f679cd))

### BREAKING CHANGES

- Pirsch Analytics is no longer supported.
  Install Rybbit WordPress Plugin from GitHub instead.

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>

## [1.6.1](https://github.com/raaaf/starter/compare/v1.6.0...v1.6.1) (2026-01-29)

### Bug Fixes

- improve dark mode support and WCAG accessibility ([fc3262f](https://github.com/raaaf/starter/commit/fc3262f2df7bd42d25efc3cd2a18b4cd89938da1)), closes [#e5541d](https://github.com/raaaf/starter/issues/e5541d)

# [1.6.0](https://github.com/raaaf/starter/compare/v1.5.0...v1.6.0) (2026-01-28)

### Bug Fixes

- **hero:** use direct opacity instead of Tailwind CSS variable ([545c505](https://github.com/raaaf/starter/commit/545c505babcf8b62f42b0bad65d0ca3a43d459fd))
- lower PHP requirement to 8.2 for broader hosting compatibility ([e46f44e](https://github.com/raaaf/starter/commit/e46f44e228e20b218926f54aeb9bab6586d8c6af))
- resolve PHPCS and PHPUnit CI failures ([e407294](https://github.com/raaaf/starter/commit/e40729472c9a87746c296cc0ac49f109d9fdd6d5))
- resolve PHPStan CI failures ([be2f54d](https://github.com/raaaf/starter/commit/be2f54d378948e32ed8434962cf02c9ea4d36f3b))
- **templates:** hide page header when ACF flexible content exists ([e63df2c](https://github.com/raaaf/starter/commit/e63df2c5d4a59f8ddbff73ce7fddb711ccf1769b))

### Features

- **acf:** add source selection to Team and Testimonials layouts ([3a228d0](https://github.com/raaaf/starter/commit/3a228d0e4cb6c23a162e969d96f798aaba9ccdcb))
- add design token management system ([0e57739](https://github.com/raaaf/starter/commit/0e577393f218144c52533bf7a596cbae1c129535))
- add theme packaging script ([58656b3](https://github.com/raaaf/starter/commit/58656b38200ad383041d0600c638f262cdf89061))
- **cpt:** add Team CPT with admin columns and field grouping ([2edb32a](https://github.com/raaaf/starter/commit/2edb32aaed6f82eb04542ec7795f400d6e0e46ee))
- **setup:** add default contact data and hero layout prefill ([454448b](https://github.com/raaaf/starter/commit/454448bb123a0991e891a7fdedd30bf7284f1f77))

# [1.5.0](https://github.com/raaaf/starter/compare/v1.4.0...v1.5.0) (2026-01-27)

### Features

- add automatic plugin configuration system ([a7370e2](https://github.com/raaaf/starter/commit/a7370e243cdbdacc9700eaef798fed0d62629cdb))

# [1.4.0](https://github.com/raaaf/starter/compare/v1.3.0...v1.4.0) (2026-01-27)

### Features

- **acf:** prefill new pages with Hero layout ([6d41d3c](https://github.com/raaaf/starter/commit/6d41d3cbd6a65c31b56df5862652c8f0d6477b4a))
- **vite:** add dynamic port detection for dev server ([d54c4fe](https://github.com/raaaf/starter/commit/d54c4fe2da7dfbe882cfb4083789fde923ea117c))

# [1.3.0](https://github.com/raaaf/starter/compare/v1.2.0...v1.3.0) (2026-01-22)

### Features

- add optional scroll animations for sections ([4d8e351](https://github.com/raaaf/starter/commit/4d8e3513f6ddbcb3dd5380d081dfb9e2f716fdd2))

# [1.2.0](https://github.com/raaaf/starter/compare/v1.1.2...v1.2.0) (2026-01-22)

### Features

- **blog:** redesign blog templates with bento grid layout ([72c5b9f](https://github.com/raaaf/starter/commit/72c5b9f878f17bbad603bed4d4fb27691eb232ae))

## [1.1.2](https://github.com/raaaf/starter/compare/v1.1.1...v1.1.2) (2026-01-22)

### Bug Fixes

- CSS layer architecture, namespace consistency, and Alpine.js syntax ([ab4e62d](https://github.com/raaaf/starter/commit/ab4e62db6bb8fd63ca73a25362c659ad0c2602f4))

## [1.1.1](https://github.com/raaaf/starter/compare/v1.1.0...v1.1.1) (2026-01-22)

### Bug Fixes

- **release:** auto-update style.css version on release ([15752c0](https://github.com/raaaf/starter/commit/15752c0e201c1e662cb447c53f1ac9b96933e240))

# [1.1.0](https://github.com/raaaf/starter/compare/v1.0.0...v1.1.0) (2026-01-22)

### Bug Fixes

- **deps:** sync composer.lock with webp-express package ([6e62188](https://github.com/raaaf/starter/commit/6e621880a61de28ca666804b88b8c8e0526ef01b))
- **deps:** sync package-lock.json with updated dependencies ([07e50d6](https://github.com/raaaf/starter/commit/07e50d631ff8f53cf40f5475127c56dce776b02a))
- **tests:** update FieldDefinitionsTest to use methods instead of constants ([652db5d](https://github.com/raaaf/starter/commit/652db5dea51078264c710a4292f781534eb72aa2))

### Features

- **i18n:** add full internationalization support for translation readiness ([6d942c1](https://github.com/raaaf/starter/commit/6d942c1a5c898e72d60bc6cfacd7b0b4df76bcef))
- **seo:** add social sharing image with full Open Graph metadata ([a5e60a5](https://github.com/raaaf/starter/commit/a5e60a52327da07368d13e1a2924bfe714aeeae0))

# 1.0.0 (2026-01-22)

### Bug Fixes

- **ci:** mark compiled directory as optional in PHPStan config ([6c7764e](https://github.com/raaaf/starter/commit/6c7764ecb01f42b6a83d18cc40d432abeaec2dc7))
- **i18n:** add missing translators comment for PHPCS ([a1f7dab](https://github.com/raaaf/starter/commit/a1f7dab8071257c9051385e141e8f98d752888e2))

### Features

- add automated releases and WordPress update checker ([53d75f2](https://github.com/raaaf/starter/commit/53d75f25255466de54232b2c66df9ac725318b97))

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Security escaping for all block templates (OWASP compliance)
- Accessibility improvements (WCAG 2.1 AA)
  - Skip link with working `#main-content` target
  - Visible focus states using `focus-visible`
  - Link underlines for non-color-only identification
- WebP image support via `Fields::pictureWebP()` helper
- Resource hints for Pirsch Analytics (dns-prefetch)
- Open Graph and Twitter Card meta tags
- Extended Schema.org markup (Organization, Article)
- Custom 404 error page with search and navigation
- GitHub Actions CI/CD pipeline
- CHANGELOG.md for version tracking

### Changed

- Replaced `focus:outline-none` with `focus-visible` for better keyboard accessibility
- Updated button component with proper URL/attribute escaping
- Improved `Fields::responsiveImage()` with default lazy loading

### Fixed

- XSS vulnerability in CTA block ID generation
- Missing `esc_attr()` on anchor IDs across blocks
- Missing `esc_url()` on video and map URLs

## [1.0.0] - 2026-01-20

### Added

- Initial release with modern WordPress theme architecture
- Vite 7.3 build system with HMR
- TailwindCSS v4.1 with Figma design tokens
- Alpine.js v3.15 for reactive components
- Laravel Blade templating engine
- ACF Pro integration with 27 custom blocks
- Service Provider architecture
- PHP 8.2+ with strict types
- Pirsch Analytics integration (GDPR-compliant)
- Content Security Policy (CSP) headers
- Comprehensive test suite (PHPUnit, Vitest)
