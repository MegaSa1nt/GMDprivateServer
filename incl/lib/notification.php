<?php

function ensureNotificationsTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        notificationID BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        targetAccountID BIGINT NOT NULL,
        icon VARCHAR(64) NOT NULL DEFAULT 'keydemon',
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        notificationTime VARCHAR(64) NOT NULL DEFAULT '',
        isNew TINYINT NOT NULL DEFAULT 1,
        achievementID VARCHAR(64) NOT NULL DEFAULT '',
        hasChest TINYINT NOT NULL DEFAULT 0,
        chestType INT NOT NULL DEFAULT 0,
        claimed TINYINT NOT NULL DEFAULT 0,
        createdAt INT NOT NULL DEFAULT 0,
        KEY targetAccountID (targetAccountID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['rewardItem' => 'INT NOT NULL DEFAULT 0', 'rewardAmount' => 'INT NOT NULL DEFAULT 0'] as $column => $definition) {
        $check = $db->query("SHOW COLUMNS FROM notifications LIKE " . $db->quote($column));
        if (!$check->fetch()) $db->exec("ALTER TABLE notifications ADD COLUMN `$column` $definition");
    }
    $db->exec("CREATE TABLE IF NOT EXISTS notification_rewards (
        accountID BIGINT NOT NULL PRIMARY KEY,
        demonKeys INT NOT NULL DEFAULT 0,
        updatedAt INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function applyPendingDemonKeysToSave(PDO $db, int $accountID, string $encoded): string {
    ensureNotificationsTable($db);
    $query = $db->prepare('SELECT demonKeys FROM notification_rewards WHERE accountID = :accountID');
    $query->execute([':accountID' => $accountID]);
    $keys = (int)$query->fetchColumn();
    if ($keys <= 0) return $encoded;
    $parts = explode(';', $encoded, 2);
    $xml = @gzdecode(ExploitPatch::url_base64_decode($parts[0]));
    if ($xml === false || $xml === '') return $encoded;
    if (preg_match('/(<k>GS_value<\\/k><d>)(.*?)(<\\/d>)/s', $xml, $match, PREG_OFFSET_CAPTURE)) {
        $body = $match[2][0];
        if (preg_match('/<k>21<\\/k><i>(\\d+)<\\/i>/', $body, $stat)) {
            $body = preg_replace('/<k>21<\\/k><i>\\d+<\\/i>/', '<k>21</k><i>' . ((int)$stat[1] + $keys) . '</i>', $body, 1);
        } else $body .= '<k>21</k><i>' . $keys . '</i>';
        $xml = substr($xml, 0, $match[2][1]) . $body . substr($xml, $match[3][1]);
        $parts[0] = ExploitPatch::url_base64_encode(gzencode($xml));
        $db->prepare('DELETE FROM notification_rewards WHERE accountID = :accountID')->execute([':accountID' => $accountID]);
        return implode(';', $parts);
    }
    return $encoded;
}

