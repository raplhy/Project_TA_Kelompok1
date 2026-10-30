<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = "localhost";
$username = "root";
$password = "";
$database = "db_kel1";

@mysqli_report(MYSQLI_REPORT_OFF);

$koneksi = false;
try {
    $koneksi = @mysqli_connect($host, $username, $password, $database);
    if (!$koneksi) {
        $koneksi = @mysqli_connect($host, "app_kel1", "D@t@6@53K3l1", $database);
    }
} catch (Throwable $e) {
    $koneksi = false;
}
