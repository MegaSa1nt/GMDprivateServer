<?php
if(!isset($db)) global $db;

require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/cron.php";
require_once __DIR__."/ip.php";
require_once __DIR__."/mainLib.php";
require_once __DIR__."/security.php";

$IP = IP::getIP();

if(!$installed) {
	$check = $db->query("SHOW TABLES LIKE 'replies'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `replies` (
			 `replyID` int(11) NOT NULL AUTO_INCREMENT,
			 `commentID` int(11) NOT NULL,
			 `accountID` int(11) NOT NULL,
			 `body` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
			 `timestamp` int(11) NOT NULL,
			 PRIMARY KEY (`replyID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	$check = $db->query("SHOW TABLES LIKE 'demonlist'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("DROP TABLE `demonlist`");
	$check = $db->query("SHOW TABLES LIKE 'dlsubmits'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("DROP TABLE `dlsubmits`");
	$check = $db->query("SHOW TABLES LIKE 'modipperms'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("DROP TABLE `modipperms`");
	$check = $db->query("SHOW TABLES LIKE 'modips'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("DROP TABLE `modips`");
	$check = $db->query("SHOW TABLES LIKE 'favsongs'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `favsongs` (
			 `ID` int(20) NOT NULL AUTO_INCREMENT,
			 `songID` int(20) NOT NULL DEFAULT '0',
			 `accountID` int(20) NOT NULL DEFAULT '0',
			 `timestamp` int(20) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	$check = $db->query("SHOW TABLES LIKE 'clans'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `clans` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `clan` varchar(255) NOT NULL DEFAULT '',
			 `desc` varchar(2048) NOT NULL DEFAULT '',
			 `clanOwner` int(11) NOT NULL DEFAULT '0',
			 `color` varchar(6) NOT NULL DEFAULT 'FFFFFF',
			 `isClosed` int(11) NOT NULL DEFAULT '0',
			 `creationDate` int(11) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$check = $db->query("SHOW TABLES LIKE 'clanrequests'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `clanrequests` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `accountID` int(11) NOT NULL DEFAULT '0',
			 `clanID` int(11) NOT NULL DEFAULT '0',
			 `timestamp` int(11) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$check = $db->query("SHOW TABLES LIKE 'sfxs'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `sfxs` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			 `authorName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			 `download` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
			 `milliseconds` int(11) NOT NULL DEFAULT '0',
			 `size` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			 `isDisabled` int(11) NOT NULL DEFAULT '0',
			 `levelsCount` int(11) NOT NULL DEFAULT '0',
			 `reuploadID` int(11) NOT NULL DEFAULT '0',
			 `reuploadTime` int(11) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`ID`),
			 KEY `name` (`name`),
			 KEY `authorName` (`authorName`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
	$check = $db->query("SHOW TABLES LIKE 'bans'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `bans` (
			 `banID` int(11) NOT NULL AUTO_INCREMENT,
			 `modID` varchar(255) NOT NULL DEFAULT '',
			 `person` varchar(50) NOT NULL DEFAULT '',
			 `reason` varchar(2048) NOT NULL DEFAULT '',
			 `banType` int(11) NOT NULL DEFAULT 0,
			 `personType` int(11) NOT NULL DEFAULT 0,
			 `expires` int(11) NOT NULL DEFAULT 0,
			 `isActive` int(11) NOT NULL DEFAULT 1,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`banID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	$check = $db->query("SHOW TABLES LIKE 'automod'");
   		$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("CREATE TABLE `automod` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `type` int(11) NOT NULL DEFAULT 0,
			 `value1` varchar(255) NOT NULL DEFAULT '',
			 `value2` varchar(255) NOT NULL DEFAULT '',
			 `value3` varchar(255) NOT NULL DEFAULT '',
			 `value4` varchar(255) NOT NULL DEFAULT '',
			 `value5` varchar(255) NOT NULL DEFAULT '',
			 `value6` varchar(255) NOT NULL DEFAULT '',
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 `resolved` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	$check = $db->query("SHOW TABLES LIKE 'events'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("CREATE TABLE `events` (
			`feaID` int(11) NOT NULL AUTO_INCREMENT,
			 `levelID` int(11) NOT NULL,
			 `timestamp` int(11) NOT NULL,
			 `duration` int(11) NOT NULL,
			 `rewards` varchar(2048) NOT NULL DEFAULT '',
			 `webhookSent` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`feaID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	$check = $db->query("SHOW TABLES LIKE 'vaultcodes'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("CREATE TABLE `vaultcodes` (
			 `rewardID` int(11) NOT NULL AUTO_INCREMENT,
			 `code` varchar(255) NOT NULL DEFAULT '',
			 `rewards` varchar(2048) NOT NULL DEFAULT '',
			 `duration` int(11) NOT NULL DEFAULT 0,
			 `uses` int(11) NOT NULL DEFAULT -1,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`rewardID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	$check = $db->query("SHOW TABLES LIKE 'udids'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("CREATE TABLE `udids` (
					`ID` int(11) NOT NULL AUTO_INCREMENT,
					`userID` int(11) NOT NULL DEFAULT 0,
					`udids` varchar(1024) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
					PRIMARY KEY (`ID`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			$extIDs = $db->prepare("SELECT userID, extID FROM users WHERE extID NOT REGEXP '^[0-9]+$' AND extID != ''");
			$extIDs->execute();
			$extIDs = $extIDs->fetchAll();
			
			foreach($extIDs AS &$udid) Security::hashUDID($udid['userID'], $udid['extID']);
		}
	$check = $db->query("SHOW TABLES LIKE 'clancomments'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("CREATE TABLE `clancomments` (
				`commentID` INT(11) NOT NULL AUTO_INCREMENT,
				`comment` varchar(1024) NOT NULL DEFAULT '',
				`userID` INT(11) NOT NULL DEFAULT '0',
				`clanID` INT(11) NOT NULL DEFAULT '0',
				`likes` INT(11) NOT NULL DEFAULT '0',
				`dislikes` INT(11) NOT NULL DEFAULT '0',
				`timestamp` INT(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`commentID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			
			$db->query("ALTER TABLE clans CHANGE `ID` `clanID` INT NOT NULL AUTO_INCREMENT");
			$db->query("ALTER TABLE clans CHANGE `clan` `clanName` varchar(255) NOT NULL DEFAULT ''");
			$db->query("ALTER TABLE clans CHANGE `desc` `clanDesc` varchar(2048) NOT NULL DEFAULT ''");
			$db->query("ALTER TABLE clans CHANGE `color` `clanColor` varchar(6) NOT NULL DEFAULT 'FFFFFF'");
			
			$check = $db->query("SHOW COLUMNS FROM `clans` LIKE 'tag'");
				$exist = $check->fetchAll();
				if(!empty($exist)) $db->query("ALTER TABLE clans CHANGE `tag` `clanTag` varchar(15) NOT NULL DEFAULT ''");
				else $db->query("ALTER TABLE clans ADD clanTag varchar(15) NOT NULL DEFAULT '' AFTER clanID");
				
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'clan'");
				$exist = $check->fetchAll();
				if(!empty($exist)) $db->query("ALTER TABLE users CHANGE `clan` `clanID` INT NOT NULL DEFAULT 0");
				else $db->query("ALTER TABLE users ADD clanID INT NOT NULL DEFAULT '0' AFTER userName");
								
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'joinedAt'");
				$exist = $check->fetchAll();
				if(empty($exist)) $db->query("ALTER TABLE users ADD joinedAt INT NOT NULL DEFAULT '0' AFTER clanID");
			
			$db->query("ALTER TABLE clans ADD `clanMembers` varchar(2048) NOT NULL DEFAULT '' AFTER `clanOwner`");
			$db->query("ALTER TABLE clans ADD `clanRank` INT NOT NULL DEFAULT 0 AFTER `clanColor`");
			
			$clanMembersArray = [];
			$clanMembers = $db->prepare("SELECT extID, clanID FROM users WHERE clanID != 0 ORDER BY joinedAt ASC");
			$clanMembers->execute();
			$clanMembers = $clanMembers->fetchAll();
			
			foreach($clanMembers AS &$clanMember) {
				$clanMembersArray[$clanMember['clanID']][] = $clanMember['extID'];
			}
			
			foreach($clanMembersArray AS $clanID => $accounts) {
				$insertClan = $db->prepare('UPDATE clans SET clanMembers = "'.implode(',', $accounts).'" WHERE clanID = :clanID');
				$insertClan->execute([':clanID' => $clanID]);
			}
			
			$cronPerson = [
				'accountID' => 0,
				'userID' => 0,
				'userName' => "Undefined",
				'IP' => $IP
			];
			
			Cron::updateClansRanks($cronPerson, false);
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardLevelPackCreate'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardLevelPackCreate INT NOT NULL DEFAULT '0' AFTER dashboardModTools");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardAddMod'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardAddMod INT NOT NULL DEFAULT '0' AFTER dashboardLevelPackCreate");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageSongs'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardManageSongs INT NOT NULL DEFAULT '0' AFTER dashboardAddMod");
	$check = $db->query("SHOW COLUMNS FROM `songs` LIKE 'reuploadID'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE songs ADD reuploadID INT NOT NULL DEFAULT '0' AFTER reuploadTime");
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'auth'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE accounts ADD auth varchar(16) NOT NULL DEFAULT 'none' AFTER isActive");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'demonlistAdd'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("ALTER TABLE roles DROP `demonlistAdd`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'demonlistApprove'");
		$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("ALTER TABLE roles DROP `demonlistApprove`");
	$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'dlPoints'");
   		$exist = $check->fetchAll();
   		if(!empty($exist)) $db->query("ALTER TABLE users DROP `dlPoints`");
	$check = $db->query("SHOW COLUMNS FROM `gauntlets` LIKE 'timestamp'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE gauntlets ADD timestamp INT NOT NULL DEFAULT '0' AFTER level5");
	$check = $db->query("SHOW COLUMNS FROM `mappacks` LIKE 'timestamp'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE mappacks ADD timestamp INT NOT NULL DEFAULT '0' AFTER colors2");
	$check = $db->query("SHOW COLUMNS FROM `songs` LIKE 'duration'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE songs ADD duration INT NOT NULL DEFAULT '0' AFTER size");
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'passCode'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE accounts ADD passCode varchar(255) NOT NULL DEFAULT '' AFTER auth");
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'timezone'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE accounts ADD timezone varchar(255) NOT NULL DEFAULT '' AFTER passCode");
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'mail'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE accounts ADD mail varchar(255) NOT NULL DEFAULT '' AFTER auth");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardGauntletCreate'");
   		$exist = $check->fetchAll();
   		if(empty($exist)) $db->query("ALTER TABLE `roles` CHANGE `toolPackcreate` `dashboardGauntletCreate` INT(11) NOT NULL DEFAULT '0'");
	$check = $db->query("SHOW COLUMNS FROM `dailyfeatures` LIKE 'webhookSent'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE dailyfeatures ADD webhookSent INT NOT NULL DEFAULT '0' AFTER type");
	$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isBanned'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isCreatorBanned'");
			$creatorBanned = $check->fetchAll();
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isUploadBanned'");
			$uploadBanned = $check->fetchAll();
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isCommentBanned'");
			$commentBanned = $check->fetchAll();
			$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'banReason'");
			$banReason = $check->fetchAll();
			// Absolutely cursed, but idc, all for full compatibility
			$allBans = $db->prepare('SELECT extID, userID, IP, isBanned'.(!empty($creatorBanned) ? ', isCreatorBanned' : '').(!empty($uploadBanned) ? ', isUploadBanned' : '').(!empty($commentBanned) ? ', isCommentBanned' : '').(!empty($banReason) ? ', banReason' : '').' FROM users WHERE isBanned > 0'.(!empty($creatorBanned) ? ' OR isCreatorBanned > 0' : '').(!empty($uploadBanned) ? ' OR isUploadBanned > 0' : '').(!empty($commentBanned) ? ' OR isCommentBanned > 0' : ''));
			$allBans->execute();
			$allBans = $allBans->fetchAll();
			foreach($allBans AS &$ban) {
				if($ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
				
				$banPerson = [
					'accountID' => $ban['extID'],
					'userID' => $ban['userID'],
					'IP' => $ban['IP'],
				];
				
				switch(true) {
					case $ban['isBanned'] > 0:
						Library::banPerson(0, $banPerson, $ban['banReason'], 0, 1, 2147483647);
						break;
					case $ban['isCreatorBanned'] > 0:
						Library::banPerson(0, $banPerson, $ban['banReason'], 1, 1, 2147483647);
						break;
					case $ban['isUploadBanned'] > 0:
						Library::banPerson(0, $banPerson, $ban['banReason'], 2, 1, 2147483647);
						break;
					case $ban['isCommentBanned'] > 0:
						Library::banPerson(0, $banPerson, $ban['banReason'], 3, 1, 2147483647);
						break;
				}
			}
			$db->query('ALTER TABLE `users` DROP `isBanned`');
			if(!empty($creatorBanned)) $db->query('ALTER TABLE `users` DROP `isCreatorBanned`');
			if(!empty($uploadBanned)) $db->query('ALTER TABLE `users` DROP `isUploadBanned`');
			if(!empty($commentBanned)) $db->query('ALTER TABLE `users` DROP `isCommentBanned`');
			if(!empty($banReason)) $db->query('ALTER TABLE `users` DROP `banReason`');
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandRenameOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandRenameOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandRenameAll` `commandRename` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandPassOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandPassOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandPassAll` `commandPass` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandDescriptionOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandDescriptionOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandDescriptionAll` `commandDescription` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandPublicOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandPublicOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandPublicAll` `commandPublic` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandUnlistOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandUnlistOwn`");
			$db->query("ALTER TABLE `roles` DROP `commandUnlistAll`");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandSharecpOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandSharecpOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandSharecpAll` `commandSharecp` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandSongOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandSongOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandSongAll` `commandSong` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockCommentsOwn'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$db->query("ALTER TABLE `roles` DROP `commandLockCommentsOwn`");
			$db->query("ALTER TABLE `roles` CHANGE `commandLockCommentsAll` `commandLockComments` INT NOT NULL DEFAULT '0'");
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockComments'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandLockComments` INT NOT NULL DEFAULT '0' AFTER `commandSong`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'profilecommandDiscord'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `profilecommandDiscord`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'originalServer'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `levels` ADD `originalServer` VARCHAR(255) NOT NULL DEFAULT '' AFTER `originalReup`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'updateLocked'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `levels` ADD `updateLocked` INT NOT NULL DEFAULT '0' AFTER `settingsString`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'commentLocked'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `levels` ADD `commentLocked` INT NOT NULL DEFAULT '0' AFTER `updateLocked`");
	$check = $db->query("SHOW COLUMNS FROM `lists` LIKE 'commentLocked'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `lists` ADD `commentLocked` INT NOT NULL DEFAULT '0' AFTER `unlisted`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockUpdating'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandLockUpdating` INT NOT NULL DEFAULT '0' AFTER `commandLockComments`");
	$check = $db->query("SHOW COLUMNS FROM `sfxs` LIKE 'token'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `sfxs` ADD `token` varchar(255) NOT NULL DEFAULT '' AFTER `reuploadTime`");
	$db->query("ALTER TABLE `actions` CHANGE `account` `account` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `actions` LIKE 'IP'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `actions` ADD `IP` VARCHAR(255) NOT NULL DEFAULT '' AFTER `account`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageAutomod'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardManageAutomod` INT NOT NULL DEFAULT '0' AFTER `dashboardManageLevels`");
	$db->query("ALTER TABLE `actions` CHANGE `value3` `value3` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `actions` CHANGE `value4` `value4` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `actions` CHANGE `value5` `value5` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `actions` CHANGE `value6` `value6` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardVaultCodesManage'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardVaultCodesManage` INT NOT NULL DEFAULT '0' AFTER `dashboardManageAutomod`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandEvent'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandEvent` INT NOT NULL DEFAULT '0' AFTER `commandWeekly`");
	$check = $db->query("SHOW COLUMNS FROM `events` LIKE 'reward'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$check = $db->query("SHOW COLUMNS FROM `events` LIKE 'rewards'");
				$exist = $check->fetchAll();
				if(empty($exist)) $db->query("ALTER TABLE `events` ADD `rewards` varchar(2048) NOT NULL DEFAULT '' AFTER `duration`");
			$db->query("UPDATE events SET rewards = CONCAT(type,  \",\", reward)");
			$check = $db->query("SHOW COLUMNS FROM `events` LIKE 'type'");
				$exist = $check->fetchAll();
				if(!empty($exist)) {
					$db->query("ALTER TABLE `events` DROP `type`");
					$db->query("ALTER TABLE `events` DROP `reward`");
				}
		}
	$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'reward'");
		$exist = $check->fetchAll();
		if(!empty($exist)) {
			$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'rewards'");
				$exist = $check->fetchAll();
				if(empty($exist)) $db->query("ALTER TABLE `vaultcodes` ADD `rewards` varchar(2048) NOT NULL DEFAULT '' AFTER `duration`");
			$db->query("UPDATE vaultcodes SET rewards = CONCAT(type,  \",\", reward)");
			$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'type'");
				$exist = $check->fetchAll();
				if(!empty($exist)) {
					$db->query("ALTER TABLE `vaultcodes` DROP `type`");
					$db->query("ALTER TABLE `vaultcodes` DROP `reward`");
				}
		}
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'readTime'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `messages` ADD `readTime` INT NOT NULL DEFAULT '0' AFTER `isNew`");
	$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `users` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `acccomments` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `acccomments` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `comments` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `comments` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `levels` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `messages` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'secret'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `users` DROP `secret`");
	$check = $db->query("SHOW COLUMNS FROM `acccomments` LIKE 'userName'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `acccomments` DROP `userName`");
	$check = $db->query("SHOW COLUMNS FROM `acccomments` LIKE 'dislikes'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `acccomments` ADD `dislikes` INT NOT NULL DEFAULT '0' AFTER `likes`");
	$check = $db->query("SHOW COLUMNS FROM `comments` LIKE 'dislikes'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `comments` ADD `dislikes` INT NOT NULL DEFAULT '0' AFTER `likes`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'dislikes'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `levels` ADD `dislikes` INT NOT NULL DEFAULT '0' AFTER `likes`");
	$check = $db->query("SHOW COLUMNS FROM `lists` LIKE 'dislikes'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `lists` ADD `dislikes` INT NOT NULL DEFAULT '0' AFTER `likes`");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'difficultyDenominator'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `levels` ADD `difficultyDenominator` INT NOT NULL DEFAULT '10' AFTER `starDifficulty`");
	$check = $db->query("SHOW COLUMNS FROM `actions_downloads` LIKE 'accountID'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `actions_downloads` ADD `accountID` varchar(255) NOT NULL DEFAULT '' AFTER `ip`");
	$check = $db->query("SHOW COLUMNS FROM `actions_likes` LIKE 'accountID'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `actions_likes` ADD `accountID` varchar(255) NOT NULL DEFAULT '' AFTER `ip`");
	$check = $db->query("SHOW COLUMNS FROM `comments` LIKE 'userName'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `comments` DROP `userName`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'actionRequestMod'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `actionRequestMod`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'toolLeaderboardsban'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `toolLeaderboardsban`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'toolQuestsCreate'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `toolQuestsCreate`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'toolModactions'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `toolModactions`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'toolSuggestlist'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `toolSuggestlist`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'modipCategory'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `modipCategory`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commentsExtraText'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commentsExtraText` VARCHAR(255) NOT NULL DEFAULT '' AFTER `roleName`");
	$check = $db->query("SHOW COLUMNS FROM `roleassign` LIKE 'accountID'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roleassign` CHANGE `accountID` `person` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `roleassign` LIKE 'personType'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roleassign` ADD `personType` INT NOT NULL DEFAULT '0' AFTER `person`");
	$db->query("ALTER TABLE `modactions` CHANGE `value3` `value3` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `modactions` CHANGE `value5` `value5` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `modactions` CHANGE `value6` `value6` VARCHAR(255) NOT NULL DEFAULT ''");
	$db->query("ALTER TABLE `modactions` CHANGE `account` `account` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandUnepic'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `roles` DROP `commandUnepic`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandSuggest'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandSuggest` INT NOT NULL DEFAULT '0' AFTER `commandEvent`");
	$check = $db->query("SHOW COLUMNS FROM `modactions` LIKE 'IP'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `modactions` ADD `IP` VARCHAR(255) NOT NULL DEFAULT '' AFTER `value7`");
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'userName'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `messages` DROP `userName`");
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'userID'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `messages` DROP `userID`");
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'accID'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `messages` CHANGE `accID` `accountID` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'toAccountID'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `messages` CHANGE `toAccountID` `toAccountID` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'userName'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `levels` DROP `userName`");
	$check = $db->query("SHOW COLUMNS FROM `reports` LIKE 'hostname'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `reports` CHANGE `hostname` `IP` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `levels` LIKE 'hostname'");
		$exist = $check->fetchAll();
		if(!empty($exist)) $db->query("ALTER TABLE `levels` CHANGE `hostname` `IP` VARCHAR(255) NOT NULL DEFAULT ''");
	$check = $db->query("SHOW COLUMNS FROM `platscores` LIKE 'attempts'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("ALTER TABLE `platscores` ADD `attempts` INT NOT NULL DEFAULT '0' AFTER `points`");
			$db->query("ALTER TABLE `platscores` ADD `clicks` INT NOT NULL DEFAULT '0' AFTER `attempts`");
			$db->query("ALTER TABLE `platscores` ADD `progresses` VARCHAR(255) NOT NULL DEFAULT '' AFTER `clicks`");
			$db->query("ALTER TABLE `platscores` ADD `coins` INT NOT NULL DEFAULT '0' AFTER `progresses`");
			$db->query("ALTER TABLE `platscores` ADD `dailyID` INT NOT NULL DEFAULT '0' AFTER `coins`");
		}
	$check = $db->query("SHOW COLUMNS FROM `lists` LIKE 'rateDate'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `lists` ADD `rateDate` INT NOT NULL DEFAULT '0' AFTER `updateDate`");
	$check = $db->query("SHOW COLUMNS FROM `comments` LIKE 'creatorRating'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `comments` ADD `creatorRating` INT NOT NULL DEFAULT '0' AFTER `isSpam`");
	$check = $db->query("SELECT count(*) FROM `accounts` WHERE auth = 'none'");
		$exist = $check->fetchColumn();
		if($exist) $db->query("UPDATE accounts SET auth = '' WHERE auth = 'none'");
	$check = $db->query("SELECT count(*) FROM `accounts` WHERE mail = 'none' OR mail = 'activated'");
		$exist = $check->fetchColumn();
		if($exist) $db->query("UPDATE accounts SET mail = '' WHERE mail = 'none' OR mail = 'activated'");
	$check = $db->query("SHOW COLUMNS FROM `bans` LIKE 'modReason'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `bans` ADD `modReason` VARCHAR(2048) NOT NULL DEFAULT '' AFTER `reason`");
	$check = $db->query("SHOW COLUMNS FROM `songs` LIKE 'favouritesCount'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `songs` ADD `favouritesCount` INT NOT NULL DEFAULT '0' AFTER `levelsCount`");
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'registerIP'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("ALTER TABLE `accounts` ADD `registerIP` VARCHAR(255) NOT NULL DEFAULT '' AFTER `registerDate`");
			
			// Filling IPs from actions table
			$getIPs = $db->prepare("SELECT account, IP FROM actions WHERE type = 1");
			$getIPs->execute();
			$getIPs = $getIPs->fetchAll();
			
			foreach($getIPs AS &$account) {
				$fillIP = $db->prepare("UPDATE accounts SET registerIP = :registerIP WHERE accountID = :accountID");
				$fillIP->execute([':registerIP' => $account['IP'], ':accountID' => $account['account']]);
			}
			
			// Fill IPs of accounts before using newer version of my core
			$getAccounts = $db->prepare("SELECT accountID FROM accounts WHERE registerIP = ''");
			$getAccounts->execute();
			$getAccounts = $getAccounts->fetchAll();
			if($getAccounts) {
				foreach($getAccounts AS &$account) {
					$getIP = $db->prepare("SELECT IP FROM users WHERE extID = :accountID");
					$getIP->execute([':accountID' => $account['accountID']]);
					$getIP = $getIP->fetchColumn();
					
					$fillIP = $db->prepare("UPDATE accounts SET registerIP = :registerIP WHERE accountID = :accountID");
					$fillIP->execute([':registerIP' => $getIP, ':accountID' => $account['accountID']]);
				}
			}
		}
	
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'mailExpires'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("ALTER TABLE `accounts` ADD `mailExpires` INT NOT NULL DEFAULT '0' AFTER `mail`");
			$db->query("UPDATE accounts SET mailExpires = ".(time() + 3600));
		}
	$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'passCodeExpires'");
		$exist = $check->fetchAll();
		if(empty($exist)) {
			$db->query("ALTER TABLE `accounts` ADD `passCodeExpires` INT NOT NULL DEFAULT '0' AFTER `passCode`");
			$db->query("UPDATE accounts SET passCodeExpires = ".(time() + 3600));
		}
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardForceChangePassNick'");
    	$exist = $check->fetchAll();
    	if(!empty($exist)) $db->query("ALTER TABLE roles CHANGE `dashboardForceChangePassNick` `dashboardManageAccounts` INT NOT NULL DEFAULT '0'");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageAccounts'");
    	$exist = $check->fetchAll();
    	if(empty($exist)) $db->query("ALTER TABLE roles ADD `dashboardManageAccounts` INT NOT NULL DEFAULT '0' AFTER `dashboardManageSongs`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardDeleteLeaderboards'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardDeleteLeaderboards` INT NOT NULL DEFAULT '0' AFTER `dashboardManageAccounts`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageLevels'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardManageLevels` INT NOT NULL DEFAULT '0' AFTER `dashboardDeleteLeaderboards`");
	$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageClans'");
		$exist = $check->fetchAll();
		if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardManageClans` INT NOT NULL DEFAULT '0' AFTER `dashboardManageLevels`");
	
	$lines = file(__DIR__.'/../../config/dashboard.php');
	$first_line = $lines[2];
	$lines = array_slice($lines, 1 + 2);
	$lines = array_merge(array($first_line, "\n"), $lines);
	$file = fopen(__DIR__.'/../../config/dashboard.php', 'w');
	if($file) {
		fwrite($file, "<?php".PHP_EOL);
		fwrite($file, "\$installed = true; // Like i said, it changed!");
		fwrite($file, implode('', $lines));
		fclose($file);
	} else {
		exit("<h1>Failed opening file config/dashboard.php!</h1>
		<br>
		<p>Make sure this file exists and PHP has permissions to write to it.</p>");
	}
}
?>