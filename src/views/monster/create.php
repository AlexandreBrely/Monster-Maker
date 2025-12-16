<?php
/**
 * Boss Monster Creation Page
 * Boss form for A5 sheet monsters (legendary/lair capable). Inline CSS/JS removed; see /css/monster-form.css and /js/monster-form.js.
 */
require_once ROOT . '/src/views/templates/header.php';
require_once ROOT . '/src/views/templates/navbar.php';
?>

<!-- View-only markup; shared CSS/JS are pulled in via the layout. -->
<main class="container mt-5">
    <h2 class="mb-4">Create Boss Monster</h2>
    
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
    
    <form action="index.php?url=create_boss" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="is_legendary" value="1">
        <input type="hidden" name="card_size" value="1">

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
            <div class="col-md-3">
                <label for="ac" class="form-label">Armor Class (AC) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="ac" name="ac" min="1" required>
            </div>
            <div class="col-md-3">
                <label for="ac_notes" class="form-label">AC Notes <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control" id="ac_notes" name="ac_notes" placeholder="e.g. natural armor, plate + shield">
            </div>
            <div class="col-md-3">
                <label for="hp" class="form-label">Hit Points (HP) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="hp" name="hp" min="1" required>
            </div>
            <div class="col-md-3">
                <label for="hit_dice" class="form-label">Hit Dice</label>
                <input type="text" class="form-control" id="hit_dice" name="hit_dice" placeholder="e.g. 10d10 + 40">
            </div>
        </div>

        <!-- MOVEMENT & PROFICIENCY -->
        <div class="row mb-3">
            <div class="col-md-8">
                <label for="speed" class="form-label">Speed <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="speed" name="speed" required placeholder="e.g. 30 ft, fly 60 ft (hover)">
            </div>
            <div class="col-md-4">
                <label for="proficiency_bonus" class="form-label">Proficiency Bonus</label>
                <div class="input-group">
                    <span class="input-group-text">+</span>
                    <input type="text" class="form-control" id="proficiency_bonus" name="proficiency_bonus" placeholder="e.g. 2, 3, 5">
                </div>
            </div>
        </div>

        <!-- ABILITY SCORES -->
        <div class="row mb-3">
            <!-- Each ability score has:
                 - Input field for score (1-30)
                 - Live-calculated modifier display (updated via JavaScript)
                 - Checkbox for saving throw proficiency
                 
                 D&D 5e uses six core abilities: STR, DEX, CON, INT, WIS, CHA
                 Modifier formula: (score - 10) / 2, rounded down
                 Example: STR 16 → (16-10)/2 = 3 → +3 modifier
            -->
            <div class="col-md-2">
                <label for="strength" class="form-label">STR <span class="text-danger">*</span></label>
                <!-- data-ability attribute used by JavaScript to identify which ability -->
                <input type="number" class="form-control ability-score" id="strength" name="strength" min="1" max="30" required data-ability="str">
                <!-- Modifier display updated by monster-form.js when user types -->
                <small class="text-muted modifier-display d-block" id="str-modifier">Mod: +0</small>
                <div class="form-check form-check-sm mt-2">
                    <!-- Saving throw proficiency: checked = monster is proficient in STR saves -->
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
        <!-- Skills: D&D proficiencies like Stealth, Perception, Arcana
             Format: "Skill +modifier" separated by commas -->
        <div class="mb-3">
            <label for="skills" class="form-label">Skills</label>
            <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="e.g. Stealth +9, Perception +6, Arcana +7"></textarea>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <!-- Senses: Special vision types (Darkvision, Blindsight, Truesight, etc.) -->
                <label for="senses" class="form-label">Senses</label>
                <textarea class="form-control" id="senses" name="senses" rows="2" placeholder="e.g. Darkvision 120 ft, Blindsight 30 ft, Passive Perception 20"></textarea>
            </div>
            <div class="col-md-6">
                <!-- Languages: Spoken/understood languages, including telepathy -->
                <label for="languages" class="form-label">Languages</label>
                <textarea class="form-control" id="languages" name="languages" rows="2" placeholder="e.g. Common, Draconic, telepathy 120 ft"></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <!-- Challenge Rating: Difficulty measure (0-30+) with XP reward
                     CR determines XP, proficiency bonus, and encounter balancing -->
                <label for="challenge_rating" class="form-label">Challenge Rating / XP</label>
                <input type="text" class="form-control" id="challenge_rating" name="challenge_rating" placeholder="e.g. 13 (10,000 XP)">
            </div>
            <div class="col-md-6">
                <!-- Legendary Resistance: Boss ability to auto-succeed on failed saves
                     Typically 3/day for legendary creatures -->
                <label for="legendary_resistance" class="form-label">Legendary Resistance <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control" id="legendary_resistance" name="legendary_resistance" placeholder="e.g. 3/day; on fail, succeed instead">
            </div>
        </div>

        <!-- RESISTANCES & IMMUNITIES -->
        <!-- Damage types: fire, cold, lightning, poison, acid, necrotic, radiant, psychic, force, thunder
             Physical: bludgeoning, piercing, slashing (can specify "from nonmagical attacks")
             Resistances: Take half damage
             Immunities: Take no damage
             Vulnerabilities: Take double damage -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="damage_resistances" class="form-label">Damage Resistances</label>
                <textarea class="form-control" id="damage_resistances" name="damage_resistances" rows="2" placeholder="e.g. cold; bludgeoning, piercing, and slashing from nonmagical attacks"></textarea>
            </div>
            <div class="col-md-6">
                <label for="damage_immunities" class="form-label">Damage Immunities</label>
                <textarea class="form-control" id="damage_immunities" name="damage_immunities" rows="2" placeholder="e.g. poison, necrotic"></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="damage_vulnerabilities" class="form-label">Damage Vulnerabilities</label>
                <textarea class="form-control" id="damage_vulnerabilities" name="damage_vulnerabilities" rows="2" placeholder="e.g. radiant"></textarea>
            </div>
            <div class="col-md-6">
                <label for="condition_immunities" class="form-label">Condition Immunities</label>
                <textarea class="form-control" id="condition_immunities" name="condition_immunities" rows="2" placeholder="e.g. charmed, frightened, poisoned"></textarea>
            </div>
        </div>

        <!-- ACTIONS -->
        <?php
            $actionExamples = 'Bite. Melee Weapon Attack: +8 to hit, reach 10 ft., one target. Hit: 18 (2d10 + 7) piercing damage.<br>' .
                              'Tail Swipe (Recharge 5-6). Melee Weapon Attack: +8 to hit, reach 15 ft., one target. Hit: 15 (2d8 + 6) bludgeoning damage; target must succeed on a DC 16 Strength saving throw or be knocked prone.';
            require ROOT . '/src/views/monster/partials/actions.php';
        ?>

        <!-- TRAITS -->
        <?php
            $traitExamples = '- Amphibious. The monster can breathe air and water.<br>' .
                             '- Legendary Resistance (3/Day). If the monster fails a saving throw, it can choose to succeed instead.<br>' .
                             '- Magic Resistance. The monster has advantage on saving throws against spells and other magical effects.';
            require ROOT . '/src/views/monster/partials/traits.php';
        ?>

        <!-- BONUS ACTIONS -->
        <?php
            $bonusActionExamples = '- Monstrous Aid (3/Day). Cast Bless, Dispel Magic, Healing Word, or Lesser Restoration.<br>' .
                                   '- Cunning Action. Take the Dash, Disengage, or Hide action.';
            require ROOT . '/src/views/monster/partials/bonus_actions.php';
        ?>

        <!-- REACTIONS -->
        <?php
            $reactionExamples = '- Feather Fall (1/Day). Cast Feather Fall in response to its trigger.<br>' .
                                '- Counterspell (2/Day). Cast Counterspell in response to its trigger.';
            require ROOT . '/src/views/monster/partials/reactions.php';
        ?>

        <!-- LEGENDARY ACTIONS -->
        <?php
            $legendaryActionUses = '';
            $legendaryExamples = '- Detect (Costs 1 Action). Make a Wisdom (Perception) check.<br>' .
                                 '- Wing Attack (Costs 2 Actions). Creatures within 10 ft. make a DC 19 Dex save or take 13 (2d6 + 6) bludgeoning damage and be knocked prone; then fly up to half flying speed.<br>' .
                                 '- Cataclysmic Roar (Costs 3 Actions). Each enemy within 60 ft. that can hear must succeed on a DC 18 Wis save or be frightened for 1 minute.';
            require ROOT . '/src/views/monster/partials/legendary_actions.php';
        ?>

        <!-- IMAGES -->
        <div class="mb-3">
            <label for="image_fullbody" class="form-label">Card Back Image <span class="text-muted">(optional)</span></label>
            <input type="file" class="form-control" id="image_fullbody" name="image_fullbody" accept="image/*">
            <small class="form-text text-muted">
                Recommended: Portrait-oriented image, minimum 750×1050px (2.5×3.5in at 300dpi). Image will be cropped to fit card dimensions.
            </small>
        </div>

        <!-- SHARING & SUBMISSION -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" checked>
            <label class="form-check-label" for="is_public">Make Monster Public</label>
        </div>
        <button type="submit" class="btn btn-success mb-4">Create Monster</button>
    </form>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
