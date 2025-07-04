<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.html');
    exit();
}

// Inclure l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

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

$googleAuthenticator = new GoogleAuthenticator();
$message = '';
$error = '';

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT nom, prenom, email, secret_mfa FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: connexion.html');
    exit();
}

// Traitement de l'activation MFA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'generate_secret') {
            // Générer un nouveau secret
            $secret = $googleAuthenticator->generateSecret();
            
            // Stocker temporairement le secret en session (pas encore en base)
            $_SESSION['temp_mfa_secret'] = $secret;
            
        } elseif ($_POST['action'] === 'verify_and_activate') {
            $code = $_POST['verification_code'] ?? '';
            $secret = $_SESSION['temp_mfa_secret'] ?? '';
            
            if (empty($code) || empty($secret)) {
                $error = "Code de vérification requis.";
            } else {
                // Vérifier le code OTP
                if ($googleAuthenticator->checkCode($secret, $code)) {
                    // Code correct, activer le MFA
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET secret_mfa = ? WHERE id_user = ?");
                    $stmt->execute([$secret, $_SESSION['user_id']]);
                    
                    // Nettoyer la session temporaire
                    unset($_SESSION['temp_mfa_secret']);
                    
                    $message = "MFA activé avec succès ! Votre compte est maintenant sécurisé.";
                    
                    // Recharger les données utilisateur
                    $stmt = $pdo->prepare("SELECT nom, prenom, email, secret_mfa FROM utilisateurs WHERE id_user = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } else {
                    $error = "Code de vérification incorrect. Veuillez réessayer.";
                }
            }
        } elseif ($_POST['action'] === 'disable_mfa') {
            // Désactiver le MFA
            $stmt = $pdo->prepare("UPDATE utilisateurs SET secret_mfa = NULL WHERE id_user = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $message = "MFA désactivé avec succès.";
            
            // Recharger les données utilisateur
            $stmt = $pdo->prepare("SELECT nom, prenom, email, secret_mfa FROM utilisateurs WHERE id_user = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Générer le QR code si un secret temporaire existe
$qrCodeUrl = '';
if (isset($_SESSION['temp_mfa_secret'])) {
    $secret = $_SESSION['temp_mfa_secret'];
    $accountName = $user['email'];
    $issuer = 'MediPlus';
    
    $qrCodeUrl = GoogleQrUrl::generate($accountName, $secret, $issuer);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation MFA - MediPlus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: #64748b;
            font-size: 1.1rem;
        }

        .status-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .status-enabled {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .status-disabled {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .status-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .status-enabled .status-title {
            color: #10b981;
        }

        .status-disabled .status-title {
            color: #f59e0b;
        }

        .status-description {
            color: #64748b;
            font-size: 1rem;
        }

        .setup-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
        }

        .step-number {
            background: #3b82f6;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
        }

        .step-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .step-description {
            color: #64748b;
            line-height: 1.6;
        }

        .qr-container {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            border: 2px dashed #3b82f6;
            margin: 20px 0;
        }

        .qr-code {
            max-width: 200px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .secret-display {
            background: #1f2937;
            color: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            text-align: center;
            margin: 20px 0;
            word-break: break-all;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #1d4ed8;
        }

        .warning-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .warning-box h4 {
            color: #92400e;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .warning-box p {
            color: #92400e;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .step {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../Medecin/index.php" class="back-link">
            ← Retour au tableau de bord
        </a>

        <div class="header">
            <h1>🔐 Authentification à Deux Facteurs</h1>
            <p>Sécurisez votre compte avec Google Authenticator</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statut actuel du MFA -->
        <div class="status-card <?php echo $user['secret_mfa'] ? 'status-enabled' : 'status-disabled'; ?>">
            <div class="status-icon">
                <?php echo $user['secret_mfa'] ? '🔒' : '🔓'; ?>
            </div>
            <div class="status-title">
                MFA <?php echo $user['secret_mfa'] ? 'Activé' : 'Désactivé'; ?>
            </div>
            <div class="status-description">
                <?php if ($user['secret_mfa']): ?>
                    Votre compte est protégé par l'authentification à deux facteurs.
                <?php else: ?>
                    Votre compte n'est pas encore protégé par l'authentification à deux facteurs.
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$user['secret_mfa']): ?>
            <!-- Section d'activation du MFA -->
            <?php if (!isset($_SESSION['temp_mfa_secret'])): ?>
                <!-- Étape 1: Générer le secret -->
                <div class="setup-section">
                    <h2 class="section-title">
                        🚀 Activer l'authentification à deux facteurs
                    </h2>
                    
                    <div class="step">
                        <div class="step-title">
                            <span class="step-number">1</span>
                            Télécharger Google Authenticator
                        </div>
                        <div class="step-description">
                            Installez l'application Google Authenticator sur votre smartphone depuis :
                            <br>• <strong>Android :</strong> Google Play Store
                            <br>• <strong>iOS :</strong> App Store
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-title">
                            <span class="step-number">2</span>
                            Générer votre clé secrète
                        </div>
                        <div class="step-description">
                            Cliquez sur le bouton ci-dessous pour générer votre clé secrète unique.
                        </div>
                    </div>

                    <form method="POST" style="text-align: center;">
                        <input type="hidden" name="action" value="generate_secret">
                        <button type="submit" class="btn btn-primary">
                            🔑 Générer ma clé secrète
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Étape 2: Scanner le QR code et vérifier -->
                <div class="setup-section">
                    <h2 class="section-title">
                        📱 Configuration de Google Authenticator
                    </h2>
                    
                    <div class="step">
                        <div class="step-title">
                            <span class="step-number">3</span>
                            Scanner le QR Code
                        </div>
                        <div class="step-description">
                            Ouvrez Google Authenticator et scannez ce QR code :
                        </div>
                        
                        <div class="qr-container">
                            <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code MFA" class="qr-code">
                            <p style="margin-top: 15px; color: #64748b;">
                                Ou saisissez manuellement cette clé :
                            </p>
                            <div class="secret-display">
                                <?php echo htmlspecialchars($_SESSION['temp_mfa_secret']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-title">
                            <span class="step-number">4</span>
                            Vérifier le code
                        </div>
                        <div class="step-description">
                            Saisissez le code à 6 chiffres affiché dans Google Authenticator :
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="verify_and_activate">
                            <div class="form-group">
                                <label for="verification_code">Code de vérification :</label>
                                <input type="text" 
                                       id="verification_code" 
                                       name="verification_code" 
                                       placeholder="123456" 
                                       maxlength="6" 
                                       pattern="[0-9]{6}" 
                                       required
                                       style="text-align: center; font-size: 1.5rem; letter-spacing: 0.2em;">
                            </div>
                            <div style="text-align: center;">
                                <button type="submit" class="btn btn-primary">
                                    ✅ Activer le MFA
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="warning-box">
                        <h4>⚠️ Important</h4>
                        <p>Assurez-vous de bien sauvegarder votre clé secrète dans un endroit sûr. Si vous perdez votre téléphone, vous aurez besoin de cette clé pour reconfigurer l'authentification.</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Section de gestion du MFA activé -->
            <div class="setup-section">
                <h2 class="section-title">
                    ⚙️ Gestion du MFA
                </h2>
                
                <p style="margin-bottom: 20px; color: #64748b;">
                    L'authentification à deux facteurs est active sur votre compte. 
                    Vous devrez saisir un code de Google Authenticator à chaque connexion.
                </p>

                <div class="warning-box">
                    <h4>⚠️ Attention</h4>
                    <p>Si vous désactivez le MFA, votre compte sera moins sécurisé. Cette action est déconseillée.</p>
                </div>

                <form method="POST" style="text-align: center;" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver l\'authentification à deux facteurs ?');">
                    <input type="hidden" name="action" value="disable_mfa">
                    <button type="submit" class="btn btn-danger">
                        🔓 Désactiver le MFA
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Informations supplémentaires -->
        <div class="setup-section">
            <h2 class="section-title">
                ℹ️ Informations importantes
            </h2>
            
            <div style="color: #64748b; line-height: 1.6;">
                <p><strong>Qu'est-ce que l'authentification à deux facteurs ?</strong></p>
                <p>L'authentification à deux facteurs (MFA) ajoute une couche de sécurité supplémentaire à votre compte. 
                Même si quelqu'un connaît votre mot de passe, il ne pourra pas accéder à votre compte sans le code généré par votre téléphone.</p>
                
                <br>
                
                <p><strong>Que faire si je perds mon téléphone ?</strong></p>
                <p>Contactez immédiatement l'administrateur système à <strong>support@mediplus.sn</strong> 
                pour désactiver temporairement le MFA sur votre compte.</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus sur le champ de code de vérification
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('verification_code');
            if (codeInput) {
                codeInput.focus();
                
                // Formater automatiquement le code (espaces tous les 3 chiffres)
                codeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }
                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html>
