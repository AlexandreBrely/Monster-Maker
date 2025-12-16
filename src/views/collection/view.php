<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($collection['collection_name']) ?> - Monster Maker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/css/monster-card-mini.css">
</head>
<body>
    <?php require_once __DIR__ . '/../templates/navbar.php'; ?>

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
                        <div class="card h-100 shadow-sm monster-card">
                            <?php if (!empty($monster['image_path'])): ?>
                                <img src="public/uploads/monsters/<?= htmlspecialchars($monster['image_path']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($monster['name']) ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($monster['name']) ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?= htmlspecialchars($monster['size']) ?> 
                                        <?= htmlspecialchars($monster['type']) ?>
                                        <?= !empty($monster['subtype']) ? '(' . htmlspecialchars($monster['subtype']) . ')' : '' ?>
                                        <br>
                                        <strong>CR:</strong> <?= htmlspecialchars($monster['challenge_rating']) ?>
                                    </small>
                                </p>
                                
                                <div class="d-flex gap-2">
                                    <a href="index.php?url=monster&action=show&id=<?= $monster['monster_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger remove-monster-btn"
                                            data-monster-id="<?= $monster['monster_id'] ?>"
                                            data-monster-name="<?= htmlspecialchars($monster['name']) ?>">
                                        <i class="bi bi-x-circle"></i> Remove
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card-footer text-muted small">
                                Added <?= date('M j, Y', strtotime($monster['added_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>
</html>
