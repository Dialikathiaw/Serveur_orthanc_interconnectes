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

// Connexion à la base de données
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

// Traitement de l'ajout de fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_file') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $hopital_id = $_POST['hopital_id'];
        $patient_id = $_POST['patient_id'];
        $pathologie = $_POST['pathologie'];
        $date_ajout = $_POST['date_ajout'];
        
        // Gestion de l'upload de fichier
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            // Nouveau répertoire : ../documents/
            $upload_dir = '../documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'docx', 'xlsx', 'doc', 'xls'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Créer un nom de fichier unique
                $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['fichier']['name']);
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_path)) {
                    // Insérer en base de données avec le bon nom de colonne
                    $stmt = $pdo->prepare("INSERT INTO fichiers_non_dicom (nom_fichier, type_fichier, chemin_acces, patient_id, hopital_id, pathologie, date_ajout) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_FILES['fichier']['name'],
                        $file_extension,
                        $upload_path,
                        $patient_id,
                        $hopital_id,
                        $pathologie,
                        $date_ajout
                    ]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Fichier ajouté avec succès !';
                } else {
                    $response['message'] = 'Erreur lors de l\'upload du fichier.';
                }
            } else {
                $response['message'] = 'Type de fichier non autorisé. Utilisez PDF, Word ou Excel.';
            }
        } else {
            $response['message'] = 'Aucun fichier sélectionné ou erreur d\'upload.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Erreur : ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Traitement de la suppression de fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $file_id = $_POST['file_id'];
        
        // Récupérer le chemin du fichier avec le bon nom de colonne (id au lieu de id_fichier)
        $stmt = $pdo->prepare("SELECT chemin_acces FROM fichiers_non_dicom WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // Supprimer le fichier physique
            if (file_exists($file['chemin_acces'])) {
                unlink($file['chemin_acces']);
            }
            
            // Supprimer de la base de données avec le bon nom de colonne
            $stmt = $pdo->prepare("DELETE FROM fichiers_non_dicom WHERE id = ?");
            $stmt->execute([$file_id]);
            
            $response['success'] = true;
            $response['message'] = 'Fichier supprimé avec succès !';
        } else {
            $response['message'] = 'Fichier non trouvé.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Erreur : ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fonction pour récupérer les fichiers avec filtres
function getFichiers($pdo, $hopital_filter = '', $pathologie_filter = '', $date_filter = '') {
    try {
        $sql = "SELECT f.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                       s.nom as hopital_nom, s.ville as region
                FROM fichiers_non_dicom f
                JOIN patients p ON f.patient_id = p.id_patient
                JOIN serveurs_orthanc s ON f.hopital_id = s.id
                WHERE s.actif = 1";
        
        $params = [];
        
        if (!empty($hopital_filter)) {
            $sql .= " AND s.id = ?";
            $params[] = $hopital_filter;
        }
        
        if (!empty($pathologie_filter)) {
            $sql .= " AND f.pathologie LIKE ?";
            $params[] = "%$pathologie_filter%";
        }
        
        if (!empty($date_filter)) {
            $sql .= " AND DATE(f.date_ajout) = ?";
            $params[] = $date_filter;
        }
        
        $sql .= " ORDER BY f.date_ajout DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur getFichiers: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les hôpitaux depuis serveurs_orthanc
function getHopitaux($pdo) {
    try {
        // Récupérer depuis serveurs_orthanc au lieu de hopitaux
        $stmt = $pdo->query("SELECT id, nom, ville FROM serveurs_orthanc WHERE actif = 1 ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur getHopitaux: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les patients
function getPatients($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.id_patient, p.nom, p.prenom, s.nom as hopital_nom 
                            FROM patients p 
                            LEFT JOIN serveurs_orthanc s ON p.hopital_id = s.id 
                            ORDER BY p.nom, p.prenom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur getPatients: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les pathologies uniques
function getPathologies($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT pathologie FROM fichiers_non_dicom ORDER BY pathologie");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        error_log("Erreur getPathologies: " . $e->getMessage());
        return [];
    }
}

// Traitement AJAX pour les filtres
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $hopital_filter = $_GET['hopital'] ?? '';
    $pathologie_filter = $_GET['pathologie'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    
    $fichiers = getFichiers($pdo, $hopital_filter, $pathologie_filter, $date_filter);
    
    header('Content-Type: application/json');
    echo json_encode($fichiers);
    exit();
}

// Récupérer les données pour les filtres
$hopitaux = getHopitaux($pdo);
$patients = getPatients($pdo);
$pathologies = getPathologies($pdo);
$fichiers = getFichiers($pdo);
?>

<!doctype html>
<html class="no-js" lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Fichiers médicaux, documents non DICOM, gestion hospitalière">
    <meta name="description" content="Accédez à la liste des documents non DICOM associés aux patients.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fichiers Non DICOM - Plateforme Médicale</title>
    
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

        .filters-section {
            background: #f8f9fa;
            padding: 40px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .filter-select, .filter-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            border-color: #1A76D1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 118, 209, 0.1);
        }

        .btn-filter {
            background: #1A76D1;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-filter:hover {
            background: #0d5aa7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 118, 209, 0.3);
            color: white;
        }

        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-reset:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-add-file {
            background: #1A76D1;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-add-file:hover {
            background: #0d5aa7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 118, 209, 0.3);
            color: white;
        }

        .files-grid {
            padding: 50px 0;
        }

        .file-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            min-height: 450px;
            position: relative;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .file-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 20px;
        }

        .file-icon.pdf { background: #dc3545; }
        .file-icon.docx { background: #1A76D1; }
        .file-icon.xlsx { background: #28a745; }

        .file-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.3;
            height: 60px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            word-break: break-word;
        }

        .file-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .file-meta .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .file-meta .meta-item i {
            margin-right: 8px;
            width: 16px;
            color: #1A76D1;
        }

        .file-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            position: absolute;
            bottom: 20px;
            left: 30px;
            right: 30px;
        }

        .btn-download {
            background: #1A76D1;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
        }

        .btn-download:hover {
            background: #0d5aa7;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(26, 118, 209, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
        }

        .btn-view:hover {
            background: #138496;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }

        .pathologie-badge {
            background: #1A76D1;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1A76D1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-results i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* Modal styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: #1A76D1;
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .close {
            color: white;
            opacity: 0.8;
        }

        .close:hover {
            color: white;
            opacity: 1;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1A76D1;
            box-shadow: 0 0 0 3px rgba(26, 118, 209, 0.1);
        }

        .btn-primary {
            background: #1A76D1;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #0d5aa7;
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
                                        <li class="active"><a href="fichiers-simple.php">Fichiers Non DICOM</a></li>
                                        <li><a href="patients.php">Gestion des patients</a></li>
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

    <!-- Full-width Banner Section -->
    <section class="page-banner" style="background: url('img/fff.jpg') no-repeat center center/cover; height: 85vh; display: flex; align-items: center;">
        <div class="container">
            <div class="text-center text-white">
                <h1>Fichiers Médicaux Non DICOM</h1>
                <p style="font-size: 18px;">Accédez, visualisez et téléchargez les fichiers PDF, Word et Excel associés aux patients.</p>
            </div>
        </div>
    </section>
    <!-- End Banner -->

    <!-- Filters Section -->
    <section class="filters-section">
        <div class="container">
            <div class="filter-card">
                <h4 class="mb-4"><i class="fa fa-filter"></i> Filtres de Recherche</h4>
                <form id="filter-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="filter-group">
                                <label for="hopital-filter">Hôpital</label>
                                <select id="hopital-filter" name="hopital" class="filter-select">
                                    <option value="">Tous les hôpitaux</option>
                                    <?php foreach ($hopitaux as $hopital): ?>
                                        <option value="<?php echo $hopital['id']; ?>">
                                            <?php echo htmlspecialchars($hopital['nom'] . ' - ' . $hopital['ville']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-group">
                                <label for="pathologie-filter">Pathologie</label>
                                <select id="pathologie-filter" name="pathologie" class="filter-select">
                                    <option value="">Toutes les pathologies</option>
                                    <?php foreach ($pathologies as $pathologie): ?>
                                        <option value="<?php echo htmlspecialchars($pathologie); ?>">
                                            <?php echo htmlspecialchars($pathologie); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-group">
                                <label for="date-filter">Date d'ajout</label>
                                <input type="date" id="date-filter" name="date" class="filter-input">
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-filter">
                            <i class="fa fa-search"></i> Filtrer
                        </button>
                        <button type="button" class="btn-reset" onclick="resetFilters()">
                            <i class="fa fa-refresh"></i> Réinitialiser
                        </button>
                        <button type="button" class="btn-add-file" data-toggle="modal" data-target="#addFileModal">
                            <i class="fa fa-plus"></i> Ajouter un Dossier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loading-spinner">
        <div class="spinner"></div>
        <p>Chargement des fichiers...</p>
    </div>

    <!-- Files Grid -->
    <section class="files-grid">
        <div class="container">
            <div class="section-title">
                <h2>Liste des Documents Disponibles</h2>
                <p>Voici la liste des fichiers médicaux transmis par les différents services hospitaliers.</p>
            </div>
            
            <div id="files-container">
                <?php if (empty($fichiers)): ?>
                    <div class="no-results">
                        <i class="fa fa-folder-open"></i>
                        <h4>Aucun fichier trouvé</h4>
                        <p>Aucun document ne correspond aux critères de recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="row" id="files-grid">
                        <?php foreach ($fichiers as $fichier): ?>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="file-card">
                                    <div class="file-icon <?php echo strtolower($fichier['type_fichier']); ?>">
                                        <?php
                                        $icon = 'fa-file';
                                        switch(strtolower($fichier['type_fichier'])) {
                                            case 'pdf': $icon = 'fa-file-pdf-o'; break;
                                            case 'docx': $icon = 'fa-file-word-o'; break;
                                            case 'xlsx': $icon = 'fa-file-excel-o'; break;
                                        }
                                        ?>
                                        <i class="fa <?php echo $icon; ?>"></i>
                                    </div>
                                    
                                    <h5 class="file-title" title="<?php echo htmlspecialchars($fichier['nom_fichier']); ?>">
                                        <?php echo htmlspecialchars($fichier['nom_fichier']); ?>
                                    </h5>
                                    
                                    <div class="file-meta">
                                        <div class="meta-item">
                                            <i class="fa fa-user"></i>
                                            <span><?php echo htmlspecialchars($fichier['patient_prenom'] . ' ' . $fichier['patient_nom']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa fa-hospital-o"></i>
                                            <span><?php echo htmlspecialchars($fichier['hopital_nom']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa fa-calendar"></i>
                                            <span><?php echo date('d/m/Y', strtotime($fichier['date_ajout'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="pathologie-badge">
                                        <?php echo htmlspecialchars($fichier['pathologie']); ?>
                                    </div>
                                    
                                    <div class="file-actions">
                                        <a href="<?php echo htmlspecialchars($fichier['chemin_acces']); ?>" 
                                           class="btn-download" download>
                                            <i class="fa fa-download"></i> Télécharger
                                        </a>
                                        <a href="<?php echo htmlspecialchars($fichier['chemin_acces']); ?>" 
                                           class="btn-view" target="_blank">
                                            <i class="fa fa-eye"></i> Visualiser
                                        </a>
                                        <button class="btn-delete" onclick="deleteFile(<?php echo $fichier['id']; ?>)">
                                            <i class="fa fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal d'ajout de fichier -->
    <div class="modal fade" id="addFileModal" tabindex="-1" role="dialog" aria-labelledby="addFileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFileModalLabel">
                        <i class="fa fa-plus-circle"></i> Ajouter un Nouveau Dossier Médical
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addFileForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
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
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-patient">Patient *</label>
                                    <select id="modal-patient" name="patient_id" class="form-control" required>
                                        <option value="">Sélectionner un patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id_patient']; ?>" data-hopital="<?php echo htmlspecialchars($patient['hopital_nom'] ?? 'Non défini'); ?>">
                                                <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                                                <?php if ($patient['hopital_nom']): ?>
                                                    - <?php echo htmlspecialchars($patient['hopital_nom']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Les patients sont récupérés depuis la base de données
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-pathologie">Pathologie *</label>
                                    <input type="text" id="modal-pathologie" name="pathologie" class="form-control" 
                                           placeholder="Ex: Hypertension, Diabète..." required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="modal-date">Date d'ajout *</label>
                                    <input type="date" id="modal-date" name="date_ajout" class="form-control" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="modal-fichier">Fichier médical *</label>
                            <input type="file" id="modal-fichier" name="fichier" class="form-control" 
                                   accept=".pdf,.docx,.xlsx,.doc,.xls" required>
                            <small class="form-text text-muted">
                                Formats acceptés : PDF, Word (.docx, .doc), Excel (.xlsx, .xls) - Taille max : 10MB
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitAddFile()">
                        <i class="fa fa-save"></i> Ajouter le Dossier
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
    // Synchronisation patient/hôpital
    $(document).ready(function() {
        $('#modal-patient').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const hopitalName = selectedOption.data('hopital');
            
            if (hopitalName && hopitalName !== 'Non défini') {
                // Trouver l'hôpital correspondant dans la liste
                $('#modal-hopital option').each(function() {
                    if ($(this).text().includes(hopitalName)) {
                        $('#modal-hopital').val($(this).val());
                        return false;
                    }
                });
            }
        });
    });
</script>
</body>
</html>
