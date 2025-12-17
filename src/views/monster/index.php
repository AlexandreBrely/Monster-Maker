<?php 
/**
 * Monster Index View
 * Displays the list of all public monsters
 * 
 * Expected variables:
 * - $monsters : array of monsters
 */
?>
<?php $extraStyles = ['/css/monster-card-mini.css']; ?>
<?php require_once __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../templates/navbar.php'; ?>

<?php
$searchType = $_GET['search_type'] ?? 'monster';
$validSearchTypes = ['monster', 'lair', 'user'];
if (!in_array($searchType, $validSearchTypes)) {
    $searchType = 'monster';
}
?>
<main class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Browse Content</h1>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="index.php?url=create_select" class="btn btn-primary">Create Monster</a>
        <?php endif; ?>
    </div>

    <!-- Search and Sort Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" id="searchForm">
                <input type="hidden" name="url" value="monsters">
                
                <!-- Main Search Row -->
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-3">
                        <label for="searchType" class="form-label"><strong>Search For</strong></label>
                        <select class="form-select form-select-lg" id="searchType" name="search_type" onchange="updateSearchPlaceholder()">
                            <option value="monster" <?php echo $searchType === 'monster' ? 'selected' : ''; ?>>Monsters</option>
                            <option value="lair" <?php echo $searchType === 'lair' ? 'selected' : ''; ?>>Lair Cards</option>
                            <option value="user" <?php echo $searchType === 'user' ? 'selected' : ''; ?>>Users</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="searchName" class="form-label"><strong>Search</strong></label>
                        <input type="text" 
                               id="searchName" 
                               name="search" 
                               class="form-control form-control-lg" 
                               placeholder="<?php echo $searchType === 'monster' ? 'Search by monster name...' : ($searchType === 'lair' ? 'Search by lair name...' : 'Search by username...'); ?>" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-3" id="sortByContainer" style="<?php echo $searchType === 'user' ? 'display:none;' : ''; ?>">
                        <label for="sortBy" class="form-label"><strong>Sort By</strong></label>
                        <select class="form-select form-select-lg" id="sortBy" name="order">
                            <option value="newest" <?php echo ($_GET['order'] ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo ($_GET['order'] ?? '') === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="most_liked" <?php echo ($_GET['order'] ?? '') === 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
                            <option value="random" <?php echo ($_GET['order'] ?? '') === 'random' ? 'selected' : ''; ?>>Random</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2" id="searchBtnCol">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Options Toggle (only for monsters) -->
                <div class="text-end" id="advancedToggle" style="<?php echo $searchType !== 'monster' ? 'display:none;' : ''; ?>">
                    <button type="button" class="btn btn-link btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                        <i class="bi bi-sliders"></i> Advanced Options
                    </button>
                </div>
                
                <!-- Advanced Options (Collapsed) -->
                <div class="collapse <?php echo (!empty($_GET['size']) || !empty($_GET['type'])) && $searchType === 'monster' ? 'show' : ''; ?>" id="advancedOptions" style="<?php echo $searchType !== 'monster' ? 'display:none;' : ''; ?>">
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filterSize" class="form-label">Size</label>
                            <select id="filterSize" name="size" class="form-select">
                                <option value="">All Sizes</option>
                                <option value="Tiny" <?php echo ($_GET['size'] ?? '') === 'Tiny' ? 'selected' : ''; ?>>Tiny</option>
                                <option value="Small" <?php echo ($_GET['size'] ?? '') === 'Small' ? 'selected' : ''; ?>>Small</option>
                                <option value="Medium" <?php echo ($_GET['size'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="Large" <?php echo ($_GET['size'] ?? '') === 'Large' ? 'selected' : ''; ?>>Large</option>
                                <option value="Huge" <?php echo ($_GET['size'] ?? '') === 'Huge' ? 'selected' : ''; ?>>Huge</option>
                                <option value="Gargantuan" <?php echo ($_GET['size'] ?? '') === 'Gargantuan' ? 'selected' : ''; ?>>Gargantuan</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="filterType" class="form-label">Type</label>
                            <select id="filterType" name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="Beast" <?php echo ($_GET['type'] ?? '') === 'Beast' ? 'selected' : ''; ?>>Beast</option>
                                <option value="Dragon" <?php echo ($_GET['type'] ?? '') === 'Dragon' ? 'selected' : ''; ?>>Dragon</option>
                                <option value="Humanoid" <?php echo ($_GET['type'] ?? '') === 'Humanoid' ? 'selected' : ''; ?>>Humanoid</option>
                                <option value="Undead" <?php echo ($_GET['type'] ?? '') === 'Undead' ? 'selected' : ''; ?>>Undead</option>
                                <option value="Construct" <?php echo ($_GET['type'] ?? '') === 'Construct' ? 'selected' : ''; ?>>Construct</option>
                                <option value="Elemental" <?php echo ($_GET['type'] ?? '') === 'Elemental' ? 'selected' : ''; ?>>Elemental</option>
                                <option value="Fiend" <?php echo ($_GET['type'] ?? '') === 'Fiend' ? 'selected' : ''; ?>>Fiend</option>
                                <option value="Celestial" <?php echo ($_GET['type'] ?? '') === 'Celestial' ? 'selected' : ''; ?>>Celestial</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function updateSearchPlaceholder() {
        const searchType = document.getElementById('searchType').value;
        const searchInput = document.getElementById('searchName');
        const sortContainer = document.getElementById('sortByContainer');
        const advancedToggle = document.getElementById('advancedToggle');
        const advancedOptions = document.getElementById('advancedOptions');
        const searchBtnCol = document.getElementById('searchBtnCol');
        
        // Update placeholder
        if (searchType === 'monster') {
            searchInput.placeholder = 'Search by monster name...';
        } else if (searchType === 'lair') {
            searchInput.placeholder = 'Search by lair name...';
        } else {
            searchInput.placeholder = 'Search by username...';
        }
        
        // Show/hide sort dropdown
        if (searchType === 'user') {
            sortContainer.style.display = 'none';
            searchBtnCol.classList.remove('col-md-2');
            searchBtnCol.classList.add('col-md-5');
        } else {
            sortContainer.style.display = 'block';
            searchBtnCol.classList.remove('col-md-5');
            searchBtnCol.classList.add('col-md-2');
        }
        
        // Show/hide advanced options (only for monsters)
        if (searchType === 'monster') {
            advancedToggle.style.display = 'block';
            advancedOptions.style.display = 'block';
        } else {
            advancedToggle.style.display = 'none';
            advancedOptions.style.display = 'none';
        }
    }
    
    function clearFilters() {
        document.getElementById('filterSize').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('searchForm').submit();
    }
    </script>

    <!-- Results Display -->
    <?php if ($searchType === 'monster'): ?>
        <!-- Monster Results -->
        <?php if (empty($monsters)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> No monsters found. Try adjusting your search criteria.
            </div>
        <?php else: ?>
            <div class="mb-3 text-muted">
                <small><?php echo count($monsters); ?> monster<?php echo count($monsters) !== 1 ? 's' : ''; ?> found</small>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($monsters as $monster): ?>
                    <div class="col">
                        <?php 
                        // Check if current user has liked this monster
                        $isLiked = in_array($monster['monster_id'], $userLikes ?? []);
                        require __DIR__ . '/../templates/monster-card-mini.php'; 
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    
    <?php elseif ($searchType === 'lair'): ?>
        <!-- Lair Card Results -->
        <?php if (empty($lairCards)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> No lair cards found. Try adjusting your search criteria.
            </div>
        <?php else: ?>
            <div class="mb-3 text-muted">
                <small><?php echo count($lairCards); ?> lair card<?php echo count($lairCards) !== 1 ? 's' : ''; ?> found</small>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($lairCards as $lair): ?>
                    <div class="col">
                        <?php require __DIR__ . '/../templates/lair-card-mini.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    
    <?php else: ?>
        <!-- User Results -->
        <?php if (empty($users)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> No users found. Try adjusting your search.
            </div>
        <?php else: ?>
            <div class="mb-3 text-muted">
                <small><?php echo count($users); ?> user<?php echo count($users) !== 1 ? 's' : ''; ?> found</small>
            </div>
            <div class="list-group">
                <?php foreach ($users as $user): ?>
                    <div class="list-group-item list-group-item-action d-flex align-items-center">
                        <?php if (!empty($user['u_avatar'])): ?>
                            <img src="uploads/avatars/<?= htmlspecialchars($user['u_avatar']) ?>" 
                                 alt="<?= htmlspecialchars($user['u_name']) ?>" 
                                 class="rounded-circle me-3"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white me-3 d-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?= htmlspecialchars($user['u_name']) ?></h5>
                            <small class="text-muted">Member since <?= date('M Y', strtotime($user['u_created_at'])) ?></small>
                        </div>
                        <a href="index.php?url=monsters&search_type=monster&user=<?= (int)$user['u_id'] ?>" class="btn btn-sm btn-outline-primary">
                            View Creations
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
function toggleLike(event, monsterId) {
    event.preventDefault();
    event.stopPropagation();
    
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    const countSpan = btn.querySelector('.like-count');
    
    // Disable button during request
    btn.disabled = true;
    
    fetch('index.php?url=monster-like&id=' + monsterId, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Like response:', data);
        
        if (data.success) {
            // Update icon
            if (data.liked) {
                icon.className = 'bi bi-heart-fill';
                btn.dataset.liked = '1';
            } else {
                icon.className = 'bi bi-heart';
                btn.dataset.liked = '0';
            }
            // Update count
            countSpan.textContent = data.count;
        } else {
            console.error('Like failed:', data.error);
            alert(data.error || 'Failed to like/unlike monster');
        }
    })
    .catch(error => {
        console.error('Like error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// Add monster to collection (AJAX)
async function addToCollection(event, element) {
    event.preventDefault();
    event.stopPropagation();
    
    const monsterId = element.dataset.monsterId;
    const collectionId = element.dataset.collectionId;
    const collectionName = element.dataset.collectionName;
    
        const response = await fetch('index.php?url=collection-add-monster', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `collection_id=${collectionId}&monster_id=${monsterId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Dynamic success alert (auto-dismiss after 3 seconds)
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            successMsg.style.zIndex = '9999';
            successMsg.innerHTML = `
                <i class="bi bi-check-circle"></i> Added to ${collectionName}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        } else {
            // Show error message to user
            alert(result.message || 'Failed to add to collection');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
