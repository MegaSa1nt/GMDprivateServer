<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__."/../../../incl/lib/mainLib.php";

exit(json_encode(['success' => true, 'feaID' => Library::nextFeaturedID() - 1]));
?>