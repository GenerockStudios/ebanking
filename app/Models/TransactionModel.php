<?php
/**
 * TransactionModel.php
 * Gère les opérations financières (Dépôt, Retrait, Transfert) en garantissant l'atomicité (ACID).
 */

class TransactionModel {
    
    private $db;
    private $auditLogger;
    private $plafondChecker;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Les services critiques doivent être chargés
        $this->auditLogger = new AuditLogger();
        $this->plafondChecker = new PlafondChecker();
    }

    /**
     * Effectue un Dépôt sur un compte.
     * @param int $accountId ID du compte de destination.
     * @param float $amount Montant du dépôt.
     * @param int $userId ID de l'utilisateur/caissier initiateur.
     * @return bool Vrai en cas de succès, Faux sinon.
     */
    public function faireDepot(int $accountId, float $amount, int $userId): bool {
        if ($amount <= 0) return false;

        $reference = $this->generateAndLogStart($userId, 'DEPOT', $accountId, $amount);
        if (!$reference) return false;

        try {
            // 1. DÉMARRER LA TRANSACTION BDD (ACID)
            $this->db->beginTransaction();

            // 2. Mettre à jour le solde du compte (CRÉDIT)
            $this->updateAccountBalance($accountId, $amount);

            // 3. Enregistrer la transaction (compte source NULL pour un dépôt externe)
            $this->insertTransaction(null, $accountId, 'DEPOT', $amount, $userId, $reference);
            
            // 4. VALIDER LA TRANSACTION
            $this->db->commit();
            
            $this->auditLogger->logAction($userId, 'DEPOT_SUCCESS', 'Transactions', "Dépôt de {$amount} sur compte ID: {$accountId}.", (string)$accountId);
            return true;

        } catch (\Exception $e) {
            // En cas d'erreur (conflit de BDD, échec de mise à jour), ANNULER
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'DEPOT_FAILURE', 'Transactions', "Échec dépôt pour compte ID: {$accountId}. Erreur: {$e->getMessage()}", (string)$accountId);
            error_log("DÉPÔT ÉCHEC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Effectue un Retrait sur un compte.
     * @param int $accountId ID du compte source.
     * @param float $amount Montant du retrait.
     * @param int $userId ID de l'utilisateur/caissier initiateur.
     * @return bool Vrai en cas de succès, Faux sinon.
     */
    public function faireRetrait(int $accountId, float $amount, int $userId): bool {
        if ($amount <= 0) return false;
        
        $reference = $this->generateAndLogStart($userId, 'RETRAIT', $accountId, $amount);
        if (!$reference) return false;
        
        try {
            // Vérification critique : 1. Solde suffisant, 2. Plafond non dépassé
            $currentBalance = $this->getAccountBalanceForLock($accountId);
            
            if ($currentBalance < $amount) {
                throw new Exception("Fonds insuffisants. Solde: {$currentBalance}");
            }
            
            if (!$this->plafondChecker->checkLimit($accountId, 'RETRAIT', $amount)) {
                throw new Exception("Plafond journalier de retrait dépassé.");
            }

            // 1. DÉMARRER LA TRANSACTION BDD
            $this->db->beginTransaction();

            // 2. Mettre à jour le solde du compte (DÉBIT)
            $this->updateAccountBalance($accountId, -$amount);

            // 3. Enregistrer la transaction (compte destination NULL pour un retrait)
            $this->insertTransaction($accountId, null, 'RETRAIT', $amount, $userId, $reference);
            
            // 4. VALIDER LA TRANSACTION
            $this->db->commit();
            
            $this->auditLogger->logAction($userId, 'RETRAIT_SUCCESS', 'Transactions', "Retrait de {$amount} du compte ID: {$accountId}.", (string)$accountId);
            return true;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'RETRAIT_FAILURE', 'Transactions', "Échec retrait. Raison: {$e->getMessage()}", (string)$accountId);
            error_log("RETRAIT ÉCHEC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Effectue un Transfert INTERNE entre deux comptes.
     * @param int $sourceId ID du compte source (débité).
     * @param int $destId ID du compte de destination (crédité).
     * @param float $amount Montant du transfert.
     * @param int $userId ID de l'utilisateur initiateur.
     * @return bool Vrai en cas de succès, Faux sinon.
     */
    public function faireTransfert(int $sourceId, int $destId, float $amount, int $userId): bool {
        if ($amount <= 0 || $sourceId === $destId) return false;
        
        $reference = $this->generateAndLogStart($userId, 'TRANSFERT_INT', $sourceId, $amount, (string)$destId);
        if (!$reference) return false;

        try {
            // Vérification critique : 1. Solde suffisant, 2. Plafond non dépassé
            $currentBalance = $this->getAccountBalanceForLock($sourceId);
            
            if ($currentBalance < $amount) {
                throw new Exception("Fonds source insuffisants.");
            }
            
            if (!$this->plafondChecker->checkLimit($sourceId, 'TRANSFERT', $amount)) {
                throw new Exception("Plafond mensuel de transfert dépassé.");
            }

            // 1. DÉMARRER LA TRANSACTION BDD
            $this->db->beginTransaction();

            // 2. DÉBITER la source
            $this->updateAccountBalance($sourceId, -$amount);

            // 3. CRÉDITER la destination
            $this->updateAccountBalance($destId, $amount);

            // 4. Enregistrer la transaction (un seul enregistrement lie source et destination)
            $this->insertTransaction($sourceId, $destId, 'TRANSFERT_INT', $amount, $userId, $reference);
            
            // 5. VALIDER LA TRANSACTION
            $this->db->commit();
            
            $this->auditLogger->logAction($userId, 'TRANSFERT_SUCCESS', 'Transactions', "Transfert de {$amount} de {$sourceId} vers {$destId}.", (string)$sourceId);
            return true;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'TRANSFERT_FAILURE', 'Transactions', "Échec transfert. Raison: {$e->getMessage()}", (string)$sourceId);
            error_log("TRANSFERT ÉCHEC: " . $e->getMessage());
            return false;
        }
    }
    
    // --- Méthodes privées d'intégrité ---

    /**
     * Récupère le solde du compte pour une vérification immédiate et potentiellement un verrouillage.
     * NOTE: En production, utiliser "SELECT ... FOR UPDATE" dans une transaction explicite si le SGBD le supporte.
     */
    private function getAccountBalanceForLock(int $accountId): float {
        $stmt = $this->db->prepare("SELECT solde FROM Comptes WHERE compte_id = :id");
        $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
        $solde = $stmt->fetchColumn();
        if ($solde === false) throw new Exception("Compte source non trouvé.");
        return (float)$solde;
    }

    /**
     * Met à jour le solde du compte de manière relative (ajouter ou soustraire).
     */
    private function updateAccountBalance(int $accountId, float $deltaAmount): bool {
        $sql = "UPDATE Comptes SET solde = solde + :amount WHERE compte_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':amount', $deltaAmount);
        $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Insère un enregistrement dans la table Transactions.
     */
    private function insertTransaction(?int $sourceId, ?int $destId, string $type, float $amount, int $userId, string $reference): bool {
        $sql = "INSERT INTO Transactions (compte_source_id, compte_destination_id, type_transaction, montant, utilisateur_id, reference_externe)
                VALUES (:source, :dest, :type, :montant, :userId, :reference)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':source', $sourceId, $sourceId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':dest', $destId, $destId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':montant', $amount);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':reference', $reference);
        
        return $stmt->execute();
    }

    /**
     * Génère la référence et log l'action de départ (pré-transaction).
     */
    private function generateAndLogStart(int $userId, string $type, int $accountId, float $amount, string $destId = 'N/A'): string|false {
        $refGen = new ReferenceGenerator();
        $reference = $refGen->generateTransactionReference();
        
        try {
             $this->auditLogger->logAction($userId, "{$type}_START", 'Transactions', "Tentative {$type} de {$amount}. Source: {$accountId}. Dest: {$destId}. Ref: {$reference}", (string)$accountId);
             return $reference;
        } catch (\Exception $e) {
             error_log("ERREUR CRITIQUE: Impossible de logger le début de la transaction.");
             return false;
        }
    }
}