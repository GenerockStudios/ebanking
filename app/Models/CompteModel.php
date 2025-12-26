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
     * @param int $typeCompteId L'ID du type de compte (ex: 1 pour 'Épargne', 2 pour 'Chèque').
     * @param int $utilisateurId L'ID de l'utilisateur qui ouvre le compte (pour audit et traçabilité).
     * @param float $soldeInitial Le solde de départ (normalement 0.00).
     * @return string|false Le numéro de compte créé, ou FALSE en cas d'échec.
     */
    public function openNewAccount(int $clientId, int $typeCompteId, int $utilisateurId, float $soldeInitial = 0.00) {
        try {
            // 1. Générer le numéro de compte unique
            $numeroCompte = $this->refGenerator->generateUniqueAccountNumber();
            
            $sql = "INSERT INTO Comptes (client_id, type_compte_id, numero_compte, solde) 
                    VALUES (:clientId, :typeId, :numeroCompte, :solde)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':clientId', $clientId, PDO::PARAM_INT);
            $stmt->bindParam(':typeId', $typeCompteId, PDO::PARAM_INT);
            $stmt->bindParam(':numeroCompte', $numeroCompte);
            $stmt->bindParam(':solde', $soldeInitial);

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
            $stmt = $this->db->prepare("SELECT solde FROM Comptes WHERE numero_compte = :numeroCompte");
            $stmt->bindParam(':numeroCompte', $numeroCompte);
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
     * Récupère les informations associés au compte de l'utilisateur
     * @param string $numeroCompte Le numéro de compte.
     * @return float|false Le solde, ou FALSE si le compte n'existe pas.
     */
    public function getAccountInfos(string $numeroCompte) {
        try {
            $stmt = $this->db->prepare("SELECT compte_id, client_id, type_compte_id, solde, date_ouverture FROM Comptes WHERE numero_compte = :numeroCompte");
            $stmt->bindParam(':numeroCompte', $numeroCompte);
            $solde = $stmt->fetchColumn();
            // fetchColumn retourne la colonne ou FALSE si aucune ligne n'est trouvée
            return is_numeric($solde) ? (float)$solde : false; 

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de la récupération du solde : " . $e->getMessage());
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
            // Requête complexe pour récupérer l'ID du compte et toutes les transactions associées
            $sql = "
                SELECT 
                    T.horodatage_transaction, 
                    T.type_transaction, 
                    T.montant, 
                    T.reference_externe,
                    CASE 
                        WHEN C_Source.numero_compte = :numCompte THEN 'Débit' 
                        ELSE 'Crédit' 
                    END as sens_flux
                FROM Transactions T
                LEFT JOIN Comptes C_Source ON T.compte_source_id = C_Source.compte_id
                LEFT JOIN Comptes C_Dest ON T.compte_destination_id = C_Dest.compte_id
                WHERE C_Source.numero_compte = :numCompte OR C_Dest.numero_compte = :numCompte
                ORDER BY T.horodatage_transaction DESC
                LIMIT 100
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':numCompte', $numeroCompte);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'historique : " . $e->getMessage());
            return [];
        }
    }
    
    // NOTE : La méthode de mise à jour du solde est dans TransactionModel pour garantir l'atomicité.
}