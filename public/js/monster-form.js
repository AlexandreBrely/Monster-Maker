// Monster Maker - Shared JavaScript for monster creation forms
// This file centralizes all client-side behavior for create_boss and create_small forms.
// Sections below mirror the PHP view partials; see partial comments for cross-references.

// ---------- Ability Modifiers & Saving Throws (shared across forms) ----------

/**
 * Calculate D&D 5e ability modifier from ability score.
 * 
 * Formula: (score - 10) / 2, rounded DOWN
 * Examples:
 * - Score 16: (16-10)/2 = 3 → Modifier +3
 * - Score 8:  (8-10)/2 = -1 → Modifier -1
 * - Score 10: (10-10)/2 = 0 → Modifier +0
 * 
 * @param {number} score - Ability score (1-30 range typical)
 * @return {number} Ability modifier (-5 to +10 typical range)
 */
function calculateModifier(score) {
    // Math.floor() rounds down to nearest integer
    // Example: Math.floor(3.7) = 3, Math.floor(-0.5) = -1
    const scoreAdjustment = score - 10;
    const divideByTwo = scoreAdjustment / 2;
    const roundedDown = Math.floor(divideByTwo);
    return roundedDown;
}

/**
 * Format modifier for display with +/- sign.
 * 
 * @param {number} modifier - The calculated modifier
 * @return {string} Formatted string like "+3", "-1", or "+0"
 */
function formatModifier(modifier) {
    // Template literal (backticks) allow embedding variables with ${...}
    // Ternary operator: condition ? ifTrue : ifFalse
    return modifier >= 0 ? `+${modifier}` : `${modifier}`;
}

/**
 * Update all ability modifier displays and saving throw bonuses.
 * 
 * This function is called when:
 * - User types in any ability score input
 * - User changes proficiency bonus
 * - User checks/unchecks a saving throw proficiency checkbox
 * 
 * Process:
 * 1. Calculate modifier for each ability score
 * 2. Display modifier below each input
 * 3. Calculate saving throw bonus (modifier + proficiency if checked)
 * 4. Update saving throw display
 */
function updateCalculations() {
    // GUARD CLAUSE: Check if ability score inputs exist on this page.
    // If NOT found, return early (exit function) to prevent errors on pages that don't need this code.
    // This prevents crashes when updateCalculations() is called on views without ability scores.
    // 
    // document.querySelectorAll() returns NodeList (array-like) of matching elements
    // .length property gives count of matching elements
    // !abilityInputs.length is true when length is 0 (falsy value)
    const abilityInputs = document.querySelectorAll('.ability-score');
    if (!abilityInputs.length) {
        return; // Exit early if no ability scores found (e.g., on login page)
    }

    // Get proficiency bonus (default to 0 if not found or empty)
    const profInput = document.getElementById('proficiency_bonus');
    // parseInt() converts string to integer, || 0 provides fallback if NaN
    const profBonus = profInput ? parseInt(profInput.value) || 0 : 0;
    
    // Array of ability abbreviations (matches data-ability attributes in HTML)
    const abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];

    // forEach() executes function for each array element
    abilities.forEach((ability) => {
        // querySelector() finds first element matching CSS selector
        // Template literal with ${ability} inserts variable into string
        const scoreInput = document.querySelector(`[data-ability="${ability}"]`);
        if (!scoreInput) return; // Skip if input not found

        // Parse ability score, default to 10 if empty/invalid
        const score = parseInt(scoreInput.value) || 10;
        const modifier = calculateModifier(score);

        // Update modifier display (small text under input)
        const modDisplay = document.getElementById(`${ability}-modifier`);
        if (modDisplay) {
            // .textContent sets text without HTML parsing (safer than .innerHTML)
            modDisplay.textContent = `Mod: ${formatModifier(modifier)}`;
        }

        // Update saving throw bonus when proficiency checkbox is checked
        const saveCheckbox = document.getElementById(`save_${ability}`);
        const saveBonusDisplay = document.getElementById(`save-${ability}-bonus`);
        if (saveCheckbox && saveBonusDisplay) {
            // .checked is boolean property (true if checkbox checked)
            const isProficient = saveCheckbox.checked;
            // If proficient: add proficiency bonus to modifier
            // Example: DEX modifier +3, prof +2 → saving throw +5
            const saveBonus = isProficient ? modifier + profBonus : modifier;
            saveBonusDisplay.textContent = formatModifier(saveBonus);
        }
    });
}

/**
 * Attach event listeners to ability score inputs and proficiency bonus.
 * 
 * Event types:
 * - 'input': Fires every time value changes (while typing)
 * - 'change': Fires when focus leaves field after changes
 * 
 * addEventListener() registers a function to run when event occurs
 */
function attachAbilityListeners() {
    const abilityInputs = document.querySelectorAll('.ability-score');
    const profInput = document.getElementById('proficiency_bonus');
    const saveCheckboxes = document.querySelectorAll('.save-proficiency');

    if (abilityInputs.length) {
        // forEach() iterates over each input in NodeList
        abilityInputs.forEach((input) => input.addEventListener('input', updateCalculations));
    }
    if (profInput) {
        profInput.addEventListener('input', updateCalculations);
    }
    if (saveCheckboxes.length) {
        saveCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCalculations));
    }
}

// ---------- Dynamic section builders (actions, traits, bonus actions, reactions, legendary) ----------

// Counters ensure unique IDs for dynamically-added elements
// Incremented each time user clicks "Add Action", "Add Trait", etc.
let actionCounter = 0;
let traitCounter = 0;
let bonusActionCounter = 0;
let reactionCounter = 0;
let legendaryActionCounter = 0;

/**
 * Toggle collapse/expand state of a dynamic entry (action, trait, etc.).
 * 
 * Bootstrap 5 uses .collapse class for show/hide behavior
 * - .collapse = hidden
 * - .collapse.show = visible
 * 
 * @param {string} id - The ID of the container div to toggle
 */
function toggleCollapse(id) {
    // GUARD CLAUSE #1: Check if the container element exists by ID.
    // If element not found, return early to prevent errors when manipulating null/undefined.
    const element = document.getElementById(id);
    if (!element) {
        return; // Exit if container doesn't exist
    }
    
    // GUARD CLAUSE #2: Check if the content child element exists.
    // We look for any class ending in "-content-" (e.g., 'action-content-1').
    // If not found, return early since we can't toggle what doesn't exist.
    // querySelector() returns first match or null
    const content = element.querySelector('[class*="-content-"]');
    if (!content) {
        return; // Exit if content element doesn't exist
    }
    
    // Check current visibility state: is the content currently hidden?
    // If style.display === 'none', content is hidden, so isHidden = true.
    const isHidden = content.style.display === 'none';
    
    // Toggle: if hidden, show it ('block'). If visible, hide it ('none').
    // Ternary operator: condition ? valueIfTrue : valueIfFalse
    content.style.display = isHidden ? 'block' : 'none';
}

/**
 * Remove a dynamically-added item after user confirmation.
 * 
 * Used for: Actions, Traits, Bonus Actions, Reactions, Legendary Actions
 * 
 * @param {string} id - The ID of the container div to remove
 */
function removeItem(id) {
    // GUARD CLAUSE: Check if the element exists before trying to remove it.
    // If element not found (e.g., wrong ID), return early to prevent errors.
    const element = document.getElementById(id);
    if (!element) {
        return; // Exit if element doesn't exist
    }
    
    // Ask user for confirmation before permanently removing the item.
    // confirm() displays browser dialog with OK/Cancel buttons
    // Returns true if user clicks 'OK', false if 'Cancel'
    const userConfirmed = confirm('Are you sure you want to remove this item?');
    
    // Only remove if user confirmed
    if (userConfirmed) {
        // .remove() permanently deletes the element from the DOM (Document Object Model)
        // Cannot be undone (unless user refreshes page)
        element.remove();
    }
}

/**
 * Update the title display when user enters a name in an input field.
 * 
 * This provides visual feedback: when user types "Fireball" in action name,
 * the collapsed header shows "Fireball" instead of "Action 1"
 * 
 * @param {HTMLElement} input - The input field containing the name
 * @param {string} itemId - The ID of the container div
 * @param {string} defaultTitle - Fallback title if input is empty (e.g., "Action 1")
 */
function updateTitle(input, itemId, defaultTitle) {
    // GUARD CLAUSE: Try to find the title display element.
    // We look for any element with a class ending in "-title-display" inside the container.
    // If not found, return early to prevent errors.
    const titleDisplay = document.querySelector(`#${itemId} [class*="-title-display"]`);
    if (!titleDisplay) {
        return; // Exit if title display element doesn't exist
    }
    
    // Get the input field's current value and trim whitespace.
    // If input is empty, value becomes empty string ''.
    const value = (input.value || '').trim();
    
    // Update the title text:
    // If user entered text (value is not empty), use that.
    // Otherwise, use the defaultTitle (e.g., 'Action 1', 'Trait 1').
    titleDisplay.textContent = value || defaultTitle;
}

// Action builder
function addAction() {
    const container = document.getElementById('actions-container');
    if (!container) return;
    const id = ++actionCounter;
    const actionHtml = `
        <div class="mb-3 border rounded action-item" id="action-${id}">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-action">
                    <h6 class="mb-0"><span class="action-title-display">Action ${id}</span></h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('action-${id}')"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('action-${id}')"></button>
                    </div>
                </div>
                <div class="action-content-${id}">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Action Name</label>
                        <input type="text" class="form-control" name="action_names[]" placeholder="e.g. Multiattack, Poison Breath" onblur="updateTitle(this, 'action-${id}', 'Action ${id}')">
                        <small class="text-muted">Tip: include Recharge/Cost in the name if relevant.</small>
                    </div>
                    <div>
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="actions[]" rows="4" placeholder="Attack bonus, damage dice, save DC, area, special effects..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', actionHtml);
}

// Trait builder
function addTrait() {
    const container = document.getElementById('traits-container');
    if (!container) return;
    const id = ++traitCounter;
    const traitHtml = `
        <div class="mb-3 border rounded trait-item" id="trait-${id}">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-trait">
                    <h6 class="mb-0"><span class="trait-title-display">Trait ${id}</span></h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('trait-${id}')"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('trait-${id}')"></button>
                    </div>
                </div>
                <div class="trait-content-${id}">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Trait Name</label>
                        <input type="text" class="form-control" name="trait_names[]" placeholder="e.g. Legendary Resistance, Keen Smell" onblur="updateTitle(this, 'trait-${id}', 'Trait ${id}')">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="traits[]" rows="3" placeholder="Passive effect, conditions, advantage/disadvantage..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', traitHtml);
}

// Bonus Action builder
function addBonusAction() {
    const container = document.getElementById('bonus-actions-container');
    if (!container) return;
    const id = ++bonusActionCounter;
    const bonusActionHtml = `
        <div class="mb-3 border rounded bonus-action-item" id="bonus-action-${id}">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-bonus">
                    <h6 class="mb-0"><span class="bonus-action-title-display">Bonus Action ${id}</span></h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('bonus-action-${id}')"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('bonus-action-${id}')"></button>
                    </div>
                </div>
                <div class="bonus-action-content-${id}">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Bonus Action Name</label>
                        <input type="text" class="form-control" name="bonus_action_names[]" placeholder="e.g. Nimble Escape" onblur="updateTitle(this, 'bonus-action-${id}', 'Bonus Action ${id}')">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="bonus_actions[]" rows="3" placeholder="What the bonus action does and limits (per day, recharge, etc.)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', bonusActionHtml);
}

// Reaction builder
function addReaction() {
    const container = document.getElementById('reactions-container');
    if (!container) return;
    const id = ++reactionCounter;
    const reactionHtml = `
        <div class="mb-3 border rounded reaction-item" id="reaction-${id}">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-reaction">
                    <h6 class="mb-0"><span class="reaction-title-display">Reaction ${id}</span></h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('reaction-${id}')"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('reaction-${id}')"></button>
                    </div>
                </div>
                <div class="reaction-content-${id}">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Reaction Name</label>
                        <input type="text" class="form-control" name="reaction_names[]" placeholder="e.g. Parry, Counterspell" onblur="updateTitle(this, 'reaction-${id}', 'Reaction ${id}')">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="reactions[]" rows="3" placeholder="Trigger + effect (e.g., when hit by melee, add AC)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', reactionHtml);
}

// Legendary Action builder (boss form only)
function addLegendaryAction() {
    const container = document.getElementById('legendary-actions-container');
    if (!container) return;
    const id = ++legendaryActionCounter;
    const legendaryActionHtml = `
        <div class="mb-3 border rounded legendary-action-item" id="legendary-action-${id}">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 section-header-button color-code-legendary">
                    <h6 class="mb-0"><span class="legendary-action-title-display">Legendary Action ${id}</span></h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCollapse('legendary-action-${id}')"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('legendary-action-${id}')"></button>
                    </div>
                </div>
                <div class="legendary-action-content-${id}">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Legendary Action Name & Cost</label>
                        <input type="text" class="form-control" name="legendary_action_names[]" placeholder="e.g. Detect (Costs 1 Action)" onblur="updateTitle(this, 'legendary-action-${id}', 'Legendary Action ${id}')">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="legendary_actions[]" rows="3" placeholder="What happens, save DC/attack, and recharge rules..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', legendaryActionHtml);
}

// ---------- Initialization ----------
document.addEventListener('DOMContentLoaded', () => {
    attachAbilityListeners();
    updateCalculations(); // Initial pass so modifiers/save bonuses display correctly
    // Note: dynamic sections are user-driven; no auto-seeding to keep forms clean at load.
});
