<?php
// Protection : s'assurer que l'utilisateur est connecté pour inclure le header sécurisé
if (!AuthController::isLoggedIn()) {
    header("Location: " . BASE_URL . "?controller=Auth&action=login");
    exit();
}

// Variables de session pour les rôles
$userName = $_SESSION['nom_complet'] ?? 'Utilisateur';
$roleName = $_SESSION['role_name'] ?? 'Invité';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?? APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* CSS de Base pour la Caisse/Admin */
        @font-face {
            font-family: 'Material Symbols Rounded';
            font-style: normal;
            font-weight: 400;
            src: url(/ebanking/src/font/materialssymbols.woff2) format('woff2');
        }

        .material-symbols-rounded {
            font-family: 'Material Symbols Rounded';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .user-info {
            font-size: 0.9em;
        }

        .content {
            padding: 20px;
            padding-left: 100px;
            width: 99%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-top: 0;
        }

        /* Style pour les messages */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }


        /* Importing Google Fonts - Poppins */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .sidebar {
            position: fixed;
            width: 270px;
            border-bottom-right-radius: 16px;
            border-top-right-radius: 16px;
            background: #042e5a;
            height: calc(100vh - 2px);
            transition: all 0.4s ease;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.25) transparent;
        }

        .sidebar.collapsed {
            width: 85px;
        }

        .sidebar .sidebar-header {
            display: flex;
            position: relative;
            padding: 25px 20px;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-header .header-logo svg {
            width: 46px;
            height: 46px;
            display: block;
            object-fit: contain;
            border-radius: 50%;
        }

        .sidebar-header .toggler {
            height: 35px;
            width: 35px;
            color: #151A2D;
            border: none;
            cursor: pointer;
            display: flex;
            background: #fff;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: 0.4s ease;
        }

        .sidebar-header .sidebar-toggler {
            position: absolute;
            right: 20px;
        }

        .sidebar-header .menu-toggler {
            display: none;
        }

        .sidebar.collapsed .sidebar-header .toggler {
            transform: translate(-4px, 65px);
        }

        .sidebar-header .toggler:hover {
            background: #dde4fb;
        }

        .sidebar-header .toggler span {
            font-size: 1.75rem;
            transition: 0.4s ease;
        }

        .sidebar.collapsed .sidebar-header .toggler span {
            transform: rotate(180deg);
        }

        .sidebar-nav .nav-list {
            list-style: none;
            display: flex;
            gap: 4px;
            padding: 0 15px;
            flex-direction: column;
            transform: translateY(15px);
            transition: 0.4s ease;
        }

        .sidebar.collapsed .sidebar-nav .primary-nav {
            transform: translateY(65px);
        }

        .sidebar-nav .nav-link {
            color: #fff;
            display: flex;
            gap: 12px;
            white-space: nowrap;
            border-radius: 8px;
            padding: 12px 15px;
            align-items: center;
            text-decoration: none;
            transition: 0.4s ease;
        }

        .sidebar.collapsed .sidebar-nav .nav-link {
            border-radius: 12px;
        }

        .sidebar .sidebar-nav .nav-link .nav-label {
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .sidebar-nav .nav-link .nav-label {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-nav .nav-link:hover {
            color: #151A2D;
            background: #fff;
        }

        .sidebar-nav .nav-item {
            position: relative;
        }

        .sidebar-nav .nav-tooltip {
            position: absolute;
            top: -10px;
            opacity: 0;
            color: #151A2D;
            display: none;
            pointer-events: none;
            padding: 6px 12px;
            border-radius: 8px;
            white-space: nowrap;
            background: #fff;
            left: calc(100% + 25px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            transition: 0s;
        }

        .sidebar.collapsed .sidebar-nav .nav-tooltip {
            display: block;
        }

        .sidebar-nav .nav-item:hover .nav-tooltip {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(50%);
            transition: all 0.4s ease;
        }

        .sidebar-nav .secondary-nav {
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: 20px;
            padding-bottom: 20px;
        }

        /* Responsive media query code for small screens */
        @media (max-width: 1024px) {
            .sidebar {
                height: 56px;
                margin: 13px;
                overflow-y: hidden;
                scrollbar-width: none;
                width: calc(100% - 26px);
                max-height: calc(100vh - 26px);
            }

            .sidebar.menu-active {
                overflow-y: auto;
            }

            .sidebar .sidebar-header {
                position: sticky;
                top: 0;
                z-index: 20;
                border-radius: 16px;
                background: #151A2D;
                padding: 8px 10px;
            }

            .sidebar-header .header-logo svg {
                width: 40px;
                height: 40px;
            }

            .sidebar-header .sidebar-toggler,
            .sidebar-nav .nav-item:hover .nav-tooltip {
                display: none;
            }

            .sidebar-header .menu-toggler {
                display: flex;
                height: 30px;
                width: 30px;
            }

            .sidebar-header .menu-toggler span {
                font-size: 1.3rem;
            }

            .sidebar .sidebar-nav .nav-list {
                padding: 0 10px;
            }

            .sidebar-nav .nav-link {
                gap: 10px;
                padding: 10px;
                font-size: 0.94rem;
            }

            .sidebar-nav .nav-link .nav-icon {
                font-size: 1.37rem;
            }

            .sidebar-nav .secondary-nav {
                position: relative;
                bottom: 0;
                margin: 40px 0 30px;
            }
        }
    </style>
</head>

<body>

    <!-- =============================================
         PAGE LOADER PREMIUM — Banking Edition
         Se déclenche au chargement ET à chaque nav.
    ============================================= -->
    <div id="page-loader">
        <!-- Fond animé avec particules -->
        <div class="loader-particles">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
            <span></span><span></span>
        </div>

        <!-- Noyau central -->
        <div class="loader-core">
            <!-- Anneaux orbitaux -->
            <div class="orbit orbit-1"><div class="orb"></div></div>
            <div class="orbit orbit-2"><div class="orb"></div></div>
            <div class="orbit orbit-3"><div class="orb"></div></div>

            <!-- Logo central -->
            <div class="loader-logo">
                <span class="material-symbols-rounded">account_balance</span>
            </div>
        </div>

        <!-- Texte status -->
        <div class="loader-text">
            <div class="loader-brand">E-BANKING PRO</div>
            <div class="loader-status" id="loader-status">Chargement en cours…</div>
        </div>

        <!-- Barre de progression -->
        <div class="loader-bar-track">
            <div class="loader-bar-fill" id="loader-bar"></div>
        </div>
    </div>

    <style>
    /* ========== LOADER OVERLAY ========== */
    #page-loader {
        position: fixed; inset: 0; z-index: 99999;
        background: linear-gradient(145deg, #021d3a 0%, #042e5a 50%, #0a4a8a 100%);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 28px;
        transition: opacity .5s ease, visibility .5s ease;
    }
    #page-loader.hidden {
        opacity: 0; visibility: hidden; pointer-events: none;
    }

    /* ---- Particules flottantes ---- */
    .loader-particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
    .loader-particles span {
        position: absolute; display: block; border-radius: 50%;
        background: rgba(255,255,255,.06);
        animation: floatUp var(--d, 8s) var(--delay, 0s) infinite ease-in-out;
    }
    .loader-particles span:nth-child(1)  { width:60px;height:60px; left:10%;  --d:9s;  --delay:0s; }
    .loader-particles span:nth-child(2)  { width:30px;height:30px; left:25%;  --d:7s;  --delay:1s; }
    .loader-particles span:nth-child(3)  { width:80px;height:80px; left:70%;  --d:11s; --delay:2s; }
    .loader-particles span:nth-child(4)  { width:20px;height:20px; left:85%;  --d:6s;  --delay:.5s; }
    .loader-particles span:nth-child(5)  { width:50px;height:50px; left:40%;  --d:10s; --delay:3s; }
    .loader-particles span:nth-child(6)  { width:15px;height:15px; left:55%;  --d:8s;  --delay:1.5s; }
    .loader-particles span:nth-child(7)  { width:40px;height:40px; left:5%;   --d:12s; --delay:4s; }
    .loader-particles span:nth-child(8)  { width:25px;height:25px; left:90%;  --d:7s;  --delay:2.5s; }
    @keyframes floatUp {
        0%   { transform: translateY(110vh) scale(.8); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: .5; }
        100% { transform: translateY(-20vh) scale(1.2); opacity: 0; }
    }

    /* ---- Noyau central ---- */
    .loader-core { position: relative; width: 130px; height: 130px; }

    /* Anneaux orbitaux */
    .orbit {
        position: absolute; border-radius: 50%;
        border: 2px solid transparent;
    }
    .orbit-1 {
        inset: 0;
        border-top-color: rgba(255,255,255,.9);
        border-right-color: rgba(255,255,255,.3);
        animation: spin1 1.4s linear infinite;
    }
    .orbit-2 {
        inset: 14px;
        border-bottom-color: rgba(96,181,255,.9);
        border-left-color: rgba(96,181,255,.2);
        animation: spin2 2s linear infinite;
    }
    .orbit-3 {
        inset: 28px;
        border-top-color: rgba(52,211,153,.9);
        border-right-color: rgba(52,211,153,.2);
        animation: spin1 2.8s linear infinite reverse;
    }
    @keyframes spin1 { to { transform: rotate(360deg); } }
    @keyframes spin2 { to { transform: rotate(-360deg); } }

    /* Point coloré sur chaque anneau */
    .orb {
        position: absolute; width: 8px; height: 8px;
        border-radius: 50%; top: -4px; left: calc(50% - 4px);
    }
    .orbit-1 .orb { background: #fff; box-shadow: 0 0 10px #fff; }
    .orbit-2 .orb { background: #60b5ff; box-shadow: 0 0 10px #60b5ff; }
    .orbit-3 .orb { background: #34d399; box-shadow: 0 0 10px #34d399; }

    /* Logo central */
    .loader-logo {
        position: absolute; inset: 42px;
        background: rgba(255,255,255,.08);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
        animation: pulseLogo 2s ease-in-out infinite;
    }
    .loader-logo .material-symbols-rounded { font-size: 26px; color: #fff; }
    @keyframes pulseLogo {
        0%,100% { transform: scale(1);   box-shadow: 0 0 0   0 rgba(255,255,255,.15); }
        50%      { transform: scale(1.08); box-shadow: 0 0 0 12px rgba(255,255,255,0); }
    }

    /* ---- Texte ---- */
    .loader-text { text-align: center; }
    .loader-brand {
        font-size: 20px; font-weight: 900; color: #fff;
        letter-spacing: 4px; text-transform: uppercase;
        margin-bottom: 6px;
    }
    .loader-status {
        font-size: 13px; color: rgba(255,255,255,.55);
        font-style: italic; letter-spacing: .5px;
        animation: fadeStatus 2.5s ease-in-out infinite;
    }
    @keyframes fadeStatus {
        0%,100% { opacity: .55; } 50% { opacity: 1; }
    }

    /* ---- Barre de progression ---- */
    .loader-bar-track {
        width: 260px; height: 3px;
        background: rgba(255,255,255,.12);
        border-radius: 3px; overflow: hidden;
    }
    .loader-bar-fill {
        height: 100%; width: 0%;
        background: linear-gradient(90deg, #34d399, #60b5ff, #fff);
        border-radius: 3px;
        animation: progressFill 1.8s ease-out forwards;
        box-shadow: 0 0 8px rgba(52,211,153,.6);
    }
    @keyframes progressFill {
        0%   { width: 0%; }
        20%  { width: 35%; }
        55%  { width: 68%; }
        80%  { width: 85%; }
        100% { width: 98%; }
    }
    </style>

    <script>
    (function() {
        var loader    = document.getElementById('page-loader');
        var statusEl  = document.getElementById('loader-status');
        var barEl     = document.getElementById('loader-bar');

        // Labels contextuels qui s'affichent pendant le chargement
        var labels = [
            'Initialisation sécurisée…',
            'Vérification des droits d\'accès…',
            'Chargement des données…',
            'Connexion au serveur bancaire…',
            'Chiffrement de la session…',
            'Synchronisation en cours…',
        ];

        // Rotation des labels toutes les 600ms
        var i = 0;
        var labelTimer = setInterval(function() {
            i = (i + 1) % labels.length;
            if (statusEl) statusEl.textContent = labels[i];
        }, 600);

        // Cacher le loader dès que la page est prête, avec un délai artificiel
        function hideLoader() {
            setTimeout(function() {
                clearInterval(labelTimer);
                if (statusEl) statusEl.textContent = 'Prêt !';
                if (barEl) {
                    barEl.style.animation = 'none';
                    barEl.style.width = '100%';
                    barEl.style.transition = 'width .25s ease';
                }
                setTimeout(function() {
                    if (loader) loader.classList.add('hidden');
                }, 320);
            }, 2000); // 2 secondes de délai minimum
        }

        if (document.readyState === 'complete') {
            hideLoader();
        } else {
            window.addEventListener('load', hideLoader);
            // Fallback : masquer après 5s max au lieu de 3.5s
            setTimeout(hideLoader, 4000);
        }

        // Re-déclencher le loader à chaque clic de navigation interne
        document.addEventListener('click', function(e) {
            var anchor = e.target.closest('a');
            if (!anchor) return;
            var href = anchor.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript') || href.startsWith('mailto')) return;
            if (anchor.target === '_blank') return;

            // Retarder la navigation de 2 secondes
            e.preventDefault();

            // Afficher le loader
            if (loader) loader.classList.remove('hidden');

            // Remettre la barre à zéro
            if (barEl) {
                barEl.style.animation = 'none';
                barEl.style.width = '0%';
                void barEl.offsetWidth; // reflow
                barEl.style.animation = 'progressFill 1.8s ease-out forwards';
            }

            // Label contextuel selon la destination
            if (statusEl) {
                if (href.indexOf('controller=Caisse') !== -1) {
                    statusEl.textContent = 'Accès caisse…';
                } else if (href.indexOf('controller=Admin') !== -1) {
                    statusEl.textContent = 'Accès administration…';
                } else if (href.indexOf('print') !== -1 || href.indexOf('imprimer') !== -1) {
                    statusEl.textContent = 'Génération du document…';
                } else {
                    statusEl.textContent = 'Navigation en cours…';
                }
            }

            // Sécurité : si la page ne charge pas en 5s, masquer quand même
            setTimeout(function() {
                if (loader && !loader.classList.contains('hidden')) {
                    loader.classList.add('hidden');
                }
            }, 4000);

            // Redirection effective après 2s (pour que l'animation s'apprécie)
            setTimeout(function() {
                window.location.href = href;
            }, 2000);
        });
    })();
    </script>
    <!-- =============================================
         FIN LOADER
    ============================================= -->

    <aside class="sidebar collapsed">

        <!-- Sidebar header -->
        <header class="sidebar-header">
            <a href="#" class="header-logo">
                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <circle style="fill:#1b28e4;" cx="256.005" cy="256.005" r="219.73"></circle>
                        <g style="opacity:0.25;">
                            <path style="fill:#666666;" d="M256,16c132.549,0,240,107.451,240,240S388.549,496,256,496S16,388.549,16,256 C16.15,123.514,123.514,16.15,256,16 M256,0C114.616,0,0,114.616,0,256s114.616,256,256,256s256-114.615,256-256S397.385,0,256,0z"></path>
                        </g>
                        <g>
                            <path style="fill:#FFFFFF;" d="M261.424,91.752l-123.24,82.648h246.48L261.424,91.752z M261.424,152.744 c-7.512,0-13.6-6.089-13.6-13.6s6.089-13.6,13.6-13.6s13.6,6.089,13.6,13.6c-0.004,7.508-6.092,13.592-13.6,13.592V152.744z"></path>
                            <rect x="128.935" y="322.357" style="fill:#FFFFFF;" width="264.965" height="25.825"></rect>
                            <path style="fill:#FFFFFF;" d="M257.673,290.016v-10.584c-5.671-0.019-11.226-1.617-16.04-4.616l2.512-7.032 c4.495,2.922,9.735,4.489,15.096,4.512c7.44,0,12.48-4.296,12.48-10.272c0-5.768-4.088-9.328-11.856-12.48 c-10.696-4.192-17.296-9.016-17.296-18.136c0.129-8.872,6.987-16.191,15.832-16.896v-10.576h6.496v10.168 c4.772,0.045,9.454,1.309,13.6,3.672l-2.632,6.92c-3.992-2.38-8.561-3.615-13.208-3.568c-8.08,0-11.12,4.8-11.12,9.016 c0,5.456,3.88,8.185,13.008,11.952c10.799,4.408,16.248,9.856,16.248,19.2c-0.173,9.296-7.31,16.973-16.568,17.824v10.904h-6.6 L257.673,290.016z"></path>
                            <rect x="156.003" y="196.994" style="fill:#FFFFFF;" width="25.824" height="101.492"></rect>
                            <rect x="196.027" y="196.994" style="fill:#FFFFFF;" width="25.824" height="101.492"></rect>
                            <rect x="301.003" y="196.994" style="fill:#FFFFFF;" width="25.825" height="101.492"></rect>
                            <rect x="341.018" y="196.994" style="fill:#FFFFFF;" width="25.825" height="101.492"></rect>
                        </g>
                    </g>
                </svg>
            </a>
            <button class="toggler sidebar-toggler">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
            <button class="toggler menu-toggler">
                <span class="material-symbols-rounded">menu</span>
            </button>
        </header>
        <nav class="sidebar-nav">
            <!-- Primary top nav -->
            <ul class="nav-list primary-nav">
                <li class="nav-item">
                    <?php
                        if ($roleName === 'Admin') {
                            $dashUrl = BASE_URL . '?controller=Admin&action=analyticsDashboard';
                        } else {
                            $dashUrl = BASE_URL . '?controller=Caisse&action=dashboard';
                        }
                    ?>
                    <a href="<?= $dashUrl ?>" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">dashboard</span>
                        <span class="nav-label">Tableau de bord</span>
                    </a>
                    <span class="nav-tooltip">Tableau de bord</span>
                </li>
                <?php if ($roleName === 'Caissier' || $roleName === 'Admin'): ?>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Caisse&action=depot" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">account_balance_wallet</span>
                            <span class="nav-label">Dépôts</span>
                        </a>
                        <span class="nav-tooltip">Dépôts</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Caisse&action=retrait" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">payments</span>
                            <span class="nav-label">Retraits</span>
                        </a>
                        <span class="nav-tooltip">Retraits</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Caisse&action=transfert" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">sync_alt</span>
                            <span class="nav-label">Transfert Interne</span>
                        </a>
                        <span class="nav-tooltip">Transfert Interne</span>
                    </li>
                <?php endif; ?>
                <?php if ($roleName === 'Superviseur' || $roleName === 'Admin'): ?>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">assessment</span>
                            <span class="nav-label">Rapports</span>
                        </a>
                        <span class="nav-tooltip">Rapports</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">lock_clock</span>
                            <span class="nav-label">Clôture Journée</span>
                        </a>
                        <span class="nav-tooltip">Clôture Journée</span>
                    </li>
                <?php endif; ?>
                <?php if ($roleName === 'Admin'): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">manage_accounts</span>
                            <span class="nav-label">Admin Utilisateurs</span>
                        </a>
                        <span class="nav-tooltip">Admin Utilisateurs</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=manageClients" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">groups</span>
                            <span class="nav-label">Gestion Clients</span>
                        </a>
                        <span class="nav-tooltip">Gestion Clients</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=analyticsDashboard" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">bar_chart</span>
                            <span class="nav-label">Analytics</span>
                        </a>
                        <span class="nav-tooltip">Analytics</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=auditLogs" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">policy</span>
                            <span class="nav-label">Journal Audit</span>
                        </a>
                        <span class="nav-tooltip">Journal Audit</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=auditKyc" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">fact_check</span>
                            <span class="nav-label">Audit Conformité KYC</span>
                        </a>
                        <span class="nav-tooltip">Audit Conformité KYC</span>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=managePlafonds" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">shield_lock</span>
                            <span class="nav-label">Gestion Plafonds</span>
                        </a>
                        <span class="nav-tooltip">Gestion Plafonds</span>
                    </li>
                <?php endif; ?>
                <?php if (in_array($roleName, ['Superviseur', 'Admin'])): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>?controller=Rapport&action=releveMensuel" class="nav-link">
                            <span class="nav-icon material-symbols-rounded">receipt_long</span>
                            <span class="nav-label">Releve Mensuel</span>
                        </a>
                        <span class="nav-tooltip">Releve Mensuel</span>
                    </li>
                <?php endif; ?>
            </ul>
            <!-- Secondary bottom nav -->
            <ul class="nav-list secondary-nav">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>?controller=Auth&action=logout" class="nav-link">
                        <span class="nav-icon material-symbols-rounded">logout</span>
                        <span class="nav-label">Déconnexion</span>
                    </a>
                    <span class="nav-tooltip">Déconnexion</span>
                </li>
            </ul>
        </nav>
    </aside>

    <script>
        const sidebar = document.querySelector(".sidebar");
        const sidebarToggler = document.querySelector(".sidebar-toggler");
        const menuToggler = document.querySelector(".menu-toggler");
        // Ensure these heights match the CSS sidebar height values
        let collapsedSidebarHeight = "56px"; // Height in mobile view (collapsed)
        let fullSidebarHeight = "calc(100vh - 2px)"; // Height in larger screen
        // Toggle sidebar's collapsed state
        sidebarToggler.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
        });
        // Update sidebar height and menu toggle text
        const toggleMenu = (isMenuActive) => {
            sidebar.style.height = isMenuActive ? `${sidebar.scrollHeight}px` : collapsedSidebarHeight;
            menuToggler.querySelector("span").innerText = isMenuActive ? "close" : "menu";
        }
        // Toggle menu-active class and adjust height
        menuToggler.addEventListener("click", () => {
            toggleMenu(sidebar.classList.toggle("menu-active"));
        });
        // (Optional code): Adjust sidebar height on window resize
        window.addEventListener("resize", () => {
            if (window.innerWidth >= 1024) {
                sidebar.style.height = fullSidebarHeight;
            } else {
                sidebar.classList.remove("collapsed");
                sidebar.style.height = "auto";
                toggleMenu(sidebar.classList.contains("menu-active"));
            }
        });
    </script>
    <div class="content">