<?php
/**
 * Internal API: Get user's collections (GET)
 * Thin wrapper delegating to CollectionController.
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

define('ROOT', dirname(__DIR__, 2));
spl_autoload_register(function ($class) {
    $class = str_replace('App\\', '', $class);
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    $file = preg_replace_callback('/\/([A-Z][a-z]+)\//', function($matches) {
        return '/' . strtolower($matches[1]) . '/';
    }, $file);
    if (file_exists($file)) {
        require_once $file;
    }
});

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use GET.']);
    exit;
}

use App\Controllers\CollectionController;

$controller = new CollectionController();
$controller->getCollectionsApi();
