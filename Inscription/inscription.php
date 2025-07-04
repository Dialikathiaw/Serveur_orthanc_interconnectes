<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'projet_mbacke';
$username = 'mbacke_user';
$password = 'passer123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
// Récupération des données du formulaire
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['email']; // Utilisé comme login
$mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT); // Hachage du mot de passe
$profession = $_POST['profession'];
$numero_carte_etudiant = isset($_POST['numero_carte_etudiant']) ? $_POST['numero_carte_etudiant'] : null;
$classe_etudiant = isset($_POST['classe_etudiant']) ? $_POST['classe_etudiant'] : null;

try {
    // Démarrage d'une transaction
    $pdo->beginTransaction();

    // Insertion dans la table `compte`
    $stmtCompte = $pdo->prepare("INSERT INTO compte (login, mot_de_passe) VALUES (:login, :mot_de_passe)");
    $stmtCompte->execute([
        ':login' => $email,
        ':mot_de_passe' => $mot_de_passe
    ]);
    $id_compte = $pdo->lastInsertId(); // Récupération de l'ID inséré

    // Vérification de la profession
    if ($profession === 'Enseignant') {
        // Insertion dans la table `professeur`
        $stmtProfesseur = $pdo->prepare("INSERT INTO professeur (id_compte, prenom, nom, Profession) VALUES (:id_compte, :prenom, :nom, :profession)");
        $stmtProfesseur->execute([
            ':id_compte' => $id_compte,
            ':prenom' => $prenom,
            ':nom' => $nom,
            ':profession' => $profession
        ]);
    } elseif ($profession === 'Etudiant' && $numero_carte_etudiant) {
        // Insertion dans la table `etudiant` avec la classe
        $stmtEtudiant = $pdo->prepare("INSERT INTO etudiant (id_compte, prenom, nom, numero_carte_etudiant, classe_etudiant) VALUES (:id_compte, :prenom, :nom, :numero_carte_etudiant, :classe_etudiant)");
        $stmtEtudiant->execute([
            ':id_compte' => $id_compte,
            ':prenom' => $prenom,
            ':nom' => $nom,
            ':numero_carte_etudiant' => $numero_carte_etudiant,
            ':classe_etudiant' => $classe_etudiant
        ]);
    } else {
        throw new Exception("Les données pour l'insertion ne sont pas valides.");
    }

    // Validation de la transaction
    $pdo->commit();
    echo "Inscription réussie !";
    header('Location: ../Connexion/connexion.html'); // Redirection vers une page de confirmation (vous pouvez la créer).
    exit;
} catch (Exception $e) {
    // Annulation de la transaction en cas d'erreur
    $pdo->rollBack();
    echo "Erreur : " . $e->getMessage();
}
?>