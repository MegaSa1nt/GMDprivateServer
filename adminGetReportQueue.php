<?php
require_once __DIR__ . '/incl/lib/connection.php';
require_once __DIR__ . '/incl/lib/GJPCheck.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';

$accountID = GJPCheck::getAccountIDOrDie();
$admin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$admin->execute([':accountID' => $accountID]);
if (!(int)$admin->fetchColumn()) exit('-1');

$page = max(0, (int)($_POST['page'] ?? 0));
$sort = (int)($_POST['sort'] ?? 0);
$mode = (int)($_POST['mode'] ?? 0);
$pageSize = 20;

$query = $db->query('SELECT ID, targetID, type, reasons FROM reports ORDER BY ID DESC');
$groups = [];
foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $report) {
    // The current report store has no registered-reporter column, so group
    // reports by the account associated with the reported item.
    $reportedAccountID = 0;
    if ((int)$report['type'] === 0) {
        $reportedAccountID = (int)$report['targetID'];
    } elseif ((int)$report['type'] === 1) {
        $level = $db->prepare('SELECT userID FROM levels WHERE levelID = :levelID');
        $level->execute([':levelID' => (int)$report['targetID']]);
        $userID = $level->fetchColumn();
        if ($userID !== false) {
            $user = $db->prepare('SELECT extID FROM users WHERE userID = :userID');
            $user->execute([':userID' => $userID]);
            $reportedAccountID = (int)$user->fetchColumn();
        }
    }
    if (!$reportedAccountID) continue;
    if (!isset($groups[$reportedAccountID])) {
        $groups[$reportedAccountID] = [
            'count' => 0,
            'reasonCounts' => array_fill(0, 8, 0),
            'lastID' => 0,
        ];
    }
    $groups[$reportedAccountID]['count']++;
    $groups[$reportedAccountID]['lastID'] = max($groups[$reportedAccountID]['lastID'], (int)$report['ID']);
    foreach (explode(',', (string)$report['reasons']) as $reason) {
        $reason = (int)$reason;
        if ($reason >= 0 && $reason < 8) $groups[$reportedAccountID]['reasonCounts'][$reason]++;
    }
}

if ($sort === 1) uasort($groups, fn($a, $b) => $b['lastID'] <=> $a['lastID']);
else uasort($groups, fn($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['lastID'] <=> $a['lastID']));

$total = count($groups);
$entries = array_slice($groups, $page * $pageSize, $pageSize, true);
$output = [];
foreach ($entries as $reportedAccountID => $group) {
    $user = $db->prepare('SELECT userName FROM accounts WHERE accountID = :accountID');
    $user->execute([':accountID' => $reportedAccountID]);
    $username = $user->fetchColumn();
    if ($username === false) continue;
    arsort($group['reasonCounts']);
    $topReason = (int)array_key_first($group['reasonCounts']);
    // Full report queue layout (20 fields), with unknown/flag fields kept at
    // their documented neutral values.
    $output[] = implode(':', [
        $reportedAccountID,
        ExploitPatch::url_base64_encode($username),
        $group['count'], 4, 0, $topReason, 7, '0 seconds', 0,
        0, '', '', 0, 0, 0, 0, 0, 0, 0, $group['count'],
    ]);
}
echo implode('|', $output) . '#' . $total . ':' . ($page * $pageSize) . ':' . $pageSize;

