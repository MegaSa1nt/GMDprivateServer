<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/modernData.php';

$accountID = GJPCheck::getAccountIDOrDie();
$levelID = ExploitPatch::number(modernRequestValue(['levelID', 'levelId']));
$deathData = modernRequestValue(['deathData', 'data', 'deaths']);
if (!$levelID || $deathData === '') exit('-1');
ensureModernDataTables($db);
$query = $db->prepare('INSERT INTO death_data (levelID, accountID, deathData, updatedAt) VALUES (:levelID, :accountID, :deathData, :updatedAt) ON DUPLICATE KEY UPDATE deathData = VALUES(deathData), updatedAt = VALUES(updatedAt)');
$query->execute([':levelID' => $levelID, ':accountID' => $accountID, ':deathData' => $deathData, ':updatedAt' => time()]);
echo '1';

