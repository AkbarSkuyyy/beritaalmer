<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. PROTEKSI KEAMANAN TERTINGGI: Cek Otoritas Role Superadmin
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

// ------------------------------------------------------------------------
// FITUR MAGIC VIEW: Manipulasi Tayangan secara AJAX (Super Admin Only)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_views_ajax') {
    $id_berita = (int)$_POST['id'];
    $views_baru = (int)$_POST['views'];
    
    try {
        $stmt_update = $pdo->prepare("UPDATE berita SET views = ? WHERE id = ?");
        $stmt_update->execute([$views_baru, $id_berita]);
        
        echo json_encode(['status' => 'success']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

$error = '';
$success = '';

// 4. PROSES SENSOR / HAPUS BERITA (Dilengkapi Auto-Hapus Gambar Server)
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        // Ambil nama file gambar sebelum berita dihapus
        $stmt_gambar = $pdo->prepare("SELECT gambar FROM berita WHERE id = ?");
        $stmt_gambar->execute([$id_hapus]);
        $data_gambar = $stmt_gambar->fetchColumn();

        // Hapus data dari database
        $stmt_delete = $pdo->prepare("DELETE FROM berita WHERE id = ?");
        $stmt_delete->execute([$id_hapus]);

        // Hapus file fisik gambar dari server agar hosting tidak penuh
        if ($data_gambar) {
            $file_path = __DIR__ . '/../../assets/uploads/' . $data_gambar;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $success = "Sensor berhasil! Artikel dan gambar sampulnya telah dimusnahkan dari server.";
    } catch (PDOException $e) {
        $error = "Gagal menghapus berita: " . $e->getMessage();
    }
}

// 5. FITUR PENCARIAN & FILTERING BERLAPIS
$search_query = $_GET['q'] ?? '';
$filter_kategori = $_GET['kategori_id'] ?? '';
$filter_penulis = $_GET['penulis_id'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "b.judul LIKE ?";
    $params[] = "%$search_query%";
}
if (!empty($filter_kategori)) {
    $where_clauses[] = "b.kategori_id = ?";
    $params[] = $filter_kategori;
}
if (!empty($filter_penulis)) {
    $where_clauses[] = "b.penulis_id = ?";
    $params[] = $filter_penulis;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 6. SISTEM PAGINASI (Slide Halaman)
$limit = 10; // Jumlah baris per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;
$offset = ($halaman - 1) * $limit;

// Hitung total data berdasarkan filter
$stmt_count = $pdo->prepare("SELECT COUNT(b.id) FROM berita b $where_sql");
$stmt_count->execute($params);
$total_data = $stmt_count->fetchColumn();
$total_halaman = ceil($total_data / $limit);

// 7. QUERY AMBIL DATA BERITA (Menggunakan JOIN dan LIMIT)
try {
    $query_berita = "
        SELECT b.id, b.judul, b.slug, b.gambar, b.views, b.created_at, 
               k.nama_kategori, u.username as penulis
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        $where_sql
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt_berita = $pdo->prepare($query_berita);
    $stmt_berita->execute($params);
    $daftar_berita = $stmt_berita->fetchAll();
    
    // Ambil data untuk opsi dropdown filter
    $kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
    $penulis_list = $pdo->query("SELECT id, username, role FROM users ORDER BY username ASC")->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan sistem logistik: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor & Kelola Berita | Super Admin</title>
    
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
        .filter-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 15px; align-items: end; }
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
        
        .thumb-img { width: 60px; height: 45px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .news-title { font-weight: 700; color: #0f172a; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
        .news-meta { font-size: 12px; color: #64748b; }
        .badge-cat { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

        /* Tombol Aksi & Manipulasi */
        .action-flex { display: flex; gap: 8px; justify-content: center;}
        .btn-act { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .btn-act.view { background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; }
        .btn-act.view:hover { background: #0ea5e9; color: #fff; }
        .btn-act.edit { background: #f5f3ff; color: #7c3aed; border: 1px solid #d8b4fe; }
        .btn-act.edit:hover { background: #7c3aed; color: #fff; }
        .btn-act.delete { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; }
        .btn-act.delete:hover { background: #ef4444; color: #fff; }

        .btn-magic { background: #fdf4ff; color: #c026d3; border: 1px solid #f5d0fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; font-weight: 700; }
        .btn-magic:hover { background: #c026d3; color: #fff; }

        /* Paginasi Modern */
        .pagination-box { padding: 25px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 8px; }
        .page-item { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-weight: 600; text-decoration: none; transition: 0.3s; font-family: 'Outfit', sans-serif; font-size: 14px; }
        .page-item:hover { border-color: #7c3aed; color: #7c3aed; }
        .page-item.active { background: #7c3aed; color: #ffffff; border-color: #7c3aed; box-shadow: 0 4px 10px rgba(124,58,237,0.3); }

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
            .master-table { min-width: 850px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Sensor & Kelola Berita</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Pantau, perbarui, dan musnahkan artikel berita dari seluruh jaringan penulis.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-satellite-dish"></i> CONTENT RADAR
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="toast-msg toast-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="toast-msg toast-succ"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="filter-panel">
            <form action="" method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Pencarian Kata Kunci</label>
                    <input type="text" name="q" class="filter-control" placeholder="Cari judul berita..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <div class="filter-group">
                    <label>Filter Kategori</label>
                    <select name="kategori_id" class="filter-control">
                        <option value="">-- Semua Kategori --</option>
                        <?php foreach($kategori_list as $kl): ?>
                            <option value="<?= $kl['id'] ?>" <?= ($filter_kategori == $kl['id']) ? 'selected' : '' ?>><?= htmlspecialchars($kl['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Filter Penulis</label>
                    <select name="penulis_id" class="filter-control">
                        <option value="">-- Semua Penulis --</option>
                        <?php foreach($penulis_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= ($filter_penulis == $pl['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pl['username']) ?> [<?= strtoupper($pl['role']) ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                <a href="?" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            </form>
        </div>

        <div class="table-panel">
            <div class="table-header">
                <div class="table-title">
                    <i class="fa-solid fa-table-list" style="color: #7c3aed;"></i> Menampilkan <?= number_format($total_data) ?> Artikel
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Sampul</th>
                            <th style="width: 32%;">Detail Berita</th>
                            <th style="width: 15%;">Kategori</th>
                            <th style="width: 15%;">Penulis</th>
                            <th style="width: 15%;">Statistik & Magic</th>
                            <th style="width: 15%; text-align: center;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($daftar_berita) > 0): ?>
                            <?php foreach($daftar_berita as $br): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($br['gambar'])): ?>
                                        <img src="/assets/uploads/<?= htmlspecialchars($br['gambar']) ?>" class="thumb-img" alt="Thumb">
                                    <?php else: ?>
                                        <div class="thumb-img" style="background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="news-title" title="<?= htmlspecialchars($br['judul']) ?>"><?= htmlspecialchars($br['judul']) ?></div>
                                    <div class="news-meta"><i class="fa-regular fa-clock"></i> <?= date('d M Y, H:i', strtotime($br['created_at'])) ?> WIB</div>
                                </td>
                                <td><span class="badge-cat"><?= htmlspecialchars($br['nama_kategori'] ?? 'Tanpa Kategori') ?></span></td>
                                <td style="font-weight: 600; color: #334155;"><i class="fa-solid fa-user-pen" style="color:#cbd5e1; margin-right:5px;"></i><?= htmlspecialchars($br['penulis']) ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="font-weight: 700; color: #0ea5e9;"><i class="fa-solid fa-eye"></i> <?= number_format($br['views']) ?>x</div>
                                        
                                        <button type="button" class="btn-magic" onclick="magicView(<?= $br['id'] ?>, <?= $br['views'] ?>, '<?= htmlspecialchars(addslashes($br['judul'])) ?>')" title="Manipulasi Jumlah Tayangan">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        </button>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-flex">
                                        <a href="/berita/<?= htmlspecialchars($br['slug']) ?>" target="_blank" class="btn-act view" title="Lihat di Portal"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                        <a href="/admin/edit/<?= $br['id'] ?>" class="btn-act edit" title="Revisi Berita"><i class="fa-solid fa-pen"></i></a>
                                        <a href="#" class="btn-act delete" title="Sensor / Hapus Permanen" onclick="konfirmasiHapus(<?= $br['id'] ?>)"><i class="fa-solid fa-trash-can"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 40px;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 10px; color: #e2e8f0; display:block;"></i>
                                    Tidak ada berita yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_halaman > 1): ?>
                <div class="pagination-box">
                    <?php 
                        // Menyusun ulang query string untuk mempertahankan filter saat pindah halaman
                        $query_string = $_GET;
                        unset($query_string['halaman']); // Hapus parameter halaman agar bisa di-replace
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
    // Konfirmasi Hapus Menggunakan SweetAlert2
    function konfirmasiHapus(id) {
        Swal.fire({
            title: 'Eksekusi Sensor Konten?',
            text: "Berita beserta gambar sampulnya akan dimusnahkan secara permanen dari server database dan public storage.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#1e293b',
            confirmButtonText: '<i class="fa-solid fa-fire"></i> Ya, Musnahkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Pertahankan query pencarian di URL saat ini
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('hapus', id);
                window.location.href = '?' + urlParams.toString();
            }
        });
    }

    // FITUR MAGIC VIEW: Manipulasi Tayangan Berita
    function magicView(id, currentViews, judul) {
        Swal.fire({
            title: '<i class="fa-solid fa-wand-magic-sparkles" style="color:#c026d3;"></i> Magic View',
            html: `Ubah total jumlah tayangan pada berita:<br><br><b style="font-size:14px; color:#334155;">${judul}</b>`,
            input: 'number',
            inputValue: currentViews,
            inputAttributes: { min: 0 },
            showCancelButton: true,
            confirmButtonColor: '#c026d3',
            cancelButtonColor: '#1e293b',
            confirmButtonText: 'Terapkan Sihir',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value || value < 0) {
                    return 'Sistem menolak! Angka tayangan tidak valid.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newViews = result.value;
                const formData = new FormData();
                formData.append('action', 'update_views_ajax');
                formData.append('id', id);
                formData.append('views', newViews);

                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            title: 'Sihir Berhasil!',
                            text: 'Angka trafik pembaca telah dimanipulasi oleh Super Admin.',
                            icon: 'success',
                            confirmButtonColor: '#c026d3'
                        }).then(() => {
                            window.location.reload(); 
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }
    </script>
</body>
</html>