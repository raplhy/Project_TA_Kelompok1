<?php
require_once "connectBarang.php";

function kembaliError($pesan) {
    header("Location: createBarang.php?pesan=error&teks=" . urlencode($pesan));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: indexBarang.php");
    exit();
}

$id_siswa = $_POST['id_siswa'] ?? 0;
$id_loker = $_POST['id_loker'] ?? 0;

if (empty($id_siswa) || empty($id_loker)) {
    kembaliError("Siswa dan Loker wajib dipilih.");
}

$pilihan_barang = $_POST['pilihan_barang'] ?? [];
$custom_barang  = $_POST['nama_barang_custom'] ?? [];
$daftar_barang  = [];

for ($i = 0; $i < count($pilihan_barang); $i++) {
    $pilihan = trim($pilihan_barang[$i]);
    $custom  = trim($custom_barang[$i] ?? '');

    if ($pilihan === 'Lainnya') {
        if ($custom === '') {
            kembaliError("Nama barang wajib diisi jika memilih opsi 'Lainnya'.");
        }
        $daftar_barang[] = $custom;
    } elseif ($pilihan !== '') {
        $daftar_barang[] = $pilihan;
    }
}

if (count($daftar_barang) < 3) {
    kembaliError("Siswa harus memasukkan minimal 3 barang yang dititipkan.");
}

$barang_tercatat = [];
foreach ($daftar_barang as $nama_item) {
    if (strlen($nama_item) < 3 || strlen($nama_item) > 50) {
        kembaliError("Nama barang '$nama_item' harus antara 3 sampai 50 karakter.");
    }

    if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $nama_item)) {
        kembaliError("Nama barang '$nama_item' hanya boleh berisi huruf, angka, spasi, dan tanda hubung (-).");
    }

    preg_match_all('/[a-zA-Z]/', $nama_item, $huruf);
    if (count($huruf[0]) < 3) {
        kembaliError("Nama barang '$nama_item' wajib mengandung minimal 3 huruf.");
    }

    if (preg_match('/(.)\1{2,}/i', $nama_item)) {
        kembaliError("Nama barang '$nama_item' mengandung pengulangan huruf yang tidak wajar.");
    }

    $nama_kecil = strtolower($nama_item);
    foreach ($barang_tercatat as $sebelumnya) {
        $sebelumnya_kecil = strtolower($sebelumnya);

        if ($nama_kecil === $sebelumnya_kecil || strpos($nama_kecil, $sebelumnya_kecil) !== false || strpos($sebelumnya_kecil, $nama_kecil) !== false) {
            kembaliError("Nama barang '$nama_item' sama atau terlalu mirip dengan '$sebelumnya'.");
        }
    }

    $barang_tercatat[] = $nama_item;
}

if (!isset($_FILES['foto_barang']) || $_FILES['foto_barang']['error'] !== 0) {
    kembaliError("Foto barang wajib diunggah sebagai bukti fisik.");
}

$file_tmp  = $_FILES['foto_barang']['tmp_name'];
$file_nama = $_FILES['foto_barang']['name'];
$ekstensi  = strtolower(pathinfo($file_nama, PATHINFO_EXTENSION));

$ekstensi_boleh = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ekstensi, $ekstensi_boleh)) {
    kembaliError("Format foto harus JPG, JPEG, PNG, atau WEBP.");
}

if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

$nama_file_foto = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ekstensi;
if (!move_uploaded_file($file_tmp, "uploads/" . $nama_file_foto)) {
    kembaliError("Gagal mengupload file foto.");
}

foreach ($daftar_barang as $nama_item) {
    $nama_bersih = mysqli_real_escape_string($koneksi, $nama_item);

    mysqli_query($koneksi, "INSERT INTO barang 
        (id_siswa, id_loker, nama_barang, foto_barang, waktu_penitipan, status, waktu_pengambilan) 
        VALUES 
        ('$id_siswa', '$id_loker', '$nama_bersih', '$nama_file_foto', NOW(), 'Tersimpan', NULL)");
    
    $id_baru = mysqli_insert_id($koneksi);
    $id_aktor = !empty($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : (int)$id_siswa;
    if ($id_aktor <= 0) $id_aktor = 30;
    @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) 
        VALUES ('$id_aktor', 'Tambah', '$id_baru', NOW())");
}

mysqli_query($koneksi, "UPDATE loker SET status = 'Terisi' WHERE id_loker = '$id_loker'");

header("Location: indexBarang.php?pesan=sukses_tambah");
exit();
?>