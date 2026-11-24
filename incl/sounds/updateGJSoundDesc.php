<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/editorSound.php';
ensureEditorSoundsTable($db);
$accountID = GJPCheck::getAccountIDOrDie();
$id = (int)($_POST['soundID'] ?? $_POST['listID'] ?? 0);
$desc = trim((string)($_POST['soundDesc'] ?? ''));
$query = $db->prepare('UPDATE editor_sounds SET soundDesc=:desc,updateDate=:date WHERE soundID=:id AND accountID=:account');
$query->execute([':desc'=>$desc,':date'=>time(),':id'=>$id,':account'=>$accountID]);
exit($query->rowCount() ? '1' : '-1');
?>
