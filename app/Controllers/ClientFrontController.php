<?php
/**
 * ClientFrontController.php
 * Gère l'accès web des clients pour la consultation de leur compte.
 */

class ClientFrontController {
    
    private $compteModel;
    private $clientModel;

    public function __construct() {
        // Le modèle de compte est nécessaire pour le solde et l'historique
        $this->compteModel = new CompteModel();
        $this->clientModel = new ClientModel();
        
        // Nous allons nous assurer que l'utilisateur est bien un client (rôle différent de 'Caissier'/'Admin')
        // La vérification complète de la session client sera faite dans checkClientAuth
    }

    /**
     * Affiche le formulaire de connexion client et traite la soumission.
     */
    public function login() {
        $data = ['title' => 'Connexion Client'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // NOTE: En réalité, vous auriez une table et un modèle de connexion séparés pour les clients.
            // Pour la simplicité, nous supposons qu'un client s'identifie par son Numéro de Compte et un Mot de Passe.
            
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $password = $_POST['mot_de_passe'] ?? ''; 
            
            // --- Logique d'Authentification Client (Simplifiée) ---
            // Le UserModel ou un ClientAuthModel devrait valider le couple (Numéro Compte / Mot de Passe)
            $clientAuthSuccessful = $this->validateClientCredentials($numeroCompte, $password);
            
            if ($clientAuthSuccessful) {
                // Si succès, démarrer la session client
                $_SESSION['client_logged_in'] = true;
                $_SESSION['client_numero_compte'] = $numeroCompte;
                
                // Redirection vers le tableau de bord client
                header("Location: " . BASE_URL . "?controller=ClientFront&action=dashboard");
                exit();
            } else {
                $data['error'] = "Numéro de compte ou mot de passe incorrect.";
            }
        }
        
        // Charger la vue de connexion
        require_once VIEW_PATH . 'client_front/login.php';
    }
    
    /**
     * Affiche le tableau de bord du client (Solde et Historique).
     */
    public function dashboard() {
        if (!$this->checkClientAuth()) {
            return; // Redirige vers le login
        }
        
        $numeroCompte = $_SESSION['client_numero_compte'];
        $data['title'] = "Tableau de Bord de Mon Compte";
        
        // 1. Récupérer le solde
        $data['solde'] = $this->compteModel->getAccountBalance($numeroCompte);
        
        // 2. Récupérer l'historique des transactions (les 100 dernières, comme défini dans CompteModel)
        $data['transactions'] = $this->compteModel->getAccountHistory($numeroCompte);

        require_once VIEW_PATH . 'client_front/dashboard.php';
    }
    
    /**
     * Déconnecte le client.
     */
    public function logout() {
        unset($_SESSION['client_logged_in']);
        unset($_SESSION['client_numero_compte']);
        session_destroy();
        header("Location: " . BASE_URL . "?controller=ClientFront&action=login");
        exit();
    }

    // --- Méthodes de Sécurité et Utilitaires ---
    
    /**
     * Vérifie si le client est connecté. Redirige si non.
     */
    private function checkClientAuth(): bool {
        if (!($_SESSION['client_logged_in'] ?? false) || !($_SESSION['client_numero_compte'] ?? false)) {
            header("Location: " . BASE_URL . "?controller=ClientFront&action=login&error=Session expirée");
            return false;
        }
        return true;
    }
    
    /**
     * SIMULATION de la validation des identifiants client (à remplacer par la logique BDD réelle).
     */
    private function validateClientCredentials(string $numeroCompte, string $password): bool {
        // Pour l'exemple, supposons qu'un compte test (123456789012) a le mot de passe 'client123'.
        // EN RÉALITÉ: Interroger une table Client_Access avec un hash de mot de passe.
        if ($numeroCompte === '123456789012' && $password === 'client123') {
            return true;
        }
        return false;
    }
}