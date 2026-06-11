<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= isset($meta_title) ? $meta_title . ' | Berita Almer' : 'Berita Almer | Portal Berita Terpercaya' ?></title>
    
    <?php if(isset($meta_title)): ?>
        <meta property="og:title" content="<?= $meta_title ?>">
        <meta property="og:description" content="<?= $meta_desc ?>">
        <meta property="og:image" content="<?= $meta_image ?>">
        <meta property="og:url" content="<?= $meta_url ?>">
        <meta property="og:type" content="article">
        <meta name="twitter:card" content="summary_large_image">
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
            <a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" title="Twitter"><i class="fa-brands fa-twitter"></i></a>
            <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="container main-content">