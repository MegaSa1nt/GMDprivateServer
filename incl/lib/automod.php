<?php
class Automod {
	/*
		Automod::checkLevelsCount()
		
		This function checks levels upload count to see if there is too many levels uploaded in small time
		
		Return value:
			true — everything is normal, nothing to be scared of
			false — high levels amount detected! possible raid
	*/
	public static function checkLevelsCount() {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		$levelsCount = self::getLevelsCountPerTime();
		$levelsBefore = $levelsCount['before'];
		$levelsAfter = $levelsCount['after'];
		
		$levelsBeforeModified = ($levelsBefore * $levelsWarnCountModifier) + 4;
		$levelsBeforeModifiedDisable = ($levelsBefore * $levelsDisableCountModifier) + 4;

		if($levelsAfter > $levelsBeforeModified) {
			$isWarned = self::getLastAutomodAction(AutomodAction::LevelsSpamWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::LevelsSpamWarning, $levelsBefore, $levelsAfter, $levelsBeforeModified);
				
				//$gs->sendLevelsWarningWebhook($levelsBefore, $levelsAfter);
			}
			
			if($levelsSpamUploadDisable) {
				$isDisabled = self::getLastAutomodAction(AutomodAction::LevelUploadingDisable, true);
				
				if(!$isDisabled && $levelsAfter > $levelsBeforeModifiedDisable) self::changeLevelsAutomodState(0, true, time() + $levelsSpamUploadDisable);
			}
			
			return false;
		}

		return true;
	}
	
	/*
		self::logAutomodActions($type, $value1, $value2, $value3, $value4, $value5, $value6)
		
		This private function logs any automod actions
		
		$type — type of action (Number)
		$value1 — first value of action (Mixed)
		$value2 — second value of action (Mixed)
		$value3 — third value of action (Mixed)
		$value4 — fourth value of action (Mixed)
		$value5 — fifth value of action (Mixed)
		$value6 — sixth value of action (Mixed)
		
		Return value:
			ID of logged action
	*/
	private static function logAutomodActions($type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '') {
		require __DIR__."/connection.php";
		
		$insertAction = $db->prepare('INSERT INTO automod (type, value1, value2, value3, value4, value5, value6, timestamp) VALUES (:type, :value1, :value2, :value3, :value4, :value5, :value6, :timestamp)');
		$insertAction->execute([':type' => $type, ':value1' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':value5' => $value5, ':value6' => $value6, ':timestamp' => time()]);
		
		return $db->lastInsertId();
	}
	
	/*
		self::getLastAutomodAction($type, $limitTime)
		
		This private function gets last automod action from SQL
		
		$type — type of action (Number)
		
		$limitTime — should function return actions from limited time or all actions (Boolean)
			true — limit action search by $levelsCheckPeriod from config/security.php
			false — don't limit action search by time
			
		Return value:
			Array — array with action data
			false — nothing found
	*/
	private static function getLastAutomodAction($type, $limitTime = false) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		
		$getAction = $db->prepare('SELECT * FROM automod WHERE type = :type '.($limitTime ? 'AND timestamp > '.time().' - '.(int)$warningsPeriod : '').' ORDER BY timestamp DESC LIMIT 1');
		$getAction->execute([':type' => $type]);
		
		return $getAction->fetch();
	}
	
	/*
		Automod::getAutomodActions($types)
		
		This function returns all automod actions of $types
		
		$types — array of types you want to see, all public types if empty (Array)
		
		Return value:
			Array — array of actions
			false — nothing found
	*/
	public static function getAutomodActions($types = []) {
		require __DIR__."/connection.php";
		
		if(!is_array($types) || empty($types)) $types = self::getPublicActionTypes();
		
		$getActions = $db->prepare('SELECT * FROM automod WHERE type IN ('.implode(',', $types).') ORDER BY timestamp DESC');
		$getActions->execute();
		
		return $getActions->fetchAll();
	}
	
	/*
		Automod::changeAutomodAction($actionID, $isResolved, $value1, $value2, $value3, $value4, $value5, $value6)
		
		This function changes automod action values
		
		$actionID — ID of action you want to change (Number)
		
		$isResolved — is action resolved or not (Number)
			1 — action is resolved
			0 — action is not resolved
			
		$value1 — change action value 1 (Mixed)
		$value2 — change action value 2 (Mixed)
		$value3 — change action value 3 (Mixed)
		$value4 — change action value 4 (Mixed)
		$value5 — change action value 5 (Mixed)
		$value6 — change action value 6 (Mixed)
			If $value is false, doesn't change value
		
		Return value:
			true — action changed successfully
			false — something went wrong when changing value
	*/
	public static function changeAutomodAction($actionID, $isResolved, $value1 = false, $value2 = false, $value3 = false, $value4 = false, $value5 = false, $value6 = false) {
		require __DIR__."/connection.php";
		
		$getAction = $db->prepare('SELECT * FROM automod WHERE ID = :ID');
		$getAction->execute([':ID' => $actionID]);
		$getAction = $getAction->fetch();
		
		if(!$getAction) return false;
		
		$changeAction = $db->prepare('UPDATE automod SET value1 = :value1, value2 = :value2, value3 = :value3, value4 = :value4, value5 = :value5, value6 = :value6, resolved = :isResolved WHERE ID = :ID');
		return $changeAction->execute([':ID' => $actionID, ':isResolved' => $isResolved, ':value1' => ($value1 === false ? $getAction['value1'] : $value1), ':value2' => ($value2 === false ? $getAction['value2'] : $value2), ':value3' => ($value3 === false ? $getAction['value3'] : $value3), ':value4' => ($value4 === false ? $getAction['value4'] : $value4), ':value5' => ($value5 === false ? $getAction['value5'] : $value5), ':value6' => ($value6 === false ? $getAction['value6'] : $value6)]);
	}
	
	/*
		Automod::getAutomodActionByID($actionID)
		
		This function returns action values by action ID
		
		$actionID — action ID you want to find (Number)
		
		Return value:
			Array — array of action values
			false — nothing found
	*/
	public static function getAutomodActionByID($actionID) {
		require __DIR__."/connection.php";
		
		$getAction = $db->prepare('SELECT * FROM automod WHERE ID = :ID');
		$getAction->execute([':ID' => $actionID]);
		
		return $getAction->fetch();
	}
	
	/*
		Automod::isLevelsDisabled($disableType)
		
		This function checks if levels uploading is disabled by automod
		
		Return value:
			true — levels uploading is disabled
			false — levels uploading is enabled
	*/
	public static function isLevelsDisabled($disableType = 0) {
		$actionTypes = self::getLevelsDisableTypes();
		$isDisabled = self::getLastAutomodAction($actionTypes[$disableType]);
		
		if(!$isDisabled['resolved']) {
			$disableExpires = $isDisabled['value1'] ?? 0;
			
			if($disableExpires <= time()) {
				self::changeAutomodAction($isDisabled['ID'], 1);
				return false;
			}
			
			return true;
		}
		
		return false;
	}
	
	/*
		Automod::changeLevelsAutomodState($disableType, $isDisable, $expires)
		
		This function change levels automod state
		
		$disableType — type of levels disabling (Number)
		
		$isDisable — disabling or enabling (Boolean)
			true — disable state
			false — enable state
			
		$expires — when disabling will expire, required if disabling (Number)
		
		Return value: void
	*/
	public static function changeLevelsAutomodState($disableType, $isDisable, $expires = 0) {
		$actionTypes = self::getLevelsDisableTypes();
		$action = self::getLastAutomodAction($actionTypes[$disableType]);
		
		if(!$action) self::logAutomodActions($actionTypes[$disableType], $expires);
		elseif($isDisable) {
			if($action['resolved']) self::logAutomodActions($actionTypes[$disableType], $expires);
			else self::changeAutomodAction($action['ID'], 0, $expires);
		} else self::changeAutomodAction($action['ID'], 1, false, time());
	}
	
	/*
		self::getPublicActionTypes()
		
		This private function returns all types of public automod actions
		
		Return value:
			Array — array of public action types
	*/
	private static function getPublicActionTypes() {
		return [
			AutomodAction::LevelsSpamWarning,
			AutomodAction::AccountsSpamWarning,
			
			AutomodAction::CommentsSpammingWarning,
			AutomodAction::CommentsSpammerWarning,
			
			AutomodAction::AccountPostsSpammingWarning,
			AutomodAction::AccountPostsSpammerWarning,
			
			AutomodAction::PostRepliesSpammingWarning,
			AutomodAction::PostRepliesSpammerWarning,
			
			AutomodAction::ClanPostsSpammingWarning,
			AutomodAction::ClanPostsSpammerWarning
		];
	}
	
	/*
		Automod::getLevelsCountPerTime()
		
		This function returns levels count before and after
		
		Return value:
			Array — levels before and after
	*/
	public static function getLevelsCountPerTime() {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		$levelsPeriod = time() - ($levelsDaysCheckPeriod * 86400);
		$levelsCount = $db->prepare("SELECT count(*) FROM levels WHERE uploadDate >= :time AND uploadDate <= :time2 AND isDeleted = 0 ORDER BY uploadDate DESC");
		$levelsCount->execute([':time' => $levelsPeriod, ':time2' => time() - 86400]);
		$levelsBefore = $levelsCount->fetchColumn() / $levelsDaysCheckPeriod;
		
		$levelsCount = $db->prepare("SELECT count(*) FROM levels WHERE uploadDate >= :time AND isDeleted = 0 ORDER BY uploadDate DESC");
		$levelsCount->execute([':time' => time() - 86400]);
		$levelsAfter = $levelsCount->fetchColumn();
		
		return ['before' => $levelsBefore, 'after' => $levelsAfter];
	}
	
	/*
		self::getLevelsDisableTypes()
		
		This private function returns all level disables types
		
		Return value:
			Array — array of level disables types
	*/
	private static function getLevelsDisableTypes() {
		return [AutomodAction::LevelUploadingDisable, AutomodAction::LevelCommentingDisable, AutomodAction::LevelLeaderboardDisable];
	}
	
	/*
		Automod::getLevelsDisableStates()
		
		This function returns expire time of all level disables types
		
		Return value:
			Array — array of expire time of all level disables types
	*/
	public static function getLevelsDisableStates() {
		$levelsUploadingTime = $levelsCommentingTime = $levelsLeaderboardSubmitsTime = '';
		$disableTypes = self::getLevelsDisableTypes();
		
		$levelsUploadingAction = self::getLastAutomodAction($disableTypes[0]);
		
		if($levelsUploadingAction['value1'] <= time()) {
			self::changeAutomodAction($levelsUploadingAction['ID'], 1);
			$levelsUploadingAction['resolved'] = 1;
		}
		
		if(is_array($levelsUploadingAction) && !$levelsUploadingAction['resolved']) $levelsUploadingTime = date('Y-m-d\TH:i:s', $levelsUploadingAction['value1']);
		
		$levelsCommentingAction = self::getLastAutomodAction($disableTypes[1]);
		
		if($levelsCommentingAction['value1'] <= time()) {
			self::changeAutomodAction($levelsCommentingAction['ID'], 1);
			$levelsCommentingAction['resolved'] = 1;
		}
		
		if(is_array($levelsCommentingAction) && !$levelsCommentingAction['resolved']) $levelsCommentingTime = date('Y-m-d\TH:i:s', $levelsCommentingAction['value1']);
		
		$levelsLeaderboardSubmitsAction = self::getLastAutomodAction($disableTypes[2]);
		
		if($levelsLeaderboardSubmitsAction['value1'] <= time()) {
			self::changeAutomodAction($levelsLeaderboardSubmitsAction['ID'], 1);
			$levelsLeaderboardSubmitsAction['resolved'] = 1;
		}
		
		if(is_array($levelsLeaderboardSubmitsAction) && !$levelsLeaderboardSubmitsAction['resolved']) $levelsLeaderboardSubmitsTime = date('Y-m-d\TH:i:s', $levelsLeaderboardSubmitsAction['value1']);
		
		return [$levelsUploadingTime, $levelsCommentingTime, $levelsLeaderboardSubmitsTime];
	}
	
	/*
		Automod::getAccountsCountPerTime()
		
		This function returns accounts count before and after
		
		Return value:
			Array — accounts before and after
	*/
	public static function getAccountsCountPerTime() {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		
		$accountsPeriod = time() - ($accountsDaysCheckPeriod * 86400);
		$accountsCount = $db->prepare("SELECT count(*) FROM accounts WHERE registerDate >= :time AND registerDate <= :time2 ORDER BY registerDate DESC");
		$accountsCount->execute([':time' => $accountsPeriod, ':time2' => time() - 86400]);
		$accountsBefore = $accountsCount->fetchColumn() / $accountsDaysCheckPeriod;
		
		$accountsCount = $db->prepare("SELECT count(*) FROM accounts WHERE registerDate >= :time ORDER BY registerDate DESC");
		$accountsCount->execute([':time' => time() - 86400]);
		$accountsAfter = $accountsCount->fetchColumn();
		
		return ['before' => $accountsBefore, 'after' => $accountsAfter];
	}
	
	/*
		Automod::checkAccountsCount()
		
		This function checks accounts register count to see if there is too many accounts registered in small time
		
		Return value:
			true — everything is normal, nothing to be scared of
			false — high accounts amount detected! possible raid
	*/
	public static function checkAccountsCount() {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/mainLib.php";
		
		$accountsCount = self::getAccountsCountPerTime();
		$accountsBefore = $levelsCount['before'];
		$accountsAfter = $levelsCount['after'];
		
		$accountsBeforeModified = ($accountsBefore * $accountsWarnCountModifier) + 3;
		$accountsBeforeModifiedDisable = ($accountsBefore * $accountsDisableCountModifier) + 3;
		
		if($accountsAfter > $accountsBeforeModified) {
			$isWarned = self::getLastAutomodAction(AutomodAction::AccountsSpamWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::AccountsSpamWarning, $accountsBefore, $accountsAfter, $accountsBeforeModified);
				
				//$gs->sendAccountsWarningWebhook($accountsBefore, $accountsAfter);
			}
			
			if($accountsSpamUploadDisable) {
				$isDisabled = self::getLastAutomodAction(AutomodAction::AccountRegisteringDisable, true);
				
				if($isDisabled && $accountsAfter > $accountsBeforeModifiedDisable) self::changeAccountsAutomodState(0, true, time() + $accountsSpamUploadDisable);
			}
			
			return false;
		}
		
		return true;
	}
	
	/*
		Automod::getAutomodTypes()
		
		This function returns automod types (levels, accounts, etc) according to their action type
		
		Return value:
			Array — array with automod types
	*/
	public static function getAutomodTypes() {
		return [1 => 1, 5 => 2, 10 => 3, 11 => 4, 12 => 5, 13 => 6, 14 => 7, 15 => 8];
	}
	
	/*
		self::getAccountsDisableTypes()
		
		This private function returns all account disables types
		
		Return value:
			Array — array of account disables types
	*/
	private static function getAccountsDisableTypes() {
		return [
			AutomodAction::AccountRegisteringDisable,
			AutomodAction::AccountPostingDisable,
			AutomodAction::AccountUpdatingStatsDisable,
			AutomodAction::AccountMessagingDisable
		];
	}
	
	/*
		Automod::getAccountsDisableStates()
		
		This function returns expire time of all level disables types
		
		Return value:
			Array — array of expire time of all level disables types
	*/
	public static function getAccountsDisableStates() {
		$accountRegisteringTime = $accountPostingTime = $accountStatsUpdatingTime = $accountMessagingTime = '';
		$disableTypes = self::getAccountsDisableTypes();
		
		$accountRegisteringAction = self::getLastAutomodAction($disableTypes[0]);
		
		if($accountRegisteringAction['value1'] <= time()) {
			self::changeAutomodAction($accountRegisteringAction['ID'], 1);
			$accountRegisteringAction['resolved'] = 1;
		}
		
		if(is_array($accountRegisteringAction) && !$accountRegisteringAction['resolved']) $accountRegisteringTime = date('Y-m-d\TH:i:s', $accountRegisteringAction['value1']);
		
		$accountPostingAction = self::getLastAutomodAction($disableTypes[1]);
		
		if($accountPostingAction['value1'] <= time()) {
			self::changeAutomodAction($accountPostingAction['ID'], 1);
			$accountPostingAction['resolved'] = 1;
		}
		
		if(is_array($accountPostingAction) && !$accountPostingAction['resolved']) $accountPostingTime = date('Y-m-d\TH:i:s', $accountPostingAction['value1']);
		
		$accountStatsUpdatingAction = self::getLastAutomodAction($disableTypes[2]);
		
		if($accountStatsUpdatingAction['value1'] <= time()) {
			self::changeAutomodAction($accountStatsUpdatingAction['ID'], 1);
			$accountStatsUpdatingAction['resolved'] = 1;
		}
		
		if(is_array($accountStatsUpdatingAction) && !$accountStatsUpdatingAction['resolved']) $accountStatsUpdatingTime = date('Y-m-d\TH:i:s', $accountStatsUpdatingAction['value1']);
		
		$accountMessagingAction = self::getLastAutomodAction($disableTypes[3]);
		
		if($accountMessagingAction['value1'] <= time()) {
			self::changeAutomodAction($accountMessagingAction['ID'], 1);
			$accountMessagingAction['resolved'] = 1;
		}
		
		if(is_array($accountMessagingAction) && !$accountMessagingAction['resolved']) $accountMessagingTime = date('Y-m-d\TH:i:s', $accountMessagingAction['value1']);
		
		return [$accountRegisteringTime, $accountPostingTime, $accountStatsUpdatingTime, $accountMessagingTime];
	}
	
	/*
		Automod::changeAccountsAutomodState($disableType, $isDisable, $expires)
		
		This function change accounts automod state
		
		$disableType — type of accounts disabling (Number)
		
		$isDisable — disabling or enabling (Boolean)
			true — disable state
			false — enable state
			
		$expires — when disabling will expire, required if disabling (Number)
		
		Return value: void
	*/
	public static function changeAccountsAutomodState($disableType, $isDisable, $expires = 0) {
		$actionTypes = self::getAccountsDisableTypes();
		$action = self::getLastAutomodAction($actionTypes[$disableType]);
		
		if(!$action) self::logAutomodActions($actionTypes[$disableType], $expires);
		elseif($isDisable) {
			if($action['resolved']) self::logAutomodActions($actionTypes[$disableType], $expires);
			else self::changeAutomodAction($action['ID'], 0, $expires);
		} else self::changeAutomodAction($action['ID'], 1, false, time());
	}
	
	/*
		Automod::isAccountsDisabled($disableType)
		
		This function checks if accounts registering is disabled by automod
		
		Return value:
			true — accounts registering is disabled
			false — accounts registering is enabled
	*/
	public static function isAccountsDisabled($disableType = 0) {
		$actionTypes = self::getAccountsDisableTypes();
		$isDisabled = self::getLastAutomodAction($actionTypes[$disableType]);
		
		if(!$isDisabled['resolved']) {
			$disableExpires = $isDisabled['value1'] ?? 0;
			
			if($disableExpires <= time()) {
				self::changeAutomodAction($isDisabled['ID'], 1);
				return false;
			}
			
			return true;
		}
		
		return false;
	}
	
	/*
		self::check_comments_similarity($str1, $str2)
		
		This private function checks similarity of 2 strings
		
		$str1 — string 1 (String)
		$str2 — string 2 (String)
		
		Return value:
			Number — similarity of strings
			
		Taken from https://www.php.net/manual/ru/function.similar-text.php#118799
	*/
	private static function check_comments_similarity($str1, $str2) {
		$len1 = strlen($str1);
		$len2 = strlen($str2);
		$max = max($len1, $len2);
		$similarity = $i = $j = 0;
		while(($i < $len1) && isset($str2[$j])) {
			if($str1[$i] == $str2[$j]) {
				$similarity++;
				$i++;
				$j++;
			} elseif($len1 < $len2) {
				$len1++;
				$j++;
			} elseif($len1 > $len2) {
				$i++;
				$len1--;
			} else {
				$i++;
				$j++;
			}
		}
		return round($similarity / $max, 2);
	}
	
	/*
		Automod::similarity($str1, $str2)
		
		This function checks similarity of 2 strings 4 times with different algorithms and returns greatest value
		
		$str1 — string 1 (String)
		$str2 — string 2 (String)
		
		Return value:
			Number — similarity of strings
			
		https://gcs.skin/WTFIcons/checking_speed.png
	*/
	public static function similarity($str1, $str2) {
		$check1 = self::check_comments_similarity($str1, $str2);
		$check2 = self::check_comments_similarity($str2, $str1);
		
		similar_text($str1, $str2, $perc);
		$check3 = round($perc / 100, 2);
		
		similar_text($str2, $str1, $perc);
		$check4 = round($perc / 100, 2);
		
		$biggestOne = [$check1, $check2, $check3, $check4];
		
		rsort($biggestOne);
		
		return $biggestOne[0];
	}
	
	/*
		Automod::checkCommentsSpamming($userID)
		
		This function checks last comments for spamming
		
		$userID — user ID of latest comment author (Number)
		
		Return value:
			true — everything is good, no spamming
			false — spamming detected!
	*/
	public static function checkCommentsSpamming($userID) {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		
		$returnValue = true;
		
		$comments = $db->prepare('SELECT comment, userID FROM comments WHERE timestamp > :time ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		$similarCommentsAuthors = [];
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) {
				$similarCommentsAuthors[] = $comment['userID'];
				$similarCommentsCount++;
			}
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 5) {
			$isWarned = self::getLastAutomodAction(AutomodAction::CommentsSpammingWarning, true);
			
			if(!$isWarned) {
				$similarCommentsAuthors = array_unique($similarCommentsAuthors);
				self::logAutomodActions(AutomodAction::CommentsSpammingWarning, $similarCommentsCount, $similarity, $commentsCount, implode(', ', $similarCommentsAuthors));
				
				if($commentsSpamUploadDisable) self::changeLevelsAutomodState(1, true, time() + $commentsSpamUploadDisable);
				
				//$gs->sendCommentsSpammingWarningWebhook($similarCommentsCount, $similarCommentsAuthors);
			}
			
			$returnValue = false;
		}
		
		$comments = $db->prepare('SELECT comment FROM comments WHERE timestamp > :time AND userID = :userID ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod, ':userID' => $userID]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) $similarCommentsCount++;
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 3) {
			$isWarned = self::getLastAutomodAction(AutomodAction::CommentsSpammerWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::CommentsSpammerWarning, $similarCommentsCount, $similarity, $commentsCount, $userID);
				//$gs->sendCommentsSpammerWarningWebhook($similarCommentsCount, $userID);
			}
			
			$returnValue = false;
		}
		
		return $returnValue;
	}
	
	/*
		Automod::checkAccountPostsSpamming($userID)
		
		This function checks last account posts for spamming
		
		$userID — user ID of latest post author (Number)
		
		Return value:
			true — everything is good, no spamming
			false — spamming detected!
	*/
	public static function checkAccountPostsSpamming($userID) {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		
		$returnValue = true;
		$IP = IP::getIP();
		
		$banPerson = [
			'accountID' => 0,
			'userID' => $userID,
			'IP' => $IP,
		];
		
		$comments = $db->prepare('SELECT comment, userID FROM acccomments WHERE timestamp > :time ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		$similarCommentsAuthors = [];
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) {
				$similarCommentsAuthors[] = $comment['userID'];
				$similarCommentsCount++;
			}
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 4) {
			$isWarned = self::getLastAutomodAction(AutomodAction::AccountPostsSpammingWarning, true);
			
			if(!$isWarned) {
				$similarCommentsAuthors = array_unique($similarCommentsAuthors);
				self::logAutomodActions(AutomodAction::AccountPostsSpammingWarning, $similarCommentsCount, $similarity, $commentsCount, implode(', ', $similarCommentsAuthors));
				
				if($commentsSpamUploadDisable) self::changeAccountsAutomodState(1, true, time() + $commentsSpamUploadDisable);
				
				//$gs->sendAccountPostsSpammingWarningWebhook($similarCommentsCount, $similarCommentsAuthors);
			}
			
			$returnValue = false;
		}
		
		$comments = $db->prepare('SELECT comment FROM acccomments WHERE timestamp > :time AND userID = :userID ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod, ':userID' => $userID]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) $similarCommentsCount++;
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 4) {
			$isWarned = self::getLastAutomodAction(AutomodAction::AccountPostsSpammerWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::AccountPostsSpammerWarning, $similarCommentsCount, $similarity, $commentsCount, $userID);
				
				if($commentsSpamUploadDisable) Library::banPerson(0, $banPerson, "No spamming!", Ban::Commenting, Person::UserID, (time() + $commentsSpamUploadDisable), "Person tried to spam account posts. (".$similarity." > ".$commentsCount." / 3)");
				
				//$gs->sendAccountPostsSpammerWarningWebhook($similarCommentsCount, $userID);
			}
			
			$returnValue = false;
		}
		
		return $returnValue;
	}
	
	/*
		Automod::checkRepliesSpamming($accountID)
		
		This function checks last replies for spamming
		
		$accountID — account ID of latest reply author (Number)
		
		Return value:
			true — everything is good, no spamming
			false — spamming detected!
	*/
	public static function checkRepliesSpamming($accountID) {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		
		$returnValue = true;
		
		$comments = $db->prepare('SELECT body, accountID FROM replies WHERE timestamp > :time ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		$similarCommentsAuthors = [];
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(base64_decode($comment['body']));
			$comment2 = Escape::prepare_for_checking(base64_decode($comments[$x]['body']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) {
				$similarCommentsAuthors[] = $comment['accountID'];
				$similarCommentsCount++;
			}
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 4) {
			$isWarned = self::getLastAutomodAction(AutomodAction::PostRepliesSpammingWarning, true);
			
			if(!$isWarned) {
				$similarCommentsAuthors = array_unique($similarCommentsAuthors);
				self::logAutomodActions(AutomodAction::PostRepliesSpammingWarning, $similarCommentsCount, $similarity, $commentsCount, implode(', ', $similarCommentsAuthors));
				//$gs->sendRepliesSpammingWarningWebhook($similarCommentsCount, $similarCommentsAuthors);
			}
			
			$returnValue = false;
		}
		
		$comments = $db->prepare('SELECT body FROM replies WHERE timestamp > :time AND accountID = :accountID ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod, ':accountID' => $accountID]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(base64_decode($comment['body']));
			$comment2 = Escape::prepare_for_checking(base64_decode($comments[$x]['body']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) $similarCommentsCount++;
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 3) {
			$isWarned = self::getLastAutomodAction(AutomodAction::PostRepliesSpammerWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::PostRepliesSpammerWarning, $similarCommentsCount, $similarity, $commentsCount, $accountID);
				
				if($commentsSpamUploadDisable) Library::banPerson(0, $banPerson, "No spamming!", Ban::Commenting, Person::UserID, (time() + $commentsSpamUploadDisable), "Person tried to spam replies. (".$similarity." > ".$commentsCount." / 3)");
				
				//$gs->sendRepliesSpammerWarningWebhook($similarCommentsCount, $accountID);
			}
			
			$returnValue = false;
		}
		
		return $returnValue;
	}
	
	/*
		Automod::checkClanPostsSpamming($userID)
		
		This function checks last clan posts for spamming
		
		$userID — user ID of latest post author (Number)
		
		Return value:
			true — everything is good, no spamming
			false — spamming detected!
	*/
	public static function checkClanPostsSpamming($userID) {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		
		$returnValue = true;
		
		$comments = $db->prepare('SELECT comment, userID FROM clancomments WHERE timestamp > :time ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		$similarCommentsAuthors = [];
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) {
				$similarCommentsAuthors[] = $comment['userID'];
				$similarCommentsCount++;
			}
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 5) {
			$isWarned = self::getLastAutomodAction(AutomodAction::ClanPostsSpammingWarning, true);
			
			if(!$isWarned) {
				$similarCommentsAuthors = array_unique($similarCommentsAuthors);
				self::logAutomodActions(AutomodAction::ClanPostsSpammingWarning, $similarCommentsCount, $similarity, $commentsCount, implode(', ', $similarCommentsAuthors));
				
				if($commentsSpamUploadDisable) self::changeLevelsAutomodState(1, true, time() + $commentsSpamUploadDisable);
				
				//$gs->sendCommentsSpammingWarningWebhook($similarCommentsCount, $similarCommentsAuthors);
			}
			
			$returnValue = false;
		}
		
		$comments = $db->prepare('SELECT comment FROM clancomments WHERE timestamp > :time AND userID = :userID ORDER BY timestamp DESC');
		$comments->execute([':time' => time() - $commentsCheckPeriod, ':userID' => $userID]);
		$comments = $comments->fetchAll();
		$commentsCount = count($comments);
		
		$similarity = 0;
		$x = 1;
		$similarCommentsCount = 0;
		
		foreach($comments AS &$comment) {
			if(!isset($comments[$x])) break;
			
			$comment1 = Escape::prepare_for_checking(Escape::url_base64_decode($comment['comment']));
			$comment2 = Escape::prepare_for_checking(Escape::url_base64_decode($comments[$x]['comment']));
			
			$sim = self::similarity($comment1, $comment2);
			if($sim > 0.5) $similarCommentsCount++;
			
			$similarity += $sim;
			$x++;
		}
		
		if($similarity > $commentsCount / 3 && $similarCommentsCount > 3) {
			$isWarned = self::getLastAutomodAction(AutomodAction::ClanPostsSpammerWarning, true);
			
			if(!$isWarned) {
				self::logAutomodActions(AutomodAction::ClanPostsSpammerWarning, $similarCommentsCount, $similarity, $commentsCount, $userID);
				//$gs->sendCommentsSpammerWarningWebhook($similarCommentsCount, $userID);
			}
			
			$returnValue = false;
		}
		
		return $returnValue;
	}
	
	/*
		Automod::checkStatsSpeed($accountID)
		
		This function checks how fast user gains stats
		
		$accountID — account ID of user (Number)
		
		Return value:
			true — everything is good, no cheating
			false — cheating detected!
	*/
	public static function checkStatsSpeed($person) {
		require __DIR__."/../../config/security.php";
		require __DIR__."/connection.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		
		$accountID = $person['accountID'];
		
		$searchFilters = ['type = '.Action::ProfileStatsChange, 'account = '.$accountID, 'timestamp >= '.(time() - $statsTimeCheck)];
		$statChanges = Library::getActions($searchFilters);
		$statChangesCount = $statChanges ? count($statChanges) + 1 : 0;
		
		if(!$statChangesCount) return true;
		
		$searchFilters = ['type = '.Action::ProfileStatsChange, 'account = '.$accountID];
		$statChanges = Library::getActions($searchFilters, $statChangesCount);
		
		if(!$statChanges) return true;
		
		$timeBefore = $starsBefore = $moonsBefore = $userCoinsBefore = $demonsBefore = 0;
		
		foreach($statChanges AS $index => $stat) {
			if(!$index) {
				$timeBefore = time();
				continue;
			}
			
			$timeBefore -= time() - $stat['timestamp'];
			$starsBefore += $stat['value'];
			$moonsBefore += $stat['value6'];
			$userCoinsBefore += $stat['value4'];
			$demonsBefore += $stat['value3'];
		}
		
		$timeAfter = $statChanges[0]['timestamp'];
		$starsAfter = $statChanges[0]['value'];
		$moonsAfter = $statChanges[0]['value6'];
		$userCoinsAfter = $statChanges[0]['value4'];
		$demonsAfter = $statChanges[0]['value3'];
		
		$timeRatio = ($timeAfter - $timeBefore) / $statsTimeCheck;
		
		$starsRatio = ($starsAfter + $starsBefore) / $timeRatio;
		$moonsRatio = ($moonsAfter + $moonsBefore) / $timeRatio;
		$userCoinsRatio = ($userCoinsAfter + $userCoinsBefore) / $timeRatio;
		$demonsRatio = ($demonsAfter + $demonsBefore) / $timeRatio;
		
		if($starsRatio > $maxStarsPossible || $moonsRatio > $maxMoonsPossible || $userCoinsRatio > $maxUserCoinsPossible || $demonsRatio > $maxDemonsPossible) {
			$maxText = 'Max in '.$statsTimeCheck.' seconds: ⭐'.$maxStarsPossible.' • 🌙'.$maxMoonsPossible.' • 🪙'.$maxUserCoinsPossible.' • 👿'.$maxDemonsPossible.' | User stats ratio: ⭐'.$starsRatio.' • 🌙'.$moonsRatio.' • 🪙'.$userCoinsRatio.' • 👿'.$demonsRatio;
			
			Library::banPerson(0, $person, "You're too good at gaining stats.", Ban::Leaderboards, Person::AccountID, 2147483647, "Person gained too much stats in short time. (".$maxText.")");
			return false;
		}
		
		return true;
	}
}
?>