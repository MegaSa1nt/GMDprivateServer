<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/editorSound.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
ensureEditorSoundsTable($db);
$type = (int)($_POST['type'] ?? 2);
$page = max(0, (int)($_POST['page'] ?? 0));
$search = trim(ExploitPatch::charclean($_POST['str'] ?? ''));
$where = ['unlisted = 0'];
$params = [];
if($type === 0 && $search !== '') { $where[] = 'soundName LIKE :search'; $params[':search'] = '%' . $search . '%'; }
if($type === 5 && ctype_digit($search)) { $where[] = 'accountID = :creator'; $params[':creator'] = (int)$search; }
$order = match($type) { 1 => 'downloads DESC', 2 => 'likes DESC', 3 => 'updateDate DESC', default => 'uploadDate DESC' };
$count = $db->prepare('SELECT COUNT(*) FROM editor_sounds WHERE ' . implode(' AND ', $where));
$count->execute($params);
$total = (int)$count->fetchColumn();
$query = $db->prepare('SELECT * FROM editor_sounds WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order . ' LIMIT 10 OFFSET ' . ($page * 10));
$query->execute($params);
$packs = [];
$creators = [];
foreach($query->fetchAll(PDO::FETCH_ASSOC) as $sound) {
    $user = $db->prepare('SELECT userID,userName FROM users WHERE extID = :id LIMIT 1');
    $user->execute([':id' => $sound['accountID']]);
    $creator = $user->fetch(PDO::FETCH_ASSOC) ?: ['userID' => 0, 'userName' => 'Unknown'];
    $packs[] = editorSoundObject($sound, $creator['userName']);
    $creators[$sound['accountID']] = $creator['userID'] . ':' . $creator['userName'] . ':' . $sound['accountID'];
}
exit(implode('|', $packs) . '#' . implode('|', $creators) . '#' . $total . ':' . ($page * 10) . ':10#f5da5823d94bbe7208dd83a30ff427c7d88fdb99');
?>
