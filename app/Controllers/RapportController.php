<?php
/**
 * RapportController.php
 * Gère le flux pour les rapports financiers et d'audit, ainsi que la clôture de journée.
 */

class RapportController {
    
    private $db;
    private $compteModel;

    public function __construct() {
        AuthController::checkPermission('Superviseur'); 

        $this->db = Database::getInstance()->getConnection();
        $this->compteModel = new CompteModel();
    }
    
    /**
     * Affiche un rapport agrégé des transactions pour une période donnée.
     */
    public function rapportTransactions() {
        $data          = [];
        $data['title'] = "Rapport des Transactions Détaillées";
        
        $dateDebut = Sanitizer::cleanString($_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days')));
        $dateFin   = Sanitizer::cleanString($_GET['date_fin']   ?? date('Y-m-d'));
        
        $data['date_debut'] = $dateDebut;
        $data['date_fin']   = $dateFin;
        
        try {
            // FIX: table names → lowercase 'transactions', 'utilisateurs', 'comptes'
            $sql = "SELECT T.*, U.identifiant as caissier, C_src.numero_compte as source, C_dest.numero_compte as destination
                    FROM transactions T
                    JOIN utilisateurs U ON T.utilisateur_id = U.utilisateur_id
                    LEFT JOIN comptes C_src ON T.compte_source_id = C_src.compte_id
                    LEFT JOIN comptes C_dest ON T.compte_destination_id = C_dest.compte_id
                    WHERE DATE(T.date_transaction) BETWEEN :start AND :end
                    ORDER BY T.date_transaction DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start', $dateDebut, PDO::PARAM_STR);
            $stmt->bindValue(':end',   $dateFin,   PDO::PARAM_STR);
            $stmt->execute();
            
            $data['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            $data['error'] = "Erreur lors de la récupération des transactions.";
            error_log("Rapport BDD Erreur: " . $e->getMessage());
        }

        require_once VIEW_PATH . 'rapports/transactions.php';
    }
    
    /**
     * Exécute la clôture de journée : snapshot dans historique_soldes + audit.
     * FIX CRITIQUE: L'INSERT réel dans historique_soldes est maintenant implémenté.
     */
    public function clotureJournee() {
        $data          = [];
        $data['title'] = "Clôture et Réconciliation Journalière";
        $userId        = $_SESSION['user_id'];
        
        try {
            // 1. Calculer les totaux du jour
            // FIX: table name → lowercase 'transactions'
            $stmt = $this->db->query(
                "SELECT 
                    SUM(CASE WHEN type_transaction = 'DEPOT'   THEN montant ELSE 0 END) AS total_depots,
                    SUM(CASE WHEN type_transaction = 'RETRAIT' THEN montant ELSE 0 END) AS total_retraits,
                    COUNT(*) AS total_txns
                 FROM transactions 
                 WHERE DATE(date_transaction) = CURDATE()"
            );
            $data['reconciliation'] = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cloturer') {
                
                // FIX: INSERT réel dans historique_soldes (était absent — module analytics était cassé)
                // Snapshots de tous les comptes actifs pour aujourd'hui
                // FIX: table names → lowercase 'historique_soldes', 'comptes'
                $stmtSnap = $this->db->prepare(
                    "INSERT INTO historique_soldes (compte_id, date_snapshot, solde_final)
                     SELECT compte_id, CURDATE(), solde
                     FROM comptes
                     WHERE est_actif = 1
                     ON DUPLICATE KEY UPDATE solde_final = VALUES(solde_final)"
                );
                $stmtSnap->execute();
                $nbSnapshots = $stmtSnap->rowCount();

                $auditLogger = new AuditLogger();
                $auditLogger->logAction(
                    $userId, 
                    'CLOTURE_JOURNEE', 
                    'historique_soldes', 
                    "Clôture journalière effectuée. Dépôts: {$data['reconciliation']['total_depots']}. Snapshots: {$nbSnapshots} comptes."
                );

                $data['success'] = "Clôture de journée effectuée avec succès. {$nbSnapshots} snapshots enregistrés.";
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

                // FIX: table names → lowercase 'comptes', 'clients', 'type_comptes'
                $stmt = $this->db->prepare(
                    "SELECT c.numero_compte, c.solde AS solde_actuel, c.date_ouverture,
                            tc.nom_type AS type_compte,
                            cl.nom, cl.prenom, cl.adresse, cl.telephone, cl.email
                     FROM comptes c
                     JOIN clients cl      ON c.client_id      = cl.client_id
                     JOIN type_comptes tc ON c.type_compte_id = tc.type_compte_id
                     WHERE c.numero_compte = :num"
                );
                $stmt->bindValue(':num', $numeroCompte, PDO::PARAM_STR);
                $stmt->execute();
                $data['compte'] = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$data['compte']) {
                    $data['error'] = "Compte introuvable.";
                } else {
                    // FIX: table names → lowercase 'transactions', 'comptes'
                    $sqlTxn = "SELECT T.type_transaction, T.montant, T.date_transaction,
                                      T.reference_externe,
                                      CASE WHEN Cs.numero_compte = :num2 THEN 'Débit' ELSE 'Crédit' END AS sens
                               FROM transactions T
                               LEFT JOIN comptes Cs ON T.compte_source_id      = Cs.compte_id
                               LEFT JOIN comptes Cd ON T.compte_destination_id = Cd.compte_id
                               WHERE (Cs.numero_compte = :num3 OR Cd.numero_compte = :num4)
                                 AND DATE(T.date_transaction) BETWEEN :debut AND :fin
                               ORDER BY T.date_transaction ASC";
                    $stmtT = $this->db->prepare($sqlTxn);
                    $stmtT->bindValue(':num2',  $numeroCompte, PDO::PARAM_STR);
                    $stmtT->bindValue(':num3',  $numeroCompte, PDO::PARAM_STR);
                    $stmtT->bindValue(':num4',  $numeroCompte, PDO::PARAM_STR);
                    $stmtT->bindValue(':debut', $debutMois,    PDO::PARAM_STR);
                    $stmtT->bindValue(':fin',   $finMois,      PDO::PARAM_STR);
                    $stmtT->execute();
                    $transactions = $stmtT->fetchAll(PDO::FETCH_ASSOC);

                    $totalCredits = 0;
                    $totalDebits  = 0;
                    foreach ($transactions as $t) {
                        if ($t['sens'] === 'Crédit') $totalCredits += (float)$t['montant'];
                        else                          $totalDebits  += (float)$t['montant'];
                    }

                    $soldeFinal                = (float)$data['compte']['solde_actuel'];
                    $soldeInitial              = $soldeFinal - $totalCredits + $totalDebits;
                    $data['transactions']      = $transactions;
                    $data['total_credits']     = $totalCredits;
                    $data['total_debits']      = $totalDebits;
                    $data['solde_initial']     = $soldeInitial;
                    $data['solde_final']       = $soldeFinal;
                    $data['debut_mois']        = $debutMois;
                    $data['fin_mois']          = $finMois;
                }
            } catch (\Exception $e) {
                $data['error'] = "Erreur : " . $e->getMessage();
                error_log("releveMensuel: " . $e->getMessage());
            }
        }

        require_once VIEW_PATH . 'rapports/releve_mensuel.php';
    }
}
