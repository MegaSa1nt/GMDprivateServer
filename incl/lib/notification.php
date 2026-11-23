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
}

