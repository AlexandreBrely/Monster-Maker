// Monster Maker - Shared JavaScript for monster creation forms
// This file centralizes all client-side behavior for create_boss and create_small forms.
// Sections below mirror the PHP view partials; see partial comments for cross-references.

// ---------- Ability Modifiers & Saving Throws (shared across forms) ----------
function calculateModifier(score) {
    // D&D 5e ability modifier formula:
    // 1. Subtract 10 from the ability score (e.g., 16 - 10 = 6)
    // 2. Divide by 2 (e.g., 6 / 2 = 3)
    // 3. Round DOWN to nearest whole number using Math.floor()
    //    (e.g., 3.5 becomes 3, not 4)
    const scoreAdjustment = score - 10;
    const divideByTwo = scoreAdjustment / 2;
    const roundedDown = Math.floor(divideByTwo);
    return roundedDown;
}

function formatModifier(modifier) {
    // Display +X / -X
    return modifier >= 0 ? `+${modifier}` : `${modifier}`;
}

function updateCalculations() {
    // GUARD CLAUSE: Check if ability score inputs exist on this page.
    // If NOT found, return early (exit function) to prevent errors on pages that don't need this code.
    // This prevents crashes when updateCalculations() is called on views without ability scores.
    // Example: if a page has 0 ability score inputs, abilityInputs.length will be 0,
    // which is falsy (!0 = true), so we return early and skip the rest of the function.
    const abilityInputs = document.querySelectorAll('.ability-score');
    if (!abilityInputs.length) {
        return; // Exit early if no ability scores found
    }

    const profInput = document.getElementById('proficiency_bonus');
    const profBonus = profInput ? parseInt(profInput.value) || 0 : 0;
    const abilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];

    abilities.forEach((ability) => {
        // Fetch the input bound via data-ability="str|dex|..." (see create.php/create_small.php)
        const scoreInput = document.querySelector(`[data-ability="${ability}"]`);
        if (!scoreInput) return;

        const score = parseInt(scoreInput.value) || 10;
        const modifier = calculateModifier(score);

        // Update modifier display (small text under the input)
        const modDisplay = document.getElementById(`${ability}-modifier`);
        if (modDisplay) {
            modDisplay.textContent = `Mod: ${formatModifier(modifier)}`;
        }

        // Update saving throw bonus when proficiency checkbox is checked
        const saveCheckbox = document.getElementById(`save_${ability}`);
        const saveBonusDisplay = document.getElementById(`save-${ability}-bonus`);
        if (saveCheckbox && saveBonusDisplay) {
            const isProficient = saveCheckbox.checked;
            const saveBonus = isProficient ? modifier + profBonus : modifier;
            saveBonusDisplay.textContent = formatModifier(saveBonus);
        }
    });
}

function attachAbilityListeners() {
    const abilityInputs = document.querySelectorAll('.ability-score');
    const profInput = document.getElementById('proficiency_bonus');
    const saveCheckboxes = document.querySelectorAll('.save-proficiency');

    if (abilityInputs.length) {
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
let actionCounter = 0;
let traitCounter = 0;
let bonusActionCounter = 0;
let reactionCounter = 0;
let legendaryActionCounter = 0;

// Utility: toggle collapse/expand a generated item
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
    const content = element.querySelector('[class*="-content-"]');
    if (!content) {
        return; // Exit if content element doesn't exist
    }
    
    // Check current visibility state: is the content currently hidden?
    // If style.display === 'none', content is hidden, so isHidden = true.
    const isHidden = content.style.display === 'none';
    
    // Toggle: if hidden, show it ('block'). If visible, hide it ('none').
    content.style.display = isHidden ? 'block' : 'none';
}

// Utility: remove an item with confirmation
function removeItem(id) {
    // GUARD CLAUSE: Check if the element exists before trying to remove it.
    // If element not found (e.g., wrong ID), return early to prevent errors.
    const element = document.getElementById(id);
    if (!element) {
        return; // Exit if element doesn't exist
    }
    
    // Ask user for confirmation before permanently removing the item.
    // confirm() returns true if user clicks 'OK', false if 'Cancel'.
    const userConfirmed = confirm('Are you sure you want to remove this item?');
    
    // Only remove if user confirmed
    if (userConfirmed) {
        element.remove(); // Permanently delete the element from the DOM
    }
}

// Utility: update the visible title when the name field loses focus
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
