<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/modernData.php';

$accountID = GJPCheck::getAccountIDOrDie();
$levelID = ExploitPatch::number(modernRequestValue(['levelID', 'levelId']));
$controls = modernRequestValue(['controls', 'levelControls', 'data']);
if (!$levelID || $controls === '') exit('-1');

ensureModernDataTables($db);
$query = $db->prepare('SELECT userID FROM levels WHERE levelID = :levelID');
$query->execute([':levelID' => $levelID]);
$ownerUserID = $query->fetchColumn();
if (!$ownerUserID) exit('-1');
$query = $db->prepare('SELECT extID FROM users WHERE userID = :userID');
$query->execute([':userID' => $ownerUserID]);
$ownerAccountID = (int)$query->fetchColumn();
if ($ownerAccountID !== $accountID && !$db->query('SELECT 1 FROM accounts WHERE accountID = ' . (int)$accountID . ' AND isAdmin = 1')->fetchColumn()) exit('-1');

$query = $db->prepare('INSERT INTO level_controls (levelID, accountID, controls, updatedAt) VALUES (:levelID, :accountID, :controls, :updatedAt) ON DUPLICATE KEY UPDATE accountID = VALUES(accountID), controls = VALUES(controls), updatedAt = VALUES(updatedAt)');
$query->execute([':levelID' => $levelID, ':accountID' => $accountID, ':controls' => $controls, ':updatedAt' => time()]);
echo '1';

