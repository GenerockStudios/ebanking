<?php
/**
 * Fichier de Configuration Globale
 * Contient les constantes pour la connexion à la BDD et les chemins.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ebanking');
define('DB_USER', 'root');
define('DB_PASS', '');

// Le chemin de base pour les liens dans l'application
define('BASE_URL', '/ebanking/'); 
// Le titre par défaut de l'application
define('APP_NAME', 'Ebanking');

// --- Chemins du Système de Fichiers ---
// Simplifie l'inclusion des fichiers
//define('ROOT_PATH', __DIR__ . '/');
//define('APP_PATH', ROOT_PATH . 'app/');
define('MODEL_PATH', APP_PATH . 'Models/');
define('CONTROLLER_PATH', APP_PATH . 'Controllers/');
define('VIEW_PATH', APP_PATH . 'Views/');
define('SERVICE_PATH', APP_PATH . 'Services/');

// --- Configuration de Sécurité ---
// Durée maximale de la session en secondes
define('SESSION_DURATION', 7200); 

// Définition des rôles et des autorisations
$GLOBALS['ROLES'] = [
    1 => 'Admin',
    2 => 'Caissier',
    3 => 'Superviseur'
];

?>