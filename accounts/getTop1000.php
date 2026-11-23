<?php
require __DIR__ . '/../incl/lib/connection.php';
header('Content-Type: text/html; charset=utf-8');
echo '<table><thead><tr><th>Rank</th><th>AccountID</th><th>Username</th><th>Stars</th><th>Diamonds</th><th>Demons</th><th>User Coins</th><th>Coins</th></tr></thead><tbody>';
$rank = 0;
$query = $db->query('SELECT accounts.accountID, users.userName, users.stars, users.diamonds, users.demons, users.userCoins, users.coins FROM accounts INNER JOIN users ON users.extID = accounts.accountID WHERE users.isRegistered = 1 ORDER BY users.stars DESC, users.extID ASC LIMIT 1000');
foreach($query as $user) {
    $rank++;
    echo '<tr><td>' . $rank . '</td><td>' . (int)$user['accountID'] . '</td><td>' . htmlspecialchars($user['userName'], ENT_QUOTES, 'UTF-8') . '</td><td>' . (int)$user['stars'] . '</td><td>' . (int)$user['diamonds'] . '</td><td>' . (int)$user['demons'] . '</td><td>' . (int)$user['userCoins'] . '</td><td>' . (int)$user['coins'] . '</td></tr>';
}
echo '</tbody></table>';
?>
