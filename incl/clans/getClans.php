<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";

if (!empty($_POST)) {
    if ($db->connect_error) {
        die("-1"); // Connection failed
    } else {
        $querywhat = "SELECT * FROM clans"; 
        $query = $db->prepare($querywhat);
        $query->execute();

        $res = $query->fetchAll();

        if ($res) {
            if (count($res) > 0) {
                $response = array();

                foreach ($res as $row) {
                    $clanOwnerID = $row['clanOwner'];

                    $querywhatUser = "SELECT userName FROM accounts WHERE accountID = :clanOwnerID";
                    $queryUser = $db->prepare($querywhatUser);
                    $queryUser->execute([':clanOwnerID' => $clanOwnerID]);

                    $user = $queryUser->fetch();
                    $username = $user ? $user['userName'] : 'Unknown';

                    $clan = array(
                        'ID' => $row['ID'],
                        'name' => $row['clan'],
                        'tag' => $row['tag'],
                        'desc' => $row['desc'],
                        'clanOwner' => $row['clanOwner'],
                        'color' => $row['color'],
                        'isClosed' => $row['isClosed'],
                        'creationDate' => $row['creationDate'],
                        'username' => $username
                    );

                    $response[] = $clan;
                }

                echo json_encode($response);
            } else {
                echo "-1"; // No rows found in the clans table
            }
        } else {
            echo "-1"; // Error executing query
        }
    }
} else {
    die("-1"); // Invalid request method
}
?>
