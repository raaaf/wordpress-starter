<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * Thrown by the wp_send_json_error()/wp_send_json_success() test doubles in
 * tests/bootstrap.php instead of terminating the process (core's wp_die()
 * behaviour), so a test can assert the payload and HTTP status a handler
 * would have sent.
 */
final class WpJsonResponseException extends RuntimeException
{
    public function __construct(
        public readonly mixed $data,
        public readonly ?int $statusCode,
        public readonly bool $success,
    ) {
        parent::__construct('wp_send_json_' . ( $success ? 'success' : 'error' ) . ' called');
    }
}
