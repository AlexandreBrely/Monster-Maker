<?php

// Contrôleur pour les monstres
class MonsterController
{
    // Afficher la liste de tous les monstres
    public function monsters()
    {
        // TODO: Récupérer tous les monstres publics de la base de données
        require_once ROOT . '/src/views/monster/list.php';
    }

    // Afficher le formulaire de création
    public function create()
    {
        // TODO: Vérifier si l'utilisateur est connecté
        // if (!isset($_SESSION['user'])) {
        //     header('Location: index.php?url=login');
        //     exit();
        // }

        require_once ROOT . '/src/views/monster/create.php';
    }

    // Afficher les monstres de l'utilisateur
    public function myMonsters()
    {
        // TODO: Vérifier si l'utilisateur est connecté
        // if (!isset($_SESSION['user'])) {
        //     header('Location: index.php?url=login');
        //     exit();
        // }

        require_once ROOT . '/src/views/monster/my-monsters.php';
    }
}
