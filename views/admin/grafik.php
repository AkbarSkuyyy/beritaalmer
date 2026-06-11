<?php
// 1. Panggil koneksi database (naik 2 tingkat folder ke folder utama)
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

try {
    // A. Analisis Kategori Terpopuler (Berdasar Akumulasi Total Views Berita)
    $query_cat_views = "
        SELECT k.nama_kategori, SUM(b.views) as total_views 
        FROM berita b 
        LEFT JOIN kategori k ON b.kategori_id = k.id 
        GROUP BY b.kategori_id 
        ORDER BY total_views DESC
    ";
    $stmt_cat_views = $pdo->query($query_cat_views);
    $cat_views_data = $stmt_cat_views->fetchAll();

    // B. Analisis Top 5 Artikel Berita Paling Populer (Views Tertinggi)
    $query_top_articles = "
        SELECT judul, views 
        FROM berita 
        ORDER BY views DESC 
        LIMIT 5
    ";
    $stmt_top = $pdo->query($query_top_articles);
    $top_articles = $stmt_top->fetchAll();

    // C. Analisis Tren Produktivitas Rilis Berita (7 Hari Terakhir)
    $query_trend = "
        SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah 
        FROM berita 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY tanggal ASC
    ";
    $stmt_trend = $pdo->query($query_trend);
    $trend_data = $stmt_trend->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan analisis data grafik: " . $e->getMessage());
}

// Konversi data hasil query PHP ke format Array untuk diolah JavaScript (Chart.js)
$label_trend = [];
$value_trend = [];
foreach ($trend_data as $t) {
    $label_trend[] = date('d M', strtotime($t['tanggal']));
    $value_trend[] = (int)$t['jumlah'];
}

$label_cat = [];
$value_cat = [];
foreach ($cat_views_data as $c) {
    $label_cat[] = $c['nama_kategori'] ?? 'Tanpa Kategori';
    $value_cat[] = (int)$c['total_views'];
}

$label_top = [];
$value_top = [];
foreach ($top_articles as $a) {
    // Memotong judul yang terlalu panjang agar rapi di tampilan grafik
    $judul_pendek = strlen($a['judul']) > 25 ? substr($a['judul'], 0, 25) . '...' : $a['judul'];
    $label_top[] = $judul_pendek;
    $value_top[] = (int)$a['views'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Analitik | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Menu Area (Dipertahankan untuk styling file sidebar.php) */
        .admin-sidebar { width: 260px; background-color: #18181b; color: #ffffff; padding: 25px 15px; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .admin-logo { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 35px; padding-left: 10px; }
        .admin-logo span { color: #ff6b00; }
        .admin-menu { list-style: none; flex-grow: 1; }
        .admin-menu li { margin-bottom: 8px; }
        .admin-menu a { display: flex; align-items: center; gap: 12px; color: #a1a1aa; text-decoration: none; padding: 12px 15px; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .admin-menu a:hover, .admin-menu a.active { background-color: #27272a; color: #ffffff; }
        .admin-menu a.active { border-left: 4px solid #ff6b00; padding-left: 11px; }
        .menu-divider { color: #52525b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 10px 15px; }
        .admin-logout a { display: flex; align-items: center; gap: 12px; color: #ef4444; text-decoration: none; padding: 12px 15px; font-size: 14px; font-weight: 600; }

        /* Container Utama */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }

        /* Grid Pembagian Grafik */
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px; }
        .full-width-chart { grid-column: span 2; }
        .chart-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .chart-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e4e4e7; }
        .chart-header h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        .chart-container { position: relative; width: 100%; height: 320px; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Grafik & Analitik Sistem</h2>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; background: #fff7ed; padding: 8px 15px; border-radius: 20px; color: #ff6b00;">
                <i class="fa-solid fa-chart-pie"></i>
                Live Metrics Report
            </div>
        </header>

        <div class="charts-grid">
            
            <div class="chart-card full-width-chart">
                <div class="chart-header">
                    <h3><i class="fa-solid fa-chart-area" style="color: #ff6b00;"></i> Tren Produktivitas Rilis Artikel Berita (7 Hari Terakhir)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa-solid fa-pie-chart" style="color: #0ea5e9;"></i> Total Pembaca (Views) Berdasarkan Kategori</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartCategories"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Top 5 Berita Paling Banyak Dibaca</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartTopArticles"></canvas>
                </div>
            </div>

        </div>
    </main>

    <script>
        const labelTrend = <?= json_encode($label_trend) ?>;
        const valueTrend = <?= json_encode($value_trend) ?>;

        const labelCat = <?= json_encode($label_cat) ?>;
        const valueCat = <?= json_encode($value_cat) ?>;

        const labelTop = <?= json_encode($label_top) ?>;
        const valueTop = <?= json_encode($value_top) ?>;

        // Render Grafik 1: Tren Rilis (Line Chart)
        new Chart(document.getElementById('chartTrend').getContext('2d'), {
            type: 'line',
            data: {
                labels: labelTrend.length > 0 ? labelTrend : ['Tidak ada aktivitas'],
                datasets: [{
                    label: 'Jumlah Postingan',
                    data: valueTrend.length > 0 ? valueTrend : [0],
                    borderColor: '#ff6b00',
                    backgroundColor: 'rgba(255, 107, 0, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#ff6b00'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Render Grafik 2: Views Kategori (Doughnut Chart)
        new Chart(document.getElementById('chartCategories').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labelCat.length > 0 ? labelCat : ['Belum ada data'],
                datasets: [{
                    data: valueCat.length > 0 ? valueCat : [0],
                    backgroundColor: ['#ff6b00', '#0ea5e9', '#10b981', '#f59e0b', '#6366f1', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } }
            }
        });

        // Render Grafik 3: Top Artikel (Horizontal Bar Chart)
        new Chart(document.getElementById('chartTopArticles').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labelTop.length > 0 ? labelTop : ['Belum ada artikel'],
                datasets: [{
                    label: 'Total Penayangan (Views)',
                    data: valueTop.length > 0 ? valueTop : [0],
                    backgroundColor: '#ef4444',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Membuat diagram berorientasi horizontal
                scales: { x: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>