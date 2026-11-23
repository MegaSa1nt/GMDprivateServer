<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/editorSound.php';
$accountID = GJPCheck::getAccountIDOrDie();
ensureEditorSoundsTable($db);
$name = trim(mb_substr(ExploitPatch::rucharclean($_POST['soundName'] ?? ''), 0, 255));
$data = trim((string)($_POST['soundData'] ?? ''));
$desc = trim((string)($_POST['soundDesc'] ?? ''));
$emote = trim(mb_substr(ExploitPatch::charclean($_POST['emote'] ?? ''), 0, 64));
$soundID = (int)($_POST['soundID'] ?? 0);
if($name === '' || $data === '') exit('-1');
$now = time();
if($soundID > 0) {
    $query = $db->prepare('UPDATE editor_sounds SET soundName=:name,soundDesc=:desc,soundData=:data,emote=:emote,actions=:actions,sounds=:sounds,version=version+1,updateDate=:date WHERE soundID=:id AND accountID=:account');
    $query->execute([':name'=>$name,':desc'=>$desc,':data'=>$data,':emote'=>$emote,':actions'=>(int)($_POST['actionCount']??0),':sounds'=>(int)($_POST['soundCount']??0),':date'=>$now,':id'=>$soundID,':account'=>$accountID]);
    if(!$query->rowCount()) exit('-1');
    exit((string)$soundID);
}
$query = $db->prepare('INSERT INTO editor_sounds (soundName,soundDesc,soundData,accountID,actions,sounds,emote,unlisted,uploadDate,updateDate) VALUES (:name,:desc,:data,:account,:actions,:sounds,:emote,:unlisted,:date,:date)');
$query->execute([':name'=>$name,':desc'=>$desc,':data'=>$data,':account'=>$accountID,':actions'=>(int)($_POST['actionCount']??0),':sounds'=>(int)($_POST['soundCount']??0),':emote'=>$emote,':unlisted'=>(int)($_POST['unlisted']??0),':date'=>$now]);
exit((string)$db->lastInsertId());
?>
