#!/usr/bin/env python3
"""
Générateur de podcasts éducatifs — BTS Génie Logiciel (programme camerounais)

Pipeline en 2 phases avec reprise automatique :
  Phase 0 — Génération/enrichissement des notes de cours manquantes via DeepSeek
             (chaque UE doit avoir au moins 8 chapitres complets)
  Phase 1 — Conversion notes → scripts podcast → MP3 (Kokoro-82M TTS)

Usage:
    python courses_to_mp3.py [--dry-run] [--skip-note-gen] [--only-note-gen]

Prérequis :
    pip install -r requirements_podcast.txt
    Copier .env.podcast.example → .env et renseigner DEEPSEEK_API_KEY
"""

from __future__ import annotations

import os
import sys
import re
import time
import argparse
import traceback
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed

# ─────────────────────────── dépendances tierces ────────────────────────────
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass  # python-dotenv optionnel

from loguru import logger
from rich.progress import (
    Progress, SpinnerColumn, BarColumn, TaskProgressColumn,
    TimeElapsedColumn, TimeRemainingColumn, TextColumn,
)
from rich.console import Console
from rich.panel import Panel
from rich.table import Table
import openai

# ───────────────────────────── configuration ────────────────────────────────

CONFIG = {
    "DEEPSEEK_API_KEY":     os.getenv("DEEPSEEK_API_KEY", ""),
    "DEEPSEEK_BASE_URL":    os.getenv("DEEPSEEK_BASE_URL", "https://api.deepseek.com/v1"),
    "DEEPSEEK_MODEL":       os.getenv("DEEPSEEK_MODEL", "deepseek-chat"),
    "INPUT_ROOT_DIR":       os.getenv("INPUT_ROOT_DIR", "cours"),
    "OUTPUT_ROOT_DIR":      os.getenv("OUTPUT_ROOT_DIR", "podcasts"),
    "KOKORO_MODEL_NAME":    os.getenv("KOKORO_MODEL_NAME", "hexgrad/Kokoro-82M"),
    "KOKORO_VOICE":         os.getenv("KOKORO_VOICE", "ff_siwis"),
    "MAX_WORKERS":          int(os.getenv("MAX_WORKERS", "5")),
    "RETRY_ATTEMPTS":       int(os.getenv("RETRY_ATTEMPTS", "5")),
    "RETRY_BACKOFF_FACTOR": int(os.getenv("RETRY_BACKOFF_FACTOR", "2")),
    "MAX_INPUT_TOKENS":     int(os.getenv("MAX_INPUT_TOKENS", "3500")),
    "TTS_CHUNK_WORDS":      int(os.getenv("TTS_CHUNK_WORDS", "80")),
    "MP3_BITRATE":          os.getenv("MP3_BITRATE", "128k"),
    "SAVE_GENERATED_TEXT":  os.getenv("SAVE_GENERATED_TEXT", "true").lower() == "true",
}

# Matières avec formules mathématiques/scientifiques
MATIERES_AVEC_FORMULES = {
    "mathematiques", "maths",
    "comptabilite_analytique", "comptabilite_generale",
    "bases_de_donnees", "reseaux_informatiques", "informatique_generale",
    "systeme_exploitation", "terminaux_mobiles",
    "traitement_multimedia", "18_traitement_multimedia",
    "gestion_projets", "23_gestion_projets",
    "ihm", "24_ihm",
}

# ──────────────────────── curriculum officiel BTS GL ────────────────────────
# Chaque UE doit avoir au moins 8 chapitres.
# Format : { "nom_dossier": [(stem_fichier, description_du_topic), ...] }
# Si le fichier .txt existe déjà, la génération est ignorée (reprise possible).

CURRICULUM: dict[str, list[tuple[str, str]]] = {
    "01_economie_entreprise": [
        ("01_fondements_economie",
         "Fondements de l'économie : besoins, biens, agents économiques, circuits et flux économiques"),
        ("02_formes_juridiques_entreprises",
         "Formes juridiques des entreprises au Cameroun : SA, SARL, SNC, GIE, entreprise individuelle — OHADA/AUSCGIE"),
        ("03_fonctions_entreprise",
         "Les fonctions de l'entreprise : production, commerciale, financière, ressources humaines, R&D"),
        ("04_environnement_entreprise",
         "Environnement de l'entreprise : analyse PESTEL, forces de Porter, mondialisation, CEMAC"),
        ("05_droit_travail_cameroun",
         "Droit du travail camerounais : contrat de travail, CNPS, congés, licenciement, conventions collectives"),
        ("06_droit_commercial_ohada",
         "Droit commercial OHADA : actes de commerce, registre du commerce, fonds de commerce, contrats commerciaux"),
        ("07_marketing_strategie_commerciale",
         "Marketing et stratégie commerciale : segmentation, ciblage, positionnement, mix marketing 4P, CRM"),
        ("08_management_et_organisation",
         "Management et organisation : structures, styles de direction (autoritaire, participatif, délégatif), motivation"),
        ("09_financement_et_investissement",
         "Financement et investissement : fonds propres, emprunts, crédit-bail, calcul de rentabilité, VAN, TRI"),
    ],
    "02_comptabilite_generale": [
        ("01_introduction_comptabilite",
         "Introduction à la comptabilité : rôle, principes (continuité, prudence, coût historique), parties prenantes"),
        ("02_bilan_compte_resultat",
         "Bilan et compte de résultat : structure actif/passif, charges/produits, résultat net"),
        ("03_ohada_syscohada",
         "OHADA et SYSCOHADA : plan comptable général, classes 1-9, journaux obligatoires, grand-livre, balance"),
        ("04_tva_et_fiscalite_cameroun",
         "TVA et fiscalité camerounaise : taux 19,25%, collecte, déduction, déclaration mensuelle DGI, IS, IR"),
        ("05_immobilisations_et_amortissements",
         "Immobilisations et amortissements : corporelles/incorporelles, méthodes linéaire et dégressif, calculs"),
        ("06_gestion_des_stocks",
         "Gestion des stocks et inventaire : méthodes FIFO, LIFO, coût moyen pondéré, dépréciations"),
        ("07_tresorerie_et_rapprochement_bancaire",
         "Trésorerie : rapprochement bancaire, gestion de caisse, effets de commerce, lettres de change"),
        ("08_etats_financiers_et_analyse",
         "États financiers annuels SYSCOHADA : bilan, compte de résultat, TAFIRE, SIG, ratios financiers"),
        ("09_comptabilite_des_societes",
         "Comptabilité des sociétés : constitution, augmentation de capital, résultats, dividendes — OHADA"),
    ],
    "03_mathematiques": [
        ("01_algebre_lineaire",
         "Algèbre linéaire : vecteurs, matrices (opérations, inverse, déterminant), systèmes d'équations (Gauss, Cramer)"),
        ("02_probabilites_statistiques",
         "Probabilités et statistiques : événements, probabilités conditionnelles, lois (binomiale, normale, Poisson), "
         "moyenne, variance, écart-type, intervalles de confiance"),
        ("03_analyse_et_fonctions",
         "Analyse : fonctions, limites, continuité, dérivées (règles, applications), intégrales (primitives, calcul d'aire)"),
        ("04_arithmetique_et_logique_booleenne",
         "Arithmétique et logique booléenne : bases 2/8/16, conversions, algèbre de Boole, portes logiques, circuits"),
        ("05_suites_et_recurrences",
         "Suites et récurrences : suites arithmétiques et géométriques, convergence, récursivité, complexité O(n)"),
        ("06_combinatoire_et_denombrement",
         "Combinatoire : dénombrement, arrangements, permutations, combinaisons, triangle de Pascal, binôme de Newton"),
        ("07_theorie_des_graphes",
         "Théorie des graphes : définitions, représentations, parcours BFS/DFS, arbre couvrant, Dijkstra, Bellman-Ford"),
        ("08_mathematiques_financieres",
         "Mathématiques financières : intérêts simples et composés, annuités, VAN, TRI, actualisation, emprunt"),
    ],
    "04_informatique_generale": [
        ("01_architecture_ordinateur",
         "Architecture d'un ordinateur : CPU (ALU, UC, registres), mémoires (RAM, ROM, cache), bus, carte mère, "
         "périphériques E/S"),
        ("02_systemes_fichiers_et_os",
         "Systèmes de fichiers et OS : FAT32, NTFS, ext4, arborescence, partitions, gestion des processus"),
        ("03_representation_des_donnees",
         "Représentation des données : entiers (complément à 2), flottants (IEEE 754), caractères (ASCII, UTF-8), "
         "compression"),
        ("04_logiciels_et_licences",
         "Logiciels et licences : logiciel libre (GPL, MIT), propriétaire, SaaS, cloud — cycle de vie du logiciel"),
        ("05_securite_bases",
         "Bases de la sécurité informatique : menaces (virus, ransomware, phishing), antivirus, firewall, sauvegardes"),
        ("06_internet_et_web",
         "Internet et Web : protocoles TCP/IP, HTTP/HTTPS, DNS, navigateurs, moteurs de recherche, HTML/CSS de base"),
        ("07_bureautique_et_productivite",
         "Bureautique et productivité : traitement de texte, tableur (formules Excel/Calc), présentation, base de données "
         "bureautique (Access/LibreOffice Base)"),
        ("08_systeme_binaire_et_codage",
         "Système binaire et codage : conversions décimal/binaire/hexadécimal, BCD, codes détecteurs d'erreur (parité, "
         "Hamming)"),
    ],
    "05_anglais": [
        ("01_it_vocabulary_and_computing",
         "IT vocabulary and computing English: hardware, software, networking terms — reading technical documentation"),
        ("02_professional_communication",
         "Professional communication in English: emails, reports, meetings, presentations — business English"),
        ("03_grammar_for_technical_writing",
         "Grammar for technical writing: tenses (present, past, future perfect), conditionals, passive voice, modal verbs"),
        ("04_software_development_english",
         "Software development in English: reading code documentation, API docs, GitHub issues, Stack Overflow"),
        ("05_job_interview_and_cv",
         "Job interviews and CV writing in English: cover letter, LinkedIn profile, common interview questions for IT"),
        ("06_networking_and_security_english",
         "Networking and security English: reading Cisco docs, CCNA vocabulary, cybersecurity terminology"),
        ("07_project_management_english",
         "Project management in English: Agile/Scrum terminology, sprint planning, standup meeting language"),
        ("08_african_tech_ecosystem",
         "African tech ecosystem in English: fintech (mobile money), startup culture, pan-African tech companies, "
         "AfriLabs, Silicon Savannah"),
    ],
    "06_expression_francaise": [
        ("01_communication_ecrite_professionnelle",
         "Communication écrite professionnelle : lettre commerciale (norme AFNOR), compte rendu, procès-verbal, "
         "note de service"),
        ("02_rapport_et_synthese",
         "Rapport professionnel et note de synthèse : structure, introduction problématisée, parties, conclusion"),
        ("03_expression_orale_prise_de_parole",
         "Expression orale : prise de parole en public, exposé, débat, techniques de présentation (plan, accroche)"),
        ("04_grammaire_et_orthographe",
         "Grammaire et orthographe professionnelle : accords (participes, adjectifs), ponctuation, homophones, "
         "registres de langue"),
        ("05_redaction_technique",
         "Rédaction technique : manuel utilisateur, cahier des charges, documentation API, guide d'installation"),
        ("06_argumentation_et_rhetorique",
         "Argumentation et rhétorique : thèse, antithèse, synthèse, procédés argumentatifs, persuasion"),
        ("07_correspondance_administrative",
         "Correspondance administrative camerounaise : courrier officiel, format et protocole, destinataires"),
        ("08_communication_numerique",
         "Communication numérique : rédaction pour le web (SEO de base), réseaux sociaux professionnels, email formel"),
    ],
    "07_bases_de_donnees": [
        ("01_introduction_sgbd",
         "Introduction aux SGBD : données, information, base de données, modèles (hiérarchique, réseau, relationnel, "
         "NoSQL), ACID"),
        ("02_modele_entite_association",
         "Modèle Entité-Association (E/A) : entités, attributs, associations, cardinalités (1:1, 1:N, N:M), "
         "diagramme E/A"),
        ("03_modele_relationnel",
         "Modèle relationnel : relations, tuples, attributs, clés (primaire, étrangère, candidate), normalisation "
         "(1NF, 2NF, 3NF, BCNF)"),
        ("04_langage_sql_ddl_dml",
         "SQL DDL et DML : CREATE TABLE, ALTER, DROP, INSERT, UPDATE, DELETE, contraintes (NOT NULL, UNIQUE, FK)"),
        ("05_requetes_sql_avancees",
         "Requêtes SQL avancées : SELECT avec JOIN (INNER, LEFT, RIGHT, FULL), GROUP BY, HAVING, sous-requêtes, vues"),
        ("06_transactions_et_concurrence",
         "Transactions et concurrence : BEGIN/COMMIT/ROLLBACK, verrous, niveaux d'isolation, deadlock, MVCC"),
        ("07_indexation_et_optimisation",
         "Indexation et optimisation : index (B-tree, hash), EXPLAIN PLAN, partitionnement, requêtes lentes"),
        ("08_sgbd_populaires_et_nosql",
         "SGBD populaires et NoSQL : MySQL/MariaDB, PostgreSQL, Oracle, MongoDB (documents), Redis (clé-valeur), "
         "Cassandra (colonnes)"),
        ("09_conception_bd_projet",
         "Conception d'une base de données : du cahier des charges au schéma physique — projet fil rouge e-commerce"),
    ],
    "08_algorithme_programmation": [
        ("01_introduction_algorithmes",
         "Introduction aux algorithmes : définition, propriétés (correction, terminaison, efficacité), pseudo-code, "
         "organigramme"),
        ("02_structures_de_controle",
         "Structures de contrôle : séquence, condition (if/else, switch), boucles (for, while, do-while), imbrication"),
        ("03_fonctions_et_procedures",
         "Fonctions et procédures : définition, paramètres, retour, portée des variables, récursivité"),
        ("04_structures_de_donnees",
         "Structures de données : tableaux, listes chaînées, piles (LIFO), files (FIFO), arbres binaires, graphes"),
        ("05_algorithmes_de_tri_et_recherche",
         "Algorithmes de tri : Bubble Sort, Selection Sort, Insertion Sort, Quick Sort, Merge Sort — comparaison O(n²) "
         "vs O(n log n). Recherche séquentielle et dichotomique"),
        ("06_complexite_et_performance",
         "Complexité algorithmique : notation O (grand O), analyse temporelle et spatiale, cas meilleur/moyen/pire"),
        ("07_programmation_en_c",
         "Programmation en C : types, pointeurs, allocation mémoire (malloc/free), fichiers, compilation GCC"),
        ("08_programmation_en_python",
         "Programmation en Python : syntaxe de base, listes, dictionnaires, modules, fichiers, exceptions, POO de base"),
        ("09_algorithmes_numeriques",
         "Algorithmes numériques : PGCD (Euclide), nombres premiers (crible d'Ératosthène), calcul puissance rapide"),
    ],
    "09_reseaux_informatiques": [
        ("01_introduction_reseaux",
         "Introduction aux réseaux : définitions, topologies (bus, étoile, anneau, maillé), types de réseaux "
         "(LAN, MAN, WAN), avantages"),
        ("02_modele_osi_et_tcp_ip",
         "Modèles OSI (7 couches) et TCP/IP (4 couches) : rôle de chaque couche, encapsulation, PDU"),
        ("03_couche_physique_et_liaison",
         "Couches physique et liaison : câbles (RJ45, fibre), Ethernet, WiFi (802.11), MAC, CSMA/CD, spanning tree"),
        ("04_adressage_ip_et_sous_reseaux",
         "Adressage IP : IPv4 (classes, notation CIDR), masques, sous-réseaux (subnetting), IPv6 (format, avantages), "
         "NAT/PAT"),
        ("05_routage_et_protocoles",
         "Routage : routage statique, protocoles dynamiques (RIP, OSPF, EIGRP, BGP), tables de routage"),
        ("06_services_reseau",
         "Services réseau : DHCP, DNS, HTTP/HTTPS, FTP, SMTP/POP3/IMAP, SSH, SNMP — serveurs et clients"),
        ("07_securite_reseau",
         "Sécurité réseau : firewall (stateful, stateless), VPN (IPSec, OpenVPN), DMZ, IDS/IPS, proxy, VLAN"),
        ("08_reseaux_sans_fil_et_mobiles",
         "Réseaux sans fil et mobiles : WiFi 802.11 (a/b/g/n/ac/ax), Bluetooth, 3G/4G/5G, NFC, "
         "réseaux camerounais (MTN, Orange, Camtel)"),
    ],
    "10_systeme_exploitation": [
        ("01_introduction_os",
         "Introduction aux systèmes d'exploitation : rôle, composants (noyau, shell, pilotes), types "
         "(monoprogrammation, multiprogrammation, temps partagé, temps réel)"),
        ("02_gestion_processus",
         "Gestion des processus : états (prêt, actif, bloqué), PCB, ordonnancement (FCFS, SJF, Round Robin, "
         "priorité), deadlocks"),
        ("03_gestion_memoire",
         "Gestion de la mémoire : pagination, segmentation, mémoire virtuelle, swap, TLB, allocation (first fit, "
         "best fit)"),
        ("04_systeme_fichiers",
         "Systèmes de fichiers : FAT32, NTFS, ext4, répertoires, inodes, droits Unix (rwx), journalisation"),
        ("05_linux_commandes_essentielles",
         "Linux — commandes essentielles : navigation (ls, cd, pwd), fichiers (cp, mv, rm, touch), droits (chmod, "
         "chown), processus (ps, kill, top), réseau (ifconfig, ping, ssh)"),
        ("06_scripting_bash",
         "Scripting Bash : variables, conditions, boucles, fonctions, pipes, redirections, cron (planification)"),
        ("07_gestion_utilisateurs_et_securite",
         "Gestion des utilisateurs sous Linux : useradd, passwd, groupes, sudo, PAM, journaux système (syslog)"),
        ("08_virtualisation_et_conteneurs",
         "Virtualisation et conteneurs : hyperviseur (VMware, VirtualBox, KVM), Docker (images, conteneurs, "
         "Dockerfile, docker-compose)"),
        ("09_administration_serveurs",
         "Administration de serveurs Linux : Apache/Nginx, MySQL, FTP (vsftpd), configuration réseau, monitoring"),
    ],
    "11_comptabilite_analytique": [
        ("01_introduction_comptabilite_analytique",
         "Introduction à la comptabilité analytique : objectifs, différences avec la comptabilité générale, "
         "coûts et charges"),
        ("02_charges_directes_et_indirectes",
         "Charges directes et indirectes : affectation directe, répartition des charges indirectes, clés de "
         "répartition, tableau de répartition"),
        ("03_methode_des_sections_homogenes",
         "Méthode des sections homogènes (centres d'analyse) : sections auxiliaires et principales, unités d'œuvre, "
         "taux de cession interne"),
        ("04_cout_de_revient_et_production",
         "Coût de revient : coût d'achat, coût de production, coût de distribution, coût complet — calcul pas à pas"),
        ("05_methode_des_couts_variables",
         "Méthode des coûts variables (direct costing) : charges fixes/variables, marge sur coût variable, "
         "seuil de rentabilité, point mort"),
        ("06_methode_abc",
         "Méthode ABC (Activity-Based Costing) : activités, inducteurs de coût, avantages vs sections homogènes"),
        ("07_budgets_et_controle_de_gestion",
         "Budgets et contrôle de gestion : budget prévisionnel, écarts (sur volume, prix, rendement), "
         "tableau de bord"),
        ("08_prix_de_cession_interne",
         "Prix de cession interne et décisions de gestion : make or buy, sous-traitance, rentabilité d'un produit"),
    ],
    "12_systeme_information": [
        ("01_introduction_si",
         "Introduction aux systèmes d'information : définition, composantes (données, traitements, acteurs, "
         "technologies), rôle stratégique"),
        ("02_merise_modeles_conceptuels",
         "Méthode MERISE : MCD (Modèle Conceptuel de Données), MCT (Modèle Conceptuel de Traitements), "
         "niveaux conceptuel/logique/physique"),
        ("03_modele_logique_et_physique",
         "Passage MCD → MLD → MPD : règles de transformation, clés étrangères, tables de jointure, "
         "dénormalisation"),
        ("04_systeme_erp_et_crm",
         "ERP et CRM : définition, modules (Finance, RH, Supply Chain, Commercial), éditeurs (SAP, Oracle, "
         "Sage) — contexte camerounais"),
        ("05_business_intelligence",
         "Business Intelligence : entrepôts de données (Data Warehouse), cubes OLAP, tableaux de bord (KPI), "
         "reporting, Power BI"),
        ("06_architecture_si_et_urbanisation",
         "Architecture SI et urbanisation : cartographie applicative, ESB, SOA (Service-Oriented Architecture), "
         "microservices vs monolithe"),
        ("07_securite_et_gouvernance_si",
         "Sécurité et gouvernance du SI : COBIT, ITIL, ISO 27001, RGPD/loi camerounaise sur les données "
         "personnelles, PCA/PRA"),
        ("08_conduite_du_changement",
         "Conduite du changement : résistance au changement, formation, communication, accompagnement lors "
         "d'une implémentation ERP"),
    ],
    "13_reseaux_avances": [
        ("01_vlans_et_commutation_avancee",
         "VLANs et commutation avancée : 802.1Q, trunking, Inter-VLAN routing, STP/RSTP, EtherChannel"),
        ("02_routage_dynamique_avance",
         "Routage dynamique avancé : OSPF multi-area, BGP (eBGP/iBGP), redistribution de routes, politiques"),
        ("03_wan_et_technologies_operateur",
         "WAN et technologies opérateur : MPLS, PPTP, SD-WAN, fibre (FTTH), infrastructure Camtel/backbone"),
        ("04_ipv6_migration",
         "IPv6 en profondeur : adressage, autoconfiguration SLAAC, DHCPv6, transition IPv4/IPv6 (dual-stack, "
         "tunneling, NAT64)"),
        ("05_voip_et_communications_unifiees",
         "VoIP et communications unifiées : SIP, RTP, codec, QoS (DSCP, queuing), UC (Cisco Unified, "
         "Asterisk/FreePBX)"),
        ("06_securite_reseau_avancee",
         "Sécurité réseau avancée : ACL avancées, IDS/IPS (Snort), pare-feu applicatif (WAF), honeypot, "
         "analyse de trafic (Wireshark)"),
        ("07_reseaux_definis_par_logiciel",
         "SDN (Software-Defined Networking) : plan de contrôle vs données, OpenFlow, contrôleur (OpenDaylight), "
         "NFV, cloud networking"),
        ("08_supervision_et_monitoring",
         "Supervision et monitoring réseaux : SNMP, Nagios, Zabbix, Grafana, logs syslog, NetFlow — "
         "infrastructure camerounaise"),
    ],
    "14_developpement_web": [
        ("01_html5_et_css3",
         "HTML5 et CSS3 : structure sémantique, Flexbox, Grid, responsive design, media queries, accessibilité"),
        ("02_javascript_fondamentaux",
         "JavaScript fondamentaux : variables, fonctions, DOM, événements, AJAX, Fetch API, JSON, ES6+"),
        ("03_frameworks_frontend",
         "Frameworks frontend : React.js (composants, hooks, state, props), Vue.js, introduction à Angular, "
         "comparaison"),
        ("04_php_et_backend",
         "PHP et développement backend : syntaxe, fonctions, gestion de sessions, formulaires, PDO/MySQLi, "
         "sécurité (injection SQL, XSS)"),
        ("05_frameworks_backend",
         "Frameworks backend : Laravel (PHP), Express.js (Node.js), Django (Python), architecture MVC, API REST"),
        ("06_api_rest_et_graphql",
         "API REST et GraphQL : conception (endpoints, verbes HTTP, codes status, versioning), documentation "
         "Swagger/OpenAPI, authentification JWT/OAuth2"),
        ("07_devops_et_deploiement",
         "DevOps et déploiement web : Git/GitHub (branches, PR), CI/CD (GitHub Actions), hébergement "
         "(cPanel, VPS, Heroku, Vercel), HTTPS/SSL"),
        ("08_cms_et_e_commerce",
         "CMS et e-commerce : WordPress (thèmes, plugins, WooCommerce), PrestaShop — contexte e-commerce "
         "camerounais, intégration Mobile Money"),
        ("09_performance_et_seo",
         "Performance web et SEO : Lighthouse, Core Web Vitals, lazy loading, CDN, cache, référencement naturel"),
    ],
    "15_securite_informatique": [
        ("01_introduction_cybersecurite",
         "Introduction à la cybersécurité : menaces (malware, ransomware, phishing, DDoS), acteurs (hackers "
         "white/grey/black hat), cadre légal camerounais (loi 2010 sur la cybercriminalité)"),
        ("02_cryptographie",
         "Cryptographie : chiffrement symétrique (AES, DES), asymétrique (RSA, ECC), hachage (MD5, SHA-256), "
         "signatures numériques, PKI, certificats SSL/TLS"),
        ("03_securite_des_applications",
         "Sécurité des applications : OWASP Top 10 (injection SQL, XSS, CSRF, IDOR, etc.), SDL, tests de "
         "pénétration, SAST/DAST"),
        ("04_securite_reseau_pratique",
         "Sécurité réseau pratique : configuration firewall (iptables, UFW), VPN (OpenVPN, WireGuard), "
         "détection d'intrusion (Snort/Suricata)"),
        ("05_authentification_et_controle_acces",
         "Authentification et contrôle d'accès : mots de passe (bcrypt, PBKDF2), MFA, SSO (SAML, OAuth2/OIDC), "
         "RBAC, ABAC, modèle Zero Trust"),
        ("06_forensique_et_reponse_incident",
         "Forensique numérique et réponse aux incidents : collecte de preuves, analyse de logs, chaîne de "
         "custody, SIEM (Splunk, ELK Stack)"),
        ("07_securite_cloud_et_mobile",
         "Sécurité cloud et mobile : shared responsibility model (AWS/Azure/GCP), OWASP Mobile Top 10, "
         "MDM, chiffrement des données au repos"),
        ("08_normes_et_conformite",
         "Normes et conformité : ISO 27001/27002, RGPD, loi camerounaise sur les données personnelles "
         "(CNIL Cameroun), PCI-DSS, SOC 2"),
    ],
    "16_programmation_orientee_objet": [
        ("01_principes_poo",
         "Principes fondamentaux de la POO : classes, objets, encapsulation, héritage, polymorphisme, abstraction — "
         "comparaison avec la programmation procédurale"),
        ("02_classes_et_objets_java",
         "Classes et objets en Java : déclaration, constructeurs, attributs (static, final), méthodes, "
         "surcharge, toString, equals"),
        ("03_heritage_et_polymorphisme",
         "Héritage et polymorphisme en Java : extends, super, override, classes abstraites, interface, "
         "cast, instanceof"),
        ("04_packages_et_visibilite",
         "Packages, visibilité et modules Java : public/protected/private/package-private, import, "
         "modules Java 9+ (module-info)"),
        ("05_collections_java",
         "Collections Java (java.util) : List (ArrayList, LinkedList), Set (HashSet, TreeSet), Map "
         "(HashMap, TreeMap), Iterator, Comparable/Comparator"),
        ("06_gestion_exceptions",
         "Gestion des exceptions Java : try/catch/finally, checked vs unchecked, créer ses exceptions, "
         "try-with-resources, multi-catch"),
        ("07_entrees_sorties_fichiers",
         "Entrées/Sorties et fichiers Java : File, InputStream/OutputStream, Reader/Writer, BufferedReader, "
         "NIO (Path, Files), sérialisation"),
        ("08_patterns_de_conception",
         "Patterns de conception (Design Patterns) : Singleton, Factory, Observer, Strategy, Decorator, "
         "MVC — pourquoi et comment les utiliser"),
        ("09_introduction_kotlin",
         "Introduction à Kotlin : val/var, null safety, data classes, extensions, lambdas, coroutines — "
         "interopérabilité Java"),
    ],
    "17_genie_logiciel": [
        ("01_introduction_genie_logiciel",
         "Introduction au génie logiciel : définition, crise du logiciel, IEEE, qualité logicielle (ISO 25010), "
         "cycle de vie (V, spirale, agile)"),
        ("02_specifications_et_tests",
         "Spécifications et tests : cahier des charges (fonctionnel/technique), maquettage, tests unitaires, "
         "intégration, système, recette (UAT)"),
        ("03_modelisation_et_uml",
         "Modélisation et UML : rôle de la modélisation, diagrammes structurels vs comportementaux, cas "
         "d'utilisation, classes, séquence"),
        ("04_methodes_agiles",
         "Méthodes agiles : manifeste agile (4 valeurs, 12 principes), Scrum, Kanban, XP, SAFe — comparaison "
         "avec cycle en V"),
        ("05_gestion_configuration_et_versioning",
         "Gestion de configuration et versioning : Git (commit, branch, merge, rebase, PR), GitFlow, "
         "GitHub/GitLab, CI/CD, Docker"),
        ("06_qualite_et_revue_de_code",
         "Qualité et revue de code : métriques (complexité cyclomatique, couplage, cohésion), revue par les "
         "pairs, outils (SonarQube, ESLint, pylint)"),
        ("07_architecture_logicielle",
         "Architecture logicielle : monolithe, microservices, MVC, MVVM, Clean Architecture, API Gateway, "
         "bus de messages (Kafka, RabbitMQ)"),
        ("08_maintenance_et_evolution",
         "Maintenance et évolution logicielle : types (corrective, adaptative, perfective, préventive), "
         "dette technique, refactoring, migration"),
    ],
    "18_traitement_multimedia": [
        ("01_images_et_compression",
         "Images numériques et compression : pixel, résolution, couleur (RGB, CMJN, YUV), compression sans "
         "perte (PNG, LZW) et avec perte (JPEG, qualité vs taille)"),
        ("02_video_et_standards",
         "Vidéo numérique et standards : frame rate (FPS), codec (H.264, H.265, AV1), conteneur (MP4, MKV, "
         "AVI), streaming (HLS, DASH), YouTube/Netflix"),
        ("03_audio_numerique",
         "Audio numérique : échantillonnage (Nyquist), PCM, quantification, formats (WAV, MP3, AAC, FLAC), "
         "compression audio, synthèse vocale (TTS)"),
        ("04_traitement_image_python",
         "Traitement d'image avec Python : bibliothèques Pillow et OpenCV, transformations (redimensionner, "
         "recadrer, filtres), détection de contours"),
        ("05_infographie_et_svg",
         "Infographie vectorielle : SVG, Inkscape, Adobe Illustrator — raster vs vectoriel, logos, icônes, "
         "impression"),
        ("06_multimedia_web",
         "Multimédia sur le web : balises HTML5 (video, audio, canvas), API Web Audio, API MediaRecorder, "
         "accessibilité des médias"),
        ("07_steganographie_et_filigranes",
         "Stéganographie et filigranes numériques : cacher des données dans des images/sons, watermarking, "
         "DRM, protection des œuvres"),
        ("08_ia_et_traitement_multimedia",
         "IA et traitement multimédia : reconnaissance d'image (CNN, YOLO), OCR (Tesseract), génération "
         "(DALL-E, Stable Diffusion), deep fake — enjeux éthiques"),
    ],
    "19_maintenance_informatique": [
        ("01_maintenance_materielle",
         "Maintenance matérielle : composants PC (CPU, RAM, disques, cartes), pannes courantes, diagnostic, "
         "nettoyage, remplacement — matériel courant en Afrique"),
        ("02_negociation_et_contrats_it",
         "Négociation et contrats IT : appel d'offres, cahier des charges, contrat de maintenance (SLA, "
         "GTI/GTR), licences, garanties"),
        ("03_maintenance_logicielle",
         "Maintenance logicielle : mises à jour (Windows Update, apt, yum), patch management, WSUS, "
         "antivirus, désinstallation, registre Windows"),
        ("04_helpdesk_et_ticketing",
         "Helpdesk et ticketing : ITIL (incidents, problèmes, changements), outils (GLPI, Jira Service Desk), "
         "niveaux de support (N1/N2/N3), SLA"),
        ("05_sauvegarde_et_plan_reprise",
         "Sauvegarde et plan de reprise : types (complète, incrémentale, différentielle), règle 3-2-1, "
         "outils (Veeam, rsync), RTO, RPO, PCA/PRA"),
        ("06_inventaire_et_cmdb",
         "Inventaire et CMDB : gestion des actifs informatiques, logiciels de découverte réseau (OCS "
         "Inventory, Lansweeper), CMDB ITIL"),
        ("07_maintenance_reseau_et_securite",
         "Maintenance réseau et sécurité : câblage (normes EIA/TIA 568), bornes WiFi, switches, supervision "
         "(Nagios, Zabbix), mises à jour firmware"),
        ("08_maintenance_en_afrique",
         "Maintenance informatique en contexte africain : alimentation instable (onduleurs, groupe électrogène), "
         "chaleur, poussière, pièces détachées, support distant"),
    ],
    "20_techniques_expression": [
        ("01_expression_ecrite_et_orale",
         "Expression écrite et orale : types de discours, argumentation, cohérence textuelle, registres de "
         "langue (formel, courant, familier)"),
        ("02_redaction_professionnelle",
         "Rédaction professionnelle IT : rapport de stage, mémoire de fin d'études BTS, compte rendu de "
         "réunion, documentation technique"),
        ("03_communication_interculturelle",
         "Communication interculturelle : contexte camerounais multilingue (français/anglais/langues "
         "nationales), communication en entreprise internationale"),
        ("04_soutenance_et_jury",
         "Soutenance devant un jury : préparation, structure de l'exposé, gestion du stress, questions-réponses, "
         "utilisation de diapositives"),
        ("05_communication_numerique",
         "Communication numérique : LinkedIn, portfolio en ligne (GitHub Pages, Behance), email professionnel, "
         "présence numérique positive"),
        ("06_negociation_et_relation_client",
         "Négociation et relation client : écoute active, empathie, gestion des objections, fidélisation, "
         "contrat de prestation IT"),
        ("07_redaction_cv_et_lettre",
         "Rédaction du CV et lettre de motivation : structure, mise en page, verbes d'action, adaptation à "
         "l'offre, portfolio de projets"),
        ("08_ethique_et_deontologie",
         "Éthique et déontologie informatique : propriété intellectuelle, droit d'auteur, vie privée "
         "(RGPD), responsabilité du développeur, intelligence artificielle"),
    ],
    "21_uml_et_moo": [
        ("01_introduction_uml",
         "Introduction à UML 2.5 : histoire (Booch, Rumbaugh, Jacobson), 14 types de diagrammes, "
         "structurels vs comportementaux, outils (StarUML, PlantUML, draw.io)"),
        ("02_diagrammes_comportementaux",
         "Diagrammes comportementaux UML : activités (swimlanes), états-transitions, composants, déploiement, "
         "méthodes OO (OOAD, SOLID)"),
        ("03_diagramme_de_classes_avance",
         "Diagramme de classes avancé : associations (simple, agrégation, composition), héritage, réalisation, "
         "dépendance, multiplicités, classes abstraites"),
        ("04_diagramme_de_sequence",
         "Diagramme de séquence : lifelines, messages (synchrones/asynchrones/retour), fragments "
         "(alt/opt/loop/par), cas d'utilisation complets"),
        ("05_diagramme_de_cas_utilisation",
         "Diagramme de cas d'utilisation : acteurs (principal/secondaire), relations include/extend, "
         "frontière système, description textuelle (flux nominal/alternatif)"),
        ("06_conception_oo_et_patterns",
         "Conception orientée objet : analyse OOAD, identification des classes (CRC cards), principes SOLID, "
         "patterns (MVC, Observer, Factory)"),
        ("07_merise_vs_uml",
         "MERISE vs UML : MCD/MCT vs diagramme de classes/séquence — quand utiliser l'une ou l'autre, "
         "correspondance conceptuelle"),
        ("08_projet_uml_complet",
         "Projet UML complet : modélisation d'un système de gestion scolaire camerounais — UC, classes, "
         "séquence, activités, déploiement"),
    ],
    "22_terminaux_mobiles": [
        ("01_developpement_android",
         "Développement Android : architecture Android (Linux→ART→Framework), composants (Activity, Fragment, "
         "Service, Intent), Kotlin, cycle de vie Activity"),
        ("02_layouts_et_interface_android",
         "Layouts et interface Android : XML layouts (LinearLayout, ConstraintLayout, RecyclerView), Material "
         "Design, dimensions (dp/sp), thèmes"),
        ("03_navigation_et_multi_ecrans",
         "Navigation multi-écrans Android : Intent explicite/implicite, Back Stack, Navigation Component "
         "(Jetpack), Fragments, Bottom Navigation"),
        ("04_stockage_et_bases_de_donnees_android",
         "Stockage Android : SharedPreferences, Room (SQLite ORM), fichiers internes/externes, "
         "Firebase Firestore, permissions (Manifest + runtime)"),
        ("05_reseau_et_api_android",
         "Réseau et API Android : Retrofit + OkHttp (REST), Coroutines Kotlin (async/await), LiveData, "
         "ViewModel (MVVM), gestion des erreurs réseau"),
        ("06_mobile_money_et_paiement",
         "Mobile Money et paiement mobile : MTN MoMo API, Orange Money API (Cameroun), processus d'intégration "
         "(sandbox, webhook, PIN USSD), sécurisation"),
        ("07_publication_et_performance",
         "Publication sur Google Play et performance : signing APK/AAB, Play Console, optimisation (ProGuard, "
         "R8), profiling (CPU/mémoire), tests (Espresso, JUnit)"),
        ("08_developpement_cross_platform",
         "Développement cross-platform : Flutter (Dart, widgets), React Native (JS/TS) — comparaison avec "
         "natif Android, cas d'usage Afrique"),
    ],
    "23_gestion_projets": [
        ("01_planification_et_methodes",
         "Planification et méthodes : diagramme de Gantt, PERT/MPM (chemin critique, marges), jalons, "
         "outils (MS Project, GanttProject, ProjectLibre)"),
        ("02_methode_scrum_agile",
         "Méthode Scrum et agilité : rôles (PO, Scrum Master, équipe), artefacts (Product Backlog, Sprint "
         "Backlog, Incrément), cérémonies (Planning, Daily, Review, Retro)"),
        ("03_estimation_et_charges",
         "Estimation des charges : points de fonction, COCOMO, Planning Poker (Fibonacci, Story Points), "
         "vélocité, capacité d'équipe"),
        ("04_gestion_des_risques",
         "Gestion des risques : identification (SWOT, brainstorming), matrice probabilité/impact, "
         "stratégies (évitement, réduction, transfert, acceptation)"),
        ("05_gestion_des_couts",
         "Gestion des coûts de projet : budget prévisionnel, valeur acquise (EVM : PV, EV, AC, CPI, SPI), "
         "dépassement de budget"),
        ("06_gestion_des_parties_prenantes",
         "Gestion des parties prenantes : identification, matrice intérêt/influence, plan de communication, "
         "rapport d'avancement, RACI"),
        ("07_cahier_des_charges",
         "Cahier des charges informatique : CDC fonctionnel et technique, expression des besoins, "
         "spécifications, appel d'offres, réponse à appel d'offres"),
        ("08_gestion_projet_contexte_africain",
         "Gestion de projet dans le contexte africain : contraintes spécifiques (budget, énergie, "
         "connectivité), projets gouvernementaux, financement UE/Banque Mondiale"),
    ],
    "24_ihm": [
        ("01_conception_interfaces",
         "Conception d'interfaces : IHM, usabilité (ISO 9241), heuristiques de Nielsen, lois de Fitts, "
         "Miller, Hick — principes fondamentaux"),
        ("02_processus_ucd",
         "Processus UCD (User-Centered Design) : personas, user stories, wireframes, mockups, prototypes "
         "interactifs (Figma, Adobe XD)"),
        ("03_design_visuel_et_couleurs",
         "Design visuel et couleurs : théorie des couleurs, accessibilité (WCAG 2.1, contraste 4,5:1), "
         "typographie, iconographie — contexte africain"),
        ("04_systemes_de_design",
         "Systèmes de design : Material Design (Google), Human Interface Guidelines (Apple), Bootstrap, "
         "Tailwind CSS, composants réutilisables"),
        ("05_responsive_design_et_mobile_first",
         "Responsive design et Mobile First : grilles (12 colonnes), breakpoints, Flexbox, CSS Grid, "
         "tests multi-appareils — forte pénétration mobile Cameroun"),
        ("06_evaluation_et_tests_utilisateurs",
         "Évaluation et tests utilisateurs : tests modérés/non-modérés, protocole think-aloud, métriques "
         "(taux réussite, temps tâche, SUS, NPS), heuristiques"),
        ("07_accessibilite_numerique",
         "Accessibilité numérique (a11y) : WCAG 2.1 (A/AA/AAA), lecteurs d'écran (NVDA, VoiceOver), "
         "ARIA, conception inclusive — contexte handicap en Afrique"),
        ("08_ihm_et_intelligence_artificielle",
         "IHM et intelligence artificielle : interfaces conversationnelles (chatbot, voix), "
         "personnalisation adaptative, enjeux éthiques (biais, opacité), UX des systèmes IA"),
    ],
}

# ─────────────────────────────── prompts ────────────────────────────────────

SYSTEM_PROMPT = (
    "Tu es un professeur expert qui transforme des notes de cours brutes en scripts "
    "de podcast pédagogiques, clairs et agréables à écouter."
)

NOTE_GEN_SYSTEM_PROMPT = (
    "Tu es un professeur expert en BTS Génie Logiciel (programme officiel camerounais, MINPOSTEL/MINESUP). "
    "Tu rédiges des notes de cours complètes, rigoureuses et pédagogiques pour les étudiants de BTS GL. "
    "Tu connais parfaitement le programme officiel, les référentiels du BTS camerounais, et tu adaptes "
    "tes exemples au contexte camerounais et africain."
)

NOTE_GEN_USER_PROMPT = """
Rédige des notes de cours complètes et détaillées pour le chapitre suivant du BTS Génie Logiciel camerounais :

Matière : {matiere}
Chapitre : {topic}

Les notes doivent :
- Couvrir TOUS les points essentiels du chapitre selon le programme officiel BTS GL camerounais
- Inclure les définitions précises, concepts théoriques, formules et lois (si applicable)
- Donner des exemples concrets adaptés au contexte camerounais et africain (OHADA, MTN, Orange, Camtel, etc.)
- Être structurées avec des titres et sous-titres numérotés (1., 1.1., 1.2., 2., etc.)
- Inclure des tableaux comparatifs, listes de points clés, encadrés "À retenir" si pertinent
- Mentionner les outils, logiciels et technologies utilisés en entreprise
- Faire entre 1200 et 2000 mots — suffisant pour couvrir le sujet en profondeur

Notes de cours — {matiere}, {topic} :
""".strip()

USER_PROMPT_TEMPLATE = """
Contexte : Matière '{matiere}', chapitre '{chapitre}', niveau BTS Génie Logiciel (programme camerounais).

Voici les notes brutes du cours :
{notes}

Génère un script de podcast détaillé à partir de ces notes. Les notes sont un extrait du chapitre — enrichis-les avec ta connaissance complète du programme BTS GL camerounais. Le script doit :
- Être en français, avec un ton chaleureux et pédagogique.
- Développer chaque concept avec des explications claires, des exemples concrets adaptés au contexte camerounais et africain.
- Inclure des répétitions stratégiques pour faciliter la mémorisation.
- Mettre en évidence les formules importantes en les énonçant clairement (écrire les formules en toutes lettres).
- Se terminer par une section "À retenir" qui résume les points clés du chapitre.
- Utiliser des phrases courtes, des transitions orales ("Passons maintenant à...", "N'oublions pas que...", "Retenons bien que...").
- Ne pas inclure de balises HTML, de titres numérotés avec dièses, ou de formatage markdown ; seulement du texte brut avec des retours à la ligne pour les pauses naturelles.
- Longueur cible : 1500 à 2500 mots.

Texte du podcast :
""".strip()

FORMULES_PROMPT_TEMPLATE = """
Génère un résumé audio de toutes les formules et concepts-clés importants de la matière '{matiere}' (BTS Génie Logiciel, programme camerounais).

Pour chaque formule ou concept :
- Énonce la formule clairement en français (évite les symboles abstraits, écris-les en toutes lettres).
- Donne une brève explication de son utilisation pratique.
- Fournis un exemple concret si pertinent.

Structure le texte pour qu'il soit fluide et agréable à écouter, avec des transitions naturelles.
Ne pas inclure de balises ou de formatage spécial ; texte brut uniquement.

Résumé des formules — {matiere} :
""".strip()

FORMULAS_PROMPT_TEMPLATE = FORMULES_PROMPT_TEMPLATE  # alias

NO_FORMULAS_TEXT = (
    "Cette matière ne comporte pas de formules mathématiques spécifiques. "
    "Les notions essentielles sont développées dans chaque chapitre de cours. "
    "Écoutez les épisodes de chaque chapitre pour maîtriser pleinement la matière."
)

# ────────────────────────────── logging setup ───────────────────────────────

def setup_logging(output_dir: Path) -> None:
    logger.remove()
    logger.add(
        sys.stderr,
        colorize=True,
        format="<green>{time:HH:mm:ss}</green> | <level>{level: <8}</level> | <cyan>{name}</cyan> — {message}",
        level="INFO",
    )
    errors_file = output_dir / "errors.log"
    logger.add(
        str(errors_file),
        format="{time:YYYY-MM-DD HH:mm:ss} | {level} | {name} | {message}\n{exception}",
        level="WARNING",
        rotation="10 MB",
    )
    logger.info(f"Logs d'erreur → {errors_file}")

# ───────────────────────── client DeepSeek ──────────────────────────────────

def make_deepseek_client() -> openai.OpenAI:
    api_key = CONFIG["DEEPSEEK_API_KEY"]
    if not api_key:
        logger.error(
            "DEEPSEEK_API_KEY manquante. "
            "Définissez-la dans votre .env ou en variable d'environnement."
        )
        sys.exit(1)
    return openai.OpenAI(api_key=api_key, base_url=CONFIG["DEEPSEEK_BASE_URL"])


def call_deepseek_with_retry(
    client: openai.OpenAI,
    system: str,
    user: str,
    label: str = "",
    max_tokens: int = 4096,
) -> str:
    """Appelle l'API DeepSeek avec retry + exponential backoff."""
    attempts = CONFIG["RETRY_ATTEMPTS"]
    backoff  = CONFIG["RETRY_BACKOFF_FACTOR"]
    last_exc: Exception | None = None

    for attempt in range(1, attempts + 1):
        try:
            response = client.chat.completions.create(
                model=CONFIG["DEEPSEEK_MODEL"],
                messages=[
                    {"role": "system", "content": system},
                    {"role": "user",   "content": user},
                ],
                temperature=0.7,
                max_tokens=max_tokens,
                timeout=180,
            )
            text = (response.choices[0].message.content or "").strip()
            if not text:
                raise ValueError("Réponse vide reçue de l'API.")
            return text

        except (openai.APITimeoutError, openai.APIConnectionError, openai.InternalServerError) as exc:
            wait = backoff ** attempt
            logger.warning(
                f"[{label}] Tentative {attempt}/{attempts} — erreur transitoire "
                f"({exc.__class__.__name__}). Attente {wait}s…"
            )
            last_exc = exc
            time.sleep(wait)

        except openai.RateLimitError as exc:
            wait = backoff ** attempt * 5
            logger.warning(f"[{label}] Rate limit. Attente {wait}s…")
            last_exc = exc
            time.sleep(wait)

        except Exception as exc:
            logger.error(f"[{label}] Erreur non-transitoire : {exc}")
            raise

    raise RuntimeError(
        f"[{label}] Échec après {attempts} tentatives. Dernière erreur : {last_exc}"
    )

# ─────────────────── Phase 0 : génération des notes de cours ────────────────

def generate_missing_chapters(
    input_root: Path,
    client: openai.OpenAI,
    dry_run: bool,
    console: Console,
) -> tuple[int, int]:
    """
    Phase 0 — Pour chaque UE du curriculum, génère les chapitres manquants.

    Retourne (nb_generés, nb_erreurs).
    """
    tasks_to_generate: list[tuple[Path, str, str]] = []
    # (chemin_cible, matiere_slug, topic_description)

    for folder_name, chapters in CURRICULUM.items():
        subject_dir = input_root / folder_name
        subject_dir.mkdir(parents=True, exist_ok=True)

        for stem, topic in chapters:
            target = subject_dir / f"{stem}.txt"
            if target.exists() and target.stat().st_size > 100:
                # Fichier déjà présent et non vide → on saute
                continue
            tasks_to_generate.append((target, folder_name, topic))

    if not tasks_to_generate:
        logger.info("Phase 0 : tous les chapitres du curriculum existent déjà.")
        return 0, 0

    console.print(
        f"\n[bold cyan]Phase 0[/bold cyan] — Génération de "
        f"[bold]{len(tasks_to_generate)}[/bold] chapitres manquants…"
    )

    generated = 0
    errors = 0

    def _generate_one(target: Path, folder: str, topic: str) -> dict:
        label = f"{folder}/{target.stem}"
        try:
            if dry_run:
                target.write_text(
                    f"[DRY-RUN] Notes générées pour : {topic}\n", encoding="utf-8"
                )
                return {"label": label, "ok": True}

            matiere_display = folder.replace("_", " ").title()
            prompt = NOTE_GEN_USER_PROMPT.format(
                matiere=matiere_display,
                topic=topic,
            )
            logger.info(f"[{label}] Génération des notes…")
            text = call_deepseek_with_retry(
                client,
                NOTE_GEN_SYSTEM_PROMPT,
                prompt,
                label=label,
                max_tokens=3000,
            )
            target.write_text(text, encoding="utf-8")
            logger.success(f"[{label}] Notes sauvegardées ({len(text.split())} mots) → {target}")
            return {"label": label, "ok": True}

        except Exception as exc:
            logger.error(f"[{label}] ÉCHEC génération notes : {exc}")
            return {"label": label, "ok": False, "error": str(exc)}

    with Progress(
        SpinnerColumn(),
        TextColumn("[progress.description]{task.description}"),
        BarColumn(),
        TaskProgressColumn(),
        TimeElapsedColumn(),
        console=console,
    ) as progress:
        bar = progress.add_task(
            f"Génération notes ({len(tasks_to_generate)} chapitres)",
            total=len(tasks_to_generate),
        )
        # Workers réduits pour la génération (évite le rate limit)
        note_workers = min(CONFIG["MAX_WORKERS"], 3)
        with ThreadPoolExecutor(max_workers=note_workers) as executor:
            futures = {
                executor.submit(_generate_one, t, f, tp): (t, f, tp)
                for t, f, tp in tasks_to_generate
            }
            for future in as_completed(futures):
                res = future.result()
                if res["ok"]:
                    generated += 1
                else:
                    errors += 1
                progress.advance(bar)
                progress.update(
                    bar,
                    description=(
                        f"{'✓' if res['ok'] else '✗'} {res['label']}"
                    ),
                )

    return generated, errors

# ───────────────────────── synthèse vocale Kokoro ───────────────────────────

class KokoroTTS:
    def __init__(self, dry_run: bool = False):
        self.dry_run = dry_run
        self._pipeline = None
        self._backend = ""
        if not dry_run:
            self._load_model()

    def _load_model(self) -> None:
        try:
            from kokoro import KPipeline  # type: ignore
            self._pipeline = KPipeline(lang_code="f")
            self._backend = "kokoro"
            logger.info("Moteur TTS : kokoro (PyPI)")
        except ImportError:
            try:
                from transformers import pipeline as hf_pipeline  # type: ignore
                self._pipeline = hf_pipeline(
                    "text-to-speech",
                    model=CONFIG["KOKORO_MODEL_NAME"],
                )
                self._backend = "transformers"
                logger.info("Moteur TTS : transformers (Hugging Face)")
            except Exception as exc:
                logger.error(
                    f"Impossible de charger Kokoro : {exc}\n"
                    "Installez 'kokoro' ou 'transformers[torch]'."
                )
                sys.exit(1)

    @staticmethod
    def split_text(text: str, chunk_words: int = 80) -> list[str]:
        sentences = re.split(r'(?<=[.!?…])\s+|\n+', text)
        sentences = [s.strip() for s in sentences if s.strip()]
        chunks: list[str] = []
        current_words = 0
        current_parts: list[str] = []
        for sentence in sentences:
            words = len(sentence.split())
            if current_words + words > chunk_words and current_parts:
                chunks.append(" ".join(current_parts))
                current_parts = []
                current_words = 0
            current_parts.append(sentence)
            current_words += words
        if current_parts:
            chunks.append(" ".join(current_parts))
        return chunks if chunks else [text]

    def _synthesize_segment(self, text: str) -> "np.ndarray":
        import numpy as np  # type: ignore
        if self._backend == "kokoro":
            audio_chunks = []
            for _, _, audio in self._pipeline(text, voice=CONFIG["KOKORO_VOICE"]):
                audio_chunks.append(audio)
            return np.concatenate(audio_chunks) if audio_chunks else np.array([])
        else:
            result = self._pipeline(text)
            return result["audio"].squeeze()

    def generate_mp3(self, text: str, output_path: Path) -> None:
        if self.dry_run:
            logger.info(f"[DRY-RUN] TTS → {output_path}")
            return
        try:
            import numpy as np          # type: ignore
            import soundfile as sf      # type: ignore
            from pydub import AudioSegment  # type: ignore
        except ImportError as exc:
            raise ImportError(
                f"Dépendance manquante pour l'audio : {exc}\n"
                "Installez numpy, soundfile et pydub."
            ) from exc

        output_path.parent.mkdir(parents=True, exist_ok=True)
        chunks = self.split_text(text, CONFIG["TTS_CHUNK_WORDS"])
        logger.debug(f"TTS : {len(chunks)} segments pour {output_path.name}")

        sample_rate = 24000
        segments_wav: list[Path] = []
        tmp_dir = output_path.parent / ".tmp_tts"
        tmp_dir.mkdir(exist_ok=True)

        try:
            for i, chunk in enumerate(chunks):
                audio = self._synthesize_segment(chunk)
                if audio is None or len(audio) == 0:
                    logger.warning(f"Segment {i} vide, ignoré.")
                    continue
                wav_path = tmp_dir / f"{output_path.stem}_{i:04d}.wav"
                sf.write(str(wav_path), audio, sample_rate)
                segments_wav.append(wav_path)

            if not segments_wav:
                raise RuntimeError("Aucun segment audio généré.")

            combined = AudioSegment.empty()
            for wav_path in segments_wav:
                combined += AudioSegment.from_wav(str(wav_path))

            combined.export(
                str(output_path),
                format="mp3",
                bitrate=CONFIG["MP3_BITRATE"],
            )
            logger.debug(
                f"MP3 sauvegardé : {output_path} ({output_path.stat().st_size // 1024} Ko)"
            )
        finally:
            for wav_path in segments_wav:
                wav_path.unlink(missing_ok=True)
            try:
                tmp_dir.rmdir()
            except OSError:
                pass

# ───────────────────────── exploration des fichiers ─────────────────────────

class ChapterTask:
    __slots__ = ("matiere", "chapitre_name", "input_path", "output_dir", "raw_notes")

    def __init__(
        self,
        matiere: str,
        chapitre_name: str,
        input_path: Path,
        output_dir: Path,
    ) -> None:
        self.matiere       = matiere
        self.chapitre_name = chapitre_name
        self.input_path    = input_path
        self.output_dir    = output_dir
        self.raw_notes     = ""

    def load_notes(self) -> None:
        text = self.input_path.read_text(encoding="utf-8", errors="replace")
        max_chars = CONFIG["MAX_INPUT_TOKENS"] * 4
        if len(text) > max_chars:
            text = text[:max_chars] + "\n\n[… contenu tronqué pour respecter la limite de tokens]"
        self.raw_notes = text

    def mp3_path(self) -> Path:
        return self.output_dir / f"{self.chapitre_name}.mp3"

    def txt_path(self) -> Path:
        return self.output_dir / f"{self.chapitre_name}.txt"


def discover_tasks(input_root: Path, output_root: Path) -> tuple[list[ChapterTask], dict[str, Path]]:
    tasks: list[ChapterTask] = []
    matiere_dirs: dict[str, Path] = {}

    if not input_root.exists():
        logger.error(f"Dossier d'entrée introuvable : {input_root}")
        sys.exit(1)

    for matiere_path in sorted(input_root.iterdir()):
        if not matiere_path.is_dir():
            continue
        matiere = matiere_path.name
        out_dir = output_root / matiere
        out_dir.mkdir(parents=True, exist_ok=True)
        matiere_dirs[matiere] = out_dir

        txt_files = sorted(matiere_path.glob("*.txt"))
        if not txt_files:
            logger.warning(f"Aucun fichier .txt dans : {matiere_path}")
            continue

        for txt_file in txt_files:
            chapitre_name = txt_file.stem
            task = ChapterTask(
                matiere=matiere,
                chapitre_name=chapitre_name,
                input_path=txt_file,
                output_dir=out_dir,
            )
            tasks.append(task)

    logger.info(
        f"Découverte : {len(tasks)} chapitres dans {len(matiere_dirs)} matières."
    )
    return tasks, matiere_dirs

# ─────────────────────── traitement d'un chapitre (Phase 1) ──────────────────

def process_chapter(
    task: ChapterTask,
    client: openai.OpenAI,
    tts: KokoroTTS,
    dry_run: bool,
) -> dict:
    start = time.perf_counter()
    label = f"{task.matiere}/{task.chapitre_name}"
    result = {"label": label, "status": "error", "elapsed": 0.0, "error": None}

    try:
        task.load_notes()
        if not task.raw_notes.strip():
            logger.warning(f"[{label}] Fichier vide, ignoré.")
            result["status"] = "skipped"
            return result

        user_prompt = USER_PROMPT_TEMPLATE.format(
            matiere=task.matiere.replace("_", " ").title(),
            chapitre=task.chapitre_name.replace("_", " ").title(),
            notes=task.raw_notes,
        )

        if dry_run:
            generated_text = f"[DRY-RUN] Texte simulé pour {label}."
        else:
            logger.info(f"[{label}] Appel DeepSeek (podcast)…")
            generated_text = call_deepseek_with_retry(
                client, SYSTEM_PROMPT, user_prompt, label=label
            )
            logger.info(f"[{label}] Script généré ({len(generated_text.split())} mots).")

        if CONFIG["SAVE_GENERATED_TEXT"] and not dry_run:
            task.txt_path().write_text(generated_text, encoding="utf-8")

        logger.info(f"[{label}] Synthèse TTS…")
        tts.generate_mp3(generated_text, task.mp3_path())
        logger.success(f"[{label}] ✓ MP3 prêt : {task.mp3_path()}")
        result["status"] = "ok"

    except Exception as exc:
        logger.error(f"[{label}] ÉCHEC : {exc}\n{traceback.format_exc()}")
        result["error"] = str(exc)

    finally:
        result["elapsed"] = time.perf_counter() - start

    return result

# ──────────────────── génération des fichiers de formules ────────────────────

def _matiere_has_formulas(matiere: str, tasks: list[ChapterTask]) -> bool:
    slug = matiere.lower().replace(" ", "_")
    if any(kw in slug for kw in MATIERES_AVEC_FORMULES):
        return True
    math_pattern = re.compile(
        r"[=+\-*/∫∑∏√≈≤≥∀∃∈∉]|formula|formule", re.IGNORECASE
    )
    for task in tasks:
        if task.matiere == matiere and math_pattern.search(task.raw_notes):
            return True
    return False


def process_formulas(
    matiere: str,
    out_dir: Path,
    tasks: list[ChapterTask],
    client: openai.OpenAI,
    tts: KokoroTTS,
    dry_run: bool,
) -> dict:
    label = f"{matiere}/_formules"
    result = {"label": label, "status": "error", "elapsed": 0.0, "error": None}
    start = time.perf_counter()

    try:
        has_formulas = _matiere_has_formulas(matiere, tasks)

        if has_formulas:
            prompt = FORMULAS_PROMPT_TEMPLATE.format(
                matiere=matiere.replace("_", " ").title()
            )
            if dry_run:
                text = f"[DRY-RUN] Formules simulées pour {matiere}."
            else:
                logger.info(f"[{label}] Génération des formules…")
                text = call_deepseek_with_retry(
                    client, SYSTEM_PROMPT, prompt, label=label
                )
        else:
            text = NO_FORMULAS_TEXT
            logger.info(f"[{label}] Pas de formules spécifiques → texte par défaut.")

        mp3_path = out_dir / "_formules.mp3"
        txt_path = out_dir / "_formules.txt"

        if CONFIG["SAVE_GENERATED_TEXT"] and not dry_run:
            txt_path.write_text(text, encoding="utf-8")

        tts.generate_mp3(text, mp3_path)
        logger.success(f"[{label}] ✓ MP3 formules prêt : {mp3_path}")
        result["status"] = "ok"

    except Exception as exc:
        logger.error(f"[{label}] ÉCHEC : {exc}\n{traceback.format_exc()}")
        result["error"] = str(exc)

    finally:
        result["elapsed"] = time.perf_counter() - start

    return result

# ────────────────────────────── pipeline principal ───────────────────────────

def run_pipeline(dry_run: bool = False, skip_note_gen: bool = False, only_note_gen: bool = False) -> None:
    """
    Pipeline complet :
      Phase 0 — Génération des notes de cours manquantes (DeepSeek)
      Phase 1 — Traitement parallèle : notes → podcast → MP3 (TTS)
      Phase 2 — Génération des fichiers de formules
      Phase 3 — Récapitulatif final
    """
    console = Console()
    console.print(Panel.fit(
        "[bold cyan]Générateur de Podcasts Éducatifs[/bold cyan]\n"
        "BTS Génie Logiciel — Programme camerounais\n"
        f"Mode : {'[yellow]DRY-RUN[/yellow]' if dry_run else '[green]PRODUCTION[/green]'}\n"
        f"Phases : {'Notes seulement' if only_note_gen else ('Podcast seulement' if skip_note_gen else 'Notes + Podcast')}",
        border_style="cyan",
    ))

    input_root  = Path(CONFIG["INPUT_ROOT_DIR"])
    output_root = Path(CONFIG["OUTPUT_ROOT_DIR"])
    output_root.mkdir(parents=True, exist_ok=True)
    input_root.mkdir(parents=True, exist_ok=True)

    setup_logging(output_root)

    client = make_deepseek_client()

    # ── Phase 0 : génération des notes manquantes ─────────────────────────
    note_gen_count = 0
    note_gen_errors = 0

    if not skip_note_gen:
        note_gen_count, note_gen_errors = generate_missing_chapters(
            input_root, client, dry_run, console
        )
        console.print(
            f"Phase 0 terminée : [green]{note_gen_count} notes générées[/green], "
            f"[red]{note_gen_errors} erreurs[/red]."
        )

    if only_note_gen:
        console.print("[bold green]--only-note-gen : arrêt après Phase 0.[/bold green]")
        return

    # ── Phase 1 & 2 : podcast ─────────────────────────────────────────────
    tts = KokoroTTS(dry_run=dry_run)

    tasks, matiere_dirs = discover_tasks(input_root, output_root)
    if not tasks:
        logger.warning("Aucun chapitre trouvé après génération. Vérifiez INPUT_ROOT_DIR.")
        return

    # Chapitres dont le MP3 n'existe pas encore (reprise automatique)
    pending = [t for t in tasks if not t.mp3_path().exists()]
    skipped_existing = len(tasks) - len(pending)
    if skipped_existing:
        logger.info(f"{skipped_existing} chapitres déjà traités en MP3 — ignorés.")

    console.print(
        f"\n[bold cyan]Phase 1[/bold cyan] — Conversion en podcast : "
        f"[bold]{len(pending)}[/bold] chapitres à traiter "
        f"([dim]{skipped_existing} déjà prêts[/dim])"
    )

    results: list[dict] = []

    with Progress(
        SpinnerColumn(),
        TextColumn("[progress.description]{task.description}"),
        BarColumn(),
        TaskProgressColumn(),
        TimeElapsedColumn(),
        TimeRemainingColumn(),
        console=console,
    ) as progress:
        task_bar = progress.add_task(
            f"Chapitres podcast ({len(pending)} à traiter)", total=len(pending)
        )
        with ThreadPoolExecutor(max_workers=CONFIG["MAX_WORKERS"]) as executor:
            futures = {
                executor.submit(process_chapter, t, client, tts, dry_run): t
                for t in pending
            }
            for future in as_completed(futures):
                res = future.result()
                results.append(res)
                progress.update(
                    task_bar,
                    advance=1,
                    description=f"{'✓' if res['status'] == 'ok' else '✗'} {res['label']} ({res['elapsed']:.1f}s)",
                )

    # ── Phase 2 : formules ────────────────────────────────────────────────
    for task in tasks:
        if not task.raw_notes:
            try:
                task.load_notes()
            except Exception:
                pass

    formula_results: list[dict] = []
    console.print(f"\n[bold cyan]Phase 2[/bold cyan] — Fichiers de formules…")

    with Progress(
        SpinnerColumn(),
        TextColumn("[progress.description]{task.description}"),
        BarColumn(),
        TaskProgressColumn(),
        console=console,
    ) as progress:
        bar = progress.add_task("Formules", total=len(matiere_dirs))
        for matiere, out_dir in matiere_dirs.items():
            if (out_dir / "_formules.mp3").exists():
                progress.advance(bar)
                continue
            res = process_formulas(matiere, out_dir, tasks, client, tts, dry_run)
            formula_results.append(res)
            progress.update(bar, advance=1, description=matiere)

    # ── Récapitulatif ─────────────────────────────────────────────────────
    all_results = results + formula_results
    ok_count  = sum(1 for r in all_results if r["status"] == "ok")
    err_count = sum(1 for r in all_results if r["status"] == "error")

    table = Table(title="Récapitulatif final — Phase Podcast", show_lines=True)
    table.add_column("Statut",  style="bold", width=8)
    table.add_column("Fichier", style="cyan")
    table.add_column("Durée",   style="yellow", justify="right")

    for r in sorted(all_results, key=lambda x: x["label"]):
        icon = "[green]✓[/green]" if r["status"] == "ok" else "[red]✗[/red]"
        table.add_row(icon, r["label"], f"{r['elapsed']:.1f}s")

    console.print(table)
    console.print(
        f"\n[bold]Phase 0 :[/bold] {note_gen_count} notes créées "
        f"({'DRY-RUN' if dry_run else 'réel'})\n"
        f"[bold green]Podcasts OK :[/bold green] {ok_count}   "
        f"[bold red]Erreurs :[/bold red] {err_count}   "
        f"[dim]Déjà traités :[/dim] {skipped_existing}"
    )
    if err_count:
        console.print(
            f"[yellow]Consultez [bold]{output_root / 'errors.log'}[/bold] "
            "pour le détail des erreurs.[/yellow]"
        )

# ─────────────────────────────── CLI ────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Génère les notes de cours manquantes (Phase 0) puis les convertit "
            "en podcasts MP3 (Phase 1+2)."
        ),
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument(
        "--dry-run", action="store_true",
        help="Simule sans appeler les API ni créer de fichiers audio.",
    )
    parser.add_argument(
        "--skip-note-gen", action="store_true",
        help="Saute la Phase 0 (génération des notes) et va directement au podcast.",
    )
    parser.add_argument(
        "--only-note-gen", action="store_true",
        help="Exécute uniquement la Phase 0 (génération des notes) sans TTS.",
    )
    parser.add_argument(
        "--input", metavar="DIR",
        help=f"Dossier racine des notes (défaut : {CONFIG['INPUT_ROOT_DIR']})",
    )
    parser.add_argument(
        "--output", metavar="DIR",
        help=f"Dossier de sortie (défaut : {CONFIG['OUTPUT_ROOT_DIR']})",
    )
    parser.add_argument(
        "--workers", type=int, metavar="N",
        help=f"Nombre de workers parallèles pour le podcast (défaut : {CONFIG['MAX_WORKERS']})",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    if args.input:
        CONFIG["INPUT_ROOT_DIR"] = args.input
    if args.output:
        CONFIG["OUTPUT_ROOT_DIR"] = args.output
    if args.workers:
        CONFIG["MAX_WORKERS"] = args.workers

    run_pipeline(
        dry_run=args.dry_run,
        skip_note_gen=args.skip_note_gen,
        only_note_gen=args.only_note_gen,
    )


if __name__ == "__main__":
    main()
