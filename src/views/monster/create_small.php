<?php
/**
 * Small Monster Creation Page
 *
 * This page provides a form for creating a small/simple monster (playing card format).
 * Only the basic fields are shown, no legendary or lair actions.
 */
require_once ROOT . '/src/views/templates/header.php';
require_once ROOT . '/src/views/templates/navbar.php';
?>
<main class="container mt-5">
    <h2 class="mb-4">Create Small Monster</h2>
    <form action="index.php?url=create_small" method="POST" enctype="multipart/form-data">
        <!-- BASIC IDENTIFICATION -->
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
        <!-- DEFENSIVE STATISTICS -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="ac" class="form-label">Armor Class (AC) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="ac" name="ac" min="1" required>
            </div>
            <div class="col-md-4">
                <label for="hp" class="form-label">Hit Points (HP) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="hp" name="hp" min="1" required>
            </div>
            <div class="col-md-4">
                <label for="hit_dice" class="form-label">Hit Dice</label>
                <input type="text" class="form-control" id="hit_dice" name="hit_dice" placeholder="e.g. 4d8+2">
            </div>
        </div>
        <!-- MOVEMENT & ABILITY SCORES -->
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

        <!-- SKILLS, SENSES, LANGUAGES, CR -->
        <div class="mb-3">
            <label for="skills" class="form-label">Skills</label>
            <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="e.g. Stealth +4, Perception +2"></textarea>
        </div>
        <div class="mb-3">
            <label for="senses" class="form-label">Senses</label>
            <textarea class="form-control" id="senses" name="senses" rows="2" placeholder="e.g. Darkvision 60 ft, Passive Perception 12"></textarea>
        </div>
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
        <!-- ACTIONS -->
        <div class="card mb-3">
            <div class="card-header"><h5>Actions</h5></div>
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
                    <div class="mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Action Name</label>
                            <input type="text" class="form-control" name="action_names[]" 
                                   placeholder="e.g. Multiattack, Bite, Shortbow"
                                   value="<?php echo htmlspecialchars($action['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="actions[]" rows="4" 
                                      placeholder="Full description including attack bonuses, damage, saving throws, etc."><?php echo htmlspecialchars(is_array($action) ? ($action['description'] ?? '') : ($action ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2"><strong>Examples:</strong><br>
                            Bite. Melee Attack Roll: +5, reach 5 ft. Hit: 8 (1d10 + 3) Piercing damage. If the target is a Large or smaller creature, it has the Prone condition.<br>
                            Dagger. Melee or Ranged Attack Roll: +4, reach 5 ft. or range 20/60 ft. Hit: 4 (1d4 + 2) Piercing damage.
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_action" class="btn btn-sm btn-outline-primary">+ Add Action</button>
            </div>
        </div>

        <!-- TRAITS -->
        <div class="card mb-3">
            <div class="card-header"><h5>Traits</h5></div>
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
                                   placeholder="e.g. Pack Tactics, Keen Smell"
                                   value="<?php echo htmlspecialchars($trait['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="traits[]" rows="3" 
                                      placeholder="Describe the trait in full detail..."><?php echo htmlspecialchars(is_array($trait) ? ($trait['description'] ?? '') : ($trait ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Nimble Escape. The monster can take the Disengage or Hide action as a bonus action on each of its turns."<br>
                                • "Keen Smell. The monster has Advantage on Wisdom (Perception) checks that rely on smell."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_trait" class="btn btn-sm btn-outline-primary">+ Add Trait</button>
            </div>
        </div>

        <!-- BONUS ACTIONS -->
        <div class="card mb-3">
            <div class="card-header"><h5>Bonus Actions</h5></div>
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
                    <div class="mb-3 p-3 border rounded" style="background-color: #fff3cd;">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Bonus Action Name</label>
                            <input type="text" class="form-control" name="bonus_action_names[]" 
                                   placeholder="e.g. Nimble Escape, Second Wind"
                                   value="<?php echo htmlspecialchars($bonus_action['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="bonus_actions[]" rows="3" 
                                      placeholder="Describe what this bonus action does..."><?php echo htmlspecialchars(is_array($bonus_action) ? ($bonus_action['description'] ?? '') : ($bonus_action ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Monstrous Aid (3/Day). The Monster casts Bless, Dispel Magic, Healing Word, or Lesser Restoration, using the same spellcasting ability as Spellcasting."<br>
                                • "Cunning Action. The Monster takes the Dash, Disengage, or Hide action."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_bonus_action" class="btn btn-sm btn-outline-primary">+ Add Bonus Action</button>
            </div>
        </div>
        <!-- REACTIONS -->
        <div class="card mb-3">
            <div class="card-header"><h5>Reactions</h5></div>
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
                    <div class="mb-3 p-3 border rounded">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Reaction Name</label>
                            <input type="text" class="form-control" name="reaction_names[]" 
                                   placeholder="e.g. Parry, Dodge"
                                   value="<?php echo htmlspecialchars($reaction['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="reactions[]" rows="3" 
                                      placeholder="Describe when this reaction triggers and what it does..."><?php echo htmlspecialchars(is_array($reaction) ? ($reaction['description'] ?? '') : ($reaction ?? '')); ?></textarea>
                            <small class="text-muted d-block mt-2">
                                <strong>Examples:</strong><br>
                                • "Feather Fall (1/Day). The monster casts Feather Fall in response to that spell's trigger, using the same spellcasting ability as Spellcasting."<br>
                                • "Counterspell (2/Day). The monster casts Counterspell in response to that spell's trigger, using the same spellcasting ability as Spellcasting."
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="add_reaction" class="btn btn-sm btn-outline-primary">+ Add Reaction</button>
            </div>
        </div>
        <!-- IMAGES -->
        <div class="mb-3">
            <label for="image_portrait" class="form-label">Portrait Image <span class="text-muted">(optional)</span></label>
            <input type="file" class="form-control" id="image_portrait" name="image_portrait" accept="image/*">
        </div>
        <div class="mb-3">
            <label for="image_fullbody" class="form-label">Fullbody Image <span class="text-muted">(optional)</span></label>
            <input type="file" class="form-control" id="image_fullbody" name="image_fullbody" accept="image/*">
        </div>
        <!-- SHARING & SUBMISSION -->
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
