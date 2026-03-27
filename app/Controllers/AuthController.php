<?php
/**
 * AuthController.php
 * Gère le processus d'authentification, la connexion, la déconnexion et les vérifications de session.
 */

class AuthController {
    
    private $userModel;
    private $auditLogger;

    public function __construct() {
        $this->userModel   = new UserModel();
        $this->auditLogger = new AuditLogger(); // FIX: AuditLogger activé pour traçabilité
    }

    /**
     * Méthode statique pour vérifier si l'utilisateur est actuellement connecté.
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && $_SESSION['is_logged_in'] === true;
    }

    /**
     * Affiche le formulaire de connexion ou traite les données de connexion.
     */
    public function login() {
        $errorMessage = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        if (self::isLoggedIn()) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = Sanitizer::cleanString($_POST['identifiant'] ?? '');
            $password   = $_POST['mot_de_passe'] ?? '';

            if (empty($identifier) || empty($password)) {
                $errorMessage = "Veuillez saisir votre identifiant et mot de passe.";
            } else {
                $user = $this->userModel->authenticate($identifier, $password);

                if ($user) {
                    session_regenerate_id(true); 

                    $_SESSION['user_id']       = $user->utilisateur_id;
                    $_SESSION['role_id']       = $user->role_id;
                    $_SESSION['is_logged_in']  = true;
                    $_SESSION['role_name']     = $this->userModel->getUserRole($user->utilisateur_id);
                    $_SESSION['nom_complet']   = $user->nom_complet;
                    $_SESSION['last_activity'] = time();

                    // FIX: Journalisation de la connexion réussie (obligation audit bancaire)
                    $this->auditLogger->logAction(
                        $user->utilisateur_id,
                        'CONNEXION_SUCCESS',
                        'utilisateurs',
                        "Connexion réussie pour l'identifiant: {$identifier}",
                        (string)$user->utilisateur_id
                    );

                    header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard");
                    exit();
                } else {
                    // FIX: Journalisation de l'échec de connexion (traçabilité sécurité)
                    $this->auditLogger->logAction(
                        null,
                        'CONNEXION_FAILURE',
                        'utilisateurs',
                        "Tentative de connexion échouée pour l'identifiant: {$identifier}"
                    );
                    $errorMessage = "Identifiant ou mot de passe incorrect.";
                }
            }
        }
        
        require_once VIEW_PATH . 'auth/login.php';
    }

    /**
     * Détruit la session et déconnecte l'utilisateur.
     */
    public function logout() {
        if (self::isLoggedIn()) {
            // FIX: Journalisation de la déconnexion (traçabilité sécurité)
            $this->auditLogger->logAction(
                $_SESSION['user_id'],
                'DECONNEXION',
                'utilisateurs',
                "Déconnexion manuelle.",
                (string)$_SESSION['user_id']
            );
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        
        header("Location: " . BASE_URL . "?controller=Auth&action=login");
        exit();
    }
    
    /**
     * Vérifie si l'utilisateur connecté a la permission requise.
     * @param string $requiredRole Nom du rôle requis (ex: 'Admin').
     */
    public static function checkPermission(string $requiredRole) {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "?controller=Auth&action=login");
            exit();
        }
        
        $currentUserRoleName = $_SESSION['role_name'] ?? 'Invité';
        
        if ($currentUserRoleName !== $requiredRole && $currentUserRoleName !== 'Admin') {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Permission Denied");
            exit();
        }
    }
}