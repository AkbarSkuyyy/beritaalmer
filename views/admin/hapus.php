<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. Menangkap ID Berita yang dikirim dari index.php
$id_hapus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_hapus > 0) {
    try {
        // A. Ambil nama gambar sampul terlebih dahulu sebelum datanya dihapus
        $stmt_img = $pdo->prepare("SELECT gambar FROM berita WHERE id = ?");
        $stmt_img->execute([$id_hapus]);
        $gambar = $stmt_img->fetchColumn();

        // B. Hapus file fisik gambar dari folder uploads jika file tersebut ada
        if (!empty($gambar)) {
            $path_gambar = __DIR__ . '/../../assets/uploads/' . $gambar;
            if (file_exists($path_gambar)) {
                unlink($path_gambar);
            }
        }

        // C. Jalankan perintah hapus data berita dari database
        $stmt_del = $pdo->prepare("DELETE FROM berita WHERE id = ?");
        $stmt_del->execute([$id_hapus]);

        // D. Catat aktivitas hapus ke Audit Logs (Opsional, sesuaikan nama kolom jika ada)
        $user_id = $_SESSION['admin_id'];
        $action = "Hapus Berita ID: " . $id_hapus;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt_log->execute([$user_id, $action, $ip]);

        // Jika berhasil, langsung alihkan kembali ke halaman kelola berita
        header("Location: /admin/berita");
        exit;

    } catch (PDOException $e) {
        // Jika gagal karena masalah database, tampilkan pesan error-nya di sini
        echo "<div style='padding: 20px; font-family: sans-serif; color: #b91c1c; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; max-width: 600px; margin: 50px auto;'>";
        echo "<h3><i class='fa-solid fa-circle-exclamation'></i> Gagal Menghapus Berita!</h3>";
        echo "<p>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<hr style='border-color: #fecaca;'>";
        echo "<p><a href='/admin/berita' style='color: #b91c1c; font-weight: bold;'>Kembali ke Kelola Berita</a></p>";
        echo "</div>";
        exit;
    }
} else {
    // Jika ID tidak valid, langsung kembalikan ke halaman berita
    header("Location: /admin/berita");
    exit;
}
?>