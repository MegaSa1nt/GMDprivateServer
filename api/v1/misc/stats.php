<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header("Access-Control-Allow-Headers: X-Requested-With");

require __DIR__ . "/../../../incl/lib/mainLib.php";

$fileExists = file_exists('stats.json');
$lastUpdate = $fileExists ? filemtime('stats.json') : 0;
$checkTime = time() - 3600; // 3600 seconds = 1 hour

if ($checkTime < $lastUpdate) {
    $isCached = true;
    $stats = json_decode(file_get_contents('stats.json'));

    exit(json_encode(['success' => true, 'cached' => true, 'stats' => $stats]));
}

$stats = Library::getStats();
$stats = [
    'users' => [
        'total' => (int) $stats['users'],
        'active' => (int) $stats['activeUsers']
    ],
    'levels' => [
        'total' => (int) $stats['levels'],
        'rated' => (int) $stats['ratedLevels'],
        'featured' => (int) $stats['featuredLevels'],
        'epic' => (int) $stats['epicLevels'],
        'legendary' => (int) $stats['legendaryLevels'],
        'mythic' => (int) $stats['mythicLevels']
    ],
    'special' => [
        'total' => (int) $stats['dailies'] + (int) $stats['weeklies'] + (int) $stats['gauntlets'] + (int) $stats['mapPacks'] + (int) $stats['lists'],
        'dailies' => (int) $stats['dailies'],
        'weeklies' => (int) $stats['weeklies'],
        'gauntlets' => (int) $stats['gauntlets'],
        'map_packs' => (int) $stats['mapPacks'],
        'lists' => (int) $stats['lists']
    ],
    'songs' => [
        'total' => (int) $stats['songs'],
        'newgrounds' => (int) $stats['newgroundsSongs'],
        'reuploaded' => (int) $stats['reuploadedSongs'],
        'mostUsedSong' => [
            'name' => (string) $stats['mostUsedSongName'],
            'levelsCount' => (int) $stats['mostUsedSongLevelsCount'],
        ]
    ],
    'downloads' => [
        'total' => (int) $stats['downloads'],
        'average' => (double) ($stats['downloads'] / $stats['levels'])
    ],
    'objects' => [
        'total' => (int) $stats['objects'],
        'average' => (double) ($stats['objects'] / $stats['levels'])
    ],
    'likes' => [
        'total' => (int) $stats['likes'],
        'average' => (double) ($stats['likes'] / $stats['levels'])
    ],
    'comments' => [
        'total' => (int) $stats['totalComments'],
        'comments' => (int) $stats['comments'],
        'posts' => (int) $stats['posts'],
        'post_replies' => (int) $stats['postReplies']
    ],
    'gained_stars' => [
        'total' => (int) $stats['stars'],
        'average' => (double) ($stats['stars'] / $stats['users'])
    ],
    'creator_points' => [
        'total' => (float) $stats['creatorPoints'],
        'average' => (double) ($stats['creatorPoints'] / $stats['users'])
    ],
    'bans' => [
        'total' => (int) $stats['bannedPlayers'],
        'personTypes' => [
            'accountIDBans' => (int) $stats['accountIDBans'],
            'userIDBans' => (int) $stats['userIDBans'],
            'IPBans' => (int) $stats['IPBans']
        ],
        'banTypes' => [
            'leaderboardBans' => (int) $stats['leaderboardBans'],
            'creatorBans' => (int) $stats['creatorBans'],
            'levelUploadBans' => (int) $stats['levelUploadBans'],
            'commentBans' => (int) $stats['commentBans'],
            'accountBans' => (int) $stats['accountBans']
        ]
    ]
];

file_put_contents('stats.json', json_encode($stats));
exit(json_encode(['success' => true, 'cached' => false, 'stats' => $stats]));
?>