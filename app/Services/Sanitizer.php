<?php
/**
 * Sanitizer.php
 * Classe utilitaire pour nettoyer et sécuriser les données provenant de l'utilisateur (GET, POST).
 */

class Sanitizer {
    
    /**
     * Nettoie une chaîne de caractères pour l'utiliser dans le HTML ou comme paramètre de requête.
     * Enlève les balises HTML et encode les caractères spéciaux.
     * @param mixed $input La valeur à nettoyer.
     * @return string La chaîne nettoyée.
     */
    public static function cleanString($input): string {
        if (!is_scalar($input)) {
            // Gérer les cas où l'entrée n'est pas une chaîne ou un nombre (ex: null, array)
            $input = '';
        }
        
        // 1. Enlever les balises HTML (prévention XSS)
        $cleaned = strip_tags($input); 
        
        // 2. Encoder les caractères spéciaux (double encodage désactivé pour éviter de ré-encoder des entités existantes)
        // Ceci est une précaution supplémentaire si la chaîne est affichée plus tard.
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8', false); 
        
        // 3. Supprimer les espaces blancs en début et fin
        return trim($cleaned);
    }

    /**
     * Nettoie une valeur et s'assure qu'elle est un nombre entier (INT).
     * @param mixed $input La valeur à nettoyer.
     * @return int L'entier nettoyé (0 si non-numérique).
     */
    public static function cleanInt($input): int {
        if (!is_scalar($input)) {
            return 0;
        }
        // Force la valeur à être un entier
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Nettoie une valeur et s'assure qu'elle est un nombre décimal/flottant (DECIMAL).
     * @param mixed $input La valeur à nettoyer.
     * @return float Le nombre décimal nettoyé (0.0 si non-numérique).
     */
    public static function cleanFloat($input): float {
        if (!is_scalar($input)) {
            return 0.0;
        }
        // Permet les virgules et points comme séparateurs décimaux
        $cleaned = str_replace(',', '.', $input);
        // Force la valeur à être un flottant
        return (float) filter_var($cleaned, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    // Vous pouvez ajouter d'autres méthodes pour les dates, les emails, etc.
}