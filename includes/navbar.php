<?php
// Mendapatkan URL saat ini untuk mendeteksi menu mana yang sedang aktif
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<nav class="almer-navbar">
    <div class="nav-container">
        <a href="/" class="nav-logo">
            BERITA<span>ALMER</span>
        </a>

        <button class="nav-toggle" id="navToggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li>
                <a href="/" class="<?= ($uri == '/' || $uri == '/index.php') ? 'active' : '' ?>">Beranda</a>
            </li>
            <li>
                <a href="/kategori/nasional" class="<?= (strpos($uri, '/kategori/nasional') !== false) ? 'active' : '' ?>">Nasional</a>
            </li>
            <li>
                <a href="/kategori/teknologi" class="<?= (strpos($uri, '/kategori/teknologi') !== false) ? 'active' : '' ?>">Teknologi</a>
            </li>
            <li>
                <a href="/kategori/olahraga" class="<?= (strpos($uri, '/kategori/olahraga') !== false) ? 'active' : '' ?>">Olahraga</a>
            </li>
            <li>
                <a href="/kategori/otomotif" class="<?= (strpos($uri, '/kategori/otomotif') !== false) ? 'active' : '' ?>">Otomotif</a>
            </li>
            
        </ul>
    </div>
</nav>

<script>
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if(navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            
            // Mengubah ikon garis tiga menjadi silang saat diklik
            const icon = navToggle.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        });
    }
</script>