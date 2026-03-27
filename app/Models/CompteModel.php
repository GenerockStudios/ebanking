<?php
/**
 * CompteModel.php
 * Gère la logique liée aux comptes bancaires (ouverture, consultation du solde).
 */

class CompteModel {
    
    private $db;
    private $refGenerator;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Le modèle de compte DOIT avoir accès au générateur de références
        $this->refGenerator = new ReferenceGenerator(); 
    }

    /**
     * Ouvre un nouveau compte bancaire pour un client donné.
     * @param int $clientId L'ID du client.
     * @param int $typeCompteId L'ID du type de compte.
     * @param int $utilisateurId L'ID de l'utilisateur qui ouvre le compte.
     * @param float $soldeInitial Le solde de départ (normalement 0.00).
     * @return string|false Le numéro de compte créé, ou FALSE en cas d'échec.
     */
    public function openNewAccount(int $clientId, int $typeCompteId, int $utilisateurId, float $soldeInitial = 0.00) {
        try {
            // 1. Générer le numéro de compte unique
            $numeroCompte = $this->refGenerator->generateUniqueAccountNumber();
            
            // FIX: table name → lowercase 'comptes'
            $sql = "INSERT INTO comptes (client_id, type_compte_id, numero_compte, solde) 
                    VALUES (:clientId, :typeId, :numeroCompte, :solde)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':clientId',     $clientId,     PDO::PARAM_INT);
            $stmt->bindValue(':typeId',        $typeCompteId, PDO::PARAM_INT);
            $stmt->bindValue(':numeroCompte',  $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':solde',         $soldeInitial, PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Retourne le numéro de compte pour référence
                return $numeroCompte;
            }
            return false;

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'ouverture de compte : " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
             // Capturer l'exception si ReferenceGenerator n'arrive pas à générer un numéro unique
            error_log("Erreur critique du générateur de référence : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère le solde courant d'un compte.
     * @param string $numeroCompte Le numéro de compte.
     * @return float|false Le solde, ou FALSE si le compte n'existe pas.
     */
    public function getAccountBalance(string $numeroCompte) {
        try {
            // FIX: table name → lowercase 'comptes'
            $stmt = $this->db->prepare("SELECT solde FROM comptes WHERE numero_compte = :numeroCompte");
            $stmt->bindValue(':numeroCompte', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            
            $solde = $stmt->fetchColumn();
            
            // fetchColumn retourne la colonne ou FALSE si aucune ligne n'est trouvée
            return is_numeric($solde) ? (float)$solde : false; 

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de la récupération du solde : " . $e->getMessage());
            return false;
        }
    }
    

    
    /**
     * Récupère les informations associées au compte de l'utilisateur.
     * @param string $numeroCompte Le numéro de compte.
     * @return array|false Les données du compte, ou FALSE si le compte n'existe pas.
     */
    public function getAccountInfos(string $numeroCompte) {
        try {
            // FIX: table name → lowercase 'comptes'
            // FIX CRITIQUE: execute() manquant — la requête n'était jamais exécutée
            // FIX: fetchColumn() → fetch() car on sélectionne plusieurs colonnes
            $stmt = $this->db->prepare(
                "SELECT compte_id, client_id, type_compte_id, solde, date_ouverture
                 FROM comptes
                 WHERE numero_compte = :numeroCompte"
            );
            $stmt->bindValue(':numeroCompte', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute(); // FIX: appel execute() obligatoire
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de la récupération des infos compte : " . $e->getMessage());
            return false;
        }
    }



    /**
     * Récupère toutes les transactions (débit/crédit) pour un compte donné.
     * @param string $numeroCompte Le numéro de compte.
     * @return array La liste des transactions.
     */
    public function getAccountHistory(string $numeroCompte): array {
        try {
            // FIX: table names → lowercase 'transactions', 'comptes'
            $sql = "
                SELECT 
                    T.date_transaction, 
                    T.type_transaction, 
                    T.montant, 
                    T.reference_externe,
                    CASE 
                        WHEN C_Source.numero_compte = :numCompte THEN 'Débit' 
                        ELSE 'Crédit' 
                    END as sens_flux
                FROM transactions T
                LEFT JOIN comptes C_Source ON T.compte_source_id = C_Source.compte_id
                LEFT JOIN comptes C_Dest ON T.compte_destination_id = C_Dest.compte_id
                WHERE C_Source.numero_compte = :numCompte OR C_Dest.numero_compte = :numCompte
                ORDER BY T.date_transaction DESC
                LIMIT 100
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':numCompte', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'historique : " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les détails complets d'un compte et de son titulaire pour l'édition d'un RIB.
     * @param string $numeroCompte Le numéro de compte.
     * @return object|false Les données (client, compte, type) ou FALSE si introuvable.
     */
    public function getCompteDetailsForRib(string $numeroCompte): object|false {
        try {
            // FIX: table names → lowercase 'comptes', 'clients', 'type_comptes'
            $sql = "SELECT c.compte_id, c.numero_compte, c.solde, c.date_ouverture,
                           cl.nom, cl.prenom, cl.adresse, cl.telephone, cl.email,
                           tc.nom_type AS type_compte
                    FROM comptes c
                    JOIN clients cl ON c.client_id = cl.client_id
                    JOIN type_comptes tc ON c.type_compte_id = tc.type_compte_id
                    WHERE c.numero_compte = :numeroCompte";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':numeroCompte', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_OBJ) ?: false;

        } catch (\PDOException $e) {
            error_log("Erreur BDD getCompteDetailsForRib : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les comptes d'un client spécifique.
     * @param int $clientId
     * @return array
     */
    public function getAccountsByClientId(int $clientId): array {
        try {
            // FIX: table names → lowercase 'comptes', 'type_comptes'
            $sql = "SELECT c.*, tc.nom_type 
                    FROM comptes c
                    JOIN type_comptes tc ON c.type_compte_id = tc.type_compte_id
                    WHERE c.client_id = :clientId
                    ORDER BY c.date_ouverture DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':clientId', $clientId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAccountsByClientId error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les types de comptes disponibles avec leurs taux d'intérêt.
     * @return array
     */
    public function getAccountTypes(): array {
        try {
            // FIX: table name → lowercase 'type_comptes'
            $sql = "SELECT type_compte_id, nom_type, taux_interet FROM type_comptes ORDER BY nom_type ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAccountTypes error: " . $e->getMessage());
            return [];
        }
    }
}