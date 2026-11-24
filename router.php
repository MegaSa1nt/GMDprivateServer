<?php
// PHP's built-in server does not read .htaccess rewrite rules. Route the
// Geometry Dash news namespace to its existing handler while leaving all
// ordinary files and PHP endpoints untouched.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/news(?:/|$)#', $requestPath)) {
    $_GET['request'] = basename($requestPath);
    require __DIR__ . '/news/handler.php';
    return true;
}

if (preg_match('#^/music/[^/]+$#', $requestPath)) {
	$_GET['request'] = basename($requestPath);
	require __DIR__ . '/music/handler.php';
	return true;
}

if (preg_match('#^/sfx/[^/]+$#', $requestPath)) {
	$_GET['request'] = basename($requestPath);
	require __DIR__ . '/sfx/handler.php';
	return true;
}

return false;
