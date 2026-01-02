<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($monster['name'] ?? 'Monster Card'); ?> - Print</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Spectral:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Card-specific CSS -->
    <?php if (isset($extraStyles) && is_array($extraStyles)): ?>
        <?php foreach ($extraStyles as $styleSheet): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($styleSheet); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Print-specific styles to ensure clean PDF output */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        /* Ensure cards don't split across pages */
        .boss-card-front,
        .boss-card-back,
        .small-statblock,
        .card-back {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        /* Keep front and back together on same page */
        .boss-card-display-wrapper,
        .card-display-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10mm;
            page-break-inside: avoid !important;
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate card print partial
    if ($isBoss ?? false) {
        require_once __DIR__ . '/../monster/boss-card-print.php';
    } else {
        require_once __DIR__ . '/../monster/small-statblock-print.php';
    }
    ?>
</body>
</html>
