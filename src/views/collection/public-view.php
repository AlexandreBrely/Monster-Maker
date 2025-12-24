<?php
/**
 * Public Collection View - Read-Only Template
 * 
 * Displays a shared collection that was made public via share token.
 * This view is accessible to anyone with the share link, regardless of authentication.
 * 
 * Variables:
 * - $collection: Collection data (id, name, description, share_token, user_id, created_at)
 * - $creator: Creator user information (u_id, username, email)
 * - $monsters: Array of monster objects in this collection
 * - $token: Share token from URL parameter
 * 
 * Features:
 * - Display collection metadata (name, description, creator)
 * - Grid layout of monster cards (same as collection/view.php but read-only)
 * - Copy-to-clipboard functionality for share link
 * - "View Monster Details" links to monster profiles
 * 
 * Security Notes:
 * - No edit or delete buttons are shown
 * - Add to collection buttons show login prompt if not authenticated
 * - Creator info is limited to username only (no email)
 */
?>

<?php include dirname(__DIR__) . '/templates/header.php'; ?>
<?php include dirname(__DIR__) . '/templates/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <!-- Collection Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-2"><?php echo htmlspecialchars($collection['name']); ?></h1>
            <p class="text-muted">
                <small>Shared by <strong><?php echo htmlspecialchars($creator['username'] ?? 'Unknown User'); ?></strong></small>
            </p>
            
            <?php if (!empty($collection['description'])): ?>
                <p class="lead"><?php echo htmlspecialchars($collection['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Share Link Display & Copy Button -->
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Share This Collection</h6>
                    <div class="input-group mb-2">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               id="shareLink" 
                               value="<?php echo htmlspecialchars($shareUrl); ?>" 
                               readonly>
                        <button class="btn btn-outline-secondary btn-sm" 
                                type="button" 
                                onclick="copyShareLink()"
                                title="Copy link to clipboard">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <small class="text-muted">Anyone with this link can view this collection.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monster Cards Grid -->
    <div class="row">
        <?php if (empty($monsters)): ?>
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i> This collection is empty.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($monsters as $monster): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <?php 
                        $isLiked = in_array($monster['monster_id'], $userLikes ?? []);
                        include dirname(__DIR__) . '/templates/monster-card-mini.php'; 
                    ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Copy to Clipboard JavaScript -->
<script>
/**
 * Copy the share link to clipboard and show feedback
 * 
 * Uses modern Clipboard API with fallback for older browsers.
 * Shows a temporary toast message to confirm the copy.
 */
function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    
    // Copy to clipboard
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
 * Allow pressing Enter in the text field to trigger copy
 * Improves UX on mobile and for keyboard users
 */
document.getElementById('shareLink')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        copyShareLink();
    }
});
</script>

<?php include dirname(__DIR__) . '/templates/footer.php'; ?>
