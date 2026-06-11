<?php
// Memanggil config dengan mundur 1 folder (ke public_html/config.php)
require_once __DIR__ . '/../config.php';
global $pdo;

// 1. Mengambil Kategori untuk Navigasi
try {
    $stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmt_kategori->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

$filter_kategori = $_GET['kategori'] ?? '';
$where_clause = "";
$params = [];

if (!empty($filter_kategori)) {
    $where_clause = "WHERE k.slug = ?";
    $params[] = $filter_kategori;
}

// 2. SISTEM PAGINASI (SLIDE HALAMAN)
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;

$query_count = "SELECT COUNT(b.id) FROM berita b LEFT JOIN kategori k ON b.kategori_id = k.id $where_clause";
$stmt_count = $pdo->prepare($query_count);
$stmt_count->execute($params);
$total_data = $stmt_count->fetchColumn();

$is_home_utama = empty($filter_kategori);

if ($is_home_utama) {
    if ($halaman == 1) {
        $limit = 9;
        $offset = 0;
    } else {
        $limit = 6; 
        $offset = 9 + (($halaman - 2) * 6);
    }
    $total_halaman = 1 + ceil(max(0, $total_data - 9) / 6);
} else {
    $limit = 6;
    $offset = ($halaman - 1) * $limit;
    $total_halaman = ceil($total_data / $limit);
}

// 3. Mengambil Berita Sesuai Halaman
try {
    $query = "
        SELECT b.id, b.judul, b.slug, b.gambar, b.created_at, b.views, 
               k.nama_kategori, u.username as penulis
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $semua_berita = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

// 4. Mengambil Top 5 Berita Terpopuler
try {
    $stmt_populer = $pdo->query("SELECT judul, slug, views, created_at FROM berita ORDER BY views DESC LIMIT 5");
    $berita_populer = $stmt_populer->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

// 5. Logika Pemisahan Highlight
$highlight_utama = null;
$highlight_samping = [];

if ($is_home_utama && $halaman == 1 && count($semua_berita) > 0) {
    $highlight_utama = array_shift($semua_berita);
    for ($i = 0; $i < 2; $i++) {
        if (count($semua_berita) > 0) {
            $highlight_samping[] = array_shift($semua_berita);
        }
    }
}

// MEMANGGIL HEADER
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .home-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    .kategori-nav { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f1f5f9; }
    .kategori-pill { padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s; border: 1px solid #e2e8f0; }
    .kategori-pill.active { background: #ff6b00; color: #fff; border-color: #ff6b00; box-shadow: 0 4px 10px rgba(255,107,0,0.3); }
    .kategori-pill:not(.active) { background: #fff; color: #475569; }
    .kategori-pill:not(.active):hover { background: #f8fafc; border-color: #cbd5e1; }

    .highlight-section { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 40px; }
    .headline-card { position: relative; border-radius: 12px; overflow: hidden; display: block; background: #1e293b; text-decoration: none; }
    .main-headline { height: 450px; } /* Dipindahkan ke CSS agar mudah diubah untuk HP */
    
    .headline-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; opacity: 0.8; }
    .headline-card:hover img { transform: scale(1.05); opacity: 1; }
    .headline-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.6) 60%, transparent 100%); padding: 30px; color: #fff; }
    .headline-badge { background: #ff6b00; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: inline-block; }
    .headline-main-title { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; line-height: 1.25; margin-bottom: 10px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
    .side-highlights { display: flex; flex-direction: column; gap: 20px; }
    .side-card { height: calc(50% - 10px); }
    .side-card .headline-overlay { padding: 20px; }
    .side-card .headline-main-title { font-size: 18px; }

    .content-wrapper { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }
    
    .section-title { font-family: 'Outfit', sans-serif; font-size: 24px; margin-bottom: 25px; border-left: 5px solid #ff6b00; padding-left: 15px; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
    .news-card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; border: 1px solid #f1f5f9; }
    .news-card:hover { transform: translateY(-5px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); }
    .news-img { width: 100%; height: 180px; object-fit: cover; background-color: #e2e8f0; }
    .news-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
    .news-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; text-decoration: none; line-height: 1.4; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .news-title:hover { color: #ff6b00; }

    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 50px; padding-top: 30px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; }
    .page-item { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; text-decoration: none; transition: 0.3s; font-family: 'Outfit', sans-serif;}
    .page-item:hover { border-color: #ff6b00; color: #ff6b00; }
    .page-item.active { background: #ff6b00; color: #ffffff; border-color: #ff6b00; box-shadow: 0 4px 10px rgba(255,107,0,0.3); }

    .sidebar-widget { background: #ffffff; border-radius: 12px; border: 1px solid #f1f5f9; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: sticky; top: 90px; }
    .widget-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
    
    .populer-item { display: flex; gap: 15px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px dashed #e2e8f0; }
    .populer-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .populer-number { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .populer-title { font-size: 14px; font-weight: 600; color: #1e293b; text-decoration: none; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; }
    .populer-title:hover { color: #ff6b00; }
    .populer-meta { font-size: 12px; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 10px; }

    /* =========================================
       PERBAIKAN CSS RESPONSIF (TABLET & HP)
       ========================================= */
    
    /* Untuk Tablet (Di bawah 992px) */
    @media (max-width: 992px) {
        .content-wrapper { grid-template-columns: 1fr; }
        .highlight-section { grid-template-columns: 1fr; }
        .main-headline { height: 350px; }
        .side-card { height: 250px; }
        .sidebar-widget { position: static; margin-top: 40px; }
    }

    /* Untuk Smartphone (Di bawah 768px) */
    @media (max-width: 768px) {
        .home-container { padding: 0 15px; margin: 25px auto; }
        
        /* Navigasi Kategori */
        .kategori-nav { gap: 8px; margin-bottom: 20px; padding-bottom: 15px; }
        .kategori-pill { padding: 6px 14px; font-size: 12px; }

        /* Headline Utama */
        .main-headline { height: 280px; }
        .headline-main-title { font-size: 20px; margin-bottom: 5px; }
        .headline-overlay { padding: 15px 20px; }
        .headline-overlay div { font-size: 11px !important; }
        
        /* Headline Samping */
        .side-highlights { gap: 15px; }
        .side-card { height: 180px; }
        .side-card .headline-main-title { font-size: 16px; }
        
        /* Daftar Berita */
        .section-title { font-size: 20px; margin-bottom: 20px; }
        .news-grid { grid-template-columns: 1fr; gap: 20px; } /* Memaksa jadi 1 kolom penuh */
        .news-img { height: 200px; }
        .news-title { font-size: 16px; }
        .news-content { padding: 15px; }

        /* Widget Populer */
        .sidebar-widget { padding: 20px; }
        .populer-number { font-size: 26px; }
        .populer-title { font-size: 13px; }
    }
</style>

<div class="home-container">
    
    <div class="kategori-nav">
        <a href="/" class="kategori-pill <?= empty($filter_kategori) ? 'active' : '' ?>">Beranda Utama</a>
        <?php foreach ($kategori_list as $kat): ?>
            <a href="/?kategori=<?= $kat['slug'] ?>" class="kategori-pill <?= $filter_kategori == $kat['slug'] ? 'active' : '' ?>">
                <?= htmlspecialchars($kat['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($highlight_utama): ?>
    <div class="highlight-section">
        <a href="/berita/<?= htmlspecialchars($highlight_utama['slug']) ?>" class="headline-card main-headline">
            <?php if (!empty($highlight_utama['gambar'])): ?>
                <img src="/assets/uploads/<?= htmlspecialchars($highlight_utama['gambar']) ?>" alt="Headline">
            <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#475569;"><i class="fa-solid fa-newspaper" style="font-size: 80px;"></i></div>
            <?php endif; ?>
            <div class="headline-overlay">
                <span class="headline-badge"><?= htmlspecialchars($highlight_utama['nama_kategori'] ?? 'Berita Utama') ?></span>
                <h1 class="headline-main-title"><?= htmlspecialchars($highlight_utama['judul']) ?></h1>
                <div style="font-size: 13px; color: #cbd5e1; font-weight: 500; display: flex; gap: 15px; align-items: center;">
                    <span><i class="fa-solid fa-pen-nib"></i> <?= htmlspecialchars($highlight_utama['penulis']) ?></span>
                    <span><i class="fa-regular fa-clock"></i> <?= date('d M Y', strtotime($highlight_utama['created_at'])) ?></span>
                </div>
            </div>
        </a>

        <div class="side-highlights">
            <?php foreach ($highlight_samping as $side): ?>
            <a href="/berita/<?= htmlspecialchars($side['slug']) ?>" class="headline-card side-card">
                <?php if (!empty($side['gambar'])): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($side['gambar']) ?>" alt="Sub Headline">
                <?php else: ?>
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#475569;"><i class="fa-solid fa-image" style="font-size: 50px;"></i></div>
                <?php endif; ?>
                <div class="headline-overlay">
                    <span class="headline-badge" style="background: #0ea5e9;"><?= htmlspecialchars($side['nama_kategori'] ?? 'Trending') ?></span>
                    <h2 class="headline-main-title"><?= htmlspecialchars($side['judul']) ?></h2>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-wrapper">
        <main class="main-content">
            <h3 class="section-title">
                <i class="fa-solid fa-bolt" style="color: #ff6b00;"></i> 
                <?php 
                    if (!empty($filter_kategori)) {
                        echo 'Kategori: ' . htmlspecialchars(ucfirst(str_replace('-', ' ', $filter_kategori)));
                    } else {
                        echo ($halaman > 1) ? 'Berita Terkini Halaman ' . $halaman : 'Berita Terkini Lainnya';
                    }
                ?>
            </h3>
            
            <div class="news-grid">
                <?php if (count($semua_berita) > 0): ?>
                    <?php foreach ($semua_berita as $row): ?>
                        <article class="news-card">
                            <a href="/berita/<?= htmlspecialchars($row['slug']) ?>" style="display:block;">
                                <?php if (!empty($row['gambar'])): ?>
                                    <img src="/assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Thumbnail" class="news-img">
                                <?php else: ?>
                                    <div class="news-img" style="display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="fa-solid fa-image" style="font-size: 40px;"></i></div>
                                <?php endif; ?>
                            </a>
                            <div class="news-content">
                                <div style="display: flex; align-items: center; gap: 12px; font-size: 12px; color: #64748b; margin-bottom: 12px; font-weight: 600;">
                                    <span style="color: #ff6b00; text-transform: uppercase;"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?></span>
                                    <span>&bull;</span>
                                    <span><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                </div>
                                <a href="/berita/<?= htmlspecialchars($row['slug']) ?>" class="news-title">
                                    <?= htmlspecialchars($row['judul']) ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: #64748b;">
                        <i class="fa-solid fa-folder-open" style="font-size: 50px; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <p>Belum ada berita untuk ditampilkan di halaman ini.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_halaman > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <?php 
                            $active = ($halaman == $i) ? 'active' : '';
                            $url_paginasi = "?halaman=$i";
                            if (!empty($filter_kategori)) {
                                $url_paginasi .= "&kategori=" . urlencode($filter_kategori);
                            }
                        ?>
                        <a href="<?= $url_paginasi ?>" class="page-item <?= $active ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </main>

        <aside class="right-sidebar">
            <div class="sidebar-widget">
                <h4 class="widget-title"><i class="fa-solid fa-fire-flame-curved" style="color: #ef4444;"></i> Paling Populer</h4>
                
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
                                    <span style="color: #0ea5e9;"><i class="fa-solid fa-eye"></i> <?= number_format($pop['views']) ?>x dibaca</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 13px; color: #94a3b8; text-align: center;">Belum ada statistik pembaca.</p>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</div>

<?php
// MEMANGGIL FOOTER
require_once __DIR__ . '/../includes/footer.php';
?>