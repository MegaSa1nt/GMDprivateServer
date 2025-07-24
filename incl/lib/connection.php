<?php
@header('Content-Type: text/html; charset=utf-8');
require __DIR__."/../../config/misc.php";
require __DIR__."/../../config/connection.php";
require __DIR__."/../../config/security.php";
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/ip.php";
require_once __DIR__."/enums.php";

if(function_exists('ini_set')) ini_set('display_errors', ($debugMode ? 'On' : 'Off'));
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

if(!isset($db)) global $db;
if(empty($db)) {
	if(!isset($GLOBALS['core_cache'])) $GLOBALS['core_cache'] = [];
	
	try {
		if(!empty($_POST['gameVersion'])) {
			if($minGameVersion && $minGameVersion > $_POST['gameVersion']) exit(CommonError::InvalidRequest);
			if($maxGameVersion && $maxGameVersion < $_POST['gameVersion']) exit(CommonError::InvalidRequest);
		}
		if(!empty($_POST['binaryVersion'])) {
			if($minBinaryVersion && $minBinaryVersion > $_POST['binaryVersion']) exit(CommonError::InvalidRequest);
			if($maxBinaryVersion && $maxBinaryVersion < $_POST['binaryVersion']) exit(CommonError::InvalidRequest);
		}
				
		$db = new PDO("mysql:host=".$servername.";port=".$port.";dbname=".$dbname, $username, $password, array(PDO::ATTR_PERSISTENT => true));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		IP::checkIP($db);
		
		if(!$installed) require __DIR__."/migrate.php";
	} catch (PDOException $e) {
		echo "<h1>Connection to MySQL failed!</h1>";
		echo "Error: ".$e->getMessage();
	}
}
?>