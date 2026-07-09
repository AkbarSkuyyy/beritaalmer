<?php
// Pastikan $pdo sudah tersedia (jika file ini dipanggil dari home.php/single.php, variabel ini sudah ada)
global $pdo;

// 1. Ambil data berita terbaru
$sidebar_stmt = $pdo->query("SELECT slug, judul, created_at as tanggal, gambar FROM berita ORDER BY created_at DESC LIMIT 5");

// 2. Ambil data kategori beserta jumlah berita di dalamnya
$cat_stmt = $pdo->query("
    SELECT k.nama_kategori, k.slug, COUNT(b.id) as total 
    FROM kategori k 
    LEFT JOIN berita b ON k.id = b.kategori_id 
    GROUP BY k.id 
    ORDER BY k.nama_kategori ASC
");
?>

<aside class="sidebar-column">
    
    <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fa-solid fa-clock"></i> Berita Terbaru</h3>
        <div class="widget-content">
            <ul class="sidebar-news-list">
                <?php while ($side_news = $sidebar_stmt->fetch()): ?>
                    <li>
                        <div class="side-thumb">
                            <?php if (!empty($side_news['gambar'])): ?>
                                <img src="/assets/uploads/<?= htmlspecialchars($side_news['gambar']) ?>" alt="Thumb">
                            <?php else: ?>
                                <div class="side-thumb-placeholder"><i class="fa-regular fa-image"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="side-info">
                            <span class="sidebar-date"><?= date('d M Y', strtotime($side_news['tanggal'])) ?></span>
                            <a href="/berita/<?= htmlspecialchars($side_news['slug']) ?>" class="side-title">
                                <?= htmlspecialchars($side_news['judul']) ?>
                            </a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fa-solid fa-list-ul"></i> Kategori Berita</h3>
        <div class="widget-content">
            <ul class="sidebar-category-list">
                <?php while ($cat = $cat_stmt->fetch()): ?>
                    <li>
                        <a href="/?kategori=<?= htmlspecialchars($cat['slug']) ?>">
                            <?= htmlspecialchars($cat['nama_kategori']) ?> 
                            <span class="cat-count"><?= $cat['total'] ?></span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fa-solid fa-bullhorn"></i> Sponsor</h3>
        <div class="widget-content">
            <div class="ad-space" style="background: #f8fafc; height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #a1a1aa; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; border-radius: 6px; border: 2px dashed #e2e8f0;">
                <i class="fa-solid fa-rectangle-ad" style="font-size: 24px; margin-bottom: 10px; color: #d4d4d8;"></i>
                SPACE IKLAN
            </div>
        </div>
    </div>

</aside>