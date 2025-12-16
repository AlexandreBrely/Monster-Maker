<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold new-rocker-regular" href="index.php?url=home">Monster Maker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php?url=home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?url=monsters">Monsters</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?url=create_select">Create Monster</a>
                </li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?url=my-monsters">My Monsters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?url=my-lair-cards">My Lair Cards</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($_SESSION['user']['u_avatar'])): ?>
                                <img 
                                    src="uploads/avatars/<?= htmlspecialchars($_SESSION['user']['u_avatar']) ?>" 
                                    alt="Profile" 
                                    class="rounded-circle me-2"
                                    style="width: 30px; height: 30px; object-fit: cover; border: 2px solid #fff;">
                            <?php endif; ?>
                            <span><?= htmlspecialchars($_SESSION['user']['u_name'] ?? 'Profile') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="index.php?url=edit-profile"><i class="fa-solid fa-user-edit me-2"></i>Edit Profile</a></li>
                            <li><a class="dropdown-item" href="index.php?url=settings"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?url=logout"><i class="fa-solid fa-sign-out-alt me-2"></i>Disconnect</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?url=login">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
