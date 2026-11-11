<?php
header("Content-Type: text/html; charset=utf-8");
echo bin2hex(random_bytes(16));
?>