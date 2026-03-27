<?php
/**
 * PlafondModel.php
 * Gère les limites (plafonds) de retrait, dépôt et transfert pour les comptes.
 */

class PlafondModel {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les comptes avec leurs plafonds (ou valeurs par défaut si non définis).
     */
    public function getAllAccountPlafonds(): array {
        try {
            // FIX: table names → lowercase 'comptes', 'clients', 'plafonds_comptes'
            $sql = "SELECT c.compte_id, c.numero_compte, c.solde,
                           cl.nom, cl.prenom,
                           p.plafond_retrait_journalier, p.plafond_depot_journalier, p.plafond_transfert_mensuel
                    FROM comptes c
                    JOIN clients cl ON c.client_id = cl.client_id
                    LEFT JOIN plafonds_comptes p ON c.compte_id = p.compte_id
                    ORDER BY cl.nom ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PlafondModel::getAllAccountPlafonds error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les plafonds d'un compte spécifique.
     */
    public function getPlafondByCompteId(int $compteId) {
        try {
            // FIX: table names → lowercase 'comptes', 'clients', 'plafonds_comptes'
            $sql = "SELECT c.compte_id, c.numero_compte, cl.nom, cl.prenom,
                           p.plafond_retrait_journalier, p.plafond_depot_journalier, p.plafond_transfert_mensuel
                    FROM comptes c
                    JOIN clients cl ON c.client_id = cl.client_id
                    LEFT JOIN plafonds_comptes p ON c.compte_id = p.compte_id
                    WHERE c.compte_id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $compteId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PlafondModel::getPlafondByCompteId error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour ou insère les plafonds pour un compte (UPSERT).
     */
    public function updatePlafond(int $compteId, float $retrait, float $depot, float $transfert): bool {
        try {
            // FIX: table name → lowercase 'plafonds_comptes'
            // Utilisation d'un vrai UPSERT MariaDB pour atomicité
            $sql = "INSERT INTO plafonds_comptes (compte_id, plafond_retrait_journalier, plafond_depot_journalier, plafond_transfert_mensuel)
                    VALUES (:id, :retrait, :depot, :transfert)
                    ON DUPLICATE KEY UPDATE
                        plafond_retrait_journalier = :retrait2,
                        plafond_depot_journalier   = :depot2,
                        plafond_transfert_mensuel  = :transfert2";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',         $compteId,  PDO::PARAM_INT);
            $stmt->bindValue(':retrait',    $retrait,   PDO::PARAM_STR);
            $stmt->bindValue(':depot',      $depot,     PDO::PARAM_STR);
            $stmt->bindValue(':transfert',  $transfert, PDO::PARAM_STR);
            $stmt->bindValue(':retrait2',   $retrait,   PDO::PARAM_STR);
            $stmt->bindValue(':depot2',     $depot,     PDO::PARAM_STR);
            $stmt->bindValue(':transfert2', $transfert, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("PlafondModel::updatePlafond error: " . $e->getMessage());
            return false;
        }
    }
}
