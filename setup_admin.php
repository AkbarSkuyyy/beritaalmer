<?php
// Panggil koneksi database
require_once __DIR__ . '/config.php';

// ==========================================
// SILAKAN UBAH DATA ADMIN DI BAWAH INI
// ==========================================
$username = 'admin';
$email    = 'admin@beritaalmer.my.id';
$password = 'admin'; // Ubah dengan kata sandi rahasia Anda
$role     = 'admin';
// ==========================================

// Mengenkripsi kata sandi agar aman (standar keamanan PHP)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Mengecek apakah email atau username sudah ada agar tidak duplikat
    $cek = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $cek->execute([$username, $email]);
    
    if ($cek->rowCount() > 0) {
        echo "<h2 style='color: orange; font-family: sans-serif;'>Akun admin dengan username atau email tersebut sudah pernah dibuat!</h2>";
        echo "<a href='/login'>Kembali ke Halaman Login</a>";
    } else {
        // Memasukkan data admin ke database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $role]);
        
        echo "<div style='font-family: sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; border: 2px solid green; border-radius: 8px;'>";
        echo "<h2 style='color: green;'>✅ Akun Admin Berhasil Dibuat!</h2>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "<hr>";
        echo "<p style='color: red; font-weight: bold;'>⚠️ SANGAT PENTING: Segera hapus file <code>setup_admin.php</code> ini dari cPanel Anda sekarang juga agar tidak disalahgunakan oleh peretas!</p>";
        echo "<br><a href='/login' style='padding: 10px 15px; background: #121212; color: white; text-decoration: none; border-radius: 5px;'>Menuju Halaman Login</a>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<h2 style='color: red; font-family: sans-serif;'>Terjadi Kesalahan Database:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>