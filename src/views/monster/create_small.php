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
    
    <!-- Display validation errors if any -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Errors found:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $field => $message): ?>
                    <li><?php echo htmlspecialchars($message); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <form action="index.php?url=create_small" method="POST" enctype="multipart/form-data">
        <!-- BASIC IDENTIFICATION -->
        <div class="mb-3">
            <label for="name" class="form-label">Monster Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
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
            <div class="card-header color-code-action">
                <h5 class="mb-0">Actions</h5>
                <small class="text-muted d-block">Multiattack, weapon attacks, spellcasting, or special abilities (use Recharge/Cost in the name when relevant).</small>
            </div>
            <div class="card-body">
                <div id="actions-container">
                    <!-- Actions will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addAction()">
                    Add Action
                </button>
                <small class="text-muted d-block mt-2"><strong>Examples:</strong><br>
                Bite. Melee Attack Roll: +5, reach 5 ft. Hit: 8 (1d10 + 3) Piercing damage.<br>
                Dagger. Melee or Ranged Attack Roll: +4, reach 5 ft. or range 20/60 ft. Hit: 4 (1d4 + 2) Piercing damage.
                </small>
            </div>
        </div>

        <!-- TRAITS -->
        <div class="card mb-3">
            <div class="card-header color-code-trait">
                <h5 class="mb-0">Traits</h5>
            </div>
            <div class="card-body">
                <div id="traits-container">
                    <!-- Traits will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addTrait()">
                    Add Trait
                </button>
                <small class="text-muted d-block mt-2">
                    <strong>Examples:</strong><br>
                    • "Nimble Escape. The monster can take the Disengage or Hide action as a bonus action."<br>
                    • "Keen Smell. The monster has Advantage on Wisdom (Perception) checks that rely on smell."
                </small>
            </div>
        </div>

        <!-- BONUS ACTIONS -->
        <div class="card mb-3">
            <div class="card-header color-code-bonus">
                <h5 class="mb-0">Bonus Actions</h5>
            </div>
            <div class="card-body">
                <div id="bonus-actions-container">
                    <!-- Bonus Actions will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addBonusAction()">
                    Add Bonus Action
                </button>
                <small class="text-muted d-block mt-2">
                    <strong>Examples:</strong><br>
                    • "Monstrous Aid (3/Day). The Monster casts Bless, Dispel Magic, Healing Word, or Lesser Restoration."<br>
                    • "Cunning Action. The Monster takes the Dash, Disengage, or Hide action."
                </small>
            </div>
        </div>
        <!-- REACTIONS -->
        <div class="card mb-3">
            <div class="card-header color-code-reaction">
                <h5 class="mb-0">Reactions</h5>
            </div>
            <div class="card-body">
                <div id="reactions-container">
                    <!-- Reactions will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addReaction()">
                    Add Reaction
                </button>
                <small class="text-muted d-block mt-2">
                    <strong>Examples:</strong><br>
                    • "Feather Fall (1/Day). The monster casts Feather Fall in response to that spell's trigger."<br>
                    • "Counterspell (2/Day). The monster casts Counterspell in response to that spell's trigger."
                </small>
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

        // ===== DYNAMIC FORM BUILDER FOR ACTIONS/TRAITS/ETC =====
        let actionCounter = 0;
        let traitCounter = 0;
        let bonusActionCounter = 0;
        let reactionCounter = 0;

        function addAction() {
            const container = document.getElementById('actions-container');
            const id = ++actionCounter;
            const actionHtml = `
                <div class="mb-3 border rounded action-item" id="action-${id}">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa-solid fa-swords me-2"></i><span class="action-title-display">Action ${id}</span></h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('action-${id}')">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('action-${id}')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="action-content-${id}">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Action Name</label>
                                <input type="text" class="form-control" name="action_names[]" placeholder="e.g. Bite, Shortbow" onblur="updateTitle(this, 'action-${id}', 'Action ${id}')">
                            </div>
                            <div>
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" name="actions[]" rows="4" placeholder="Full description with attack bonuses, damage..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', actionHtml);
        }

        function addTrait() {
            const container = document.getElementById('traits-container');
            const id = ++traitCounter;
            const traitHtml = `
                <div class="mb-3 border rounded trait-item" id="trait-${id}">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa-solid fa-star me-2"></i><span class="trait-title-display">Trait ${id}</span></h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('trait-${id}')">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('trait-${id}')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="trait-content-${id}">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Trait Name</label>
                                <input type="text" class="form-control" name="trait_names[]" placeholder="e.g. Pack Tactics" onblur="updateTitle(this, 'trait-${id}', 'Trait ${id}')">
                            </div>
                            <div>
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" name="traits[]" rows="3" placeholder="Describe the trait..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', traitHtml);
        }

        function addBonusAction() {
            const container = document.getElementById('bonus-actions-container');
            const id = ++bonusActionCounter;
            const bonusActionHtml = `
                <div class="mb-3 border rounded bonus-action-item" id="bonus-action-${id}">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa-solid fa-bolt me-2"></i><span class="bonus-action-title-display">Bonus Action ${id}</span></h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('bonus-action-${id}')">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('bonus-action-${id}')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="bonus-action-content-${id}">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Bonus Action Name</label>
                                <input type="text" class="form-control" name="bonus_action_names[]" placeholder="e.g. Nimble Escape" onblur="updateTitle(this, 'bonus-action-${id}', 'Bonus Action ${id}')">
                            </div>
                            <div>
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" name="bonus_actions[]" rows="3" placeholder="What this bonus action does..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', bonusActionHtml);
        }

        function addReaction() {
            const container = document.getElementById('reactions-container');
            const id = ++reactionCounter;
            const reactionHtml = `
                <div class="mb-3 border rounded reaction-item" id="reaction-${id}">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa-solid fa-shield me-2"></i><span class="reaction-title-display">Reaction ${id}</span></h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('reaction-${id}')">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('reaction-${id}')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="reaction-content-${id}">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Reaction Name</label>
                                <input type="text" class="form-control" name="reaction_names[]" placeholder="e.g. Parry" onblur="updateTitle(this, 'reaction-${id}', 'Reaction ${id}')">
                            </div>
                            <div>
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" name="reactions[]" rows="3" placeholder="Trigger and effect..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', reactionHtml);
        }

        function toggleCollapse(id) {
            const element = document.getElementById(id);
            const content = element.querySelector('[class*="-content-"]');
            const icon = element.querySelector('.fa-chevron-up, .fa-chevron-down');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                content.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        function removeItem(id) {
            if (confirm('Are you sure you want to remove this item?')) {
                document.getElementById(id).remove();
            }
        }

        function updateTitle(input, itemId, defaultTitle) {
            const value = input.value.trim();
            const titleDisplay = document.querySelector(`#${itemId} [class*="-title-display"]`);
            if (titleDisplay) {
                titleDisplay.textContent = value || defaultTitle;
            }
        }
    </script>
</main>
<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
