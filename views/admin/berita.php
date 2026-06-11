<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. Fitur Pencarian Berita
$search = trim($_GET['cari'] ?? '');
$where_clause = '';
$params = [];

if (!empty($search)) {
    // Mencari berdasarkan judul berita
    $where_clause = "WHERE b.judul LIKE ?";
    $params[] = "%$search%";
}

// 4. Mengambil data seluruh berita dari database (menggunakan JOIN untuk nama kategori & penulis)
try {
    $query_berita = "
        SELECT b.id, b.judul, b.views, b.created_at, 
               k.nama_kategori, 
               u.username AS penulis 
        FROM berita b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN users u ON b.penulis_id = u.id
        $where_clause
        ORDER BY b.created_at DESC
    ";
    
    $stmt_berita = $pdo->prepare($query_berita);
    $stmt_berita->execute($params);
    $daftar_berita = $stmt_berita->fetchAll();
    
} catch (PDOException $e) {
    die("Terjadi kesalahan saat memuat data berita: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Menu (Tetap dipertahankan untuk styling sidebar.php) */
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

        /* Area Konten Utama */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }

        /* Area Kontrol Tabel (Search & Button) */
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-form { display: flex; gap: 10px; width: 400px; }
        .search-input { width: 100%; padding: 10px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: #ff6b00; }
        .btn-search { background: #18181b; color: #fff; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .btn-search:hover { background: #3f3f46; }

        /* Card Tabel */
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-primary { background: #ff6b00; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: background 0.3s; }
        .btn-primary:hover { background: #e05e00; }
        
        .badge-cat { background: #f4f4f5; color: #3f3f46; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-views { background: #e0f2fe; color: #0ea5e9; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;}

        /* Desain Tabel Data */
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 15px 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; background-color: #fafafa; }
        .admin-table td { padding: 16px 12px; border-bottom: 1px solid #f4f4f5; vertical-align: middle; }
        .admin-table tr:hover { background-color: #fafafa; }
        
        /* Tombol Aksi */
        .btn-action { color: #71717a; margin: 0 5px; font-size: 15px; text-decoration: none; transition: color 0.2s; display: inline-flex; width: 32px; height: 32px; background: #f4f4f5; align-items: center; justify-content: center; border-radius: 6px; }
        .btn-action.edit:hover { color: #fff; background: #3b82f6; }
        .btn-action.delete:hover { color: #fff; background: #ef4444; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Manajemen Konten Berita</h2>
            <div style="font-size: 14px; color: #71717a;">
                <i class="fa-solid fa-database" style="color: #ff6b00; margin-right: 5px;"></i> Total: <?= count($daftar_berita) ?> Artikel Terdata
            </div>
        </header>

        <div class="table-controls">
            <form action="" method="GET" class="search-form">
                <input type="text" name="cari" class="search-input" placeholder="Ketik judul berita yang ingin dicari..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($search)): ?>
                    <a href="/admin/berita" class="btn-search" style="background:#ef4444; text-decoration:none;"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
            
            <a href="/admin/tulis" class="btn-primary"><i class="fa-solid fa-plus"></i> Tulis Berita Baru</a>
        </div>

        <div class="content-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 35%;">Judul Berita</th>
                        <th style="width: 15%;">Kategori</th>
                        <th style="width: 15%;">Tgl. Publikasi</th>
                        <th style="width: 10%; text-align: center;">Views</th>
                        <th style="width: 10%;">Penulis</th>
                        <th style="width: 10%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($daftar_berita) > 0): ?>
                        <?php $no = 1; foreach($daftar_berita as $row): ?>
                        <tr>
                            <td style="color: #a1a1aa; font-weight: 600;"><?= $no++ ?></td>
                            <td style="font-weight: 600; color: #1c1917; line-height: 1.4;">
                                <?= htmlspecialchars($row['judul']) ?>
                            </td>
                            <td><span class="badge-cat"><?= htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori') ?></span></td>
                            <td style="color: #52525b;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td style="text-align: center;">
                                <span class="badge-views"><i class="fa-solid fa-eye"></i> <?= number_format($row['views']) ?></span>
                            </td>
                            <td style="color: #71717a; font-size: 13px;">
                                <i class="fa-solid fa-user-pen" style="margin-right:4px;"></i> <?= htmlspecialchars($row['penulis'] ?? 'Admin') ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="/admin/edit/<?= $row['id'] ?>" class="btn-action edit" title="Edit Berita"><i class="fa-solid fa-pen"></i></a>
                                <a href="/admin/hapus/<?= $row['id'] ?>" class="btn-action delete" title="Hapus Berita" onclick="return confirm('PERINGATAN: Anda yakin ingin menghapus berita ini secara permanen? Data tidak dapat dikembalikan!')"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #a1a1aa; padding: 50px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; color: #e4e4e7; display: block;"></i>
                                <?php if(!empty($search)): ?>
                                    Berita dengan judul "<strong><?= htmlspecialchars($search) ?></strong>" tidak ditemukan.
                                <?php else: ?>
                                    Belum ada artikel berita yang diterbitkan.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>