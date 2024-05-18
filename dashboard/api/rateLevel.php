<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
require "../incl/dashboardLib.php";
include "../".$dbPath."incl/lib/connection.php";
include "../".$dbPath."incl/lib/mainLib.php";
require_once "../".$dbPath."incl/lib/exploitPatch.php";
require_once "../".$dbPath."incl/lib/GJPCheck.php";
$gs = new mainLib();
if(!isset($_GET)) $_GET = json_decode(file_get_contents('php://input'), true);

$starAuto = 0;

$accountID = GJPCheck::getAccountIDOrDie();

$levelID = ExploitPatch::number(urldecode($_GET['levelID'])) ?? 0;
$stars = ExploitPatch::number(urldecode($_GET['stars']));
$feature = ExploitPatch::number(urldecode($_GET['feature'])) ?? 0;
$demon = ExploitPatch::number(urldecode($_GET['demon'])) ?? 0;
$coins = ExploitPatch::number(urldecode($_GET['coins'])) ?? 0;

if(!$gs->checkPermission(ExploitPatch::number(urldecode($_GET['accountID'])), "commandRate") && !$gs->checkPermission(ExploitPatch::number(urldecode($_GET['accountID'])), "actionRateStars")) {
    http_response_code(403);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 1, 'message' => 'You do not have the necessary permissions to use this!']));
}

if($feature != 0 && !$gs->checkPermission(ExploitPatch::number(urldecode($_GET['accountID'])), "commandFeature")) {
    http_response_code(403);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 1, 'message' => 'You do not have the necessary permissions to feature/epic a level!']));
}

if($feature != 0 && !$gs->checkPermission(ExploitPatch::number(urldecode($_GET['accountID'])), "commandFeature") && !$gs->checkPermission(ExploitPatch::number(urldecode($_GET['accountID'])), "commandEpic")) {
    http_response_code(403);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 1, 'message' => 'You do not have the necessary permissions to feature a level!']));
}

if ($stars < 0 || $stars > 10) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please specify a valid amount of stars']));
}

if ($feature < 0 || $feature > 5) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please specify a valid feature state']));
}

if ($coins < 0 || $coins > 1) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please specify a valid true/false parameter']));
}

$query = $db->prepare("SELECT * FROM levels WHERE levelID = :levelID");
$query->execute([':levelID' => $levelID]);
$query = $query->fetchColumn();

if (empty($query)) {
    http_response_code(404);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 3, 'message' => 'This level does not exist!']));
}

if ($stars = 1) $starAuto = 1;

$query = $db->prepare("UPDATE levels SET starStars = :stars AND starAuto = :starAuto AND rateDate = :rateDate WHERE levelID = :levelID");
$query->execute([':stars' => $stars, ':starAuto' => $starAuto, ':rateDate' => time()]);
$gs->featureLevel($accountID, $levelID, $feature);

exit(json_encode(['dashboard' => true, 'success' => true]));
?>
