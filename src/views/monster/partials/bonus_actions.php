<?php
// Partial: Bonus Actions section (shared).
// CSS colors: /css/monster-form.css (.color-code-bonus class)
// JS behavior: /js/monster-form.js (addBonusAction()).
// Provide examples via $bonusActionExamples (HTML string).
?>
<div class="card mb-3">
    <div class="card-header color-code-bonus">
        <h5 class="mb-0">Bonus Actions</h5>
        <small class="text-muted d-block">Quick actions taken in addition to the main action (movement, teleports, class-like features).</small>
    </div>
    <div class="card-body">
        <div id="bonus-actions-container">
            <!-- JS will inject bonus action items; see /js/monster-form.js -->
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="addBonusAction()">
            Add Bonus Action
        </button>
        <?php if (!empty($bonusActionExamples)): ?>
            <small class="text-muted d-block mt-2">
                <strong>Examples:</strong><br>
                <?= $bonusActionExamples ?>
            </small>
        <?php endif; ?>
    </div>
</div>
