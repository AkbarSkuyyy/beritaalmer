<?php
// Pastikan file config terpanggil, ini sangat aman berkat penggunaan require_once
require_once __DIR__ . '/../config.php';
global $pdo; // Memastikan koneksi database terbaca di dalam sistem Router

// Jika admin sudah login sebelumnya, langsung arahkan ke dasbor
if (isset($_SESSION['admin_id'])) {
    header("Location: /admin");
    exit;
}

$error = '';

// Jika tombol login ditekan (Form disubmit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            // Mencari data user di database berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Jika user ditemukan & password yang diketik cocok dengan password acak (hash) di database
            if ($user && password_verify($password, $user['password'])) {
                // Berhasil Login! Simpan data ke Sesi (Session)
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role']; // Sesuai dengan kolom di tabel users kita
                
                // Panggil fungsi log
                logActivity('LOGIN_SUCCESS', $user['id']);
    
                // Lemparkan ke halaman Dasbor Admin
                header("Location: /admin");
                exit;
            } else {
                $error = "Username atau Password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Berita Almer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            padding: 40px;
            border-top: 5px solid #ff6b00; /* Aksen Oren */
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 900;
            color: #121212;
            text-decoration: none;
            letter-spacing: -1px;
        }
        .login-logo span { color: #ff6b00; }
        .login-tagline {
            font-size: 13px;
            color: #71717a;
            margin-top: 5px;
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #27272a;
            margin-bottom: 8px;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a1a1aa;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #e4e4e7;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #ff6b00;
        }
        .btn-login {
            width: 100%;
            background: #121212;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #ff6b00;
        }
        .error-msg {
            background: #fef2f2;
            color: #ef4444;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #71717a;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link:hover { color: #121212; }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <a href="/" class="login-logo">BERITA<span>ALMER</span></a>
            <span class="login-tagline">Sistem Manajemen Konten</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-icon-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                </div>
            </div>
            
            <div class="form-group">
                <label>Kata Sandi</label>
                <div class="input-icon-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
                </div>
            </div>

            <button type="submit" class="btn-login">Login Akses <i class="fa-solid fa-arrow-right-to-bracket" style="margin-left: 5px;"></i></button>
        </form>
    </div>
    <a href="/" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda Publik</a>
</div>

</body>
</html>