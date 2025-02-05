<?php
require_once __DIR__."/enums.php";
class Library {
	/*
		Account-related functions
	*/
	
	public function createAccount($userName, $password, $repeatPassword, $email, $repeatEmail) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/mail.php";
		require_once __DIR__."/security.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$salt = self::randomString(32);
		
		if(strlen($userName) > 20 || is_numeric($userName) || strpos($userName, " ") !== false) return ["success" => false, "error" => RegisterError::InvalidUserName];
		if(strlen($userName) < 3) return ["success" => false, "error" => RegisterError::UserNameIsTooShort];
		if(strlen($password) < 6) return ["success" => false, "error" => RegisterError::PasswordIsTooShort];
		if($password !== $repeatPassword) return ["success" => false, "error" => RegisterError::PasswordsDoNotMatch];
		if($email !== $repeatEmail) return ["success" => false, "error" => RegisterError::EmailsDoNotMatch];
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return ["success" => false, "error" => RegisterError::InvalidEmail];
		
		$userNameExists = self::getAccountIDWithUserName($userName);
		if($userNameExists) return ["success" => false, "error" => RegisterError::AccountExists];
		
		if($mailEnabled) {
			$emailExists = self::getAccountByEmail($email);
			if($emailExists) return ["success" => false, "error" => RegisterError::EmailIsInUse];
		}
		
		$gjp2 = Security::GJP2FromPassword($password);
		$createAccount = $db->prepare("INSERT INTO accounts (userName, password, email, registerDate, isActive, gjp2, salt)
			VALUES (:userName, :password, :email, :registerDate, :isActive, :gjp2, :salt)");
		$createAccount->execute([':userName' => $userName, ':password' => Security::hashPassword($password), ':email' => $email, ':registerDate' => time(), ':isActive' => $preactivateAccounts ? 1 : 0, ':gjp2' => Security::hashPassword($gjp2), ':salt' => $salt]);
		
		$accountID = $db->lastInsertId();
		$userID = self::createUser($userName, $accountID, $IP);
		
		self::logAction($accountID, $IP, 1, $userName, $email, $userID);

		// TO-DO: Readd email verification
		
		return ["success" => true, "accountID" => $accountID, "userID" => $userID];
	}
	
	public static function getAccountByUserName($userName) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE userName LIKE :userName LIMIT 1");
		$account->execute([':userName' => $userName]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function getAccountByID($accountID) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE accountID = :accountID");
		$account->execute([':accountID' => $accountID]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function getAccountByEmail($email) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE email LIKE :email ORDER BY registerDate ASC LIMIT 1");
		$account->execute([':email' => $email]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function createUser($userName, $accountID, $IP) {
		require __DIR__."/connection.php";
		
		$isRegistered = is_numeric($accountID) ? 1 : 0;
		
		$createUser = $db->prepare("INSERT INTO users (isRegistered, extID, userName, IP)
			VALUES (:isRegistered, :extID, :userName, :IP)");
		$createUser->execute([':isRegistered' => $isRegistered, ':extID' => $accountID, ':userName' => $userName, ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function getUserID($accountID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$userID = $db->prepare("SELECT userID FROM users WHERE extID = :extID");
		$userID->execute([':extID' => $accountID]);
		$userID = $userID->fetchColumn();
		
		if(!$userID) {
			$account = self::getAccountByID($accountID);
			if(!$account) return false;
			
			$IP = IP::getIP();
			$userName = $account['userName'];
			$userID = self::createUser($userName, $accountID, $IP);
		}
		
		return $userID;
	}
	
	public static function getAccountID($userID) {
		require __DIR__."/connection.php";
		
		$accountID = $db->prepare("SELECT extID FROM users WHERE userID = :userID");
		$accountID->execute([':userID' => $userID]);
		$accountID = $accountID->fetchColumn();
		
		return $accountID;
	}
	
	public static function getAccountIDWithUserName($userName) {
		require __DIR__."/connection.php";
		
		$accountID = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :userName");
		$accountID->execute([':userName' => $userName]);
		$accountID = $accountID->fetchColumn();
		
		return $accountID;
	}
	
	public static function getUserByID($userID) {
		require __DIR__."/connection.php";
		
		$user = $db->prepare("SELECT * FROM users WHERE userID = :userID");
		$user->execute([':userID' => $userID]);
		$user = $user->fetch();
		
		return $user;
	}
	
	public static function getFriendRequest($accountID, $targetAccountID) {
		require __DIR__."/connection.php";
		
		$friendRequest = $db->prepare("SELECT * FROM friendreqs WHERE accountID = :accountID AND toAccountID = :targetAccountID");
		$friendRequest->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		$friendRequest = $friendRequest->fetch();
		
		return $friendRequest;
	}
	
	public static function isFriends($accountID, $targetAccountID) {
		require __DIR__."/connection.php";

		$isFriends = $db->prepare("SELECT count(*) FROM friendships WHERE (person1 = :accountID AND person2 = :targetAccountID) OR (person1 = :targetAccountID AND person2 = :accountID)");
		$isFriends->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		
		return $isFriends->fetchColumn() > 0;
	}
	
	public static function getAccountComments($userID, $commentsPage) {
		require __DIR__."/connection.php";

		$accountComments = $db->prepare("SELECT * FROM acccomments WHERE userID = :userID ORDER BY timestamp DESC LIMIT 10 OFFSET ".$commentsPage);
		$accountComments->execute([':userID' => $userID]);
		$accountComments = $accountComments->fetchAll();
		
		$accountCommentsCount = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID");
		$accountCommentsCount->execute([':userID' => $userID]);
		$accountCommentsCount = $accountCommentsCount->fetchColumn();
		
		return ["comments" => $accountComments, "count" => $accountCommentsCount];
	}
	
	public static function uploadAccountComment($accountID, $userID, $userName, $comment) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$comment = Escape::url_base64_encode($comment);
		
		$uploadAccountComment = $db->prepare("INSERT INTO acccomments (userID, comment, timestamp)
			VALUES (:userID, :comment, :timestamp)");
		$uploadAccountComment->execute([':userID' => $userID, ':comment' => $comment, ':timestamp' => time()]);
		$commentID = $db->lastInsertId();

		self::logAction($accountID, $IP, 14, $userName, $comment, $commentID);
		
		return $commentID;
	}
	
	public static function updateAccountSettings($accountID, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$updateAccountSettings = $db->prepare("UPDATE accounts SET mS = :messagesState, frS = :friendRequestsState, cS = :commentsState, youtubeurl = :socialsYouTube, twitter = :socialsTwitter, twitch = :socialsTwitch WHERE accountID = :accountID");
		$updateAccountSettings->execute([':accountID' => $accountID, ':messagesState' => $messagesState, ':friendRequestsState' => $friendRequestsState, ':commentsState' => $commentsState, ':socialsYouTube' => $socialsYouTube, ':socialsTwitter' => $socialsTwitter, ':socialsTwitch' => $socialsTwitch]);
		
		self::logAction($accountID, $IP, 27, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);
	}
	
	public static function getFriends($accountID) {
		require __DIR__."/connection.php";
		
		$friendsArray = [];
		
		$getFriends = $db->prepare("SELECT person1, person2 FROM friendships WHERE person1 = :accountID OR person2 = :accountID");
		$getFriends->execute([':accountID' => $accountID]);
		$getFriends = $getFriends->fetchAll();
		
		foreach($getFriends as &$friendship) $friendsArray[] = $friendship["person2"] == $accountID ? $friendship["person1"] : $friendship["person2"];
		
		return $friendsArray;
	}
	
	public static function getUserString($user) {
		//$userdata['userName'] = $this->makeClanUsername($user);
		return $user['userID'].':'.$user["userName"].':'.(is_numeric($user['extID']) ? $user['extID'] : 0);
	}
	
	public static function isAccountAdmininstrator($accountID) {
		$account = self::getAccountByID($accountID);
		return $account['isAdmin'] != 0;
	}
	
	/*
		Levels-related functions
	*/
	
	public static function escapeDescriptionCrash($rawDesc) {
		if(strpos($rawDesc, '<c') !== false) {
			$tagsStart = substr_count($rawDesc, '<c');
			$tagsEnd = substr_count($rawDesc, '</c>');
			if($tagsStart > $tagsEnd) {
				$tags = $tagsStart - $tagsEnd;
				for($i = 0; $i < $tags; $i++) $rawDesc .= '</c>';
			}
		}
		return $rawDesc;
	}
	
	public static function isAbleToUploadLevel($accountID, $userID, $IP) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		
		$lastUploadedLevel = $db->prepare('SELECT count(*) FROM levels WHERE uploadDate >= :time');
		$lastUploadedLevel->execute([':time' => time() - $globalLevelsUploadDelay]);
		$lastUploadedLevel = $lastUploadedLevel->fetchColumn();
		if($lastUploadedLevel) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		$lastUploadedLevelByUser = $db->prepare('SELECT count(*) FROM levels WHERE uploadDate >= :time AND (userID = :userID OR hostname = :IP)');
		$lastUploadedLevelByUser->execute([':time' => time() - $perUserLevelsUploadDelay, ':userID' => $userID, ':IP' => $IP]);
		$lastUploadedLevelByUser = $lastUploadedLevelByUser->fetchColumn();
		if($lastUploadedLevelByUser) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		return ["success" => true];
	}
	
	public function uploadLevel($accountID, $userID, $levelID, $levelName, $levelString, $levelDetails) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$checkLevelExistenceByID = $db->prepare("SELECT count(*) FROM levels WHERE levelID = :levelID AND userID = :userID");
		$checkLevelExistenceByID->execute([':levelID' => $levelID, ':userID' => $userID]);
		$checkLevelExistenceByID = $checkLevelExistenceByID->fetchColumn();
		if($checkLevelExistenceByID) {
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$levelID, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			$updateLevel = $db->prepare('UPDATE levels SET userName = :userName, gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = :levelVersion, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, hostname = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID');
			$updateLevel->execute([':levelID' => $levelID, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelVersion' => $levelDetails['levelVersion'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			self::logAction($accountID, $IP, 23, $levelDetails['levelName'], $levelDetails['levelDesc'], $levelID);
			return ["success" => true, "levelID" => (string)$levelID];
		}
		
		$checkLevelExistenceByName = $db->prepare("SELECT levelID FROM levels WHERE levelName LIKE :levelName AND userID = :userID ORDER BY levelID DESC LIMIT 1");
		$checkLevelExistenceByName->execute([':levelName' => $levelName, ':userID' => $userID]);
		$checkLevelExistenceByName = $checkLevelExistenceByName->fetchColumn();
		if($checkLevelExistenceByName) {
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$checkLevelExistenceByName, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			$updateLevel = $db->prepare('UPDATE levels SET userName = :userName, gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = :levelVersion, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, hostname = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID');
			$updateLevel->execute([':levelID' => $checkLevelExistenceByName, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelVersion' => $levelDetails['levelVersion'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			self::logAction($accountID, $IP, 23, $levelDetails['levelName'], $levelDetails['levelDesc'], $levelID);
			return ["success" => true, "levelID" => (string)$checkLevelExistenceByName];
		}
		
		$timestamp = time();
		$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, $levelString);
		if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
		$uploadLevel = $db->prepare("INSERT INTO levels (userID, extID, userName, gameVersion, binaryVersion, levelName, levelDesc, levelVersion, levelLength, audioTrack, auto, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, unlisted, unlisted2, hostname, isLDM, wt, wt2, settingsString, songIDs, sfxIDs, ts, password, uploadDate, updateDate)
			VALUES (:userID, :accountID, :userName, :gameVersion, :binaryVersion, :levelName, :levelDesc, :levelVersion, :levelLength, :audioTrack, :auto, :original, :twoPlayer, :songID, :objects, :coins, :requestedStars, :extraString, '', :levelInfo, :unlisted, :unlisted, :IP, :isLDM, :wt, :wt2, :settingsString, :songIDs, :sfxIDs, :ts, :password, :timestamp, 0)");
		$uploadLevel->execute([':userID' => $userID, ':accountID' => $accountID, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelName' => $levelName, ':levelDesc' => $levelDetails['levelDesc'], ':levelVersion' => $levelDetails['levelVersion'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => $timestamp, ':IP' => $IP]);
		$levelID = $db->lastInsertId();
		rename(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, __DIR__.'/../../data/levels/'.$levelID);
		self::logAction($accountID, $IP, 22, $levelDetails['levelName'], $levelDetails['levelDesc'], $levelID);
		return ["success" => true, "levelID" => (string)$levelID];
	}
	
	public static function getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset) {
		require __DIR__."/connection.php";
		
		$levels = $db->prepare("SELECT * FROM levels ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).") ORDER BY ".$order." ".$orderSorting." LIMIT 10 OFFSET ".$pageOffset);
		$levels->execute();
		$levels = $levels->fetchAll();
		
		$levelsCount = $db->prepare("SELECT count(*) FROM levels ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).")");
		$levelsCount->execute();
		$levelsCount = $levelsCount->fetchColumn();
		
		return ["levels" => $levels, "count" => $levelsCount];
	}
	
	public static function getGauntletByID($gauntletID) {
		require __DIR__."/connection.php";
		
		$gauntlet = $db->prepare("SELECT * FROM gauntlets WHERE ID = :gauntletID");
		$gauntlet->execute([':gauntletID' => $gauntletID]);
		$gauntlet = $gauntlet->fetch();
		
		return $gauntlet;
	}
	
	public static function canAccountPlayLevel($accountID, $level) {
		require __DIR__."/../../config/misc.php";
		
		if($unlistedLevelsForAdmins && self::isAccountAdmininstrator($accountID)) return true;
		
		return !($level['unlisted'] > 0 && ($level['unlisted'] == 1 && (self::isFriends($accountID, $level['extID']) || $accountID == $level['extID'])));
	}
	
	public static function getDailyLevelID($type) {
		require __DIR__."/connection.php";
		
		switch($type) {
			case -1: // Daily level
				$dailyLevelID = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 0 ORDER BY timestamp DESC LIMIT 1");
				$dailyLevelID->execute([':time' => time()]);
				$dailyLevelID = $dailyLevelID->fetch();
				$levelID = $dailyLevelID["levelID"];
				$feaID = $dailyLevelID["feaID"];
				break;
			case -2: // Weekly level
				$weeklyLevelID = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 1 ORDER BY timestamp DESC LIMIT 1");
				$weeklyLevelID->execute([':time' => time()]);
				$weeklyLevelID = $weeklyLevelID->fetch();
				$levelID = $weeklyLevelID["levelID"];
				$feaID = $weeklyLevelID["feaID"] + 100000;
				break;
			case -3: // Event level
				$eventLevelID = $db->prepare("SELECT feaID, levelID FROM events WHERE timestamp < :time AND duration >= :time ORDER BY timestamp DESC LIMIT 1");
				$eventLevelID->execute([':time' => time()]);
				$eventLevelID = $eventLevelID->fetch();
				$levelID = $eventLevelID["levelID"];
				$feaID = $eventLevelID["feaID"] + 200000;
				break;
		}
		
		if(!$levelID) return false;
		
		return ["levelID" => $levelID, "feaID" => $feaID];
	}
	
	public static function getLevelByID($levelID) {
		require __DIR__."/connection.php";
		
		$level = $db->prepare('SELECT * FROM levels WHERE levelID = :levelID');
		$level->execute([':levelID' => $levelID]);
		$level = $level->fetch();
		
		return $level;
	}
	
	public static function addDownloadToLevel($accountID, $IP, $levelID) {
		require __DIR__."/connection.php";
		
		$getDownloads = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID = :levelID AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$getDownloads->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		if($getDownloads->fetchColumn() == 0) {
			$query2 = $db->prepare("UPDATE levels SET downloads = downloads + 1 WHERE levelID = :levelID");
			$query2->execute([':levelID' => $levelID]);
			$query6 = $db->prepare("INSERT INTO actions_downloads (levelID, ip, accountID) VALUES (:levelID, INET6_ATON(:IP), :accountID)");
			$query6->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		}
	}
	
	/*
		Lists-related functions
	*/
	
	public static function getListLevels($listID) {
		require __DIR__."/connection.php";
		
		$listLevels = $db->prepare('SELECT listlevels FROM lists WHERE listID = :listID');
		$listLevels->execute([':listID' => $listID]);
		$listLevels = $listLevels->fetchColumn();
		
		return $listLevels;
	}
	
	/*
		Audio-related functions
	*/
	
	public static function getSongByID($songID, $column = "*", $library = false) {
		require __DIR__."/connection.php";
		
		$isLocalSong = true;
		
		$song = $db->prepare("SELECT $column FROM songs WHERE ID = :songID");
		$song->execute([':songID' => $songID]);
		$song = $song->fetch();
		
		if(empty($song)) {
			$song = self::getLibrarySongInfo($songID, 'music', $library);
			if(!$song) return false;
			$isLocalSong = false;
		}
		
		if($column != "*") return $song[$column];
		else return array("isLocalSong" => $isLocalSong, "ID" => $song["ID"], "name" => $song["name"], "authorName" => $song["authorName"], "size" => $song["size"], "duration" => $song["duration"], "download" => $song["download"], "reuploadTime" => $song["reuploadTime"], "reuploadID" => $song["reuploadID"]);
	}
	
	public static function getSFXByID($sfxID, $column = "*") {
		require __DIR__."/connection.php";
		
		$sfx = $db->prepare("SELECT $column FROM sfxs WHERE ID = :sfxID");
		$sfx->execute([':sfxID' => $sfxID]);
		$sfx = $sfx->fetch();
		if(empty($sfx)) return false;
		
		if($column != "*") return $sfx[$column];
		else return array("ID" => $sfx["ID"], "name" => $sfx["name"], "authorName" => $sfx["authorName"], "size" => $sfx["size"], "download" => $sfx["download"], "reuploadTime" => $sfx["reuploadTime"], "reuploadID" => $sfx["reuploadID"]);
	}
	
	public static function getSongString($songID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		$librarySong = false;
		$extraSongString = '';
		$song = self::getSongByID($songID);
		if(!$song) {
			$librarySong = true;
			$song = self::getLibrarySongInfo($song['songID']);
		}
		if(!$song || empty($song['ID']) || $song["isDisabled"] == 1) return false;
		$downloadLink = urlencode($song["download"]);
		if($librarySong) {
			$artistsNames = [];
			$artistsArray = explode('.', $song['artists']);
			if(count($artistsArray) > 0) {
				foreach($artistsArray AS &$artistID) {
					$artistData = self::getLibrarySongAuthorInfo($artistID);
					if(!$artistData) continue;
					$artistsNames[] = $artistID.','.$artistData['name'];
				}
			}
			$artistsNames = implode(',', $artistsNames);
			$extraSongString = '~|~9~|~'.$song['priorityOrder'].'~|~11~|~'.$song['ncs'].'~|~12~|~'.$song['artists'].'~|~13~|~'.($song['new'] ? 1 : 0).'~|~14~|~'.$song['new'].'~|~15~|~'.$artistsNames;
		}
		return "1~|~".$song["ID"]."~|~2~|~".Escape::translit(str_replace("#", "", $song["name"]))."~|~3~|~".$song["authorID"]."~|~4~|~".Escape::translit($song["authorName"])."~|~5~|~".$song["size"]."~|~6~|~~|~10~|~".$downloadLink."~|~7~|~~|~8~|~1".$extraSongString;
	}
	
	public static function getLibrarySongInfo($audioID, $type = 'music', $extraLibrary = false) {
		require __DIR__."/../../config/dashboard.php";
		if(!file_exists(__DIR__.'/../../'.$type.'/ids.json')) return false;
		$servers = $serverIDs = $serverNames = [];
		foreach($customLibrary AS $customLib) {
			$servers[$customLib[0]] = $customLib[2];
			$serverNames[$customLib[0]] = $customLib[1];
			$serverIDs[$customLib[2]] = $customLib[0];
		}
		
		$library = $extraLibrary ? $extraLibrary : json_decode(file_get_contents(__DIR__.'/../../'.$type.'/ids.json'), true);
		if(!isset($library['IDs'][$audioID]) || ($type == 'music' && $library['IDs'][$audioID]['type'] != 1)) return false;
		
		if($type == 'music') {
			$song = $library['IDs'][$audioID];
			$author = $library['IDs'][$song['authorID']];
			$token =self::randomString(22);
			$expires = time() + 3600;
			$link = $servers[$song['server']].'/music/'.$song['originalID'].'.ogg?token='.$token.'&expires='.$expires;
			return ['server' => $song['server'], 'ID' => $audioID, 'name' => $song['name'], 'authorID' => $song['authorID'], 'authorName' => $author['name'], 'size' => round($song['size'] / 1024 / 1024, 2), 'download' => $link, 'seconds' => $song['seconds'], 'tags' => $song['tags'], 'ncs' => $song['ncs'], 'artists' => $song['artists'], 'externalLink' => $song['externalLink'], 'new' => $song['new'], 'priorityOrder' => $song['priorityOrder']];
		} else {
			$SFX = $library['IDs'][$audioID];
			$token = self::randomString(22);
			$expires = time() + 3600;
			$link = $servers[$SFX['server']] != null ? $servers[$SFX['server']].'/sfx/s'.$SFX['ID'].'.ogg?token='.$token.'&expires='.$expires : self::getSFXByID($SFX['ID'], 'download');
			return ['isLocalSFX' => $servers[$SFX['server']] == null, 'server' => $SFX['server'], 'ID' => $audioID, 'name' => $song['name'], 'download' => $link, 'originalID' => $SFX['ID']];
		}
	}
	
	public static function getLibrarySongAuthorInfo($songID) {
		if(!file_exists(__DIR__.'/../../music/ids.json')) return false;
		
		$library = json_decode(file_get_contents(__DIR__.'/../../music/ids.json'), true);
		if(!isset($library['IDs'][$songID])) return false;
		
		return $library['IDs'][$songID];
	}
	
	/*
		Utils
	*/
	
	public static function logAction($accountID, $IP, $type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '') {
		require __DIR__."/connection.php";
		
		$insertAction = $db->prepare('INSERT INTO actions (account, type, timestamp, value, value2, value3, value4, value5, value6, IP) VALUES (:account, :type, :timestamp, :value, :value2, :value3, :value4, :value5, :value6, :IP)');
		$insertAction->execute([':account' => $accountID, ':type' => $type, ':value' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':value5' => $value5, ':value6' => $value6, ':timestamp' => time(), ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function randomString($length = 6) {
		$randomString = openssl_random_pseudo_bytes(round($length / 2, 0, PHP_ROUND_HALF_UP));
		if($randomString == false) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		$randomString = bin2hex($randomString);
		return $randomString;
	}
	
	public static function makeTime($time) {
		require __DIR__."/../../config/dashboard.php";
		if(!isset($timeType)) $timeType = 0;
		switch($timeType) {
			case 1:
				if(date("d.m.Y", $time) == date("d.m.Y", time())) return date("G;i", $time);
				elseif(date("Y", $time) == date("Y", time())) return date("d.m", $time);
				else return date("d.m.Y", $time);
				break;
			case 2:
				// taken from https://stackoverflow.com/a/36297417
				$time = time() - $time;
				$time = ($time < 1) ? 1 : $time;
				$tokens = array (31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second');
				foreach($tokens as $unit => $text) {
					if($time < $unit) continue;
					$numberOfUnits = floor($time / $unit);
					return $numberOfUnits.' '.$text.(($numberOfUnits > 1) ? 's' : '');
				}
				break;
			default:
				return date("d/m/Y G.i", $time);
				break;
		}
	}
}
?>