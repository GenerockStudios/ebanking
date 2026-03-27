<?php
/**
 * ClientModel.php
 * Gère la logique liée aux clients, à l'enregistrement des données (KYC de base) et aux documents.
 */

class ClientModel {
    
    private $db;

    public function __construct() {
        // Récupère l'objet PDO via la classe Database (Singleton)
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crée un nouveau client dans la base de données.
     * @param array $data Les données du client (nom, prenom, numero_identite, etc.).
     * @return int|bool L'ID du nouveau client inséré, ou FALSE en cas d'échec.
     */
    public function createClient(array $data) {
        try {
            // FIX: table name → lowercase 'clients' (Linux-safe)
            $sql = "INSERT INTO clients (nom, prenom, date_naissance, adresse, telephone, email, numero_identite) 
                    VALUES (:nom, :prenom, :dob, :adresse, :tel, :email, :identite)";
            
            $stmt = $this->db->prepare($sql);
            
            // Les données sont supposées avoir été nettoyées par Sanitizer avant d'arriver ici
            $stmt->bindValue(':nom',      $data['nom'],             PDO::PARAM_STR);
            $stmt->bindValue(':prenom',   $data['prenom'],          PDO::PARAM_STR);
            $stmt->bindValue(':dob',      $data['date_naissance'],  PDO::PARAM_STR);
            $stmt->bindValue(':adresse',  $data['adresse'],         PDO::PARAM_STR);
            $stmt->bindValue(':tel',      $data['telephone'],       PDO::PARAM_STR);
            $stmt->bindValue(':email',    $data['email'],           PDO::PARAM_STR);
            $stmt->bindValue(':identite', $data['numero_identite'], PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Retourne l'ID de la dernière insertion (nécessaire pour créer le compte ensuite)
                return $this->db->lastInsertId();
            }
            return false;

        } catch (\PDOException $e) {
            // Log de l'erreur (ex: violation de contrainte UNIQUE sur numero_identite ou email)
            error_log("Erreur BDD lors de la création client : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les informations d'un client.
     * @param int $clientId L'ID du client.
     * @return array|false
     */
    public function getClientInfo(int $clientId) {
        // FIX: table name → lowercase 'clients'
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE client_id = :id");
        $stmt->bindValue(':id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre une référence de document KYC fourni par le client.
     * @param int $clientId
     * @param string $docType Type de document (CNI, Domicile, etc.)
     * @param string $fileRef Chemin/référence du fichier stocké
     * @param int $validatorId ID de l'utilisateur ayant validé
     * @return bool
     */
    public function addKycDocument(int $clientId, string $docType, string $fileRef, int $validatorId): bool {
        try {
            // FIX: table name → lowercase 'documents_kyc'
            // FIX: boolean TRUE → 1 (PDO::PARAM_INT) pour tinyint(1)
            $sql = "INSERT INTO documents_kyc (client_id, type_document, reference_fichier, valide_par_utilisateur_id, est_valide)
                    VALUES (:clientId, :docType, :fileRef, :validatorId, :estValide)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':clientId',    $clientId,    PDO::PARAM_INT);
            $stmt->bindValue(':docType',     $docType,     PDO::PARAM_STR);
            $stmt->bindValue(':fileRef',     $fileRef,     PDO::PARAM_STR);
            $stmt->bindValue(':validatorId', $validatorId, PDO::PARAM_INT);
            $stmt->bindValue(':estValide',   1,            PDO::PARAM_INT); // FIX: 1 au lieu de TRUE

            return $stmt->execute();
            
        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'ajout d'un document KYC : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère la liste des documents KYC d'un client.
     * @param int $clientId
     * @return array
     */
    public function getClientDocuments(int $clientId): array {
        try {
            // FIX: table names → lowercase 'documents_kyc', 'utilisateurs'
            $sql = "SELECT d.*, u.nom_complet as validateur 
                    FROM documents_kyc d
                    LEFT JOIN utilisateurs u ON d.valide_par_utilisateur_id = u.utilisateur_id
                    WHERE d.client_id = :clientId
                    ORDER BY d.date_validation DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':clientId', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getClientDocuments error: " . $e->getMessage());
            return [];
        }
    }
}