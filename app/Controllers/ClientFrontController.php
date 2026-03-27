<?php
/**
 * ClientFrontController.php
 * Gère l'accès web des clients pour la consultation de leur compte.
 */

class ClientFrontController {
    
    private $compteModel;
    private $clientModel;

    public function __construct() {
        $this->compteModel = new CompteModel();
        $this->clientModel = new ClientModel();
    }

    /**
     * Affiche le formulaire de connexion client et traite la soumission.
     */
    public function login() {
        $data = ['title' => 'Connexion Client'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $password     = $_POST['mot_de_passe'] ?? ''; 
            
            $clientAuthSuccessful = $this->validateClientCredentials($numeroCompte, $password);
            
            if ($clientAuthSuccessful) {
                $_SESSION['client_logged_in']      = true;
                $_SESSION['client_numero_compte']  = $numeroCompte;
                
                header("Location: " . BASE_URL . "?controller=ClientFront&action=dashboard");
                exit();
            } else {
                $data['error'] = "Numéro de compte ou mot de passe incorrect.";
            }
        }
        
        require_once VIEW_PATH . 'client_front/login.php';
    }
    
    /**
     * Affiche le tableau de bord du client (Solde et Historique).
     */
    public function dashboard() {
        if (!$this->checkClientAuth()) {
            return;
        }
        
        $numeroCompte       = $_SESSION['client_numero_compte'];
        $data['title']      = "Tableau de Bord de Mon Compte";
        $data['solde']      = $this->compteModel->getAccountBalance($numeroCompte);
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
     * Valide les identifiants client via la base de données.
     * FIX SÉCURITÉ CRITIQUE: Les credentials hardcodés ont été supprimés.
     * Remplacés par une vérification PDO sécurisée avec password_verify().
     * 
     * NOTE: Cette méthode nécessite une table 'clients_access' avec les colonnes:
     *   numero_compte VARCHAR(20), mot_de_passe_hash VARCHAR(255), est_actif TINYINT(1)
     * Alternativement, ajouter mot_de_passe_hash directement dans la table clients.
     */
    private function validateClientCredentials(string $numeroCompte, string $password): bool {
        if (empty($numeroCompte) || empty($password)) {
            return false;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Vérification via la table clients_access (à créer si inexistante)
            // La requête cherche un compte actif avec ce numéro
            $stmt = $db->prepare(
                "SELECT ca.mot_de_passe_hash
                 FROM clients_access ca
                 JOIN comptes c ON ca.compte_id = c.compte_id
                 WHERE c.numero_compte = :num
                   AND ca.est_actif = 1
                 LIMIT 1"
            );
            $stmt->bindValue(':num', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            $hash = $stmt->fetchColumn();

            if (!$hash) {
                return false;
            }

            return password_verify($password, $hash);

        } catch (\PDOException $e) {
            error_log("validateClientCredentials error: " . $e->getMessage());
            return false;
        }
    }
}