<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";

if(isset($_POST['lang'])) {
	$lang = strtoupper(Escape::latin($_POST['lang'], 2)) ?: 'EN';
	
	$_COOKIE['lang'] = $lang;
	setcookie("lang", $lang, 2147483647, "/");
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", "@", "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>