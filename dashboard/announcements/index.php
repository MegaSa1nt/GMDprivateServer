<?php
try {
  session_start();
  require "../incl/dashboardLib.php";
  require "../".$dbPath."incl/lib/connection.php";
  require_once "../".$dbPath."incl/lib/exploitPatch.php";
  error_reporting(0);
  $dl = new dashboardLib();
  require_once "../".$dbPath."incl/lib/mainLib.php";
  $gs = new mainLib();
  include "../".$dbPath."incl/lib/connection.php";
  $dl->title($dl->getLocalizedString("announcements"));
  $dl->printFooter('../');
  if(isset($_GET["page"]) AND is_numeric($_GET["page"]) AND $_GET["page"] > 0){
    $page = ($_GET["page"] - 1) * 10;
    $actualpage = $_GET["page"];
  }else{
    $page = 0;
    $actualpage = 1;
  }
  $pagelol = explode("/", $_SERVER["REQUEST_URI"]);
  $pagelol = $pagelol[count($pagelol)-2]."/".$pagelol[count($pagelol)-1];
  $pagelol = explode("?", $pagelol)[0];
  if(!isset($_GET["search"])) $_GET["search"] = "";
  if(!isset($_GET["type"])) $_GET["type"] = "";
  if(!isset($_GET["ng"])) $_GET["ng"] = "";
  $srcbtn = $announcements = "";
  if(!isset($_GET["search"]) OR empty(trim(ExploitPatch::remove($_GET["search"])))) {
    $query = $db->prepare("SELECT * FROM announcements ORDER BY announcementID DESC LIMIT 10 OFFSET $page");
    $query->execute();
    $result = $query->fetchAll();
    if(empty($result)) {
      $dl->printSong('<div class="form">
      <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
      <form class="form__inner" method="post" action=".">
        <p>'.$dl->getLocalizedString("emptyPage").'</p>
        <button type="button" onclick="a(\'\', true, false, \'GET\')" class="btn-primary">'.$dl->getLocalizedString("dashboard").'</button>
      </form>
    </div>');
      die();
    } 
  } else {
    $srcbtn = '<button type="button" onclick="a(\''.$pagelol.'\', true, true, \'GET\')"  href="'.$_SERVER["SCRIPT_NAME"].'" style="width: 0%;display: flex;margin-left: 5px;align-items: center;justify-content: center;color: indianred; text-decoration:none" class="btn-primary" title="'.$dl->getLocalizedString("searchCancel").'"><i class="fa-solid fa-xmark"></i></button>';
    $query = $db->prepare("SELECT * FROM announcements WHERE announcement LIKE '%".trim(ExploitPatch::remove($_GET["search"]))."%' ORDER BY announcementID DESC LIMIT 10 OFFSET $page");
    $query->execute();
    $result = $query->fetchAll();
    if(empty($result)) {
      $dl->printSong('<div class="form">
      <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
      <form class="form__inner" method="post" action="'.$_SERVER["SCRIPT_NAME"].'">
        <p>'.$dl->getLocalizedString("emptySearch").'</p>
        <button type="button" onclick="a(\'announcements\', true, false, \'GET\')" class="btn-primary">'.$dl->getLocalizedString("tryAgainBTN").'</button>
      </form>
    </div>');
      die();
    }
  }
  $x = $page + 1;
  foreach ($result as &$action) {
    $authorName = $action['authorName'];
    $announcementID = $action['announcementID'];
    $announcement = str_replace("\n", "<br>", $action['announcement']);
    $time = date("F j, Y, g:i:s a", $action["timestamp"]);
    header('Content-Type: image/png');
    $image = '../images/'.$action["imageID"].'.'.$action["imageType"];
    if(file_exists($image)) $image = '<div class="form-control" style="display: flex;width: 100%;height: max-content;align-items: center;border-radius: 0px;background-color: transparent;border: 0px transparent;"><img style="position:relative;width:100%;max-width:100%;" src="./images/'.$action["imageID"].'.png"></img></div>'; else $image = "";
    $announcements .= '<div style="width: 100%; height: auto; display: flex;flex-wrap: wrap;justify-content: center;">
    <div class="profile"><div style="display: flex;width: 100%;justify-content: space-between;margin-bottom: 7px;align-items: center;"><button style="display:contents;cursor:pointer" type="button" onclick="a(\'profile/'.$a.'\', true, true, \'GET\')"><div style="display: flex;width: 100%; justify-content: space-between;align-items: center;">
      <h2 style="margin: 0px;font-size: 27px;margin-left:5px;display: flex;align-items: center;" class="profilenick">'.$authorName.'</h2>
    </div></button></div>
    <div class="form-control" style="display: flex;width: 100%;height: max-content;align-items: center;"><h2>'.$announcement.'</h2></div>
    '.$image.'
    <div style="display: flex;justify-content: space-between;margin-top: 10px;"><h3 id="comments" style="margin: 0px;width: max-content;"></h3><h3 id="comments" style="justify-content: flex-end;grid-gap: 0.5vh;margin: 0px;width: max-content;"><b>'.$time.'</b></h3></div>
  </div></div>';
    $x++;
  }
  
  $pagel = '<div class="form new-form" style="width:50%;max-width:50%;">
  <h1 style="margin-bottom:5px">'.$dl->getLocalizedString("announcements").'</h1>
  <div class="form-control new-form-control">
      '.$announcements.'
    </div></div><form name="searchform" class="form__inner">
    <div class="field" style="display:flex">
      <input id="searchinput" style="border-top-right-radius: 0;border-bottom-right-radius: 0;" type="text" name="search" value="'.$_GET["search"].'" placeholder="'.$dl->getLocalizedString("search").'">
      <button id="searchbutton" type="button" onclick="a(\''.$pagelol.'\', true, true, \'GET\', 69)" style="width: 6%;border-top-left-radius:0px !important;border-bottom-left-radius:0px !important" type="submit" class="btn-primary" title="'.$dl->getLocalizedString("search").'"><i class="fa-solid fa-magnifying-glass"></i></button>
      '.$srcbtn.'
    </div>
  </form>';
  if(empty(trim(ExploitPatch::remove($_GET["search"])))) $query = $db->prepare("SELECT count(*) FROM announcements");
  else $query = $db->prepare("SELECT count(*) FROM announcements WHERE announcement LIKE '%".trim(ExploitPatch::remove($_GET["search"]))."%'");
  $query->execute();
  $packcount = $query->fetchColumn();
  $pagecount = ceil($packcount / 10);
  $bottomrow = $dl->generateBottomRow($pagecount, $actualpage);
  $dl->printPage($pagel . $bottomrow, true, "browse");
} catch (Exception $e ) {
  echo '<script>console.log("'.$e->getMessage().'")</script>';
}
?>