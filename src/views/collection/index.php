<?php
/**
 * Collections Index View
 * Displays all collections for the logged-in user
 */
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Header: d-flex, justify-content-between, align-items-center, mb-4 -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-folder2"></i> My Collections</h1>
            <a href="index.php?url=collection-create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Collection
            </a>
        </div>

        <?php 
        // Flash messages (success after redirect)
        if (isset($_SESSION['success'])): 
        ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php 
        // Empty state if no collections
        if (empty($collections)): 
        ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> You don't have any collections yet. 
                <a href="index.php?url=collection-create">Create your first collection</a> to organize your monsters!
            </div>
        <?php else: ?>
            <!-- Responsive grid: row-cols-1 / row-cols-md-2 / row-cols-lg-3 / g-4 -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($collections as $collection): ?>
                    <div class="col">
                        <!-- Card (h-100 = equal height) -->
                        <div class="card h-100">
                            <div class="card-body">
                                <!-- Default badge shown when is_default = 1 -->
                                <h5 class="card-title">
                                    <i class="bi bi-folder2"></i>
                                    <?= htmlspecialchars($collection['collection_name']) ?>
                                    <?php if ($collection['is_default'] == 1): ?>
                                        <span class="badge bg-primary">Default</span>
                                    <?php endif; ?>
                                </h5>
                                
                                <?php if (!empty($collection['description'])): ?>
                                    <p class="card-text text-muted">
                                        <?= htmlspecialchars($collection['description']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="card-text">
                                    <span class="badge bg-secondary">
                                        <?= $collection['monster_count'] ?> monster<?= $collection['monster_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </p>
                                
                                <!-- Actions: d-flex gap-2, btn-sm -->
                                <div class="d-flex gap-2">
                                    <a href="index.php?url=collection-view&id=<?= $collection['collection_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    
                                    <a href="index.php?url=collection-edit&id=<?= $collection['collection_id'] ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    
                                    <?php if ($collection['is_default'] == 0): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?= $collection['collection_id'] ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Card footer with created timestamp -->
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="bi bi-calendar"></i> 
                                    Created <?= date('M j, Y', strtotime($collection['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Delete confirmation modal (Bootstrap) -->
                    <?php if ($collection['is_default'] == 0): ?>
                        <div class="modal fade" id="deleteModal<?= $collection['collection_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Delete</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete <strong><?= htmlspecialchars($collection['collection_name']) ?></strong>?</p>
                                        <p class="text-danger">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            This action cannot be undone. The monsters will not be deleted, only the collection.
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <!-- Delete form (POST) with hidden collection_id -->
                                        <form method="POST" action="index.php?url=collection-delete" class="d-inline">
                                            <input type="hidden" name="collection_id" value="<?= $collection['collection_id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
