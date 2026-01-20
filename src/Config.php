<?php

declare(strict_types=1);

namespace WordpressStarter;

class Config
{
    /** @var array<string, mixed> */
    private static array $config = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::loadEnvironmentVariables();
        self::loadConfigFile();
        self::$loaded = true;
    }

    private static function loadEnvironmentVariables(): void
    {
        $envFile = get_template_directory() . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                self::$config[$key] = $value;
            }
        }
    }

    private static function loadConfigFile(): void
    {
        $configFile = get_template_directory() . '/config/app.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (is_array($config)) {
                self::$config = array_merge(self::$config, $config);
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        
        // Support dot notation
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::load();
        self::$config[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::load();
        return isset(self::$config[$key]);
    }
}