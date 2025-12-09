<?php
// Configuration de base
define('ROOT', dirname(__DIR__));
define('BASE_URL', '/');

// Démarrage de la session
session_start();

// Autoloader simple pour charger les classes automatiquement
spl_autoload_register(function ($class) {
    // Remove 'App\' prefix from namespace
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

// Récupérer l'URL demandée
$url = isset($_GET['url']) ? $_GET['url'] : 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Récupérer la première partie de l'URL (route principale)
$route = isset($url[0]) && $url[0] != '' ? $url[0] : 'home';
$action = isset($url[1]) && $url[1] != '' ? $url[1] : 'index';

// Router simple: mapper les routes vers les contrôleurs
// Format: 'route' => ['controller' => 'ControllerName', 'action' => 'methodName']
$routes = [
    'home' => ['controller' => 'HomeController', 'action' => 'index'],
    'login' => ['controller' => 'AuthController', 'action' => 'login'],
    'register' => ['controller' => 'AuthController', 'action' => 'register'],
    'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
    'edit-profile' => ['controller' => 'AuthController', 'action' => 'editProfile'],
    'settings' => ['controller' => 'AuthController', 'action' => 'settings'],
    'monsters' => ['controller' => 'MonsterController', 'action' => 'index'],
    'monster' => ['controller' => 'MonsterController', 'action' => 'handleMonsterRoute'],
    'create' => ['controller' => 'MonsterController', 'action' => 'create'],
    'create_select' => ['controller' => 'MonsterController', 'action' => 'selectCreate'],
    'create_boss' => ['controller' => 'MonsterController', 'action' => 'createBoss'],
    'create_small' => ['controller' => 'MonsterController', 'action' => 'createSmall'],
    'my-monsters' => ['controller' => 'MonsterController', 'action' => 'myMonsters'],
    'cgu' => ['controller' => 'PagesController', 'action' => 'cgu'],
    'terms' => ['controller' => 'PagesController', 'action' => 'terms'],
];

// Vérifier si la route existe
if (isset($routes[$route])) {
    $controllerName = $routes[$route]['controller'];
    $action = $routes[$route]['action'];
} else {
    // Route non trouvée, afficher 404
    $controllerName = 'PagesController';
    $action = 'error404';
}

// Chemin complet du contrôleur
$controllerFile = ROOT . '/src/controllers/' . $controllerName . '.php';
$controllerClass = 'App\\Controllers\\' . $controllerName;

// Vérifier si le contrôleur existe
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    // Vérifier si la classe existe
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        
        // Vérifier si la méthode existe
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            // Méthode non trouvée, afficher page 404
            require_once ROOT . '/src/controllers/PagesController.php';
            $controller = new \App\Controllers\PagesController();
            $controller->error404();
        }
    } else {
        // Classe non trouvée, afficher page 404
        require_once ROOT . '/src/controllers/PagesController.php';
        $controller = new \App\Controllers\PagesController();
        $controller->error404();
    }
} else {
    // Contrôleur non trouvé, afficher page 404
    require_once ROOT . '/src/controllers/PagesController.php';
    $controller = new \App\Controllers\PagesController();
    $controller->error404();
}
