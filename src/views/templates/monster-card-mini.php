<?php
/**
 * Mini Monster Card Template
 * Displays a compact statblock card for use in listings (index, my-monsters)
 * 
 * For beginners:
 * This is a reusable template (partial) that displays one monster card.
 * It's included in listing pages using: require 'monster-card-mini.php'
 * 
 * Expected variables (must be set before including this file):
 * - $monster: array with monster data (must be deserialized with JSON fields as arrays)
 * - $showOwnerBadge: bool (optional) - show public/private badge for owner
 * 
 * How it works:
 * 1. Loop through abilities (STR, DEX, etc.) and calculate modifiers
 * 2. Display front card with stats
 * 3. Display back card with full-body image
 * 4. Entire card is wrapped in a link to the full monster view
 */

$showOwnerBadge = $showOwnerBadge ?? false; // Default to false if not set
?>
<div class="monster-card-mini">
    <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>" class="text-decoration-none">
        <!-- Front & Back Card Display -->
        <div class="mini-card-wrapper">
            <!-- FRONT: Compact Statblock -->
            <div class="mini-statblock">
                <!-- Header -->
                <div class="mini-header">
                    <div>
                        <h3 class="mini-title"><?php echo htmlspecialchars($monster['name']); ?></h3>
                        <p class="mini-subtitle">
                            <?php echo htmlspecialchars($monster['size']); ?> 
                            <?php echo htmlspecialchars($monster['type']); ?>
                        </p>
                    </div>
                    <div class="mini-cr">
                        <strong>CR <?php echo htmlspecialchars($monster['challenge_rating']); ?></strong>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="mini-stats">
                    <div class="mini-stat">
                        <span class="mini-stat-label">AC</span>
                        <span class="mini-stat-value"><?php echo (int)$monster['ac']; ?></span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-stat-label">HP</span>
                        <span class="mini-stat-value"><?php echo (int)$monster['hp']; ?></span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-stat-label">Speed</span>
                        <span class="mini-stat-value"><?php echo htmlspecialchars($monster['speed']); ?></span>
                    </div>
                </div>

                <!-- Abilities -->
                <div class="mini-abilities">
                    <?php
                    $abilities = [
                        'STR' => $monster['strength'],
                        'DEX' => $monster['dexterity'],
                        'CON' => $monster['constitution'],
                        'INT' => $monster['intelligence'],
                        'WIS' => $monster['wisdom'],
                        'CHA' => $monster['charisma']
                    ];
                    foreach ($abilities as $label => $score):
                        $mod = floor(($score - 10) / 2);
                        $modStr = ($mod >= 0 ? '+' : '') . $mod;
                    ?>
                        <div class="mini-ability">
                            <div class="mini-ability-label"><?php echo $label; ?></div>
                            <div class="mini-ability-mod"><?php echo $modStr; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($showOwnerBadge): ?>
                    <div class="mini-badge">
                        <span class="badge <?php echo $monster['is_public'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <?php echo $monster['is_public'] ? 'Public' : 'Private'; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- BACK: Full Body Image -->
            <div class="mini-card-back">
                <?php if (!empty($monster['image_fullbody'])): ?>
                    <img src="/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>"
                        alt="<?php echo htmlspecialchars($monster['name']); ?>"
                        class="mini-back-image">
                <?php else: ?>
                    <div class="mini-back-placeholder">
                        <i class="fa-solid fa-dragon"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>
