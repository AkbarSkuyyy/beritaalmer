<?php
// Mulai sesi
session_start();

require_once __DIR__ . '/../config.php';
global $pdo;

// Jika sudah login, langsung arahkan ke tempat yang seharusnya
if (isset($_SESSION['admin_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
        header("Location: /admin/superadmin");
    } else {
        header("Location: /admin/dashboard");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            // Cari data pengguna di database berdasarkan username
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan password dengan hash yang ada di database
            if ($user && password_verify($password, $user['password'])) {
                
                // Mendaftarkan identitas ke dalam Sesi (Session)
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Pengalihan cerdas berdasarkan hak akses
                if ($user['role'] === 'superadmin') {
                    header("Location: /admin/superadmin");
                } else {
                    header("Location: /admin/dashboard");
                }
                exit;
            } else {
                $error = "Kredensial ditolak! Username atau kata sandi tidak valid.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi gangguan pada database: " . $e->getMessage();
        }
    } else {
        $error = "Kolom username dan kata sandi wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerbang Akses | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
            color: #0f172a; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 900;
            color: #0f172a;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .brand-logo span { color: #ff6b00; }
        
        .login-subtitle {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px; /* Padding kiri lebih besar untuk ikon */
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
            color: #0f172a;
        }

        .form-control:focus {
            border-color: #ff6b00;
            box-shadow: 0 0 0 4px rgba(255,107,0,0.1);
        }

        /* Saat input fokus, ikon di dalamnya ikut menyala */
        .form-control:focus + i, 
        .input-icon-wrapper:focus-within i {
            color: #ff6b00;
        }

        .btn-login {
            width: 100%;
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #ff6b00;
            box-shadow: 0 4px 12px rgba(255,107,0,0.3);
            transform: translateY(-2px);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover { color: #ff6b00; }

    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="login-header">
                <a href="/" class="brand-logo">BERITA<span>ALMER</span></a>
                <p class="login-subtitle">Silakan masuk ke panel kontrol Anda</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <div class="form-group">
                    <label for="username">Username Akses</label>
                    <div class="input-icon-wrapper">
                        <input type="text" name="username" id="username" class="form-control" placeholder="Ketik username Anda..." required autocomplete="off">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-icon-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Ketik kata sandi Anda..." required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    Otorisasi Masuk <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>

            </form>

            <a href="/" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda Publik</a>

        </div>
    </div>

</body>
</html>