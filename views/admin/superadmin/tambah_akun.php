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

// 3. Ambil semua kategori untuk dropdown opsi select
try {
    $stmt_cat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal mengambil data kategori: " . $e->getMessage();
}

// 4. Proses data ketika form dikirim (Submit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul       = trim($_POST['judul']);
    $kategori_id = $_POST['kategori_id'];
    // Ambil konten HTML murni dari editor
    $konten      = trim($_POST['konten']);
    $penulis_id  = $_SESSION['admin_id'];

    if (!empty($judul) && !empty($kategori_id) && !empty($konten)) {
        // Membuat slug otomatis dari judul
        $slug = createSlug($judul);
        
        // PENCEGAH DUPLIKASI SLUG
        $stmt_cek = $pdo->prepare("SELECT id FROM berita WHERE slug = ?");
        $stmt_cek->execute([$slug]);
        if ($stmt_cek->rowCount() > 0) {
            $slug = $slug . '-' . rand(100, 999);
        }
        
        $nama_gambar = null;
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = __DIR__ . '/../../assets/uploads/';
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $allowed_ext    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($file_extension), $allowed_ext)) {
                $nama_gambar = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $nama_gambar;

                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    $error = "Gagal mengunggah gambar utama ke sistem server.";
                }
            } else {
                $error = "Format gambar utama salah! Hanya diperbolehkan format JPG, JPEG, PNG, dan WEBP.";
            }
        } else {
            $error = "Gambar sampul berita wajib diunggah!";
        }

        if (empty($error)) {
            try {
                $stmt_insert = $pdo->prepare("INSERT INTO berita (judul, slug, konten, gambar, kategori_id, penulis_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->execute([$judul, $slug, $konten, $nama_gambar, $kategori_id, $penulis_id]);
                
                $success = "Berita baru berhasil diterbitkan!";
                $judul = $konten = ''; 
            } catch (PDOException $e) {
                $error = "Gagal menyimpan berita ke database: " . $e->getMessage();
            }
        }
    } else {
        $error = "Harap isi semua kolom wajib (Tajuk, Kategori, dan Konten)!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Berita Baru | Berita Almer</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f4f5; color: #1c1917; display: flex; min-height: 100vh; }
        
        /* CSS SIDEBAR YANG SEMPAT HILANG (PENTING) */
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

        .form-card { background: #ffffff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #3f3f46; margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e4e4e7; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: #ff6b00; }
        textarea.form-control { resize: vertical; min-height: 200px; }
        
        /* Box untuk Preview Gambar */
        .image-preview-box {
            width: 100%;
            max-width: 400px;
            height: 220px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8fafc;
            margin-top: 10px;
        }
        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none; 
        }
        .image-preview-text {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
        }

        .alert { padding: 15px; border-radius: 6px; font-size: 14px; margin-bottom: 25px; font-weight: 500; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }

        .btn-submit { background: #ff6b00; color: #ffffff; border: none; padding: 14px 28px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #e05e00; }
        
        .tox-tinymce { border-radius: 8px !important; border-color: #e4e4e7 !important; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header">
            <h2>Buat Artikel Berita Baru</h2>
            <div style="font-size: 14px; color: #71717a; background: #f4f4f5; padding: 8px 15px; border-radius: 20px;">
                <i class="fa-solid fa-bolt" style="color: #ff6b00; margin-right: 5px;"></i> Rich Text Editor Active
            </div>
        </header>

        <div class="form-card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="judul">Tajuk / Judul Berita</label>
                    <input type="text" name="judul" id="judul" class="form-control" placeholder="Masukkan judul berita yang menarik..." value="<?= htmlspecialchars($judul ?? '') ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="kategori_id">Kategori Berita</label>
                    <select name="kategori_id" id="kategori_id" class="form-control" required>
                        <option value="">-- Pilih Kategori Berita --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gambar">Gambar Utama / Sampul Berita</label>
                    <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*" required onchange="previewImage(event)">
                    <small style="color:#71717a; display:block; margin-top:5px;">Ini adalah gambar yang akan muncul di halaman depan (Thumbnail).</small>
                    
                    <div class="image-preview-box">
                        <span class="image-preview-text" id="previewText"><i class="fa-regular fa-image" style="font-size: 30px; display:block; text-align:center; margin-bottom:5px;"></i> Preview Gambar</span>
                        <img id="imgPreview" src="#" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <label for="konten">Isi Berita Lengkap</label>
                    <textarea name="konten" id="konten" class="form-control" placeholder="Tulis konten berita lengkap Anda di sini..."><?= htmlspecialchars($konten ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Terbitkan Berita Sekarang
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

        function previewImage(event) {
            const input = event.target;
            const previewImg = document.getElementById('imgPreview');
            const previewText = document.getElementById('previewText');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    previewText.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewImg.style.display = 'none';
                previewText.style.display = 'block';
            }
        }
    </script>

    <?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil Diterbitkan!',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonColor: '#ff6b00',
                confirmButtonText: '<i class="fa-solid fa-list"></i> Lihat Daftar Berita',
                allowOutsideClick: false 
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/berita';
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>