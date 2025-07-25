<?php
/*
	Path to main directory
	
	It needs to point to main endpoint files: https://imgur.com/a/P8LdhzY
	
	Don't change this value if you don't undestand what it means!
*/
$dbPath = '../';

require_once __DIR__."/../".$dbPath."incl/lib/enums.php";

class Dashboard {
	/*
		Utils
	*/
	
	public static function loginDashboardUser() {
		global $dbPath;
		require __DIR__."/../".$dbPath."incl/lib/connection.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/security.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		require_once __DIR__."/../".$dbPath."incl/lib/ip.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['person'])) return $GLOBALS['core_cache']['dashboard']['person'];
		
		$IP = IP::getIP();
		
		$auth = Escape::latin($_COOKIE['auth']);
		
		if(empty($auth) || $auth == 'none') {
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => "0", "IP" => $IP];
			return ["success" => false, "accountID" => "0", "IP" => $IP];
		}
		
		$checkAuth = $db->prepare("SELECT * FROM accounts WHERE auth = :auth");
		$checkAuth->execute([':auth' => $auth]);
		$checkAuth = $checkAuth->fetch();
		if(empty($checkAuth)) {
			$logPerson = [
				'accountID' => "0",
				'userID' => "0",
				'userName' => '',
				'IP' => $IP
			];
			
			setcookie('auth', '', 2147483647, '/');

			Library::logAction($logPerson, Action::FailedLogin);
			
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => "0", "IP" => $IP];
			
			return ["success" => false, "accountID" => "0", "IP" => $IP];
		}
		
		$accountID = $checkAuth['accountID'];
		$userID = Library::getUserID($checkAuth['accountID']);
		$userName = $checkAuth['userName'];
		
		if(Security::isTooManyAttempts()) {
			$logPerson = [
				'accountID' => (string)$accountID,
				'userID' => (string)$userID,
				'userName' => $userName,
				'IP' => $IP
			];
			
			setcookie('auth', '', 2147483647, '/');

			Library::logAction($logPerson, Action::FailedLogin);
			
			$GLOBALS['core_cache']['dashboard']['person'] = ["success" => false, "accountID" => (string)$accountID, "IP" => $IP];
			
			return ["success" => false, "accountID" => (string)$accountID, "IP" => $IP];
		}
		
		$GLOBALS['core_cache']['dashboard']['person'] = ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => $userName, "IP" => $IP];
		
		return ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => $userName, "IP" => $IP];
	}
	
	public static function getUserIconKit($user) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		if(!is_array($user)) {
			$userID = $user;
			$user = Library::getUserByID($userID);
		} else $userID = $user['userID'];
		
		if(isset($GLOBALS['core_cache']['dashboard']['iconKit'][$userID])) return $GLOBALS['core_cache']['dashboard']['iconKit'][$userID];
		
		$iconTypes = ['cube', 'ship', 'ball', 'ufo', 'wave', 'robot', 'spider', 'swing', 'jetpack'];
		
		if(!$user) {
			$iconKit = [
				"main" => $iconsRendererServer."/icon.png?type=cube&value=1&color1=0&color2=3",
				"cube" => $iconsRendererServer."/icon.png?type=cube&value=1&color1=0&color2=3",
				"ship" => $iconsRendererServer."/icon.png?type=ship&value=1&color1=0&color2=3",
				"ball" => $iconsRendererServer."/icon.png?type=ball&value=1&color1=0&color2=3",
				"ufo" => $iconsRendererServer."/icon.png?type=ufo&value=1&color1=0&color2=3",
				"wave" => $iconsRendererServer."/icon.png?type=wave&value=1&color1=0&color2=3",
				"robot" => $iconsRendererServer."/icon.png?type=robot&value=1&color1=0&color2=3",
				"spider" => $iconsRendererServer."/icon.png?type=spider&value=1&color1=0&color2=3",
				"swing" => $iconsRendererServer."/icon.png?type=swing&value=1&color1=0&color2=3",
				"jetpack" => $iconsRendererServer."/icon.png?type=jetpack&value=1&color1=0&color2=3"
			];
			
			$GLOBALS['core_cache']['dashboard']['iconKit'][$userID] = $iconKit;
			
			return $iconKit;
		}
		
		$iconKit = [
			'main' => $iconsRendererServer.'/icon.png?type='.$iconTypes[$user['iconType']].'&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'cube' => $iconsRendererServer.'/icon.png?type=cube&value='.($user['accIcon'] ? $user['accIcon'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ship' => $iconsRendererServer.'/icon.png?type=ship&value='.($user['accShip'] ? $user['accShip'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ball' => $iconsRendererServer.'/icon.png?type=ball&value='.($user['accBall'] ? $user['accBall'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'ufo' => $iconsRendererServer.'/icon.png?type=ufo&value='.($user['accBird'] ? $user['accBird'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'wave' => $iconsRendererServer.'/icon.png?type=wave&value='.($user['accDart'] ? $user['accDart'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'robot' => $iconsRendererServer.'/icon.png?type=robot&value='.($user['accRobot'] ? $user['accRobot'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'spider' => $iconsRendererServer.'/icon.png?type=spider&value='.($user['accSpider'] ? $user['accSpider'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'swing' => $iconsRendererServer.'/icon.png?type=swing&value='.($user['accSwing'] ? $user['accSwing'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : ''),
			'jetpack' => $iconsRendererServer.'/icon.png?type=jetpack&value='.($user['accJetpack'] ? $user['accJetpack'] : 1).'&color1='.$user['color1'].'&color2='.$user['color2'].($user['accGlow'] ? '&glow='.$user['accGlow'].'&color3='.$user['color3'] : '')
		];
		
		$GLOBALS['core_cache']['dashboard']['iconKit'][$userID] = $iconKit;
			
		return $iconKit;
	}
	
	public static function parseMentions($person, $body) {
		global $dbPath;
		require __DIR__."/../".$dbPath."incl/lib/connection.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";

		$parseBody = explode(' ', $body);
		$players = $levels = $lists =  $emojis = [];
		$hasEmojis = strpos($body, ':') !== false;

		foreach($parseBody AS &$element) {
			$firstChar = mb_substr($element, 0, 1);
			if(!in_array($firstChar, ['@', '#'])) continue;

			$element = mb_substr($element, 1);

			switch($firstChar) {
				case '@':
					$player = Escape::latin($element);
					
					if(substr($element, 0, strlen($player)) != $player || empty($player) || in_array($player, $players)) break;
					
					$players[] = $player;

					break;
				case '#':
					$level = Escape::number($element);
					
					if(substr($element, 0, strlen($level)) != $level || !is_numeric($level) || in_array($level, $levels)) break;
					
					if($level >= 0) $levels[] = $level;
					else $lists[] = $level * -1;

					break;
			}
		}
		
		if($hasEmojis) {
			preg_match_all(':(\w+):', Escape::translit($body), $emojisArray);
			
			foreach($emojisArray AS &$emojiArray) {
				foreach($emojiArray AS &$emojiName) {				
					$emoji = Escape::text(strtolower($emojiName));
					$emojis[] = $emoji;
				}
			}
		}
		
		$players = array_unique($players);
		$levels = array_unique($levels);
		$lists = array_unique($lists);
		$emojis = array_unique($emojis);
		
		if(!empty($players)) {
			Library::cacheAccountsByUserNames($players);
			Library::cacheUsersByUserNames($players);
			
			foreach($players AS &$userName) {
				$user = Library::getUserByUserName($userName);
				if(!$user) continue;
				
				$account = Library::getAccountByUserName($userName);
				if(!$account || !$account['isActive']) continue;
				
				$userMetadata = self::getUserMetadata($user);
				$userString = self::getUsernameString($person, $user, $user['userName'], $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
				
				$body = str_replace('@'.$userName, $userString, $body);
			}
		}
		
		if(!empty($levels)) {
			Library::cacheLevelsByID($levels);
			
			foreach($levels AS &$levelID) {
				$level = Library::getLevelByID($levelID);
				if(!$level) continue;
				
				$canSeeLevel = Library::canAccountPlayLevel($person, $level);
				if(!$canSeeLevel) continue;
				
				$levelString = self::getLevelString($person, $level['extID'], $levelID, $level['levelName']);
				
				$body = str_replace('#'.$levelID, $levelString, $body);
			}
		}
		
		if(!empty($lists)) {
			Library::cacheListsByID($lists);
			
			foreach($lists AS &$listID) {
				$list = Library::getListByID($listID);
				if(!$list) continue;
				
				$canSeeList = Library::canAccountSeeList($person, $list);
				if(!$canSeeList) continue;
				
				$listString = self::getListString($person, $list['accountID'], $listID, $list['listName']);
				
				$body = str_replace('#-'.$listID, $listString, $body);
			}
		}
		
		if(!empty($emojis)) {
			$emojisList = self::getExistingEmojisFromList($emojis);
			
			foreach($emojisList AS $emoji => $emojiCategory) {
				$body = str_replace(':'.$emoji.':', self::getEmojiImg($emojiCategory, $emoji), $body);
			}
		}
		
		return trim($body);
	}
	
	public static function getUserMetadata($user) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['userMetadata'][$user['userID']])) return $GLOBALS['core_cache']['dashboard']['userMetadata'][$user['userID']];
		
		if(!$user) {
			return [
				'mainIcon' => $iconsRendererServer."/icon.png?type=cube&value=1&color1=0&color2=3",
				'userAppearance' => [
					'commentsExtraText' => '',
					'roleName' => '',
					'modBadgeLevel' => 0,
					'commentColor' => '255,255,255'
				],
				'userAttributes' => ''
			];
		}
		
		$userAttributes = [];
		
		$userPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		$iconKit = self::getUserIconKit($user);
		$userAppearance = Library::getPersonCommentAppearance($userPerson);
		$userColor = str_replace(",", " ", $userAppearance['commentColor']);
		
		if($userColor != '255 255 255') $userAttributes[] = 'style="--href-color: rgb('.$userColor.'); --href-shadow-color: rgb('.$userColor.' / 38%)"';
		if(!$user['isRegistered']) $userAttributes[] = 'dashboard-remove="href title"';
		
		$GLOBALS['core_cache']['dashboard']['userMetadata'][$user['userID']] = [
			'mainIcon' => $iconKit['main'],
			'userAppearance' => $userAppearance,
			'userAttributes' => implode(' ', $userAttributes)
		];
		
		return $GLOBALS['core_cache']['dashboard']['userMetadata'][$user['userID']];
	}
	
	public static function getExistingEmojisFromList($emojis) {
		$emojisArray = $allEmojisArray = [];
		
		$emojisJSONArray = self::loadJSON("icons/emojis/list");
		
		foreach($emojisJSONArray AS $emojisCategory => $emojisCategoryValue) {
			foreach($emojisCategoryValue AS &$emoji) $allEmojisArray[$emoji] = $emojisCategory;
		}
		
		foreach($emojis AS &$emoji) {
			if($allEmojisArray[$emoji]) $emojisArray[$emoji] = $allEmojisArray[$emoji];
		}
		
		return $emojisArray;
	}
	
	public static function getEmojiImg($emojiCategory, $emojiName, $setOnclick = false) {
		return '<img loading="lazy" title=":'.$emojiName.':" src="incl/icons/emojis/'.$emojiCategory.'/'.$emojiName.'.png" '.($setOnclick ? 'onclick="addEmojiToInput(\''.$emojiName.'\')"' : '').' />';
	}
	
	/*
		Translations
	*/
	
	public static function string($languageString) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['language'][$languageString])) return $GLOBALS['core_cache']['dashboard']['language'][$languageString];
		if(isset($GLOBALS['core_cache']['dashboard']['language'])) return $languageString;
		
		$language = self::allStrings();
		if(!isset($language[$languageString])) return $languageString;
		
		return $language[$languageString];
	}
	
	public static function allStrings() {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		if(isset($GLOBALS['core_cache']['dashboard']['language'])) return $GLOBALS['core_cache']['dashboard']['language'];
		
		if(!$_COOKIE['lang']) {
			if(file_exists(__DIR__.'/langs/'.strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2))).'.php') $_COOKIE['lang'] = strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
			else $_COOKIE['lang'] = 'EN';
				
			setcookie("lang", $_COOKIE['lang'], 2147483647, "/");
		}
		
		$userLanguage = Escape::latin_no_spaces($_COOKIE['lang'], 2);
		if(!file_exists(__DIR__."/langs/".$userLanguage.".php")) $userLanguage = 'EN';
		
		if($userLanguage != 'EN') require __DIR__."/langs/EN.php";
		require __DIR__."/langs/".$userLanguage.".php";
		
		$GLOBALS['core_cache']['dashboard']['language'] = $language;
		
		return $language;
	}
	
	public static function loadJSON($JSONPath) {
		if(isset($GLOBALS['core_cache']['dashboard']['json'][$JSONPath])) return $GLOBALS['core_cache']['dashboard']['json'][$JSONPath];
		
		$json = json_decode(file_get_contents(__DIR__."/".$JSONPath.".json"), true);
		
		$GLOBALS['core_cache']['dashboard']['json'][$JSONPath] = $json;
		
		return $json;
	}
	
	/*
		Render pages
	*/
	
	public static function renderPage($template, $pageTitle, $pageBase, $dataArray) {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		if(!is_array($dataArray)) $dataArray = ['PAGE' => $dataArray];
		
		$isInClan = false;
		$clanName = '';
		
		$person = self::loginDashboardUser();
		$userID = $person['userID'];
		$user = Library::getUserByID($userID);
		
		if($clansEnabled && $user['clanID']) {
			$isInClan = true;
			$clan = Library::getClanByID($user['clanID']);
			
			$clanName = $clan['clanName'];
		}
		
		if(!file_exists(__DIR__."/templates/main.html") || !file_exists(__DIR__."/templates/".$template.".html") || !is_array($dataArray)) return false;
		
		$templatePage = self::renderTemplate($template, $dataArray);
		
		$iconKit = self::getUserIconKit($userID);
		
		$mainPageData = [
			'PAGE' => $templatePage,
			
			'PAGE_TITLE' => $pageTitle,
			'GDPS_NAME' => $gdps,
			'PAGE_BASE' => $pageBase,
			
			'DASHBOARD_FAVICON' => $dashboardFavicon,
			'DASHBOARD_ACCENT_COLOR' => $accentColor,
			'DATABASE_PATH' => $dbPath,
			
			'STYLE_TIMESTAMP' => filemtime(__DIR__."/style.css"),
			'SCRIPT_TIMESTAMP' => filemtime(__DIR__."/script.js"),
			
			'MAX_SONG_SIZE' => $songSize,
			'MAX_SFX_SIZE' => $sfxSize,
			
			'MAX_SONG_SIZE_TEXT' => sprintf(self::string("errorMaxFileSize"), $songSize),
			'MAX_SFX_SIZE_TEXT' => sprintf(self::string("errorMaxFileSize"), $sfxSize),
			
			'FAILED_TO_LOAD_TEXT' => "<i class='fa-solid fa-xmark'></i>".self::string("errorFailedToLoadPage"),
			'COPIED_TEXT' => "<i class='fa-solid fa-copy'></i>".self::string("successCopiedText"),
			
			'CONVERTER_APIS' => json_encode($convertSFXAPI),
			
			'LANGUAGE' => Escape::latin_no_spaces($_COOKIE['lang'], 2) ?: "EN",
			
			'UPLOAD_SONG_PAGE_ENABLED' => strpos($songEnabled, '1') !== false || strpos($songEnabled, '2') !== false ? 'true' : 'false',
			'UPLOAD_SFX_PAGE_ENABLED' => $sfxEnabled ? 'true' : 'false',
			'REUPLOAD_LEVEL_PAGE_ENABLED' => $lrEnabled ? 'true' : 'false',
			
			'IS_LOGGED_IN' => $person['success'] ? 'true' : 'false',
			'USERNAME' => $person['success'] ? htmlspecialchars($person['userName']) : '',
			'PROFILE_ICON' => $person['success'] ? $iconKit['main'] : '',
			'IS_IN_CLAN' => $isInClan ? 'true' : 'false',
			'CLAN_NAME' => htmlspecialchars($clanName),
			
			'FOOTER' => ""
		];
		
		$personPermissions = Library::getPersonPermissions($person);
		foreach($personPermissions AS $permission => $value) $mainPageData['PERMISSION_'.$permission] = $value ? 'true' : 'false';
		
		$allStrings = self::allStrings();
		foreach($allStrings AS $string => $value) $mainPageData['TEXT_'.$string] = $value;
		
		$languageCredits = self::loadJSON("credits");
		foreach($languageCredits['languages'] AS $string => $value) $mainPageData['LANGUAGE_'.$string] = $value;
		
		$page = self::renderTemplate('main', $mainPageData);
		
		return $page;
	}
	
	public static function renderErrorPage($pageTitle, $error, $pageBase = "../") {
		global $dbPath;
		require __DIR__."/../".$dbPath."config/dashboard.php";
		
		$dataArray = [
			'INFO_TITLE' => self::string("errorTitle"),
			'INFO_DESCRIPTION' => $error,
			'INFO_EXTRA' => '',
			'INFO_BUTTON_TEXT' => self::string("home"),
			'INFO_BUTTON_ONCLICK' => "getPage('')"
		];
		
		$page = self::renderPage("general/info", $pageTitle, $pageBase, $dataArray);
		
		return $page;
	}
	
	public static function renderToast($icon, $text, $state, $location = '') {
		return "<div id='toast' state='".$state."' location='".$location."'><i class='fa-solid fa-".$icon."'></i>".$text."</div>";
	}
	
	public static function renderTemplate($template, $dataArray) {
		if(!isset($GLOBALS['core_cache']['dashboard']['template'][$template])) {
			$templatePage = file_get_contents(__DIR__."/templates/".$template.".html");
			$GLOBALS['core_cache']['dashboard']['template'][$template] = $templatePage;
		} else $templatePage = $GLOBALS['core_cache']['dashboard']['template'][$template];
		
		if(!empty($dataArray)) foreach($dataArray AS $key => $value) $templatePage = str_replace("%".$key."%", (string)$value, $templatePage);
		
		return $templatePage;
	}
	
	public static function getUsernameString($person, $user, $userName, $mainIcon, $userAppearance, $attributes = '', $showContextMenu = true) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$isPersonThemselves = $person['accountID'] == $user['extID']; 
		$badgeNumber = $userAppearance['modBadgeLevel'];
		
		$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $user['userID']);

		$usernameData = $contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'true';
		
		$usernameData['USERNAME_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		$usernameData['USERNAME_TITLE'] = sprintf(self::string('userProfile'), $usernameData['USERNAME_NAME']);
		$usernameData['USERNAME_PFP'] = $contextMenuData['MENU_PFP'] = $mainIcon;
			
		$usernameData['USERNAME_ATTRIBUTES'] = $contextMenuData['MENU_NAME_ATTRIBUTES'] = $attributes;
			
		$usernameData['USERNAME_HAS_BADGE'] = $contextMenuData['MENU_HAS_BADGE'] = (int)$badgeNumber ? 'true' : 'false';
		$usernameData['USERNAME_BADGE_NUMBER'] = $contextMenuData['MENU_BADGE_NUMBER'] = (int)$badgeNumber;
		$usernameData['USERNAME_ROLE'] = $contextMenuData['MENU_ROLE'] = htmlspecialchars($userAppearance['roleName']);
		
		$usernameData['USERNAME_CONTEXT_MENU'] = '';
		
		if($showContextMenu) {
			$contextMenuData['MENU_CAN_SEE_COMMENT_HISTORY'] = $canSeeCommentHistory ? 'true' : 'false';
			
			$contextMenuData['MENU_CAN_SEE_BANS'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
			$contextMenuData['MENU_CAN_OPEN_SETTINGS'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageAccounts")) ? 'true' : 'false';
			$contextMenuData['MENU_CAN_BLOCK'] = ($person['accountID'] != 0 && !$isPersonThemselves) ? 'true' : 'false';
			$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
			
			$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_SEE_BANS'] == 'true' || $contextMenuData['MENU_CAN_OPEN_SETTINGS'] == 'true' || $contextMenuData['MENU_CAN_BLOCK'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';
			
			$usernameData['USERNAME_CONTEXT_MENU'] = self::renderTemplate('components/menus/user', $contextMenuData);
		}
		
		return self::renderTemplate('components/username', $usernameData);
	}
	
	public static function getLevelString($person, $accountID, $levelID, $levelName) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$isPersonThemselves = $person['accountID'] == $accountID; 
		
		$levelnameData = $contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'true';
		
		$levelnameData['LEVELNAME_ID'] = $contextMenuData['MENU_ID'] = (int)$levelID;
		$levelnameData['LEVELNAME_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($levelName);
		$levelnameData['LEVELNAME_TITLE'] = sprintf(self::string('levelProfile'), $levelnameData['LEVELNAME_NAME']);
			
		$levelnameData['LEVELNAME_ATTRIBUTES'] = $contextMenuData['MENU_NAME_ATTRIBUTES'] = !$levelID ? 'dashboard-remove="href title"' : '';
		
		$contextMenuData['MENU_CAN_MANAGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "commandDelete")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
		
		$levelnameData['LEVELNAME_CONTEXT_MENU'] = self::renderTemplate('components/menus/level', $contextMenuData);
		
		return self::renderTemplate('components/levelname', $levelnameData);
	}
	
	public static function getListString($person, $accountID, $listID, $listName) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$isPersonThemselves = $person['accountID'] == $accountID; 
		
		$listnameData = $contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'true';
		
		$listnameData['LISTNAME_ID'] = $contextMenuData['MENU_ID'] = (int)$listID;
		$listnameData['LISTNAME_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($listName);
		$listnameData['LISTNAME_TITLE'] = sprintf(self::string('listProfile'), $listnameData['LISTNAME_NAME']);
			
		$listnameData['LISTNAME_ATTRIBUTES'] = $contextMenuData['MENU_NAME_ATTRIBUTES'] = !$listID ? 'dashboard-remove="href title"' : '';
		
		$contextMenuData['MENU_CAN_MANAGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "commandDelete")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
		
		$listnameData['LISTNAME_CONTEXT_MENU'] = self::renderTemplate('components/menus/list', $contextMenuData);
		
		return self::renderTemplate('components/listname', $listnameData);
	}
	
	public static function getClanString($person, $clanID) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$accountID = $person['accountID'];
		
		$clan = Library::getClanByID($clanID);
		if(!$clan) return false;
		
		$isClanOwner = $clan['clanOwner'] == $accountID; 
		
		$clannameData = $contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'true';
		
		$clannameData['CLANNAME_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($clan['clanName']);
		$clannameData['CLANNAME_TITLE'] = sprintf(self::string('clanProfile'), $clannameData['CLANNAME_NAME']);
			
		$clannameData['CLANNAME_ATTRIBUTES'] = $contextMenuData['MENU_NAME_ATTRIBUTES'] = 'style="color: #'.$clan['clanColor'].'; text-shadow: 0px 0px 20px #'.$clan['clanColor'].'61;"';
		
		$contextMenuData['MENU_CAN_MANAGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageClans")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = $contextMenuData['MENU_CAN_MANAGE'] == 'true' ? 'true' : 'false';
		
		$clannameData['CLANNAME_CONTEXT_MENU'] = self::renderTemplate('components/menus/clan', $contextMenuData);
		
		return self::renderTemplate('components/clanname', $clannameData);
	}
	
	public static function renderLevelCard($level, $person, $showPrivacy = true) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		$contextMenuData = [];
		
		$user = Library::getUserByID($level['userID']);
		$userName = $user ? $user['userName'] : 'Undefined';
	
		$levelLengths = ['Tiny', 'Short', 'Medium', 'Long', 'XL', 'Platformer'];
		
		$userMetadata = self::getUserMetadata($user);
		
		$song = $level['songID'] ? Library::getSongByID($level['songID']) : Library::getAudioTrack($level['audioTrack']);
		
		$level['LEVEL_TITLE'] = sprintf(self::string('levelTitle'), $level['levelName'], self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']));
		$level['LEVEL_DESCRIPTION'] = self::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($level['levelDesc']))) ?: "<i>".self::string('noDescription')."</i>";
		$level['LEVEL_DIFFICULTY_IMAGE'] = Library::getLevelDifficultyImage($level);
		
		$level['LEVEL_LENGTH'] = $levelLengths[$level['levelLength']];
		$level['LEVEL_IS_PLATFORMER'] = $level['levelLength'] == 5 ? 'true' : 'false';
		
		$level['LEVEL_LIKES'] = abs($level['likes'] - $level['dislikes']);
		$level['LEVEL_IS_DISLIKED'] = $level['dislikes'] > $level['likes'] ? 'true' : 'false';
		
		if($song) $level['LEVEL_SONG'] = $song['authorName']." - ".$song['name'];
		else $level['LEVEL_SONG'] = self::string("unknownSong");
		$level['LEVEL_SONG_ID'] = $song['ID'] ?: '';
		$level['LEVEL_SONG_AUTHOR'] = $song['authorName'] ?: '';
		$level['LEVEL_SONG_TITLE'] = $song['name'] ?: '';
		$level['LEVEL_SONG_URL'] = urlencode(urldecode($song['download'])) ?: '';
		$level['LEVEL_IS_CUSTOM_SONG'] = isset($song['ID']) ? 'true' : 'false';
		
		$level['LEVEL_SHOW_PRIVACY'] = 'false';
		$level['LEVEL_PRIVACY_ICON'] = $level['LEVEL_PRIVACY_TEXT'] = '';
		if($showPrivacy) {
			$level['LEVEL_SHOW_PRIVACY'] = 'true';
			
			switch($level['unlisted']) {
				case 0:
					$level['LEVEL_PRIVACY_ICON'] = 'eye';
					$level['LEVEL_PRIVACY_TEXT'] = self::string("public");
					break;
				case 1:
					$level['LEVEL_PRIVACY_ICON'] = 'lock';
					$level['LEVEL_PRIVACY_TEXT'] = self::string("onlyForFriends");
					break;
				default:
					$level['LEVEL_PRIVACY_ICON'] = 'eye-slash';
					$level['LEVEL_PRIVACY_TEXT'] = self::string("unlisted");
					break;
			}
		}
		
		$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
		$contextMenuData['MENU_ID'] = $level['levelID'];
		
		$contextMenuData['MENU_CAN_MANAGE'] = ($person['accountID'] == $level['extID'] || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_DELETE'] = ($person['accountID'] == $level['extID'] || Library::checkPermission($person, "commandDelete")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
		
		$level['LEVEL_CONTEXT_MENU'] = self::renderTemplate('components/menus/level', $contextMenuData);
		
		return self::renderTemplate('components/level', $level);
	}
	
	public static function renderCommentCard($comment, $person, $showLevel = false) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['userID'] == $comment['userID'];
		
		$user = Library::getUserByID($comment['userID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		if($showLevel) {
			$comment['COMMENT_LEVEL_TEXT'] = $comment['itemID'] >= 0 ? self::getLevelString($person, $comment['creatorAccountID'], $comment['itemID'], $comment['itemName']) : self::getListString($person, $comment['creatorAccountID'], $comment['itemID'] * -1, $comment['itemName']);
		}
		$comment['COMMENT_SHOW_LEVEL'] = $showLevel ? 'true' : 'false';
		
		$comment['COMMENT_USER'] = self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
		$comment['COMMENT_CONTENT'] = self::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($comment['comment']))) ?: "<i>".self::string('emptyComment')."</i>";
		
		$comment['COMMENT_SHOW_PERCENT'] = $comment['percent'] > 0 ? 'true' : 'false';
		
		$contextMenuData['MENU_ID'] = $comment['commentID'];
		$contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "actionDeleteComment")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_CONTEXT'] = ($contextMenuData['MENU_CAN_DELETE'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';
		
		$comment['COMMENT_CONTEXT_MENU'] = self::renderTemplate('components/menus/comment', $contextMenuData);
			
		return self::renderTemplate('components/comment', $comment);
	}
	
	public static function renderPostCard($accountPost, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['userID'] == $accountPost['userID'];
		
		$user = Library::getUserByID($accountPost['userID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		$accountPost['POST_USER'] = self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
		$accountPost['POST_CONTENT'] = self::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($accountPost['comment']))) ?: "<i>".self::string('emptyPost')."</i>";
		
		$contextMenuData['MENU_ID'] = $accountPost['commentID'];
		$contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "actionDeleteComment")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_CONTEXT'] = ($contextMenuData['MENU_CAN_DELETE'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';
		
		$accountPost['POST_CONTEXT_MENU'] = self::renderTemplate('components/menus/post', $contextMenuData);
			
		return self::renderTemplate('components/post', $accountPost);
	}
	
	public static function renderScoreCard($score, $person, $levelIsPlatformer, $showLevel = false) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['accountID'] == $score['accountID'];
		
		$user = Library::getUserByAccountID($score['accountID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		if($showLevel) {
			$score['SCORE_LEVEL_TEXT'] = self::getLevelString($person, $score['accountID'], $score['levelID'], $score['levelName']);
		}
		$score['SCORE_SHOW_LEVEL'] = $showLevel ? 'true' : 'false';
		$score['SCORE_SHOW_PLACE'] = !$showLevel ? 'true' : 'false';
		
		$score['SCORE_USER'] = self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
		
		$score['SCORE_IS_LEADER'] = $score['SCORE_NUMBER'] < 4 ? 'true' : 'false';
		
		$score['SCORE_IS_PLATFORMER'] = $levelIsPlatformer ? 'true' : 'false';
		
		$score['SCORE_CAN_SEE_HIDDEN'] = ($person['accountID'] == $user['accountID'] || Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		if($score['SCORE_CAN_SEE_HIDDEN'] == 'false') {
			$score['clicks'] = 'Smartest one here? :trollface:';
			if(!$levelIsPlatformer) $score['time'] = 'No time for ya!!!';
		}
		
		if(isset($score['uploadDate'])) $score['timestamp'] = $score['uploadDate'];
		if(isset($score['ID'])) $score['scoreID'] = $score['ID'];
		
		$contextMenuData['MENU_ID'] = $score['scoreID'];
		$contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardDeleteLeaderboards")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_CONTEXT'] = ($contextMenuData['MENU_CAN_DELETE'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';
		
		$score['SCORE_CONTEXT_MENU'] = self::renderTemplate('components/menus/score', $contextMenuData);
			
		return self::renderTemplate('components/score', $score);
	}
	
	public static function renderSongCard($song, $person, $favouriteSongs) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['accountID'] == $song['reuploadID'];
		
		$user = Library::getUserByAccountID($song['reuploadID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		$downloadLink = urlencode(urldecode($song["download"]));
		
		$song['SONG_USER'] = self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
		
		$song['SONG_TITLE'] = sprintf(self::string('songTitle'), htmlspecialchars($song['authorName']), htmlspecialchars($song['name']));
		$song['SONG_AUTHOR'] = $contextMenuData['MENU_SONG_AUTHOR'] = htmlspecialchars($song['authorName']);
		$song['SONG_NAME'] = $contextMenuData['MENU_SONG_NAME'] = htmlspecialchars($song['name']);
		$song['SONG_URL'] = $contextMenuData['MENU_SONG_URL'] = htmlspecialchars($downloadLink);
		
		$song['SONG_SIZE'] = sprintf(self::string("songSizeTemplate"), $song['size'] ?: 0);
		
		$song['SONG_LEVELS_COUNT'] = $song['levelsCount'] ?: 0;
		$song['SONG_FAVORITES_COUNT'] = $song['favoritesCount'] ?: 0;
		
		$song['SONG_IS_FAVOURITE'] = (is_array($favouriteSongs) && in_array($song['ID'], $favouriteSongs)) || (!is_array($favouriteSongs) && $favouriteSongs) ? 'true' : 'false';
		
		$contextMenuData['MENU_ID'] = $song['ID'];
		$contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_CHANGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageSongs")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$song['SONG_CONTEXT_MENU'] = self::renderTemplate('components/menus/song', $contextMenuData);
		
		return self::renderTemplate('components/song', $song);
	}
	
	public static function renderSFXCard($sfx, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['accountID'] == $sfx['reuploadID'];
		
		$user = Library::getUserByAccountID($sfx['reuploadID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		$downloadLink = urlencode(urldecode($sfx["download"]));
		
		$sfx['SFX_IS_LOCAL'] = $sfx['isLocalSFX'] ? 'true' : 'false';
		
		$sfx['SFX_USER'] = $sfx['isLocalSFX'] ? self::getUsernameString($person, $user, $user['userName'], $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']) : '';
		
		$sfx['SFX_AUTHOR'] =$contextMenuData['MENU_SONG_AUTHOR'] =  $sfx['isLocalSFX'] ? htmlspecialchars($user['userName']) : htmlspecialchars($sfx['authorName']);
		$sfx['SFX_NAME'] = $contextMenuData['MENU_SFX_NAME'] = htmlspecialchars($sfx['name']);
		$sfx['SFX_URL'] = $contextMenuData['MENU_SFX_URL'] = htmlspecialchars($downloadLink);
		
		$sfx['SFX_ID'] = $contextMenuData['MENU_ID'] = $sfx['ID'] ?: $sfx['originalID'];
		$contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_CHANGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageSongs")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$sfx['SFX_CONTEXT_MENU'] = self::renderTemplate('components/menus/sfx', $contextMenuData);
		
		return self::renderTemplate('components/sfx', $sfx);
	}
	
	public static function renderListCard($list, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
		
		$contextMenuData = [];
		$isPersonThemselves = $person['accountID'] == $list['accountID'];
		
		$user = Library::getUserByAccountID($list['accountID']);
		$userName = $user ? $user['userName'] : 'Undefined';
		
		$userMetadata = self::getUserMetadata($user);
		
		$list['LIST_TITLE'] = sprintf(self::string('levelTitle'), $list['listName'], self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']));
		$list['LIST_DESCRIPTION'] = self::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($list['listDesc']))) ?: "<i>".self::string('noDescription')."</i>";
		$list['LIST_DIFFICULTY_IMAGE'] = Library::getListDifficultyImage($list);
		
		$list['LIST_LIKES'] = abs($list['likes'] - $list['dislikes']);
		$list['LIST_IS_DISLIKED'] = $list['dislikes'] > $list['likes'] ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
		$contextMenuData['MENU_ID'] = $list['listID'];
		
		$contextMenuData['MENU_CAN_MANAGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "commandDelete")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
		
		$list['LIST_CONTEXT_MENU'] = self::renderTemplate('components/menus/list', $contextMenuData);
		
		return self::renderTemplate('components/list', $list);
	}
	
	public static function renderMapPackCard($mapPack, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$contextMenuData = [];
		
		$mapPackTextColor = str_replace(',', ' ', $mapPack['rgbcolors']);
		$mapPackBarColor = $mapPack['colors2'] && $mapPack['colors2'] != 'none' ? str_replace(',', ' ', $mapPack['colors2']) : $mapPackTextColor;
		
		$mapPack['MAPPACK_TITLE'] = htmlspecialchars($mapPack['name']);
		$mapPack['MAPPACK_DIFFICULTY_IMAGE'] = Library::getMapPackDifficultyImage($mapPack);
		
		$mapPack['MAPPACK_NAME_ATTRIBUTES'] = 'style="--href-color: rgb('.$mapPackTextColor.'); --href-shadow-color: rgb('.$mapPackTextColor.' / 38%)"';
		$mapPack['MAPPACK_LEVELS_ATTRIBUTES'] = 'style="--href-color: rgb('.$mapPackBarColor.'); --href-shadow-color: rgb('.$mapPackBarColor.' / 38%)"';
		
		$mapPack['MAPPACK_LEVELS_COUNT'] = count(explode(',', $mapPack['levels']));
		
		$contextMenuData['MENU_ID'] = $mapPack['ID'];
		
		$contextMenuData['MENU_CAN_MANAGE'] = Library::checkPermission($person, "dashboardLevelPackCreate") ? 'true' : 'false';
		
		$mapPack['MAPPACK_CONTEXT_MENU'] = self::renderTemplate('components/menus/mappack', $contextMenuData);
		
		return self::renderTemplate('components/mappack', $mapPack);
	}
	
	public static function renderGauntletCard($gauntlet, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$contextMenuData = [];
		
		$gauntlet['GAUNTLET_TITLE'] = Library::getGauntletName($gauntlet['ID']).' Gauntlet';
		$gauntlet['GAUNTLET_DIFFICULTY_IMAGE'] = Library::getGauntletImage($gauntlet['ID']);
		
		$contextMenuData['MENU_ID'] = $gauntlet['ID'];
		
		$contextMenuData['MENU_CAN_MANAGE'] = Library::checkPermission($person, "dashboardGauntletCreate") ? 'true' : 'false';
		
		$gauntlet['GAUNTLET_CONTEXT_MENU'] = self::renderTemplate('components/menus/gauntlet', $contextMenuData);
		
		return self::renderTemplate('components/gauntlet', $gauntlet);
	}
	
	public static function renderEmojisDiv() {
		if(file_exists(__DIR__.'/templates/components/emojis_prerendered.html')) {
			$emojisDiv = file_get_contents(__DIR__.'/templates/components/emojis_prerendered.html');
			
			return $emojisDiv;
		}
		
		$emojisDivs = [];
		$emojisDiv = '';
		
		$emojisJSONArray = self::loadJSON("icons/emojis/list");
		$emojisOrder = self::loadJSON("icons/emojis/order");
		
		foreach($emojisJSONArray AS $emojisCategory => $emojisCategoryValue) {
			if(!$emojisDivs[$emojisCategory]) $emojisDivs[$emojisCategory] = '';
			
			foreach($emojisCategoryValue AS &$emoji) {
				$emojisDivs[$emojisCategory] .= self::getEmojiImg($emojisCategory, $emoji, true);
			}
		}
		
		foreach($emojisOrder AS $emojiCategory => $emojis) {
			$emojisDiv .= '<div class="horisontal">
				<h3>'.$emojis[0].' '.self::getEmojiImg($emojiCategory, $emojis[1]).'</h3>'.
				$emojisDivs[$emojiCategory]
			.'</div>';
		}
		
		file_put_contents(__DIR__.'/templates/components/emojis_prerendered.html', $emojisDiv);
		
		return $emojisDiv;
	}
	
	public static function renderUserCard($user, $person, $extraIcon = '', $extraIconTitle = '') {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$isPersonThemselves = $person['accountID'] == $user['extID'];
	
		$userName = $user ? $user['userName'] : 'Undefined';
		$userMetadata = self::getUserMetadata($user);

		$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $user['userID']);

		$contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
		$user['USER_CARD'] = self::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes'], false);
		
		$user['USER_SHOW_EXTRA_ICON'] = $extraIcon ? 'true' : 'false';
		$user['USER_EXTRA_ICON'] = htmlspecialchars($extraIcon);
		$user['USER_EXTRA_ICON_TITLE'] = htmlspecialchars($extraIconTitle);
		
		$user['USER_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($userName);
		
		$contextMenuData['MENU_CAN_SEE_COMMENT_HISTORY'] = $canSeeCommentHistory ? 'true' : 'false';
		
		$contextMenuData['MENU_CAN_SEE_BANS'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_OPEN_SETTINGS'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageAccounts")) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BLOCK'] = ($person['accountID'] != 0 && !$isPersonThemselves) ? 'true' : 'false';
		$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_SEE_BANS'] == 'true' || $contextMenuData['MENU_CAN_OPEN_SETTINGS'] == 'true' || $contextMenuData['MENU_CAN_BLOCK'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';
		
		$user['USER_CONTEXT_MENU'] = self::renderTemplate('components/menus/user', $contextMenuData);
		
		return self::renderTemplate('components/user', $user);
	}
	
	public static function renderClanCard($clan, $person) {
		global $dbPath;
		require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
		
		$isClanOwner = $person['accountID'] == $clan['clanOwner'];
	
		$ownerUser = Library::getUserByAccountID($clan['clanOwner']);
		$ownerUserName = $ownerUser ? $ownerUser['userName'] : 'Undefined';
		$userMetadata = self::getUserMetadata($ownerUser);

		$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $ownerUser['userID']);

		$contextMenuData = [];
		
		$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
		$clan['CLAN_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($clan['clanName']);
		$clan['CLAN_DESCRIPTION'] = self::parseMentions($person, htmlspecialchars($clan['clanDesc'])) ?: "<i>".self::string('noDescription')."</i>";
		$clan['CLAN_TITLE'] = sprintf(self::string("clanProfile"), htmlspecialchars($clan['clanName']));
		$clan['CLAN_COLOR'] = "color: #".$clan['clanColor']."; text-shadow: 0px 0px 20px #".$clan['clanColor']."61;";
		
		$clan['CLAN_TAG'] = htmlspecialchars($clan['clanTag']);
		$clan['CLAN_HAS_TAG'] = !empty($clan['CLAN_TAG']) ? 'true' : 'false';

		$clan['CLAN_HAS_RANK'] = $clan['clanRank'] != 0 ? 'true' : 'false';
		$clan['CLAN_IS_TOP_100'] = $clan['clanRank'] <= 100 ? 'true' : 'false';

		$clan['CLAN_IS_CLOSED'] = $clan['isClosed'] ? 'true' : 'false';

		$clan['CLAN_OWNER_CARD'] = self::getUsernameString($person, $ownerUser, $ownerUserName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);
		
		$contextMenuData['MENU_SHOW_MANAGE_HR'] = $contextMenuData['MENU_CAN_MANAGE'] == 'true' ? 'true' : 'false';
		
		$clan['CLAN_CONTEXT_MENU'] = self::renderTemplate('components/menus/clan', $contextMenuData);
		
		return self::renderTemplate('components/clan', $clan);
	}
}
?>