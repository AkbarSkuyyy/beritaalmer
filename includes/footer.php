</main> 

<?php
// Memastikan koneksi dan variabel setting tersedia (sebagai pengaman tambahan)
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
    global $pdo;
}

if (!isset($setting_web)) {
    try {
        $stmt_set = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
        $setting_web = $stmt_set->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $setting_web = [];
    }
}

// Menarik 5 Kategori Teratas dari database untuk menu footer
try {
    $stmt_foot_kat = $pdo->query("SELECT nama_kategori, slug FROM kategori ORDER BY urutan ASC LIMIT 5");
    $footer_kategori = $stmt_foot_kat->fetchAll();
} catch(PDOException $e) {
    $footer_kategori = [];
}

// Memecah nama situs untuk memberi warna aksen pada kata kedua (seperti BERITA ALMER)
$foot_nama = $setting_web['nama_situs'] ?? 'Berita Almer';
$nama_parts = explode(' ', $foot_nama, 2);
$nama_part1 = strtoupper($nama_parts[0]);
$nama_part2 = isset($nama_parts[1]) ? strtoupper($nama_parts[1]) : '';
?>

<footer class="site-footer">
    <div class="container footer-inner">
        
        <div class="footer-col">
            <a href="/" class="footer-logo">
                <?= htmlspecialchars($nama_part1) ?><span class="logo-accent"><?= htmlspecialchars($nama_part2) ?></span>
            </a>
            
            <p class="footer-desc">
                <?= htmlspecialchars($setting_web['meta_deskripsi'] ?? 'Portal berita terdepan yang menyajikan informasi paling akurat, cepat, dan terpercaya dengan standar jurnalisme berdefinisi tinggi.') ?>
            </p>
            
            <div class="footer-social">
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

        <div class="footer-col">
            <h4 class="footer-heading">Redaksi</h4>
            <ul class="footer-links">
                <li><a href="#">Tentang Kami</a></li>
                <li><a href="#">Susunan Redaksi</a></li>
                <li><a href="#">Pedoman Media Siber</a></li>
                <li><a href="#">Disclaimer</a></li>
                <li><a href="#">Kebijakan Privasi</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-heading">Kategori</h4>
            <ul class="footer-links">
                <?php if (count($footer_kategori) > 0): ?>
                    <?php foreach ($footer_kategori as $fkat): ?>
                        <li><a href="/?kategori=<?= htmlspecialchars($fkat['slug']) ?>"><?= htmlspecialchars($fkat['nama_kategori']) ?></a></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><a href="#">Belum ada kategori</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-heading">Hubungi Kami</h4>
            <ul class="footer-contact">
                <li>
                    <i class="fa-solid fa-location-dot"></i> 
                    <?= htmlspecialchars($setting_web['alamat'] ?? 'Alamat kantor redaksi belum diatur.') ?>
                </li>
                <li>
                    <i class="fa-solid fa-envelope"></i> 
                    <?= htmlspecialchars($setting_web['email_publik'] ?? 'redaksi@domain.com') ?>
                </li>
                <li>
                    <i class="fa-solid fa-phone"></i> 
                    <?= htmlspecialchars($setting_web['telepon'] ?? '+62 000 0000 0000') ?>
                </li>
            </ul>
        </div>
        
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y'); ?> <?= htmlspecialchars($foot_nama) ?>. Seluruh Hak Cipta Dilindungi.</p>
        </div>
    </div>
</footer>

</body>
</html>