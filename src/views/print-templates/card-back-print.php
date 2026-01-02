<?php
/**
 * Back Card Print Template
 * Shows the monster portrait/image on back of card
 */

if (empty($monster)) {
    echo '<div style="width: 63mm; height: 88mm; background: #f0f0f0;"></div>';
    return;
}

?>

<div style="width: 63mm; height: 88mm; background: white; display: flex; align-items: center; justify-content: center;">
    
    <?php 
    // Check if monster has a portrait image
    if (!empty($monster['image_portrait'])):
        // Convert to absolute file path for mPDF
        $imagePath = __DIR__ . '/../../../public/uploads/monsters/' . $monster['image_portrait'];
        
        // Only show if file exists
        if (file_exists($imagePath)):
    ?>
        <!-- Display portrait image, scaled to fill card -->
        <img src="<?php echo $imagePath; ?>" 
             style="width: 100%; height: 100%; object-fit: cover;" />
    
    <?php else: ?>
        <!-- Image file not found - show placeholder -->
        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    display: flex; align-items: center; justify-content: center; 
                    color: white; font-size: 3rem; font-family: Arial;">
            <?php echo htmlspecialchars(substr($monster['name'], 0, 1)); ?>
        </div>
    <?php endif; ?>
    
    <?php else: ?>
        <!-- No image - show monster initial -->
        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    display: flex; align-items: center; justify-content: center; 
                    color: white; font-size: 3rem; font-family: Arial;">
            <?php echo htmlspecialchars(substr($monster['name'], 0, 1)); ?>
        </div>
    
    <?php endif; ?>
    
</div>
