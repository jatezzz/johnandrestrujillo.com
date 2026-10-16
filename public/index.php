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
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$app->handle(
    $method,
    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    $_POST,
    $_SERVER,
    $method === 'POST' ? (string) file_get_contents('php://input', false, null, 0, 8192) : '',
)->send();

// Close the connection first where the server allows it, then send metrics.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
$app->finish();
