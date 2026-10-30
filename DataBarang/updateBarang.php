<?php
require_once "connectBarang.php";

if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
    header("Location: indexBarang.php?pesan=akses_ditolak");
    exit();
}

$id = $_REQUEST['id'] ?? 0;

$qBarang = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = '$id'");
$barang  = mysqli_fetch_assoc($qBarang);

if (!$barang) {
    header("Location: indexBarang.php");
    exit();
}

$daftarBarang  = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol", "Lainnya"];
$barangStandar = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol"];

$isCustom       = !in_array($barang['nama_barang'], $barangStandar);
$pilihanSaatIni = $isCustom ? 'Lainnya' : $barang['nama_barang'];
$customSaatIni  = $isCustom ? $barang['nama_barang'] : '';

$pesanError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_siswa      = $_POST['id_siswa'] ?? 0;
    $id_loker_baru = $_POST['id_loker'] ?? 0;
    $id_loker_lama = $barang['id_loker'];
    $status        = $_POST['status'] ?? 'Tersimpan';
    $pilihan       = trim($_POST['pilihan_barang'] ?? '');
    $custom        = trim($_POST['nama_barang_custom'] ?? '');

    $nama_barang = ($pilihan === 'Lainnya') ? $custom : $pilihan;

    if (empty($id_siswa) || empty($id_loker_baru) || empty($nama_barang)) {
        $pesanError = "Siswa, Loker, dan Nama Barang wajib diisi.";
    } elseif (strlen($nama_barang) < 3 || strlen($nama_barang) > 50) {
        $pesanError = "Nama barang harus 3 sampai 50 karakter.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $nama_barang)) {
        $pesanError = "Hanya boleh huruf, angka, spasi, dan tanda hubung (-).";
    } elseif (preg_match('/(.)\1{2,}/i', $nama_barang)) {
        $pesanError = "Nama barang tidak boleh mengandung huruf berulang 3 kali berturut-turut.";
    }

    $nama_file_foto = $barang['foto_barang'];
    if ($pesanError === "" && isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === 0) {
        $file_tmp  = $_FILES['foto_barang']['tmp_name'];
        $file_nama = $_FILES['foto_barang']['name'];
        $ekstensi  = strtolower(pathinfo($file_nama, PATHINFO_EXTENSION));

        if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
            $pesanError = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
        } else {
            $foto_baru = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ekstensi;
            if (move_uploaded_file($file_tmp, "uploads/" . $foto_baru)) {
                if (!empty($barang['foto_barang']) && file_exists("uploads/" . $barang['foto_barang'])) {
                    unlink("uploads/" . $barang['foto_barang']);
                }
                $nama_file_foto = $foto_baru;
            } else {
                $pesanError = "Gagal mengupload foto baru.";
            }
        }
    }

    if ($pesanError === "") {
        $nama_bersih = mysqli_real_escape_string($koneksi, $nama_barang);

        if ($status === 'Sudah diambil' && empty($barang['waktu_pengambilan'])) {
            $waktu_ambil = "NOW()";
        } elseif ($status === 'Tersimpan') {
            $waktu_ambil = "NULL";
        } else {
            $waktu_ambil = "'" . $barang['waktu_pengambilan'] . "'";
        }

        mysqli_query($koneksi, "UPDATE barang SET 
            id_siswa = '$id_siswa',
            id_loker = '$id_loker_baru',
            nama_barang = '$nama_bersih',
            foto_barang = '$nama_file_foto',
            status = '$status',
            waktu_pengambilan = $waktu_ambil
            WHERE id_barang = '$id'");

        $id_admin = (int)($_SESSION['id_user'] ?? 30);
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) 
            VALUES ('$id_admin', 'Ubah', '$id', NOW())");

        if ($status === 'Tersimpan') {
            mysqli_query($koneksi, "UPDATE loker SET status = 'Terisi' WHERE id_loker = '$id_loker_baru'");
        }

        foreach ([$id_loker_lama, $id_loker_baru] as $id_lkr) {
            $cekSisa = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_loker = '$id_lkr' AND status = 'Tersimpan'");
            if (mysqli_fetch_assoc($cekSisa)['total'] == 0) {
                mysqli_query($koneksi, "UPDATE loker SET status = 'Kosong' WHERE id_loker = '$id_lkr'");
            }
        }

        header("Location: indexBarang.php?pesan=sukses_edit");
        exit();
    }
}

$queryLoker = mysqli_query($koneksi, "SELECT l.id_loker, l.nomor_loker, g.nama_gedung AS lokasi FROM loker l LEFT JOIN gedung g ON l.id_gedung = g.id_gedung ORDER BY l.nomor_loker ASC");
$querySiswa = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'siswa' ORDER BY nama_lengkap ASC");
$path_foto  = 'uploads/' . $barang['foto_barang'];
$ada_foto   = (!empty($barang['foto_barang']) && file_exists($path_foto));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Barang</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Edit Data Barang</h2>
            <p>Perbarui informasi barang titipan <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong></p>
        </div>
        <div>
            <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <?php if ($pesanError !== ""): ?>
        <div class="alert alert-danger"><?= $pesanError ?></div>
    <?php endif; ?>

    <div class="card">
        <form action="updateBarang.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $barang['id_barang'] ?>">


            <div class="form-group">
                <label for="id_siswa">Siswa Pemilik:</label>
                <select name="id_siswa" id="id_siswa" class="form-control" required>
                    <option value="">Pilih Siswa</option>
                    <?php while ($s = mysqli_fetch_assoc($querySiswa)): ?>
                        <option value="<?= $s['id_user'] ?>" <?= ($barang['id_siswa'] == $s['id_user']) ? 'selected' : '' ?>>
                            <?= $s['nama_lengkap'] ?> (NIS: <?= $s['username'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_loker">Loker Tujuan:</label>
                <select name="id_loker" id="id_loker" class="form-control" required>
                    <option value="">Pilih Loker</option>
                    <?php while ($l = mysqli_fetch_assoc($queryLoker)): ?>
                        <option value="<?= $l['id_loker'] ?>" <?= ($barang['id_loker'] == $l['id_loker']) ? 'selected' : '' ?>>
                            Loker <?= $l['nomor_loker'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nama / Jenis Barang:</label>
                <div class="barang-item-box" style="margin-bottom: 0;">
                    <select name="pilihan_barang" id="pilihan_barang" class="form-control" onchange="cekLainnya()" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($daftarBarang as $item): ?>
                            <option value="<?= $item ?>" <?= ($pilihanSaatIni === $item) ? 'selected' : '' ?>>
                                <?= $item ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="nama_barang_custom" id="input_custom" class="form-control input-custom" 
                           placeholder="Ketik nama barang..." value="<?= $customSaatIni ?>" 
                           style="display: <?= $isCustom ? 'block' : 'none' ?>;">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status Barang:</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="Tersimpan" <?= ($barang['status'] === 'Tersimpan') ? 'selected' : '' ?>>Tersimpan</option>
                    <option value="Sudah diambil" <?= ($barang['status'] === 'Sudah diambil') ? 'selected' : '' ?>>Sudah diambil</option>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_barang">Foto Barang (Kosongkan jika tidak ingin mengganti foto):</label>
                <?php if ($ada_foto): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="<?= $path_foto ?>" alt="Foto Saat Ini" style="width: 100px; border-radius: 6px; border: 1px solid #D8D2C7;">
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_barang" id="foto_barang" class="form-control" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="indexBarang.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function cekLainnya() {
    const select = document.getElementById('pilihan_barang');
    const input = document.getElementById('input_custom');

    if (select.value === 'Lainnya') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

</body>
</html>