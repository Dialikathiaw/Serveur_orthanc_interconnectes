<?php
session_start();

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
    // Récupération et validation des données
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profession = trim($_POST['profession'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des champs
    if (empty($prenom) || empty($nom) || empty($email) || empty($profession) || empty($password)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: register.html");
        exit();
    }

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format d'email invalide.";
        header("Location: register.html");
        exit();
    }

    // Validation des mots de passe
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header("Location: register.html");
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
        header("Location: register.html");
        exit();
    }

    // Vérifier si l'email existe déjà dans la table utilisateurs
    $stmt = $pdo->prepare("SELECT id_user FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Cet email est déjà utilisé.";
        header("Location: register.html");
        exit();
    }

    // Hachage du mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Insertion du nouvel utilisateur avec statut 'en_attente' dans la table utilisateurs
        $stmt = $pdo->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, role, mot_de_passe, statut) 
            VALUES (?, ?, ?, ?, ?, 'en_attente')
        ");
        
        $stmt->execute([$nom, $prenom, $email, $profession, $hashed_password]);

        // Redirection vers la page d'attente (PAS vers la connexion)
        header("Location: attente.html");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'inscription : " . $e->getMessage();
        header("Location: register.html");
        exit();
    }
} else {
    // Redirection si accès direct
    header("Location: register.html");
    exit();
}
?>
