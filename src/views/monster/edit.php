<?php 
/**
 * Monster Edit View
 * Form to edit an existing monster
 * 
 * Expected variables:
 * - $monster : array with current monster data
 * - $errors : array of validation errors (optional)
 * - $old : array with old values (in case of error)
 */
$errors = $errors ?? [];
$old = $old ?? $monster;

// Get the current card_size value from old data or default to the monster's card_size
$card_size = $old['card_size'] ?? $monster['card_size'] ?? 1;
?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<main class="container mt-5 mb-5">
    <h1 class="mb-4">Edit: <?php echo htmlspecialchars($monster['name']); ?></h1>

    <!-- Error display -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $field => $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Edit form (same as create.php) -->
    <form method="POST" enctype="multipart/form-data" action="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>&action=update">
        <!-- Card size selector -->
        <div class="mb-3">
            <label class="form-label">Card Size</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="card_size" id="card_size_small" value="1" <?php echo ($old['card_size'] ?? 1) == 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="card_size_small">Small Monster (Playing Card)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="card_size" id="card_size_boss" value="0" <?php echo ($old['card_size'] ?? 1) == 0 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="card_size_boss">Boss Monster (A5 Sheet)</label>
                </div>
            </div>
        </div>
        
        <!-- Same form as create.php -->
        <!-- Data will be pre-filled via $old/$monster -->
        
        <!-- Basic Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Monster Name *</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="size" class="form-label">Size *</label>
                        <select class="form-select <?php echo isset($errors['size']) ? 'is-invalid' : ''; ?>" 
                                id="size" name="size" required>
                            <option value="">Select a size</option>
                            <option value="Tiny" <?php echo ($old['size'] ?? '') === 'Tiny' ? 'selected' : ''; ?>>Tiny</option>
                            <option value="Small" <?php echo ($old['size'] ?? '') === 'Small' ? 'selected' : ''; ?>>Small</option>
                            <option value="Medium" <?php echo ($old['size'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Large" <?php echo ($old['size'] ?? '') === 'Large' ? 'selected' : ''; ?>>Large</option>
                            <option value="Huge" <?php echo ($old['size'] ?? '') === 'Huge' ? 'selected' : ''; ?>>Huge</option>
                            <option value="Gargantuan" <?php echo ($old['size'] ?? '') === 'Gargantuan' ? 'selected' : ''; ?>>Gargantuan</option>
                        </select>
                        <?php if (isset($errors['size'])): ?>
                            <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['size']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">Type *</label>
                        <input type="text" class="form-control <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" 
                               id="type" name="type" value="<?php echo htmlspecialchars($old['type'] ?? ''); ?>" 
                               placeholder="e.g. Dragon, Beast..." required>
                        <?php if (isset($errors['type'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['type']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="alignment" class="form-label">Alignment</label>
                        <input type="text" class="form-control" id="alignment" name="alignment" 
                               value="<?php echo htmlspecialchars($old['alignment'] ?? ''); ?>"
                               placeholder="e.g. Chaotic Evil">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="ac" class="form-label">Armor Class (AC) *</label>
                        <input type="number" class="form-control <?php echo isset($errors['ac']) ? 'is-invalid' : ''; ?>" 
                               id="ac" name="ac" min="1" value="<?php echo htmlspecialchars($old['ac'] ?? 10); ?>" required>
                        <?php if (isset($errors['ac'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['ac']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="hp" class="form-label">Hit Points *</label>
                        <input type="number" class="form-control <?php echo isset($errors['hp']) ? 'is-invalid' : ''; ?>" 
                               id="hp" name="hp" min="1" value="<?php echo htmlspecialchars($old['hp'] ?? 1); ?>" required>
                        <?php if (isset($errors['hp'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['hp']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ability Scores -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Ability Scores</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $abilities = ['strength' => 'STR', 'dexterity' => 'DEX', 'constitution' => 'CON', 
                                  'intelligence' => 'INT', 'wisdom' => 'WIS', 'charisma' => 'CHA'];
                    foreach ($abilities as $ability => $label): 
                    ?>
                        <div class="col-md-2 mb-3">
                            <label for="<?php echo $ability; ?>" class="form-label"><?php echo $label; ?></label>
                            <input type="number" class="form-control" id="<?php echo $ability; ?>" 
                                   name="<?php echo $ability; ?>" min="1" max="30" 
                                   value="<?php echo htmlspecialchars($old[$ability] ?? 10); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Images</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="image_portrait" class="form-label">Portrait Image</label>
                        <input type="file" class="form-control" id="image_portrait" name="image_portrait" accept="image/*">
                        <?php if (!empty($monster['image_portrait'])): ?>
                            <small class="text-muted d-block mt-2">Current image: 
                                <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_portrait']); ?>" 
                                     alt="Portrait" style="max-height: 100px;">
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image_fullbody" class="form-label">Full Body Image</label>
                        <input type="file" class="form-control" id="image_fullbody" name="image_fullbody" accept="image/*">
                        <?php if (!empty($monster['image_fullbody'])): ?>
                            <small class="text-muted d-block mt-2">Current image: 
                                <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_fullbody']); ?>" 
                                     alt="Full body" style="max-height: 100px;">
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visibility -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>Visibility</h5>
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                           <?php echo ($old['is_public'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_public">
                        Make this monster public (visible to all users)
                    </label>
                </div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>" class="btn btn-secondary">Cancel</a>
            <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteModal">
                Delete this monster
            </button>
        </div>
    </form>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
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
                    <form method="POST" action="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>&action=delete" 
                          style="display: inline;">
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
