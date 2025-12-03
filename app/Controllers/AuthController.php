<?php
/**
 * AuthController.php
 * Gère le processus d'authentification, la connexion, la déconnexion et les vérifications de session.
 */

// NOTE : Les classes UserModel, Sanitizer, AuditLogger doivent exister dans app/Models et app/Services.

class AuthController {
    
    private $userModel;
    // private $auditLogger; // Décommenter si l'AuditLogger est utilisé

    public function __construct() {
        // Le modèle est chargé pour l'interaction BDD
        $this->userModel = new UserModel();
        // $this->auditLogger = new AuditLogger(); // Décommenter si l'AuditLogger est utilisé
    }

    /**
     * Méthode statique pour vérifier si l'utilisateur est actuellement connecté.
     * @return bool
     */
    public static function isLoggedIn(): bool {
        // Vérifie la variable de session critique
        return isset($_SESSION['user_id']) && $_SESSION['is_logged_in'] === true;
    }

    /**
     * Affiche le formulaire de connexion ou traite les données de connexion.
     */
    public function login() {
        $errorMessage = $_SESSION['error'] ?? null;
        unset($_SESSION['error']); // Nettoyer le message après affichage

        if (self::isLoggedIn()) {
            // Si déjà connecté, rediriger vers le tableau de bord (par défaut : caisse)
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Nettoyage et Validation des entrées
            // NOTE: On suppose que Sanitizer existe et est efficace
            $identifier = Sanitizer::cleanString($_POST['identifiant'] ?? '');
            $password = $_POST['mot_de_passe'] ?? ''; // Le mot de passe n'est pas nettoyé (car il doit rester intact pour le hachage)

            if (empty($identifier) || empty($password)) {
                $errorMessage = "Veuillez saisir votre identifiant et mot de passe.";
            } else {
                // 2. Authentification via le Modèle
                $user = $this->userModel->authenticate($identifier, $password);

                if ($user) {
                    // 3. Authentification Réussie : Initialisation sécurisée de la Session
                    
                    // Sécurité : Régénère l'ID de session pour prévenir la fixation de session
                    session_regenerate_id(true); 

                    $_SESSION['user_id'] = $user->utilisateur_id;
                    $_SESSION['role_id'] = $user->role_id;
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['role_name'] = $this->userModel->getUserRole($user->utilisateur_id);
                    $_SESSION['nom_complet'] = $user->nom_complet;
                    $_SESSION['last_activity'] = time(); // Pour le timeout de session

                    // $this->auditLogger->logAction($user->utilisateur_id, 'CONNEXION_SUCCESS', 'Utilisateurs', "Connexion réussie."); // Audit

                    // Redirection vers la page sécurisée après connexion
                    header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard");
                    exit();
                } else {
                    // $this->auditLogger->logAction(null, 'CONNEXION_FAILURE', 'Utilisateurs', "Échec de connexion pour l'identifiant: " . $identifier); // Audit
                    $errorMessage = "Identifiant ou mot de passe incorrect.";
                }
            }
        }
        
        // Afficher la vue de connexion
        require_once VIEW_PATH . 'auth/login.php';
    }

    /**
     * Détruit la session et déconnecte l'utilisateur.
     */
    public function logout() {
        if (self::isLoggedIn()) {
            // $this->auditLogger->logAction($_SESSION['user_id'], 'DECONNEXION', 'Utilisateurs', "Déconnexion manuelle."); // Audit
        }

        // Détruire toutes les variables de session
        $_SESSION = [];

        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page de connexion
        header("Location: " . BASE_URL . "?controller=Auth&action=login");
        exit();
    }
    
    /**
     * Vérifie si l'utilisateur connecté a la permission requise.
     * Cette méthode peut être appelée au début de chaque action de Contrôleur.
     * @param string $requiredRole Nom du rôle requis (ex: 'Admin').
     */
    public static function checkPermission(string $requiredRole) {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "?controller=Auth&action=login");
            exit();
        }
        
        $currentUserRoleName = $_SESSION['role_name'] ?? 'Invité';
        
        if ($currentUserRoleName !== $requiredRole && $currentUserRoleName !== 'Admin') {
            // Un Admin a généralement tous les droits, sinon vérifier le rôle exact
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Permission Denied");
            exit();
        }
        // Si la vérification passe, l'exécution continue
    }
}