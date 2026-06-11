<?php
// Mulai sesi untuk mendapatkan data siapa yang sedang login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
global $pdo;

// REKAM JEJAK (CCTV LOG) SEBELUM SESI DIHANCURKAN
if (isset($_SESSION['admin_id'])) {
    try {
        $user_id = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, ip_address, action, description) VALUES (?, ?, 'LOGOUT', 'Sesi diakhiri secara manual. Keluar dari sistem.')");
        $log_stmt->execute([$user_id, $ip_address]);
    } catch (PDOException $e) {
        // Biarkan jika gagal merekam log, proses logout harus tetap berjalan
    }
}

// Hapus semua variabel sesi yang terdaftar
session_unset();

// Hancurkan sesi sepenuhnya dari browser
session_destroy();

// Tendang pengguna kembali ke halaman login
header("Location: /login");
exit;
?>