<?php
/**
 * Reusable action buttons bar with optional edit/delete controls.
 * Expects: $monster, $editUrl, $deleteAction, $deleteModalId
 */

$canEdit = isset($_SESSION['user']) && isset($monster['u_id']) && $_SESSION['user']['u_id'] == $monster['u_id'];
$deleteModalId = $deleteModalId ?? 'deleteModal';
?>

<div class="action-buttons-bar">
    <button onclick="window.print()" class="btn btn-sm btn-primary">
        <i class="fa-solid fa-print"></i> Print
    </button>
    <?php if ($canEdit): ?>
        <a href="<?php echo htmlspecialchars($editUrl); ?>" class="btn btn-sm btn-warning">
            <i class="fa-solid fa-edit"></i> Edit
        </a>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
            data-bs-target="#<?php echo htmlspecialchars($deleteModalId); ?>">
            <i class="fa-solid fa-trash"></i> Delete
        </button>
    <?php endif; ?>
</div>

<?php if ($canEdit): ?>
    <div class="modal fade no-print" id="<?php echo htmlspecialchars($deleteModalId); ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Monster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong><?php echo htmlspecialchars($monster['name']); ?></strong>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="<?php echo htmlspecialchars($deleteAction); ?>" style="display: inline;">
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
