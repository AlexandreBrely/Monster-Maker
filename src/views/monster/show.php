<?php 
/**
 * Monster Show View
 * Displays the details of a specific monster
 * 
 * Expected variables:
 * - $monster : array with all monster data
 */
?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="row">
        <!-- Images and basic information -->
        <div class="col-md-4 mb-4">
            <?php if (!empty($monster['image_fullbody'])): ?>
                <div class="card mb-3">
                    <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>" 
                         alt="<?php echo htmlspecialchars($monster['name']); ?>" class="card-img-top">
                </div>
            <?php endif; ?>

            <?php if (!empty($monster['image_portrait'])): ?>
                <div class="card">
                    <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_portrait']); ?>" 
                         alt="<?php echo htmlspecialchars($monster['name']); ?>" class="card-img-top">
                </div>
            <?php endif; ?>
        </div>

        <!-- Main content -->
        <div class="col-md-8">
            <!-- Monster header -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="card-title"><?php echo htmlspecialchars($monster['name']); ?></h1>
                            <p class="text-muted mb-0">
                                <strong><?php echo htmlspecialchars($monster['size']); ?></strong> 
                                <?php echo htmlspecialchars($monster['type']); ?>, 
                                <?php echo htmlspecialchars($monster['alignment']); ?>
                            </p>
                        </div>

                        <!-- Action buttons: Print, Edit (if owner), Delete (if owner) -->
                        <?php
                        $editUrl = "index.php?url=monster&id={$monster['monster_id']}&action=edit";
                        $deleteAction = "index.php?url=monster&id={$monster['monster_id']}&action=delete";
                        $deleteModalId = 'deleteModal';
                        require __DIR__ . '/../templates/action-buttons.php';
                        ?>
                    </div>
                </div>
            </div>

            <!-- Basic statistics -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <small class="text-muted">Armor Class</small>
                                <p class="h4 mb-0"><?php echo (int)$monster['ac']; ?></p>
                                <?php if (!empty($monster['ac_notes'])): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($monster['ac_notes']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <small class="text-muted">Hit Points</small>
                                <p class="h4 mb-0"><?php echo (int)$monster['hp']; ?></p>
                                <small class="text-muted"><?php echo htmlspecialchars($monster['hit_dice']); ?></small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <small class="text-muted">Speed</small>
                                <p class="text-wrap" style="font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($monster['speed']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <small class="text-muted">Proficiency Bonus</small>
                                <p class="h4 mb-0">+<?php echo (int)$monster['proficiency_bonus']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ability Scores -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Ability Scores</h5>
                    <div class="row text-center">
                        <?php 
                        // D&D 5e's six core abilities - map database column names to abbreviations
                        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
                        $ability_names = ['STR', 'DEX', 'CON', 'INT', 'WIS', 'CHA'];
                        
                        // Loop through each ability to display score and modifier
                        foreach ($abilities as $i => $ability): 
                            $score = (int)$monster[$ability]; // Ability score (1-30 range)
                            
                            // D&D 5e modifier formula: (score - 10) / 2, rounded down
                            // Example: STR 16 → (16-10)/2 = 3 → +3 modifier
                            $modifier = floor(($score - 10) / 2);
                        ?>
                            <div class="col-4 col-md-2 mb-2">
                                <strong><?php echo $ability_names[$i]; ?></strong><br>
                                <span class="h5"><?php echo $score; ?></span><br>
                                <small class="text-muted">(<?php echo $modifier > 0 ? '+' : ''; ?><?php echo $modifier; ?>)</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Skills and senses -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Skills & Senses</h5>
                    <?php if (!empty($monster['skills'])): ?>
                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($monster['skills']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($monster['senses'])): ?>
                        <p><strong>Senses:</strong> <?php echo htmlspecialchars($monster['senses']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($monster['languages'])): ?>
                        <p><strong>Languages:</strong> <?php echo htmlspecialchars($monster['languages']); ?></p>
                    <?php endif; ?>
                    <p><strong>Challenge:</strong> <?php echo htmlspecialchars($monster['challenge_rating']); ?></p>
                </div>
            </div>

            <!-- Immunities and resistances -->
            <?php if (!empty($monster['damage_immunities']) || !empty($monster['damage_resistances']) || !empty($monster['damage_vulnerabilities']) || !empty($monster['condition_immunities'])): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Immunities & Resistances</h5>
                        <?php if (!empty($monster['damage_immunities'])): ?>
                            <p><strong>Damage Immunities:</strong> <?php echo htmlspecialchars($monster['damage_immunities']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($monster['damage_resistances'])): ?>
                            <p><strong>Damage Resistances:</strong> <?php echo htmlspecialchars($monster['damage_resistances']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($monster['damage_vulnerabilities'])): ?>
                            <p><strong>Damage Vulnerabilities:</strong> <?php echo htmlspecialchars($monster['damage_vulnerabilities']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($monster['condition_immunities'])): ?>
                            <p><strong>Condition Immunities:</strong> <?php echo htmlspecialchars($monster['condition_immunities']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Traits -->
            <?php if (!empty($monster['traits'])): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Traits</h5>
                        <!-- nl2br() converts newlines (\n) to <br> tags for display -->
                        <p><?php echo nl2br(htmlspecialchars($monster['traits'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <?php if (!empty($monster['actions']) && count($monster['actions']) > 0): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Actions</h5>
                        <?php 
                        // $monster['actions'] is an array deserialized from JSON in controller
                        // Each $action is an associative array: ['name' => '...', 'description' => '...']
                        foreach ($monster['actions'] as $action): 
                        ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($action['name']); ?></strong>
                                <?php if (!empty($action['description'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($action['description'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reactions -->
            <?php if (!empty($monster['reactions']) && count($monster['reactions']) > 0): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Reactions</h5>
                        <?php 
                        // Reactions are triggered abilities (e.g., Shield spell as reaction)
                        // Each reaction has: name, trigger condition, description
                        foreach ($monster['reactions'] as $reaction): 
                        ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($reaction['name']); ?></strong>
                                <?php if (!empty($reaction['trigger'])): ?>
                                    <p class="text-muted mb-1"><em>Trigger:</em> <?php echo htmlspecialchars($reaction['trigger']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($reaction['description'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($reaction['description'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Legendary abilities -->
            <?php if ($monster['is_legendary'] && !empty($monster['legendary_actions']) && count($monster['legendary_actions']) > 0): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Legendary Actions</h5>
                        <?php if (!empty($monster['legendary_resistance'])): ?>
                            <!-- Legendary Resistance allows auto-succeeding on failed saves -->
                            <p class="text-muted"><strong>Legendary Resistance:</strong> <?php echo htmlspecialchars($monster['legendary_resistance']); ?></p>
                        <?php endif; ?>
                        <?php 
                        // Legendary actions can be used at end of another creature's turn
                        // Each has a cost (typically 1-3 actions, boss gets 3 per round)
                        foreach ($monster['legendary_actions'] as $leg_action): 
                        ?>
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($leg_action['name']); ?></strong> 
                                <!-- Badge shows action cost: "1 cost" or "2 costs" -->
                                <span class="badge bg-secondary"><?php echo (int)$leg_action['cost']; ?> cost<?php echo (int)$leg_action['cost'] > 1 ? 's' : ''; ?></span>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($leg_action['description'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
