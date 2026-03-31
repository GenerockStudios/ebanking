<?php
/**
 * simulation.php - Simulateur d'Épargne sur Capitalisation
 * Outil interactif pour les agents de caisse.
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<div class="simulation-view container">
    <!-- Section Titre (Masquée à l'impression si besoin, mais ici on veut garder le titre du doc) -->
    <div class="no-print" style="margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-retour">
            <i class="fas fa-arrow-left"></i> Retour au Tableau de Bord
        </a>
    </div>

    <!-- DOCUMENT OFFICIEL (Partie Imprimable) -->
    <div class="document-officiel">
        <div class="header-banque">
            <div class="titre-document">
                <i class="fas fa-chart-line no-print"></i> Projection d'Épargne
            </div>
            <div class="infos-banque">
                <strong>BANQUE DE MICROFINANCE PLUS</strong><br>
                Service Clientèle / Simulation Financière<br>
                Date de simulation : <?= date('d/m/Y H:i:s') ?>
            </div>
        </div>

        <section class="simulation-params no-print">
            <h3 class="section-title"><i class="fas fa-edit"></i> Paramètres de la Simulation</h3>
            <div class="grid-params">
                <div class="form-group">
                    <label>Type de Compte</label>
                    <select id="type_compte" class="form-control">
                        <option value="0" data-rate="0">Choisir un produit...</option>
                        <?php foreach ($data['account_types'] as $type): ?>
                            <option value="<?= $type['type_compte_id'] ?>" data-rate="<?= $type['taux_interet'] ?>">
                                <?= htmlspecialchars($type['nom_type']) ?> (<?= number_format($type['taux_interet'] * 100, 2) ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Placement Initial (FCFA)</label>
                    <input type="tel" inputmode="numeric" id="capital_initial" class="form-control" placeholder="Ex: 500000" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label>Versement Mensuel (FCFA)</label>
                    <input type="tel" inputmode="numeric" id="versement_mensuel" class="form-control" placeholder="Ex: 50000" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label>Durée (Mois)</label>
                    <input type="tel" inputmode="numeric" id="duree_mois" class="form-control" placeholder="Nombre de mois" min="1" max="360" value="12">
                </div>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn-action primary" onclick="calculerSimulation()">
                    <i class="fas fa-calculator"></i> Calculer la Projection
                </button>
                <button type="button" class="btn-action" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer Simulation
                </button>
            </div>
        </section>

        <!-- Résultats de la Simulation -->
        <div id="results-container" style="display: none;">
            <div class="synthese-box">
                <div class="synthese-item">
                    <span class="label">Total Versé</span>
                    <span class="value" id="res-total-verse">0 FCFA</span>
                </div>
                <div class="synthese-item">
                    <span class="label">Intérêts Générés</span>
                    <span class="value" id="res-total-interets">0 FCFA</span>
                </div>
                <div class="synthese-item highlight">
                    <span class="label">Capital Final Estimé</span>
                    <span class="value" id="res-capital-final">0 FCFA</span>
                </div>
            </div>

            <h4 class="table-title">Échéancier Prévisionnel de Capitalisation</h4>
            <div class="table-scroll">
                <table class="table-simulation" id="table-projection">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Solde Début</th>
                            <th>Versement</th>
                            <th>Intérêts Mensuels</th>
                            <th>Solde Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamique JS -->
                    </tbody>
                </table>
            </div>

            <p class="mention-legale">
                * Cette simulation est fournie à titre indicatif sur la base d'une capitalisation mensuelle des intérêts. 
                Les taux d'intérêt sont susceptibles d'évoluer selon les conditions générales de la banque.
            </p>

            <div class="signature-box print-only">
                <div>Signature du Conseiller / Caissier</div>
                <div>Signature du Client</div>
            </div>
        </div>

        <div id="no-results" class="no-print" style="padding: 40px; text-align: center; color: #888;">
            <i class="fas fa-info-circle fa-2x"></i><br><br>
            Saisissez les paramètres ci-dessus pour générer une simulation.
        </div>
    </div>
</div>

<style>
/* Style UI Moderne (Le reset print est géré dans responsive-core.css) */
.simulation-view { padding: 30px; }
.document-officiel {
    background: #fff;
    border: 1px solid #e0e6ed;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.header-banque {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px double #2c3e50;
    margin-bottom: 30px;
    padding-bottom: 20px;
}
.titre-document {
    font-size: 26px;
    font-weight: 800;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.infos-banque { text-align: right; font-size: 13px; color: #555; line-height: 1.5; }

.section-title { font-size: 18px; color: #0056b3; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }

.grid-params {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    background: #f8fbff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e1e8f0;
}
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #344767; font-size: 14px; }
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d2d6da;
    border-radius: 6px;
    font-size: 14px;
    color: #495057;
    transition: border-color 0.2s;
}
.form-control:focus { border-color: #0056b3; outline: none; box-shadow: 0 0 0 2px rgba(0,86,179,0.1); }

.btn-action {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}
.btn-action.primary { background: #0056b3; color: #fff; }
.btn-action.primary:hover { background: #004494; transform: translateY(-1px); }
.btn-action:not(.primary) { background: #e9ecef; color: #495057; }
.btn-action:not(.primary):hover { background: #dee2e6; }

.synthese-box {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    gap: 15px;
}
.synthese-item {
    flex: 1;
    background: #fff;
    border: 1px solid #e0e6ed;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}
.synthese-item .label { display: block; font-size: 12px; color: #8392ab; text-transform: uppercase; margin-bottom: 5px; }
.synthese-item .value { font-size: 18px; font-weight: 700; color: #252f40; }
.synthese-item.highlight { background: #0056b3; border-color: #0056b3; }
.synthese-item.highlight .label { color: rgba(255,255,255,0.8); }
.synthese-item.highlight .value { color: #fff; font-size: 22px; }

.table-title { font-size: 16px; margin: 30px 0 15px; color: #2c3e50; font-weight: 700; border-left: 5px solid #0056b3; padding-left: 15px; }
.table-simulation { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fff; }
.table-simulation th { background: #f8f9fa; color: #344767; font-weight: 700; font-size: 13px; text-transform: uppercase; padding: 12px; border: 1px solid #dee2e6; }
.table-simulation td { padding: 10px 12px; border: 1px solid #dee2e6; font-size: 14px; text-align: right; }
.table-simulation td:first-child { text-align: center; font-weight: 600; color: #555; }
.table-simulation tr:nth-child(even) { background-color: #fcfcfc; }
.table-simulation tr:hover { background-color: #f5f9ff; }

.mention-legale { font-size: 11px; color: #888; font-style: italic; margin-top: 20px; line-height: 1.4; border-top: 1px solid #eee; padding-top: 15px; }

.signature-box { display: none; justify-content: space-between; margin-top: 60px; }
.signature-box div { border-top: 1px dotted #000; width: 40%; text-align: center; padding-top: 10px; font-style: italic; font-size: 12px; height: 80px; }

.btn-retour { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #0056b3; font-weight: 600; transition: color 0.2s; }
.btn-retour:hover { color: #004494; }
</style>

<script>
function formatCurrency(val) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(val) + " FCFA";
}

function calculerSimulation() {
    const select      = document.getElementById('type_compte');
    const rateAnnual  = parseFloat(select.options[select.selectedIndex].getAttribute('data-rate'));
    const capInitial  = parseFloat(document.getElementById('capital_initial').value) || 0;
    const versMensuel = parseFloat(document.getElementById('versement_mensuel').value) || 0;
    const duree       = parseInt(document.getElementById('duree_mois').value) || 1;

    if (rateAnnual === 0 && capInitial === 0 && versMensuel === 0) {
        alert("Veuillez saisir des valeurs de simulation valides.");
        return;
    }

    const rateMonthly = rateAnnual / 12;
    const tbody       = document.querySelector('#table-projection tbody');
    tbody.innerHTML   = "";

    let soldeActuel  = capInitial;
    let totalVersement = capInitial;
    let totalInterets  = 0;

    for (let i = 1; i <= duree; i++) {
        let soldeDebut = soldeActuel;
        
        // Calcul intérêts mensuels sur le solde de début
        let interets = soldeDebut * rateMonthly;
        totalInterets += interets;
        
        // Ajout du versement mensuel
        soldeActuel = soldeDebut + interets + versMensuel;
        if (i > 0) totalVersement += versMensuel;

        // Limiter l'affichage à 60 lignes pour éviter un PDF trop long, mais calculer tout
        if (i <= 60 || i === duree) {
            let row = `<tr>
                <td>${i}</td>
                <td>${formatCurrency(soldeDebut)}</td>
                <td>${formatCurrency(i === 1 ? capInitial + versMensuel : versMensuel)}</td>
                <td style="color: #28a745;">+ ${formatCurrency(interets)}</td>
                <td style="font-weight: 700;">${formatCurrency(soldeActuel)}</td>
            </tr>`;
            
            if (i === 61 && duree > 61) {
                row = `<tr><td colspan="5" style="text-align:center; padding: 20px; background: #fafafa;">... Suite des calculs sur ${duree - 61} mois suivants ...</td></tr>`;
            }
            
            tbody.insertAdjacentHTML('beforeend', row);
        }
    }

    // Mise à jour synthèse
    document.getElementById('res-total-verse').innerText   = formatCurrency(totalVersement);
    document.getElementById('res-total-interets').innerText = formatCurrency(totalInterets);
    document.getElementById('res-capital-final').innerText  = formatCurrency(soldeActuel);

    // Affichage
    document.getElementById('no-results').style.display = 'none';
    document.getElementById('results-container').style.display = 'block';
}

// Initialisation si valeurs par défaut présentes
document.addEventListener('DOMContentLoaded', () => {
    // Peut-être lancer un calcul par défaut ? 
    // calculerSimulation();
});
</script>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
