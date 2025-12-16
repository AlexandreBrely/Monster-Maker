<?php

namespace App\Controllers;

/**
 * PagesController
 * Renders static pages and error pages.
 * 
 * Responsibilities:
 * - Serve static content (Terms of Use, Privacy Policy)
 * - Display error pages (404, 403, etc.)
 * - No database access or complex logic
 */
class PagesController
{
    /**
     * Display the Terms of Use (CGU) page.
     */
    public function cgu()
    {
        require_once ROOT . '/src/views/pages/cgu.php';
    }

    /**
     * Display the Terms page.
     */
    public function terms()
    {
        require_once ROOT . '/src/views/pages/terms.php';
    }

    /**
     * Display the 404 Not Found error page.
     * Called when a requested route or resource doesn't exist.
     */
    public function error404()
    {
        require_once ROOT . '/src/views/pages/error-404.php';
    }
}
