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
// FITUR AJAX LENGKAP (Berjalan di latar belakang tanpa refresh HTML bawah)
// ------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // A. AJAX: Simpan Urutan Drag & Drop
    if ($_POST['action'] == 'update_urutan_ajax' && isset($_POST['urutan'])) {
        try {
            $pdo->beginTransaction();
            $stmt_update = $pdo->prepare("UPDATE kategori SET urutan = ? WHERE id = ?");
            foreach ($_POST['urutan'] as $index => $id_kategori) {
                $stmt_update->execute([$index, (int)$id_kategori]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success']);
            exit; 
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    // B. AJAX: Edit Nama Kategori Cepat (Inline Edit)
    if ($_POST['action'] == 'edit_kategori_ajax' && isset($_POST['id']) && isset($_POST['nama_baru'])) {
        $id_edit = (int)$_POST['id'];
        $nama_baru = trim($_POST['nama_baru']);
        $slug_baru = createSlug($nama_baru); // Fungsi bawaan dari config.php

        if (!empty($nama_baru)) {
            try {
                // Cek apakah nama/slug baru bentrok dengan kategori lain
                $cek = $pdo->prepare("SELECT id FROM kategori WHERE slug = ? AND id != ?");
                $cek->execute([$slug_baru, $id_edit]);
                
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Nama kategori sudah digunakan!']);
                    exit;
                }

                $stmt_edit = $pdo->prepare("UPDATE kategori SET nama_kategori = ?, slug = ? WHERE id = ?");
                $stmt_edit->execute([$nama_baru, $slug_baru, $id_edit]);
                echo json_encode(['status' => 'success', 'slug' => $slug_baru]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }
    }
}
// ------------------------------------------------------------------------

$error = '';
$success = '';

// 4. POST: Tambah Kategori Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    
    if (!empty($nama_kategori)) {
        $slug = createSlug($nama_kategori);
        try {
            $cek = $pdo->prepare("SELECT id FROM kategori WHERE slug = ?");
            $cek->execute([$slug]);
            
            if ($cek->rowCount() > 0) {
                $error = "Kategori dengan nama tersebut sudah terdaftar!";
            } else {
                // Beri urutan paling akhir otomatis
                $stmt_max = $pdo->query("SELECT MAX(urutan) FROM kategori");
                $urutan_baru = (int)$stmt_max->fetchColumn() + 1;

                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, slug, urutan) VALUES (?, ?, ?)");
                $stmt->execute([$nama_kategori, $slug, $urutan_baru]);
                $success = "Kategori baru berhasil disuntikkan ke dalam sistem!";
            }
        } catch (PDOException $e) {
            $error = "Gagal menambah kategori: " . $e->getMessage();
        }
    } else {
        $error = "Nama kategori tidak boleh kosong!";
    }
}

// 5. GET: Hapus Kategori Permanen
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int)$_GET['hapus_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id_hapus]);
        $success = "Kategori berhasil dimusnahkan!";
    } catch (PDOException $e) {
        $error = "Penolakan Sistem: Kategori ini masih memiliki artikel berita yang tertaut padanya.";
    }
}

// 6. Tarik Data Kategori Lengkap Dengan Total Beritanya
try {
    $stmt_kategori = $pdo->query("
        SELECT k.id, k.nama_kategori, k.slug, k.urutan, COUNT(b.id) as total_berita
        FROM kategori k
        LEFT JOIN berita b ON k.id = b.kategori_id
        GROUP BY k.id
        ORDER BY k.urutan ASC, k.id DESC
    ");
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
    <title>Manajemen Hierarki Kategori | Super Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; }
        
        .super-main-wrapper { margin-left: 280px; width: calc(100% - 280px); padding: 40px; transition: 0.3s; }
        
        .master-banner { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; border-bottom: 4px solid #7c3aed; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .master-banner h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .master-control-badge { background: rgba(124,58,237,0.2); border: 1px solid #a855f7; color: #c084fc; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        .kategori-grid { display: grid; grid-template-columns: 360px 1fr; gap: 30px; align-items: start; }
        
        .panel-card { background: #ffffff; border-radius: 14px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }

        /* Form Styling */
        .input-block { margin-bottom: 20px; }
        .input-block label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .field-box { width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.3s; color: #0f172a; }
        .field-box:focus { border-color: #7c3aed; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        .master-btn { width: 100%; background: #0f172a; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.25s; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14.5px; }
        .master-btn:hover { background: #7c3aed; box-shadow: 0 4px 12px rgba(124,58,237,0.3); }

        /* Tabel Data / Sortable List */
        .master-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .master-table th { padding: 15px 15px; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background-color: #f8fafc; font-family: 'Outfit', sans-serif; font-size: 13px; text-transform: uppercase; }
        .master-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background-color: #ffffff; }
        
        .drag-handle { color: #cbd5e1; cursor: grab; text-align: center; transition: 0.2s; font-size: 18px; }
        .drag-handle:hover { color: #7c3aed; }
        
        /* State saat diseret */
        .sortable-ghost { opacity: 0.4; background-color: #f5f3ff !important; border: 1px dashed #7c3aed; }
        .sortable-drag { box-shadow: 0 10px 20px rgba(0,0,0,0.1); cursor: grabbing !important; }
        
        .badge-slug { background: #f1f5f9; color: #64748b; font-family: monospace; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;}
        .badge-count { background: #f0fdf4; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 5px;}
        
        .action-flex { display: flex; gap: 6px; justify-content: center; }
        .btn-act { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; text-decoration: none; font-size: 14px; transition: 0.2s; cursor: pointer; border: none; }
        .btn-act.edit { background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; }
        .btn-act.edit:hover { background: #0ea5e9; color: #fff; }
        .btn-act.delete { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; }
        .btn-act.delete:hover { background: #ef4444; color: #fff; }

        .toast-msg { padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .toast-err { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
        .toast-succ { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Notifikasi Sukses AJAX Kanan Atas */
        .toast-notify { position: fixed; top: 25px; right: 25px; background: #10b981; color: white; padding: 14px 22px; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 10px 20px rgba(16,185,129,0.3); transform: translateX(150%); transition: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); z-index: 9999; display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif;}
        .toast-notify.show { transform: translateX(0); }

        @media (max-width: 1200px) { .kategori-grid { grid-template-columns: 1fr; } }
        @media (max-width: 992px) {
            .super-main-wrapper { margin-left: 0; width: 100%; padding: 20px; }
            .master-banner { flex-direction: column; align-items: flex-start; gap: 15px; }
            .table-responsive { overflow-x: auto; }
            .master-table { min-width: 600px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/sidebar_super.php'; ?>

    <div id="toastSave" class="toast-notify"><i class="fa-solid fa-cloud-arrow-up"></i> Urutan Tersinkronisasi!</div>
    <div id="toastEdit" class="toast-notify" style="background: #7c3aed; box-shadow: 0 10px 20px rgba(124,58,237,0.3);"><i class="fa-solid fa-check-double"></i> Kategori Diperbarui!</div>

    <main class="super-main-wrapper">
        
        <header class="master-banner">
            <div>
                <h2>Hierarki & Navigasi Kategori</h2>
                <p style="font-size: 14px; color: #94a3b8; margin-top: 4px;">Kelola tata letak menu portal dengan teknologi Drag & Drop secara real-time.</p>
            </div>
            <div class="master-control-badge">
                <i class="fa-solid fa-layer-group"></i> MENU BUILDER
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="toast-msg toast-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="toast-msg toast-succ"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="kategori-grid">
            
            <div class="panel-card" style="position: sticky; top: 30px;">
                <div class="panel-title">
                    <span><i class="fa-solid fa-square-plus" style="color:#7c3aed; margin-right:5px;"></i> Injeksi Kategori Baru</span>
                </div>
                <form action="" method="POST">
                    <div class="input-block">
                        <label>Nama Kategori Berita</label>
                        <input type="text" name="nama_kategori" class="field-box" placeholder="Cth: Kecerdasan Buatan..." required autocomplete="off">
                        <small style="color:#94a3b8; display:block; margin-top:8px; line-height: 1.5;">URL Parameter (Slug) akan dibangun dan dienkripsi secara otomatis oleh sistem.</small>
                    </div>
                    <button type="submit" name="tambah_kategori" class="master-btn">
                        <i class="fa-solid fa-plus"></i> Tambahkan ke Jaringan
                    </button>
                </form>
            </div>

            <div class="panel-card" style="padding: 0; overflow: hidden;">
                <div class="panel-title" style="padding: 25px 30px 15px; margin-bottom: 0;">
                    <span><i class="fa-solid fa-bars-staggered" style="color:#7c3aed; margin-right:5px;"></i> Arsitektur Menu Utama</span>
                    <span style="font-size: 12px; color: #64748b; font-weight: 500; background: #f1f5f9; padding: 6px 12px; border-radius: 20px;"><i class="fa-solid fa-up-down-left-right"></i> Geser Baris Untuk Mengatur</span>
                </div>
                
                <div class="table-responsive">
                    <table class="master-table">
                        <thead>
                            <tr>
                                <th style="width: 8%; text-align: center;">Tarik</th>
                                <th style="width: 30%;">Identitas Kategori</th>
                                <th style="width: 25%;">URL Slug</th>
                                <th style="width: 20%; text-align: center;">Total Berita</th>
                                <th style="width: 17%; text-align: center;">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-kategori">
                            <?php if (count($daftar_kategori) > 0): ?>
                                <?php foreach($daftar_kategori as $cat): ?>
                                <tr data-id="<?= $cat['id'] ?>">
                                    <td class="drag-handle" title="Tarik untuk memindahkan">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    </td>
                                    <td style="font-weight: 700; color: #0f172a;" id="name-<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                                    </td>
                                    <td>
                                        <span class="badge-slug" id="slug-<?= $cat['id'] ?>">/<?= htmlspecialchars($cat['slug']) ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-count"><i class="fa-solid fa-newspaper"></i> <?= number_format($cat['total_berita']) ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-flex">
                                            <button type="button" class="btn-act edit" title="Edit Nama Cepat" onclick="editKategori(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nama_kategori'])) ?>')"><i class="fa-solid fa-pen"></i></button>
                                            <a href="#" class="btn-act delete" title="Hapus Kategori" onclick="konfirmasiHapus(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nama_kategori'])) ?>', <?= $cat['total_berita'] ?>)"><i class="fa-solid fa-trash-can"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Arsitektur kategori masih kosong.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Inisialisasi Fitur Drag and Drop (SortableJS)
            const tableBody = document.getElementById('sortable-kategori');
            
            if (tableBody) {
                Sortable.create(tableBody, {
                    handle: '.drag-handle',
                    animation: 250,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: function (evt) {
                        const rows = tableBody.querySelectorAll('tr');
                        const urutanData = [];
                        rows.forEach(function(row) {
                            if(row.getAttribute('data-id')) urutanData.push(row.getAttribute('data-id'));
                        });

                        const formData = new FormData();
                        formData.append('action', 'update_urutan_ajax');
                        urutanData.forEach(id => formData.append('urutan[]', id));

                        fetch('', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                const toast = document.getElementById('toastSave');
                                toast.classList.add('show');
                                setTimeout(() => { toast.classList.remove('show'); }, 3000);
                            }
                        });
                    }
                });
            }
        });

        // 2. Fitur Inline Edit Cepat (SweetAlert2)
        function editKategori(id, currentName) {
            Swal.fire({
                title: 'Revisi Nama Kategori',
                input: 'text',
                inputValue: currentName,
                inputPlaceholder: 'Ketik nama kategori baru...',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Simpan Pembaruan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') { return 'Nama kategori tidak boleh kosong!' }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newName = result.value.trim();
                    if(newName !== currentName) {
                        const formData = new FormData();
                        formData.append('action', 'edit_kategori_ajax');
                        formData.append('id', id);
                        formData.append('nama_baru', newName);

                        fetch('', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                // Update tampilan HTML secara instan
                                document.getElementById('name-' + id).innerText = newName;
                                document.getElementById('slug-' + id).innerText = '/' + data.slug;
                                
                                const toast = document.getElementById('toastEdit');
                                toast.classList.add('show');
                                setTimeout(() => { toast.classList.remove('show'); }, 3000);
                            } else {
                                Swal.fire('Gagal Menyimpan', data.message, 'error');
                            }
                        });
                    }
                }
            });
        }

        // 3. Peringatan Keamanan Penghapusan
        function konfirmasiHapus(id, nama, total_berita) {
            if (total_berita > 0) {
                Swal.fire({
                    title: 'Operasi Ditolak!',
                    html: `Kategori <b>${nama}</b> tidak dapat dihapus karena masih menampung <b>${total_berita}</b> artikel.<br><br>Harap pindahkan atau hapus artikel-artikel tersebut terlebih dahulu.`,
                    icon: 'error',
                    confirmButtonColor: '#0f172a'
                });
                return;
            }

            Swal.fire({
                title: 'Musnahkan Kategori?',
                html: `Kategori <b>${nama}</b> akan dihilangkan dari sistem navigasi.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Ya, Musnahkan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?hapus_id=' + id;
                }
            });
        }
    </script>
</body>
</html>