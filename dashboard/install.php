<?php
require "incl/dashboardLib.php";
require $dbPath."incl/lib/connection.php";
require $dbPath."incl/lib/mainLib.php";
require $dbPath."config/dashboard.php";
$gs = new mainLib();
http_response_code(307);
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
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	$check = $db->query("SHOW TABLES LIKE 'favsongs'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("CREATE TABLE `favsongs` (
			 `ID` int(20) NOT NULL AUTO_INCREMENT,
			 `songID` int(20) NOT NULL DEFAULT '0',
			 `accountID` int(20) NOT NULL DEFAULT '0',
			 `timestamp` int(20) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
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
			) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
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
		$check = $db->query("SHOW TABLES LIKE 'follows'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("CREATE TABLE `follows` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `person1` int(11) NOT NULL DEFAULT 0,
			 `person2` int(11) NOT NULL DEFAULT 0,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$check = $db->query("SHOW TABLES LIKE 'mutes'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("CREATE TABLE `mutes` (
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			 `person1` int(11) NOT NULL DEFAULT 0,
			 `person2` int(11) NOT NULL DEFAULT 0,
			 `isThread` int(11) NOT NULL DEFAULT 0,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$check = $db->query("SHOW TABLES LIKE 'threads'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("CREATE TABLE `threads` (
			 `threadID` int(11) NOT NULL AUTO_INCREMENT,
			 `threadName` varchar(255) NOT NULL DEFAULT '',
			 `threadDesc` varchar(2048) NOT NULL DEFAULT '',
			 `threadIcon` varchar(50) NOT NULL DEFAULT '',
			 `threadOwner` int(11) NOT NULL DEFAULT 0,
			 `threadMembers` text NOT NULL DEFAULT '',
			 `invitedUsers` text NOT NULL DEFAULT '',
			 `formerMembers` text NOT NULL DEFAULT '',
			 `isDMThread` int(11) NOT NULL DEFAULT 0,
			 `isLocked` int(11) NOT NULL DEFAULT 0,
			 `lastMessageID` int(11) NOT NULL DEFAULT 0,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`threadID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$check = $db->query("SHOW TABLES LIKE 'threadinvites'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("CREATE TABLE `threadinvites` (
			 `inviteID` int(11) NOT NULL AUTO_INCREMENT,
			 `threadID` int(11) NOT NULL DEFAULT 0,
			 `accountID` int(11) NOT NULL DEFAULT 0,
			 `toAccountID` int(11) NOT NULL DEFAULT 0,
			 `isIgnored` int(11) NOT NULL DEFAULT 0,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`inviteID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardLevelPackCreate'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardLevelPackCreate INT NOT NULL DEFAULT '0' AFTER dashboardModTools");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardAddMod'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardAddMod INT NOT NULL DEFAULT '0' AFTER dashboardLevelPackCreate");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageSongs'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardManageSongs INT NOT NULL DEFAULT '0' AFTER dashboardAddMod");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardForceChangePassNick'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD dashboardForceChangePassNick INT NOT NULL DEFAULT '0' AFTER dashboardManageSongs");
		$check = $db->query("SHOW COLUMNS FROM `songs` LIKE 'reuploadID'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE songs ADD reuploadID INT NOT NULL DEFAULT '0' AFTER reuploadTime");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'auth'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE accounts ADD auth varchar(16) NOT NULL DEFAULT 'none' AFTER isActive");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'demonlistAdd'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD demonlistAdd INT NOT NULL DEFAULT '0' AFTER dashboardForceChangePassNick");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'demonlistApprove'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE roles ADD demonlistApprove INT NOT NULL DEFAULT '0' AFTER demonlistAdd");
		$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'clan'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE users ADD clan INT NOT NULL DEFAULT '0' AFTER userName");
		$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'joinedAt'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE users ADD joinedAt INT NOT NULL DEFAULT '0' AFTER clan");
		$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'dlPoints'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE users ADD dlPoints INT NOT NULL DEFAULT '0' AFTER joinedAt");
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
		$check = $db->query("SHOW COLUMNS FROM `clans` LIKE 'tag'");
      		$exist = $check->fetchAll();
      		if(empty($exist)) $db->query("ALTER TABLE clans ADD tag varchar(15) NOT NULL DEFAULT '' AFTER clan");
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
		$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isUploadBanned'");
			$exist = $check->fetchAll();
			if(!empty($exist)) {
				$allBans = $db->prepare('SELECT userID, isBanned, isCreatorBanned, isUploadBanned, isCommentBanned, banReason FROM users WHERE isBanned > 0 OR isCreatorBanned > 0 OR isUploadBanned > 0 OR isCommentBanned > 0');
				$allBans->execute();
				$allBans = $allBans->fetchAll();
				foreach($allBans AS &$ban) {
					if($ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
					switch(true) {
						case $ban['isBanned'] > 0:
							$gs->banPerson(0, $ban['userID'], $ban['banReason'], 0, 1, 2147483647);
							break;
						case $ban['isCreatorBanned'] > 0:
							$gs->banPerson(0, $ban['userID'], $ban['banReason'], 1, 1, 2147483647);
							break;
						case $ban['isUploadBanned'] > 0:
							$gs->banPerson(0, $ban['userID'], $ban['banReason'], 2, 1, 2147483647);
							break;
						case $ban['isCommentBanned'] > 0:
							$gs->banPerson(0, $ban['userID'], $ban['banReason'], 3, 1, 2147483647);
							break;
					}
				}
				$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isBanned'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query('ALTER TABLE `users` DROP `isBanned`');
				$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isCreatorBanned'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query('ALTER TABLE `users` DROP `isCreatorBanned`');
				$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isUploadBanned'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query('ALTER TABLE `users` DROP `isUploadBanned`');
				$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'isCommentBanned'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query('ALTER TABLE `users` DROP `isCommentBanned`');
				$check = $db->query("SHOW COLUMNS FROM `users` LIKE 'banReason'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query('ALTER TABLE `users` DROP `banReason`');
			}
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
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockCommentsOwn'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandLockCommentsOwn` INT NOT NULL DEFAULT '1' AFTER `commandSongAll`");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockCommentsAll'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandLockCommentsAll` INT NOT NULL DEFAULT '0' AFTER `commandLockCommentsOwn`");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'commandLockUpdating'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `commandLockUpdating` INT NOT NULL DEFAULT '0' AFTER `commandLockCommentsAll`");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardDeleteLeaderboards'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardDeleteLeaderboards` INT NOT NULL DEFAULT '0' AFTER `dashboardForceChangePassNick`");
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'dashboardManageLevels'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `dashboardManageLevels` INT NOT NULL DEFAULT '0' AFTER `dashboardDeleteLeaderboards`");
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
					if(empty($exist)) $db->query("ALTER TABLE `events` ADD `rewards` varchar(2048) NOT NULL DEFAULT '0' AFTER `duration`");
				$db->query("UPDATE events SET rewards = CONCAT(type,  \",\", reward)");
				$check = $db->query("SHOW COLUMNS FROM `events` LIKE 'type'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query("ALTER TABLE `events` DROP `type`");
				$db->query("ALTER TABLE `events` DROP `reward`");
			}
		$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'reward'");
			$exist = $check->fetchAll();
			if(!empty($exist)) {
				$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'rewards'");
					$exist = $check->fetchAll();
					if(empty($exist)) $db->query("ALTER TABLE `vaultcodes` ADD `rewards` varchar(2048) NOT NULL DEFAULT '0' AFTER `duration`");
				$db->query("UPDATE vaultcodes SET rewards = CONCAT(type,  \",\", reward)");
				$check = $db->query("SHOW COLUMNS FROM `vaultcodes` LIKE 'type'");
					$exist = $check->fetchAll();
					if(!empty($exist)) $db->query("ALTER TABLE `vaultcodes` DROP `type`");
				$db->query("ALTER TABLE `vaultcodes` DROP `reward`");
			}
		$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'readTime'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `messages` ADD `readTime` INT NOT NULL DEFAULT '0' AFTER `isNew`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'instagram'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `instagram` varchar(255) NOT NULL DEFAULT '' AFTER `twitch`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'tiktok'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `tiktok` varchar(255) NOT NULL DEFAULT '' AFTER `instagram`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'discord'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `discord` varchar(255) NOT NULL DEFAULT '' AFTER `tiktok`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'custom'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `custom` varchar(255) NOT NULL DEFAULT '' AFTER `discord`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'gF'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `gF` int(11) NOT NULL DEFAULT 1 AFTER `cS`");
		$check = $db->query("SHOW COLUMNS FROM `accounts` LIKE 'gO'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `accounts` ADD `gO` int(11) NOT NULL DEFAULT 2 AFTER `gF`");
		$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'isThreadMessage'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `messages` ADD `isThreadMessage` int(11) NOT NULL DEFAULT 0 AFTER `readTime`");
		$check = $db->query("SHOW COLUMNS FROM `messages` LIKE 'isDeleted'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `messages` ADD `isDeleted` int(11) NOT NULL DEFAULT 0 AFTER `isThreadMessage`");
		$check = $db->query("SHOW COLUMNS FROM `friendships` LIKE 'timestamp'");
			$exist = $check->fetchAll();
			if(empty($exist)) {
				$db->query("ALTER TABLE `friendships` ADD `timestamp` int(11) NOT NULL DEFAULT 0 AFTER `isNew2`");
				
				$friendshipsArray = [];
				$friendshipsString = "";
				
				$friendshipsActions = $db->prepare("SELECT * FROM actions WHERE type = 28 ORDER BY timestamp DESC");
				$friendshipsActions->execute();
				$friendshipsActions = $friendshipsActions->fetchAll();
				
				foreach($friendshipsActions AS &$action) {
					$friendshipsUsers = $action["account"] > $action["value"] ? $action["value"].",".$action["account"] : $action["account"].",".$action["value"];
					if(isset($friendshipsArray[$friendshipsUsers])) continue;
					
					$friendshipsArray[$friendshipsUsers] = true;
					$friendshipsString .= 'WHEN ((person1 = '.$action["account"].' AND person2 = '.$action["value"].') OR (person1 = '.$action["value"].' AND person2 = '.$action["account"].')) THEN '.$action["timestamp"].PHP_EOL;
				}
				
				$fillTimestamps = $db->prepare("UPDATE friendships SET timestamp = (CASE
						".$friendshipsString."
						ELSE 0
					END)");
				$fillTimestamps->execute();
			}
		$check = $db->query("SHOW COLUMNS FROM `blocks` LIKE 'timestamp'");
			$exist = $check->fetchAll();
			if(empty($exist)) {
				$db->query("ALTER TABLE `blocks` ADD `timestamp` int(11) NOT NULL DEFAULT 0 AFTER `person2`");
				
				$blocksArray = [];
				$blocksString = "";
				
				$blocksActions = $db->prepare("SELECT * FROM actions WHERE type = 29 ORDER BY timestamp DESC");
				$blocksActions->execute();
				$blocksActions = $blocksActions->fetchAll();
				
				foreach($blocksActions AS &$action) {
					$blocksUsers = $action["account"].",".$action["value"];
					if(isset($blocksArray[$blocksUsers])) continue;
					
					$blocksArray[$blocksUsers] = true;
					$blocksString .= 'WHEN (person1 = '.$action["account"].' AND person2 = '.$action["value"].') THEN '.$action["timestamp"].PHP_EOL;
				}
				
				$fillTimestamps = $db->prepare("UPDATE blocks SET timestamp = (CASE
						".$blocksString."
						ELSE 0
					END)");
				$fillTimestamps->execute();
			}
		$check = $db->query("SHOW COLUMNS FROM `roles` LIKE 'gameAddMembersWithoutInvite'");
			$exist = $check->fetchAll();
			if(empty($exist)) $db->query("ALTER TABLE `roles` ADD `gameAddMembersWithoutInvite` INT NOT NULL DEFAULT '0' AFTER `toolSuggestlist`");
	$lines = file($dbPath.'config/dashboard.php');
	$first_line = $lines[2];
	$lines = array_slice($lines, 1 + 2);
	$lines = array_merge(array($first_line, "\n"), $lines);
	$file = fopen($dbPath.'config/dashboard.php', 'w');
  	fwrite($file, "<?php\r\n");
  	fwrite($file, "\$installed = true; // Like i said, it changed!\r");
	fwrite($file, implode('', $lines));
  	fclose($file);
  	header('Location: .?installed');
} else header('Location: .');
?>