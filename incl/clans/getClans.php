<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";

$query = "SELECT * FROM clans";
$result = $db->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        $response = array();

        while ($row = $result->fetch_assoc()) {
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
    echo "Query failed: " . $db->error;
}
?>
