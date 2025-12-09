<?php 
/**
 * Monster Index View
 * Displays the list of all public monsters
 * 
 * Expected variables:
 * - $monsters : array of monsters
 */
?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>All Monsters</h1>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="index.php?url=create" class="btn btn-primary">Create Monster</a>
        <?php endif; ?>
    </div>

    <!-- Search bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="url" value="monsters">
                
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search for a monster..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <select name="size" class="form-select">
                        <option value="">All sizes</option>
                        <option value="Tiny" <?php echo ($_GET['size'] ?? '') === 'Tiny' ? 'selected' : ''; ?>>Tiny</option>
                        <option value="Small" <?php echo ($_GET['size'] ?? '') === 'Small' ? 'selected' : ''; ?>>Small</option>
                        <option value="Medium" <?php echo ($_GET['size'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="Large" <?php echo ($_GET['size'] ?? '') === 'Large' ? 'selected' : ''; ?>>Large</option>
                        <option value="Huge" <?php echo ($_GET['size'] ?? '') === 'Huge' ? 'selected' : ''; ?>>Huge</option>
                        <option value="Gargantuan" <?php echo ($_GET['size'] ?? '') === 'Gargantuan' ? 'selected' : ''; ?>>Gargantuan</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All types</option>
                        <option value="Beast" <?php echo ($_GET['type'] ?? '') === 'Beast' ? 'selected' : ''; ?>>Beast</option>
                        <option value="Dragon" <?php echo ($_GET['type'] ?? '') === 'Dragon' ? 'selected' : ''; ?>>Dragon</option>
                        <option value="Humanoid" <?php echo ($_GET['type'] ?? '') === 'Humanoid' ? 'selected' : ''; ?>>Humanoid</option>
                        <option value="Undead" <?php echo ($_GET['type'] ?? '') === 'Undead' ? 'selected' : ''; ?>>Undead</option>
                        <option value="Construct" <?php echo ($_GET['type'] ?? '') === 'Construct' ? 'selected' : ''; ?>>Construct</option>
                        <option value="Elemental" <?php echo ($_GET['type'] ?? '') === 'Elemental' ? 'selected' : ''; ?>>Elemental</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Monster list -->
    <?php if (empty($monsters)): ?>
        <div class="alert alert-info" role="alert">
            No monsters found.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($monsters as $monster): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <!-- Image -->
                        <?php if (!empty($monster['image_portrait'])): ?>
                            <img src="/public/uploads/monsters/<?php echo htmlspecialchars($monster['image_portrait']); ?>" 
                                 alt="<?php echo htmlspecialchars($monster['name']); ?>" 
                                 class="card-img-top" style="height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 250px;">
                                <span class="text-muted">No image</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <!-- Monster title -->
                            <h5 class="card-title"><?php echo htmlspecialchars($monster['name']); ?></h5>

                            <!-- Quick info -->
                            <p class="card-text text-muted small">
                                <strong><?php echo htmlspecialchars($monster['size']); ?></strong> 
                                <?php echo htmlspecialchars($monster['type']); ?> • CR <?php echo htmlspecialchars($monster['challenge_rating']); ?>
                            </p>

                            <!-- Main stats -->
                            <div class="row g-2 mb-3 text-center" style="font-size: 0.9rem;">
                                <div class="col-6">
                                    <small class="text-muted d-block">AC</small>
                                    <span class="fw-bold"><?php echo (int)$monster['ac']; ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">HP</small>
                                    <span class="fw-bold"><?php echo (int)$monster['hp']; ?></span>
                                </div>
                            </div>

                            <!-- Short description -->
                            <p class="card-text" style="font-size: 0.9rem;">
                                <?php echo htmlspecialchars(substr($monster['traits'], 0, 100)); ?>...
                            </p>
                        </div>

                        <!-- Link button -->
                        <div class="card-footer bg-white">
                            <a href="index.php?url=monster&id=<?php echo $monster['monster_id']; ?>" class="btn btn-primary btn-sm w-100">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
