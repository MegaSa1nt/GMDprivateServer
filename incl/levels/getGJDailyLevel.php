<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require "../lib/mainLib.php";
require "../../config/misc.php";
$gs = new mainLib();
$type = !empty($_POST["type"]) ? $_POST["type"] : (!empty($_POST["weekly"]) ? $_POST["weekly"] : 0);
$midnight = ($type == 1) ? strtotime("next monday") : strtotime("tomorrow 00:00:00");
$current = time();
$query = $db->prepare("SELECT * FROM dailyfeatures WHERE timestamp < :current AND type = :type ORDER BY timestamp DESC LIMIT 1");
$query->execute([':current' => $current, ':type' => $type]);
$daily = $query->fetch();
if($query->rowCount() == 0) exit("-1");
$dailyID = $daily['feaID'];
if($type == 1) $dailyID += 100001;
$timeleft = $midnight - $current;

if(!$oldDailyWeekly) {
	$expire = $daily['timestamp'] + ($type == 0 ? 86400 : 604800);
	if($expire < $current) exit('0|'.$timeleft);
}

if(!$daily['webhookSent']) {
	$gs->sendDailyWebhook($daily['levelID'], $daily['type']);
	$sent = $db->prepare('UPDATE dailyfeatures SET webhookSent = 1 WHERE feaID = :feaID');
	$sent->execute([':feaID' => $daily['feaID']]);
}
echo $dailyID ."|". $timeleft;
?>
