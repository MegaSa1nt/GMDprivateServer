<?php
chdir(dirname(__FILE__));
//error_reporting(0);
require "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();
if($_POST["levelID"]){
	$levelID =  ExploitPatch::remove($_POST["levelID"]);
	$ip = $gs->getIP();
	$query = "SELECT count(*) FROM reports WHERE targetID = :levelID AND type = 1 AND hostname = :hostname";
	$query = $db->prepare($query);
	$query->execute([':levelID' => $levelID, ':hostname' => $ip]);

	if($query->fetchColumn() == 0){
		$query = $db->prepare("INSERT INTO reports (targetID, hostname) VALUES (:levelID, :hostname)");	
		$query->execute([':levelID' => $levelID, ':hostname' => $ip]);
		echo $db->lastInsertId();
	}else{
		echo -1;
	}	
}
?>