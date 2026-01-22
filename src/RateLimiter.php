<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Simple transient-based rate limiter for AJAX handlers
 *
 * Provides protection against abuse by limiting the number of requests
 * per time window. Uses WordPress transients for storage.
 *
 * Usage:
 *   $limiter = new RateLimiter('my_action', 10, 60); // 10 requests per 60 seconds
 *   if (!$limiter->attempt()) {
 *       wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
 *   }
 */
class RateLimiter
{
    private string $key;
    private int $maxAttempts;
    private int $decaySeconds;

    /**
     * Create a new rate limiter instance
     *
     * @param string $action     Unique identifier for the rate limited action
     * @param int    $maxAttempts Maximum number of attempts allowed in the time window
     * @param int    $decaySeconds Time window in seconds
     */
    public function __construct(string $action, int $maxAttempts = 10, int $decaySeconds = 60)
    {
        // Include user identifier for per-user rate limiting
        $userId = get_current_user_id();
        $ip = $this->getClientIp();

        $identifier = $userId > 0 ? "user_{$userId}" : "ip_{$ip}";
        $this->key = "rate_limit_{$action}_{$identifier}";
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    /**
     * Check if the rate limit has been exceeded and record an attempt
     *
     * @return bool True if the request is allowed, false if rate limited
     */
    public function attempt(): bool
    {
        $current = $this->getCurrentAttempts();

        if ($current >= $this->maxAttempts) {
            return false;
        }

        $this->incrementAttempts($current);
        return true;
    }

    /**
     * Check if the rate limit would be exceeded (without recording)
     */
    public function tooManyAttempts(): bool
    {
        return $this->getCurrentAttempts() >= $this->maxAttempts;
    }

    /**
     * Get the number of remaining attempts
     */
    public function remainingAttempts(): int
    {
        return max(0, $this->maxAttempts - $this->getCurrentAttempts());
    }

    /**
     * Get seconds until the rate limit resets
     */
    public function retryAfter(): int
    {
        $data = get_transient($this->key);
        if (!is_array($data) || !isset($data['expires'])) {
            return 0;
        }

        return max(0, $data['expires'] - time());
    }

    /**
     * Clear the rate limit for this action/user combination
     */
    public function clear(): void
    {
        delete_transient($this->key);
    }

    /**
     * Get the current number of attempts
     */
    private function getCurrentAttempts(): int
    {
        $data = get_transient($this->key);

        if (!is_array($data)) {
            return 0;
        }

        // Check if the window has expired
        if (isset($data['expires']) && $data['expires'] <= time()) {
            delete_transient($this->key);
            return 0;
        }

        return (int) ( $data['count'] ?? 0 );
    }

    /**
     * Increment the attempt counter
     */
    private function incrementAttempts(int $current): void
    {
        $data = get_transient($this->key);

        if (!is_array($data) || !isset($data['expires']) || $data['expires'] <= time()) {
            // Start a new window
            $data = [
                'count' => 1,
                'expires' => time() + $this->decaySeconds,
            ];
        } else {
            // Increment existing window
            $data['count'] = $current + 1;
        }

        // Store with expiration slightly longer than the decay to ensure cleanup
        set_transient($this->key, $data, $this->decaySeconds + 10);
    }

    /**
     * Get the client IP address
     *
     * Returns a hashed version for privacy while still allowing
     * per-IP rate limiting.
     */
    private function getClientIp(): string
    {
        $ip = '';

        // Check for forwarded IP (behind proxy/load balancer)
        $forwardedHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($forwardedHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // X-Forwarded-For can contain multiple IPs, take the first
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                break;
            }
        }

        // Hash the IP for privacy (we don't need to store actual IPs)
        return substr(md5($ip . wp_salt('auth')), 0, 16);
    }

    /**
     * Static helper to quickly check and enforce rate limiting
     *
     * @param string $action      Action identifier
     * @param int    $maxAttempts Maximum attempts
     * @param int    $decaySeconds Time window
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $action, int $maxAttempts = 10, int $decaySeconds = 60): bool
    {
        $limiter = new self($action, $maxAttempts, $decaySeconds);
        return $limiter->attempt();
    }

    /**
     * Static helper that sends a 429 response if rate limited
     *
     * @param string $action      Action identifier
     * @param int    $maxAttempts Maximum attempts
     * @param int    $decaySeconds Time window
     */
    public static function enforce(string $action, int $maxAttempts = 10, int $decaySeconds = 60): void
    {
        $limiter = new self($action, $maxAttempts, $decaySeconds);

        if (!$limiter->attempt()) {
            wp_send_json_error(
                [
                    'message' => __('Zu viele Anfragen. Bitte versuchen Sie es später erneut.', 'wp-starter'),
                    'retry_after' => $limiter->retryAfter(),
                ],
                429
            );
        }
    }
}
