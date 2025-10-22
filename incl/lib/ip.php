<?php
class IP {
	public static function ipv4inrange($ip, $range) {
		require_once __DIR__ . "/ip_in_range.php";
		return ipInRange::ipv4_in_range($ip, $range);
	}
	
	public static function ipv6_in_range($ip, $range_ip) {
		require_once __DIR__ . "/ip_in_range.php";
		return ipInRange::ipv6_in_range($ip, $range_ip);
	}
	
	public static function isCloudFlareIP($ip) {
		$cf_ipv4s = array(
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22'
		);
		$cf_ipv6s = array(
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2a06:98c0::/29',
			'2c0f:f248::/32'
		);
		foreach($cf_ipv4s as $cf_ip) {
			if(self::ipv4inrange($ip, $cf_ip)) {
				return true;
			}
		}
		foreach($cf_ipv6s as $cf_ip) {
			if(self::ipv6_in_range($ip, $cf_ip)) {
				return true;
			}
		}
		return false;
	}
	
	public static function getIP() {
		/*
			Cloudflare reverse proxy
		*/
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP']) && self::isCloudFlareIP($_SERVER['REMOTE_ADDR'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
		
		/*
			141412.xyz support
		*/
		if(isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['REMOTE_ADDR'] == "10.0.1.10") return $_SERVER['HTTP_X_REAL_IP'];
			
		/*
			Localhost reverse proxy (mostly 7m.pl)
		*/
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && self::ipv4inrange($_SERVER['REMOTE_ADDR'], '127.0.0.0/8')) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		
		/*
			Direct access to server
		*/
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public static function checkProxy() {
		require __DIR__."/../../config/security.php";
		require_once __DIR__."/mainLib.php";
		
		if(!isset($blockFreeProxies)) global $blockFreeProxies;
		if(!isset($proxies)) global $proxies;
		
		if(!$blockFreeProxies) return;
		
		$fileExists = file_exists(__DIR__ .'/../../config/proxies.txt');
		$lastUpdate = $fileExists ? filemtime(__DIR__ .'/../../config/proxies.txt') : 0;
		$checkTime = time() - 3600;
		$allProxies = '';
		
		if($checkTime > $lastUpdate) {
			foreach($proxies AS $url) {
				$IPs = Library::sendRequest($url, "", [], "GET");
				$proxy = preg_split('/\r\n|\r|\n/', $IPs);
				foreach($proxy AS $ip) $allProxies .= explode(':', $ip)[0].PHP_EOL;
			}
			file_put_contents(__DIR__ .'/../../config/proxies.txt', $allProxies);
		}
		
		if(empty($allProxies)) $allProxies = file(__DIR__ .'/../../config/proxies.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		else $allProxies = explode(PHP_EOL, $allProxies);
		
		if(in_array(self::getIP(), $allProxies)) {
			http_response_code(404);
			exit;
		}
	}
	public static function checkVPN() {
		require __DIR__."/../../config/security.php";
		require __DIR__."/../../config/proxy.php";
		
		if(!isset($blockCommonVPNs)) global $blockCommonVPNs;
		if(!isset($vpns)) global $vpns;
		
		if(!$blockCommonVPNs) return;
		
		$fileExists = file_exists(__DIR__ .'/../../config/vpns.txt');
		$lastUpdate = $fileExists ? filemtime(__DIR__ .'/../../config/vpns.txt') : 0;
		$checkTime = time() - 3600; 
		$allVPNs = '';
		
		if($checkTime > $lastUpdate) {
			foreach($vpns AS $url) {
				$IPs = Library::sendRequest($url, "", [], "GET");
				$allVPNs .= $IPs.PHP_EOL;
			}
			file_put_contents(__DIR__ .'/../../config/vpns.txt', $allVPNs);
		}
		
		if(empty($allVPNs)) $allVPNs = file(__DIR__ .'/../../config/vpns.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		else $allVPNs = explode(PHP_EOL, $allVPNs);
		
		foreach($allVPNs AS &$vpnCheck) {
			if(self::ipv4inrange(self::getIP(), $vpnCheck)) {
				http_response_code(404);
				exit;
			}
		}
	}
	public static function checkIP($db) {
		require_once __DIR__."/mainLib.php";
		
		self::checkProxy();
		self::checkVPN();
		
		$banip = $db->prepare("SELECT count(*) FROM bannedips WHERE IP REGEXP CONCAT('((\\\D[^.])|^)(', :IP, ')(\\\D[^$])')");
		$banip->execute([':IP' => Library::convertIPForSearching(self::getIP(), true)]);
		$banip = $banip->fetchColumn();
		
		if($banip > 0) {
			http_response_code(404);
			exit;
		}
	}
}
?>