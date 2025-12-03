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
            $sql = "INSERT INTO Clients (nom, prenom, date_naissance, adresse, telephone, email, numero_identite) 
                    VALUES (:nom, :prenom, :dob, :adresse, :tel, :email, :identite)";
            
            $stmt = $this->db->prepare($sql);
            
            // Les données sont supposées avoir été nettoyées par Sanitizer avant d'arriver ici
            $stmt->bindParam(':nom', $data['nom']);
            $stmt->bindParam(':prenom', $data['prenom']);
            $stmt->bindParam(':dob', $data['date_naissance']);
            $stmt->bindParam(':adresse', $data['adresse']);
            $stmt->bindParam(':tel', $data['telephone']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':identite', $data['numero_identite']);

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
     * @return object|false
     */
    public function getClientInfo(int $clientId) {
        $stmt = $this->db->prepare("SELECT * FROM Clients WHERE client_id = :id");
        $stmt->bindParam(':id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
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
            $sql = "INSERT INTO Documents_KYC (client_id, type_document, reference_fichier, valide_par_utilisateur_id, est_valide)
                    VALUES (:clientId, :docType, :fileRef, :validatorId, TRUE)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':clientId', $clientId, PDO::PARAM_INT);
            $stmt->bindParam(':docType', $docType);
            $stmt->bindParam(':fileRef', $fileRef);
            $stmt->bindParam(':validatorId', $validatorId, PDO::PARAM_INT);

            return $stmt->execute();
            
        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'ajout d'un document KYC : " . $e->getMessage());
            return false;
        }
    }

    // ... d'autres méthodes comme updateClient, getClientDocuments, etc.
}