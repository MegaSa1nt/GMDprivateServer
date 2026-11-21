<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";

$gs = new mainLib();

$targetID = ExploitPatch::number($_POST["targetID"] ?? "");
$type = ExploitPatch::number($_POST["type"] ?? "");
$reasons = ExploitPatch::remove($_POST["reasons"] ?? "");
$extraID = ExploitPatch::number($_POST["messageID"] ?? "") ?: ExploitPatch::number($_POST["special"] ?? "");

if (!$targetID) exit("-1");

switch ($type) {
    case 0: // account
        $query = $db->prepare("SELECT 1 FROM accounts WHERE accountID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 1: // level
        $query = $db->prepare("SELECT 1 FROM levels WHERE levelID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 2: // list
        $query = $db->prepare("SELECT 1 FROM lists WHERE listID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 3: // level/list comment
        $query = $db->prepare("SELECT 1 FROM comments WHERE commentID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 4: // profile post
        $query = $db->prepare("SELECT 1 FROM acccomments WHERE commentID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 5: // legacy message
        $query = $db->prepare("SELECT 1 FROM messages WHERE messageID = :targetID AND isThreadMessage = 0");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 6: // friend request
        $query = $db->prepare("SELECT 1 FROM friendreqs WHERE ID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 7: // sound pack comment (not implemented yet)
        $exists = false;
        break;
    case 8: // thread
        $query = $db->prepare("SELECT 1 FROM threads WHERE threadID = :targetID");
        $query->execute([":targetID" => $targetID]);
        $exists = $query->fetchColumn();
        break;
    case 9: // sound pack (not implemented yet)
    default:
        $exists = false;
}
if (!$exists) exit("-1");

$reasonsArray = explode(",", $reasons);
foreach ($reasonsArray as $reason) {
    if (!in_array($reason, [0, 1, 2, 3, 4, 5, 6, 7])) {
        exit("-1");
    }
    if ($type == 9 && in_array($reason, [3, 7])) {
        exit("-1");
    }
    if ($type != 0 && in_array($reason, [4, 5])) {
        exit("-1");
    }
    if (!in_array($type, [1, 2, 9]) && $reason == 6) {
        exit("-1");
    }
}

if ($extraID) {
    switch ($type) {
        case 3: // level ID
            $query = $db->prepare("SELECT 1 FROM comments WHERE commentID = :targetID AND levelID = :extraID");
            $query->execute([":targetID" => $targetID, ":extraID" => $extraID]);
            $exists = $query->fetchColumn();
            break;
        case 4: // account ID of the poster
            $query = $db->prepare("SELECT 1 FROM acccomments WHERE commentID = :targetID AND userID = :extraID");
            $query->execute([":targetID" => $targetID, ":extraID" => $gs->getUserID($extraID)]);
            $exists = $query->fetchColumn();
            break;
        case 7: // sound pack ID (not implemented yet)
            $exists = false;
            break;
        case 8: // message ID
            $query = $db->prepare("SELECT 1 FROM messages
                JOIN threads ON threads.threadID = messages.threadID
                WHERE messages.messageID = :extraID AND messages.isThreadMessage = 1
                AND threads.threadID = :targetID AND (messages.accID = threads.threadOwner OR FIND_IN_SET(messages.accID, threads.threadMembers))
                AND (messages.toAccountID = threads.threadOwner OR FIND_IN_SET(messages.toAccountID, threads.threadMembers))");
            $query->execute([":extraID" => $extraID, ":targetID" => $targetID]);
            $exists = $query->fetchColumn();
            break;
        default:
            $exists = false;
    }
    if (!$exists) exit("-1");
}

$ip = $gs->getIP();

$query = $db->prepare("INSERT INTO reports (targetID, type, reasons, extraID, hostname) VALUES (:targetID, :type, :reasons, :extraID, :hostname)");
$query->execute([":targetID" => $targetID, ":type" => $type, ":reasons" => $reasons, ":extraID" => $extraID, ":hostname" => $ip]);

echo "1";
?>