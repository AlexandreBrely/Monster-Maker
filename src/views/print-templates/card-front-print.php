<?php
/**
 * Front Card Print Template - Boss Card Format
 * Uses boss-card.css styling
 */

if (empty($monster)) {
    echo '<div>No monster data</div>';
    return;
}

// Calculate modifier from ability score
function abilityModifier($score) {
    return floor(($score - 10) / 2);
}

function formatModifier($modifier) {
    return ($modifier >= 0 ? '+' : '') . $modifier;
}
?>

<div class="boss-card-front" style="width: 63mm; height: 88mm;">
    <div class="boss-card-inner">
        <div class="boss-content">
            <div class="boss-flow">
                
                <!-- Header -->
                <div class="boss-header">
                    <div class="boss-title-section">
                        <h1 class="boss-name"><?php echo htmlspecialchars($monster['name']); ?></h1>
                        <p class="boss-type">
                            <?php echo htmlspecialchars($monster['size']); ?> 
                            <?php echo htmlspecialchars($monster['type']); ?>, 
                            <?php echo htmlspecialchars($monster['alignment']); ?>
                        </p>
                    </div>
                </div>

                <!-- Stats Bar -->
                <div class="boss-stats-bar">
                    <div class="boss-stat-item">
                        <span class="stat-label">AC</span>
                        <span class="stat-value"><?php echo (int)$monster['ac']; ?></span>
                    </div>
                    <div class="boss-stat-item">
                        <span class="stat-label">HP</span>
                        <span class="stat-value"><?php echo (int)$monster['hp']; ?></span>
                    </div>
                    <div class="boss-stat-item">
                        <span class="stat-label">SPD</span>
                        <span class="stat-value"><?php echo htmlspecialchars($monster['speed'] ?? '30 ft.'); ?></span>
                    </div>
                </div>

                <!-- Ability Scores -->
                <div class="boss-abilities">
                    <?php 
                    $abilities = [
                        'STR' => $monster['strength'] ?? 10,
                        'DEX' => $monster['dexterity'] ?? 10,
                        'CON' => $monster['constitution'] ?? 10,
                        'INT' => $monster['intelligence'] ?? 10,
                        'WIS' => $monster['wisdom'] ?? 10,
                        'CHA' => $monster['charisma'] ?? 10
                    ];
                    foreach ($abilities as $name => $score): 
                        $modifier = abilityModifier($score);
                    ?>
                    <div class="ability-box">
                        <div class="ability-name"><?php echo $name; ?></div>
                        <div class="ability-score"><?php echo $score; ?></div>
                        <div class="ability-mod"><?php echo formatModifier($modifier); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Traits -->
                <?php if (!empty($monster['traits'])): 
                    $traits = is_array($monster['traits']) ? $monster['traits'] : json_decode($monster['traits'], true);
                    if ($traits):
                ?>
                <div class="boss-section">
                    <?php foreach ($traits as $trait): ?>
                    <div class="trait-item">
                        <strong><?php echo htmlspecialchars($trait['name']); ?>.</strong>
                        <span><?php echo htmlspecialchars($trait['description']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; endif; ?>

                <!-- Actions -->
                <?php if (!empty($monster['actions'])): 
                    $actions = is_array($monster['actions']) ? $monster['actions'] : json_decode($monster['actions'], true);
                    if ($actions):
                ?>
                <div class="boss-section">
                    <h3 class="section-title">Actions</h3>
                    <?php foreach ($actions as $action): ?>
                    <div class="action-item">
                        <strong><?php echo htmlspecialchars($action['name']); ?>.</strong>
                        <span><?php echo htmlspecialchars($action['description']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; endif; ?>

            </div>
        </div>
    </div>
</div>

?>

<div class="card-content" style="padding: 4px; display: flex; flex-direction: column; font-size: 0.65rem; line-height: 1.1;">
    
    <!-- ===== HEADER ===== -->
    <div style="border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 2px; text-align: center;">
        <!-- Monster name -->
        <h2 style="margin: 0 0 1px 0; font-size: 0.75rem; font-weight: bold;">
            <?php echo htmlspecialchars($monster['name'] ?? 'Unknown'); ?>
        </h2>
        <!-- Type line: Size, Type, Alignment -->
        <small style="color: #333; font-size: 0.55rem;">
            <?php echo htmlspecialchars($monster['size'] ?? 'Medium'); ?> 
            <?php echo htmlspecialchars($monster['type'] ?? 'creature'); ?>,
            <?php echo htmlspecialchars($monster['alignment'] ?? 'unaligned'); ?>
        </small>
    </div>
    
    <!-- ===== QUICK STATS (AC, HP, Speed, Initiative) ===== -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2px; margin-bottom: 2px; font-size: 0.65rem;">
        <div><strong>AC</strong> <?php echo htmlspecialchars($monster['ac'] ?? '10'); ?></div>
        <div><strong>HP</strong> <?php echo htmlspecialchars($monster['hp'] ?? '0'); ?></div>
        <div><strong>SPD</strong> <?php echo htmlspecialchars($monster['speed'] ?? '30ft'); ?></div>
        <div><strong>Init</strong> <?php echo ($monster['initiative'] ?? 0) >= 0 ? '+' . ($monster['initiative'] ?? 0) : ($monster['initiative'] ?? 0); ?></div>
    </div>
    
    <!-- ===== ABILITY SCORES (3x2 grid) ===== -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; margin-bottom: 2px; border: 1px solid #ccc;">
        <?php 
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        $abbrev = ['STR', 'DEX', 'CON', 'INT', 'WIS', 'CHA'];
        
        foreach ($abilities as $index => $ability):
            $score = $monster[$ability] ?? 10;
            $modifier = floor(($score - 10) / 2);
            $modStr = $modifier >= 0 ? '+' . $modifier : $modifier;
        ?>
            <div style="border: 1px solid #ddd; padding: 1px; text-align: center; font-size: 0.55rem;">
                <strong><?php echo $abbrev[$index]; ?></strong><br>
                <?php echo $score; ?><br>
                <span style="font-size: 0.45rem; color: #666;"><?php echo $modStr; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ===== TRAITS (Condensed) ===== -->
    <div style="font-size: 0.6rem; margin-bottom: 2px; border-top: 1px solid #ccc; padding-top: 1px;">
        <?php 
        // Handle traits whether they're array or string
        $traits = $monster['traits'] ?? [];
        if (is_string($traits)) {
            $traits = json_decode($traits, true) ?: [];
        }
        if (!empty($traits) && is_array($traits)): 
        ?>
            <strong>Traits:</strong>
            <?php 
            $traitCount = 0;
            foreach ($traits as $trait): 
                if ($traitCount >= 2) break; // Only show first 2 traits
                $name = htmlspecialchars($trait['name'] ?? 'Unknown');
                $desc = htmlspecialchars(substr($trait['description'] ?? '', 0, 40));
            ?>
                <div style="font-size: 0.55rem;"><strong><?php echo $name; ?></strong> <?php echo $desc; ?>...</div>
            <?php 
                $traitCount++;
            endforeach; 
            ?>
        <?php endif; ?>
    </div>
    
    <!-- ===== ACTIONS (Condensed) ===== -->
    <div style="font-size: 0.6rem; border-top: 1px solid #ccc; padding-top: 1px; flex-grow: 1; overflow: hidden;">
        <?php 
        // Handle actions whether they're array or string
        $actions = $monster['actions'] ?? [];
        if (is_string($actions)) {
            $actions = json_decode($actions, true) ?: [];
        }
        if (!empty($actions) && is_array($actions)): 
        ?>
            <strong>Actions:</strong>
            <?php 
            $actionCount = 0;
            foreach ($actions as $action): 
                if ($actionCount >= 1) break; // Only show first action
                $name = htmlspecialchars($action['name'] ?? 'Attack');
            ?>
                <div style="font-size: 0.55rem;"><strong><?php echo $name; ?></strong></div>
            <?php 
                $actionCount++;
            endforeach; 
            ?>
        <?php endif; ?>
    </div>

</div>
