<?php
// Désactiver l'affichage des erreurs PHP dans la réponse JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Connexion à la base de données
$host = 'localhost';
$db   = 'orthanc_app';
$user = 'root';
$pass = ''; // Mets ton mot de passe ici si nécessaire
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['erreur' => 'Connexion échouée : ' . $e->getMessage()]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'lister':
        listerServeurs($pdo);
        break;
    case 'ajouter':
        ajouterServeur($pdo);
        break;
    case 'modifier':
        modifierServeur($pdo);
        break;
    case 'supprimer':
        supprimerServeur($pdo);
        break;
    case 'obtenir':
        obtenirServeur($pdo);
        break;
    default:
        echo json_encode(['erreur' => 'Action non reconnue']);
}

function listerServeurs($pdo) {
    try {
        $sql = "SELECT id, nom, ville, url, actif, 
                       IF(derniere_synchro IS NOT NULL, 
                          DATE_FORMAT(derniere_synchro, '%d/%m/%Y %H:%i'), 
                          NULL
                       ) AS derniere_synchro 
                FROM serveurs_orthanc 
                ORDER BY nom";
        $stmt = $pdo->query($sql);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultats, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['erreur' => 'Erreur lors de la récupération : ' . $e->getMessage()]);
    }
}

function ajouterServeur($pdo) {
    try {
        $nom = trim($_POST['nom'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $actif = (int)($_POST['actif'] ?? 1);
        
        // Validation des données
        if (empty($nom) || empty($ville) || empty($url)) {
            echo json_encode(['erreur' => 'Tous les champs obligatoires doivent être remplis']);
            return;
        }
        
        // Vérifier si l'URL est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['erreur' => 'URL invalide']);
            return;
        }
        
        // Vérifier si le nom existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM serveurs_orthanc WHERE nom = ?");
        $stmt->execute([$nom]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['erreur' => 'Un serveur avec ce nom existe déjà']);
            return;
        }
        
        // Insérer le nouveau serveur
        $sql = "INSERT INTO serveurs_orthanc (nom, ville, url, actif, date_creation) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$nom, $ville, $url, $actif]);
        
        if ($result) {
            echo json_encode([
                'succes' => true, 
                'message' => 'Serveur ajouté avec succès',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['erreur' => 'Erreur lors de l\'insertion']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['erreur' => 'Erreur lors de l\'ajout : ' . $e->getMessage()]);
    }
}

function modifierServeur($pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $actif = (int)($_POST['actif'] ?? 1);
        
        if ($id <= 0 || empty($nom) || empty($ville) || empty($url)) {
            echo json_encode(['erreur' => 'Données invalides']);
            return;
        }
        
        // Vérifier si l'URL est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['erreur' => 'URL invalide']);
            return;
        }
        
        // Vérifier si le nom existe déjà (sauf pour ce serveur)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM serveurs_orthanc WHERE nom = ? AND id != ?");
        $stmt->execute([$nom, $id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['erreur' => 'Un autre serveur avec ce nom existe déjà']);
            return;
        }
        
        $sql = "UPDATE serveurs_orthanc 
                SET nom = ?, ville = ?, url = ?, actif = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$nom, $ville, $url, $actif, $id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['succes' => true, 'message' => 'Serveur modifié avec succès']);
        } else {
            echo json_encode(['erreur' => 'Aucune modification effectuée ou serveur introuvable']);
        }
    } catch (PDOException $e) {
        echo json_encode(['erreur' => 'Erreur lors de la modification : ' . $e->getMessage()]);
    }
}

function supprimerServeur($pdo) {
    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['erreur' => 'ID invalide']);
            return;
        }
        
        $sql = "DELETE FROM serveurs_orthanc WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['succes' => true, 'message' => 'Serveur supprimé avec succès']);
        } else {
            echo json_encode(['erreur' => 'Serveur introuvable']);
        }
    } catch (PDOException $e) {
        echo json_encode(['erreur' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
    }
}

function obtenirServeur($pdo) {
    try {
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['erreur' => 'ID invalide']);
            return;
        }
        
        $sql = "SELECT id, nom, ville, url, actif FROM serveurs_orthanc WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $serveur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($serveur) {
            echo json_encode($serveur, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['erreur' => 'Serveur introuvable']);
        }
    } catch (PDOException $e) {
        echo json_encode(['erreur' => 'Erreur lors de la récupération : ' . $e->getMessage()]);
    }
}
?>
