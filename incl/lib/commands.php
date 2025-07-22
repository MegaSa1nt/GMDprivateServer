<?php
class Commands {
	public static function processLevelCommand($comment, $level, $person) {
		require __DIR__.'/../../config/misc.php';
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/security.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$levelID = $level['levelID'];
		
		$commentSplit = explode(' ', $comment);
		$increaseSplit = 0;
		$command = $commentSplit[0];
		
		$forceFlagSet = false;
		
		if(strtolower($commentSplit[1]) == '-f') {
			$forceFlagSet = true;
			array_splice($commentSplit, 1, 1);
		}
		
		switch($command) {
			case '!rate':
			case '!r':
				if(!Library::checkPermission($person, 'commandRate')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";

				$difficulty = Escape::latin($commentSplit[1]);
				if(!is_numeric($commentSplit[2])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[1 + $increaseSplit]);
				}
				$stars = abs(Escape::number($commentSplit[2 + $increaseSplit]));
				$verifyCoins = Security::limitValue(0, Escape::number($commentSplit[3 + $increaseSplit]), 1);
				$featured = Security::limitValue(0, Escape::number($commentSplit[4 + $increaseSplit]), 4);
				
				if(!$difficulty || !is_numeric($stars) || !is_numeric($verifyCoins) || !is_numeric($featured)) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!rate ".Library::textColor("*difficulty* *stars* *are coins verified* *featured/epic/legendary/mythic*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!rate harder 7 1 4", Color::LightYellow);
				}

				if(!$stars) return "Please use ".Library::textColor("!unrate", Color::Red)." to unrate level.";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You ".Library::textColor("can't", Color::Red)." rate your own level.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to rate ".Library::textColor($level['levelName'], Color::SkyBlue).' to '.$stars .' star'.($stars > 1 ? 's' : '').'?'.PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$rateLevel = Library::rateLevel($levelID, $person, $difficulty, $stars, $verifyCoins, $featured);
				
				return "You ".Library::textColor("successfully", Color::Green)." rated ".Library::textColor($level['levelName'], Color::SkyBlue).' as '.Library::textColor($rateLevel, Color::Yellow).', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				if(!Library::checkPermission($person, 'commandRate')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You ".Library::textColor("can't", Color::Red)." unrate your own level.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to unrate ".Library::textColor($level['levelName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), 0, 0, 0);
				
				return "You ".Library::textColor("successfully", Color::Green)." unrated ".Library::textColor($level['levelName'], Color::SkyBlue).'!';
			case '!feature':
			case '!epic':
			case '!legendary':
			case '!mythic':
			case '!unfeature':
			case '!unepic':
			case '!unlegendary':
			case '!unmythic':
			case '!fea':
			case '!f':
			case '!epi':
			case '!leg':
			case '!myt':
			case '!unfea':
			case '!unf':
			case '!unepi':
			case '!unleg':
			case '!unmyt':
				$commandArray = [
					'!feature' => 1, '!fea' => 1, '!f' => 1,
					'!epic' => 2, '!epi' => 2,
					'!legendary' => 3, '!leg' => 3,
					'!mythic' => 4, '!myt' => 4,
					'!unfeature' => 0, '!unfea' => 0, '!unf' => 0,
					'!unepic' => 0, '!unepi' => 0,
					'!unlegendary' => 0, '!unleg' => 0,
					'!unmythic' => 0, '!unmyt' => 0
				];
				$returnTextArray = ['unfeatured %1$s!', 'featured %1$s!', 'set %1$s as epic!', 'set %1$s as legendary!', 'set %1$s as mythic!'];
				$featured = $commandArray[$command];
				
				$featurePermission = $featured < 2 && $level['starEpic'] == 0 ? 'Feature' : 'Epic';
				if(!Library::checkPermission($person, 'command'.$featurePermission)) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You ".Library::textColor("can't", Color::Red)." ".(!$featured ? 'un' : '')."feature your own level.";
				
				if(($featured == 1 && $level['starFeatured'] && !$level['starEpic']) || ($featured - 1 == $level['starEpic'])) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".($featured ? '' : 'un')."featured!";
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), $level['starStars'], $level['starCoins'], $featured);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".sprintf($returnTextArray[$featured], Library::textColor($level['levelName'], Color::SkyBlue));
			case '!verifycoins':
			case '!unverifycoins':
			case '!vc':
			case '!unvc':
				if(!Library::checkPermission($person, 'commandVerifycoins')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				$commandArray = [
					'!verifycoins' => 1, '!vc' => 1,
					'!unverifycoins' => 0, '!unvc' => 0
				];
			
				$returnTextArray = ['unverified coins in %1$s!', 'verified coins in %1$s!'];
				$verifyCoins = $commandArray[$command];
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You ".Library::textColor("can't", Color::Red)." ".(!$verifyCoins ? 'un' : '')."verify coins on your own level.";
				
				if($verifyCoins == $level['starCoins']) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." ".(!$verifyCoins ? 'un' : '')."verified coins!";
				
				$featured = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), $level['starStars'], $verifyCoins, $featured);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".sprintf($returnTextArray[$verifyCoins], Library::textColor($level['levelName'], Color::SkyBlue));
			case '!daily':
			case '!weekly':
			case '!da':
			case '!w':
				$typeArray = [
					'!daily' => 0, '!da' => 0,
					'!weekly' => 1, '!w' => 1
				];
				$type = $typeArray[$command];
				
				$dailyPermission = $type ? 'Weekly' : 'Daily';
				if(!Library::checkPermission($person, 'command'.$dailyPermission)) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to make ".Library::textColor($level['levelName'], Color::SkyBlue)." ".($type ? 'weekly' : 'daily')."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$setDaily = Library::setLevelAsDaily($levelID, $person, $type);
				if(!$setDaily) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".($type ? 'weekly' : 'daily')."!";
				
				return "You ".Library::textColor("successfully", Color::Green)." set ".Library::textColor($level['levelName'], Color::SkyBlue)." as ".($type ? 'weekly' : 'daily')."!".PHP_EOL
					."It will appear ".Library::textColor(Library::makeTime($setDaily), Color::Yellow).'.';
			case '!undaily':
			case '!unda':
			case '!unweekly':
			case '!unw':
				$typeArray = [
					'!undaily' => 0, '!unda' => 0,
					'!unweekly' => 1, '!unw' => 1
				];
				$type = $typeArray[$command];
				
				$dailyPermission = $type ? 'Weekly' : 'Daily';
				if(!Library::checkPermission($person, 'command'.$dailyPermission)) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to remove ".Library::textColor($level['levelName'], Color::SkyBlue)." from ".($type ? 'weekly' : 'daily')." levels?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$removeDaily = Library::removeDailyLevel($levelID, $person, $type);
				if(!$removeDaily) return Library::textColor($level['levelName'], Color::SkyBlue)." is not ".($type ? 'weekly' : 'daily')." level!";
				
				return "You ".Library::textColor("successfully", Color::Green)." removed ".Library::textColor($level['levelName'], Color::SkyBlue)." from ".($type ? 'weekly' : 'daily')." levels!";
			case '!event':
			case '!ev':
				if(!Library::checkPermission($person, 'commandEvent')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				if(!is_numeric($commentSplit[1])) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!event ".Library::textColor("*duration in minutes* *reward type* *reward amount*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!event 60 7 1000 8 20 1001 379", Color::LightYellow);
				}
				$duration = abs(Escape::number($commentSplit[1])) * 60;
				unset($commentSplit[0], $commentSplit[1]);
				$rewards = implode(",", $commentSplit);
				
				if(!$duration || $duration < 0 || !$rewards || $rewards != Escape::multiple_ids($rewards)) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!event ".Library::textColor("*duration in minutes* *reward type* *reward amount*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!event 60 7 1000 8 20 1001 379", Color::LightYellow);
				}
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to make ".Library::textColor($level['levelName'], Color::SkyBlue)." event level?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$setEvent = Library::setLevelAsEvent($levelID, $person, $duration, $rewards);
				if(!$setEvent) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." event level!";
				
				return "You ".Library::textColor("successfully", Color::Green)." set ".Library::textColor($level['levelName'], Color::SkyBlue)." as event level!".PHP_EOL
					."It will appear ".Library::makeTime($setEvent).'.';
			case "!unevent":
			case "!unev":
				if(!Library::checkPermission($person, 'commandEvent')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to remove ".Library::textColor($level['levelName'], Color::SkyBlue)." from event levels?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);

				$removeEvent = Library::removeEventLevel($levelID, $person);
				if(!$removeEvent) return Library::textColor($level['levelName'], Color::SkyBlue)." is not event level!";
				
				return "You ".Library::textColor("successfully", Color::Green)." removed ".Library::textColor($level['levelName'], Color::SkyBlue)." from event levels!";
			case '!send':
			case '!suggest':
			case '!sug':
				if(!Library::checkPermission($person, 'commandSuggest')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$difficulty = Escape::latin($commentSplit[1]);
				if(!is_numeric($commentSplit[2])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[1 + $increaseSplit]);
				}
				$stars = abs(Escape::number($commentSplit[2 + $increaseSplit]));
				$featured = abs(Escape::number($commentSplit[3 + $increaseSplit]));
				
				if(!$difficulty || !$stars || !is_numeric($featured)) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!send ".Library::textColor("*difficulty* *stars* *featured/epic/legendary/mythic*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!send harder 7 4", Color::LightYellow);
				}
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You ".Library::textColor("can't", Color::Red)." suggest your own level.";
				
				$sendLevel = Library::sendLevel($levelID, $person, $difficulty, $stars, $featured);
				if(!$sendLevel) return "You ".Library::textColor("already suggested", Color::Green)." ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
				
				return "You ".Library::textColor("successfully", Color::Green)." sent ".Library::textColor($level['levelName'], Color::SkyBlue).' as '.Library::textColor($sendLevel, Color::Yellow).', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!unsend':
			case '!unsuggest':
			case '!unsug':
				if(!Library::checkPermission($person, 'commandSuggest')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				$unsendLevel = Library::unsendLevel($levelID, $person);
				if(!$unsendLevel) return "You ".Library::textColor("haven't suggested", Color::Red)." ".Library::textColor($level['levelName'], Color::SkyBlue).'!';
				
				return "You ".Library::textColor("successfully", Color::Green)." unsent ".Library::textColor($level['levelName'], Color::SkyBlue).'!';
			case '!setacc':
			case '!account':
			case '!move':
			case '!sa':
			case '!acc':
			case '!m':
				if(!Library::checkPermission($person, 'commandSetacc')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				if(empty($commentSplit[1])) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!move ".Library::textColor("*player*", Color::Orange).PHP_EOL
						."Examples: ".PHP_EOL
						.Library::textColor("!move MegaSa1nt - move level to MegaSa1nt", Color::LightYellow).PHP_EOL
						.Library::textColor("!move 71 - move level to player with account ID 71", Color::LightYellow).PHP_EOL
						.Library::textColor("!move u5 - move level to player with user ID 5", Color::LightYellow);
				}
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was ".Library::textColor("not found", Color::Red).".";
				
				if($player['extID'] == $level['extID']) return "User ".Library::textColor($player['userName'], Color::SkyBlue)." ".Library::textColor("already owns", Color::Green)." level ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to move ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor($player['userName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::moveLevel($levelID, $person, $player);
				
				return "You ".Library::textColor("successfully", Color::Green)." moved ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor($player['userName'], Color::SkyBlue)."!";
			case '!lockUpdating':
			case '!unlockUpdating':
			case '!lu':
			case '!unlu':
				if(!Library::checkPermission($person, 'commandLockUpdating')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$lockUpdatingArray = [
					'!lockUpdating' => 1, '!lu' => 1,
					'!unlockUpdating' => 0, '!unlu' => 0
				];
				$lockUpdating = $lockUpdatingArray[$command];
				if($level['updateLocked'] == $lockUpdating) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".(!$lockUpdating ? 'un' : '')."locked!";
				
				Library::lockUpdatingLevel($levelID, $person, $lockUpdating);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".(!$lockUpdating ? 'un' : '')."locked ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
			case "!rename":
			case "!re":
				if(!Library::checkPermission($person, 'commandRename') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				unset($commentSplit[0]);
				$newLevelName = trim(Escape::latin(implode(' ', $commentSplit)));
				if(!$newLevelName) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!rename ".Library::textColor("*level name*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!rename My cool level", Color::LightYellow);
				}
				
				if($level['levelName'] == $newLevelName) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this name!";
				
				if(Security::checkFilterViolation($person, $newLevelName, 3)) return "New level name contains a ".Library::textColor("bad", Color::Red)." word.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to rename ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor($newLevelName, Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::renameLevel($levelID, $person, $newLevelName);
				
				return "You ".Library::textColor("successfully", Color::Green)." renamed ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".$newLevelName."!";
			case "!password":
			case "!pass":
			case "!p":
				if(!Library::checkPermission($person, 'commandPass') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if(!is_numeric($commentSplit[1]) || strlen($commentSplit[1]) > 6) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!password ".Library::textColor("*level password*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!password 141412", Color::LightYellow).PHP_EOL
						."Maximum password length is ".Library::textColor("6 characters", Color::Green);
				}
				
				$newPassword = '1'.sprintf("%06d", abs(Escape::number($commentSplit[1])));
				if($newPassword == "1000000") $newPassword = $level['gameVersion'] > 21 ? 1 : 0;
				
				if($level['password'] == $newPassword) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this password!";
				
				if($forceCommandFlag && !$forceFlagSet) {
					if($newPassword == 1 || $newPassword == 0) return "Are you sure you want to remove password from ".Library::textColor($level['levelName'], Color::SkyBlue)."?".PHP_EOL
						.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
					
					return "Are you sure you want to change password of ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor($newPassword, Color::Yellow)."?".PHP_EOL
						.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				}
				
				Library::changeLevelPassword($levelID, $person, $newPassword);
				
				if($newPassword == 1 || $newPassword == 0) return "You ".Library::textColor("successfully", Color::Green)." removed password from ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
				return "You ".Library::textColor("successfully", Color::Green)." changed password of ".Library::textColor($level['levelName'], Color::SkyBlue).' to '.Library::textColor($newPassword, Color::Yellow)."!";
			case "!song":
			case "!s":
				if(!Library::checkPermission($person, 'commandSong') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$songID = abs(Escape::number($commentSplit[1]));
				if(!$songID) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!song ".Library::textColor("*song ID* *is custom song*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!song 1967605 1", Color::LightYellow);
				}
				$isCustomSong = Escape::number($commentSplit[2]) ? 1 : 0;
				
				if(($isCustomSong && $level["songID"] == $songID) || (!$isCustomSong && $level["audioTrack"] == $songID)) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this song!";
				
				if($isCustomSong) {
					$song = Library::getSongByID($songID);
					if(!$song) return "This song ".Library::textColor("doesn't exist", Color::Red)."!";
				} else $song = Library::getAudioTrack($songID);
				
				Library::changeLevelSong($levelID, $person, $songID, $isCustomSong);
				
				return "You ".Library::textColor("successfully", Color::Green)." changed song of ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor(Escape::translit($song['authorName'])." - ".Escape::translit($song['name']), Color::Yellow)."!";
			case "!description":
			case "!desc":
				if(!Library::checkPermission($person, 'commandDescription') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				unset($commentSplit[0]);
				$newLevelDesc = Library::escapeDescriptionCrash(trim(Escape::text(implode(' ', $commentSplit))));
				if(!$newLevelDesc) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!description ".Library::textColor("*level description*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!description This is my cool level i made in 3 hours. Please enjoy!", Color::LightYellow);
				}
				
				if(Escape::url_base64_decode($level['levelDesc']) == $newLevelDesc) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this description!";
				
				if(Security::checkFilterViolation($person, $newLevelDesc, 3)) return "New level description contains a ".Library::textColor("bad", Color::Red)." word.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to change description of ".Library::textColor($level['levelName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::changeLevelDescription($levelID, $person, $newLevelDesc);
				
				return "You ".Library::textColor("successfully", Color::Green)." changed description of ".Library::textColor($level['levelName'], Color::SkyBlue)." to:".PHP_EOL
					.Library::textColor($newLevelDesc, Color::Yellow);
			case "!public":
			case "!unlist":
			case "!friends":
			case "!pub":
			case "!unl":
			case "!fr":
				if(!Library::checkPermission($person, 'commandPublic') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$privacyArray = [
					'!public' => 0, '!pub' => 0,
					'!friends' => 1, '!fr' => 1,
					'!unlist' => 2, '!unl' => 2,
				];
				$privacyText = ['public', 'only for friends', 'unlisted'];
				$privacy = $privacyArray[$command];
				
				if($level['unlisted'] == $privacy) return Library::textColor($level['levelName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".$privacyText[$privacy]."!";
				
				Library::changeLevelPrivacy($levelID, $person, $privacy);
				
				return "You ".Library::textColor("successfully", Color::Green)." made ".Library::textColor($level['levelName'], Color::SkyBlue)." ".$privacyText[$privacy]."!";
			case "!sharecp":
			case "!share":
			case "!cp":
				if(!Library::checkPermission($person, 'commandSharecp')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				if(empty($commentSplit[1])) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!share ".Library::textColor("*player*", Color::Orange).PHP_EOL
						."Examples: ".PHP_EOL
						.Library::textColor("!share MegaSa1nt - share CP of level with MegaSa1nt", Color::LightYellow).PHP_EOL
						.Library::textColor("!share 71 - share CP of level with player with account ID 71", Color::LightYellow).PHP_EOL
						.Library::textColor("!share u5 - share CP of level with player with user ID 5", Color::LightYellow);
				}
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was ".Library::textColor("not found", Color::Red).".";
				
				if($player['extID'] == $level['extID']) return "User ".Library::textColor($player['userName'], Color::SkyBlue)." is creator of ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to share Creator Points of ".Library::textColor($level['levelName'], Color::SkyBlue)." to ".Library::textColor($player['userName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$shareCreatorPoints = Library::shareCreatorPoints($levelID, $person, $player['userID']);
				if(!$shareCreatorPoints) return "User ".Library::textColor($player['userName'], Color::SkyBlue)." have already been shared Creator Points from ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
				
				return "You ".Library::textColor("successfully", Color::Green)." shared Creator Points from ".Library::textColor($level['levelName'], Color::SkyBlue)." with user ".Library::textColor($player['userName'], Color::SkyBlue)."!";
			case '!lockComments':
			case '!unlockComments':
			case '!lc':
			case '!unlc':
				if(!Library::checkPermission($person, 'commandLockComments') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				$lockCommentingArray = [
					'!lockComments' => 1, '!lc' => 1,
					'!unlockComments' => 0, '!unlc' => 0
				];
				$lockCommenting = $lockCommentingArray[$command];
				if($level['commentLocked'] == $lockCommenting) return "Comments on ".Library::textColor($level['levelName'], Color::SkyBlue)." are already ".(!$lockCommenting ? 'un' : '')."locked!";
				
				Library::lockCommentingOnLevel($levelID, $person, $lockCommenting);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".(!$lockCommenting ? 'un' : '')."locked comments on ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
			case '!delete':
			case '!delet':
			case '!del':
			case '!d':
				if(!Library::checkPermission($person, 'commandDelete') && $person['userID'] != $level['userID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to delete ".Library::textColor($level['levelName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$deleteLevel = Library::deleteLevel($levelID, $person);
				if(!$deleteLevel) return "You ".Library::textColor("can't", Color::Red)." delete ".Library::textColor($level['levelName'], Color::SkyBlue).".";
				
				return "You ".Library::textColor("successfully", Color::Green)." deleted ".Library::textColor($level['levelName'], Color::SkyBlue)."!";
		}
		
		return "Command ".Library::textColor($command, Color::SkyBlue)." was ".Library::textColor("not found", Color::Red).".";
	}
	
	public static function processListCommand($comment, $list, $person) {
		require __DIR__.'/../../config/misc.php';
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$listID = $list['listID'];
		
		$commentSplit = explode(' ', $comment);
		$increaseSplit = 0;
		$command = $commentSplit[0];
		
		$forceFlagSet = false;
		
		if(strtolower($commentSplit[1]) == '-f') {
			$forceFlagSet = true;
			array_splice($commentSplit, 1, 1);
		}
		
		switch($command) {
			case '!rate':
			case '!r':
				if(!Library::checkPermission($person, 'commandRate')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";

				$reward = abs(Escape::number($commentSplit[1]));
				$difficulty = Escape::latin($commentSplit[2]);
				if(!is_numeric($commentSplit[3])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[2 + $increaseSplit]);
				}
				$featured = Security::limitValue(0, Escape::number($commentSplit[3 + $increaseSplit]), 1);
				$levelsCount = abs(Escape::number($commentSplit[4 + $increaseSplit]));
				
				if(empty($levelsCount)) $levelsCount = count(explode(',', $list['listlevels']));
				
				if(!is_numeric($reward) || !$difficulty || !is_numeric($featured)) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!rate ".Library::textColor("*reward amount* *difficulty* *is featured* *required levels amount to complete list*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!rate 50 harder 1 7", Color::LightYellow);
				}

				if(!$reward) return "Please use ".Library::textColor("!unrate", Color::Red)." to unrate list.";
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You ".Library::textColor("can't", Color::Red)." rate your own list.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to rate ".Library::textColor($list['listName'], Color::SkyBlue).' '.$reward .' diamond'.($reward > 1 ? 's' : '').'?'.PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				$rateList = Library::rateList($listID, $person, $reward, $difficulty, $featured, $levelsCount);
				
				return "You ".Library::textColor("successfully", Color::Green)." rated ".Library::textColor($list['listName'], Color::SkyBlue).' as '.Library::textColor($rateList, Color::Yellow).', '.$reward .' diamond'.($reward > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				if(!Library::checkPermission($person, 'commandRate')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You ".Library::textColor("can't", Color::Red)." unrate your own list.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to unrate ".Library::textColor($list['listName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::rateList($listID, $person, 0, $list['starDifficulty'], 0, 0);
				
				return "You ".Library::textColor("successfully", Color::Green)." unrated ".Library::textColor($list['listName'], Color::SkyBlue).'!';
			case '!feature':
			case '!unfeature':
			case '!fea':
			case '!unfea':
			case '!f':
			case '!unf':
				if(!Library::checkPermission($person, 'commandFeature')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				$commandArray = [
					'!feature' => 1, '!fea' => 1, '!f' => 1,
					'!unfeature' => 0, '!unfea' => 0, '!unf' => 0,
				];
				$featuredValue = $commandArray[$command];
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You ".Library::textColor("can't", Color::Red)." ".(!$featuredValue ? 'un' : '')."feature your own list.";
				
				if($featuredValue == $list['starFeatured']) return Library::textColor($list['listName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".($featuredValue ? '' : 'un')."featured!";
				
				Library::rateList($listID, $person, $list['starStars'], $list['starDifficulty'], $featuredValue, $list['countForReward']);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".(!$featuredValue ? 'un' : '')."featured ".Library::textColor($list['listName'], Color::SkyBlue).'!';
			case '!delete':
			case '!delet':
			case '!del':
			case '!d':
				if(!Library::checkPermission($person, 'commandDelete') && $person['accountID'] != $list['accountID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to delete ".Library::textColor($list['listName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::deleteList($listID, $person);
				
				return "You ".Library::textColor("successfully", Color::Green)." deleted ".Library::textColor($list['listName'], Color::SkyBlue)."!";
			case "!public":
			case "!unlist":
			case "!friends":
			case "!pub":
			case "!unl":
			case "!fr":
				if(!Library::checkPermission($person, 'commandPublic') && $person['accountID'] != $list['accountID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$privacyArray = [
					'!public' => 0, '!pub' => 0,
					'!friends' => 1, '!fr' => 1,
					'!unlist' => 2, '!unl' => 2,
				];
				$privacyText = ['public', 'only for friends', 'unlisted'];
				$privacy = $privacyArray[$command];
				
				if($list['unlisted'] == $privacy) return Library::textColor($list['listName'], Color::SkyBlue)." ".Library::textColor("is already", Color::Green)." ".$privacyText[$privacy]."!";
				
				Library::changeListPrivacy($listID, $person, $privacy);
				
				return "You ".Library::textColor("successfully", Color::Green)." made ".Library::textColor($list['listName'], Color::SkyBlue)." ".$privacyText[$privacy]."!";
			case '!setacc':
			case '!account':
			case '!move':
			case '!sa':
			case '!acc':
			case '!m':
				if(!Library::checkPermission($person, 'commandSetacc')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				if(empty($commentSplit[1])) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!move ".Library::textColor("*player*", Color::Orange).PHP_EOL
						."Examples: ".PHP_EOL
						.Library::textColor("!move MegaSa1nt - move list to MegaSa1nt", Color::LightYellow).PHP_EOL
						.Library::textColor("!move 71 - move list to player with account ID 71", Color::LightYellow).PHP_EOL
						.Library::textColor("!move u5 - move list to player with user ID 5", Color::LightYellow);
				}
				
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was ".Library::textColor("not found", Color::Red).".";
				
				if($player['extID'] == $list['accountID']) return "User ".Library::textColor($player['userName'], Color::SkyBlue)." ".Library::textColor("already owns", Color::Green)." ".Library::textColor($list['listName'], Color::SkyBlue)."!";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to move ".Library::textColor($list['listName'], Color::SkyBlue)."to ".Library::textColor($player['userName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::moveList($listID, $person, $player);
				
				return "You ".Library::textColor("successfully", Color::Green)." moved ".Library::textColor($list['listName'], Color::SkyBlue)." to ".Library::textColor($player['userName'], Color::SkyBlue)."!";
			case "!rename":
			case "!re":
				if(!Library::checkPermission($person, 'commandRename') && $person['accountID'] != $list['accountID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				unset($commentSplit[0]);
				$newListName = trim(Escape::latin(implode(' ', $commentSplit)));
				if(!$newListName) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!rename ".Library::textColor("*list name*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!rename My cool list", Color::LightYellow);
				}
				
				if($list['listName'] == $newListName) return Library::textColor($list['listName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this name!";
				
				if(Security::checkFilterViolation($person, $newLevelName, 3)) return "New list name contains a ".Library::textColor("bad", Color::Red)." word.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to rename ".Library::textColor($list['listName'], Color::SkyBlue)." to ".Library::textColor($newListName, Color::Yellow)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::renameList($listID, $person, $newListName);
				
				return "You ".Library::textColor("successfully", Color::Green)." renamed ".Library::textColor($list['listName'], Color::SkyBlue)." to ".Library::textColor($newListName, Color::Yellow)."!";
			case "!description":
			case "!desc":
				if(!Library::checkPermission($person, 'commandDescription') && $person['accountID'] != $list['accountID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				unset($commentSplit[0]);
				$newListDesc = Library::escapeDescriptionCrash(trim(Escape::text(implode(' ', $commentSplit))));
				if(!$newListDesc) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!description ".Library::textColor("*list description*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!description This is list with my favorite levels. Please enjoy!", Color::LightYellow);
				}
				
				if(Escape::url_base64_decode($list['listDesc']) == $newListDesc) return Library::textColor($list['listName'], Color::SkyBlue)." ".Library::textColor("already has", Color::Green)." this description!";
				
				if(Security::checkFilterViolation($person, $newLevelName, 3)) return "New list description contains a ".Library::textColor("bad", Color::Red)." word.";
				
				if($forceCommandFlag && !$forceFlagSet) return "Are you sure you want to change description of ".Library::textColor($list['listName'], Color::SkyBlue)."?".PHP_EOL
					.Library::textColor('Add "-f" flag after '.$command.' to execute it.', Color::Yellow);
				
				Library::changeListDescription($listID, $person, $newListDesc);
				
				return "You ".Library::textColor("successfully", Color::Green)." changed description of ".Library::textColor($list['listName'], Color::SkyBlue)." to:".PHP_EOL
					.Library::textColor($newListDesc, Color::Yellow);
			case '!lockComments':
			case '!unlockComments':
			case '!lc':
			case '!unlc':
				if(!Library::checkPermission($person, 'commandLockComments') && $person['accountID'] != $list['accountID']) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
				
				$lockCommentingArray = [
					'!lockComments' => 1, '!lc' => 1,
					'!unlockComments' => 0, '!unlc' => 0
				];
				$lockCommenting = $lockCommentingArray[$command];
				if($list['commentLocked'] == $lockCommenting) return "Comments on ".Library::textColor($list['listName'], Color::SkyBlue)." are already ".(!$lockCommenting ? 'un' : '')."locked!";
				
				Library::lockCommentingOnList($listID, $person, $lockCommenting);
				
				return "You ".Library::textColor("successfully", Color::Green)." ".(!$lockCommenting ? 'un' : '')."locked comments on ".Library::textColor($list['listName'], Color::SkyBlue)."!";
			case '!send':
			case '!suggest':
			case '!sug':
				if(!Library::checkPermission($person, 'commandSuggest')) return "You ".Library::textColor("don't have permissions", Color::Red)." to use command ".Library::textColor($command, Color::SkyBlue)."!";
			
				$reward = abs(Escape::number($commentSplit[1]));
				$difficulty = Escape::latin($commentSplit[2]);
				if(!is_numeric($commentSplit[3])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[2 + $increaseSplit]);
				}
				$featured = abs(Escape::number($commentSplit[3 + $increaseSplit]));
				$levelsCount = abs(Escape::number($commentSplit[4 + $increaseSplit]));
				
				if(empty($levelsCount)) $levelsCount = count(explode(',', $list['listlevels']));
				
				if(!is_numeric($reward) || !$difficulty || !is_numeric($featured)) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!send ".Library::textColor("*reward amount* *difficulty* *is featured* *required levels amount to complete list*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!send 50 harder 1 7", Color::LightYellow);
				}
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You ".Library::textColor("can't", Color::Red)." suggest your own list.";
				
				$sendList = Library::sendList($listID, $person, $reward, $difficulty, $featured, $levelsCount);
				if(!$sendList) return "You ".Library::textColor("already suggested", Color::Green)." ".Library::textColor($list['listName'], Color::SkyBlue)."!";
				
				return "You ".Library::textColor("successfully", Color::Green)." sent ".Library::textColor($list['listName'], Color::SkyBlue).' as '.Library::textColor($sendList, Color::Yellow).', '.$reward .' diamond'.($reward > 1 ? 's!' : '!');
		}
		
		return "Command ".Library::textColor($command, Color::SkyBlue)." was ".Library::textColor("not found", Color::Red).".";
	}
	
	public static function processProfileCommand($comment, $account, $person) {
		require __DIR__.'/../../config/discord.php';
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/discord.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$accountID = $person['accountID'];
		
		$commentSplit = explode(' ', $comment);
		$command = $commentSplit[0];
		$subCommand = $commentSplit[1];
		
		if($command != '!discord') return "Command ".Library::textColor($command, Color::SkyBlue)." was ".Library::textColor("not found", Color::Red).".";
		if(!$subCommand) return "Please specify subcommand to ".Library::textColor($command, Color::SkyBlue).".";
		
		if(!$discordEnabled) return "Linking account to Discord ".Library::textColor("is disabled", Color::Red)."!";
		
		switch($subCommand) {
			case 'link':
			case 'l':
				$discordID = abs(Escape::number($commentSplit[2]));
				if(!$discordID) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!discord link ".Library::textColor("*Discord account ID*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!discord link 297295491417505793", Color::LightYellow);
				}
				
				$link = Discord::getUserDiscord($accountID);
				if($link) return "You already linked your account with Discord ID ".$link."!";
				
				$discordAccount = Library::getAccountByDiscord($discordID);
				if($discordAccount) return "Discord ID ".$discordID." ".Library::textColor("is already", Color::Green)." linked with account ".$discordAccount['userName']."!";
				
				$startLinking = Discord::startLinkingAccount($person, $discordID);
				if(!$startLinking) return "Something went wrong when trying to send code to Discord DMs or Discord ID ".$discordID." ".Library::textColor("doesn't exist", Color::Red).".";
				
				return "Verification code and next steps were sent to ".$startLinking." (".$discordID.")!";
			case 'accept':
			case 'verify':
			case 'a':
			case 'v':
				$code = abs(Escape::number($commentSplit[2]));
				if(!$code || strlen($code) != 4) {
					return Library::textColor("Incorrect usage!", Color::Red).PHP_EOL
						."!discord verify ".Library::textColor("*Verification code*", Color::Orange).PHP_EOL
						."Example: ".Library::textColor("!discord verify 7024", Color::LightYellow);
				}
				
				$link = Discord::getUserDiscord($accountID);
				if($link) return "You ".Library::textColor("already linked", Color::Green)." your account with Discord ID ".$link."!";
				
				$verifyLinking = Discord::verifyDiscordLinking($person, $code);
				if(!$verifyLinking) return "You ".Library::textColor("didn't start", Color::Red)." linking your Discord account or code ".Library::textColor("is wrong", Color::Red).".";
				
				return "You ".Library::textColor("successfully", Color::Green)." linked your account to Discord ID ".$verifyLinking."!";
			case 'unlink':
			case 'u':
				$unlink = Discord::unlinkDiscordAccount($person);
				if(!$unlink) return "Your account ".Library::textColor("doesn't have", Color::Red)." connection with Discord!";
				
				return "You ".Library::textColor("successfully", Color::Green)." unlinked your account from Discord!";
		}
		
		return "Command ".Library::textColor($command." ".$subCommand, Color::SkyBlue)." was ".Library::textColor("not found", Color::Red).".";
	}
}
?>