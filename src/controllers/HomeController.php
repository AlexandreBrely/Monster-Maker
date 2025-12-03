<?php

class HomeController
{
    // Afficher la page d'accueil
    public function index()
    {
        // Charger la vue de la page d'accueil
        require_once ROOT . '/src/views/home/index.php';
    }
}
