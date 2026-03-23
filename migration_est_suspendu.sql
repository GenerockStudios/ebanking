-- ============================================================
-- MIGRATION : Ajout de la colonne est_suspendu a la table Comptes
-- A executer UNE SEULE FOIS dans phpMyAdmin ou MySQL CLI.
-- ============================================================

-- 1. Ajouter la colonne est_suspendu (0 = actif, 1 = suspendu)
ALTER TABLE `Comptes`
    ADD COLUMN `est_suspendu` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0=Compte actif, 1=Compte suspendu (retraits bloques)';

-- 2. Verification : tous les comptes existants sont actifs par defaut (DEFAULT 0)
-- UPDATE `Comptes` SET est_suspendu = 0; -- Facultatif, car DEFAULT gere cela.

-- 3. Index optionnel pour performance des requetes de filtrage
CREATE INDEX idx_comptes_suspendu ON `Comptes` (`est_suspendu`);

-- ============================================================
-- VERIFICATION POST-MIGRATION
-- ============================================================
-- SELECT compte_id, numero_compte, est_suspendu FROM Comptes LIMIT 10;
