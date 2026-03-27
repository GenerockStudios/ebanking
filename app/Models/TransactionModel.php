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
        $this->auditLogger   = new AuditLogger();
        $this->plafondChecker = new PlafondChecker();
    }

    /**
     * Effectue un Dépôt sur un compte.
     * @param int $accountId ID du compte de destination.
     * @param float $amount Montant du dépôt.
     * @param int $userId ID de l'utilisateur/caissier initiateur.
     * @return int|false ID de transaction en cas de succès, false sinon.
     */
    public function faireDepot(int $accountId, float $amount, int $userId) {
        if ($amount <= 0) return false;

        $reference = $this->generateAndLogStart($userId, 'DEPOT', $accountId, $amount);
        if (!$reference) return false;

        try {
            $this->db->beginTransaction();

            $this->updateAccountBalance($accountId, $amount);
            $this->insertTransaction(null, $accountId, 'DEPOT', $amount, $userId, $reference);
            
            $lastId = $this->db->lastInsertId();
            $this->db->commit();
            
            $this->auditLogger->logAction($userId, 'DEPOT_SUCCESS', 'transactions', "Dépôt de {$amount} sur compte ID: {$accountId}.", (string)$accountId);
            return (int)$lastId;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'DEPOT_FAILURE', 'transactions', "Échec dépôt pour compte ID: {$accountId}. Erreur: {$e->getMessage()}", (string)$accountId);
            error_log("DÉPÔT ÉCHEC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Effectue un Retrait sur un compte.
     * @param int $accountId ID du compte source.
     * @param float $amount Montant du retrait.
     * @param int $userId ID de l'utilisateur/caissier initiateur.
     * @return int ID de transaction en cas de succès.
     * @throws Exception En cas d'échec métier ou BDD.
     */
    public function faireRetrait(int $accountId, float $amount, int $userId) {
        if ($amount <= 0) throw new Exception("Montant invalide.");
        
        $reference = $this->generateAndLogStart($userId, 'RETRAIT', $accountId, $amount);
        
        try {
            $this->db->beginTransaction();

            // FIX: table name → lowercase 'comptes'
            // Verrouillage pessimiste (FOR UPDATE) : empêche toute autre écriture concurrente
            $stmt = $this->db->prepare("SELECT solde, est_suspendu FROM comptes WHERE compte_id = :id FOR UPDATE");
            $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) throw new Exception("Compte introuvable.");
            if ((int)$row['est_suspendu'] === 1) throw new Exception("Ce compte est suspendu. Le retrait est interdit.");

            $currentBalance = (float)$row['solde'];

            if ($currentBalance < $amount) {
                throw new Exception("Solde insuffisant (Solde: " . number_format($currentBalance, 2) . ").");
            }
            
            if (!$this->plafondChecker->checkLimit($accountId, 'RETRAIT', $amount)) {
                throw new Exception("Plafond journalier de retrait dépassé.");
            }

            $this->updateAccountBalance($accountId, -$amount);
            $this->insertTransaction($accountId, null, 'RETRAIT', $amount, $userId, $reference);
            
            $lastId = $this->db->lastInsertId();
            $this->db->commit();
            $this->auditLogger->logAction($userId, 'RETRAIT_SUCCESS', 'transactions', "Retrait de {$amount}", (string)$accountId);
            return (int)$lastId;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'RETRAIT_FAILURE', 'transactions', "Echec: " . $e->getMessage(), (string)$accountId);
            throw $e; 
        }
    }

    /**
     * Effectue un Transfert INTERNE entre deux comptes.
     * @param int $sourceId ID du compte source (débité).
     * @param int $destId ID du compte de destination (crédité).
     * @param float $amount Montant du transfert.
     * @param int $userId ID de l'utilisateur initiateur.
     * @return int ID de transaction en cas de succès.
     * @throws Exception En cas d'échec.
     */
    public function faireTransfert(int $sourceId, int $destId, float $amount, int $userId) {
        if ($amount <= 0 || $sourceId === $destId) return false;

        // FIX: type → 'TRANSFERT' pour cohérence avec les données de seed du schéma
        $reference = $this->generateAndLogStart($userId, 'TRANSFERT', $sourceId, $amount, (string)$destId);
        if (!$reference) return false;

        try {
            $this->db->beginTransaction();

            // FIX: table name → lowercase 'comptes'
            // VERROUILLAGE PESSIMISTE : FOR UPDATE sur les deux comptes en ordre croissant d'ID
            $lockIds = [$sourceId, $destId];
            sort($lockIds);
            $stmt = $this->db->prepare("SELECT compte_id, solde, est_suspendu FROM comptes WHERE compte_id IN (:id1, :id2) ORDER BY compte_id FOR UPDATE");
            $stmt->bindValue(':id1', $lockIds[0], PDO::PARAM_INT);
            $stmt->bindValue(':id2', $lockIds[1], PDO::PARAM_INT);
            $stmt->execute();

            // Re-fetch pour avoir toutes les colonnes avec clé associative
            $stmt2 = $this->db->prepare("SELECT compte_id, solde, est_suspendu FROM comptes WHERE compte_id IN (:id1, :id2)");
            $stmt2->bindValue(':id1', $lockIds[0], PDO::PARAM_INT);
            $stmt2->bindValue(':id2', $lockIds[1], PDO::PARAM_INT);
            $stmt2->execute();
            $accounts = [];
            foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $accounts[$row['compte_id']] = $row;
            }

            if (!isset($accounts[$sourceId]) || !isset($accounts[$destId])) {
                throw new Exception("Compte source ou destination introuvable.");
            }

            if ((int)$accounts[$sourceId]['est_suspendu'] === 1) {
                throw new Exception("Le compte source est suspendu. Opération refusée.");
            }

            $currentBalance = (float)$accounts[$sourceId]['solde'];
            if ($currentBalance < $amount) {
                throw new Exception("Fonds source insuffisants (Solde: " . number_format($currentBalance, 2) . ").");
            }

            if (!$this->plafondChecker->checkLimit($sourceId, 'TRANSFERT', $amount)) {
                throw new Exception("Plafond mensuel de transfert dépassé.");
            }

            // FIX: type_transaction → 'TRANSFERT' (cohérent avec schéma et seed data)
            $this->updateAccountBalance($sourceId, -$amount);
            $this->updateAccountBalance($destId, $amount);
            $this->insertTransaction($sourceId, $destId, 'TRANSFERT', $amount, $userId, $reference);

            $lastId = $this->db->lastInsertId();
            $this->db->commit();

            $this->auditLogger->logAction($userId, 'TRANSFERT_SUCCESS', 'transactions', "Transfert de {$amount} de {$sourceId} vers {$destId}.", (string)$sourceId);
            return (int)$lastId;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->auditLogger->logAction($userId, 'TRANSFERT_FAILURE', 'transactions', "Échec transfert. Raison: {$e->getMessage()}", (string)$sourceId);
            error_log("TRANSFERT ÉCHEC: " . $e->getMessage());
            throw $e;
        }
    }
    

    /**
     * Met à jour le solde du compte de manière relative (ajouter ou soustraire).
     */
    private function updateAccountBalance(int $accountId, float $deltaAmount): bool {
        // FIX: table name → lowercase 'comptes'
        $sql = "UPDATE comptes SET solde = solde + :amount WHERE compte_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':amount', $deltaAmount, PDO::PARAM_STR);
        $stmt->bindValue(':id',     $accountId,   PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Insère un enregistrement dans la table transactions.
     */
    private function insertTransaction(?int $sourceId, ?int $destId, string $type, float $amount, int $userId, string $reference): bool {
        // FIX: table name → lowercase 'transactions'
        $sql = "INSERT INTO transactions (compte_source_id, compte_destination_id, type_transaction, montant, utilisateur_id, reference_externe)
                VALUES (:source, :dest, :type, :montant, :userId, :reference)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':source',    $sourceId,  $sourceId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':dest',      $destId,    $destId   === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':type',      $type,      PDO::PARAM_STR);
        $stmt->bindValue(':montant',   $amount,    PDO::PARAM_STR);
        $stmt->bindValue(':userId',    $userId,    PDO::PARAM_INT);
        $stmt->bindValue(':reference', $reference, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Récupère les détails complets d'une transaction pour l'impression d'un reçu.
     * @param int $transactionId ID de la transaction.
     * @return object|null Objet contenant les détails ou null si introuvable.
     */
    public function getTransactionDetails(int $transactionId): ?object {
        try {
            // FIX: table names → lowercase 'transactions', 'comptes', 'clients', 'utilisateurs'
            $sql = "SELECT 
                        t.transaction_id, 
                        t.type_transaction, 
                        t.montant, 
                        t.date_transaction, 
                        t.reference_externe,
                        t.compte_source_id,
                        t.compte_destination_id,
                        cs.numero_compte AS num_source,
                        CONCAT(cls.nom, ' ', cls.prenom) AS client_source,
                        cs.solde AS solde_source,
                        cd.numero_compte AS num_dest,
                        CONCAT(cld.nom, ' ', cld.prenom) AS client_dest,
                        cd.solde AS solde_dest,
                        u.nom_complet AS caissier_nom
                    FROM transactions t
                    LEFT JOIN comptes cs ON t.compte_source_id = cs.compte_id
                    LEFT JOIN clients cls ON cs.client_id = cls.client_id
                    LEFT JOIN comptes cd ON t.compte_destination_id = cd.compte_id
                    LEFT JOIN clients cld ON cd.client_id = cld.client_id
                    LEFT JOIN utilisateurs u ON t.utilisateur_id = u.utilisateur_id
                    WHERE t.transaction_id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
        } catch (\PDOException $e) {
            error_log("getTransactionDetails Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Génère la référence et log l'action de départ (pré-transaction).
     */
    private function generateAndLogStart(int $userId, string $type, int $accountId, float $amount, string $destId = 'N/A'): string|false {
        $refGen = new ReferenceGenerator();
        $reference = $refGen->generateTransactionReference();
        
        try {
             $this->auditLogger->logAction($userId, "{$type}_START", 'transactions', "Tentative {$type} de {$amount}. Source: {$accountId}. Dest: {$destId}. Ref: {$reference}", (string)$accountId);
             return $reference;
        } catch (\Exception $e) {
             error_log("ERREUR CRITIQUE: Impossible de logger le début de la transaction.");
             return false;
        }
    }

    /**
     * Récupère les transactions pour un relevé de compte sur une période donnée.
     * @param int $accountId ID du compte.
     * @param string $startDate Date de début (YYYY-MM-DD).
     * @param string $endDate Date de fin (YYYY-MM-DD).
     * @return array Liste des transactions.
     */
    public function getTransactionsForStatement(int $accountId, string $startDate, string $endDate): array {
        try {
            // FIX: table name → lowercase 'transactions'
            $sql = "SELECT 
                        t.transaction_id, 
                        t.type_transaction, 
                        t.montant, 
                        t.date_transaction, 
                        t.reference_externe,
                        CASE 
                            WHEN t.compte_source_id = :id THEN 'DEBIT'
                            WHEN t.compte_destination_id = :id THEN 'CREDIT'
                        END AS sens
                    FROM transactions t
                    WHERE (t.compte_source_id = :id OR t.compte_destination_id = :id)
                      AND DATE(t.date_transaction) BETWEEN :start AND :end
                    ORDER BY t.date_transaction ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',    $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':start', $startDate, PDO::PARAM_STR);
            $stmt->bindValue(':end',   $endDate,   PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getTransactionsForStatement Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcule mathématiquement le solde à une date précise.
     * @param int $accountId ID du compte.
     * @param string $targetDate Date cible (YYYY-MM-DD).
     * @return float Solde calculé.
     */
    public function calculateBalanceAtDate(int $accountId, string $targetDate): float {
        try {
            // FIX: table names → lowercase 'comptes', 'transactions'
            $stmt = $this->db->prepare("SELECT solde FROM comptes WHERE compte_id = :id");
            $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            $currentBalance = (float)$stmt->fetchColumn();

            $sql = "SELECT 
                        compte_source_id, 
                        compte_destination_id, 
                        montant 
                    FROM transactions 
                    WHERE (compte_source_id = :id OR compte_destination_id = :id)
                      AND date_transaction >= :target";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',     $accountId,              PDO::PARAM_INT);
            $stmt->bindValue(':target', $targetDate . ' 00:00:00', PDO::PARAM_STR);
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $calculatedBalance = $currentBalance;

            foreach ($transactions as $t) {
                if ((int)$t['compte_destination_id'] === $accountId) {
                    $calculatedBalance -= (float)$t['montant'];
                } elseif ((int)$t['compte_source_id'] === $accountId) {
                    $calculatedBalance += (float)$t['montant'];
                }
            }

            return $calculatedBalance;

        } catch (\PDOException $e) {
            error_log("calculateBalanceAtDate Error: " . $e->getMessage());
            return 0.00;
        }
    }
}