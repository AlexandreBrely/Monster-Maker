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
                    <h4 class="card-title">🐺 Small Monster</h4>
                    <p class="card-text">A compact statblock for minions, animals, or simple NPCs. Fast to create and easy to use at the table.</p>
                    
                    <!-- Example card placeholder -->
                    <div class="bg-light border rounded p-4 text-center mb-3" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                        <span class="text-muted">[Small Monster Example Coming Soon]</span>
                    </div>
                    
                    <a href="index.php?url=create_small" class="btn btn-primary btn-lg w-100">Create Small Monster</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="card-title">🐉 Boss Monster</h4>
                    <p class="card-text">A full-featured statblock for legendary monsters, villains, or epic encounters. Includes legendary actions, lair actions, and more.</p>
                    
                    <!-- Example card placeholder -->
                    <div class="bg-light border rounded p-4 text-center mb-3" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                        <span class="text-muted">[Boss Monster Example Coming Soon]</span>
                    </div>
                    
                    <a href="index.php?url=create_boss" class="btn btn-danger btn-lg w-100">Create Boss Monster</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once ROOT . '/src/views/templates/footer.php'; ?>

