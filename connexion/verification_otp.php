<?php
session_start();

// Vérifier si l'utilisateur est en cours de connexion avec MFA
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_mfa_secret'])) {
    header('Location: connexion.html');
    exit();
}

// Inclure l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

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
$error = '';

// Traitement de la vérification OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = $_POST['otp_code'] ?? '';
    
    if (empty($otp_code)) {
        $error = "Veuillez saisir le code de vérification.";
    } else {
        $secret = $_SESSION['temp_mfa_secret'];
        
        // Vérifier le code OTP
        if ($googleAuthenticator->checkCode($secret, $otp_code)) {
            // Code correct, finaliser la connexion
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['user_email'] = $_SESSION['temp_user_email'];
            $_SESSION['user_prenom'] = $_SESSION['temp_user_prenom'];
            $_SESSION['user_nom'] = $_SESSION['temp_user_nom'];
            $_SESSION['user_profession'] = $_SESSION['temp_user_profession'];
            $_SESSION['user_statut'] = $_SESSION['temp_user_statut'];

            // Nettoyer les variables temporaires
            unset($_SESSION['temp_user_id'], $_SESSION['temp_user_email'], $_SESSION['temp_user_prenom'], 
                  $_SESSION['temp_user_nom'], $_SESSION['temp_user_profession'], $_SESSION['temp_user_statut'], 
                  $_SESSION['temp_mfa_secret']);

            // Redirection selon le rôle
            $role = $_SESSION['user_profession'];
            switch ($role) {
                case 'admin':
                    header("Location: ../Administrateur/interface_admin.html");
                    break;
                    
                case 'medecin':
                    header("Location: ../Medecin/index.php");
                    break;
                    
                case 'infirmier':
                    header("Location: ../Medecin/index.php");
                    break;
                    
                case 'epidemiologiste':
                    header("Location: ../Medecin/index.php");
                    break;
                    
                default:
                    header("Location: ../Medecin/index.php");
                    break;
            }
            exit();
            
        } else {
            $error = "Code de vérification incorrect. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification OTP - MediPlus</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 30px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 
                0 25px 50px rgba(59, 130, 246, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.8),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            text-align: center;
            animation: cardEntrance 1s ease-out;
        }

        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .security-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .user-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #374151;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .otp-input {
            width: 100%;
            padding: 20px;
            background: rgba(248, 250, 252, 0.8);
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-radius: 15px;
            color: #1f2937;
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.3em;
            font-weight: 700;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .otp-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .otp-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }

        .verify-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .verify-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transition: left 0.4s ease;
            z-index: -1;
        }

        .verify-btn:hover::before {
            left: 0;
        }

        .verify-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
        }

        .verify-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .help-text {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .back-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link:hover {
            color: #1d4ed8;
            transform: translateX(-3px);
        }

        .countdown {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 10px;
            margin-top: 15px;
            color: #92400e;
            font-size: 0.85rem;
        }

        @media (max-width: 480px) {
            .verification-card {
                padding: 30px 25px;
                margin: 10px;
            }

            .title {
                font-size: 1.5rem;
            }

            .otp-input {
                font-size: 1.5rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="security-icon">🔐</div>
        
        <h1 class="title">Vérification de Sécurité</h1>
        <p class="subtitle">
            Saisissez le code à 6 chiffres affiché dans votre application Google Authenticator
        </p>

        <div class="user-info">
            👤 Connexion en cours pour : <strong><?php echo htmlspecialchars($_SESSION['temp_user_prenom'] . ' ' . $_SESSION['temp_user_nom']); ?></strong>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="form-group">
                <label for="otp_code">Code de vérification :</label>
                <input type="text" 
                       id="otp_code" 
                       name="otp_code" 
                       class="otp-input"
                       placeholder="123456" 
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       autocomplete="off"
                       required>
            </div>

            <button type="submit" class="verify-btn" id="verifyBtn">
                ✅ Vérifier et se connecter
            </button>
        </form>

        <div class="help-text">
            💡 Le code change toutes les 30 secondes. Si le code ne fonctionne pas, attendez le prochain code.
        </div>

        <div class="countdown" id="countdown">
            ⏱️ Nouveau code dans : <span id="timer">30</span> secondes
        </div>

        <div style="margin-top: 20px;">
            <a href="connexion.html" class="back-link">
                ← Retour à la connexion
            </a>
        </div>
    </div>

    <script>
        // Auto-focus et formatage du champ OTP
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp_code');
            const verifyBtn = document.getElementById('verifyBtn');
            
            // Focus automatique
            otpInput.focus();
            
            // Formatage automatique (uniquement des chiffres)
            otpInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 6) {
                    value = value.substring(0, 6);
                }
                e.target.value = value;
                
                // Auto-submit si 6 chiffres
                if (value.length === 6) {
                    setTimeout(() => {
                        document.getElementById('otpForm').submit();
                    }, 500);
                }
            });

            // Empêcher la soumission multiple
            document.getElementById('otpForm').addEventListener('submit', function() {
                verifyBtn.disabled = true;
                verifyBtn.textContent = '🔄 Vérification en cours...';
            });

            // Countdown timer (simulation)
            let timeLeft = 30;
            const timerElement = document.getElementById('timer');
            
            function updateTimer() {
                timerElement.textContent = timeLeft;
                if (timeLeft > 0) {
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                } else {
                    timeLeft = 30; // Reset
                    setTimeout(updateTimer, 1000);
                }
            }
            
            updateTimer();
        });

        // Gestion du collage (paste)
        document.getElementById('otp_code').addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = paste.replace(/\D/g, '').substring(0, 6);
            this.value = numbers;
            
            if (numbers.length === 6) {
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 500);
            }
        });
    </script>
</body>
</html>
