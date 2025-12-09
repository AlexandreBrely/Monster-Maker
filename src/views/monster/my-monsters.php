<?php
// Page: User's personal monsters
?>
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
        <div class="row">
            <?php foreach ($monsters as $monster): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($monster['image_portrait'])): ?>
                            <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_portrait']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($monster['name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fa-solid fa-dragon fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($monster['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($monster['size']); ?> 
                                <?php echo htmlspecialchars($monster['type']); ?>
                            </p>
                            <p class="card-text">
                                <small>
                                    <strong>CR:</strong> <?php echo htmlspecialchars($monster['challenge_rating']); ?> | 
                                    <strong>AC:</strong> <?php echo (int)$monster['ac']; ?> | 
                                    <strong>HP:</strong> <?php echo (int)$monster['hp']; ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <span class="badge <?php echo $monster['is_public'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo $monster['is_public'] ? 'Public' : 'Private'; ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>" 
                                   class="btn btn-sm btn-primary">View</a>
                                <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>&action=edit" 
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
