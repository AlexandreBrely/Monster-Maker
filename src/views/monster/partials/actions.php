<?php
// Partial: Actions section (shared).
// CSS colors: /css/monster-form.css (.color-code-action class)
// JS behavior: /js/monster-form.js (addAction(), toggleCollapse())
// Examples are provided by $actionExamples (HTML string with <br> for line breaks).
?>
<div class="card mb-3">
    <div class="card-header color-code-action">
        <h5 class="mb-0">Actions</h5>
        <small class="d-block">Multiattack, weapon attacks, spellcasting, or special abilities (use Recharge/Cost in the name when relevant).</small>
    </div>
    <div class="card-body">
        <div id="actions-container">
            <!-- JS will inject action items; see /js/monster-form.js -->
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="addAction()">
            Add Action
        </button>
        <?php if (!empty($actionExamples)): ?>
            <small class="text-muted d-block mt-2">
                <strong>Examples:</strong><br>
                <?= $actionExamples ?>
            </small>
        <?php endif; ?>
    </div>
</div>
