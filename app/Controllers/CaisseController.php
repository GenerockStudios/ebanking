<?php

/**
 * CaisseController.php
 * Gère le flux pour toutes les opérations de guichet (Dépôt, Retrait, Transfert).
 */

class CaisseController
{
    private $transactionModel;
    private $compteModel;
    private $sessionModel;
    private $auditLogger;

    public function __construct()
    {
        AuthController::checkPermission('Caissier');
        $this->transactionModel = new TransactionModel();
        $this->compteModel      = new CompteModel();
        $this->sessionModel     = new SessionModel();
        $this->auditLogger      = new AuditLogger();
    }

    // -------------------------------------------------------------------------
    // TABLEAU DE BORD
    // -------------------------------------------------------------------------

    public function dashboard(): void
    {
        $data              = [];
        $data['title']     = "Tableau de Bord Caisse";
        $roleId            = $_SESSION['role_id'] ?? 0;
        $data['user_role'] = $GLOBALS['ROLES'][$roleId] ?? 'Invité';

        require_once VIEW_PATH . 'caisse/dashboard.php';
    }

    // -------------------------------------------------------------------------
    // DÉPÔT
    // -------------------------------------------------------------------------

    public function depot(): void
    {
        $data          = [];
        $data['title'] = "Opération de Dépôt";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $montant      = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId       = $_SESSION['user_id'];

            $compteInfo = $this->getCompteInfoByNumber($numeroCompte);

            if (!$compteInfo) {
                $data['error'] = "Compte bancaire introuvable.";
            } elseif ($montant <= 0) {
                $data['error'] = "Le montant doit être positif.";
            } else {
                $compteId = $compteInfo->compte_id;
                $lastId = $this->transactionModel->faireDepot($compteId, $montant, $userId);
                if ($lastId) {
                    $newBalance = $this->compteModel->getAccountBalance($numeroCompte);
                    $data['success']             = "Dépôt de " . number_format($montant, 2, ',', ' ') . " effectué sur le compte " . $numeroCompte . ".";
                    $data['new_balance']         = $newBalance;
                    $data['derniers_mouvements'] = $this->getDerniersMovements($numeroCompte, 5);
                    $data['transaction_id']      = $lastId;
                    $data['show_receipt']        = true;
                } else {
                    $data['error'] = "Échec du dépôt. Vérifiez les logs.";
                }
            }
        }

        require_once VIEW_PATH . 'caisse/depot.php';
    }

    // -------------------------------------------------------------------------
    // RETRAIT
    // -------------------------------------------------------------------------

    public function retrait(): void
    {
        $data          = [];
        $data['title'] = "Opération de Retrait";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? '');
            $montant      = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId       = $_SESSION['user_id'];

            $compteInfo = $this->getCompteInfoByNumber($numeroCompte);

            if (!$compteInfo) {
                $data['error'] = "Compte bancaire introuvable.";
            } elseif ($montant <= 0) {
                $data['error'] = "Le montant doit être positif.";
            } else {
                try {
                    $compteId = $compteInfo->compte_id;
                    $lastId = $this->transactionModel->faireRetrait($compteId, $montant, $userId);
                    if ($lastId) {
                        $newBalance = $this->compteModel->getAccountBalance($numeroCompte);
                        $data['success']             = "Retrait de " . number_format($montant, 2, ',', ' ') . " effectué sur le compte " . $numeroCompte . ".";
                        $data['new_balance']         = $newBalance;
                        $data['derniers_mouvements'] = $this->getDerniersMovements($numeroCompte, 5);
                        $data['transaction_id']      = $lastId;
                        $data['show_receipt']        = true;
                    }
                } catch (Exception $e) {
                    $data['error'] = "Erreur : " . $e->getMessage();
                }
            }
        }

        require_once VIEW_PATH . 'caisse/retrait.php';
    }

    // -------------------------------------------------------------------------
    // TRANSFERT
    // -------------------------------------------------------------------------

    public function transfert(): void
    {
        $data          = [];
        $data['title'] = "Opération de Transfert Interne";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $compteSourceNum = Sanitizer::cleanString($_POST['compte_source'] ?? '');
            $compteDestNum   = Sanitizer::cleanString($_POST['compte_destination'] ?? '');
            $montant         = Sanitizer::cleanFloat($_POST['montant'] ?? 0.00);
            $userId          = $_SESSION['user_id'];

            $sourceInfo = $this->getCompteInfoByNumber($compteSourceNum);
            $destInfo   = $this->getCompteInfoByNumber($compteDestNum);

            if (!$sourceInfo || !$destInfo) {
                $data['error'] = "Compte source ou destination introuvable.";
            } elseif ($sourceInfo->compte_id === $destInfo->compte_id) {
                $data['error'] = "Le compte source et le compte destination doivent être différents.";
            } elseif ($montant <= 0) {
                $data['error'] = "Le montant doit être positif.";
            } else {
                try {
                    $lastId = $this->transactionModel->faireTransfert($sourceInfo->compte_id, $destInfo->compte_id, $montant, $userId);
                    if ($lastId) {
                        $newBalance      = $this->compteModel->getAccountBalance($compteSourceNum);
                        $data['success'] = "Transfert de " . number_format($montant, 2, ',', ' ') . " de " . $compteSourceNum . " vers " . $compteDestNum . " réussi.";
                        $data['new_balance']         = $newBalance;
                        $data['derniers_mouvements'] = $this->getDerniersMovements($compteSourceNum, 5);
                        $data['transaction_id']      = $lastId;
                        $data['show_receipt']        = true;
                    }
                } catch (Exception $e) {
                    $data['error'] = "Erreur : " . $e->getMessage();
                }
            }
        }

        require_once VIEW_PATH . 'caisse/transfert.php';
    }

    // -------------------------------------------------------------------------
    // REÇU IMPRIMABLE
    // -------------------------------------------------------------------------

    public function recepisse(): void
    {
        if (empty($_SESSION['last_receipt'])) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard");
            exit;
        }
        $receipt = $_SESSION['last_receipt'];
        require_once VIEW_PATH . 'caisse/recepisse.php';
    }

    // -------------------------------------------------------------------------
    // GÉNÉRATION DE RIB/IBAN
    // -------------------------------------------------------------------------

    public function rib(): void
    {
        AuthController::checkPermission('Caissier');

        $data          = [];
        $data['title'] = "Relevé d'Identité Bancaire (RIB)";

        $numeroCompte = Sanitizer::cleanString($_GET['numero_compte'] ?? '');

        if (empty($numeroCompte)) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Veuillez+saisir+un+numéro+de+compte");
            exit;
        }

        $compte = $this->compteModel->getCompteDetailsForRib($numeroCompte);

        if (!$compte) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Compte+introuvable");
            exit;
        }

        $codeBanque  = "30001"; 
        $codeGuichet = "01234"; 
        $numCompteFormatted = str_pad(substr($numeroCompte, -11), 11, '0', STR_PAD_LEFT);
        $cleRib = str_pad((intval(substr($numeroCompte, -2)) % 97), 2, '0', STR_PAD_LEFT);
        if ($cleRib === '00') $cleRib = '97';

        $data['rib'] = [
            'banque'  => $codeBanque,
            'guichet' => $codeGuichet,
            'compte'  => $numCompteFormatted,
            'cle'     => $cleRib,
            'iban'    => "FR76 " . $codeBanque . " " . $codeGuichet . " " . chunk_split($numCompteFormatted, 4, ' ') . " " . $cleRib,
            'bic'     => "MSBBFR2PXXX"
        ];
        $data['compte'] = $compte;

        $this->auditLogger->logAction(
            $_SESSION['user_id'],
            'CONSULTATION_RIB',
            'comptes',
            "Génération du RIB pour le compte : " . $numeroCompte,
            $numeroCompte
        );

        require_once VIEW_PATH . 'comptes/rib.php';
    }

    /**
     * Affiche le reçu officiel d'une transaction.
     */
    public function recu(): void
    {
        AuthController::checkPermission('Caissier');

        $data          = [];
        $data['title'] = "Reçu de Transaction";

        $transactionId = Sanitizer::cleanInt($_GET['id'] ?? 0);

        if ($transactionId <= 0) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=ID+de+transaction+invalide");
            exit;
        }

        $transaction = $this->transactionModel->getTransactionDetails($transactionId);

        if (!$transaction) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Transaction+introuvable");
            exit;
        }

        $data['transaction'] = $transaction;

        $this->auditLogger->logAction(
            $_SESSION['user_id'],
            'IMPRESSION_RECU',
            'transactions',
            "Génération du reçu pour la transaction ID : " . $transactionId . " (Réf: " . $transaction->reference_externe . ")",
            (string)$transactionId
        );

        require_once VIEW_PATH . 'caisse/recu.php';
    }

    /**
     * Génère et affiche le relevé de compte détaillé.
     */
    public function releve(): void {
        AuthController::checkPermission('Caissier');

        $data          = [];
        $data['title'] = "Relevé de Compte Détaillé";
        
        $numeroCompte = Sanitizer::cleanString($_POST['numero_compte'] ?? $_GET['numero_compte'] ?? '');
        $dateDebut    = Sanitizer::cleanString($_POST['date_debut'] ?? date('Y-m-01'));
        $dateFin      = Sanitizer::cleanString($_POST['date_fin']   ?? date('Y-m-d'));

        $data['filters'] = [
            'numero_compte' => $numeroCompte,
            'date_debut'    => $dateDebut,
            'date_fin'      => $dateFin
        ];

        if (!empty($numeroCompte)) {
            $compte = $this->compteModel->getCompteDetailsForRib($numeroCompte);

            if (!$compte) {
                $data['error'] = "Compte introuvable.";
            } else {
                $accountId    = (int)$compte->compte_id;
                $soldeInitial = $this->transactionModel->calculateBalanceAtDate($accountId, $dateDebut);
                $transactions = $this->transactionModel->getTransactionsForStatement($accountId, $dateDebut, $dateFin);
                
                $totalDebit  = 0;
                $totalCredit = 0;
                foreach ($transactions as $t) {
                    if ($t['sens'] === 'DEBIT') {
                        $totalDebit += (float)$t['montant'];
                    } else {
                        $totalCredit += (float)$t['montant'];
                    }
                }
                
                $soldeFinal = $soldeInitial + $totalCredit - $totalDebit;

                $data['compte']       = $compte;
                $data['transactions'] = $transactions;
                $data['bilan']        = [
                    'solde_initial' => $soldeInitial,
                    'total_debit'   => $totalDebit,
                    'total_credit'  => $totalCredit,
                    'solde_final'   => $soldeFinal
                ];

                $this->auditLogger->logAction(
                    $_SESSION['user_id'],
                    'CONSULTATION_RELEVE',
                    'transactions',
                    "Consultation relevé de compte: {$numeroCompte} du {$dateDebut} au {$dateFin}",
                    $numeroCompte
                );
            }
        }

        require_once VIEW_PATH . 'caisse/releve.php';
    }

    // -------------------------------------------------------------------------
    // GESTION DES SESSIONS ET CLÔTURE DE CAISSE
    // -------------------------------------------------------------------------

    public function cloture(): void
    {
        $data          = [];
        $data['title'] = "Clôture de Session de Caisse";
        $userId        = $_SESSION['user_id'];

        $session = $this->sessionModel->getOpenSession($userId);

        if (!$session) {
            $data['no_session'] = true;
            $data['title']      = "Ouverture de Session de Caisse";
        } else {
            $data['session']        = $session;
            $data['system_balance'] = $this->sessionModel->calculateSystemBalance($session->session_id, $userId);
            $data['stats']          = $this->sessionModel->getSessionStats($session->session_id, $userId);
        }

        require_once VIEW_PATH . 'caisse/cloture.php';
    }

    public function ouvrirCaisse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "?controller=Caisse&action=cloture");
            exit;
        }

        $userId      = $_SESSION['user_id'];
        $initialCash = Sanitizer::cleanFloat($_POST['solde_initial'] ?? 0.00);

        if ($this->sessionModel->getOpenSession($userId)) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=cloture&error=Une+session+est+déjà+ouverte");
            exit;
        }

        if ($this->sessionModel->openSession($userId, $initialCash)) {
            $this->auditLogger->logAction($userId, 'OUVERTURE_CAISSE', 'sessions_caisse', "Ouverture de caisse avec {$initialCash}");
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&success=Caisse+ouverte+avec+succès");
        } else {
            header("Location: " . BASE_URL . "?controller=Caisse&action=cloture&error=Erreur+lors+de+l'ouverture");
        }
        exit;
    }

    public function validerCloture(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "?controller=Caisse&action=cloture");
            exit;
        }

        $userId  = $_SESSION['user_id'];
        $session = $this->sessionModel->getOpenSession($userId);

        if (!$session) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Aucune+session+active");
            exit;
        }

        $finalReal   = Sanitizer::cleanFloat($_POST['solde_reel'] ?? 0.00);
        $finalSystem = $this->sessionModel->calculateSystemBalance($session->session_id, $userId);

        if ($this->sessionModel->closeSession($session->session_id, $finalSystem, $finalReal)) {
            $diff = $finalReal - $finalSystem;
            $this->auditLogger->logAction($userId, 'CLOTURE_CAISSE', 'sessions_caisse', "Clôture de session ID: {$session->session_id}. Écart: {$diff}", (string)$session->session_id);
            header("Location: " . BASE_URL . "?controller=Caisse&action=pvCloture&id=" . $session->session_id);
        } else {
            header("Location: " . BASE_URL . "?controller=Caisse&action=cloture&error=Erreur+lors+de+la+clôture");
        }
        exit;
    }

    public function pvCloture(): void
    {
        $sessionId = Sanitizer::cleanInt($_GET['id'] ?? 0);
        $userId    = $_SESSION['user_id'];

        if ($sessionId <= 0) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=ID+session+invalide");
            exit;
        }

        $session = $this->sessionModel->getSessionDetails($sessionId);

        if (!$session) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Session+introuvable");
            exit;
        }

        if ($_SESSION['role_id'] != 1 && $session->utilisateur_id != $userId) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Accès+non+autorisé");
            exit;
        }

        $data          = [];
        $data['title'] = "Procès-Verbal d'Arrêté de Caisse";
        $data['session'] = $session;
        $data['stats']   = $this->sessionModel->getSessionStats($sessionId, $session->utilisateur_id);

        $this->auditLogger->logAction($userId, 'CONSULTATION_PV_CAISSE', 'sessions_caisse', "Consultation PV Clôture Session: {$sessionId}", (string)$sessionId);

        require_once VIEW_PATH . 'caisse/pv_cloture.php';
    }

    // -------------------------------------------------------------------------
    // SIMULATION D'ÉPARGNE
    // -------------------------------------------------------------------------

    public function simulation(): void
    {
        AuthController::checkPermission('Caissier');

        $data                  = [];
        $data['title']         = "Simulateur d'Épargne sur Capitalisation";
        $data['account_types'] = $this->compteModel->getAccountTypes();

        $this->auditLogger->logAction(
            $_SESSION['user_id'],
            'CONSULTATION_SIMULATEUR',
            'outils',
            "Accès au simulateur d'épargne interactive",
            null
        );

        require_once VIEW_PATH . 'caisse/simulation.php';
    }

    // -------------------------------------------------------------------------
    // MÉTHODES PRIVÉES UTILITAIRES
    // -------------------------------------------------------------------------

    /**
     * Récupère les informations complètes d'un compte par son numéro.
     */
    private function getCompteInfoByNumber(string $numeroCompte): object|false
    {
        try {
            $db   = Database::getInstance()->getConnection();
            // FIX: table names → lowercase 'comptes', 'clients'
            $stmt = $db->prepare(
                "SELECT c.compte_id, c.solde, c.est_suspendu,
                        CONCAT(cl.nom, ' ', cl.prenom) AS nom_client
                 FROM comptes c
                 JOIN clients cl ON c.client_id = cl.client_id
                 WHERE c.numero_compte = :numero"
            );
            $stmt->bindValue(':numero', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
        } catch (Exception $e) {
            error_log("getCompteInfoByNumber: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne les N dernières transactions d'un compte.
     */
    private function getDerniersMovements(string $numeroCompte, int $limit = 5): array
    {
        try {
            $db  = Database::getInstance()->getConnection();
            // FIX: table names → lowercase 'transactions', 'comptes'
            $sql = "SELECT
                        T.type_transaction,
                        T.montant,
                        T.date_transaction,
                        T.reference_externe,
                        CASE WHEN Cs.numero_compte = :num THEN 'Débit' ELSE 'Crédit' END AS sens
                    FROM transactions T
                    LEFT JOIN comptes Cs ON T.compte_source_id = Cs.compte_id
                    LEFT JOIN comptes Cd ON T.compte_destination_id = Cd.compte_id
                    WHERE Cs.numero_compte = :num2 OR Cd.numero_compte = :num3
                    ORDER BY T.date_transaction DESC
                    LIMIT :lim";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':num',  $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':num2', $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':num3', $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':lim',  $limit,        PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
