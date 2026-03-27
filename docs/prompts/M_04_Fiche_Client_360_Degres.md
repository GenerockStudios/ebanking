# CONTEXTE GLOBAL DU PROJET : E-BANKING / MICROFINANCE

Vous agissez en tant que Développeur Full-Stack PHP Senior Expert.
Votre mission est de coder au **paroxysme de la qualité**, en respectant méticuleusement le design existant et les contraintes techniques imposées pour la soutenance, **sans introduire de framworks lourds** (pas de Bootstrap, pas de dompdf). Tout se fait en pur PHP, CSS natif, et PDO.

## 1. Architecture du Projet (PHP MVC Custom)
Le projet repose sur une architecture MVC artisanale et robuste :
- **Routage Centralisé** : Tout passe par `index.php` qui dispatche vers `ControllerName` et `ActionName` (ex: `?controller=Caisse&action=depot`).
- **Sanitisation** : Les entrées GET/POST sont systématiquement nettoyées via `Sanitizer::cleanString()` ou `Sanitizer::cleanInt()`.
- **Modèles (`app/Models/`)** : Utilisent PDO natif pour les requêtes préparées (`$stmt->bindParam()`). Les modèles encapsulent toute la logique de base de données.
- **Contrôleurs (`app/Controllers/`)** : Gèrent les droits (via `$_SESSION['role_id']`), valident les formulaires, font appel aux Modèles, et passent les données (`$data`) aux Vues.
- **Vues (`app/Views/caisse/` ou `admin/`)** : HTML structuré et stylisé avec un CSS Grid/Flexbox custom. Le design s'inspire de tableaux de bord modernes (cartes avec dégradés subtils, ombres, icônes FontAwesome v6).

## 2. Contrainte Absolue sur la Base de Données (Le Paroxysme)
**ATTENTION : Il est STRICTEMENT INTERDIT de créer de nouvelles tables ou d'altérer la structure des colonnes existantes.** 
L'application doit exploiter l'existant à son *paroxysme* via des jointures complexes, des vues métier, et des interfaces parfaites.
La base `ebanking_db.sql` contient :
1. `clients` : `client_id`, `nom`, `prenom`, `adresse`, `telephone`, `email`, `numero_identite`, `date_creation`.
2. `comptes` : `compte_id`, `numero_compte`, `client_id`, `type_compte_id`, `solde`, `date_ouverture`, `est_actif`, `est_suspendu`.
3. `transactions` : `transaction_id`, `compte_source_id`, `compte_destination_id`, `type_transaction` (DEPOT, RETRAIT, TRANSFERT), `montant`, `date_transaction`, `utilisateur_id`, `statut`, `reference_externe`.
4. `journal_audit` : `log_id`, `date_heure`, `utilisateur_id`, `type_action`, `table_affectee`, `identifiant_element_affecte`, `details`.
5. `historique_soldes` : `historique_id`, `compte_id`, `date_snapshot`, `solde_final` (pour les fins de mois).
6. `documents_kyc` : `document_id`, `client_id`, `type_document`, `reference_fichier`, `date_expiration`, `est_valide`, `valide_par_utilisateur_id`.
7. `sessions_caisse` : `session_id`, `utilisateur_id`, `date_ouverture`, `heure_ouverture`, `heure_fermeture`, `solde_initial_caisse`, `solde_final_systeme`, `solde_final_reel`, `difference`, `est_cloture`.
8. `plafonds_comptes` : `plafond_id`, `compte_id`, `plafond_retrait_journalier`, `plafond_depot_journalier`, `plafond_transfert_mensuel`.
9. `type_comptes` : `type_compte_id`, `nom_type`, `taux_interet` (ex: 0.0150).
10. `utilisateurs` & `roles` : Gèrent les accès Admin (1) ou Caissier (3).

## 3. Le Secret de l'Impression Native (CRITIQUE POUR LE JURY)
Pour toute vue destinée à être imprimée (Reçu, RIB, Rapport comptable), la signature d'impression du navigateur (URL en bas, Date et Numéro de page en haut) **DOIT DISPARAÎTRE COMPLÈTEMENT**. C'est une exigence du jury.
Vous DEVEZ inclure ce snippet CSS exact au sein de ces vues spécifiques :
```html
<style>
/* Reset de la signature navigateur */
@media print {
    @page { margin: 0; size: A4 portrait; }
    body { padding: 1.5cm; margin: 0; background: #fff; color: #000; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    * { box-shadow: none !important; text-shadow: none !important; }
}

/* Style Professionnel du Document Impression */
.document-officiel {
    border: 2px solid #2c3e50;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.header-banque {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px double #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 15px;
}
.titre-document {
    font-size: 24px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.infos-banque { text-align: right; font-size: 12px; color: #555; }
.signature-box { display: flex; justify-content: space-between; margin-top: 50px; }
.signature-box div { border-top: 1px dotted #000; width: 40%; text-align: center; padding-top: 10px; font-style: italic; }
</style>
```

---


# OBJECTIF EXCLUSIF DU PROMPT 4

## Sujet : Fiche Signalétique Client Consolidée (Vue 360°)
**Acteur Principal** : Administrateur (1)
**Focus Imposé** : Création d'un tableau de bord unique par client, fusionnant les requêtes sur `clients`, `comptes` existants de ce client, et liste des `documents_kyc`. UI très riche et impression de la 'Fiche Titulaire' officielle.

### Travail Code Complet Attendu :
1. **Code du Contrôleur (Backend)** : Méthode à ajouter, validation stricte, redirection si échec.
2. **Code de la Vue (Frontend / UI)** : Structure HTML, intégration avec le header/footer existant, classe `.document-officiel`, `<style>` @media print intégré sans marge Chrome.
3. **Code du Modèle (Requêtes BDD)** : Requêtes PDO (`SELECT`, `UPDATE` si applicable) optimisées.

## Plan Intégration et Tests Exigé (Vérifications strictes)
En tant qu'expert, concevez un plan clair :
1. **Intégration Frontend** : La vue doit être accessible depuis `dashboard.php` avec les mêmes icônes FontAwesome (`<i class="fas fa-file-invoice"></i>` par ex.) et des boutons `<a href="..." class="btn-action">...</a>`.
2. **Crash Tests Impression** : Le code doit prévoir un bouton JavaScript `onclick="window.print()"` visible en ligne, mais masqué à l'impression via `.no-print`. L'audit visuel du Ctrl+P ne doit afficher aucune URL ni Date système de Chrome/Edge/Firefox.
3. **Tests Robustesse PDO** : Le Modèle doit obligatoirement encapsuler ses requêtes dans un `try { ... } catch (PDOException $e)` et logger les erreurs avec `error_log()`.
4. **Log Audit Légal** : Chaque fois qu'un PDF métier critique est généré ou consulté, on doit insérer une trace dans `journal_audit` via `$_SESSION['user_id']`.


## Consignes d'Élaboration du Code (Pour atteindre le niveau Expert/Paroxysme)

1. **Validation d'Entrées** : Jamais de requête avec variables directes. Toujours utiliser `$stmt->bindValue()`.
2. **Gestion des Erreurs Utilisateur** : Si l'utilisateur demande l'impression d'un compte inexistant, redirigez-le vers le tableau de bord avec une bannière rouge via la querystring `&error=Compte+introuvable`.
3. **Design System** : Utilisez des balises sémantiques. Un tableau financier (`<table>`) doit être esthétiquement propre : bordures inférieures pour les lignes (`border-bottom: 1px solid #ddd`), surlignages au passage de la souris (`hover`), et alignement numérique à droite pour les montants.
4. **Dates** : Les dates affichées sur les reçus doivent suivre le format français `d/m/Y H:i:s`.
5. **Couleurs de la Marque** : Sauf avis contraire, le bleu roi (`#0056b3`) et le vert validation (`#28a745`) prédominent.
6. **Contrôle d'Accès** : Veillez scrupuleusement à tester le `$_SESSION['role_id']`. Un caissier (3) ne doit pas voir les éditions critiques (1).
7. Mettez en évidence dans votre implémentation comment l'acteur (Admin ou Caisse) va initier l'action.
8. La vue globale (`layout/header.php`) est déjà incluse. N'ouvrez pas les balises `<html>` ou `<body>`, contentez-vous du contenu centré et du style interne pour l'impression.
9. Proposez toujours des messages de succès clairs et un chemin de retour utilisateur (breadcrumb ou simple lien "Retour à l'accueil").
10. Commentez votre code PHP abondamment, à destination des correcteurs du jury ("// Extraction optimisée des soldes : évite la boucle N+1").
