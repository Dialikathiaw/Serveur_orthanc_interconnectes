<?php
session_start();

// Vérification de rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit;
}

$conn = mysqli_connect("localhost", "user_orthanc", "passer123", "orthanc_app");
if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

// Requête pour récupérer tous les fichiers avec jointure sur patient et utilisateur
$sql = "SELECT f.*, 
               p.nom AS nom_patient, p.prenom AS prenom_patient, p.sexe, p.date_naissance, 
               u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur 
        FROM fichiers_non_dicom f
        LEFT JOIN patients p ON f.patient_id = p.id_patient
        LEFT JOIN utilisateurs u ON f.ajoute_par = u.id_user
        ORDER BY f.date_upload DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Erreur SQL : " . mysqli_error($conn));
}
?>
<!doctype html>
<html class="no-js" lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Gestion des fichiers, administrateur, MediPlus">
    <meta name="description" content="Liste des fichiers médicaux non DICOM transmis par les hôpitaux.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestion des Fichiers Non DICOM - MediPlus</title>

    <link rel="icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Medecin/css/bootstrap.min.css">
    <link rel="stylesheet" href="../Medecin/css/font-awesome.min.css">
    <link rel="stylesheet" href="../Medecin/css/icofont.css">
    <link rel="stylesheet" href="../Medecin/style.css">
</head>
<body>

<!-- Header -->

    <header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-5 col-12">
                    <ul class="top-link">
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="#">À propos</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-6 col-md-7 col-12">
                    <ul class="top-contact">
                        <li><i class="fa fa-phone"></i> +221 33 123 45 67</li>
                        <li><i class="fa fa-envelope"></i> <a href="mailto:support@plateforme-medicale.sn">support@plateforme-medicale.sn</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="header-inner">
        <div class="container">
            <div class="inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-12">
                        <div class="logo">
                            <a href="#"><img src="../Medecin/img/logo.png" alt="Plateforme Médicale"></a>
                        </div>
                        <div class="mobile-nav"></div>
                    </div>
                    <div class="col-lg-7 col-md-9 col-12">
                        <div class="main-menu">
                            <nav class="navigation">
                                <ul class="nav menu">
                                    

                                    <li ><a href="./interface_admin.html">Accueil</a></li>
											<li ><a href="admin_utilisateurs.php">Gestions utilisateurs</a></li>
											<li><a href="#">Configuration des serveurs orthanc</a></li>
											<li class="active"><a href="#">Gerer les fichiers non DICOM</a></li>
											<li><a href="../Medecin/statistiques.php">Superviser les activités </a></li>
											<li><a href="../Medecin/contact.html">Contact</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <div class="col-lg-2 col-12">
                        <div class="get-quote">
                            <a href="dashboard.html" class="btn">Accéder</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>


<!-- Bannière -->
<section class="page-banner" style="background: url('../Medecin/img/slider2.jpg') no-repeat center center/cover; height: 60vh; display: flex; align-items: center;">
    <div class="container">
        <div class="text-center text-white">
            <h1 style="color : blue">Gestion des Fichiers Non DICOM</h1>
            <p style="font-size: 20px; color: blue;">Accédez, visualisez et téléchargez les fichiers PDF, Word et Excel associés aux patients.</p>
        </div>
    </div>
</section>

<!-- Liste des fichiers -->
<section class="services section">
    <div class="container mt-5">
        <div class="section-title">
            <h2 style="text-align:center; color:black;">Liste des Documents Disponibles</h2>
            <p style="text-align:center; color:black;">Voici la liste des fichiers médicaux transmis par les différents services hospitaliers.</p>
        </div>

        <?php if (isset($_GET['delete']) && $_GET['delete'] === 'success') : ?>
            <div class="alert alert-success">Fichier supprimé avec succès.</div>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Nom du Fichier</th>
                    <th>Type</th>
                    <th>Patient</th>
                    <th>Sexe / Naissance</th>
                    <th>Ajouté par</th>
                    <th>Date d'Upload</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['nom_fichier']) ?></td>
                    <td><?= htmlspecialchars($row['type_fichier']) ?></td>
                    <td><?= htmlspecialchars($row['nom_patient'] . ' ' . $row['prenom_patient']) ?></td>
                    <td><?= htmlspecialchars($row['sexe'] . ' / ' . $row['date_naissance']) ?></td>
                    <td><?= htmlspecialchars($row['nom_utilisateur'] . ' ' . $row['prenom_utilisateur']) ?></td>
                    <td><?= htmlspecialchars($row['date_upload']) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($row['chemin_stockage']) ?>" class="btn btn-sm btn-primary" target="_blank">Télécharger</a>
                        <a href="supprimer_fichier.php?id=<?= $row['id_Non_dicom'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- JS -->
<script src="../Medecin/js/jquery.min.js"></script>
<script src="../Medecin/js/bootstrap.min.js"></script>
</body>
</html>
