<?php
header("X-Content-Type-Options: nosniff");

require_once "../incl/lib/connection.php";
require_once "../config/dashboard.php";
require "../incl/lib/exploitPatch.php";

$file = basename($_GET["request"] ?? "");

switch ($file) {
    case "news.dat":
        $info = $db->query("SELECT MAX(entryDate), COUNT(*), MAX(entryID) FROM news");
        [$time, $count, $maxID] = $info->fetch(PDO::FETCH_NUM);

        header("Content-Type: application/octet-stream");

        // This will display a preview news entry
        if (empty($time)) {
            $payload = "1,1|1:1;2:" . ExploitPatch::url_base64_encode("January 1, 1970") . ";3:" . ExploitPatch::url_base64_encode("Welcome to " . $gdps) . ";4:0," . ExploitPatch::url_base64_encode("Here, moderators of your GDPS can publish useful information for its playerbase! :eyes:");
            $dat = ExploitPatch::url_base64_encode(gzencode($payload));
            header("Content-Length: " . strlen($dat));
            echo $dat;
            break;
        }

        $version = strtotime($time);

        // (Re)generate news.dat
        $news = $db->query("SELECT * FROM news ORDER BY entryDate DESC, entryID DESC");
        $news = $news->fetchAll();

        $entries = [];
        foreach ($news as $entry) {
            $entries[] = implode(";", [
                "1:" . $entry["entryID"],
                "2:" . ExploitPatch::url_base64_encode(date("F j, Y", strtotime($entry["entryDate"]))),
                "3:" . $entry["entryTitle"],
                "4:" . $entry["entryElements"],
            ]);
        }
        $payload = $version . "," . count($news) . "|" . implode("|", $entries);
        $dat = ExploitPatch::url_base64_encode(gzencode($payload));

        file_put_contents("news.dat", $dat, LOCK_EX);
        file_put_contents("news_version.txt", $version, LOCK_EX);

        header("Content-Length: " . strlen($dat));
        echo $dat;
        break;

    case "news_version.txt":
        $time = $db->query("SELECT MAX(entryDate) FROM news");
        $time = $time->fetchColumn();

        header("Content-Type: text/plain");

        // This will display a preview news entry
        if (empty($time)) {
            header("Content-Length: 1");      
            echo "1";
            break;
        }

        $version = strtotime($time);

        header("Content-Length: " . strlen((string)$version));
        echo $version;
        break;

    default:
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Only serve PNG or JPEG
        if (!in_array($ext, ["jpg", "jpeg", "png"], true)) {
            http_response_code(404);
            exit;
        }

        // Make sure the file exists AND isn't in another directory
        $path = realpath($file);
        if ($path === false || !str_starts_with($path, __DIR__ . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            exit;
        }

        $mime = $ext === "png" ? "image/png" : "image/jpeg";

        header("Content-Type: " . $mime);
        header("Content-Length: " . filesize($path));
        readfile($path);
        break;
}