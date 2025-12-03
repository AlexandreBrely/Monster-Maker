<?php
// Configuration de base
define('ROOT', dirname(__DIR__));
define('BASE_URL', '/');

// Démarrage de la session
session_start();

// Autoloader simple pour charger les classes automatiquement
spl_autoload_register(function ($class) {
    $file = ROOT . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Récupérer l'URL demandée
$url = isset($_GET['url']) ? $_GET['url'] : 'home';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Déterminer le contrôleur et l'action
$controllerName = isset($url[0]) && $url[0] != '' ? ucfirst($url[0]) . 'Controller' : 'HomeController';
$action = isset($url[1]) && $url[1] != '' ? $url[1] : 'index';

// Chemin complet du contrôleur
$controllerFile = ROOT . '/src/controllers/' . $controllerName . '.php';

// Vérifier si le contrôleur existe
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    // Vérifier si la classe existe
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        
        // Vérifier si la méthode existe
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            // Méthode non trouvée, rediriger vers la page d'accueil
            header('Location: index.php?url=home');
            exit();
        }
    } else {
        // Classe non trouvée
        header('Location: index.php?url=home');
        exit();
    }
} else {
    // Contrôleur non trouvé, rediriger vers la page d'accueil
    header('Location: index.php?url=home');
    exit();
}
