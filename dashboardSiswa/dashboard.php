<?php
session_start();
require_once "../login/koneksi.php";

// ==========================================
// 1. SATPAM LOGIN
// ==========================================
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ==========================================
// 2. PROSES SIMPAN EDIT PROFIL (JIKA ADA POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $email         = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $no_hp         = mysqli_real_escape_string($koneksi, $_POST['no_hp'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';

    // Query default (update email & no hp)
    $query = "UPDATE users SET email = '$email', no_hp = '$no_hp' WHERE id_user = '$id_user'";

    // Kalau password diisi, update password juga
    if (!empty($password_baru)) {
        $pass_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $query = "UPDATE users SET email = '$email', no_hp = '$no_hp', password = '$pass_hash' WHERE id_user = '$id_user'";
    }

    // Eksekusi dan set notifikasi
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash_success'] = 'Profil dan Password berhasil diperbarui!';
    } else {
        $_SESSION['flash_error'] = 'Gagal memperbarui profil: ' . mysqli_error($koneksi);
    }

    // Redirect ke dirinya sendiri biar form nggak ter-submit 2x saat di-refresh
    header("Location: dashboard.php");
    exit;
}

// ==========================================
// 3. AMBIL DATA USER & DATA GEDUNG
// ==========================================
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$data_user = mysqli_fetch_assoc($query_user);

$nama_lengkap = $data_user['nama_lengkap'] ?? 'Siswa';
$role         = $_SESSION['role'] ?? 'siswa';
$inisial      = strtoupper(substr(trim($nama_lengkap), 0, 1));

$gedung_aktif = isset($_GET['gedung']) ? $_GET['gedung'] : 'Gedung A';
$kode_gedung_prefix = strtoupper(trim(str_replace('Gedung', '', $gedung_aktif)));
$page_title = 'Dashboard Siswa - ' . $gedung_aktif;

include __DIR__ . '/header.php';
?>

<!-- NOTIFIKASI SUKSES / GAGAL (Tema Cream) -->
<?php if (isset($_SESSION['flash_success'])): ?>
    <div style="background: #FDF3E7; color: #B85D19; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: bold; border: 1px solid #E5A970;">
        <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['flash_success']; ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div style="background: #F9E2DF; color: #B8473E; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; font-weight: bold; border: 1px solid #DFA69F;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= $_SESSION['flash_error']; ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- 3 KARTU STATISTIK -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
        <div class="stat-data">
            <h4>Barang Tersimpan</h4>
            <h2>-</h2>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
        <div class="stat-data">
            <h4>Sisa Loker Kosong</h4>
            <h2>-</h2> <!-- Warna biru dihapus, ikut tema -->
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
            <h4>Sudah Diambil</h4>
            <h2>-</h2>
        </div>
    </div>
</div>

<!-- TABEL -->
<div class="panel">
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div class="panel-title">Daftar Barang Tersimpan - <?php echo htmlspecialchars($gedung_aktif); ?></div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="dashboard.php" class="search-box" onsubmit="event.preventDefault();">
                <input type="hidden" name="gedung" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
                <input type="text" name="search" class="search-input" placeholder="Cari barang / loker...">
                <button type="submit" class="btn-primary" style="padding: 8px 12px;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button type="button" class="btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> + Titip Barang</button>
        </div>
    </div>

    <table class="table-custom">
        <thead>
            <tr>
                <th>NO</th>
                <th>NAMA BARANG</th>
                <th>KETERANGAN</th>
                <th>NO. LOKER</th>
                <th>TANGGAL SIMPAN</th>
                <th>STATUS</th>
                <th>AKSI</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align: center; color: #9E988E; padding: 30px;">Belum ada data barang.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- MODAL 1: TITIP BARANG -->
<div class="modal-bg" id="modalInput">
    <div class="modal-box">
        <h3><i class="fa-solid fa-box-archive"></i> Form Titip Barang (<?php echo htmlspecialchars($gedung_aktif); ?>)</h3>
        <form onsubmit="event.preventDefault();">
            <input type="hidden" name="gedung" value="<?php echo htmlspecialchars($gedung_aktif); ?>">
            <div class="form-group">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" class="form-input" placeholder="Misal: Helm KYT / Tas Laptop">
            </div>
            <div class="form-group">
                <label>Nomor Loker Kosong di <?php echo htmlspecialchars($gedung_aktif); ?> (1-140)</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: bold; font-size: 14px; background: #EFECE4; color: #1E1D1B; padding: 7px 12px; border-radius: 6px; border: 1px solid #D8D2C7;">
                        <?php echo $kode_gedung_prefix; ?>
                    </span>
                    <input type="number" name="no_loker" class="form-input" placeholder="Contoh: 1" min="1" max="140">
                </div>
                <div class="loker-info-box">
                    <strong><i class="fa-solid fa-info-circle"></i> Daftar Loker Kosong:</strong><br>
                    <span>Data loker akan tampil setelah integrasi backend.</span>
                </div>
            </div>
            <div class="form-group">
                <label>Keterangan / Ciri Barang (Opsional)</label>
                <input type="text" name="keterangan" class="form-input" placeholder="Misal: Warna hitam, casing gambar anime">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: PROFIL -->
<div class="modal-bg" id="modalProfile">
    <div class="modal-box">
        <h3><i class="fa-solid fa-user-gear"></i> Pengaturan Profil Saya</h3>
        
        <form action="dashboard.php" method="POST">
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="avatar-circle" style="width: 70px; height: 70px; margin: 0 auto 10px; font-size: 24px;">
                    <?php echo $inisial; ?>
                </div>
                <label style="cursor: pointer; font-size: 12px; color: #C85A17; font-weight: 600;">
                    <i class="fa-solid fa-camera"></i> Ubah Foto Profil
                    <input type="file" name="foto_profil" accept="image/*" style="display: none;">
                </label>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-input" value="<?php echo htmlspecialchars($nama_lengkap); ?>" readonly style="background: #FAF8F5; cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($data_user['email'] ?? ''); ?>" placeholder="alamat@email.com">
            </div>
            <div class="form-group">
                <label>No. WhatsApp / Telepon</label>
                <input type="text" name="no_hp" class="form-input" value="<?php echo htmlspecialchars($data_user['no_hp'] ?? ''); ?>" placeholder="08123456789">
            </div>
            <div class="form-group">
                <label>Password Baru (Kosongkan jika tidak ingin diubah)</label>
                <input type="password" name="password_baru" class="form-input" placeholder="Ketik password baru..." autocomplete="new-password">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModalProfile()">Batal</button>
                <button type="submit" name="update_profil" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: RIWAYAT -->
<div class="modal-bg" id="modalHistory">
    <div class="modal-box modal-large">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penitipan Barang</h3>
        <div style="overflow-x: auto; max-height: 400px;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NAMA BARANG</th>
                        <th>GEDUNG</th>
                        <th>NO. LOKER</th>
                        <th>TGL SIMPAN</th>
                        <th>TGL AMBIL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9E988E; padding: 20px;">Belum ada data riwayat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModalHistory()">Tutup</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>