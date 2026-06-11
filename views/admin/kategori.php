<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

$error = '';
$success = '';

// 3. Proses Menambah Kategori Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    
    if (!empty($nama_kategori)) {
        $slug = createSlug($nama_kategori);
        
        try {
            // Cek apakah kategori dengan nama/slug ini sudah ada
            $cek = $pdo->prepare("SELECT id FROM kategori WHERE slug = ?");
            $cek->execute([$slug]);
            
            if ($cek->rowCount() > 0) {
                $error = "Kategori dengan nama tersebut sudah ada!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, slug) VALUES (?, ?)");
                $stmt->execute([$nama_kategori, $slug]);
                $success = "Kategori baru berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            $error = "Gagal menambah kategori: " . $e->getMessage();
        }
    } else {
        $error = "Nama kategori tidak boleh kosong!";
    }
}

// 4. Proses Menghapus Kategori
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id_hapus]);
        $success = "Kategori berhasil dihapus!";
    } catch (PDOException $e) {
        // Mencegah error jika kategori sedang dipakai di tabel berita (Integrity Constraint)
        $error = "Gagal! Kategori ini tidak bisa dihapus karena sedang digunakan oleh artikel berita.";
    }
}

// 5. Mengambil daftar semua kategori
try {
    $stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY id DESC");
    $daftar_kategori = $stmt_kategori->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* Sidebar Menu (Dipertahankan untuk styling sidebar.php) */
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

        /* Grid Layout (Form & Table) */
        .kategori-grid { display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 20px; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #1c1917; border-bottom: 1px solid #e4e4e7; padding-bottom: 12px; }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: #ff6b00; }
        .btn-submit { width: 100%; background: #121212; color: #ffffff; border: none; padding: 12px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { background: #ff6b00; }

        /* Alert Styling */
        .alert { padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Tabel Data */
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 15px 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; background-color: #fafafa; }
        .admin-table td { padding: 16px 12px; border-bottom: 1px solid #f4f4f5; vertical-align: middle; }
        .admin-table tr:hover { background-color: #fafafa; }
        
        .badge-slug { background: #f4f4f5; color: #71717a; font-family: monospace; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        
        /* Tombol Aksi */
        .btn-action { color: #71717a; margin: 0 5px; font-size: 15px; text-decoration: none; transition: color 0.2s; display: inline-flex; width: 32px; height: 32px; background: #f4f4f5; align-items: center; justify-content: center; border-radius: 6px; }
        .btn-action.delete:hover { color: #fff; background: #ef4444; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Kelola Kategori Berita</h2>
            <div style="font-size: 14px; color: #71717a;">
                <i class="fa-solid fa-tags" style="color: #ff6b00; margin-right: 5px;"></i> <?= count($daftar_kategori) ?> Kategori Tersedia
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="kategori-grid">
            
            <div class="content-card">
                <div class="card-header">Tambah Kategori Baru</div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Cth: Teknologi, Olahraga..." required autocomplete="off">
                        <small style="color:#a1a1aa; display:block; margin-top:8px;">URL Slug akan dibuat secara otomatis berdasarkan nama kategori yang Anda ketik.</small>
                    </div>
                    <button type="submit" name="tambah_kategori" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Kategori
                    </button>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">Daftar Kategori Tersedia</div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">No</th>
                            <th style="width: 40%;">Nama Kategori</th>
                            <th style="width: 35%;">URL Slug</th>
                            <th style="width: 15%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($daftar_kategori) > 0): ?>
                            <?php $no = 1; foreach($daftar_kategori as $cat): ?>
                            <tr>
                                <td style="color: #a1a1aa; font-weight: 600;"><?= $no++ ?></td>
                                <td style="font-weight: 600; color: #1c1917;">
                                    <?= htmlspecialchars($cat['nama_kategori']) ?>
                                </td>
                                <td><span class="badge-slug">/<?= htmlspecialchars($cat['slug']) ?></span></td>
                                <td style="text-align: center;">
                                    <a href="?hapus=<?= $cat['id'] ?>" class="btn-action delete" title="Hapus Kategori" onclick="return confirm('Yakin ingin menghapus kategori ini? Jika terhubung dengan berita, sistem akan menolaknya.')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #a1a1aa; padding: 40px;">Belum ada kategori yang ditambahkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>