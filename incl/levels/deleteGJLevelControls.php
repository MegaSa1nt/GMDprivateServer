<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/modernData.php';

$accountID = GJPCheck::getAccountIDOrDie();
$levelID = ExploitPatch::number(modernRequestValue(['levelID', 'levelId']));
if (!$levelID) exit('-1');
ensureModernDataTables($db);
$query = $db->prepare('SELECT accountID FROM level_controls WHERE levelID = :levelID');
$query->execute([':levelID' => $levelID]);
$owner = $query->fetchColumn();
if ($owner === false) exit('-1');
$isAdmin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$isAdmin->execute([':accountID' => $accountID]);
if ((int)$owner !== $accountID && !(int)$isAdmin->fetchColumn()) exit('-1');
$query = $db->prepare('DELETE FROM level_controls WHERE levelID = :levelID');
$query->execute([':levelID' => $levelID]);
echo '1';

