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
