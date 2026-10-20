<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require "../../config/misc.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
require_once "../lib/cron.php";
$gs = new mainLib();

?>