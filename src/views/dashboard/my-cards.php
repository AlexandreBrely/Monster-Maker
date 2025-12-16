<?php
/**
 * My Cards - Combined Dashboard
 * Shows user's Monsters (left) and Lair Cards (right) in two Bootstrap columns.
 */
require_once ROOT . '/src/views/templates/header.php';
require_once ROOT . '/src/views/templates/navbar.php';
?>
<main class="container mt-5">
    <div class="text-center mb-4">
        <h2>My Cards</h2>
        <p class="text-muted">Your monsters and lair action cards in one place.</p>
    </div>

    <div class="row g-4">
        <!-- My Monsters Column -->
        <div class="col-12 col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Monsters</h4>
                <a href="index.php?url=my-monsters" class="btn btn-sm btn-outline-primary">View all</a>
            </div>

            <?php if (!empty($monsters)): ?>
                <div class="row g-3">
                    <?php foreach ($monsters as $monster): ?>
                        <div class="col-12">
                            <?php require ROOT . '/src/views/templates/monster-card-mini.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">You have no monsters yet. Create your first one!</div>
                <a href="index.php?url=create_select" class="btn btn-primary">Create Monster</a>
            <?php endif; ?>
        </div>

        <!-- My Lair Cards Column -->
        <div class="col-12 col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Lair Cards</h4>
                <a href="index.php?url=my-lair-cards" class="btn btn-sm btn-outline-secondary">View all</a>
            </div>

            <?php if (!empty($lairCards)): ?>
                <div class="list-group">
                    <?php foreach ($lairCards as $lair): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                           href="index.php?url=lair-card&id=<?= (int)$lair['lair_id']; ?>">
                            <div>
                                <strong><?= htmlspecialchars($lair['lair_name']); ?></strong>
                                <div class="text-muted small">Monster: <?= htmlspecialchars($lair['monster_name']); ?></div>
                            </div>
                            <span class="badge bg-dark">Init <?= (int)$lair['lair_initiative']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">You have no lair cards yet.</div>
                <a href="index.php?url=lair-card-create" class="btn btn-outline-secondary">Create Lair Card</a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
