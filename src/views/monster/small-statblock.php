<?php

/**
 * Small Monster Statblock View (Single-Column Playing Card)
 * Displays small-format monsters in D&D 5e statblock style.
 *
 * This view renders a vertical playing card (2.48in × 3.46in) for small monsters.
 * The card shows all essential stats in a compact, print-ready format.
 *
 * Expected variables from controller (MonsterController->show):
 * - $monster : array with all monster data (deserialized from database)
 * - $abilitiesGrid : pre-calculated ability scores with modifiers
 * - $skills : array of skill names parsed from comma-separated string
 * - $senses : array of senses parsed from comma-separated string
 * - $traits, $actions, $bonusActions, $reactions : deserialized JSON arrays
 *
 * Data flow:
 * 1. Controller calls Monster model's getById() which deserializes JSON fields
 * 2. Controller builds $abilitiesGrid, $skills, $senses arrays
 * 3. This view displays the prepared data in a playing card layout
 */

// ?? operator: Use provided value if exists, otherwise default to empty array
// This prevents errors if controller didn't prepare these variables
$traits = $traits ?? [];
$actions = $actions ?? [];
$bonusActions = $bonusActions ?? [];
$reactions = $reactions ?? [];
$savingThrows = $savingThrows ?? [];
$skills = $skills ?? [];
$senses = $senses ?? [];
$abilitiesGrid = $abilitiesGrid ?? [];
?>
<?php $extraStyles = ['/css/small-statblock.css']; ?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<?php
$editUrl = "index.php?url=monster&id={$monster['monster_id']}&action=edit";
$deleteAction = "index.php?url=monster&id={$monster['monster_id']}&action=delete";
$deleteModalId = 'deleteModal';
require __DIR__ . '/../templates/action-buttons.php';
?>

<!-- Card Display: Front (SmallStatblock) and Back (Full Body Image) -->
<div class="card-display-wrapper">
    <!-- FRONT: Small monster statblock card -->
    <div class="small-statblock statblock compact">
        <!-- Header: name, type/alignment, CR/XP -->
        <div class="statblock-header">
            <div>
                <h1 class="statblock-title"><?php echo htmlspecialchars($monster['name']); ?></h1>
                <p class="statblock-subtitle">
                    <?php echo htmlspecialchars($monster['size']); ?>
                    <?php echo htmlspecialchars($monster['type']); ?>,
                    <?php echo htmlspecialchars($monster['alignment']); ?>
                </p>
            </div>
            <?php
            // XP values by challenge rating (D&D 5e standard progression)
            // Maps CR string (including fractions like '1/2') to XP reward
            // Used to display XP value next to CR in the header
            $xpByCR = [
                '0' => 10,      // CR 0 creatures (trivial encounters)
                '1/8' => 25,    // Fractional CRs for very weak creatures
                '1/4' => 50,
                '1/2' => 100,
                '1' => 200,     // CR 1 and up follow exponential curve
                '2' => 450,
                '3' => 700,
                '4' => 1100,
                '5' => 1800,
                '6' => 2300,
                '7' => 2900,
                '8' => 3900,
                '9' => 5000,
                '10' => 5900,
                '11' => 7200,
                '12' => 8400,
                '13' => 10000,
                '14' => 11500,
                '15' => 13000,
                '16' => 15000,
                '17' => 18000,
                '18' => 20000,
                '19' => 22000,
                '20' => 25000,
                '21' => 33000,
                '22' => 41000,
                '23' => 50000,
                '24' => 62000,
                '25' => 75000,
                '26' => 90000,
                '27' => 105000,
                '28' => 120000,
                '29' => 135000,
                '30' => 155000
            ];
            $cr = $monster['challenge_rating'];
            // isset() checks if array key exists before accessing it
            // number_format() adds thousand separators (e.g., 1000 → 1,000)
            $xp = isset($xpByCR[$cr]) ? number_format($xpByCR[$cr]) : '—';
            ?>
            <div class="header-cr">
                CR <?php echo htmlspecialchars($cr); ?> <small>(<?php echo $xp; ?> XP)</small>
            </div>
        </div>

        <div class="statblock-container d-flex flex-column h-100">
            <div class="statblock-top statblock-left">
                <div class="basic-stats">
                    <!-- Quick Stats: AC / HP / Speed -->
                    <div class="statblock-stats">
                        <div class="stat-line ac-block">
                            <span class="stat-label">AC:</span>
                            <span class="stat-value"><?php echo (int)$monster['ac']; ?></span>
                            <?php if (!empty($monster['ac_notes'])): ?>
                                <span class="stat-detail">(<?php echo htmlspecialchars($monster['ac_notes']); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-line hp-block">
                            <span class="stat-label">HP:</span>
                            <span class="stat-value"><?php echo (int)$monster['hp']; ?></span>
                            <?php if (!empty($monster['hit_dice'])): ?>
                                <span class="stat-detail">(<?php echo htmlspecialchars($monster['hit_dice']); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-line speed-block">
                        <span class="stat-label">Speed:</span>
                        <span class="stat-value"><?php echo htmlspecialchars($monster['speed']); ?></span>
                    </div>

                    <!-- Ability Scores grid -->
                    <div class="ability-grid">
                        <?php foreach ($abilitiesGrid as $ability): ?>
                            <div class="ability-box">
                                <div class="ability-name"><?php echo htmlspecialchars($ability['label']); ?></div>
                                <div class="ability-mod">
                                    <?php echo htmlspecialchars($ability['mod_display']); ?>
                                    <?php if (!empty($ability['save_bonus'])): ?>
                                        <span class="save-bonus"> /<?php echo htmlspecialchars($ability['save_bonus']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ability-grid-divider"></div>
                </div>

                <!-- Monster Details (skills, defenses, senses, languages) -->
                <div class="skills-secondary statblock-details">

                    <?php if (!empty($skills)): ?>
                        <div class="detail-item">
                            <span class="detail-label">Skills:</span>
                            <?php echo implode(', ', array_map('htmlspecialchars', $skills)); ?>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $damageImm = !empty($monster['damage_immunities']) ? trim($monster['damage_immunities']) : '';
                    $condImm = !empty($monster['condition_immunities']) ? trim($monster['condition_immunities']) : '';
                    $immParts = [];
                    if ($damageImm !== '') { $immParts[] = htmlspecialchars($damageImm); }
                    if ($condImm !== '') { $immParts[] = htmlspecialchars($condImm); }
                    if (!empty($immParts)): ?>
                        <div class="detail-item">
                            <span class="detail-label">Imm.</span>
                            <?php echo implode('; ', $immParts); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['damage_resistances'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Resist.</span>
                            <?php echo htmlspecialchars($monster['damage_resistances']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['damage_vulnerabilities'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Vuln.</span>
                            <?php echo htmlspecialchars($monster['damage_vulnerabilities']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($senses)): ?>
                        <div class="detail-item">
                            <span class="detail-label">Senses:</span>
                            <?php echo implode(', ', array_map('htmlspecialchars', $senses)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($monster['languages'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Languages:</span>
                            <?php echo htmlspecialchars($monster['languages']); ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RIGHT COLUMN REMOVED FOR SINGLE-COLUMN PRINT FIT -->
            <div class="statblock-right mt-auto">
                <!-- Actions Section -->
                <?php if (!empty($actions) && is_array($actions)): ?>
                    <div class="section-block">
                        <h3 class="section-title">Actions</h3>
                        <div class="statblock-section">
                            <?php foreach ($actions as $action): ?>
                                <?php if (isset($action['name']) && isset($action['description'])): ?>
                                    <div class="action-entry">
                                        <span class="entry-title"><?php echo htmlspecialchars($action['name']); ?>.</span>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bonus Actions Section -->
                <?php if (!empty($bonusActions) && is_array($bonusActions)): ?>
                    <div class="section-block">
                        <h3 class="section-title">Bonus Actions</h3>
                        <div class="statblock-section">
                            <?php foreach ($bonusActions as $action): ?>
                                <?php if (isset($action['name']) && isset($action['description'])): ?>
                                    <div class="action-entry">
                                        <span class="entry-title"><?php echo htmlspecialchars($action['name']); ?>.</span>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reactions Section -->
                <?php if (!empty($reactions) && is_array($reactions)): ?>
                    <div class="section-block">
                        <h3 class="section-title">Reactions</h3>
                        <div class="statblock-section">
                            <?php foreach ($reactions as $reaction): ?>
                                <?php if (isset($reaction['name']) && isset($reaction['description'])): ?>
                                    <div class="action-entry">
                                        <span class="entry-title"><?php echo htmlspecialchars($reaction['name']); ?>.</span>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($reaction['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Traits Section (moved to end) -->
                <?php if (!empty($traits) && is_array($traits)): ?>
                    <div class="section-block">
                        <h3 class="section-title">Traits</h3>
                        <div class="statblock-section">
                            <?php foreach ($traits as $trait): ?>
                                <?php if (isset($trait['name']) && isset($trait['description'])): ?>
                                    <div class="trait-entry">
                                        <span class="entry-title"><?php echo htmlspecialchars($trait['name']); ?>.</span>
                                        <span class="entry-text"><?php echo nl2br(htmlspecialchars($trait['description'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div> <!-- end statblock -->

    <!-- BACK: Monster Full Body Image -->
    <div class="card-back">
        <?php if (!empty($monster['image_fullbody'])): ?>
            <img src="/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>"
                alt="<?php echo htmlspecialchars($monster['name']); ?>"
                class="card-back-image">
        <?php else: ?>
            <div class="card-back-placeholder">
                <i class="bi bi-image"></i>
            </div>
        <?php endif; ?>
    </div>
</div> <!-- end card-display-wrapper -->

</main>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
