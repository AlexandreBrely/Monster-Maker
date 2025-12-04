<?php
// Page: 404 Error - Page Not Found
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 new-rocker-regular">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            
            <p class="lead mb-4">
                Oops! The page you're looking for doesn't exist or has been moved.
            </p>
            
            <div class="mb-4">
                <i class="fa-solid fa-face-confused display-1 text-warning"></i>
            </div>
            
            <a href="index.php?url=home" class="btn btn-primary btn-lg">
                Back to Home
            </a>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
