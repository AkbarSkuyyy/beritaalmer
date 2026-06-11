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

$error = '';
$success = '';

// 4. PROSES SIMPAN PENGATURAN (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_setting'])) {
    // Ambil data form
    $nama_situs     = trim($_POST['nama_situs']);
    $tagline        = trim($_POST['tagline']);
    $meta_deskripsi = trim($_POST['meta_deskripsi']);
    $meta_keyword   = trim($_POST['meta_keyword']);
    $email_publik   = trim($_POST['email_publik']);
    $telepon        = trim($_POST['telepon']);
    $alamat         = trim($_POST['alamat']);
    $fb_link        = trim($_POST['fb_link']);
    $ig_link        = trim($_POST['ig_link']);
    $x_link         = trim($_POST['x_link']);
    $yt_link        = trim($_POST['yt_link']);
    
    // Switch toggle (jika tidak dicentang, maka bernilai '0')
    $maintenance_mode  = isset($_POST['maintenance_mode']) ? '1' : '0';
    $daftar_penulis   = isset($_POST['daftar_penulis']) ? '1' : '0';
    $max_upload_size  = (int)$_POST['max_upload_size'];

    try {
        // Skema Fleksibel: Cek apakah data row pertama (ID 1) sudah ada
        $cek_data = $pdo->query("SELECT id FROM pengaturan LIMIT 1")->fetch();
        
        if ($cek_data) {
            // Jalankan Query Update jika row sudah tersedia
            $query_save = "
                UPDATE pengaturan SET 
                nama_situs = ?, tagline = ?, meta_deskripsi = ?, meta_keyword = ?,
                email_publik = ?, telepon = ?, alamat = ?, 
                fb_link = ?, ig_link = ?, x_link = ?, yt_link = ?,
                maintenance_mode = ?, daftar_penulis = ?, max_upload_size = ?
                WHERE id = ?
            ";
            $stmt_save = $pdo->prepare($query_save);
            $stmt_save->execute([
                $nama_situs, $tagline, $meta_deskripsi, $meta_keyword,
                $email_publik, $telepon, $alamat,
                $fb_link, $ig_link, $x_link, $yt_link,
                $maintenance_mode, $daftar_penulis, $max_upload_size, $cek_data['id']
            ]);
        } else {
            // Jalankan Query Insert Baru jika database masih kosong
            $query_save = "
                INSERT INTO pengaturan 
                (nama_situs, tagline, meta_deskripsi, meta_keyword, email_publik, telepon, alamat, fb_link, ig_link, x_link, yt_link, maintenance_mode, daftar_penulis, max_upload_size) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt_save = $pdo->prepare($query_save);
            $stmt_save->execute([
                $nama_situs, $tagline, $meta_deskripsi, $meta_keyword,
                $email_publik, $telepon, $alamat,
                $fb_link, $ig_link, $x_link, $yt_link,
                $maintenance_mode, $daftar_penulis, $max_upload_size
            ]);
        }
        
        $success = "Konfigurasi core engine sistem berhasil disinkronisasi!";
    } catch (PDOException $e) {
        $error = "Gagal memperbarui konfigurasi database: " . $e->getMessage();
    }
}

// 5. TARIK DATA PENGATURAN SAAT INI UNTUK DI-BIND KE INPUT FORM
try {
    $current_setting = $pdo->query("SELECT * FROM pengaturan LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika tabel belum dibuat, kita buat penanganan fallback array kosong agar tidak error 500
    $current_setting = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurasi Inti Portal | Super Admin</title>
    
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

        /* Desain Tab Navigasi Premium */
        .tabs-container { background: #ffffff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; max-width: 950px; margin: 0 auto; }
        .tabs-nav { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px 15px 0; gap: 5px; }
        .tab-btn { background: transparent; border: none; padding: 14px 20px; font-family: 'Outfit', sans-serif; font-size: 14.5px; font-weight: 700; color: #64748b; cursor: pointer; transition: 0.2s; border-radius: 8px 8px 0 0; border: 1px solid transparent; border-bottom: none; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #0f172a; background: #f1f5f9; }
        .tab-btn.active { color: #7c3aed; background: #ffffff; border-color: #e2e8f0; position: relative; margin-bottom: -1px; }

        /* Isi Panel Tab */
        .tab-panel { padding: 35px; display: none; }
        .tab-panel.active { display: block; }
        
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .input-block { margin-bottom: 25px; }
        .input-block label { display: block; font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .field-box { width: 100%; padding: 13px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; color: #0f172a; }
        .field-box:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        textarea.field-box { resize: vertical; min-height: 100px; line-height: 1.5; }

        /* Switch Sakelar Toggle Modern */
        .toggle-flex { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; margin-bottom: 20px; }
        .toggle-info h4 { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
        .toggle-info p { font-size: 12.5px; color: #64748b; }
        
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: #7c3aed; }
        input:focus + .slider { box-shadow: 0 0 1px #7c3aed; }
        input:checked + .slider:before { transform: translateX(24px); }

        /* Sektor Simpan */
        .footer-save-bar { padding: 20px 35px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; }
        .master-btn { background: #0f172a; color: #fff; border: none; padding: 14px 30px; border-radius: 8px; font-weight: 600; font-family: 'Inter', sans-serif; font-size: 14.5px; cursor: pointer; transition: 0.25s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(15,23,42,0.1); }
        .master-btn:hover { background: #7c3aed; box-shadow: 0 4px 15px rgba(124,58,237,0.3); }

        .toast-msg { padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; max-width: 950px; margin: 0 auto 25px; }
        .toast-err { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }

        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .tabs-nav { flex-direction: column; padding: 10px 10px 10px; gap: 2px; }
            .tab-btn { border-radius: 6px; border: 1px solid #e2e8f0; }
            .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Pengaturan Inti Portal</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Kelola arsitektur penamaan, optimasi SEO, media sosial, serta parameter keamanan website.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-sliders"></i> CORE TUNING
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="toast-msg toast-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="tabs-container">
            
            <nav class="tabs-nav">
                <button type="button" class="tab-btn active" onclick="bukaTab(event, 'identitas')"><i class="fa-solid fa-globe"></i> Identitas Situs</button>
                <button type="button" class="tab-btn" onclick="bukaTab(event, 'kontak')"><i class="fa-solid fa-share-nodes"></i> Kontak & Sosial Media</button>
                <button type="button" class="tab-btn" onclick="bukaTab(event, 'sistem')"><i class="fa-solid fa-microchip"></i> Keamanan & Sistem</button>
            </nav>

            <div id="identitas" class="tab-panel active">
                <div class="form-grid-2">
                    <div class="input-block">
                        <label>Nama Website / Portal Berita</label>
                        <input type="text" name="nama_situs" class="field-box" placeholder="Cth: Berita Almer" value="<?= htmlspecialchars($current_setting['nama_situs'] ?? 'Berita Almer') ?>" required autocomplete="off">
                    </div>
                    <div class="input-block">
                        <label>Slogan / Tagline Portal</label>
                        <input type="text" name="tagline" class="field-box" placeholder="Cth: Aktual, Tajam, Terpercaya..." value="<?= htmlspecialchars($current_setting['tagline'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>
                <div class="input-block">
                    <label>Deskripsi Utama Meta SEO (Meta Description)</label>
                    <textarea name="meta_deskripsi" class="field-box" placeholder="Tuliskan deskripsi singkat website untuk ringkasan di mesin pencari Google..."><?= htmlspecialchars($current_setting['meta_deskripsi'] ?? '') ?></textarea>
                </div>
                <div class="input-block">
                    <label>Kata Kunci Global SEO (Meta Keywords)</label>
                    <input type="text" name="meta_keyword" class="field-box" placeholder="Cth: berita terkini, almer portal, portal kalimantan (pisahkan dengan koma)" value="<?= htmlspecialchars($current_setting['meta_keyword'] ?? '') ?>" autocomplete="off">
                </div>
            </div>

            <div id="kontak" class="tab-panel">
                <div class="form-grid-2">
                    <div class="input-block">
                        <label>Email Publik Portal</label>
                        <input type="email" name="email_publik" class="field-box" placeholder="Cth: redaksi@beritaalmer.my.id" value="<?= htmlspecialchars($current_setting['email_publik'] ?? '') ?>" autocomplete="off">
                    </div>
                    <div class="input-block">
                        <label>Nomor Telepon Hotline / WhatsApp</label>
                        <input type="text" name="telepon" class="field-box" placeholder="Cth: 08123456789" value="<?= htmlspecialchars($current_setting['telepon'] ?? '') ?>" autocomplete="off">
                    </div>
                    <div class="input-block">
                        <label>Facebook Fanpage Link</label>
                        <input type="url" name="fb_link" class="field-box" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($current_setting['fb_link'] ?? '') ?>">
                    </div>
                    <div class="input-block">
                        <label>Instagram Official Link</label>
                        <input type="url" name="ig_link" class="field-box" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($current_setting['ig_link'] ?? '') ?>">
                    </div>
                    <div class="input-block">
                        <label>Twitter / X Channel Link</label>
                        <input type="url" name="x_link" class="field-box" placeholder="https://x.com/..." value="<?= htmlspecialchars($current_setting['x_link'] ?? '') ?>">
                    </div>
                    <div class="input-block">
                        <label>YouTube Video Channel Link</label>
                        <input type="url" name="yt_link" class="field-box" placeholder="https://youtube.com/..." value="<?= htmlspecialchars($current_setting['yt_link'] ?? '') ?>">
                    </div>
                </div>
                <div class="input-block">
                    <label>Alamat Fisik Kantor Redaksi Utama</label>
                    <textarea name="alamat" class="field-box" placeholder="Ketikkan alamat lengkap kantor pusat penyiaran berita..."><?= htmlspecialchars($current_setting['alamat'] ?? '') ?></textarea>
                </div>
            </div>

            <div id="sistem" class="tab-panel">
                <div class="toggle-flex">
                    <div class="toggle-info">
                        <h4>Modus Perbaikan (Maintenance Mode)</h4>
                        <p>Jika diaktifkan, halaman publik akan dikunci dan hanya menampilkan layar perbaikan.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= (isset($current_setting['maintenance_mode']) && $current_setting['maintenance_mode'] == '1') ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-flex">
                    <div class="toggle-info">
                        <h4>Pendaftaran Penulis Baru (Open Registration)</h4>
                        <p>Izinkan publik luar untuk mendaftar akun penulis secara mandiri dari luar portal.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="daftar_penulis" value="1" <?= (isset($current_setting['daftar_penulis']) && $current_setting['daftar_penulis'] == '1') ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="input-block" style="margin-top: 30px;">
                    <label>Batas Maksimal Ukuran Gambar Sampul (Dalam KiloBytes / KB)</label>
                    <select name="max_upload_size" class="field-box" style="font-weight: 600;">
                        <option value="512" <?= (isset($current_setting['max_upload_size']) && $current_setting['max_upload_size'] == 512) ? 'selected' : '' ?>>512 KB (Disarankan - Sangat Ringan)</option>
                        <option value="1024" <?= (isset($current_setting['max_upload_size']) && $current_setting['max_upload_size'] == 1024) ? 'selected' : '' ?>>1024 KB / 1 MB (Standar Kualitas Menengah)</option>
                        <option value="2048" <?= (isset($current_setting['max_upload_size']) && $current_setting['max_upload_size'] == 2048) ? 'selected' : '' ?>>2048 KB / 2 MB (Batas Atas Maksimal)</option>
                    </select>
                </div>
            </div>

            <div class="footer-save-bar">
                <button type="submit" name="simpan_setting" class="master-btn">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi Portal
                </button>
            </div>

        </form>
    </main>

    <script>
        function bukaTab(evt, namaTab) {
            // Sembunyikan semua isi tab panel
            const tabPanels = document.getElementsByClassName("tab-panel");
            for (let i = 0; i < tabPanels.length; i++) {
                tabPanels[i].classList.remove("active");
            }

            // Matikan status active di semua tombol navigasi tab
            const tabBtns = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabBtns.length; i++) {
                tabBtns[i].classList.remove("active");
            }

            // Tampilkan tab panel yang sedang diklik dan beri status active pada tombolnya
            document.getElementById(namaTab).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>

    <?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Konfigurasi Tersimpan!',
                text: '<?= htmlspecialchars($success) ?>',
                icon: 'success',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Selesai'
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>