<?php
require_once "connectBarang.php";

$id_user_session = (int)($_SESSION['id_user'] ?? 0);
$role_session    = $_SESSION['role'] ?? '';

$siswa_login = null;
if ($id_user_session > 0 && $role_session === 'siswa') {
    $qCek = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE id_user = $id_user_session AND role = 'siswa' LIMIT 1");
    if ($row = mysqli_fetch_assoc($qCek)) {
        $siswa_login = $row;
    }
}

if (!$siswa_login) {
    $qFirst = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'siswa' ORDER BY id_user ASC LIMIT 1");
    if ($row = mysqli_fetch_assoc($qFirst)) {
        $siswa_login = $row;
    }
}

$id_siswa_terpilih   = $siswa_login ? (int)$siswa_login['id_user'] : 0;
$nama_siswa_terpilih = $siswa_login ? $siswa_login['nama_lengkap'] : 'Siswa';
$nis_siswa_terpilih  = $siswa_login ? $siswa_login['username'] : '-';

$queryLoker = mysqli_query($koneksi, "SELECT l.id_loker, l.nomor_loker, g.nama_gedung AS lokasi FROM loker l LEFT JOIN gedung g ON l.id_gedung = g.id_gedung ORDER BY l.nomor_loker ASC");
$daftarBarang = ["Tas", "Jaket", "Helm", "Laptop", "Sepatu", "Buku", "Botol", "Lainnya"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Barang</title>
    <link rel="stylesheet" href="styleBarang.css">
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Tambah Penitipan Barang</h2>
            <p>Formulir pencatatan barang siswa yang dititipkan di loker</p>
        </div>
        <div>
            <a href="indexBarang.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <?php if (($_GET['pesan'] ?? '') === 'error'): ?>
        <div class="alert alert-danger">
            <?= $_GET['teks'] ?? 'Terjadi kesalahan pada data input.' ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form action="saveBarang.php" method="POST" enctype="multipart/form-data" id="formBarang">

            <div class="form-group">
                <label for="id_siswa_display">Siswa Pemilik:</label>
                <select id="id_siswa_display" class="form-control" disabled style="background-color: #F7F4EE; cursor: not-allowed; color: #1E1E1E; font-weight: 500;">
                    <option value="<?= $id_siswa_terpilih ?>" selected>
                        <?= htmlspecialchars($nama_siswa_terpilih) ?> (NIS: <?= htmlspecialchars($nis_siswa_terpilih) ?>)
                    </option>
                </select>
                <input type="hidden" name="id_siswa" value="<?= $id_siswa_terpilih ?>">
            </div>

            <div class="form-group">
                <label for="id_loker">Loker Tujuan:</label>
                <select name="id_loker" id="id_loker" class="form-control" required>
                    <option value="">Pilih Loker</option>
                    <?php while ($l = mysqli_fetch_assoc($queryLoker)): ?>
                        <option value="<?= $l['id_loker'] ?>">
                            Loker <?= $l['nomor_loker'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_barang">Foto Barang Bawaan:</label>
                <input type="file" name="foto_barang" id="foto_barang" class="form-control" accept="image/*" required>
            </div>

            <hr class="divider">

            <div class="form-group">
                <label>Daftar Barang Bawaan:</label>
                <div id="daftarBarang">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="barang-item-box">
                            <label>Barang <?= $i ?> (Wajib):</label>
                            <select name="pilihan_barang[]" class="form-control" onchange="ubahPilihan(this)" required>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($daftarBarang as$barang): ?>
                                    <option value="<?= $barang ?>"><?= $barang ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nama_barang_custom[]" class="form-control input-custom" placeholder="Ketik nama barang..." oninput="validasiCustom(this)">
                            <small class="pesan-error-custom" style="color: #B8473E; font-size: 11px; display: none; margin-top: 4px; font-weight: bold;"></small>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarang()">+ Tambah Barang</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Data Barang</button>
                <a href="indexBarang.php" class="btn btn-secondary">Batal</a>
            </div>

        </form>
    </div>
</div>

<script>
const barangStandar = ["tas", "jaket", "helm", "laptop", "sepatu", "buku", "botol"];

function ubahPilihan(select) {
    const box = select.closest('.barang-item-box');
    const input = box.querySelector('.input-custom');
    const errorMsg = box.querySelector('.pesan-error-custom');

    if (select.value === 'Lainnya') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
        validasiCustom(input);
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
        input.style.borderColor = '';
        errorMsg.style.display = 'none';
    }

    updateDropdown();
}

function updateDropdown() {
    const semuaSelect = document.querySelectorAll('select[name="pilihan_barang[]"]');
    const terpilih = [];

    semuaSelect.forEach(s => {
        if (s.value && s.value !== 'Lainnya') {
            terpilih.push(s.value);
        }
    });

    semuaSelect.forEach(s => {
        Array.from(s.options).forEach(opt => {
            if (!opt.value || opt.value === 'Lainnya' || opt.value === s.value) {
                opt.hidden = false;
                opt.disabled = false;
            } else if (terpilih.includes(opt.value)) {
                opt.hidden = true;
                opt.disabled = true;
            } else {
                opt.hidden = false;
                opt.disabled = false;
            }
        });
    });
}

function validasiCustom(input) {
    const box = input.closest('.barang-item-box');
    const select = box.querySelector('select');
    const errorMsg = box.querySelector('.pesan-error-custom');

    if (select.value !== 'Lainnya') return true;

    const teks = input.value.trim();
    let pesan = "";

    if (teks === "") {
        pesan = "Nama barang wajib diisi.";
    } else if (teks.length < 3 || teks.length > 50) {
        pesan = "Nama barang harus 3 sampai 50 karakter.";
    } else if (!/^[a-zA-Z0-9\s\-]+$/.test(teks)) {
        pesan = "Hanya boleh huruf, angka, spasi, dan tanda hubung (-).";
    } else if ((teks.match(/[a-zA-Z]/g) || []).length < 3) {
        pesan = "Wajib mengandung minimal 3 huruf.";
    } else if (/(.)\1{2,}/i.test(teks)) {
        pesan = "Tidak boleh ada huruf yang diulang 3 kali berturut-turut.";
    }

    if (pesan === "") {
        const teksKecil = teks.toLowerCase();

        for (let item of barangStandar) {
            if (teksKecil === item || teksKecil.includes(item)) {
                pesan = `Barang "${item}" sudah ada di pilihan dropdown.`;
                break;
            }
        }

        const semuaBox = document.querySelectorAll('.barang-item-box');
        semuaBox.forEach(b => {
            if (b !== box) {
                const inputLain = b.querySelector('.input-custom').value.trim().toLowerCase();
                if (inputLain && (teksKecil === inputLain || teksKecil.includes(inputLain))) {
                    pesan = "Nama barang tidak boleh sama dengan input barang lainnya.";
                }
            }
        });
    }

    if (pesan !== "") {
        input.style.borderColor = '#B8473E';
        errorMsg.textContent = pesan;
        errorMsg.style.display = 'block';
        return false;
    } else {
        input.style.borderColor = '#181715';
        errorMsg.textContent = '';
        errorMsg.style.display = 'none';
        return true;
    }
}

function tambahBarang() {
    const container = document.getElementById('daftarBarang');
    const box = container.firstElementChild.cloneNode(true);
    const select = box.querySelector('select');
    const input = box.querySelector('.input-custom');
    const errorMsg = box.querySelector('.pesan-error-custom');

    box.querySelector('label').outerHTML = `
        <div class="toolbar" style="margin-bottom: 5px;">
            <label class="judul-tambahan" style="margin: 0;"></label>
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusBarang(this)">Hapus</button>
        </div>`;

    select.required = false;
    select.value = '';
    select.options[0].text = 'Pilih Barang (Opsional)';
    input.style.display = 'none';
    input.value = '';
    input.style.borderColor = '';
    errorMsg.style.display = 'none';

    container.appendChild(box);
    urutkanNomor();
    updateDropdown();
}

function hapusBarang(btn) {
    btn.closest('.barang-item-box').remove();
    urutkanNomor();
    updateDropdown();
}

function urutkanNomor() {
    document.querySelectorAll('.judul-tambahan').forEach((el, idx) => {
        el.textContent = `Barang ${idx + 4} (Tambahan):`;
    });
}

document.getElementById('formBarang').addEventListener('submit', function(e) {
    const semuaInput = document.querySelectorAll('.input-custom');
    for (let input of semuaInput) {
        if (!validasiCustom(input)) {
            e.preventDefault();
            input.focus();
            alert('Mohon perbaiki nama barang yang masih salah!');
            break;
        }
    }
});
</script>

</body>
</html>