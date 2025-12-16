<?php
// Partial: Reactions section (shared).
// CSS colors: /css/monster-form.css (.color-code-reaction class)
// JS behavior: /js/monster-form.js (addReaction()).
// Provide examples via $reactionExamples (HTML string).
?>
<div class="card mb-3">
    <div class="card-header color-code-reaction">
        <h5 class="mb-0">Reactions</h5>
        <small class="text-muted d-block">Immediate responses to triggers during the monster's turn or others' turns.</small>
    </div>
    <div class="card-body">
        <div id="reactions-container">
            <!-- JS will inject reaction items; see /js/monster-form.js -->
        </div>
        <button type="button" class="btn btn-sm btn-primary" onclick="addReaction()">
            Add Reaction
        </button>
        <?php if (!empty($reactionExamples)): ?>
            <small class="text-muted d-block mt-2">
                <strong>Examples:</strong><br>
                <?= $reactionExamples ?>
            </small>
        <?php endif; ?>
    </div>
</div>
