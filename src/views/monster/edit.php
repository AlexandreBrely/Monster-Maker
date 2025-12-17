<?php 
/**
 * Monster Edit View
 * Form to edit an existing monster
 * 
 * Expected variables:
 * - $monster : array with current monster data
 * - $errors : array of validation errors (optional)
 * - $old : array with old values (in case of error)
 */
$errors = $errors ?? [];
$old = $old ?? $monster;

// Get the current card_size value from old data or default to the monster's card_size
$card_size = $old['card_size'] ?? $monster['card_size'] ?? 1;

// Parse existing saving throws to determine which abilities have save proficiency
$savedAbilities = [];
if (!empty($old['saving_throws'])) {
    // Parse "STR +5, DEX +3" format into array of ability abbreviations
    $saveParts = explode(', ', $old['saving_throws']);
    foreach ($saveParts as $part) {
        $parts = explode(' ', trim($part));
        if (!empty($parts[0])) {
            $savedAbilities[] = strtolower($parts[0]);
        }
    }
}
?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<main class="container mt-5 mb-5">
    <h1 class="mb-4">Edit: <?php echo htmlspecialchars($monster['name']); ?></h1>

    <!-- Error display -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $field => $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Edit form (same as create.php) -->
    <form method="POST" enctype="multipart/form-data" action="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>&action=update">
        <!-- Card size selector -->
        <div class="mb-3">
            <label class="form-label">Card Size</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="card_size" id="card_size_small" value="2" <?php echo ($old['card_size'] ?? 2) == 2 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="card_size_small">Small Monster (Playing Card)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="card_size" id="card_size_boss" value="1" <?php echo ($old['card_size'] ?? 2) == 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="card_size_boss">Boss Monster (A6 Sheet)</label>
                </div>
            </div>
        </div>
        
        <!-- Same form as create.php -->
        <!-- Data will be pre-filled via $old/$monster -->
        
        <!-- Basic Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Monster Name *</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="size" class="form-label">Size *</label>
                        <select class="form-select <?php echo isset($errors['size']) ? 'is-invalid' : ''; ?>" 
                                id="size" name="size" required>
                            <option value="">Select a size</option>
                            <option value="Tiny" <?php echo ($old['size'] ?? '') === 'Tiny' ? 'selected' : ''; ?>>Tiny</option>
                            <option value="Small" <?php echo ($old['size'] ?? '') === 'Small' ? 'selected' : ''; ?>>Small</option>
                            <option value="Medium" <?php echo ($old['size'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Large" <?php echo ($old['size'] ?? '') === 'Large' ? 'selected' : ''; ?>>Large</option>
                            <option value="Huge" <?php echo ($old['size'] ?? '') === 'Huge' ? 'selected' : ''; ?>>Huge</option>
                            <option value="Gargantuan" <?php echo ($old['size'] ?? '') === 'Gargantuan' ? 'selected' : ''; ?>>Gargantuan</option>
                        </select>
                        <?php if (isset($errors['size'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['size']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">Type *</label>
                        <input type="text" class="form-control <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" 
                               id="type" name="type" value="<?php echo htmlspecialchars($old['type'] ?? ''); ?>" 
                               placeholder="e.g. Dragon, Beast..." required>
                        <?php if (isset($errors['type'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['type']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="alignment" class="form-label">Alignment</label>
                        <input type="text" class="form-control" id="alignment" name="alignment" 
                               value="<?php echo htmlspecialchars($old['alignment'] ?? ''); ?>"
                               placeholder="e.g. Chaotic Evil">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="ac" class="form-label">Armor Class (AC) *</label>
                        <input type="number" class="form-control <?php echo isset($errors['ac']) ? 'is-invalid' : ''; ?>" 
                               id="ac" name="ac" min="1" value="<?php echo htmlspecialchars($old['ac'] ?? 10); ?>" required>
                        <?php if (isset($errors['ac'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['ac']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="ac_notes" class="form-label">AC Notes <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" id="ac_notes" name="ac_notes" 
                               value="<?php echo htmlspecialchars($old['ac_notes'] ?? ''); ?>"
                               placeholder="e.g. natural armor, plate + shield">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hp" class="form-label">Hit Points *</label>
                        <input type="number" class="form-control <?php echo isset($errors['hp']) ? 'is-invalid' : ''; ?>" 
                               id="hp" name="hp" min="1" value="<?php echo htmlspecialchars($old['hp'] ?? 1); ?>" required>
                        <?php if (isset($errors['hp'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['hp']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ability Scores -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Ability Scores</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $abilities = ['strength' => 'STR', 'dexterity' => 'DEX', 'constitution' => 'CON', 
                                  'intelligence' => 'INT', 'wisdom' => 'WIS', 'charisma' => 'CHA'];
                    $abilityShortMap = ['strength' => 'str', 'dexterity' => 'dex', 'constitution' => 'con', 
                                       'intelligence' => 'int', 'wisdom' => 'wis', 'charisma' => 'cha'];
                    foreach ($abilities as $ability => $label): 
                        $shortName = $abilityShortMap[$ability];
                        $isChecked = in_array($shortName, $savedAbilities);
                    ?>
                        <div class="col-md-2 mb-3">
                            <label for="<?php echo $ability; ?>" class="form-label"><?php echo $label; ?></label>
                            <input type="number" class="form-control ability-score" id="<?php echo $ability; ?>" 
                                   name="<?php echo $ability; ?>" min="1" max="30" 
                                   value="<?php echo htmlspecialchars($old[$ability] ?? 10); ?>"
                                   data-ability="<?php echo $shortName; ?>">
                            <small class="text-muted modifier-display d-block" id="<?php echo $shortName; ?>-modifier">Mod: +0</small>
                            <div class="form-check form-check-sm mt-2">
                                <input class="form-check-input save-proficiency" type="checkbox" 
                                       id="save_<?php echo $shortName; ?>" 
                                       name="save_proficiencies[]" 
                                       value="<?php echo $shortName; ?>"
                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="save_<?php echo $shortName; ?>">Save</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Card Back Image</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image_fullbody" class="form-label">Full Body Image</label>
                    <input type="file" class="form-control <?php echo isset($errors['image_fullbody']) ? 'is-invalid' : ''; ?>" id="image_fullbody" name="image_fullbody" accept="image/*">
                    <small class="form-text text-muted d-block mt-1">
                        Recommended: Portrait-oriented image, minimum 750×1050px (2.5×3.5in at 300dpi). Image will be cropped to fit card dimensions.
                    </small>
                    <?php if (isset($errors['image_fullbody'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['image_fullbody']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($monster['image_fullbody'])): ?>
                        <small class="text-muted d-block mt-2">Current image: 
                            <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>" 
                                 alt="Full body" style="max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Other Fields -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Other Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="hit_dice" class="form-label">Hit Dice</label>
                        <input type="text" class="form-control" id="hit_dice" name="hit_dice" 
                               value="<?php echo htmlspecialchars($old['hit_dice'] ?? ''); ?>"
                               placeholder="e.g. 8d8 + 16">
                    </div>
                    <div class="col-md-6">
                        <label for="speed" class="form-label">Speed</label>
                        <input type="text" class="form-control" id="speed" name="speed" 
                               value="<?php echo htmlspecialchars($old['speed'] ?? ''); ?>"
                               placeholder="e.g. 30 ft., fly 60 ft.">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="challenge_rating" class="form-label">Challenge Rating</label>
                        <input type="text" class="form-control" id="challenge_rating" name="challenge_rating" 
                               value="<?php echo htmlspecialchars($old['challenge_rating'] ?? '0'); ?>"
                               placeholder="e.g. 5 (1,800 XP)">
                    </div>
                    <div class="col-md-4">
                        <label for="initiative" class="form-label">Initiative</label>
                        <div class="input-group">
                            <span class="input-group-text">+</span>
                            <input type="number" class="form-control" id="initiative" name="initiative" 
                                   value="<?php echo htmlspecialchars($old['initiative'] ?? 0); ?>" step="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="proficiency_bonus" class="form-label">Proficiency Bonus</label>
                        <div class="input-group">
                            <span class="input-group-text">+</span>
                            <input type="number" class="form-control" id="proficiency_bonus" name="proficiency_bonus" 
                                   value="<?php echo htmlspecialchars($old['proficiency_bonus'] ?? 0); ?>" min="0" step="1">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="skills" class="form-label">Skills</label>
                        <input type="text" class="form-control" id="skills" name="skills" 
                               value="<?php echo htmlspecialchars($old['skills'] ?? ''); ?>"
                               placeholder="e.g. Perception +5, Stealth +3">
                    </div>
                    <div class="col-md-6">
                        <label for="senses" class="form-label">Senses</label>
                        <input type="text" class="form-control" id="senses" name="senses" 
                               value="<?php echo htmlspecialchars($old['senses'] ?? ''); ?>"
                               placeholder="e.g. Truesight 120 ft.">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="languages" class="form-label">Languages</label>
                        <input type="text" class="form-control" id="languages" name="languages" 
                               value="<?php echo htmlspecialchars($old['languages'] ?? ''); ?>"
                               placeholder="e.g. Common, Draconic">
                    </div>
                    <div class="col-md-6">
                        <label for="damage_immunities" class="form-label">Damage Immunities</label>
                        <input type="text" class="form-control" id="damage_immunities" name="damage_immunities" 
                               value="<?php echo htmlspecialchars($old['damage_immunities'] ?? ''); ?>"
                               placeholder="e.g. Fire, Poison">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="condition_immunities" class="form-label">Condition Immunities</label>
                        <input type="text" class="form-control" id="condition_immunities" name="condition_immunities" 
                               value="<?php echo htmlspecialchars($old['condition_immunities'] ?? ''); ?>"
                               placeholder="e.g. Charmed, Frightened">
                    </div>
                    <div class="col-md-6">
                        <label for="damage_resistances" class="form-label">Damage Resistances</label>
                        <input type="text" class="form-control" id="damage_resistances" name="damage_resistances" 
                               value="<?php echo htmlspecialchars($old['damage_resistances'] ?? ''); ?>"
                               placeholder="e.g. Cold, Lightning">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="damage_vulnerabilities" class="form-label">Damage Vulnerabilities</label>
                    <input type="text" class="form-control" id="damage_vulnerabilities" name="damage_vulnerabilities" 
                           value="<?php echo htmlspecialchars($old['damage_vulnerabilities'] ?? ''); ?>"
                           placeholder="e.g. Radiant">
                </div>
            </div>
        </div>

        

        <!-- ACTIONS -->
        <div class="card mb-3">
            <div class="card-header color-code-action">
                <h5 class="mb-0">Actions</h5>
                <small class="d-block">Multiattack, weapon attacks, spellcasting, or special abilities (use Recharge/Cost in the name when relevant).</small>
            </div>
            <div class="card-body">
                <div id="actions-container">
                    <?php 
                    $actionsData = [];
                    if (!empty($old['actions'])) {
                        if (is_array($old['actions'])) {
                            $actionsData = $old['actions'];
                        } else {
                            $decoded = json_decode($old['actions'], true);
                            $actionsData = is_array($decoded) ? $decoded : [];
                        }
                    }
                    foreach ($actionsData as $index => $actionItem): 
                        $actionName = $actionItem['name'] ?? "Action " . ($index + 1);
                        $actionDesc = $actionItem['description'] ?? '';
                    ?>
                        <div class="mb-3 border rounded action-item" id="action-<?php echo $index + 1; ?>">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-action">
                                    <h6 class="mb-0"><span class="action-title-display"><?php echo htmlspecialchars($actionName); ?></span></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('action-<?php echo $index + 1; ?>')"></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('action-<?php echo $index + 1; ?>')"></button>
                                    </div>
                                </div>
                                <div class="action-content-<?php echo $index + 1; ?>">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Action Name</label>
                                        <input type="text" class="form-control" name="action_names[]" value="<?php echo htmlspecialchars($actionName); ?>" onblur="updateTitle(this, 'action-<?php echo $index + 1; ?>', 'Action <?php echo $index + 1; ?>')">
                                        <small class="text-muted">Tip: include Recharge/Cost in the name if relevant.</small>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control" name="actions[]" rows="4" placeholder="Attack bonus, damage dice, save DC, area, special effects..."><?php echo htmlspecialchars($actionDesc); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        if ($index >= 0) {
                            echo '<script>actionCounter = ' . max($index + 1, (isset($actionCounter) ? $actionCounter : 0)) . ';</script>';
                        }
                    endforeach; 
                    ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addAction()">
                    Add Action
                </button>
            </div>
        </div>

        <!-- BONUS ACTIONS -->
        <div class="card mb-3">
            <div class="card-header color-code-bonus-action">
                <h5 class="mb-0">Bonus Actions</h5>
            </div>
            <div class="card-body">
                <div id="bonus-actions-container">
                    <?php 
                    $bonusActionsData = [];
                    if (!empty($old['bonus_actions'])) {
                        if (is_array($old['bonus_actions'])) {
                            $bonusActionsData = $old['bonus_actions'];
                        } else {
                            $decoded = json_decode($old['bonus_actions'], true);
                            $bonusActionsData = is_array($decoded) ? $decoded : [];
                        }
                    }
                    foreach ($bonusActionsData as $index => $actionItem): 
                        $actionName = $actionItem['name'] ?? "Bonus Action " . ($index + 1);
                        $actionDesc = $actionItem['description'] ?? '';
                    ?>
                        <div class="mb-3 border rounded bonus-action-item" id="bonus-action-<?php echo $index + 1; ?>">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-bonus-action">
                                    <h6 class="mb-0"><span class="bonus-action-title-display"><?php echo htmlspecialchars($actionName); ?></span></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('bonus-action-<?php echo $index + 1; ?>')"></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('bonus-action-<?php echo $index + 1; ?>')"></button>
                                    </div>
                                </div>
                                <div class="bonus-action-content-<?php echo $index + 1; ?>">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Bonus Action Name</label>
                                        <input type="text" class="form-control" name="bonus_action_names[]" value="<?php echo htmlspecialchars($actionName); ?>" onblur="updateTitle(this, 'bonus-action-<?php echo $index + 1; ?>', 'Bonus Action <?php echo $index + 1; ?>')">
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control" name="bonus_actions[]" rows="3" placeholder="Additional attack, movement ability, etc."><?php echo htmlspecialchars($actionDesc); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        if ($index >= 0) {
                            echo '<script>bonusActionCounter = ' . max($index + 1, (isset($bonusActionCounter) ? $bonusActionCounter : 0)) . ';</script>';
                        }
                    endforeach; 
                    ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addBonusAction()">
                    Add Bonus Action
                </button>
            </div>
        </div>

        <!-- REACTIONS -->
        <div class="card mb-3">
            <div class="card-header color-code-reaction">
                <h5 class="mb-0">Reactions</h5>
            </div>
            <div class="card-body">
                <div id="reactions-container">
                    <?php 
                    $reactionsData = [];
                    if (!empty($old['reactions'])) {
                        if (is_array($old['reactions'])) {
                            $reactionsData = $old['reactions'];
                        } else {
                            $decoded = json_decode($old['reactions'], true);
                            $reactionsData = is_array($decoded) ? $decoded : [];
                        }
                    }
                    foreach ($reactionsData as $index => $actionItem): 
                        $actionName = $actionItem['name'] ?? "Reaction " . ($index + 1);
                        $actionDesc = $actionItem['description'] ?? '';
                    ?>
                        <div class="mb-3 border rounded reaction-item" id="reaction-<?php echo $index + 1; ?>">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-reaction">
                                    <h6 class="mb-0"><span class="reaction-title-display"><?php echo htmlspecialchars($actionName); ?></span></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('reaction-<?php echo $index + 1; ?>')"></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('reaction-<?php echo $index + 1; ?>')"></button>
                                    </div>
                                </div>
                                <div class="reaction-content-<?php echo $index + 1; ?>">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Reaction Name</label>
                                        <input type="text" class="form-control" name="reaction_names[]" value="<?php echo htmlspecialchars($actionName); ?>" onblur="updateTitle(this, 'reaction-<?php echo $index + 1; ?>', 'Reaction <?php echo $index + 1; ?>')">
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control" name="reactions[]" rows="3" placeholder="Opportunity attack, parry, dodge, etc."><?php echo htmlspecialchars($actionDesc); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        if ($index >= 0) {
                            echo '<script>reactionCounter = ' . max($index + 1, (isset($reactionCounter) ? $reactionCounter : 0)) . ';</script>';
                        }
                    endforeach; 
                    ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addReaction()">
                    Add Reaction
                </button>
            </div>
        </div>

        <!-- TRAITS (moved after Reactions) -->
        <div class="card mb-3">
            <div class="card-header color-code-trait">
                <h5 class="mb-0">Traits</h5>
                <small class="d-block">Passive abilities, special senses, resistances, or unique features that are always active.</small>
            </div>
            <div class="card-body">
                <div id="traits-container">
                    <?php 
                    $traitsData = [];
                    if (!empty($old['traits'])) {
                        if (is_array($old['traits'])) {
                            $traitsData = $old['traits'];
                        } else {
                            $decoded = json_decode($old['traits'], true);
                            $traitsData = is_array($decoded) ? $decoded : [];
                        }
                    }
                    foreach ($traitsData as $index => $traitItem): 
                        $traitName = $traitItem['name'] ?? "Trait " . ($index + 1);
                        $traitDesc = $traitItem['description'] ?? '';
                    ?>
                        <div class="mb-3 border rounded trait-item" id="trait-<?php echo $index + 1; ?>">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-trait">
                                    <h6 class="mb-0"><span class="trait-title-display"><?php echo htmlspecialchars($traitName); ?></span></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('trait-<?php echo $index + 1; ?>')"></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('trait-<?php echo $index + 1; ?>')"></button>
                                    </div>
                                </div>
                                <div class="trait-content-<?php echo $index + 1; ?>">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Trait Name</label>
                                        <input type="text" class="form-control" name="trait_names[]" value="<?php echo htmlspecialchars($traitName); ?>" onblur="updateTitle(this, 'trait-<?php echo $index + 1; ?>', 'Trait <?php echo $index + 1; ?>')">
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control" name="traits[]" rows="3" placeholder="Passive effect, conditions, advantage/disadvantage..."><?php echo htmlspecialchars($traitDesc); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        if ($index >= 0) {
                            echo '<script>traitCounter = ' . max($index + 1, (isset($traitCounter) ? $traitCounter : 0)) . ';</script>';
                        }
                    endforeach; 
                    ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addTrait()">
                    Add Trait
                </button>
            </div>
        </div>

        <!-- LEGENDARY ACTIONS (if boss monster) -->
        <?php 
        // Check if this is a boss monster (card_size = 1) or legendary flag is set
        $isBossMonster = (($old['card_size'] ?? $monster['card_size'] ?? 2) == 1) || (!empty($monster['is_legendary']));
        if ($isBossMonster): 
        ?>
        <div class="card mb-3">
            <div class="card-header color-code-legendary">
                <h5 class="mb-0">Legendary Actions</h5>
            </div>
            <div class="card-body">
                <div id="legendary-actions-container">
                    <?php 
                    $legendaryActionsData = [];
                    if (!empty($old['legendary_actions'])) {
                        if (is_array($old['legendary_actions'])) {
                            $legendaryActionsData = $old['legendary_actions'];
                        } else {
                            $decoded = json_decode($old['legendary_actions'], true);
                            $legendaryActionsData = is_array($decoded) ? $decoded : [];
                        }
                    }
                    foreach ($legendaryActionsData as $index => $actionItem): 
                        $actionName = $actionItem['name'] ?? "Legendary Action " . ($index + 1);
                        $actionDesc = $actionItem['description'] ?? '';
                    ?>
                        <div class="mb-3 border rounded legendary-action-item" id="legendary-action-<?php echo $index + 1; ?>">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-legendary">
                                    <h6 class="mb-0"><span class="legendary-action-title-display"><?php echo htmlspecialchars($actionName); ?></span></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('legendary-action-<?php echo $index + 1; ?>')"></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('legendary-action-<?php echo $index + 1; ?>')"></button>
                                    </div>
                                </div>
                                <div class="legendary-action-content-<?php echo $index + 1; ?>">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Action Name (include cost)</label>
                                        <input type="text" class="form-control" name="legendary_action_names[]" value="<?php echo htmlspecialchars($actionName); ?>" onblur="updateTitle(this, 'legendary-action-<?php echo $index + 1; ?>', 'Legendary Action <?php echo $index + 1; ?>')">
                                        <small class="text-muted">Include action cost, e.g. "Claw Attack (Costs 1 Action)"</small>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea class="form-control" name="legendary_actions[]" rows="3" placeholder="Can take 3 legendary actions..."><?php echo htmlspecialchars($actionDesc); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        if ($index >= 0) {
                            echo '<script>legendaryActionCounter = ' . max($index + 1, (isset($legendaryActionCounter) ? $legendaryActionCounter : 0)) . ';</script>';
                        }
                    endforeach; 
                    ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addLegendaryAction()">
                    Add Legendary Action
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Visibility -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Visibility</h5>
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                           <?php echo ($old['is_public'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_public">
                        Make this monster public (visible to all users)
                    </label>
                </div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>" class="btn btn-secondary">Cancel</a>
            <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteModal">
                Delete this monster
            </button>
        </div>
    </form>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Monster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong><?php echo htmlspecialchars($monster['name']); ?></strong>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>&action=delete" 
                          style="display: inline;">
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="/public/js/monster-form.js"></script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
