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
if(isset($_SESSION["accountID"]) AND $_SESSION["accountID"] != 0 AND $gs->checkPermission($_SESSION["accountID"], "announcementCreate")) {
	$dl->printSong("<div class='form'>
  <h1>".$dl->getLocalizedString("announcements").'</h1>
  <p>Not finished yet.</p>
  <form class="field" action="" method="post">
    <div class="form-group">
      <input type="text" class="form-control login-input" id="announcement" name="announcement" placeholder="'.$dl->getLocalizedString("enterAnnouncement").'">
    </div>
    <button type="submit" class="btn-primary btn-block" id="post" disabled>'.$dl->getLocalizedString("post").'</button>
  </form>
  <script>
    $(document).on("keyup keypress change keydown",function(){
      const p1 = document.getElementById("announcement");
      const btn = document.getElementById("post");
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
  </script>
</div>');
} else {
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
    <form class="form__inner" method="post" action=".">
		<p>'.$dl->getLocalizedString("noPermission").'</p>
	        <button type="submit" class="btn-primary">'.$dl->getLocalizedString("Kish!").'</button>
    </form>
</div>', 'mod');
}
?>