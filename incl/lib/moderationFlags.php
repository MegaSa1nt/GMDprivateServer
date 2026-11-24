<?php

function ensureModerationFlagsTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS moderation_flags (
        accountID BIGINT NOT NULL,
        flag VARCHAR(32) NOT NULL,
        active TINYINT NOT NULL DEFAULT 1,
        moderatorID BIGINT NOT NULL,
        updatedAt INT NOT NULL DEFAULT 0,
        PRIMARY KEY (accountID, flag)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

