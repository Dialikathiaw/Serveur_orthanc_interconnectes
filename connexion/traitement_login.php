<?php
session_start();

// Inclure l'autoloader de Composer pour Google Authenticator
require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Configuration de la base de données
$host = 'localhost';
$dbname = 'orthanc_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation des champs
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header("Location: connexion.html");
        exit();
    }

    try {
        // Récupération de l'utilisateur avec le champ secret_mfa
        $stmt = $pdo->prepare("
            SELECT id_user, prenom, nom, email, role, mot_de_passe, statut, secret_mfa 
            FROM utilisateurs 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            
            // VÉRIFICATION CRUCIALE : Statut d'approbation
            if ($user['statut'] !== 'approuve') {
                $_SESSION['error'] = "Votre compte n'est pas encore approuvé par un administrateur. Veuillez patienter.";
                header("Location: connexion.html");
                exit();
            }

            // Stocker les informations utilisateur temporairement
            $_SESSION['temp_user_id'] = $user['id_user'];
            $_SESSION['temp_user_email'] = $user['email'];
            $_SESSION['temp_user_prenom'] = $user['prenom'];
            $_SESSION['temp_user_nom'] = $user['nom'];
            $_SESSION['temp_user_profession'] = $user['role'];
            $_SESSION['temp_user_statut'] = $user['statut'];

            // Vérifier si l'utilisateur a activé le MFA
            if (!empty($user['secret_mfa'])) {
                // MFA activé, rediriger vers la page de vérification OTP
                $_SESSION['temp_mfa_secret'] = $user['secret_mfa'];
                header("Location: verification_otp.php");
                exit();
            } else {
                // Pas de MFA, connexion directe
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_profession'] = $user['role'];
                $_SESSION['user_statut'] = $user['statut'];

                // Nettoyer les variables temporaires
                unset($_SESSION['temp_user_id'], $_SESSION['temp_user_email'], $_SESSION['temp_user_prenom'], 
                      $_SESSION['temp_user_nom'], $_SESSION['temp_user_profession'], $_SESSION['temp_user_statut']);

                // REDIRECTION SELON LE RÔLE
                switch ($user['role']) {
                    case 'admin':
                        header("Location: ../Administrateur/interface_admin.html");
                        break;
                        
                    case 'medecin':
                        header("Location: ../Medecin/index.php");
                        break;
                        
                    case 'infirmier':
                        header("Location: ../Medecin/index.php");
                        break;
                        
                    case 'epidemiologiste':
                        header("Location: ../Medecin/index.php");
                        break;
                        
                    default:
                        header("Location: ../Medecin/index.php");
                        break;
                }
                exit();
            }

        } else {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            header("Location: connexion.html");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la connexion : " . $e->getMessage();
        header("Location: connexion.html");
        exit();
    }
} else {
    // Redirection si accès direct
    header("Location: connexion.html");
    exit();
}
?>
