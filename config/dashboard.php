<?php
$installed = true; // Like i said, it changed!

/*
	Main dashboard settings
	
	$gdps — GDPS name, shows in title and various dashboard and Discord webhooks
	
	$accentColor — main color of your GDPS, will affect dashboard's accent color
	
	$lrEnabled — are level reupload tools enabled or not
		True — level reupload tools are enabled
		False — level reupload tools are disabled
	$msgEnabled — is messenger page on dashboard enabled or not
		True — messenger page is enabled
		False — messenger page is disabled
	$clansEnabled — are clans enabled or not
		True — clans are enabled
		False — clans are disabled
	$songEnabled — are song upload tools enabled or not
		Add 1 — upload songs with file is enabled
		Add 2 — upload songs via URL is enabled
	$sfxEnabled — is SFX upload tool enabled
		True — SFX upload tool is enabled
		False — SFX upload tool is disabled
		
	$songSize — maximum song size user can upload via file
	$sfxSize — maximum SFX size user can upload via file
	-- WARNING -- Be careful when increasing this value! If you make it too high, you will exceed size limit of converters below!
	
	$timeType — how time shows in-game
		0 — time, as in Cvolton's core (06/02/2024 22.18)
		1 — default dashboard time (depends on time since, today — 22;18, this year — 02.06, older — 02.06.2024)
		2 — time as in real Geometry Dash (2 weeks ago)
		
	$dashboardIcon — icon in dashboard's navbar, can be link
	$dashboardFavicon — icon in browser's title, can be link
	
	$preenableSongs — should core autoenable songs when uploading new one?
		True — enable new songs
		False — disable new songs (requires moderator input to enable)
	$preenableSongs — should core autoenable SFXs when uploading new one?
		True — enable new SFXs
		False — disable new SFXs (requires moderator input to enable)
		
	$clansTagPosition — how clan tag should display in-game
		%1$s — username
		%2$s — clan tag
		%1$s [%2$s] -> USERNAME [TAG], for example: Sa1ntSosetHui [GCS]
*/
$gdps = "GDPS";

$accentColor = "#93D7FF";

$lrEnabled = true;
$msgEnabled = true;
$clansEnabled = true;
$songEnabled = 12;
$sfxEnabled = true;

$songSize = 8;
$sfxSize = 4.5;

$timeType = 1;

$dashboardIcon = '/dashboard/icon.png';
$dashboardFavicon = '/dashboard/icon.png';

$preenableSongs = true;
$preenableSFXs = true;

$clansTagPosition = '%1$s [%2$s]';

/*
	Download links
	
	$downloadLinks[] = ['download name', 'download link'];
*/
//$downloadLinks[] = ['Windows', 'download/'.$gdps.'.zip'];
//$downloadLinks[] = ['Android', 'download/'.$gdps.'.apk'];
// $downloadLinks[] = ['Mac OS', 'download/'.$gdps.'.dmg'];
// $downloadLinks[] = ['iOS', 'download/'.$gdps.'.ipa'];

/*
	Footer social links
	
	$footer[] = ['title', 'link to social', 'icon url'];
*/

// $footer[] = ['', '', ''];

/*
	Third-party resources
	
	$thirdParty[] = ['icon url', 'username', 'social media link', 'what person did'];
*/
$thirdParty[] = ['https://yt3.googleusercontent.com/EZ149IVvU5JX2Fi6yH7R95NQmKdNsea_gggEvJXA0MIZQ397E_WHLLNCgBjL45npnMZNUkpq=s88-c-k-c0x00ffffff-no-rj', 'RobTop', 'https://store.steampowered.com/app/322170/Geometry_Dash/', 'For Geometry Dash'];
$thirdParty[] = ['https://avatars.githubusercontent.com/u/5721187', 'Cvolton', 'https://github.com/Cvolton', 'For GDPS code'];

/*
	Custom music and SFX libraries
	
	$customLibrary[] = ['library ID (number, must be unique)', 'library name', 'library link', 'library type'];
	Library types:
		0 — only SFX library
		1 — only music library
		1 — both
*/
$customLibrary[] = [1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net', 2]; 
$customLibrary[] = [2, 'GDPSFH', 'https://sfx.fhgdps.com', 0]; 
$customLibrary[] = [3, $gdps, null, 2]; // Your GDPS's library, don't remove it
$customLibrary[] = [4, 'Song File Hub', 'https://api.songfilehub.com', 1];

/*
	SFX converter API's
	https://github.com/M336G/audio-converter
	
	$convertSFXAPI[] = "link to converter";
*/
$convertSFXAPI[] = "https://m336-audio.gcs.icu"; // Will update APIs below on new core release
//$convertSFXAPI[] = "https://niko.gcs.icu";
//$convertSFXAPI[] = "https://lamb.gcs.icu";
//$convertSFXAPI[] = "https://omori.gcs.icu";
//$convertSFXAPI[] = "https://im.gcs.icu";
//$convertSFXAPI[] = "https://hat.gcs.icu";
//$convertSFXAPI[] = "https://converter.m336.dev";

/*
	Level reupload tool
	
	These confing will allow you to customize level reupload tool
	
	$requireAccountForReuploading — if user must enter their account credentials to reupload level
		True — require logging in
		False — don't require to login
	$disallowReuploadingNotUserLevels — if user should be allowed to reupload only their levels
		True — allow reuploading only their levels
		False — allow reuploading any levels
*/
$requireAccountForReuploading = false;
$disallowReuploadingNotUserLevels = false;

/*
	Cobalt API
	
	Use Cobalt API to be able to reupload songs with YouTube links and etc.
	Requires file upload to be enabled
	
	$useCobalt — Should server use Cobalt to reupload songs by links
		True — use Cobalt
		False — don't use Cobalt

	$cobaltAPI — link to Cobalt's APIs
		
	Turnstile-protected APIs are currently not supported, sorry
*/
$useCobalt = true;
$cobaltAPI = 'https://cobalt.gcs.icu';

/*
	Geometry Dash icons renderer Server
	
	Dashboard shows icons of players, therefore it requires some server to get icons
	
	$iconsRendererServer — what server to use
	
	If gdicon.oat.zone doesn't work for you for some reason, you can use icons.gcs.icu
*/
$iconsRendererServer = 'https://gdicon.oat.zone';

/*
	Account for level reuploads
 
	You can setup account for level reuploads, reuploaded levels will appear in this account

	$automaticID — should level reupload tool use player's account or one you setup
		True — levels will be reuploaded on player's account
		False — levels will be reuploaded on account you setup below
	
	$reuploadUserID — user ID of account for level reuploads
	$reuploadAccountID — account ID of account for level reuploads
*/
$automaticID = true;
$reuploadUserID = 0;
$reuploadAccountID = 0;
?>
