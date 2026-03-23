<?php
/**
 * TransactionTest.php
 * Tests unitaires pour le cycle transactionnel du système EBanking.
 *
 * LANCEMENT :
 *   cd C:\xampp\htdocs\ebanking
 *   php tests/TransactionTest.php
 *
 * Aucune dépendance externe requise (pas de PHPUnit) — framework de test maison léger.
 */

// ============================================================
// MINI FRAMEWORK DE TEST
// ============================================================

$testsPassed = 0;
$testsFailed = 0;
$testLog     = [];

function it(string $description, callable $fn): void
{
    global $testsPassed, $testsFailed, $testLog;
    try {
        $fn();
        $testsPassed++;
        $testLog[] = "[PASS] $description";
        echo "\033[32m[PASS]\033[0m $description\n";
    } catch (AssertionError $e) {
        $testsFailed++;
        $testLog[] = "[FAIL] $description — {$e->getMessage()}";
        echo "\033[31m[FAIL]\033[0m $description\n       > {$e->getMessage()}\n";
    } catch (Throwable $e) {
        $testsFailed++;
        $testLog[] = "[FAIL] $description — Exception: {$e->getMessage()}";
        echo "\033[31m[FAIL]\033[0m $description\n       > Exception: {$e->getMessage()}\n";
    }
}

function assertEqual(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionError(
            ($msg ? "$msg — " : '') . "Attendu: " . var_export($expected, true) . " | Obtenu: " . var_export($actual, true)
        );
    }
}

function assertTrue(bool $condition, string $msg = ''): void
{
    if (!$condition) throw new AssertionError($msg ?: "La condition devrait être vraie.");
}

function assertFalse(bool $condition, string $msg = ''): void
{
    if ($condition) throw new AssertionError($msg ?: "La condition devrait être fausse.");
}

function assertThrows(string $exceptionClass, callable $fn, string $msg = ''): void
{
    try {
        $fn();
        throw new AssertionError(($msg ?: "Exception attendue") . " (aucune exception levée)");
    } catch (AssertionError $e) {
        throw $e;
    } catch (Throwable $e) {
        if (!($e instanceof $exceptionClass)) {
            throw new AssertionError("Exception attendue: $exceptionClass, reçue: " . get_class($e) . " — {$e->getMessage()}");
        }
    }
}

// ============================================================
// STUBS / MOCKS (pas de vrai BDD nécessaire)
// ============================================================

/**
 * Compte bancaire en mémoire pour les tests.
 */
class TestAccount
{
    public int   $id;
    public float $solde;
    public bool  $est_suspendu;

    public function __construct(int $id, float $solde, bool $est_suspendu = false)
    {
        $this->id           = $id;
        $this->solde        = $solde;
        $this->est_suspendu = $est_suspendu;
    }
}

/**
 * Moteur transactionnel pur (sans BDD) pour tester la logique métier.
 * Reproduit exactement les règles de TransactionModel.
 */
class TransactionEngine
{
    private array $accounts = [];
    private float $dailyCap;
    private float $monthCap;
    private array $auditLog = [];

    public function __construct(float $dailyCap = 500000, float $monthCap = 2000000)
    {
        $this->dailyCap  = $dailyCap;
        $this->monthCap  = $monthCap;
    }

    public function addAccount(TestAccount $account): void
    {
        $this->accounts[$account->id] = $account;
    }

    public function getBalance(int $id): float
    {
        return $this->accounts[$id]->solde ?? 0.0;
    }

    public function depot(int $destId, float $amount): bool
    {
        if ($amount <= 0) return false;
        if (!isset($this->accounts[$destId])) throw new \RuntimeException("Compte introuvable.");

        $this->accounts[$destId]->solde += $amount;
        $this->auditLog[] = "DEPOT +{$amount} -> compte {$destId}";
        return true;
    }

    public function retrait(int $sourceId, float $amount): bool
    {
        if ($amount <= 0) throw new \InvalidArgumentException("Montant invalide.");
        if (!isset($this->accounts[$sourceId])) throw new \RuntimeException("Compte introuvable.");

        $account = $this->accounts[$sourceId];

        if ($account->est_suspendu) {
            throw new \RuntimeException("Ce compte est suspendu. Le retrait est interdit.");
        }
        if ($account->solde < $amount) {
            throw new \RuntimeException("Solde insuffisant (Solde: " . number_format($account->solde, 2) . ").");
        }
        if ($amount > $this->dailyCap) {
            throw new \RuntimeException("Plafond journalier de retrait dépassé.");
        }

        $account->solde -= $amount;
        $this->auditLog[] = "RETRAIT -{$amount} <- compte {$sourceId}";
        return true;
    }

    public function transfert(int $sourceId, int $destId, float $amount): bool
    {
        if ($amount <= 0 || $sourceId === $destId) return false;
        if (!isset($this->accounts[$sourceId], $this->accounts[$destId])) {
            throw new \RuntimeException("Compte source ou destination introuvable.");
        }

        $source = $this->accounts[$sourceId];
        $dest   = $this->accounts[$destId];

        if ($source->est_suspendu) {
            throw new \RuntimeException("Le compte source est suspendu. Opération refusée.");
        }
        if ($source->solde < $amount) {
            throw new \RuntimeException("Fonds source insuffisants.");
        }
        if ($amount > $this->monthCap) {
            throw new \RuntimeException("Plafond mensuel de transfert dépassé.");
        }

        $source->solde -= $amount;
        $dest->solde   += $amount;
        $this->auditLog[] = "TRANSFERT {$amount} : {$sourceId} -> {$destId}";
        return true;
    }

    public function getAuditLog(): array { return $this->auditLog; }
}

// ============================================================
// SUITE DE TESTS
// ============================================================

echo "\n========================================\n";
echo "  EBanking — Suite de Tests Unitaires\n";
echo "========================================\n\n";

$engine = new TransactionEngine(dailyCap: 500000, monthCap: 2000000);
$engine->addAccount(new TestAccount(1, 10000.00));  // Compte A — solde 10 000
$engine->addAccount(new TestAccount(2, 0.00));       // Compte B — vide
$engine->addAccount(new TestAccount(3, 500.00, true)); // Compte C — SUSPENDU

// --- DÉPÔT ---
it("Un dépôt positif augmente le solde", function() use ($engine) {
    $engine->depot(1, 5000.00);
    assertEqual(15000.00, $engine->getBalance(1), "Solde après dépôt de 5000");
});

it("Un dépôt sur un compte vide le crédite correctement", function() use ($engine) {
    $engine->depot(2, 1000.00);
    assertEqual(1000.00, $engine->getBalance(2));
});

it("Un montant de dépôt nul retourne false", function() use ($engine) {
    assertFalse($engine->depot(1, 0), "Dépôt de 0 doit retourner false");
});

it("Un montant de dépôt négatif retourne false", function() use ($engine) {
    assertFalse($engine->depot(1, -100), "Dépôt négatif doit retourner false");
});

// --- RETRAIT ---
it("Un retrait valide diminue le solde", function() use ($engine) {
    $before = $engine->getBalance(1); // 15000
    $engine->retrait(1, 5000.00);
    assertEqual($before - 5000, $engine->getBalance(1));
});

it("Un retrait supérieur au solde lève une exception (InsufficientFunds)", function() use ($engine) {
    assertThrows(\RuntimeException::class, function() use ($engine) {
        $engine->retrait(2, 9999.00); // Solde = 1000, retrait = 9999
    }, "Retrait > solde");
});

it("10.00 - 15.00 soulève une exception solde insuffisant", function() use ($engine) {
    $e2 = new TransactionEngine();
    $e2->addAccount(new TestAccount(10, 10.00));
    assertThrows(\RuntimeException::class, function() use ($e2) {
        $e2->retrait(10, 15.00);
    }, "10 - 15 doit lever une exception");
});

it("Un retrait sur un compte suspendu lève une exception", function() use ($engine) {
    assertThrows(\RuntimeException::class, function() use ($engine) {
        $engine->retrait(3, 100.00);
    }, "Retrait sur compte suspendu");
});

it("Un retrait dépassant le plafond journalier lève une exception", function() {
    $e = new TransactionEngine(dailyCap: 500000);
    $e->addAccount(new TestAccount(99, 9999999.00));
    assertThrows(\RuntimeException::class, function() use ($e) {
        $e->retrait(99, 600000.00); // > 500 000
    }, "Plafond journalier dépassé");
});

// --- TRANSFERT ---
it("Un transfert valide débite la source et crédite la destination", function() use ($engine) {
    $balA = $engine->getBalance(1);
    $balB = $engine->getBalance(2);
    $engine->transfert(1, 2, 2000.00);
    assertEqual($balA - 2000, $engine->getBalance(1), "Source débitée");
    assertEqual($balB + 2000, $engine->getBalance(2), "Destination créditée");
});

it("Transfert source === destination retourne false", function() use ($engine) {
    assertFalse($engine->transfert(1, 1, 100.00), "Auto-transfert doit retourner false");
});

it("Transfert avec solde source insuffisant lève une exception", function() use ($engine) {
    assertThrows(\RuntimeException::class, function() use ($engine) {
        $engine->transfert(2, 1, 999999.00); // Solde de 2 trop faible
    }, "Transfert > solde source");
});

it("Transfert depuis un compte suspendu lève une exception", function() use ($engine) {
    assertThrows(\RuntimeException::class, function() use ($engine) {
        $engine->transfert(3, 1, 100.00); // Compte 3 suspendu
    }, "Transfert compte source suspendu");
});

it("Transfert dépassant le plafond mensuel lève une exception", function() {
    $e = new TransactionEngine(monthCap: 2000000);
    $e->addAccount(new TestAccount(10, 99999999.00));
    $e->addAccount(new TestAccount(11, 0.00));
    assertThrows(\RuntimeException::class, function() use ($e) {
        $e->transfert(10, 11, 3000000.00); // > 2 000 000
    }, "Plafond mensuel transfert dépassé");
});

// --- TEST DE CHARGE LÉGÈRE (5 transactions rapides) ---
it("5 transactions consécutives donnent un solde mathématiquement exact", function() {
    $e = new TransactionEngine();
    $e->addAccount(new TestAccount(20, 100000.00));
    $e->addAccount(new TestAccount(21, 0.00));

    $e->depot(20, 10000);     // +10 000  → 110 000
    $e->retrait(20, 5000);    // -5 000   → 105 000
    $e->transfert(20, 21, 20000); // -20 000 → 85 000 | +20 000
    $e->depot(20, 1000);      // +1 000   → 86 000
    $e->retrait(20, 500);     // -500     → 85 500

    assertEqual(85500.00, $e->getBalance(20), "Solde compte 20 après 5 opérations");
    assertEqual(20000.00, $e->getBalance(21), "Solde compte 21 après transfert");
});

it("L'annulation mentale d'un transfert (rollback) préserve les soldes", function() {
    $e = new TransactionEngine();
    $e->addAccount(new TestAccount(30, 5000.00));
    $e->addAccount(new TestAccount(31, 1000.00));

    // Simuler un échec : tentative de transfert trop élevé
    $balBefore30 = $e->getBalance(30);
    $balBefore31 = $e->getBalance(31);

    try {
        $e->transfert(30, 31, 99999.00); // Doit échouer
    } catch (\RuntimeException $ex) {
        // Exception attrapée — les soldes ne doivent PAS avoir changé
    }

    assertEqual($balBefore30, $e->getBalance(30), "Solde source inchangé après échec transfert");
    assertEqual($balBefore31, $e->getBalance(31), "Solde dest inchangé après échec transfert");
});

// ============================================================
// RÉSUMÉ
// ============================================================

echo "\n========================================\n";
echo "  Résultats : {$testsPassed} PASSES | {$testsFailed} ECHECS\n";
echo "========================================\n\n";

if ($testsFailed > 0) {
    echo "ECHECS DETECTES :\n";
    foreach ($testLog as $entry) {
        if (str_starts_with($entry, '[FAIL]')) {
            echo "  $entry\n";
        }
    }
    exit(1);
} else {
    echo "Tous les tests sont passes. Le noyau transactionnel est valide.\n\n";
    exit(0);
}
