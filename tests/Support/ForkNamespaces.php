<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Namespaces of the client themes forked from this starter.
 *
 * The real fork names stay out of this public repository: they are read from
 * the git-ignored tests/fork-namespaces.php (copy tests/fork-namespaces.php.example).
 * The fictitious fixtures below always apply, so the leak checks keep covering
 * every naming shape a fork can have even without the local file:
 * PascalCase with a second capital, a single capital, all lowercase and an
 * acronym prefix.
 */
final class ForkNamespaces
{
    public const FIXTURES = [
        'AcmeAgentur',
        'Nordlicht',
        'kundentheme',
        'XYZVertrieb',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $local = [];
        $file = dirname(__DIR__) . '/fork-namespaces.php';

        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $local = array_values(array_filter($loaded, 'is_string'));
            }
        }

        return array_values(array_unique([...self::FIXTURES, ...$local]));
    }
}
