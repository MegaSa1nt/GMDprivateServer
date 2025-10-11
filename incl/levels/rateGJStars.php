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
$stars = abs(Escape::number($_POST['stars']));
$ratingArray = [0, 1, 2, 3, 3, 4, 4, 5, 5, 5];
$ratingNumber = $ratingArray[$stars - 1] ?? 0;

$level = Library::getLevelByID($levelID);
if(!$level) exit(CommonError::InvalidRequest);

switch(true) {
	case Library::checkPermission($person, 'gameRateLevel'):
		$featured = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
		
		Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating($ratingNumber, ($stars == 1), ($stars == 10)), $stars, ($level['coins'] > 0 ? 1 : 0), $featured);
		
		exit(CommonError::Success);
	case $normalLevelsVotes:
		if($level['starStars']) exit(CommonError::InvalidRequest);
		
		Library::voteForLevelDifficulty($levelID, $person, $ratingNumber);
		
		exit(CommonError::Success);
}

exit(CommonError::InvalidRequest);
?>