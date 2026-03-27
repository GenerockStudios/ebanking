<?php
/**
 * KycModel.php
 * Gère l'analyse de conformité des documents KYC.
 * Détecte les anomalies telles que les CNI expirées et les passeports non validés.
 */

class KycModel {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Analyse massivement les documents KYC pour extraire les anomalies de conformité.
     * @param array $filters Filtres optionnels (type_document, client_id)
     * @return array Liste des anomalies avec informations clients
     */
    public function getKycAnomalies(array $filters = []): array {
        try {
            // FIX: table names → lowercase 'documents_kyc', 'clients', 'utilisateurs'
            $sql = "SELECT d.*, c.nom, c.prenom, c.telephone, c.email, 
                           u.nom_complet as validateur_nom
                    FROM documents_kyc d
                    INNER JOIN clients c ON d.client_id = c.client_id
                    LEFT JOIN utilisateurs u ON d.valide_par_utilisateur_id = u.utilisateur_id
                    WHERE (
                        (d.date_expiration < CURDATE() AND d.date_expiration IS NOT NULL) 
                        OR (d.type_document = 'PASSEPORT' AND d.est_valide = 0)
                        OR (d.est_valide = 0 AND d.type_document = 'CNI')
                    )";
            
            $params = [];
            if (!empty($filters['type_document'])) {
                $sql .= " AND d.type_document = :type";
                $params[':type'] = $filters['type_document'];
            }

            $sql .= " ORDER BY d.date_expiration ASC, c.nom ASC";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("KycModel::getKycAnomalies error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre total d'anomalies KYC pour le dashboard.
     */
    public function getAnomalyCount(): int {
        try {
            // FIX: table name → lowercase 'documents_kyc'
            $sql = "SELECT COUNT(*) as nb 
                    FROM documents_kyc 
                    WHERE (date_expiration < CURDATE() AND date_expiration IS NOT NULL) 
                    OR (type_document = 'PASSEPORT' AND est_valide = 0)
                    OR (est_valide = 0 AND type_document = 'CNI')";
            $stmt = $this->db->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['nb'] ?? 0);
        } catch (\PDOException $e) {
            error_log("KycModel::getAnomalyCount error: " . $e->getMessage());
            return 0;
        }
    }
}
