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
