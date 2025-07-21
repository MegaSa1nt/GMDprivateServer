<?php
class Discord {
	public static function generateEmbedArray($author, $color, $title, $description, $thumbnail, $fieldsArray, $footer) {
		if(!is_array($author) || !is_array($title) || !is_array($fieldsArray) || !is_array($footer)) return false;
		
		$fields = [];
		
		$author = [
			"name" => $author[0],
			"url" => $author[1],
			"icon_url" => $author[2]
		];
		
		foreach($fieldsArray AS &$field) {
			if(!empty($field)) $fields[] = [
				"name" => $field[0],
				"value" => $field[1],
				"inline" => $field[2]
			];
		}
		
		$footer = [
			"text" => $footer[0],
			"icon_url" => $footer[1]
		];
		
		return [
			"content" => "",
			"tts" => false,
			"embeds" => [
				[
					"type" => "rich",
					"timestamp" => date("c", time()),
					"title" => $title[0],
					"url" => $title[1],
					"color" => hexdec($color),
					"description" => $description,
					"thumbnail" => [
						"url" => $thumbnail
					],
					"footer" => $footer,
					"author" => $author,
					"fields" => $fields
				]
			]
		];
	}
	
	public static function getUserDiscord($accountID) {
		require __DIR__."/connection.php";
		
		if(isset($GLOBALS['core_cache']['discord'][$accountID])) return $GLOBALS['core_cache']['discord'][$accountID];
		
		$discordLink = $db->prepare("SELECT discordID FROM accounts WHERE accountID = :accountID AND discordID != 0 AND discordLinkReq = 0");
		$discordLink->execute([':accountID' => $accountID]);
		$discordLink = $discordLink->fetchColumn();
		
		$GLOBALS['core_cache']['discord'][$accountID] = $discordLink;
		
		return $discordLink;
	}
	
	public static function startLinkingAccount($person, $discordID) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		require __DIR__."/../../config/discord.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		
		if(self::isTooManyFailedLinks($person, true)) return false;
		
		$accountID = $person['accountID'];
		$userName = $person['userName'];
		
		$code = rand(1000, 9999);
		
		$startLinking = $db->prepare("UPDATE accounts SET discordID = :discordID, discordLinkReq = :code WHERE accountID = :accountID");
		$startLinking->execute([':discordID' => $discordID, ':code' => $code, ':accountID' => $accountID]);
		
		$setTitle = self::getWebhookString('accountLinkTitle');
		$setDescription = sprintf(self::getWebhookString('accountLinkDesc'), $userName, $code);
		$setFooter = sprintf(self::getWebhookString('footer'), $gdps);
		
		$embed = self::generateEmbedArray(
			[$gdps, $authorURL, $authorIconURL],
			$pendingColor,
			[$setTitle, $linkTitleURL],
			$setDescription,
			$linkThumbnailURL,
			[],
			[$setFooter, $footerIconURL]
		);
		
		$sendMessage = self::sendDiscordMessage($discordID, $embed);
		
		if($sendMessage) Library::logAction($person, Action::DiscordLinkStart, $discordID, $sendMessage);
		else Library::logAction($person, Action::FailedDiscordLinkStart, $discordID);
		
		return $sendMessage;
	}
	
	public static function getWebhookString($langString) {
		require __DIR__.'/../../config/discord.php';
		
		if(isset($GLOBALS['core_cache']['webhookLanguageArray'])) $webhookLang = $GLOBALS['core_cache']['webhookLanguageArray'];
		else {
			if(!file_exists(__DIR__."/../../config/webhooks/lang/".$webhookLanguage.".php")) return $langString;
			
			require __DIR__."/../../config/webhooks/lang/".$webhookLanguage.".php";
			
			$GLOBALS['core_cache']['webhookLanguageArray'] = $webhookLang;
		}
		
		if(isset($webhookLang[$langString])) {
			if(is_array($webhookLang[$langString])) return $webhookLang[$langString][rand(0, count($webhookLang[$langString]) - 1)];
			
			return $webhookLang[$langString];
		}
		
		return $langString;
	}
	
	public static function sendDiscordMessage($discordID, $content) {
		require __DIR__.'/../../config/discord.php';
		require_once __DIR__.'/mainLib.php';
		
		if(!is_array($content)) $content = [
			"content" => $content,
			"tts" => false
		];

		$data = ["recipient_id" => $discordID];
		$headers = ['Content-type: application/json', 'Authorization: Bot '.$bottoken];
		
		$recipient = json_decode(Library::sendRequest("https://discord.com/api/v10/users/@me/channels", json_encode($data), $headers, "POST"), true);
		if(!$recipient || !$recipient['id']) return false;
	
		$channelID = $recipient['id'];		
		
		$sendMessage = json_decode(Library::sendRequest("https://discord.com/api/v10/channels/".$channelID."/messages", json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $headers, "POST"), true);
		if(!$sendMessage) return false;
		
		return $recipient['recipients'][0]['username'];
	}
	
	public static function verifyDiscordLinking($person, $code) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		require __DIR__."/../../config/discord.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		
		if(self::isTooManyFailedLinks($person, false)) return false;
		
		$accountID = $person['accountID'];
		$userName = $person['userName'];
		
		$discordID = $db->prepare("SELECT discordID FROM accounts WHERE accountID = :accountID AND discordLinkReq = :code");
		$discordID->execute([':accountID' => $accountID, ':code' => $code]);
		$discordID = $discordID->fetchColumn();
		if(!$discordID) {
			Library::logAction($person, Action::FailedDiscordLink, $code);
			return false;
		}
		
		$clearDiscordIDs = $db->prepare("UPDATE accounts SET discordID = 0, discordLinkReq = 0 WHERE discordID = :discordID");
		$clearDiscordIDs->execute([':discordID' => $discordID]);
		
		$linkDiscord = $db->prepare("UPDATE accounts SET discordID = :discordID WHERE accountID = :accountID");
		$linkDiscord->execute([':discordID' => $discordID, ':accountID' => $accountID]);
		
		$setTitle = self::getWebhookString('accountAcceptTitle');
		$setDescription = sprintf(self::getWebhookString('accountAcceptDesc'), $userName);
		$setFooter = sprintf(self::getWebhookString('footer'), $gdps);
		
		$embed = self::generateEmbedArray(
			[$gdps, $authorURL, $authorIconURL],
			$successColor,
			[$setTitle, $linkTitleURL],
			$setDescription,
			$acceptThumbnailURL,
			[],
			[$setFooter, $footerIconURL]
		);
		
		self::sendDiscordMessage($discordID, $embed);
		
		Library::logAction($person, Action::DiscordLink, $discordID);
		
		return $discordID;
	}
	
	public static function unlinkDiscordAccount($person) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		require __DIR__."/../../config/discord.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		
		$accountID = $person['accountID'];
		$userName = $person['userName'];
		
		$discordID = self::getUserDiscord($accountID);
		if(!$discordID) return false;
		
		$unlinkAccount = $db->prepare("UPDATE accounts SET discordID = 0 WHERE accountID = :accountID");
		$unlinkAccount->execute([':accountID' => $accountID]);
		
		$setTitle = self::getWebhookString('accountUnlinkTitle');
		$setDescription = sprintf(self::getWebhookString('accountUnlinkDesc'), $userName);
		$setFooter = sprintf(self::getWebhookString('footer'), $gdps);
		
		$embed = self::generateEmbedArray(
			[$gdps, $authorURL, $authorIconURL],
			$failColor,
			[$setTitle, $linkTitleURL],
			$setDescription,
			$unlinkThumbnailURL,
			[],
			[$setFooter, $footerIconURL]
		);
		
		self::sendDiscordMessage($discordID, $embed);
		
		Library::logAction($person, Action::DiscordUnlink, $discordID);
		
		return true;
	}
	
	public static function isTooManyFailedLinks($person, $isLinkStart) {
		require_once __DIR__."/enums.php";
		require_once __DIR__."/mainLib.php";
		
		$filters[] = "type = ".($isLinkStart ? Action::FailedDiscordLinkStart : Action::FailedDiscordLink);
		$filters[] = "timestamp >= ".time() - 3600;
		
		$failedLinks = Library::getPersonActions($person, $filters);
		
		return count($failedLinks) > 3;
	}
}
?>