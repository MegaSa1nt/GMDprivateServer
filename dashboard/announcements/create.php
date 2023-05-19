<?php
try {
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
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $dl->title($dl->getLocalizedString("createAnnouncement"));
    $dl->printFooter('../');
    if($gs->checkPermission($_SESSION["accountID"], "announcementCreate")){
        $accountID = $_SESSION["accountID"];
        $accountName = $gs->getAccountName($accountID);
        if(!empty($_POST["user"])) {
            if(!Captcha::validateCaptcha()) {
                $dl->printSong('<div class="form">
                    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
                    <form class="form__inner" method="post" action="">
                    <p>'.$dl->getLocalizedString("invalidCaptcha").'</p>
                    <button type="button" onclick="a(\'announcements.php\', true, false, \'GET\')" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
                    </form>
                    </div>', 'announcement');
                die();
            }
            $announcement = ExploitPatch::remove($_POST["announcement"]);
            if(!is_string($announcement)) {
                $dl->printSong('<div class="form">
                <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
                <form class="form__inner" method="post" action="">
                <p>'.$dl->getLocalizedString("invalidPost").'</p>
                <button type="button" onclick="a(\'announcements/create.php\', true, false, \'GET\')" class="btn-song">'.$dl->getLocalizedString("tryAgainBTN").'</button>
                </form>
            </div>', 'announcement');
            die();
            }
            $query = db-> prepare("INSERT INTO announcements (announcement, authorID, authorName, timestamp) VALUES (".$announcement.", ".$accountID.", ".$accountName.", ".time().")");
            $query->execute();
            $success = $dl->getLocalizedString("createdAnnouncement").' <b>'.$mod."</b>!";
            $dl->printSong('<div class="form">
            <h1>'.$dl->getLocalizedString("createAnnouncement").'</h1>
            <form class="form__inner" method="post" action="">
                <p>'.$success.'</p>
                <button type="button" onclick="a(\'announcements/create.php\', true, false, \'GET\')" class="btn-primary">'.$dl->getLocalizedString("addOneMoreAnnouncement").'</button>
                <button type="button" onclick="a(\'announcements\', true, false, \'GET\')" class="btn-primary">'.$dl->getLocalizedString("viewAllAnnouncements").'</button>
            </form>
        </div>', 'mod');
        } else {
            $dl->printSong('<div class="form">
            <h1>'.$dl->getLocalizedString("createAnnouncement").'</h1>
            <form class="form__inner" method="post" action="" enctype="multipart/form-data">
                <p>'.$dl->getLocalizedString("createAnnouncementDesc").'</p>
                <div class="field"><input type="text" name="announcement" id="announcement-bar" placeholder="'.$dl->getLocalizedString("announcement").'"></div>', 'announcement');
            Captcha::displayCaptcha();
            echo '<button type="button" onclick="a(\'announcements/create.php\', true, false, \'POST\');" style="margin-top:5px;margin-bottom:5px" type="submit" id="submit" class="btn-song btn-block" disabled>'.$dl->getLocalizedString("create").'</button>
            </form>
            </div>
            <script>
            $(document).on("keyup keypress change keydown",function(){
                const announcebar = document.getElementById("announcement-bar");
                const btn = document.getElementById("submit");
                if(!announcebar.value.trim().length) {
                             btn.disabled = true;
                             btn.classList.add("btn-block");
                             btn.classList.remove("btn-song");
                 } else {
                             btn.removeAttribute("disabled");
                             btn.classList.remove("btn-block");
                             btn.classList.remove("btn-size");
                             btn.classList.add("btn-song");
                 }
             })</script>';
        }

    }
} catch (Exception $e) {
    echo "<script>console.log('".$e->getMessage()."')</script>";
}
?>