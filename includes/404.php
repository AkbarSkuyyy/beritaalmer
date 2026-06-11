<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Almer | Portal Berita Terpercaya</title>
    
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
            <a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" title="Twitter"><i class="fa-brands fa-twitter"></i></a>
            <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
    </div>
</div>

<header class="main-header">
    <div class="container header-inner">
        <div class="site-branding">
            <a href="/" class="logo-text">BERITA<span class="logo-accent">ALMER</span></a>
            <span class="site-tagline">Akurat, Cepat, Terpercaya</span>
        </div>
        <div class="header-search">
            <form action="#" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Cari berita hari ini..." autocomplete="off">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</header>

<nav class="main-navigation">
    <div class="container">
        <ul class="nav-menu">
            <li><a href="/" class="active">Beranda</a></li>
            <li><a href="#">Nasional</a></li>
            <li><a href="#">Teknologi</a></li>
            <li><a href="#">Olahraga</a></li>
            <li><a href="#">Otomotif</a></li>
        </ul>
    </div>
</nav>

<main class="container main-content">