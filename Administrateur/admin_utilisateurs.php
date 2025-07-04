<!doctype html>
<html class="no-js" lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Gestion des utilisateurs, administrateur, MediPlus">
    <meta name="description" content="Liste des utilisateurs en attente de validation par l'administrateur.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestion des Utilisateurs - MediPlus</title>

    <link rel="icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Medecin/css/bootstrap.min.css">
    <link rel="stylesheet" href="../Medecin/css/font-awesome.min.css">
    <link rel="stylesheet" href="../Medecin/css/icofont.css">
    <link rel="stylesheet" href="../Medecin/style.css">
</head>
<body>

<!-- Header Area -->
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
											<li class="active"><a href="admin_utilisateurs.php">Gestions utilisateurs</a></li>
											<li><a href="../Medecin/orthanc.html">Configuration des serveurs orthanc</a></li>
											<li><a href="../Medecin/fichiers.php">Gerer les fichiers non DICOM</a></li>
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
<!-- End Header Area -->

<!-- Bannière -->
<section class="page-banner" style="background: url('../Medecin/img/fff.jpg') no-repeat center center/cover; height: 85vh; display: flex; align-items: center;">
    <div class="container">
        <div class="text-center text-white">
            <h1 style="color : blue ">Gestion des Utilisateurs</h1>
            <p style="font-size: 20px;color:blue;">Liste des comptes utilisateurs en attente ou actifs avec possibilité d'action.</p>
        </div>
    </div>
</section>

<!-- Section utilisateurs -->
<section class="services section">
    <div class="container">
        <div class="section-title">
            <br/><br/>
            <h2 style="text-align:center;color:black;">Liste des Utilisateurs</h2>
            <p style="text-align:center;color:black;">Voici tous les utilisateurs de la plateforme, avec leur statut actuel.</p>
        </div>
        <div class="row">
           <?php
$conn = mysqli_connect("localhost", "user_orthanc", "passer123", "orthanc_app");

if (!$conn) {
    echo "<p class='text-danger'>Erreur de connexion à la base de données.</p>";
} else {
    // --- Liste 1 : Utilisateurs EN ATTENTE ---
    echo '<div class="col-12"><h4 style="margin-top:30px; color:blue;">Utilisateurs en attente</h4><div class="row">';
    $sql_attente = "SELECT id_user, nom, prenom, email, role, statut FROM utilisateurs WHERE statut = 'en_attente'";
    $result_attente = mysqli_query($conn, $sql_attente);

    if (mysqli_num_rows($result_attente) > 0) {
        while ($row = mysqli_fetch_assoc($result_attente)) {
            echo '<div class="col-lg-4 col-md-6 col-12">';
            echo '<div class="single-service">';
            echo '<i class="icofont-user-alt-3"></i>';
            echo '<h4>' . htmlspecialchars($row['prenom'] . ' ' . $row['nom']) . '</h4>';
            echo '<p>Email : ' . htmlspecialchars($row['email']) . '</p>';
            echo '<p>Profession : ' . htmlspecialchars($row['role']) . '</p>';
            echo '<p>Statut : <strong>' . htmlspecialchars($row['statut']) . '</strong></p>';
            echo '<form action="traiter_utilisateur.php" method="POST" class="d-flex justify-content-between">';
            echo '<input type="hidden" name="id_utilisateur" value="' . $row['id_user'] . '">';
            echo '<button name="action" value="accepter" class="btn btn-success btn-sm">Accepter</button>';
            echo '<button name="action" value="rejeter" class="btn btn-danger btn-sm">Rejeter</button>';
            echo '</form>';
            echo '</div></div>';
        }
    } else {
        echo "<p class='text-muted'>Aucun utilisateur en attente.</p>";
    }
    echo '</div></div>'; // fin row attente

    // --- Liste 2 : Tableau utilisateurs NON admin ---
    echo '<div class="col-12 mt-5"><h4 style="color:blue;text-align:center;">Listes des utilisateurs</h4>';
    $sql_all = "SELECT id_user, nom, prenom, email, role, statut FROM utilisateurs WHERE role != 'administrateur'";
    $result_all = mysqli_query($conn, $sql_all);

    if (mysqli_num_rows($result_all) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered">';
        echo '<thead class="thead-dark"><tr>
                <th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th>
              </tr></thead><tbody>';

        while ($row = mysqli_fetch_assoc($result_all)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($row['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "<td>" . htmlspecialchars($row['statut']) . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table></div>";
    } else {
        echo "<p class='text-muted'>Aucun utilisateur trouvé.</p>";
    }

    echo '</div>'; // fin tableau

    mysqli_close($conn);
}
?>


        </div>
    </div>
</section>

<!-- JS -->
<script src="../Medecin/js/jquery.min.js"></script>
<script src="../Medecin/js/bootstrap.min.js"></script>
</body>
</html>
