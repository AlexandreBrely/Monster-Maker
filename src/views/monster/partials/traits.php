<?php
// Partial: Traits section (shared).
// CSS colors: /css/monster-form.css (.color-code-trait class)
// JS behavior: /js/monster-form.js (addTrait()).
// Provide examples via $traitExamples (HTML string).
?>
<div class="card mb-3">
    <div class="card-header color-code-trait">
        <h5 class="mb-0">Traits</h5>
        <small class="text-muted d-block">Passive abilities always on (resistances, senses, immunities, special conditions).</small>
    </div>
    <div class="card-body">
        <div id="traits-container">
            <!-- JS will inject trait items; see /js/monster-form.js -->
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="addTrait()">
            Add Trait
        </button>
        <?php if (!empty($traitExamples)): ?>
            <small class="text-muted d-block mt-2">
                <strong>Examples:</strong><br>
                <?= $traitExamples ?>
            </small>
        <?php endif; ?>
    </div>
</div>
