<?php
if(!isset($db)) global $db;

require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/cron.php";
require_once __DIR__."/ip.php";
require_once __DIR__."/enums.php";
require_once __DIR__."/mainLib.php";
require_once __DIR__."/security.php";

$IP = IP::getIP();

$errorNoRequiredColumn = '<h1>Failed migrating GDPS!</h1>
	<br>
	<p>Required column "%1$s" doesn\'t exist. Did you import database.sql?</p>';
$postMigrationCommands = $accountRegisterIPMigration = [];
$accountRegisterIPMigrationCaseString = $accountRegisterIPMigrationWhereString = $accountRegisterIPMigrationCaseStringAccountID = $accountRegisterIPMigrationWhereStringAccountID = '';

function getTableColumns($table) {
	global $db;
	$tableColumns = [];
	
	$tableExists = $db->query("SHOW TABLES LIKE '".$table."'");
	$tableExists = $tableExists->fetchColumn();
	if(!$tableExists) return [];
	
	$tableColumnsArray = $db->query("SHOW COLUMNS FROM ".$table);
	$tableColumnsArray = $tableColumnsArray->fetchAll();
	
	foreach($tableColumnsArray AS &$tableColumn) $tableColumns[$tableColumn['Field']] = $tableColumn['Type'];
	
	return $tableColumns;
}

if(!$installed) {
	// Migrate table "acccomments"
	$columnsToDelete = ['userName'];
	$columnsToAdd = [ // column name, column type, default value, add after this column
		['dislikes', 'int(11)', '0', 'likes'],
		['isSpam', 'tinyint(1)', '0', 'dislikes'],
	];
	
	$acccommentsColumns = getTableColumns('acccomments');
	if(!$acccommentsColumns) exit(sprintf($errorNoRequiredColumn, 'acccomments'));
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($acccommentsColumns[$column])) continue;
		
		unset($acccommentsColumns[$column]);
		
		$db->query("ALTER TABLE `acccomments` DROP `".$column."`");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($acccommentsColumns[$column[0]])) {
			if($acccommentsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `acccomments` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `acccomments` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'dislikes':
				$db->query("UPDATE acccomments SET dislikes = likes * -1, likes = 0 WHERE likes < 0");
				break;
		}
	}
	
	// Migrate table "accounts"
	$columnsToAdd = [
		['auth', 'varchar(66)', '', 'isActive'],
		['mail', 'varchar(66)', '', 'auth'],
		['passCode', 'varchar(66)', '', 'mail'],
		['timezone', 'varchar(255)', 'America/Danmarkshavn', 'passCode'],
		
		['registerIP', 'varchar(255)', '', 'registerDate'],
		['isDeleted', 'tinyint(1)', '0', 'isActive'],
		['mailExpires', 'int(11)', '0', 'mail'],
		['passCodeExpires', 'int(11)', '0', 'passCode']
	];
	
	$accountsColumns = getTableColumns('accounts');
	if(!$accountsColumns) exit(sprintf($errorNoRequiredColumn, 'accounts'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($accountsColumns[$column[0]])) {
			if($accountsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `accounts` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			switch($column[0]) {
				case 'auth':
					$db->query("UPDATE accounts SET auth = '' WHERE auth = 'none'");
					break;
				case 'mail':
					$db->query("UPDATE accounts SET mail = '' WHERE mail IN ('none', 'activated')");
					break;
			}
			
			continue;
		}
		
		$db->query("ALTER TABLE `accounts` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'mailExpires':
				$db->query("UPDATE accounts SET mailExpires = ".(time() + 3600));
				break;
			case 'passCodeExpires':
				$db->query("UPDATE accounts SET passCodeExpires = ".(time() + 3600));
				break;
			case 'registerIP':
				// Filling IPs from actions table
				$postMigrationCommands[] = ['accountsRegisterIPActions'];
				
				// Fill IPs of accounts created before using newer version of my core
				$getAccounts = $db->prepare("SELECT accountID FROM accounts WHERE registerIP = '' ORDER BY accountID ASC");
				$getAccounts->execute();
				$getAccounts = $getAccounts->fetchAll();
				if($getAccounts) {
					foreach($getAccounts AS &$account) {
						$getIP = $db->prepare("SELECT IP FROM users WHERE extID = :accountID ORDER BY isRegistered DESC, extID ASC");
						$getIP->execute([':accountID' => $account['accountID']]);
						$getIP = $getIP->fetchColumn();
						
						$fillIP = $db->prepare("UPDATE accounts SET registerIP = :registerIP WHERE accountID = :accountID");
						$fillIP->execute([':registerIP' => $getIP, ':accountID' => $account['accountID']]);
						
						$postMigrationCommands[] = ['accountsRegisterIP', $getIP, $account['accountID']];
					}
				}
				
				break;
		}
	}
	
	// Migrate table "actions"
	$columnsToAdd = [
		['value', 'varchar(2048)', '', 'type'],
		['value2', 'varchar(2048)', '', 'value'],
		['value3', 'varchar(2048)', '', 'value2'],
		['value4', 'varchar(255)', '', 'value3'],
		['value5', 'varchar(255)', '', 'value4'],
		['value6', 'varchar(255)', '', 'value5'],
		['account', 'varchar(255)', '', 'value6'],
		['IP', 'varchar(255)', '', 'account']
	];
	
	$actionsColumns = getTableColumns('actions');
	if(!$actionsColumns) exit(sprintf($errorNoRequiredColumn, 'actions'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($actionsColumns[$column[0]])) {
			if($actionsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `actions` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `actions` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "actions_downloads"
	$columnsToAdd = [
		['accountID', 'varchar(255)', '', 'IP']
	];
	
	$actionsDownloadsColumns = getTableColumns('actions_downloads');
	if(!$actionsDownloadsColumns) exit(sprintf($errorNoRequiredColumn, 'actions_downloads'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($actionsDownloadsColumns[$column[0]])) {
			if($actionsDownloadsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `actions_downloads` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `actions_downloads` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	if(isset($actionsDownloadsColumns['uploadDate'])) {
		$db->query("ALTER TABLE `actions_downloads` ADD `timestamp` INT NOT NULL DEFAULT '0' AFTER `uploadDate`;
			UPDATE `actions_downloads` SET timestamp = UNIX_TIMESTAMP(uploadDate);
			ALTER TABLE `actions_downloads` DROP `uploadDate`;
			
			ALTER TABLE `actions_downloads` ADD `IP_temp` varchar(255) NOT NULL DEFAULT '' AFTER `ip`;
			UPDATE `actions_downloads` SET IP_temp = INET6_NTOA(ip);
			ALTER TABLE `actions_downloads` DROP `ip`;
			ALTER TABLE `actions_downloads` CHANGE `IP_temp` `IP` varchar(255) NOT NULL DEFAULT '';");
	}
	
	// Migrate table "actions_likes"
	$columnsToAdd = [
		['isLike', 'tinyint(1)', '0', 'type'],
		['accountID', 'varchar(255)', '', 'IP']
	];
	
	$actionsLikesColumns = getTableColumns('actions_likes');
	if(!$actionsLikesColumns) exit(sprintf($errorNoRequiredColumn, 'actions_likes'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($actionsLikesColumns[$column[0]])) {
			if($actionsLikesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `actions_likes` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `actions_likes` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	if(isset($actionsLikesColumns['uploadDate'])) {
		$db->query("ALTER TABLE `actions_likes` ADD `timestamp` INT NOT NULL DEFAULT '0' AFTER `uploadDate`;
			UPDATE `actions_likes` SET timestamp = UNIX_TIMESTAMP(uploadDate);
			ALTER TABLE `actions_likes` DROP `uploadDate`;
			
			ALTER TABLE `actions_likes` ADD `IP_temp` varchar(255) NOT NULL DEFAULT '' AFTER `ip`;
			UPDATE `actions_likes` SET IP_temp = INET6_NTOA(ip);
			ALTER TABLE `actions_likes` DROP `ip`;
			ALTER TABLE `actions_likes` CHANGE `IP_temp` `IP` varchar(255) NOT NULL DEFAULT '';");
	}
	
	// Migrate table "automod"
	$columnsToAdd = [
		['resolved', 'tinyint(1)', '0', 'timestamp']
	];
	
	$automodColumns = getTableColumns('automod');
	if(!$automodColumns) {
		$db->query("CREATE TABLE `automod` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`type` int(11) NOT NULL DEFAULT 0,
			`value1` varchar(255) NOT NULL DEFAULT '',
			`value2` varchar(255) NOT NULL DEFAULT '',
			`value3` varchar(255) NOT NULL DEFAULT '',
			`value4` varchar(255) NOT NULL DEFAULT '',
			`value5` varchar(255) NOT NULL DEFAULT '',
			`value6` varchar(255) NOT NULL DEFAULT '',
			`timestamp` int(11) NOT NULL DEFAULT 0,
			`resolved` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`ID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		
		$automodColumns = getTableColumns('automod');
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($automodColumns[$column[0]])) {
			if($automodColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `automod` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `automod` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "bans"
	$columnsToAdd = [
		['modReason', 'varchar(2048)', '', 'reason'],
		['isActive', 'tinyint(1)', '1', 'expires']
	];
	
	$bansColumns = getTableColumns('bans');
	if(!$bansColumns) {
		$db->query("CREATE TABLE `bans` (
			 `banID` int(11) NOT NULL AUTO_INCREMENT,
			 `modID` varchar(255) NOT NULL DEFAULT '',
			 `person` varchar(50) NOT NULL DEFAULT '',
			 `reason` varchar(2048) NOT NULL DEFAULT '',
			 `modReason` varchar(2048) NOT NULL DEFAULT '',
			 `banType` int(11) NOT NULL DEFAULT 0,
			 `personType` int(11) NOT NULL DEFAULT 0,
			 `expires` int(11) NOT NULL DEFAULT 0,
			 `isActive` tinyint(1) NOT NULL DEFAULT 1,
			 `timestamp` int(11) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`banID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			
			$bansColumns = getTableColumns('bans');
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($bansColumns[$column[0]])) {
			if($bansColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `bans` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `bans` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'modReason':
				$db->query("UPDATE bans SET modReason = reason, reason = ''");
				break;
		}
	}
	
	// Migrate table "clanrequests"
	$clanrequestsColumns = getTableColumns('clanrequests');
	if(!$clanrequestsColumns) {
		$db->query("CREATE TABLE `clanrequests` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`accountID` int(11) NOT NULL DEFAULT '0',
			`clanID` int(11) NOT NULL DEFAULT '0',
			`timestamp` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`ID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		
		$clanrequestsColumns = getTableColumns('clanrequests');
	}
	
	// Migrate table "clancomments"
	$clancommentsColumns = getTableColumns('clancomments');
	if(!$clancommentsColumns) {
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
		
		$clancommentsColumns = getTableColumns('clancomments');
	}
	
	// Migrate table "clans"
	$columnsToChange = [ // old column name, new column name, column type, default value
		['ID', 'clanID', 'int(11)', '0'],
		['clan', 'clanName', 'varchar(255)', ''],
		['desc', 'clanDesc', 'varchar(2048)', ''],
		['color', 'clanColor', 'varchar(6)', 'FFFFFF'],
		['tag', 'clanTag', 'varchar(15)', '']
	];
	$columnsToAdd = [
		['clanTag', 'varchar(15)', '', 'clanName'],
		['clanMembers', 'varchar(2048)', '', 'clanOwner'],
		['clanRank', 'int(11)', '0', 'clanColor'],
		['isClosed', 'tinyint(1)', '0', 'clanRank']
	];
	
	$clansColumns = getTableColumns('clans');
	if(!$clansColumns) {
		$db->query("CREATE TABLE `clans` (
			`clanID` int(11) NOT NULL AUTO_INCREMENT,
			`clanName` varchar(255) NOT NULL DEFAULT '',
			`clanTag` varchar(15) NOT NULL DEFAULT '',
			`clanDesc` varchar(2048) NOT NULL DEFAULT '',
			`clanOwner` int(11) NOT NULL DEFAULT '0',
			`clanMembers` varchar(2048) NOT NULL DEFAULT '',
			`clanColor` varchar(6) NOT NULL DEFAULT 'FFFFFF',
			`clanRank` int(11) NOT NULL DEFAULT '0',
			`isClosed` tinyint(1) NOT NULL DEFAULT '0',
			`creationDate` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`clanID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			
			$clansColumns = getTableColumns('clans');
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($clansColumns[$column[0]])) continue;
		
		$clansColumns[$column[1]] = $clansColumns[$column[0]];
		unset($clansColumns[$column[0]]);
		
		$db->query("ALTER TABLE `clans` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($clansColumns[$column[0]])) {
			if($clansColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `clans` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `clans` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'clanMembers':
				$postMigrationCommands[] = ['clansClanMembers'];
				
				break;
			case 'clanRank':
				$postMigrationCommands[] = ['clansClanRank'];
				
				break;
		}
	}
	
	// Migrate table "comments"
	$columnsToDelete = ['userName', 'secret'];
	$columnsToAdd = [
		['dislikes', 'int(11)', '0', 'likes'],
		['isSpam', 'tinyint(1)', '0', 'dislikes'],
		['creatorRating', 'tinyint(1)', '0', 'isSpam']
	];
	
	$commentsColumns = getTableColumns('comments');
	if(!$commentsColumns) exit(sprintf($errorNoRequiredColumn, 'comments'));
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($commentsColumns[$column])) continue;
		
		unset($commentsColumns[$column]);
		
		$db->query("ALTER TABLE `comments` DROP `".$column."`");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($commentsColumns[$column[0]])) {
			if($commentsColumns[$column[0]] == $column[1]) continue;
		
			$commentsColumns[$column[1]] = $commentsColumns[$column[0]];
			unset($commentsColumns[$column[0]]);
			
			$db->query("ALTER TABLE `comments` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `comments` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'dislikes':
				$db->query("UPDATE comments SET dislikes = likes * -1, likes = 0 WHERE likes < 0");
				break;
			case 'creatorRating':
				$db->query("UPDATE comments JOIN (
						SELECT commentID, IF(actions_likes.isLike = 1, 1, -1) AS creatorRating FROM comments
						INNER JOIN levels ON comments.levelID = levels.levelID
						INNER JOIN users ON levels.extID = users.extID
						INNER JOIN actions_likes ON actions_likes.type = 2 AND actions_likes.itemID = comments.commentID AND (levels.extID = actions_likes.accountID OR users.IP = actions_likes.IP)
					) creatorRatings
					SET comments.creatorRating = creatorRatings.creatorRating
					WHERE comments.commentID = creatorRatings.commentID");
				break;
		}
	}
	
	// Migrate table "dailyfeatures"
	$columnsToAdd = [
		['webhookSent', 'tinyint(1)', '0', 'type']
	];
	
	$dailyfeaturesColumns = getTableColumns('dailyfeatures');
	if(!$dailyfeaturesColumns) exit(sprintf($errorNoRequiredColumn, 'dailyfeatures'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($dailyfeaturesColumns[$column[0]])) {
			if($dailyfeaturesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `dailyfeatures` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `dailyfeatures` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "events"
	$columnsToAdd = [
		['rewards', 'varchar(2048)', '', 'duration'],
		['webhookSent', 'tinyint(1)', '0', 'rewards']
	];
	
	$eventsColumns = getTableColumns('events');
	if(!$eventsColumns) {
		$db->query("CREATE TABLE `events` (
			`feaID` int(11) NOT NULL AUTO_INCREMENT,
			`levelID` int(11) NOT NULL,
			`timestamp` int(11) NOT NULL,
			`duration` int(11) NOT NULL,
			`rewards` varchar(2048) NOT NULL DEFAULT '',
			`webhookSent` tinyint(1) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`feaID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		
		$eventsColumns = getTableColumns('events');
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($eventsColumns[$column[0]])) {
			if($eventsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `events` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `events` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'rewards':
				if(isset($eventsColumns["type"])) {
					$db->query("UPDATE events SET rewards = CONCAT(type,  \",\", reward)");
					$db->query("ALTER TABLE `events` DROP `type`");
					$db->query("ALTER TABLE `events` DROP `reward`");
				}
				break;
		}
	}
	
	// Migrate table "favsongs"
	$favsongsColumns = getTableColumns('favsongs');
	if(!$favsongsColumns) {
		$db->query("CREATE TABLE `favsongs` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`songID` int(11) NOT NULL DEFAULT '0',
			`accountID` int(11) NOT NULL DEFAULT '0',
			`timestamp` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`ID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		
		$favsongsColumns = getTableColumns('favsongs');
	}
	
	// Migrate table "gauntlets"
	$columnsToAdd = [
		['timestamp', 'int(11)', '0', 'level5']
	];
	
	$gauntletsColumns = getTableColumns('gauntlets');
	if(!$gauntletsColumns) exit(sprintf($errorNoRequiredColumn, 'gauntlets'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($gauntletsColumns[$column[0]])) {
			if($gauntletsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `gauntlets` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `gauntlets` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "levels"
	$columnsToDelete = ['secret', 'userName', 'starHall'];
	$columnsToChange = [
		['hostname', 'IP', 'varchar(255)', '']
	];
	$columnsToAdd = [
		['dislikes', 'int(11)', '0', 'likes'],
		['difficultyDenominator', 'int(11)', '0', 'starDifficulty'],
		['originalServer', 'varchar(255)', '', 'originalReup'],
		['updateLocked', 'varchar(255)', '', 'settingsString'],
		['commentLocked', 'varchar(255)', '', 'updateLocked'],
		['hasMagicString', 'tinyint(1)', '0', 'levelInfo'],
	];
	
	$levelsColumns = getTableColumns('levels');
	if(!$levelsColumns) exit(sprintf($errorNoRequiredColumn, 'levels'));
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($levelsColumns[$column])) continue;
		
		unset($levelsColumns[$column]);
		
		$db->query("ALTER TABLE `levels` DROP `".$column."`");
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($levelsColumns[$column[0]])) continue;
		
		$levelsColumns[$column[1]] = $levelsColumns[$column[0]];
		unset($levelsColumns[$column[0]]);
		
		$db->query("ALTER TABLE `levels` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($levelsColumns[$column[0]])) {
			if($levelsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `levels` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `levels` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'dislikes':
				$db->query("UPDATE levels SET dislikes = likes * -1, likes = 0 WHERE likes < 0");
				break;
		}
	}
	
	// Migrate table "lists"
	$columnsToAdd = [
		['dislikes', 'int(11)', '0', 'likes'],
		['rateDate', 'int(11)', '0', 'updateDate'],
		['updateLocked', 'tinyint(1)', '0', 'unlisted'],
		['commentLocked', 'tinyint(1)', '0', 'updateLocked'],
	];
	
	$listsColumns = getTableColumns('lists');
	if(!$listsColumns) exit(sprintf($errorNoRequiredColumn, 'lists'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($listsColumns[$column[0]])) {
			if($listsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `lists` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `lists` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'dislikes':
				$db->query("UPDATE lists SET dislikes = likes * -1, likes = 0 WHERE likes < 0");
				break;
		}
	}
	
	// Migrate table "mappacks"
	$columnsToAdd = [
		['timestamp', 'int(11)', '0', 'colors2']
	];
	
	$mappacksColumns = getTableColumns('mappacks');
	if(!$mappacksColumns) exit(sprintf($errorNoRequiredColumn, 'mappacks'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($mappacksColumns[$column[0]])) {
			if($mappacksColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `mappacks` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `mappacks` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "messages"
	$columnsToDelete = ['secret', 'userName', 'userID'];
	$columnsToChange = [
		['accID', 'accountID', 'varchar(255)', '']
	];
	$columnsToAdd = [
		['toAccountID', 'varchar(255)', '', 'accountID'],
		['readTime', 'int(11)', '0', 'isNew']
	];
	
	$messagesColumns = getTableColumns('messages');
	if(!$messagesColumns) exit(sprintf($errorNoRequiredColumn, 'messages'));
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($messagesColumns[$column])) continue;
		
		unset($messagesColumns[$column]);
		
		$db->query("ALTER TABLE `messages` DROP `".$column."`");
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($messagesColumns[$column[0]])) continue;
		
		$messagesColumns[$column[1]] = $messagesColumns[$column[0]];
		unset($messagesColumns[$column[0]]);
		
		$db->query("ALTER TABLE `messages` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($messagesColumns[$column[0]])) {
			if($messagesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `messages` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `messages` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "modactions"
	$columnsToAdd = [
		['value3', 'varchar(255)', '', 'value2'],
		['value5', 'varchar(255)', '', 'value4'],
		['value6', 'varchar(255)', '', 'value5'],
		['value8', 'varchar(2048)', '', 'value7'],
		['account', 'varchar(255)', '', 'value8'],
		['IP', 'varchar(255)', '', 'account']
	];
	
	$modactionsColumns = getTableColumns('modactions');
	if(!$modactionsColumns) exit(sprintf($errorNoRequiredColumn, 'modactions'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($modactionsColumns[$column[0]])) {
			if($modactionsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `modactions` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `modactions` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "notifies"
	$columnsToChange = [
		['extID', 'accountID', 'varchar(255)', '0'],
		['action', 'type', 'int(11)', '0'],
		['moderator', 'modID', 'int(11)', '0'],
		['checked', 'isChecked', 'tinyint(1)', '0']
	];
	$columnsToAdd = [
		['value1', 'varchar(2048)', '', 'type'],
		['value2', 'varchar(255)', '', 'value1'],
		['value3', 'varchar(255)', '', 'value2'],
		['value4', 'varchar(255)', '', 'value3'],
		['value5', 'varchar(255)', '', 'value4'],
		['value6', 'varchar(255)', '', 'value5']
	];
	
	$notifiesColumns = getTableColumns('notifies');
	if(!$notifiesColumns) {
		$db->query("CREATE TABLE `notifies` (
			`notifyID` int(11) NOT NULL AUTO_INCREMENT,
			`accountID` varchar(255) NOT NULL DEFAULT '0',
			`type` int(11) NOT NULL DEFAULT '0',
			`value1` varchar(2048) NOT NULL DEFAULT '',
			`value2` varchar(255) NOT NULL DEFAULT '',
			`value3` varchar(255) NOT NULL DEFAULT '',
			`value4` varchar(255) NOT NULL DEFAULT '',
			`value5` varchar(255) NOT NULL DEFAULT '',
			`value6` varchar(255) NOT NULL DEFAULT '',
			`modID` int(11) NOT NULL DEFAULT '0',
			`timestamp` int(11) NOT NULL DEFAULT '0',
			`isChecked` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`notifyID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		
		$notifiesColumns = getTableColumns('notifies');
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($notifiesColumns[$column[0]])) continue;
		
		$notifiesColumns[$column[1]] = $notifiesColumns[$column[0]];
		unset($notifiesColumns[$column[0]]);
		
		$db->query("ALTER TABLE `notifies` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($notifiesColumns[$column[0]])) {
			if($notifiesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `notifies` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `notifies` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "platscores"
	$columnsToAdd = [
		['attempts', 'int(11)', '0', 'points'],
		['clicks', 'int(11)', '0', 'attempts'],
		['progresses', 'varchar(255)', '', 'clicks'],
		['coins', 'int(11)', '0', 'progresses'],
		['dailyID', 'int(11)', '0', 'coins']
	];
	
	$platscoresColumns = getTableColumns('platscores');
	if(!$platscoresColumns) exit(sprintf($errorNoRequiredColumn, 'platscores'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($platscoresColumns[$column[0]])) {
			if($platscoresColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `platscores` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `platscores` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "replies"
	$repliesColumns = getTableColumns('replies');
	if(!$repliesColumns) {
		$db->query("CREATE TABLE `replies` (
			`replyID` int(11) NOT NULL AUTO_INCREMENT,
			`commentID` int(11) NOT NULL,
			`accountID` int(11) NOT NULL,
			`body` varchar(255) NOT NULL,
			`timestamp` int(11) NOT NULL,
			PRIMARY KEY (`replyID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		
		$repliesColumns = getTableColumns('replies');
	}
	
	// Migrate table "reports"
	$columnsToChange = [
		['hostname', 'IP', 'varchar(255)', '']
	];
	
	$reportsColumns = getTableColumns('reports');
	if(!$reportsColumns) exit(sprintf($errorNoRequiredColumn, 'reports'));
	
	foreach($columnsToChange AS &$column) {
		if(!isset($reportsColumns[$column[0]])) continue;
		
		$reportsColumns[$column[1]] = $reportsColumns[$column[0]];
		unset($reportsColumns[$column[0]]);
		
		$db->query("ALTER TABLE `reports` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	// Migrate table "roleassign"
	$columnsToChange = [
		['accountID', 'person', 'varchar(255)', '']
	];
	$columnsToAdd = [
		['personType', 'int(11)', '0', 'person']
	];
	
	$roleassignColumns = getTableColumns('roleassign');
	if(!$roleassignColumns) exit(sprintf($errorNoRequiredColumn, 'roleassign'));
	
	foreach($columnsToChange AS &$column) {
		if(!isset($roleassignColumns[$column[0]])) continue;
		
		$roleassignColumns[$column[1]] = $roleassignColumns[$column[0]];
		unset($roleassignColumns[$column[0]]);
		
		$db->query("ALTER TABLE `roleassign` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($roleassignColumns[$column[0]])) {
			if($roleassignColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `roleassign` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `roleassign` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "roles"
	$columnsToDelete = ['commandRenameOwn', 'commandPassOwn', 'commandDescriptionOwn', 'commandPublicOwn', 'commandUnlistOwn', 'commandUnlistAll', 'commandSharecpOwn', 'commandSongOwn', 'commandLockCommentsOwn', 'commandUnepic', 'commandSuggest', 'actionRateDemon', 'actionRateStars', 'actionRequestMod', 'toolLeaderboardsban', 'toolQuestsCreate', 'toolModactions', 'toolSuggestlist', 'toolPackcreate', 'modipCategory', 'demonlistAdd', 'demonlistApprove', 'profilecommandDiscord'];
	$columnsToChange = [
		['commandRate', 'gameRateLevel', 'tinyint(1)', '0'],
		['commandFeature', 'gameSetFeatured', 'tinyint(1)', '0'],
		['commandEpic', 'gameSetEpic', 'tinyint(1)', '0'],
		['commandVerifycoins', 'gameVerifyCoins', 'tinyint(1)', '0'],
		['commandDaily', 'gameSetDaily', 'tinyint(1)', '0'],
		['commandWeekly', 'gameSetWeekly', 'tinyint(1)', '0'],
		['commandEvent', 'gameSetEvent', 'tinyint(1)', '0'],
		['actionSuggestRating', 'gameSuggestLevel', 'tinyint(1)', '0'],
		['commandDelete', 'gameDeleteLevel', 'tinyint(1)', '0'],
		['commandSetacc', 'gameMoveLevel', 'tinyint(1)', '0'],
		['commandRenameAll', 'commandRename', 'tinyint(1)', '0'],
		['commandRename', 'gameRenameLevel', 'tinyint(1)', '0'],
		['commandPassAll', 'commandPass', 'tinyint(1)', '0'],
		['commandPass', 'gameSetPassword', 'tinyint(1)', '0'],
		['commandDescriptionAll', 'commandDescription', 'tinyint(1)', '0'],
		['commandDescription', 'gameSetDescription', 'tinyint(1)', '0'],
		['commandPublicAll', 'commandPublic', 'tinyint(1)', '0'],
		['commandPublic', 'gameSetLevelPrivacy', 'tinyint(1)', '0'],
		['commandSharecpAll', 'commandSharecp', 'tinyint(1)', '0'],
		['commandSharecp', 'gameShareCreatorPoints', 'tinyint(1)', '0'],
		['commandSongAll', 'commandSong', 'tinyint(1)', '0'],
		['commandSong', 'gameSetLevelSong', 'tinyint(1)', '0'],
		['commandLockCommentsAll', 'commandLockComments', 'tinyint(1)', '0'],
		['commandLockComments', 'gameLockLevelComments', 'tinyint(1)', '0'],
		['commandLockUpdating', 'gameLockLevelUpdating', 'tinyint(1)', '0'],
		['actionDeleteComment', 'gameDeleteComments', 'tinyint(1)', '0'],
		['actionRateDifficulty', 'gameSetDifficulty', 'tinyint(1)', '0'],
		['dashboardGauntletCreate', 'dashboardManageGauntlets', 'tinyint(1)', '0'],
		['dashboardModTools', 'dashboardModeratorTools', 'tinyint(1)', '0'],
		['dashboardLevelPackCreate', 'dashboardManageMapPacks', 'tinyint(1)', '0'],
		['dashboardAddMod', 'dashboardSetAccountRoles', 'tinyint(1)', '0'],
		['dashboardVaultCodesManage', 'dashboardManageVaultCodes', 'tinyint(1)', '0']
	];
	$columnsToAdd = [
		['commentsExtraText', 'varchar(255)', '', 'roleName'],
		['gameLockLevelComments', 'varchar(255)', '', 'gameSetLevelSong'],
		['gameLockLevelUpdating', 'varchar(255)', '', 'gameLockLevelComments'],
		['gameSetListLevels', 'varchar(255)', '', 'gameLockLevelUpdating'],
		['dashboardManageMapPacks', 'tinyint(1)', '0', 'dashboardModeratorTools'],
		['dashboardManageSongs', 'tinyint(1)', '0', 'dashboardManageMapPacks'],
		['dashboardManageAccounts', 'tinyint(1)', '0', 'dashboardManageSongs'],
		['dashboardDeleteLeaderboards', 'tinyint(1)', '0', 'dashboardManageAccounts'],
		['dashboardManageLevels', 'tinyint(1)', '0', 'dashboardDeleteLeaderboards'],
		['dashboardManageAutomod', 'tinyint(1)', '0', 'dashboardManageLevels'],
		['dashboardManageClans', 'tinyint(1)', '0', 'dashboardManageLevels'],
		['dashboardManageRoles', 'tinyint(1)', '0', 'dashboardManageAutomod'],
		['dashboardManageVaultCodes', 'tinyint(1)', '0', 'dashboardManageRoles'],
		['dashboardSetAccountRoles', 'tinyint(1)', '0', 'dashboardManageVaultCodes'],
		['dashboardBypassMaintenance', 'tinyint(1)', '0', 'dashboardSetAccountRoles']
	];
	
	$rolesColumns = getTableColumns('roles');
	if(!$rolesColumns) exit(sprintf($errorNoRequiredColumn, 'roles'));
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($rolesColumns[$column])) continue;
		
		unset($rolesColumns[$column]);
		
		$db->query("ALTER TABLE `roles` DROP `".$column."`");
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($rolesColumns[$column[0]])) continue;
		
		$rolesColumns[$column[1]] = $rolesColumns[$column[0]];
		unset($rolesColumns[$column[0]]);
		
		$db->query("ALTER TABLE `roles` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($rolesColumns[$column[0]])) {
			if($rolesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `roles` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `roles` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "sfxs"
	$columnsToDelete = ['token'];
	
	$sfxsColumns = getTableColumns('sfxs');
	if(!$sfxsColumns) {
		$db->query("CREATE TABLE `sfxs` (
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
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		
		$sfxsColumns = getTableColumns('sfxs');
	}
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($sfxsColumns[$column])) continue;
		
		unset($sfxsColumns[$column]);
		
		$db->query("ALTER TABLE `sfxs` DROP `".$column."`");
	}
	
	// Migrate table "songs"
	$columnsToAdd = [
		['duration', 'int(11)', '0', 'size'],
		['isDisabled', 'tinyint(1)', '0', 'isDisabled'],
		['levelsCount', 'int(11)', '0', 'isDisabled'],
		['favouritesCount', 'int(11)', '0', 'levelsCount'],
		['reuploadTime', 'int(11)', '0', 'favouritesCount'],
		['reuploadID', 'int(11)', '0', 'reuploadTime']
	];
	
	$songsColumns = getTableColumns('songs');
	if(!$songsColumns) exit(sprintf($errorNoRequiredColumn, 'songs'));
	
	foreach($columnsToAdd AS &$column) {
		if(isset($songsColumns[$column[0]])) {
			if($songsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `songs` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `songs` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "udids"
	$columnsToAdd = [
		['udids', 'varchar(2048)', '', 'userID']
	];
	
	$udidsColumns = getTableColumns('udids');
	if(!$udidsColumns) {
		$db->query("CREATE TABLE `udids` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`userID` int(11) NOT NULL DEFAULT 0,
				`udids` varchar(2048) NOT NULL DEFAULT '',
				PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		
		$udidsColumns = getTableColumns('udids');
		
		$extIDs = $db->prepare("SELECT userID, extID FROM users WHERE extID NOT REGEXP '^[0-9]+$' AND extID != ''");
		$extIDs->execute();
		$extIDs = $extIDs->fetchAll();
		
		foreach($extIDs AS &$udid) Security::hashUDID($udid['userID'], $udid['extID']);
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($udidsColumns[$column[0]])) {
			if($udidsColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `udids` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `udids` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "users"
	$columnsToDelete = ['dlPoints', 'isBanned', 'isCreatorBanned', 'isUploadBanned', 'isCommentBanned', 'banReason', 'secret'];
	$columnsToChange = [
		['clan', 'clanID', 'int(11)', '0']
	];
	$columnsToAdd = [
		['clanID', 'int(11)', '0', 'userName'],
		['joinedAt', 'int(11)', '0', 'clanID'],
	];
	
	$usersColumns = getTableColumns('users');
	if(!$usersColumns) exit(sprintf($errorNoRequiredColumn, 'users'));
	
	$usersQueryArray = [];
	if(isset($usersColumns['isBanned'])) $usersQueryArray[] = 'isBanned';
	if(isset($usersColumns['isCreatorBanned'])) $usersQueryArray[] = 'isCreatorBanned';
	if(isset($usersColumns['isUploadBanned'])) $usersQueryArray[] = 'isUploadBanned';
	if(isset($usersColumns['isCommentBanned'])) $usersQueryArray[] = 'isCommentBanned';
	
	$usersQueryWhereString = implode('> 0 OR ', $usersQueryArray);
	
	if(isset($usersColumns['banReason'])) $usersQueryArray[] = 'banReason';
	
	$usersQueryString = ', '.implode(', ', $usersQueryArray);
	
	if(!empty($usersQueryArray)) {
		$allBans = $db->prepare('SELECT extID, userID, IP'.$usersQueryString.' FROM users WHERE '.$usersQueryWhereString);
		$allBans->execute();
		$allBans = $allBans->fetchAll();
	}
	
	foreach($columnsToDelete AS &$column) {
		if(!isset($usersColumns[$column])) continue;
		
		switch($column) {
			case 'isBanned':
				foreach($allBans AS &$ban) {
					if(!$ban['banReason'] || $ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
					
					$banPerson = [
						'accountID' => $ban['extID'],
						'userID' => $ban['userID'],
						'IP' => $ban['IP'],
					];
					
					if($ban['isBanned'] > 0) Library::banPerson(0, $banPerson, '', Ban::Leaderboards, Person::UserID, 2147483647, $ban['banReason']);
				}
				
				break;
			case 'isCreatorBanned':
				foreach($allBans AS &$ban) {
					if(!$ban['banReason'] || $ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
					
					$banPerson = [
						'accountID' => $ban['extID'],
						'userID' => $ban['userID'],
						'IP' => $ban['IP'],
					];
					
					if($ban['isCreatorBanned'] > 0) Library::banPerson(0, $banPerson, '', Ban::Creators, Person::UserID, 2147483647, $ban['banReason']);
				}
				
				break;
			case 'isUploadBanned':
				foreach($allBans AS &$ban) {
					if(!$ban['banReason'] || $ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
					
					$banPerson = [
						'accountID' => $ban['extID'],
						'userID' => $ban['userID'],
						'IP' => $ban['IP'],
					];
					
					if($ban['isUploadBanned'] > 0) Library::banPerson(0, $banPerson, '', Ban::UploadingLevels, Person::UserID, 2147483647, $ban['banReason']);
				}
				
				break;
			case 'isCommentBanned':
				foreach($allBans AS &$ban) {
					if(!$ban['banReason'] || $ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = ''; 
					
					$banPerson = [
						'accountID' => $ban['extID'],
						'userID' => $ban['userID'],
						'IP' => $ban['IP'],
					];
					
					if($ban['isCommentBanned'] > 0) Library::banPerson(0, $banPerson, '', Ban::Commenting, Person::UserID, 2147483647, $ban['banReason']);
				}
				
				break;
		}
		
		unset($usersColumns[$column]);
		
		$db->query("ALTER TABLE `users` DROP `".$column."`");
	}
	
	foreach($columnsToChange AS &$column) {
		if(!isset($usersColumns[$column[0]])) continue;
		
		$usersColumns[$column[1]] = $usersColumns[$column[0]];
		unset($usersColumns[$column[0]]);
		
		$db->query("ALTER TABLE `users` CHANGE `".$column[0]."` `".$column[1]."` ".$column[2]." NOT NULL DEFAULT '".$column[3]."'");
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($usersColumns[$column[0]])) {
			if($usersColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `users` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `users` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
	}
	
	// Migrate table "vaultcodes"
	$columnsToAdd = [
		['rewards', 'varchar(2048)', '', 'duration']
	];
	
	$vaultcodesColumns = getTableColumns('vaultcodes');
	if(!$vaultcodesColumns) {
		$db->query("CREATE TABLE `vaultcodes` (
			`rewardID` int(11) NOT NULL AUTO_INCREMENT,
			`code` varchar(255) NOT NULL DEFAULT '',
			`rewards` varchar(2048) NOT NULL DEFAULT '',
			`duration` int(11) NOT NULL DEFAULT 0,
			`uses` int(11) NOT NULL DEFAULT -1,
			`timestamp` int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (`rewardID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		
		$vaultcodesColumns = getTableColumns('vaultcodes');
	}
	
	foreach($columnsToAdd AS &$column) {
		if(isset($vaultcodesColumns[$column[0]])) {
			if($vaultcodesColumns[$column[0]] == $column[1]) continue;
			
			$db->query("ALTER TABLE `vaultcodes` CHANGE `".$column[0]."` `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."'");
			
			continue;
		}
		
		$db->query("ALTER TABLE `vaultcodes` ADD `".$column[0]."` ".$column[1]." NOT NULL DEFAULT '".$column[2]."' AFTER `".$column[3]."`");
		
		switch($column[0]) {
			case 'rewards':
				if(isset($vaultcodesColumns["type"])) {
					$db->query("UPDATE vaultcodes SET rewards = CONCAT(type,  \",\", reward)");
					$db->query("ALTER TABLE `vaultcodes` DROP `type`");
					$db->query("ALTER TABLE `vaultcodes` DROP `reward`");
				}
				break;
		}
	}
	
	foreach($postMigrationCommands AS &$migration) {
		switch($migration[0]) {
			case 'accountsRegisterIPActions':
				$getIPs = $db->prepare("SELECT IF(IP != '', account, value) AS account, IF(IP != '', IP, value2) AS IP FROM actions WHERE IF(IP != '', 1, type = 16) GROUP BY ip ORDER BY timestamp ASC, account ASC");
				$getIPs->execute();
				$getIPs = $getIPs->fetchAll();
				
				foreach($getIPs AS &$account) {
					$accountRegisterIPMigration[$account['IP']] = $account['account'];
				}
				break;
			case 'accountsRegisterIP':
				$accountRegisterIPMigration[$migration[1]] = $migration[2];
				
				break;
			case 'clansClanMembers':
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
				break;
			case 'clansClanRank':
				$cronPerson = [
					'accountID' => 0,
					'userID' => 0,
					'userName' => "Undefined",
					'IP' => $IP
				];
				
				Cron::updateClansRanks($cronPerson, false);
				break;
		}
	}
	
	if(!empty($accountRegisterIPMigration)) {
		foreach($accountRegisterIPMigration AS $migrationIP => $migrationAccountID) {
			$accountRegisterIPMigrationCaseString .= 'WHEN IP = "'.$migrationIP.'" THEN "'.$migrationAccountID.'"'.PHP_EOL;
			$accountRegisterIPMigrationCaseStringAccountID .= 'WHEN accountID = "'.$migrationAccountID.'" THEN "'.$migrationIP.'"'.PHP_EOL;
			$accountRegisterIPMigrationWhereString .= '"'.$migrationIP.'",';
			$accountRegisterIPMigrationWhereStringAccountID .= '"'.$accountID.'",';
		}
		
		$fillIP = $db->prepare("UPDATE accounts SET registerIP = (CASE
				".$accountRegisterIPMigrationCaseStringAccountID."
			END)
			WHERE accountID IN (".rtrim($accountRegisterIPMigrationWhereStringAccountID, ',').") AND registerIP != ''");
		$fillIP->execute();
		
		$fillActionsIP = $db->prepare("UPDATE actions_downloads SET accountID = (CASE
				".$accountRegisterIPMigrationCaseString."
			END)
			WHERE IP IN (".rtrim($accountRegisterIPMigrationWhereString, ',').") AND accountID = ''");
		$fillActionsIP->execute();
		
		$fillActionsIP = $db->prepare("UPDATE actions_likes SET accountID = (CASE
				".$accountRegisterIPMigrationCaseString."
			END)
			WHERE IP IN (".rtrim($accountRegisterIPMigrationWhereString, ',').") AND accountID = ''");
		$fillActionsIP->execute();
	}
	
	$lines = file(__DIR__.'/../../config/dashboard.php');
	$firstLine = $lines[2];
	$lines = array_slice($lines, 3);
	$lines = array_merge(array($firstLine, "\n"), $lines);
	
	$file = fopen(__DIR__.'/../../config/dashboard.php', 'w');
	if(!$file) exit("<h1>Failed opening file \"config/dashboard.php\"!</h1>
		<br>
		<p>Make sure this file exists and PHP has permissions to write to it.</p>");
	
	fwrite($file, "<?php".PHP_EOL);
	fwrite($file, "\$installed = true;");
	fwrite($file, implode('', $lines));
	fclose($file);
}
?>