
-- Création de la base de données
CREATE DATABASE IF NOT EXISTS orthanc_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orthanc_app;

-- Table des hôpitaux
CREATE TABLE IF NOT EXISTS hopitaux (
    id_hopital  INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    region VARCHAR(100),
    adresse TEXT
);

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'medecin', 'infirmier', 'epidemiologiste') ,
    -- statut VARCHAR(50) CHECK (statut IN ('en_attente', 'approuve', 'rejete')),
    statut ENUM('en_attente', 'approuve', 'rejete') NOT NULL DEFAULT 'approuve'
    -- hopital_id INT,
    -- FOREIGN KEY (hopital_id) REFERENCES hopitaux(id_hopital)
);

-- Table des patients
CREATE TABLE IF NOT EXISTS patients (
    id_patient  INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    sexe ENUM('Homme', 'Femme'),
    date_naissance DATE,
    identifiant_orthanc VARCHAR(255) UNIQUE,
    hopital_id INT,
    FOREIGN KEY (hopital_id) REFERENCES hopitaux(id_hopital)
);

-- Table des fichiers non DICOM
CREATE TABLE IF NOT EXISTS fichiers_non_dicom (
    id_Non_dicom INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    nom_fichier VARCHAR(255),
    type_fichier VARCHAR(50),
    chemin_stockage TEXT,
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    ajoute_par INT,
    FOREIGN KEY (patient_id) REFERENCES patients(id_patient),
    FOREIGN KEY (ajoute_par) REFERENCES utilisateurs(id_user)
);
