# PROMPT ARCHITECTE EXPERT : AUDIT ET PLANIFICATION RESPONSIVE DU PROJET E-BANKING

Tu vas agir en tant qu'Expert Architecte Frontend, Spécialiste UI/UX et Maître en Intégration Web (Responsive Design, Mobile-First, CSS3 avancé, Flexbox/Grid).  
Ton objectif est de réaliser un audit complet et de définir un plan d'action d'intégration responsive exhaustif pour mon projet "ebanking".  

Le projet est développé en PHP pur (MVC) et l'ensemble des vues se trouve de manière isolée dans le répertoire local `C:\xampp\htdocs\ebanking\app\Views`.  
Ce système e-banking possède plusieurs interfaces destinées à différents types d'utilisateurs. Actuellement, je veux m'assurer que toutes ces vues soient parfaitement utilisables et esthétiques sur tous les supports (Smartphones, Tablettes, Écrans d'ordinateur portables et affichages de bureau de grande taille).

Voici la cartographie exacte des 31 fichiers et dossiers que tu vas devoir analyser et refondre virtuellement dans ton plan :

### 1. Dossier `layout` (Structure Globale de l'Application)
- `header.php` : Doit gérer la navigation, intégrer un menu hamburger ou "Off-canvas" sur mobile, un menu latéral ou horizontal sur desktop.
- `footer.php` : Doit s'empiler correctement sur de petits écrans, avec les mentions légales bien lisibles.

### 2. Dossier `auth` & `client_front` (Vues Publiques et Connexion)
- `auth/login.php` : Portail de connexion centralisé pour le staff. Doit être centré de manière esthétique, lisible sur mobile sans déformation.
- `client_front/login.php` : Portail client. Même contrainte que le portail staff, avec éventuellement une illustration qui disparaît sur mobile.
- `client_front/dashboard.php` : Le tableau de bord du client final. L'interface la plus critique pour l'expérience client (UX irréprochable exigée sur smartphone).

### 3. Dossier `caisse` (Opérations Tactiles et Courantes)
L'interface caissier doit être considérée comme un outil de terrain "Touch-Ready", manipulable rapidement sur tablette tactile ou écran de caisse.
- `dashboard.php` : Menu principal avec grandes "Cards" d'accès rapide.
- `depot.php` : Formulaire de dépôt d'espèces. Les inputs doivent être très larges et facilement sélectionnables.
- `retrait.php` : Formulaire de retrait, mêmes exigences d'ergonomie et boutons XL.
- `transfert.php` : Transfert d'un compte à un autre, nécessité de sélecteurs clairs (dropdown) optimisés pour le touch UI.
- `cloture.php` & `simulation.php` : Processus de fin de journée et simulateurs. Nécessitent un clavier numérique virtuel ou en tout cas une forte adaptabilité au clavier natif du téléphone.
- `pv_cloture.php`, `recepisse.php`, `recu.php`, `releve.php` : Ces fichiers génèrent des documents formels. Il faut gérer leur apparence sur l'écran sans détruire la vue d'impression.

### 4. Dossier `admin` (Espace de Gestion - Haute Densité Visuelle)
L'interface administrateur contient massivement des tableaux de données et de la configuration lourde.
- `analytics.php` : Graphiques (Chart.js ou similaire) qui doivent redimensionner (resize) automatiquement sur mobile.
- `audit_kyc.php`, `audit_logs.php`, `audit_report.php` : Tableaux de logs longs et complexes. Stratégie de "scroll horizontal avec indication visuelle" indispensable sur smartphone.
- `contrat_plafond.php`, `edit_plafond.php`, `manage_plafonds.php` : Formulaires complexes avec configurations de limites. Nécessite une grille Bootstrap/CSS Grid pour aligner les champs sur bureau, et les empiler sur mobile.
- `fiche_client.php`, `manage_clients.php`, `manage_users.php` : Gestion des profils avec photos ou métadonnées. L'agencement "Carte Profil" s'impose avec un passage "avatar au-dessus du texte" sur mobile.
- `snapshot_bilan.php` : Bilan financier massif. Obligation de figer la première colonne de gauche lors du défilement sur les petits écrans.

### 5. Dossiers `client` et `comptes` (Interfaces Spécifiques)
- `client/nouveau_client.php` : Formulaire de création multi-étapes ou très long. Prévoir un système de "Wizard" responsive ou un découpage horizontal/vertical fluide.
- `comptes/rib.php` : L'affichage du RIB doit contenir un bouton de copie facile d'accès (finger-friendly) et un agencement carte de crédit / carte bancaire.

### 6. Dossier `rapports` (Documents de Synthèse)
- `cloture_journee.php`, `releve_mensuel.php`, `transactions.php` : Pages affichant des synthèses lourdes orientées impression (`@media print`).

---

## MISSION A : AUDIT STRATÉGIQUE (UX ET UI)
Tu dois commencer ta réponse par un diagnostic formel de la situation et tes recommandations d'expert :
1. **Stratégie pour les Data Tables :** Décris très précisément 3 stratégies différentes pour que les pages de logs (`audit_logs.php`) et de bilans (`snapshot_bilan.php`) restent lisibles sur un écran de 320px de large (ex: Stacking en mode Card, Scroll Horizontal, ou Masquage ciblé de colonnes non essentielles).
2. **Standardisation des zones tactiles :** Énonce les normes absolues pour les vues du dossier `caisse`. (Taille des zones de clic en pixels, spacement entre les éléments cliquables pour éviter les erreurs de saisie).
3. **Hiérarchie de l'Information (Mobile) :** Comment vas-tu réorganiser le `header.php` ? Par exemple : la barre d'état admin sera-t-elle masquée derrière une roue crantée ? Le menu de navigation principal deviendra-t-il une "Bottom App Bar" pour les clients ou un "Sidebar Drawer" pour le staff ?

## MISSION B : SPÉCIFICATIONS TECHNIQUES D'INTÉGRATION
Fournis une nomenclature claire, un guide technique qui sera suivi à la lettre pour chaque `view` :
1. **Les Breakpoints (Media Queries) :** Défini la liste des variables que je mettrais dans mon CSS (`--breakpoint-sm`, `--breakpoint-md`, etc.) en approche Mobile-First, en justifiant les tailles retenues pour l'e-banking.
2. **Typographie "Fluid Control" :** Intègre une explication mathématique/CSS sur l'utilisation de `clamp()` pour que la taille des éléments (Titre H1 du Dashboard Caisse jusqu'aux paragraphes des Reçus) s'adapte sans créer 50 règles de media queries différentes.
3. **Structure des Formulaires :** Pour les formulaires (ex: `nouveau_client.php`), impose des règles de design CSS claires : labels flottants, disposition verticale forcée sur mobile, bordures d'inputs réactives au focus, et "Safe area" de 16px sur les bords du téléphone.
4. **Les Notifications (UI Feedback) :** Le design responsive pour l'e-banking implique que les messages "Succès" ou "Erreur" ne poussent pas tout le contenu vers le bas sur mobile. Propose une architecture "Toast" flottante.

## MISSION C : DIFFÉRENCIATION PRINT VS SCREEN
C'est capital. Le projet possède de nombreuses pages qui servent de reçus imprimables (`pv_cloture.php`, `recu.php`, `releve.php`, `audit_report.php`).
- Rédige une section entière de ton plan pour spécifier comment nous allons protéger nos vues lors de l'appel de `window.print()`.
- Détaille les classes CSS d'exclusion (ex: `.no-print`) que je devrai injecter dans les vues.
- Explique comment annuler l'empilement (stacking) responsive sur le papier, car sur du A4 imprimé, nous voulons garder nos colonnes comme sur du format "Desktop" !

## MISSION D : GÉNÉRATION DU CODE DE FONDATION CSS
Je veux que tu produises, purement et simplement, un bloc de code nommé `responsive-core.css` dans ta réponse. Ce fichier doit contenir environ 40 à 60 lignes et inclure :
- Le point d'entrée avec reset ciblé.
- L'initialisation des variables (`:root`).
- Un système de grille léger en pure CSS Grid/Flexbox si Bootstrap n'est pas utilisé.
- Toutes les classes utilitaires majeures qui corrigeront 80% des problèmes des vues listées (classes d'espacement, classes de masquage responsive, classes de formatage de formulaires).

## MISSION E : ORGANISATION DU CHANTIER (FEUILLE DE ROUTE DÉTAILLÉE)
Génère pour moi une `Checklist Markdown` sous forme de "Roadmap".
Tu regrouperas la migration responsive des 31 vues dans des "Sprints" cohérents pour que je (ou toi en tant qu'assistant futur) puisse avancer étape par étape avec logique (ex: Sprint 1: Layout global & Login, Sprint 2: Opérations tactiles de Caisse, Sprint 3: Les tableaux de données complexes de l'Admin...).
Identifie explicitement quels fichiers feront l'objet d'une passe purement CSS et quels fichiers nécessiteront une restructuration lourde du code HTML.

## MISSION F : ÉCHANTILLON D'EXÉCUTION (PREUVE DE CONCEPT)
Afin de valider tes choix techniques, tu écriras en fin de réponse, le code HTML/PHP complet, modernisé et 100% Responsive de la vue `caisse/depot.php`.
- Utilise les "Utility Classes" que tu as théorisées.
- Le dépôt doit être minimaliste, très clair : Un champ "Montant", Un champ "Numéro de compte", un bouton XL "Valider le Dépôt". 
- Ajoute les directives CSS spécifiques à cette vue à l'intérieur de balises `<style>`.

## RÈGLES DE RENDU STRIKTES
- Ton retour doit être professionnel, extrêmement structuré, comme un livrable d'une agence Web de haut niveau adressé à un Directeur Technique (CTO).
- Utilise des blocs d'alertes markdown (exemple : `> [!IMPORTANT]`) pour mettre en exergue les défis techniques difficiles (comme les Data Tables dans l'espace admin ou les confusions Print/Responsive).
- N'oublie aucune page listée. L'exhaustivité est LA grande règle de cet audit.
- C'est parti, génère ce plan directeur !
