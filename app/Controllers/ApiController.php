<?php
/**
 * ApiController.php
 * Contrôleur JSON pour les requêtes AJAX internes.
 * Toutes les réponses sont en JSON. Aucune vue HTML n'est chargée.
 */

class ApiController
{
    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET ?controller=Api&action=lookupAccount&prefix=XXXXX
     * Retourne les comptes dont le numéro commence par le préfixe saisi (min 5 chiffres).
     */
    public function lookupAccount(): void
    {
        if (!AuthController::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Non autorisé.'], 401);
        }

        $prefix = Sanitizer::cleanString($_GET['prefix'] ?? '');
        $prefix = preg_replace('/\D/', '', $prefix);

        if (strlen($prefix) < 5) {
            $this->jsonResponse(['error' => 'Saisir au moins 5 chiffres.'], 400);
        }

        try {
            $db = Database::getInstance()->getConnection();
            // FIX: table names → lowercase 'comptes', 'clients'
            $sql = "SELECT
                        c.numero_compte,
                        c.solde,
                        c.est_suspendu,
                        cl.nom,
                        cl.prenom,
                        cl.telephone
                    FROM comptes c
                    JOIN clients cl ON c.client_id = cl.client_id
                    WHERE c.numero_compte LIKE :prefix
                    LIMIT 8";

            $stmt = $db->prepare($sql);
            $search = $prefix . '%';
            $stmt->bindValue(':prefix', $search, PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = [];
            foreach ($results as $row) {
                $formatted[] = [
                    'numero_compte' => $row['numero_compte'],
                    'nom_client'    => htmlspecialchars($row['nom'] . ' ' . $row['prenom']),
                    'telephone'     => htmlspecialchars($row['telephone'] ?? ''),
                    'solde'         => number_format((float)$row['solde'], 2, ',', ' '),
                    'est_suspendu'  => (bool)$row['est_suspendu'],
                ];
            }

            $this->jsonResponse(['results' => $formatted]);

        } catch (\PDOException $e) {
            error_log("ApiController::lookupAccount BDD Error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * GET ?controller=Api&action=getLastTransactions&numero_compte=XXXXX
     * Retourne les 5 dernières transactions d'un compte.
     */
    public function getLastTransactions(): void
    {
        if (!AuthController::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Non autorisé.'], 401);
        }

        $numeroCompte = Sanitizer::cleanString($_GET['numero_compte'] ?? '');
        if (empty($numeroCompte)) {
            $this->jsonResponse(['error' => 'Numéro de compte requis.'], 400);
        }

        try {
            $db = Database::getInstance()->getConnection();
            // FIX: table names → lowercase 'transactions', 'comptes'
            $sql = "SELECT
                        T.type_transaction,
                        T.montant,
                        T.date_transaction,
                        T.reference_externe,
                        CASE
                            WHEN Cs.numero_compte = :num THEN 'Débit'
                            ELSE 'Crédit'
                        END AS sens
                    FROM transactions T
                    LEFT JOIN comptes Cs ON T.compte_source_id = Cs.compte_id
                    LEFT JOIN comptes Cd ON T.compte_destination_id = Cd.compte_id
                    WHERE Cs.numero_compte = :num2 OR Cd.numero_compte = :num3
                    ORDER BY T.date_transaction DESC
                    LIMIT 5";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':num',  $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':num2', $numeroCompte, PDO::PARAM_STR);
            $stmt->bindValue(':num3', $numeroCompte, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = [];
            foreach ($rows as $r) {
                $formatted[] = [
                    'type'      => $r['type_transaction'],
                    'montant'   => number_format((float)$r['montant'], 2, ',', ' '),
                    'date'      => $r['date_transaction'],
                    'reference' => $r['reference_externe'],
                    'sens'      => $r['sens'],
                ];
            }

            $this->jsonResponse(['transactions' => $formatted]);

        } catch (\PDOException $e) {
            error_log("ApiController::getLastTransactions BDD Error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Erreur serveur.'], 500);
        }
    }
}
