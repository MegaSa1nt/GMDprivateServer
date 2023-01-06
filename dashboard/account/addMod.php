<?php
session_start();
require "../incl/dashboardLib.php";
require "../".$dbPath."incl/lib/Captcha.php";
require "../".$dbPath."incl/lib/connection.php";
$dl = new dashboardLib();
require_once "../".$dbPath."incl/lib/mainLib.php";
$gs = new mainLib();
include "../".$dbPath."incl/lib/connection.php";
include "../".$dbPath."incl/lib/exploitPatch.php";
$ep = new exploitPatch();
$dl->title($dl->getLocalizedString("addMod"));
$dl->printFooter('../');
$options = '';
if($gs->checkPermission($_SESSION["accountID"], "dashboardAddMod")){
	$accountID = $_SESSION["accountID"];
if(!empty($_POST["user"])) {
	if(!Captcha::validateCaptcha()) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("invalidCaptcha").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod');
		die();
	}
	$mod = ExploitPatch::remove($_POST["user"]);
	$role = ExploitPatch::remove($_POST["role"]);
	if(!is_numeric($role)) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("invalidPost").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod');
		die();
	}
	if(!is_numeric($mod)) $mod = $gs->getAccountIDFromName($mod); 
	$query = $db->prepare("SELECT accountID FROM accounts WHERE accountID=".$mod."");
	$query->execute();
	$res = $query->fetchAll();
	if(count($res) == 0) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("nothingFound").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod');
		die();
	}
	if($role < $gs->getMaxValuePermission($_SESSION["accountID"], 'roleID')) die($dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("modAboveYourRole").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod'));
	if($mod == $accountID) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("modYourself").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod');
		die();
	}
	$query = $db->prepare("SELECT accountID FROM roleassign WHERE accountID=:mod");
	$query->execute([':mod' => $mod]);
	$res = $query->fetchAll();
	if(count($res) != 0) {
		$dl->printSong('<div class="form">
			<h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
			<form class="form__inner" method="post" action="">
			<p>'.$dl->getLocalizedString("alreadyMod").'</p>
			<button type="submit" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
			</form>
		</div>', 'mod');
		die();
	}
	$query = $db->prepare("INSERT INTO roleassign (roleID, accountID) VALUES (:role, :mod)");
	$query->execute([':role' => $role, ':mod' => $mod]);
	$mod = $gs->getAccountName($mod);
	$query = $db->prepare("INSERT INTO modactions  (type, value, timestamp, account) VALUES ('20', :value, :timestamp, :account)");
	$query->execute([':value' => $mod, ':timestamp' => time(), ':account' => $accountID]);
	$success = $dl->getLocalizedString("addedMod").' <b>'.$mod."</b>!";
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("addMod").'</h1>
    <form class="form__inner" method="post" action="">
		<p>'.$success.'</p>
	    <button type="submit" class="btn-primary">'.$dl->getLocalizedString("addModOneMore").'</button>
    </form>
</div>', 'mod');
} else {
	$query = $db->prepare("SELECT roleName, roleID FROM roles WHERE roleID >= :id");
	$query->execute([':id' => $gs->getMaxValuePermission($_SESSION["accountID"], 'roleID')]);
	$query = $query->fetchAll();
	foreach($query as &$role) {
		switch($role["roleID"]) {
			case 1:
				$options .= '<option value="1">'.$dl->getLocalizedString("admin").'</option>';
				break;
			case 2:
				$options .= '<option value="2">'.$dl->getLocalizedString("elder").'</option>';
				break;
			case 3:
				$options .= '<option value="3">'.$dl->getLocalizedString("moder").'</option>';
				break;
			default:
				$options .= '<option value="'.$role["roleID"].'">'.$role["roleName"].'</option>';
				break;
		}
	}
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("addMod").'</h1>
    <form class="form__inner form__create" method="post" action="">
    <p>'.$dl->getLocalizedString("addModDesc").'</p>
    <div class="field"><input type="text" name="user" id="p1" placeholder="' . $dl->getLocalizedString("banUserID") . '"></div>
	<div id="selecthihi">
	<select name="role">
		'.$options.'
	</select>
	</div>
	', 'mod');
		Captcha::displayCaptcha();
        echo '
        <button type="submit" id="submit" class="btn-primary btn-block" disabled>' . $dl->getLocalizedString("addMod") . '</button>
    </form>
    </div><script>
$(document).change(function(){
   const p1 = document.getElementById("p1");
   const btn = document.getElementById("submit");
   if(!p1.value.trim().length) {
                btn.disabled = true;
                btn.classList.add("btn-block");
                btn.classList.remove("btn-primary");
	} else {
		        btn.removeAttribute("disabled");
                btn.classList.remove("btn-block");
                btn.classList.remove("btn-size");
                btn.classList.add("btn-primary");
	}
});
</script>';
}
} else
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
    <form class="form__inner" method="post" action=".">
		<p>'.$dl->getLocalizedString("noPermission").'</p>
	        <button type="submit" class="btn-primary">'.$dl->getLocalizedString("Kish!").'</button>
    </form>
</div>');
?>