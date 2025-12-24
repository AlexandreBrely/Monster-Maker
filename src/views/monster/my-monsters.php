<?php
// Page: User's personal monsters
?>
<?php $extraStyles = ['/css/monster-card-mini.css']; ?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Monsters</h2>
        <a href="index.php?url=create" class="btn btn-primary">Create New Monster</a>
    </div>
    
    <?php if (empty($monsters)): ?>
        <div class="alert alert-info">
            <i class="fa-solid fa-info-circle me-2"></i>
            You haven't created any monsters yet. Start by creating your first monster!
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($monsters as $monster): ?>
                <div class="col">
                    <?php 
                    $showOwnerBadge = true;
                    $isLiked = in_array($monster['monster_id'], $userLikes ?? []);
                    require __DIR__ . '/../templates/monster-card-mini.php'; 
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Quick Actions Bar -->
        <div class="mt-4 text-center">
            <a href="index.php?url=monsters" class="btn btn-outline-primary">
                <i class="fa-solid fa-globe me-2"></i>Browse All Monsters
            </a>
        </div>
    <?php endif; ?>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
