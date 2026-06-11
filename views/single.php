<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../config.php';
global $pdo;

// 2. Ambil parameter slug dari URL (Disediakan oleh index.php)
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: /");
    exit;
}

// 3. Tarik Data Berita berdasarkan Slug
try {
    $stmt = $pdo->prepare("
        SELECT b.*, k.nama_kategori, u.username as penulis 
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        WHERE b.slug = ?
    ");
    $stmt->execute([$slug]);
    $berita = $stmt->fetch();

    // Jika berita tidak ditemukan, arahkan ke 404
    if (!$berita) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

    // 4. Tambahkan +1 pada jumlah Views
    $stmt_views = $pdo->prepare("UPDATE berita SET views = views + 1 WHERE id = ?");
    $stmt_views->execute([$berita['id']]);

    // 5. Tarik Data untuk Sidebar (Populer & Kategori)
    $stmt_populer = $pdo->query("SELECT judul, slug, views, created_at FROM berita WHERE id != {$berita['id']} ORDER BY views DESC LIMIT 5");
    $berita_populer = $stmt_populer->fetchAll();

    $stmt_kategori = $pdo->query("SELECT nama_kategori, slug FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmt_kategori->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan database: " . $e->getMessage());
}

// 6. Siapkan Variabel SEO untuk header.php
$meta_title = $berita['judul'];
// Potong konten untuk deskripsi (hilangkan tag HTML)
$meta_desc = mb_strimwidth(strip_tags($berita['konten']), 0, 150, '...');
// Siapkan URL gambar utuh (pastikan menggunakan protokol domain yang benar)
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$meta_image = !empty($berita['gambar']) ? $domain . '/assets/uploads/' . $berita['gambar'] : $domain . '/assets/img/default-share.jpg';
$meta_url = $domain . '/berita/' . $berita['slug'];

// Panggil Header
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .single-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    .single-grid { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }
    
    .article-wrapper { background: #ffffff; padding: 40px; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .breadcrumb { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .breadcrumb a { color: #ff6b00; text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    
    .article-title { font-family: 'Outfit', sans-serif; font-size: 36px; font-weight: 800; color: #0f172a; line-height: 1.3; margin-bottom: 25px; }
    
    .article-meta { display: flex; align-items: center; gap: 20px; padding: 20px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; margin-bottom: 30px; font-size: 14px; color: #64748b; font-weight: 500; flex-wrap: wrap; }
    .meta-item { display: flex; align-items: center; gap: 8px; }
    .meta-item i { color: #ff6b00; }
    
    .article-cover { width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 12px; margin-bottom: 35px; background: #e2e8f0; }
    
    /* FIX KHUSUS iOS/SAFARI & LAYAR KECIL */
    .article-body { 
        font-size: 17px; 
        line-height: 1.8; 
        color: #334155; 
        /* Tiga baris sakti untuk mencegah URL panjang merusak lebar web */
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
    }
    
    .article-body p { margin-bottom: 20px; }
    .article-body h2, .article-body h3, .article-body h4 { font-family: 'Outfit', sans-serif; color: #0f172a; margin: 30px 0 15px; font-weight: 700; line-height: 1.4; }
    
    /* Mengurung gambar, iframe, video dari TinyMCE agar tidak keluar layar */
    .article-body img, .article-body iframe, .article-body video { 
        max-width: 100% !important; 
        height: auto !important; 
        border-radius: 8px; 
        margin: 20px 0; 
    }
    
    /* Memaksa tabel besar (jika ada) bisa di-scroll horizontal alih-alih merusak lebar layar */
    .article-body table {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .article-body a { color: #0ea5e9; text-decoration: none; font-weight: 600; }
    .article-body a:hover { text-decoration: underline; }
    .article-body blockquote { border-left: 5px solid #ff6b00; background: #f8fafc; padding: 20px; font-style: italic; color: #475569; margin: 30px 0; border-radius: 0 8px 8px 0; }

    /* Sidebar Styles */
    .sidebar-widget { background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: sticky; top: 90px; }
    .widget-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
    
    .populer-item { display: flex; gap: 15px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px dashed #e2e8f0; }
    .populer-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .populer-number { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .populer-title { font-size: 14px; font-weight: 600; color: #1e293b; text-decoration: none; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }
    .populer-title:hover { color: #ff6b00; }
    .populer-meta { font-size: 12px; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 10px; }

    .cat-list { list-style: none; }
    .cat-list li { margin-bottom: 10px; }
    .cat-list a { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: #f8fafc; border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; border: 1px solid #f1f5f9; }
    .cat-list a:hover { background: #ff6b00; color: #fff; border-color: #ff6b00; }

    /* =========================================
       CSS RESPONSIF (TABLET & SMARTPHONE)
       ========================================= */
    @media (max-width: 992px) {
        .single-grid { grid-template-columns: 1fr; }
        .sidebar-widget { position: static; margin-top: 20px; }
    }

    @media (max-width: 768px) {
        /* PENGUNCI EKSTRA: Memastikan halaman utama tidak tergeser ke samping di HP */
        body, html { overflow-x: hidden; width: 100%; }
        .single-container { padding: 0 15px; margin: 25px auto; overflow: hidden; width: 100%; }
        .article-wrapper { padding: 25px 20px; overflow: hidden; width: 100%; }
        
        .article-title { font-size: 26px; margin-bottom: 15px; }
        .article-meta { gap: 15px; padding: 15px 0; margin-bottom: 25px; font-size: 13px; flex-direction: column; align-items: flex-start; }
        
        .article-cover { max-height: 250px; margin-bottom: 25px; border-radius: 8px; }
        
        .article-body { font-size: 16px; line-height: 1.7; }
        .article-body h2 { font-size: 22px; }
        .article-body h3 { font-size: 20px; }
        .article-body blockquote { padding: 15px; font-size: 15px; margin: 20px 0; }
    }
</style>

<div class="single-container">
    <div class="single-grid">
        
        <main class="article-wrapper">
            
            <div class="breadcrumb">
                <a href="/"><i class="fa-solid fa-house"></i> Beranda</a>
                <i class="fa-solid fa-angle-right" style="color:#cbd5e1; font-size:10px;"></i>
                <a href="/?kategori=<?= htmlspecialchars($berita['slug'] ?? '') ?>"><?= htmlspecialchars($berita['nama_kategori'] ?? 'Umum') ?></a>
                <i class="fa-solid fa-angle-right" style="color:#cbd5e1; font-size:10px;"></i>
                <span style="color:#94a3b8;">Artikel</span>
            </div>

            <h1 class="article-title"><?= htmlspecialchars($berita['judul']) ?></h1>

            <div class="article-meta">
                <div class="meta-item">
                    <i class="fa-solid fa-user-pen"></i> Ditulis oleh <strong><?= htmlspecialchars($berita['penulis']) ?></strong>
                </div>
                <div class="meta-item">
                    <i class="fa-regular fa-calendar-days"></i> <?= date('l, d F Y - H:i', strtotime($berita['created_at'])) ?> WIB
                </div>
                <div class="meta-item" style="color: #0ea5e9;">
                    <i class="fa-solid fa-eye"></i> <?= number_format($berita['views']) ?>x Dibaca
                </div>
            </div>

            <?php if (!empty($berita['gambar'])): ?>
                <img src="/assets/uploads/<?= htmlspecialchars($berita['gambar']) ?>" alt="<?= htmlspecialchars($berita['judul']) ?>" class="article-cover">
            <?php endif; ?>

            <div class="article-body">
                <?= $berita['konten'] ?>
            </div>
            
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <span style="font-weight: 700; color: #0f172a; font-size: 15px;">Bagikan:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($meta_url) ?>" target="_blank" style="background: #1877f2; color: #fff; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($meta_url) ?>&text=<?= urlencode($meta_title) ?>" target="_blank" style="background: #1da1f2; color: #fff; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;"><i class="fa-brands fa-twitter"></i> Twitter</a>
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($meta_title . ' - ' . $meta_url) ?>" target="_blank" style="background: #25d366; color: #fff; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            </div>

        </main>

        <aside class="right-sidebar">
            
            <div class="sidebar-widget">
                <h4 class="widget-title"><i class="fa-solid fa-fire-flame-curved" style="color: #ef4444;"></i> Sedang Tren</h4>
                <?php if (count($berita_populer) > 0): ?>
                    <?php $no = 1; foreach ($berita_populer as $pop): ?>
                        <div class="populer-item">
                            <div class="populer-number">#<?= $no++ ?></div>
                            <div>
                                <a href="/berita/<?= htmlspecialchars($pop['slug']) ?>" class="populer-title">
                                    <?= htmlspecialchars($pop['judul']) ?>
                                </a>
                                <div class="populer-meta">
                                    <span><i class="fa-regular fa-clock"></i> <?= date('d M', strtotime($pop['created_at'])) ?></span>
                                    <span>&bull;</span>
                                    <span style="color: #0ea5e9;"><i class="fa-solid fa-eye"></i> <?= number_format($pop['views']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #94a3b8; text-align: center;">Belum ada tren berita.</p>
                <?php endif; ?>
            </div>

            <div class="sidebar-widget">
                <h4 class="widget-title"><i class="fa-solid fa-folder-tree" style="color: #7c3aed;"></i> Jelajahi Topik</h4>
                <ul class="cat-list">
                    <?php foreach ($kategori_list as $kat): ?>
                        <li>
                            <a href="/?kategori=<?= htmlspecialchars($kat['slug']) ?>">
                                <?= htmlspecialchars($kat['nama_kategori']) ?> <i class="fa-solid fa-angle-right" style="font-size: 12px; opacity: 0.5;"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </aside>

    </div>
</div>

<?php
// Panggil Footer
require_once __DIR__ . '/../includes/footer.php';
?>