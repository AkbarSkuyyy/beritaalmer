<?php
// 1. Panggil koneksi database
require_once __DIR__ . '/../../config.php';
global $pdo;

// 2. Proteksi Halaman: Wajib Login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /login");
    exit;
}

// 3. FITUR BARU: Tangkap Request AJAX untuk Drag & Drop
// Jika ada request dari Javascript untuk update urutan, proses di sini secara diam-diam
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_urutan_ajax') {
    if (isset($_POST['urutan']) && is_array($_POST['urutan'])) {
        try {
            $pdo->beginTransaction();
            $stmt_update = $pdo->prepare("UPDATE kategori SET urutan = ? WHERE id = ?");
            
            // Looping data urutan yang dikirim oleh Javascript
            foreach ($_POST['urutan'] as $index => $id_kategori) {
                $stmt_update->execute([$index, (int)$id_kategori]);
            }
            
            $pdo->commit();
            // Berikan balasan format JSON ke Javascript bahwa proses berhasil
            echo json_encode(['status' => 'success']);
            exit; // Hentikan eksekusi script di sini agar HTML di bawah tidak ikut terkirim ke AJAX
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

$error = '';
$success = '';

// 4. Proses Menambah Kategori Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    
    if (!empty($nama_kategori)) {
        $slug = createSlug($nama_kategori);
        
        try {
            $cek = $pdo->prepare("SELECT id FROM kategori WHERE slug = ?");
            $cek->execute([$slug]);
            
            if ($cek->rowCount() > 0) {
                $error = "Kategori dengan nama tersebut sudah ada!";
            } else {
                // Berikan urutan terbesar (paling bawah) secara otomatis untuk kategori baru
                $stmt_max = $pdo->query("SELECT MAX(urutan) FROM kategori");
                $max_urutan = (int)$stmt_max->fetchColumn();
                $urutan_baru = $max_urutan + 1;

                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, slug, urutan) VALUES (?, ?, ?)");
                $stmt->execute([$nama_kategori, $slug, $urutan_baru]);
                $success = "Kategori baru berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            $error = "Gagal menambah kategori. Pastikan Anda sudah menjalankan query ALTER TABLE di phpMyAdmin: " . $e->getMessage();
        }
    } else {
        $error = "Nama kategori tidak boleh kosong!";
    }
}

// 5. Proses Menghapus Kategori
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id_hapus]);
        $success = "Kategori berhasil dihapus!";
    } catch (PDOException $e) {
        $error = "Gagal! Kategori ini tidak bisa dihapus karena sedang digunakan oleh artikel berita.";
    }
}

// 6. Mengambil daftar semua kategori diurutkan berdasarkan kolom 'urutan'
try {
    $stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY urutan ASC, id DESC");
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
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
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

        .kategori-grid { display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }
        .content-card { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 20px; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #1c1917; border-bottom: 1px solid #e4e4e7; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: #ff6b00; }
        
        .btn-submit { background: #121212; color: #ffffff; border: none; padding: 12px 20px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        .btn-submit:hover { background: #ff6b00; }

        .alert { padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .admin-table th { padding: 15px 12px; color: #71717a; font-weight: 600; border-bottom: 2px solid #e4e4e7; background-color: #fafafa; }
        .admin-table td { padding: 16px 12px; border-bottom: 1px solid #f4f4f5; vertical-align: middle; }
        
        /* Modifikasi baris saat diseret (drag) */
        .sortable-ghost { opacity: 0.4; background-color: #fff7ed; }
        .sortable-drag { background-color: #ffffff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); cursor: grabbing !important; }
        
        .drag-handle { color: #d4d4d8; cursor: grab; text-align: center; transition: color 0.2s; }
        .drag-handle:hover { color: #ff6b00; }
        
        .badge-slug { background: #f4f4f5; color: #71717a; font-family: monospace; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        
        .btn-action { color: #71717a; margin: 0 5px; font-size: 15px; text-decoration: none; transition: color 0.2s; display: inline-flex; width: 32px; height: 32px; background: #f4f4f5; align-items: center; justify-content: center; border-radius: 6px; }
        .btn-action.delete:hover { color: #fff; background: #ef4444; }

        /* Notifikasi Toast Kecil di Kanan Atas */
        .toast-notify {
            position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; 
            border-radius: 6px; font-weight: 600; font-size: 13px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transform: translateX(150%); transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); z-index: 9999;
        }
        .toast-notify.show { transform: translateX(0); }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div id="toast" class="toast-notify"><i class="fa-solid fa-check-circle" style="margin-right: 5px;"></i> Urutan tersimpan!</div>

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
                <div class="card-header">
                    Daftar Kategori Tersedia
                    <span style="font-size: 12px; color: #a1a1aa; font-weight: 500;"><i class="fa-solid fa-arrows-up-down"></i> Geser baris untuk mengurutkan</span>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 10%; text-align: center;">Geser</th>
                            <th style="width: 40%;">Nama Kategori</th>
                            <th style="width: 35%;">URL Slug</th>
                            <th style="width: 15%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="kategori-list">
                        <?php if (count($daftar_kategori) > 0): ?>
                            <?php foreach($daftar_kategori as $cat): ?>
                            <tr data-id="<?= $cat['id'] ?>">
                                <td class="drag-handle" title="Tarik dan geser baris ini">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </td>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('kategori-list');
            
            if (tableBody) {
                // Mengaktifkan SortableJS
                Sortable.create(tableBody, {
                    handle: '.drag-handle', // Area klik dibatasi hanya pada ikon titik (grip)
                    animation: 200,         // Animasi transisi yang halus
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: function (evt) {
                        // Dijalankan saat baris selesai digeser (dilepas)
                        
                        // Kumpulkan susunan ID terbaru dari atas ke bawah
                        const rows = tableBody.querySelectorAll('tr');
                        const urutanData = [];
                        rows.forEach(function(row) {
                            urutanData.push(row.getAttribute('data-id'));
                        });

                        // Menyiapkan paket data untuk dikirim ke PHP
                        const formData = new FormData();
                        formData.append('action', 'update_urutan_ajax');
                        urutanData.forEach(id => formData.append('urutan[]', id));

                        // Kirim data ke PHP di latar belakang tanpa me-refresh halaman
                        fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                // Munculkan pop-up hijau di kanan atas jika sukses
                                const toast = document.getElementById('toast');
                                toast.classList.add('show');
                                setTimeout(() => { toast.classList.remove('show'); }, 3000);
                            } else {
                                alert('Terjadi kesalahan saat menyimpan: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>