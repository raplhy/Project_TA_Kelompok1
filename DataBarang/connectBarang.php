<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host     = "localhost";
$username = "root";
$password = "";
$database = "db_kel1";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>