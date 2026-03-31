<?php
/**
 * dashboard.php
 * Tableau de bord client (Espace Privé en Ligne)
 * Refonte intégrale Sprint 1 — Mobile-First
 */

$numeroCompte = $_SESSION['client_numero_compte'] ?? 'N/A';
$solde = $data['solde'] ?? 'N/A';
$transactions = $data['transactions'] ?? [];
$userName = $data['client_name'] ?? 'Client';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?? APP_NAME . " - Espace Client" ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <!-- Typographie & CSS de base -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ebanking/src/css/responsive-core.css">

    <style>
        body { background-color: #f4f7fb; color: #1a1f36; padding-bottom: 80px; } /* padding for bottom nav on mobile */
        
        /* ── Header Top (Desktop view) ── */
        .client-header {
            background: linear-gradient(135deg, #042e5a, #0a4a8a);
            color: white;
            padding: max(20px, env(safe-area-inset-top)) max(24px, env(safe-area-inset-right)) 20px max(24px, env(safe-area-inset-left));
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 12px rgba(4,46,90,.15);
            position: sticky; top: 0; z-index: 100;
        }
        .header__brand { font-size: 1.25rem; font-weight: 700; letter-spacing: 1px; }
        .header__user { font-size: 0.9rem; opacity: .9; }
        .nav-desktop { display: flex; gap: 20px; align-items: center; }
        .nav-desktop a { color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem; background: rgba(255,255,255,.1); padding: 8px 16px; border-radius: 8px; transition: background .2s; }
        .nav-desktop a:hover { background: rgba(255,255,255,.2); }

        /* ── Main Content Container ── */
        .client-main {
            padding: 24px max(24px, env(safe-area-inset-right)) 40px max(24px, env(safe-area-inset-left));
            max-width: 1000px; margin: 0 auto;
        }

        /* ── Widget Solde ── */
        .solde-widget {
            background: #ffffff;
            border-radius: 20px;
            padding: clamp(24px, 5vw, 40px);
            box-shadow: 0 4px 24px rgba(0,0,0,.04);
            margin-bottom: 32px;
            border: 1px solid rgba(0,0,0,.03);
            background-image: radial-gradient(circle at top right, rgba(40,167,69,.05), transparent 400px);
        }
        .solde-widget__label { color: #8792a2; font-size: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .solde-widget__amount { font-size: clamp(2rem, 6vw, 3.5rem); font-weight: 700; color: #042e5a; display: flex; align-items: baseline; gap: 8px; }
        .solde-widget__currency { font-size: clamp(1rem, 3vw, 1.5rem); color: #8792a2; font-weight: 500; }
        .solde-widget__meta { display: inline-block; margin-top: 16px; padding: 6px 12px; background: #f0f4f8; border-radius: 6px; font-size: 0.85rem; font-family: monospace; color: #555; }

        /* ── Transactions Section ── */
        .section-title { font-size: var(--text-h3); margin-bottom: 16px; font-weight: 700; color: #1a1f36; }
        .txn-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.03); }

        /* Custom Table Styling overrides */
        .data-table th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: .5px; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.9rem; }
        .flux-debit { color: #dc3545; font-weight: 600; }
        .flux-credit { color: #28a745; font-weight: 600; }
        .amount-col { text-align: right; font-weight: 700; font-family: monospace; font-size: 1rem !important; }

        /* ── Bottom App Bar (Mobile Only) ── */
        .bottom-nav { display: none; }
        
        @media (max-width: 768px) {
            .nav-desktop { display: none; } /* On cache la nav du haut */
            .client-header { flex-direction: column; align-items: flex-start; gap: 4px; padding: 16px; }
            
            /* Bottom App Bar active */
            .bottom-nav {
                display: flex;
                position: fixed; bottom: 0; left: 0; right: 0;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
                box-shadow: 0 -4px 16px rgba(0,0,0,.05);
                padding-bottom: env(safe-area-inset-bottom);
                z-index: 1000;
            }
            .bottom-nav__item {
                flex: 1; padding: 12px;
                display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
                color: #64748b; text-decoration: none; font-size: 0.7rem; font-weight: 600;
                transition: color .2s;
            }
            .bottom-nav__item svg { width: 24px; height: 24px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
            .bottom-nav__item.active { color: #042e5a; }
            .bottom-nav__item:hover { background: #f8fafc; }
        }
    </style>
</head>
<body>

    <!-- Top Header -->
    <header class="client-header">
        <div>
            <div class="header__brand">L'Oasis &middot; Client</div>
            <div class="header__user">Bonjour, <?= htmlspecialchars($userName) ?></div>
        </div>
        <nav class="nav-desktop">
            <a href="javascript:window.location.reload();">Actualiser</a>
            <a href="<?= BASE_URL ?>?controller=ClientFront&action=logout" style="background:#dc3545; color:#fff;">Déconnexion</a>
        </nav>
    </header>

    <!-- Main View -->
    <main class="client-main">
        
        <!-- Widget Solde Principal -->
        <section class="solde-widget">
            <div class="solde-widget__label">Votre solde disponible</div>
            <div class="solde-widget__amount">
                <?php if (is_numeric($solde)): ?>
                    <?= number_format($solde, 2, ',', ' ') ?>
                    <span class="solde-widget__currency">FCFA</span>
                <?php else: ?>
                    Indisponible
                <?php endif; ?>
            </div>
            <div class="solde-widget__meta">CPT: <?= htmlspecialchars($numeroCompte) ?></div>
        </section>

        <!-- Historique Transactions -->
        <section>
            <h2 class="section-title">Opérations récentes</h2>
            
            <div class="txn-card">
                <?php if (empty($transactions)): ?>
                    <p style="text-align:center; color:#8792a2; padding:30px 0;">Aucune transaction récente.<br>Votre historique apparaîtra ici.</p>
                <?php else: ?>
                    <!-- Utilisation du pattern Table Scroll Horizontal (Mission A) -->
                    <div class="table-scroll-wrap">
                        <table class="data-table" style="min-width: 600px;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Réf. Opération</th>
                                    <th class="amount-col">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?= date('d M Y', strtotime($txn['date_transaction'])) ?></div>
                                            <div style="font-size:0.75rem;color:#8792a2;"><?= date('H:i', strtotime($txn['date_transaction'])) ?></div>
                                        </td>
                                        <td>
                                            <span style="font-weight:500;"><?= htmlspecialchars($txn['type_transaction']) ?></span><br>
                                            <span style="font-size:0.75rem;color:#8792a2;"><?= htmlspecialchars($txn['details'] ?? '') ?></span>
                                        </td>
                                        <td style="font-family:monospace;"><?= htmlspecialchars($txn['reference_externe']) ?></td>
                                        
                                        <?php $isDebit = ($txn['sens_flux'] === 'Débit'); ?>
                                        <td class="amount-col <?= $isDebit ? 'flux-debit' : 'flux-credit' ?>">
                                            <?= $isDebit ? '- ' : '+ ' ?>
                                            <?= number_format($txn['montant'], 0, '', ' ') ?> FCFA
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
    </main>

    <!-- Bottom Navigation Bar pour Smartphones -->
    <nav class="bottom-nav">
        <a href="#" class="bottom-nav__item active">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            Comptes
        </a>
        <a href="javascript:window.location.reload();" class="bottom-nav__item">
            <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Actualiser
        </a>
        <a href="<?= BASE_URL ?>?controller=ClientFront&action=logout" class="bottom-nav__item" style="color:#dc3545;">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Quitter
        </a>
    </nav>

    <!-- Injection du Toast System -->
    <div id="toast-container" style="bottom: calc(85px + env(safe-area-inset-bottom));"></div>

</body>
</html>