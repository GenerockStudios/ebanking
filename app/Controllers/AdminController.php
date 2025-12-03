<?php
/**
 * AdminController.php
 * Gère les tâches d'administration (création/gestion des utilisateurs et des rôles).
 */

class AdminController {
    
    private $userModel;
    private $auditLogger;

    public function __construct() {
        // Vérification de la permission: STRICTEMENT ADMIN
        // On suppose que l'ID de rôle 1 correspond à 'Admin' (voir config.php)
        if (($_SESSION['role_id'] ?? 0) !== 1) {
            header("Location: " . BASE_URL . "?controller=Caisse&action=dashboard&error=Acces Interdit");
            exit();
        }

        $this->userModel = new UserModel();
        $this->auditLogger = new AuditLogger();
    }
    
    /**
     * Affiche la liste des utilisateurs et le formulaire de création.
     */
    public function manageUsers() {
        $data = [];
        $data['title'] = "Gestion des Utilisateurs du Système";
        
        // Simuler la récupération de tous les utilisateurs et rôles
        $data['users'] = $this->getAllSystemUsers();
        $data['roles'] = $GLOBALS['ROLES']; // Récupérer la liste des rôles depuis config.php
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            $this->createUserHandler($data);
        }
        
        require_once VIEW_PATH . 'admin/manage_users.php';
    }

    /**
     * Gère la logique de création d'un nouvel utilisateur.
     */
    private function createUserHandler(&$data) {
        $identifiant = Sanitizer::cleanString($_POST['identifiant'] ?? '');
        $password = $_POST['mot_de_passe'] ?? ''; // Non nettoyé, car haché
        $fullName = Sanitizer::cleanString($_POST['nom_complet'] ?? '');
        $roleId = Sanitizer::cleanInt($_POST['role_id'] ?? 0);
        $adminId = $_SESSION['user_id'];
        
        if (empty($identifiant) || empty($password) || empty($fullName) || $roleId === 0) {
            $data['error'] = "Tous les champs sont requis.";
            return;
        }

        // 1. Validation de la force du mot de passe (simplifié)
        if (strlen($password) < 8) {
             $data['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
             return;
        }

        // 2. Création via UserModel
        if ($this->userModel->createUser($identifiant, $password, $fullName, $roleId)) {
            $data['success'] = "L'utilisateur **{$identifiant}** a été créé avec succès.";
            $this->auditLogger->logAction($adminId, 'USER_CREATE', 'Utilisateurs', "Création utilisateur: {$identifiant} (Rôle ID: {$roleId})");
            
            // Recharger la liste des utilisateurs pour mise à jour
            $data['users'] = $this->getAllSystemUsers();
        } else {
            $data['error'] = "Échec de la création de l'utilisateur. L'identifiant existe-t-il déjà ?";
        }
    }
    
    /**
     * Simule la récupération de tous les utilisateurs du système.
     */
    private function getAllSystemUsers(): array {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT utilisateur_id, identifiant, nom_complet, role_id, date_creation, est_actif FROM Utilisateurs";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur BDD Admin: " . $e->getMessage());
            return [];
        }
    }
    
    // ... d'autres méthodes: deactivateUser(), changeRole(), resetPassword()
}