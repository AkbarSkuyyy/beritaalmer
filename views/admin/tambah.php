<?php
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $slug = createSlug($judul) . '-' . time(); // Ditambah time() agar slug selalu unik
    $kategori = $_POST['kategori'];
    $ringkasan = $_POST['ringkasan'];
    $konten = $_POST['konten'];
    $penulis_id = $_SESSION['admin_id'];

    // Proses Upload Gambar
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_dir = 'assets/uploads/';
            
            // Buat folder jika belum ada
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $gambar = $new_file_name;
            }
        } else {
            $pesan = "<div class='alert alert-danger'>Format gambar harus JPG, PNG, atau WEBP!</div>";
        }
    }

    if (empty($pesan)) {
        // Simpan ke Database
        $sql = "INSERT INTO berita (slug, judul, kategori, ringkasan, konten, gambar, penulis_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$slug, $judul, $kategori, $ringkasan, $konten, $gambar, $penulis_id])) {
            $pesan = "<div class='alert alert-success'>Berita berhasil dipublikasikan!</div>";
        } else {
            $pesan = "<div class='alert alert-danger'>Gagal menyimpan data!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tambah Berita | Admin Berita Almer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="brand"><a href="<?= BASE_URL ?>admin">ADMIN PANEL</a></div>
            <ul class="admin-menu">
                <li><a href="<?= BASE_URL ?>admin/dashboard">Daftar Berita</a></li>
                <li><a href="<?= BASE_URL ?>admin/tambah-berita" class="active">Tambah Berita</a></li>
                <li><a href="<?= BASE_URL ?>" target="_blank">Lihat Website</a></li>
                <li><a href="<?= BASE_URL ?>logout" style="color: #ff6b00;">Logout</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <header class="admin-header"><h2>Tulis Berita Baru</h2></header>
            
            <div class="content-body">
                <?= $pesan ?>
                <div class="card">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Judul Berita</label>
                                <input type="text" name="judul" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="kategori" class="form-control" required>
                                    <option value="Nasional">Nasional</option>
                                    <option value="Teknologi">Teknologi</option>
                                    <option value="Olahraga">Olahraga</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Gambar Thumbnail</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" required>
                        </div>

                        <div class="form-group">
                            <label>Ringkasan (Tampil di Homepage)</label>
                            <textarea name="ringkasan" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Konten Lengkap</label>
                            <textarea name="konten" class="form-control" rows="10" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 15px 30px;">Publikasikan Berita</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>