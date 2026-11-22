<?php
session_start();
$_SESSION["accountID"] = 0;
setcookie('auth', 'no', [
	'expires' => time() - 3600,
	'path' => '/',
	'secure' => true,
	'httponly' => true,
	'samesite' => 'Strict'
]);
if(!empty($_SERVER["HTTP_REFERER"])) header('Location: '.$_SERVER["HTTP_REFERER"]);
else header('Location: ../');
?>