<?php
// Memanggil koneksi database jika belum ada
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
    global $pdo;
}

// Tarik data pengaturan dari database
try {
    $stmt_set = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $setting_web = $stmt_set->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $setting_web = []; // Fallback jika tabel belum ada / kosong
}

// 🚧 LOGIKA MAINTENANCE MODE 🚧
// Jika maintenance aktif (1) DAN yang mengakses BUKAN admin, kunci halaman!
if (isset($setting_web['maintenance_mode']) && $setting_web['maintenance_mode'] == '1') {
    if (!isset($_SESSION['admin_id'])) {
        die("
            <div style='text-align:center; padding:100px 20px; font-family:sans-serif; background:#0f172a; color:#fff; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
                <h1 style='font-size:45px; color:#ff6b00; margin-bottom:10px;'>🚧 UNDER MAINTENANCE 🚧</h1>
                <p style='color:#94a3b8; font-size:18px;'>Mohon maaf, sistem portal sedang dalam pemeliharaan rutin. Silakan kembali beberapa saat lagi.</p>
            </div>
        ");
    }
}

// Variabel default dari database (Jika sedang tidak membuka artikel spesifik)
$site_name = $setting_web['nama_situs'] ?? 'Berita Almer';
$site_tagline = $setting_web['tagline'] ?? 'Portal Berita Terpercaya';
$default_desc = $setting_web['meta_deskripsi'] ?? '';
$default_key = $setting_web['meta_keyword'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= isset($meta_title) ? htmlspecialchars($meta_title) . ' | ' . htmlspecialchars($site_name) : htmlspecialchars($site_name) . ' | ' . htmlspecialchars($site_tagline) ?></title>
    <meta name="description" content="<?= isset($meta_desc) ? htmlspecialchars($meta_desc) : htmlspecialchars($default_desc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($default_key) ?>">
    
    <?php if(isset($meta_title)): ?>
        <meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">
        <meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
        <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
        <meta property="og:image" itemprop="image" content="<?= htmlspecialchars($meta_image) ?>">
        <meta property="og:image:secure_url" content="<?= htmlspecialchars($meta_image) ?>">
        <meta property="og:url" content="<?= htmlspecialchars($meta_url) ?>">
        <meta property="og:type" content="article">
        
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= htmlspecialchars($meta_title) ?>">
        <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
        <meta name="twitter:image" content="<?= htmlspecialchars($meta_image) ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700;800;900&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="top-bar">
    <div class="container top-bar-inner">
        <div class="top-bar-left">
            <span class="date-now"><i class="fa-regular fa-calendar-days"></i> <?php echo date('l, d F Y'); ?></span>
        </div>
        <div class="top-bar-right">
            <?php if(!empty($setting_web['fb_link'])): ?>
                <a href="<?= htmlspecialchars($setting_web['fb_link']) ?>" target="_blank" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <?php endif; ?>
            
            <?php if(!empty($setting_web['x_link'])): ?>
                <a href="<?= htmlspecialchars($setting_web['x_link']) ?>" target="_blank" title="Twitter / X"><i class="fa-brands fa-twitter"></i></a>
            <?php endif; ?>
            
            <?php if(!empty($setting_web['ig_link'])): ?>
                <a href="<?= htmlspecialchars($setting_web['ig_link']) ?>" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <?php endif; ?>
            
            <?php if(!empty($setting_web['yt_link'])): ?>
                <a href="<?= htmlspecialchars($setting_web['yt_link']) ?>" target="_blank" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="container main-content">