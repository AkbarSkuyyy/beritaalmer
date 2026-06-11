<?php
// 1. Panggil koneksi database (Mundur 3 tingkat ke root directory)
require_once __DIR__ . '/../../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. PROTEKSI KEAMANAN TERTINGGI: Cek Validitas Otoritas Role
try {
    $stmt_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt_role->execute([$_SESSION['admin_id']]);
    $user_check = $stmt_role->fetch();

    if (!$user_check || $user_check['role'] !== 'superadmin') {
        header("Location: /admin/dashboard");
        exit;
    }
} catch (PDOException $e) {
    die("Sistem Keamanan Gagal Memvalidasi Kredensial: " . $e->getMessage());
}

$error = '';
$success = '';

// 4. Aksi Tambah Pengguna / Admin Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_tambah'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (!empty($username) && !empty($email) && !empty($password) && !empty($role)) {
        try {
            $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $cek->execute([$username, $email]);

            if ($cek->rowCount() > 0) {
                $error = "Identitas Gagal: Username atau Email sudah terpakai di sistem cloud.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                $success = "Aktivasi Berhasil! Akun baru (" . strtoupper($role) . ") diaktifkan.";
            }
        } catch (PDOException $e) {
            $error = "Kesalahan Query DB: " . $e->getMessage();
        }
    } else {
        $error = "Seluruh form isian mutlak wajib diisi!";
    }
}

// 5. Aksi Penghapusan Akun Pengguna
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int)$_GET['hapus_id'];

    if ($id_hapus === (int)$_SESSION['admin_id']) {
        $error = "Blokir Tindakan: Anda dilarang memusnahkan akun Superadmin Anda sendiri!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_hapus]);
            $success = "Data akun berhasil dihapus secara permanen dari server.";
        } catch (PDOException $e) {
            $error = "Gagal memutus relasi database: Akun terikat dengan artikel berita.";
        }
    }
}

// 6. Mengambil Data Statistik Pengguna untuk Tampilan Dashboard Atas
$count_super = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'superadmin'")->fetchColumn();
$count_admin = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'admin'")->fetchColumn();
$count_users = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'user'")->fetchColumn();

// 7. Tarik Data Tabel Berdasarkan Hierarki Tingkatan Akses
try {
    $stmt_all = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY FIELD(role, 'superadmin', 'admin', 'user'), id DESC");
    $daftar_pengguna = $stmt_all->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat arsitektur pengguna: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Otoritas Tertinggi | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        /* Area Kerja Sebelah Kanan (Mundur 280px untuk ruang sidebar) */
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        /* Header Banner Ungu Mewah */
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px; }

        /* Kartu Penghitung Statistik Modern */
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 35px; }
        .stat-box { background: #fff; padding: 25px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .stat-info h5 { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .stat-info h2 { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; color: #0f172a; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-red { background: #fee2e2; color: #ef4444; }
        .icon-blue { background: #e0f2fe; color: #0ea5e9; }

        /* Arsitektur Kolom Ganda */
        .master-grid { display: grid; grid-template-columns: 360px 1fr; gap: 30px; align-items: start; }
        .panel-card { background: #ffffff; border-radius: 14px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        /* Form Elemen Minimalis */
        .input-block { margin-bottom: 20px; }
        .input-block label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .field-box { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: all 0.3s; color: #0f172a; }
        .field-box:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        .master-btn { width: 100%; background: #0f172a; color: #fff; border: none; padding: 14px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.25s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .master-btn:hover { background: #7c3aed; box-shadow: 0 4px 12px rgba(124,58,237,0.3); }

        /* Notifikasi Standar */
        .toast-msg { padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .toast-err { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .toast-succ { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Desain Tabel Premium */
        .master-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .master-table th { padding: 16px 12px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background-color: #f8fafc; font-family: 'Outfit', sans-serif; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .master-table td { padding: 18px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .master-table tr:hover { background-color: #fafafa; }
        
        /* Pewarnaan Badge Kategori Hak Akses */
        .tag-role { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Outfit', sans-serif; }
        .tag-super { background: #f3e8ff; color: #7c3aed; border: 1px solid #d8b4fe; }
        .tag-admin { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
        .tag-user { background: #e0f2fe; color: #0ea5e9; border: 1px solid #bae6fd; }
        
        .trash-circle-btn { color: #64748b; font-size: 14px; text-decoration: none; transition: 0.2s; display: inline-flex; width: 34px; height: 34px; background: #f1f5f9; align-items: center; justify-content: center; border-radius: 50%; }
        .trash-circle-btn:hover { color: #fff; background: #ef4444; }

        @media (max-width: 1200px) { .master-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper class-main-content">
        
        <header class="master-banner">
            <div>
                <h2>Otoritas Super Administrator</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Gerbang pemantauan enkripsi database dan manajemen keamanan tingkat tinggi.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-crown"></i> SUPER SYSTEM ACTIVATED
            </div>
        </header>

        <section class="stat-row">
            <div class="stat-box">
                <div class="stat-info"><h5>Super Admin</h5><h2><?= $count_super ?></h2></div>
                <div class="stat-icon icon-purple"><i class="fa-solid fa-crown"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info"><h5>Administrator</h5><h2><?= $count_admin ?></h2></div>
                <div class="stat-icon icon-red"><i class="fa-solid fa-user-shield"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info"><h5>Penulis / Users</h5><h2><?= $count_users ?></h2></div>
                <div class="stat-icon icon-blue"><i class="fa-solid fa-users"></i></div>
            </div>
        </section>

        <?php if (!empty($error)): ?>
            <div class="toast-msg toast-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="toast-msg toast-succ"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="master-grid">
            
            <div class="panel-card">
                <div class="panel-title"><i class="fa-solid fa-user-shield" style="color:#7c3aed;"></i> Daftarkan Otoritas Akun</div>
                <form action="" method="POST">
                    <div class="input-block">
                        <label>Nama Pengguna (Username)</label>
                        <input type="text" name="username" class="field-box" placeholder="Cth: almer_dev" required autocomplete="off">
                    </div>
                    <div class="input-block">
                        <label>Email Resmi Pengguna</label>
                        <input type="email" name="email" class="field-box" placeholder="Cth: almer@domain.com" required autocomplete="off">
                    </div>
                    <div class="input-block">
                        <label>Kata Sandi Kuat (Password)</label>
                        <input type="password" name="password" class="field-box" placeholder="Kombinasi huruf, angka, simbol" required>
                    </div>
                    <div class="input-block">
                        <label>Derajat Tingkatan Akses (Role)</label>
                        <select name="role" class="field-box" required style="font-weight: 600;">
                            <option value="user">Penulis Standar (User)</option>
                            <option value="admin">Administrator Sistem (Admin)</option>
                            <option value="superadmin" style="color: #7c3aed; font-weight: bold;">♛ Super Administrator (Pemilik Penuh)</option>
                        </select>
                    </div>
                    <button type="submit" name="proses_tambah" class="master-btn">
                        <i class="fa-solid fa-shield-halved"></i> Validasi & Simpan Akun
                    </button>
                </form>
            </div>

            <div class="panel-card">
                <div class="panel-title"><i class="fa-solid fa-users-viewfinder" style="color:#7c3aed;"></i> Database Seluruh Jaringan Pengguna</div>
                <div style="overflow-x: auto;">
                    <table class="master-table">
                        <thead>
                            <tr>
                                <th>Kredensial Profil</th>
                                <th>Tingkat Akses</th>
                                <th>Tanggal Aktivasi</th>
                                <th style="text-align: center;">Hapus Otoritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($daftar_pengguna as $usr): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                        <?= htmlspecialchars($usr['username']) ?>
                                    </td>
                                <td>
                                    <?php 
                                        $style_badge = 'tag-user';
                                        if ($usr['role'] == 'superadmin') $style_badge = 'tag-super';
                                        elseif ($usr['role'] == 'admin') $style_badge = 'tag-admin';
                                    ?>
                                    <span class="tag-role <?= $style_badge ?>">
                                        <?php if($usr['role'] == 'superadmin') echo '<i class="fa-solid fa-crown" style="margin-right:3px;"></i>'; ?>
                                        <?= htmlspecialchars($usr['role']) ?>
                                    </span>
                                </td>
                                <td style="color: #64748b; font-size: 13px;">
                                    <?= date('d M Y, H:i', strtotime($usr['created_at'])) ?> WIB
                                </td>
                                <td style="text-align: center;">
                                    <?php if($usr['id'] == $_SESSION['admin_id']): ?>
                                        <span style="font-size: 11px; font-weight: 700; color: #059669; background: #d1fae5; padding: 5px 10px; border-radius: 6px; border: 1px solid #a7f3d0;"><i class="fa-solid fa-user-check"></i> AKTIF</span>
                                    <?php else: ?>
                                        <a href="#" class="trash-circle-btn" title="Hapus Permanen" onclick="konfirmasiHapus(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['username']) ?>')"><i class="fa-solid fa-trash-can"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
    function konfirmasiHapus(id, nama) {
        Swal.fire({
            title: 'Hapus Akun Secara Permanen?',
            text: "Pengguna '" + nama + "' akan dimusnahkan secara permanen dari server.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#1e293b',
            confirmButtonText: 'Ya, Eksekusi!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Alihkan ke URL hapus jika dikonfirmasi Ya
                window.location.href = '?superadmin&hapus_id=' + id;
            }
        });
    }
    </script>
</body>
</html>