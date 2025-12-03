<?php
/**
 * ReferenceGenerator.php
 * Classe utilitaire pour générer des identifiants uniques et structurés (numéros de compte, références transactionnelles).
 * Assure que les références ne sont pas facilement prévisibles.
 */

class ReferenceGenerator {
    
    // Longueur souhaitée pour le numéro de compte (ex: 12 chiffres)
    const ACCOUNT_NUMBER_LENGTH = 12; 
    
    // Préfixe pour le numéro de transaction (peut être un code banque/succursale)
    const TRANSACTION_PREFIX = 'TXN'; 
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Génère un numéro de compte unique et s'assure qu'il n'existe pas en BDD.
     * @return string Le numéro de compte unique.
     */
    public function generateUniqueAccountNumber(): string {
        $maxAttempts = 5;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Générer un nombre aléatoire de la longueur désirée
            $randomNumber = $this->generateRandomNumericString(self::ACCOUNT_NUMBER_LENGTH);
            
            // Ajouter potentiellement un préfixe ou un checksum ici (ommis pour la simplicité)
            $accountNumber = $randomNumber; 

            // Vérification de l'unicité dans la base de données
            if (!$this->isAccountNumberTaken($accountNumber)) {
                return $accountNumber; // Unique et prêt à l'emploi
            }
        }

        // Échec de la génération après plusieurs tentatives (très rare)
        throw new Exception("Échec critique: Impossible de générer un numéro de compte unique.");
    }
    
    /**
     * Génère une référence unique pour une transaction (inclut un timestamp et un aléatoire).
     * @return string La référence de transaction unique.
     */
    public function generateTransactionReference(): string {
        $timestamp = date('YmdHis'); // AnnéeMoisJourHeureMinuteSeconde
        $randomPart = $this->generateRandomNumericString(4); // 4 chiffres aléatoires
        
        // Ex: TXN-202511062243-1234
        return self::TRANSACTION_PREFIX . '-' . $timestamp . '-' . $randomPart;
    }

    /**
     * Vérifie l'existence du numéro de compte dans la table Comptes.
     * @param string $number Le numéro à vérifier.
     * @return bool Vrai s'il existe.
     */
    private function isAccountNumberTaken(string $number): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Comptes WHERE numero_compte = :number");
        $stmt->bindParam(':number', $number);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Génère une chaîne de chiffres aléatoires de la longueur spécifiée.
     * @param int $length
     * @return string
     */
    private function generateRandomNumericString(int $length): string {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }
}