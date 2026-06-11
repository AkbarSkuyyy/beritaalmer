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
                $error = "Identitas Gagal: Username atau Email sudah terpakai di sistem database.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                
                $success = "Aktivasi Berhasil! Akun baru dengan hak akses '" . strtoupper($role) . "' telah diaktifkan.";
            }
        } catch (PDOException $e) {
            $error = "Kesalahan Query DB: " . $e->getMessage();
        }
    } else {
        $error = "Seluruh form isian mutlak wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun Baru | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px; }

        .panel-card { background: #ffffff; border-radius: 14px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; max-width: 700px; margin: 0 auto; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }

        .input-block { margin-bottom: 25px; }
        .input-block label { display: block; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .field-box { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: all 0.3s; color: #0f172a; }
        .field-box:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        
        /* Desain Khusus Opsi Role */
        .role-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
        .role-option { border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: 0.3s; position: relative; }
        .role-option input[type="radio"] { position: absolute; opacity: 0; }
        .role-option .role-icon { font-size: 24px; margin-bottom: 8px; display: block; color: #94a3b8; transition: 0.3s; }
        .role-option .role-name { font-weight: 700; font-size: 14px; font-family: 'Outfit', sans-serif; color: #475569; transition: 0.3s; }
        
        /* Efek Menyala Saat Role Dipilih */
        .role-option:has(input[value="user"]:checked) { border-color: #0ea5e9; background: #f0f9ff; }
        .role-option:has(input[value="user"]:checked) .role-icon, .role-option:has(input[value="user"]:checked) .role-name { color: #0ea5e9; }
        
        .role-option:has(input[value="admin"]:checked) { border-color: #ef4444; background: #fef2f2; }
        .role-option:has(input[value="admin"]:checked) .role-icon, .role-option:has(input[value="admin"]:checked) .role-name { color: #ef4444; }
        
        .role-option:has(input[value="superadmin"]:checked) { border-color: #7c3aed; background: #f5f3ff; }
        .role-option:has(input[value="superadmin"]:checked) .role-icon, .role-option:has(input[value="superadmin"]:checked) .role-name { color: #7c3aed; }

        .master-btn { width: 100%; background: #0f172a; color: #fff; border: none; padding: 16px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.25s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;}
        .master-btn:hover { background: #7c3aed; box-shadow: 0 4px 12px rgba(124,58,237,0.3); }

        .toast-msg { padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .toast-err { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }

        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .role-selector { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Pembuatan Akun Jaringan</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Daftarkan identitas staf baru ke dalam pusat database cloud.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-user-shield"></i> REGISTRASI SISTEM
            </div>
        </header>

        <div class="panel-card">
            <div class="panel-title">
                <i class="fa-solid fa-address-card" style="color:#7c3aed;"></i> Formulir Otorisasi Akun
            </div>

            <?php if (!empty($error)): ?>
                <div class="toast-msg toast-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-block">
                    <label>Nama Pengguna (Username)</label>
                    <input type="text" name="username" class="field-box" placeholder="Cth: joko_editor" required autocomplete="off">
                </div>
                
                <div class="input-block">
                    <label>Email Resmi Pengguna</label>
                    <input type="email" name="email" class="field-box" placeholder="Cth: joko@domain.com" required autocomplete="off">
                </div>
                
                <div class="input-block">
                    <label>Kata Sandi (Password)</label>
                    <input type="password" name="password" class="field-box" placeholder="Gunakan sandi yang kuat..." required>
                </div>

                <div class="input-block">
                    <label>Tetapkan Derajat Akses (Pilih Salah Satu)</label>
                    <div class="role-selector">
                        <label class="role-option">
                            <input type="radio" name="role" value="user" required>
                            <span class="role-icon"><i class="fa-solid fa-pen-nib"></i></span>
                            <span class="role-name">Penulis Biasa</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="admin">
                            <span class="role-icon"><i class="fa-solid fa-user-gear"></i></span>
                            <span class="role-name">Administrator</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="superadmin">
                            <span class="role-icon"><i class="fa-solid fa-crown"></i></span>
                            <span class="role-name">Super Admin</span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="proses_tambah" class="master-btn">
                    <i class="fa-solid fa-check-to-slot"></i> Daftarkan & Simpan Akun
                </button>
            </form>
        </div>

    </main>

    <?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Otorisasi Berhasil!',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Lihat Daftar Akun',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Pindah kembali ke halaman manajemen akun
                    window.location.href = '/admin/superadmin';
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>