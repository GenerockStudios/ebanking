<?php
/**
 * AdminController.php
 * Gère les tâches d'administration : utilisateurs, clients, audit, analytics, suspension.
 */

class AdminController
{
    private $userModel;
    private $auditLogger;
    private $db;

    public function __construct()
    {
        if (($_SESSION['role_id'] ?? 0) !== 1) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Acces+Interdit");
            exit();
        }
        $this->userModel   = new UserModel();
        $this->auditLogger = new AuditLogger();
        $this->db          = Database::getInstance()->getConnection();
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
            $stmt = $this->db->prepare("UPDATE Comptes SET est_suspendu = :val WHERE compte_id = :id");
            $stmt->bindParam(':val', $newValue, PDO::PARAM_INT);
            $stmt->bindParam(':id',  $compteId, PDO::PARAM_INT);
            $stmt->execute();

            $this->auditLogger->logAction($adminId, $label, 'Comptes', "Action: {$action} sur compte ID: {$compteId}", (string)$compteId);
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
            $where  = ["DATE(ja.date_heure) BETWEEN :d1 AND :d2"];
            $params = [':d1' => $dateDebut, ':d2' => $dateFin];

            if ($filterType === 'SUCCESS') {
                $where[] = "ja.type_action LIKE '%_SUCCESS%'";
            } elseif ($filterType === 'FAILURE') {
                $where[] = "ja.type_action LIKE '%_FAILURE%'";
            } elseif ($filterType === 'START') {
                $where[] = "ja.type_action LIKE '%_START%'";
            }

            if (!empty($filterUser)) {
                $where[]              = "u.identifiant LIKE :usr";
                $params[':usr'] = '%' . $filterUser . '%';
            }

            $sql = "SELECT ja.log_id, ja.type_action, ja.table_affectee,
                           ja.identifiant_element_affecte, ja.details, ja.date_heure,
                           COALESCE(u.identifiant, 'Système') AS identifiant_utilisateur
                    FROM Journal_Audit ja
                    LEFT JOIN Utilisateurs u ON ja.utilisateur_id = u.utilisateur_id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY ja.date_heure DESC
                    LIMIT 500";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            $data['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("auditLogs: " . $e->getMessage());
            $data['error'] = "Erreur lors de la récupération des logs.";
            $data['logs']  = [];
        }

        require_once VIEW_PATH . 'admin/audit_logs.php';
    }

    // -------------------------------------------------------------------------
    // DASHBOARD ANALYTIQUE
    // -------------------------------------------------------------------------

    public function analyticsDashboard(): void
    {
        $data          = [];
        $data['title'] = "Tableau de Bord Analytique";

        try {
            // -- Statistiques globales du jour --
            $stmt = $this->db->query(
                "SELECT
                    COUNT(*) AS total_txn_aujourd_hui,
                    SUM(CASE WHEN type_transaction = 'DEPOT'   THEN montant ELSE 0 END) AS total_depots,
                    SUM(CASE WHEN type_transaction = 'RETRAIT' THEN montant ELSE 0 END) AS total_retraits,
                    SUM(CASE WHEN type_transaction = 'TRANSFERT_INT' THEN montant ELSE 0 END) AS total_transferts
                 FROM Transactions
                 WHERE DATE(date_transaction) = CURDATE()"
            );
            $data['stats_jour'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // -- Totaux généraux --
            $stmt2 = $this->db->query(
                "SELECT
                    (SELECT COUNT(*) FROM Clients) AS nb_clients,
                    (SELECT COUNT(*) FROM Comptes) AS nb_comptes,
                    (SELECT COUNT(*) FROM Utilisateurs WHERE est_actif = 1) AS nb_utilisateurs,
                    (SELECT SUM(solde) FROM Comptes) AS total_encours"
            );
            $data['stats_globales'] = $stmt2->fetch(PDO::FETCH_ASSOC);

            // -- Données 7 jours pour Chart.js --
            $stmt3 = $this->db->query(
                "SELECT
                    DATE(date_transaction) AS jour,
                    COUNT(*) AS nb_transactions,
                    SUM(CASE WHEN type_transaction = 'DEPOT'   THEN montant ELSE 0 END) AS depots,
                    SUM(CASE WHEN type_transaction = 'RETRAIT' THEN montant ELSE 0 END) AS retraits,
                    SUM(CASE WHEN type_transaction = 'TRANSFERT_INT' THEN montant ELSE 0 END) AS transferts
                 FROM Transactions
                 WHERE date_transaction >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(date_transaction)
                 ORDER BY jour ASC"
            );
            $rows7j = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            // Normaliser sur 7 jours (combler les jours sans transaction)
            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $chart[$day] = ['jour' => $day, 'nb_transactions' => 0, 'depots' => 0, 'retraits' => 0, 'transferts' => 0];
            }
            foreach ($rows7j as $r) {
                $chart[$r['jour']] = $r;
            }
            $data['chart_7j'] = array_values($chart);

            // -- Répartition par type de transaction (30 derniers jours) --
            $stmt4 = $this->db->query(
                "SELECT type_transaction, COUNT(*) AS nb, SUM(montant) AS total
                 FROM Transactions
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
            $this->auditLogger->logAction($adminId, 'USER_CREATE', 'Utilisateurs', "Création: {$identifiant} (Rôle {$roleId})");
            $data['users']   = $this->getAllSystemUsers();
        } else {
            $data['error'] = "Échec. L'identifiant existe peut-être déjà.";
        }
    }

    private function getAllSystemUsers(): array
    {
        try {
            $sql  = "SELECT utilisateur_id, identifiant, nom_complet, role_id, date_creation, est_actif FROM Utilisateurs ORDER BY date_creation DESC";
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
            $sql = "SELECT cl.client_id, cl.nom, cl.prenom, cl.date_naissance, cl.telephone,
                           cl.email, cl.numero_identite, cl.adresse, cl.date_creation,
                           c.compte_id, c.solde, c.numero_compte, c.est_suspendu
                    FROM Clients cl
                    INNER JOIN Comptes c ON c.client_id = cl.client_id
                    ORDER BY cl.date_creation DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAllSystemClients: " . $e->getMessage());
            return [];
        }
    }
}
