<?php
require_once __DIR__ . '/incl/lib/connection.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';

$levelID = ExploitPatch::number($_POST['levelID'] ?? 0);
if (!$levelID) exit('-1');
$query = $db->prepare('SELECT starStars, starFeatured, starEpic, starDemon, starAuto, starDifficulty FROM levels WHERE levelID = :levelID');
$query->execute([':levelID' => $levelID]);
$level = $query->fetch(PDO::FETCH_ASSOC);
if (!$level) exit('-1');
echo '1:' . (int)$level['starStars'] . ':2:' . (int)$level['starFeatured'] . ':3:' . (int)$level['starEpic'] . ':4:' . (int)$level['starDemon'] . ':5:' . (int)$level['starAuto'] . ':9:' . (int)$level['starDifficulty'];

