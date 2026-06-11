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

// 4. QUERY STATISTIK GLOBAL (METRIK INTI)
try {
    // Total Seluruh Berita
    $total_berita = $pdo->query("SELECT COUNT(id) FROM berita")->fetchColumn();
    
    // Total Kategori
    $total_kategori = $pdo->query("SELECT COUNT(id) FROM kategori")->fetchColumn();
    
    // Total Seluruh Pengguna Sistem
    $total_users = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
    
    // Akumulasi Total Views (Semua Berita Dibaca)
    $total_views = $pdo->query("SELECT SUM(views) FROM berita")->fetchColumn() ?? 0;

    // 5. QUERY DATA TERBARU UNTUK TABEL MONITORING
    // 5 Berita Terbaru yang Diterbitkan lintas penulis
    $stmt_berita_baru = $pdo->query("
        SELECT b.judul, b.created_at, b.views, k.nama_kategori, u.username as penulis
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        ORDER BY b.created_at DESC LIMIT 5
    ");
    $berita_terbaru = $stmt_berita_baru->fetchAll();

    // 5 Akun Pengguna yang Baru Bergabung/Dibuat
    $stmt_user_baru = $pdo->query("
        SELECT username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC LIMIT 5
    ");
    $user_terbaru = $stmt_user_baru->fetchAll();

} catch (PDOException $e) {
    die("Gagal Memuat Data Logistik Dasbor: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Dasbor Master | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        /* Area Konten Utama */
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        /* Banner Dasbor */
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Grid Statistik Atas (4 Kolom) */
        .stat-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 35px; }
        .stat-box { background: #fff; padding: 25px; border-radius: 14px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .stat-info h5 { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; font-weight: 600; }
        .stat-info h2 { font-family: 'Outfit', sans-serif; font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        
        /* Tema Warna Icon */
        .icon-purple { background: #f5f3ff; color: #7c3aed; }
        .icon-blue { background: #f0f9ff; color: #0ea5e9; }
        .icon-green { background: #f0fdf4; color: #10b981; }
        .icon-orange { background: #fff7ed; color: #f97316; }

        /* Grid Dua Kolom untuk Tabel */
        .dashboard-layout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 35px; }
        .panel-card { background: #ffffff; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .view-all-btn { font-size: 12px; color: #7c3aed; text-decoration: none; font-weight: 600; background: #f5f3ff; padding: 5px 12px; border-radius: 6px; transition: 0.2s; }
        .view-all-btn:hover { background: #7c3aed; color: white; }

        /* Tabel Data Ringkas */
        .compact-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
        .compact-table th { padding: 12px 8px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; font-family: 'Outfit', sans-serif; font-size: 12px; text-transform: uppercase; }
        .compact-table td { padding: 14px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .compact-table tr:last-child td { border-bottom: none; }
        
        .badge-role { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .badge-super { background: #f5f3ff; color: #7c3aed; }
        .badge-admin { background: #fee2e2; color: #ef4444; }
        .badge-user { background: #e0f2fe; color: #0ea5e9; }

        /* Responsif Layar */
        @media (max-width: 1200px) {
            .dashboard-layout-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .compact-table { min-width: 500px; }
            .table-responsive { overflow-x: auto; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Ringkasan Dasbor Portal</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Selamat datang kembali, Master. Berikut akumulasi data server Berita Almer hari ini.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-chart-line"></i> LIVE MONITORING
            </div>
        </header>

        <section class="stat-grid-4">
            <div class="stat-box">
                <div class="stat-info"><h5>Total Artikel</h5><h2><?= number_format($total_berita) ?></h2></div>
                <div class="stat-icon icon-purple"><i class="fa-solid fa-newspaper"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info"><h5>Total Kategori</h5><h2><?= number_format($total_kategori) ?></h2></div>
                <div class="stat-icon icon-orange"><i class="fa-solid fa-folder-tree"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info"><h5>Total Pembaca (Views)</h5><h2><?= number_format($total_views) ?></h2></div>
                <div class="stat-icon icon-green"><i class="fa-solid fa-eye"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-info"><h5>Total Akun</h5><h2><?= number_format($total_users) ?></h2></div>
                <div class="stat-icon icon-blue"><i class="fa-solid fa-users-gear"></i></div>
            </div>
        </section>

        <div class="dashboard-layout-grid">
            
            <div class="panel-card">
                <div class="panel-title">
                    <span><i class="fa-solid fa-bolt" style="color:#7c3aed; margin-right:5px;"></i> Publikasi Berita Terkini</span>
                    <a href="/admin/berita" class="view-all-btn">Semua Berita</a>
                </div>
                <div class="table-responsive">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th>Judul Berita</th>
                                <th>Kategori / Penulis</th>
                                <th style="text-align:right;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($berita_terbaru) > 0): ?>
                                <?php foreach($berita_terbaru as $b): ?>
                                <tr>
                                    <td style="font-weight:600; color:#0f172a; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($b['judul']) ?>">
                                        <?= htmlspecialchars($b['judul']) ?>
                                    </td>
                                    <td>
                                        <div style="font-size:11px; color:#ff6b00; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum') ?></div>
                                        <div style="font-size:12px; color:#64748b;">Oleh: <?= htmlspecialchars($b['penulis']) ?></div>
                                    </td>
                                    <td style="text-align:right; font-weight:700; color:#0f172a;">
                                        <?= number_format($b['views']) ?>x
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:30px;">Belum ada berita yang diterbitkan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-title">
                    <span><i class="fa-solid fa-user-shield" style="color:#7c3aed; margin-right:5px;"></i> Staff & Pengguna Baru</span>
                    <a href="/admin/superadmin" class="view-all-btn">Kelola Akses</a>
                </div>
                <div class="table-responsive">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th>Username / Email</th>
                                <th>Otoritas</th>
                                <th>Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($user_terbaru) > 0): ?>
                                <?php foreach($user_terbaru as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;"><?= htmlspecialchars($u['username']) ?></div>
                                        <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $u_role = $u['role'];
                                            $b_class = 'badge-user';
                                            if($u_role === 'superadmin') $b_class = 'badge-super';
                                            elseif($u_role === 'admin') $b_class = 'badge-admin';
                                        ?>
                                        <span class="badge-role <?= $b_class ?>"><?= $u_role ?></span>
                                    </td>
                                    <td style="color:#64748b; font-size:12px;">
                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:30px;">Data pengguna kosong.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>