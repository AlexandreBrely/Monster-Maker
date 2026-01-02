<?php
/**
 * Boss Monster Card (Print-Only - No Page Wrapper)
 * Extract of boss-card.php without header/navbar/footer
 */
?>

<!-- Boss Card Display: Front (Two-Column Statblock) and Back (Full Body Image) -->
<div class="boss-card-display-wrapper">
    <!-- FRONT: Boss Monster Statblock (A6 Landscape) -->
    <div class="boss-card-front">
        <div class="boss-card-inner">
            <!-- Two-column flowing content -->
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

                    <!-- Stats Bar (AC, HP, Speed) -->
                    <div class="boss-stats-bar">
                        <div class="stat-item">
                            <span class="stat-label">AC</span>
                            <span class="stat-value"><?php echo htmlspecialchars($monster['armor_class'] ?? '10'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">HP</span>
                            <span class="stat-value"><?php echo htmlspecialchars($monster['hit_points'] ?? '1'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Speed</span>
                            <span class="stat-value"><?php echo htmlspecialchars($monster['speed'] ?? '30ft'); ?></span>
                        </div>
                    </div>

                    <!-- Ability Scores -->
                    <div class="boss-abilities">
                        <div class="ability-grid">
                            <?php foreach (['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability): ?>
                                <?php $ab = substr($ability, 0, 3); ?>
                                <div class="ability">
                                    <div class="ability-name"><?php echo strtoupper($ab); ?></div>
                                    <div class="ability-score"><?php echo $abilitiesGrid[$ability]['score'] ?? '10'; ?></div>
                                    <div class="ability-mod"><?php echo $abilitiesGrid[$ability]['modifier'] ?? '+0'; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Challenge Rating and XP -->
                    <div class="boss-cr-section">
                        <div class="cr-item">
                            <span class="cr-label">Challenge</span>
                            <span class="cr-value"><?php echo htmlspecialchars($monster['challenge_rating'] ?? '0'); ?></span>
                        </div>
                        <div class="cr-item">
                            <span class="cr-label">XP</span>
                            <span class="cr-value"><?php echo htmlspecialchars($monsterXp ?? 0); ?></span>
                        </div>
                    </div>

                    <!-- Skills, Senses, Saving Throws -->
                    <?php if (!empty($savingThrows)): ?>
                        <div class="boss-trait">
                            <strong>Saving Throws</strong>
                            <p><?php echo htmlspecialchars(implode(', ', $savingThrows)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($skills)): ?>
                        <div class="boss-trait">
                            <strong>Skills</strong>
                            <p><?php echo htmlspecialchars(implode(', ', $skills)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($senses)): ?>
                        <div class="boss-trait">
                            <strong>Senses</strong>
                            <p><?php echo htmlspecialchars(implode(', ', $senses)); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Traits -->
                    <?php foreach (($traits ?? []) as $trait): ?>
                        <div class="boss-trait">
                            <strong><?php echo htmlspecialchars($trait['name'] ?? 'Trait'); ?></strong>
                            <p><?php echo htmlspecialchars($trait['description'] ?? ''); ?></p>
                        </div>
                    <?php endforeach; ?>

                    <!-- Actions -->
                    <?php if (!empty($actions ?? [])): ?>
                        <div class="boss-section-title">Actions</div>
                        <?php foreach ($actions as $action): ?>
                            <div class="boss-trait">
                                <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?></strong>
                                <p><?php echo htmlspecialchars($action['description'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Bonus Actions -->
                    <?php if (!empty($bonusActions ?? [])): ?>
                        <div class="boss-section-title">Bonus Actions</div>
                        <?php foreach ($bonusActions as $action): ?>
                            <div class="boss-trait">
                                <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?></strong>
                                <p><?php echo htmlspecialchars($action['description'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Reactions -->
                    <?php if (!empty($reactions ?? [])): ?>
                        <div class="boss-section-title">Reactions</div>
                        <?php foreach ($reactions as $reaction): ?>
                            <div class="boss-trait">
                                <strong><?php echo htmlspecialchars($reaction['name'] ?? 'Reaction'); ?></strong>
                                <p><?php echo htmlspecialchars($reaction['description'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Legendary Actions -->
                    <?php if (!empty($legendaryActions ?? [])): ?>
                        <div class="boss-section-title">Legendary Actions</div>
                        <?php foreach ($legendaryActions as $action): ?>
                            <div class="boss-trait">
                                <strong><?php echo htmlspecialchars($action['name'] ?? 'Action'); ?></strong>
                                <p><?php echo htmlspecialchars($action['description'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
