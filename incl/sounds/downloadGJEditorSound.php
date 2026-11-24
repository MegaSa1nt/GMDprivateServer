<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/editorSound.php';
ensureEditorSoundsTable($db);
$id = (int)($_POST['soundID'] ?? 0);
$query = $db->prepare('SELECT * FROM editor_sounds WHERE soundID=:id AND unlisted=0');
$query->execute([':id'=>$id]);
$sound = $query->fetch(PDO::FETCH_ASSOC);
if(!$sound) exit('-1');
$user = $db->prepare('SELECT userName FROM users WHERE extID=:id LIMIT 1');
$user->execute([':id'=>$sound['accountID']]);
$username = $user->fetchColumn() ?: 'Unknown';
$db->prepare('UPDATE editor_sounds SET downloads=downloads+1 WHERE soundID=:id')->execute([':id'=>$id]);
exit(editorSoundObject($sound, $username) . '#f5da5823d94bbe7208dd83a30ff427c7d88fdb99');
?>
