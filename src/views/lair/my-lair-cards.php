<?php
/**
 * My Lair Cards - User's lair card collection
 */
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Lair Cards</h2>
        <a href="index.php?url=lair-card-create" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Create Lair Card
        </a>
    </div>

    <?php if (empty($lairCards)): ?>
        <div class="alert alert-info">
            <i class="fa-solid fa-info-circle me-2"></i>
            You haven't created any lair cards yet. Lair cards contain lair actions and regional effects for legendary monsters.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($lairCards as $card): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($card['image_back'])): ?>
                            <img src="/uploads/lair/<?php echo htmlspecialchars($card['image_back']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($card['lair_name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fa-solid fa-dungeon fa-3x text-white-50"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($card['lair_name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($card['monster_name']); ?>
                            </p>
                            <p class="card-text">
                                <small>
                                    <strong>Actions:</strong> <?php echo count($card['lair_actions']); ?> | 
                                    <strong>Initiative:</strong> <?php echo (int)$card['lair_initiative']; ?>
                                </small>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <a href="index.php?url=lair-card&id=<?php echo $card['lair_id']; ?>" 
                                   class="btn btn-sm btn-primary">View</a>
                                <a href="index.php?url=lair-card&id=<?php echo $card['lair_id']; ?>&action=edit" 
                                   class="btn btn-sm btn-warning">Edit</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
