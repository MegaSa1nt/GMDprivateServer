<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));
$accountID = $person['accountID'];

if(Library::checkPermission($person, "dashboardManageRoles") && isset($_POST['roleID']) && isset($_POST['roleName']) && isset($_POST['roleColor']) && isset($_POST['rolePriority']) && isset($_POST['modBadgeLevel'])) {
	$roleID = Escape::number($_POST['roleID']);
	
	$personRolePriority = Library::getPersonRolePriority($person);
	
	$role = Library::getRoleByID($roleID);
	if($roleID && (!$role || $role['priority'] >= $personRolePriority)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorRoleNotFound"), "error"));
	
	$newRoleName = trim(Escape::text($_POST['roleName'], 40));
	if(!$newRoleName) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	if(Security::checkFilterViolation($person, $newRoleName, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
	
	$newRoleCommentsCaption = trim(Escape::latin($_POST['roleCommentsExtraText'], 30)) ?: '';
	$newRoleColor = Library::convertHEXToRBG(Escape::latin_no_spaces($_POST['roleColor']) ?: '000000') ?: '000,000,000';
	$newRolePriority = abs(Escape::number($_POST['rolePriority']) ?: 0);
	$newModBadge = Security::limitValue(0, abs(Escape::number($_POST['modBadgeLevel']) ?: 0), 3);
	$newRoleIsDefault = isset($_POST['roleIsDefault']) ? 1 : 0;
	
	$newGameSuggestLevel = Security::limitValue(0, abs(Escape::number($_POST['gameSuggestLevel']) ?: 0), 2);
	$newGameRateLevel = Security::limitValue(0, abs(Escape::number($_POST['gameRateLevel']) ?: 0), 2);
	$newGameSetDifficulty = Security::limitValue(0, abs(Escape::number($_POST['gameSetDifficulty']) ?: 0), 2);
	$newGameSetFeatured = Security::limitValue(0, abs(Escape::number($_POST['gameSetFeatured']) ?: 0), 2);
	$newGameSetEpic = Security::limitValue(0, abs(Escape::number($_POST['gameSetEpic']) ?: 0), 2);
	$newGameDeleteLevel = Security::limitValue(0, abs(Escape::number($_POST['gameDeleteLevel']) ?: 0), 2);
	$newGameMoveLevel = Security::limitValue(0, abs(Escape::number($_POST['gameMoveLevel']) ?: 0), 2);
	$newGameRenameLevel = Security::limitValue(0, abs(Escape::number($_POST['gameRenameLevel']) ?: 0), 2);
	$newGameSetPassword = Security::limitValue(0, abs(Escape::number($_POST['gameSetPassword']) ?: 0), 2);
	$newGameSetDescription = Security::limitValue(0, abs(Escape::number($_POST['gameSetDescription']) ?: 0), 2);
	$newGameSetLevelPrivacy = Security::limitValue(0, abs(Escape::number($_POST['gameSetLevelPrivacy']) ?: 0), 2);
	$newGameShareCreatorPoints = Security::limitValue(0, abs(Escape::number($_POST['gameShareCreatorPoints']) ?: 0), 2);
	$newGameSetLevelSong = Security::limitValue(0, abs(Escape::number($_POST['gameSetLevelSong']) ?: 0), 2);
	$newGameLockLevelComments = Security::limitValue(0, abs(Escape::number($_POST['gameLockLevelComments']) ?: 0), 2);
	$newGameLockLevelUpdating = Security::limitValue(0, abs(Escape::number($_POST['gameLockLevelUpdating']) ?: 0), 2);
	$newGameSetListLevels = Security::limitValue(0, abs(Escape::number($_POST['gameSetListLevels']) ?: 0), 2);
	$newGameDeleteComments = Security::limitValue(0, abs(Escape::number($_POST['gameDeleteComments']) ?: 0), 2);
	$newGameVerifyCoins = Security::limitValue(0, abs(Escape::number($_POST['gameVerifyCoins']) ?: 0), 2);
	$newGameSetDaily = Security::limitValue(0, abs(Escape::number($_POST['gameSetDaily']) ?: 0), 2);
	$newGameSetWeekly = Security::limitValue(0, abs(Escape::number($_POST['gameSetWeekly']) ?: 0), 2);
	$newGameSetEvent = Security::limitValue(0, abs(Escape::number($_POST['gameSetEvent']) ?: 0), 2);
	$newDashboardModeratorTools = Security::limitValue(0, abs(Escape::number($_POST['dashboardModeratorTools']) ?: 0), 2);
	$newDashboardDeleteLeaderboards = Security::limitValue(0, abs(Escape::number($_POST['dashboardDeleteLeaderboards']) ?: 0), 2);
	$newDashboardManageMapPacks = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageMapPacks']) ?: 0), 2);
	$newDashboardManageGauntlets = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageGauntlets']) ?: 0), 2);
	$newDashboardManageSongs = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageSongs']) ?: 0), 2);
	$newDashboardManageAccounts = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageAccounts']) ?: 0), 2);
	$newDashboardManageLevels = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageLevels']) ?: 0), 2);
	$newDashboardManageClans = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageClans']) ?: 0), 2);
	$newDashboardManageAutomod = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageAutomod']) ?: 0), 2);
	$newDashboardManageRoles = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageRoles']) ?: 0), 2);
	$newDashboardManageVaultCodes = Security::limitValue(0, abs(Escape::number($_POST['dashboardManageVaultCodes']) ?: 0), 2);
	$newDashboardBypassMaintenance = Security::limitValue(0, abs(Escape::number($_POST['dashboardBypassMaintenance']) ?: 0), 2);
	$newDashboardSetAccountRoles = Security::limitValue(0, abs(Escape::number($_POST['dashboardSetAccountRoles']) ?: 0), 2);
	
	$rolePermissions = [
		'gameSuggestLevel' => $newGameSuggestLevel,
		'gameRateLevel' => $newGameRateLevel,
		'gameSetDifficulty' => $newGameSetDifficulty,
		'gameSetFeatured' => $newGameSetFeatured,
		'gameSetEpic' => $newGameSetEpic,
		'gameDeleteLevel' => $newGameDeleteLevel,
		'gameMoveLevel' => $newGameMoveLevel,
		'gameRenameLevel' => $newGameRenameLevel,
		'gameSetPassword' => $newGameSetPassword,
		'gameSetDescription' => $newGameSetDescription,
		'gameSetLevelPrivacy' => $newGameSetLevelPrivacy,
		'gameShareCreatorPoints' => $newGameShareCreatorPoints,
		'gameSetLevelSong' => $newGameSetLevelSong,
		'gameLockLevelComments' => $newGameLockLevelComments,
		'gameLockLevelUpdating' => $newGameLockLevelUpdating,
		'gameSetListLevels' => $newGameSetListLevels,
		'gameDeleteComments' => $newGameDeleteComments,
		'gameVerifyCoins' => $newGameVerifyCoins,
		'gameSetDaily' => $newGameSetDaily,
		'gameSetWeekly' => $newGameSetWeekly,
		'gameSetEvent' => $newGameSetEvent,
		'dashboardModeratorTools' => $newDashboardModeratorTools,
		'dashboardDeleteLeaderboards' => $newDashboardDeleteLeaderboards,
		'dashboardManageMapPacks' => $newDashboardManageMapPacks,
		'dashboardManageGauntlets' => $newDashboardManageGauntlets,
		'dashboardManageSongs' => $newDashboardManageSongs,
		'dashboardManageAccounts' => $newDashboardManageAccounts,
		'dashboardManageLevels' => $newDashboardManageLevels,
		'dashboardManageClans' => $newDashboardManageClans,
		'dashboardManageAutomod' => $newDashboardManageAutomod,
		'dashboardManageRoles' => $newDashboardManageRoles,
		'dashboardManageVaultCodes' => $newDashboardManageVaultCodes,
		'dashboardBypassMaintenance' => $newDashboardBypassMaintenance,
		'dashboardSetAccountRoles' => $newDashboardSetAccountRoles
	];
	
	$newRoleID = Library::changeRole($person, $roleID, $newRoleName, $newRoleCommentsCaption, $newRoleColor, $newRolePriority, $newModBadge, $newRoleIsDefault, $rolePermissions);
	
	if(!$roleID) exit(Dashboard::renderToast("check", Dashboard::string("successCreatedRole"), "success", 'mod/roles/'.$newRoleID, "manage"));
	else exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "manage"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>