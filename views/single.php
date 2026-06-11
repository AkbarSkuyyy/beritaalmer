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

// --- SETUP META TAGS UNTUK WHATSAPP (UPDATE ANTI-GAGAL) ---
$domain = rtrim(BASE_URL, '/');

$meta_title = htmlspecialchars($berita['judul']);
$meta_desc  = htmlspecialchars(substr(strip_tags($berita['konten']), 0, 150)) . '...';
$meta_url   = $domain . "/berita/" . htmlspecialchars($berita['slug']);

if (!empty($berita['gambar'])) {
    $meta_image = $domain . "/assets/uploads/" . htmlspecialchars($berita['gambar']);
} else {
    $meta_image = $domain . "/assets/img/logo-default.jpg"; 
}
// -----------------------------------------------------------

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

<div class="content-wrapper" style="margin-top: 20px;">
    
    <div class="main-column" style="background: #ffffff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        
        <div style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
            <a href="/" style="color: #ff6b00; text-decoration: none; font-weight: 600;">Beranda</a> &raquo; <?= htmlspecialchars($kategori_aktif) ?>
        </div>

        <h1 style="font-family: 'Outfit', sans-serif; font-size: 38px; font-weight: 800; line-height: 1.25; margin-bottom: 25px; color: #0f172a; letter-spacing: -0.5px;">
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
            <img src="/assets/uploads/<?= htmlspecialchars($berita['gambar']) ?>" alt="Sampul Berita" style="width: 100%; max-height: 480px; object-fit: cover; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
        <?php endif; ?>

        <article style="font-size: 17px; color: #334155; line-height: 1.8; font-family: 'Inter', sans-serif;">
            <?= $berita['konten'] ?>
        </article>

        <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #e2e8f0;">
            <span style="display: block; margin-bottom: 12px; font-weight: 700; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 15px;">
                <i class="fa-solid fa-share-nodes" style="color: #ff6b00; margin-right: 5px;"></i> Bagikan Berita Ini:
            </span>
            
            <?php 
            $teks_wa = $berita['judul'] . " \n\nBaca selengkapnya di: " . $meta_url;
            $link_wa = "https://api.whatsapp.com/send?text=" . urlencode($teks_wa);
            ?>
            
            <a href="<?= $link_wa ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background-color: #25D366; color: white; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fa-brands fa-whatsapp" style="font-size: 18px;"></i> WhatsApp
            </a>
        </div>

    </div> <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

</div> <?php
// 2. MEMANGGIL FOOTER ASLI ANDA
require_once __DIR__ . '/../includes/footer.php';
?>