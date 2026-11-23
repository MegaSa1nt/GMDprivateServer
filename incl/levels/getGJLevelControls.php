<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/modernData.php';

$levelID = ExploitPatch::number(modernRequestValue(['levelID', 'levelId']));
if (!$levelID) exit('-1');
ensureModernDataTables($db);
$query = $db->prepare('SELECT controls FROM level_controls WHERE levelID = :levelID');
$query->execute([':levelID' => $levelID]);
$controls = $query->fetchColumn();
if ($controls === false) exit('-1');
echo $controls;

