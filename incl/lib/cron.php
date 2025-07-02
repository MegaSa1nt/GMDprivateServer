<?php
class Cron {
	public static function autoban($person, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 39 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$levelStats = $db->prepare("SELECT
			212 + IFNULL(stars.stars, 0) + IFNULL(dailyStars.stars, 0) + IFNULL(eventStars.stars, 0) + IFNULL(gauntletStars.stars, 0) + IFNULL(mappackStars.stars, 0) AS stars,
			10 + IFNULL(coins.coins, 0) + IFNULL(dailyCoins.coins, 0) + IFNULL(eventCoins.coins, 0) + IFNULL(gauntletCoins.coins, 0) AS coins,
			3 + IFNULL(demons.demons, 0) + IFNULL(dailyDemons.demons, 0) + IFNULL(eventDemons.demons, 0) + IFNULL(gauntletDemons.demons, 0) AS demons,
			25 + IFNULL(moons.moons, 0) + IFNULL(dailyMoons.moons, 0) + IFNULL(eventMoons.moons, 0) + IFNULL(gauntletMoons.moons, 0) AS moons 
			FROM (
				-- Levels table
				(SELECT SUM(starStars) AS stars FROM levels WHERE levelLength != 5 AND isDeleted = 0) stars
				JOIN (SELECT SUM(coins) AS coins FROM levels WHERE starCoins > 0 AND isDeleted = 0) coins
				JOIN (SELECT SUM(starDemon) AS demons FROM levels WHERE starStars > 0 AND isDeleted = 0) demons
				JOIN (SELECT SUM(starStars) AS moons FROM levels WHERE levelLength = 5 AND isDeleted = 0) moons
				
				-- Daily/Weekly levels
				JOIN (SELECT SUM(starStars) as stars FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levels.levelLength != 5 AND levels.isDeleted = 0) dailyStars
				JOIN (SELECT SUM(coins) as coins FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levels.starCoins > 0 AND levels.isDeleted = 0) dailyCoins
				JOIN (SELECT SUM(starDemon) as demons FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levels.starStars > 0 AND levels.isDeleted = 0) dailyDemons
				JOIN (SELECT SUM(starStars) as moons FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levels.levelLength = 5 AND levels.isDeleted = 0) dailyMoons
				
				-- Event levels
				JOIN (SELECT SUM(starStars) as stars FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levels.levelLength != 5 AND levels.isDeleted = 0) eventStars
				JOIN (SELECT SUM(coins) as coins FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levels.starCoins > 0 AND levels.isDeleted = 0) eventCoins
				JOIN (SELECT SUM(starDemon) as demons FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levels.starStars > 0 AND levels.isDeleted = 0) eventDemons
				JOIN (SELECT SUM(starStars) as moons FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levels.levelLength = 5 AND levels.isDeleted = 0) eventMoons
				
				-- Map Packs
				JOIN (SELECT SUM(stars) as stars FROM mappacks) mappackStars
				
				-- Gauntlets
				JOIN (SELECT SUM(starStars) as stars FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levels.levelLength != 5 AND levels.isDeleted = 0) gauntletStars
				JOIN (SELECT SUM(coins) as coins FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levels.starCoins > 0 AND levels.isDeleted = 0) gauntletCoins
				JOIN (SELECT SUM(starDemon) as demons FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levels.starStars > 0 AND levels.isDeleted = 0) gauntletDemons
				JOIN (SELECT SUM(starStars) as moons FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levels.levelLength = 5 AND levels.isDeleted = 0) gauntletMoons
			)");
		$levelStats->execute();
		$levelStats = $levelStats->fetch();
		
		$stars = $levelStats['stars'];
		$coins = $levelStats['coins'];
		$demons = $levelStats['demons'];
		$moons = $levelStats['moons']; 
		
		$getCheaters = $db->prepare("SELECT userID FROM users WHERE stars > :stars OR demons > :demons OR userCoins > :coins OR moons > :moons OR stars < 0 OR demons < 0 OR coins < 0 OR userCoins < 0 OR diamonds < 0 OR moons < 0");
		$getCheaters->execute([':stars' => $stars, ':demons' => $demons, ':coins' => $coins, ':moons' => $moons]);
		$getCheaters = $getCheaters->fetchAll();
		
		foreach($getCheaters AS &$ban) {
			$getUser = Library::getUserByID($ban['userID']);
			$maxText = 'MAX: ⭐'.$stars.' • 🌙'.$moons.' • 👿'.$demons.' • 🪙'.$coins.' | USER: ⭐'.$getUser['stars'].' • 🌙'.$getUser['moons'].' • 👿'.$getUser['demons'].' • 🪙'.$getUser['userCoins'];
		
			$banPerson = [
				'accountID' => $getUser['extID'],
				'userID' => $ban['userID'],
				'IP' => $getUser['IP'],
			];
			
			Library::banPerson(0, $banPerson, "You're too good at gaining stats.", 0, 1, 2147483647, $maxText);
		}
		
		Library::logAction($person, Action::CronAutoban, $stars, $coins, $demons, $moons, count($getCheaters));
		return true;
	}
	
	public static function updateCreatorPoints($person, $checkForTime) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 40 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$people = [];
		
		$unlistedQuery = !$unlistedCreatorPoints ? 'AND unlisted = 0 AND unlisted2 = 0' : '';
		
		/*
			Creator Points for rated levels
		*/
		
		$rateCreatorPoints = $db->prepare("UPDATE users
			LEFT JOIN
			(
				SELECT usersTable.userID, (IFNULL(starredTable.starred, 0) + IFNULL(featuredTable.featured, 0) + (IFNULL(epicTable.epic,0))) as CP FROM (
					SELECT userID FROM users
				) AS usersTable
				LEFT JOIN
				(
					SELECT count(*) as starred, userID FROM levels WHERE starStars != 0 AND isCPShared = 0 AND isDeleted = 0 ".$unlistedQuery." GROUP BY(userID) 
				) AS starredTable ON usersTable.userID = starredTable.userID
				LEFT JOIN
				(
					SELECT count(*) as featured, userID FROM levels WHERE starFeatured != 0 AND isCPShared = 0 AND isDeleted = 0 ".$unlistedQuery." GROUP BY(userID) 
				) AS featuredTable ON usersTable.userID = featuredTable.userID
				LEFT JOIN
				(
					SELECT SUM(starEpic) as epic, userID FROM levels WHERE starEpic != 0 AND isCPShared = 0 AND isDeleted = 0 ".$unlistedQuery." GROUP BY(userID) 
				) AS epicTable ON usersTable.userID = epicTable.userID
			) calculated
			ON users.userID = calculated.userID
			SET users.creatorPoints = IFNULL(calculated.CP, 0)");
		$rateCreatorPoints->execute();
		
		/*
			Creator Points sharing
		*/
		
		$shareCreatorPoints = $db->prepare("SELECT levelID, userID, starStars, starFeatured, starEpic FROM levels WHERE isCPShared != 0 AND isDeleted = 0 ".$unlistedQuery);
		$shareCreatorPoints->execute();
		$shareCreatorPoints = $shareCreatorPoints->fetchAll();
		
		foreach($shareCreatorPoints AS &$level) {
			$deservedcp = 0;
			
			if($level["starStars"]) $deservedcp++;
			if($level["starFeatured"]) $deservedcp++;
			if($level["starEpic"]) $deservedcp += $level["starEpic"];
			
			$shares = $db->prepare("SELECT userID FROM cpshares WHERE levelID = :levelID");
			$shares->execute([':levelID' => $level["levelID"]]);
			$shares = $shares->fetchAll();
			$shareCount = count($shares) + 1;
			
			$addCreatorPoints = $deservedcp / $shareCount;
			
			foreach($shares as &$share) $people[$share["userID"]] += $addCreatorPoints;
			$people[$level["userID"]] += $addCreatorPoints;
		}
		
		/*
			Creator Points for levels in Map Packs
		*/
		
		$mapPacksCreatorPoints = $db->prepare("SELECT levels FROM mappacks");
		$mapPacksCreatorPoints->execute();
		$mapPacksCreatorPoints = $mapPacksCreatorPoints->fetchAll();
		
		foreach($mapPacksCreatorPoints AS &$pack) {
			$levels = $db->prepare("SELECT userID FROM levels WHERE levelID IN (".$pack['levels'].") AND isDeleted = 0 ".$unlistedQuery);
			$levels->execute();
			$levels = $levels->fetchAll();
			
			foreach($levels AS &$level) $people[$level["userID"]] += 1;
		}
		
		/*
			Creator Points for levels in Gauntlets
		*/
		
		$gauntletsCreatorPoints = $db->prepare("SELECT level1, level2, level3, level4, level5 FROM gauntlets");
		$gauntletsCreatorPoints->execute();
		$gauntletsCreatorPoints = $gauntletsCreatorPoints->fetchAll();
		
		foreach($gauntletsCreatorPoints AS &$gauntlet) {
			$levels = $db->prepare("SELECT userID FROM levels WHERE levelID IN (".$gauntlet['level1'].",".$gauntlet['level2'].",".$gauntlet['level3'].",".$gauntlet['level4'].",".$gauntlet['level5'].") AND isDeleted = 0 ".$unlistedQuery);
			$levels->execute();
			$levels = $levels->fetchAll();
			
			foreach($levels AS &$level) $people[$level["userID"]] += 1;
		}
		
		/*
			Creator Points for Daily/Weekly levels
		*/
		
		$dailyCreatorPoints = $db->prepare("SELECT levelID FROM dailyfeatures WHERE timestamp < :time AND timestamp > 0");
		$dailyCreatorPoints->execute([':time' => time()]);
		$dailyCreatorPoints = $dailyCreatorPoints->fetchAll();
		
		foreach($dailyCreatorPoints AS &$daily) {
			$dailyCreatorPoint = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID AND isDeleted = 0 ".$unlistedQuery);
			$dailyCreatorPoint->execute([':levelID' => $daily["levelID"]]);
			$dailyCreatorPoint = $dailyCreatorPoint->fetch();
			
			if($dailyCreatorPoint) $people[$dailyCreatorPoint["userID"]] += 1;
		}
		
		/*
			Creator Points for Event levels
		*/
		
		$eventsCreatorPoints = $db->prepare("SELECT levelID FROM events WHERE timestamp < :time AND duration > 0");
		$eventsCreatorPoints->execute([':time' => time()]);
		$eventsCreatorPoints = $eventsCreatorPoints->fetchAll();
		
		foreach($eventsCreatorPoints AS &$event) {
			$eventCreatorPoints = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID AND isDeleted = 0 ".$unlistedQuery);
			$eventCreatorPoints->execute([':levelID' => $event["levelID"]]);
			$eventCreatorPoints = $eventCreatorPoints->fetch();
			
			if($eventCreatorPoints) $people[$eventCreatorPoints["userID"]] += 1;
		}
		
		/*
			Done
		*/
		
		foreach($people AS $user => $cp) {
			$updateCreatorPoints = $db->prepare("UPDATE users SET creatorPoints = creatorPoints + :creatorPoints WHERE userID = :userID");
			$updateCreatorPoints->execute([':userID' => $user, ':creatorPoints' => $cp]);
		}
		
		Library::logAction($person, Action::CronCreatorPoints);
		return true;
	}
	
	public static function fixUsernames($person, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 41 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$fixUsernames = $db->prepare("UPDATE users
			INNER JOIN accounts ON accounts.accountID = users.extID
			SET users.userName = accounts.userName
			WHERE users.extID REGEXP '^-?[0-9]+$'
			AND LENGTH(accounts.userName) <= 69");
		$fixUsernames->execute();
		
		Library::logAction($person, Action::CronUsernames);
		return true;
	}
	
	public static function updateFriendsCount($person, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 42 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$updateFriendsCount = $db->prepare("UPDATE accounts
			LEFT JOIN
			(
				SELECT a.person, (IFNULL(a.friends, 0) + IFNULL(b.friends, 0)) AS friends FROM (
					SELECT count(*) as friends, person1 AS person FROM friendships GROUP BY(person1) 
				) AS a
				JOIN
				(
					SELECT count(*) as friends, person2 AS person FROM friendships GROUP BY(person2) 
				) AS b ON a.person = b.person
			) calculated
			ON accounts.accountID = calculated.person
			SET accounts.friendsCount = IFNULL(calculated.friends, 0)");
		$updateFriendsCount->execute();
		
		Library::logAction($person, Action::CronFriendsCount);
		return true;
	}
	
	public static function miscFixes($person, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 43 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		/*
			Unbanning everyone who has expired ban
		*/
		
		$bans = $db->prepare('UPDATE bans SET isActive = 0 WHERE expires < :time');
		$bans->execute([':time' => time()]);
		
		/*
			Unbanning IPs
		*/
		
		$getIPBans = $db->prepare("SELECT person FROM bans WHERE personType = 2 AND banType = 4 AND isActive = 0");
		$getIPBans->execute();
		$getIPBans = $getIPBans->fetchAll();
		$IPBans = [];
		
		foreach($getIPBans AS &$ban) $IPBans[] = Library::convertIPForSearching($ban['person'], true);
		
		$bannedIPsString = implode("|", $IPBans);
		$unbanIPs = $db->prepare("DELETE FROM bannedips WHERE IP REGEXP '((\\\D[^.])|^)(".$bannedIPsString.")(\\\D[^$])'");
		$unbanIPs->execute();
		
		Library::logAction($person, Action::CronMisc);
		return true;
	}
	
	public static function updateSongsUsage($person, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 44 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		/*
			Clear songs usage
		*/
		
		$db->query("UPDATE songs SET levelsCount = 0, favouritesCount = 0");
		$db->query("UPDATE sfxs SET levelsCount = 0");
		
		/*
			Update songs usage
		*/
		
		$songsUsage = $db->prepare("UPDATE songs
			JOIN (
				SELECT count(*) AS songCount, songID FROM levels
				INNER JOIN songs ON songs.ID = levels.songID AND songs.isDisabled = 0 WHERE songID > 0 AND isDeleted = 0
				GROUP BY songID
			) songsUsage
			SET levelsCount = songsUsage.songCount
			WHERE ID = songsUsage.songID");
		$songsUsage = $songsUsage->execute();
		$songsFavourites = $db->prepare("UPDATE songs
			JOIN (
				SELECT songs.ID AS songID, favCount FROM songs
				JOIN (SELECT count(*) AS favCount, songID FROM favsongs GROUP BY favsongs.songID) favs ON favs.songID = songs.ID
				WHERE songs.isDisabled = 0
			) songsFavourites
			SET favouritesCount = songsFavourites.favCount
			WHERE ID = songsFavourites.songID");
		$songsFavourites = $songsFavourites->execute();
		
		$levels = $db->prepare("SELECT songIDs, sfxIDs FROM levels WHERE (songIDs != '' OR sfxIDs != '') AND isDeleted = 0");
		$levels->execute();
		$levels = $levels->fetchAll();
		
		$songsUsage = $sfxsUsage = [];
		
		/*
			Count songs and SFXs usage
		*/
		
		foreach($levels AS &$level) {
			$extraSongs = explode(',', $level['songIDs']);
			foreach($extraSongs AS &$song) {
				if(empty($song)) continue;
				
				$extraSong = Library::getSongByID($song);
				if($extraSong && $extraSong['isLocalSong']) $songsUsage[$extraSong['ID']]++;
			}
			
			$extraSFXs = explode(',', $level['sfxIDs']);
			foreach($extraSFXs AS &$sfx) {
				if(empty($sfx)) continue;
				
				$extraSFX = Library::getLibrarySongInfo($sfx, 'sfx');
				if($extraSFX && $extraSFX['isLocalSFX']) $sfxsUsage[$extraSFX['originalID']]++;
			}
		}
		
		/*
			Add this info to SQL
		*/
		
		foreach($songsUsage AS $song => $usage) {
			$addInfo = $db->prepare("UPDATE songs SET levelsCount = levelsCount + :usage WHERE ID = :songID");
			$addInfo->execute([':usage' => $usage, ':songID' => $song]);
		}
		
		foreach($sfxsUsage AS $sfx => $usage) {
			$addInfo = $db->prepare("UPDATE sfxs SET levelsCount = :usage WHERE ID = :sfxID");
			$addInfo->execute([':usage' => $usage, ':sfxID' => $sfx]);
		}
		
		/*
			Get updated info count
		*/
		
		$updatedAudio = $db->prepare("SELECT * FROM (
			(SELECT count(*) AS songsCount FROM songs WHERE levelsCount > 0) songsUsage
			JOIN (SELECT count(*) AS favCount FROM songs WHERE favouritesCount > 0) songsFavourites
			JOIN (SELECT count(*) AS sfxsCount FROM sfxs WHERE levelsCount > 0) sfxs
		)");
		$updatedAudio->execute();
		$updatedAudio = $updatedAudio->fetch();
		
		Library::logAction($person, Action::CronSongsUsage, $updatedAudio['songsCount'], $updatedAudio['sfxsCount'], $updatedAudio['favCount']);
		return true;
	}
	
	public static function updateClansRanks($person, $checkForTime) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 59 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		/*
			Clear clans ranks
		*/
		
		$db->query("UPDATE clans SET clanRank = 0");
		
		/*
			Get clans leaderboard
		*/
		
		$clansStats = $db->prepare("SELECT * FROM (
				SELECT users.clanID, SUM(stars) AS stars, SUM(moons) AS moons FROM users INNER JOIN clans ON users.clanID = clans.clanID GROUP BY users.clanID ASC
			) stats
			WHERE stats.stars + stats.moons >= :leaderboardMinStars
			ORDER BY stats.stars + stats.moons DESC");
		$clansStats->execute([':leaderboardMinStars' => $leaderboardMinStars]);
		$clansStats = $clansStats->fetchAll();
		
		/*
			Update clans ranks
		*/
		
		$clanRank = 0;
		foreach($clansStats AS &$clan) {
			$clanRank++;
			
			$updateClanRank = $db->prepare("UPDATE clans SET clanRank = :clanRank WHERE clanID = :clanID");
			$updateClanRank->execute([':clanRank' => $clanRank, ':clanID' => $clan['clanID']]);
		}
		
		Library::logAction($person, Action::CronClansRanks, count($clansStats));
		return true;
	}
	
	public static function doEverything($person, $checkForTime) {
		if(
			!self::autoban($person, $checkForTime) ||
			!self::updateCreatorPoints($person, $checkForTime) ||
			!self::fixUsernames($person, $checkForTime) ||
			!self::updateFriendsCount($person, $checkForTime) ||
			!self::miscFixes($person, $checkForTime) ||
			!self::updateSongsUsage($person, $checkForTime) ||
			!self::updateClansRanks($person, $checkForTime)
		) return false;
		
		return true;
	}
}
?>
