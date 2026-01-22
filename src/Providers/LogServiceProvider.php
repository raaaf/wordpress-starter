<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Provides simple logging functionality for the theme
 *
 * Writes to WordPress debug.log when WP_DEBUG_LOG is enabled.
 * Optionally integrates with external services like Sentry.
 */
class LogServiceProvider extends ServiceProvider
{
    private const LOG_LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];

    public function register(): void
    {
        // Nothing to register
    }

    public function boot(): void
    {
        // Register error handler for uncaught exceptions
        if (WP_DEBUG) {
            add_action('shutdown', [$this, 'logFatalErrors']);
        }
    }

    /**
     * Log fatal errors on shutdown
     */
    public function logFatalErrors(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::error('Fatal error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }
    }

    /**
     * Log a debug message
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Log a critical message
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    /**
     * Log a message at the specified level
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!in_array($level, self::LOG_LEVELS, true)) {
            $level = 'info';
        }

        // Only log if WP_DEBUG_LOG is enabled
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $timestamp = wp_date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $contextString = !empty($context) ? ' ' . wp_json_encode($context) : '';

        $formattedMessage = "[{$timestamp}] wp-starter.{$levelUpper}: {$message}{$contextString}";

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log($formattedMessage);
    }

    /**
     * Log an exception
     *
     * @param \Throwable $exception
     * @param string $level
     */
    public static function exception(\Throwable $exception, string $level = 'error'): void
    {
        self::log($level, $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => array_slice($exception->getTrace(), 0, 5),
        ]);
    }
}
