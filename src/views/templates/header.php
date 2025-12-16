<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <!-- Viewport meta tag enables responsive design (adapts to screen width) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monster Maker</title>
    
    <!-- Bootstrap 5.3.8 CSS framework for responsive grid layout and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts preconnect: speeds up font loading by establishing early connection -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts: New Rocker (display font), Nunito, Raleway -->
    <link href="https://fonts.googleapis.com/css2?family=New+Rocker&family=Nunito:wght@200..1000&family=Raleway:ital,wght@1,100..900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6.5.1 for icon set (fa-icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS files (global styles) -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/monster-form.css"> <!-- Color-coded borders for monster form sections -->
    
    <!-- Dynamic extra styles: Controllers can add page-specific CSS by setting $extraStyles array -->
    <!-- Example: $extraStyles = ['/css/boss-card.css', '/css/lair-card.css']; -->
    <?php if (!empty($extraStyles) && is_array($extraStyles)): ?>
        <?php foreach ($extraStyles as $stylePath): ?>
            <!-- htmlspecialchars() prevents XSS by escaping special characters -->
            <link rel="stylesheet" href="<?php echo htmlspecialchars($stylePath); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Bootstrap Icons library (bi-icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body class="d-flex flex-column vh-100">
    <!-- Header: Dark theme with centered title using custom Google Font -->
    <header class=" text-center p-3 bg-dark text-white">
        <!-- new-rocker-regular: Custom font class from Google Fonts -->
        <!-- display-1: Bootstrap utility class for large heading -->
        <h1 class="new-rocker-regular display-1">Monster Maker </h1>
    </header>
