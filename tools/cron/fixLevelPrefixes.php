<?php

//made by yxzhin with <3

//echo "fixing level prefixes... ";

require __DIR__."/../../incl/lib/connection.php";

$query = $db->prepare("SELECT levelID FROM demonlist ORDER BY pseudoPoints DESC");
$query->execute();
$levelIDs = $query->fetchAll();
$y = $query->rowCount();

for($x = 0; $x < $y; $x++){
    
    $levelID = $levelIDs[$x]["levelID"];
    //echo $levelID."; ";
    
    $query = $db->prepare("SELECT levelName FROM levels WHERE levelID = :levelID");
    $query->execute([":levelID"=>$levelID]);
    $levelName = $query->fetchColumn();
    
    //im sorry for this garbage code :sob: :sob:
    $a = $x+1;
    $newLevelName = "[top $a] ".$levelName;
    if(strpos($levelName, "top")) $newLevelName = str_replace(substr($levelName, 5, strpos($levelName, "]")-5), $a, $levelName);
    
    $query = $db->prepare("UPDATE levels SET levelName = :levelName WHERE levelID = :levelID");
    $query->execute([":levelName"=>$newLevelName, ":levelID"=>$levelID]);
    
}

//echo "done fixing level prefixes!";

?>