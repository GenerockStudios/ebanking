<?php
/**
 * UserModel.php
 * Gère la logique liée aux utilisateurs, rôles, et l'authentification.
 */

class UserModel {
    
    private $db;

    public function __construct() {
        // Récupère l'objet PDO via la classe Database (Singleton)
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Authentifie un utilisateur en vérifiant l'identifiant et le mot de passe haché.
     * @param string $identifier L'identifiant (login) de l'utilisateur.
     * @param string $password Le mot de passe non haché soumis.
     * @return object|false L'objet utilisateur si l'authentification réussit, sinon FALSE.
     */
    public function authenticate(string $identifier, string $password) {
        try {
            // 1. Récupérer l'utilisateur et son hash de mot de passe
            $stmt = $this->db->prepare("SELECT utilisateur_id, mot_de_passe_hash, nom_complet, role_id, est_actif 
                                        FROM Utilisateurs 
                                        WHERE identifiant = :identifiant");
            $stmt->bindParam(':identifiant', $identifier);
            $stmt->execute();
            
            $user = $stmt->fetch();

            if ($user && $user->est_actif) {
                // 2. Vérifier le mot de passe soumis avec le hash stocké
                if (password_verify($password, $user->mot_de_passe_hash)) {
                    // Authentification réussie
                    return $user;
                }
            }
            
            // Échec de l'authentification (identifiant non trouvé, inactif, ou mot de passe incorrect)
            return false;

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de l'authentification : " . $e->getMessage());
            // En cas d'erreur BDD, pour des raisons de sécurité, on retourne FALSE sans donner de détails.
            return false;
        }
    }

    /**
     * Récupère le rôle d'un utilisateur
     * @param int $userId L'ID de l'utilisateur.
     * @return string Le nom du rôle (ex: 'Caissier')
     */
    public function getUserRole(int $userId): string {
        try {
            $stmt = $this->db->prepare("SELECT R.nom_role 
                                        FROM Utilisateurs U
                                        JOIN Roles R ON U.role_id = R.role_id 
                                        WHERE U.utilisateur_id = :userId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchColumn();
            return $result ? $result : 'Invité'; // Rôle par défaut si non trouvé

        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de la récupération du rôle : " . $e->getMessage());
            return 'Erreur';
        }
    }
    
    /**
     * Crée un nouvel utilisateur (méthode Admin)
     * NOTE: Le mot de passe doit être haché avant l'insertion.
     */
    public function createUser(string $identifier, string $password, string $fullName, int $roleId): bool {
        try {
            // Hachage du mot de passe FORT (Bcrypt recommandé)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT); 
            
            $stmt = $this->db->prepare("INSERT INTO Utilisateurs (identifiant, mot_de_passe_hash, nom_complet, role_id) 
                                        VALUES (:identifiant, :pass, :nom, :role_id)");
            $stmt->bindParam(':identifiant', $identifier);
            $stmt->bindParam(':pass', $hashedPassword);
            $stmt->bindParam(':nom', $fullName);
            $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);

            return $stmt->execute();
            
        } catch (\PDOException $e) {
            error_log("Erreur BDD lors de la création utilisateur : " . $e->getMessage());
            return false;
        }
    }

    // ... d'autres méthodes comme updateUser, deactivateUser, etc.
}