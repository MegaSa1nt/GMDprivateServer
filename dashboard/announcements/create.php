<?php
try {
session_start();
require "../incl/dashboardLib.php";
require "../".$dbPath."incl/lib/connection.php";
$dl = new dashboardLib();
require_once "../".$dbPath."incl/lib/mainLib.php";
require "../".$dbPath."incl/lib/exploitPatch.php";
error_reporting(0);
$gs = new mainLib();
include "../".$dbPath."incl/lib/connection.php";
$dl->printFooter('../');
if((!isset($_SESSION["accountID"]) AND $_SESSION["accountID"] != 0)){
  $dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
	<p id="bruh">'.$dl->getLocalizedString("noPermission").'</p>
    <form class="form__inner" method="post" action=".">
	<button type="button" onclick="a(\'\')" class="btn-primary">'.$dl->getLocalizedString("Kish!").'</button>
    </form>
	</div>', 'mod');
	die();
}
if(!isset($_SESSION["accountID"]) | !$gs->checkPermission($_SESSION["accountID"], 'announcementCreate')) {
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
	<p id="bruh">'.$dl->getLocalizedString("noPermission").'</p>
    <form class="form__inner" method="post" action=".">
	<button type="button" onclick="a(\'\')" class="btn-primary">'.$dl->getLocalizedString("Kish!").'</button>
    </form>
	</div>', 'mod');
	die();
}
} catch (Exception $e) {
  echo '<script> console.log("Error: ' . $e->getMessage() . '")</script>';
  $dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
	<p id="bruh">'.$dl->getLocalizedString("noPermission").'</p>
    <form class="form__inner" method="post" action=".">
	<button type="button" onclick="a(\'\')" class="btn-primary">'.$dl->getLocalizedString("Kish!").'</button>
    </form>
	</div>', 'mod');
	die();
}
$dl->title($dl->getLocalizedString("createAnnouncement"));
$dl->printSong("<div class='form'>
  <h1>".$dl->getLocalizedString("createAnnouncement")."</h1>
  <p>Not finished yet.</p>
</div>");
?>