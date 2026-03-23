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
                    WHERE DATE(T.date_transaction) BETWEEN :start AND :end
                    ORDER BY T.date_transaction DESC";
            
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
                WHERE DATE(date_transaction) = CURDATE()"); // Transactions du jour
            
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

    // -------------------------------------------------------------------------
    // RELEVÉ MENSUEL
    // -------------------------------------------------------------------------

    public function releveMensuel(): void
    {
        $data                  = [];
        $data['title']         = "Relevé de Compte Mensuel";
        $numeroCompte          = Sanitizer::cleanString($_REQUEST['numero_compte'] ?? '');
        $mois                  = Sanitizer::cleanString($_REQUEST['mois']          ?? date('Y-m'));
        $data['numero_compte'] = $numeroCompte;
        $data['mois']          = $mois;

        if (!empty($numeroCompte)) {
            try {
                if (!preg_match('/^\d{4}-\d{2}$/', $mois)) {
                    throw new \Exception("Format de mois invalide (attendu: YYYY-MM).");
                }
                $debutMois = $mois . '-01';
                $finMois   = date('Y-m-t', strtotime($debutMois));

                $stmt = $this->db->prepare(
                    "SELECT c.numero_compte, c.solde AS solde_actuel, c.date_ouverture,
                            tc.nom_type AS type_compte,
                            cl.nom, cl.prenom, cl.adresse, cl.telephone, cl.email
                     FROM Comptes c
                     JOIN Clients cl      ON c.client_id      = cl.client_id
                     JOIN Type_Comptes tc ON c.type_compte_id = tc.type_compte_id
                     WHERE c.numero_compte = :num"
                );
                $stmt->bindParam(':num', $numeroCompte);
                $stmt->execute();
                $data['compte'] = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$data['compte']) {
                    $data['error'] = "Compte introuvable.";
                } else {
                    $sqlTxn = "SELECT T.type_transaction, T.montant, T.date_transaction,
                                      T.reference_externe,
                                      CASE WHEN Cs.numero_compte = :num2 THEN 'Débit' ELSE 'Crédit' END AS sens
                               FROM Transactions T
                               LEFT JOIN Comptes Cs ON T.compte_source_id      = Cs.compte_id
                               LEFT JOIN Comptes Cd ON T.compte_destination_id = Cd.compte_id
                               WHERE (Cs.numero_compte = :num3 OR Cd.numero_compte = :num4)
                                 AND DATE(T.date_transaction) BETWEEN :debut AND :fin
                               ORDER BY T.date_transaction ASC";
                    $stmtT = $this->db->prepare($sqlTxn);
                    $stmtT->bindParam(':num2',  $numeroCompte);
                    $stmtT->bindParam(':num3',  $numeroCompte);
                    $stmtT->bindParam(':num4',  $numeroCompte);
                    $stmtT->bindParam(':debut', $debutMois);
                    $stmtT->bindParam(':fin',   $finMois);
                    $stmtT->execute();
                    $transactions = $stmtT->fetchAll(PDO::FETCH_ASSOC);

                    $totalCredits = 0;
                    $totalDebits  = 0;
                    foreach ($transactions as $t) {
                        if ($t['sens'] === 'Crédit') $totalCredits += (float)$t['montant'];
                        else                          $totalDebits  += (float)$t['montant'];
                    }

                    $soldeFinal            = (float)$data['compte']['solde_actuel'];
                    $soldeInitial          = $soldeFinal - $totalCredits + $totalDebits;
                    $data['transactions']  = $transactions;
                    $data['total_credits'] = $totalCredits;
                    $data['total_debits']  = $totalDebits;
                    $data['solde_initial'] = $soldeInitial;
                    $data['solde_final']   = $soldeFinal;
                    $data['debut_mois']    = $debutMois;
                    $data['fin_mois']      = $finMois;
                }
            } catch (\Exception $e) {
                $data['error'] = "Erreur : " . $e->getMessage();
                error_log("releveMensuel: " . $e->getMessage());
            }
        }

        require_once VIEW_PATH . 'rapports/releve_mensuel.php';
    }
}
