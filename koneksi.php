<?php
$host = 'mysql-4ec7fa0-rizky15raditya-e894.d.aivencloud.com';
$port = 21015;
$dbname = 'defaultdb';
$username = 'avnadmin';
$password = 'AVNS_kDRQLgJnd6wDKbJmjgD';

// Koneksi pakai PDO dan SSL
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, [
    PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/ca.pem', // Download dari Aiven console
]);

// Test koneksi
if ($pdo) {
    echo "Koneksi ke Aiven MySQL sukses!";
}
