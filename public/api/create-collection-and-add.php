<?php
/**
 * Internal API: Create collection and add monster (POST)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['monster_id']) || !isset($input['collection_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: monster_id, collection_name']);
    exit;
}

use App\Controllers\CollectionController;

$controller = new CollectionController();
$controller->createCollectionAndAddMonsterApi($input);
