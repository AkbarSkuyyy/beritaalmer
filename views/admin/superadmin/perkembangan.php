<?php
// 1. Panggil koneksi database (Mundur 3 tingkat ke root directory)
require_once __DIR__ . '/../../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. PROTEKSI KEAMANAN TERTINGGI: Cek Validitas Otoritas Role Superadmin
try {
    $stmt_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt_role->execute([$_SESSION['admin_id']]);
    $user_check = $stmt_role->fetch();

    if (!$user_check || $user_check['role'] !== 'superadmin') {
        header("Location: /admin/dashboard");
        exit;
    }
} catch (PDOException $e) {
    die("Sistem Keamanan Gagal Memvalidasi Kredensial: " . $e->getMessage());
}

// 4. QUERY AGREGASI METRIK PERKEMBANGAN (ADVANCED ANALYTICS)
try {
    // A. Nilai Rata-rata Artikel Dibaca (Indikator Kualitas Konten)
    $avg_views = $pdo->query("SELECT ROUND(AVG(views), 1) FROM berita")->fetchColumn() ?? 0;

    // B. Pertumbuhan Artikel Baru dalam 30 Hari Terakhir
    $berita_30_hari = $pdo->query("SELECT COUNT(id) FROM berita WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?? 0;

    // C. Akumulasi Trafik Baru dalam 30 Hari Terakhir
    $views_30_hari = $pdo->query("SELECT SUM(views) FROM berita WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?? 0;

    // D. Analisis Pertumbuhan Berdasarkan Kategori (Tabel Performa)
    $stmt_growth_kategori = $pdo->query("
        SELECT k.nama_kategori, 
               COUNT(b.id) as total_artikel, 
               SUM(b.views) as total_views, 
               ROUND(AVG(b.views), 1) as rasio_baca
        FROM kategori k
        LEFT JOIN berita b ON k.id = b.kategori_id
        GROUP BY k.id
        ORDER BY total_views DESC
    ");
    $growth_kategori = $stmt_growth_kategori->fetchAll();

    // E. Tren Riwayat Pertumbuhan Bulanan (Grup Berdasarkan Tahun & Bulan)
    $stmt_tren_bulanan = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%M %Y') as bulan, 
               COUNT(id) as jumlah_artikel, 
               SUM(views) as total_views
        FROM berita
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC
        LIMIT 6
    ");
    $tren_bulanan = $stmt_tren_bulanan->fetchAll();

} catch (PDOException $e) {
    die("Gagal Memuat Komputasi Metrik Perkembangan: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metrik Perkembangan Sistem | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Grid Indikator */
        .metrics-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 35px; }
        .metric-card { background: #fff; padding: 25px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .metric-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .metric-value { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .metric-subtext { font-size: 12px; color: #94a3b8; font-weight: 500; }

        /* Tata Letak Tabel Ganda */
        .layout-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .panel-card { background: #ffffff; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        /* Desain Tabel Indeks */
        .growth-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
        .growth-table th { padding: 14px 10px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; font-family: 'Outfit', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .growth-table td { padding: 16px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        .growth-table tr:last-child td { border-bottom: none; }
        
        .trend-up { color: #10b981; font-weight: 600; }
        .ratio-badge { background: #f5f3ff; color: #7c3aed; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-size: 12px; border: 1px solid #ddd6fe; }

        @media (max-width: 1200px) {
            .layout-grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .table-responsive { overflow-x: auto; }
            .growth-table { min-width: 500px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Metrik Perkembangan Portal</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Analisis matriks rasio efisiensi konten dan laju akselerasi pertumbuhan server data.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-seedling"></i> GROWTH INSIGHTS
            </div>
        </header>

        <section class="metrics-grid-3">
            <div class="metric-card">
                <div class="metric-label"><i class="fa-solid fa-chart-line" style="color:#7c3aed;"></i> Efisiensi Konten</div>
                <div class="metric-value"><?= number_format($avg_views) ?>x</div>
                <div class="metric-subtext">Rata-rata tayangan per satu artikel berita</div>
            </div>
            <div class="metric-card">
                <div class="metric-label"><i class="fa-solid fa-folder-plus" style="color:#10b981;"></i> Akselerasi Publikasi</div>
                <div class="metric-value">+<?= number_format($berita_30_hari) ?></div>
                <div class="metric-subtext">Artikel baru diterbitkan dalam 30 hari terakhir</div>
            </div>
            <div class="metric-card">
                <div class="metric-label"><i class="fa-solid fa-bolt" style="color:#f97316;"></i> Lonjakan Trafik</div>
                <div class="metric-value">+<?= number_format($views_30_hari) ?></div>
                <div class="metric-subtext">Penayangan baru dalam 30 hari terakhir</div>
            </div>
        </section>

        <div class="layout-grid-2">
            
            <div class="panel-card">
                <div class="panel-title">
                    <span><i class="fa-solid fa-chart-gantt" style="color:#7c3aed; margin-right:5px;"></i> Pangsa & Produktivitas Kategori</span>
                </div>
                <div class="table-responsive">
                    <table class="growth-table">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th style="text-align:center;">Artikel</th>
                                <th style="text-align:center;">Total Views</th>
                                <th style="text-align:right;">Rasio Baca</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($growth_kategori as $kat): ?>
                            <tr>
                                <td style="font-weight: 700; color: #0f172a;">
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </td>
                                <td style="text-align: center; font-weight: 500;">
                                    <?= number_format($kat['total_artikel']) ?>
                                </td>
                                <td style="text-align: center; color: #64748b;">
                                    <?= number_format($kat['total_views'] ?? 0) ?>
                                </td>
                                <td style="text-align: right;">
                                    <span class="ratio-badge"><?= number_format($kat['rasio_baca'] ?? 0) ?>x</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-title">
                    <span><i class="fa-solid fa-history" style="color:#7c3aed; margin-right:5px;"></i> Riwayat Kecepatan Laju Bulanan</span>
                </div>
                <div class="table-responsive">
                    <table class="growth-table">
                        <thead>
                            <tr>
                                <th>Periode Bulan</th>
                                <th style="text-align:center;">Produksi Rilis</th>
                                <th style="text-align:right;">Trafik Bulanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($tren_bulanan) > 0): ?>
                                <?php foreach($tren_bulanan as $tb): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #0f172a;">
                                        <i class="fa-regular fa-calendar" style="color:#94a3b8; margin-right:5px;"></i> <?= htmlspecialchars($tb['bulan']) ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;" class="trend-up">
                                        <?= number_format($tb['jumlah_artikel']) ?> bks
                                    </td>
                                    <td style="text-align: right; font-weight: 600; color: #0f172a;">
                                        <?= number_format($tb['total_views']) ?> views
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:40px;">Belum ada tumpukan riwayat data publikasi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>