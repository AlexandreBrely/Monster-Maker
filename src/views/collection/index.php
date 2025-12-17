<?php
/**
 * Collections Index View
 * Displays all collections for the logged-in user
 */
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

    <div class="container mt-4">
        <!-- 
            FOR BEGINNERS - PAGE HEADER:
            d-flex = display flex (flexible box layout)
            justify-content-between = space items apart (title on left, button on right)
            align-items-center = vertically center items
            mb-4 = margin bottom (spacing below header)
        -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-folder2"></i> My Collections</h1>
            <a href="index.php?url=collection-create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Collection
            </a>
        </div>

        <?php 
        /*
         * FOR BEGINNERS - FLASH MESSAGES:
         * Flash messages are one-time notifications stored in session.
         * They show success/error messages after redirects.
         * After displaying, we unset() them so they don't show again.
         */
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
        /*
         * FOR BEGINNERS - EMPTY STATE:
         * empty() checks if array has no items
         * If user has no collections, show helpful message with link to create one
         * This is better UX than showing a blank page
         */
        if (empty($collections)): 
        ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> You don't have any collections yet. 
                <a href="index.php?url=collection-create">Create your first collection</a> to organize your monsters!
            </div>
        <?php else: ?>
            <!-- 
                FOR BEGINNERS - BOOTSTRAP RESPONSIVE GRID:
                row-cols-1 = 1 column on mobile (phones)
                row-cols-md-2 = 2 columns on tablets
                row-cols-lg-3 = 3 columns on desktops
                g-4 = gutter (gap) between cards
                
                This makes the layout adapt to screen size automatically
            -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php 
                /*
                 * FOR BEGINNERS - FOREACH LOOP:
                 * foreach($array as $item) iterates through each element
                 * $collections is an array of collection data from database
                 * $collection is the current item in the loop
                 */
                foreach ($collections as $collection): 
                ?>
                    <div class="col">
                        <!-- 
                            FOR BEGINNERS - BOOTSTRAP CARD:
                            Cards are containers for content (like a physical card)
                            h-100 = height 100% (makes all cards same height)
                        -->
                        <div class="card h-100">
                            <div class="card-body">
                                <!-- 
                                    FOR BEGINNERS - CONDITIONAL BADGES:
                                    if ($collection['is_default'] == 1) → show "Default" badge
                                    is_default is a flag in database (1 = true, 0 = false)
                                    The default collection is auto-created on registration
                                -->
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
                                    <!-- 
                                        FOR BEGINNERS - PLURAL HANDLING:
                                        English grammar: "1 monster" vs "2 monsters"
                                        Ternary operator (? :) is shorthand if/else:
                                        condition ? value_if_true : value_if_false
                                        
                                        != 1 means "not equal to 1"
                                        If count is 0, 2, 3, etc. → add 's'
                                        If count is 1 → don't add 's'
                                    -->
                                    <span class="badge bg-secondary">
                                        <?= $collection['monster_count'] ?> monster<?= $collection['monster_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </p>
                                
                                <!-- 
                                    FOR BEGINNERS - ACTION BUTTONS:
                                    d-flex gap-2 = horizontal layout with 2-unit spacing
                                    btn-sm = small button size
                                    btn-outline-* = outlined (not filled) style
                                -->
                                <div class="d-flex gap-2">
                                    <a href="index.php?url=collection-view&id=<?= $collection['collection_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    
                                    <a href="index.php?url=collection-edit&id=<?= $collection['collection_id'] ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    
                                    <?php 
                                    /*
                                     * FOR BEGINNERS - CONDITIONAL DELETE BUTTON:
                                     * Only show delete button if NOT the default collection
                                     * Default collection (To Print) cannot be deleted
                                     * This is a safety measure - every user needs at least one collection
                                     */
                                    if ($collection['is_default'] == 0): 
                                    ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?= $collection['collection_id'] ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- 
                                FOR BEGINNERS - CARD FOOTER:
                                text-muted = gray text (de-emphasized)
                                Shows when collection was created
                            -->
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="bi bi-calendar"></i> 
                                    Created <?= date('M j, Y', strtotime($collection['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- 
                        FOR BEGINNERS - BOOTSTRAP MODAL (Delete Confirmation):
                        A modal is a popup dialog box that overlays the page
                        Used here to confirm deletion (prevents accidental deletes)
                        
                        data-bs-toggle="modal" on button triggers modal to open
                        data-bs-target="#deleteModal..." specifies which modal
                    -->
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
                                        <!-- 
                                            FOR BEGINNERS - FORM IN MODAL:
                                            We use a form to submit the delete request
                                            POST method for security (not GET)
                                            Hidden input passes collection_id to server
                                        -->
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
