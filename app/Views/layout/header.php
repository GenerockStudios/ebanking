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
            position: absolute;
            bottom: 30px;
            width: 100%;
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
                    <a href="#" class="nav-link">
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