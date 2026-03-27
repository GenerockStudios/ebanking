<?php
/**
 * SessionModel.php
 * Gère les sessions de caisse (Ouverture, Calcul des soldes, Clôture).
 */

class SessionModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère la session ouverte pour un utilisateur donné.
     */
    public function getOpenSession(int $userId): ?object {
        try {
            // FIX: table name → lowercase 'sessions_caisse'
            $sql = "SELECT * FROM sessions_caisse 
                    WHERE utilisateur_id = :userId 
                    AND est_cloture = 0 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
        } catch (PDOException $e) {
            error_log("getOpenSession Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ouvre une nouvelle session de caisse.
     */
    public function openSession(int $userId, float $initialCash): bool {
        try {
            // FIX: table name → lowercase 'sessions_caisse'
            $sql = "INSERT INTO sessions_caisse (utilisateur_id, date_ouverture, heure_ouverture, solde_initial_caisse, est_cloture) 
                    VALUES (:userId, CURDATE(), CURTIME(), :initialCash, 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':userId',      $userId,      PDO::PARAM_INT);
            $stmt->bindValue(':initialCash', $initialCash, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("openSession Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule le solde théorique (système) basé sur les transactions de la session.
     * Solde Système = Solde Initial + Somme(DEPOT) - Somme(RETRAIT)
     */
    public function calculateSystemBalance(int $sessionId, int $userId): float {
        try {
            // FIX: table names → lowercase 'sessions_caisse', 'transactions'
            $sqlSession = "SELECT date_ouverture, heure_ouverture FROM sessions_caisse WHERE session_id = :sid";
            $stmtS = $this->db->prepare($sqlSession);
            $stmtS->bindValue(':sid', $sessionId, PDO::PARAM_INT);
            $stmtS->execute();
            $session = $stmtS->fetch(PDO::FETCH_OBJ);

            if (!$session) return 0.0;

            $startDateTime = $session->date_ouverture . ' ' . $session->heure_ouverture;

            $sql = "SELECT 
                        SUM(CASE WHEN type_transaction = 'DEPOT' THEN montant ELSE 0 END) as total_depots,
                        SUM(CASE WHEN type_transaction = 'RETRAIT' THEN montant ELSE 0 END) as total_retraits
                    FROM transactions 
                    WHERE utilisateur_id = :userId 
                    AND date_transaction >= :start";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':userId', $userId,        PDO::PARAM_INT);
            $stmt->bindValue(':start',  $startDateTime, PDO::PARAM_STR);
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_OBJ);

            $sqlInit = "SELECT solde_initial_caisse FROM sessions_caisse WHERE session_id = :sid";
            $stmtI = $this->db->prepare($sqlInit);
            $stmtI->bindValue(':sid', $sessionId, PDO::PARAM_INT);
            $stmtI->execute();
            $initial = $stmtI->fetchColumn();

            return (float)$initial + (float)($totals->total_depots ?? 0) - (float)($totals->total_retraits ?? 0);
        } catch (PDOException $e) {
            error_log("calculateSystemBalance Error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Clôture la session avec les montants finaux.
     */
    public function closeSession(int $sessionId, float $finalSystem, float $finalReal): bool {
        try {
            $difference = $finalReal - $finalSystem;
            // FIX: table name → lowercase 'sessions_caisse'
            $sql = "UPDATE sessions_caisse 
                    SET heure_fermeture = CURTIME(), 
                        solde_final_systeme = :finalSystem, 
                        solde_final_reel = :finalReal, 
                        difference = :diff, 
                        est_cloture = 1 
                    WHERE session_id = :sid";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':finalSystem', $finalSystem, PDO::PARAM_STR);
            $stmt->bindValue(':finalReal',   $finalReal,   PDO::PARAM_STR);
            $stmt->bindValue(':diff',        $difference,  PDO::PARAM_STR);
            $stmt->bindValue(':sid',         $sessionId,   PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("closeSession Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les détails d'une session pour le PV.
     */
    public function getSessionDetails(int $sessionId): ?object {
        try {
            // FIX: table names → lowercase 'sessions_caisse', 'utilisateurs'
            $sql = "SELECT s.*, u.nom_complet as caissier_nom 
                    FROM sessions_caisse s
                    JOIN utilisateurs u ON s.utilisateur_id = u.utilisateur_id
                    WHERE s.session_id = :sid";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':sid', $sessionId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
        } catch (PDOException $e) {
            error_log("getSessionDetails Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Statistiques de transactions pour la session (pour le PV).
     */
    public function getSessionStats(int $sessionId, int $userId): array {
         try {
            $session = $this->getSessionDetails($sessionId);
            $start = $session->date_ouverture . ' ' . $session->heure_ouverture;
            
            // FIX: table name → lowercase 'transactions'
            $sql = "SELECT type_transaction, COUNT(*) as nb, SUM(montant) as total 
                    FROM transactions 
                    WHERE utilisateur_id = :userId 
                    AND date_transaction >= :start
                    GROUP BY type_transaction";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':start',  $start,  PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
