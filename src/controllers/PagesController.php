<?php

namespace App\Controllers;

// Contrôleur pour les pages statiques (CGU, Terms, 404)
class PagesController
{
    // Afficher la page CGU
    public function cgu()
    {
        require_once ROOT . '/src/views/pages/cgu.php';
    }

    // Afficher la page Terms
    public function terms()
    {
        require_once ROOT . '/src/views/pages/terms.php';
    }

    // Afficher la page 404
    public function error404()
    {
        require_once ROOT . '/src/views/pages/error-404.php';
    }
}
