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

$error = '';
$success = '';

// 4. EKSEKUSI PENGHAPUSAN AKUN
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Fitur Keamanan: Mencegah Super Admin menghapus dirinya sendiri saat sedang login
    if ($id_hapus === $_SESSION['admin_id']) {
        $error = "Penolakan Akses: Anda tidak dapat memusnahkan akun Super Admin Anda sendiri yang sedang aktif digunakan!";
    } else {
        try {
            // Cek apakah akun memiliki artikel berita (mencegah error foreign key jika ada)
            $cek_berita = $pdo->prepare("SELECT id FROM berita WHERE penulis_id = ?");
            $cek_berita->execute([$id_hapus]);
            
            if ($cek_berita->rowCount() > 0) {
                $error = "Gagal: Akun ini tidak bisa dihapus karena masih menjadi pemilik dari beberapa artikel berita. Hapus atau pindahkan beritanya terlebih dahulu.";
            } else {
                $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt_delete->execute([$id_hapus]);
                $success = "Akun berhasil dimusnahkan dari jaringan server.";
            }
        } catch (PDOException $e) {
            $error = "Sistem gagal mengeksekusi penghapusan: " . $e->getMessage();
        }
    }
}

// 5. SISTEM PENCARIAN & FILTER
$search_query = $_GET['q'] ?? '';
$filter_role = $_GET['role'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}
if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 6. QUERY AMBIL SELURUH DAFTAR AKUN
try {
    $query_users = "
        SELECT id, username, email, role, created_at 
        FROM users 
        $where_sql 
        ORDER BY FIELD(role, 'superadmin', 'admin', 'user'), created_at DESC
    ";
    $stmt_users = $pdo->prepare($query_users);
    $stmt_users->execute($params);
    $daftar_akun = $stmt_users->fetchAll();
    
    // Total akun setelah filter
    $total_data = count($daftar_akun);
} catch (PDOException $e) {
    die("Terjadi kesalahan pembacaan database pengguna: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Daftar Akun | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
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
        .filter-panel { background: #ffffff; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; margin-bottom: 30px; }
        .filter-grid { display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 15px; align-items: end; }
        .filter-group label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        .filter-control { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; color: #0f172a; }
        .filter-control:focus { border-color: #7c3aed; }
        .btn-filter { background: #0f172a; color: #fff; border: none; padding: 13px 20px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.25s; height: 44px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-filter:hover { background: #7c3aed; }
        .btn-reset { background: #f1f5f9; color: #475569; border: none; padding: 13px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; height: 44px; transition: 0.2s; }
        .btn-reset:hover { background: #e2e8f0; color: #0f172a; }

        /* Panel Tabel */
        .table-panel { background: #ffffff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        
        .master-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .master-table th { padding: 15px 25px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background-color: #f8fafc; font-family: 'Outfit', sans-serif; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .master-table td { padding: 18px 25px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .master-table tr:hover { background-color: #fafafa; }
        
        .user-identity { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #fff; background: #cbd5e1; }
        .user-name { font-weight: 700; color: #0f172a; margin-bottom: 2px; }
        .user-email { font-size: 12px; color: #64748b; }
        
        /* Tema Badge Role */
        .badge-role { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; }
        .badge-super { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
        .badge-admin { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .badge-user { background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; }

        .btn-act { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; font-size: 14px; transition: 0.2s; border: none; cursor: pointer; }
        .btn-act.delete { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; }
        .btn-act.delete:hover { background: #ef4444; color: #fff; }
        .btn-act.disabled { opacity: 0.4; cursor: not-allowed; background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }

        .toast-msg { padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .toast-err { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .toast-succ { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        @media (max-width: 1200px) { .filter-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            .btn-filter, .btn-reset { width: 100%; justify-content: center; }
            .table-responsive { overflow-x: auto; }
            .master-table { min-width: 700px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Otoritas Daftar Akun Jaringan</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Pusat kendali untuk memonitor, menyaring, dan membatasi akses staff portal.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-users-viewfinder"></i> USER DIRECTORY
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="toast-msg toast-err"><i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="toast-msg toast-succ"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="filter-panel">
            <form action="" method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Pencarian Identitas</label>
                    <input type="text" name="q" class="filter-control" placeholder="Cari username atau email..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <div class="filter-group">
                    <label>Filter Derajat Akses</label>
                    <select name="role" class="filter-control">
                        <option value="">-- Seluruh Role --</option>
                        <option value="superadmin" <?= ($filter_role == 'superadmin') ? 'selected' : '' ?>>Super Admin</option>
                        <option value="admin" <?= ($filter_role == 'admin') ? 'selected' : '' ?>>Administrator</option>
                        <option value="user" <?= ($filter_role == 'user') ? 'selected' : '' ?>>Penulis Biasa (User)</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Filter Akun</button>
                <a href="?" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            </form>
        </div>

        <div class="table-panel">
            <div class="table-header">
                <div class="table-title">
                    <i class="fa-solid fa-server" style="color: #7c3aed;"></i> Total <?= number_format($total_data) ?> Akun Terekam
                </div>
                <a href="/admin/superadmin/tambah" class="btn-filter" style="height: auto; padding: 10px 18px; border-radius: 6px;"><i class="fa-solid fa-user-plus"></i> Injeksi Akun Baru</a>
            </div>
            
            <div class="table-responsive">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Identitas Pengguna</th>
                            <th style="width: 25%;">Derajat Otoritas</th>
                            <th style="width: 25%;">Tanggal Bergabung</th>
                            <th style="width: 15%; text-align: center;">Pemutusan Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php foreach($daftar_akun as $akun): ?>
                            <tr>
                                <td>
                                    <div class="user-identity">
                                        <div class="user-avatar" style="background: <?= ($akun['role'] == 'superadmin') ? '#c084fc' : (($akun['role'] == 'admin') ? '#fca5a5' : '#7dd3fc') ?>">
                                            <?= strtoupper(substr($akun['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?= htmlspecialchars($akun['username']) ?> 
                                                <?= ($akun['id'] == $_SESSION['admin_id']) ? '<span style="color:#10b981; font-size:11px; margin-left:5px;">(Anda)</span>' : '' ?>
                                            </div>
                                            <div class="user-email"><?= htmlspecialchars($akun['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        if($akun['role'] === 'superadmin') {
                                            echo '<span class="badge-role badge-super"><i class="fa-solid fa-crown"></i> Super Admin</span>';
                                        } elseif($akun['role'] === 'admin') {
                                            echo '<span class="badge-role badge-admin"><i class="fa-solid fa-user-gear"></i> Administrator</span>';
                                        } else {
                                            echo '<span class="badge-role badge-user"><i class="fa-solid fa-pen-nib"></i> Penulis Biasa</span>';
                                        }
                                    ?>
                                </td>
                                <td style="color: #64748b; font-size: 13px; font-weight: 500;">
                                    <i class="fa-regular fa-calendar" style="margin-right: 5px;"></i> <?= date('d F Y', strtotime($akun['created_at'])) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($akun['id'] == $_SESSION['admin_id']): ?>
                                        <button type="button" class="btn-act disabled" title="Tidak dapat menghapus diri sendiri"><i class="fa-solid fa-lock"></i></button>
                                    <?php else: ?>
                                        <button type="button" class="btn-act delete" title="Musnahkan Akun" onclick="konfirmasiHapus(<?= $akun['id'] ?>, '<?= htmlspecialchars(addslashes($akun['username'])) ?>')"><i class="fa-solid fa-trash-can"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;">
                                    <i class="fa-solid fa-users-slash" style="font-size: 40px; margin-bottom: 10px; color: #e2e8f0; display:block;"></i>
                                    Tidak ada data pengguna yang cocok dengan filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
    // Konfirmasi Hapus Menggunakan SweetAlert2
    function konfirmasiHapus(id, username) {
        Swal.fire({
            title: 'Musnahkan Identitas?',
            html: `Akun <b>${username}</b> akan dihapus secara permanen dan tidak dapat login kembali.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#1e293b',
            confirmButtonText: '<i class="fa-solid fa-ban"></i> Ya, Cabut Akses!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mempertahankan filter URL saat penghapusan
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('hapus', id);
                window.location.href = '?' + urlParams.toString();
            }
        });
    }
    </script>
</body>
</html>