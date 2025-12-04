<?php 
// Page d'inscription
// Permet aux nouveaux utilisateurs de créer un compte
?>
<?php require_once ROOT . '/src/views/templates/header.php'; ?>
<?php require_once ROOT . '/src/views/templates/navbar.php'; ?>

<?php 
// Initialiser les variables pour éviter les erreurs
$errors = $errors ?? [];  // Tableau des erreurs de validation
$old = $old ?? [];        // Anciennes valeurs du formulaire (en cas d'erreur)
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Créer un compte</h2>

            <?php 
            // Afficher toutes les erreurs de validation s'il y en a
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

            <!-- Formulaire d'inscription -->
            <form action="index.php?url=register" method="POST" novalidate>
                
                <!-- Champ Pseudo -->
                <div class="mb-3">
                    <label for="username" class="form-label">Pseudo</label>
                    <!-- Afficher l'erreur spécifique pour ce champ en italique -->
                    <span class="ms-2 text-danger fst-italic fw-light raleway-light-italic">
                        <?= $errors["username"] ?? '' ?>
                    </span>
                    <!-- Garder la valeur saisie en cas d'erreur -->
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        required 
                        value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                </div>

                <!-- Champ Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email</label>
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

                <!-- Champ Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
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

                <!-- Champ Confirmation mot de passe -->
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" class="btn btn-primary">S'inscrire</button>

                <!-- Lien vers la page de connexion -->
                <a class="d-block mt-3" href="index.php?url=login">
                    Déjà inscrit ? Je me connecte !
                </a>
            </form>
        </div>
    </div>
</main>

<?php require_once ROOT . '/src/views/templates/footer.php'; ?>
