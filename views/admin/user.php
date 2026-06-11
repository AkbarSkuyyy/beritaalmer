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

// 3. Proses Menambah Pengguna Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    
    if (!empty($username) && !empty($email) && !empty($password) && !empty($role)) {
        try {
            // Cek apakah username atau email sudah pernah didaftarkan
            $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $cek->execute([$username, $email]);
            
            if ($cek->rowCount() > 0) {
                $error = "Username atau Email tersebut sudah terdaftar! Gunakan yang lain.";
            } else {
                // Enkripsi password menggunakan sistem standar PHP yang sangat aman
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                
                $success = "Akun pengguna baru berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem database: " . $e->getMessage();
        }
    } else {
        $error = "Semua kolom input wajib diisi!";
    }
}

// 4. Proses Menghapus Pengguna
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // PROTEKSI: Mencegah admin menghapus akunnya sendiri yang sedang aktif
    if ($id_hapus === (int)$_SESSION['admin_id']) {
        $error = "Akses Ditolak: Anda tidak dapat menghapus akun Anda sendiri saat sedang login!";
    } else {
        try {
            // Opsional: Jika user ini pernah menulis berita, ID penulis di tabel berita akan diubah menjadi NULL 
            // (tergantung konstrain ON DELETE SET NULL di tabel berita Anda)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_hapus]);
            $success = "Akun pengguna berhasil dihapus permanen!";
        } catch (PDOException $e) {
            $error = "Gagal menghapus! Pastikan pengguna ini tidak memiliki keterkaitan krusial di database.";
        }
    }
}

// 5. Mengambil daftar semua pengguna
try {
    $stmt_users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $daftar_users = $stmt_users->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan saat memuat data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Users | Berita Almer</title>
    
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

        /* Grid Layout */
        .kategori-grid { display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 20px; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #1c1917; border-bottom: 1px solid #e4e4e7; padding-bottom: 12px; }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: #ff6b00; }
        .btn-submit { width: 100%; background: #121212; color: #ffffff; border: none; padding: 12px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { background: #ff6b00; }

        /* Alert Styling */
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Tabel Data */
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 15px 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; background-color: #fafafa; }
        .admin-table td { padding: 16px 12px; border-bottom: 1px solid #f4f4f5; vertical-align: middle; }
        .admin-table tr:hover { background-color: #fafafa; }
        
        .badge-role { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;}
        .role-admin { background: #fef2f2; color: #ef4444; }
        .role-user { background: #e0f2fe; color: #0ea5e9; }
        
        /* Tombol Aksi */
        .btn-action { color: #71717a; margin: 0 5px; font-size: 15px; text-decoration: none; transition: color 0.2s; display: inline-flex; width: 32px; height: 32px; background: #f4f4f5; align-items: center; justify-content: center; border-radius: 6px; }
        .btn-action.delete:hover { color: #fff; background: #ef4444; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Manajemen Akun Pengguna</h2>
            <div style="font-size: 14px; color: #71717a;">
                <i class="fa-solid fa-shield-halved" style="color: #10b981; margin-right: 5px;"></i> Sistem Keamanan Aktif
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="kategori-grid">
            
            <div class="content-card">
                <div class="card-header">Daftarkan Akun Baru</div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Cth: jhon_doe" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Alamat Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Cth: jhondoe@email.com" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi (Password)</label>
                        <input type="password" name="password" class="form-control" placeholder="Buat sandi yang kuat" required>
                    </div>
                    <div class="form-group">
                        <label>Hak Akses (Role)</label>
                        <select name="role" class="form-control" required>
                            <option value="user">Penulis Biasa (User)</option>
                            <option value="admin">Administrator Penuh (Admin)</option>
                        </select>
                    </div>
                    <button type="submit" name="tambah_user" class="btn-submit">
                        <i class="fa-solid fa-user-plus"></i> Simpan Pengguna
                    </button>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">Daftar Akun Terdaftar</div>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username / Email</th>
                                <th>Role</th>
                                <th>Bergabung Pada</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftar_users) > 0): ?>
                                <?php foreach($daftar_users as $usr): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #1c1917; margin-bottom: 3px;">
                                            <?= htmlspecialchars($usr['username']) ?>
                                        </div>
                                        <div style="font-size: 12px; color: #71717a;">
                                            <?= htmlspecialchars($usr['email']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-role <?= $usr['role'] == 'admin' ? 'role-admin' : 'role-user' ?>">
                                            <?= htmlspecialchars($usr['role']) ?>
                                        </span>
                                    </td>
                                    <td style="color: #71717a; font-size: 13px;">
                                        <?= date('d M Y, H:i', strtotime($usr['created_at'])) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($usr['id'] == $_SESSION['admin_id']): ?>
                                            <span style="font-size: 11px; font-weight: 600; color: #10b981; background: #d1fae5; padding: 4px 8px; border-radius: 4px;">ANDA</span>
                                        <?php else: ?>
                                            <a href="?hapus=<?= $usr['id'] ?>" class="btn-action delete" title="Hapus Pengguna" onclick="return confirm('PERHATIAN: Yakin ingin menghapus pengguna ini? Tindakan ini tidak bisa dibatalkan!')"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #a1a1aa; padding: 40px;">Belum ada pengguna terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>