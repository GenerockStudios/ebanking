<?php

/**
 * CaisseController.php
 * Gère le flux pour toutes les opérations de guichet (Dépôt, Retrait, Transfert).
 */

class CaisseController
{
    private $transactionModel;
    private $compteModel;
    private $auditLogger;

    public function __construct()
    {
        AuthController::checkPermission('Caissier');
        $this->transactionModel = new TransactionModel();
        $this->compteModel      = new CompteModel();
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
                if ($this->transactionModel->faireDepot($compteId, $montant, $userId)) {
                    $newBalance = $this->compteModel->getAccountBalance($numeroCompte);
                    $data['success']     = "Dépôt de " . number_format($montant, 2, ',', ' ') . " effectué sur le compte " . $numeroCompte . ".";
                    $data['new_balance'] = $newBalance;
                    $data['derniers_mouvements'] = $this->getDerniersMovements($numeroCompte, 5);

                    // Stocker en session pour le reçu imprimable
                    $_SESSION['last_receipt'] = [
                        'operation'      => 'DÉPÔT',
                        'numero_compte'  => $numeroCompte,
                        'nom_client'     => $compteInfo->nom_client ?? 'N/A',
                        'montant'        => number_format($montant, 2, ',', ' '),
                        'nouveau_solde'  => number_format($newBalance, 2, ',', ' '),
                        'caissier'       => $_SESSION['nom_complet'] ?? 'N/A',
                        'horodatage'     => date('d/m/Y H:i:s'),
                        'reference'      => 'OP-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
                    ];
                    $data['show_receipt'] = true;
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
                    if ($this->transactionModel->faireRetrait($compteId, $montant, $userId)) {
                        $newBalance = $this->compteModel->getAccountBalance($numeroCompte);
                        $data['success']     = "Retrait de " . number_format($montant, 2, ',', ' ') . " effectué sur le compte " . $numeroCompte . ".";
                        $data['new_balance'] = $newBalance;
                        $data['derniers_mouvements'] = $this->getDerniersMovements($numeroCompte, 5);

                        $_SESSION['last_receipt'] = [
                            'operation'      => 'RETRAIT',
                            'numero_compte'  => $numeroCompte,
                            'nom_client'     => $compteInfo->nom_client ?? 'N/A',
                            'montant'        => number_format($montant, 2, ',', ' '),
                            'nouveau_solde'  => number_format($newBalance, 2, ',', ' '),
                            'caissier'       => $_SESSION['nom_complet'] ?? 'N/A',
                            'horodatage'     => date('d/m/Y H:i:s'),
                            'reference'      => 'OP-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
                        ];
                        $data['show_receipt'] = true;
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
                    if ($this->transactionModel->faireTransfert($sourceInfo->compte_id, $destInfo->compte_id, $montant, $userId)) {
                        $newBalance      = $this->compteModel->getAccountBalance($compteSourceNum);
                        $data['success'] = "Transfert de " . number_format($montant, 2, ',', ' ') . " de " . $compteSourceNum . " vers " . $compteDestNum . " réussi.";
                        $data['new_balance'] = $newBalance;
                        $data['derniers_mouvements'] = $this->getDerniersMovements($compteSourceNum, 5);

                        $_SESSION['last_receipt'] = [
                            'operation'      => 'TRANSFERT',
                            'numero_compte'  => $compteSourceNum,
                            'nom_client'     => $sourceInfo->nom_client ?? 'N/A',
                            'montant'        => number_format($montant, 2, ',', ' '),
                            'nouveau_solde'  => number_format($newBalance, 2, ',', ' '),
                            'caissier'       => $_SESSION['nom_complet'] ?? 'N/A',
                            'horodatage'     => date('d/m/Y H:i:s'),
                            'reference'      => 'OP-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
                            'compte_dest'    => $compteDestNum,
                            'nom_dest'       => $destInfo->nom_client ?? 'N/A',
                        ];
                        $data['show_receipt'] = true;
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
    // MÉTHODES PRIVÉES UTILITAIRES
    // -------------------------------------------------------------------------

    /**
     * Récupère les informations complètes d'un compte (ID, solde, nom client) par son numéro.
     */
    private function getCompteInfoByNumber(string $numeroCompte): object|false
    {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT c.compte_id, c.solde, c.est_suspendu,
                        CONCAT(cl.nom, ' ', cl.prenom) AS nom_client
                 FROM Comptes c
                 JOIN Clients cl ON c.client_id = cl.client_id
                 WHERE c.numero_compte = :numero"
            );
            $stmt->bindParam(':numero', $numeroCompte);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
        } catch (Exception $e) {
            error_log("getCompteInfoByNumber: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne les N dernières transactions d'un compte (pour affichage rapide).
     */
    private function getDerniersMovements(string $numeroCompte, int $limit = 5): array
    {
        try {
            $db  = Database::getInstance()->getConnection();
            $sql = "SELECT
                        T.type_transaction,
                        T.montant,
                        T.date_transaction,
                        T.reference_externe,
                        CASE WHEN Cs.numero_compte = :num THEN 'Débit' ELSE 'Crédit' END AS sens
                    FROM Transactions T
                    LEFT JOIN Comptes Cs ON T.compte_source_id = Cs.compte_id
                    LEFT JOIN Comptes Cd ON T.compte_destination_id = Cd.compte_id
                    WHERE Cs.numero_compte = :num2 OR Cd.numero_compte = :num3
                    ORDER BY T.date_transaction DESC
                    LIMIT :lim";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',  $numeroCompte);
            $stmt->bindParam(':num2', $numeroCompte);
            $stmt->bindParam(':num3', $numeroCompte);
            $stmt->bindParam(':lim',  $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
