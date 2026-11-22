<?php
error_reporting(0);
class au {
  function auth($dbPath = '../') {
	// Это что
    if(file_exists($dbPath."incl/lib/connection.php")) require_once $dbPath."incl/lib/connection.php";
    elseif(file_exists("../../$dbPath".''."incl/lib/connection.php")) require_once "../../$dbPath".''."incl/lib/connection.php";
    else require_once "../$dbPath".''."incl/lib/connection.php";
	
    $check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'auth'");
    $exist = $check->fetchAll();
    if(empty($exist)) return 'no';
    $token = $_COOKIE["auth"] ?? '';
    if(!is_string($token)) $token = '';
    if(isset($_SESSION["accountID"]) AND $_SESSION["accountID"] != 0) {
        $query = $db->prepare("SELECT auth FROM accounts WHERE accountID = :id");
        $query->execute([':id' => $_SESSION["accountID"]]);
        $auth = $query->fetchColumn();
        if(empty($token) || empty($auth) || !hash_equals($auth, $token)) $_SESSION["accountID"] = 0;
    } elseif($token !== '' && $token !== 'none') {
        $query = $db->prepare("SELECT accountID FROM accounts WHERE auth = :id AND auth != ''");
        $query->execute([':id' => $token]);
        $auth = $query->fetchColumn();
        if(!empty($auth)) $_SESSION["accountID"] = $auth;
    }
	return true;
  }
}
?>
