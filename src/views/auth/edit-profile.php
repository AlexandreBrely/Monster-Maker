<?php
// Page: User profile
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fa-solid fa-user-edit me-2"></i>Edit Profile</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($errors['server'])): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-exclamation-circle me-2"></i><?= htmlspecialchars($errors['server']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-check-circle me-2"></i>Profile updated successfully!
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Profile Picture Section -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-image me-2"></i>Profile Picture</h5>
                                <div class="d-flex align-items-center gap-4">
                                    <div>
                                        <?php if (!empty($user['u_avatar'])): ?>
                                            <img 
                                                src="uploads/avatars/<?= htmlspecialchars($user['u_avatar']) ?>" 
                                                alt="Profile Picture" 
                                                class="rounded-circle border border-3 border-primary"
                                                style="width: 120px; height: 120px; object-fit: cover;"
                                                id="avatar-preview">
                                        <?php else: ?>
                                            <div 
                                                class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white border border-3 border-primary"
                                                style="width: 120px; height: 120px; font-size: 3rem;"
                                                id="avatar-preview">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label for="avatar" class="form-label fw-bold">Change Profile Picture</label>
                                        <?php if (isset($errors['avatar'])): ?>
                                            <div class="text-danger small mb-2">
                                                <i class="fa-solid fa-exclamation-triangle me-1"></i><?= htmlspecialchars($errors['avatar']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <input 
                                            type="file" 
                                            class="form-control" 
                                            id="avatar" 
                                            name="avatar" 
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onchange="previewImage(event)">
                                        <small class="text-muted d-block mt-2">
                                            <i class="fa-solid fa-info-circle me-1"></i>Accepted formats: JPG, PNG, GIF, WEBP (max 5MB)
                                        </small>
                                        
                                        <?php if (!empty($user['u_avatar'])): ?>
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-danger mt-2"
                                                onclick="return confirm('Are you sure you want to remove your profile picture?') && deleteAvatar()">
                                                <i class="fa-solid fa-trash me-1"></i>Remove Picture
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information Section -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-user-circle me-2"></i>Account Information</h5>
                            </div>
                            
                            <!-- Username Field -->
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label fw-bold">Username</label>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="text-danger small mb-2">
                                        <i class="fa-solid fa-exclamation-triangle me-1"></i><?= htmlspecialchars($errors['username']) ?>
                                    </div>
                                <?php endif; ?>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    value="<?= htmlspecialchars($old['username'] ?? $user['u_name']) ?>"
                                    minlength="3"
                                    placeholder="Enter username">
                                <small class="text-muted">Minimum 3 characters</small>
                            </div>

                            <!-- Email Field -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-bold">Email Address</label>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small mb-2">
                                        <i class="fa-solid fa-exclamation-triangle me-1"></i><?= htmlspecialchars($errors['email']) ?>
                                    </div>
                                <?php endif; ?>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($old['email'] ?? $user['u_email']) ?>"
                                    placeholder="Enter email address">
                            </div>
                        </div>

                        <!-- Account Details (Read-only) -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-info-circle me-2"></i>Account Details</h5>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Account ID:</strong> #<?= htmlspecialchars($user['u_id']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['u_created_at'])) ?></p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="index.php?url=settings" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-gear me-2"></i>Settings
                            </a>
                            <div>
                                <a href="index.php?url=my-monsters" class="btn btn-outline-primary me-2">
                                    <i class="fa-solid fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card shadow-sm mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Once you delete your account, there is no going back. All your monsters will be permanently deleted.</p>
                    <button type="button" class="btn btn-danger" onclick="alert('Account deletion feature coming soon')">
                        <i class="fa-solid fa-trash me-2"></i>Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Preview image before upload
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace placeholder with image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Profile Picture';
                img.className = 'rounded-circle border border-3 border-primary';
                img.style.width = '120px';
                img.style.height = '120px';
                img.style.objectFit = 'cover';
                img.id = 'avatar-preview';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);
    }
}

// Delete avatar via AJAX
function deleteAvatar() {
    fetch('index.php?url=delete-avatar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error deleting avatar: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
    return false;
}
</script>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
