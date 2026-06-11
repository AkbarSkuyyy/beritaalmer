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

// 4. QUERY DATA UNTUK GRAFIK (CHART.JS)
try {
    // A. Data Distribusi Berita Berdasarkan Kategori (Doughnut Chart)
    $stmt_kategori = $pdo->query("
        SELECT k.nama_kategori, COUNT(b.id) as jumlah_berita 
        FROM kategori k 
        LEFT JOIN berita b ON k.id = b.kategori_id 
        GROUP BY k.id
    ");
    $data_kategori = $stmt_kategori->fetchAll();
    
    $label_kategori = [];
    $nilai_kategori = [];
    foreach ($data_kategori as $row) {
        $label_kategori[] = $row['nama_kategori'];
        $nilai_kategori[] = $row['jumlah_berita'];
    }

    // B. Data Top 5 Penulis Berdasarkan Total Views (Bar Chart)
    $stmt_penulis = $pdo->query("
        SELECT u.username, SUM(b.views) as total_views, COUNT(b.id) as total_artikel
        FROM users u 
        JOIN berita b ON u.id = b.penulis_id 
        GROUP BY u.id 
        ORDER BY total_views DESC 
        LIMIT 5
    ");
    $data_penulis = $stmt_penulis->fetchAll();
    
    $label_penulis = [];
    $nilai_views_penulis = [];
    foreach ($data_penulis as $row) {
        $label_penulis[] = $row['username'];
        $nilai_views_penulis[] = $row['total_views'] ?? 0;
    }

    // C. Data Top 7 Berita Paling Banyak Dibaca (Horizontal Bar Chart)
    $stmt_top_berita = $pdo->query("
        SELECT judul, views 
        FROM berita 
        ORDER BY views DESC 
        LIMIT 7
    ");
    $data_top_berita = $stmt_top_berita->fetchAll();
    
    $label_berita = [];
    $nilai_berita = [];
    foreach ($data_top_berita as $row) {
        // Memotong judul jika terlalu panjang agar grafik tidak rusak
        $judul_pendek = mb_strimwidth($row['judul'], 0, 30, '...');
        $label_berita[] = $judul_pendek;
        $nilai_berita[] = $row['views'];
    }

} catch (PDOException $e) {
    die("Gagal Memuat Data Analitik: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Analitik Jaringan | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Grid Khusus Grafik */
        .chart-grid-top { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-bottom: 30px; }
        .chart-grid-bottom { display: grid; grid-template-columns: 1fr; gap: 30px; }
        
        .chart-card { background: #ffffff; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .chart-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .canvas-container { position: relative; width: 100%; height: 300px; display: flex; justify-content: center; align-items: center; }

        @media (max-width: 1200px) {
            .chart-grid-top { grid-template-columns: 1fr; }
        }
        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Grafik & Tren Trafik</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Visualisasi data logistik portal untuk memudahkan pengambilan keputusan master.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-chart-pie"></i> DATA VISUALIZATION
            </div>
        </header>

        <div class="chart-grid-top">
            
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fa-solid fa-chart-pie" style="color: #7c3aed;"></i> Distribusi Kategori Berita
                </div>
                <div class="canvas-container">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-title">
                    <i class="fa-solid fa-chart-column" style="color: #7c3aed;"></i> Top 5 Penulis Terpopuler (Berdasarkan Views)
                </div>
                <div class="canvas-container">
                    <canvas id="penulisChart"></canvas>
                </div>
            </div>

        </div>

        <div class="chart-grid-bottom">
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fa-solid fa-ranking-star" style="color: #7c3aed;"></i> Top 7 Berita Paling Banyak Dibaca Sepanjang Masa
                </div>
                <div class="canvas-container" style="height: 350px;">
                    <canvas id="beritaChart"></canvas>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Mengambil data dari PHP ke format JSON yang dimengerti Javascript
        const labelKategori = <?= json_encode($label_kategori) ?>;
        const nilaiKategori = <?= json_encode($nilai_kategori) ?>;
        
        const labelPenulis = <?= json_encode($label_penulis) ?>;
        const nilaiViewsPenulis = <?= json_encode($nilai_views_penulis) ?>;
        
        const labelBerita = <?= json_encode($label_berita) ?>;
        const nilaiBerita = <?= json_encode($nilai_berita) ?>;

        // Tema Warna Elegan Super Admin
        const warnaUtama = [
            '#7c3aed', // Royal Purple
            '#0ea5e9', // Sky Blue
            '#10b981', // Emerald Green
            '#f97316', // Orange
            '#ef4444', // Red
            '#eab308', // Yellow
            '#6366f1'  // Indigo
        ];

        // 1. Inisialisasi Grafik Kategori (Doughnut Chart)
        const ctxKategori = document.getElementById('kategoriChart').getContext('2d');
        new Chart(ctxKategori, {
            type: 'doughnut',
            data: {
                labels: labelKategori,
                datasets: [{
                    data: nilaiKategori,
                    backgroundColor: warnaUtama,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 } } }
                },
                cutout: '65%' // Membuat lubang donat lebih besar
            }
        });

        // 2. Inisialisasi Grafik Penulis (Bar Chart)
        const ctxPenulis = document.getElementById('penulisChart').getContext('2d');
        new Chart(ctxPenulis, {
            type: 'bar',
            data: {
                labels: labelPenulis,
                datasets: [{
                    label: 'Total Views Keseluruhan',
                    data: nilaiViewsPenulis,
                    backgroundColor: '#7c3aed',
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { font: { family: 'Inter' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter', weight: 'bold' } } }
                }
            }
        });

        // 3. Inisialisasi Grafik Berita Paling Populer (Horizontal Bar Chart)
        const ctxBerita = document.getElementById('beritaChart').getContext('2d');
        new Chart(ctxBerita, {
            type: 'bar',
            data: {
                labels: labelBerita,
                datasets: [{
                    label: 'Total Penayangan (Views)',
                    data: nilaiBerita,
                    backgroundColor: '#0ea5e9',
                    borderRadius: 6,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y', // Mengubah chart menjadi horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { font: { family: 'Inter' } } },
                    y: { grid: { display: false }, ticks: { font: { family: 'Inter', weight: 'bold' } } }
                }
            }
        });
    </script>
</body>
</html>