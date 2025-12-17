<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Collection</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($_SESSION['errors'] as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <form method="POST" action="index.php?url=collection-edit&id=<?= $collection['collection_id'] ?>">
                            <div class="mb-3">
                                <label for="collection_name" class="form-label">
                                    Collection Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="collection_name" 
                                       name="collection_name" 
                                       maxlength="100"
                                       value="<?= htmlspecialchars($_POST['collection_name'] ?? $collection['collection_name']) ?>"
                                       required>
                                <div class="form-text">Maximum 100 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          maxlength="500"><?= htmlspecialchars($_POST['description'] ?? $collection['description'] ?? '') ?></textarea>
                                <div class="form-text">Optional. Maximum 500 characters</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?url=collection-view&id=<?= $collection['collection_id'] ?>" 
                                   class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Collection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
