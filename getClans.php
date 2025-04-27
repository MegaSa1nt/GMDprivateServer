<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";

// Prepare the SQL query
$query = "SELECT * FROM clans";
$result = $db->query($query);

// Check if the query was successful and if there are rows returned
if ($result) {
    if ($result->num_rows > 0) {
        // Initialize the response array
        $response = array();

        // Loop through each row and fetch the data
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

            // Add each clan's data to the response array
            $response[] = $clan;
        }

        // Output the response as JSON
        echo json_encode($response);
    } else {
        echo "No rows found in the clans table.";
    }
} else {
    echo "Query failed: " . $db->error;
}
?>
