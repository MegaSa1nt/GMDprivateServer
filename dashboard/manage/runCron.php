<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/cron.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login"));

$runCron = Cron::doEverything($person, true);
if(!$runCron) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCronTooFast"), "error"));

exit(Dashboard::renderToast("check", Dashboard::string("successRanCron"), "success"));
?>