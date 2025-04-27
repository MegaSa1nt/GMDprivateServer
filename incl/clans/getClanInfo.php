<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";

if (!isset($_POST['id'])) {
    die("-1"); // Invalid request: 'id' is required
} else {
    if ($db->connect_error) {
        die("-1"); // Connection failed
    } else {
        $querywhat = "SELECT * FROM clans WHERE ID = :id"; 
        $query = $db->prepare($querywhat);
        $query->execute([':id' => $_POST['id']]);

        $res = $query->fetchAll();

        if ($res) {
            $clanOwnerID = $res[0]['clanOwner'];
            $querywhatUser = "SELECT userName FROM accounts WHERE accountID = :clanOwnerID";
            $queryUser = $db->prepare($querywhatUser);
            $queryUser->execute([':clanOwnerID' => $clanOwnerID]);

            $user = $queryUser->fetch();
            $username = $user ? $user['userName'] : 'Unknown';

            $res[0]['username'] = $username;

            $querywhatMembers = "SELECT userName FROM users WHERE clan = :id";
            $queryMembers = $db->prepare($querywhatMembers);
            $queryMembers->execute([':id' => $_POST['id']]);

            $members = [];
            while ($member = $queryMembers->fetch()) {
                $members[] = $member['userName'];
            }

            $res[0]['members'] = $members;

            echo json_encode($res[0]);
        } else {
            echo "-1"; // No results found
        }
    }
}
?>
