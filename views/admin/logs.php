<?php
require_once __DIR__ . '/../../config.php';
global $pdo;

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) { 
    header("Location: /login"); 
    exit; 
}

// Ambil data log
$stmt = $pdo->query("SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Gaya CSS Modern Internal - Identik dengan Dashboard */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling (Dipertahankan untuk styling file sidebar.php) */
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

        /* Main Content Styling */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }

        /* Panel & Tabel Kontrol */
        .admin-panel { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .panel-header { padding-bottom: 15px; border-bottom: 1px solid #e4e4e7; margin-bottom: 20px; }
        .panel-header h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; }
        
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; }
        .admin-table td { padding: 14px 12px; border-bottom: 1px solid #f4f4f5; }

        /* Komponen Spesifik Audit Logs */
        .log-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-success { background: #dcfce7; color: #15803d; }
        .bg-danger { background: #fee2e2; color: #b91c1c; }
        .bg-info { background: #e0f2fe; color: #0ea5e9; }
        .bg-warning { background: #fef3c7; color: #d97706; }
        
        .ip-code { background: #f4f4f5; padding: 5px 10px; border-radius: 4px; font-size: 12px; color: #ff6b00; font-family: monospace; font-weight: 600; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        
        <header class="admin-header">
            <h2>Log Keamanan Sistem</h2>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500;">
                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #ff6b00;"></i>
                <?= htmlspecialchars(ucfirst($_SESSION['username'] ?? 'Admin')) ?>
            </div>
        </header>

        <div class="admin-panel">
            <div class="panel-header">
                <h3>Riwayat Aktivitas (Audit Logs)</h3>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tindakan (Aksi)</th>
                        <th>IP Address</th>
                        <th>Waktu Eksekusi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): 
                        $action = strtoupper($log['action']);
                        $badge_class = 'bg-info'; 
                        
                        if (strpos($action, 'LOGIN') !== false || strpos($action, 'TAMBAH') !== false || strpos($action, 'SUCCESS') !== false) {
                            $badge_class = 'bg-success';
                        }
                        if (strpos($action, 'GAGAL') !== false || strpos($action, 'HAPUS') !== false || strpos($action, 'FAILED') !== false) {
                            $badge_class = 'bg-danger';
                        }
                        if (strpos($action, 'EDIT') !== false || strpos($action, 'UPDATE') !== false) {
                            $badge_class = 'bg-warning';
                        }
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: #121212;">
                            <i class="fa-solid fa-user-astronaut" style="color: #a1a1aa; margin-right: 8px;"></i> 
                            <?= htmlspecialchars($log['username'] ?? 'Guest (Gagal)') ?>
                        </td>
                        <td>
                            <span class="log-badge <?= $badge_class ?>"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td>
                            <span class="ip-code"><?= htmlspecialchars($log['ip_address']) ?></span>
                        </td>
                        <td style="color: #71717a; font-size: 13px;">
                            <i class="fa-regular fa-clock" style="margin-right: 5px;"></i>
                            <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?> WIB
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($logs)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #a1a1aa; padding: 40px;">Belum ada catatan aktivitas lalu lintas di dalam sistem.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </main>
</body>
</html>