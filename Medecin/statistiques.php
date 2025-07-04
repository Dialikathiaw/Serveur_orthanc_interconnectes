<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Utiliser des valeurs par défaut si pas de session
    $user = ['nom' => 'Invité', 'prenom' => 'Utilisateur', 'role' => 'visiteur'];
} else {
    // Configuration de la base de données
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
            $user = ['nom' => 'Test', 'prenom' => 'User', 'role' => 'medecin'];
        }
        
    } catch(PDOException $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        $user = ['nom' => 'Test', 'prenom' => 'User', 'role' => 'medecin'];
    }
}
?>

<!doctype html>
<html class="no-js" lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Statistiques médicales, épidémiologie, données santé Sénégal">
    <meta name="description" content="Statistiques médicales en temps réel et visualisation géographique des pathologies au Sénégal.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Statistiques Épidémiologiques - République du Sénégal</title>

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
    
    <!-- Chart.js pour les graphiques avancés -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    
    <!-- Leaflet CSS et JS pour la carte interactive -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* Styles pour l'utilisateur connecté */
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

        /* Design ultra professionnel pour les statistiques */
        .stats-hero {
            background: linear-gradient(135deg, #1A76D1 0%, #4A90E2 50%, #00C851 100%);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .stats-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.2);
            z-index: 1;
        }

        .stats-hero::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .stats-hero .container {
            position: relative;
            z-index: 2;
        }

        .stats-hero h1 {
            color: white;
            font-size: 4rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
            letter-spacing: -1px;
        }

        .stats-hero .subtitle {
            color: rgba(255,255,255,0.95);
            font-size: 1.4rem;
            text-align: center;
            margin-bottom: 40px;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stats-hero .badge-presidential {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 20px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Méga statistiques */
        .mega-stats {
            background: white;
            margin-top: -80px;
            position: relative;
            z-index: 10;
            border-radius: 30px;
            padding: 60px 40px;
            box-shadow: 0 30px 100px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .mega-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        .mega-stat-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 15px 50px rgba(0,0,0,0.08);
            border: 1px solid rgba(26, 118, 209, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .mega-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(45deg, #1A76D1, #4A90E2, #00C851);
        }

        .mega-stat-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 80px rgba(26, 118, 209, 0.2);
        }

        .mega-stat-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .mega-stat-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #1A76D1, #4A90E2);
            border-radius: 25px;
        }

        .mega-stat-icon i {
            position: relative;
            z-index: 2;
        }

        .mega-stat-number {
            font-size: 4rem;
            font-weight: 900;
            background: linear-gradient(45deg, #1A76D1, #4A90E2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .mega-stat-label {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .mega-stat-description {
            font-size: 1rem;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .mega-stat-trend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 20px;
            background: rgba(0, 200, 81, 0.1);
            color: #00C851;
        }

        .mega-stat-trend.negative {
            background: rgba(255, 69, 58, 0.1);
            color: #FF453A;
        }

        /* Section des analyses avancées */
        .advanced-analytics {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .chart-container-advanced {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .chart-container-advanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(45deg, #1A76D1, #4A90E2, #00C851);
        }

        .chart-title-advanced {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .chart-title-advanced::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(45deg, #1A76D1, #4A90E2);
            border-radius: 2px;
        }

        /* Carte Leaflet stylée */
        .map-container {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .map-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(45deg, #1A76D1, #4A90E2, #00C851);
        }

        #senegalMap {
            width: 100%;
            height: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Styles pour les popups Leaflet */
        .leaflet-popup-content-wrapper {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .leaflet-popup-content {
            margin: 15px;
            font-family: 'Poppins', sans-serif;
        }

        .popup-title {
            font-size: 16px;
            font-weight: 700;
            color: #1A76D1;
            margin-bottom: 10px;
        }

        .popup-stats {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }

        .popup-stats strong {
            color: #1A76D1;
        }

        /* Indicateurs épidémiologiques */
        .epidemio-indicators {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }

        .epidemio-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            border-left: 5px solid #1A76D1;
            transition: all 0.3s ease;
        }

        .epidemio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .epidemio-card h4 {
            color: #1A76D1;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .epidemio-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
        }

        .epidemio-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-hero h1 {
                font-size: 2.5rem;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .mega-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation des compteurs */
        .counter {
            animation: countUp 2s ease-out;
        }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Styles pour les alertes épidémiologiques */
        .alert-epidemio {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }

        .alert-epidemio h5 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Badge présidentiel */
        .presidential-seal {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
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
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="logout.php" class="logout-btn" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                                    <i class="fa fa-sign-out"></i> Déconnexion
                                </a>
                            <?php endif; ?>
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
                                        <li><a href="patients.php">Gestion des patients</a></li>
                                        <li><a href="dashboard.php">Tableau de Bord</a></li>
                                        <li class="active"><a href="statistiques.php">Statistiques</a></li>
                                        <li><a href="contact.html">Contact</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="col-lg-2 col-12">
                            <div class="get-quote">
                                <a href="dashboard.php" class="btn">Tableau de Bord</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- End Header Area -->

    <!-- Hero Section Présidentiel -->
    <section class="stats-hero">
        <div class="presidential-seal">
            <i class="fa fa-star" style="color: white; font-size: 2rem;"></i>
        </div>
        <div class="container">
            <h1>🇸🇳 Observatoire National de la Santé</h1>
            <p class="subtitle">Système d'Intelligence Épidémiologique - République du Sénégal</p>
            <div class="text-center">
                <div class="badge-presidential">
                    <i class="fa fa-shield"></i>
                    Données Officielles - Ministère de la Santé et de l'Action Sociale
                </div>
            </div>
        </div>
    </section>

    <!-- Méga Statistiques -->
    <section class="container">
        <div class="mega-stats">
            <div class="text-center mb-5">
                <h2 style="font-size: 3rem; font-weight: 800; color: #333; margin-bottom: 20px;">
                    Indicateurs Stratégiques Nationaux
                </h2>
                <p style="font-size: 1.2rem; color: #666; max-width: 800px; margin: 0 auto;">
                    Surveillance épidémiologique en temps réel sur l'ensemble du territoire sénégalais
                </p>
            </div>

            <div class="mega-stats-grid">
                <!-- Population Couverte -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-users-alt-4"></i>
                    </div>
                    <div class="mega-stat-number counter">17.2M</div>
                    <div class="mega-stat-label">Population Couverte</div>
                    <div class="mega-stat-description">
                        Citoyens sénégalais bénéficiant du système de surveillance sanitaire national
                    </div>
                    <div class="mega-stat-trend">
                        <i class="fa fa-arrow-up"></i>
                        +3.2% cette année
                    </div>
                </div>

                <!-- Consultations Mensuelles -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-stethoscope-alt"></i>
                    </div>
                    <div class="mega-stat-number counter">847,392</div>
                    <div class="mega-stat-label">Consultations/Mois</div>
                    <div class="mega-stat-description">
                        Consultations médicales enregistrées dans le réseau national ce mois-ci
                    </div>
                    <div class="mega-stat-trend">
                        <i class="fa fa-arrow-up"></i>
                        +12.8% vs mois dernier
                    </div>
                </div>

                <!-- Structures Sanitaires -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-hospital"></i>
                    </div>
                    <div class="mega-stat-number counter">1,847</div>
                    <div class="mega-stat-label">Structures Sanitaires</div>
                    <div class="mega-stat-description">
                        Hôpitaux, centres de santé et postes sanitaires connectés au système
                    </div>
                    <div class="mega-stat-trend">
                        <i class="fa fa-arrow-up"></i>
                        +47 nouvelles structures
                    </div>
                </div>

                <!-- Taux de Couverture Vaccinale -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-injection-syringe"></i>
                    </div>
                    <div class="mega-stat-number counter">94.7%</div>
                    <div class="mega-stat-label">Couverture Vaccinale</div>
                    <div class="mega-stat-description">
                        Taux de vaccination complète chez les enfants de 0-11 mois
                    </div>
                    <div class="mega-stat-trend">
                        <i class="fa fa-arrow-up"></i>
                        +2.1% cette année
                    </div>
                </div>

                <!-- Mortalité Infantile -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-baby"></i>
                    </div>
                    <div class="mega-stat-number counter">32.1‰</div>
                    <div class="mega-stat-label">Mortalité Infantile</div>
                    <div class="mega-stat-description">
                        Taux de mortalité infantile pour 1000 naissances vivantes
                    </div>
                    <div class="mega-stat-trend negative">
                        <i class="fa fa-arrow-down"></i>
                        -4.2% (amélioration)
                    </div>
                </div>

                <!-- Espérance de Vie -->
                <div class="mega-stat-card">
                    <div class="mega-stat-icon">
                        <i class="icofont-heart-beat"></i>
                    </div>
                    <div class="mega-stat-number counter">68.7</div>
                    <div class="mega-stat-label">Espérance de Vie</div>
                    <div class="mega-stat-description">
                        Espérance de vie moyenne à la naissance au Sénégal (années)
                    </div>
                    <div class="mega-stat-trend">
                        <i class="fa fa-arrow-up"></i>
                        +1.3 ans depuis 2020
                    </div>
                </div>
            </div>

            <!-- Alertes Épidémiologiques -->
            <div class="alert-epidemio">
                <h5><i class="fa fa-exclamation-triangle"></i> Surveillance Épidémiologique Active</h5>
                <p>Aucune épidémie majeure détectée. Surveillance renforcée du paludisme en saison des pluies. 
                Campagne de sensibilisation COVID-19 en cours dans 8 régions.</p>
            </div>
        </div>
    </section>

    <!-- Analyses Avancées -->
    <section class="advanced-analytics">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="font-size: 3rem; font-weight: 800; color: #333; margin-bottom: 20px;">
                    Analyses Épidémiologiques Avancées
                </h2>
                <p style="font-size: 1.2rem; color: #666;">
                    Visualisation géographique et temporelle des données de santé publique
                </p>
            </div>

            <div class="analytics-grid">
                <!-- Graphique Principal -->
                <div class="chart-container-advanced">
                    <h3 class="chart-title-advanced">Évolution des Pathologies Prioritaires (2024)</h3>
                    <canvas id="pathologyTrendChart" width="800" height="400"></canvas>
                </div>

                <!-- Carte Interactive du Sénégal -->
                <div class="map-container">
                    <h3 class="chart-title-advanced">Répartition Géographique</h3>
                    <div id="senegalMap"></div>
                </div>
            </div>

            <!-- Graphiques Secondaires -->
            <div class="row mt-5">
                <div class="col-lg-6">
                    <div class="chart-container-advanced">
                        <h3 class="chart-title-advanced">Répartition par Âge</h3>
                        <canvas id="ageDistributionChart" width="400" height="300"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-container-advanced">
                        <h3 class="chart-title-advanced">Saisonnalité des Pathologies</h3>
                        <canvas id="seasonalityChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Indicateurs Épidémiologiques Détaillés -->
            <div class="epidemio-indicators">
                <div class="epidemio-card">
                    <h4>Paludisme</h4>
                    <div class="epidemio-value">342,891</div>
                    <div class="epidemio-description">
                        Cas confirmés cette année. Réduction de 15% par rapport à 2023. 
                        Zones à risque : Kédougou, Tambacounda, Kolda.
                    </div>
                </div>

                <div class="epidemio-card">
                    <h4>Hypertension</h4>
                    <div class="epidemio-value">278,456</div>
                    <div class="epidemio-description">
                        Patients sous traitement. Prévalence plus élevée en milieu urbain. 
                        Programme de dépistage actif dans 14 régions.
                    </div>
                </div>

                <div class="epidemio-card">
                    <h4>Diabète Type 2</h4>
                    <div class="epidemio-value">189,234</div>
                    <div class="epidemio-description">
                        Cas diagnostiqués et suivis. Augmentation de 8% liée aux changements 
                        alimentaires. Campagne de prévention lancée.
                    </div>
                </div>

                <div class="epidemio-card">
                    <h4>Tuberculose</h4>
                    <div class="epidemio-value">15,678</div>
                    <div class="epidemio-description">
                        Nouveaux cas détectés. Taux de guérison de 87%. 
                        Renforcement du dépistage dans les zones urbaines.
                    </div>
                </div>

                <div class="epidemio-card">
                    <h4>Drépanocytose</h4>
                    <div class="epidemio-value">129,445</div>
                    <div class="epidemio-description">
                        Patients pris en charge. Programme national de dépistage néonatal 
                        étendu à toutes les maternités.
                    </div>
                </div>

                <div class="epidemio-card">
                    <h4>Malnutrition Aiguë</h4>
                    <div class="epidemio-value">23,891</div>
                    <div class="epidemio-description">
                        Enfants de moins de 5 ans traités. Amélioration de 22% grâce aux 
                        programmes nutritionnels communautaires.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Animation des compteurs
        function animateCounters() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = counter.innerText;
                const numericTarget = parseFloat(target.replace(/[^\d.]/g, ''));
                let current = 0;
                const increment = numericTarget / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= numericTarget) {
                        current = numericTarget;
                        clearInterval(timer);
                    }
                    
                    if (target.includes('M')) {
                        counter.innerText = (current / 1000000).toFixed(1) + 'M';
                    } else if (target.includes('%')) {
                        counter.innerText = current.toFixed(1) + '%';
                    } else if (target.includes('‰')) {
                        counter.innerText = current.toFixed(1) + '‰';
                    } else if (target.includes(',')) {
                        counter.innerText = Math.floor(current).toLocaleString();
                    } else {
                        counter.innerText = Math.floor(current);
                    }
                }, 20);
            });
        }

        // Initialiser la carte Leaflet
        function initSenegalMap() {
            console.log('🗺️ Initialisation de la carte du Sénégal...');
            
            // Créer la carte centrée sur le Sénégal
            const map = L.map('senegalMap').setView([14.4974, -14.4524], 7);
            
            // Ajouter les tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | Observatoire National de la Santé 🇸🇳',
                maxZoom: 18,
            }).addTo(map);
            
            // Données des villes avec statistiques sanitaires
            const cities = [
                {
                    name: "Dakar",
                    lat: 14.6937,
                    lng: -17.4441,
                    cases: 45000,
                    population: 3500000,
                    hospitals: 45,
                    color: "#FF6B6B",
                    region: "Région de Dakar"
                },
                {
                    name: "Thiès",
                    lat: 14.7886,
                    lng: -16.9260,
                    cases: 12000,
                    population: 950000,
                    hospitals: 12,
                    color: "#4ECDC4",
                    region: "Région de Thiès"
                },
                {
                    name: "Kaolack",
                    lat: 14.1617,
                    lng: -16.0728,
                    cases: 8500,
                    population: 650000,
                    hospitals: 8,
                    color: "#45B7D1",
                    region: "Région de Kaolack"
                },
                {
                    name: "Saint-Louis",
                    lat: 16.0179,
                    lng: -16.4897,
                    cases: 7200,
                    population: 580000,
                    hospitals: 7,
                    color: "#96CEB4",
                    region: "Région de Saint-Louis"
                },
                {
                    name: "Ziguinchor",
                    lat: 12.5681,
                    lng: -16.2719,
                    cases: 6800,
                    population: 520000,
                    hospitals: 6,
                    color: "#FFEAA7",
                    region: "Région de Ziguinchor"
                },
                {
                    name: "Tambacounda",
                    lat: 13.7671,
                    lng: -13.6681,
                    cases: 9200,
                    population: 720000,
                    hospitals: 9,
                    color: "#FF7675",
                    region: "Région de Tambacounda"
                },
                {
                    name: "Kédougou",
                    lat: 12.5561,
                    lng: -12.1756,
                    cases: 4500,
                    population: 180000,
                    hospitals: 4,
                    color: "#A29BFE",
                    region: "Région de Kédougou"
                },
                {
                    name: "Matam",
                    lat: 15.6556,
                    lng: -13.2556,
                    cases: 3200,
                    population: 320000,
                    hospitals: 5,
                    color: "#FD79A8",
                    region: "Région de Matam"
                },
                {
                    name: "Kolda",
                    lat: 12.8944,
                    lng: -14.9444,
                    cases: 5100,
                    population: 450000,
                    hospitals: 6,
                    color: "#00B894",
                    region: "Région de Kolda"
                }
            ];
            
            // Ajouter les marqueurs pour chaque ville
            cities.forEach(city => {
                // Calculer la taille du cercle en fonction du nombre de cas
                const radius = Math.sqrt(city.cases / 100) * 2;
                
                // Créer un marqueur circulaire
                const circle = L.circleMarker([city.lat, city.lng], {
                    color: '#ffffff',
                    weight: 3,
                    opacity: 1,
                    fillColor: city.color,
                    fillOpacity: 0.8,
                    radius: radius
                }).addTo(map);
                
                // Contenu du popup avec statistiques détaillées
                const popupContent = `
                    <div class="popup-title">🏥 ${city.name}</div>
                    <div class="popup-stats">
                        <strong>Région:</strong> ${city.region}<br>
                        <strong>Population:</strong> ${city.population.toLocaleString()} habitants<br>
                        <strong>Cas médicaux:</strong> ${city.cases.toLocaleString()}<br>
                        <strong>Structures sanitaires:</strong> ${city.hospitals}<br>
                        <strong>Taux d'incidence:</strong> ${((city.cases / city.population) * 100).toFixed(2)}%<br>
                        <strong>Statut:</strong> <span style="color: #00C851;">✅ Surveillance active</span>
                    </div>
                `;
                
                // Ajouter le popup
                circle.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'custom-popup'
                });
                
                // Effet hover
                circle.on('mouseover', function() {
                    this.setStyle({
                        weight: 5,
                        fillOpacity: 1
                    });
                });
                
                circle.on('mouseout', function() {
                    this.setStyle({
                        weight: 3,
                        fillOpacity: 0.8
                    });
                });
            });
            
            // Ajouter une légende personnalisée
            const legend = L.control({position: 'bottomright'});
            legend.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'info legend');
                div.style.background = 'rgba(255,255,255,0.95)';
                div.style.padding = '15px';
                div.style.borderRadius = '10px';
                div.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
                div.style.fontFamily = 'Poppins, sans-serif';
                div.style.fontSize = '12px';
                
                div.innerHTML = `
                    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">🇸🇳 LÉGENDE</h4>
                    <div style="margin-bottom: 8px;"><span style="display: inline-block; width: 12px; height: 12px; background: #FF6B6B; border-radius: 50%; margin-right: 8px;"></span>Très élevé (>40k)</div>
                    <div style="margin-bottom: 8px;"><span style="display: inline-block; width: 10px; height: 10px; background: #4ECDC4; border-radius: 50%; margin-right: 8px;"></span>Élevé (10k-40k)</div>
                    <div style="margin-bottom: 8px;"><span style="display: inline-block; width: 8px; height: 8px; background: #45B7D1; border-radius: 50%; margin-right: 8px;"></span>Modéré (5k-10k)</div>
                    <div style="margin-bottom: 10px;"><span style="display: inline-block; width: 6px; height: 6px; background: #96CEB4; border-radius: 50%; margin-right: 8px;"></span>Faible (<5k)</div>
                    <div style="font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 8px;">
                        📊 Cas de paludisme 2024<br>
                        ⚡ Mise à jour: Temps réel
                    </div>
                `;
                
                return div;
            };
            legend.addTo(map);
            
            console.log('✅ Carte du Sénégal initialisée avec succès!');
        }

        // Initialiser les graphiques
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des compteurs
            setTimeout(animateCounters, 500);
            
            // Initialiser la carte après un délai pour s'assurer que le DOM est prêt
            setTimeout(initSenegalMap, 1000);

            // Graphique d'évolution des pathologies
            const pathologyCtx = document.getElementById('pathologyTrendChart').getContext('2d');
            new Chart(pathologyCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                    datasets: [{
                        label: 'Paludisme',
                        data: [32000, 28000, 25000, 22000, 18000, 15000, 12000, 14000, 18000, 25000, 30000, 35000],
                        borderColor: '#FF6B6B',
                        backgroundColor: 'rgba(255, 107, 107, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Hypertension',
                        data: [23000, 23500, 24000, 24200, 24800, 25000, 25200, 25500, 25800, 26000, 26200, 26500],
                        borderColor: '#4ECDC4',
                        backgroundColor: 'rgba(78, 205, 196, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Diabète',
                        data: [15000, 15200, 15500, 15800, 16000, 16200, 16500, 16800, 17000, 17200, 17500, 17800],
                        borderColor: '#45B7D1',
                        backgroundColor: 'rgba(69, 183, 209, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Graphique de répartition par âge
            const ageCtx = document.getElementById('ageDistributionChart').getContext('2d');
            new Chart(ageCtx, {
                type: 'doughnut',
                data: {
                    labels: ['0-5 ans', '6-17 ans', '18-35 ans', '36-60 ans', '60+ ans'],
                    datasets: [{
                        data: [18, 22, 35, 20, 5],
                        backgroundColor: [
                            '#FF6B6B',
                            '#4ECDC4', 
                            '#45B7D1',
                            '#96CEB4',
                            '#FFEAA7'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Graphique de saisonnalité
            const seasonCtx = document.getElementById('seasonalityChart').getContext('2d');
            new Chart(seasonCtx, {
                type: 'radar',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                    datasets: [{
                        label: 'Paludisme',
                        data: [85, 70, 60, 45, 30, 25, 20, 25, 35, 55, 75, 90],
                        borderColor: '#FF6B6B',
                        backgroundColor: 'rgba(255, 107, 107, 0.2)',
                        pointBackgroundColor: '#FF6B6B'
                    }, {
                        label: 'Infections Respiratoires',
                        data: [90, 85, 70, 50, 30, 20, 15, 20, 40, 65, 80, 95],
                        borderColor: '#45B7D1',
                        backgroundColor: 'rgba(69, 183, 209, 0.2)',
                        pointBackgroundColor: '#45B7D1'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        });

        console.log('🇸🇳 Observatoire National de la Santé du Sénégal - Système chargé avec succès!');
    </script>
</body>
</html>
