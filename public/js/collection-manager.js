/**
 * Collection Manager - Handle adding monsters to collections
 * 
 * Features:
 * - Load user's collections dynamically via AJAX
 * - Add monster to existing collection
 * - Create new collection and immediately add monster to it
 * - Provides real-time feedback (success/error messages)
 * - Validates user input before submission
 * 
 * Dependencies:
 * - Bootstrap 5+ (for modals)
 * - Requires user to be logged in
 */

/**
 * Load user's collections into the dropdown when modal opens
 * Called automatically when page loads (modal event listeners set up)
 */
async function loadCollections() {
    const select = document.getElementById('collectionSelect');
    const loading = document.getElementById('collectionsLoading');
    const alert = document.getElementById('collectionAlert');
    
    try {
        // Fetch user's collections from server
        const response = await fetch('/api/get-collections.php');
        
        if (!response.ok) {
            throw new Error(`Failed to load collections (HTTP ${response.status})`);
        }
        
        // Gracefully handle non-JSON responses (e.g., HTML error pages)
        let data;
        const raw = await response.text();
        try {
            data = JSON.parse(raw);
        } catch (err) {
            throw new Error('Invalid JSON response from server');
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load collections');
        }
        
        // Clear existing options except the placeholder
        select.innerHTML = '<option value="">-- Select a collection --</option>';
        
        // Add each collection to the dropdown
        data.collections.forEach(collection => {
            const option = document.createElement('option');
            option.value = collection.collection_id;
            option.textContent = `${collection.collection_name} (${collection.monster_count} monsters)`;
            select.appendChild(option);
        });
        
        // Show dropdown, hide loading
        loading.classList.add('d-none');
        select.classList.remove('d-none');
        
    } catch (error) {
        console.error('Error loading collections:', error);
        loading.innerHTML = `<div class="alert alert-danger mb-0">Failed to load collections: ${error.message}</div>`;
    }
}

/**
 * Add monster to existing collection
 * Validates that a collection is selected before submission
 */
async function addMonsterToCollection() {
    const monsterId = document.getElementById('collectionMonsterId').value;
    const collectionId = document.getElementById('collectionSelect').value;
    const alert = document.getElementById('collectionAlert');
    
    // Validate selection
    if (!collectionId) {
        alert.className = 'alert alert-warning d-flex';
        alert.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2"></i> Please select a collection';
        return;
    }
    
    // Show loading state
    const addBtn = document.getElementById('addToExistingBtn');
    const originalText = addBtn.textContent;
    addBtn.disabled = true;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
    
    try {
        // Send AJAX request to add monster to collection
        const response = await fetch('/api/add-to-collection.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                monster_id: monsterId,
                collection_id: collectionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            alert.className = 'alert alert-success d-flex';
            alert.innerHTML = `<i class="fa-solid fa-check-circle me-2"></i> Added to collection!`;
            
            // Close modal after 1.5 seconds
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addToCollectionModal'));
                if (modal) modal.hide();
            }, 1500);
        } else {
            // Show error message
            alert.className = 'alert alert-danger d-flex';
            alert.innerHTML = `<i class="fa-solid fa-exclamation-circle me-2"></i> ${data.message || 'Failed to add to collection'}`;
        }
        
    } catch (error) {
        console.error('Error adding to collection:', error);
        alert.className = 'alert alert-danger d-flex';
        alert.innerHTML = `<i class="fa-solid fa-exclamation-circle me-2"></i> Error: ${error.message}`;
    } finally {
        // Restore button state
        addBtn.disabled = false;
        addBtn.textContent = originalText;
    }
}

/**
 * Create new collection and immediately add monster to it
 * Validates collection name before submission
 */
async function createAndAddToCollection() {
    const monsterId = document.getElementById('collectionMonsterId').value;
    const collectionName = document.getElementById('newCollectionName').value.trim();
    const description = document.getElementById('newCollectionDescription').value.trim();
    const alert = document.getElementById('collectionAlert');
    
    // Validate collection name
    if (!collectionName) {
        alert.className = 'alert alert-warning d-flex';
        alert.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2"></i> Please enter a collection name';
        return;
    }
    
    if (collectionName.length > 100) {
        alert.className = 'alert alert-warning d-flex';
        alert.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-2"></i> Collection name must be 100 characters or less';
        return;
    }
    
    // Show loading state
    const createBtn = document.getElementById('createAndAddBtn');
    const originalText = createBtn.textContent;
    createBtn.disabled = true;
    createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
    
    try {
        // Send AJAX request to create collection and add monster
        const response = await fetch('/api/create-collection-and-add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                monster_id: monsterId,
                collection_name: collectionName,
                description: description || null
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            alert.className = 'alert alert-success d-flex';
            alert.innerHTML = `<i class="fa-solid fa-check-circle me-2"></i> Collection created and monster added!`;
            
            // Close modal after 1.5 seconds
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addToCollectionModal'));
                if (modal) modal.hide();
            }, 1500);
        } else {
            // Show error message (e.g., collection already exists)
            alert.className = 'alert alert-danger d-flex';
            alert.innerHTML = `<i class="fa-solid fa-exclamation-circle me-2"></i> ${data.message || 'Failed to create collection'}`;
        }
        
    } catch (error) {
        console.error('Error creating collection:', error);
        alert.className = 'alert alert-danger d-flex';
        alert.innerHTML = `<i class="fa-solid fa-exclamation-circle me-2"></i> Error: ${error.message}`;
    } finally {
        // Restore button state
        createBtn.disabled = false;
        createBtn.textContent = originalText;
    }
}

/**
 * Initialize collection modal when it's shown
 * Loads collections dynamically each time modal is opened
 */
document.addEventListener('DOMContentLoaded', function() {
    const collectionModal = document.getElementById('addToCollectionModal');
    
    if (collectionModal) {
        collectionModal.addEventListener('show.bs.modal', function() {
            // Reset form fields
            document.getElementById('newCollectionName').value = '';
            document.getElementById('newCollectionDescription').value = '';
            document.getElementById('collectionAlert').className = 'alert d-none';
            document.getElementById('collectionSelect').value = '';
            
            // Load collections fresh each time modal opens
            loadCollections();
        });
    }
});
