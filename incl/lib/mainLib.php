<?php
require_once __DIR__."/enums.php";
class Library {
	/*
		Account-related functions
	*/
	
	public static function createAccount($userName, $accountPassword, $repeatPassword, $email, $repeatEmail) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/mail.php";
		require_once __DIR__."/security.php";
		require_once __DIR__."/automod.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$salt = self::randomString(32);
		
		if(Automod::isAccountsDisabled(0)) return ["success" => false, "error" => CommonError::Automod];
		
		$logPerson = [
			'accountID' => 0,
			'userID' => 0,
			'userName' => $userName,
			'IP' => $IP,
		];
		
		$checkRegisterRateLimit = Security::checkRateLimits($logPerson, 2);
		if(!$checkRegisterRateLimit) return ["success" => false, "error" => CommonError::Automod];
		
		if($maxAccountsFromIP) {
			$checkIPs = self::getAccountsByIP(self::convertIPForSearching($IP, true));
			if(count($checkIPs) > $maxAccountsFromIP) return ["success" => false, "error" => CommonError::Automod];
		}
		
		if(strlen($userName) > 15 || is_numeric($userName) || strpos($userName, " ") !== false || Security::checkFilterViolation($logPerson, $userName, 0)) return ["success" => false, "error" => RegisterError::InvalidUserName];
		if(strlen($userName) < 3) return ["success" => false, "error" => RegisterError::UserNameIsTooShort];
		if(strlen($accountPassword) < 6) return ["success" => false, "error" => RegisterError::PasswordIsTooShort];
		if($accountPassword != $repeatPassword) return ["success" => false, "error" => RegisterError::PasswordsDoNotMatch];
		if($email != $repeatEmail) return ["success" => false, "error" => RegisterError::EmailsDoNotMatch];
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return ["success" => false, "error" => RegisterError::InvalidEmail];
		
		$userNameExists = self::getAccountIDWithUserName($userName);
		if($userNameExists) return ["success" => false, "error" => RegisterError::AccountExists];
		
		if($mailEnabled) {
			$emailExists = self::getAccountByEmail($email);
			if($emailExists) return ["success" => false, "error" => RegisterError::EmailIsInUse];
		}
		
		$gjp2 = Security::GJP2FromPassword($accountPassword);
		$createAccount = $db->prepare("INSERT INTO accounts (userName, password, email, registerDate, registerIP, isActive, gjp2, salt)
			VALUES (:userName, :password, :email, :registerDate, :registerIP, :isActive, :gjp2, :salt)");
		$createAccount->execute([':userName' => $userName, ':password' => Security::hashPassword($accountPassword), ':email' => $email, ':registerDate' => time(), ':registerIP' => $IP, ':isActive' => $preactivateAccounts ? 1 : 0, ':gjp2' => Security::hashPassword($gjp2), ':salt' => $salt]);
		
		$accountID = $db->lastInsertId();
		$userID = self::createUser($userName, $accountID, $IP, true);
		
		$person = [
			'accountID' => $accountID,
			'userID' => $userID,
			'userName' => $userName,
			'IP' => $IP
		];
		
		self::logAction($person, Action::AccountRegister, $userName, $email, $userID);

		// TO-DO: Re-add email verification
		
		Automod::checkAccountsCount();
		
		return ["success" => true, "accountID" => $accountID, "userID" => $userID];
	}
	
	public static function getAccountByUserName($userName) {
		require __DIR__."/connection.php";

		if(isset($GLOBALS['core_cache']['accounts']['userName'][$userName])) return $GLOBALS['core_cache']['accounts']['userName'][$userName];
		
		$account = $db->prepare("SELECT * FROM accounts WHERE userName LIKE :userName LIMIT 1");
		$account->execute([':userName' => $userName]);
		$account = $account->fetch();
		
		$GLOBALS['core_cache']['accounts']['userName'][$userName] = $account;
		if($account) $GLOBALS['core_cache']['accounts']['accountID'][$account['accountID']] = $account;
		
		return $account;
	}
	
	public static function getAccountByID($accountID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accounts']['accountID'][$accountID])) return $GLOBALS['core_cache']['accounts']['accountID'][$accountID];
		
		$account = $db->prepare("SELECT * FROM accounts WHERE accountID = :accountID");
		$account->execute([':accountID' => $accountID]);
		$account = $account->fetch();
		
		$GLOBALS['core_cache']['accounts']['accountID'][$accountID] = $account;
		
		return $account;
	}
	
	public static function getAccountByEmail($email) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accounts']['email'][$email])) return $GLOBALS['core_cache']['accounts']['email'][$email];
		
		$account = $db->prepare("SELECT * FROM accounts WHERE email LIKE :email ORDER BY registerDate ASC LIMIT 1");
		$account->execute([':email' => $email]);
		$account = $account->fetch();
		
		$GLOBALS['core_cache']['accounts']['email'][$email] = $account;
		if($account) $GLOBALS['core_cache']['accounts']['accountID'][$account['accountID']] = $account;
		
		return $account;
	}
	
	public static function getAccountByDiscord($discordID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accounts']['discord'][$discordID])) return $GLOBALS['core_cache']['accounts']['discord'][$discordID];
		
		$account = $db->prepare("SELECT * FROM accounts WHERE discordID = :discordID AND discordLinkReq = 0");
		$account->execute([':discordID' => $discordID]);
		$account = $account->fetch();
		
		$GLOBALS['core_cache']['accounts']['discord'][$discordID] = $account;
		if($account) $GLOBALS['core_cache']['accounts']['accountID'][$account['accountID']] = $account;
		
		return $account;
	}
	
	public static function getAccountsByIP($IP) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accounts']['ip'][$IP])) return $GLOBALS['core_cache']['accounts']['ip'][$IP];
		
		$accounts = $db->prepare("SELECT * FROM accounts WHERE registerIP REGEXP CONCAT('((\\\D[^.])|^)(', :IP, ')(\\\D[^$])')");
		$accounts->execute([':IP' => $IP]);
		$accounts = $accounts->fetchAll();
		
		$GLOBALS['core_cache']['accounts']['ip'][$IP] = $accounts;
		
		if($accounts) {
			foreach($accounts AS &$account) $GLOBALS['core_cache']['accounts']['accountID'][$account['accountID']] = $account;
		}
		
		return $accounts;
	}
	
	public static function createUser($userName, $accountID, $IP, $bypassRateLimit = false) {
		require __DIR__."/connection.php";
		require_once __DIR__."/security.php";
		
		$isRegistered = is_numeric($accountID) ? 1 : 0;
		
		$logPerson = [
			'accountID' => $accountID,
			'userID' => 0,
			'userName' => $userName,
			'IP' => $IP
		];
		
		if(!$bypassRateLimit) {
			$checkCreateRateLimit = Security::checkRateLimits($logPerson, 3);
			if($checkCreateRateLimit) return false;
		}
		
		$createUser = $db->prepare("INSERT INTO users (isRegistered, extID, userName, IP)
			VALUES (:isRegistered, :extID, :userName, :IP)");
		$createUser->execute([':isRegistered' => $isRegistered, ':extID' => $accountID, ':userName' => $userName, ':IP' => $IP]);
		$userID = $db->lastInsertId();
		
		$logPerson['userID'] = $userID;
		
		self::logAction($logPerson, Action::UserCreate, $userID, $userName);
		
		return $userID;
	}
	
	public static function getUserID($accountID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		if(isset($GLOBALS['core_cache']['userID'][$accountID])) return $GLOBALS['core_cache']['userID'][$accountID];
		
		$userID = $db->prepare("SELECT userID FROM users WHERE extID = :extID");
		$userID->execute([':extID' => $accountID]);
		$userID = $userID->fetchColumn();
		
		if(!$userID) {
			$account = self::getAccountByID($accountID);
			if(!$account) return false;
			
			$IP = IP::getIP();
			$userName = $account['userName'];
			$userID = self::createUser($userName, $accountID, $IP) ?: 0;
		}
		
		$GLOBALS['core_cache']['userID'][$accountID] = $userID;
		
		return $userID;
	}
	
	public static function getAccountID($userID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accountID']['userID'][$userID])) return $GLOBALS['core_cache']['accountID']['userID'][$userID];
		
		$accountID = $db->prepare("SELECT extID FROM users WHERE userID = :userID");
		$accountID->execute([':userID' => $userID]);
		$accountID = $accountID->fetchColumn();
		
		$GLOBALS['core_cache']['accountID']['userID'][$userID] = $accountID;
		
		return $accountID;
	}
	
	public static function getAccountIDWithUserName($userName) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['accountID']['userName'][$userName])) return $GLOBALS['core_cache']['accountID']['userName'][$userName];
		
		$accountID = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :userName");
		$accountID->execute([':userName' => $userName]);
		$accountID = $accountID->fetchColumn();
		
		$GLOBALS['core_cache']['accountID']['userName'][$userName] = $accountID;
		
		return $accountID;
	}
	
	public static function getUserByID($userID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['user']['userID'][$userID])) return $GLOBALS['core_cache']['user']['userID'][$userID];
		
		$user = $db->prepare("SELECT * FROM users WHERE userID = :userID");
		$user->execute([':userID' => $userID]);
		$user = $user->fetch();
		
		$GLOBALS['core_cache']['user']['userID'][$userID] = $user;
		
		return $user;
	}
	
	public static function getUserByAccountID($extID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['user']['extID'][$extID])) return $GLOBALS['core_cache']['user']['extID'][$extID];
		
		$user = $db->prepare("SELECT * FROM users WHERE extID = :extID");
		$user->execute([':extID' => $extID]);
		$user = $user->fetch();
		
		$GLOBALS['core_cache']['user']['extID'][$extID] = $user;
		if($user) $GLOBALS['core_cache']['user']['userID'][$user['userID']] = $user;
		
		return $user;
	}
	
	public static function getUserByUserName($userName) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['user']['userName'][$userName])) return $GLOBALS['core_cache']['user']['userName'][$userName];
		
		$user = $db->prepare("SELECT * FROM users WHERE userName LIKE :userName ORDER BY isRegistered DESC LIMIT 1");
		$user->execute([':userName' => $userName]);
		$user = $user->fetch();
		
		$GLOBALS['core_cache']['user']['userName'][$userName] = $user;
		if($user) $GLOBALS['core_cache']['user']['userID'][$user['userID']] = $user;
		
		return $user;
	}
	
	public static function getUserFromSearch($player) {
		switch(true) {
			case is_numeric($player):
				$userID = self::getUserID($player);
				$player = self::getUserByID($userID);
				break;
			case substr($player, 0, 1) == 'u':
				$userID = substr($player, 1);
				if(is_numeric($userID)) {
					$player = self::getUserByID($userID);
					break;
				}
			default:
				$player = self::getUserByUserName($player);
				break;
		}
		
		return $player;
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
	
	public static function getAccountComments($userID, $commentsPage, $mode = 'timestamp') {
		require __DIR__."/connection.php";

		$accountComments = $db->prepare("SELECT * FROM acccomments WHERE userID = :userID ".($mode ? 'ORDER BY '.$mode.' DESC' : '')." LIMIT 10 OFFSET ".$commentsPage);
		$accountComments->execute([':userID' => $userID]);
		$accountComments = $accountComments->fetchAll();
		
		$accountCommentsCount = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID");
		$accountCommentsCount->execute([':userID' => $userID]);
		$accountCommentsCount = $accountCommentsCount->fetchColumn();
		
		return ["comments" => $accountComments, "count" => $accountCommentsCount];
	}
	
	public static function uploadAccountComment($person, $comment) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/automod.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$userID = $person['userID'];
		$userName = $person['userName'];
		
		if($enableCommentLengthLimiter) $comment = mb_substr($comment, 0, $maxAccountCommentLength);
		
		$comment = Escape::url_base64_encode($comment);
		
		$uploadAccountComment = $db->prepare("INSERT INTO acccomments (userID, comment, timestamp)
			VALUES (:userID, :comment, :timestamp)");
		$uploadAccountComment->execute([':userID' => $userID, ':comment' => $comment, ':timestamp' => time()]);
		$commentID = $db->lastInsertId();

		self::logAction($person, Action::AccountCommentUpload, $userName, $comment, $commentID);
		
		Automod::checkAccountPostsSpamming($userID);
		
		return $commentID;
	}
	
	public static function updateAccountSettings($person, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$updateAccountSettings = $db->prepare("UPDATE accounts SET mS = :messagesState, frS = :friendRequestsState, cS = :commentsState, youtubeurl = :socialsYouTube, twitter = :socialsTwitter, twitch = :socialsTwitch WHERE accountID = :accountID");
		$updateAccountSettings->execute([':accountID' => $accountID, ':messagesState' => $messagesState, ':friendRequestsState' => $friendRequestsState, ':commentsState' => $commentsState, ':socialsYouTube' => $socialsYouTube, ':socialsTwitter' => $socialsTwitter, ':socialsTwitch' => $socialsTwitch]);
		
		self::logAction($person, Action::ProfileSettingsChange, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);
		
		return true;
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
		$user['userName'] = self::makeClanUsername($user['extID']);
		
		return $user['userID'].':'.$user["userName"].':'.$user['extID'];
	}
	
	public static function isAccountAdministrator($accountID) {
		if(isset($GLOBALS['core_cache']['isAdministrator'][$accountID])) return $GLOBALS['core_cache']['isAdministrator'][$accountID];
		
		$account = self::getAccountByID($accountID);
		$isAdmin = $account['isAdmin'] != 0;
		
		$GLOBALS['core_cache']['isAdministrator'][$accountID] = $isAdmin;
		
		return $isAdmin;
	}
	
	public static function getCommentsOfUser($userID, $sortMode, $pageOffset, $count = 10) {
		require __DIR__."/connection.php";
		
		$comments = $db->prepare("SELECT * FROM comments
			JOIN (
				(SELECT levelID AS itemID, levelName COLLATE utf8mb3_unicode_ci AS itemName, extID AS creatorAccountID FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0)
				UNION
				(SELECT listID * -1 AS itemID, listName COLLATE utf8mb3_unicode_ci AS itemName, accountID AS creatorAccountID FROM lists WHERE lists.unlisted = 0)
			) items ON comments.levelID = items.itemID
			WHERE comments.userID = :userID ORDER BY ".$sortMode." DESC LIMIT ".$count." OFFSET ".$pageOffset);
		$comments->execute([':userID' => $userID]);
		$comments = $comments->fetchAll();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM comments
			JOIN (
				(SELECT levelID AS itemID, levelName COLLATE utf8mb3_unicode_ci AS itemName, extID AS creatorAccountID FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0)
				UNION
				(SELECT listID AS itemID, listName COLLATE utf8mb3_unicode_ci AS itemName, accountID AS creatorAccountID FROM lists WHERE lists.unlisted = 0)
			) items ON comments.levelID = items.itemID
			WHERE comments.userID = :userID");
		$commentsCount->execute([':userID' => $userID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		return ["comments" => $comments, "count" => $commentsCount];
	}
	
	public static function deleteAccountComment($person, $commentID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		$userName = $person['userName'];
		$userID = $person['userID'];
		
		$getComment = $db->prepare("SELECT * FROM acccomments WHERE userID = :userID AND commentID = :commentID");
		$getComment->execute([':userID' => $userID, ':commentID' => $commentID]);
		$getComment = $getComment->fetch();
		if(!$getComment) return false;
		
		$deleteAccountComment = $db->prepare("DELETE FROM acccomments WHERE commentID = :commentID");
		$deleteAccountComment->execute([':commentID' => $commentID]);
		
		self::logAction($person, Action::AccountCommentDeletion, $userName, $getComment['comment'], $accountID, $getComment['commentID'], $getComment['likes'], $getComment['dislikes']);
		
		return true;
	}
	
	public static function getAllBans($onlyActive = true) {
		require __DIR__."/connection.php";
		
		$bans = $db->prepare('SELECT * FROM bans'.($onlyActive ? ' AND isActive = 1' : '').' ORDER BY timestamp DESC');
		$bans->execute();
		$bans = $bans->fetchAll();
		
		return $bans;
	}
	
	public static function getAllBansFromPerson($person, $onlyActive = true) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$userID = $person['userID'];
		$IP = self::convertIPForSearching($person['IP'], true);
		
		$bans = $db->prepare('SELECT * FROM bans WHERE ((person = :accountID AND personType = 0) OR (person = :userID AND personType = 1) OR (person = :IP AND personType = 2))'.($onlyActive ? ' AND isActive = 1' : '').' ORDER BY timestamp DESC');
		$bans->execute([':accountID' => $accountID, ':userID' => $userID, ':IP' => $IP, ':personType' => $personType]);
		$bans = $bans->fetchAll();
		
		return $bans;
	}
	
	public static function getAllBansOfPersonType($personType, $onlyActive = true) {
		require __DIR__."/connection.php";
		
		$bans = $db->prepare('SELECT * FROM bans WHERE personType = :personType'.($onlyActive ? ' AND isActive = 1' : '').' ORDER BY timestamp DESC');
		$bans->execute([':personType' => $personType]);
		$bans = $bans->fetchAll();
		
		return $bans;
	}
	
	public static function getAllBansOfBanType($banType, $onlyActive = true) {
		require __DIR__."/connection.php";
		
		$bans = $db->prepare('SELECT * FROM bans WHERE banType = :banType'.($onlyActive ? ' AND isActive = 1' : '').' ORDER BY timestamp DESC');
		$bans->execute([':banType' => $banType]);
		$bans = $bans->fetchAll();
		
		return $bans;
	}
	
	public static function banPerson($modID, $person, $reason, $banType, $personType, $expires, $modReason = '') {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$moderatorPerson = [
			'accountID' => $modID,
			'IP' => $IP
		];
		
		switch($personType) {
			case 0:
				$person = $person['accountID'];
				
				if($banType == 4) {
					$removeAuth = $db->prepare('UPDATE accounts SET auth = "" WHERE accountID = :accountID');
					$removeAuth->execute([':accountID' => $person]);
				}
				
				break;
			case 1:
				$person = $person['userID'];
				
				break;
			case 2:
				$person = self::convertIPForSearching($person['IP']);
				
				if($banType == 4) {
					$banIP = $db->prepare("INSERT INTO bannedips (IP) VALUES (:IP)");
					$banIP->execute([':IP' => $person]);
				}
				
				break;
		}
		
		$check = self::getBan($person, $personType, $banType);
		if($check) {
			if($check['expires'] <= $expires) return $check['banID'];
			self::unbanPerson($check['banID'], $modID);
		}
		
		$reason = base64_encode($reason);
		if(!empty($modReason)) $modReason = base64_encode($modReason);
		$ban = $db->prepare('INSERT INTO bans (modID, person, reason, modReason, banType, personType, expires, timestamp) VALUES (:modID, :person, :reason, :modReason, :banType, :personType, :expires, :timestamp)');
		$ban->execute([':modID' => $modID, ':person' => $person, ':reason' => $reason, ':modReason' => $modReason, ':banType' => $banType, ':personType' => $personType, ':expires' => $expires, ':timestamp' => ($modID != 0 ? time() : 0)]);
		$banID = $db->lastInsertId();
		
		self::logModeratorAction($moderatorPerson, ModeratorAction::PersonBan, $person, $reason, $personType, $banType, $expires, $modReason);
		//$this->sendBanWebhook($banID, $modID);
		
		return $banID;
	}
	
	public static function getBan($person, $personType, $banType) {
		require __DIR__."/connection.php";
		
		$ban = $db->prepare('SELECT * FROM bans WHERE person = :person AND personType = :personType AND banType = :banType AND isActive = 1 ORDER BY timestamp DESC');
		$ban->execute([':person' => $person, ':personType' => $personType, ':banType' => $banType]);
		$ban = $ban->fetch();
		
		return $ban;
	}
	
	public static function unbanPerson($banID, $modID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$moderatorPerson = [
			'accountID' => $modID,
			'IP' => $IP
		];
		
		$ban = self::getBanByID($banID);
		if(!$ban) return false;
		
		if($ban['personType'] == 2 && $ban['banType'] == 4) {
			$banIP = $db->prepare("DELETE FROM bannedips WHERE IP = :IP");
			$banIP->execute([':IP' => $ban['person']]);
		}
		
		$unban = $db->prepare('UPDATE bans SET isActive = 0 WHERE banID = :banID');
		$unban->execute([':banID' => $banID]);
		self::logModeratorAction($moderatorPerson, ModeratorAction::PersonUnban, $ban['person'], $ban['reason'], $ban['personType'], $ban['banType'], $ban['expires'], $ban['modReason']);
		//$this->sendBanWebhook($banID, $modID);
		
		return true;
	}
	
	public static function getBanByID($banID) {
		require __DIR__."/connection.php";
		
		$ban = $db->prepare('SELECT * FROM bans WHERE banID = :banID');
		$ban->execute([':banID' => $banID]);
		$ban = $ban->fetch();
		
		return $ban;
	}
	
	public static function getPersonBan($person, $banType) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$accountID = $person['accountID'];
		$userID = $person['userID'];
		$IP = self::convertIPForSearching($person['IP']);
		
		$ban = $db->prepare('SELECT * FROM bans WHERE ((person = :accountID AND personType = 0) OR (person = :userID AND personType = 1) OR (person = :IP AND personType = 2)) AND banType = :banType AND isActive = 1 ORDER BY expires DESC');
		$ban->execute([':accountID' => $accountID, ':userID' => $userID, ':IP' => $IP, ':banType' => $banType]);
		$ban = $ban->fetch();
		
		return $ban['expires'] > time() ? $ban : false;
	}
	
	public static function convertIPForSearching($IP, $isSearch = false) {
		$IP = explode('.', $IP);
		return $IP[0].'.'.$IP[1].'.'.$IP[2].($isSearch ? '' : '.0');
	}
	
	public static function changeBan($banID, $modPerson, $reason, $expires, $modReason) {
		require __DIR__."/connection.php";
		
		$ban = self::getBanByID($banID);
		$reason = base64_encode($reason);
		if(!empty($modReason)) $modReason = base64_encode($modReason);
		
		if($ban && $ban['isActive'] != 0) {
			$changeBan = $db->prepare('UPDATE bans SET reason = :reason, modReason = :modReason, expires = :expires WHERE banID = :banID');
			$changeBan->execute([':banID' => $banID, ':reason' => $reason, ':modReason' => $modReason, ':expires' => $expires]);
			
			self::logModeratorAction($modPerson, ModeratorAction::PersonBanChange, $ban['person'], $reason, $ban['personType'], $ban['banType'], $expires, $modReason);
			//$this->sendBanWebhook($banID, $modID);
			
			return true;
		}
		
		return false;
	}
	
	public static function getPersonRoles($person) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(isset($GLOBALS['core_cache']['roles'][$person['accountID']])) return $GLOBALS['core_cache']['roles'][$person['accountID']];
		
		$roleIDs = [];
		
		$getRoleID = $db->prepare("SELECT roleID FROM roleassign WHERE (person = :accountID AND personType = 0) OR (person = :userID AND personType = 1) OR (person REGEXP CONCAT('((\\\D[^.])|^)(', :IP, ')(\\\D[^$])') AND personType = 2)");
		$getRoleID->execute([':accountID' => $person['accountID'], ':userID' => $person['userID'], ':IP' => self::convertIPForSearching($person['IP'], true)]);
		$getRoleID = $getRoleID->fetchAll();
		
		foreach($getRoleID AS &$roleID) $roleIDs[] = $roleID['roleID'];
		$roleIDs[] = 0;
		
		$getRoles = $db->prepare("SELECT * FROM roles WHERE roleID IN (".implode(',', $roleIDs).") OR isDefault != 0 ORDER BY priority DESC, isDefault ASC");
		$getRoles->execute();
		$getRoles = $getRoles->fetchAll();
		
		$GLOBALS['core_cache']['roles'][$person['accountID']] = $getRoles;
		
		return $getRoles;
	}
	
	public static function checkPermission($person, $permission) {
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(isset($GLOBALS['core_cache']['permissions'][$permission][$person['accountID']])) return $GLOBALS['core_cache']['permissions'][$permission][$person['accountID']];
		
		$isAdmin = self::isAccountAdministrator($person['accountID']);
		if($isAdmin) {
			$GLOBALS['core_cache']['permissions'][$permission][$person['accountID']] = true;
			return true;
		}
		
		$getRoles = self::getPersonRoles($person);
		if(!$getRoles) {
			$GLOBALS['core_cache']['permissions'][$permission][$person['accountID']] = false;
			return false;
		}
		
		foreach($getRoles AS &$role) {
			if(!isset($role[$permission])) return false;
			
			switch($role[$permission]) {
				case 1:
					$GLOBALS['core_cache']['permissions'][$permission][$person['accountID']] = true;
					return true;
				case 2:
					$GLOBALS['core_cache']['permissions'][$permission][$person['accountID']] = false;
					return false;
			}	
		}
		
		$GLOBALS['core_cache']['permissions'][$permission][$person['accountID']] = false;
		return false;
	}
	
	public static function getDailyChests($userID) {
		require __DIR__."/connection.php";
		
		$getTime = $db->prepare("SELECT chest1time, chest2time, chest1count, chest2count FROM users WHERE userID = :userID");
		$getTime->execute([':userID' => $userID]);
		$getTime = $getTime->fetch();
		
		return $getTime;
	}
	
	public static function retrieveDailyChest($userID, $rewardType) {
		require __DIR__."/connection.php";
		
		$retrieveChest = $db->prepare("UPDATE users SET chest".$rewardType."time = :time, chest".$rewardType."count = chest".$rewardType."count + 1 WHERE userID = :userID");
		$retrieveChest->execute([':userID' => $userID, ':time' => time()]);
		
		return true;
	}
	
	public static function getPersonCommentAppearance($person) {
		if($person['accountID'] == 0 || $person['userID'] == 0) return [
			'commentsExtraText' => '',
			'roleName' => '',
			'modBadgeLevel' => 0,
			'commentColor' => '255,255,255'
		];
		
		if(isset($GLOBALS['core_cache']['roleAppearance'][$person['accountID']])) return $GLOBALS['core_cache']['roleAppearance'][$person['accountID']];
		
		$getRoles = self::getPersonRoles($person);
		
		if(!$getRoles) {
			$roleAppearance = [
				'commentsExtraText' => '',
				'roleName' => '',
				'modBadgeLevel' => 0,
				'commentColor' => '255,255,255'
			];
		} else {		
			$roleAppearance = [
				'commentsExtraText' => $getRoles[0]['commentsExtraText'],
				'roleName' => $getRoles[0]['roleName'],
				'modBadgeLevel' => $getRoles[0]['modBadgeLevel'],
				'commentColor' => $getRoles[0]['commentColor']
			];
		}
		
		$GLOBALS['core_cache']['roleAppearance'][$person['accountID']] = $roleAppearance;
		
		return $roleAppearance;
	}
	
	public static function getAllBannedPeople($type) {
		if(isset($GLOBALS['core_cache']['bannedPeople'][$type])) return $GLOBALS['core_cache']['bannedPeople'][$type];
		
		$extIDs = $userIDs = $bannedIPs = [];
		
		$bans = self::getAllBansOfBanType($type);
		
		foreach($bans AS &$ban) {
			switch($ban['personType']) {
				case 0:
					$extIDs[] = $ban['person'];
					break;
				case 1:
					$userIDs[] = $ban['person'];
					break;
				case 2:
					$bannedIPs[] = self::convertIPForSearching($ban['person'], true);
					break;
			}
		}
		
		$bannedPeople = ['accountIDs' => $extIDs, 'userIDs' => $userIDs, 'IPs' => $bannedIPs];
		
		$GLOBALS['core_cache']['bannedPeople'][$type] = $bannedPeople;
		
		return $bannedPeople;
	}
	
	public static function getBannedPeopleQuery($type, $addSeparator = false) {
		if(isset($GLOBALS['core_cache']['bannedPeopleQuery'][$type])) return $GLOBALS['core_cache']['bannedPeopleQuery'][$type];

		$queryArray = [];
		
		$bannedPeople = self::getAllBannedPeople($type);
		
		$extIDsString = implode("','", $bannedPeople['accountIDs']);
		$userIDsString = implode("','", $bannedPeople['userIDs']);
		$bannedIPsString = implode("|", $bannedPeople['IPs']);
		
		if(!empty($extIDsString)) $queryArray[] = "extID NOT IN ('".$extIDsString."')";
		if(!empty($userIDsString)) $queryArray[] = "userID NOT IN ('".$userIDsString."')";
		if(!empty($bannedIPsString)) $queryArray[] = "IP NOT REGEXP '((\\\D[^.])|^)(".$bannedIPsString.")(\\\D[^$])'";
	
		$queryText = !empty($queryArray) ? '('.implode(' AND ', $queryArray).')'.($addSeparator ? ' AND' : '') : '';
		
		$GLOBALS['core_cache']['bannedPeopleQuery'][$type] = $queryText;
		
		return $queryText;
	}
	
	public static function getLeaderboard($person, $type, $count) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		
		$accountID = $person["accountID"];
		$userID = $person["userID"];
		$userName = $person["userName"];
		
		$user = self::getUserByID($userID);
		$rank = 0;
		
		switch($type) {
			case 'top':
				$queryText = self::getBannedPeopleQuery(0, true);
				
				$leaderboard = $db->prepare("SELECT * FROM users WHERE ".$queryText." stars + moons >= :stars ORDER BY stars + moons DESC, userName ASC LIMIT 100");
				$leaderboard->execute([':stars' => $leaderboardMinStars]);
				
				break;
			case 'creators':
				$queryText = self::getBannedPeopleQuery(1, true);
				
				$leaderboard = $db->prepare("SELECT * FROM users WHERE ".$queryText." creatorPoints > 0 ORDER BY creatorPoints DESC, userName ASC LIMIT 100");
				$leaderboard->execute();
				break;
			case 'relative':
				if($moderatorsListInGlobal) {
					$leaderboard = $db->prepare("SELECT * FROM users
						INNER JOIN roleassign ON
							(users.extID = roleassign.person AND roleassign.personType = 0) OR
							(users.userID = roleassign.person AND roleassign.personType = 1)
						INNER JOIN roles ON roleassign.roleID = roles.roleID
						ORDER BY roles.priority DESC, users.userName ASC");
					$leaderboard ->execute();
					break;
				}
				
				$queryText = self::getBannedPeopleQuery(0, true);
				
				$count = floor($count / 2);
				
				$leaderboard = $db->prepare("SELECT leaderboards.* FROM (
						(
							SELECT * FROM users
							WHERE ".$queryText."
							stars + moons <= :stars
							ORDER BY stars + moons DESC
							LIMIT ".$count."
						)
						UNION
						(
							SELECT * FROM users
							WHERE ".$queryText."
							stars + moons >= :stars
							ORDER BY stars + moons ASC
							LIMIT ".$count."
						)
					) as leaderboards
					ORDER BY leaderboards.stars + leaderboards.moons DESC, leaderboards.userName ASC");
				$leaderboard->execute([':stars' => $user['stars'] + $user['moons']]);
				
				$rank = max(0, self::getUserRank($user['stars'], $user['moons'], $userName) - $count);
				
				break;
			case 'friends':
				$friendsArray = self::getFriends($accountID);
				$friendsArray[] = $accountID;
				$friendsString = "'".implode("','", $friendsArray)."'";
				
				$leaderboard = $db->prepare("SELECT * FROM users WHERE extID IN (".$friendsString.") ORDER BY stars + moons DESC, userName ASC");
				$leaderboard->execute();
				break;
			case 'week':
				$queryText = self::getBannedPeopleQuery(0, true);

				$leaderboard = $db->prepare("SELECT users.*, SUM(actions.value) AS stars, SUM(actions.value2) AS coins, SUM(actions.value3) AS demons FROM actions
					INNER JOIN users ON actions.account = users.extID WHERE type = '9' AND ".$queryText." timestamp > :time AND stars > 0
					GROUP BY account ORDER BY stars DESC, userName ASC LIMIT 100");
				$leaderboard->execute([':time' => time() - 604800]);
				break;
		}
		
		$leaderboard = $leaderboard->fetchAll();
		
		return ["rank" => $rank, "leaderboard" => $leaderboard];
	}
	
	public static function getUserRank($stars, $moons, $userName) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		
		if($stars + $moons < $leaderboardMinStars) return 0;
		
		$queryText = self::getBannedPeopleQuery(0, true);
		
		$rank = $db->prepare("SELECT count(*) FROM users WHERE ".$queryText." stars + moons >= :stars AND IF(stars + moons = :stars, userName <= :userName, 1)");
		$rank->execute([':stars' => $stars + $moons, ':userName' => $userName]);
		$rank = $rank->fetchColumn();
		
		return $rank;
	}
	
	public static function getAccountMessages($person, $getSent, $pageOffset) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$messages = $db->prepare("SELECT * FROM messages JOIN users ON messages.".($getSent ? 'toAccountID' : 'accountID')." = users.extID WHERE messages.".($getSent ? 'accountID' : 'toAccountID')." = :accountID ORDER BY messages.timestamp DESC LIMIT 10 OFFSET ".$pageOffset);
		$messages->execute([':accountID' => $accountID]);
		$messages = $messages->fetchAll();
		
		$messagesCount = $db->prepare("SELECT count(*) FROM messages WHERE ".($getSent ? 'toAccountID' : 'accountID')." = :accountID");
		$messagesCount->execute([':accountID' => $accountID]);
		$messagesCount = $messagesCount->fetchColumn();
		
		return ['messages' => $messages, 'count' => $messagesCount];
	}
	
	public static function readMessage($person, $messageID, $isSender) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/XOR.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$getMessage = $db->prepare("SELECT * FROM messages JOIN users ON messages.".($isSender ? 'toAccountID' : 'accountID')." = users.extID WHERE messages.".($isSender ? 'accountID' : 'toAccountID')." = :accountID AND messages.messageID = :messageID");
		$getMessage->execute([':accountID' => $accountID, ':messageID' => $messageID]);
		$getMessage = $getMessage->fetch();
		
		if(!$getMessage) return false;
		
		$readMessage = $db->prepare("UPDATE messages SET isNew = 1, readTime = :readTime WHERE messageID = :messageID AND toAccountID = :accountID AND readTime = 0");
		$readMessage->execute([':messageID' => $messageID, ':accountID' => $accountID, ':readTime' => time()]);
		
		$getMessage["subject"] = Escape::url_base64_encode(Escape::translit(Escape::url_base64_decode($getMessage["subject"])));
		$getMessage["body"] = Escape::url_base64_encode(XORCipher::cipher(Escape::translit(XORCipher::cipher(Escape::url_base64_decode($getMessage["body"]), 14251)), 14251));
		
		return $getMessage;
	}
	
	public static function canSendMessage($person, $toAccountID) {
		require_once __DIR__."/automod.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(isset($GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID])) return $GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID];
		
		if(Automod::isAccountsDisabled(3)) return false;
		
		if($person['accountID'] == $toAccountID) {
			$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		$checkBan = self::getPersonBan($person, 3);
		if($checkBan) {
			$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		$account = self::getAccountByID($toAccountID);
		if(!$account) {
			$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		$isBlocked = self::isPersonBlocked($toAccountID, $person['accountID']);
		if($isBlocked) {
			$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
			return false;
		}

		switch($account['mS']) {
			case 2:
				$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
				return false;
			case 1:
				$isFriends = self::isFriends($person['accountID'], $toAccountID);
				if(!$isFriends) {
					$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = false;
					return false;
				}
				break;
		}
		
		$GLOBALS['core_cache']['canSendMessage'][$person['accountID']][$toAccountID] = true;
		return true;
	}
	
	public static function isPersonBlocked($accountID, $targetAccountID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['personBlocked'][$accountID][$targetAccountID])) return $GLOBALS['core_cache']['personBlocked'][$accountID][$targetAccountID];
		
		if($accountID == $targetAccountID) {
			$GLOBALS['core_cache']['personBlocked'][$accountID][$targetAccountID] = false;
			return false;
		}
		
		$isBlocked = $db->prepare("SELECT count(*) FROM blocks WHERE (person1 = :accountID AND person2 = :targetAccountID) OR (person1 = :targetAccountID AND person2 = :accountID)");
		$isBlocked->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		$isBlocked = $isBlocked->fetchColumn() > 0;
		
		$GLOBALS['core_cache']['personBlocked'][$accountID][$targetAccountID] = $isBlocked;
		
		return $isBlocked;
	}
	
	public static function sendMessage($person, $toAccountID, $subject, $body) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$sendMessage = $db->prepare("INSERT INTO messages (subject, body, accountID, toAccountID, timestamp)
			VALUES (:subject, :body, :accountID, :toAccountID, :timestamp)");
		$sendMessage->execute([':subject' => $subject, ':body' => $body, ':accountID' => $accountID, ':toAccountID' => $toAccountID, ':timestamp' => time()]);
		
		return true;
	}
	
	public static function deleteMessages($person, $messages) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(!$messages) return false;
		
		$accountID = $person['accountID'];
		
		$deleteMessages = $db->prepare("DELETE FROM messages WHERE messageID IN (".$messages.") AND (accountID = :accountID OR toAccountID = :accountID)");
		$deleteMessages->execute([':accountID' => $accountID]);
		
		return true;
	}
	
	public static function canSeeCommentsHistory($person, $targetUserID) {
		if(isset($GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID])) return $GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID];
		
		if($person['userID'] == $targetUserID) {
			$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = true;
			return true;
		}

		$user = self::getUserByID($targetUserID);
		if(!$user) {
			$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = false;
			return false;
		}
		
		$account = self::getAccountByID($user['extID']);
		if(!$account) {
			$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = false;
			return false;
		}
		
		$isBlocked = self::isPersonBlocked($account['accountID'], $person['accountID']);
		if($isBlocked) {
			$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = false;
			return false;
		}

		switch($account['cS']) {
			case 2:
				$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = false;
				return false;
			case 1:
				$isFriends = self::isFriends($person['accountID'], $account['accountID']);
				if(!$isFriends) {
					$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = false;
					return false;
				}
				break;
		}
		
		$GLOBALS['core_cache']['canSeeCommentsHistory'][$person['userID']][$targetUserID] = true;
		return true;
	}
	
	public static function getFriendships($person) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		if(isset($GLOBALS['core_cache']['friendships'][$accountID])) return $GLOBALS['core_cache']['friendships'][$accountID];
		
		$friendships = $db->prepare("SELECT * FROM friendships INNER JOIN users ON (person1 = users.extID AND person1 != :accountID) OR (person2 = users.extID AND person2 != :accountID) WHERE person1 = :accountID OR person2 = :accountID ORDER BY users.userName ASC");
		$friendships->execute([':accountID' => $accountID]);
		$friendships = $friendships->fetchAll();
		
		$readFriendships = $db->prepare("UPDATE friendships SET isNew1 = 0 WHERE person1 = :accountID");
		$readFriendships->execute([':accountID' => $accountID]);
		$readFriendships = $db->prepare("UPDATE friendships SET isNew2 = 0 WHERE person2 = :accountID");
		$readFriendships->execute([':accountID' => $accountID]);
		
		$GLOBALS['core_cache']['friendships'][$accountID] = $friendships;
		
		return $friendships;
	}
	
	public static function getBlocks($person) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		if(isset($GLOBALS['core_cache']['blocks'][$accountID])) return $GLOBALS['core_cache']['blocks'][$accountID];
		
		$blocks = $db->prepare("SELECT * FROM blocks INNER JOIN users ON blocks.person2 = users.extID WHERE blocks.person1 = :accountID ORDER BY users.userName ASC");
		$blocks->execute([':accountID' => $accountID]);
		$blocks = $blocks->fetchAll();
		
		$GLOBALS['core_cache']['blocks'][$accountID] = $blocks;
		
		return $blocks;
	}
	
	public static function removeFriend($person, $targetAccountID, $logAction = true) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$isFriends = self::isFriends($accountID, $targetAccountID);
		if(!$isFriends) return false;
		
		$removeFriend = $db->prepare("DELETE FROM friendships WHERE (person1 = :accountID AND person2 = :targetAccountID) OR (person1 = :targetAccountID AND person2 = :accountID)");
		$removeFriend->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		
		if($logAction) self::logAction($person, Action::FriendRemove, $targetAccountID);
		
		return true;
	}
	
	public static function unblockUser($person, $targetAccountID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$isBlocked = self::isPersonBlocked($accountID, $targetAccountID);
		if(!$isBlocked) return false;
		
		$unblockUser = $db->prepare("DELETE FROM blocks WHERE person1 = :accountID AND person2 = :targetAccountID");
		$unblockUser->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		
		self::logAction($person, Action::UnblockAccount, $targetAccountID);
		
		return true;
	}
	
	public static function getFriendRequests($person, $getSent, $pageOffset) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		if(isset($GLOBALS['core_cache']['friendRequests'][$accountID])) return $GLOBALS['core_cache']['friendRequests'][$accountID];
		
		$friendRequests = $db->prepare("SELECT * FROM friendreqs INNER JOIN users ON (friendreqs.accountID = users.extID AND friendreqs.accountID != :accountID) OR (friendreqs.toAccountID = users.extID AND friendreqs.toAccountID != :accountID) WHERE friendreqs.".($getSent ? 'accountID' : 'toAccountID')." = :accountID ORDER BY friendreqs.uploadDate DESC LIMIT 10 OFFSET ".$pageOffset);
		$friendRequests->execute([':accountID' => $accountID]);
		$friendRequests = $friendRequests->fetchAll();
		
		$friendRequestsCount = $db->prepare("SELECT count(*) FROM friendreqs WHERE friendreqs.".($getSent ? 'accountID' : 'toAccountID')." = :accountID");
		$friendRequestsCount->execute([':accountID' => $accountID]);
		$friendRequestsCount = $friendRequestsCount->fetchColumn();
		
		$GLOBALS['core_cache']['friendRequests'][$accountID] = ["requests" => $friendRequests, 'count' => $friendRequestsCount];
		
		return ["requests" => $friendRequests, 'count' => $friendRequestsCount];		
	}
	
	public static function canSendFriendRequest($person, $toAccountID) {
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(isset($GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID])) return $GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID];
		
		if($person['accountID'] == $toAccountID) {
			$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		$account = self::getAccountByID($toAccountID);
		if(!$account) {
			$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		if($account['fS']) {
			$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = false;
			return false;
		}

		$isFriends = self::isFriends($toAccountID, $person['accountID']);
		if($isFriends) {
			$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = false;
			return false;
		}

		$isBlocked = self::isPersonBlocked($toAccountID, $person['accountID']);
		if($isBlocked) {
			$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = false;
			return false;
		}
		
		$GLOBALS['core_cache']['canSendFriendRequest'][$person['accountID']][$toAccountID] = true;
		return true;
	}
	
	public static function sendFriendRequest($person, $toAccountID, $comment) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$sendFriendRequest = $db->prepare("INSERT INTO friendreqs (accountID, toAccountID, comment, uploadDate)
			VALUES (:accountID, :toAccountID, :comment, :timestamp)");
		$sendFriendRequest->execute([':accountID' => $accountID, ':toAccountID' => $toAccountID, ':comment' => $comment, ':timestamp' => time()]);
		
		self::logAction($person, Action::FriendRequestSend, $toAccountID);
		
		return true;
	}
	
	public static function deleteFriendRequests($person, $accounts, $logAction = true) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$deleteFriendRequests = $db->prepare("DELETE FROM friendreqs WHERE (accountID = :accountID AND toAccountID IN (".$accounts.")) OR (toAccountID = :accountID AND accountID IN (".$accounts."))");
		$deleteFriendRequests->execute([':accountID' => $accountID]);
		
		if($logAction) self::logAction($person, Action::FriendRequestDeny, $accounts);
		
		return true;
	}
	
	public static function acceptFriendRequest($person, $requestID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$getFriendRequest = self::getFriendRequestByID($accountID, $requestID);
		
		if($accountID == $getFriendRequest['accountID']) return false;
		
		self::deleteFriendRequests($accountID, $getFriendRequest['accountID'], false);
		
		$acceptFriendRequest = $db->prepare("INSERT INTO friendships (person1, person2, isNew1, isNew2)
			VALUES (:accountID, :targetAccountID, 1, 1)");
		$acceptFriendRequest->execute([':accountID' => $accountID, ':targetAccountID' => $getFriendRequest['accountID']]);
		
		self::logAction($person, Action::FriendRequestAccept, $getFriendRequest['accountID']);
		
		return true;
	}
	
	public static function getFriendRequestByID($accountID, $requestID) {
		require __DIR__."/connection.php";
		
		$friendRequest = $db->prepare("SELECT * FROM friendreqs WHERE toAccountID = :accountID AND ID = :requestID");
		$friendRequest->execute([':accountID' => $accountID, ':requestID' => $requestID]);
		$friendRequest = $friendRequest->fetch();
		
		return $friendRequest;
	}
	
	public static function blockUser($person, $targetAccountID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$isBlocked = self::isPersonBlocked($accountID, $targetAccountID);
		if($isBlocked) return false;
		
		$blockUser = $db->prepare("INSERT INTO blocks (person1, person2)
			VALUES (:accountID, :targetAccountID)");
		$blockUser->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		
		self::removeFriend($accountID, $targetAccountID, false);
		
		self::logAction($person, Action::BlockAccount, $targetAccountID);
		
		return true;
	}
	
	public static function readFriendRequest($person, $requestID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$getFriendRequest = self::getFriendRequestByID($accountID, $requestID);
		if(!$getFriendRequest) return false;
		
		$friendRequest = $db->prepare("UPDATE friendreqs SET isNew = 0 WHERE toAccountID = :accountID AND ID = :requestID");
		$friendRequest->execute([':accountID' => $accountID, ':requestID' => $requestID]);
		
		return true;
	}
	
	public static function getUsers($str, $pageOffset) {
		require __DIR__."/connection.php";
		
		$users = $db->prepare("SELECT * FROM users WHERE userID = :str OR userName LIKE CONCAT('%', :str, '%') ORDER BY stars DESC LIMIT 10 OFFSET ".$pageOffset);
		$users->execute([':str' => $str]);
		$users = $users->fetchAll();
		
		$usersCount = $db->prepare("SELECT count(*) FROM users WHERE userID = :str OR userName LIKE CONCAT('%', :str, '%')");
		$usersCount->execute([':str' => $str]);
		$usersCount = $usersCount->fetchColumn();
		
		return ["users" => $users, 'count' => $usersCount];
	}
	
	public static function getQuests() {
		require __DIR__."/connection.php";
		
		$quests = $db->prepare("SELECT * FROM quests");
		$quests->execute();
		$quests = $quests->fetchAll();
		shuffle($quests);
		
		return $quests;
	}
	
	public static function getVaultCode($code) {
		require __DIR__."/connection.php";

		if(isset($GLOBALS['core_cache']['vaultCode'][$code])) return $GLOBALS['core_cache']['vaultCode'][$code];

		$vaultCode = $db->prepare('SELECT * FROM vaultcodes WHERE code = :code');
		$vaultCode->execute([':code' => base64_encode($code)]);
		$vaultCode = $vaultCode->fetch();
		
		$GLOBALS['core_cache']['vaultCode'][$code] = $vaultCode;
		
		return $vaultCode;
	}
	
	public static function isVaultCodeUsed($person, $rewardID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return true;
		
		$accountID = $person['accountID'];
		
		$isVaultCodeUsed = $db->prepare("SELECT count(*) FROM actions WHERE type = 38 AND value = :vaultCode AND account = :accountID");
		$isVaultCodeUsed->execute([':vaultCode' => $rewardID, ':accountID' => $accountID]);
		$isVaultCodeUsed = $isVaultCodeUsed->fetchColumn() > 0;
		
		return $isVaultCodeUsed;
	}
	
	public static function useVaultCode($person, $vaultCode, $code) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		if($vaultCode['uses'] == 0) return false;
		
		$reduceUses = $db->prepare('UPDATE vaultcodes SET uses = uses - 1 WHERE rewardID = :rewardID');
		$reduceUses->execute([':rewardID' => $vaultCode['rewardID']]);
		
		self::logAction($accountID, $IP, Action::VaultCodeUse, $vaultCode['rewardID'], $vaultCode['rewards'], $code);
		
		return true;
	}
	
	public static function getPermissions() {
		return [
			'commandRate',
			'commandFeature',
			'commandEpic',
			'commandVerifycoins',
			'commandDaily',
			'commandWeekly',
			'commandEvent',
			'commandSuggest',
			'commandDelete',
			'commandSetacc',
			'commandRename',
			'commandPass',
			'commandDescription',
			'commandPublic',
			'commandSharecp',
			'commandSong',
			'commandLockComments',
			'commandLockUpdating',
			'actionRateDemon',
			'actionRateStars',
			'actionRateDifficulty',
			'actionSuggestRating',
			'actionDeleteComment',
			'dashboardGauntletCreate',
			'dashboardModTools',
			'dashboardLevelPackCreate',
			'dashboardManageSongs',
			'dashboardAddMod',
			'dashboardManageAccounts',
			'dashboardDeleteLeaderboards',
			'dashboardManageLevels',
			'dashboardManageAutomod',
			'dashboardVaultCodesManage'
		];
	}
	
	public static function getPersonPermissions($person) {
		if(isset($GLOBALS['core_cache']['allPermissions'][$person['accountID']])) return $GLOBALS['core_cache']['allPermissions'][$person['accountID']];
		
		$allPermissions = self::getPermissions();
		$personPermissions = [];
		
		if($person['accountID'] == 0 || $person['userID'] == 0) {
			foreach($allPermissions AS &$permission) $personPermissions[$permission] = false;
			
			$GLOBALS['core_cache']['allPermissions'][$person['accountID']] = $personPermissions;
			
			return $personPermissions;
		}
		
		$isAdmin = self::isAccountAdministrator($person['accountID']);
		if($isAdmin) {
			foreach($allPermissions AS &$permission) $personPermissions[$permission] = true;
			
			$GLOBALS['core_cache']['allPermissions'][$person['accountID']] = $personPermissions;
			
			return $personPermissions;
		}
		
		$getRoles = self::getPersonRoles($person);
		if(!$getRoles) {
			foreach($allPermissions AS &$permission) $personPermissions[$permission] = false;
			
			$GLOBALS['core_cache']['allPermissions'][$person['accountID']] = $personPermissions;
			
			return $personPermissions;
		}
		
		foreach($getRoles AS &$role) {
			foreach($allPermissions AS &$permission) {
				if(isset($personPermissions[$permission])) continue;
				
				switch($role[$permission]) {
					case 1:
						$personPermissions[$permission] = true;
						break;
					case 2:
						$personPermissions[$permission] = false;
						break;
				}	
			}
		}
		
		foreach($allPermissions AS &$permission) if(!isset($personPermissions[$permission])) $personPermissions[$permission] = false;
		
		$GLOBALS['core_cache']['allPermissions'][$person['accountID']] = $personPermissions;
		
		return $personPermissions;
	}
	
	public static function changeUsername($person, $targetUserName) {
		require __DIR__."/connection.php";
		require_once __DIR__."/security.php";
		
		$accountID = $person['accountID'];
		$userName = $person['userName'];
		
		if(strlen($targetUserName) > 20 || strlen($targetUserName) < 3 || is_numeric($targetUserName) || empty($targetUserName) || $targetUserName == $userName || Security::checkFilterViolation($person, $targetUserName, 0)) return false;
		
		$changeAccountUsername = $db->prepare("UPDATE accounts SET userName = :userName WHERE accountID = :accountID");
		$changeAccountUsername->execute([':userName' => $targetUserName, ':accountID' => $accountID]);
		
		$changeUserUsername = $db->prepare("UPDATE users SET userName = :userName WHERE extID = :accountID");
		$changeUserUsername->execute([':userName' => $targetUserName, ':accountID' => $accountID]);
		
		Security::assignAuthToken($accountID);
		
		self::logAction($person, Action::UsernameChange, $userName, $targetUserName);
		
		return true;
	}
	
	public static function changePassword($person, $targetPassword) {
		require __DIR__."/connection.php";
		require_once __DIR__."/security.php";
		
		$accountID = $person['accountID'];
		
		if(empty($targetPassword) || strlen($targetPassword) < 6) return false;
		
		$gjp2 = Security::GJP2FromPassword($targetPassword);
		$changePassword = $db->prepare("UPDATE accounts SET password = :password, gjp2 = :gjp2 WHERE accountID = :accountID");
		$changePassword->execute([':password' => Security::hashPassword($targetPassword), ':gjp2' => Security::hashPassword($gjp2), ':accountID' => $accountID]);
		
		Security::assignAuthToken($accountID);
		
		self::logAction($person, Action::PasswordChange);
		
		return true;
	}
	
	public static function updateOrbsAndCompletedLevels($person, $orbs, $levels) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		
		$updateStats = $db->prepare("UPDATE users SET orbs = :orbs, completedLvls = :levels WHERE extID = :accountID");
		$updateStats->execute([':orbs' => $orbs, ':levels' => $levels, ':accountID' => $accountID]);
		
		return true;
	}
	
	public static function cacheAccountsByID($accountIDs) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$accountIDsString = Escape::multiple_ids(implode(',', $accountIDs));
		
		$getAccounts = $db->prepare("SELECT * FROM accounts WHERE accountID IN (".$accountIDsString.")");
		$getAccounts->execute();
		$getAccounts = $getAccounts->fetchAll();
		
		foreach($getAccounts AS &$account) $GLOBALS['core_cache']['accounts']['accountID'][$account['accountID']] = $account;
		
		return $getAccounts;
	}
	
	public static function cacheAccountsByUserNames($userNames) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$userNamesString = Escape::text(implode("','", $userNames));
		
		$getAccounts = $db->prepare("SELECT * FROM accounts WHERE userName IN ('".$userNamesString."')");
		$getAccounts->execute();
		$getAccounts = $getAccounts->fetchAll();
		
		foreach($getAccounts AS &$account) $GLOBALS['core_cache']['accounts']['userName'][$account['userName']] = $account;
		
		return $getAccounts;
	}
	
	public static function cacheUsersByID($userIDs) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$userIDsString = Escape::multiple_ids(implode(',', $userIDs));
		
		$getUsers = $db->prepare("SELECT * FROM users WHERE userID IN (".$userIDsString.")");
		$getUsers->execute();
		$getUsers = $getUsers->fetchAll();
		
		foreach($getUsers AS &$user) $GLOBALS['core_cache']['user']['userID'][$user['userID']] = $user;
		
		return $getUsers;
	}
	
	public static function cacheUsersByUserNames($userNames) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$userNamesString = Escape::text(implode("','", $userNames));
		
		$getUsers = $db->prepare("SELECT * FROM users WHERE userName IN ('".$userNamesString."') ORDER BY isRegistered DESC");
		$getUsers->execute();
		$getUsers = $getUsers->fetchAll();
		
		$userNames = [];
		
		foreach($getUsers AS &$user) {
			if($userNames[strtolower($user['userName'])]) continue;
			
			$userNames[strtolower($user['userName'])] = true;
			$GLOBALS['core_cache']['user']['userName'][$user['userName']] = $user;
		}
		
		return $getUsers;
	}
	
	public static function getProfileStatsCount($person, $userID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['profileStatsCount'][$userID])) return $GLOBALS['core_cache']['profileStatsCount'][$userID];
		
		$user = self::getUserByID($userID);
		if(!$user) {
			$GLOBALS['core_cache']['profileStatsCount'][$userID] = ['posts' => 0, 'comments' => 0, 'scores' => 0, 'songs' => 0, 'sfxs' => 0, 'bans' => 0];
			return $GLOBALS['core_cache']['profileStatsCount'][$userID];
		}
		$accountID = $user['extID'];
		$IP = self::convertIPForSearching($user['IP']);
		
		$postsCount = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID");
		$postsCount->execute([':userID' => $userID]);
		$postsCount = $postsCount->fetchColumn();
		
		$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $userID);
		if($canSeeCommentHistory) {		
			$levelsCommentsCount = $db->prepare("SELECT count(*) FROM users INNER JOIN comments ON comments.userID = users.userID INNER JOIN levels ON levels.levelID = comments.levelID WHERE users.userID = :userID AND levels.unlisted = 0");
			$levelsCommentsCount->execute([':userID' => $userID]);
			$levelsCommentsCount = $levelsCommentsCount->fetchColumn();
			
			$listsCommentsCount = $db->prepare("SELECT count(*) FROM users INNER JOIN comments ON comments.userID = users.userID INNER JOIN lists ON lists.listID = comments.levelID * -1 WHERE users.userID = :userID AND lists.unlisted = 0");
			$listsCommentsCount->execute([':userID' => $userID]);
			$listsCommentsCount = $listsCommentsCount->fetchColumn();
		} else $levelsCommentsCount = $listsCommentsCount = 0;
		
		$levelScoresCount = $db->prepare("SELECT count(*) FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE users.userID = :userID");
		$levelScoresCount->execute([':userID' => $userID]);
		$levelScoresCount = $levelScoresCount->fetchColumn();
		
		$platformerScoresCount = $db->prepare("SELECT count(*) FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE users.userID = :userID");
		$platformerScoresCount->execute([':userID' => $userID]);
		$platformerScoresCount = $platformerScoresCount->fetchColumn();
		
		$songsCount = $db->prepare("SELECT count(*) FROM songs WHERE reuploadID = :accountID AND isDisabled = 0");
		$songsCount->execute([':accountID' => $accountID]);
		$songsCount = $songsCount->fetchColumn();
		
		$SFXsCount = $db->prepare("SELECT count(*) FROM sfxs WHERE reuploadID = :accountID AND isDisabled = 0");
		$SFXsCount->execute([':accountID' => $accountID]);
		$SFXsCount = $SFXsCount->fetchColumn();
		
		$bansCount = $db->prepare("SELECT count(*) FROM bans WHERE ((person = :accountID AND personType = 0) OR (person = :userID AND personType = 1) OR (person = :IP AND personType = 2))");
		$bansCount->execute([':accountID' => $accountID, ':userID' => $userID, ':IP' => $IP]);
		$bansCount = $bansCount->fetchColumn();
		
		$GLOBALS['core_cache']['profileStatsCount'][$userID] = ['posts' => $postsCount, 'comments' => $levelsCommentsCount + $listsCommentsCount, 'scores' => $levelScoresCount + $platformerScoresCount, 'songs' => $songsCount, 'sfxs' => $SFXsCount, 'bans' => $bansCount];
		
		return $GLOBALS['core_cache']['profileStatsCount'][$userID];
	}
	
	public static function getAccounts($filters, $order, $orderSorting, $pageOffset, $noLimit = false) {
		require __DIR__."/connection.php";

		$accounts = $db->prepare("SELECT * FROM users INNER JOIN accounts ON users.extID = accounts.accountID WHERE (".implode(") AND (", $filters).") ".($order ? "ORDER BY ".$order." ".$orderSorting : "")." ".(!$noLimit ? "LIMIT 10 OFFSET ".$pageOffset : ''));
		$accounts->execute();
		$accounts = $accounts->fetchAll();
		
		$accountsCount = $db->prepare("SELECT count(*) FROM users INNER JOIN accounts ON users.extID = accounts.accountID WHERE (".implode(" ) AND ( ", $filters).")");
		$accountsCount->execute();
		$accountsCount = $accountsCount->fetchColumn();
		
		return ["accounts" => $accounts, "count" => $accountsCount];
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
	
	public static function isAbleToUploadLevel($person, $levelName, $levelDesc) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/automod.php";
		require_once __DIR__."/security.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return ["success" => false, "error" => LoginError::WrongCredentials];
		
		$accountID = $person['accountID'];
		$userID = $person['userID'];
		$IP = $person['IP'];
		
		$checkBan = self::getPersonBan($person, 2);
		if($checkBan) return ["success" => false, "error" => CommonError::Banned];
		
		if(is_numeric($accountID)) { // Numeric account ID = registered account
			$account = self::getAccountByID($accountID);
			if($account && $account['registerDate'] > time() - $minAccountDate) return ["success" => false, "error" => LevelUploadError::TooFast];
		}
		
		$checkGlobalRateLimit = Security::checkRateLimits($person, 0);
		if(!$checkGlobalRateLimit) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		$checkPerUserRateLimit = Security::checkRateLimits($person, 1);
		if(!$checkPerUserRateLimit) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		$checkACEExploitRateLimit = Security::checkRateLimits($person, 6);
		if(!$checkACEExploitRateLimit) return ["success" => false, "error" => CommonError::Automod];
		
		if(Security::checkFilterViolation($person, $levelName, 3) || Security::checkFilterViolation($person, $levelDesc, 3)) return ["success" => false, "error" => CommonError::Filter];
		
		if(Automod::isLevelsDisabled(0)) return ["success" => false, "error" => CommonError::Automod];
		
		return ["success" => true];
	}
	
	public static function uploadLevel($person, $levelID, $levelName, $levelString, $levelDetails) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/automod.php";
		require_once __DIR__."/security.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return ['success' => false, 'error' => LoginError::WrongCredentials];
		
		$accountID = $person['accountID'];
		$userID = $person['userID'];
		$IP = $person['IP'];
		
		$timestamp = time();
		
		if(!Security::validateLevel($levelString, $levelDetails['gameVersion'])) {
			self::logAction($person, Action::LevelMalicious, $levelName, $levelDetails['levelDesc'], $levelID);
			return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
		}
		
		$checkLevelExistenceByID = $db->prepare("SELECT updateLocked, starStars, levelVersion FROM levels WHERE levelID = :levelID AND userID = :userID AND isDeleted = 0");
		$checkLevelExistenceByID->execute([':levelID' => $levelID, ':userID' => $userID]);
		$checkLevelExistenceByID = $checkLevelExistenceByID->fetch();
		if($checkLevelExistenceByID) {
			if($checkLevelExistenceByID['updateLocked'] || (!$ratedLevelsUpdates && $checkLevelExistenceByID['starStars'] > 0 && !in_array($levelID, $ratedLevelsUpdatesExceptions))) return ['success' => false, 'error' => LevelUploadError::UploadingDisabled];
			
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			
			if($saveLevelVersions) {
				$levelVersion = (int)$checkLevelExistenceByID['levelVersion'];
				
				rename(__DIR__.'/../../data/levels/'.$levelID, __DIR__.'/../../data/levels/versions/'.$levelID.'_'.$levelVersion);
				
				$oldLevelVersionPath = realpath(__DIR__.'/../../data/levels/versions/'.$levelID.'_'.($levelVersion - $maxLevelVersionsSaves));
				if(file_exists($oldLevelVersionPath)) unlink($oldLevelVersionPath);
			}
			
			rename(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, __DIR__.'/../../data/levels/'.$levelID);
			
			$updateLevel = $db->prepare('UPDATE levels SET gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = levelVersion + 1, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, IP = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID');
			$updateLevel->execute([':levelID' => $levelID, ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			
			self::logAction($person, Action::LevelChange, $levelName, $levelDetails['levelDesc'], $levelID);
			return ["success" => true, "levelID" => (string)$levelID];
		}
		
		$checkLevelExistenceByName = $db->prepare("SELECT levelID, updateLocked, starStars, levelVersion FROM levels WHERE levelName LIKE :levelName AND userID = :userID AND isDeleted = 0 ORDER BY levelID DESC LIMIT 1");
		$checkLevelExistenceByName->execute([':levelName' => $levelName, ':userID' => $userID]);
		$checkLevelExistenceByName = $checkLevelExistenceByName->fetch();
		if($checkLevelExistenceByName) {
			if($checkLevelExistenceByName['updateLocked'] || (!$ratedLevelsUpdates && $checkLevelExistenceByName['starStars'] > 0 && !in_array($checkLevelExistenceByName['levelID'], $ratedLevelsUpdatesExceptions))) return ['success' => false, 'error' => LevelUploadError::UploadingDisabled];
			
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			
			if($saveLevelVersions) {
				$levelVersion = (int)$checkLevelExistenceByName['levelVersion'];
				
				rename(__DIR__.'/../../data/levels/'.$checkLevelExistenceByName['levelID'], __DIR__.'/../../data/levels/versions/'.$checkLevelExistenceByName['levelID'].'_'.$levelVersion);
				
				$oldLevelVersionPath = __DIR__.'/../../data/levels/versions/'.$checkLevelExistenceByName['levelID'].'_'.($levelVersion - $maxLevelVersionsSaves);
				if(file_exists($oldLevelVersionPath)) unset($oldLevelVersionPath);
			}
			
			rename(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, __DIR__.'/../../data/levels/'.$checkLevelExistenceByName['levelID']);
			
			$updateLevel = $db->prepare('UPDATE levels SET gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = levelVersion + 1, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, IP = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID AND isDeleted = 0');
			$updateLevel->execute([':levelID' => $checkLevelExistenceByName['levelID'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			
			self::logAction($person, Action::LevelChange, $levelName, $levelDetails['levelDesc'], $checkLevelExistenceByName['levelID']);
			return ["success" => true, "levelID" => (string)$checkLevelExistenceByName['levelID']];
		}
		
		$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, $levelString);
		if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
		
		$uploadLevel = $db->prepare("INSERT INTO levels (userID, extID, gameVersion, binaryVersion, levelName, levelDesc, levelVersion, levelLength, audioTrack, auto, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, unlisted, unlisted2, IP, isLDM, wt, wt2, settingsString, songIDs, sfxIDs, ts, password, uploadDate, updateDate)
			VALUES (:userID, :accountID, :gameVersion, :binaryVersion, :levelName, :levelDesc, 1, :levelLength, :audioTrack, :auto, :original, :twoPlayer, :songID, :objects, :coins, :requestedStars, :extraString, '', :levelInfo, :unlisted, :unlisted, :IP, :isLDM, :wt, :wt2, :settingsString, :songIDs, :sfxIDs, :ts, :password, :timestamp, 0)");
		$uploadLevel->execute([':userID' => $userID, ':accountID' => $accountID, ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelName' => $levelName, ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => $timestamp, ':IP' => $IP]);
		$levelID = $db->lastInsertId();
		
		rename(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, __DIR__.'/../../data/levels/'.$levelID);
		self::logAction($person, Action::LevelUpload, $levelName, $levelDetails['levelDesc'], $levelID);
		
		Automod::checkLevelsCount();
		
		return ["success" => true, "levelID" => (string)$levelID];
	}
	
	public static function getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset, $noLimit = false) {
		require __DIR__."/connection.php";

		$levels = $db->prepare("SELECT * FROM levels ".$queryJoin." WHERE (".implode(") AND (", $filters).") AND isDeleted = 0 ".($order ? "ORDER BY ".$order." ".$orderSorting : "")." ".(!$noLimit ? "LIMIT 10 OFFSET ".$pageOffset : ''));
		$levels->execute();
		$levels = $levels->fetchAll();
		
		$levelsCount = $db->prepare("SELECT count(*) FROM levels ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).") AND isDeleted = 0");
		$levelsCount->execute();
		$levelsCount = $levelsCount->fetchColumn();
		
		return ["levels" => $levels, "count" => $levelsCount];
	}
	
	public static function canAccountPlayLevel($person, $level) {
		require __DIR__."/../../config/misc.php";
		
		$accountID = $person['accountID'];
		
		if($unlistedLevelsForAdmins && self::isAccountAdministrator($accountID)) return true;
		
		return $level['unlisted'] != 1 || ($accountID == $level['extID'] || self::isFriends($accountID, $level['extID']));
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
		
		if(isset($GLOBALS['core_cache']['levels'][$levelID])) return $GLOBALS['core_cache']['levels'][$levelID];
		
		$level = $db->prepare('SELECT * FROM levels WHERE levelID = :levelID AND isDeleted = 0');
		$level->execute([':levelID' => $levelID]);
		$level = $level->fetch();
		
		$GLOBALS['core_cache']['levels'][$levelID] = $level;
		
		return $level;
	}
	
	public static function addDownloadToLevel($person, $levelID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		$getDownloads = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID = :levelID AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$getDownloads->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		$getDownloads = $getDownloads->fetchColumn();
		if($getDownloads) return false;
		
		$addDownload = $db->prepare("UPDATE levels SET downloads = downloads + 1 WHERE levelID = :levelID AND isDeleted = 0");
		$addDownload->execute([':levelID' => $levelID]);
		$insertAction = $db->prepare("INSERT INTO actions_downloads (levelID, ip, accountID)
			VALUES (:levelID, INET6_ATON(:IP), :accountID)");
		$insertAction->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		
		return true;
	}
	
	public static function showCommentsBanScreen($text, $time) {
		$time = $time - time();
		if($time < 0) $time = 0;
		return $_POST['gameVersion'] > 20 ? 'temp_'.$time.'_</c>'.PHP_EOL.' '.$text.'<cc> ' : '-10';
	}
	
	public static function getCommentsOfLevel($levelID, $sortMode, $pageOffset, $count = 10) {
		require __DIR__."/connection.php";
		
		$comments = $db->prepare("SELECT *, levels.userID AS creatorUserID FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE levels.levelID = :levelID AND levels.isDeleted = 0 ORDER BY ".$sortMode." DESC LIMIT ".$count." OFFSET ".$pageOffset);
		$comments->execute([':levelID' => $levelID]);
		$comments = $comments->fetchAll();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE levels.levelID = :levelID AND levels.isDeleted = 0");
		$commentsCount->execute([':levelID' => $levelID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		return ["comments" => $comments, "count" => $commentsCount];
	}
	
	public static function uploadComment($person, $levelID, $comment, $percent) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/misc.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/automod.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$userID = $person['userID'];
		$userName = $person['userName'];
		
		if($enableCommentLengthLimiter) $comment = mb_substr($comment, 0, $maxCommentLength);
		
		$comment = Escape::url_base64_encode($comment);
		
		$uploadComment = $db->prepare("INSERT INTO comments (userID, levelID, percent, comment, timestamp)
			VALUES (:userID, :levelID, :percent, :comment, :timestamp)");
		$uploadComment->execute([':userID' => $userID, ':levelID' => $levelID, ':percent' => $percent, ':comment' => $comment, ':timestamp' => time()]);
		$commentID = $db->lastInsertId();

		self::logAction($person, Action::CommentUpload, $userName, $comment, $commentID, $levelID);
		
		Automod::checkCommentsSpamming($userID);
		
		return $commentID;
	}
	
	public static function getFirstMentionedLevel($text) {
		require __DIR__."/../../config/misc.php";
		if(!$mentionLevelsInComments) return false;
		
		$textArray = explode(' ', $text);
		foreach($textArray AS &$element) {
			if(substr($element, 0, 1) != "#") continue;
			
			$element = substr($element, 1);
			if(!is_numeric($element)) continue;
			
			return $element;
		}
		return false;
	}
	
	public static function getLevelDifficulty($difficulty) {
		switch(strtolower($difficulty)) {
			case 1:
			case "auto":
				return ["name" => "Auto", "difficulty" => 50, "auto" => 1, "demon" => 0];
			case 2:
			case "easy":
				return ["name" => "Easy", "difficulty" => 10, "auto" => 0, "demon" => 0];
			case 3:
			case "normal":
				return ["name" => "Normal", "difficulty" => 20, "auto" => 0, "demon" => 0];
			case 4:
			case 5:
			case "hard":
				return ["name" => "Hard", "difficulty" => 30, "auto" => 0, "demon" => 0];
			case 6:
			case 7:
			case "harder":
				return ["name" => "Harder", "difficulty" => 40, "auto" => 0, "demon" => 0];
			case 8:
			case 9:
			case "insane":
				return ["name" => "Insane", "difficulty" => 50, "auto" => 0, "demon" => 0];
			case 10:
			case "demon":
			case "harddemon":
			case "hard_demon":
			case "hard demon":
				return ["name" => "Hard Demon", "difficulty" => 60, "auto" => 0, "demon" => 1];
			case "easydemon":
			case "easy_demon":
			case "easy demon":
				return ["name" => "Easy Demon", "difficulty" => 70, "auto" => 0, "demon" => 3];
			case "mediumdemon":
			case "medium_demon":
			case "medium demon":
				return ["name" => "Medium Demon", "difficulty" => 80, "auto" => 0, "demon" => 4];
			case "insanedemon":
			case "insane_demon":
			case "insane demon":
				return ["name" => "Insane Demon", "difficulty" => 90, "auto" => 0, "demon" => 5];
			case "extremedemon":
			case "extreme_demon":
			case "extreme demon":
				return ["name" => "Extreme Demon", "difficulty" => 100, "auto" => 0, "demon" => 6];
			default:
				return ["name" => "N/A", "difficulty" => 0, "auto" => 0, "demon" => 0];
		}
	}
	
	public static function prepareDifficultyForRating($difficulty, $auto = false, $demon = false, $demonDiff = false) {
		if($auto) return "auto";
		
		if($demon) {
			switch($demonDiff) {
				case 3:
					return "easy demon";
				case 4:
					return "medium demon";
				case 5:
					return "insane demon";
				case 6:
					return "extreme demon";
				default:
					return "hard demon";
			}
		}
		
		switch(true) {
			case $difficulty >= 9.5:
				return "extreme demon";
			case $difficulty >= 8.5:
				return "insane demon";
			case $difficulty >= 7.5:
				return "medium demon";
			case $difficulty >= 6.5:
				return "easy demon";
			case $difficulty >= 5.5:
				return "hard demon";
			case $difficulty >= 4.5:
				return "insane";
			case $difficulty >= 3.5:
				return "harder";
			case $difficulty >= 2.5:
				return "hard";
			case $difficulty >= 1.5:
				return "normal";
			case $difficulty >= 0.5:
				return "easy";
			default:
				return "na";
		}
	}
	
	public static function rateLevel($levelID, $person, $difficulty, $stars, $verifyCoins, $featuredValue) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$level = self::getLevelByID($levelID);
		
		$realDifficulty = self::getLevelDifficulty($difficulty);
		
		if($featuredValue) {
			$epic = $featuredValue - 1;
			$featured = $level['starFeatured'] ?: self::nextFeaturedID();
		} else $epic = $featured = 0;
		
		$starCoins = $verifyCoins != 0 ? 1 : 0;
		$starDemon = $realDifficulty['demon'] != 0 ? 1 : 0;
		$demonDiff = $realDifficulty['demon'] != 1 ? $realDifficulty['demon'] : 0;
		
		$rateLevel = $db->prepare("UPDATE levels SET starDifficulty = :starDifficulty, difficultyDenominator = 10, starStars = :starStars, starFeatured = :starFeatured, starEpic = :starEpic, starCoins = :starCoins, starDemon = :starDemon, starDemonDiff = :starDemonDiff, starAuto = :starAuto, rateDate = :rateDate WHERE levelID = :levelID AND isDeleted = 0");
		$rateLevel->execute([':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $stars, ':starFeatured' => $featured, ':starEpic' => $epic, ':starCoins' => $starCoins, ':starDemon' => $starDemon, ':starDemonDiff' => $demonDiff, ':starAuto' => $realDifficulty['auto'], ':rateDate' => time(), ':levelID' => $levelID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelRate, $realDifficulty['difficulty'], $stars, $levelID, $featuredValue, $starCoins);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return $realDifficulty['name'];
	}
	
	public static function nextFeaturedID() {
		require __DIR__."/connection.php";
		
		$featuredID = $db->prepare("SELECT starFeatured FROM levels WHERE isDeleted = 0 ORDER BY starFeatured DESC LIMIT 1");
		$featuredID->execute();
		$featuredID = $featuredID->fetchColumn() + 1;
		
		return $featuredID;
	}
	
	public static function setLevelAsDaily($levelID, $person, $type) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$isDaily = self::isLevelDaily($levelID, $type);
		if($isDaily) return false;
		
		$dailyTime = self::nextDailyTime($type);
		
		$setDaily = $db->prepare("INSERT INTO dailyfeatures (levelID, type, timestamp)
			VALUES (:levelID, :type, :timestamp)");
		$setDaily->execute([':levelID' => $levelID, ':type' => $type, ':timestamp' => $dailyTime]);
		
		self::logModeratorAction($person, ModeratorAction::LevelDailySet, 1, $dailyTime, $levelID, $type);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return $dailyTime;
	}
	
	public static function isLevelDaily($levelID, $type) {
		require __DIR__."/connection.php";
		
		$isDaily = $db->prepare("SELECT feaID FROM dailyfeatures WHERE levelID = :levelID AND type = :type AND timestamp >= :time");
		$isDaily->execute([':levelID' => $levelID, ':type' => $type, ':time' => time() - ($type ? 604800 : 86400)]);
		$isDaily = $isDaily->fetchColumn();
		
		return $isDaily;
	}
	
	public static function nextDailyTime($type) {
		require __DIR__."/connection.php";
		
		$typeTime = $type ? 604800 : 86400;
		
		$dailyTime = $db->prepare("SELECT timestamp FROM dailyfeatures WHERE type = :type AND timestamp >= :time ORDER BY timestamp DESC LIMIT 1");
		$dailyTime->execute([':type' => $type, ':time' => time() - $typeTime]);
		$dailyTime = $dailyTime->fetchColumn();
		
		if(!$dailyTime) $dailyTime = time();
		$dailyTime = $type ? strtotime(date('d.m.Y', $dailyTime)." next monday") : strtotime(date('d.m.Y', $dailyTime)." tomorrow");
		
		return $dailyTime;
	}
	
	public static function setLevelAsEvent($levelID, $person, $duration, $rewards) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$isEvent = self::isLevelEvent($levelID);
		if($isEvent) return false;
		
		$eventTime = self::nextEventTime($duration);
		
		$setEvent = $db->prepare("INSERT INTO events (levelID, timestamp, duration, rewards)
			VALUES (:levelID, :timestamp, :duration, :rewards)");
		$setEvent->execute([':levelID' => $levelID, ':timestamp' => $eventTime, ':duration' => $eventTime + $duration, ':rewards' => $rewards]);
		
		self::logModeratorAction($person, ModeratorAction::LevelEventSet, $eventTime + $duration, $rewards, $levelID);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return $eventTime;
	}
	
	public static function isLevelEvent($levelID) {
		require __DIR__."/connection.php";
		
		$isEvent = $db->prepare("SELECT feaID FROM events WHERE levelID = :levelID AND duration >= :time");
		$isEvent->execute([':levelID' => $levelID, ':time' => time()]);
		$isEvent = $isEvent->fetchColumn();
		
		return $isEvent;
	}
	
	public static function nextEventTime($duration) {
		require __DIR__."/connection.php";
		
		$time = time();
		
		$eventTime = $db->prepare("SELECT duration FROM events WHERE timestamp < :time AND duration >= :duration ORDER BY duration DESC LIMIT 1");
		$eventTime->execute([':time' => $time, ':duration' => $time + $duration]);
		$eventTime = $eventTime->fetchColumn();
		
		if(!$eventTime) $eventTime = $time;
		
		return $eventTime;
	}
	
	public static function sendLevel($levelID, $person, $difficulty, $stars, $featured) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$realDifficulty = self::getLevelDifficulty($difficulty);
		$starDemon = $realDifficulty['demon'] != 0 ? 1 : 0;
		$demonDiff = $realDifficulty['demon'];
		
		$isSent = self::isLevelSent($levelID, $accountID);
		if($isSent) return false;
		
		$sendLevel = $db->prepare("INSERT INTO suggest (suggestBy, suggestLevelId, suggestDifficulty, suggestStars, suggestFeatured, suggestAuto, suggestDemon, timestamp)
			VALUES (:accountID, :levelID, :starDifficulty, :starStars, :starFeatured, :starAuto, :starDemon, :timestamp)");
		$sendLevel->execute([':accountID' => $accountID, ':levelID' => $levelID, ':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $stars, ':starFeatured' => $featured, ':starAuto' => $realDifficulty['auto'], ':starDemon' => $realDifficulty['demon'], ':timestamp' => time()]);
		
		self::logModeratorAction($person, ModeratorAction::LevelSuggest, $stars, $realDifficulty['difficulty'], $levelID, $featured);
		
		return $realDifficulty['name'];
	}
	
	public static function unsendLevel($levelID, $person) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$isSent = self::isLevelSent($levelID, $person['accountID']);
		if(!$isSent) return false;
		
		$unsendLevel = $db->prepare("DELETE FROM suggest WHERE suggestLevelId = :levelID AND suggestBy = :accountID");
		$unsendLevel->execute([':levelID' => $levelID, ':accountID' => $person['accountID']]);
		
		self::logModeratorAction($person, ModeratorAction::LevelSuggestRemove, $levelID);

		return true;
	}
	
	public static function isLevelSent($levelID, $accountID) {
		require __DIR__."/connection.php";
		
		$isSent = $db->prepare("SELECT count(*) FROM suggest WHERE suggestLevelId = :levelID AND suggestBy = :accountID");
		$isSent->execute([':levelID' => $levelID, ':accountID' => $accountID]);
		$isSent = $isSent->fetchColumn();
		
		return $isSent > 0;
	}
	
	public static function removeDailyLevel($levelID, $person, $type) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$isDaily = self::isLevelDaily($levelID, $type);
		if(!$isDaily) return false;
		
		$removeDaily = $db->prepare("UPDATE dailyfeatures SET timestamp = timestamp * -1 WHERE feaID = :feaID");
		$removeDaily->execute([':feaID' => $isDaily]);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return true;
	}
	
	public static function removeEventLevel($levelID, $person) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$isEvent = self::isLevelEvent($levelID);
		if(!$isEvent) return false;
		
		$removeEvent = $db->prepare("UPDATE events SET duration = duration * -1 WHERE feaID = :feaID");
		$removeEvent->execute([':feaID' => $isEvent]);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return true;
	}
	
	public static function moveLevel($levelID, $person, $player) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$targetAccountID = $player['extID'];
		$targetUserID = $player['userID'];
		$targetUserName = $player['userName'];
		
		$setAccount = $db->prepare("UPDATE levels SET extID = :extID, userID = :userID WHERE levelID = :levelID AND isDeleted = 0");
		$setAccount->execute([':extID' => $targetAccountID, ':userID' => $targetUserID, ':levelID' => $levelID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelCreatorChange, $targetUserName, $targetUserID, $levelID);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return true;
	}
	
	public static function lockUpdatingLevel($levelID, $person, $lockUpdating) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$lockLevel = $db->prepare("UPDATE levels SET updateLocked = :updateLocked WHERE levelID = :levelID AND isDeleted = 0");
		$lockLevel->execute([':updateLocked' => $lockUpdating, ':levelID' => $levelID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelLockUpdating, $lockUpdating, '', $levelID);
		
		return true;
	}
	
	public static function deleteComment($person, $commentID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$userID = $person['userID'];
		
		$getComment = $db->prepare("SELECT * FROM comments WHERE commentID = :commentID");
		$getComment->execute([':commentID' => $commentID]);
		$getComment = $getComment->fetch();
		if(!$getComment || ($getComment['userID'] != $userID && !self::checkPermission($person, 'actionDeleteComment'))) return false;
		
		$user = self::getUserByID($getComment['userID']);
		
		$deleteComment = $db->prepare("DELETE FROM comments WHERE commentID = :commentID");
		$deleteComment->execute([':commentID' => $commentID]);
		
		self::logAction($person, Action::CommentDeletion, $person['userName'], $getComment['comment'], $user['extID'], $getComment['commentID'], $getComment['likes'] - $getComment['dislikes'], $getComment['levelID']);
		
		return true;
	}
	
	public static function renameLevel($levelID, $person, $levelName) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$level = self::getLevelByID($levelID);
		
		$renameLevel = $db->prepare("UPDATE levels SET levelName = :levelName WHERE levelID = :levelID AND isDeleted = 0");
		$renameLevel->execute([':levelID' => $levelID, ':levelName' => $levelName]);
		
		self::logModeratorAction($person, ModeratorAction::LevelRename, $levelName, $level['levelName'], $levelID);
		
		return true;
	}
	
	public static function changeLevelPassword($levelID, $person, $newPassword) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if($newPassword == '000000') $newPassword = '';
		
		$level = self::getLevelByID($levelID);
		
		$changeLevelPassword = $db->prepare("UPDATE levels SET password = :password WHERE levelID = :levelID AND isDeleted = 0");
		$changeLevelPassword->execute([':levelID' => $levelID, ':password' => "1".$newPassword]);
		
		self::logModeratorAction($person, ModeratorAction::LevelPasswordChange, "1".$newPassword, $level['password'], $levelID);
		
		return true;
	}
	
	public static function changeLevelSong($levelID, $person, $songID) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$level = self::getLevelByID($levelID);
		
		$changeLevelSong = $db->prepare("UPDATE levels SET songID = :songID WHERE levelID = :levelID AND isDeleted = 0");
		$changeLevelSong->execute([':levelID' => $levelID, ':songID' => $songID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelChangeSong, $songID, $level['songID'], $levelID);
		
		if($automaticCron) Cron::updateSongsUsage($person, false);
		
		return true;
	}
	
	public static function changeLevelDescription($levelID, $person, $description) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$userID = $person['userID'];
		
		$level = self::getLevelByID($levelID);
		
		$description = Escape::url_base64_encode($description);
		
		$changeLevelDescription = $db->prepare("UPDATE levels SET levelDesc = :levelDesc WHERE levelID = :levelID AND isDeleted = 0");
		$changeLevelDescription->execute([':levelID' => $levelID, ':levelDesc' => $description]);
		
		if($level['userID'] == $userID) self::logAction($person, Action::LevelChange, $level['levelName'], $description, $levelID);
		else self::logModeratorAction($person, ModeratorAction::LevelDescriptionChange, $description, $level['levelDesc'], $levelID);
		
		return true;
	}
	
	public static function changeLevelPrivacy($levelID, $person, $privacy) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$changeLevelPrivacy = $db->prepare("UPDATE levels SET unlisted = :privacy, unlisted2 = :privacy WHERE levelID = :levelID AND isDeleted = 0");
		$changeLevelPrivacy->execute([':levelID' => $levelID, ':privacy' => $privacy]);
		
		self::logModeratorAction($person, ModeratorAction::LevelPrivacyChange, $privacy, '', $levelID);
		
		return true;
	}
	
	public static function shareCreatorPoints($levelID, $person, $targetUserID) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$changeLevel = $db->prepare("UPDATE levels SET isCPShared = 1 WHERE levelID = :levelID");
		$changeLevel->execute([':levelID' => $levelID]);
		
		$checkIfShared = $db->prepare("SELECT count(*) FROM cpshares WHERE levelID = :levelID AND userID = :userID");
		$checkIfShared->execute([':levelID' => $levelID, ':userID' => $targetUserID]);
		$checkIfShared = $checkIfShared->fetchColumn();
		if($checkIfShared) return false;
		
		$shareCreatorPoints = $db->prepare("INSERT INTO cpshares (levelID, userID)
			VALUES (:levelID, :userID)");
		$shareCreatorPoints->execute([':levelID' => $levelID, ':userID' => $targetUserID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelCreatorPointsShare, $targetUserID, '', $levelID);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return true;
	}
	
	public static function lockCommentingOnLevel($levelID, $person, $lockCommenting) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;

		$lockLevel = $db->prepare("UPDATE levels SET commentLocked = :commentLocked WHERE levelID = :levelID AND isDeleted = 0");
		$lockLevel->execute([':commentLocked' => $lockCommenting, ':levelID' => $levelID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelLockCommenting, $lockCommenting, '', $levelID);
		
		return true;
	}
	
	public static function isAbleToComment($levelID, $person, $comment) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/automod.php";
		require_once __DIR__."/security.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return ["success" => false, "error" => LoginError::WrongCredentials];
		
		$accountID = $person['accountID'];
		
		$checkBan = self::getPersonBan($person, 3);
		if($checkBan) return ["success" => false, "error" => CommonError::Banned, "info" => $checkBan];
		
		if(is_numeric($accountID)) { // Numeric account ID = registered account
			$account = self::getAccountByID($accountID);
			if($account && $account['registerDate'] > time() - $minAccountDate) return ["success" => false, "error" => CommonError::Automod];
		}

		$item = $levelID > 0 ? self::getLevelByID($levelID) : self::getListByID($levelID * -1);
		if($item['commentLocked']) return ["success" => false, "error" => CommonError::Disabled];
		
		if(Security::checkFilterViolation($person, $comment, 3)) return ["success" => false, "error" => CommonError::Filter];
		
		if(Automod::isLevelsDisabled(1)) return ["success" => false, "error" => CommonError::Automod];
		
		return ["success" => true];
	}
	
	public static function isAbleToAccountComment($person, $comment) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/automod.php";
		require_once __DIR__."/security.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return ["success" => false, "error" => LoginError::WrongCredentials];
		
		$checkBan = self::getPersonBan($person, 3);
		if($checkBan) return ["success" => false, "error" => CommonError::Banned, "info" => $checkBan];
		
		if(is_numeric($accountID)) { // Numeric account ID = registered account
			$account = self::getAccountByID($accountID);
			if($account && $account['registerDate'] > time() - $minAccountDate) return ["success" => false, "error" => CommonError::Automod];
		}
		
		if(Security::checkFilterViolation($person, $comment, 3)) return ["success" => false, "error" => CommonError::Filter];
		
		if(Automod::isAccountsDisabled(1)) return ["success" => false, "error" => CommonError::Automod];
		
		return ["success" => true];
	}
	
	public static function deleteLevel($levelID, $person) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$userID = $person['userID'];
		
		$level = self::getLevelByID($levelID);
		
		if($disallowDeletingUpdateLockedLevel && $level['updateLocked']) return false;
		
		if($disallowDeletingLevelByBannedPerson) {
			$checkBan = self::getPersonBan($person, 2);
			if($checkBan) return false;
		}
		
		$deleteLevel = $db->prepare("UPDATE levels SET isDeleted = 1 WHERE levelID = :levelID AND isDeleted = 0");
		$deleteLevel->execute([':levelID' => $levelID]);
		
		if($level['userID'] == $userID) self::logAction($person, Action::LevelDeletion, $levelID, $level['levelName']);
		else self::logModeratorAction($person, ModeratorAction::LevelDeletion, 1, $level['levelName'], $levelID);
		
		if(file_exists(__DIR__."/../../data/levels/".$levelID)) rename(__DIR__."/../../data/levels/".$levelID, __DIR__."/../../data/levels/deleted/".$levelID);
		
		if($automaticCron) Cron::updateCreatorPoints($person, false);
		
		return true;
	}
	
	public static function voteForLevelDifficulty($levelID, $person, $rating) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		if(self::isVotedForLevelDifficulty($levelID, $person, $rating > 5)) return false;
		
		$level = self::getLevelByID($levelID);
		$realDifficulty = self::getLevelDifficulty(self::prepareDifficultyForRating(($level['starDifficulty'] + $rating) / ($level['difficultyDenominator'] + 1)));
		
		$voteForLevelDifficulty = $db->prepare("UPDATE levels SET starDifficulty = starDifficulty + :rating, difficultyDenominator = difficultyDenominator + 1, starDemonDiff = :starDemonDiff, starAuto = :starAuto WHERE levelID = :levelID");
		$voteForLevelDifficulty->execute([':rating' => $rating, ':levelID' => $levelID, ':starDemonDiff' => $realDifficulty['demon'], ':starAuto' => $realDifficulty['auto']]);
		
		self::logAction($person, ($rating > 5 ? Action::LevelVoteDemon : Action::LevelVoteNormal), $levelID, $rating);
		
		return true;
	}
	
	public static function reportLevel($levelID, $IP) {
		require __DIR__."/connection.php";
		
		$checkIfReported = $db->prepare("SELECT count(*) FROM reports WHERE levelID = :levelID AND IP REGEXP CONCAT('((\\\D[^.])|^)(', :IP, ')(\\\D[^$])')");
		$checkIfReported->execute([':levelID' => $levelID, ':IP' => self::convertIPForSearching($IP, true)]);
		$checkIfReported = $checkIfReported->fetchColumn();
		if($checkIfReported) return false;
		
		$reportLevel = $db->prepare("INSERT INTO reports (levelID, IP)
			VALUES (:levelID, :IP)");
		$reportLevel->execute([':levelID' => $levelID, ':IP' => $IP]);
		
		return true;
	}
	
	public static function submitLevelScore($levelID, $person, $percent, $attempts, $clicks, $time, $progresses, $coins, $dailyID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/automod.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0 || Automod::isLevelsDisabled(2)) return false;
		
		$checkBan = self::getPersonBan($person, 0);
		if($checkBan) return false;
		
		$accountID = $person['accountID'];
		$condition = $dailyID ? ">" : "=";
		$level = self::getLevelByID($levelID);
		
		if($coins < 0 || $coins > $level['coins']) {
			self::banPerson(0, $person, "Good level score, buddy!", 0, 0, 2147483647, "Person tried to post level score with invalid coins value. (".$coins.")");
			return false;
		}
		if($percent < 0 || $percent > 100) {
			self::banPerson(0, $person, "Good level score, buddy!", 0, 0, 2147483647, "Person tried to post level score with invalid percent value. (".$percent.")");
			return false;
		}
		
		$progressesPercent = 0;
		$progressesArray = explode(",", $progresses);
		if(!empty($progressesArray)) foreach($progressesArray AS &$progressValue) $progressesPercent += $progressValue;

		if($percent != $progressesPercent) {
			self::banPerson(0, $person, "Good level score, buddy!", 0, 0, 2147483647, "Person tried to post level score with invalid progresses value. (".$percent.", \"".$progresses."\" -> ".$progressesPercent.")");
			return false;
		}
		
		$oldPercent = $db->prepare("SELECT percent FROM levelscores WHERE accountID = :accountID AND levelID = :levelID AND dailyID ".$condition." 0");
		$oldPercent->execute([':accountID' => $accountID, ':levelID' => $levelID]);
		$oldPercent = $oldPercent->fetchColumn();
		if(!$oldPercent && $percent > 0) {
			$submitLevelScore = $db->prepare("INSERT INTO levelscores (accountID, levelID, percent, uploadDate, coins, attempts, clicks, time, progresses, dailyID)
				VALUES (:accountID, :levelID, :percent, :timestamp, :coins, :attempts, :clicks, :time, :progresses, :dailyID)");
			$submitLevelScore->execute([':accountID' => $accountID, ':levelID' => $levelID, ':percent' => $percent, ':timestamp' => time(), ':coins' => $coins, ':attempts' => $attempts, ':clicks' => $clicks, ':time' => $time, ':progresses' => $progresses, ':dailyID' => $dailyID]);
			
			self::logAction($person, Action::LevelScoreSubmit, $levelID, $percent, $coins, $attempts, $clicks, $time);
			
			return true;
		} elseif($oldPercent < $percent) {
			$updateLevelScore = $db->prepare("UPDATE levelscores SET percent = :percent, uploadDate = :timestamp, coins = :coins, attempts = :attempts, clicks = :clicks, time = :time, progresses = :progresses, dailyID = :dailyID WHERE accountID = :accountID AND levelID = :levelID AND dailyID ".$condition." 0");
			$updateLevelScore->execute([':accountID' => $accountID, ':levelID' => $levelID, ':percent' => $percent, ':timestamp' => time(), ':coins' => $coins, ':attempts' => $attempts, ':clicks' => $clicks, ':time' => $time, ':progresses' => $progresses, ':dailyID' => $dailyID]);
			
			self::logAction($person, Action::LevelScoreUpdate, $levelID, $percent, $coins, $attempts, $clicks, $time);
			
			return true;
		}
		
		return false;
	}
	
	public static function getLevelScores($levelID, $person, $type, $dailyID, $pageOffset = false) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$condition = $dailyID ? ">" : "=";
		
		$queryText = self::getBannedPeopleQuery(0, true);
		
		switch($type) {
			case 0:
				$friendsArray = self::getFriends($accountID);
				$friendsArray[] = $accountID;
				$friendsString = "'".implode("','", $friendsArray)."'";
				
				$levelScores = $db->prepare("SELECT *, levelscores.coins AS scoreCoins FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND accountID IN (".$friendsString.") ORDER BY percent DESC, uploadDate ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND accountID IN (".$friendsString.")");
				$levelScoresCount->execute([':levelID' => $levelID]);
				break;
			case 1:
				$levelScores = $db->prepare("SELECT *, levelscores.coins AS scoreCoins FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID ORDER BY percent DESC, uploadDate ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID");
				$levelScoresCount->execute([':levelID' => $levelID]);
				break;
			case 2:
				$time = time() - 604800;
			
				$levelScores = $db->prepare("SELECT *, levelscores.coins AS scoreCoins FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND uploadDate > :time ORDER BY percent DESC, uploadDate ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID, ':time' => $time]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM levelscores INNER JOIN users ON users.extID = levelscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND uploadDate > :time");
				$levelScoresCount->execute([':levelID' => $levelID, ':time' => $time]);
				break;
			default:
				return false;
		}
		
		$levelScores = $levelScores->fetchAll();
		$levelScoresCount = $levelScoresCount->fetchColumn();
		
		return ['scores' => $levelScores, 'count' => $levelScoresCount];
	}
	
	public static function submitPlatformerLevelScore($levelID, $person, $scores, $attempts, $clicks, $progresses, $coins, $dailyID, $mode) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$checkBan = self::getPersonBan($person, 0);
		if($checkBan) return false;
		
		$accountID = $person['accountID'];
		$condition = $dailyID ? ">" : "=";
		$level = self::getLevelByID($levelID);
		
		if($coins > $level['coins']) {
			self::banPerson(0, $person, "Good level score, buddy!", 0, 0, 2147483647, "Person tried to post level score with invalid coins value. (".$coins.")");
			return false;
		}
		
		if($scores['time'] < 0 || $scores['points'] < 0) {
			self::banPerson(0, $person, "Good level score, buddy!", 0, 0, 2147483647, "Person tried to post level score with invalid scores value. (time: ".$scores['time'].", points: ".$scores['points'].")");
			return false;
		}
		
		if($scores['time'] == 0) return false;
		
		$oldPercent = $db->prepare("SELECT time, points FROM platscores WHERE accountID = :accountID AND levelID = :levelID AND dailyID ".$condition." 0");
		$oldPercent->execute([':accountID' => $accountID, ':levelID' => $levelID]);
		$oldPercent = $oldPercent->fetch();
		if(!$oldPercent['time']) {
			$submitLevelScore = $db->prepare("INSERT INTO platscores (accountID, levelID, time, points, timestamp, coins, attempts, clicks, progresses, dailyID)
				VALUES (:accountID, :levelID, :time, :points, :timestamp, :coins, :attempts, :clicks, :progresses, :dailyID)");
			$submitLevelScore->execute([':accountID' => $accountID, ':levelID' => $levelID, ':time' => $scores['time'], ':points' => $scores['points'], ':timestamp' => time(), ':coins' => $coins, ':attempts' => $attempts, ':clicks' => $clicks, ':progresses' => $progresses, ':dailyID' => $dailyID]);
			
			self::logAction($person, Action::PlatformerLevelScoreSubmit, $levelID, $scores['time'], $scores['points'], $attempts, $clicks, $time);
			
			return true;
		} elseif(($mode == "time" AND $oldPercent['time'] > $scores['time']) OR ($mode == "points" AND $oldPercent['points'] < $scores['points'])) {
			$updateLevelScore = $db->prepare("UPDATE platscores SET time = :time, points = :points, timestamp = :timestamp, coins = :coins, attempts = :attempts, clicks = :clicks, progresses = :progresses, dailyID = :dailyID WHERE accountID = :accountID AND levelID = :levelID AND dailyID ".$condition." 0");
			$updateLevelScore->execute([':accountID' => $accountID, ':levelID' => $levelID, ':time' => $scores['time'], ':points' => $scores['points'], ':timestamp' => time(), ':coins' => $coins, ':attempts' => $attempts, ':clicks' => $clicks, ':progresses' => $progresses, ':dailyID' => $dailyID]);
			
			self::logAction($person, Action::PlatformerLevelScoreUpdate, $levelID, $scores['time'], $scores['points'], $attempts, $clicks, $time);
			
			return true;
		}
		
		return false;
	}
	
	public static function getPlatformerLevelScores($levelID, $person, $type, $dailyID, $mode, $pageOffset = false) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$condition = $dailyID ? ">" : "=";
		$order = $mode == 'time' ? 'ASC' : 'DESC';
		
		$queryText = self::getBannedPeopleQuery(0, true);
		
		switch($type) {
			case 0:
				$friendsArray = self::getFriends($accountID);
				$friendsArray[] = $accountID;
				$friendsString = "'".implode("','", $friendsArray)."'";
				
				$levelScores = $db->prepare("SELECT *, platscores.coins AS scoreCoins FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND accountID IN (".$friendsString.") ORDER BY ".$mode." ".$order.", timestamp ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND accountID IN (".$friendsString.")");
				$levelScoresCount->execute([':levelID' => $levelID]);
				break;
			case 1:
				$levelScores = $db->prepare("SELECT *, platscores.coins AS scoreCoins FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID ORDER BY ".$mode." ".$order.", timestamp ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID");
				$levelScoresCount->execute([':levelID' => $levelID]);
				break;
			case 2:
				$levelScores = $db->prepare("SELECT *, platscores.coins AS scoreCoins FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND timestamp > :time ORDER BY ".$mode." ".$order.", timestamp ASC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
				$levelScores->execute([':levelID' => $levelID, ':time' => time() - 604800]);
				
				$levelScoresCount = $db->prepare("SELECT count(*) FROM platscores INNER JOIN users ON users.extID = platscores.accountID WHERE ".$queryText." dailyID ".$condition." 0 AND levelID = :levelID AND timestamp > :time");
				$levelScoresCount->execute([':levelID' => $levelID, ':time' => time() - 604800]);
				break;
			default:
				return ['scores' => [], 'count' => 0];
		}
		
		$levelScores = $levelScores->fetchAll();
		$levelScoresCount = $levelScoresCount->fetchColumn();
		
		return ['scores' => $levelScores, 'count' => $levelScoresCount];
	}
	
	public static function getGMDFile($levelID) {
		$level = self::getLevelByID($levelID);
		if(!$level) return false;
		
		$levelString = file_get_contents(__DIR__.'/../../data/levels/'.$levelID) ?? $level['levelString'];
		$gmdFile = '<?xml version="1.0"?><plist version="1.0" gjver="2.0"><dict>';
		
		$gmdFile .= '<k>k1</k><i>'.$levelID.'</i>';
		$gmdFile .= '<k>k2</k><s>'.$level['levelName'].'</s>';
		$gmdFile .= '<k>k3</k><s>'.$level['levelDesc'].'</s>';
		$gmdFile .= '<k>k4</k><s>'.$levelString.'</s>';
		$gmdFile .= '<k>k5</k><s>'.$level['userName'].'</s>';
		$gmdFile .= '<k>k6</k><i>'.$level['userID'].'</i>';
		$gmdFile .= '<k>k8</k><i>'.$level['audioTrack'].'</i>';
		$gmdFile .= '<k>k11</k><i>'.$level['downloads'].'</i>';
		$gmdFile .= '<k>k13</k><t />';
		$gmdFile .= '<k>k16</k><i>'.$level['levelVersion'].'</i>';
		$gmdFile .= '<k>k21</k><i>2</i>';
		$gmdFile .= '<k>k23</k><i>'.$level['levelLength'].'</i>';
		$gmdFile .= '<k>k42</k><i>'.$level['levelID'].'</i>';
		$gmdFile .= '<k>k45</k><i>'.$level['songID'].'</i>';
		$gmdFile .= '<k>k47</k><t />';
		$gmdFile .= '<k>k48</k><i>'.$level['objects'].'</i>';
		$gmdFile .= '<k>k50</k><i>'.$level['binaryVersion'].'</i>';
		$gmdFile .= '<k>k87</k><i>556365614873111</i>';
		$gmdFile .= '<k>k101</k><i>0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0</i>';
		$gmdFile .= '<k>kl1</k><i>0</i>';
		$gmdFile .= '<k>kl2</k><i>0</i>';
		$gmdFile .= '<k>kl3</k><i>1</i>';
		$gmdFile .= '<k>kl5</k><i>1</i>';
		$gmdFile .= '<k>kl6</k><k>kI6</k><d><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s><k>0</k><s>0</s></d>';
		
		$gmdFile .= '</dict></plist>';

		return $gmdFile;
	}
	
	public static function getLatestSendsByLevelID($levelID) {
		require __DIR__."/connection.php";

		if(isset($GLOBALS['core_cache']['latestSends'][$levelID])) return $GLOBALS['core_cache']['latestSends'][$levelID];

		$sendsInfo = $db->prepare("SELECT * FROM suggest WHERE suggestLevelId = :levelID ORDER BY timestamp DESC");
		$sendsInfo->execute([":levelID" => $levelID]);
		$sendsInfo = $query->fetchAll();

		$GLOBALS['core_cache']['latestSends'][$levelID] = $sendsInfo;

		return $sendsInfo;
	}
	
	public static function isVotedForLevelDifficulty($levelID, $person, $isDemonVote) {
		if($person['accountID'] == 0 || $person['userID'] == 0) return true;
	
		$filters[] = "type = ".($isDemonVote ? Action::LevelVoteDemon : Action::LevelVoteNormal);
		$filters[] = "value = ".$levelID;
	
		$isVoted = self::getPersonActions($person, $filters);
		
		return count($isVoted) > 0;
	}
	
	public static function getLevelDifficultyImage($level) {
		require __DIR__."/../../config/discord.php";
		
		$featured = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
		$starsDiff = ['stars', 'featured', 'epic', 'legendary', 'mythic'];
		$starsIcon = $starsDiff[$featured];

		$difficulty = self::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']);
		$diffArray = ['n/a' => 'na', 'auto' => 'auto', 'easy' => 'easy', 'normal' => 'normal', 'hard' => 'hard', 'harder' => 'harder', 'insane' => 'insane', 'demon' => 'demon-hard', 'easy demon' => 'demon-easy', 'medium demon' => 'demon-medium', 'hard demon' => 'demon-hard', 'insane demon' => 'demon-insane', 'extreme demon' => 'demon-extreme'];
        $diffIcon = $diffArray[strtolower($difficulty)] ?? 'na';
		
		return $difficultiesURL.$starsIcon.'/'.$diffIcon.'.png';
	}
	
	public static function getLevelStatsCount($levelID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['levelStatsCount'][$levelID])) return $GLOBALS['core_cache']['levelStatsCount'][$levelID];
		
		$level = self::getLevelByID($levelID);
		if(!$level) {
			$GLOBALS['core_cache']['levelStatsCount'][$levelID] = ['comments' => 0, 'scores' => 0];
			return ['comments' => 0, 'scores' => 0];
		}
		
		$commentsCount = $db->prepare("SELECT count(*) FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE levels.levelID = :levelID AND levels.isDeleted = 0");
		$commentsCount->execute([':levelID' => $levelID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		$queryText = self::getBannedPeopleQuery(0, true);
		
		$levelScoresCount = $db->prepare("SELECT count(*) FROM ".($level['levelLength'] == 5 ? 'plat' : 'level')."scores INNER JOIN users ON users.extID = ".($level['levelLength'] == 5 ? 'plat' : 'level')."scores.accountID WHERE ".$queryText." levelID = :levelID");
		$levelScoresCount->execute([':levelID' => $levelID]);
		$levelScoresCount = $levelScoresCount->fetchColumn();
		
		$GLOBALS['core_cache']['levelStatsCount'][$levelID] = ['comments' => $commentsCount, 'scores' => $levelScoresCount];
		
		return ['comments' => $commentsCount, 'scores' => $levelScoresCount];
	}
	
	public static function deleteScore($person, $scoreID, $isPlatformer) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		
		$getScore = $db->prepare("SELECT * FROM ".($isPlatformer ? 'plat' : 'level')."scores WHERE ".($isPlatformer ? 'ID' : 'scoreID')." = :scoreID");
		$getScore->execute([':scoreID' => $scoreID]);
		$getScore = $getScore->fetch();
		if(!$getScore || ($accountID != $getScore['accountID'] && !self::checkPermission($person, "dashboardDeleteLeaderboards"))) return false;
		
		$deleteScore = $db->prepare("DELETE FROM ".($isPlatformer ? 'plat' : 'level')."scores WHERE ".($isPlatformer ? 'ID' : 'scoreID')." = :scoreID");
		$deleteScore->execute([':scoreID' => $scoreID]);
		
		self::logModeratorAction($person, ModeratorAction::LevelScoreDelete, $getScore['percent'] ?: '', $getScore['attempts'] ?: '', $getScore['coins'] ?: '', $getScore['clicks'] ?: '', $getScore['time'] ?: '', $getScore['points'] ?: '');
		
		return true;
	}
	
	public static function cacheLevelsByID($levelIDs) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$levelIDsString = Escape::multiple_ids(implode(',', $levelIDs));
		
		$getLevels = $db->prepare("SELECT * FROM levels WHERE levelID IN (".$levelIDsString.")");
		$getLevels->execute();
		$getLevels = $getLevels->fetchAll();
		
		foreach($getLevels AS &$level) $GLOBALS['core_cache']['levels'][$level['levelID']] = $level;
		
		return $getLevels;
	}
	
	public static function getLevelSearchFilters($query, $gameVersion, $addSearchFilter, $removeDefaultFilter) {
		require __DIR__."/../../config/misc.php";
		require_once __DIR__."/exploitPatch.php";
		
		$epicParams = $diffParams = $demonParams = [];
		$filters = !$removeDefaultFilter ? ["unlisted = 0 AND unlisted2 = 0"] : [];

		$str = Escape::text(urldecode($query["str"])) ?: '';
		
		$type = Escape::number($query["type"]) ?: 0;
		$diff = Escape::multiple_ids(urldecode($query["diff"])) ?: '-';

		if(!$showAllLevels) $filters[] = "levels.gameVersion <= '".$gameVersion."'";

		if(isset($query["original"]) && $query["original"] == 1) $filters[] = "original = 0";
		if(isset($query["coins"]) && $query["coins"] == 1) $filters[] = "starCoins = 1 AND NOT levels.coins = 0";
		
		if((isset($query["uncompleted"]) || isset($query["onlyCompleted"])) && ($query["uncompleted"] == 1 || $query["onlyCompleted"] == 1)) {
			$completedLevels = Escape::multiple_ids(urldecode($query["completedLevels"]));
			$filters[] = ($query['uncompleted'] == 1 ? 'NOT ' : '')."levelID IN (".$completedLevels.")";
		}
		
		if(isset($query["song"])) {
			$song = abs(Escape::number($query["song"]) ?: 0);
			if(!isset($query["customSong"])) {
				$song = $song - 1;
				$filters[] = "audioTrack = '".$song."' AND songID = 0";
			} else $filters[] = $song == 0 ? "audioTrack = 0 AND songID > 0" : "audioTrack = 0 AND (songID = '".$song."' OR songIDs REGEXP '(\\\D|^)(".$song.")(\\\D|$)')";
		}
		
		if(isset($query["twoPlayer"]) && $query["twoPlayer"] == 1) $filters[] = "twoPlayer = 1";
		if(isset($query["star"]) && $query["star"] == 1) $filters[] = "NOT starStars = 0";
		if(isset($query["noStar"]) && $query["noStar"] == 1) $filters[] = "starStars = 0";
		
		if(isset($query["gauntlet"]) && $query["gauntlet"] != 0) {
			$gauntletID = abs(Escape::number($query["gauntlet"]));
			
			$gauntlet = self::getGauntletByID($gauntletID);
			$str = $gauntlet["level1"].",".$gauntlet["level2"].",".$gauntlet["level3"].",".$gauntlet["level4"].",".$gauntlet["level5"];

			$type = 10;
		}
		
		$len = Escape::multiple_ids(urldecode($query["len"])) ?: '-';
		if($len && $len != "-") $filters[] = "levelLength IN (".$len.")";
		
		if(isset($query["featured"]) && $query["featured"] == 1) $epicParams[] = "starFeatured > 0";
		if(isset($query["epic"]) && $query["epic"] == 1) $epicParams[] = "starEpic = 1";
		if(isset($query["mythic"]) && $query["mythic"] == 1) $epicParams[] = "starEpic = 2"; // The reason why Mythic and Legendary ratings are swapped: RobTop accidentally swapped them in-game
		if(isset($query["legendary"]) && $query["legendary"] == 1) $epicParams[] = "starEpic = 3";
		$epicFilter = implode(" OR ", $epicParams);
		if(!empty($epicFilter)) $filters[] = $epicFilter;
		
		if($diff && $diff != '-') {
			$diffArray = explode(',', $diff);
			$starAuto = $starDemon = 0;
			
			foreach($diffArray AS &$diffEntry) {
				switch($diffEntry) {
					case -1:
						$diffParams[] = "starDifficulty / difficultyDenominator < 0.5";
						
						break;
					case -3:
						$diffParams[] = "starAuto = 1";
						
						break;
					case -2:
						$demonDiffs = [0, 3, 4, 0, 5, 6];
						$demonFilter = $query["demonFilter"] ? explode(',', Escape::multiple_ids(urldecode($query["demonFilter"]))) : [];
						
						foreach($demonFilter AS &$demonDiff) $demonParams[] = "starDemonDiff = ".($demonDiffs[$demonDiff] ?: 0);
						
						$diffParams[] = "starDemon = 1".(!empty($demonParams) ? ' AND ('.implode(" OR ", $demonParams).')' : '');
						
						break;
					default:
						$diffParams[] = "starAuto = 0 AND starDemon = 0 AND starDifficulty / difficultyDenominator >= ".((int)$diffEntry - 0.5)." AND starDifficulty / difficultyDenominator < ".((int)$diffEntry + 0.5);
						break;
				}
			}
		}
		$diffFilter = implode(") OR (", $diffParams);
		if(!empty($diffFilter)) $filters[] = '('.$diffFilter.')';
		
		if($addSearchFilter) $filters[] = "levelName LIKE '%".$str."%'";
		
		return [
			'filters' => $filters,
			'type' => $type
		];
	}
	
	public static function getUserLevelScores($accountID, $pageOffset = false) {
		require __DIR__."/connection.php";
		
		$levelScores = $db->prepare("SELECT scoreID, accountID, levelscores.levelID, levelName, percent, attempts, coins AS scoreCoins, clicks, time, progresses COLLATE utf8mb3_general_ci AS progresses, dailyID, uploadDate AS timestamp, 0 AS points, 0 AS isPlatformer FROM levelscores
			JOIN (SELECT levelID, levelName FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0) levels ON levelscores.levelID = levels.levelID
			WHERE accountID = :accountID
			
			UNION
			
			SELECT ID AS scoreID, accountID, platscores.levelID, levelName, 0 AS percent, attempts, coins AS scoreCoins, clicks, time, progresses COLLATE utf8mb3_general_ci AS progresses, dailyID, timestamp, points, 1 AS isPlatformer FROM platscores
			JOIN (SELECT levelID, levelName FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0) levels ON platscores.levelID = levels.levelID
			WHERE accountID = :accountID
			
			ORDER BY timestamp DESC".($pageOffset !== false ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
		$levelScores->execute([':accountID' => $accountID]);
		$levelScores = $levelScores->fetchAll();
		
		$levelScoresCount = $db->prepare("SELECT count(*) FROM (
			SELECT scoreID, accountID, levelscores.levelID, levelName, percent, attempts, coins AS scoreCoins, clicks, time, progresses COLLATE utf8mb3_general_ci AS progresses, dailyID, uploadDate AS timestamp, 0 AS points, 0 AS isPlatformer FROM levelscores
			JOIN (SELECT levelID, levelName FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0) levels ON levelscores.levelID = levels.levelID
			WHERE accountID = :accountID
			
			UNION
			
			SELECT ID AS scoreID, accountID, platscores.levelID, levelName, 0 AS percent, attempts, coins AS scoreCoins, clicks, time, progresses COLLATE utf8mb3_general_ci AS progresses, dailyID, timestamp, points, 1 AS isPlatformer FROM platscores
			JOIN (SELECT levelID, levelName FROM levels WHERE levels.unlisted = 0 AND levels.unlisted2 = 0 AND levels.isDeleted = 0) levels ON platscores.levelID = levels.levelID
			WHERE accountID = :accountID
		) count");
		$levelScoresCount->execute([':accountID' => $accountID]);
		$levelScoresCount = $levelScoresCount->fetchColumn();
		
		return ['scores' => $levelScores, 'count' => $levelScoresCount];
	}
	
	/*
		Lists-related functions
	*/
	
	public static function getListLevels($listID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['listLevels'][$listID])) return $GLOBALS['core_cache']['listLevels'][$listID];
		
		$listLevels = $db->prepare('SELECT listlevels FROM lists WHERE listID = :listID');
		$listLevels->execute([':listID' => $listID]);
		$listLevels = $listLevels->fetchColumn();
		
		$GLOBALS['core_cache']['listLevels'][$listID] = $listLevels;

		return $listLevels;
	}
	
	public static function getMapPacks($pageOffset) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['mapPacks'])) return $GLOBALS['core_cache']['mapPacks'];
		
		$mapPacks = $db->prepare("SELECT * FROM mappacks ORDER BY ".($orderMapPacksByStars ? 'stars' : 'ID')." ASC LIMIT 10 OFFSET ".$pageOffset);
		$mapPacks->execute();
		$mapPacks = $mapPacks->fetchAll();
		
		$mapPacksCount = $db->prepare("SELECT count(*) FROM mappacks");
		$mapPacksCount->execute();
		$mapPacksCount = $mapPacksCount->fetchColumn();
		
		$GLOBALS['core_cache']['mapPacks'] = ['mapPacks' => $mapPacks, 'count' => $mapPacksCount];
		
		return ['mapPacks' => $mapPacks, 'count' => $mapPacksCount];
	}
	
	public static function getGauntlets($pageOffset = false) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['gauntlets'])) return $GLOBALS['core_cache']['gauntlets'];
		
		$gauntlets = $db->prepare("SELECT * FROM gauntlets ORDER BY ID ASC".($pageOffset ? ' LIMIT 10 OFFSET '.$pageOffset : ''));
		$gauntlets->execute();
		$gauntlets = $gauntlets->fetchAll();
		
		$gauntletsCount = $db->prepare("SELECT count(*) FROM gauntlets");
		$gauntletsCount->execute();
		$gauntletsCount = $gauntletsCount->fetchColumn();
		
		$GLOBALS['core_cache']['gauntlets'] = ['gauntlets' => $gauntlets, 'count' => $gauntletsCount];
	
		return ['gauntlets' => $gauntlets, 'count' => $gauntletsCount];
	}
	
	public static function getLists($person, $filters, $order, $pageOffset) {
		require __DIR__."/connection.php";
		
		$lists = $db->prepare("SELECT * FROM lists WHERE (".implode(") AND (", $filters).") ".($order ? "ORDER BY ".$order." DESC" : "")." LIMIT 10 OFFSET ".$pageOffset);
		$lists->execute();
		$lists = $lists->fetchAll();
		
		$listsCount = $db->prepare("SELECT count(*) FROM lists WHERE (".implode(" ) AND ( ", $filters).")");
		$listsCount->execute();
		$listsCount = $listsCount->fetchColumn();
		
		foreach($lists AS $listKey => $list) {
			$addDownload = self::addDownloadToList($person, $list['listID']);
			if($addDownload) $lists[$listKey]['downloads']++;
		}
		
		return ["lists" => $lists, "count" => $listsCount];
	}
	
	public static function addDownloadToList($person, $listID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		$getDownloads = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID = :listID AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$getDownloads->execute([':listID' => ($listID * -1), ':IP' => $IP, ':accountID' => $accountID]);
		$getDownloads = $getDownloads->fetchColumn();
		if($getDownloads) return false;
		
		$addDownload = $db->prepare("UPDATE lists SET downloads = downloads + 1 WHERE listID = :listID");
		$addDownload->execute([':listID' => $listID]);
		$insertAction = $db->prepare("INSERT INTO actions_downloads (levelID, ip, accountID)
			VALUES (:listID, INET6_ATON(:IP), :accountID)");
		$insertAction->execute([':listID' => ($listID * -1), ':IP' => $IP, ':accountID' => $accountID]);
		
		return true;
	}
	
	public static function getListByID($listID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['lists'][$listID])) return $GLOBALS['core_cache']['lists'][$listID];
		
		$list = $db->prepare('SELECT * FROM lists WHERE listID = :listID');
		$list->execute([':listID' => $listID]);
		$list = $list->fetch();
		
		$GLOBALS['core_cache']['lists'][$listID] = $list;
		
		return $list;
	}
	
	public static function getCommentsOfList($listID, $sortMode, $pageOffset, $count = 10) {
		require __DIR__."/connection.php";
		
		$comments = $db->prepare("SELECT *, lists.accountID AS creatorAccountID FROM lists INNER JOIN comments ON comments.levelID = (lists.listID * -1) WHERE lists.listID = :listID ORDER BY ".$sortMode." DESC LIMIT ".$count." OFFSET ".$pageOffset);
		$comments->execute([':listID' => $listID]);
		$comments = $comments->fetchAll();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM lists INNER JOIN comments ON comments.levelID = (lists.listID * -1) WHERE lists.listID = :listID");
		$commentsCount->execute([':listID' => $listID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		return ["comments" => $comments, "count" => $commentsCount];
	}
	
	public static function uploadList($person, $listID, $listDetails) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		if($listID != 0) {
			$list = self::getListByID($listID);
			if(!$list || $list['accountID'] != $accountID) return false;
			
			$updateList = $db->prepare('UPDATE lists SET listDesc = :listDesc, listVersion = listVersion + 1, listlevels = :listlevels, starDifficulty = :difficulty, original = :original, unlisted = :unlisted, updateDate = :timestamp WHERE listID = :listID');
			$updateList->execute([':listID' => $listID, ':listDesc' => $listDetails['listDesc'], ':listlevels' => $listDetails['listLevels'], ':difficulty' => $listDetails['difficulty'], ':original' => $listDetails['original'], ':unlisted' => $listDetails['unlisted'], ':timestamp' => time()]);
			
			self::logAction($person, Action::ListChange, $listDetails['listName'], $listDetails['listLevels'], $listID, $listDetails['difficulty'], $listDetails['unlisted']);
			//$gs->sendLogsListChangeWebhook($listID, $accountID, $list);
			return $listID;
		}
		
		$list = $db->prepare('INSERT INTO lists (listName, listDesc, listVersion, accountID, listlevels, starDifficulty, original, unlisted, uploadDate) VALUES (:listName, :listDesc, 1, :accountID, :listlevels, :difficulty, :original, :unlisted, :timestamp)');
		$list->execute([':listName' => $listDetails['listName'], ':listDesc' => $listDetails['listDesc'], ':accountID' => $accountID, ':listlevels' => $listDetails['listLevels'], ':difficulty' => $listDetails['difficulty'], ':original' => $listDetails['original'], ':unlisted' => $listDetails['unlisted'], ':timestamp' => time()]);
		$listID = $db->lastInsertId();
		
		self::logAction($person, Action::ListUpload, $listDetails['listName'], $listDetails['listLevels'], $listID, $listDetails['difficulty'], $listDetails['unlisted']);
		
		return $listID;
	}
	
	public static function deleteList($listID, $person) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$list = self::getListByID($listID);
		
		$deleteList = $db->prepare("DELETE FROM lists WHERE listID = :listID");
		$deleteList->execute([':listID' => $listID]);
		
		if($list['accountID'] == $accountID) self::logAction($person, Action::ListDeletion, $list['listName'], $list['listLevels'], $listID, $list['difficulty'], $list['unlisted']);
		else self::logModeratorAction($person, ModeratorAction::ListDeletion, 1, $list['listName'], $list['listLevels'], $listID, $list['difficulty'], $list['unlisted']);
		
		return true;
	}
	
	public static function canAccountSeeList($person, $list) {
		require __DIR__."/../../config/misc.php";
		
		$accountID = $person['accountID'];
		
		if($unlistedLevelsForAdmins && self::isAccountAdministrator($accountID)) return true;
		
		return $list['unlisted'] != 1 || ($accountID == $list['accountID'] || self::isFriends($accountID, $list['accountID']));
	}
	
	public static function rateList($listID, $person, $reward, $difficulty, $featuredValue, $levelsCount) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$list = self::getListByID($listID);
		
		$realDifficulty = self::getListDifficulty($difficulty);
		
		$featured = $featuredValue ? 1 : 0;
		
		$rateList = $db->prepare("UPDATE lists SET starDifficulty = :starDifficulty, starStars = :starStars, starFeatured = :starFeatured, rateDate = :rateDate, countForReward = :levelsCount WHERE listID = :listID");
		$rateList->execute([':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $reward, ':starFeatured' => $featured, ':rateDate' => time(), ':levelsCount' => $levelsCount, ':listID' => $listID]);
		
		self::logModeratorAction($person, ModeratorAction::ListRate, $reward, $realDifficulty['difficulty'], $listID, $featured, $levelsCount);
		
		return $realDifficulty['name'];
	}
	
	public static function getListDifficulty($difficulty) {
		switch(strtolower($difficulty)) {
			case 0:
			case "auto":
				return ["name" => "Auto", "difficulty" => 0];
			case 1:
			case "easy":
				return ["name" => "Easy", "difficulty" => 1];
			case 2:
			case "normal":
				return ["name" => "Normal", "difficulty" => 2];
			case 3:
			case "hard":
				return ["name" => "Hard", "difficulty" => 3];
			case 4:
			case "harder":
				return ["name" => "Harder", "difficulty" => 4];
			case 5:
			case "insane":
				return ["name" => "Insane", "difficulty" => 5];
			case 6:
			case "easydemon":
			case "easy_demon":
			case "easy demon":
				return ["name" => "Easy Demon", "difficulty" => 6];
			case 7:
			case "mediumdemon":
			case "medium_demon":
			case "medium demon":
				return ["name" => "Medium Demon", "difficulty" => 7];
			case 8:
			case "demon":
			case "harddemon":
			case "hard_demon":
			case "hard demon":
				return ["name" => "Hard Demon", "difficulty" => 8];
			case 9:
			case "insanedemon":
			case "insane_demon":
			case "insane demon":
				return ["name" => "Insane Demon", "difficulty" => 9];
			case 10:
			case "extremedemon":
			case "extreme_demon":
			case "extreme demon":
				return ["name" => "Extreme Demon", "difficulty" => 10];
			default:
				return ["name" => "N/A", "difficulty" => -1];
		}
	}
	
	public static function changeListPrivacy($listID, $person, $privacy) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$changeListPrivacy = $db->prepare("UPDATE lists SET unlisted = :privacy WHERE listID = :listID");
		$changeListPrivacy->execute([':listID' => $listID, ':privacy' => $privacy]);
		
		self::logModeratorAction($person, ModeratorAction::ListPrivacyChange, $privacy, '', $listID);
		
		return true;
	}
	
	public static function moveList($listID, $person, $player) {
		require __DIR__."/../../config/misc.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/cron.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$targetAccountID = $player['extID'];
		$targetUserID = $player['userID'];
		$targetUserName = $player['userName'];
		
		$setAccount = $db->prepare("UPDATE lists SET accountID = :targetAccountID WHERE listID = :listID");
		$setAccount->execute([':targetAccountID' => $targetAccountID, ':listID' => $listID]);
		
		self::logModeratorAction($person, ModeratorAction::ListCreatorChange, $targetUserName, $targetUserID, $listID);
		
		return true;
	}
	
	public static function renameList($listID, $person, $listName) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$list = self::getListByID($listID);
		
		$renameList = $db->prepare("UPDATE lists SET listName = :listName WHERE listID = :listID");
		$renameList->execute([':listID' => $listID, ':listName' => $listName]);
		
		self::logModeratorAction($person, ModeratorAction::ListRename, $listName, $list['listName'], $listID);
		
		return true;
	}
	
	public static function changeListDescription($listID, $person, $description) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$list = self::getListByID($listID);
		
		$description = Escape::url_base64_encode($description);
		
		$changeLevelDescription = $db->prepare("UPDATE lists SET listDesc = :listDesc WHERE listID = :listID");
		$changeLevelDescription->execute([':listID' => $listID, ':listDesc' => $description]);
		
		if($list['accountID'] == $accountID) self::logAction($person, Action::ListChange, $list['listName'], $description, $listID);
		else self::logModeratorAction($person, ModeratorAction::ListDescriptionChange, $description, $list['listDesc'], $listID);
		
		return true;
	}
	
	public static function lockCommentingOnList($listID, $person, $lockCommenting) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;

		$lockLevel = $db->prepare("UPDATE lists SET commentLocked = :commentLocked WHERE listID = :listID");
		$lockLevel->execute([':commentLocked' => $lockCommenting, ':listID' => $listID]);
		
		self::logModeratorAction($person, ModeratorAction::ListLockCommenting, $lockCommenting, '', $listID);
		
		return true;
	}
	
	public static function sendList($listID, $person, $reward, $difficulty, $featured, $levelsCount) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$accountID = $person['accountID'];
		
		$realDifficulty = self::getListDifficulty($difficulty);
		
		$isSent = self::isListSent($listID, $accountID);
		if($isSent) return false;
		
		$sendLevel = $db->prepare("INSERT INTO suggest (suggestBy, suggestLevelId, suggestDifficulty, suggestStars, suggestFeatured, timestamp)
			VALUES (:accountID, :listID, :starDifficulty, :starStars, :starFeatured, :timestamp)");
		$sendLevel->execute([':accountID' => $accountID, ':listID' => ($listID * -1), ':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $reward, ':starFeatured' => $featured, ':timestamp' => time()]);
		
		self::logModeratorAction($person, ModeratorAction::ListSuggest, $reward, $realDifficulty['difficulty'], $listID, $featured, $levelsCount);
		
		return $realDifficulty['name'];
	}
	
	public static function isListSent($listID, $accountID) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return true;
		
		$isSent = $db->prepare("SELECT count(*) FROM suggest WHERE suggestLevelId = :listID AND suggestBy = :accountID");
		$isSent->execute([':listID' => ($listID * -1), ':accountID' => $accountID]);
		$isSent = $isSent->fetchColumn();
		
		return $isSent > 0;
	}
	
	public static function cacheListsByID($listIDs) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$listIDsString = Escape::multiple_ids(implode(',', $listIDs));
		
		$getLists = $db->prepare("SELECT * FROM lists WHERE listID IN (".$listIDsString.")");
		$getLists->execute();
		$getLists = $getLists->fetchAll();
		
		foreach($getLists AS &$list) $GLOBALS['core_cache']['lists'][$list['listID']] = $list;
		
		return $getLists;
	}
	
	public static function getListSearchFilters($query, $addSearchFilter, $removeDefaultFilter) {
		require __DIR__."/../../config/misc.php";
		require_once __DIR__."/exploitPatch.php";
		
		$diffParams = [];
		$filters = !$removeDefaultFilter ? ["unlisted = 0"] : [];
		
		$str = Escape::text(urldecode($query["str"])) ?: '';
		$diff = Escape::multiple_ids(urldecode($query["diff"])) ?: '-';

		// Additional search parameters
		if(isset($query["star"])) $filters[] = $query["star"] == 1 ? "NOT starStars = 0" : "starStars = 0";

		// Difficulty filters
		if($diff && $diff != '-') {
			$diffArray = explode(',', $diff);
			
			foreach($diffArray AS &$diffEntry) {
				switch($diffEntry) {
					case -3:
						$diffParams[] = "starDifficulty = '0'";
						
						break;
					case -2:
						$demonDiffs = [1, 2, 3, 4, 5];
						$demonFilter = $query["demonFilter"] ? explode(',', Escape::multiple_ids(urldecode($query["demonFilter"]))) : [];
						
						foreach($demonFilter AS &$demonDiff) $demonParams[] = "starDifficulty = ".($demonDiffs[$demonDiff - 1] + 5);
						
						$diffParams[] = $demonParams ? '('.implode(" OR ", $demonParams).')' : "starDifficulty >= 6";
						
						break;
					default:
						$diffParams[] = "starDifficulty = '".$diffEntry."'";
						
						break;
				}
			}
		}
		$diffFilter = implode(") OR (", $diffParams);
		if(!empty($diffFilter)) $filters[] = '('.$diffFilter.')';
		
		if($addSearchFilter) $filters[] = "listName LIKE '%".$str."%'";
		
		return [
			'filters' => $filters,
			'type' => $type
		];
	}
	
	public static function getListDifficultyImage($list) {
		require __DIR__."/../../config/discord.php";
		
		$starsIcon = $list['starFeatured'] ? 'featured' : 'stars';
		
		$diffArray = ['-1' => 'na', '0' => 'auto', '1' => 'easy', '2' => 'normal', '3' => 'hard', '4' => 'harder', '5' => 'insane', '6' => 'demon-easy', '7' => 'demon-medium', '8' => 'demon-hard', '9' => 'demon-insane', '10' => 'demon-extreme'];
        $diffIcon = $diffArray[(string)$list['starDifficulty']] ?: 'na';
		
		return $difficultiesURL.$starsIcon.'/'.$diffIcon.'.png';
	}
	
	public static function getListStatsCount($person, $listID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		if(isset($GLOBALS['core_cache']['listStatsCount'][$listID])) return $GLOBALS['core_cache']['listStatsCount'][$listID];
		
		$accountID = $person['accountID'];
		
		$list = self::getListByID($listID);
		if(!$list) {
			$GLOBALS['core_cache']['listStatsCount'][$listID] = ['levels' => 0, 'comments' => 0];
			return ['levels' => 0, 'comments' => 0];
		}
		
		$friendsArray = self::getFriends($accountID);
		$friendsArray[] = $accountID;
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$levelsCount = $db->prepare("SELECT count(*) FROM levels WHERE levelID IN (".$list['listlevels'].") AND (unlisted != 1 OR (unlisted = 1 AND (extID IN (".$friendsString."))))");
		$levelsCount->execute();
		$levelsCount = $levelsCount->fetchColumn();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM lists INNER JOIN comments ON comments.levelID * -1 = lists.listID WHERE lists.listID = :listID");
		$commentsCount->execute([':listID' => $listID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		$GLOBALS['core_cache']['listStatsCount'][$listID] = ['levels' => $levelsCount, 'comments' => $commentsCount];
		
		return ['levels' => $levelsCount, 'comments' => $commentsCount];
	}
	
	public static function getMapPackDifficultyImage($mapPack) {
		require __DIR__."/../../config/discord.php";
		
		$diffArray = ['-1' => 'na', '0' => 'auto', '1' => 'easy', '2' => 'normal', '3' => 'hard', '4' => 'harder', '5' => 'insane', '6' => 'demon-easy', '7' => 'demon-medium', '8' => 'demon-hard', '9' => 'demon-insane', '10' => 'demon-extreme'];
        $diffIcon = $diffArray[(string)$mapPack['difficulty']] ?: 'na';
		
		return $difficultiesURL.'stars/'.$diffIcon.'.png';
	}
	
	public static function getMapPackByID($mapPackID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['mapPack'][$mapPackID])) return $GLOBALS['core_cache']['mapPack'][$mapPackID];
		
		$mapPack = $db->prepare("SELECT * FROM mappacks WHERE ID = :mapPackID");
		$mapPack->execute([':mapPackID' => $mapPackID]);
		$mapPack = $mapPack->fetch();
		
		$GLOBALS['core_cache']['mapPack'][$mapPackID] = $mapPack;
		
		return $mapPack;
	}
	
	public static function getGauntletByID($gauntletID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['gauntlet'][$gauntletID])) return $GLOBALS['core_cache']['gauntlet'][$gauntletID];
		
		$gauntlet = $db->prepare("SELECT * FROM gauntlets WHERE ID = :gauntletID");
		$gauntlet->execute([':gauntletID' => $gauntletID]);
		$gauntlet = $gauntlet->fetch();
		
		$GLOBALS['core_cache']['gauntlet'][$gauntletID] = $gauntlet;
		
		return $gauntlet;
	}
	
	public static function getGauntletImage($gauntletID) {
		require __DIR__."/../../config/discord.php";
		
		return $gauntletsURL.'/'.$gauntletID.'.png';
	}
	
	public static function getGauntletNames() {
		$gauntlets = ["Unknown", "Fire", "Ice", "Poison", "Shadow", "Lava", "Bonus", "Chaos", "Demon", "Time", "Crystal", "Magic", "Spike", "Monster", "Doom", "Death", 'Forest', 'Rune', 'Force', 'Spooky', 'Dragon', 'Water', 'Haunted', 'Acid', 'Witch', 'Power', 'Potion', 'Snake', 'Toxic', 'Halloween', 'Treasure', 'Ghost', 'Spider', 'Gem', 'Inferno', 'Portal', 'Strange', 'Fantasy', 'Christmas', 'Surprise', 'Mystery', 'Cursed', 'Cyborg', 'Castle', 'Grave', 'Temple', 'World', 'Galaxy', 'Universe', 'Discord', 'Split', 'NCS I', 'NCS II', 'Space', 'Cosmos'];
		
		return $gauntlets;
	}
	
	public static function getGauntletName($gauntletID) {
		$gauntlets = self::getGauntletNames();
		
		return $gauntlets[$gauntletID] ?: $gauntlets[0];
	}
	
	/*
		Audio-related functions
	*/
	
	public static function getSongByID($songID, $column = "*") {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['songs'][$songID])) {
			if($column != "*" && $GLOBALS['core_cache']['songs'][$songID]) return $GLOBALS['core_cache']['songs'][$songID][$column];
			
			return $GLOBALS['core_cache']['songs'][$songID];
		}
		
		$isLocalSong = true;
		
		$song = $db->prepare("SELECT * FROM songs WHERE ID = :songID");
		$song->execute([':songID' => $songID]);
		$song = $song->fetch();
		
		if(!$song) {
			$song = self::getLibrarySongInfo($songID, 'music');
			$isLocalSong = false;
		}
		
		if(!$song) {
			$GLOBALS['core_cache']['songs'][$songID] = false;			
			return false;
		}

		$song['isLocalSong'] = $isLocalSong;
		$GLOBALS['core_cache']['songs'][$songID] = $song;		
		
		if($column != "*") return $song[$column];
		else return array("isLocalSong" => $isLocalSong, "ID" => $song["ID"], "name" => $song["name"], "authorName" => $song["authorName"], "size" => $song["size"], "duration" => $song["duration"], "download" => urldecode($song["download"]), "reuploadTime" => $song["reuploadTime"], "reuploadID" => $song["reuploadID"]);
	}
	
	public static function getSFXByID($sfxID, $column = "*") {
		require __DIR__."/connection.php";
		
		$isLocalSFX = true;
		
		if(isset($GLOBALS['core_cache']['sfxs'][$sfxID])) {
			if($column != "*" && $GLOBALS['core_cache']['sfxs'][$sfxID]) return $GLOBALS['core_cache']['sfxs'][$sfxID][$column];
			
			return $GLOBALS['core_cache']['sfxs'][$sfxID];
		}
		
		$sfx = $db->prepare("SELECT $column FROM sfxs WHERE ID = :sfxID");
		$sfx->execute([':sfxID' => $sfxID]);
		$sfx = $sfx->fetch();
		
		if(!$sfx) {
			$sfx = self::getLibrarySongInfo($sfxID, 'sfx');
			$isLocalSFX = $sfx['isLocalSFX'];
		}
		
		if(!$sfx) {
			$GLOBALS['core_cache']['sfxs'][$sfxID] = false;
			return false;
		}
		
		$sfx['isLocalSFX'] = $isLocalSFX;
		$GLOBALS['core_cache']['sfxs'][$sfxID] = $sfx;
		
		if($column != "*") return $sfx[$column];
		else return ["isLocalSFX" => $isLocalSFX, "ID" => $sfx["ID"], "name" => $sfx["name"], "authorName" => $sfx["authorName"], "size" => $sfx["size"], "download" => $sfx["download"], "reuploadTime" => $sfx["reuploadTime"], "reuploadID" => $sfx["reuploadID"], "levelsCount" => $sfx["levelsCount"] ?: 0];
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
		
		$downloadLink = urlencode(urldecode($song["download"]));
		
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
	
	public static function getLibrarySongInfo($audioID, $type = 'music') {
		require __DIR__."/../../config/dashboard.php";
		
		if(!file_exists(__DIR__.'/../../'.$type.'/ids.json')) return false;
		
		if(isset($GLOBALS['core_cache']['libraryAudio'][$type][$audioID])) return $GLOBALS['core_cache']['libraryAudio'][$type][$audioID];
		
		$servers = $serverIDs = $serverNames = [];
		
		foreach($customLibrary AS $customLib) {
			$servers[$customLib[0]] = $customLib[2];
			$serverNames[$customLib[0]] = $customLib[1];
			$serverIDs[$customLib[2]] = $customLib[0];
		}
		
		$library = self::getLibrary($type);
		
		if(!isset($library['IDs'][(int)$audioID]) || ($type == 'music' && $library['IDs'][(int)$audioID]['type'] != 1)) return false;
		
		if($type == 'music') {
			$song = $library['IDs'][(int)$audioID];
			$author = $library['IDs'][$song['authorID']];
			
			$token = self::randomString(22);
			$expires = time() + 3600;
			
			$link = $servers[$song['server']].'/music/'.$song['originalID'].'.ogg?token='.$token.'&expires='.$expires;
			
			$songArray = ['server' => $song['server'], 'ID' => $audioID, 'name' => $song['name'], 'authorID' => $song['authorID'], 'authorName' => $author['name'], 'size' => round($song['size'] / 1024 / 1024, 2), 'download' => $link, 'seconds' => $song['seconds'], 'tags' => $song['tags'], 'ncs' => $song['ncs'], 'artists' => $song['artists'], 'externalLink' => $song['externalLink'], 'new' => $song['new'], 'priorityOrder' => $song['priorityOrder']];
			
			$GLOBALS['core_cache']['libraryAudio'][$type][$audioID] = $songArray;
			
			return $songArray;
		} else {
			$SFX = $library['IDs'][(int)$audioID];
			
			$token = self::randomString(22);
			$expires = time() + 3600;
			
			$isLocalSFX = $servers[$SFX['server']] == null;
			if($isLocalSFX) $SFX = self::getSFXByID($SFX['ID']);
			
			$link = !$isLocalSFX ? $servers[$SFX['server']].'/sfx/s'.$SFX['ID'].'.ogg?token='.$token.'&expires='.$expires : $SFX['download'];
			
			$sfxArray = ['isLocalSFX' => $isLocalSFX, 'server' => $SFX['server'] ?: $serverIDs[null], 'ID' => $audioID, 'name' => $SFX['name'], 'authorName' => $serverNames[$SFX['server']], 'download' => $link, 'originalID' => $SFX['ID'], 'reuploadID' => $SFX['reuploadID'] ?: 0, 'reuploadTime' => $SFX['reuploadTime'] ?: 0, 'levelsCount' => $SFX['levelsCount'] ?: 0];
			
			$GLOBALS['core_cache']['libraryAudio'][$type][$audioID] = $sfxArray;
			
			return $sfxArray;
		}
	}
	
	public static function getLibrarySongAuthorInfo($songID) {
		if(!file_exists(__DIR__.'/../../music/ids.json')) return false;
		
		$library = self::getLibrary("music");
		if(!isset($library['IDs'][$songID])) return false;
		
		return $library['IDs'][$songID];
	}
	
	public static function updateLibraries($token, $expires, $mainServerTime, $type = 0) {
		require __DIR__."/../../config/dashboard.php";
		require_once __DIR__."/exploitPatch.php";
		
		$servers = $times = [];
		
		$types = ['sfx', 'music'];
		if(!isset($customLibrary)) global $customLibrary;
		if(empty($customLibrary)) $customLibrary = [[1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net'], [3, $gdps, null]];
		
		foreach($customLibrary AS $library) {
			if(($types[$type] == 'sfx' AND $library[3] === 1) OR ($types[$type] == 'music' AND $library[3] === 0)) continue;
			
			if($library[2] !== null) $servers['s'.$library[0]] = $library[2];
		}
		
		$updatedLib = false;
		foreach($servers AS $key => &$server) {
			$versionUrl = $server.'/'.$types[$type].'/'.$types[$type].'library_version'.($types[$type] == 'music' ? '_02' : '').'.txt';
			$dataUrl = $server.'/'.$types[$type].'/'.$types[$type].'library'.($types[$type] == 'music' ? '_02' : '').'.dat';

			$oldVersion = file_exists(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt') ? file_get_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt') : [0, 0];
			$times[] = (int)$oldVersion[1];
			
			if((int)$oldVersion[1] + 600 > time()) continue; // Download library only once per 10 minutes
			
			$curl = curl_init($versionUrl.'?token='.$token.'&expires='.$expires);
			curl_setopt_array($curl, [
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FOLLOWLOCATION => 1
			]);
			$newVersion = (int)abs(Escape::number(curl_exec($curl)));
			curl_close($curl);
			
			if($newVersion > $oldVersion[0] || !$oldVersion[0]) {
				file_put_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt', $newVersion.', '.time());
				
				$download = curl_init($dataUrl.'?token='.$token.'&expires='.$expires.'&dashboard=1');
				curl_setopt_array($download, [
					CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FOLLOWLOCATION => 1
				]);
				$dat = curl_exec($download);
				$resultStatus = curl_getinfo($download, CURLINFO_HTTP_CODE);
				curl_close($download);
				
				if($resultStatus == 200) {
					file_put_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat', $dat);
					$updatedLib = true;
				}
			}
		}
		// Now this server's version check
		if(file_exists(__DIR__.'/../../'.$types[$type].'/gdps.txt')) $oldVersion = file_get_contents(__DIR__.'/../../'.$types[$type].'/gdps.txt');
		else {
			$oldVersion = 0;
			file_put_contents(__DIR__.'/../../'.$types[$type].'/gdps.txt', $mainServerTime);
		}
		
		$times[] = $mainServerTime;
		rsort($times);
		
		if($oldVersion < $mainServerTime || $updatedLib) self::generateDATFile($times[0], $type);
	}
	
	public static function generateDATFile($mainServerTime, $type = 0) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		require_once __DIR__."/exploitPatch.php";
		
		$library = $servers = $serverIDs = $serverTypes = [];
		if(!isset($customLibrary)) global $customLibrary;
		if(empty($customLibrary)) $customLibrary = [[1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net', 2], [3, $gdps, null, 2]]; 
		
		$types = ['sfx', 'music'];
		foreach($customLibrary AS $customLib) {
			if($customLib[2] !== null) {
				$servers['s'.$customLib[0]] = $customLib[0];
			}
			
			$serverIDs[$customLib[2]] = $customLib[0];
			
			if($types[$type] == 'sfx') {
				if($customLib[3] != 1) $library['folders'][($customLib[0] + 1)] = [
					'name' => Escape::dat($customLib[1]),
					'type' => 1,
					'parent' => 1
				];
			} else {
				if($customLib[3] != 0) $library['tags'][$customLib[0]] = [
					'ID' => $customLib[0],
					'name' => Escape::dat($customLib[1]),
				];
			}
		}
		
		$idsConverter = file_exists(__DIR__.'/../../'.$types[$type].'/ids.json') ? json_decode(file_get_contents(__DIR__.'/../../'.$types[$type].'/ids.json'), true) : ['count' => ($type == 0 ? count($customLibrary) + 2 : 8000000), 'IDs' => [], 'originalIDs' => []];
		$skipSFXIDs = file_exists(__DIR__.'/../../config/skipSFXIDs.json') ? json_decode(file_get_contents(__DIR__.'/../../config/skipSFXIDs.json'), true) : [];
		
		foreach($servers AS $key => $server) {
			if(!file_exists(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat')) continue;
			$res = $bits = null;
			
			$res = file_get_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat');
			$res = mb_convert_encoding($res, 'UTF-8', 'UTF-8');
			try {
				$res = Escape::url_base64_decode($res);
				$res = zlib_decode($res);
			} catch(Exception $e) {
				unlink(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat');
				continue;
			}
			
			$res = explode('|', $res);
			if(!$type) {
				// SFX library decoding was made by MigMatos, check their ObeyGDBot! https://obeybd.web.app/
				for($i = 0; $i < count($res); $i++) {
					$res[$i] = explode(';', $res[$i]);
					
					if($i === 0) {
						$library['version'] = $mainServerTime;
						$version = explode(',', $res[0][0]);
						$version[1] = $mainServerTime;
						$version = implode(',', $version);
					}
					
					for($j = 1; $j <= count($res[$i]); $j++) {
						$bits = explode(',', $res[$i][$j]);
						
						switch($i) {
							case 0: // File/Folder
								if(empty(trim($bits[1])) || empty($bits[0]) || !is_numeric($bits[0])) break;
								
								if(empty($idsConverter['originalIDs'][$server][$bits[0]])) {
									$idsConverter['count']++;
									
									while(in_array($idsConverter['count'], $skipSFXIDs)) $idsConverter['count']++;
									
									$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $bits[0], 'name' => $bits[1], 'type' => $bits[2]];
									$idsConverter['originalIDs'][$server][$bits[0]] = $idsConverter['count'];
									$bits[0] = $idsConverter['count'];
								} else {
									$bits[0] = $idsConverter['originalIDs'][$server][$bits[0]];
									
									if(!isset($idsConverter['IDs'][$bits[0]]['name'])) $idsConverter['IDs'][$bits[0]] = ['server' => $server, 'ID' => $bits[0], 'name' => $bits[1], 'type' => $bits[2]];
								}
								
								if($bits[3] != 1) {
									if(empty($idsConverter['originalIDs'][$server][$bits[3]])) {
										$idsConverter['count']++;
										
										while(in_array($idsConverter['count'], $skipSFXIDs)) $idsConverter['count']++;
										
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $bits[3], 'name' => $bits[1], 'type' => 1];
										$idsConverter['originalIDs'][$server][$bits[3]] = $idsConverter['count'];
										$bits[3] = $idsConverter['count'];
									} else $bits[3] = $idsConverter['originalIDs'][$server][$bits[3]];
								} else $bits[3] = $server + 1;
								
								if($bits[2]) {
									$library['folders'][$bits[0]] = [
										'name' => Escape::dat($bits[1]),
										'type' => (int)$bits[2],
										'parent' => (int)$bits[3]
									];
								} else {
									$library['files'][$bits[0]] = [
										'name' => Escape::dat($bits[1]),
										'type' => (int)$bits[2],
										'parent' => (int)$bits[3],
										'bytes' => (int)$bits[4],
										'milliseconds' => (int)$bits[5],
									];
								}
								
								break;
							case 1: // Credit
								if(empty(trim($bits[0])) || empty(trim($bits[1]))) continue 2;
								
								$library['credits'][Escape::dat($bits[0])] = [
									'name' => Escape::dat($bits[0]),
									'website' => Escape::dat($bits[1]),
								];
								break;
						}
					}
				}
				$sfxs = $db->prepare("SELECT sfxs.*, accounts.userName FROM sfxs JOIN accounts ON accounts.accountID = sfxs.reuploadID WHERE isDisabled = 0");
				$sfxs->execute();
				$sfxs = $sfxs->fetchAll();
				
				$folderID = $gdpsLibrary = [];
				$server = $serverIDs[null];
				
				foreach($sfxs AS &$customSFX) {
					if(!isset($folderID[$customSFX['reuploadID']])) {
						if(empty($idsConverter['originalIDs'][$server][$customSFX['reuploadID']])) {
							$idsConverter['count']++;
							$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $customSFX['ID'], 'name' => $customSFX['userName'].'\'s SFXs', 'type' => 1];
							$idsConverter['originalIDs'][$server][$customSFX['reuploadID']] = $idsConverter['count'];
							
							$newID = $idsConverter['count'];
						} else $newID = $idsConverter['originalIDs'][$server][$customSFX['reuploadID']];
						
						$library['folders'][$newID] = [
							'name' => Escape::dat($customSFX['userName']).'\'s SFXs',
							'type' => 1,
							'parent' => (int)($server + 1)
						];
						
						$gdpsLibrary['folders'][$newID] = [
							'name' => Escape::dat($customSFX['userName']).'\'s SFXs',
							'type' => 1,
							'parent' => 1
						];
						
						$folderID[$customSFX['reuploadID']] = true;
					}
					
					if(empty($idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000])) {
						$idsConverter['count']++;
						$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $customSFX['ID'], 'name' => $customSFX['name'], 'type' => 0];
						$idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000] = $idsConverter['count'];
						
						$customSFX['ID'] = $idsConverter['count'];
					} else $customSFX['ID'] = $idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000];
					
					$library['files'][$customSFX['ID']] = $gdpsLibrary['files'][$customSFX['ID']] = [
						'name' => Escape::dat($customSFX['name']),
						'type' => 0,
						'parent' => (int)$idsConverter['originalIDs'][$server][$customSFX['reuploadID']],
						'bytes' => (int)$customSFX['size'],
						'milliseconds' => (int)($customSFX['milliseconds'] / 10)
					];
				}
				
				$filesEncrypted = $creditsEncrypted = [];
				foreach($library['folders'] AS $id => &$folder) $filesEncrypted[] = implode(',', [$id, $folder['name'], 1, $folder['parent'], 0, 0]);
				foreach($library['files'] AS $id => &$file) $filesEncrypted[] = implode(',', [$id, $file['name'], 0, $file['parent'], $file['bytes'], $file['milliseconds']]);
				foreach($library['credits'] AS &$credit) $creditsEncrypted[] = implode(',', [$credit['name'], $credit['website']]);
				$encrypted = $version.";".implode(';', $filesEncrypted)."|" .implode(';', $creditsEncrypted).';';
				
				$filesEncrypted = $creditsEncrypted = [];
				foreach($gdpsLibrary['folders'] AS $id => &$folder) $filesEncrypted[] = implode(',', [$id, $folder['name'], 1, $folder['parent'], 0, 0]);
				foreach($gdpsLibrary['files'] AS $id => &$file) $filesEncrypted[] = implode(',', [$id, $file['name'], 0, $file['parent'], $file['bytes'], $file['milliseconds']]);
				$creditsEncrypted[] = implode(',', [$gdps, $_SERVER['SERVER_NAME']]);
				$gdpsEncrypted = $version.";".implode(';', $filesEncrypted)."|" .implode(';', $creditsEncrypted).';';
			} else {
				$version = $mainServerTime;
				array_shift($res);
				$x = 0;
				
				foreach($res AS &$data) {
					$data = rtrim($data, ';');
					$music = explode(';', $data);
					
					foreach($music AS &$songString) {
						$song = explode(',', $songString);
						$originalID = $song[0];
						
						if(empty($song[0]) || !is_numeric($song[0])) continue;
						
						if(empty($idsConverter['originalIDs'][$server][$song[0]])) {
							$idsConverter['count']++;
							$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $song[0], 'type' => $x];
							$idsConverter['originalIDs'][$server][$song[0]] = $idsConverter['count'];
							$song[0] = $idsConverter['count'];
						} else $song[0] = $idsConverter['originalIDs'][$server][$song[0]];
						
						switch($x) {
							case 0:
								$idsConverter['IDs'][$song[0]] = $library['authors'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'authorID' => $song[0],
									'name' => Escape::dat($song[1]),
									'link' => Escape::dat($song[2]),
									'yt' => Escape::dat($song[3])
								];
								break;
							case 1:
								if(empty($idsConverter['originalIDs'][$server][$song[2]])) {
									$idsConverter['count']++;
									$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $song[2], 'type' => $x];
									$idsConverter['originalIDs'][$server][$song[2]] = $idsConverter['count'];
									$song[2] = $idsConverter['count'];
								} else $song[2] = $idsConverter['originalIDs'][$server][$song[2]];
								
								$tags = explode('.', $song[5]);
								$newTags = [];
								
								foreach($tags AS &$tag) {
									if(empty($tag)) continue;
									if(empty($idsConverter['originalIDs'][$server][$tag])) {
										$idsConverter['count']++;
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $tag, 'type' => 2];
										$idsConverter['originalIDs'][$server][$tag] = $idsConverter['count'];
										$tag = $idsConverter['count'];
									} else $tag = $idsConverter['originalIDs'][$server][$tag];
									$newTags[] = $tag;
								}
								
								$newTags[] = $server;
								$tags = '.'.implode('.', $newTags).'.';
								
								$newArtists = [];
								$artists = explode('.', $song[7]);
								
								foreach($artists AS &$artist) {
									if(empty($artist)) continue;
									if(empty($idsConverter['originalIDs'][$server][$artist])) {
										$idsConverter['count']++;
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $artist, 'type' => 0];
										$idsConverter['originalIDs'][$server][$artist] = $idsConverter['count'];
										$artist = $idsConverter['count'];
									} else $artist = $idsConverter['originalIDs'][$server][$artist];
									$newArtists[] = $artist;
								}
								
								$artists = implode('.', $newArtists);
								$idsConverter['IDs'][$song[0]] = $library['songs'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'ID' => $song[0],
									'name' => Escape::dat($song[1]),
									'authorID' => $song[2],
									'size' => $song[3],
									'seconds' => $song[4],
									'tags' => $tags,
									'ncs' => $song[6] ?: 0,
									'artists' => $artists,
									'externalLink' => $song[8] ?: '',
									'new' => $song[9] ?: 0,
									'priorityOrder' => $song[10] ?: 0
								];
								break;
							case 2:
								$idsConverter['IDs'][$song[0]] = $library['tags'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'ID' => $song[0],
									'name' => Escape::dat($song[1])
								];
								break;
						}
					}
					$x++;
				}
				$songs = $db->prepare("SELECT songs.*, accounts.userName FROM songs JOIN accounts ON accounts.accountID = songs.reuploadID WHERE isDisabled = 0");
				$songs->execute();
				$songs = $songs->fetchAll();
				
				$folderID = $accIDs = $gdpsLibrary = [];
				$c = 100;
				
				foreach($songs AS &$customSongs) {
					$c++;
					$authorName = trim(Escape::text(Escape::dat(Escape::translit($customSongs['authorName'])), 40));
					
					if(empty($authorName)) $authorName = 'Reupload';
					if(empty($folderID[$authorName])) {
						$folderID[$authorName] = $c;
						$library['authors'][$serverIDs[null]. 0 .$folderID[$authorName]] = $gdpsLibrary['authors'][$serverIDs[null]. 0 .$folderID[$authorName]] = [
							'authorID' => (int)($serverIDs[null]. 0 .$folderID[$authorName]),
							'name' => $authorName,
							'link' => ' ',
							'yt' => ' '
						];
					}
					
					if(empty($accIDs[$customSongs['reuploadID']])) {
						$c++;
						$accIDs[$customSongs['reuploadID']] = $c;
						$library['tags'][$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]] = $gdpsLibrary['tags'][$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]] = [
							'ID' => (int)($serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]),
							'name' => Escape::text(Escape::dat($customSongs['userName']), 30),
						];
					}
					
					$customSongs['name'] = trim(Escape::text(Escape::dat(Escape::translit($customSongs['name'])), 40));
					$library['songs'][$customSongs['ID']] = $gdpsLibrary['songs'][$customSongs['ID']] = [
						'ID' => ($customSongs['ID']),
						'name' => !empty($customSongs['name']) ? $customSongs['name'] : 'Unnamed',
						'authorID' => (int)($serverIDs[null]. 0 .$folderID[$authorName]),
						'size' => $customSongs['size'] * 1024 * 1024,
						'seconds' => $customSongs['duration'],
						'tags' => '.'.$serverIDs[null].'.'.$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']].'.',
						'ncs' => 0,
						'artists' => '',
						'externalLink' => urlencode($customSongs['download']),
						'new' => ($customSongs['reuploadTime'] > time() - 604800 ? 1 : 0),
						'priorityOrder' => 0
					];
				}
				
				$authorsEncrypted = $songsEncrypted = $tagsEncrypted = [];
				foreach($library['authors'] AS &$authorList) {
					unset($authorList['server'], $authorList['type'], $authorList['originalID']);
					$authorsEncrypted[] = implode(',', $authorList);
				}
				foreach($library['songs'] AS &$songsList) {
					unset($songsList['server'], $songsList['type'], $songsList['originalID']);
					$songsEncrypted[] = implode(',', $songsList);
				}
				foreach($library['tags'] AS &$tagsList) {
					unset($tagsList['server'], $tagsList['type'], $tagsList['originalID']);
					$tagsEncrypted[] = implode(',', $tagsList);
				}
				$encrypted = $version."|".implode(';', $authorsEncrypted).";|" .implode(';', $songsEncrypted).";|" .implode(';', $tagsEncrypted).';';
				
				$authorsEncrypted = $songsEncrypted = $tagsEncrypted = [];
				foreach($gdpsLibrary['authors'] AS &$authorList) {
					unset($authorList['server'], $authorList['type'], $authorList['originalID']);
					$authorsEncrypted[] = implode(',', $authorList);
				}
				foreach($gdpsLibrary['songs'] AS &$songsList) {
					unset($songsList['server'], $songsList['type'], $songsList['originalID']);
					$songsEncrypted[] = implode(',', $songsList);
				}
				foreach($gdpsLibrary['tags'] AS &$tagsList) {
					unset($tagsList['server'], $tagsList['type'], $tagsList['originalID']);
					$tagsEncrypted[] = implode(',', $tagsList);
				}
				$gdpsEncrypted = $version."|".implode(';', $authorsEncrypted).";|" .implode(';', $songsEncrypted).";|" .implode(';', $tagsEncrypted).';';
			}
		}

		file_put_contents(__DIR__.'/../../'.$types[$type].'/ids.json', json_encode($idsConverter, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE));
		$encrypted = zlib_encode($encrypted, ZLIB_ENCODING_DEFLATE);
		$encrypted = Escape::url_base64_encode($encrypted);
		file_put_contents(__DIR__.'/../../'.$types[$type].'/gdps.dat', $encrypted);
		
		$gdpsEncrypted = zlib_encode($gdpsEncrypted, ZLIB_ENCODING_DEFLATE);
		$gdpsEncrypted = Escape::url_base64_encode($gdpsEncrypted);
		file_put_contents(__DIR__.'/../../'.$types[$type].'/standalone.dat', $gdpsEncrypted);
	}
	
	public static function getSongs($filters, $order, $orderSorting, $queryJoin, $pageOffset, $limit = false) {
		require __DIR__."/connection.php";
		
		$songs = $db->prepare("SELECT * FROM songs ".$queryJoin." WHERE (".implode(") AND (", $filters).") AND isDisabled = 0 ".($order ? "ORDER BY ".$order." ".$orderSorting : "")." ".($limit ? "LIMIT ".$limit." OFFSET ".$pageOffset : ''));
		$songs->execute();
		$songs = $songs->fetchAll();
		
		$songsCount = $db->prepare("SELECT count(*) FROM songs ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).") AND isDisabled = 0");
		$songsCount->execute();
		$songsCount = $songsCount->fetchColumn();
		
		return ["songs" => $songs, "count" => $songsCount];
	}
	
	public static function lastSongTime() {
		require __DIR__."/connection.php";
		
		$lastSongTime = $db->prepare('SELECT reuploadTime FROM songs WHERE reuploadTime > 0 AND isDisabled = 0 ORDER BY reuploadTime DESC LIMIT 1');
		$lastSongTime->execute();
		$lastSongTime = $lastSongTime->fetchColumn();
		if(!$lastSongTime) $lastSongTime = 1;
		
		return $lastSongTime;
	}
	
	public static function lastSFXTime() {
		require __DIR__."/connection.php";
		
		$lastSongTime = $db->prepare('SELECT reuploadTime FROM sfxs WHERE reuploadTime > 0 AND isDisabled = 0 ORDER BY reuploadTime DESC LIMIT 1');
		$lastSongTime->execute();
		$lastSongTime = $lastSongTime->fetchColumn();
		if(!$lastSongTime) $lastSongTime = 1;
		
		return $lastSongTime;
	}
	
	public static function getFavouriteSongs($person, $pageOffset, $limit = 20) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		
		$favouriteSongs = $db->prepare("SELECT * FROM favsongs INNER JOIN songs on favsongs.songID = songs.ID WHERE favsongs.accountID = :accountID ORDER BY favsongs.ID DESC ".($limit ? "LIMIT ".$limit." OFFSET ".$pageOffset : ""));
		$favouriteSongs->execute([':accountID' => $accountID]);
		$favouriteSongs = $favouriteSongs->fetchAll();
		
		$favouriteSongsCount = $db->prepare("SELECT count(*) FROM favsongs INNER JOIN songs on favsongs.songID = songs.ID WHERE favsongs.accountID = :accountID");
		$favouriteSongsCount->execute([':accountID' => $accountID]);
		$favouriteSongsCount = $favouriteSongsCount->fetchColumn();
		
		return ["songs" => $favouriteSongs, "count" => $favouriteSongsCount];
	}
	
	public static function saveNewgroundsSong($songID) {
		require __DIR__."/connection.php";
		
		$data = ['songID' => $songID, 'secret' => 'Wmfd2893gb7'];
		$headers = ['Content-type: application/x-www-form-urlencoded'];
		
		$request = self::sendRequest('http://www.boomlings.com/database/getGJSongInfo.php', http_build_query($data), $headers, "POST", false);
		if(!$request || is_numeric($request)) return false;
		
		// Will replace with function later
		$resultarray = explode('~|~', $request);
		$uploadDate = time();
		$query = $db->prepare("INSERT INTO songs (ID, name, authorID, authorName, size, download)
		VALUES (:id, :name, :authorID, :authorName, :size, :download)");
		$query->execute([':id' => $songID, ':name' => $resultarray[3], ':authorID' => $resultarray[5], ':authorName' => $resultarray[7], ':size' => $resultarray[9], ':download' => $resultarray[13]]);
		
		unset($GLOBALS['core_cache']['songs'][$songID]);
		
		return self::getSongByID($songID);
	}
	
	public static function getAudioTrack($trackID) {
		$songs = [
			"Practice: Stay Inside Me by OcularNebula",
			"Stereo Madness by ForeverBound",
			"Back on Track by DJVI",
			"Polargeist by Step",
			"Dry Out by DJVI",
			"Base after Base by DJVI",
			"Can't Let Go by DJVI",
			"Jumper by Waterflame",
			"Time Machine by Waterflame",
			"Cycles by DJVI",
			"xStep by DJVI",
			"Clutterfunk by Waterflame",
			"Theory of Everything by DJ Nate",
			"Electroman Adventures by Waterflame",
			"Club Step by DJ Nate",
			"Electrodynamix by DJ Nate",
			"Hexagon Force by Waterflame",
			"Blast Processing by Waterflame",
			"Theory of Everything 2 by DJ Nate",
			"Geometrical Dominator by Waterflame",
			"Deadlocked by F-777",
			"Fingerbang by MDK",
			"Dash by MDK",
			"Explorers by Hinkik",
			"The Seven Seas by F-777",
			"Viking Arena by F-777",
			"Airborne Robots by F-777",
			"Secret by RobTopGames",
			"Payload by Dex Arson",
			"Beast Mode by Dex Arson",
			"Machina by Dex Arson",
			"Years by Dex Arson",
			"Frontlines by Dex Arson",
			"Space Pirates by Waterflame",
			"Striker by Waterflame",
			"Embers by Dex Arson",
			"Round 1 by Dex Arson",
			"Monster Dance Off by F-777",
			"Press Start by MDK",
			"Nock Em by Bossfight",
			"Power Trip by Boom Kitty"
		];
		
		$track = $songs[($trackID + 1)] ?: "Unknown by DJVI";
		$trackArray = explode(' by ', $track);
		
		return [
			'name' => $trackArray[0],
			'authorName' => $trackArray[1]
		];
	}
	
	public static function favouriteSong($person, $songID) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		
		$song = self::getSongByID($songID);
		if(!$song || !$song['isLocalSong']) return false;
		
		$favouritedSong = $db->prepare("SELECT count(*) FROM favsongs WHERE songID = :songID AND accountID = :accountID");
		$favouritedSong->execute([':songID' => $songID, ':accountID' => $accountID]);
		$favouritedSong = $favouritedSong->fetchColumn();
		
		if($favouritedSong) { // Song is favourited
			$removeFavourite = $db->prepare("DELETE FROM favsongs WHERE songID = :songID AND accountID = :accountID");
			$removeFavourite->execute([':songID' => $songID, ':accountID' => $accountID]);
			
			$decreaseFavouriteCount = $db->prepare("UPDATE songs SET favouritesCount = favouritesCount - 1 WHERE ID = :songID");
			$decreaseFavouriteCount->execute([':songID' => $songID]);
			
			return '-1';
		} else { // Song is not favourited
			$addFavourite = $db->prepare("INSERT INTO favsongs (songID, accountID, timestamp) VALUES (:songID, :accountID, :timestamp)");
			$addFavourite->execute([':songID' => $songID, ':accountID' => $accountID, ':timestamp' => time()]);
			
			$increaseFavouriteCount = $db->prepare("UPDATE songs SET favouritesCount = favouritesCount + 1 WHERE ID = :songID");
			$increaseFavouriteCount->execute([':songID' => $songID]);
			return '1';
		}
	}
	
	public static function getSFXs($filters, $order, $orderSorting, $queryJoin, $pageOffset, $limit = false) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		
		$sfxs = $db->prepare("SELECT *, 1 AS isLocalSFX FROM sfxs ".$queryJoin." WHERE (".implode(") AND (", $filters).") AND isDisabled = 0 ".($order ? "ORDER BY ".$order." ".$orderSorting : "")." ".($limit ? "LIMIT ".$limit." OFFSET ".$pageOffset : ''));
		$sfxs->execute();
		$sfxs = $sfxs->fetchAll();
		
		$serverIDs = [];
		foreach($customLibrary AS $customLib) $serverIDs[$customLib[2]] = $customLib[0];
		
		foreach($sfxs AS &$sfx) {
			$sfx['originalID'] = $sfx['ID'];
			$sfx['ID'] = self::getLibraryOriginalID($sfx['ID'] + 8000000, 'sfx', $serverIDs[null]);
		}
		
		$sfxsCount = $db->prepare("SELECT count(*) FROM sfxs ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).") AND isDisabled = 0");
		$sfxsCount->execute();
		$sfxsCount = $sfxsCount->fetchColumn();
		
		return ["sfxs" => $sfxs, "count" => $sfxsCount];
	}
	
	public static function getSFXsByLibraryIDs($sfxIDs, $pageOffset, $limit = false) {
		require __DIR__."/connection.php";
		
		$sfxs = [];
		
		$currentSFX = 0;
		
		foreach($sfxIDs AS &$sfxID) {
			$currentSFX++;
			
			if($currentSFX < $pageOffset) continue;
			if($limit && $currentSFX > $pageOffset + $limit) break;
			
			$sfx = self::getSFXByID($sfxID);
			$sfxs[] = $sfx;
		}
		
		return ["sfxs" => $sfxs, "count" => count($sfxIDs)];
	}
	
	public static function getLibrary($type) {
		if(!isset($GLOBALS['core_cache']['libraryFile'][$type])) {
			$library = json_decode(file_get_contents(__DIR__.'/../../'.$type.'/ids.json'), true);
			
			$GLOBALS['core_cache']['libraryFile'][$type] = $library;
		} else $library = $GLOBALS['core_cache']['libraryFile'][$type];
		
		return $library;
	}
	
	public static function getLibraryOriginalID($audioID, $type, $server) {
		$library = self::getLibrary($type, $server);
		
		return $library['originalIDs'][$server][$audioID];
	}
	
	/*
		Utils-related functions
	*/
	
	public static function logAction($person, $type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '') {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		$insertAction = $db->prepare('INSERT INTO actions (account, type, timestamp, value, value2, value3, value4, value5, value6, IP)
			VALUES (:account, :type, :timestamp, :value, :value2, :value3, :value4, :value5, :value6, :IP)');
		$insertAction->execute([':account' => $accountID, ':type' => $type, ':value' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':value5' => $value5, ':value6' => $value6, ':timestamp' => time(), ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function logModeratorAction($person, $type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '', $value7 = '') {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		$insertModeratorAction = $db->prepare('INSERT INTO modactions (account, type, timestamp, value, value2, value3, value4, value5, value6, value7, IP)
			VALUES (:account, :type, :timestamp, :value, :value2, :value3, :value4, :value5, :value6, :value7, :IP)');
		$insertModeratorAction->execute([':account' => $accountID, ':type' => $type, ':value' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':value5' => $value5, ':value6' => $value6, ':value7' => $value7, ':timestamp' => time(), ':IP' => $IP]);
		
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
	
	public static function makeTime($time, $extraTextArray = []) {
		require __DIR__."/../../config/dashboard.php";
		
		if(!isset($timeType)) $timeType = 0;
		
		$extraText = !empty($extraTextArray) ? implode(", ", $extraTextArray).', ' : '';
		
		switch($timeType) {
			case 1:
				if(date("d.m.Y", $time) == date("d.m.Y", time())) return $extraText.date("G;i", $time);
				elseif(date("Y", $time) == date("Y", time())) return $extraText.date("d.m", $time);
				else return $extraText.date("d.m.Y", $time);
				break;
			case 2:
				// taken from https://stackoverflow.com/a/36297417
				$isFuture = false;
				$time = time() - $time;
				
				if($time < 0) {
					$time = abs($time);
					$isFuture = true;
				}
				
				$tokens = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second'];
				
				foreach($tokens as $unit => $text) {
					if($time < $unit) continue;
					$numberOfUnits = floor($time / $unit);
					return $extraText.($isFuture ? 'in ' : '').$numberOfUnits.' '.$text.(($numberOfUnits > 1) ? 's' : '');
				}
				break;
			default:
				return $extraText.date("d/m/Y G.i", $time);
				break;
		}
	}
	
	public static function rateItem($person, $itemID, $type, $isLike) {
		require __DIR__."/connection.php";
		
		if($person['accountID'] == 0 || $person['userID'] == 0) return false;
		
		$extraCommentsColumns = '';
		
		$accountID = $person['accountID'];
		$IP = $person['IP'];
		
		$checkIfRated = $db->prepare("SELECT count(*) FROM actions_likes WHERE itemID = :itemID AND type = :type AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$checkIfRated->execute([':itemID' => $itemID, ':type' => $type, ':IP' => $IP, ':accountID' => $accountID]);
		$checkIfRated = $checkIfRated->fetchColumn();
		if($checkIfRated) return false;
		
		switch($type) {
			case 1:
				$table = "levels";
				$column = "levelID";
				break;
			case 2:
				$table = "comments";
				$column = "commentID";
				$extraCommentsColumns = ', isSpam = IF(likes - dislikes < -1, 1, 0)';
				break;
			case 3:
				$table = "acccomments";
				$column = "commentID";
				break;
			case 4:
				$table = "lists";
				$column = "listID";
				break;
		}
		$rateColumn = $isLike ? 'likes' : 'dislikes';
		
		$item = $db->prepare("SELECT * FROM ".$table." WHERE ".$column." = :itemID");
		$item->execute([':itemID' => $itemID]);
		$item = $item->fetch();
		if(!$item) return false;
		
		if($type == 2) {
			$commentItem = $item['levelID'] > 0 ? self::getLevelByID($item['levelID']) : self::getListByID($item['levelID'] * -1);
			
			if($person['userID'] == $commentItem['userID'] || $person['accountID'] == $commentItem['accountID']) $extraCommentsColumns .= ', creatorRating = '.($isLike ? '1' : '-1');
		}
		
		$rateItemAction = $db->prepare("INSERT INTO actions_likes (itemID, type, isLike, ip, accountID)
			VALUES (:itemID, :type, :isLike, INET6_ATON(:IP), :accountID)");
		$rateItemAction->execute([':itemID' => $itemID, ':type' => $type, ':isLike' => $isLike, ':IP' => $IP, ':accountID' => $accountID]);
		
		$rateItem = $db->prepare("UPDATE ".$table." SET ".$rateColumn." = ".$rateColumn." + 1".$extraCommentsColumns." WHERE ".$column." = :itemID");
		$rateItem->execute([':itemID' => $itemID]);
		
		return true;
	}
	
	public static function getClanInfo($clan, $column = "*") {
	    require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['clan'][$clan])) {
			if($column != "*" && $GLOBALS['core_cache']['clan'][$clan]) return $GLOBALS['core_cache']['clan'][$clan][$column];
			return $GLOBALS['core_cache']['clan'][$clan];
		}

	    $clanInfo = $db->prepare("SELECT * FROM clans WHERE ID = :clanID");
	    $clanInfo->execute([':clanID' => $clan]);
	    $clanInfo = $clanInfo->fetch();

	    if(empty($clanInfo)) {
			$GLOBALS['core_cache']['clan'][$clan] = false;
			return false;
		}

		$clanInfo['clan'] = base64_decode($clanInfo["clan"]);
		$clanInfo['tag'] = base64_decode($clanInfo["tag"]);
		$clanInfo['desc'] = base64_decode($clanInfo["desc"]);

		$GLOBALS['core_cache']['clan'][$clan] = $clanInfo;

		if($column != "*") return $clanInfo[$column];
		
		return ["ID" => $clanInfo["ID"], "clan" => $clanInfo["clan"], "tag" => $clanInfo["tag"], "desc" => $clanInfo["desc"], "clanOwner" => $clanInfo["clanOwner"], "color" => $clanInfo["color"], "isClosed" => $clanInfo["isClosed"], "creationDate" => $clanInfo["creationDate"]];
	}
	
	public static function makeClanUsername($accountID) {
		require __DIR__."/../../config/dashboard.php";
		
		if(isset($GLOBALS['core_cache']['accountClanUsername'][$accountID])) return $GLOBALS['core_cache']['accountClanUsername'][$accountID];
		
		if(!isset($clansTagPosition)) $clansTagPosition = '[%2$s] %1$s';
		
		$user = self::getUserByAccountID($accountID);
		
		if($clansEnabled && $user['clan'] > 0 && !isset($_REQUEST['noClan'])) {
			$clan = self::getClanInfo($user['clan'], 'tag');
			
			$clanUsername = !empty($clan) ? sprintf($clansTagPosition, $user['userName'], $clan) : $user['userName'];
			
			$GLOBALS['core_cache']['accountClanUsername'][$accountID] = $clanUsername;
			
			return $clanUsername;
		}
		
		$GLOBALS['core_cache']['accountClanUsername'][$accountID] = $user['userName'];
		
		return $user['userName'];
	}
	
	public static function getAccountClan($accountID) {
		require __DIR__."/../../config/dashboard.php";
		
		if(isset($GLOBALS['core_cache']['accountClan'][$accountID])) return $GLOBALS['core_cache']['accountClan'][$accountID];
		
		$user = self::getUserByAccountID($accountID);
		
		if($clansEnabled && $user['clan'] > 0) {
			$clan = self::getClanInfo($user['clan']);
			
			$GLOBALS['core_cache']['accountClan'][$accountID] = $clan;
			
			return $clan;
		}
		
		return false;
	}
	
	public static function getPersonActions($person, $filters, $limit = false) {
		require __DIR__."/connection.php";
		
		$accountID = $person['accountID'];
		$IP = self::convertIPForSearching($person['IP'], true);
		
		$getActions = $db->prepare("SELECT * FROM actions WHERE (account = :accountID OR IP REGEXP CONCAT('((\\\D[^.])|^)(', :IP, ')(\\\D[^$])')) AND (".implode(") AND (", $filters).") ORDER BY timestamp DESC".($limit ? ' LIMIT '.$limit : ''));
		$getActions->execute([':accountID' => $accountID, ':IP' => $IP]);
		$getActions = $getActions->fetchAll();
		
		return $getActions;
	}
	
	public static function getActions($filters, $limit = false) {
		require __DIR__."/connection.php";
		
		$getActions = $db->prepare("SELECT * FROM actions WHERE (".implode(") AND (", $filters).") ORDER BY timestamp DESC".($limit ? ' LIMIT '.$limit : ''));
		$getActions->execute();
		$getActions = $getActions->fetchAll();
		
		return $getActions;
	}
	
	public static function sendRequest($url, $data, $headers, $method, $includeUserAgent = false) {
		require __DIR__."/../../config/proxy.php";
		
		$curl = curl_init($url);
		
		if($proxytype > 0) {
			curl_setopt($curl, CURLOPT_PROXY, $host);
			if(!empty($auth)) curl_setopt($curl, CURLOPT_PROXYUSERPWD, $auth); 
			
			if($proxytype == 2) curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		}
		
		if(!$includeUserAgent) curl_setopt($curl, CURLOPT_USERAGENT, "");
		else $headers[] = 'User-Agent: GMDprivateServer (https://github.com/MegaSa1nt/GMDprivateServer, 2.0)';
		
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		if($method != "GET") curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		$result = curl_exec($curl);
		curl_close($curl);
		
		return $result;
	}
	
	public static function textColor($text, $color) {
		return '<c'.$color.'>'.$text.'</c>';
	}
	
	/*
		Return to Geometry Dash-related functions
	*/
	
	public static function returnUserString($user) {
		$user['userName'] = self::makeClanUsername($user['extID']);
		
		return "1:".$user["userName"].":2:".$user["userID"].":13:".$user["coins"].":17:".$user["userCoins"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":3:".$user["stars"].":46:".$user["diamonds"].":52:".$user["moons"].":4:".$user["demons"].":8:".$user['creatorPoints'].":18:".$user['messagesState'].":19:".$user['friendRequestsState'].":50:".$user['commentsState'].":20:".$user["youtubeurl"].":21:".$user["accIcon"].":22:".$user["accShip"].":23:".$user["accBall"].":24:".$user["accBird"].":25:".$user["accDart"].":26:".$user["accRobot"].":28:".$user["accGlow"].":43:".$user["accSpider"].":48:".$user["accExplosion"].":53:".$user["accSwing"].":54:".$user["accJetpack"].":30:".$user['rank'].":16:".$user["extID"].":31:".$user['friendsState'].":44:".$user["twitter"].":45:".$user["twitch"].":49:".$user['badge'].":55:".$user["dinfo"].":56:".$user["sinfo"].":57:".$user["pinfo"].$user['incomingRequestText'].":29:".$user['isRegistered'];
	}
	
	public static function returnFriendshipsString($person, $user, $isBlocks) {
		if(!$isBlocks) {
			$user['isNew'] = $user['person2'] == $user['extID'] ? $user['isNew1'] : $user['isNew2'];
			$user['canMessage'] = self::canSendMessage($person, $user['extID']) ? 0 : 2;
		}
		
		$user['userName'] = self::makeClanUsername($user['extID']);
		
		return "1:".$user["userName"].":2:".$user["userID"].":9:".$user['icon'].":10:".$user["color1"].":11:".$user["color2"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user["extID"].(!$isBlocks ? ":18:".$user['canMessage'] : '').":41:".$user['isNew'];
	}
	
	public static function returnFriendRequestsString($person, $user) {
		$user['userName'] = self::makeClanUsername($user['extID']);
		
		return "1:".$user["userName"].":2:".$user["userID"].":9:".$user['icon'].":10:".$user["color1"].":11:".$user["color2"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user["extID"].":32:".$user["ID"].":35:".$user["comment"].":41:".$user["isNew"].":37:".$user['uploadTime'];
	}
	public static function getStats() {
		require_once __DIR__."/connection.php";

    	// 2592000 seconds = 30d
    	$query = $db->prepare("SELECT
    		(SELECT COUNT(*) FROM users) AS users,
    		(SELECT COUNT(*) FROM users WHERE lastPlayed > :time - 2592000) AS activeUsers, 
    		(SELECT COUNT(*) FROM levels) AS levels,
    		(SELECT COUNT(*) FROM levels WHERE starStars >= 1) AS ratedLevels,
    		(SELECT COUNT(*) FROM levels WHERE starStars >= 1 AND starFeatured >= 1 AND starEpic = 0) AS featuredLevels,
    		(SELECT COUNT(*) FROM levels WHERE starStars >= 1 AND starFeatured >= 1 AND starEpic = 1) AS epicLevels,
    		(SELECT COUNT(*) FROM levels WHERE starStars >= 1 AND starFeatured >= 1 AND starEpic = 2) AS legendaryLevels,
    		(SELECT COUNT(*) FROM levels WHERE starStars >= 1 AND starFeatured >= 1 AND starEpic = 3) AS mythicLevels,
    		(SELECT COUNT(*) FROM dailyfeatures WHERE type = 0) AS dailies,
    		(SELECT COUNT(*) FROM dailyfeatures WHERE type = 1) AS weeklies,
    		(SELECT COUNT(*) FROM gauntlets) AS gauntlets,
    		(SELECT COUNT(*) FROM mappacks) AS mapPacks,
    		(SELECT COUNT(*) FROM lists) AS lists,
    		(SELECT SUM(downloads) FROM levels) AS downloads,
    		(SELECT COUNT(*) FROM songs) AS songs,
    		(SELECT COUNT(*) FROM songs WHERE reuploadID = 0) AS newgroundsSongs,
    		(SELECT COUNT(*) FROM songs WHERE reuploadID != 0) AS reuploadedSongs,
    		(SELECT name FROM songs ORDER BY levelsCount DESC LIMIT 1) AS mostUsedSongName,
    		(SELECT levelsCount FROM songs ORDER BY levelsCount DESC LIMIT 1) AS mostUsedSongLevelsCount,
    		(SELECT SUM(objects) FROM levels) AS objects,
    		(SELECT SUM(likes) FROM levels) AS likes,
    		(SELECT (SELECT COUNT(*) FROM comments) + (SELECT COUNT(*) FROM acccomments)) AS totalComments,
    		(SELECT COUNT(*) FROM comments) AS comments,
    		(SELECT COUNT(*) FROM acccomments) AS posts,
    		(SELECT COUNT(*) FROM replies) AS postReplies,
    		(SELECT SUM(stars) FROM users WHERE " . self::getBannedPeopleQuery(0) . ") AS stars,
    		(SELECT SUM(creatorPoints) FROM users WHERE " . self::getBannedPeopleQuery(1) . ") AS creatorPoints,
			(SELECT COUNT(*) FROM bans) AS bannedPlayers,
			(SELECT COUNT(personType) FROM bans WHERE personType = 0) AS accountIDBans,
			(SELECT COUNT(personType) FROM bans WHERE personType = 1) AS userIDBans,
			(SELECT COUNT(personType) FROM bans WHERE personType = 2) AS IPBans,
			(SELECT COUNT(banType) FROM bans WHERE banType = 0) AS leaderboardBans,
			(SELECT COUNT(banType) FROM bans WHERE banType = 1) AS creatorBans,
			(SELECT COUNT(banType) FROM bans WHERE banType = 2) AS levelUploadBans,
			(SELECT COUNT(banType) FROM bans WHERE banType = 3) AS commentBans,
			(SELECT COUNT(banType) FROM bans WHERE banType = 4) AS accountBans
		");
    	$query->execute([':time' => time()]);
    	return $query->fetch(PDO::FETCH_ASSOC);
	}
}
?>