<?php
// Page: 403 Error - Access Forbidden
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 new-rocker-regular">403</h1>
            <h2 class="mb-4">Access Forbidden</h2>
            
            <p class="lead mb-4">
                You don't have permission to access this resource.
            </p>
            
            <div class="mb-4">
                <i class="fa-solid fa-lock display-1 text-danger"></i>
            </div>
            
            <a href="index.php?url=home" class="btn btn-primary btn-lg">
                Back to Home
            </a>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
