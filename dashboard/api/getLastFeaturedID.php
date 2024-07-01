<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header("Access-Control-Allow-Headers: X-Requested-With");
require_once "../incl/dashboardLib.php";
include_once "../".$dbPath."incl/lib/connection.php";
if(!isset($_GET)) $_GET = json_decode(file_get_contents('php://input'), true);

$query = $db->prepare("SELECT starFeatured FROM levels ORDER BY starFeatured DESC LIMIT 1");
$query->execute();
$featuredID = $query->fetchColumn();

exit(json_encode(['dashboard' => true, 'success' => true, 'id' => $featuredID]));