<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

$error = '';
$success = '';
$admin_id = $_SESSION['admin_id'];

// 3. Mengambil data terbaru admin yang sedang login
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
} catch (PDOException $e) {
    die("Gagal memuat data pengaturan: " . $e->getMessage());
}

// 4. Proses Update Pengaturan Profil & Password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        
        if (!empty($email)) {
            try {
                // Cek apakah email sudah digunakan oleh orang lain
                $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $cek->execute([$email, $admin_id]);
                
                if ($cek->rowCount() > 0) {
                    $error = "Email tersebut sudah digunakan oleh akun lain!";
                } else {
                    // Update email di database
                    $update = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $update->execute([$email, $admin_id]);
                    
                    $success = "Profil berhasil diperbarui!";
                    $admin['email'] = $email; // Perbarui tampilan email di form
                }
            } catch (PDOException $e) {
                $error = "Gagal memperbarui profil: " . $e->getMessage();
            }
        } else {
            $error = "Email tidak boleh kosong!";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_baru = $_POST['konfirmasi_baru'];
        
        if (!empty($password_lama) && !empty($password_baru) && !empty($konfirmasi_baru)) {
            try {
                // Ambil password hash lama dari database
                $stmt_pass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt_pass->execute([$admin_id]);
                $hash_lama = $stmt_pass->fetchColumn();
                
                // Verifikasi apakah password lama yang diketik sudah benar
                if (password_verify($password_lama, $hash_lama)) {
                    if ($password_baru === $konfirmasi_baru) {
                        // Enkripsi password baru
                        $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                        
                        $update_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_pass->execute([$hash_baru, $admin_id]);
                        
                        $success = "Kata sandi berhasil diubah!";
                    } else {
                        $error = "Konfirmasi kata sandi baru tidak cocok!";
                    }
                } else {
                    $error = "Kata sandi lama yang Anda masukkan salah!";
                }
            } catch (PDOException $e) {
                $error = "Gagal mengubah kata sandi: " . $e->getMessage();
            }
        } else {
            $error = "Semua kolom kata sandi wajib diisi!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Menu (Dipertahankan untuk styling file sidebar.php) */
        .admin-sidebar { width: 260px; background-color: #18181b; color: #ffffff; padding: 25px 15px; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .admin-logo { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 35px; padding-left: 10px; }
        .admin-logo span { color: #ff6b00; }
        .admin-menu { list-style: none; flex-grow: 1; }
        .admin-menu li { margin-bottom: 8px; }
        .admin-menu a { display: flex; align-items: center; gap: 12px; color: #a1a1aa; text-decoration: none; padding: 12px 15px; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .admin-menu a:hover, .admin-menu a.active { background-color: #27272a; color: #ffffff; }
        .admin-menu a.active { border-left: 4px solid #ff6b00; padding-left: 11px; }
        .menu-divider { color: #52525b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 10px 15px; }
        .admin-logout a { display: flex; align-items: center; gap: 12px; color: #ef4444; text-decoration: none; padding: 12px 15px; font-size: 14px; font-weight: 600; }

        /* Area Konten Utama */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }

        /* Layout Grid Dua Kolom Pengaturan */
        .setting-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; align-items: start; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 25px; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #1c1917; border-bottom: 1px solid #e4e4e7; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }

        /* Form Controls */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; background-color: #ffffff; }
        .form-control:focus { border-color: #ff6b00; }
        .form-control:disabled { background-color: #f4f4f5; color: #a1a1aa; cursor: not-allowed; }
        
        .btn-submit { background: #121212; color: #ffffff; border: none; padding: 12px 20px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #ff6b00; }

        /* Alert Notifikasi */
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 25px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Pengaturan Keamanan Akun</h2>
            <div style="font-size: 14px; color: #71717a;"><i class="fa-solid fa-user-shield"></i> Profile & Security</div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="setting-grid">
            
            <div class="content-card">
                <div class="card-header"><i class="fa-solid fa-id-card" style="color: #ff6b00;"></i> Informasi Akun</div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Username (Tidak dapat diubah)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Alamat Email Resmi</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required autocomplete="off">
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
                    </button>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header"><i class="fa-solid fa-key" style="color: #ff6b00;"></i> Ganti Kata Sandi</div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Kata Sandi Lama</label>
                        <input type="password" name="password_lama" class="form-control" placeholder="Masukkan kata sandi saat ini" required>
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi Baru</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Buat kata sandi baru yang kuat" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="konfirmasi_baru" class="form-control" placeholder="Ulangi kata sandi baru" required>
                    </div>
                    <button type="submit" name="update_password" class="btn-submit">
                        <i class="fa-solid fa-shield-keyhole"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>

        </div>
    </main>

</body>
</html>