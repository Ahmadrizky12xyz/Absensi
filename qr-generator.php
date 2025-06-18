<?php
include('libs/phpqrcode/qrlib.php');

$text = "https://localhost/absensi/scan.php"; // URL yang dipindai
$filename = "assets/qrcode.png";

QRcode::png($text, $filename);
echo "<img src='$filename' />";
?>