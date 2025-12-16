<?php
/**
 * Lair Card Display (Horizontal Landscape Format)
 * Shows lair actions and regional effects in a landscape card
 * 
 * For beginners:
 * This view displays a lair card in A6 landscape format.
 * It shows two cards side-by-side:
 * - Front: Lair name, initiative, actions, regional effects
 * - Back: Landscape image of the lair
 * 
 * The view loops through $lairCard['lair_actions'] array
 * to display each action with its name and description.
 */
?>
<?php $extraStyles = ['/css/lair-card.css']; ?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container-fluid mt-4">
    <!-- Action Buttons -->
    <div class="action-buttons-bar">
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['u_id'] == $lairCard['u_id']): ?>
            <a href="index.php?url=lair-card&id=<?php echo $lairCard['lair_id']; ?>&action=edit" 
               class="btn btn-sm btn-warning">
                <i class="fa-solid fa-edit"></i> Edit
            </a>
            <form method="POST" action="index.php?url=lair-card&id=<?php echo $lairCard['lair_id']; ?>&action=delete" 
                  style="display: inline;" onsubmit="return confirm('Delete this lair card?');">
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Lair Card Display: Front & Back -->
    <div class="lair-card-wrapper">
        <!-- FRONT: Lair Actions & Effects -->
        <div class="lair-card-front">
            <!-- Header -->
            <div class="lair-header">
                <div>
                    <h1 class="lair-title"><?php echo htmlspecialchars($lairCard['lair_name']); ?></h1>
                    <p class="lair-subtitle"><?php echo htmlspecialchars($lairCard['monster_name']); ?>'s Lair</p>
                </div>
                <div class="lair-initiative">
                    <span class="init-label">Initiative</span>
                    <span class="init-value"><?php echo (int)$lairCard['lair_initiative']; ?></span>
                </div>
            </div>

            <!-- Description -->
            <?php if (!empty($lairCard['lair_description'])): ?>
                <div class="lair-description">
                    <?php echo nl2br(htmlspecialchars($lairCard['lair_description'])); ?>
                </div>
                <div class="lair-divider"></div>
            <?php endif; ?>

            <!-- Lair Actions -->
            <?php if (!empty($lairCard['lair_actions'])): ?>
                <div class="lair-section">
                    <h2 class="section-title">LAIR ACTIONS</h2>
                    <p class="lair-action-intro">
                        On initiative count <?php echo (int)$lairCard['lair_initiative']; ?> (losing initiative ties), 
                        <?php echo htmlspecialchars($lairCard['monster_name']); ?> can take one lair action to cause one of the following effects:
                    </p>
                    <div class="lair-actions-list">
                        <?php foreach ($lairCard['lair_actions'] as $action): ?>
                            <div class="lair-action-item">
                                <strong class="action-name"><?php echo htmlspecialchars($action['name']); ?>.</strong>
                                <span class="action-desc"><?php echo nl2br(htmlspecialchars($action['description'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Regional Effects -->
            <?php if (!empty($lairCard['regional_effects'])): ?>
                <div class="lair-divider"></div>
                <div class="lair-section">
                    <h2 class="section-title">REGIONAL EFFECTS</h2>
                    <div class="regional-effects-text">
                        <?php echo nl2br(htmlspecialchars($lairCard['regional_effects'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- BACK: Landscape Image -->
        <div class="lair-card-back">
            <?php if (!empty($lairCard['image_back'])): ?>
                <img src="/uploads/lair/<?php echo htmlspecialchars($lairCard['image_back']); ?>"
                     alt="<?php echo htmlspecialchars($lairCard['lair_name']); ?>"
                     class="lair-back-image">
            <?php else: ?>
                <div class="lair-back-placeholder">
                    <i class="fa-solid fa-dungeon"></i>
                    <p>Lair Environment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
