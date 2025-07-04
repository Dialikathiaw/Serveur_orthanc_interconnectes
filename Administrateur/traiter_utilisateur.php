<?php
// Connexion à la base de données
$conn = mysqli_connect("localhost", "user_orthanc", "passer123", "orthanc_app");

if (!$conn) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}

// Vérifie si les données ont été envoyées par le formulaire
if (isset($_POST['id_utilisateur']) && isset($_POST['action'])) {
    $id = intval($_POST['id_utilisateur']);
    $action = $_POST['action'];

    // Déterminer le nouveau statut
    if ($action === 'accepter') {
        $nouveau_statut = 'approuve';
    } elseif ($action === 'rejeter') {
        $nouveau_statut = 'rejete';
    } else {
        die("Action non reconnue.");
    }

    // Préparation et exécution de la requête
    $sql = "UPDATE utilisateurs SET statut = ? WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $nouveau_statut, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Erreur lors de la préparation de la requête : " . mysqli_error($conn));
    }

    // Redirection vers la page admin
    header("Location: admin_utilisateurs.php");
    exit;
} else {
    die("Données invalides.");
}

mysqli_close($conn);
?>
