<?php
/**
 * Monster Creation Type Selection Page
 *
 * Lets the user pick between creating a Small Monster (playing card) or a Boss Monster (A5 sheet).
 * Each option has a short description and a placeholder for an example card.
 */
require_once ROOT . '/src/views/templates/header.php';
require_once ROOT . '/src/views/templates/navbar.php';
?>
<main class="container mt-5">
    <div class="text-center mb-5">
        <h2>Choose Your Monster Type</h2>
        <p class="lead text-muted">Pick between a simple creature or an epic boss monster</p>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="card-title">Small Monster</h4>
                    <p class="card-text">A compact statblock for minions, animals, or simple NPCs. Fast to create and easy to use at the table.</p>
                    
                    <!-- Example mini statblock (random public small monster) -->
                    <div class="d-flex justify-content-center mb-3">
                        <?php if (!empty($randomSmall)): ?>
                            <div style="max-width: 320px;">
                                <?php $monster = $randomSmall; require ROOT . '/src/views/templates/monster-card-mini.php'; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-light border rounded p-4 text-center" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                <span class="text-muted">No public small monsters yet</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="index.php?url=create_small" class="btn btn-primary btn-lg w-100">Create Small Monster</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="card-title">Boss Monster</h4>
                    <p class="card-text">A full-featured statblock for legendary monsters, villains, or epic encounters. Includes legendary actions, lair actions, and more.</p>
                    
                    <!-- Example mini statblock (random public boss monster) -->
                    <div class="d-flex justify-content-center mb-3">
                        <?php if (!empty($randomBoss)): ?>
                            <div style="max-width: 320px;">
                                <?php $monster = $randomBoss; require ROOT . '/src/views/templates/monster-card-mini.php'; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-light border rounded p-4 text-center" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                <span class="text-muted">No public boss monsters yet</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="index.php?url=create_boss" class="btn btn-danger btn-lg w-100">Create Boss Monster</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Lair Card CTA: Centered below the two monster cards with mini preview -->
    <div class="row mt-2 mb-4">
        <div class="col-12 d-flex justify-content-center">
            <div class="card shadow-sm" style="max-width: 560px; width: 100%;">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h4 class="card-title mb-0">Lair Card</h4>
                        <small class="text-muted">Landscape lair actions and regional effects</small>
                    </div>

                    <div class="d-flex justify-content-center mb-3">
                        <div style="max-width: 420px; width: 100%;">
                            <?php if (!empty($randomLair)): ?>
                                <?php $lair = $randomLair; require ROOT . '/src/views/templates/lair-card-mini.php'; ?>
                            <?php else: ?>
                                <div class="bg-light border rounded p-4 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted">No lair cards yet</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="index.php?url=lair-card-create" class="btn btn-outline-secondary btn-lg">Create Lair Card</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once ROOT . '/src/views/templates/footer.php'; ?>

