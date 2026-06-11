<?php
// Mendeteksi URL aktif untuk menyalakan menu otomatis
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<button class="super-mobile-btn" id="superMobileBtn">
    <i class="fa-solid fa-crown"></i>
</button>

<div class="super-overlay" id="superOverlay"></div>

<aside class="super-sidebar" id="superSidebar">
    <div class="super-brand">
        <i class="fa-solid fa-shield-cat"></i> MASTER<span>CONTROL</span>
    </div>
    
    <div class="super-user-profile">
        <div class="avatar-circle"><i class="fa-solid fa-user-gear"></i></div>
        <div class="profile-info">
            <h4>Super Administrator</h4>
            <span class="badge-status-online"><span class="dot"></span> Otoritas Tertinggi</span>
        </div>
    </div>

    <ul class="super-menu-list">
        <div class="super-menu-divider">Utama & Analitik</div>
        <li><a href="/admin/superadmin/dashboard" class="<?= ($uri == '/admin/superadmin/dashboard') ? 'active' : '' ?>"><i class="fa-solid fa-chart-pie"></i> Ringkasan Dasbor</a></li>
        <li><a href="/admin/superadmin/grafik" class="<?= ($uri == '/admin/superadmin/grafik') ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Grafik & Tren Trafik</a></li>
        <li><a href="/admin/superadmin/perkembangan" class="<?= ($uri == '/admin/superadmin/perkembangan') ? 'active' : '' ?>"><i class="fa-solid fa-seedling"></i> Metrik Perkembangan</a></li>
        
        <div class="super-menu-divider">Manajemen Konten</div>
        <li><a href="/admin/superadmin/tulis" class="<?= ($uri == '/admin/superadmin/tulis') ? 'active' : '' ?>"><i class="fa-solid fa-pen-nib"></i> Tulis Berita Baru</a></li>
        <li><a href="/admin/superadmin/berita" class="<?= ($uri == '/admin/superadmin/berita') ? 'active' : '' ?>"><i class="fa-solid fa-newspaper"></i> Sensor & Kelola Berita</a></li>
        <li><a href="/admin/superadmin/kategori" class="<?= ($uri == '/admin/superadmin/kategori') ? 'active' : '' ?>"><i class="fa-solid fa-folder-tree"></i> Urutan Kategori (Drag)</a></li>
        
        <div class="super-menu-divider">Otoritas Jaringan</div>
        <li><a href="/admin/superadmin" class="<?= ($uri == '/admin/superadmin') ? 'active' : '' ?>"><i class="fa-solid fa-users-viewfinder"></i> Daftar Seluruh Akun</a></li>
        <li><a href="/admin/superadmin/tambah" class="<?= ($uri == '/admin/superadmin/tambah') ? 'active' : '' ?>"><i class="fa-solid fa-user-plus"></i> Buat Akun Baru</a></li>
        
        <div class="super-menu-divider">Konfigurasi Inti</div>
        <li><a href="/admin/superadmin/setting" class="<?= ($uri == '/admin/superadmin/setting') ? 'active' : '' ?>"><i class="fa-solid fa-sliders"></i> Pengaturan Portal</a></li>
        <li><a href="/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Monitor Web Publik</a></li>
    </ul>

    <div class="super-logout-zone">
        <a href="#" id="superLogoutBtn"><i class="fa-solid fa-power-off"></i> Tutup Sesi Otoritas</a>
    </div>
</aside>

<style>
/* ==============================================================
   CSS SIDEBAR SUPER ADMIN - MINIMALIS & PREMIUM
============================================================== */
.super-sidebar { 
    width: 280px; 
    background-color: #0f172a; 
    color: #f8fafc; 
    padding: 30px 20px; 
    display: flex; 
    flex-direction: column; 
    position: fixed; 
    top: 0;    /* KUNCI TITIK ATAS AGAR TIDAK TURUN */
    left: 0;   /* KUNCI TITIK KIRI AGAR TIDAK GESER */
    bottom: 0; /* PASTIKAN TINGGI PENUH SAMPAI BAWAH */
    height: 100vh; 
    z-index: 1000; 
    border-right: 1px solid #1e293b; 
    box-shadow: 10px 0 30px rgba(0,0,0,0.2); 
}
.super-brand { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #f8fafc; letter-spacing: 0.5px; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
.super-brand i { color: #a855f7; font-size: 24px; }
.super-brand span { color: #a855f7; }

/* Profil */
.super-user-profile { display: flex; align-items: center; gap: 12px; background: #1e293b; padding: 15px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #334155; }
.avatar-circle { width: 40px; height: 40px; background: #a855f7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
.profile-info h4 { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.badge-status-online { font-size: 11px; color: #34d399; display: flex; align-items: center; gap: 5px; font-weight: 500; }
.badge-status-online .dot { width: 6px; height: 6px; background: #34d399; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }

/* Daftar Menu */
.super-menu-list { list-style: none; flex-grow: 1; overflow-y: auto; padding-right: 5px; margin: 0; }
.super-menu-list::-webkit-scrollbar { width: 4px; }
.super-menu-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
.super-menu-divider { color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 22px 0 10px 10px; font-family: 'Outfit', sans-serif; }
.super-menu-list li { margin-bottom: 4px; padding: 0; }
.super-menu-list a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 14px; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all 0.25s ease; font-family: 'Inter', sans-serif; }
.super-menu-list a i { width: 20px; font-size: 16px; transition: transform 0.25s; }
.super-menu-list a:hover { color: #fff; background-color: #1e293b; }
.super-menu-list a:hover i { transform: translateX(3px); }
.super-menu-list a.active { background-color: #7c3aed; color: #ffffff; font-weight: 600; box-shadow: 0 4px 15px rgba(124,58,237,0.3); }
.super-menu-list a.active i { color: #fff; }

/* Keluar */
.super-logout-zone { margin-top: 20px; border-top: 1px solid #1e293b; padding-top: 15px; }
.super-logout-zone a { display: flex; align-items: center; gap: 12px; color: #f87171; text-decoration: none; padding: 12px 14px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; border-radius: 8px; transition: 0.2s; }
.super-logout-zone a:hover { background: #7f1d1d; color: #fca5a5; }

/* Tombol HP Melayang */
.super-mobile-btn { display: none; position: fixed; bottom: 25px; right: 25px; background: #7c3aed; color: #fff; border: none; width: 55px; height: 55px; border-radius: 50%; font-size: 22px; box-shadow: 0 4px 20px rgba(124,58,237,0.4); z-index: 2001; cursor: pointer; transition: 0.3s; }
.super-overlay { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(15,23,42,0.7); backdrop-filter: blur(3px); z-index: 998; opacity: 0; transition: 0.3s; }

@keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52,211,153,0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(52,211,153,0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52,211,153,0); } }

div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm { background-color: #7c3aed !important; border-radius: 6px; font-family: 'Inter', sans-serif; }
div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel { background-color: #1e293b !important; border-radius: 6px; font-family: 'Inter', sans-serif; }

/* REPO RESPONSIVE UNTUK LAYAR HP */
@media (max-width: 992px) {
    .super-mobile-btn { display: flex; align-items: center; justify-content: center; }
    .super-sidebar { transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .super-sidebar.open { transform: translateX(0); }
    .super-overlay.open { display: block; opacity: 1; }
    .super-main-wrapper { margin-left: 0 !important; width: 100% !important; padding: 20px !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigasi HP Toggle
    const mobileBtn = document.getElementById('superMobileBtn');
    const sidebar = document.getElementById('superSidebar');
    const overlay = document.getElementById('superOverlay');

    if (mobileBtn && sidebar && overlay) {
        mobileBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
            const icon = mobileBtn.querySelector('i');
            if(sidebar.classList.contains('open')) {
                icon.classList.replace('fa-crown', 'fa-xmark');
            } else {
                icon.classList.replace('fa-xmark', 'fa-crown');
            }
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            mobileBtn.querySelector('i').classList.replace('fa-xmark', 'fa-crown');
        });
    }

    // Peringatan Keamanan Logout
    const logoutBtn = document.getElementById('superLogoutBtn');
    if(logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Tutup Sesi Superadmin?',
                text: "Anda akan keluar dari kendali master jaringan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Ya, Log Out',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) { window.location.href = '/logout'; }
            });
        });
    }
});
</script>