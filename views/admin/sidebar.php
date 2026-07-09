<?php
// Mendeteksi URL saat ini agar menu yang aktif otomatis menyala
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-logo">
        <a href="/admin" style="color:white; text-decoration:none;">BERITA<span>ALMER</span></a>
    </div>
    <ul class="admin-menu">
        <div class="menu-divider">Core</div>
        <li><a href="/admin" class="<?= ($uri == '/admin' || $uri == '/admin/dashboard') ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
        <li><a href="/admin/grafik" class="<?= ($uri == '/admin/grafik') ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Grafik Analitik</a></li>
        <li><a href="/admin/perkembangan" class="<?= ($uri == '/admin/perkembangan') ? 'active' : '' ?>"><i class="fa-solid fa-seedling"></i> Perkembangan</a></li>
        
        <div class="menu-divider">Konten</div>
        <li><a href="/admin/tulis" class="<?= ($uri == '/admin/tulis') ? 'active' : '' ?>"><i class="fa-solid fa-pen-to-square"></i> Tulis Berita</a></li>
        <li><a href="/admin/berita" class="<?= ($uri == '/admin/berita' || strpos($uri, '/admin/edit') === 0) ? 'active' : '' ?>"><i class="fa-solid fa-table-list"></i> Kelola Berita</a></li>
        <li><a href="/admin/kategori" class="<?= ($uri == '/admin/kategori') ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i> Kelola Kategori</a></li>
        
        <div class="menu-divider">Sistem</div>
        <li><a href="/admin/users" class="<?= ($uri == '/admin/users' || $uri == '/admin/user') ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Manajemen Users</a></li>
        <li><a href="/admin/logs" class="<?= ($uri == '/admin/logs') ? 'active' : '' ?>"><i class="fa-solid fa-shield-halved"></i> Audit Logs</a></li>
        <li><a href="/admin/setting" class="<?= ($uri == '/admin/setting') ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> Pengaturan / Setting</a></li>
        <li><a href="/" target="_blank"><i class="fa-solid fa-globe"></i> Lihat Website <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a></li>
    </ul>
    
    <div class="admin-logout">
        <a href="#" id="tombolLogout"><i class="fa-solid fa-right-from-bracket"></i> Logout Sistem</a>
    </div>
</aside>

<style>
/* ==============================================================
   CSS RESPONSIF GLOBAL UNTUK SELURUH HALAMAN ADMIN
============================================================== */

/* Modifikasi tampilan tombol SweetAlert agar selaras dengan tema */
div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
    background-color: #ff6b00 !important;
    font-family: 'Outfit', sans-serif;
    border-radius: 8px;
}
div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
    background-color: #18181b !important;
    font-family: 'Outfit', sans-serif;
    border-radius: 8px;
}
div:where(.swal2-container) h2:where(.swal2-title) {
    font-family: 'Outfit', sans-serif;
}
div:where(.swal2-container) div:where(.swal2-html-container) {
    font-family: 'Inter', sans-serif;
    font-size: 15px;
}

.mobile-menu-btn {
    display: none;
    position: fixed;
    bottom: 25px;
    right: 25px;
    background: #ff6b00;
    color: #fff;
    border: none;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(255,107,0,0.4);
    z-index: 1001;
    cursor: pointer;
    transition: transform 0.3s;
}
.mobile-menu-btn:active { transform: scale(0.9); }

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(2px);
    z-index: 99;
    opacity: 0;
    transition: opacity 0.3s ease;
}

@media (max-width: 992px) {
    .mobile-menu-btn { display: flex; align-items: center; justify-content: center; }
    
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
    }
    
    .admin-sidebar.show { transform: translateX(0); }
    .sidebar-overlay.show { display: block; opacity: 1; }
    
    .admin-main {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 20px !important;
    }
    
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .stats-grid, .dashboard-grid, .kategori-grid, .setting-grid, .charts-grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 20px !important;
    }
    
    .search-form { width: 100% !important; }
    .table-controls { flex-direction: column; gap: 15px; align-items: stretch; }
    
    .content-card, .admin-panel {
        width: 100%;
        overflow-x: auto;
    }
    .admin-table { min-width: 600px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Logika untuk Menu Mobile
    const btn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if(btn && sidebar && overlay) {
        btn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            if (sidebar.classList.contains('show')) {
                overlay.classList.add('show');
                btn.querySelector('i').classList.replace('fa-bars', 'fa-xmark');
            } else {
                overlay.classList.remove('show');
                btn.querySelector('i').classList.replace('fa-xmark', 'fa-bars');
            }
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            btn.querySelector('i').classList.replace('fa-xmark', 'fa-bars');
        });
    }

    // 2. Logika ALERT Konfirmasi Logout (SweetAlert2)
    const btnLogout = document.getElementById('tombolLogout');
    if(btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link langsung terbuka
            
            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: "Sesi admin Anda akan diakhiri.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b00', /* Warna Oren Khas Berita Almer */
                cancelButtonColor: '#18181b',  /* Warna Hitam Gelap */
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                reverseButtons: true /* Menukar posisi tombol agar "Batal" di kiri */
            }).then((result) => {
                if (result.isConfirmed) {
                    // Lanjutkan ke halaman logout jika ditekan Ya
                    window.location.href = '/logout';
                }
            });
        });
    }
});
</script>