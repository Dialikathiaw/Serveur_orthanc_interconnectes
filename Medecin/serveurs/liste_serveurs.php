<?php
header('Content-Type: application/json');

// Connexion à la base de données
$host = 'localhost';
$db   = 'orthanc_app';
$user = 'root';
$pass = ''; // Mets ton mot de passe ici si nécessaire
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Requête pour récupérer les serveurs
    $sql = "SELECT nom, ville, url, actif, 
                   IF(derniere_synchro IS NOT NULL, 
                      CONCAT('il y a ', TIMESTAMPDIFF(MINUTE, derniere_synchro, NOW()), ' minutes'), 
                      NULL
                   ) AS derniere_synchro 
            FROM serveurs_orthanc";

    $stmt = $pdo->query($sql);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['erreur' => 'Connexion échouée : ' . $e->getMessage()]);
}
?>
