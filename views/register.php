<?php
$message = '';
$error = '';

// Proses registrasi saat tombol diklik
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];

    if (!empty($nama_lengkap) && !empty($username) && !empty($password)) {
        // Enkripsi password sebelum disimpan ke database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, password) VALUES (?, ?, ?)");
            $stmt->execute([$nama_lengkap, $username, $hashed_password]);
            $message = "Registrasi berhasil! Silakan <a href='/login'>Login di sini</a>.";
        } catch (PDOException $e) {
            $error = "Gagal registrasi: Username mungkin sudah terpakai.";
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
    <title>Registrasi Admin | Berita Almer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background: #f4f4f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { width: 100%; max-width: 400px; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h2 { text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-reg { width: 100%; background: #ff6b00; color: #fff; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .msg { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .error { background: #fee2e2; color: #b91c1c; }
        .success { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

<div class="box">
    <h2>Daftar Akun Baru</h2>
    
    <?php if ($error): ?> <div class="msg error"><?= $error ?></div> <?php endif; ?>
    <?php if ($message): ?> <div class="msg success"><?= $message ?></div> <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required>
        </div>
        <div class="form-group">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn-reg">Buat Akun</button>
    </form>
    <p style="text-align: center; font-size: 12px; margin-top: 15px;">Sudah punya akun? <a href="/login">Login</a></p>
</div>

</body>
</html>