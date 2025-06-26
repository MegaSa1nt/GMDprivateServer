<?php
/*
	Map-packs order in-game
	
	True — Order map-packs by their diffuculty
	False — Order map-packs by their creation date (newest to oldest)
*/
$orderMapPacksByStars = true;

/*
	SAKUJES
	
	This is April Fools joke by Cvolton, when leaderboards would fill with SAKUJES players and have 999 stats
	
	$sakujes — April Fools joke
		True — Enable this joke
		False — Keep leaderboards normal on April Fools
	$sakujesUsername — username, that will appear in leaderboards
*/
$sakujes = true;
$sakujesUsername = 'sakujes';

/*
	Count unlisted rated levels in the creator points calculation (cron.php / fixcps.php)

	Whether you want unlisted rated levels to be counted in the creator points calculation or not

	True — Count unlisted rated levels in the creator points calculation
	False — Do not count unlisted rated levels in the creator points calculation

*/
$unlistedCreatorPoints = false;

/*
	Comment length limiter
	
	This setting will enable comment length limiter to prevent flooding with scripts
	
	$enableCommentLengthLimiter:
		True — Use $maxCommentLength and $maxAccountCommentLength to limit comment length
		False — Don't limit comments by their length
	
	$maxCommentLength - Maximum level comment length, default is 100
	$maxAccountCommentLength - Maximum profile comment length, default is 140
*/
$enableCommentLengthLimiter = true;
$maxCommentLength = 100;
$maxAccountCommentLength = 140;

/*
	Daily/Weekly logic
	
	This setting refers to the situation where you did not set the new daily/weekly level.
	
	Usually, daily levels can be played for 1 day and weekly levels can be played for 1 week.
	After that these levels should 'expire' and no one can play them again (in daily/weekly tab)
	
	True —When the daily/weekly level expires, still show it for players, until the new level is set. (default)
	False — When the daily/weekly level expires, show no level and a timer, until the new level is set.
	
	Note: if you select "true" and daily level is not updated, it still can be beaten only once, then the player will see only a timer.
*/
$oldDailyWeekly = false;

/*
    Minimum and Maximum Game versions

    Set both to 0 to disable
    Only setting one of them to something else than 0 will make this limit work but not the other

    Examples: setting minimum version to 22 and maximum version to 0 won't allow versions below 2.2 but will allow versions above or equal to 2.2
    or setting maximum version to 22 and minimum version to 0 will allow versions below or equel to 2.2 but not above

    Note: setting both to the same value will only allow this specific version
*/
$minGameVersion = 0;
$maxGameVersion = 0;

/* 
    Same thing, but for binary versions, also note:
    2.207 = 44
    2.206 = 42
    2.205 = 41
    2.204 = 40
    2.203 = 39
    2.202 = 38
    2.201 = 37
    2.200 = 36
    ...
*/
$minBinaryVersion = 0;
$maxBinaryVersion = 0;

/*
	Show levels from newer Geometry Dash version
	
	This setting will allow showing levels if they were posted from newer Geometry Dash version, than yours
	
	True — Show all levels
	False — Only show levels your Geometry Dash version support
*/
$showAllLevels = true;

/*
	Amount of stars for leaderboards

	$leaderboardMinStars - Minimum amount of stars for players to be displayed in the leaderboard, default is 10
*/
$leaderboardMinStars = 10;

/*
	Update rated levels
	
	This setting allows to disable updating of rated levels
	
	$ratedLevelsUpdates:
		True — Allow updating rated levels
		False — Disallow updating rated levels
		
	$ratedLevelsUpdatesExceptions - Allow these levels to update
*/
$ratedLevelsUpdates = true;
$ratedLevelsUpdatesExceptions = [
    1,
    2
];

/*
	Multiply comment likes by value
	
	Fun setting, it will allow you to visually multiply comment likes by value you set
	
	$commentAutoLike:
		True — Enable multiplying
		False — Disable multiplying
		
	$specialCommentLikes - Comment IDs and their multipliers:
		Comment ID => Multiplier
*/
$commentAutoLike = false;
$specialCommentLikes = [
    1 => 10,
    2 => 20
];

/*
	Let GDPS administrators to see unlisted levels
	
	This setting will show unlisted levels for administrators
	
	True — Show unlisted levels
	False — Don't show unlisted levels
*/
$unlistedLevelsForAdmins = false;

/*
	Show rated levels in sent tab
	
	This setting will show rated levels in sent tab
	
	True — Show rated levels in sent tab
	False — Don't show rated levels in sent tab
*/
$ratedLevelsInSent = false;

/*
	Show moderators list in-game
	
	This setting replaces global leaderboard with moderators list
	https://github.com/MegaSa1nt/GMDprivateServer/issues/181
	
	True — Replace global leaderboard with moderators list
	False — Keep global leaderboard
*/
$moderatorsListInGlobal = false;

/*
	Run Cron automatically
	
	This setting will enable automatic Cron
	
	True — Cron should run automatically
	False — Cron should run manually in dashboard
*/
$automaticCron = false;

/*
	Show level ID in comments if mentioned
	
	Players can mention levels in comments and other places by writing #levelID
	Example: "Did you play #648? It's cool!"
	Minor issue is you can't open player's account anymore
	
	True — You can mention levels in comments and it will show first mentioned level
	False — Comments in levels will never show level ID button
*/
$mentionLevelsInComments = true;

/*
	Difficulty votes
	
	Enable community votes for levels difficulty
	
	$normalLevelsVotes — should players be able to vote for difficulties on unrated levels
		True — difficulty face votings are enabled
		False — difficulty face votings are disabled
	$demonDifficultiesVotes — should players be able to vote for demon difficulty faces on rated levels
		True — voting for demon faces is enabled
		False — voting for demon faces is disabled
*/
$normalLevelsVotes = true;
$demonDifficultiesVotes = true;

/*
	Search for user and account levels
	
	Before Geometry Dash 2.0 players could search levels of other players by searching `uUSER_ID`
	Should GDPS core allow searching levels that way? This also will enable searching `aACCOUNT_ID`
	
	Example: u16, a71
*/
$enableUserLevelsSearching = true;

/*
	Show top artists from real Geometry Dash
	
	GDPS core has favourite songs feature, but if you dont want to show them in-game, you can turn on this config
	
	True — real Geometry Dash will show instead of showing player's favourite songs
	False — favourite songs will show in top artists page
*/
$topArtistsFromGD = false;

/*
	Disallow rating your own levels
	
	Moderators with rate permissions can rate any levels, but what if you need to disallow them rating their own levels?
	
	True — moderators are not allowed to rate their own levels
	False — moderators can rate their own levels
*/
$dontRateYourOwnLevels = false;

/*
	Show creator comment rating
	
	Creators can like or dislike comments on their levels, should GDPS core show their rating?
	
	True — Show creator comment rating
	False — Hide creator comment rating
*/
$showCreatorRating = true;

/*
	Level update lock also disallows deleting level
	
	Should levels with locked updating be locked from deleting too?
	
	True — Disallow deleting if level is update locked
	False — Allow deleting if level is update locked
*/
$disallowDeletingUpdateLockedLevel = true;

/*
	Banning person from uploading levels also disallows deleting level
	
	Should person banned from uploading levels be not able to delete levels too?
	
	True — Disallow deleting if person is banned from uploading levels
	False — Allow deleting if person is banned from uploading levels
*/
$disallowDeletingLevelByBannedPerson = true;

/*
	Save level versions
	
	Should core save old level versions? This can be useful if person got hacked or maliciously updated their level
	
	$saveLevelVersions — save level versions when person updates their level
		True — enable saving level versions
		False — disable saving level versions
	$maxLevelVersionsSaves — how much level versions should be stored? Once limit is reached, core will delete older level versions
*/
$saveLevelVersions = true;
$maxLevelVersionsSaves = 10;

/*
	"Force" flag when running dangerous commands
	
	Should core ask for -f flag when running some dangerous commands? (!delete, !setacc, etc)
	
	True — core requires adding -f flag to execute dangerous commands
	False — you can run dangerous commands without -f flag
*/
$forceCommandFlag = true;
?>