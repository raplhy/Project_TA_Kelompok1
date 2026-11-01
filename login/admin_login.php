<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/functions.php';

// Kalau sudah login sebagai admin, langsung ke dashboard admin
if (is_admin()) {
    redirect('../DashboardAdmin/index.php');
}

$error_msg = get_flash('error');
$filled_username = $_SESSION['form_username'] ?? '';
unset($_SESSION['form_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - LOKIFY</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }

    /* BACKGROUND KREM (Sama seperti Dashboard) */
    body {
        background-color: #F4EFE6;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    /* Kotak login PUTIH BERSIH */
    .card-login {
        width: 100%;
        max-width: 450px;
        background: #FFFFFF;
        border-radius: 12px;
        border: 1px solid #E8E3D9;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-header-custom {
        padding: 35px 20px 25px 20px;
        width: 100%;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .card-header-custom img {
        height: 70px;
        width: auto;
        margin-bottom: 15px;
        display: block;
        object-fit: contain;
    }

    .card-header-custom h1 {
        font-size: 18px;
        font-weight: 800;
        color: #181715; /* Teks Gelap */
        line-height: 1.3;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        text-align: center;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .card-header-custom h1 span { color: #E04F16; } /* Aksen Oranye */

    .role-title {
        width: 100%;
        padding: 12px;
        background: #FAF8F5; /* Krem muda */
        border-top: 1px solid #E8E3D9;
        border-bottom: 1px solid #E8E3D9;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #C85A17; /* Coklat Oranye */
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .card-body-inner {
        width: 100%;
        padding: 25px 35px 30px 35px;
    }

    .alert-error {
        background: #F9E2DF; border: 1px solid #DFA69F; color: #B8473E;
        padding: 10px; border-radius: 6px; font-size: 12px;
        margin-bottom: 15px; text-align: center;
    }

    .form-group { margin-bottom: 15px; width: 100%; }
    
    .form-group label {
        display: block; font-size: 12px; font-weight: 600;
        color: #1E1D1B; margin-bottom: 6px;
    }

    .input-group { position: relative; display: flex; align-items: center; width: 100%; }
    
    .input-group i {
        position: absolute; left: 12px; color: #9E988E; font-size: 13px;
    }
    
    .input-control {
        width: 100%; padding: 10px 12px 10px 35px;
        font-size: 13px; border: 1px solid #D8D2C7;
        border-radius: 6px; outline: none;
        color: #1E1D1B; background: #FFFFFF;
    }
    
    .input-control:focus { border-color: #E04F16; background: #FFFFFF; }
    
    .input-control::placeholder { color: #9E988E; }

    .btn-submit {
        width: 100%; padding: 11px; background: #E04F16; /* Tombol Oranye */
        color: #FFFFFF; border: none; border-radius: 6px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        margin-top: 10px; transition: background 0.2s;
    }
    
    .btn-submit:hover { background: #C84310; }

    .card-footer-custom {
        width: 100%; padding: 15px 35px 25px 35px;
        text-align: center; font-size: 11px; color: #9E988E;
    }
    </style>
</head>
<body>

    <div class="card-login">

        <div class="card-header-custom">
            <img src="logoLOKIFY.png" alt="Logo LOKIFY" onerror="this.style.display='none'">
            <h1>SISTEM PENDATAAN<br><span>BARANG LOKER</span> SEKOLAH</h1>
        </div>

        <div class="role-title">
            <i class="fa-solid fa-user-shield"></i> Administrator
        </div>

        <div class="card-body-inner">

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_process.php">
                <input type="hidden" name="role" value="admin">

                <div class="form-group">
                    <label>Username Admin</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user-gear"></i>
                        <input type="text" name="username" class="input-control"
                               placeholder="Masukkan Username Admin"
                               value="<?php echo htmlspecialchars($filled_username); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" class="input-control"
                               placeholder="Masukkan Password" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> Login Admin
                </button>
            </form>

        </div>

        <div class="card-footer-custom">
            &copy; 2026 SMK PGRI 3 Malang. All Rights Reserved.
        </div>

    </div>

</body>
</html> 