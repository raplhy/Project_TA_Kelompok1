<?php
require_once "connectBarang.php";

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aksi_massal'])) {
    $aksi = $_POST['aksi_massal'];
    $ids  = $_POST['id_barang'] ?? [];

    if (empty($ids)) {
        header("Location: indexBarang.php?pesan=tidak_ada_pilihan");
        exit();
    }

    $id_list = implode(',', $ids);
    $jumlah  = count($ids);

    $qData = mysqli_query($koneksi, "SELECT foto_barang, id_loker FROM barang WHERE id_barang IN ($id_list)");
    $loker_terpengaruh = [];

    while ($b = mysqli_fetch_assoc($qData)) {
        if (!empty($b['id_loker'])) {
            $loker_terpengaruh[] = $b['id_loker'];
        }
        if ($aksi === 'hapus' && !empty($b['foto_barang']) && file_exists("uploads/" . $b['foto_barang'])) {
            unlink("uploads/" . $b['foto_barang']);
        }
    }

    $id_admin = (int)($_SESSION['id_user'] ?? 30);
    if ($aksi === 'verifikasi') {
        mysqli_query($koneksi, "UPDATE barang SET status = 'Sudah diambil', waktu_pengambilan = NOW() WHERE id_barang IN ($id_list) AND status = 'Tersimpan'");
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ('$id_admin', 'Ubah', NULL, NOW())");
        $pesan = "sukses_verifikasi_massal";
    } elseif ($aksi === 'hapus') {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
            header('Location: indexBarang.php?pesan=akses_ditolak');
            exit();
        }
        mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang IN ($id_list)");
        @mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_admin, aksi, id_barang_terkait, waktu) VALUES ('$id_admin', 'Hapus', NULL, NOW())");
        $pesan = "sukses_hapus_massal";
    }

    foreach ($loker_terpengaruh as $id_lkr) {
        $cekSisa = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_loker = '$id_lkr' AND status = 'Tersimpan'");
        $sisa = mysqli_fetch_assoc($cekSisa)['total'];
        if ($sisa == 0) {
            mysqli_query($koneksi, "UPDATE loker SET status = 'Kosong' WHERE id_loker = '$id_lkr'");
        }
    }

    header("Location: indexBarang.php?pesan=$pesan&total=$jumlah");
    exit();
}

$id_siswa_login = (int)($_SESSION['id_user'] ?? 0);
$kata_kunci    = trim($_GET['cari'] ?? '');
$filter_status = $_GET['status'] ?? '';
$kondisi       = "WHERE 1=1";

if ($id_siswa_login > 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') {
    $kondisi .= " AND b.id_siswa = $id_siswa_login";
}

if ($kata_kunci !== '') {
    $cari = mysqli_real_escape_string($koneksi, $kata_kunci);
    $kondisi .= " AND (b.nama_barang LIKE '%$cari%' 
                   OR u.nama_lengkap LIKE '%$cari%' 
                   OR l.nomor_loker LIKE '%$cari%')";
}

if ($filter_status !== '') {
    $status = mysqli_real_escape_string($koneksi, $filter_status);
    $kondisi .= " AND b.status = '$status'";
}

$querySql = "SELECT b.*, u.nama_lengkap AS nama_siswa, u.username AS nis_siswa, l.nomor_loker, g.nama_gedung AS lokasi
             FROM barang b
             LEFT JOIN users u ON b.id_siswa = u.id_user
             LEFT JOIN loker l ON b.id_loker = l.id_loker
             LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
             $kondisi
             ORDER BY b.id_barang DESC";
$hasilData = mysqli_query($koneksi, $querySql);

$where_siswa = ($id_siswa_login > 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') ? " WHERE id_siswa = $id_siswa_login" : "";
$and_siswa   = ($id_siswa_login > 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'siswa') ? " AND id_siswa = $id_siswa_login" : "";

$c_semua     = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_barang FROM barang $where_siswa"));
$c_tersimpan = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_barang FROM barang WHERE status = 'Tersimpan' $and_siswa"));
$c_diambil   = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_barang FROM barang WHERE status = 'Sudah diambil' $and_siswa"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Penitipan Loker</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Penitipan Barang Siswa</h2>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="createBarang.php" class="btn btn-primary">+ Tambah Barang</a>
        </div>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <div id="notifikasiAlert">
            <?php $p = $_GET['pesan']; $total = $_GET['total'] ?? 0; ?>
            <?php if ($p == 'sukses_tambah'): ?>
                <div class="alert alert-success">Data barang berhasil ditambahkan ke loker.</div>
            <?php elseif ($p == 'sukses_edit'): ?>
                <div class="alert alert-success">Data barang berhasil diperbarui.</div>
            <?php elseif ($p == 'sukses_hapus'): ?>
                <div class="alert alert-info">Data barang berhasil dihapus.</div>
            <?php elseif ($p == 'sukses_ambil'): ?>
                <div class="alert alert-success">Status barang berhasil diubah menjadi Sudah diambil.</div>
            <?php elseif ($p == 'sukses_verifikasi_massal'): ?>
                <div class="alert alert-success">Berhasil memverifikasi <strong><?= $total ?></strong> barang terpilih sudah diambil.</div>
            <?php elseif ($p == 'sukses_hapus_massal'): ?>
                <div class="alert alert-info">Berhasil menghapus <strong><?= $total ?></strong> data barang terpilih beserta fotonya.</div>
            <?php elseif ($p == 'tidak_ada_pilihan'): ?>
                <div class="alert alert-danger">Silakan pilih minimal 1 barang terlebih dahulu dengan mencentang kotak.</div>
            <?php elseif ($p == 'akses_ditolak'): ?>
                <div class="alert alert-danger">Akses ditolak: Siswa hanya dapat menambahkan barang dan memverifikasi pengambilan (tidak diizinkan mengedit atau menghapus).</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <form method="GET" action="indexBarang.php" class="search-form" onsubmit="return false;">
                <input type="text" id="inputCariBarang" name="cari" class="search-input" placeholder="Cari barang atau nomor loker..." value="<?= htmlspecialchars($kata_kunci) ?>" autocomplete="off" style="width: 270px;">
            </form>

            <?php $paramCari = ($kata_kunci !== '') ? '&cari=' . urlencode($kata_kunci) : ''; ?>
            <div class="filter-tabs">
                <a href="indexBarang.php<?= ($kata_kunci !== '') ? '?cari=' . urlencode($kata_kunci) : '' ?>" 
                   class="filter-tab <?= ($filter_status == '') ? 'active' : '' ?>">
                    Semua <span class="tab-count">(<?= $c_semua ?>)</span>
                </a>
                <a href="indexBarang.php?status=Tersimpan<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Tersimpan') ? 'active' : '' ?>">
                    Tersimpan <span class="tab-count">(<?= $c_tersimpan ?>)</span>
                </a>
                <a href="indexBarang.php?status=Sudah diambil<?= $paramCari ?>" 
                   class="filter-tab <?= ($filter_status == 'Sudah diambil') ? 'active' : '' ?>">
                    Sudah Diambil <span class="tab-count">(<?= $c_diambil ?>)</span>
                </a>
            </div>
        </div>

        <form method="POST" action="indexBarang.php" id="formAksiMassal">
            <div class="bulk-action-bar">
                <div class="bulk-select-area">
                    <label class="label-select-all">
                        <input type="checkbox" id="checkAll" onchange="toggleSelectAll(this)">
                        <span>Pilih Semua Barang</span>
                    </label>
                    <span class="bulk-divider">|</span>
                    <span class="bulk-count">Terpilih: <strong id="jumlahTerpilih" style="color: #C85A17;">0</strong> data</span>
                </div>
                <div class="bulk-buttons">
                    <button type="submit" name="aksi_massal" value="verifikasi" class="btn btn-sm btn-success" onclick="return konfirmasiAksi('verifikasi')">
                        Tandai Sudah Diambil
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                <input type="checkbox" id="checkAllHead" onchange="toggleSelectAll(this)" title="Pilih Semua" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                            </th>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 65px; text-align: center;">Foto</th>
                            <th style="text-align: center;">Nama Barang</th>
                            <th style="width: 90px; text-align: center;">Loker</th>
                            <th style="width: 120px; text-align: center;">Status</th>
                            <th style="width: 145px; text-align: center;">Waktu Penitipan</th>
                            <th style="width: 145px; text-align: center;">Waktu Pengambilan</th>
                            <th style="width: 130px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="barisKosongCari" style="display: none;">
                            <td colspan="9" style="text-align: center; color: #9E988E; padding: 30px;">
                                Tidak ada data barang yang sesuai dengan pencarian.
                            </td>
                        </tr>
                        <?php if (mysqli_num_rows($hasilData) > 0): ?>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($hasilData)): 
                                $path_foto = 'uploads/' . $row['foto_barang'];
                                $ada_foto = (!empty($row['foto_barang']) && file_exists($path_foto));
                            ?>
                            <tr class="baris-barang" data-nama="<?= htmlspecialchars(mb_strtolower($row['nama_barang'], 'UTF-8')) ?>" data-loker="<?= htmlspecialchars(mb_strtolower($row['nomor_loker'] ?? '', 'UTF-8')) ?>" data-siswa="<?= htmlspecialchars(mb_strtolower($row['nama_siswa'] ?? '', 'UTF-8')) ?>">
                                <td style="width: 36px; min-width: 36px; max-width: 38px; text-align: center; padding: 6px 4px;">
                                    <input type="checkbox" name="id_barang[]" value="<?= $row['id_barang'] ?>" class="item-checkbox" onchange="hitungTerpilih()" style="cursor: pointer; width: 14px; height: 14px; margin: 0; vertical-align: middle; accent-color: #E04F16;">
                                </td>
                                <td style="text-align: center; color: #9E988E;"><?= $no++ ?></td>
                                <td style="text-align: center;">
                                    <?php if ($ada_foto): ?>
                                        <a href="<?= $path_foto ?>" target="_blank" style="display: inline-block;">
                                            <img src="<?= $path_foto ?>" alt="Foto" class="thumb-img">
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #9E988E;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <strong><?= htmlspecialchars($row['nama_barang']) ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($row['nomor_loker'])): ?>
                                        <span class="badge badge-kode"><?= htmlspecialchars($row['nomor_loker']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($row['status'] == 'Tersimpan'): ?>
                                        <span class="badge badge-tersimpan">Tersimpan</span>
                                    <?php else: ?>
                                        <span class="badge badge-diambil">Sudah diambil</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($row['waktu_penitipan'])) ?>
                                </td>
                                <td style="text-align: center; color: #6E6A63; font-size: 12px; white-space: nowrap;">
                                    <?= !empty($row['waktu_pengambilan']) ? date('d/m/Y H:i', strtotime($row['waktu_pengambilan'])) : '-' ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if ($row['status'] === 'Tersimpan'): ?>
                                        <a href="verificationBarang.php?id=<?= $row['id_barang'] ?>" class="btn btn-sm btn-primary">Verifikasi</a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #8C867D; font-weight: 600;">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="barisKosongPhp">
                                <td colspan="9" style="text-align: center; padding: 30px; color: #6E6A63;">
                                    Belum ada data barang. Silakan klik tombol <strong>+ Tambah Barang</strong>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    var checkboxes = document.querySelectorAll('.item-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        var tr = checkboxes[i].closest('tr');
        if (!tr || tr.style.display !== 'none') {
            checkboxes[i].checked = master.checked;
        }
    }
    var m1 = document.getElementById('checkAll');
    var m2 = document.getElementById('checkAllHead');
    if (m1 && m1 !== master) m1.checked = master.checked;
    if (m2 && m2 !== master) m2.checked = master.checked;
    hitungTerpilih();
}

function hitungTerpilih() {
    var checkboxes = document.querySelectorAll('.item-checkbox');
    var totalVisible = 0;
    var checkedVisible = 0;
    for (var i = 0; i < checkboxes.length; i++) {
        var tr = checkboxes[i].closest('tr');
        if (!tr || tr.style.display !== 'none') {
            totalVisible++;
            if (checkboxes[i].checked) checkedVisible++;
        }
    }

    var el = document.getElementById('jumlahTerpilih');
    if (el) el.innerText = checkedVisible;

    var m1 = document.getElementById('checkAll');
    var m2 = document.getElementById('checkAllHead');
    var all = (totalVisible > 0 && checkedVisible === totalVisible);
    if (m1) m1.checked = all;
    if (m2) m2.checked = all;
}

function konfirmasiAksi(jenisAksi) {
    var ceks = document.querySelectorAll('.item-checkbox:checked');
    if (ceks.length === 0) {
        alert('Silakan pilih minimal 1 barang terlebih dahulu!');
        return false;
    }

    if (jenisAksi === 'verifikasi') {
        return confirm('Yakin ingin memverifikasi ' + ceks.length + ' barang terpilih sudah diambil?');
    } else if (jenisAksi === 'hapus') {
        return confirm('Yakin ingin menghapus ' + ceks.length + ' data barang terpilih beserta fotonya?');
    }
    return false;
}

(function() {
    var inputCari = document.getElementById('inputCariBarang');
    if (!inputCari) return;

    var rows = document.querySelectorAll('.baris-barang');
    var emptyRow = document.getElementById('barisKosongCari');
    var emptyPhp = document.getElementById('barisKosongPhp');

    function filterBarang() {
        var q = inputCari.value.trim().toLowerCase();
        var visibleCount = 0;

        rows.forEach(function(row) {
            var nama = row.getAttribute('data-nama') || '';
            var loker = row.getAttribute('data-loker') || '';
            var siswa = row.getAttribute('data-siswa') || '';

            if (q === '' || nama.indexOf(q) !== -1 || loker.indexOf(q) !== -1 || siswa.indexOf(q) !== -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
                var cb = row.querySelector('.item-checkbox');
                if (cb && cb.checked) {
                    cb.checked = false;
                }
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
        if (emptyPhp && rows.length === 0) {
            emptyPhp.style.display = '';
        }

        hitungTerpilih();
    }

    inputCari.addEventListener('input', filterBarang);
    inputCari.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    if (inputCari.value.trim() !== '') {
        filterBarang();
    }
})();

var notifAlert = document.getElementById('notifikasiAlert');
if (notifAlert) {
    setTimeout(function() {
        notifAlert.style.display = 'none';
    }, 4000);
}
</script>

</body>
</html>