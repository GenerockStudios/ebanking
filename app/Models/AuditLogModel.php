<?php
/**
 * AuditLogModel.php
 * Modèle spécialisé pour l'extraction et l'analyse du journal d'audit.
 */

class AuditLogModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recherche avancée multicritère dans le journal d'audit.
     * 
     * @param array $filters Filtres : date_debut, date_fin, user_id, type_action, table_affectee
     * @return array Résultats de la recherche
     */
    public function searchLogs(array $filters): array
    {
        try {
            // FIX: table names → lowercase 'journal_audit', 'utilisateurs', 'roles'
            $sql = "SELECT ja.log_id, ja.date_heure, ja.type_action, ja.table_affectee, 
                           ja.identifiant_element_affecte, ja.details,
                           u.nom_complet, u.identifiant as username, r.nom_role
                    FROM journal_audit ja
                    LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.utilisateur_id
                    LEFT JOIN roles r ON u.role_id = r.role_id
                    WHERE 1=1";
            
            $params = [];

            if (!empty($filters['date_debut'])) {
                $sql .= " AND ja.date_heure >= :date_debut";
                $params[':date_debut'] = $filters['date_debut'] . " 00:00:00";
            }
            if (!empty($filters['date_fin'])) {
                $sql .= " AND ja.date_heure <= :date_fin";
                $params[':date_fin'] = $filters['date_fin'] . " 23:59:59";
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND ja.utilisateur_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }

            if (!empty($filters['type_action'])) {
                $sql .= " AND ja.type_action LIKE :type_action";
                $params[':type_action'] = '%' . $filters['type_action'] . '%';
            }

            if (!empty($filters['table_affectee'])) {
                $sql .= " AND ja.table_affectee LIKE :table_affectee";
                $params[':table_affectee'] = '%' . $filters['table_affectee'] . '%';
            }

            if (!empty($filters['target_id'])) {
                $sql .= " AND ja.identifiant_element_affecte = :target_id";
                $params[':target_id'] = $filters['target_id'];
            }

            if (($filters['only_failures'] ?? false) === true) {
                $sql .= " AND (ja.type_action LIKE '%_FAILURE%' OR ja.type_action LIKE '%ERROR%' OR ja.type_action LIKE '%DENIED%' OR ja.type_action LIKE '%ALERT%')";
            }

            $sql .= " ORDER BY ja.date_heure DESC LIMIT 1000";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("AuditLogModel::searchLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques d'audit pour le rapport de sécurité.
     */
    public function getSecurityStats(string $start, string $end): array
    {
        try {
            $stats = [];
            
            // FIX: table names → lowercase 'journal_audit', 'utilisateurs'
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM journal_audit WHERE date_heure BETWEEN :s AND :e");
            $stmt->execute([':s' => $start . " 00:00:00", ':e' => $end . " 23:59:59"]);
            $stats['total_actions'] = $stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM journal_audit WHERE (type_action LIKE '%_FAILURE%' OR type_action LIKE '%ERROR%') AND date_heure BETWEEN :s AND :e");
            $stmt->execute([':s' => $start . " 00:00:00", ':e' => $end . " 23:59:59"]);
            $stats['total_failures'] = $stmt->fetchColumn();

            $stmt = $this->db->prepare(
                "SELECT u.identifiant, COUNT(*) as nb 
                 FROM journal_audit ja 
                 JOIN utilisateurs u ON ja.utilisateur_id = u.utilisateur_id 
                 WHERE ja.date_heure BETWEEN :s AND :e 
                 GROUP BY u.identifiant 
                 ORDER BY nb DESC 
                 LIMIT 5"
            );
            $stmt->execute([':s' => $start . " 00:00:00", ':e' => $end . " 23:59:59"]);
            $stats['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (PDOException $e) {
            error_log("AuditLogModel::getSecurityStats Error: " . $e->getMessage());
            return [];
        }
    }
}
