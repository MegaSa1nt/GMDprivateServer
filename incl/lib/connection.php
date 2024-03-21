<?php
error_reporting(0);
include dirname(__FILE__)."/../../config/connection.php";
@header('Content-Type: text/html; charset=utf-8');

// Combine all of https://github.com/SevenworksDev/proxy-list/tree/main/proxies as /database/config/proxies.txt
if (in_array($_SERVER['REMOTE_ADDR'], file('../../config/proxies.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) {
    http_response_code(404);
    exit;
}

if(!isset($port))
	$port = 3306;
try {
    $db = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password, array(
    PDO::ATTR_PERSISTENT => true
));
    // set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }
?>
