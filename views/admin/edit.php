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

// 3. Menangkap ID Berita dari URL (Mendukung format /admin/edit/1 atau ?id=1)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', rtrim($path, '/'));
$id_berita = (int)end($segments);

if ($id_berita === 0 && isset($_GET['id'])) {
    $id_berita = (int)$_GET['id'];
}

if ($id_berita === 0) {
    // Jika tidak ada ID yang valid, kembalikan ke halaman daftar berita
    header("Location: /admin/berita");
    exit;
}

// 4. Ambil data berita yang akan diedit
try {
    $stmt_berita = $pdo->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt_berita->execute([$id_berita]);
    $berita = $stmt_berita->fetch();

    if (!$berita) {
        die("Artikel berita tidak ditemukan di database.");
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

// 5. Ambil semua kategori untuk dropdown
try {
    $stmt_cat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal mengambil data kategori: " . $e->getMessage();
}

// 6. Proses Update Data saat form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul       = trim($_POST['judul']);
    $kategori_id = $_POST['kategori_id'];
    $konten      = trim($_POST['konten']);
    
    if (!empty($judul) && !empty($kategori_id) && !empty($konten)) {
        $slug = createSlug($judul);
        $nama_gambar = $berita['gambar']; // Secara default, pertahankan gambar lama
        
        // Jika ada file gambar baru yang diunggah
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = __DIR__ . '/../../assets/uploads/';
            
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowed_ext    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($file_extension), $allowed_ext)) {
                $nama_gambar_baru = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $nama_gambar_baru;

                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    // Hapus gambar lama dari server jika ada
                    if (!empty($berita['gambar']) && file_exists($target_dir . $berita['gambar'])) {
                        unlink($target_dir . $berita['gambar']);
                    }
                    $nama_gambar = $nama_gambar_baru; // Setel gambar baru untuk dimasukkan ke DB
                } else {
                    $error = "Gagal mengunggah gambar sampul yang baru.";
                }
            } else {
                $error = "Format gambar salah! Hanya JPG, JPEG, PNG, dan WEBP yang diizinkan.";
            }
        }

        // Jalankan query UPDATE jika tidak ada error
        if (empty($error)) {
            try {
                $stmt_update = $pdo->prepare("UPDATE berita SET judul = ?, slug = ?, konten = ?, gambar = ?, kategori_id = ? WHERE id = ?");
                $stmt_update->execute([$judul, $slug, $konten, $nama_gambar, $kategori_id, $id_berita]);
                
                $success = "Artikel berita berhasil diperbarui!";
                
                // Segarkan data array agar form langsung menampilkan data terbaru
                $berita['judul'] = $judul;
                $berita['kategori_id'] = $kategori_id;
                $berita['konten'] = $konten;
                $berita['gambar'] = $nama_gambar;
                
            } catch (PDOException $e) {
                $error = "Gagal memperbarui berita: " . $e->getMessage();
            }
        }
    } else {
        $error = "Tajuk, kategori, dan konten wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

    <style>
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

        /* Area Konten Utama */
        .admin-main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; background: #fff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-header h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        .btn-back { background: #f4f4f5; color: #3f3f46; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; }
        .btn-back:hover { background: #e4e4e7; color: #1c1917; }

        /* Card Form */
        .form-card { background: #ffffff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: #ff6b00; }
        
        /* Notifikasi */
        .alert { padding: 15px; border-radius: 6px; font-size: 14px; margin-bottom: 25px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Tombol & Preview Gambar */
        .btn-submit { background: #ff6b00; color: #ffffff; border: none; padding: 14px 28px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #e05e00; }
        
        .current-image-preview { margin-top: 15px; display: inline-block; position: relative; border-radius: 8px; overflow: hidden; border: 2px solid #e4e4e7; }
        .current-image-preview img { display: block; max-width: 250px; height: auto; }
        .img-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; padding: 5px; text-align: center; font-weight: 600; }

        .tox-tinymce { border-radius: 8px !important; border-color: #e4e4e7 !important; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2><i class="fa-solid fa-pen-nib" style="color: #ff6b00;"></i> Edit Artikel Berita</h2>
            <a href="/admin/berita" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar</a>
        </header>

        <div class="form-card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="judul">Tajuk / Judul Berita</label>
                    <input type="text" name="judul" id="judul" class="form-control" value="<?= htmlspecialchars($berita['judul']) ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="kategori_id">Kategori Berita</label>
                    <select name="kategori_id" id="kategori_id" class="form-control" required>
                        <option value="">-- Pilih Kategori Berita --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $berita['kategori_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gambar">Gambar Utama / Sampul Berita</label>
                    <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*">
                    <small style="color:#71717a; display:block; margin-top:5px;">Biarkan kosong jika Anda tidak ingin mengubah gambar sampul saat ini.</small>
                    
                    <?php if(!empty($berita['gambar'])): ?>
                        <div class="current-image-preview">
                            <img src="/assets/uploads/<?= htmlspecialchars($berita['gambar']) ?>" alt="Gambar Saat Ini">
                            <div class="img-label">Gambar Saat Ini</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="konten">Isi Berita Lengkap</label>
                    <textarea name="konten" id="konten" class="form-control"><?= htmlspecialchars($berita['konten']) ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>

            </form>
        </div>
    </main>

    <script>
        tinymce.init({
            selector: '#konten',
            height: 600,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat | code',
            image_title: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            file_picker_callback: function (cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');

                input.onchange = function () {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function () {
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);

                        cb(blobInfo.blobUri(), { title: file.name });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            },
            content_style: 'body { font-family: Inter, sans-serif; font-size: 15px; color: #1c1917; line-height: 1.6; }'
        });
    </script>
</body>
</html>