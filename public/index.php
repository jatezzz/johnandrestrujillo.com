<?php

declare(strict_types=1);

// Front controller. In the repository (and on staging) the application sits next to
// public/; on the shared host it sits in a site/ folder beside the web root.
$root = dirname(__DIR__);
if (!is_file($root . '/src/bootstrap.php')) {
    $root = dirname(__DIR__) . '/site';
}

require $root . '/src/bootstrap.php';

$app = new Portfolio\App($root);
$app->handle(
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    $_POST,
    $_SERVER,
)->send();
