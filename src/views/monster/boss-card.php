<?php
/**
 * Boss Monster Card (Horizontal A6 Format - Two Column Layout)
 * Displays boss monsters in landscape A6 format (5.8in x 4.1in)
 * Two-column A6 boss card layout for legendary monsters.
 *
 * Card Structure:
 * - Front Card (5.8in × 4.1in horizontal):
 *   - Left Column: AC, HP, Speed, Abilities, Skills, Senses, Immunities
 *   - Right Column: Traits, Actions, Bonus Actions, Reactions, Legendary Actions
 * - Back Card: Full body image
 * 
 * Data Flow:
 * 1. Controller (MonsterController->show) prepares data:
 *    - $abilitiesGrid: Array of abilities with modifiers
 *    - $skills, $senses: Parsed from comma-separated strings
 *    - $traits, $actions, etc.: Deserialized from JSON
 * 2. This view receives the prepared data and displays it
 * 3. We loop through arrays (foreach) to display each trait/action
 * 
 * CSS: boss-card.css handles the horizontal layout and two-column grid
 */
?>
<?php $extraStyles = ['/css/boss-card.css']; ?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<?php
$editUrl = "index.php?url=monster&id={$monster['monster_id']}&action=edit";
$deleteAction = "index.php?url=monster&id={$monster['monster_id']}&action=delete";
$deleteModalId = 'deleteModal';
require __DIR__ . '/../templates/action-buttons.php';
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
                        <div class="boss-cr">
                            <span class="cr-label">CR</span>
                            <span class="cr-value"><?php echo htmlspecialchars($monster['challenge_rating']); ?></span>
                            <?php if (!empty($monsterXp)): ?>
                                <span class="cr-xp"><?php echo number_format($monsterXp); ?> XP</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Quick Stats: AC / HP / Speed / Initiative in 2x2 Grid -->
                    <div class="boss-stats-bar">
                        <!-- Row 1: AC | Initiative -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="boss-stat-item">
                                    <span class="stat-label">AC</span>
                                    <span class="stat-value"><?php echo (int)$monster['ac']; ?></span>
                                    <?php if (!empty($monster['ac_notes'])): ?>
                                        <span class="stat-note">(<?php echo htmlspecialchars($monster['ac_notes']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="boss-stat-item">
                                    <span class="stat-label">Init</span>
                                    <span class="stat-value">+<?php echo (int)($monster['initiative'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 2: HP | Speed -->
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="boss-stat-item">
                                    <span class="stat-label">HP</span>
                                    <span class="stat-value"><?php echo (int)$monster['hp']; ?></span>
                                    <span class="stat-note">(<?php echo htmlspecialchars($monster['hit_dice']); ?>)</span>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="boss-stat-item">
                                    <span class="stat-label">Speed</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($monster['speed']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="boss-divider"></div>

                    <!-- Abilities -->
                    <div class="boss-abilities">
                        <?php foreach ($abilitiesGrid as $ability): ?>
                            <div class="boss-ability">
                                <div class="ability-label"><?php echo htmlspecialchars($ability['label']); ?></div>
                                <div class="ability-value">
                                    <?php echo htmlspecialchars($ability['mod_display']); ?>
                                    <?php if (!empty($ability['save_bonus'])): ?>
                                        <span class="save-bonus">/<?php echo htmlspecialchars($ability['save_bonus']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="boss-divider"></div>

                    <!-- Skills, Senses, Languages, Immunities -->
                    <?php if (!empty($skills)): ?>
                        <div class="detail-line">
                            <strong>Skills:</strong> <?php echo implode(', ', array_map('htmlspecialchars', $skills)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($senses)): ?>
                        <div class="detail-line">
                            <strong>Senses:</strong> <?php echo implode(', ', array_map('htmlspecialchars', $senses)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['languages'])): ?>
                        <div class="detail-line">
                            <strong>Languages:</strong> <?php echo htmlspecialchars($monster['languages']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['damage_immunities'])): ?>
                        <div class="detail-line">
                            <strong>Damage Immunities:</strong> <?php echo htmlspecialchars($monster['damage_immunities']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['damage_resistances'])): ?>
                        <div class="detail-line">
                            <strong>Resistances:</strong> <?php echo htmlspecialchars($monster['damage_resistances']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['condition_immunities'])): ?>
                        <div class="detail-line">
                            <strong>Condition Immunities:</strong> <?php echo htmlspecialchars($monster['condition_immunities']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="boss-divider"></div>

                    <!-- Traits -->
                    <?php if (!empty($traits) && count($traits) > 0): ?>
                        <div class="boss-section">
                            <h3 class="section-title">TRAITS</h3>
                            <?php foreach ($traits as $trait): ?>
                                <?php if (!empty($trait['name'])): ?>
                                    <div class="entry-item">
                                        <strong class="entry-name"><?php echo htmlspecialchars($trait['name']); ?>.</strong>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($trait['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <?php if (!empty($actions) && count($actions) > 0): ?>
                        <div class="boss-section">
                            <h3 class="section-title">ACTIONS</h3>
                            <?php foreach ($actions as $action): ?>
                                <?php if (!empty($action['name'])): ?>
                                    <div class="entry-item">
                                        <strong class="entry-name"><?php echo htmlspecialchars($action['name']); ?>.</strong>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bonus Actions -->
                    <?php if (!empty($bonusActions) && count($bonusActions) > 0): ?>
                        <div class="boss-section">
                            <h3 class="section-title">BONUS ACTIONS</h3>
                            <?php foreach ($bonusActions as $action): ?>
                                <?php if (!empty($action['name'])): ?>
                                    <div class="entry-item">
                                        <strong class="entry-name"><?php echo htmlspecialchars($action['name']); ?>.</strong>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reactions -->
                    <?php if (!empty($reactions) && count($reactions) > 0): ?>
                        <div class="boss-section">
                            <h3 class="section-title">REACTIONS</h3>
                            <?php foreach ($reactions as $reaction): ?>
                                <?php if (!empty($reaction['name'])): ?>
                                    <div class="entry-item">
                                        <strong class="entry-name"><?php echo htmlspecialchars($reaction['name']); ?>.</strong>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($reaction['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Legendary Actions -->
                    <?php if (!empty($legendaryActions) && count($legendaryActions) > 0): ?>
                        <div class="boss-section">
                            <h3 class="section-title">LEGENDARY ACTIONS</h3>
                            <p class="legendary-intro">
                                <?php echo htmlspecialchars($monster['name']); ?> can take 3 legendary actions.
                            </p>
                            <?php foreach ($legendaryActions as $action): ?>
                                <?php if (!empty($action['name'])): ?>
                                    <div class="entry-item">
                                        <strong class="entry-name">
                                            <?php echo htmlspecialchars($action['name']); ?>
                                            <?php if (!empty($action['cost']) && $action['cost'] > 1): ?>
                                                (Costs <?php echo (int)$action['cost']; ?> Actions)
                                            <?php endif; ?>.
                                        </strong>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BACK: Full Body Image -->
    <div class="boss-card-back">
        <?php if (!empty($monster['image_fullbody'])): ?>
            <img src="/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>"
                alt="<?php echo htmlspecialchars($monster['name']); ?>"
                class="boss-back-image">
        <?php else: ?>
            <div class="boss-back-placeholder">
                <i class="fa-solid fa-dragon"></i>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
