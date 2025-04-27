<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} else {
    echo "Database connected successfully!";
}

$querywhat = "SELECT * FROM clans"; 
$query = $db->prepare($querywhat);
$query->execute();

$res = $query->fetchAll();

if ($res) {
    if (count($res) > 0) {
        $response = array();

        foreach ($res as $row) {
            $clan = array(
                'ID' => $row['ID'],
                'name' => $row['clan'],
                'tag' => $row['tag'],
                'desc' => $row['desc'],
                'clanOwner' => $row['clanOwner'],
                'color' => $row['color'],
                'isClosed' => $row['isClosed'],
                'creationDate' => $row['creationDate']
            );

            $response[] = $clan;
        }

        echo json_encode($response);
    } else {
        echo "No rows found in the clans table.";
    }
} else {
    echo "Error executing query.";
}
?>
