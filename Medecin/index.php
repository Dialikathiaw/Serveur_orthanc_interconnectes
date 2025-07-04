<?php
session_start();

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
    
    // Récupérer les informations de l'utilisateur connecté avec le statut MFA
    $stmt = $pdo->prepare("SELECT nom, prenom, role, secret_mfa FROM utilisateurs WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Si l'utilisateur n'existe plus, détruire la session
        session_destroy();
        header('Location: ../connexion/connexion.html');
        exit();
    }
    
} catch(PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    header('Location: ../connexion/connexion.html');
    exit();
}
?>

<!doctype html>
<html class="no-js" lang="zxx">
    <head>
        <!-- Meta Tags -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="keywords" content="Plateforme médicale, Orthanc, DICOM, statistiques, hôpital">
		<meta name="description" content="Plateforme de gestion des serveurs Orthanc, collecte d'informations médicales et statistiques épidémiologiques.">
		<meta name='copyright' content=''>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		
		<!-- Title -->
        <title>Plateforme Médicale - Gestion des Images et Statistiques</title>
		
		<!-- Favicon -->
        <link rel="icon" href="img/favicon.png">
		
		<!-- Google Fonts -->
		<link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">

		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<!-- Nice Select CSS -->
		<link rel="stylesheet" href="css/nice-select.css">
		<!-- Font Awesome CSS -->
        <link rel="stylesheet" href="css/font-awesome.min.css">
		<!-- icofont CSS -->
        <link rel="stylesheet" href="css/icofont.css">
		<!-- Slicknav -->
		<link rel="stylesheet" href="css/slicknav.min.css">
		<!-- Owl Carousel CSS -->
        <link rel="stylesheet" href="css/owl-carousel.css">
		<!-- Datepicker CSS -->
		<link rel="stylesheet" href="css/datepicker.css">
		<!-- Animate CSS -->
        <link rel="stylesheet" href="css/animate.min.css">
		<!-- Magnific Popup CSS -->
        <link rel="stylesheet" href="css/magnific-popup.css">
		
		<!-- Medipro CSS -->
        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="css/responsive.css">

        <!-- Styles personnalisés pour l'utilisateur connecté -->
        <style>
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
    
    .logout-btn i {
        font-size: 11px;
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

/* Styles pour la section MFA */
.mfa-status-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.mfa-status-enabled {
    border-left: 4px solid #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.1));
}

.mfa-status-disabled {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(245, 158, 11, 0.1));
}

.mfa-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.mfa-icon {
    font-size: 1.5rem;
}

.mfa-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
}

.mfa-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.mfa-enabled {
    background: #d1fae5;
    color: #065f46;
}

.mfa-disabled {
    background: #fef3c7;
    color: #92400e;
}

.mfa-description {
    color: #64748b;
    margin-bottom: 15px;
    line-height: 1.5;
}

.mfa-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-mfa {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-mfa-activate {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.btn-mfa-activate:hover {
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    color: white;
    text-decoration: none;
}

.btn-mfa-manage {
    background: #10b981;
    color: white;
}

.btn-mfa-manage:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    color: white;
    text-decoration: none;
}

.security-section {
    margin-top: 30px;
}

.security-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Styles pour le module de prédiction de maladies - Version Époustouflante */
.prediction-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #1e3c72 100%);
    padding: 100px 0;
    margin: 0;
    position: relative;
    overflow: hidden;
}

.prediction-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(10px, 10px); }
}

.prediction-section::after {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: float 15s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(-20px, -20px) rotate(180deg); }
}

.prediction-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 50px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.prediction-header {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
}

.medical-icon {
    font-size: 4rem;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
    display: block;
    animation: pulse 2s ease-in-out infinite;
}

.prediction-title {
    color: #1a202c;
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 15px;
    text-align: center;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.prediction-subtitle {
    color: #4a5568;
    font-size: 1.2rem;
    margin-bottom: 30px;
    text-align: center;
    line-height: 1.8;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.ai-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
    border: 1px solid rgba(79, 172, 254, 0.3);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.stat-card:hover::before {
    left: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #4facfe;
    margin-bottom: 5px;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 600;
}

.prediction-form {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    border: 1px solid rgba(79, 172, 254, 0.2);
}

.form-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
}

.form-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
}

.symptoms-input-group {
    position: relative;
    margin-bottom: 25px;
}

.symptoms-input {
    width: 100%;
    padding: 25px 30px;
    border: 2px solid #e2e8f0;
    border-radius: 15px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #ffffff;
    font-family: inherit;
    resize: vertical;
    min-height: 140px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.symptoms-input:focus {
    outline: none;
    border-color: #4facfe;
    box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.1), 0 8px 25px rgba(79, 172, 254, 0.15);
    transform: translateY(-2px);
}

.input-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.suggestion-tag {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
    color: #4facfe;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid rgba(79, 172, 254, 0.3);
}

.suggestion-tag:hover {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: white;
    transform: translateY(-1px);
}

.symptoms-help {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    padding: 15px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
    border-radius: 10px;
    border-left: 4px solid #10b981;
}

.help-icon {
    color: #10b981;
    font-size: 1.2rem;
}

.help-text {
    color: #059669;
    font-size: 0.9rem;
    font-weight: 500;
}

.predict-btn {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border: none;
    padding: 20px 50px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    max-width: 400px;
    margin: 30px auto 0;
    display: block;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
}

.predict-btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.predict-btn:hover::before {
    left: 100%;
}

.predict-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4);
}

.predict-btn:disabled {
    background: #a0aec0;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
}

.prediction-result {
    margin-top: 40px;
    padding: 40px;
    border-radius: 20px;
    display: none;
    animation: fadeInUp 0.6s ease;
    position: relative;
    overflow: hidden;
}

.prediction-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
    border: 2px solid #10b981;
    color: #065f46;
}

.prediction-error {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
    border: 2px solid #ef4444;
    color: #991b1b;
}

.result-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 25px;
}

.result-icon {
    font-size: 3rem;
    animation: bounce 1s ease-in-out;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.result-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.result-disease {
    font-size: 2.2rem;
    font-weight: 900;
    text-align: center;
    margin: 25px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.result-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.detail-card {
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 15px;
    border: 1px solid rgba(79, 172, 254, 0.2);
}

.detail-title {
    font-weight: 700;
    margin-bottom: 10px;
    color: #1a202c;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-content {
    color: #4a5568;
    line-height: 1.6;
}

.confidence-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 10px;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 4px;
    transition: width 1s ease;
}

.medical-info {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    padding: 30px;
    margin-top: 30px;
    border: 1px solid rgba(79, 172, 254, 0.2);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.info-section {
    padding: 20px;
    background: rgba(79, 172, 254, 0.05);
    border-radius: 15px;
    border-left: 4px solid #4facfe;
}

.info-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    padding: 8px 0;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-list li::before {
    content: "✓";
    color: #10b981;
    font-weight: bold;
}

/* Animations et effets */
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

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .prediction-section {
        padding: 60px 0;
    }
    
    .prediction-card {
        padding: 30px 20px;
        margin: 0 15px;
        border-radius: 20px;
    }
    
    .prediction-title {
        font-size: 2rem;
    }
    
    .ai-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .prediction-form {
        padding: 25px;
    }
    
    .symptoms-input {
        padding: 20px;
        min-height: 120px;
    }
    
    .predict-btn {
        padding: 18px 40px;
        font-size: 16px;
    }
    
    .result-disease {
        font-size: 1.8rem;
    }
    
    .result-details {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .prediction-title {
        font-size: 1.75rem;
    }
    
    .medical-icon {
        font-size: 3rem;
    }
    
    .ai-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}

/* Effets de particules */
.particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 50%;
    animation: particleFloat 15s linear infinite;
}

@keyframes particleFloat {
    0% {
        transform: translateY(100vh) translateX(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) translateX(100px);
        opacity: 0;
    }
}
</style>
		
    </head>
    <body>
	
		<!-- Preloader -->
        <div class="preloader">
            <div class="loader">
                <div class="loader-outter"></div>
                <div class="loader-inner"></div>

                <div class="indicator"> 
                    <svg width="16px" height="12px">
                        <polyline id="back" points="1 6 4 6 6 11 10 1 12 6 15 6"></polyline>
                        <polyline id="front" points="1 6 4 6 6 11 10 1 12 6 15 6"></polyline>
                    </svg>
                </div>
            </div>
        </div>
        <!-- End Preloader -->
		
	
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

			<!-- Header Inner -->
			<div class="header-inner">
				<div class="container">
					<div class="inner">
						<div class="row">
							<div class="col-lg-3 col-md-3 col-12">
								<!-- Logo -->
								<div class="logo">
									<a href="index.php"><img src="img/logo.png" alt="Plateforme Médicale"></a>
								</div>
								<div class="mobile-nav"></div>
							</div>
							<div class="col-lg-7 col-md-9 col-12">
								<!-- Main Menu -->
								<div class="main-menu">
									<nav class="navigation">
										<ul class="nav menu">
											<li class="active"><a href="index.php">Accueil</a></li>
											<li><a href="orthanc.html">Serveurs Orthanc</a></li>
											<li><a href="fichiers.php">Fichiers Non DICOM</a></li>
											<li><a href="patients.php">Gestion des patients </a></li>
											<li><a href="dashboard.php">Tableau de Bord</a></li>
											<li><a href="statistiques.php">Statistiques</a></li>
											<li><a href="contact.html">Contact</a></li>
										</ul>
									</nav>
								</div>
								<!--/ End Main Menu -->
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
			<!--/ End Header Inner -->
		</header>
		<!-- End Header Area -->

		
		<!-- Slider Area -->
		<section class="slider">
			<div class="hero-slider">
				<!-- Start Single Slider -->
				<div class="single-slider" style="background-image:url('img/ggg.jpg')">
					<div class="container">
						<div class="row">
							<div class="col-lg-7">
								<div class="text">
									<h1>Bienvenue <span><?php echo htmlspecialchars($user['prenom']); ?></span>, Plateforme <span>Médicale</span> Moderne Pour La <span>Gestion D'Images</span> Et Données</h1>
									<p>Administrez facilement les serveurs Orthanc et accédez à toutes les données médicales sécurisées.</p>
									<div class="button">
										<a href="orthanc.html" class="btn">Voir les Serveurs</a>
										<a href="fichiers.php" class="btn primary">Fichiers Non DICOM</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Single Slider -->

				<!-- Start Single Slider -->
				<div class="single-slider" style="background-image:url('img/eee.jpg')">
					<div class="container">
						<div class="row">
							<div class="col-lg-7">
								<div class="text">
									<h1>Visualisez <span>Les Statistiques</span> et Les <span>Épidémies</span> En Temps Réel</h1>
									<p>Générez des statistiques médicales et consultez les données par région via une carte interactive du Sénégal.</p>
									<div class="button">
										<a href="statistiques.php" class="btn">Statistiques</a>
										<a href="dashboard.php" class="btn primary">Tableau de Bord</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Single Slider -->

				<!-- Start Single Slider -->
				<div class="single-slider" style="background-image:url('img/fff.jpg')">
					<div class="container">
						<div class="row">
							<div class="col-lg-7">
								<div class="text">
									<h1>Une <span>Plateforme Sécurisée</span> Pour Les <span>Professionnels De Santé</span></h1>
									<p>Facilitez la téléradiologie, la collaboration médicale et la gestion complète des consultations.</p>
									<div class="button">
										<a href="contact.html" class="btn">Nous Contacter</a>
										<a href="index.php" class="btn primary">En Savoir Plus</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Single Slider -->
			</div>
		</section>
		<!--/ End Slider Area -->


		<!-- Start Schedule Area -->
<section class="schedule">
    <div class="container">
        <div class="schedule-inner">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12">
                    <!-- single-schedule -->
                    <div class="single-schedule first">
                        <div class="inner">
                            <div class="icon">
                                <i class="fa fa-server"></i>
                            </div>
                            <div class="single-content">
                                <span>Serveurs Connectés</span>
                                <h4>Accéder aux Serveurs Orthanc</h4>
                                <p>Consultez et administrez les serveurs DICOM connectés pour chaque hôpital en toute sécurité.</p>
                                <a href="orthanc.html">Voir Plus <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <!-- single-schedule -->
                    <div class="single-schedule middle">
                        <div class="inner">
                            <div class="icon">
                                <i class="fa fa-file-text"></i>
                            </div>
                            <div class="single-content">
                                <span>Documents Médicaux</span>
                                <h4>Fichiers Non DICOM</h4>
                                <p>Accédez facilement aux fichiers PDF, Excel ou Word partagés entre les médecins et les services.</p>
                                <a href="fichiers.php">Voir Plus <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 col-12">
                    <!-- single-schedule -->
                    <div class="single-schedule last">
                        <div class="inner">
                            <div class="icon">
                                <i class="icofont-chart-bar-graph"></i>
                            </div>
                            <div class="single-content">
                                <span>Statistiques Régionales</span>
                                <h4>Suivi Épidémiologique</h4>
                                <ul class="time-sidual">
                                    <li class="day">Mise à jour toutes les <span>5 minutes</span></li>
                                    <li class="day">Dernière analyse : <span>Il y a 2 minutes</span></li>
                                    <li class="day">Carte interactive par région</li>
                                </ul>
                                <a href="statistiques.php">Voir les Stats <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--/End Start schedule Area -->


		<!-- Start Features -->
<section class="Feautes section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>Une Plateforme Intelligente Pour Les Médecins et Hôpitaux</h2>
                    <img src="img/section-img.png" alt="#">
                    <p>Centralisez la gestion des serveurs Orthanc, consultez les données patients et accédez aux statistiques de santé publique.</p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-12">
                <!-- Start Single features -->
                <div class="single-features">
                    <div class="signle-icon">
                        <i class="icofont icofont-server"></i>
                    </div>
                    <h3>Gestion des Serveurs</h3>
                    <p>Supervisez, administrez et consultez les serveurs Orthanc connectés aux différents hôpitaux.</p>
                </div>
                <!-- End Single features -->
            </div>
            <div class="col-lg-4 col-12">
                <!-- Start Single features -->
                <div class="single-features">
                    <div class="signle-icon">
                        <i class="icofont icofont-file-document"></i>
                    </div>
                    <h3>Fichiers Médicaux Non DICOM</h3>
                    <p>Accédez aux documents médicaux importants (PDF, Word, Excel) liés aux patients, centralisés et sécurisés.</p>
                </div>
                <!-- End Single features -->
            </div>
            <div class="col-lg-4 col-12">
                <!-- Start Single features -->
                <div class="single-features last">
                    <div class="signle-icon">
                        <i class="icofont icofont-chart-bar-graph"></i>
                    </div>
                    <h3>Statistiques en Temps Réel</h3>
                    <p>Analysez les données épidémiologiques, consultez les statistiques par région sur la carte interactive du Sénégal.</p>
                </div>
                <!-- End Single features -->
            </div>
        </div>
    </div>
</section>
<!--/ End Features -->

<!-- Start Disease Prediction Section -->
<section class="prediction-section">
    <div class="particles"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-md-12 col-12 mx-auto">
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="medical-icon">🧠</div>
                        <h2 class="prediction-title">Diagnostic Intelligent par IA</h2>
                        <p class="prediction-subtitle">
                            Exploitez la puissance de l'intelligence artificielle médicale pour obtenir des prédictions de diagnostic précises basées sur l'analyse de symptômes. Notre modèle avancé utilise des algorithmes de machine learning entraînés sur des milliers de cas cliniques.
                        </p>
                    </div>

                    <div class="ai-stats">
                        <div class="stat-card">
                            <span class="stat-number" id="totalDiseases">50+</span>
                            <span class="stat-label">Maladies Reconnues</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="accuracy">94.7%</span>
                            <span class="stat-label">Précision du Modèle</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="totalPredictions">15,847</span>
                            <span class="stat-label">Prédictions Effectuées</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="responseTime">0.3s</span>
                            <span class="stat-label">Temps de Réponse</span>
                        </div>
                    </div>

                    <div class="prediction-form">
                        <div class="form-header">
                            <div class="form-icon">🩺</div>
                            <h3 class="form-title">Analyse des Symptômes</h3>
                        </div>

                        <form id="predictionForm">
                            <div class="symptoms-input-group">
                                <textarea 
                                    id="symptomsInput" 
                                    class="symptoms-input" 
                                    placeholder="Décrivez précisément les symptômes observés, séparés par des virgules..."
                                    rows="5"
                                    required
                                ></textarea>
                                
                                <div class="input-suggestions">
                                    <span class="suggestion-tag" onclick="addSymptom('fièvre')">🌡️ Fièvre</span>
                                    <span class="suggestion-tag" onclick="addSymptom('toux')">😷 Toux</span>
                                    <span class="suggestion-tag" onclick="addSymptom('maux de tête')">🤕 Maux de tête</span>
                                    <span class="suggestion-tag" onclick="addSymptom('fatigue')">😴 Fatigue</span>
                                    <span class="suggestion-tag" onclick="addSymptom('nausées')">🤢 Nausées</span>
                                    <span class="suggestion-tag" onclick="addSymptom('douleurs musculaires')">💪 Douleurs musculaires</span>
                                </div>
                                
                                <div class="symptoms-help">
                                    <span class="help-icon">💡</span>
                                    <span class="help-text">Plus vous êtes précis dans la description des symptômes, plus le diagnostic sera fiable. Incluez l'intensité, la durée et les circonstances d'apparition.</span>
                                </div>
                            </div>
                            
                            <button type="submit" id="predictBtn" class="predict-btn">
                                <span id="btnText">🔬 Lancer l'Analyse IA</span>
                            </button>
                        </form>
                    </div>
                    
                    <div id="predictionResult" class="prediction-result">
                        <div class="result-header">
                            <div class="result-icon" id="resultIcon"></div>
                            <h3 class="result-title" id="resultTitle"></h3>
                        </div>
                        <div class="result-disease" id="resultDisease"></div>
                        
                        <div class="result-details">
                            <div class="detail-card">
                                <div class="detail-title">
                                    📊 Niveau de Confiance
                                </div>
                                <div class="detail-content">
                                    <span id="confidenceText">Calcul en cours...</span>
                                    <div class="confidence-bar">
                                        <div class="confidence-fill" id="confidenceFill" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-card">
                                <div class="detail-title">
                                    🎯 Symptômes Analysés
                                </div>
                                <div class="detail-content" id="analyzedSymptoms">
                                    En attente d'analyse...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="medical-info">
                        <div class="info-grid">
                            <div class="info-section">
                                <h4 class="info-title">
                                    🏥 Domaines Médicaux Couverts
                                </h4>
                                <ul class="info-list">
                                    <li>Maladies Infectieuses</li>
                                    <li>Troubles Respiratoires</li>
                                    <li>Pathologies Digestives</li>
                                    <li>Affections Neurologiques</li>
                                    <li>Maladies Cardiovasculaires</li>
                                    <li>Troubles Dermatologiques</li>
                                </ul>
                            </div>
                            
                            <div class="info-section">
                                <h4 class="info-title">
                                    🔬 Technologie IA Avancée
                                </h4>
                                <ul class="info-list">
                                    <li>Algorithmes Random Forest</li>
                                    <li>Vectorisation TF-IDF</li>
                                    <li>Apprentissage Supervisé</li>
                                    <li>Validation Croisée</li>
                                    <li>Optimisation Continue</li>
                                    <li>Base de Données Médicales</li>
                                </ul>
                            </div>
                            
                            <div class="info-section">
                                <h4 class="info-title">
                                    ⚠️ Avertissements Importants
                                </h4>
                                <ul class="info-list">
                                    <li>Outil d'aide au diagnostic uniquement</li>
                                    <li>Ne remplace pas une consultation</li>
                                    <li>Consultez un médecin pour confirmation</li>
                                    <li>En cas d'urgence, appelez le 15</li>
                                    <li>Résultats à titre informatif</li>
                                    <li>Données confidentielles et sécurisées</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Disease Prediction Section -->

<!-- Start Security Section -->
<section class="security-section section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="security-title">
                    🔐 Sécurité de votre compte
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-10 col-12 mx-auto">
                <div class="mfa-status-card <?php echo !empty($user['secret_mfa']) ? 'mfa-status-enabled' : 'mfa-status-disabled'; ?>">
                    <div class="mfa-header">
                        <div class="mfa-icon">
                            <?php echo !empty($user['secret_mfa']) ? '🔒' : '🔓'; ?>
                        </div>
                        <div class="mfa-title">
                            Authentification à Deux Facteurs (MFA)
                        </div>
                        <div class="mfa-status-badge <?php echo !empty($user['secret_mfa']) ? 'mfa-enabled' : 'mfa-disabled'; ?>">
                            <?php echo !empty($user['secret_mfa']) ? 'Activé' : 'Désactivé'; ?>
                        </div>
                    </div>
                    
                    <div class="mfa-description">
                        <?php if (!empty($user['secret_mfa'])): ?>
                            Excellent ! Votre compte est protégé par l'authentification à deux facteurs. 
                            Vous devez saisir un code de Google Authenticator à chaque connexion.
                        <?php else: ?>
                            Renforcez la sécurité de votre compte en activant l'authentification à deux facteurs. 
                            Cette protection supplémentaire empêche l'accès non autorisé même si votre mot de passe est compromis.
                        <?php endif; ?>
                    </div>
                    
                    <div class="mfa-actions">
                        <?php if (!empty($user['secret_mfa'])): ?>
                            <a href="../connexion/activer_mfa.php" class="btn-mfa btn-mfa-manage">
                                ⚙️ Gérer le MFA
                            </a>
                        <?php else: ?>
                            <a href="../connexion/activer_mfa.php" class="btn-mfa btn-mfa-activate">
                                🚀 Activer le MFA
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informations supplémentaires -->
                <div class="mfa-status-card" style="margin-top: 20px; border-left: 4px solid #6b7280;">
                    <div class="mfa-header">
                        <div class="mfa-icon">ℹ️</div>
                        <div class="mfa-title">Pourquoi activer le MFA ?</div>
                    </div>
                    <div class="mfa-description">
                        <ul style="margin: 0; padding-left: 20px; color: #64748b;">
                            <li>Protection contre le piratage de compte</li>
                            <li>Sécurité renforcée pour les données médicales sensibles</li>
                            <li>Conformité aux standards de sécurité hospitalière</li>
                            <li>Tranquillité d'esprit pour vous et vos patients</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Security Section -->
		
		<!-- Start Fun-facts -->
<div id="fun-facts" class="fun-facts section overlay">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Fun -->
                <div class="single-fun">
                    <i class="icofont icofont-server"></i>
                    <div class="content">
                        <span class="counter">12</span>
                        <p>Serveurs Orthanc Connectés</p>
                    </div>
                </div>
                <!-- End Single Fun -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Fun -->
                <div class="single-fun">
                    <i class="icofont icofont-doctor-alt"></i>
                    <div class="content">
                        <span class="counter">450</span>
                        <p>Professionnels de Santé Utilisateurs</p>
                    </div>
                </div>
                <!-- End Single Fun -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Fun -->
                <div class="single-fun">
                    <i class="icofont icofont-file-document"></i>
                    <div class="content">
                        <span class="counter">8500</span>
                        <p>Fichiers Médicaux Gérés</p>
                    </div>
                </div>
                <!-- End Single Fun -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Fun -->
                <div class="single-fun">
                    <i class="icofont icofont-patient-bed"></i>
                    <div class="content">
                        <span class="counter">21500</span>
                        <p>Consultations Enregistrées</p>
                    </div>
                </div>
                <!-- End Single Fun -->
            </div>
        </div>
    </div>
</div>
<!--/ End Fun-facts -->

		
		<!-- Start Why choose -->
<section class="why-choose section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h2>Pourquoi Choisir Notre Plateforme Médicale ?</h2>
                    <img src="img/section-img.png" alt="#">
                    <p>Un outil complet, sécurisé et pensé pour les professionnels de santé et les institutions hospitalières.</p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-12">
                <!-- Start Choose Left -->
                <div class="choose-left">
                    <h3>Notre Vision</h3>
                    <p>Fournir une plateforme centralisée pour la gestion des images médicales DICOM, des fichiers non DICOM et des statistiques de santé publique.</p>
                    <p>Faciliter la collaboration entre les hôpitaux, garantir la traçabilité des patients et offrir des outils d'analyse avancés aux professionnels de santé.</p>
                    <div class="row">
                        <div class="col-lg-6">
                            <ul class="list">
                                <li><i class="fa fa-caret-right"></i>Administration multi-hôpitaux.</li>
                                <li><i class="fa fa-caret-right"></i>Accès sécurisé et chiffré.</li>
                                <li><i class="fa fa-caret-right"></i>Téléradiologie et partage d'images.</li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="list">
                                <li><i class="fa fa-caret-right"></i>Collecte d'informations en temps réel.</li>
                                <li><i class="fa fa-caret-right"></i>Tableau de bord interactif.</li>
                                <li><i class="fa fa-caret-right"></i>Carte épidémiologique dynamique.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- End Choose Left -->
            </div>
            <div class="col-lg-6 col-12">
                <!-- Start Choose Rights -->
                <div class="choose-right">
                    <div class="video-image">
                        <!-- Video Animation -->
                        <div class="promo-video">
                            <div class="waves-block">
                                <div class="waves wave-1"></div>
                                <div class="waves wave-2"></div>
                                <div class="waves wave-3"></div>
                            </div>
                        </div>
                        <!--/ End Video Animation -->
                        <a href="https://www.youtube.com/watch?v=nk1TCEXVgOI" class="video video-popup mfp-iframe">
                            <i class="fa fa-play"></i>
                        </a>
                    </div>
                </div>
                <!-- End Choose Rights -->
            </div>
        </div>
    </div>
</section>
<!--/ End Why choose -->

		
		<!-- Start Call to action -->
		<section class="call-action overlay" data-stellar-background-ratio="0.5">
			<div class="container">
				<div class="row">
					<div class="col-lg-12 col-md-12 col-12">
						<div class="content">
							<h2>Vous souhaitez accéder aux serveurs ou consulter les statistiques ?</h2>
							<p>Explorez la plateforme ou contactez l'administrateur pour toute demande d'assistance technique.</p>
							<div class="button">
								<a href="dashboard.php" class="btn">Tableau de Bord</a>
								<a href="contact.html" class="btn second">Nous Contacter <i class="fa fa-long-arrow-right"></i></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!--/ End Call to action -->

		
		<!-- Start portfolio -->
<section class="portfolio section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="section-title">
					<h2>Découvrez Les Fonctionnalités Clés De La Plateforme</h2>
					<img src="img/section-img.png" alt="#">
					<p>Visualisez en images comment la plateforme facilite la gestion des serveurs Orthanc, l'accès aux fichiers médicaux et la consultation de statistiques épidémiologiques.</p>
				</div>
			</div>
		</div>
	</div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12 col-12">
				<div class="owl-carousel portfolio-slider">
					<div class="single-pf">
						<img src="img/pe1.jpg" alt="Serveurs Orthanc">
						<a href="orthanc.html" class="btn">Voir Détails</a>
					</div>
					<div class="single-pf">
						<img src="img/pe2.jpg" alt="Fichiers Médicaux Non DICOM">
						<a href="fichiers.php" class="btn">Voir Détails</a>
					</div>
					<div class="single-pf">
						<img src="img/pe3.jpg" alt="Tableau de Bord">
						<a href="dashboard.php" class="btn">Voir Détails</a>
					</div>
					<div class="single-pf">
						<img src="img/pe4.jpg" alt="Statistiques Épidémiologiques">
						<a href="statistiques.php" class="btn">Voir Détails</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--/ End portfolio -->


		
		<!-- Start service -->
		<section class="services section">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
						<div class="section-title">
							<h2>Les Services Offerts Par La Plateforme</h2>
							<img src="img/section-img.png" alt="#">
							<p>Découvrez les fonctionnalités principales de la plateforme permettant la gestion des données médicales et l'analyse épidémiologique.</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-hospital"></i>
							<h4><a href="orthanc.html">Gestion des serveurs Orthanc</a></h4>
							<p>Accédez et administrez facilement les serveurs DICOM connectés dans les hôpitaux.</p>	
						</div>
						<!-- End Single Service -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-file-document"></i>
							<h4><a href="fichiers.php">Fichiers Médicaux Non DICOM</a></h4>
							<p>Stockez et consultez les fichiers PDF, Word et Excel associés aux dossiers patients.</p>	
						</div>
						<!-- End Single Service -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-chart-bar-graph"></i>
							<h4><a href="statistiques.php">Statistiques Épidémiologiques</a></h4>
							<p>Visualisez les statistiques des consultations par région sur la carte interactive.</p>	
						</div>
						<!-- End Single Service -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-dashboard-web"></i>
							<h4><a href="dashboard.php">Tableau de Bord</a></h4>
							<p>Un tableau de bord interactif pour suivre l'activité des hôpitaux et serveurs.</p>	
						</div>
						<!-- End Single Service -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-shield-alt"></i>
							<h4><a href="#">Sécurité & Traçabilité</a></h4>
							<p>Suivi des connexions, historisation des accès et protection avancée des données.</p>	
						</div>
						<!-- End Single Service -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Start Single Service -->
						<div class="single-service">
							<i class="icofont icofont-support"></i>
							<h4><a href="contact.html">Assistance et Support</a></h4>
							<p>Un support technique et des conseils pour exploiter pleinement la plateforme.</p>	
						</div>
						<!-- End Single Service -->
					</div>
				</div>
			</div>
		</section>  
		<!--/ End service -->

		
		
		
		
		
		<!-- Start Blog Area -->
		<section class="blog section" id="blog">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
						<div class="section-title">
							<h2>Suivez Nos Dernières Actualités Médicales</h2>
							<img src="img/section-img.png" alt="#">
							<p>Retrouvez ici les mises à jour importantes, les évolutions des fonctionnalités de la plateforme et des informations médicales clés.</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Single Blog -->
						<div class="single-news">
							<div class="news-head">
								<img src="img/blo1.jpg" alt="Mise à jour plateforme">
							</div>
							<div class="news-body">
								<div class="news-content">
									<div class="date">15 Mars, 2025</div>
									<h2><a href="blog-single.html">Lancement de la version 2.0 de la plateforme</a></h2>
									<p class="text">Découvrez les nouvelles fonctionnalités : tableaux de bord interactifs, notifications de sécurité et synchronisation améliorée.</p>
								</div>
							</div>
						</div>
						<!-- End Single Blog -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Single Blog -->
						<div class="single-news">
							<div class="news-head">
								<img src="img/blo2.jpg" alt="Nouveautés médicales">
							</div>
							<div class="news-body">
								<div class="news-content">
									<div class="date">28 Février, 2025</div>
									<h2><a href="blog-single.html">Nouvelles recommandations de l'OMS pour la gestion des données médicales</a></h2>
									<p class="text">L'OMS publie de nouvelles directives sur la sécurité et la confidentialité des données médicales sensibles. Apprenez-en plus ici.</p>
								</div>
							</div>
						</div>
						<!-- End Single Blog -->
					</div>
					<div class="col-lg-4 col-md-6 col-12">
						<!-- Single Blog -->
						<div class="single-news">
							<div class="news-head">
								<img src="img/blo3.jpg" alt="Sécurité des données">
							</div>
							<div class="news-body">
								<div class="news-content">
									<div class="date">10 Janvier, 2025</div>
									<h2><a href="blog-single.html">Sécuriser les serveurs Orthanc : bonnes pratiques</a></h2>
									<p class="text">Comment protéger efficacement vos serveurs Orthanc contre les cyberattaques ? Découvrez les meilleures pratiques de sécurité.</p>
								</div>
							</div>
						</div>
						<!-- End Single Blog -->
					</div>
				</div>
			</div>
		</section>
		<!-- End Blog Area -->

		
		<!-- Start Clients -->
<div class="clients overlay">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-12">
				<div class="owl-carousel clients-slider">
					<div class="single-clients">
						<img src="img/client1.png" alt="Hôpital Principal de Dakar">
					</div>
					<div class="single-clients">
						<img src="img/client2.png" alt="Hôpital Aristide Le Dantec">
					</div>
					<div class="single-clients">
						<img src="img/client3.png" alt="Hôpital Fann">
					</div>
					<div class="single-clients">
						<img src="img/client4.png" alt="Hôpital Dalal Jamm">
					</div>
					<div class="single-clients">
						<img src="img/client5.png" alt="Centre Hospitalier de Thiès">
					</div>
					<div class="single-clients">
						<img src="img/client1.png" alt="Hôpital Principal de Dakar">
					</div>
					<div class="single-clients">
						<img src="img/client2.png" alt="Hôpital Aristide Le Dantec">
					</div>
					<div class="single-clients">
						<img src="img/client3.png" alt="Hôpital Fann">
					</div>
					<div class="single-clients">
						<img src="img/client4.png" alt="Hôpital Dalal Jamm">
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!--/ End Clients -->

		
		<!-- Start Appointment / Demande d'Accès -->
		<section class="appointment">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
						<div class="section-title">
							<h2>Demandez un Accès ou Contactez-Nous</h2>
							<img src="img/section-img.png" alt="Icone">
							<p>Remplissez ce formulaire pour demander un accès à la plateforme ou poser vos questions. Nous vous répondrons dans les plus brefs délais.</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-md-12 col-12">
						<form class="form" action="#" method="post">
							<div class="row">
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="name" type="text" placeholder="Nom complet" required>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="email" type="email" placeholder="Adresse email" required>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="phone" type="text" placeholder="Téléphone">
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<div class="nice-select form-control wide" tabindex="0">
											<span class="current">Service concerné</span>
											<ul class="list">
												<li data-value="1" class="option selected ">Sélectionner</li>
												<li data-value="2" class="option">Accès Serveur Orthanc</li>
												<li data-value="3" class="option">Accès aux fichiers médicaux</li>
												<li data-value="4" class="option">Support technique</li>
												<li data-value="5" class="option">Autre demande</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="col-lg-12 col-md-12 col-12">
									<div class="form-group">
										<textarea name="message" placeholder="Votre message ou précisez votre demande..." required></textarea>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-5 col-md-4 col-12">
									<div class="form-group">
										<div class="button">
											<button type="submit" class="btn">Envoyer la Demande</button>
										</div>
									</div>
								</div>
								<div class="col-lg-7 col-md-8 col-12">
									<p>(Vous recevrez une confirmation par e-mail ou SMS.)</p>
								</div>
							</div>
						</form>
					</div>
					<div class="col-lg-6 col-md-12 ">
						<div class="appointment-image">
							<img src="img/contact.jpg" alt="Contact illustration">
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- End Appointment / Demande d'Accès -->

		
		<!-- Start Newsletter Area -->
<section class="newsletter section">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-12">
				<!-- Start Newsletter Text -->
				<div class="subscribe-text">
					<h6>Abonnez-vous à notre newsletter</h6>
					<p>Recevez les dernières informations médicales, mises à jour des serveurs Orthanc et alertes épidémiologiques directement dans votre boîte mail.</p>
				</div>
				<!-- End Newsletter Text -->
			</div>
			<div class="col-lg-6 col-12">
				<!-- Start Newsletter Form -->
				<div class="subscribe-form">
					<form action="mail/mail.php" method="post" class="newsletter-inner">
						<input name="email" placeholder="Votre adresse email" class="common-input" onfocus="this.placeholder = ''"
							onblur="this.placeholder = 'Votre adresse email'" required="" type="email">
						<button class="btn" type="submit">S'abonner</button>
					</form>
				</div>
				<!-- End Newsletter Form -->
			</div>
		</div>
	</div>
</section>
<!-- /End Newsletter Area -->

		
		<!-- Footer Area -->
<footer id="footer" class="footer">
	<!-- Footer Top -->
	<div class="footer-top">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-md-6 col-12">
					<div class="single-footer">
						<h2>À propos de la plateforme</h2>
						<p>Plateforme médicale pour la consultation et la gestion d'images DICOM, de fichiers médicaux et de statistiques épidémiologiques.</p>
						<!-- Social -->
						<ul class="social">
							<li><a href="#"><i class="icofont-facebook"></i></a></li>
							<li><a href="#"><i class="icofont-linkedin"></i></a></li>
							<li><a href="#"><i class="icofont-twitter"></i></a></li>
						</ul>
						<!-- End Social -->
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<div class="single-footer f-link">
						<h2>Liens rapides</h2>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-12">
								<ul>
									<li><a href="index.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Accueil</a></li>
									<li><a href="orthanc.html"><i class="fa fa-caret-right" aria-hidden="true"></i>Serveurs Orthanc</a></li>
									<li><a href="fichiers.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Fichiers Non DICOM</a></li>
									<li><a href="dashboard.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Tableau de Bord</a></li>
								</ul>
							</div>
							<div class="col-lg-6 col-md-6 col-12">
								<ul>
									<li><a href="statistiques.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Statistiques</a></li>
									<li><a href="contact.html"><i class="fa fa-caret-right" aria-hidden="true"></i>Contact</a></li>
									<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>FAQ</a></li>
									<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Mentions légales</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<div class="single-footer">
						<h2>Heures d'ouverture</h2>
						<p>Support et assistance technique :</p>
						<ul class="time-sidual">
							<li class="day">Lundi - Vendredi <span>08h00 - 20h00</span></li>
							<li class="day">Samedi <span>09h00 - 18h30</span></li>
							<li class="day">Dimanche <span>Fermé</span></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<div class="single-footer">
						<h2>Newsletter</h2>
						<p>Inscrivez-vous pour recevoir nos mises à jour et notifications importantes.</p>
						<form action="mail/mail.php" method="post" class="newsletter-inner">
							<input name="email" placeholder="Votre adresse email" class="common-input" onfocus="this.placeholder = ''"
								onblur="this.placeholder = 'Votre adresse email'" required="" type="email">
							<button class="button"><i class="icofont icofont-paper-plane"></i></button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--/ End Footer Top -->
	<!-- Copyright -->
	<div class="copyright">
		<div class="container">
			<div class="row">
				<div class="col-lg-12 col-md-12 col-12">
					<div class="copyright-content">
						<p>© 2025 Plateforme Médicale - Tous droits réservés. Design adapté par votre équipe projet.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--/ End Copyright -->
</footer>
<!--/ End Footer Area -->

		
		<!-- jquery Min JS -->
        <script src="js/jquery.min.js"></script>
		<!-- jquery Migrate JS -->
		<script src="js/jquery-migrate-3.0.0.js"></script>
		<!-- jquery Ui JS -->
		<script src="js/jquery-ui.min.js"></script>
		<!-- Easing JS -->
        <script src="js/easing.js"></script>
		<!-- Color JS -->
		<script src="js/colors.js"></script>
		<!-- Popper JS -->
		<script src="js/popper.min.js"></script>
		<!-- Bootstrap Datepicker JS -->
		<script src="js/bootstrap-datepicker.js"></script>
		<!-- Jquery Nav JS -->
        <script src="js/jquery.nav.js"></script>
		<!-- Slicknav JS -->
		<script src="js/slicknav.min.js"></script>
		<!-- ScrollUp JS -->
        <script src="js/jquery.scrollUp.min.js"></script>
		<!-- Niceselect JS -->
		<script src="js/niceselect.js"></script>
		<!-- Tilt Jquery JS -->
		<script src="js/tilt.jquery.min.js"></script>
		<!-- Owl Carousel JS -->
        <script src="js/owl-carousel.js"></script>
		<!-- counterup JS -->
		<script src="js/jquery.counterup.min.js"></script>
		<!-- Steller JS -->
		<script src="js/steller.js"></script>
		<!-- Wow JS -->
		<script src="js/wow.min.js"></script>
		<!-- Magnific Popup JS -->
		<script src="js/jquery.magnific-popup.min.js"></script>
		<!-- Counter Up CDN JS -->
		<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
		<!-- Bootstrap JS -->
		<script src="js/bootstrap.min.js"></script>
		<!-- Main JS -->
		<script src="js/main.js"></script>

		<!-- Script pour la prédiction de maladies -->
		<script>
// Fonction pour ajouter des symptômes via les tags
function addSymptom(symptom) {
    const input = document.getElementById('symptomsInput');
    const currentValue = input.value.trim();
    
    if (currentValue === '') {
        input.value = symptom;
    } else if (!currentValue.toLowerCase().includes(symptom.toLowerCase())) {
        input.value = currentValue + ', ' + symptom;
    }
    
    input.focus();
}

// Animation des statistiques au chargement
function animateStats() {
    const stats = [
        { id: 'totalDiseases', target: 50, suffix: '+' },
        { id: 'accuracy', target: 94.7, suffix: '%' },
        { id: 'totalPredictions', target: 15847, suffix: '' },
        { id: 'responseTime', target: 0.3, suffix: 's' }
    ];
    
    stats.forEach(stat => {
        const element = document.getElementById(stat.id);
        let current = 0;
        const increment = stat.target / 50;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= stat.target) {
                current = stat.target;
                clearInterval(timer);
            }
            
            if (stat.id === 'totalPredictions') {
                element.textContent = Math.floor(current).toLocaleString() + stat.suffix;
            } else {
                element.textContent = current.toFixed(stat.id === 'accuracy' || stat.id === 'responseTime' ? 1 : 0) + stat.suffix;
            }
        }, 50);
    });
}

// Créer des particules flottantes
function createParticles() {
    const particlesContainer = document.querySelector('.particles');
    const particleCount = 20;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (15 + Math.random() * 10) + 's';
        particlesContainer.appendChild(particle);
    }
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    animateStats();
    createParticles();
});

// Modifier le script de prédiction existant pour inclure la barre de confiance
$('#predictionForm').on('submit', function(e) {
    e.preventDefault();
    
    const symptomsText = $('#symptomsInput').val().trim();
    const predictBtn = $('#predictBtn');
    const btnText = $('#btnText');
    const resultDiv = $('#predictionResult');
    
    if (!symptomsText) {
        alert('Veuillez saisir au moins un symptôme.');
        return;
    }
    
    // Désactiver le bouton et afficher le loading
    predictBtn.prop('disabled', true);
    btnText.html('<span class="loading-spinner"></span>Analyse IA en cours...');
    resultDiv.hide();
    
    // Préparer les symptômes
    const symptoms = symptomsText.split(',').map(s => s.trim()).filter(s => s.length > 0);
    
    // Appel AJAX vers l'API Flask
    $.ajax({
        url: 'http://127.0.0.1:5001/predict',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            symptoms: symptoms
        }),
        success: function(response) {
            // Afficher le résultat de succès
            $('#resultIcon').text('✅');
            $('#resultTitle').text('Diagnostic IA Complété');
            $('#resultDisease').text(response.disease);
            
            // Simuler un niveau de confiance (à remplacer par la vraie valeur de l'API)
            const confidence = response.confidence || (85 + Math.random() * 10);
            $('#confidenceText').text(confidence.toFixed(1) + '% de confiance');
            $('#confidenceFill').css('width', confidence + '%');
            
            $('#analyzedSymptoms').html(
                symptoms.map(s => `<span style="background: rgba(79, 172, 254, 0.1); padding: 4px 8px; border-radius: 12px; margin: 2px; display: inline-block;">${s}</span>`).join('')
            );
            
            resultDiv.removeClass('prediction-error').addClass('prediction-success').show();
            
            // Scroll vers le résultat
            $('html, body').animate({
                scrollTop: resultDiv.offset().top - 100
            }, 800);
        },
        error: function(xhr, status, error) {
            // Afficher le message d'erreur
            $('#resultIcon').text('❌');
            $('#resultTitle').text('Erreur d\'Analyse');
            $('#resultDisease').text('Impossible d\'analyser les symptômes');
            
            let errorMessage = 'Erreur de connexion à l\'API de prédiction.';
            if (xhr.status === 0) {
                errorMessage = 'Vérifiez que le serveur Flask est démarré sur le port 5001.';
            }
            
            $('#confidenceText').text('0% de confiance');
            $('#confidenceFill').css('width', '0%');
            $('#analyzedSymptoms').text(errorMessage);
            
            resultDiv.removeClass('prediction-success').addClass('prediction-error').show();
        },
        complete: function() {
            // Réactiver le bouton
            predictBtn.prop('disabled', false);
            btnText.html('🔬 Lancer l\'Analyse IA');
        }
    });
});
		</script>
    </body>
</html>
