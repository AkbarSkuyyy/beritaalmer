<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

try {
    // A. Statistik Pertumbuhan Bulanan
    $query_bulanan = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') as bulan, COUNT(*) as jumlah 
        FROM berita 
        GROUP BY bulan 
        ORDER BY bulan DESC
    ";
    $stmt_bulanan = $pdo->query($query_bulanan);
    $data_bulanan = $stmt_bulanan->fetchAll();

    // B. Statistik Distribusi Kategori
    $query_distribusi = "
        SELECT k.nama_kategori, COUNT(b.id) as total 
        FROM kategori k 
        LEFT JOIN berita b ON k.id = b.kategori_id 
        GROUP BY k.id 
        ORDER BY total DESC
    ";
    $stmt_distribusi = $pdo->query($query_distribusi);
    $data_distribusi = $stmt_distribusi->fetchAll();

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perkembangan Sistem | Berita Almer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Menu (Dipertahankan untuk styling file sidebar.php) */
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

        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e4e4e7; }
        
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .admin-table th { padding: 12px; border-bottom: 2px solid #e4e4e7; color: #71717a; font-weight: 600; font-size: 14px; }
        .admin-table td { padding: 12px; border-bottom: 1px solid #f4f4f5; font-size: 14px; }
        .bar-bg { background: #f4f4f5; height: 10px; border-radius: 5px; width: 100%; overflow: hidden; margin-top: 8px; }
        .bar-fill { background: #ff6b00; height: 100%; border-radius: 5px; transition: width 0.5s ease; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Laporan Perkembangan Situs</h2>
            <div style="font-size: 14px; color: #71717a;"><i class="fa-solid fa-chart-pie" style="color: #ff6b00; margin-right: 5px;"></i> Data & Analitik</div>
        </header>

        <div class="stats-grid">
            <div class="content-card">
                <div class="card-header">Pertumbuhan Bulanan</div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Total Artikel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_bulanan) > 0): ?>
                            <?php foreach($data_bulanan as $row): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($row['bulan'] . '-01')) ?></td>
                                <td><strong><?= $row['jumlah'] ?></strong> Artikel</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #a1a1aa; padding: 30px;">Belum ada data bulanan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-card">
                <div class="card-header">Distribusi Konten per Kategori</div>
                <?php if (count($data_distribusi) > 0): ?>
                    <?php foreach($data_distribusi as $row): ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display:flex; justify-content:space-between; font-size: 13px; font-weight: 600; color: #3f3f46;">
                            <span><?= htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori') ?></span>
                            <span><?= $row['total'] ?> Berita</span>
                        </div>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width: <?= ($row['total'] > 0) ? 'min(100%, ' . ($row['total'] * 10) . '%)' : '0%' ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #a1a1aa; padding: 30px;">Belum ada data kategori.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>