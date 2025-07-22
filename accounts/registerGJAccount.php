<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";

$userName = Escape::latin_no_spaces($_POST['userName']);
$password = $_POST['password'];
$email = Escape::text($_POST['email']);

if(empty($userName) || empty($password) || empty($email)) exit(CommonError::InvalidRequest);

$createAccount = Library::createAccount($userName, $password, $password, $email, $email);
if(!$createAccount['success']) exit($createAccount['error']);

exit(RegisterError::Success);
?>