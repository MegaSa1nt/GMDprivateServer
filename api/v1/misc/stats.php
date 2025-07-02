<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header("Access-Control-Allow-Headers: X-Requested-With");

require __DIR__."/../../../incl/lib/mainLib.php";

$fileExists = file_exists(__DIR__."/stats.json");
$lastUpdate = $fileExists ? filemtime(__DIR__."/stats.json") : 0;
$checkTime = time() - 3600; // 3600 seconds = 1 hour

if($checkTime < $lastUpdate) {
    $stats = json_decode(file_get_contents(__DIR__."/stats.json"));

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
        'total' => (int) $stats['dailies'] + (int) $stats['weeklies'] + (int) $stats['events'] + (int) $stats['gauntlets'] + (int) $stats['mapPacks'] + (int) $stats['lists'],
        'dailies' => (int) $stats['dailies'],
        'weeklies' => (int) $stats['weeklies'],
        'events' => (int) $stats['events'],
        'gauntlets' => (int) $stats['gauntlets'],
        'mapPacks' => (int) $stats['mapPacks'],
        'lists' => (int) $stats['lists']
    ],
    'songs' => [
        'total' => (int) $stats['newgroundsSongs'] + (int) $stats['reuploadedSongs'],
        'newgrounds' => (int) $stats['newgroundsSongs'],
        'reuploaded' => (int) $stats['reuploadedSongs'],
        'mostUsedSong' => [
            'songID' => (int) $stats['mostUsedSongID'],
            'author' => (string) $stats['mostUsedSongAuthor'],
            'name' => (string) $stats['mostUsedSongName'],
            'isReuploaded' => (bool) $stats['mostUsedSongIsReupload'],
            'levelsCount' => (int) $stats['mostUsedSongLevelsCount'],
            'size' => (double) $stats['mostUsedSongSize'],
        ]
    ],
    'downloads' => [
        'total' => (int) $stats['downloads'],
        'average' => ($stats['levels'] ? (double) ($stats['downloads'] / $stats['levels']) : 0)
    ],
    'objects' => [
        'total' => (int) $stats['objects'],
        'average' => ($stats['levels'] ? (double) ($stats['objects'] / $stats['levels']) : 0)
    ],
    'likes' => [
        'total' => (int) $stats['likes'],
        'average' => ($stats['levels'] ? (double) ($stats['likes'] / $stats['levels']) : 0)
    ],
    'dislikes' => [
        'total' => (int) $stats['dislikes'],
        'average' => ($stats['levels'] ? (double) ($stats['dislikes'] / $stats['levels']) : 0)
    ],
    'comments' => [
        'total' => (int) $stats['comments'] + (int) $stats['posts'] + (int) $stats['clanPosts'] + (int) $stats['postReplies'],
        'comments' => (int) $stats['comments'],
        'posts' => (int) $stats['posts'],
        'clanPosts' => (int) $stats['clanPosts'],
        'postReplies' => (int) $stats['postReplies']
    ],
    'gainedStars' => [
        'total' => (int) $stats['stars'],
        'average' => ($stats['users'] ? (double) ($stats['stars'] / $stats['users']) : 0)
    ],
    'gainedMoons' => [
        'total' => (int) $stats['moons'],
        'average' => ($stats['users'] ? (double) ($stats['moons'] / $stats['users']) : 0)
    ],
    'creatorPoints' => [
        'total' => (float) $stats['creatorPoints'],
        'average' => ($stats['users'] ? (double) ($stats['creatorPoints'] / $stats['users']) : 0)
    ],
    'bans' => [
        'total' => (int) $stats['allBans'],
        'active' => (int) $stats['activeBans'],
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

file_put_contents(__DIR__."/stats.json", json_encode($stats));
exit(json_encode(['success' => true, 'cached' => false, 'stats' => $stats]));
?>