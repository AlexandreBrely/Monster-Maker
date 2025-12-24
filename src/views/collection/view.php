<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

    <div class="container my-5">
        <!-- Collection Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?url=collections">Collections</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($collection['collection_name']) ?></li>
                    </ol>
                </nav>
                
                <h1 class="mb-2">
                    <?php if ($collection['is_default']): ?>
                        <i class="bi bi-printer text-primary"></i>
                    <?php else: ?>
                        <i class="bi bi-folder text-secondary"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($collection['collection_name']) ?>
                </h1>
                
                <?php if (!empty($collection['description'])): ?>
                    <p class="text-muted"><?= htmlspecialchars($collection['description']) ?></p>
                <?php endif; ?>
                
                <p class="text-muted">
                    <span class="badge bg-secondary"><?= count($monsters) ?> monster<?= count($monsters) != 1 ? 's' : '' ?></span>
                </p>
            </div>
            
            <div class="btn-group" role="group">
                <?php if (!$collection['is_default']): ?>
                    <a href="index.php?url=collection-edit&id=<?= $collection['collection_id'] ?>" 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                <?php endif; ?>
                
                <!-- Share/Unshare Button -->
                <?php 
                    $hasShareToken = !empty($collection['share_token']);
                    $shareButtonClass = $hasShareToken ? 'btn-warning' : 'btn-outline-success';
                    $shareButtonText = $hasShareToken ? '<i class="bi bi-link-45deg"></i> Sharing' : '<i class="bi bi-share"></i> Share';
                ?>
                <button type="button" 
                        class="btn <?= $shareButtonClass ?>"
                        onclick="toggleShareCollection(<?= $collection['collection_id'] ?>)"
                        title="<?= $hasShareToken ? 'Stop sharing' : 'Share via link' ?>">
                    <?= $shareButtonText ?>
                </button>
                
                <a href="index.php?url=collections" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Collections
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (empty($monsters)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This collection is empty. 
                <a href="index.php?url=monsters">Browse monsters</a> and add them to this collection!
            </div>
        <?php else: ?>
            <!-- Monsters Grid -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($monsters as $monster): ?>
                    <div class="col">
                        <?php 
                        // Check if current user has liked this monster
                        $isLiked = in_array($monster['monster_id'], $userLikes ?? []);
                        include dirname(__DIR__) . '/templates/monster-card-mini.php'; 
                        ?>
                        
                        <!-- Remove Button Overlay for Collection View -->
                        <button type="button" 
                                class="btn btn-sm btn-danger mt-2 w-100 remove-monster-btn"
                                data-monster-id="<?= $monster['monster_id'] ?>"
                                data-monster-name="<?= htmlspecialchars($monster['name']) ?>"
                                title="Remove from collection">
                            <i class="bi bi-x-circle"></i> Remove from Collection
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Share Link Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="shareContent" style="display: none;">
                        <p>Your collection can be shared via this link:</p>
                        <div class="input-group mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="shareLink" 
                                   readonly>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="copyShareLink()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <p class="small text-muted">
                            Share this link on Reddit, Discord, or any platform! Anyone with the link can view your collection.
                        </p>
                    </div>
                    <div id="loadingContent">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2">Generating share link...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($hasShareToken): ?>
                        <button type="button" class="btn btn-danger" onclick="unshareCollection(<?= $collection['collection_id'] ?>)">
                            <i class="bi bi-x-circle"></i> Stop Sharing
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Toggle collection sharing (share/unshare)
         * 
         * If collection has share token: Show share link modal
         * If collection doesn't have token: Generate one and show modal
         * 
         * @param {number} collectionId - The collection ID
         */
        async function toggleShareCollection(collectionId) {
            const modal = new bootstrap.Modal(document.getElementById('shareModal'));
            const shareContent = document.getElementById('shareContent');
            const loadingContent = document.getElementById('loadingContent');
            
            // Show modal
            modal.show();
            
            // Check if already has share token
            <?php if ($hasShareToken): ?>
                // Already shared - show link immediately
                const shareUrl = '<?php 
                    $baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
                    echo $baseUrl . '/?url=collection-public&token=' . htmlspecialchars($collection['share_token']);
                ?>';
                document.getElementById('shareLink').value = shareUrl;
                
                loadingContent.style.display = 'none';
                shareContent.style.display = 'block';
            <?php else: ?>
                // Not shared yet - generate token
                try {
                    const response = await fetch('index.php?url=collection-share', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `collection_id=${collectionId}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        document.getElementById('shareLink').value = result.share_url;
                        loadingContent.style.display = 'none';
                        shareContent.style.display = 'block';
                    } else {
                        alert(result.message || 'Failed to generate share link');
                        modal.hide();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    modal.hide();
                }
            <?php endif; ?>
        }
        
        /**
         * Copy share link to clipboard with visual feedback
         * 
         * Uses modern Clipboard API with fallback messaging
         * Shows temporary success message to user
         */
        function copyShareLink() {
            const shareLink = document.getElementById('shareLink');
            
            navigator.clipboard.writeText(shareLink.value).then(() => {
                // Show success feedback
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                
                button.innerHTML = '<i class="bi bi-check-circle"></i> Copied!';
                button.classList.add('btn-success');
                button.classList.remove('btn-outline-secondary');
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy link. Please copy manually.');
            });
        }
        
        /**
         * Stop sharing collection by revoking share token
         * 
         * After confirmation, removes share token and reloads page
         * 
         * @param {number} collectionId - The collection ID
         */
        async function unshareCollection(collectionId) {
            if (!confirm('Stop sharing this collection? The share link will no longer work.')) {
                return;
            }
            
            try {
                const response = await fetch('index.php?url=collection-unshare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `collection_id=${collectionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to stop sharing');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }
        
        // Remove monster from collection
        document.querySelectorAll('.remove-monster-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const monsterId = this.dataset.monsterId;
                const monsterName = this.dataset.monsterName;
                
                if (!confirm(`Remove ${monsterName} from this collection?`)) {
                    return;
                }
                
                try {
                    const response = await fetch('index.php?url=collection-remove-monster', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `collection_id=<?= $collection['collection_id'] ?>&monster_id=${monsterId}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Reload page to update the list
                        location.reload();
                    } else {
                        alert(result.message || 'Failed to remove monster');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                }
            });
        });
    </script>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
