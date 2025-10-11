<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$levelID = Escape::number($_POST['levelID']);
$rating = abs(Escape::number($_POST['rating']));
$ratingArray = [7, 8, 6, 9, 10];
$ratingNumber = $ratingArray[$rating - 1] ?? 6;

$level = Library::getLevelByID($levelID);
if(!$level) exit(CommonError::InvalidRequest);

switch(true) {
	case Library::checkPermission($person, 'gameSetDifficulty'):
		$featured = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
		
		Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating($ratingNumber), $level['starStars'], $level['starCoins'], $featured);
		
		exit(CommonError::Success);
	case $demonDifficultiesVotes:
		if($level['starStars'] != 10) exit(CommonError::InvalidRequest);
		
		Library::voteForLevelDifficulty($levelID, $person, $ratingNumber);
		
		exit(CommonError::Success);
}

exit(CommonError::InvalidRequest);
?>