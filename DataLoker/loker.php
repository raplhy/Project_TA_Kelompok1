<?php
session_start();
include "koneksi.php";

/* =========================================================
   DATA LOKER + DATA GEDUNG
   ========================================================= */

/* Filter gedung (0 = semua gedung) */
$filter_gedung = (int) ($_GET['gedung'] ?? 0);

/* Pencarian kode loker */
$cari = trim($_GET['cari'] ?? '');

$cari_aman = mysqli_real_escape_string($koneksi, $cari);

/* Gabungkan filter gedung + pencarian */
$kondisi = [];

if ($filter_gedung) {
    $kondisi[] = "loker.id_gedung = $filter_gedung";
}

if ($cari !== '') {
    $kondisi[] = "loker.nomor_loker LIKE '%$cari_aman%'";
}

$where = $kondisi ? "WHERE " . implode(" AND ", $kondisi) : "";

$data = mysqli_query($koneksi, "
    SELECT 
        loker.id_loker,
        loker.nomor_loker,
        loker.id_gedung,
        gedung.nama_gedung,
        loker.status
    FROM loker
    INNER JOIN gedung 
        ON loker.id_gedung = gedung.id_gedung
    $where
    ORDER BY loker.nomor_loker ASC
");


/* =========================================================
   STATISTIK LOKER
   ========================================================= */

$total = mysqli_num_rows(
    mysqli_query($koneksi, "SELECT id_loker FROM loker")
);

$kosong = mysqli_num_rows(
    mysqli_query(
        $koneksi,
        "SELECT id_loker FROM loker WHERE status = 'Kosong'"
    )
);

$terisi = mysqli_num_rows(
    mysqli_query(
        $koneksi,
        "SELECT id_loker FROM loker WHERE status != 'Kosong'"
    )
);


/* =========================================================
   DAFTAR GEDUNG
   ========================================================= */

$daftar_gedung = mysqli_query(
    $koneksi,
    "
    SELECT id_gedung, nama_gedung
    FROM gedung
    ORDER BY id_gedung ASC
    "
);


/* =========================================================
   DAFTAR GEDUNG UNTUK MODAL EDIT
   ========================================================= */

$daftar_gedung_edit = mysqli_query(
    $koneksi,
    "
    SELECT id_gedung, nama_gedung
    FROM gedung
    ORDER BY id_gedung ASC
    "
);


/* =========================================================
   DAFTAR GEDUNG UNTUK DROPDOWN FILTER
   ========================================================= */

$daftar_gedung_filter = mysqli_query(
    $koneksi,
    "
    SELECT id_gedung, nama_gedung
    FROM gedung
    ORDER BY id_gedung ASC
    "
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Data Loker - LokerKu</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css"
    >

</head>

<body>

<div class="layout">


    <!-- ================================================= -->
    <!-- SIDEBAR -->
    <!-- ================================================= -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                L
            </div>

            <div>

                <h2>LokerKu</h2>

                <span>
                    School System
                </span>

            </div>

        </div>


        <div class="menu-title">
            MENU UTAMA
        </div>


        <a href="#" class="menu active">

            <span>
                ▦
            </span>

            Data Loker

        </a>

    </aside>


    <!-- ================================================= -->
    <!-- CONTENT -->
    <!-- ================================================= -->

    <main class="content">


        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <p class="welcome">
                    Selamat datang kembali, Admin 👋
                </p>

                <h1>
                    Manajemen Loker
                </h1>

            </div>


            <div class="profile">

                <div class="profile-icon">
                    A
                </div>

                <div>

                    <strong>
                        Admin
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>

        </div>



        <!-- ================================================= -->
        <!-- STATISTIK -->
        <!-- ================================================= -->

        <div class="statistics">


            <div class="stat-card">

                <div class="stat-icon blue">
                    ▣
                </div>

                <div>

                    <p>
                        Total Loker
                    </p>

                    <h2>
                        <?= $total ?>
                    </h2>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon green">
                    ✓
                </div>

                <div>

                    <p>
                        Loker Kosong
                    </p>

                    <h2>
                        <?= $kosong ?>
                    </h2>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon red">
                    !
                </div>

                <div>

                    <p>
                        Loker Terisi
                    </p>

                    <h2>
                        <?= $terisi ?>
                    </h2>

                </div>

            </div>


        </div>



        <!-- ================================================= -->
        <!-- DATA LOKER -->
        <!-- ================================================= -->

        <div class="data-card">


            <div class="data-header">

                <div>

                    <h2>
                        Data Loker
                    </h2>

                    <p>
                        Daftar seluruh loker yang tersedia di sekolah
                    </p>

                </div>


                <div class="header-actions">

                    <!-- FILTER PER GEDUNG -->

                    <form method="GET" action="loker.php">

                        <select
                            name="gedung"
                            class="filter-gedung"
                            onchange="this.form.submit()"
                        >

                            <option value="0">
                                Semua Gedung
                            </option>

                            <?php while (
                                $g = mysqli_fetch_assoc(
                                    $daftar_gedung_filter
                                )
                            ): ?>

                                <option
                                    value="<?= $g['id_gedung'] ?>"
                                    <?= $filter_gedung == $g['id_gedung'] ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $g['nama_gedung']
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                        <!-- PENCARIAN KODE LOKER -->

                        <input
                            type="search"
                            name="cari"
                            class="cari-loker"
                            placeholder="Cari kode loker..."
                            value="<?= htmlspecialchars($cari) ?>"
                        >

                        <button
                            type="submit"
                            class="btn-cari"
                        >
                            Cari
                        </button>

                        <?php if ($cari !== '' || $filter_gedung): ?>

                            <a href="loker.php" class="btn-reset">
                                Reset
                            </a>

                        <?php endif; ?>

                    </form>


                    <button
                        type="button"
                        class="btn-add"
                        onclick="bukaModalTambah()"
                    >
                        + Tambah Loker
                    </button>

                </div>

            </div>



            <!-- ================================================= -->
            <!-- TABEL -->
            <!-- ================================================= -->

            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                NO
                            </th>

                            <th>
                                KODE LOKER
                            </th>

                            <th>
                                LOKASI
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th>
                                ACTION
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (mysqli_num_rows($data) == 0): ?>

                        <tr>
                            <td colspan="5" class="empty-row">
                                Tidak ada loker yang cocok.
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php

                    $no = 1;

                    mysqli_data_seek($data, 0);

                    while ($row = mysqli_fetch_assoc($data)):

                        /*
                         * Data untuk dikirim ke JavaScript
                         */

                        $js_id = (int) $row['id_loker'];

                        $js_nomor = htmlspecialchars(
                            json_encode($row['nomor_loker']),
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $js_id_gedung = (int) $row['id_gedung'];

                        $js_status = htmlspecialchars(
                            json_encode($row['status']),
                            ENT_QUOTES,
                            'UTF-8'
                        );

                    ?>

                        <tr>


                            <!-- NO -->

                            <td>
                                <?= $no++ ?>
                            </td>



                            <!-- NOMOR LOKER -->

                            <td>

                                <strong class="kode">

                                    <?= htmlspecialchars(
                                        $row['nomor_loker']
                                    ) ?>

                                </strong>

                            </td>



                            <!-- GEDUNG -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_gedung']
                                ) ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if ($row['status'] == 'Kosong'): ?>

                                    <span class="status kosong">
                                        ● Kosong
                                    </span>

                                <?php else: ?>

                                    <span class="status terisi">
                                        ● Terisi
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <div class="actions">


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="btn-edit"
                                        onclick="bukaModalEdit(
                                            <?= $js_id ?>,
                                            <?= $js_nomor ?>,
                                            <?= $js_id_gedung ?>,
                                            <?= $js_status ?>
                                        )"
                                    >
                                        Edit
                                    </button>



                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="btn-delete"
                                        onclick="bukaModalHapus(
                                            <?= $js_id ?>,
                                            <?= $js_nomor ?>
                                        )"
                                    >
                                        Delete
                                    </button>


                                </div>

                            </td>


                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>



<!-- ================================================= -->
<!-- MODAL TAMBAH LOKER -->
<!-- ================================================= -->

<div
    class="modal-overlay"
    id="modalTambah"
>

    <div class="modal-card">


        <div class="modal-header">

            <div>

                <h2>
                    Tambah Loker
                </h2>

                <p>
                    Tambahkan data loker baru
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="tutupModal()"
            >
                ×
            </button>

        </div>



        <form
            action="tambah_loker.php"
            method="POST"
        >


            <!-- NOMOR LOKER -->

            <div class="form-group">

                <label>
                    Nomor Loker
                </label>

                <input
                    type="text"
                    name="nomor_loker"
                    placeholder="Contoh: A001"
                    required
                >

            </div>



            <!-- GEDUNG -->

            <div class="form-group">

                <label>
                    Gedung
                </label>

                <select
                    name="id_gedung"
                    required
                >

                    <option
                        value=""
                        disabled
                        selected
                    >
                        -- Pilih Gedung --
                    </option>


                    <?php while (
                        $gedung = mysqli_fetch_assoc(
                            $daftar_gedung
                        )
                    ): ?>

                        <option
                            value="<?= $gedung['id_gedung'] ?>"
                        >

                            <?= htmlspecialchars(
                                $gedung['nama_gedung']
                            ) ?>

                        </option>

                    <?php endwhile; ?>


                </select>

            </div>



            <!-- STATUS -->

            <div class="status-info">

                <span class="status kosong">
                    ● Kosong
                </span>

                <p>

                    Loker baru otomatis berstatus
                    <strong>Kosong</strong>.

                    Status akan berubah otomatis ketika
                    user mengisi barang.

                </p>

            </div>



            <!-- ACTION -->

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn-cancel"
                    onclick="tutupModal()"
                >
                    Batal
                </button>


                <button
                    type="submit"
                    name="simpan"
                    class="btn-save"
                >
                    Simpan Loker
                </button>

            </div>


        </form>

    </div>

</div>



<!-- ================================================= -->
<!-- MODAL EDIT LOKER -->
<!-- ================================================= -->

<div
    class="modal-overlay"
    id="modalEdit"
>

    <div class="modal-card">


        <div class="modal-header">

            <div>

                <h2>
                    Edit Loker
                </h2>

                <p>
                    Ubah informasi loker
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="tutupEdit()"
            >
                ×
            </button>

        </div>



        <form
            action="edit_loker.php"
            method="POST"
        >


            <!-- ID LOKER -->

            <input
                type="hidden"
                name="id_loker"
                id="edit_id_loker"
            >



            <!-- NOMOR LOKER -->

            <div class="form-group">

                <label>
                    Nomor Loker
                </label>

                <input
                    type="text"
                    name="nomor_loker"
                    id="edit_nomor_loker"
                    required
                >

            </div>



            <!-- GEDUNG -->

            <div class="form-group">

                <label>
                    Gedung
                </label>

                <select
                    name="id_gedung"
                    id="edit_lokasi"
                    required
                >

                    <?php while (
                        $gedung = mysqli_fetch_assoc(
                            $daftar_gedung_edit
                        )
                    ): ?>

                        <option
                            value="<?= $gedung['id_gedung'] ?>"
                        >

                            <?= htmlspecialchars(
                                $gedung['nama_gedung']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>



            <!-- STATUS -->

            <div class="status-info">

                <span
                    class="status"
                    id="edit_status_tampilan"
                >
                    ● Status
                </span>

                <p>

                    Status loker tidak dapat diubah
                    oleh admin.

                    Status akan ditentukan otomatis
                    berdasarkan barang user.

                </p>

            </div>



            <!-- ACTION -->

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn-cancel"
                    onclick="tutupEdit()"
                >
                    Batal
                </button>


                <button
                    type="submit"
                    name="edit"
                    class="btn-save"
                >
                    Simpan Perubahan
                </button>

            </div>


        </form>

    </div>

</div>



<!-- ================================================= -->
<!-- MODAL HAPUS LOKER -->
<!-- ================================================= -->

<div
    class="modal-overlay"
    id="modalHapus"
>

    <div class="modal-card delete-card">


        <div class="delete-icon">
            !
        </div>


        <h2>
            Hapus Loker?
        </h2>


        <p>

            Apakah kamu yakin ingin menghapus loker

            <strong id="namaLokerHapus"></strong>?

        </p>


        <div class="modal-actions">


            <button
                type="button"
                class="btn-cancel"
                onclick="tutupHapus()"
            >
                Batal
            </button>


            <a
                href="#"
                id="linkHapus"
                class="btn-delete-confirm"
            >
                Ya, Hapus
            </a>


        </div>


    </div>

</div>



<!-- ================================================= -->
<!-- JAVASCRIPT -->
<!-- ================================================= -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>

<script src="script.js"></script>


</body>

</html>