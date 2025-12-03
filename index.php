<?php
/**
 * index.php
 * Le Routeur Frontal de l'application Mini Système Bancaire (MSB).
 * Point d'entrée unique qui gère l'initialisation, la sécurité des sessions et le routage.
 */

// --------------------------------------------------------------------------------
// 1. INITIALISATION CRITIQUE & CONFIGURATION SÉCURISÉE
// --------------------------------------------------------------------------------

// Sécurité de Session : Utiliser des cookies uniquement, HttpOnly pour prévenir les attaques XSS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
session_start();

// Définir les chemins d'accès
define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', ROOT_PATH . 'app/');
//define('VIEW_PATH', APP_PATH . 'Views/');
// Ajustez '/minibanque/' si votre application est dans un sous-dossier, ou '/' si elle est à la racine
//define('BASE_URL', '/'); 
//define('APP_NAME', 'Mini Banque Core');

// Charger le fichier de configuration (variables globales, rôles, etc.)
require_once APP_PATH . 'config.php'; 

// --------------------------------------------------------------------------------
// 2. AUTOCHARGEMENT (Autoloading)
// --------------------------------------------------------------------------------

spl_autoload_register(function ($className) {
    // Liste des répertoires où chercher les classes
    $directories = [
        APP_PATH . 'Controllers/',
        APP_PATH . 'Models/',
        APP_PATH . 'Services/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $className . '.php';
        // Utiliser strtolower pour les chemins si vos noms de fichiers sont en minuscules (non nécessaire ici)
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
//require_once APP_PATH . 'Models/Database.php';
// Inclure la classe Sanitizer pour l'utiliser immédiatement pour le routage
// NOTE: Ceci est nécessaire car Sanitizer n'est pas encore chargé par l'autoloader au moment du GET
require_once APP_PATH . 'Services/Sanitizer.php';


// --------------------------------------------------------------------------------
// 3. ROUTAGE
// --------------------------------------------------------------------------------

// Nettoyage des entrées GET pour la sécurité (même si c'est pour un nom de fichier)
$controllerName = Sanitizer::cleanString($_GET['controller'] ?? 'Auth');
$actionName = Sanitizer::cleanString($_GET['action'] ?? 'login');

$controllerClass = ucfirst($controllerName) . 'Controller';

// --------------------------------------------------------------------------------
// 4. GESTION DES SESSIONS SÉCURISÉES (Déconnexion Inactivité)
// --------------------------------------------------------------------------------

if (class_exists('AuthController') && method_exists('AuthController', 'isLoggedIn')) {
    if (AuthController::isLoggedIn()) {
        $sessionTimeout = 1800; // 30 minutes d'inactivité
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
            // Déconnexion automatique
            $auth = new AuthController(); 
            $auth->logout(); // Redirige vers la page de connexion
        }
        $_SESSION['last_activity'] = time(); // Mettre à jour l'activité
    }
}


// --------------------------------------------------------------------------------
// 5. INSTANCIATION ET EXÉCUTION
// --------------------------------------------------------------------------------

if (class_exists($controllerClass)) {
    $controller = new $controllerClass();

    if (method_exists($controller, $actionName)) {
        // Exécuter l'action demandée
        $controller->$actionName();
    } else {
        // Erreur 404: Action non trouvée
        header("HTTP/1.0 404 Not Found");
        echo "Erreur 404: Action '{$actionName}' non trouvée dans le contrôleur '{$controllerClass}'.";
    }
} else {
    // Erreur 404: Contrôleur non trouvé
    header("HTTP/1.0 404 Not Found");
    echo "Erreur 404: Contrôleur '{$controllerClass}' non trouvé.";
}

?>