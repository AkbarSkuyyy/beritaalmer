<?php
require_once __DIR__ . '/../config.php';
global $pdo;

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: /");
    exit;
}

try {
    $query = "
        SELECT b.*, k.nama_kategori, u.username as penulis
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        WHERE b.slug = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$slug]);
    $berita = $stmt->fetch();

    if (!$berita) {
        http_response_code(404);
        require_once __DIR__ . '/../includes/header.php';
        echo "<h1 style='text-align:center; margin-top:100px; font-family: sans-serif;'>404 - Berita Tidak Ditemukan</h1>";
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }

    $update_views = $pdo->prepare("UPDATE berita SET views = views + 1 WHERE id = ?");
    $update_views->execute([$berita['id']]);

} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}

// --- SETUP META TAGS UNTUK WHATSAPP ---
// Mendapatkan protokol (http/https) dan nama domain secara otomatis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $protocol . $_SERVER['HTTP_HOST'];

// Menyiapkan variabel yang akan dibaca oleh header.php
$meta_title = htmlspecialchars($berita['judul']);
// Mengambil 150 karakter pertama dari konten sebagai deskripsi (tanpa tag HTML)
$meta_desc  = htmlspecialchars(substr(strip_tags($berita['konten']), 0, 150)) . '...';
$meta_url   = $domain . "/berita/" . htmlspecialchars($berita['slug']);

// WhatsApp WAJIB menggunakan URL gambar lengkap (Absolute URL)
if (!empty($berita['gambar'])) {
    $meta_image = $domain . "/assets/uploads/" . htmlspecialchars($berita['gambar']);
} else {
    // Gambar default jika berita tidak punya foto
    $meta_image = $domain . "/assets/img/logo-default.jpg"; 
}
// --------------------------------------

// --- LOGIKA KADALUARSA "BERITA UTAMA" ---
$tgl_buat = strtotime($berita['created_at']);
$selisih_hari = floor((time() - $tgl_buat) / (60 * 60 * 24));

$kategori_aktif = $berita['nama_kategori'] ?? 'Umum';
if ($kategori_aktif === 'Berita Utama' && $selisih_hari > 5) {
    $kategori_aktif = 'Berita';
}
// ----------------------------------------

// 1. MEMANGGIL HEADER ASLI ANDA
require_once __DIR__ . '/../includes/header.php';
?>

<main style="max-width: 800px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; color: #0f172a; line-height: 1.7;">
    
    <div style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
        <a href="/" style="color: #ff6b00; text-decoration: none; font-weight: 600;">Beranda</a> &raquo; <?= htmlspecialchars($kategori_aktif) ?>
    </div>

    <h1 style="font-family: 'Outfit', sans-serif; font-size: 40px; font-weight: 800; line-height: 1.2; margin-bottom: 25px; color: #0f172a;">
        <?= htmlspecialchars($berita['judul']) ?>
    </h1>

    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 20px; font-size: 14px; color: #64748b; padding-bottom: 25px; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
            <i class="fa-solid fa-user-pen" style="color:#ff6b00;"></i>
            Oleh <strong><?= htmlspecialchars($berita['penulis']) ?></strong>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
            <i class="fa-solid fa-calendar-days"></i>
            <?= date('d M Y, H:i', strtotime($berita['created_at'])) ?> WIB
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
            <i class="fa-solid fa-eye"></i>
            Dibaca <?= number_format($berita['views']) ?> kali
        </div>
    </div>

    <?php if (!empty($berita['gambar'])): ?>
        <img src="/assets/uploads/<?= htmlspecialchars($berita['gambar']) ?>" alt="Sampul Berita" style="width: 100%; max-height: 450px; object-fit: cover; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
    <?php endif; ?>

    <article style="font-size: 17px; color: #334155;">
        <?= $berita['konten'] ?>
    </article>

</main>

<?php
// 2. MEMANGGIL FOOTER ASLI ANDA
require_once __DIR__ . '/../includes/footer.php';
?>