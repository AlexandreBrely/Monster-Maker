<?php 
// Registration page
// Allows new users to create an account
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<?php 
// Initialize variables to avoid errors
$errors = $errors ?? [];  // Array of validation errors
$old = $old ?? [];        // Old form values (in case of error)
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Create an Account</h2>

            <?php 
            // Display all validation errors if any
            if (!empty($errors)): 
            ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $key => $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Registration form -->
            <form action="index.php?url=register" method="POST" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <span class="ms-2 text-danger fst-italic fw-light raleway-light-italic">
                        <?= $errors["username"] ?? '' ?>
                    </span>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        required 
                        value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <span class="ms-2 text-danger fst-italic fw-light raleway-light-italic">
                        <?= $errors["email"] ?? '' ?>
                    </span>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        required 
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <span class="ms-2 text-danger fst-italic fw-light raleway-light-italic">
                        <?= $errors["password"] ?? '' ?>
                    </span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required>
                </div>
                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture <span class="text-muted">(optional)</span></label>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn btn-warning">Register</button>
                <a class="d-block mt-2" href="index.php?url=login">Already registered? Log in!</a>
            </form>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
