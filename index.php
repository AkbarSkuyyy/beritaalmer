<?php
// Mulai sesi dan panggil database di gerbang utama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Menangkap URL yang sedang diakses pengguna
$request = $_SERVER['REQUEST_URI'];
$request = strtok($request, '?'); // Membuang embel-embel parameter di belakang URL
$request = rtrim($request, '/');  // Membuang garis miring di akhir URL jika ada

// 1. TANGKAP URL DINAMIS UNTUK BACA BERITA (/berita/slug-berita)
if (strpos($request, '/berita/') === 0) {
    $slug = substr($request, 8); 
    $_GET['slug'] = $slug;
    require __DIR__ . '/views/single.php';
    exit;
}

// 2. TANGKAP URL DINAMIS UNTUK EDIT BERITA (/admin/edit/123)
if (strpos($request, '/admin/edit/') === 0) {
    $id = substr($request, 12); 
    $_GET['id'] = $id;
    require __DIR__ . '/views/admin/edit.php';
    exit;
}

// 3. TANGKAP URL DINAMIS UNTUK HAPUS BERITA (/admin/hapus/123)
if (strpos($request, '/admin/hapus/') === 0) {
    $id = substr($request, 13); 
    $_GET['id'] = $id;
    require __DIR__ . '/views/admin/hapus.php';
    exit;
}

// SISTEM ROUTER STATIS: Mencocokkan URL dengan file fisik di folder views
switch ($request) {
    
    // Rute Website Publik
    case '':
    case '/':
        require __DIR__ . '/views/home.php';
        break;

    // Rute Autentikasi
    case '/login':
        require __DIR__ . '/views/login.php';
        break;
    case '/logout':
        require __DIR__ . '/views/logout.php';
        break;

    // Rute Dashboard Admin
    case '/admin':
    case '/admin/dashboard':
        require __DIR__ . '/views/admin/dashboard.php';
        break;
    case '/admin/tulis':
        require __DIR__ . '/views/admin/tulis.php';
        break;
    case '/admin/grafik':
        require __DIR__ . '/views/admin/grafik.php';
        break;
    case '/admin/berita':
        require __DIR__ . '/views/admin/berita.php';
        break;
    case '/admin/kategori':
        require __DIR__ . '/views/admin/kategori.php';
        break;
    case '/admin/users':
    case '/admin/user': // Penjagaan jika terjadi salah ketik URL
        require __DIR__ . '/views/admin/user.php';
        break;
    case '/admin/setting':
        require __DIR__ . '/views/admin/setting.php';
        break;
    case '/admin/perkembangan':
        require __DIR__ . '/views/admin/perkembangan.php';
        break;
    case '/admin/logs':
        require __DIR__ . '/views/admin/logs.php';
        break;
    case '/admin/superadmin':
        require __DIR__ . '/views/admin/superadmin/super_admin.php';
        break;

    // Jika URL yang diketik/diklik tidak ada di daftar atas (Error 404)
    default:
        http_response_code(404);
        // Memanggil halaman 404 khusus jika ada, atau menampilkan teks sederhana
        if (file_exists(__DIR__ . '/views/404.php')) {
            require __DIR__ . '/views/404.php';
        } else {
            echo "<h1 style='text-align:center; font-family:sans-serif; margin-top:50px;'>404 - Halaman Tidak Ditemukan</h1>";
            echo "<p style='text-align:center; font-family:sans-serif;'><a href='/'>Kembali ke Beranda</a></p>";
        }
        break;
}
?>