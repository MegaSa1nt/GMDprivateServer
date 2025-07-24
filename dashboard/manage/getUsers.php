<?php
require_once __DIR__."/../incl/dashboardLib.php";
require __DIR__."/../".$dbPath."config/dashboard.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";

$users = [];

if(isset($_GET['search'])) {
	$search = trim(Escape::text($_GET['search']));
	if(empty($search)) exit(json_encode([]));
	
	$usersArray = Library::getUsers($search, 0);
	if(!$usersArray['users']) exit(json_encode([]));
	
	foreach($usersArray['users'] AS &$user) {
		$userMetadata = Dashboard::getUserMetadata($user);
		
		$users[] = [
			'ID' => $user['extID'],
			'name' => htmlspecialchars($user['userName']),
			'icon' => '<img loading="lazy" src="'.$userMetadata['mainIcon'].'"></img>',
			'elementAfter' => '<span class="difficultyButton" title="'.($user['isRegistered'] ? Dashboard::string("registeredPlayer") : Dashboard::string("unregisteredPlayer")).'"><i class="fa-'.($user['isRegistered'] ? 'solid' : 'regular').' fa-user"></i></span>',
			'attributes' => $userMetadata['userAttributes']
		];
	}
	
	exit(json_encode($users));
}

exit(json_encode([]));
?>