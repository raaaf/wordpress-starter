<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Validates design token JSON files before processing
 *
 * Ensures token files have the correct structure and valid values
 * before they are saved and transformed to CSS.
 */
class DesignTokenValidator
{
    /**
     * Required sections in primitives.tokens.json
     *
     * @var array<string>
     */
    private const REQUIRED_PRIMITIVE_SECTIONS = [
        'color',
        'spacing',
        'fontSize',
        'fontWeight',
    ];

    /**
     * Required categories in semantic token files (light/dark)
     *
     * @var array<string>
     */
    private const REQUIRED_SEMANTIC_CATEGORIES = [
        'bg',
        'text',
        'border',
        'icon',
    ];

    /**
     * Validate primitives.tokens.json structure
     *
     * @param array<string, mixed> $data Parsed JSON data
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validatePrimitives(array $data): array
    {
        $errors = [];

        // Check required sections
        foreach (self::REQUIRED_PRIMITIVE_SECTIONS as $section) {
            if (!isset($data[$section])) {
                $errors[] = sprintf(
                    /* translators: %s: section name (e.g., color, spacing) */
                    __('Fehlende Sektion: %s', 'wp-starter'),
                    $section
                );
            }
        }

        // Validate color section if present
        if (isset($data['color']) && is_array($data['color'])) {
            $colorErrors = $this->validateColorSection($data['color'], 'color');
            $errors = array_merge($errors, $colorErrors);
        }

        return $errors;
    }

    /**
     * Validate semantic token files (light.tokens.json or dark.tokens.json)
     *
     * @param array<string, mixed> $data Parsed JSON data
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validateSemanticTokens(array $data): array
    {
        $errors = [];

        // Check required categories
        foreach (self::REQUIRED_SEMANTIC_CATEGORIES as $category) {
            if (!isset($data[$category])) {
                $errors[] = sprintf(
                    /* translators: %s: category name (e.g., bg, text, border) */
                    __('Fehlende Kategorie: %s', 'wp-starter'),
                    $category
                );
            }
        }

        // Validate each category
        foreach (self::REQUIRED_SEMANTIC_CATEGORIES as $category) {
            if (isset($data[$category]) && is_array($data[$category])) {
                $categoryErrors = $this->validateColorSection($data[$category], $category);
                $errors = array_merge($errors, $categoryErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate a color section recursively
     *
     * @param array<string, mixed> $colors Color data
     * @param string $path Current path for error messages
     * @return array<string> Array of error messages
     */
    private function validateColorSection(array $colors, string $path = ''): array
    {
        $errors = [];

        foreach ($colors as $key => $value) {
            // Cast to string for numeric keys (e.g., 50, 100, 200 in color scales)
            $key = (string) $key;

            // Skip metadata keys
            if (str_starts_with($key, '$')) {
                continue;
            }

            $currentPath = $path ? "{$path}.{$key}" : $key;

            if (!is_array($value)) {
                continue;
            }

            // Check if this is a token definition
            if (isset($value['$type']) && $value['$type'] === 'color') {
                // Validate color token
                if (!isset($value['$value'])) {
                    $errors[] = sprintf(
                        /* translators: %s: token path (e.g., color.accent.500) */
                        __('Fehlender Wert für: %s', 'wp-starter'),
                        $currentPath
                    );
                } elseif (is_array($value['$value'])) {
                    // Figma format with hex
                    if (!isset($value['$value']['hex'])) {
                        $errors[] = sprintf(
                            /* translators: %s: token path (e.g., color.accent.500) */
                            __('Fehlender Hex-Wert für: %s', 'wp-starter'),
                            $currentPath
                        );
                    } elseif (!$this->isValidHexColor($value['$value']['hex'])) {
                        $errors[] = sprintf(
                            /* translators: 1: token path, 2: invalid hex value */
                            __('Ungültiger Hex-Wert für %1$s: %2$s', 'wp-starter'),
                            $currentPath,
                            $value['$value']['hex']
                        );
                    }
                } elseif (is_string($value['$value'])) {
                    // Simple hex string format
                    if (!$this->isValidHexColor($value['$value'])) {
                        $errors[] = sprintf(
                            /* translators: 1: token path, 2: invalid hex value */
                            __('Ungültiger Hex-Wert für %1$s: %2$s', 'wp-starter'),
                            $currentPath,
                            $value['$value']
                        );
                    }
                }
            } elseif (!isset($value['$type'])) {
                // Nested object, recurse
                $nestedErrors = $this->validateColorSection($value, $currentPath);
                $errors = array_merge($errors, $nestedErrors);
            }
        }

        return $errors;
    }

    /**
     * Check if a string is a valid hex color
     *
     * @param string $color Color string to validate
     * @return bool True if valid hex color
     */
    private function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color);
    }

    /**
     * Validate JSON string and parse it
     *
     * @param string $json JSON string to validate
     * @return array{valid: bool, data: array<string, mixed>|null, error: string|null}
     */
    public function parseAndValidateJson(string $json): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'data' => null,
                'error' => sprintf(
                    /* translators: %s: JSON error message */
                    __('Ungültiges JSON: %s', 'wp-starter'),
                    json_last_error_msg()
                ),
            ];
        }

        if (!is_array($data)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => __('JSON muss ein Objekt sein', 'wp-starter'),
            ];
        }

        return [
            'valid' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    /**
     * Validate complete token file based on type
     *
     * @param string $json JSON content
     * @param string $type Token type: 'primitives', 'light', or 'dark'
     * @return array{valid: bool, errors: array<string>}
     */
    public function validate(string $json, string $type): array
    {
        $result = $this->parseAndValidateJson($json);

        if (!$result['valid']) {
            return [
                'valid' => false,
                'errors' => [$result['error']],
            ];
        }

        $errors = match ($type) {
            'primitives' => $this->validatePrimitives($result['data']),
            'light', 'dark' => $this->validateSemanticTokens($result['data']),
            default => [__('Unbekannter Token-Typ', 'wp-starter')],
        };

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
