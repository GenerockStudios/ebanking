<?php

/**
 * CaisseController.php
 * Gère le flux pour toutes les opérations de guichet (Dépôt, Retrait, Transfert).
 */

class CaisseController
{

    private $transactionModel;
    private $compteModel;
    private $auditLogger;

    public function __construct()
    {
        // Vérification immédiate de la permission pour accéder au contrôleur (Rôle 'Caissier' ou 'Admin')
        AuthController::checkPermission('Caissier');

        $this->transactionModel = new TransactionModel();
        $this->compteModel = new CompteModel();
        // L'audit est vital ici
        $this->auditLogger = new AuditLogger();
    }

    // app/Controllers/CaisseController.php (Méthode dashboard)

    public function dashboard()
    {
        $data = [];
        $data['title'] = "Tableau de Bord Caisse";

        // 1. Récupérer le nom du rôle de l'utilisateur (Crucial pour la vue)
        $roleId = $_SESSION['role_id'] ?? 0;
        $data['user_role'] = $GLOBALS['ROLES'][$roleId] ?? 'Invité'; // Utilise les rôles définis dans config.php

        // 2. Logique spécifique au tableau de bord (statistiques rapides, etc.) irait ici.

        // 3. Charger la vue
        require_once VIEW_PATH . 'caisse/dashboard.php'; // Ligne 29 (qui posait problème)
    }

    // --- Opérations de Dépôt ---
    public function transfert()
    {
        $data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Nettoyage et Récupération des données
            $compteSourceNum = Sanitizer::cleanString($_POST['compte_source'] ?? '');
            $compteDestNum = Sanitizer::cleanString($_POST['compte_destination'] ?? '');
            $montant = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId = $_SESSION['user_id'];

            // Récupérer les ID internes des comptes
            $sourceInfo = $this->getCompteIdByNumber($compteSourceNum);
            $destInfo = $this->getCompteIdByNumber($compteDestNum);

            // 2. Validation Préliminaire
            if (!$sourceInfo || !$destInfo) {
                $data['error'] = "Erreur: Compte source ou destination non trouvé.";
            } elseif ($sourceInfo->compte_id === $destInfo->compte_id) {
                $data['error'] = "Erreur: Le compte source et le compte destination doivent être différents.";
            } elseif ($montant <= 0) {
                $data['error'] = "Erreur: Le montant doit être positif.";
            } else {
                try {
                    $sourceId = $sourceInfo->compte_id;
                    $destId = $destInfo->compte_id;

                    // 3. Appel au Modèle de Transaction (vérification solde/plafond incluse dans le Modèle)
                    if ($this->transactionModel->faireTransfert($sourceId, $destId, $montant, $userId)) {
                        $data['success'] = "Transfert de " . number_format($montant, 2) . " réussi de " . $compteSourceNum . " vers " . $compteDestNum . ".";
                        // Optionnel : Afficher le nouveau solde source
                        $data['new_balance'] = $this->compteModel->getAccountBalance($compteSourceNum);
                    } else {
                        // L'erreur détaillée est gérée par le Modèle via l'Exception et audit.
                        $data['error'] = "Échec du transfert. Vérifiez solde ou plafond.";
                    }
                } catch (Exception $e) {
                    // Capture les exceptions levées (ex: compte non trouvé dans TransactionModel/PlafondChecker)
                    $data['error'] = "Erreur : " . $e->getMessage();
                }
            }
        }

        $data['title'] = "Opération de Transfert Interne";
        require_once VIEW_PATH . 'caisse/transfert.php'; // Nouvelle vue à créer
    }


    /**
     * Affiche le formulaire de dépôt ou traite la soumission.
     */
    public function depot()
    {
        $data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Nettoyage et Récupération des données
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $montant = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId = $_SESSION['user_id'];

            $compteInfo = $this->getCompteIdByNumber($numeroCompte);

            if (!$compteInfo) {
                $data['error'] = "Erreur: Compte bancaire non trouvé.";
            } elseif ($montant <= 0) {
                $data['error'] = "Erreur: Le montant doit être positif.";
            } else {
                // 2. Appel au Modèle de Transaction
                $compteId = $compteInfo->compte_id;

                if ($this->transactionModel->faireDepot($compteId, $montant, $userId)) {
                    $data['success'] = "Dépôt de " . number_format($montant, 2) . " réussi sur le compte " . $numeroCompte . ".";
                    // Après succès, on peut consulter le nouveau solde
                    $data['new_balance'] = $this->compteModel->getAccountBalance($numeroCompte);
                } else {
                    $data['error'] = "Échec du dépôt. Veuillez vérifier les logs.";
                }
            }
        }

        $data['title'] = "Opération de Dépôt";
        require_once VIEW_PATH . 'caisse/depot.php';
    }

    // --- Opérations de Retrait ---

    /**
     * Affiche le formulaire de retrait ou traite la soumission.
     */
    public function retrait()
    {
        $data = [];


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $montant = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId = $_SESSION['user_id'];
            $compteInfo = $this->getCompteIdByNumber($numeroCompte);

            if (!$compteInfo) {
                $data['error'] = "Erreur: Compte bancaire non trouvé.";
            } elseif ($montant <= 0) {
                $data['error'] = "Erreur: Le montant doit être positif.";
            } else {
                try {
                    $compteId = $compteInfo->compte_id;

                    if ($this->transactionModel->faireRetrait($compteId, $montant, $userId)) {
                        $data['success'] = "Retrait de " . number_format($montant, 2) . " effectué sur le compte " . $numeroCompte . ".";
                        $data['new_balance'] = $this->compteModel->getAccountBalance($numeroCompte);
                    } else {
                        $data['error'] = "Échec du retrait. Vérifiez solde ou plafond.";
                    }
                } catch (Exception $e) {
                    // Capture les exceptions levées par PlafondChecker ou TransactionModel
                    $data['error'] = "Erreur : " . $e->getMessage();
                }
            }
        }

        $data['title'] = "Opération de Retrait";
        require_once VIEW_PATH . 'caisse/retrait.php';
    }

    // --- Méthode Utile ---

    /**
     * Récupère l'ID interne du compte à partir de son numéro externe.
     */
    private function getCompteIdByNumber(string $numeroCompte)
    {
        // C'est une méthode de recherche, peut être ajoutée à CompteModel
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT compte_id FROM Comptes WHERE numero_compte = :numero");
            $stmt->bindParam(':numero', $numeroCompte);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log("Erreur BDD : " . $e->getMessage());
            return false;
        }
    }

    // ... d'autres méthodes: transfert(), consultationCompte(), clotureJournee()
}
