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
    /**
     * Object cache group used when a persistent object cache is available.
     */
    private const CACHE_GROUP = 'rate_limit';

    private readonly string $key;

    private readonly int $maxAttempts;

    private readonly int $decaySeconds;

    /**
     * Create a new rate limiter instance
     *
     * @param string $action Unique identifier for the rate limited action
     * @param int $maxAttempts Maximum number of attempts allowed in the time window
     * @param int $decaySeconds Time window in seconds
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
     * With a persistent object cache, the count is incremented atomically
     * via wp_cache_add()/wp_cache_incr() so concurrent requests cannot all
     * pass. Without one, the transient path below is best-effort: it reads
     * the transient exactly once and writes the incremented value right
     * away to keep the race window as small as possible, but two requests
     * arriving inside that window can still both be counted as attempt 1.
     *
     * @return bool True if the request is allowed, false if rate limited
     */
    public function attempt(): bool
    {
        $count = $this->recordAttempt();

        return $count <= $this->maxAttempts;
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
        if ($this->usingExternalObjectCache()) {
            $found = false;
            $expires = wp_cache_get($this->expiresCacheKey(), self::CACHE_GROUP, false, $found);

            if (!$found || !is_int($expires)) {
                return 0;
            }

            return max(0, $expires - time());
        }

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
        wp_cache_delete($this->key, self::CACHE_GROUP);
        wp_cache_delete($this->expiresCacheKey(), self::CACHE_GROUP);
    }

    /**
     * Get the current number of attempts, without recording a new one
     */
    private function getCurrentAttempts(): int
    {
        if ($this->usingExternalObjectCache()) {
            $found = false;
            $count = wp_cache_get($this->key, self::CACHE_GROUP, false, $found);

            return $found ? (int) $count : 0;
        }

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
     * Record an attempt and return the resulting count for the current window.
     *
     * When a persistent object cache is available, the count is stored and
     * incremented atomically via wp_cache_add()/wp_cache_incr(), so
     * concurrent requests cannot read the same stale count. Without a
     * persistent object cache, the transient is read exactly once and
     * written back immediately with the incremented value.
     */
    private function recordAttempt(): int
    {
        if ($this->usingExternalObjectCache()) {
            // Seed the window on first use; a no-op if it already exists.
            wp_cache_add($this->expiresCacheKey(), time() + $this->decaySeconds, self::CACHE_GROUP, $this->decaySeconds);
            wp_cache_add($this->key, 0, self::CACHE_GROUP, $this->decaySeconds);

            $count = wp_cache_incr($this->key, 1, self::CACHE_GROUP);

            // wp_cache_incr() returns false if the key vanished between the
            // add() above and the incr() (e.g. TTL expiry race); treat that
            // as the first attempt of a fresh window.
            return $count === false ? 1 : $count;
        }

        $data = get_transient($this->key);
        $now = time();

        if (!is_array($data) || !isset($data['expires']) || $data['expires'] <= $now) {
            // Start a new window
            $count = 1;
            $data = [
                'count' => 1,
                'expires' => $now + $this->decaySeconds,
            ];
        } else {
            // Increment existing window
            $count = (int) ( $data['count'] ?? 0 ) + 1;
            $data['count'] = $count;
        }

        // Store with expiration slightly longer than the decay to ensure cleanup
        set_transient($this->key, $data, $this->decaySeconds + 10);

        return $count;
    }

    /**
     * Whether a persistent object cache is available for atomic counting
     */
    private function usingExternalObjectCache(): bool
    {
        // Returns null (not false) until wp_start_object_cache() ran, and some
        // cache plugins leave it null on admin-ajax; treat null as "no cache".
        return (bool) wp_using_ext_object_cache();
    }

    /**
     * Cache key that stores the current window's expiry timestamp
     */
    private function expiresCacheKey(): string
    {
        return $this->key . '_expires';
    }

    /**
     * Get the client IP address
     *
     * By default only REMOTE_ADDR is used — forwarded headers are client-controlled
     * and would allow trivial rate-limit bypass via spoofing. When the site runs
     * behind a trusted proxy/load balancer, enable the
     * `{theme_prefix}_trust_proxy_headers` filter to honour the forwarded headers.
     *
     * Returns a hashed version for privacy while still allowing
     * per-IP rate limiting.
     */
    private function getClientIp(): string
    {
        $ip = '';

        // Forwarded headers are only trusted behind an explicitly enabled trusted proxy
        $forwardedHeaders = ['REMOTE_ADDR'];
        if (apply_filters(ThemeContext::prefix() . '_trust_proxy_headers', false)) {
            $forwardedHeaders = [
                'HTTP_CF_CONNECTING_IP', // Cloudflare
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_REAL_IP',
                'REMOTE_ADDR',
            ];
        }

        foreach ($forwardedHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // X-Forwarded-For can contain multiple IPs: the client controls the
                // leftmost entries, so take the rightmost one, which is the one
                // appended by the trusted proxy itself.
                if (str_contains($ip, ',')) {
                    $ipParts = explode(',', $ip);
                    $ip = trim( (string) end($ipParts));
                }

                if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
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
     * @param string $action Action identifier
     * @param int $maxAttempts Maximum attempts
     * @param int $decaySeconds Time window
     *
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
     * @param string $action Action identifier
     * @param int $maxAttempts Maximum attempts
     * @param int $decaySeconds Time window
     */
    public static function enforce(string $action, int $maxAttempts = 10, int $decaySeconds = 60): void
    {
        $limiter = new self($action, $maxAttempts, $decaySeconds);

        if (!$limiter->attempt()) {
            wp_send_json_error(
                [
                    'message' => __('Zu viele Anfragen. Bitte versuche es später erneut.', 'wp-starter'),
                    'retry_after' => $limiter->retryAfter(),
                ],
                429,
            );
        }
    }
}
