-- Script pour mettre à jour la structure de la base de données
-- Ajouter les colonnes manquantes si elles n'existent pas

-- Ajouter la colonne id si elle n'existe pas (clé primaire auto-incrémentée)
ALTER TABLE serveurs_orthanc 
ADD COLUMN IF NOT EXISTS id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- Ajouter la colonne date_creation si elle n'existe pas
ALTER TABLE serveurs_orthanc 
ADD COLUMN IF NOT EXISTS date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Ajouter la colonne statut_ping pour suivre l'état des vérifications
ALTER TABLE serveurs_orthanc 
ADD COLUMN IF NOT EXISTS statut_ping ENUM('OK', 'ERREUR', 'INCONNU') DEFAULT 'INCONNU';

-- Créer un index sur le champ actif pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_actif ON serveurs_orthanc(actif);

-- Créer un index sur le champ derniere_synchro
CREATE INDEX IF NOT EXISTS idx_derniere_synchro ON serveurs_orthanc(derniere_synchro);

-- Afficher la structure finale de la table
DESCRIBE serveurs_orthanc;
