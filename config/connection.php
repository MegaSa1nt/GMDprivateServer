<?php
$servername = getenv('DB_HOST') ?: "127.0.0.1";
$port = getenv('DB_PORT') ?: 3306;
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "123123";
$dbname = getenv('DB_NAME') ?: "gcs";
?>