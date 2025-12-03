<?php
/**
 * Database.php
 * Classe responsable de la connexion à la base de données via PDO.
 * Utilise le pattern Singleton pour garantir une seule instance de connexion.
 */

// Assurez-vous que config.php a été chargé (c'est fait dans index.php)

class Database {
    
    // Propriété statique pour stocker l'instance de la connexion
    private static $instance = null;
    
    // Objet de connexion PDO
    private $connection;

    // Le constructeur est privé pour empêcher la création d'instances externes (Singleton)
    private function __construct() {
        // Chaîne de connexion DSN (Data Source Name)
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        
        // Options pour la connexion PDO
        $options = [
            // Activer le mode exception pour la gestion des erreurs
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Mode de récupération par défaut : objets
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            // Ne pas utiliser de Prepared Statements émulées (pour une meilleure sécurité)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (\PDOException $e) {
            // En cas d'échec de connexion, enregistrer l'erreur et arrêter l'exécution.
            // EN PRODUCTION : N'AFFICHEZ PAS $e->getMessage() à l'utilisateur !
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            die("Une erreur critique est survenue. Veuillez réessayer plus tard.");
        }
    }

    /**
     * Empêche le clonage de l'objet
     */
    private function __clone() {}

    /**
     * Méthode statique pour obtenir l'instance unique de la classe et établir la connexion si elle n'existe pas.
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet de connexion PDO.
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
}