# Testing Guide

This project uses multiple testing frameworks to ensure code quality:

- **PHPUnit** - Unit tests for PHP code
- **Vitest** - Unit tests for JavaScript/TypeScript
- **Playwright** - End-to-end (E2E) tests
- **axe-core** - Accessibility testing

## Quick Start

```bash
# Run all PHP tests
composer test

# Run all JS/TS unit tests
npm run test

# Run E2E tests (requires running WordPress site)
npm run test:e2e

# Run accessibility tests only
npm run test:a11y
```

## PHP Unit Tests

Located in `tests/Unit/`. Run with:

```bash
composer test
# or
./vendor/bin/phpunit
```

### Test Files

- `ApplicationTest.php` - Service provider bootstrap tests
- `SecurityTest.php` - CSP and security header tests
- `ViteTest.php` - Asset loading tests
- `FieldDefinitionsTest.php` - ACF field configuration tests
- `HelpersTest.php` - Helper function tests

## JavaScript/TypeScript Tests

Located in `tests/js/`. Run with:

```bash
npm run test          # Run once
npm run test:ui       # Interactive UI mode
npm run test:coverage # With coverage report
```

### Test Files

- `navigation.test.ts` - Alpine.js navigation component tests
- `analytics.test.ts` - Pirsch analytics tracking tests

## E2E Tests (Playwright)

Located in `tests/e2e/`. Requires a running WordPress installation.

### Setup

1. Ensure WordPress is running (e.g., via Local by Flywheel)
2. Set the base URL in environment or `playwright.config.ts`

```bash
# Set base URL (default: http://starter.local)
export PLAYWRIGHT_BASE_URL=http://your-site.local
```

### Running Tests

```bash
npm run test:e2e       # Run all E2E tests
npm run test:e2e:ui    # Interactive UI mode
npm run test:a11y      # Accessibility tests only
```

### Test Files

- `homepage.spec.ts` - Homepage loading and structure
- `navigation.spec.ts` - Navigation functionality
- `accessibility.spec.ts` - WCAG accessibility compliance

## Accessibility Testing

The `accessibility.spec.ts` file uses axe-core to check for WCAG 2.1 Level AA compliance:

- Color contrast
- Keyboard accessibility
- ARIA attributes
- Document structure
- Form labels
- Image alt text

### Interpreting Results

Violations are categorized by impact:

- **Critical** - Must fix immediately
- **Serious** - Should fix soon
- **Moderate** - Should address
- **Minor** - Nice to fix

## CI/CD Integration

Tests run automatically on:

- Pull requests
- Pushes to main/master branch

See `.github/workflows/` for CI configuration.

## Writing New Tests

### PHP Tests

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}
```

### JS/TS Tests

```typescript
import { describe, it, expect } from 'vitest';

describe('My Feature', () => {
  it('should work', () => {
    expect(true).toBe(true);
  });
});
```

### E2E Tests

```typescript
import { test, expect } from '@playwright/test';

test.describe('My Page', () => {
  test('should load', async ({ page }) => {
    await page.goto('/my-page');
    await expect(page).toHaveTitle(/My Page/);
  });
});
```

## Code Coverage

Generate coverage reports:

```bash
# PHP coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage/php

# JS/TS coverage
npm run test:coverage
```

Coverage reports are generated in the `coverage/` directory.

## Best Practices

1. **Test behavior, not implementation** - Focus on what the code does, not how
2. **Keep tests isolated** - Each test should be independent
3. **Use descriptive names** - Test names should describe the expected behavior
4. **Follow AAA pattern** - Arrange, Act, Assert
5. **Mock external dependencies** - Don't test WordPress core functions
