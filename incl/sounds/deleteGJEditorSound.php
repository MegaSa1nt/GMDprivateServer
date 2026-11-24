<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/editorSound.php';
ensureEditorSoundsTable($db);
$accountID = GJPCheck::getAccountIDOrDie();
$id = (int)($_POST['soundID'] ?? $_POST['listID'] ?? 0);
$query = $db->prepare('DELETE FROM editor_sounds WHERE soundID=:id AND accountID=:account');
$query->execute([':id'=>$id,':account'=>$accountID]);
exit($query->rowCount() ? '1' : '-1');
?>
