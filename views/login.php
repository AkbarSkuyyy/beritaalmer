<?php
// Mulai sesi dan panggil database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
global $pdo;

// SMART ROUTING: Jika sudah login, langsung usir dari halaman login ke dasbor masing-masing
if (isset($_SESSION['admin_id'])) {
    $role = $_SESSION['role'] ?? 'user';
    if ($role === 'superadmin') {
        header("Location: /admin/superadmin/dashboard");
    } else {
        header("Location: /admin/dashboard");
    }
    exit;
}

$error = '';

// PROSES OTENTIKASI
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            // Cari data user berdasarkan Username ATAU Email
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan Hash Password
            if ($user && password_verify($password, $user['password'])) {
                
                // Set Variabel Sesi
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['username'] = $user['username'];

                // REKAM JEJAK (CCTV LOG)
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, ip_address, action, description) VALUES (?, ?, 'LOGIN', 'Otentikasi berhasil. Masuk ke dalam sistem portal.')");
                $log_stmt->execute([$user['id'], $ip_address]);

                // Arahkan sesuai derajat akses
                if ($user['role'] === 'superadmin') {
                    header("Location: /admin/superadmin/dashboard");
                } else {
                    header("Location: /admin/dashboard");
                }
                exit;

            } else {
                $error = "Akses Ditolak! Kredensial tidak cocok atau akun tidak ditemukan.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi gangguan pada database: " . $e->getMessage();
        }
    } else {
        $error = "Harap masukkan username/email dan kata sandi Anda.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; position: relative; }
        
        /* Ornamen Latar Belakang */
        .bg-shape-1 { position: absolute; top: -10%; left: -5%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(124,58,237,0.3) 0%, rgba(15,23,42,0) 70%); z-index: 0; }
        .bg-shape-2 { position: absolute; bottom: -10%; right: -5%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(14,165,233,0.2) 0%, rgba(15,23,42,0) 70%); z-index: 0; }

        .login-wrapper { width: 100%; max-width: 420px; padding: 20px; z-index: 10; position: relative; }
        
        .login-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 35px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        
        .brand-logo { text-align: center; margin-bottom: 30px; }
        .brand-logo h1 { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; letter-spacing: 1px; color: #f8fafc; }
        .brand-logo h1 span { color: #ff6b00; }
        .brand-logo p { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500; }

        .input-group { margin-bottom: 20px; position: relative; }
        .input-group i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px; transition: 0.3s; }
        .input-field { width: 100%; background: #0f172a; border: 1px solid #334155; color: #f8fafc; padding: 15px 15px 15px 45px; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; }
        .input-field:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.15); }
        .input-field:focus + i, .input-group:focus-within i { color: #7c3aed; }

        .btn-login { width: 100%; background: #7c3aed; color: #fff; border: none; padding: 15px; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; box-shadow: 0 4px 15px rgba(124,58,237,0.3); }
        .btn-login:hover { background: #6d28d9; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,58,237,0.4); }

        .back-link { display: block; text-align: center; margin-top: 25px; color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.2s; }
        .back-link:hover { color: #f8fafc; }
    </style>
</head>
<body>

    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-logo">
                <h1>BERITA<span>ALMER</span></h1>
                <p>Otorisasi Gerbang Jaringan</p>
            </div>

            <form action="" method="POST">
                <div class="input-group">
                    <input type="text" name="username" class="input-field" placeholder="Username atau Email" required autocomplete="off">
                    <i class="fa-solid fa-user"></i>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" class="input-field" placeholder="Kata Sandi Akses" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="btn-login">Verifikasi Identitas</button>
            </form>

            <a href="/" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda Publik</a>
        </div>
    </div>

    <?php if(!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Otentikasi Gagal',
                text: '<?= addslashes($error) ?>',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444'
            });
        </script>
    <?php endif; ?>

</body>
</html>