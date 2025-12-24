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

<!-- Action Buttons: Download, Add to Collection, Edit, Delete (styled with Bootstrap only) -->
<div class="container my-4">
    <div class="d-flex justify-content-center gap-2 flex-wrap">
        <!-- Download for Print button: Creates HTML with embedded cards at correct print size -->
        <!-- Users download and open the file, then print without adjustments needed -->
        <button onclick="downloadCardForPrint(event)" class="btn btn-sm btn-success">
            <i class="fa-solid fa-file-pdf"></i> Download for Print
        </button>
        
        <!-- Add to Collection button: Opens modal for selecting/creating collections -->
        <!-- Only shown to logged-in users -->
        <?php if (isset($_SESSION['user'])): ?>
            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#addToCollectionModal">
                <i class="fa-solid fa-folder-plus"></i> Add to Collection
            </button>
        <?php endif; ?>
        
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

<!-- Add to Collection Modal: Only rendered for logged-in users -->
<?php if (isset($_SESSION['user'])): ?>
    <!-- Bootstrap modal: hidden by default, shown when "Add to Collection" button clicked -->
    <!-- .fade class provides smooth transition animation -->
    <div class="modal fade" id="addToCollectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add to Collection</h5>
                    <!-- Close button: data-bs-dismiss="modal" is Bootstrap attribute for closing -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden field: Store monster ID for AJAX submission -->
                    <input type="hidden" id="collectionMonsterId" value="<?php echo (int)$monster['monster_id']; ?>">
                    
                    <!-- Collection selection dropdown: Populated via JavaScript -->
                    <label for="collectionSelect" class="form-label">Select a Collection:</label>
                    <div class="mb-3" id="collectionsLoading">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading collections...</span>
                        </div>
                        <small class="text-muted ms-2">Loading your collections...</small>
                    </div>
                    <select id="collectionSelect" class="form-select d-none" aria-label="Select a collection">
                        <option value="">-- Select a collection --</option>
                    </select>
                    
                    <!-- Alert messages: Shown for success/error feedback -->
                    <div id="collectionAlert" class="alert d-none mt-3" role="alert"></div>
                    
                    <!-- Divider with "or" text -->
                    <div class="my-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 border-top"></div>
                            <span class="mx-3 text-muted small">or</span>
                            <div class="flex-grow-1 border-top"></div>
                        </div>
                    </div>
                    
                    <!-- Create new collection section -->
                    <label for="newCollectionName" class="form-label">Create New Collection:</label>
                    <input 
                        type="text" 
                        class="form-control mb-2" 
                        id="newCollectionName" 
                        placeholder="Collection name (e.g., 'Goblin Encounters')"
                        maxlength="100"
                    >
                    <textarea 
                        class="form-control mb-2" 
                        id="newCollectionDescription" 
                        placeholder="Optional description" 
                        rows="2"
                        maxlength="500"
                    ></textarea>
                </div>
                <div class="modal-footer">
                    <!-- Cancel button: Closes modal without action -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    
                    <!-- Add to Existing Collection button -->
                    <button 
                        type="button" 
                        class="btn btn-primary" 
                        id="addToExistingBtn"
                        onclick="addMonsterToCollection()"
                    >
                        Add to Collection
                    </button>
                    
                    <!-- Create & Add to New Collection button -->
                    <button 
                        type="button" 
                        class="btn btn-success" 
                        id="createAndAddBtn"
                        onclick="createAndAddToCollection()"
                    >
                        Create & Add
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript: Load collection manager when page loads -->
    <script src="/js/collection-manager.js"></script>
<?php endif; ?>
