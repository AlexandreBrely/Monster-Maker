<?php 
// Page de connexion
// Permet aux utilisateurs de se connecter avec email et mot de passe
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Connexion</h2>

            <?php 
            // Afficher le message d'erreur si présent
            if (isset($error)): 
            ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form action="index.php?url=login" method="POST">
                
                <!-- Champ Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <!-- Champ Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" class="btn btn-primary">Log in</button>

                <!-- Lien vers la page d'inscription -->
                <a class="d-block mt-3" href="index.php?url=register">
                    Not registered yet?! Create an account!
                </a>
            </form>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
