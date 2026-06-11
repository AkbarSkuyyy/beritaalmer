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

$error = '';
$success = '';

// 4. Ambil semua kategori untuk opsi pilihan dropdown
try {
    $stmt_cat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal mengambil data kategori: " . $e->getMessage();
}

// 5. FITUR SUPER ADMIN: Ambil daftar semua user/penulis untuk opsi publish
try {
    $stmt_users = $pdo->query("SELECT id, username, role FROM users ORDER BY FIELD(role, 'superadmin', 'admin', 'user'), username ASC");
    $daftar_penulis = $stmt_users->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal mengambil data pengguna sistem: " . $e->getMessage();
}

// 6. Proses data ketika form dikirim (Submit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul       = trim($_POST['judul']);
    $kategori_id = $_POST['kategori_id'];
    $konten      = trim($_POST['konten']);
    $penulis_id  = $_POST['penulis_id']; // Mengambil ID penulis yang dipilih secara dinamis

    if (!empty($judul) && !empty($kategori_id) && !empty($konten) && !empty($penulis_id)) {
        // Membuat slug otomatis dari judul
        $slug = createSlug($judul);
        
        // PENCEGAH DUPLIKASI SLUG
        $stmt_cek = $pdo->prepare("SELECT id FROM berita WHERE slug = ?");
        $stmt_cek->execute([$slug]);
        if ($stmt_cek->rowCount() > 0) {
            $slug = $slug . '-' . rand(100, 999);
        }
        
        $nama_gambar = null;
        
        // Pemrosesan File Gambar Sampul
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
                $error = "Format gambar utama salah! Gunakan format JPG, JPEG, PNG, atau WEBP.";
            }
        } else {
            $error = "Gambar sampul utama berita wajib diunggah!";
        }

        // Jika tidak ada error, simpan ke database
        if (empty($error)) {
            try {
                $stmt_insert = $pdo->prepare("INSERT INTO berita (judul, slug, konten, gambar, kategori_id, penulis_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->execute([$judul, $slug, $konten, $nama_gambar, $kategori_id, $penulis_id]);
                
                $success = "Berita berhasil diterbitkan atas nama pengguna yang dipilih!";
                $judul = $konten = ''; // Bersihkan isi form
            } catch (PDOException $e) {
                $error = "Gagal menyimpan berita ke database: " . $e->getMessage();
            }
        }
    } else {
        $error = "Harap isi semua bidang isian formulir tanpa terkecuali!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Tulis Berita | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        .form-card { background: #ffffff; padding: 35px; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; max-width: 900px; margin: 0 auto; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: all 0.3s; color: #0f172a; }
        .form-control:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        
        /* Box Preview Sampul */
        .image-preview-box { width: 100%; max-width: 350px; height: 180px; border: 2px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #f8fafc; margin-top: 10px; }
        .image-preview-box img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .image-preview-text { color: #94a3b8; font-size: 13px; font-weight: 500; }

        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 8px; }

        .btn-submit { background: #7c3aed; color: #ffffff; border: none; padding: 14px 28px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.25s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(124,58,237,0.2); }
        .btn-submit:hover { background: #6d28d9; transform: translateY(-1px); }
        
        .tox-tinymce { border-radius: 8px !important; border-color: #e2e8f0 !important; }

        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Penerbitan Berita Multi-Penulis</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Tulis artikel berita baru dan distribusikan kepemilikannya ke staff manapun.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-feather-pointed"></i> MASTER COMPOSER
            </div>
        </header>

        <div class="form-card">
            <?php if (!empty($error)): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="judul">Tajuk / Judul Berita Utama</label>
                    <input type="text" name="judul" id="judul" class="form-control" placeholder="Ketikkan judul berita yang tajam dan menarik..." value="<?= htmlspecialchars($judul ?? '') ?>" required autocomplete="off">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="kategori_id">Kategori Berita</label>
                        <select name="kategori_id" id="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="penulis_id" style="color: #7c3aed;">Tetapkan Atas Nama Penulis (Author)</label>
                        <select name="penulis_id" id="penulis_id" class="form-control" required style="border-color: #c084fc; font-weight: 600; background-color: #faf5ff;">
                            <option value="">-- Pilih Akun Penerbit --</option>
                            <?php foreach ($daftar_penulis as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $_SESSION['admin_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['username']) ?> [<?= strtoupper($p['role']) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="gambar">File Gambar Sampul Utama (Thumbnail Frontpage)</label>
                    <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*" required onchange="previewSampul(event)">
                    
                    <div class="image-preview-box">
                        <span class="image-preview-text" id="textPreview"><i class="fa-regular fa-image" style="font-size: 24px; display:block; text-align:center; margin-bottom:5px;"></i> Resolusi Gambar</span>
                        <img id="imgPreview" src="#" alt="Pratinjau">
                    </div>
                </div>

                <div class="form-group">
                    <label for="konten">Lembar Isi Konten Berita</label>
                    <textarea name="konten" id="konten" class="form-control" placeholder="Susun narasi berita lengkap Anda di sini..."><?= htmlspecialchars($konten ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Publikasikan Artikel Lintas Jaringan
                </button>

            </form>
        </div>
    </main>

    <script>
        tinymce.init({
            selector: '#konten',
            height: 550,
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
            content_style: 'body { font-family: Inter, sans-serif; font-size: 15px; color: #0f172a; line-height: 1.6; }'
        });

        // Alur Logika Javascript Preview Unggahan Gambar
        function previewSampul(event) {
            const input = event.target;
            const previewImg = document.getElementById('imgPreview');
            const previewText = document.getElementById('textPreview');

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
                title: 'Berita Berhasil Terbit!',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: '<i class="fa-solid fa-list-check"></i> Monitor Semua Berita',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Diarahkan langsung ke halaman manajemen list berita
                    window.location.href = '/admin/berita';
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>