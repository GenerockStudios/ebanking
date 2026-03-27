<?php
/**
 * AdminController.php
 * Gère les tâches d'administration : utilisateurs, clients, audit, analytics, suspension.
 */

class AdminController
{
    private $userModel;
    private $plafondModel;
    private $kycModel;
    private $auditLogModel;
    private $auditLogger;
    private $db;

    public function __construct()
    {
        if (($_SESSION['role_id'] ?? 0) !== 1) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Acces+Interdit");
            exit();
        }
        $this->userModel     = new UserModel();
        $this->plafondModel  = new PlafondModel();
        $this->kycModel      = new KycModel();
        $this->auditLogModel = new AuditLogModel();
        $this->auditLogger   = new AuditLogger();
        $this->db            = Database::getInstance()->getConnection();
    }

    // -------------------------------------------------------------------------
    // GESTION UTILISATEURS
    // -------------------------------------------------------------------------

    public function manageUsers(): void
    {
        $data            = [];
        $data['title']   = "Gestion des Utilisateurs du Système";
        $data['users']   = $this->getAllSystemUsers();
        $data['roles']   = $GLOBALS['ROLES'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            $this->createUserHandler($data);
        }

        require_once VIEW_PATH . 'admin/manage_users.php';
    }

    // -------------------------------------------------------------------------
    // GESTION CLIENTS
    // -------------------------------------------------------------------------

    public function manageClients(): void
    {
        $data          = [];
        $data['title'] = "Gestion des Clients";
        $data['users'] = $this->getAllSystemClients();

        require_once VIEW_PATH . 'admin/manage_clients.php';
    }

    /**
     * Affiche la fiche signalétique consolidée (Vue 360°) d'un client.
     */
    public function ficheClient(): void
    {
        $clientId = Sanitizer::cleanInt($_GET['client_id'] ?? 0);
        $adminId  = $_SESSION['user_id'];

        if ($clientId <= 0) {
            header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&error=Client+introuvable");
            exit;
        }

        try {
            $clientModel = new ClientModel();
            $compteModel = new CompteModel();

            $client = $clientModel->getClientInfo($clientId);
            if (!$client) {
                header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&error=Client+inexistant");
                exit;
            }

            $accounts  = $compteModel->getAccountsByClientId($clientId);
            $documents = $clientModel->getClientDocuments($clientId);

            $this->auditLogger->logAction(
                $adminId, 
                'FICHE_CLIENT_VIEW', 
                'clients', 
                "Consultation fiche 360° du client: {$client['nom']} {$client['prenom']}", 
                (string)$clientId
            );

            $data = [
                'title'     => "Fiche Signalétique - " . strtoupper($client['nom']) . " " . $client['prenom'],
                'client'    => $client,
                'accounts'  => $accounts,
                'documents' => $documents
            ];

            require_once VIEW_PATH . 'admin/fiche_client.php';

        } catch (\Exception $e) {
            error_log("ficheClient action error: " . $e->getMessage());
            header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&error=Erreur+interne");
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // SUSPENSION / RÉACTIVATION D'UN COMPTE
    // -------------------------------------------------------------------------

    public function suspendCompte(): void
    {
        $compteId = Sanitizer::cleanInt($_POST['compte_id'] ?? 0);
        $action   = Sanitizer::cleanString($_POST['action_type'] ?? '');
        $adminId  = $_SESSION['user_id'];

        if ($compteId <= 0 || !in_array($action, ['suspendre', 'reactiver'])) {
            header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&error=Parametre+invalide");
            exit;
        }

        $newValue = ($action === 'suspendre') ? 1 : 0;
        $label    = ($action === 'suspendre') ? 'COMPTE_SUSPENDU' : 'COMPTE_REACTIVÉ';

        try {
            // FIX: table name → lowercase 'comptes'
            $stmt = $this->db->prepare("UPDATE comptes SET est_suspendu = :val WHERE compte_id = :id");
            $stmt->bindValue(':val', $newValue,  PDO::PARAM_INT);
            $stmt->bindValue(':id',  $compteId,  PDO::PARAM_INT);
            $stmt->execute();

            $this->auditLogger->logAction($adminId, $label, 'comptes', "Action: {$action} sur compte ID: {$compteId}", (string)$compteId);
            header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&success=Operation+effectuee");
        } catch (\PDOException $e) {
            error_log("suspendCompte: " . $e->getMessage());
            header("Location: " . BASE_URL . "?controller=Admin&action=manageClients&error=Erreur+BDD");
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // AUDIT LOGS
    // -------------------------------------------------------------------------

    public function auditLogs(): void
    {
        $data          = [];
        $data['title'] = "Journal d'Audit Panoptique";

        $filterType = Sanitizer::cleanString($_GET['filter_type'] ?? '');
        $filterUser = Sanitizer::cleanString($_GET['filter_user'] ?? '');
        $dateDebut  = Sanitizer::cleanString($_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days')));
        $dateFin    = Sanitizer::cleanString($_GET['date_fin']   ?? date('Y-m-d'));

        $data['filter_type'] = $filterType;
        $data['filter_user'] = $filterUser;
        $data['date_debut']  = $dateDebut;
        $data['date_fin']    = $dateFin;

        try {
            $filters = [
                'date_debut'     => $dateDebut,
                'date_fin'       => $dateFin,
                'type_action'    => $filterType,
                'table_affectee' => Sanitizer::cleanString($_GET['filter_table'] ?? ''),
                'target_id'      => Sanitizer::cleanString($_GET['filter_id'] ?? ''),
                'only_failures'  => isset($_GET['only_failures'])
            ];

            if (!empty($filterUser)) {
                // FIX: table name → lowercase 'utilisateurs'
                $stmt = $this->db->prepare("SELECT utilisateur_id FROM utilisateurs WHERE identifiant = :id");
                $stmt->bindValue(':id', $filterUser, PDO::PARAM_STR);
                $stmt->execute();
                $usr = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($usr) {
                    $filters['user_id'] = $usr['utilisateur_id'];
                }
            }

            $data['logs'] = $this->auditLogModel->searchLogs($filters);

        } catch (\Exception $e) {
            error_log("auditLogs action error: " . $e->getMessage());
            $data['error'] = "Erreur lors de la récupération des logs.";
            $data['logs']  = [];
        }

        require_once VIEW_PATH . 'admin/audit_logs.php';
    }

    /**
     * Rapport de Sécurité et Audit (Vue Impression).
     */
    public function securityAuditReport(): void
    {
        $adminId = $_SESSION['user_id'];
        
        $dateDebut    = Sanitizer::cleanString($_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days')));
        $dateFin      = Sanitizer::cleanString($_GET['date_fin']   ?? date('Y-m-d'));
        $onlyFailures = isset($_GET['only_failures']);

        try {
            $filters = [
                'date_debut'    => $dateDebut,
                'date_fin'      => $dateFin,
                'only_failures' => $onlyFailures
            ];

            $logs  = $this->auditLogModel->searchLogs($filters);
            $stats = $this->auditLogModel->getSecurityStats($dateDebut, $dateFin);

            $this->auditLogger->logAction(
                $adminId, 
                'SECURITY_REPORT_GENERATE', 
                'journal_audit', 
                "Génération du rapport d'audit de sécurité financier - Plage: $dateDebut à $dateFin"
            );

            $data = [
                'title'         => "Rapport d'Audit Sécurité & Intégrité",
                'logs'          => $logs,
                'stats'         => $stats,
                'date_debut'    => $dateDebut,
                'date_fin'      => $dateFin,
                'only_failures' => $onlyFailures,
                'date_edition'  => date('d/m/Y H:i:s')
            ];

            require_once VIEW_PATH . 'admin/audit_report.php';

        } catch (\Exception $e) {
            error_log("securityAuditReport error: " . $e->getMessage());
            header("Location: " . BASE_URL . "?controller=Admin&action=auditLogs&error=Erreur+generation+rapport");
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // DASHBOARD ANALYTIQUE
    // -------------------------------------------------------------------------

    public function analyticsDashboard(): void
    {
        $data          = [];
        $data['title'] = "Tableau de Bord Analytique";

        try {
            // FIX: table name → lowercase 'transactions'
            $stmt = $this->db->query(
                "SELECT
                    COUNT(*) AS total_txn_aujourd_hui,
                    SUM(CASE WHEN type_transaction = 'DEPOT'    THEN montant ELSE 0 END) AS total_depots,
                    SUM(CASE WHEN type_transaction = 'RETRAIT'  THEN montant ELSE 0 END) AS total_retraits,
                    SUM(CASE WHEN type_transaction = 'TRANSFERT' THEN montant ELSE 0 END) AS total_transferts
                 FROM transactions
                 WHERE DATE(date_transaction) = CURDATE()"
            );
            $data['stats_jour'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // FIX: table names → lowercase 'clients', 'comptes', 'utilisateurs'
            $stmt2 = $this->db->query(
                "SELECT
                    (SELECT COUNT(*) FROM clients) AS nb_clients,
                    (SELECT COUNT(*) FROM comptes) AS nb_comptes,
                    (SELECT COUNT(*) FROM utilisateurs WHERE est_actif = 1) AS nb_utilisateurs,
                    (SELECT SUM(solde) FROM comptes) AS total_encours"
            );
            $data['stats_globales'] = $stmt2->fetch(PDO::FETCH_ASSOC);

            // FIX: table name → lowercase 'transactions'
            // FIX: type → 'TRANSFERT' (cohérent avec TransactionModel)
            $stmt3 = $this->db->query(
                "SELECT
                    DATE(date_transaction) AS jour,
                    COUNT(*) AS nb_transactions,
                    SUM(CASE WHEN type_transaction = 'DEPOT'    THEN montant ELSE 0 END) AS depots,
                    SUM(CASE WHEN type_transaction = 'RETRAIT'  THEN montant ELSE 0 END) AS retraits,
                    SUM(CASE WHEN type_transaction = 'TRANSFERT' THEN montant ELSE 0 END) AS transferts
                 FROM transactions
                 WHERE date_transaction >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(date_transaction)
                 ORDER BY jour ASC"
            );
            $rows7j = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $chart[$day] = ['jour' => $day, 'nb_transactions' => 0, 'depots' => 0, 'retraits' => 0, 'transferts' => 0];
            }
            foreach ($rows7j as $r) {
                $chart[$r['jour']] = $r;
            }
            $data['chart_7j'] = array_values($chart);

            // FIX: table name → lowercase 'transactions'
            $stmt4 = $this->db->query(
                "SELECT type_transaction, COUNT(*) AS nb, SUM(montant) AS total
                 FROM transactions
                 WHERE date_transaction >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY type_transaction"
            );
            $data['repartition'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("analyticsDashboard: " . $e->getMessage());
            $data['error'] = "Erreur lors du chargement des données analytiques.";
        }

        require_once VIEW_PATH . 'admin/analytics.php';
    }

    // -------------------------------------------------------------------------
    // GESTION DES PLAFONDS & CONTRAT DE DÉPLAFONNEMENT
    // -------------------------------------------------------------------------

    public function managePlafonds(): void
    {
        $data = [
            'title'    => "Gestion Experte des Plafonds Comptes",
            'plafonds' => $this->plafondModel->getAllAccountPlafonds()
        ];
        require_once VIEW_PATH . 'admin/manage_plafonds.php';
    }

    public function editPlafond(): void
    {
        $compteId = Sanitizer::cleanInt($_GET['compte_id'] ?? 0);
        if ($compteId <= 0) {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Compte+introuvable");
            exit;
        }

        $plafond = $this->plafondModel->getPlafondByCompteId($compteId);
        if (!$plafond) {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Compte+inexistant");
            exit;
        }

        $data = [
            'title'   => "Paramétrage des Plafonds - " . $plafond['numero_compte'],
            'plafond' => $plafond
        ];
        require_once VIEW_PATH . 'admin/edit_plafond.php';
    }

    public function updatePlafond(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds");
            exit;
        }

        $compteId  = Sanitizer::cleanInt($_POST['compte_id'] ?? 0);
        $retrait   = (float)($_POST['plafond_retrait_journalier'] ?? 0);
        $depot     = (float)($_POST['plafond_depot_journalier'] ?? 0);
        $transfert = (float)($_POST['plafond_transfert_mensuel'] ?? 0);
        $adminId   = $_SESSION['user_id'];

        if ($compteId <= 0 || $retrait < 0 || $depot < 0 || $transfert < 0) {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Valeurs+invalides");
            exit;
        }

        if ($this->plafondModel->updatePlafond($compteId, $retrait, $depot, $transfert)) {
            $this->auditLogger->logAction(
                $adminId,
                'PLAFOND_UPDATE',
                'plafonds_comptes',
                "Mise à jour plafonds ID Compte: $compteId (R:$retrait, D:$depot, T:$transfert)",
                (string)$compteId
            );
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&success=Plafonds+mis+a+jour");
        } else {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Erreur+lors+de+la+mise+a+jour");
        }
        exit;
    }

    public function contratPlafond(): void
    {
        $compteId = Sanitizer::cleanInt($_GET['compte_id'] ?? 0);
        $adminId  = $_SESSION['user_id'];

        if ($compteId <= 0) {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Compte+introuvable");
            exit;
        }

        $plafond = $this->plafondModel->getPlafondByCompteId($compteId);
        if (!$plafond) {
            header("Location: " . BASE_URL . "?controller=Admin&action=managePlafonds&error=Compte+inexistant");
            exit;
        }

        $this->auditLogger->logAction(
            $adminId,
            'CONTRAT_PLAFOND_VIEW',
            'plafonds_comptes',
            "Génération contrat de déplafonnement pour compte: {$plafond['numero_compte']}",
            (string)$compteId
        );

        $data = [
            'title'   => "Contrat d'Engagement de Modification de Plafonds",
            'plafond' => $plafond,
            'date'    => date('d/m/Y H:i:s')
        ];
        require_once VIEW_PATH . 'admin/contrat_plafond.php';
    }

    // -------------------------------------------------------------------------
    // AUDIT DE CONFORMITÉ KYC & RISQUES
    // -------------------------------------------------------------------------

    public function auditKyc(): void
    {
        $adminId = $_SESSION['user_id'];
        
        $filters = [
            'type_document' => Sanitizer::cleanString($_GET['type_doc'] ?? '')
        ];

        try {
            $anomalies = $this->kycModel->getKycAnomalies($filters);

            $this->auditLogger->logAction(
                $adminId, 
                'KYC_AUDIT_GENERATE', 
                'documents_kyc', 
                "Génération du rapport d'audit de conformité KYC (Anomalies trouvées: " . count($anomalies) . ")"
            );

            $data = [
                'title'        => "Audit de Conformité KYC et Risque",
                'anomalies'    => $anomalies,
                'filters'      => $filters,
                'date_edition' => date('d/m/Y H:i:s')
            ];

            require_once VIEW_PATH . 'admin/audit_kyc.php';

        } catch (\Exception $e) {
            error_log("auditKyc error: " . $e->getMessage());
            header("Location: " . BASE_URL . "?controller=Admin&action=analyticsDashboard&error=Erreur+generation+audit");
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // DASHBOARD ANALYTIQUE : SNAPSHOT FIN DE MOIS
    // -------------------------------------------------------------------------

    public function snapshotBilan(): void
    {
        $adminId   = $_SESSION['user_id'];
        $statModel = new StatistiqueModel();

        try {
            $availableDates = $statModel->getAvailableSnapshotDates();
            
            if (empty($availableDates)) {
                header("Location: " . BASE_URL . "?controller=Admin&action=analyticsDashboard&error=Aucun+snapshot+disponible");
                exit;
            }

            $currentDate  = Sanitizer::cleanString($_GET['current_date']  ?? ($availableDates[0] ?? ''));
            $previousDate = Sanitizer::cleanString($_GET['previous_date'] ?? ($availableDates[1] ?? ''));

            $currentData  = $statModel->getFinancialSnapshot($currentDate);
            $previousData = $statModel->getFinancialSnapshot($previousDate);

            $reportData = $statModel->getEvolutionData($currentData, $previousData);

            $totalM   = array_sum(array_column($currentData,  'total_solde'));
            $totalM_1 = array_sum(array_column($previousData, 'total_solde'));
            $globalEvolution = ($totalM_1 > 0) ? (($totalM - $totalM_1) / $totalM_1) * 100 : (($totalM > 0) ? 100 : 0);

            $this->auditLogger->logAction(
                $adminId, 
                'SNAPSHOT_BILAN_VIEW', 
                'historique_soldes', 
                "Consultation du bilan analytique comparatif ($currentDate vs $previousDate)"
            );

            $data = [
                'title'            => "Bilan Analytique de Snapshot Fin de Mois",
                'dates'            => $availableDates,
                'current_date'     => $currentDate,
                'previous_date'    => $previousDate,
                'report'           => $reportData,
                'total_m'          => $totalM,
                'total_m_1'        => $totalM_1,
                'global_evolution' => round($globalEvolution, 2),
                'date_edition'     => date('d/m/Y H:i:s')
            ];

            require_once VIEW_PATH . 'admin/snapshot_bilan.php';

        } catch (\Exception $e) {
            error_log("snapshotBilan action error: " . $e->getMessage());
            header("Location: " . BASE_URL . "?controller=Admin&action=analyticsDashboard&error=Erreur+interne+snapshot");
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // MÉTHODES PRIVÉES
    // -------------------------------------------------------------------------

    private function createUserHandler(array &$data): void
    {
        $identifiant = Sanitizer::cleanString($_POST['identifiant'] ?? '');
        $password    = $_POST['mot_de_passe'] ?? '';
        $fullName    = Sanitizer::cleanString($_POST['nom_complet'] ?? '');
        $roleId      = Sanitizer::cleanInt($_POST['role_id'] ?? 0);
        $adminId     = $_SESSION['user_id'];

        if (empty($identifiant) || empty($password) || empty($fullName) || $roleId === 0) {
            $data['error'] = "Tous les champs sont requis.";
            return;
        }
        if (strlen($password) < 8) {
            $data['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            return;
        }
        if ($this->userModel->createUser($identifiant, $password, $fullName, $roleId)) {
            $data['success'] = "L'utilisateur {$identifiant} a été créé avec succès.";
            $this->auditLogger->logAction($adminId, 'USER_CREATE', 'utilisateurs', "Création: {$identifiant} (Rôle {$roleId})");
            $data['users']   = $this->getAllSystemUsers();
        } else {
            $data['error'] = "Échec. L'identifiant existe peut-être déjà.";
        }
    }

    private function getAllSystemUsers(): array
    {
        try {
            // FIX: table name → lowercase 'utilisateurs'
            $sql  = "SELECT utilisateur_id, identifiant, nom_complet, role_id, date_creation, est_actif FROM utilisateurs ORDER BY date_creation DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAllSystemUsers: " . $e->getMessage());
            return [];
        }
    }

    private function getAllSystemClients(): array
    {
        try {
            // FIX: table names → lowercase 'clients', 'comptes'
            $sql = "SELECT cl.client_id, cl.nom, cl.prenom, cl.date_naissance, cl.telephone,
                           cl.email, cl.numero_identite, cl.adresse, cl.date_creation,
                           c.compte_id, c.solde, c.numero_compte, c.est_suspendu
                    FROM clients cl
                    INNER JOIN comptes c ON c.client_id = cl.client_id
                    ORDER BY cl.date_creation DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAllSystemClients: " . $e->getMessage());
            return [];
        }
    }
}
