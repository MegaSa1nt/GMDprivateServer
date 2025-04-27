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
            echo json_encode($res[0]); // Output the first result as an object
        } else {
            echo "-1"; // No results found
        }
    }
}
?>
