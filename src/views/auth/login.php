<?php 
// Registration page
// Allows new users to create an account
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>
<?php 
// Initialize error variable
$errors = $errors ?? [];
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Login</h2>

            <?php 
            // Display error message if present
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

            <!-- Login form -->
            <form action="index.php?url=login" method="POST">
                
                <!-- Email field -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <!-- Password field -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary">Log in</button>

                <!-- Link to registration page -->
                <a class="d-block mt-3" href="index.php?url=register">
                    Not registered yet?! Create an account!
                </a>
            </form>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
