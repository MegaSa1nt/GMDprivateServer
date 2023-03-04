<?php
session_start();
require "../incl/dashboardLib.php";
require "../".$dbPath."incl/lib/connection.php";
$dl = new dashboardLib();
require_once "../".$dbPath."incl/lib/mainLib.php";
require "../".$dbPath."incl/lib/exploitPatch.php";
error_reporting(0);
$gs = new mainLib();
include "../".$dbPath."incl/lib/connection.php";
$dl->title($dl->getLocalizedString("announcements"));
$dl->printFooter('../');

$dl->printSong("<div class='form'>
  <h1>".$dl->getLocalizedString("announcements")."</h1>
  <p>Not finished yet.</p>
</div>");

if(isset($_GET["page"]) AND is_numeric($_GET["page"]) AND $_GET["page"] > 0){
	$page = ($_GET["page"] - 1) * 10;
	$actualpage = $_GET["page"];
}else{
	$page = 0;
	$actualpage = 1;
}
$table = '<table class="table table-inverse"><tr><th>#</th><th>'.$dl->getLocalizedString("username").'</th><th>'.'</th><th>'.$dl->getLocalizedString("announcement").'</th><th>'.$dl->getLocalizedString("timestamp").'</th><th>'.$dl->getLocalizedString("likes").'</th></tr>';
if(!isset($_GET["search"])) $_GET["search"] = "";
$srcbtn = "";
if(!isset($_GET["search"]) OR empty(trim(ExploitPatch::remove($_GET["search"])))) {
	$query = $db->prepare("SELECT * FROM accounts WHERE isActive = 1 ORDER BY accountID ASC LIMIT 10 OFFSET $page");
	$query->execute();
	$result = $query->fetchAll();
	if(empty($result)) {
		$dl->printSong('<div class="form">
		<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
		<form class="form__inner" method="post" action=".">
			<p>'.$dl->getLocalizedString("emptyPage").'</p>
			<button type="submit" class="btn-primary">'.$dl->getLocalizedString("dashboard").'</button>
		</form>
	</div>');
		die();
	} 
}

?>
