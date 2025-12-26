<?php
/**
 * PlafondChecker.php
 * Service pour vérifier si une transaction (Retrait ou Transfert) dépasse les plafonds définis.
 * S'appuie sur les tables Plafonds_Comptes et Transactions.
 */

class PlafondChecker {
    
    private $db;
    private $compteModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->compteModel = new CompteModel();
    }

    /**
     * Vérifie si un retrait d'espèces ou un transfert est autorisé par rapport aux plafonds.
     * @param int $accountId L'ID interne du compte concerné (source ou destination).
     * @param string $operationType Le type d'opération ('RETRAIT', 'TRANSFERT').
     * @param float $amount Le montant de la transaction.
     * @return bool Vrai si l'opération est dans les limites, Faux sinon.
     * @throws Exception Si le compte n'a pas de plafonds définis.
     */
    public function checkLimit(int $accountId, string $operationType, float $amount): bool {
        
        $currentDate = date('Y-m-d');
        
        // 1. Récupérer les plafonds définis pour ce compte
        $plafonds = $this->getPlafondsByAccountId($accountId);

        // Ajouter le plafonds par défaut pour les transaction

        if (!$plafonds) {

            // C'est critique : un compte sans plafond est non sécurisé.
            throw new Exception("Plafonds non définis pour le compte ID: " . $accountId);
        }

        // 2. Déterminer le type de vérification
        if ($operationType === 'RETRAIT') {
            $limitKey = 'plafond_retrait_journalier';
            $transactionType = 'RETRAIT';
            $limit = $plafonds->{$limitKey};
            $scope = 'JOURNALIER';
        } elseif ($operationType === 'TRANSFERT') {
            $limitKey = 'plafond_transfert_mensuel';
            $transactionType = 'TRANSFERT_INT'; // Utiliser le type stocké en BDD
            $limit = $plafonds->{$limitKey};
            $scope = 'MENSUEL';
        } else {
            // Les dépôts n'ont généralement pas de plafond de sécurité ici.
            return true;
        }

        // 3. Si aucune limite n'est définie (plafond à 0), l'opération est refusée par défaut.
        if ($limit <= 0) {
             return false; // Ou dépend de la politique : pourrait être considéré illimité.
        }

        // 4. Calculer le montant déjà dépensé (pour la période)
        $amountSpent = $this->getAmountSpent($accountId, $transactionType, $scope);
        
        // 5. Vérification finale
        $newTotal = $amountSpent + $amount;
        
        if ($newTotal > $limit) {
            error_log("PLAFOND DÉPASSÉ: Compte {$accountId} - Type {$operationType}. Nouvelle somme {$newTotal} > Limite {$limit}.");
            return false;
        }

        return true;
    }

    /**
     * Récupère les plafonds du compte depuis la table Plafonds_Comptes.
     */
    private function getPlafondsByAccountId(int $accountId) {
        $stmt = $this->db->prepare("SELECT * FROM Plafonds_Comptes WHERE compte_id = :id");
        $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }


    
    /**
     * Cette méthode permet d'ajouter un plafonds dans la base de données
     */
    private function addNewPlafond(int $accountId) {
        // Recupération du clients
        

        // Recupération du compte lié à ce client
        //$this->compteModel->getAccountBalance();

        $stmt = $this->db->prepare("SELECT * FROM Plafonds_Comptes WHERE compte_id = :id");
        $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Calcule la somme des transactions pour un compte sur une période donnée.
     */
    private function getAmountSpent(int $accountId, string $transactionType, string $scope): float {
        $dateCondition = '';
        $currentDate = date('Y-m-d');
        
        if ($scope === 'JOURNALIER') {
            $dateCondition = "AND DATE(date_transaction) = '{$currentDate}'";
        } elseif ($scope === 'MENSUEL') {
            $currentMonth = date('Y-m-01');
            $dateCondition = "AND date_transaction >= '{$currentMonth}'";
        }
        
        $sql = "SELECT SUM(montant) FROM Transactions 
                WHERE compte_source_id = :accountId 
                AND type_transaction = :txnType 
                AND statut = 'COMPLETÉ'
                {$dateCondition}";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':accountId', $accountId, PDO::PARAM_INT);
        $stmt->bindParam(':txnType', $transactionType);
        $stmt->execute();
        
        $sum = $stmt->fetchColumn();
        return (float) $sum;
    }
}