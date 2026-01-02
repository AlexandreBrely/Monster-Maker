<?php
// === Front Controller ===
// This is the single entry point for all HTTP requests.
// It bootstraps the app, then dispatches to the appropriate Controller.

// Basic configuration
define('ROOT', dirname(__DIR__));
define('BASE_URL', '/');

// Start session
session_start();

// Simple autoloader to load classes automatically
spl_autoload_register(function ($class) {
    // Remove 'App\' prefix from namespace (our base namespace)
    $class = str_replace('App\\', '', $class);
    // Convert namespace separators to directory separators
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    // Convert to lowercase for directory names (Controllers -> controllers, Models -> models)
    $file = preg_replace_callback('/\/([A-Z][a-z]+)\//', function($matches) {
        return '/' . strtolower($matches[1]) . '/';
    }, $file);
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Parse requested URL from query string (e.g., ?url=monster/show)
$url = isset($_GET['url']) ? $_GET['url'] : 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Extract primary route segment from URL
$route = isset($url[0]) && $url[0] != '' ? $url[0] : 'home';
$action = isset($url[1]) && $url[1] != '' ? $url[1] : 'index';

// Simple Router: map route keys to controllers
// Format: 'route' => ['controller' => 'ControllerName', 'action' => 'methodName']
// Example: index.php?url=my-lair-cards → LairCardController->myLairCards()
$routes = [
    'home' => ['controller' => 'HomeController', 'action' => 'index'],
    'login' => ['controller' => 'AuthController', 'action' => 'login'],
    'register' => ['controller' => 'AuthController', 'action' => 'register'],
    'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
    'edit-profile' => ['controller' => 'AuthController', 'action' => 'editProfile'],
    'delete-avatar' => ['controller' => 'AuthController', 'action' => 'deleteAvatar'],
    'settings' => ['controller' => 'AuthController', 'action' => 'settings'],
    
    // Monster routes
    'monsters' => ['controller' => 'MonsterController', 'action' => 'index'],
    'monster' => ['controller' => 'MonsterController', 'action' => 'handleMonsterRoute'],
    'monster-like' => ['controller' => 'MonsterController', 'action' => 'toggleLike'],
    'monster-print' => ['controller' => 'MonsterController', 'action' => 'printPreview'],
    'monster-pdf' => ['controller' => 'MonsterController', 'action' => 'generatePdf'],
    'create' => ['controller' => 'MonsterController', 'action' => 'create'],
    'create_select' => ['controller' => 'MonsterController', 'action' => 'selectCreate'],
    'create_boss' => ['controller' => 'MonsterController', 'action' => 'createBoss'],
    'create_small' => ['controller' => 'MonsterController', 'action' => 'createSmall'],
    'my-monsters' => ['controller' => 'MonsterController', 'action' => 'myMonsters'],
    'my-cards' => ['controller' => 'MonsterController', 'action' => 'myCards'],
    
    // Lair card routes (horizontal landscape cards for lair actions)
    'my-lair-cards' => ['controller' => 'LairCardController', 'action' => 'myLairCards'],
    'lair-card' => ['controller' => 'LairCardController', 'action' => 'show'],
    'lair-card-create' => ['controller' => 'LairCardController', 'action' => 'create'],
    'lair-card-store' => ['controller' => 'LairCardController', 'action' => 'store'],
    
    // Collection routes (organize monsters for printing)
    'collections' => ['controller' => 'CollectionController', 'action' => 'index'],
    'collection-view' => ['controller' => 'CollectionController', 'action' => 'view'],
    'collection-create' => ['controller' => 'CollectionController', 'action' => 'create'],
    'collection-edit' => ['controller' => 'CollectionController', 'action' => 'edit'],
    'collection-delete' => ['controller' => 'CollectionController', 'action' => 'delete'],
    'collection-add-monster' => ['controller' => 'CollectionController', 'action' => 'addMonster'],
    'collection-remove-monster' => ['controller' => 'CollectionController', 'action' => 'removeMonster'],
    'collection-share' => ['controller' => 'CollectionController', 'action' => 'shareCollection'],
    'collection-unshare' => ['controller' => 'CollectionController', 'action' => 'unshareCollection'],
    'collection-public' => ['controller' => 'CollectionController', 'action' => 'viewPublic'],
    
    // Static pages
    'cgu' => ['controller' => 'PagesController', 'action' => 'cgu'],
    'terms' => ['controller' => 'PagesController', 'action' => 'terms'],
];

// Resolve controller/action from the routes table
if (isset($routes[$route])) {
    $controllerName = $routes[$route]['controller'];
    $action = $routes[$route]['action'];
} else {
    // Route not found; render 404
    $controllerName = 'PagesController';
    $action = 'error404';
}

// Build controller path
$controllerFile = ROOT . '/src/controllers/' . $controllerName . '.php';
$controllerClass = 'App\\Controllers\\' . $controllerName;

// Load controller file and execute action if found; otherwise show 404
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    // Ensure controller class exists
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        
        // Ensure action method exists
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            // Method not found; render 404
            require_once ROOT . '/src/controllers/PagesController.php';
            $controller = new \App\Controllers\PagesController();
            $controller->error404();
        }
    } else {
        // Controller class not found; render 404
        require_once ROOT . '/src/controllers/PagesController.php';
        $controller = new \App\Controllers\PagesController();
        $controller->error404();
    }
} else {
    // Controller file not found; render 404
    require_once ROOT . '/src/controllers/PagesController.php';
    $controller = new \App\Controllers\PagesController();
    $controller->error404();
}
