<?php

declare(strict_types=1);

namespace Portfolio;

// The shared host starts every request with a 2 MB memory limit. Raise it before
// anything else allocates; never lower a limit that is already higher.
(static function (): void {
    $current = ini_get('memory_limit');
    if ($current === false || $current === '-1') {
        return;
    }
    $units = ['k' => 1024, 'm' => 1024 ** 2, 'g' => 1024 ** 3];
    $suffix = strtolower(substr($current, -1));
    $bytes = isset($units[$suffix]) ? (int) $current * $units[$suffix] : (int) $current;
    if ($bytes < 32 * 1024 ** 2) {
        ini_set('memory_limit', '32M');
    }
})();

spl_autoload_register(static function (string $class): void {
    $prefix = __NAMESPACE__ . '\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// The one vendored dependency: gregwar/captcha (see vendor/gregwar-captcha/README.md).
spl_autoload_register(static function (string $class): void {
    $prefix = 'Gregwar\\Captcha\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__) . '/vendor/gregwar-captcha/' . substr($class, strlen($prefix)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
