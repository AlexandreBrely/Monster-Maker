<?php
/**
 * Reusable action buttons bar with optional edit/delete controls.
 * 
 * This template creates a consistent button interface for viewing/editing/deleting
 * cards (monsters, lair cards, etc.). It's included in various view files.
 * 
 * Expected variables from parent view:
 * - $monster: Array with monster/card data (must have 'u_id' and 'name' keys)
 * - $editUrl: URL for editing (e.g., "index.php?url=monster&id=5&action=edit")
 * - $deleteAction: URL for delete form submission
 * - $deleteModalId: (optional) Unique modal ID if multiple delete modals on same page
 * 
 * Permissions:
 * - Print button: Always visible
 * - Edit/Delete buttons: Only shown to owner (user ID matches card's creator ID)
 */

// Check ownership: Can user edit this card?
// isset() checks if variable exists and is not null
// == compares values (user ID in session vs database)
$canEdit = isset($_SESSION['user']) && isset($monster['u_id']) && $_SESSION['user']['u_id'] == $monster['u_id'];

// Default modal ID if not provided by parent view
// ?? is null coalescing operator: use left value if it exists, otherwise use right value
$deleteModalId = $deleteModalId ?? 'deleteModal';
?>

<!-- Action Buttons: Download, Edit, Delete (styled with Bootstrap only) -->
<div class="container my-4">
    <div class="d-flex justify-content-center gap-2 flex-wrap">
        <!-- Download button: Saves card as high-quality image -->
        <!-- JavaScript function will capture card and download as PNG/JPEG -->
        <button onclick="downloadCard()" class="btn btn-sm btn-success">
            <i class="fa-solid fa-download"></i> Download Card
        </button>
        
        <!-- Edit/Delete buttons: Only visible to card owner -->
        <?php if ($canEdit): ?>
            <!-- Edit button: Link to edit form -->
            <a href="<?php echo htmlspecialchars($editUrl); ?>" class="btn btn-sm btn-warning">
                <i class="fa-solid fa-edit"></i> Edit
            </a>
            
            <!-- Delete button: Opens confirmation modal (data-bs-toggle, data-bs-target are Bootstrap attributes) -->
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                data-bs-target="#<?php echo htmlspecialchars($deleteModalId); ?>">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Delete confirmation modal: Only rendered if user is card owner -->
<?php if ($canEdit): ?>
    <!-- Bootstrap modal: hidden by default, shown when delete button clicked -->
    <!-- .fade class provides smooth transition animation -->
    <!-- .no-print class hides modal when printing (see CSS) -->
    <div class="modal fade no-print" id="<?php echo htmlspecialchars($deleteModalId); ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Monster</h5>
                    <!-- Close button: data-bs-dismiss="modal" is Bootstrap attribute for closing -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Confirmation message with card name -->
                    Are you sure you want to delete <strong><?php echo htmlspecialchars($monster['name']); ?></strong>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <!-- Cancel button: Closes modal without action -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    
                    <!-- Delete form: POST request to delete action -->
                    <!-- style="display: inline;" keeps form next to cancel button -->
                    <form method="POST" action="<?php echo htmlspecialchars($deleteAction); ?>" style="display: inline;">
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
