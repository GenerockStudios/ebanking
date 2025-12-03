<?php
/**
 * AuditLogger.php
 * Classe utilitaire pour enregistrer toutes les actions critiques dans la table Journal_Audit.
 * Cette classe est essentielle pour la conformité et la sécurité (Qui, Quoi, Quand).
 */

class AuditLogger {
    
    private $db;

    public function __construct() {
        // Obtient l'instance de la connexion PDO
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Enregistre une action d'audit dans la base de données.
     * * @param int|null $userId ID de l'utilisateur (NULL pour les actions non authentifiées, ex: échec de connexion).
     * @param string $actionType Type de l'action (ex: 'DEPOT', 'MODIF_CLIENT', 'CONNEXION_FAILURE').
     * @param string|null $tableAffected Nom de la table affectée (optionnel).
     * @param string $details Description détaillée de l'opération.
     * @param string|null $elementId ID de l'élément affecté (ex: numero_compte, client_id).
     * @return bool Vrai si l'enregistrement a réussi, Faux sinon.
     */
    public function logAction(
        ?int $userId, 
        string $actionType, 
        ?string $tableAffected, 
        string $details, 
        ?string $elementId = null
    ): bool {
        
        try {
            $sql = "INSERT INTO Journal_Audit (utilisateur_id, type_action, table_affectee, identifiant_element_affecte, details) 
                    VALUES (:userId, :actionType, :tableAffected, :elementId, :details)";
            
            $stmt = $this->db->prepare($sql);
            
            // Les ID utilisateurs peuvent être NULL (ex: échec de connexion)
            if ($userId !== null) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':userId', null, PDO::PARAM_NULL);
            }

            $stmt->bindParam(':actionType', $actionType);
            $stmt->bindParam(':tableAffected', $tableAffected);
            $stmt->bindParam(':elementId', $elementId);
            $stmt->bindParam(':details', $details);
            
            return $stmt->execute();
            
        } catch (\PDOException $e) {
            // C'est critique : si l'audit échoue, on doit le logger ailleurs (logs système ou fichier)
            error_log("CRITICAL AUDIT FAILURE: Impossible d'écrire dans Journal_Audit. Détails: " . $e->getMessage());
            // L'application doit continuer même si l'audit échoue (selon la politique), mais l'alerte est cruciale.
            return false;
        }
    }
}