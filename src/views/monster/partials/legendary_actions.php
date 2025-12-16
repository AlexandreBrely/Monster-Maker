<?php
// Partial: Legendary Actions section (boss form only).
// CSS colors: /css/monster-form.css (.color-code-legendary class)
// JS behavior: /js/monster-form.js (addLegendaryAction()).
// Provide examples via $legendaryExamples (HTML string).
// Provide current uses via $legendaryActionUses (string from controller/view data).
?>
<div class="card mb-3">
    <div class="card-header color-code-legendary">
        <h5 class="mb-0">Legendary Actions</h5>
        <small class="text-muted d-block">Special actions taken at the end of other creatures' turns. Each has a cost in legendary action uses.</small>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="legendary_action_uses" class="form-label">Legendary Action Uses <span class="text-muted">(optional)</span></label>
            <input type="text" class="form-control" id="legendary_action_uses" name="legendary_action_uses"
                   placeholder="e.g. 3, or '3 (4 in Lair)'"
                   value="<?= htmlspecialchars($legendaryActionUses ?? '') ?>">
            <small class="text-muted d-block mt-2">How many uses total? Example: "3" or "3 (4 in Lair)". This pairs with the actions you add below.</small>
        </div>
        <div id="legendary-actions-container">
            <!-- JS will inject legendary action items; see /js/monster-form.js -->
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="addLegendaryAction()">
            Add Legendary Action
        </button>
        <?php if (!empty($legendaryExamples)): ?>
            <small class="text-muted d-block mt-2">
                <strong>Examples:</strong><br>
                <?= $legendaryExamples ?>
            </small>
        <?php endif; ?>
    </div>
</div>
