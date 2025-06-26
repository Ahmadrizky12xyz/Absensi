<?php
define('DB_HOST', 'switchyard.proxy.rlwy.net');
define('DB_USER', 'root');
define('DB_PASS', 'GimIJrayMAhWfrQSDRBRSSicHVbCWQNi');
define('DB_NAME', 'railway');
define('DB_PORT', 35645);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
