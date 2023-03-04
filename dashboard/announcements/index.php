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
$dl->printSong("<div class="form">
  <h1>".$dl->getLocalizedString("announcements")."</h1>
  <p>Not finished yet.</p>
</div>");
?>
