<?php
session_start();

// Désactiver l'affichage des erreurs en production
error_reporting(0);
ini_set('display_errors', 0);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion/connexion.html');
    exit();
}

// Connexion à la base de données (paramètres unifiés)
$host = 'localhost';
$dbname = 'orthanc_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT nom, prenom, role FROM utilisateurs WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: ../connexion/connexion.html');
        exit();
    }
    
} catch(PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    $user = ['nom' => 'Test', 'prenom' => 'User', 'role' => 'medecin'];
}

// Traitement de l'ajout de patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_patient') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $sexe = $_POST['sexe'];
        $date_naissance = $_POST['date_naissance'];
        $hopital_id = $_POST['hopital_id'];
        
        // Récupérer le nom de l'hôpital pour générer l'ID Orthanc
        $stmt = $pdo->prepare("SELECT nom FROM serveurs_orthanc WHERE id = ?");
        $stmt->execute([$hopital_id]);
        $hopital = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$hopital) {
            throw new Exception("Hôpital non trouvé");
        }
        
        // Générer l'ID Orthanc automatiquement : prenom_nom_hopital_orthanc
        $hopital_clean = strtolower(str_replace([' ', '-', "'"], '_', $hopital['nom']));
        $prenom_clean = strtolower(str_replace([' ', '-', "'"], '_', $prenom));
        $nom_clean = strtolower(str_replace([' ', '-', "'"], '_', $nom));
        $identifiant_orthanc = $prenom_clean . '_' . $nom_clean . '_' . $hopital_clean . '_orthanc';
        
        // Insérer le patient
        $stmt = $pdo->prepare("INSERT INTO patients (nom, prenom, sexe, date_naissance, identifiant_orthanc, hopital_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $sexe, $date_naissance, $identifiant_orthanc, $hopital_id]);
        
        $response['success'] = true;
        $response['message'] = 'Patient ajouté avec succès !';
        $response['identifiant_orthanc'] = $identifiant_orthanc;
        
    } catch (Exception $e) {
        $response['message'] = 'Erreur : ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fonction pour récupérer les patients
function getPatients($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.*, s.nom AS hopital_nom, s.ville AS region 
                            FROM patients p 
                            LEFT JOIN serveurs_orthanc s ON p.hopital_id = s.id 
                            ORDER BY p.nom, p.prenom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur getPatients: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les hôpitaux depuis serveurs_orthanc
function getHopitaux($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nom, ville FROM serveurs_orthanc WHERE actif = 1 ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur getHopitaux: " . $e->getMessage());
        return [];
    }
}

// Récupérer les données
$patients = getPatients($pdo);
$hopitaux = getHopitaux($pdo);
?>

<!doctype html>
<html class="no-js" lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Gestion patients, dossiers médicaux, hôpital">
    <meta name="description" content="Gestion des patients et de leurs dossiers médicaux.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestion des Patients - Plateforme Médicale</title>
    
    <link rel="icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/icofont.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        /* Styles identiques à index.php pour le header */
        .user-info-section {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 5px;
            padding: 5px 0;
        }

        .user-details {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(26, 118, 209, 0.1);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid #1A76D1;
        }

        .user-details i {
            color: #1A76D1;
            font-size: 16px;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .user-role {
            background: #1A76D1;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 500;
        }

        .logout-btn {
            background: #dc3545;
            color: white !important;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: #c82333;
            color: white !important;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .user-info-section {
                flex-direction: column;
                gap: 8px;
                align-items: flex-end;
            }
            
            .user-details {
                font-size: 12px;
            }
            
            .logout-btn {
                font-size: 11px;
                padding: 4px 8px;
            }
        }

        /* Ensure topbar layout */
        .topbar .top-contact {
            margin-bottom: 0;
        }

        .topbar .container .row .col-lg-6:last-child {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        /* Banner avec meilleure visibilité */
        .page-banner {
            background: linear-gradient(135deg, rgba(26, 118, 209, 0.95), rgba(52, 152, 219, 0.9)), url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-S8u7SepNQJN5ZFvZBov3vFVHl3j17L.png') no-repeat center center/cover;
            height: 85vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .page-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
        }

        .page-banner .container {
            position: relative;
            z-index: 3;
        }

        .banner-content {
            text-align: left;
            color: white;
            max-width: 60%;
        }

        .banner-title {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 25px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        .banner-subtitle {
            font-size: 22px;
            opacity: 0.95;
            margin-bottom: 30px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        .doctor-image {
            position: absolute;
            right: 8%;
            top: 50%;
            transform: translateY(-50%);
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-S8u7SepNQJN5ZFvZBov3vFVHl3j17L.png') no-repeat center center/cover;
            border: 6px solid rgba(255,255,255,0.8);
            animation: float 6s ease-in-out infinite, pulse 4s ease-in-out infinite alternate;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            z-index: 2;
        }

        @keyframes titleGlow {
            0% { text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 20px rgba(255,255,255,0.3); }
            100% { text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 30px rgba(255,255,255,0.6), 0 0 40px rgba(26, 118, 209, 0.4); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(-50%) translateX(0px); }
            50% { transform: translateY(-50%) translateX(-10px); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 0 0 0 rgba(26, 118, 209, 0.7); }
            100% { box-shadow: 0 25px 80px rgba(0,0,0,0.4), 0 0 0 20px rgba(26, 118, 209, 0); }
        }

        /* Section patients */
        .patients-section {
            padding: 60px 0;
            background: #f8f9fa;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .section-subtitle {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .patients-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .btn-add-patient {
            background: linear-gradient(45deg, #1A76D1, #4CAF50);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .btn-add-patient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 118, 209, 0.3);
            color: white;
        }

        .patients-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .patients-table thead {
            background: linear-gradient(45deg, #1A76D1, #4CAF50);
        }

        .patients-table thead th {
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
            font-size: 14px;
        }

        .patients-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .patients-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.3s ease;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #1A76D1, #4CAF50);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }

        .patient-info {
            display: flex;
            align-items: center;
        }

        .patient-name {
            font-weight: 600;
            color: #333;
        }

        .patient-id {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .gender-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .gender-badge.homme {
            background: #e3f2fd;
            color: #1976d2;
        }

        .gender-badge.femme {
            background: #fce4ec;
            color: #c2185b;
        }

        .hospital-badge {
            background: #f0f8ff;
            color: #1A76D1;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Modal styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: linear-gradient(45deg, #1A76D1, #4CAF50);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding: 20px 30px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 20px;
        }

        .close {
            color: white;
            opacity: 0.8;
            font-size: 28px;
        }

        .close:hover {
            color: white;
            opacity: 1;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #1A76D1;
            box-shadow: 0 0 0 3px rgba(26, 118, 209, 0.1);
        }

        .btn-primary {
            background: linear-gradient(45deg, #1A76D1, #4CAF50);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 118, 209, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .no-patients {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-patients i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .banner-title {
                font-size: 32px;
            }
            
            .banner-subtitle {
                font-size: 16px;
            }
            
            .doctor-image {
                display: none;
            }
            
            .patients-table {
                font-size: 12px;
            }
            
            .patients-table thead th,
            .patients-table tbody td {
                padding: 10px 8px;
            }
        }
    </style>
</head>

<body>
    <!-- Header Area -->
    <header class="header">
        <!-- Topbar -->
        <div class="topbar">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-5 col-12">
                        <!-- Quick Links -->
                        <ul class="top-link">
                            <li><a href="index.php">Accueil</a></li>
                            <li><a href="contact.html">Contact</a></li>
                            <li><a href="#">À propos</a></li>
                            <li><a href="#">FAQ</a></li>
                        </ul>
                        <!-- End Quick Links -->
                    </div>
                    <div class="col-lg-6 col-md-7 col-12">
                        <!-- Top Contact Info -->
                        <ul class="top-contact">
                            <li><i class="fa fa-phone"></i> +221 33 123 45 67</li>
                            <li><i class="fa fa-envelope"></i> <a href="mailto:support@plateforme-medicale.sn">support@plateforme-medicale.sn</a></li>
                        </ul>
                        <!-- User Info Section -->
                        <div class="user-info-section">
                            <div class="user-details">
                                <i class="fa fa-user-circle"></i>
                                <span class="user-name"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
                                <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
                            </div>
                            <a href="logout.php" class="logout-btn" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                                <i class="fa fa-sign-out"></i> Déconnexion
                            </a>
                        </div>
                        <!-- End Top Contact Info -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Topbar -->
        
        <div class="header-inner">
            <div class="container">
                <div class="inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-12">
                            <div class="logo">
                                <a href="index.php"><img src="img/logo.png" alt="Plateforme Médicale"></a>
                            </div>
                            <div class="mobile-nav"></div>
                        </div>
                        <div class="col-lg-7 col-md-9 col-12">
                            <div class="main-menu">
                                <nav class="navigation">
                                    <ul class="nav menu">
                                        <li><a href="index.php">Accueil</a></li>
                                        <li><a href="orthanc.html">Serveurs Orthanc</a></li>
                                        <li><a href="fichiers.php">Fichiers Non DICOM</a></li>
                                        <li class="active"><a href="patients.php">Gestion des patients</a></li>
                                        <li><a href="dashboard.php">Tableau de Bord</a></li>
                                        <li><a href="statistiques.php">Statistiques</a></li>
                                        <li><a href="contact.html">Contact</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="col-lg-2 col-12">
                            <div class="get-quote">
                                <a href="dashboard.php" class="btn">Accéder</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- End Header Area -->

    <!-- Banner Section avec animation -->
    <section class="page-banner">
        <div class="container">
            <div class="banner-content">
                <h1 class="banner-title">Gestion des Patients</h1>
                <p class="banner-subtitle">Gérez efficacement les dossiers patients et leurs informations médicales</p>
            </div>
        </div>
        <div class="doctor-image"></div>
    </section>
    <!-- End Banner -->

    <!-- Patients Section -->
    <section class="patients-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Patients Enregistrés</h2>
                <p class="section-subtitle">Consultez et gérez la liste complète des patients avec leurs informations hospitalières</p>
            </div>
            
            <div class="patients-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Liste des Patients</h4>
                        <p class="text-muted mb-0"><?php echo count($patients); ?> patient(s) enregistré(s)</p>
                    </div>
                    <button class="btn btn-add-patient" data-toggle="modal" data-target="#addPatientModal">
                        <i class="fa fa-plus"></i> Ajouter un Patient
                    </button>
                </div>
                
                <?php if (empty($patients)): ?>
                    <div class="no-patients">
                        <i class="fa fa-user-plus"></i>
                        <h4>Aucun patient enregistré</h4>
                        <p>Commencez par ajouter votre premier patient en cliquant sur le bouton ci-dessus.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table patients-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Sexe</th>
                                    <th>Date de naissance</th>
                                    <th>ID Orthanc</th>
                                    <th>Hôpital</th>
                                    <th>Région</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td>
                                            <div class="patient-info">
                                                <div class="patient-avatar">
                                                    <?php echo strtoupper(substr($patient['prenom'], 0, 1) . substr($patient['nom'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="patient-name"><?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?></div>
                                                    <div class="patient-id">ID: <?php echo htmlspecialchars($patient['identifiant_orthanc']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="gender-badge <?php echo strtolower($patient['sexe']); ?>">
                                                <?php echo htmlspecialchars($patient['sexe']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($patient['date_naissance'])); ?></td>
                                        <td><code><?php echo htmlspecialchars($patient['identifiant_orthanc']); ?></code></td>
                                        <td>
                                            <span class="hospital-badge">
                                                <?php echo htmlspecialchars($patient['hopital_nom'] ?? 'Non défini'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($patient['region'] ?? 'Non définie'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal d'ajout de patient -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPatientModalLabel">
                        <i class="fa fa-user-plus"></i> Ajouter un Nouveau Patient
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addPatientForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-nom">Nom *</label>
                                    <input type="text" id="modal-nom" name="nom" class="form-control" 
                                           placeholder="Nom de famille" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-prenom">Prénom *</label>
                                    <input type="text" id="modal-prenom" name="prenom" class="form-control" 
                                           placeholder="Prénom" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-sexe">Sexe *</label>
                                    <select id="modal-sexe" name="sexe" class="form-control" required>
                                        <option value="">Sélectionner le sexe</option>
                                        <option value="Homme">Homme</option>
                                        <option value="Femme">Femme</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-date-naissance">Date de naissance *</label>
                                    <input type="date" id="modal-date-naissance" name="date_naissance" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="modal-hopital">Hôpital *</label>
                                    <select id="modal-hopital" name="hopital_id" class="form-control" required>
                                        <option value="">Sélectionner un hôpital</option>
                                        <?php foreach ($hopitaux as $hopital): ?>
                                            <option value="<?php echo $hopital['id']; ?>">
                                                <?php echo htmlspecialchars($hopital['nom'] . ' - ' . $hopital['ville']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        L'identifiant Orthanc sera généré automatiquement : prénom_nom_hôpital_orthanc
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitAddPatient()">
                        <i class="fa fa-save"></i> Ajouter le Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate-3.0.0.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/easing.js"></script>
    <script src="js/colors.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap-datepicker.js"></script>
    <script src="js/jquery.nav.js"></script>
    <script src="js/slicknav.min.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/niceselect.js"></script>
    <script src="js/tilt.jquery.min.js"></script>
    <script src="js/owl-carousel.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/steller.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        // Fonction pour ajouter un patient
        function submitAddPatient() {
            const form = document.getElementById('addPatientForm');
            const formData = new FormData(form);
            formData.append('action', 'add_patient');
            
            // Validation
            if (!formData.get('nom') || !formData.get('prenom') || 
                !formData.get('sexe') || !formData.get('date_naissance') || 
                !formData.get('hopital_id')) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            // Afficher le loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Ajout en cours...';
            btn.disabled = true;
            
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Patient ajouté avec succès !');
                    $('#addPatientModal').modal('hide');
                    form.reset();
                    window.location.reload(); // Recharger la page pour voir le nouveau patient
                } else {
                    alert('Erreur : ' . data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du patient.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        // Animation au scroll
        $(document).ready(function() {
            // Animation des cartes au scroll
            $(window).scroll(function() {
                $('.patients-card').each(function() {
                    var elementTop = $(this).offset().top;
                    var elementBottom = elementTop + $(this).outerHeight();
                    var viewportTop = $(window).scrollTop();
                    var viewportBottom = viewportTop + $(window).height();
                    
                    if (elementBottom > viewportTop && elementTop < viewportBottom) {
                        $(this).addClass('animate__animated animate__fadeInUp');
                    }
                });
            });
        });
    </script>
</body>
</html>
