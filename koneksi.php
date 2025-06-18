<?php
// Pastikan parameter sesuai dengan konfigurasi database Anda
$conn = mysqli_connect('localhost', 'root', '', 'absensi_ptindotekhnoplus');

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>