<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

try {
    // 3. Mengambil data statistik dinamis dari database
    $stmt_berita = $pdo->query("SELECT COUNT(*) FROM berita");
    $total_berita = $stmt_berita->fetchColumn();

    $stmt_kategori = $pdo->query("SELECT COUNT(*) FROM kategori");
    $total_kategori = $stmt_kategori->fetchColumn();

    $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt_users->fetchColumn();

    // 4. Daftar Berita Terakhir (Max 5)
    $query_recent = "
        SELECT b.id, b.judul, b.created_at, k.nama_kategori 
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        ORDER BY b.id DESC 
        LIMIT 5
    ";
    $stmt_recent = $pdo->query($query_recent);
    $recent_berita = $stmt_recent->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Gaya CSS Modern Internal untuk Dashboard */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling (Tetap dipertahankan untuk styling sidebar.php) */
        .admin-sidebar { width: 260px; background-color: #18181b; color: #ffffff; padding: 25px 15px; display: flex; flex-direction: column; position: fixed; height: 100vh; }
        .admin-logo { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 35px; padding-left: 10px; }
        .admin-logo span { color: #ff6b00; }
        .admin-menu { list-style: none; flex-grow: 1; }
        .admin-menu li { margin-bottom: 8px; }
        .admin-menu a { display: flex; align-items: center; gap: 12px; color: #a1a1aa; text-decoration: none; padding: 12px 15px; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .admin-menu a:hover, .admin-menu a.active { background-color: #27272a; color: #ffffff; }
        .admin-menu a.active { border-left: 4px solid #ff6b00; padding-left: 11px; }
        .menu-divider { color: #52525b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 10px 15px; }
        .admin-logout a { display: flex; align-items: center; gap: 12px; color: #ef4444; text-decoration: none; padding: 12px 15px; font-size: 14px; font-weight: 600; }

        /* Main Content Styling */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
        
        /* Grid Statistik */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-icon { width: 55px; height: 55px; border-radius: 10px; background: #fff7ed; color: #ff6b00; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-info h3 { font-size: 14px; color: #71717a; font-weight: 500; margin-bottom: 5px; }
        .stat-info p { font-size: 28px; font-weight: 700; font-family: 'Outfit', sans-serif; }

        /* Layout Grafik & Tabel */
        .dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 25px; margin-bottom: 30px; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e4e4e7; }
        .card-header h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; }
        
        /* Tombol & Badge */
        .btn-primary { background: #ff6b00; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: background 0.3s; }
        .btn-primary:hover { background: #e05e00; }
        .badge-cat { background: #f4f4f5; color: #3f3f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        
        /* Tabel Kontrol */
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; }
        .admin-table td { padding: 14px 12px; border-bottom: 1px solid #f4f4f5; }
        .btn-action { color: #71717a; margin: 0 5px; font-size: 15px; text-decoration: none; transition: color 0.2s; }
        .btn-action.edit:hover { color: #3b82f6; }
        .btn-action.delete:hover { color: #ef4444; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        
        <header class="admin-header">
            <h2>Ringkasan Dashboard</h2>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500;">
                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #ff6b00;"></i>
                <?= htmlspecialchars(ucfirst($_SESSION['username'] ?? 'Admin')) ?>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-newspaper"></i></div>
                <div class="stat-info">
                    <h3>Total Berita</h3>
                    <p><?= number_format($total_berita) ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f2fe; color: #0ea5e9;"><i class="fa-solid fa-tags"></i></div>
                <div class="stat-info">
                    <h3>Kategori Aktif</h3>
                    <p><?= number_format($total_kategori) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef2f2; color: #ef4444;"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h3>Total Administrator</h3>
                    <p><?= number_format($total_users) ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-chart-area" style="color: #ff6b00; margin-right: 8px;"></i> Grafik Perkembangan Rilis</h3>
                </div>
                <div style="position: relative; width:100%; height: 280px;">
                    <canvas id="chartPerkembangan"></canvas>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Publikasi Terakhir</h3>
                    <a href="/admin/tulis" class="btn-primary"><i class="fa-solid fa-plus"></i> Tulis</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Judul Berita</th>
                            <th>Kategori</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_berita) > 0): ?>
                            <?php foreach($recent_berita as $row): ?>
                            <tr>
                                <td style="font-weight: 500; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($row['judul']) ?>
                                </td>
                                <td><span class="badge-cat"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?></span></td>
                                <td style="text-align: center;">
                                    <a href="/admin/edit/<?= $row['id'] ?>" class="btn-action edit"><i class="fa-solid fa-pen"></i></a>
                                    <a href="/admin/hapus/<?= $row['id'] ?>" class="btn-action delete" onclick="return confirm('Hapus berita ini?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #a1a1aa; padding: 40px;">Belum ada data berita.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        const ctx = document.getElementById('chartPerkembangan').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'], 
                datasets: [{
                    label: 'Perkembangan Berita Rilis',
                    data: [12, 19, 15, 25, 22, <?= (int)$total_berita ?>], 
                    borderColor: '#ff6b00',
                    backgroundColor: 'rgba(255, 107, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f4f4f5' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>