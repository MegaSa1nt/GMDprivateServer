<?php
function ensureEditorSoundsTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS editor_sounds (
        soundID INT NOT NULL AUTO_INCREMENT,
        soundName VARCHAR(255) NOT NULL,
        soundDesc TEXT NOT NULL,
        soundData LONGTEXT NOT NULL,
        accountID INT NOT NULL,
        version INT NOT NULL DEFAULT 1,
        downloads INT NOT NULL DEFAULT 0,
        likes INT NOT NULL DEFAULT 0,
        actions INT NOT NULL DEFAULT 0,
        sounds INT NOT NULL DEFAULT 0,
        emote VARCHAR(64) NOT NULL DEFAULT '',
        unlisted TINYINT NOT NULL DEFAULT 0,
        uploadDate INT NOT NULL,
        updateDate INT NOT NULL,
        PRIMARY KEY (soundID),
        KEY editor_sounds_account (accountID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function editorSoundObject(array $sound, string $username): string {
    return '1:' . (int)$sound['soundID']
        . ':2:' . $sound['soundName']
        . ':3:' . $sound['soundDesc']
        . ':5:' . (int)$sound['version']
        . ':49:' . (int)$sound['accountID']
        . ':50:' . $username
        . ':10:' . (int)$sound['downloads']
        . ':14:' . (int)$sound['likes']
        . ':19:0'
        . ':28:' . (int)$sound['uploadDate']
        . ':29:' . (int)$sound['updateDate']
        . ':59:' . (int)$sound['actions']
        . ':60:' . (int)$sound['sounds']
        . ':61:0'
        . ':64:' . $sound['emote']
        . ':51:' . $sound['soundData'];
}
?>
