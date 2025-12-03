<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container my-5">
    <div class="row">
        <div class="col-12 text-center mb-5">
            <h2 class="display-3 fw-bold">Welcome to Monster Maker!</h2>
            <p class="lead text-muted">Create, share, and print your own custom monsters for tabletop RPGs</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-pencil-square display-1 text-primary mb-3"></i>
                    <h3 class="card-title">Create Monsters</h3>
                    <p class="card-text">Design your own unique monsters with our easy-to-use creation tool. Customize stats, abilities, and more!</p>
                    <a href="index.php?url=create" class="btn btn-primary">Start Creating</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-collection display-1 text-success mb-3"></i>
                    <h3 class="card-title">Browse Monsters</h3>
                    <p class="card-text">Explore monsters created by the community. Find inspiration or download ready-to-use creatures!</p>
                    <a href="index.php?url=monsters" class="btn btn-success">View Monsters</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-printer display-1 text-danger mb-3"></i>
                    <h3 class="card-title">Print as Cards</h3>
                    <p class="card-text">Export your monsters as PDF in business card or A5 format. Perfect for your gaming table!</p>
                    <a href="index.php?url=monsters" class="btn btn-danger">Get Started</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12 text-center">
            <h3 class="mb-4">How It Works</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3 fw-bold">1</span>
                        </div>
                        <h5>Create Account</h5>
                        <p class="text-muted">Sign up for free</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3 fw-bold">2</span>
                        </div>
                        <h5>Design Monster</h5>
                        <p class="text-muted">Fill in the stats</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3 fw-bold">3</span>
                        </div>
                        <h5>Share or Save</h5>
                        <p class="text-muted">Make it public or private</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3 fw-bold">4</span>
                        </div>
                        <h5>Print & Play</h5>
                        <p class="text-muted">Export as PDF</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
