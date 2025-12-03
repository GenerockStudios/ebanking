<?php
/**
 * ClientController.php
 * Gère le flux pour la création de clients, l'ouverture de comptes et le KYC de base.
 */

class ClientController {
    
    private $clientModel;
    private $compteModel;
    private $auditLogger;

    public function __construct() {
        // Vérification des permissions : Seuls les Caissiers ou Admin peuvent créer des clients
        AuthController::checkPermission('Caissier'); 

        $this->clientModel = new ClientModel();
        $this->compteModel = new CompteModel();
        $this->auditLogger = new AuditLogger();
    }
    
    /**
     * Affiche le formulaire de création de client et traite la soumission.
     * Cette méthode gère à la fois l'insertion du client ET l'ouverture d'un compte initial.
     */
    public function nouveauClient() {
        $data = [];
        
        // Récupérer la liste des types de comptes pour le formulaire (Simulé ici, idéalement via CompteModel)
        $data['account_types'] = [
            1 => 'Compte Courant',
            2 => 'Compte Épargne'
        ];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Nettoyage et Récupération des données POST
            $clientData = [
                'nom' => Sanitizer::cleanString($_POST['nom'] ?? ''),
                'prenom' => Sanitizer::cleanString($_POST['prenom'] ?? ''),
                'date_naissance' => Sanitizer::cleanString($_POST['date_naissance'] ?? ''), // Validation de format requise
                'adresse' => Sanitizer::cleanString($_POST['adresse'] ?? ''),
                'telephone' => Sanitizer::cleanString($_POST['telephone'] ?? ''),
                'email' => Sanitizer::cleanString($_POST['email'] ?? ''),
                'numero_identite' => Sanitizer::cleanString($_POST['numero_identite'] ?? ''),
            ];
            $typeCompteId = Sanitizer::cleanInt($_POST['type_compte_id'] ?? 0);
            $userId = $_SESSION['user_id'];
            
            // 2. Validation basique
            if (empty($clientData['nom']) || empty($clientData['numero_identite']) || $typeCompteId === 0) {
                $data['error'] = "Erreur: Les champs Nom, Numéro d'identité et Type de compte sont obligatoires.";
            } else {
                
                // --- Début du processus de création client et compte ---
                $clientId = $this->clientModel->createClient($clientData);
                
                if ($clientId) {
                    $this->auditLogger->logAction($userId, 'CLIENT_CREATE_SUCCESS', 'Clients', "Création client réussie. ID: {$clientId}", (string)$clientId);
                    
                    // 3. Ouvrir le compte bancaire initial
                    $numeroCompte = $this->compteModel->openNewAccount($clientId, $typeCompteId, $userId, 0.00);
                    
                    if ($numeroCompte) {
                        $data['success'] = "Client **{$clientData['nom']}** créé. Compte **{$numeroCompte}** ouvert avec succès.";
                        $this->auditLogger->logAction($userId, 'COMPTE_OPEN_SUCCESS', 'Comptes', "Ouverture compte {$numeroCompte} pour client ID {$clientId}", (string)$numeroCompte);
                        
                        // Réinitialiser les données du formulaire après succès
                        unset($_POST); 
                    } else {
                        // Cas critique: client créé mais compte échoué. Nécessite une compensation (rollback client) ou intervention manuelle.
                        $data['error'] = "Erreur: Client créé (ID: {$clientId}), mais échec de l'ouverture du compte. Contacter l'administrateur.";
                    }
                    
                } else {
                    $data['error'] = "Échec de la création du client (peut-être Numéro d'identité déjà utilisé).";
                    $this->auditLogger->logAction($userId, 'CLIENT_CREATE_FAILURE', 'Clients', "Échec création client. Identité: {$clientData['numero_identite']}");
                }
            }
        }
        
        $data['title'] = "Création d'un Nouveau Client et Ouverture de Compte";
        require_once VIEW_PATH . 'client/nouveau_client.php';
    }
    
    /**
     * Affiche le profil d'un client et ses comptes.
     */
    public function profile() {
        // ... Logique pour récupérer un client par ID ou Numéro d'identité
        // et charger la vue client/profile.php
    }
    
    // ... d'autres méthodes comme manageKyc(), etc.
}