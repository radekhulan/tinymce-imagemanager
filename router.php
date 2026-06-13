<?php

/**
 * Dev-server router for PHP's built-in web server (`php -S`).
 *
 * It emulates the front-controller rewrite that web.config (IIS) and .htaccess
 * (Apache) provide in real deployments:
 *
 *   - existing files (TinyMCE assets, SPA bundles, uploaded media) are served as-is;
 *   - anything under /fileimagemanager/public/ is routed to the manager's index.php;
 *   - everything else falls through to the demo page.
 *
 * SCRIPT_NAME is set per-app so the manager's base-path detection (and the media
 * URL config) resolve exactly as they do behind IIS / Apache.
 *
 * Started by dev-server.ps1 / dev-server.sh — you normally don't run this directly.
 */

declare(strict_types=1);

// realpath() so the containment check below also holds when the project is reached
// through a junction / symlink (e.g. c:\work -> c:\inetpub\wwwroot).
$root = realpath(__DIR__) ?: __DIR__;
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath($root . rawurldecode($uri));

// 1) Serve an existing file directly (with a guard against path traversal).
if ($uri !== '/' && $file !== false && is_file($file)
    && strncmp($file, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) === 0) {
    return false;
}

// 2) File & Image Manager front controller.
if (strncmp($uri, '/fileimagemanager/public', 24) === 0) {
    $fc = $root . '/fileimagemanager/public/index.php';
    if (is_file($fc)) {
        $_SERVER['SCRIPT_NAME']     = '/fileimagemanager/public/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $fc;
        require $fc;
        return true;
    }
}

// 3) Demo page (and any unknown route).
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
require $root . '/index.php';
