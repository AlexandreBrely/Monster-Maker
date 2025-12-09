<?php
/**
 * Boss Monster Creation Page
 *
 * This page provides a form for creating a Boss Monster (A5 sheet format).
 * All boss monster fields are always visible (no card_size toggle).
 * Lair actions and legendary features are optional, but always shown.
 */
require_once ROOT . '/src/views/templates/header.php';
require_once ROOT . '/src/views/templates/navbar.php';
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <h2 class="mb-4">Create Boss Monster</h2>
    <form action="index.php?url=create_boss" method="POST" enctype="multipart/form-data">

        <!-- ===== BASIC IDENTIFICATION ===== -->
                <!--
                    BASIC IDENTIFICATION
                    Monster name, size, type, and alignment.
                    These are the core identity fields for your monster.
                -->
        <!-- Monster name and core type information -->
        <div class="mb-3">
            <label for="name" class="form-label">Monster Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="size" class="form-label">Size <span class="text-danger">*</span></label>
                <select class="form-select" id="size" name="size" required>
                    <option value="Tiny">Tiny</option>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="Huge">Huge</option>
                    <option value="Gargantuan">Gargantuan</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="type" name="type" required placeholder="e.g. Undead, Beast, Dragon">
            </div>
            <div class="col-md-4">
                <label for="alignment" class="form-label">Alignment</label>
                <input type="text" class="form-control" id="alignment" name="alignment" placeholder="e.g. Chaotic Evil, Lawful Good">
            </div>
        </div>

        <!-- ===== DEFENSIVE STATISTICS ===== -->
                <!--
                    DEFENSIVE STATISTICS
                    Armor Class (AC), Hit Points (HP), Hit Dice, and optional equipment notes.
                    These fields define how tough your monster is.
                -->
        <!-- Armor class, hit points, armor info, and hit dice -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="ac" class="form-label">Armor Class (AC)</label>
                <input type="number" class="form-control" id="ac" name="ac" min="1" required>
            </div>
            <div class="col-md-4">
                <label for="hp" class="form-label">Hit Points (HP)</label>
                <input type="number" class="form-control" id="hp" name="hp" min="1" required>
            </div>
            <div class="col-md-4">
                <label for="hit_dice" class="form-label">Hit Dice</label>
                <input type="text" class="form-control" id="hit_dice" name="hit_dice" placeholder="e.g. 4d8+2">
            </div>
        </div>
        <div class="mb-3">
            <label for="ac_notes" class="form-label">AC Notes <span class="text-muted">(optional - armor, equipment)</span></label>
            <input type="text" class="form-control" id="ac_notes" name="ac_notes" placeholder="e.g. leather armor; studded leather + shield; natural armor">
        </div>
        <div class="mb-3">
            <label for="equipment_variants" class="form-label">Equipment Variants <span class="text-muted">(optional - for troops with different loadouts)</span></label>
            <textarea class="form-control" id="equipment_variants" name="equipment_variants" rows="3" placeholder="e.g.&#10;- Standard: Spear + Shield (AC 16)&#10;- Crossbow Specialist: Hand Crossbow + Dagger (AC 14, ranged attacks)&#10;- Heavy: Greataxe + Chain Mail (AC 15, +2 damage)"></textarea>
        </div>

        <!-- ===== MOVEMENT & PROFICIENCY ===== -->
                <!--
                    MOVEMENT & PROFICIENCY
                    Speed and proficiency bonus.
                    Speed can include walking, flying, swimming, etc.
                -->
        <div class="row mb-3">
            <div class="col-md-8">
                <label for="speed" class="form-label">Speed <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="speed" name="speed" required placeholder="e.g. 30 ft, fly 60 ft">
            </div>
            <div class="col-md-4">
                <label for="proficiency_bonus" class="form-label">Proficiency Bonus</label>
                <div class="input-group">
                    <span class="input-group-text">+</span>
                    <input type="text" class="form-control" id="proficiency_bonus" name="proficiency_bonus" placeholder="e.g. 2, 3, 5">
                </div>
            </div>
        </div>

        <!-- ===== ABILITY SCORES ===== -->
                <!--
                    ABILITY SCORES
                    The six core D&D stats: STR, DEX, CON, INT, WIS, CHA.
                    Each is a number, usually between 1 and 30.
                -->
        <!-- STR, DEX, CON, INT, WIS, CHA in compact 6-column layout -->
        <div class="row mb-3">
            <div class="col-md-2">
                <label for="strength" class="form-label">STR <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="strength" name="strength" min="1" max="30" required data-ability="str">
                <small class="text-muted modifier-display d-block" id="str-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_str" name="save_proficiencies[]" value="str">
                    <label class="form-check-label" for="save_str">Save</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="dexterity" class="form-label">DEX <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="dexterity" name="dexterity" min="1" max="30" required data-ability="dex">
                <small class="text-muted modifier-display d-block" id="dex-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_dex" name="save_proficiencies[]" value="dex">
                    <label class="form-check-label" for="save_dex">Save</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="constitution" class="form-label">CON <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="constitution" name="constitution" min="1" max="30" required data-ability="con">
                <small class="text-muted modifier-display d-block" id="con-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_con" name="save_proficiencies[]" value="con">
                    <label class="form-check-label" for="save_con">Save</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="intelligence" class="form-label">INT <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="intelligence" name="intelligence" min="1" max="30" required data-ability="int">
                <small class="text-muted modifier-display d-block" id="int-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_int" name="save_proficiencies[]" value="int">
                    <label class="form-check-label" for="save_int">Save</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="wisdom" class="form-label">WIS <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="wisdom" name="wisdom" min="1" max="30" required data-ability="wis">
                <small class="text-muted modifier-display d-block" id="wis-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_wis" name="save_proficiencies[]" value="wis">
                    <label class="form-check-label" for="save_wis">Save</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="charisma" class="form-label">CHA <span class="text-danger">*</span></label>
                <input type="number" class="form-control ability-score" id="charisma" name="charisma" min="1" max="30" required data-ability="cha">
                <small class="text-muted modifier-display d-block" id="cha-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <input class="form-check-input save-proficiency" type="checkbox" id="save_cha" name="save_proficiencies[]" value="cha">
                    <label class="form-check-label" for="save_cha">Save</label>
                </div>
            </div>
        </div>

        <!-- ===== SKILLS & SENSES ===== -->
                <!--
                    SKILLS & SENSES
                    List any skill bonuses and senses (like darkvision or passive perception).
                -->
        <!-- Competency areas and perception methods -->
        <div class="mb-3">
            <label for="skills" class="form-label">Skills</label>
            <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="e.g. Stealth +4, Perception +2"></textarea>
        </div>
        <div class="mb-3">
            <label for="senses" class="form-label">Senses</label>
            <textarea class="form-control" id="senses" name="senses" rows="2" placeholder="e.g. Darkvision 60 ft, Passive Perception 12"></textarea>
        </div>

        <!-- ===== COMMUNICATION & DIFFICULTY ===== -->
                <!--
                    COMMUNICATION & DIFFICULTY
                    Languages the monster speaks and its challenge rating (CR).
                -->
        <!-- Languages spoken and combat challenge rating -->
        <div class="row mb-3">
            <div class="col-md-8">
                <label for="languages" class="form-label">Languages</label>
                <input type="text" class="form-control" id="languages" name="languages" placeholder="e.g. Common, Draconic">
            </div>
            <div class="col-md-4">
                <label for="challenge_rating" class="form-label">Challenge Rating / XP</label>
                <input type="text" class="form-control" id="challenge_rating" name="challenge_rating" placeholder="e.g. 1/2 (25 XP), 5 (1,800 XP)">
            </div>
        </div>

        <!-- ===== DEFENSIVE TRAITS ===== -->
                <!--
                    DEFENSIVE TRAITS
                    Optional: Damage immunities, condition immunities, resistances, vulnerabilities.
                    These fields are hidden unless the user checks the box.
                -->
        <!-- Immunities, resistances, vulnerabilities -->
        <!-- These sections are hidden by default and shown only when checkbox is checked -->
        <!-- This reduces form clutter for simple creatures without special resistances -->
        <div class="mb-3">
            <label for="damage_immunities_check" class="form-check-label">
                <input class="form-check-input" type="checkbox" id="damage_immunities_check" onclick="toggleField('damage_immunities')">
                Add Damage Immunities?
            </label>
            <textarea class="form-control d-none" id="damage_immunities" name="damage_immunities" rows="2" style="display: none;"></textarea>
        </div>
        <div class="mb-3">
            <label for="condition_immunities_check" class="form-check-label">
                <input class="form-check-input" type="checkbox" id="condition_immunities_check" onclick="toggleField('condition_immunities')">
                Add Condition Immunities?
            </label>
            <textarea class="form-control d-none" id="condition_immunities" name="condition_immunities" rows="2" style="display: none;"></textarea>
        </div>
        <div class="mb-3">
            <label for="damage_resistances_check" class="form-check-label">
                <input class="form-check-input" type="checkbox" id="damage_resistances_check" onclick="toggleField('damage_resistances')">
                Add Damage Resistances?
            </label>
            <textarea class="form-control d-none" id="damage_resistances" name="damage_resistances" rows="2" style="display: none;"></textarea>
        </div>
        <div class="mb-3">
            <label for="damage_vulnerabilities_check" class="form-check-label">
                <input class="form-check-input" type="checkbox" id="damage_vulnerabilities_check" onclick="toggleField('damage_vulnerabilities')">
                Add Damage Vulnerabilities?
            </label>
            <textarea class="form-control d-none" id="damage_vulnerabilities" name="damage_vulnerabilities" rows="2" style="display: none;"></textarea>
        </div>

        <!-- ===== ACTIONS ===== -->
                <!--
                    ACTIONS
                    Monster actions used in combat (attacks, spellcasting, special moves).
                    The loop below lets you add multiple actions.
                    You can include recharge/cost info in the name (e.g. "Poison Breath (Recharge 5-6)").
                -->
        <!-- Monster actions (attacks, abilities used in combat) -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Actions</h5>
                <small class="text-muted d-block">Multiattack, weapon attacks, spellcasting, special abilities (include Recharge on any action)
                </small>
            </div>
            <div class="card-body">
                <?php 
                $actions = $old['actions'] ?? [];
                if (is_string($actions)) {
                    $actions = json_decode($actions, true) ?? [];
                }
                if (empty($actions)) {
                    $actions = [''];
                }
                ?>
                <?php foreach ($actions as $index => $action): ?>
                                        <!-- Action builder: each action has a name and description -->
                    <div class="mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Action Name</label>
                            <input type="text" class="form-control" name="action_names[]" 
                                   placeholder="e.g. Multiattack, Paralyzing Touch, Spellcasting, Poison Breath (Recharge 5–6)"
                                   value="<?php echo htmlspecialchars($action['name'] ?? ''); ?>">
                            <small class="text-muted">Include (Recharge X–Y) or (Costs X Actions) in the name if applicable</small>
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="actions[]" rows="4" 
                                      placeholder="Full description including attack bonuses, damage, saving throws, spell lists, etc.
                                      Melee Attack Roll: +15, reach 15 ft. Hit: 17 (2d8 + 8) Slashing damage plus 10 (3d6) Poison damage."><?php echo htmlspecialchars(is_array($action) ? ($action['description'] ?? '') : ($action ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Rend. Melee Attack Roll: +15, reach 15 ft. Hit: 22 (3d8 + 8) Slashing damage."<br>
                                • "Poison Breath (Recharge 5–6). Each creature in a 90-foot cone must make a DC 20 Constitution saving throw, taking 90 (20d8) poison damage on a failed save, or half as much on a successful one."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_action" class="btn btn-sm btn-outline-primary">+ Add Action</button>
            </div>
        </div>

        <!-- ===== TRAITS ===== -->
                <!--
                    TRAITS
                    Passive abilities that are always active (not actions or reactions).
                    Examples: Legendary Resistance, Regeneration, Amphibious.
                    The loop below lets you add multiple traits.
                -->
        <!-- Passive abilities that always apply (not actions or reactions) -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Traits</h5>
                <small class="text-muted d-block">Passive abilities (Legendary Resistance, Regeneration, Immunity explanations, etc.)</small>
            </div>
            <div class="card-body">
                <?php 
                $traits = $old['traits'] ?? [];
                if (is_string($traits)) {
                    $traits = json_decode($traits, true) ?? [];
                }
                if (empty($traits)) {
                    $traits = [''];
                }
                ?>
                <?php foreach ($traits as $index => $trait): ?>
                    <div class="mb-3 p-3 border rounded">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Trait Name</label>
                            <input type="text" class="form-control" name="trait_names[]" 
                                   placeholder="e.g. Legendary Resistance, Rejuvenation, Amphibious"
                                   value="<?php echo htmlspecialchars($trait['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="traits[]" rows="3" 
                                      placeholder="Describe the trait in full detail..."><?php echo htmlspecialchars(is_array($trait) ? ($trait['description'] ?? '') : ($trait ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Amphibious. The monster can breathe air and water."<br>
                                • "Legendary Resistance (3/Day). If the monster fails a saving throw, it can choose to succeed instead."<br>
                                • "Magic Resistance. The monster has Advantage on saving throws against spells and other magical effects."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_trait" class="btn btn-sm btn-outline-primary">+ Add Trait</button>
            </div>
        </div>

        <!-- ===== BONUS ACTIONS ===== -->
                <!--
                    BONUS ACTIONS
                    Actions that can be taken as a bonus action (e.g. Nimble Escape, Second Wind).
                    The loop below lets you add multiple bonus actions.
                -->
        <!-- Quick actions that can be taken as bonus action on a turn -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Bonus Actions</h5>
                <small class="text-muted d-block">Actions that can be taken as a bonus action (Teleport, Movement, etc.)</small>
            </div>
            <div class="card-body">
                <?php 
                $bonus_actions = $old['bonus_actions'] ?? [];
                if (is_string($bonus_actions)) {
                    $bonus_actions = json_decode($bonus_actions, true) ?? [];
                }
                if (empty($bonus_actions)) {
                    $bonus_actions = [''];
                }
                ?>
                <?php foreach ($bonus_actions as $index => $bonus_action): ?>
                                        <!-- Bonus Action builder: each bonus action has a name and description -->
                    <div class="mb-3 p-3 border rounded" style="background-color: #fff3cd;">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Bonus Action Name</label>
                            <input type="text" class="form-control" name="bonus_action_names[]" 
                                   placeholder="e.g. Nimble Escape, Vile Teleport, Second Wind"
                                   value="<?php echo htmlspecialchars($bonus_action['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="bonus_actions[]" rows="3" 
                                      placeholder="Describe what this bonus action does..."><?php echo htmlspecialchars(is_array($bonus_action) ? ($bonus_action['description'] ?? '') : ($bonus_action ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Shape-Shift. The Monster shape-shifts into a Large Monster-humanoid hybrid or a Large Monster, or it returns to its true humanoid form. Its game statistics, other than its size, are the same in each form. Any equipment it is wearing or carrying isn't transformed."<br>
                                • "Vile Teleport (1/Day). The monster teleports up to 60 feet to an unoccupied space it can see."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_bonus_action" class="btn btn-sm btn-outline-primary">+ Add Bonus Action</button>
            </div>
        </div>

        <!-- ===== REACTIONS ===== -->
                <!--
                    REACTIONS
                    Actions that can be taken in response to triggers (e.g. Parry, Counterspell).
                    The loop below lets you add multiple reactions.
                -->
        <!-- Reactions - triggered responses to other creature actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Reactions</h5>
                <small class="text-muted d-block">Actions that can be taken in response to trigger events. Include reaction limit if applicable (e.g., "can take 3 reactions/turn")</small>
            </div>
            <div class="card-body">
                <?php 
                $reactions = $old['reactions'] ?? [];
                if (is_string($reactions)) {
                    $reactions = json_decode($reactions, true) ?? [];
                }
                if (empty($reactions)) {
                    $reactions = [''];
                }
                ?>
                <?php foreach ($reactions as $index => $reaction): ?>
                                        <!-- Reaction builder: each reaction has a name and description -->
                    <div class="mb-3 p-3 border rounded">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Reaction Name</label>
                            <input type="text" class="form-control" name="reaction_names[]" 
                                   placeholder="e.g. Dread Counterspell, Parry, Fell Rebuke"
                                   value="<?php echo htmlspecialchars($reaction['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Trigger & Effect</label>
                            <textarea class="form-control" name="reactions[]" rows="3" 
                                      placeholder="Describe when this reaction triggers and what it does. Example: 'When a creature the monster can see within 60 feet of it makes an attack roll against it, the monster can use its reaction to impose disadvantage...'"><?php echo htmlspecialchars(is_array($reaction) ? ($reaction['description'] ?? '') : ($reaction ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Parry. Trigger: The monster is hit by a melee attack roll while holding a weapon. Response: The monster adds 6 to its AC against that attack, possibly causing it to miss."<br>
                                • "Counterspell (2/Day). The monster casts Counterspell in response to that spell's trigger, using the same spellcasting ability as Spellcasting."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_reaction" class="btn btn-sm btn-outline-primary">+ Add Reaction</button>
            </div>
        </div>

        <!-- ===== LEGENDARY ACTIONS BUILDER ===== -->
                <!--
                    LEGENDARY ACTIONS (Boss monsters only)
                    Special actions taken at the end of other creatures' turns.
                    Each legendary action has a cost (1, 2, or 3 actions per use).
                    The loop below lets you add multiple legendary actions.
                -->
        <!-- Only visible when "Boss Monster (A5 Sheet)" is selected -->
        <!-- Dynamic builder for legendary actions - actions taken at end of other creature turns -->
        <!-- Each legendary action has a cost: 1, 2, or 3 legendary actions per use -->
        <!-- ===== LEGENDARY ACTIONS ===== -->
            <!-- Legendary actions - for boss monsters only -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Legendary Actions</h5>
                        <small class="text-muted d-block">Special actions taken at the end of other creatures' turns. Each action has a cost in legendary action uses.</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="legendary_action_uses" class="form-label">Legendary Action Uses <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" id="legendary_action_uses" name="legendary_action_uses" 
                               placeholder="e.g. 3, or '3 (4 in Lair)'" 
                               value="<?php echo htmlspecialchars($old['legendary_action_uses'] ?? ''); ?>">
                        <small class="text-muted d-block mt-2">How many legendary action uses does the monster have? Example: '3' or '3 (4 in Lair)'. This will be combined with your actions below.</small>
                    </div>
                    <?php 
                    $legendary_actions = $old['legendary_actions'] ?? [];
                    if (is_string($legendary_actions)) {
                        $legendary_actions = json_decode($legendary_actions, true) ?? [];
                    }
                    if (empty($legendary_actions)) {
                        $legendary_actions = [''];
                    }
                    ?>
                    <?php foreach ($legendary_actions as $index => $legendary_action): ?>
                                                                <!-- Legendary Action builder: each legendary action has a name (with cost) and description -->
                                <div class="mb-3 p-3 border rounded" style="background-color: #ffe6cc;">
                            <div class="mb-2">
                                    <label class="form-label fw-bold">Legendary Action Name & Cost</label>
                                <input type="text" class="form-control" name="legendary_action_names[]" 
                                       placeholder="e.g. Detect (Costs 1 Action), Slam (Costs 2 Actions), Cataclysm (Costs 3 Actions)"
                                       value="<?php echo htmlspecialchars($legendary_action['name'] ?? ''); ?>">
                            </div>
                            <div>
                                    <label class="form-label fw-bold">Description</label>
                                    <textarea class="form-control" name="legendary_actions[]" rows="3" 
                                          placeholder="Describe what this legendary action does. Include any saves, attack rolls, or special effects."><?php echo htmlspecialchars(is_array($legendary_action) ? ($legendary_action['description'] ?? '') : ($legendary_action ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Detect. The monster makes a Wisdom (Perception) check."<br>
                                • "Dread Authority. The monster uses Spellcasting to cast Command. The monster can't take this action again until the start of its next turn."<br>
                                • "Fell Word. Constitution Saving Throw: DC 18, one creature the monster can see within 120 feet. Failure: 17 (5d6) Necrotic damage, and the target's Hit Point maximum decreases by an amount equal to the damage taken. Failure or Success: The monster can't take this action again until the start of its next turn."<br>
                                • "Pounce. The monster moves up to half its Speed, and it makes one Rend attack."
                            </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="add_legendary_action" class="btn btn-sm btn-outline-primary">+ Add Legendary Action</button>
                </div>
            </div>

            <!-- ===== BOSS FEATURES ===== -->
                        <!--
                            BOSS FEATURES (Boss monsters only)
                            Legendary resistance and lair actions for epic monsters.
                        -->
            <!-- Legendary resistance and lair features -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Boss Features</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="legendary_resistance" class="form-label">Legendary Resistance <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="legendary_resistance" name="legendary_resistance" rows="2" 
                                  placeholder="e.g. (3/Day). If the monster fails a saving throw, it can choose to succeed instead."></textarea>
                        <small class="text-muted d-block mt-2">
                            <strong>Examples:</strong><br>
                            • "(3/Day). If the monster fails a saving throw, it can choose to succeed instead."<br>
                            • "(3/Day, or 4/Day in Lair). If the monster fails a saving throw, it can choose to succeed instead."
                        </small>
                    </div>

                    <!-- Lair actions - actions creatures in a lair can take each round -->
                    <div class="mb-3">
                        <label for="lair_actions" class="form-label">Lair Actions <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="lair_actions" name="lair_actions" rows="3" 
                                  placeholder="Describe the lair region effects and actions..."></textarea>
                        <small class="text-muted d-block mt-2">
                            <strong>Examples:</strong><br>
                            • "Beast Spies. Tiny Beasts magically gain the ability to understand the monster's language and can communicate telepathically with the monster while within 1 mile of the lair."<br>
                            • "Poisonous Thicket. Ordinary plants within 1 mile of the lair poison the air. When creatures other than the monster finish a Long Rest, they must succeed on a DC 15 Constitution saving throw or have the Poisoned condition for 1 hour."<br>
                            • "Foul Water. Water sources within 1 mile of the lair are supernaturally fouled. Creatures other than the monster and its allies that drink such water must succeed on a DC 15 Constitution saving throw or have the Poisoned condition for 1 hour."<br>
                            • "Psionic Projection. While in its lair, the monster can cast Project Image, requiring no spell components and using Intelligence as the spellcasting ability (spell save DC 16)."
                        </small>
                    </div>
                </div>
            </div>

        <!-- ===== IMAGES ===== -->
                <!--
                    IMAGES
                    Optional portrait and fullbody images for your monster card.
                -->
        <!-- Optional portrait and fullbody images for card display -->
        <div class="mb-3">
            <label for="image_portrait" class="form-label">Portrait Image <span class="text-muted">(optional)</span></label>
            <input type="file" class="form-control" id="image_portrait" name="image_portrait" accept="image/*">
        </div>
        <div class="mb-3">
            <label for="image_fullbody" class="form-label">Fullbody Image <span class="text-muted">(optional)</span></label>
            <input type="file" class="form-control" id="image_fullbody" name="image_fullbody" accept="image/*">
        </div>

        <!-- ===== SHARING & SUBMISSION ===== -->
                <!--
                    SHARING & SUBMISSION
                    Public/private toggle and the submit button to create your monster.
                -->
        <!-- Public/Private toggle and form submission button -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" checked>
            <label class="form-check-label" for="is_public">Make Monster Public</label>
        </div>
        <button type="submit" class="btn btn-success mb-3">Create Monster</button>
    </form>

    <script>
        // Calculate ability modifier from score
        function calculateModifier(score) {
            return Math.floor((score - 10) / 2);
        }

        // Format modifier with + or - sign
        function formatModifier(modifier) {
            return modifier >= 0 ? '+' + modifier : String(modifier);
        }

        // Update all modifiers and saves when ability scores or proficiency changes
        function updateCalculations() {
            const profBonus = parseInt(document.getElementById('proficiency_bonus').value) || 0;
            const abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
            
            abilities.forEach(ability => {
                const scoreInput = document.querySelector(`[data-ability="${ability}"]`);
                const score = parseInt(scoreInput.value) || 10;
                const modifier = calculateModifier(score);
                
                // Update modifier display
                const modDisplay = document.getElementById(`${ability}-modifier`);
                modDisplay.textContent = `Mod: ${formatModifier(modifier)}`;
                
                // Update save bonus
                const saveCheckbox = document.getElementById(`save_${ability}`);
                const isProficient = saveCheckbox.checked;
                const saveBonus = isProficient ? modifier + profBonus : modifier;
                const saveBonusDisplay = document.getElementById(`save-${ability}-bonus`);
                saveBonusDisplay.textContent = formatModifier(saveBonus);
            });
        }

        // Attach event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Update on ability score change
            document.querySelectorAll('.ability-score').forEach(input => {
                input.addEventListener('input', updateCalculations);
            });

            // Update on proficiency bonus change
            document.getElementById('proficiency_bonus').addEventListener('input', updateCalculations);

            // Update on save proficiency checkbox change
            document.querySelectorAll('.save-proficiency').forEach(checkbox => {
                checkbox.addEventListener('change', updateCalculations);
            });

            // Initial calculation
            updateCalculations();
        });
    </script>
</main>
<?php require_once ROOT . '/src/views/templates/footer.php'; ?>