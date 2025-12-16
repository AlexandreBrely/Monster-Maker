<?php

namespace App\Controllers;

/**
 * HomeController
 * Displays the public landing/home page.
 * 
 * Responsibilities:
 * - Render the home page (no business logic needed)
 * - Display public information about the app
 */
class HomeController
{
    /**
     * Display the home page.
     * No authentication required; public endpoint.
     */
    public function index()
    {
        require_once ROOT . '/src/views/home/index.php';
    }
}
