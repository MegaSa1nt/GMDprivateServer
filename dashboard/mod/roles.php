<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";

$parameters = explode("/", Escape::text(isset($_GET['id']) ? $_GET['id'] : ""));
$pageBase = $parameters[0] ? "../../" : "../";

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("manageRolesTitle"), Dashboard::string("errorLoginRequired"), $pageBase));

if(!Library::checkPermission($person, "dashboardManageRoles")) exit(Dashboard::renderErrorPage(Dashboard::string("manageRolesTitle"), Dashboard::string("errorNoPermission"), $pageBase));

$dataArray = $rolesArray = [];
$rolesElementsString = '';

$personRolePriority = Library::getPersonRolePriority($person);

$roles = Library::getRoles($personRolePriority);
foreach($roles AS &$role) {
	$rolesArray[$role['roleID']] = $role;
	
	$rolesElementsString .= '<div class="levelStat" href="mod/roles/'.$role['roleID'].'" dashboard-loader-type="manage">
			<i class="fa-solid fa-square" style="color: rgb('.$role['commentColor'].')"></i>
			'.htmlspecialchars($role['roleName']).'
		</div>';
}
$dataArray['MANAGE_ROLES_ELEMENTS'] = $rolesElementsString;

if(!empty($parameters[0])) {
	$roleID = Escape::number($parameters[0]) ?: 'create';
	
	$additionalData = [
		'ROLE_ID' => '0',
		
		'ROLE_NAME' => '',
		'ROLE_COMMENTS_EXTRA_TEXT' => '',
		'ROLE_COLOR' => '#000000',
		'ROLE_PRIORITY' => '',
		'ROLE_MOD_BADGE_LEVEL' => '',
		'ROLE_MOD_BADGE_NAME' => '',
		'ROLE_IS_DEFAULT_VALUE' => 0,
		'ROLE_IS_DEFAULT_REMOVE_CHECK' => 'checked',
		
		'ROLE_PERMISSION_GAME_SUGGEST_LEVEL_VALUE' => 0,
		'ROLE_PERMISSION_GAME_RATE_LEVEL_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_DIFFICULTY_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_FEATURED_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_EPIC_VALUE' => 0,
		'ROLE_PERMISSION_GAME_DELETE_LEVEL_VALUE' => 0,
		'ROLE_PERMISSION_GAME_MOVE_LEVEL_VALUE' => 0,
		'ROLE_PERMISSION_GAME_RENAME_LEVEL_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_PASSWORD_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_DESCRIPTION_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_LEVEL_PRIVACY_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SHARE_CREATOR_POINTS_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_LEVEL_SONG_VALUE' => 0,
		'ROLE_PERMISSION_GAME_LOCK_LEVEL_COMMENTS_VALUE' => 0,
		'ROLE_PERMISSION_GAME_LOCK_LEVEL_UPDATING_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_LIST_LEVELS_VALUE' => 0,
		'ROLE_PERMISSION_GAME_DELETE_COMMENTS_VALUE' => 0,
		'ROLE_PERMISSION_GAME_VERIFY_COINS_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_DAILY_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_WEEKLY_VALUE' => 0,
		'ROLE_PERMISSION_GAME_SET_EVENT_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MODERATOR_TOOLS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_DELETE_LEADERBOARDS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_MAPPACKS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_GAUNTLETS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_SONGS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_ACCOUNTS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_LEVELS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_CLANS_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_AUTOMOD_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_ROLES_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_MANAGE_VAULT_CODES_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_BYPASS_MAINTENANCE_VALUE' => 0,
		'ROLE_PERMISSION_DASHBOARD_SET_ACCOUNT_ROLES_VALUE' => 0,
	];
	
	if(is_numeric($roleID)) {
		$modBadgeNames = [0 => Dashboard::string("none"), 1 => Dashboard::string("moderatorRole"), 2 => Dashboard::string("elderModeratorRole"), 3 => Dashboard::string("leaderboardModeratorRole")];
		
		$role = $rolesArray[$roleID];
		if(!$role) exit(Dashboard::renderErrorPage(Dashboard::string("manageRolesTitle"), Dashboard::string("errorRoleNotFound"), '../../'));
		
		$roleColor = Library::convertRGBToHEX($role['commentColor']);
		
		$additionalData = [
			'ROLE_ID' => $roleID,
		
			'ROLE_NAME' => htmlspecialchars($role['roleName']),
			'ROLE_COMMENTS_EXTRA_TEXT' => htmlspecialchars($role['commentsExtraText']),
			'ROLE_COLOR' => $roleColor,
			'ROLE_PRIORITY' => $role['priority'],
			'ROLE_MOD_BADGE_LEVEL' => $role['modBadgeLevel'],
			'ROLE_MOD_BADGE_NAME' => $modBadgeNames[$role['modBadgeLevel']] ?: $modBadgeNames[0],
			'ROLE_IS_DEFAULT_VALUE' => $role["isDefault"] ? 1 : 0,
			'ROLE_IS_DEFAULT_REMOVE_CHECK' => !$role["isDefault"] ? 'checked' : '',
			
			'ROLE_PERMISSION_GAME_SUGGEST_LEVEL_VALUE' => $role['gameSuggestLevel'],
			'ROLE_PERMISSION_GAME_RATE_LEVEL_VALUE' => $role['gameRateLevel'],
			'ROLE_PERMISSION_GAME_SET_DIFFICULTY_VALUE' => $role['gameSetDifficulty'],
			'ROLE_PERMISSION_GAME_SET_FEATURED_VALUE' => $role['gameSetFeatured'],
			'ROLE_PERMISSION_GAME_SET_EPIC_VALUE' => $role['gameSetEpic'],
			'ROLE_PERMISSION_GAME_DELETE_LEVEL_VALUE' => $role['gameDeleteLevel'],
			'ROLE_PERMISSION_GAME_MOVE_LEVEL_VALUE' => $role['gameMoveLevel'],
			'ROLE_PERMISSION_GAME_RENAME_LEVEL_VALUE' => $role['gameRenameLevel'],
			'ROLE_PERMISSION_GAME_SET_PASSWORD_VALUE' => $role['gameSetPassword'],
			'ROLE_PERMISSION_GAME_SET_DESCRIPTION_VALUE' => $role['gameSetDescription'],
			'ROLE_PERMISSION_GAME_SET_LEVEL_PRIVACY_VALUE' => $role['gameSetLevelPrivacy'],
			'ROLE_PERMISSION_GAME_SHARE_CREATOR_POINTS_VALUE' => $role['gameShareCreatorPoints'],
			'ROLE_PERMISSION_GAME_SET_LEVEL_SONG_VALUE' => $role['gameSetLevelSong'],
			'ROLE_PERMISSION_GAME_LOCK_LEVEL_COMMENTS_VALUE' => $role['gameLockLevelComments'],
			'ROLE_PERMISSION_GAME_LOCK_LEVEL_UPDATING_VALUE' => $role['gameLockLevelUpdating'],
			'ROLE_PERMISSION_GAME_SET_LIST_LEVELS_VALUE' => $role['gameSetListLevels'],
			'ROLE_PERMISSION_GAME_DELETE_COMMENTS_VALUE' => $role['gameDeleteComments'],
			'ROLE_PERMISSION_GAME_VERIFY_COINS_VALUE' => $role['gameVerifyCoins'],
			'ROLE_PERMISSION_GAME_SET_DAILY_VALUE' => $role['gameSetDaily'],
			'ROLE_PERMISSION_GAME_SET_WEEKLY_VALUE' => $role['gameSetWeekly'],
			'ROLE_PERMISSION_GAME_SET_EVENT_VALUE' => $role['gameSetEvent'],
			'ROLE_PERMISSION_DASHBOARD_MODERATOR_TOOLS_VALUE' => $role['dashboardModeratorTools'],
			'ROLE_PERMISSION_DASHBOARD_DELETE_LEADERBOARDS_VALUE' => $role['dashboardDeleteLeaderboards'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_MAPPACKS_VALUE' => $role['dashboardManageMapPacks'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_GAUNTLETS_VALUE' => $role['dashboardManageGauntlets'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_SONGS_VALUE' => $role['dashboardManageSongs'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_ACCOUNTS_VALUE' => $role['dashboardManageAccounts'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_LEVELS_VALUE' => $role['dashboardManageLevels'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_CLANS_VALUE' => $role['dashboardManageClans'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_AUTOMOD_VALUE' => $role['dashboardManageAutomod'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_ROLES_VALUE' => $role['dashboardManageRoles'],
			'ROLE_PERMISSION_DASHBOARD_MANAGE_VAULT_CODES_VALUE' => $role['dashboardManageVaultCodes'],
			'ROLE_PERMISSION_DASHBOARD_BYPASS_MAINTENANCE_VALUE' => $role['dashboardBypassMaintenance'],
			'ROLE_PERMISSION_DASHBOARD_SET_ACCOUNT_ROLES_VALUE' => $role['dashboardSetAccountRoles'],
		];
	}
	
	$dataArray['MANAGE_ROLES_PAGE'] = Dashboard::renderTemplate("manage/role", $additionalData);
} else {
	$dataArray['MANAGE_ROLES_PAGE'] = Dashboard::renderTemplate("components/chooseRole", []);
}

exit(Dashboard::renderPage("mod/roles", Dashboard::string("manageRolesTitle"), $pageBase, $dataArray));
?>