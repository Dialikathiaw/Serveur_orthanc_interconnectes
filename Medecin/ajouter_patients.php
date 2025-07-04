<?php
$conn = mysqli_connect("localhost", "user_orthanc", "passer123", "orthanc_app");
if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

// Récupération des données
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$sexe = $_POST['sexe'];
$date_naissance = $_POST['date_naissance'];
$identifiant_orthanc = $_POST['identifiant_orthanc'];
$id_medecin = $_POST['id_medecin'];

$hopital_nom = trim($_POST['hopital_nom']);
$hopital_region = trim($_POST['hopital_region']);
$hopital_adresse = trim($_POST['hopital_adresse']);

// Étape 1 : Ajouter l’hôpital
$stmt_h = mysqli_prepare($conn, "INSERT INTO hopitaux (nom, region, adresse) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt_h, "sss", $hopital_nom, $hopital_region, $hopital_adresse);

if (!mysqli_stmt_execute($stmt_h)) {
    die("Erreur lors de l'ajout de l’hôpital : " . mysqli_error($conn));
}
$hopital_id = mysqli_insert_id($conn);

// Étape 2 : Ajouter le patient lié à cet hôpital
$stmt_p = mysqli_prepare($conn, "INSERT INTO patients (nom, prenom, sexe, date_naissance, identifiant_orthanc, hopital_id, id_medecin) VALUES (?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt_p, "ssssssi", $nom, $prenom, $sexe, $date_naissance, $identifiant_orthanc, $hopital_id, $id_medecin);

if (!mysqli_stmt_execute($stmt_p)) {
    die("Erreur lors de l'ajout du patient : " . mysqli_error($conn));
}

//  Redirection
header("Location: patients.php?success=1");
exit;
?>
