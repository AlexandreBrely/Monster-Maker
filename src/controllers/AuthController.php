<?php

// Contrôleur pour l'authentification (Login / Register)
class AuthController
{
    // Afficher la page de connexion
    public function login()
    {
        // Si l'utilisateur est déjà connecté, rediriger vers l'accueil
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit();
        }

        // Si le formulaire est soumis (méthode POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TODO: Vérifier les identifiants dans la base de données
            // Pour l'instant, on affiche juste un message d'erreur
            $error = "Fonctionnalité de connexion en cours de développement";
        }

        // Afficher la page de connexion
        require_once ROOT . '/src/views/auth/login.php';
    }

    // Afficher la page d'inscription
    public function register()
    {
        // Si l'utilisateur est déjà connecté, rediriger vers l'accueil
        if (isset($_SESSION['user'])) {
            header('Location: index.php?url=home');
            exit();
        }

        // Si le formulaire est soumis (méthode POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Tableau pour stocker les erreurs
            $errors = [];
            
            // Récupérer les données du formulaire
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validation simple
            if (empty($username)) {
                $errors['username'] = "Le pseudo est requis";
            }

            if (empty($email)) {
                $errors['email'] = "L'email est requis";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "L'email n'est pas valide";
            }

            if (empty($password)) {
                $errors['password'] = "Le mot de passe est requis";
            } elseif (strlen($password) < 6) {
                $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères";
            }

            if ($password !== $confirm_password) {
                $errors['confirm_password'] = "Les mots de passe ne correspondent pas";
            }

            // Si pas d'erreurs, on pourrait créer le compte
            if (empty($errors)) {
                // TODO: Insérer l'utilisateur dans la base de données
                $errors[] = "Fonctionnalité d'inscription en cours de développement";
            }

            // Garder les anciennes valeurs pour le formulaire
            $old = [
                'username' => $username,
                'email' => $email
            ];
        }

        // Afficher la page d'inscription
        require_once ROOT . '/src/views/auth/register.php';
    }

    // Déconnexion
    public function logout()
    {
        // Détruire la session
        session_destroy();
        
        // Rediriger vers l'accueil
        header('Location: index.php?url=home');
        exit();
    }

    // Afficher la page d'édition de profil
    public function editProfile()
    {
        // TODO: Vérifier si l'utilisateur est connecté
        // if (!isset($_SESSION['user'])) {
        //     header('Location: index.php?url=login');
        //     exit();
        // }

        require_once ROOT . '/src/views/auth/edit-profile.php';
    }

    // Afficher la page des paramètres
    public function settings()
    {
        // TODO: Vérifier si l'utilisateur est connecté
        // if (!isset($_SESSION['user'])) {
        //     header('Location: index.php?url=login');
        //     exit();
        // }

        require_once ROOT . '/src/views/auth/settings.php';
    }
}
