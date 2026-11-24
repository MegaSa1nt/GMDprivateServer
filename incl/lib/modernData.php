<?php

function ensureModernDataTables(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS level_controls (
        levelID BIGINT NOT NULL PRIMARY KEY,
        accountID BIGINT NOT NULL,
        controls LONGTEXT NOT NULL,
        updatedAt INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS death_data (
        levelID BIGINT NOT NULL,
        accountID BIGINT NOT NULL,
        deathData LONGTEXT NOT NULL,
        updatedAt INT NOT NULL DEFAULT 0,
        PRIMARY KEY (levelID, accountID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function modernRequestValue(array $names, string $default = ''): string {
    foreach ($names as $name) {
        if (isset($_POST[$name])) return (string)$_POST[$name];
		if (isset($_GET[$name])) return (string)$_GET[$name];
    }
    return $default;
}

