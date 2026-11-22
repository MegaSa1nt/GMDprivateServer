<?php
$corsOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$corsHost = $corsOrigin !== '' ? parse_url($corsOrigin, PHP_URL_HOST) : null;
$corsSelf = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
if(is_string($corsHost) && $corsSelf !== '' && strcasecmp($corsHost, $corsSelf) === 0) {
	header('Access-Control-Allow-Origin: '.$corsOrigin);
	header('Access-Control-Allow-Methods: GET, POST');
	header('Access-Control-Allow-Headers: X-Requested-With');
	header('Access-Control-Allow-Credentials: true');
	header('Vary: Origin');
}
?>
