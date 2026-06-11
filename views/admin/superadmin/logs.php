<?php
// 1. Panggil koneksi database
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
    die("Sistem Keamanan Gagal: " . $e->getMessage());
}

// 4. SISTEM PENCARIAN & FILTER LOG
$search_ip = $_GET['ip'] ?? '';
$filter_action = $_GET['action'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($search_ip)) {
    $where_clauses[] = "l.ip_address LIKE ?";
    $params[] = "%$search_ip%";
}
if (!empty($filter_action)) {
    $where_clauses[] = "l.action = ?";
    $params[] = $filter_action;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 5. SISTEM PAGINASI LOG (Agar tidak berat saat data mencapai ribuan)
$limit = 15; // Tampilkan 15 baris log per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;
$offset = ($halaman - 1) * $limit;

// Hitung total data log
$stmt_count = $pdo->prepare("SELECT COUNT(l.id) FROM audit_logs l $where_sql");
$stmt_count->execute($params);
$total_data = $stmt_count->fetchColumn();
$total_halaman = ceil($total_data / $limit);

// 6. QUERY AMBIL DATA LOG + NAMA USER
try {
    $query_logs = "
        SELECT l.*, u.username, u.role 
        FROM audit_logs l
        LEFT JOIN users u ON l.user_id = u.id
        $where_sql 
        ORDER BY l.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt_logs = $pdo->prepare($query_logs);
    $stmt_logs->execute($params);
    $daftar_log = $stmt_logs->fetchAll();
    
} catch (PDOException $e) {
    die("Terjadi kesalahan pembacaan log sistem: " . $e->getMessage());
}

// Fitur Pembersih Log (Khusus Super Admin)
if (isset($_POST['bersihkan_log'])) {
    try {
        $pdo->query("TRUNCATE TABLE audit_logs");
        header("Location: /admin/superadmin/logs?success=clear");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal membersihkan log: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Log Audit IP | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&family=Fira+Code:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Panel Filter */
        .filter-panel { background: #ffffff; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;}
        .filter-grid { display: flex; gap: 15px; align-items: flex-end; flex-grow: 1; }
        .filter-group { flex-grow: 1; max-width: 300px; }
        .filter-group label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        .filter-control { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; color: #0f172a; }
        .filter-control:focus { border-color: #7c3aed; }
        .btn-filter { background: #0f172a; color: #fff; border: none; padding: 0 20px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.25s; height: 44px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-filter:hover { background: #7c3aed; }
        .btn-reset { background: #f1f5f9; color: #475569; border: none; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 44px; transition: 0.2s; }
        .btn-reset:hover { background: #e2e8f0; color: #0f172a; }

        .btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; padding: 0 20px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.25s; height: 44px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-danger:hover { background: #ef4444; color: #fff; }

        /* Panel Tabel Log */
        .table-panel { background: #ffffff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; }
        
        .master-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
        .master-table th { padding: 15px 20px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background-color: #f8fafc; font-family: 'Outfit', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .master-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .master-table tr:hover { background-color: #f8fafc; }
        
        .ip-address { font-family: 'Fira Code', monospace; color: #7c3aed; font-weight: 600; background: #f5f3ff; padding: 4px 8px; border-radius: 6px; border: 1px solid #ddd6fe; display: inline-block; }
        
        .badge-action { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .action-login { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .action-logout { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
        .action-create { background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; }
        .action-update { background: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }
        .action-delete { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .action-other { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .log-desc { color: #334155; line-height: 1.5; }
        .log-time { font-size: 12px; color: #94a3b8; font-weight: 500; white-space: nowrap; }
        
        /* Paginasi Modern */
        .pagination-box { padding: 25px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 8px; }
        .page-item { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; text-decoration: none; transition: 0.3s; font-family: 'Outfit', sans-serif; font-size: 14px; }
        .page-item:hover { border-color: #7c3aed; color: #7c3aed; }
        .page-item.active { background: #7c3aed; color: #ffffff; border-color: #7c3aed; box-shadow: 0 4px 10px rgba(124,58,237,0.3); }

        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .filter-panel { flex-direction: column; align-items: stretch; }
            .filter-grid { flex-direction: column; align-items: stretch; }
            .filter-group { max-width: 100%; }
            .table-responsive { overflow-x: auto; }
            .master-table { min-width: 800px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Log Audit IP & Aktivitas</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Perekaman jejak digital otomatis untuk melacak tindakan krusial dan otorisasi jaringan.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-server"></i> SYSTEM RADAR
            </div>
        </header>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'clear'): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-broom"></i> Seluruh riwayat log sistem berhasil dibersihkan!
            </div>
        <?php endif; ?>

        <div class="filter-panel">
            <form action="" method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Lacak Alamat IP</label>
                    <input type="text" name="ip" class="filter-control" placeholder="Cth: 192.168..." value="<?= htmlspecialchars($search_ip) ?>">
                </div>
                <div class="filter-group">
                    <label>Filter Jenis Aksi</label>
                    <select name="action" class="filter-control">
                        <option value="">-- Semua Aksi --</option>
                        <option value="LOGIN" <?= ($filter_action == 'LOGIN') ? 'selected' : '' ?>>LOGIN (Otentikasi Masuk)</option>
                        <option value="LOGOUT" <?= ($filter_action == 'LOGOUT') ? 'selected' : '' ?>>LOGOUT (Sesi Berakhir)</option>
                        <option value="CREATE" <?= ($filter_action == 'CREATE') ? 'selected' : '' ?>>CREATE (Buat Data)</option>
                        <option value="UPDATE" <?= ($filter_action == 'UPDATE') ? 'selected' : '' ?>>UPDATE (Ubah Data)</option>
                        <option value="DELETE" <?= ($filter_action == 'DELETE') ? 'selected' : '' ?>>DELETE (Hapus Data)</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Lacak</button>
                <a href="?" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            </form>
            
            <form action="" method="POST" onsubmit="return konfirmasiBersihkan(event);">
                <button type="submit" name="bersihkan_log" class="btn-danger"><i class="fa-solid fa-trash-can-arrow-up"></i> Bersihkan Log</button>
            </form>
        </div>

        <div class="table-panel">
            <div class="table-responsive">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Waktu Aktivitas (WIB)</th>
                            <th style="width: 15%;">Alamat IP</th>
                            <th style="width: 18%;">Identitas Aktor</th>
                            <th style="width: 12%;">Tindakan</th>
                            <th style="width: 35%;">Deskripsi Detail Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php foreach($daftar_log as $log): ?>
                            <tr>
                                <td class="log-time">
                                    <i class="fa-regular fa-clock" style="margin-right:5px; color:#cbd5e1;"></i> 
                                    <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td><span class="ip-address"><?= htmlspecialchars($log['ip_address']) ?></span></td>
                                <td>
                                    <?php if($log['username']): ?>
                                        <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($log['username']) ?></div>
                                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase;"><?= htmlspecialchars($log['role']) ?></div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">Sistem / Anonymous</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $act_class = 'action-other';
                                        switch(strtoupper($log['action'])) {
                                            case 'LOGIN': $act_class = 'action-login'; break;
                                            case 'LOGOUT': $act_class = 'action-logout'; break;
                                            case 'CREATE': $act_class = 'action-create'; break;
                                            case 'UPDATE': $act_class = 'action-update'; break;
                                            case 'DELETE': $act_class = 'action-delete'; break;
                                        }
                                    ?>
                                    <span class="badge-action <?= $act_class ?>"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td class="log-desc"><?= htmlspecialchars($log['description']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">
                                    <i class="fa-solid fa-shield-cat" style="font-size: 40px; margin-bottom: 10px; color: #e2e8f0; display:block;"></i>
                                    Radar kosong. Belum ada aktivitas yang terekam.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman > 1): ?>
                <div class="pagination-box">
                    <?php 
                        $query_string = $_GET;
                        unset($query_string['halaman']);
                    ?>
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <?php 
                            $active = ($halaman == $i) ? 'active' : '';
                            $query_string['halaman'] = $i;
                            $url_paginasi = "?" . http_build_query($query_string);
                        ?>
                        <a href="<?= $url_paginasi ?>" class="page-item <?= $active ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <script>
    function konfirmasiBersihkan(e) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Wipe Out Radar Data?',
            text: "Perhatian! Tindakan ini akan menghapus permanen SELURUH rekam jejak aktivitas (Log) dari server. Tindakan ini tidak bisa dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#1e293b',
            confirmButtonText: '<i class="fa-solid fa-skull"></i> Ya, Musnahkan Log',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
    </script>
</body>
</html>