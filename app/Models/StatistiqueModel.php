<?php
/**
 * StatistiqueModel.php
 * Gère les calculs analytiques et les snapshots financiers.
 */

class StatistiqueModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère les dates uniques de snapshot disponibles dans la base.
     * @return array
     */
    public function getAvailableSnapshotDates(): array {
        try {
            $sql = "SELECT DISTINCT date_snapshot 
                    FROM historique_soldes 
                    ORDER BY date_snapshot DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (PDOException $e) {
            error_log("StatistiqueModel::getAvailableSnapshotDates error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un snapshot financier agrégé par type de compte pour une date donnée.
     * @param string $date format YYYY-MM-DD
     * @return array
     */
    public function getFinancialSnapshot(string $date): array {
        try {
            // Jointure pour avoir le nom du type de compte
            $sql = "SELECT tc.nom_type AS categorie, SUM(hs.solde_final) AS total_solde
                    FROM historique_soldes hs
                    JOIN comptes c ON hs.compte_id = c.compte_id
                    JOIN type_comptes tc ON c.type_compte_id = tc.type_compte_id
                    WHERE hs.date_snapshot = :date
                    GROUP BY tc.nom_type
                    ORDER BY total_solde DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':date', $date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("StatistiqueModel::getFinancialSnapshot error ($date): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcule l'évolution entre deux snapshots.
     * @param array $current Données du snapshot actuel
     * @param array $previous Données du snapshot précédent (M-1)
     * @return array
     */
    public function getEvolutionData(array $current, array $previous): array {
        $report = [];
        
        // On map les données précédentes par catégorie pour un accès facile
        $prevMap = [];
        foreach ($previous as $p) {
            $prevMap[$p['categorie']] = (float)$p['total_solde'];
        }

        foreach ($current as $c) {
            $cat = $c['categorie'];
            $currVal = (float)$c['total_solde'];
            $prevVal = $prevMap[$cat] ?? 0.0;
            
            $evolution = 0.0;
            if ($prevVal > 0) {
                $evolution = (($currVal - $prevVal) / $prevVal) * 100;
            } elseif ($currVal > 0) {
                $evolution = 100.0; // 100% de croissance si M-1 était à 0
            }

            $report[] = [
                'categorie' => $cat,
                'solde_m' => $currVal,
                'solde_m_1' => $prevVal,
                'evolution_pct' => round($evolution, 2)
            ];
        }

        return $report;
    }
}
