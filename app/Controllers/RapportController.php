<?php
/**
 * RapportController.php
 * Gère le flux pour les rapports financiers et d'audit, ainsi que la clôture de journée.
 */

class RapportController {
    
    private $db;
    private $compteModel;

    public function __construct() {
        // Vérification des permissions : Superviseur ou Admin uniquement
        // L'Admin est implicitement inclus dans checkPermission('Superviseur') si AuthController est bien codé
        AuthController::checkPermission('Superviseur'); 

        $this->db = Database::getInstance()->getConnection();
        $this->compteModel = new CompteModel();
    }
    
    /**
     * Affiche un rapport agrégé des transactions pour une période donnée.
     */
    public function rapportTransactions() {
        $data = [];
        $data['title'] = "Rapport des Transactions Détaillées";
        
        // 1. Récupération et Nettoyage des filtres
        $dateDebut = Sanitizer::cleanString($_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days')));
        $dateFin = Sanitizer::cleanString($_GET['date_fin'] ?? date('Y-m-d'));
        
        $data['date_debut'] = $dateDebut;
        $data['date_fin'] = $dateFin;
        
        try {
            // Requête de synthèse pour obtenir toutes les transactions entre deux dates
            $sql = "SELECT T.*, U.identifiant as caissier, C_src.numero_compte as source, C_dest.numero_compte as destination
                    FROM Transactions T
                    JOIN Utilisateurs U ON T.utilisateur_id = U.utilisateur_id
                    LEFT JOIN Comptes C_src ON T.compte_source_id = C_src.compte_id
                    LEFT JOIN Comptes C_dest ON T.compte_destination_id = C_dest.compte_id
                    WHERE DATE(T.horodatage_transaction) BETWEEN :start AND :end
                    ORDER BY T.horodatage_transaction DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start', $dateDebut);
            $stmt->bindParam(':end', $dateFin);
            $stmt->execute();
            
            $data['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            $data['error'] = "Erreur lors de la récupération des transactions.";
            error_log("Rapport BDD Erreur: " . $e->getMessage());
        }

        require_once VIEW_PATH . 'rapports/transactions.php';
    }
    
    /**
     * Simule ou exécute le processus de clôture de journée.
     * En production, cette méthode devrait insérer dans Historique_Soldes et nettoyer les tables temporaires.
     */
    public function clotureJournee() {
        $data = [];
        $data['title'] = "Clôture et Réconciliation Journalière";
        $userId = $_SESSION['user_id'];
        
        // Simuler la réconciliation (calcul des totaux du jour)
        try {
            // 1. Calculer les totaux (simplifié)
            $stmt = $this->db->query("SELECT 
                SUM(CASE WHEN type_transaction = 'DEPOT' THEN montant ELSE 0 END) AS total_depots,
                SUM(CASE WHEN type_transaction = 'RETRAIT' THEN montant ELSE 0 END) AS total_retraits,
                COUNT(*) AS total_txns
                FROM Transactions 
                WHERE DATE(horodatage_transaction) = CURDATE()"); // Transactions du jour
            
            $data['reconciliation'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Si POST (Clôture réelle demandée), on log l'événement
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'cloturer') {
                // En production: LOGIQUE D'INSERTION DANS HISTORIQUE_SOLDES ICI.
                
                $auditLogger = new AuditLogger();
                $auditLogger->logAction($userId, 'CLOTURE_JOURNEE', 'Historique_Soldes', 
                                        "Clôture journalière effectuée. Dépôts: {$data['reconciliation']['total_depots']}");

                $data['success'] = "Clôture de journée effectuée avec succès. Totaux enregistrés.";
            }

        } catch (\PDOException $e) {
            $data['error'] = "Erreur lors de la préparation de la clôture.";
            error_log("Clôture BDD Erreur: " . $e->getMessage());
        }

        require_once VIEW_PATH . 'rapports/cloture_journee.php';
    }

    // ... d'autres méthodes de rapport ...
}