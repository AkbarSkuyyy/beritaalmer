<?php
// 1. Mengaktifkan Session System (Wajib di awal untuk sistem Login/Admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Definisi URL Utama Website (Menggunakan HTTPS sesuai standar server live)
define('BASE_URL', 'https://beritaalmer.my.id/');

// 3. Konfigurasi Kredensial Database Arenhost
$db_host = 'localhost'; 
$db_user = 'beritaal_catata60';
$db_pass = 'Catatanpublik27.'; 
$db_name = 'beritaal_beritaalmer';

// 4. Inisialisasi Koneksi Database dengan Driver PDO (PHP Data Objects)
try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mengubah error menjadi Exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil query otomatis berbentuk Array Asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Menonaktifkan emulasi agar query lebih aman dari SQL Injection
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Memastikan dukungan karakter emosional/simbol modern
    ];
    
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);

} catch (PDOException $e) {
    // Menampilkan pesan jika koneksi gagal (Sangat berguna untuk proses analisa/debugging)
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// 5. Fungsi Helper Global (SEO Friendly Slug Generator)
// Mengubah judul "Berita Baru Hari Ini!" menjadi "berita-baru-hari-ini"
if (!function_exists('createSlug')) {
    function createSlug($string) {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($string));
        return trim($slug, '-');
    }
}

function logActivity($action, $user_id = null) {
    global $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR']; // Mengambil IP Address pengunjung
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $ip_address]);
}