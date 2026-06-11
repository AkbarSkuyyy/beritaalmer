<?php
http_response_code(404);

// Memanggil header dengan jalur absolut mundur 1 folder
require_once __DIR__ . '/../includes/header.php';
?>

<div style="text-align: center; padding: 100px 20px; font-family: 'Inter', sans-serif; min-height: 50vh;">
    <i class="fa-solid fa-triangle-exclamation" style="font-size: 80px; color: #ff6b00; margin-bottom: 20px;"></i>
    <h1 style="font-family: 'Outfit', sans-serif; font-size: 40px; color: #0f172a; margin-bottom: 15px;">404 - Halaman Tidak Ditemukan</h1>
    <p style="color: #64748b; font-size: 16px; margin-bottom: 30px;">Maaf, halaman atau berita yang Anda cari mungkin telah dihapus, dipindahkan, atau memang tidak pernah ada.</p>
    <a href="/" style="background-color: #0f172a; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-house"></i> Kembali ke Beranda
    </a>
</div>

<?php
// Memanggil footer dengan jalur absolut mundur 1 folder
require_once __DIR__ . '/../includes/footer.php';
?>