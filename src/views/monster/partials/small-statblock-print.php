<?php
/**
 * Small Monster Statblock (Print-Only - No Page Wrapper)
 * Extract of small-statblock.php without header/navbar/footer
 */
?>

<!-- Card Display: Front (SmallStatblock) and Back (Full Body Image) -->
<div class="card-display-wrapper">
    <!-- FRONT: Small monster statblock card -->
    <div class="small-statblock statblock">
        <!-- Header: name, type/alignment, CR/XP -->
        <div class="statblock-header">
            <h1 class="statblock-name"><?php echo htmlspecialchars($monster['name']); ?></h1>
            <p class="statblock-type">
                <?php echo htmlspecialchars($monster['size']); ?> 
                <?php echo htmlspecialchars($monster['type']); ?>, 
                <?php echo htmlspecialchars($monster['alignment']); ?>
            </p>
            <div class="statblock-cr-row">
            <div class="statblock-cr">Challenge <?php echo htmlspecialchars($monster['challenge_rating'] ?? '0'); ?> (<?php echo number_format($monsterXp ?? 0); ?> XP)</span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="statblock-quick-stats">
            <div class="quick-stat">
                <strong>AC</strong> <?php echo htmlspecialchars($monster['armor_class'] ?? '10'); ?>
            </div>
            <div class="quick-stat">
                <strong>HP</strong> <?php echo htmlspecialchars($monster['hit_points'] ?? '1'); ?>
            </div>
            <div class="quick-stat">
                <strong>Speed</strong> <?php echo htmlspecialchars($monster['speed'] ?? '30ft'); ?>
            </div>
        </div>

        <!-- Ability Scores -->
        <div class="statblock-abilities">
            <?php foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability): ?>
                <?php $ab = substr($ability, 0, 3); ?>
                <div class="ability-score">
                    <span class="ability-label"><?php echo strtoupper($ab); ?></span>
                    <span class="ability-value"><?php echo ($abilitiesGrid[$ability]['score'] ?? 10) . ' (' . ($abilitiesGrid[$ability]['modifier'] ?? '+0') . ')'; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Optional Sections -->
        <?php if (!empty($savingThrows)): ?>
            <div class="statblock-section">
                <strong>Saving Throws</strong> <?php echo htmlspecialchars(implode(', ', $savingThrows)); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($skills)): ?>
            <div class="statblock-section">
                <strong>Skills</strong> <?php echo htmlspecialchars(implode(', ', $skills)); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($senses)): ?>
            <div class="statblock-section">
                <strong>Senses</strong> <?php echo htmlspecialchars(implode(', ', $senses)); ?>
            </div>
        <?php endif; ?>

        <!-- Traits -->
        <?php foreach (($traits ?? []) as $trait): ?>
            <div class="statblock-trait">
                <strong><?php echo htmlspecialchars($trait['name'] ?? 'Trait'); ?>.</strong>
                <?php echo htmlspecialchars($trait['description'] ?? ''); ?>
            </div>
        <?php endforeach; ?>

        <!-- Actions -->
        <?php if (!empty($actions ?? [])): ?>
            <div class="statblock-section-title">Actions</div>
            <?php foreach ($actions as $action): ?>
                <div class="statblock-trait">
                    <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?>.</strong>
                    <?php echo htmlspecialchars($action['description'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Bonus Actions -->
        <?php if (!empty($bonusActions ?? [])): ?>
            <div class="statblock-section-title">Bonus Actions</div>
            <?php foreach ($bonusActions as $action): ?>
                <div class="statblock-trait">
                    <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?>.</strong>
                    <?php echo htmlspecialchars($action['description'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Reactions -->
        <?php if (!empty($reactions ?? [])): ?>
            <div class="statblock-section-title">Reactions</div>
            <?php foreach ($reactions as $reaction): ?>
                <div class="statblock-trait">
                    <strong><?php echo htmlspecialchars($reaction['name'] ?? 'Reaction'); ?>.</strong>
                    <?php echo htmlspecialchars($reaction['description'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Legendary Actions -->
        <?php if (!empty($legendaryActions ?? [])): ?>
            <div class="statblock-section-title">Legendary Actions</div>
            <?php foreach ($legendaryActions as $action): ?>
                <div class="statblock-trait">
                    <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?>.</strong>
                    <?php echo htmlspecialchars($action['description'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
