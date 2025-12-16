<?php
/**
 * Lair Card Mini Preview
 * Expected: $lair (array)
 */
?>
<div class="card lair-mini shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h5 class="mb-1"><?= htmlspecialchars($lair['lair_name'] ?? 'Lair'); ?></h5>
                <div class="text-muted small">Monster: <?= htmlspecialchars($lair['monster_name'] ?? 'Unknown'); ?></div>
            </div>
            <span class="badge bg-dark">Init <?= (int)($lair['lair_initiative'] ?? 20); ?></span>
        </div>

        <?php
        $actions = $lair['lair_actions'] ?? [];
        // Normalize: ensure it's an array of associative arrays
        if (!is_array($actions)) {
            $actions = [];
        }
        $preview = array_slice($actions, 0, 2);
        ?>

        <?php if (!empty($preview)): ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($preview as $action): ?>
                    <li class="mb-1">
                        <strong><?= htmlspecialchars($action['name'] ?? 'Action'); ?>:</strong>
                        <span class="text-muted">
                            <?php
                            $desc = (string)($action['description'] ?? '');
                            $short = mb_substr($desc, 0, 90);
                            echo htmlspecialchars($short . (mb_strlen($desc) > 90 ? '…' : ''));
                            ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted small">No lair actions defined yet.</div>
        <?php endif; ?>
    </div>
</div>
