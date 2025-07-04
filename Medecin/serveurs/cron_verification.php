<?php
/**
 * Script CRON pour vérifier la disponibilité des serveurs Orthanc
 * À exécuter toutes les 5 minutes via crontab :
 * */5 * * * * /usr/bin/php /chemin/vers/votre/projet/serveurs/cron_verification.php
 */

// Configuration de la base de données
$host = 'localhost';
$db   = 'orthanc_app';
$user = 'root';
$pass = ''; // Mets ton mot de passe ici si nécessaire
$charset = 'utf8mb4';

// Fichier de log
$logFile = __DIR__ . '/cron_verification.log';

function ecrireLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    ecrireLog("Début de la vérification des serveurs");
    
    // Récupérer tous les serveurs
    $sql = "SELECT id, nom, url, actif FROM serveurs_orthanc";
    $stmt = $pdo->query($sql);
    $serveurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $serveursVerifies = 0;
    $serveursDisponibles = 0;
    
    foreach ($serveurs as $serveur) {
        $disponible = verifierDisponibiliteServeur($serveur['url']);
        $serveursVerifies++;
        
        if ($disponible) {
            $serveursDisponibles++;
            // Mettre à jour la dernière synchronisation
            $sqlUpdate = "UPDATE serveurs_orthanc 
                         SET derniere_synchro = NOW(), 
                             statut_ping = 'OK' 
                         WHERE id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$serveur['id']]);
            
            ecrireLog("Serveur {$serveur['nom']} ({$serveur['url']}) : DISPONIBLE");
        } else {
            // Marquer comme indisponible
            $sqlUpdate = "UPDATE serveurs_orthanc 
                         SET statut_ping = 'ERREUR' 
                         WHERE id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$serveur['id']]);
            
            ecrireLog("Serveur {$serveur['nom']} ({$serveur['url']}) : INDISPONIBLE");
        }
    }
    
    ecrireLog("Vérification terminée : $serveursDisponibles/$serveursVerifies serveurs disponibles");
    
} catch (PDOException $e) {
    ecrireLog("Erreur de base de données : " . $e->getMessage());
} catch (Exception $e) {
    ecrireLog("Erreur générale : " . $e->getMessage());
}

function verifierDisponibiliteServeur($url) {
    // Timeout de 10 secondes
    $timeout = 10;
    
    // Méthode 1 : Vérification HTTP avec cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Orthanc-Monitor/1.0');
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request seulement
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Considérer comme disponible si le code HTTP est dans la plage 200-399
        if ($result !== false && $httpCode >= 200 && $httpCode < 400) {
            return true;
        }
        
        // Log de l'erreur pour debug
        if (!empty($error)) {
            ecrireLog("Erreur cURL pour $url : $error");
        } else {
            ecrireLog("Code HTTP pour $url : $httpCode");
        }
    }
    
    // Méthode 2 : Vérification avec file_get_contents (fallback)
    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'method' => 'HEAD',
            'user_agent' => 'Orthanc-Monitor/1.0'
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    if ($result !== false) {
        return true;
    }
    
    // Méthode 3 : Ping simple du domaine (dernière option)
    $urlParts = parse_url($url);
    if (isset($urlParts['host'])) {
        $host = $urlParts['host'];
        $ping = exec("ping -c 1 -W 3 $host", $output, $returnVar);
        return $returnVar === 0;
    }
    
    return false;
}

// Fonction pour nettoyer les anciens logs (garder seulement les 30 derniers jours)
function nettoyerLogs() {
    global $logFile;
    
    if (!file_exists($logFile)) {
        return;
    }
    
    $lines = file($logFile);
    $cutoffDate = date('Y-m-d', strtotime('-30 days'));
    $newLines = [];
    
    foreach ($lines as $line) {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            if ($matches[1] >= $cutoffDate) {
                $newLines[] = $line;
            }
        }
    }
    
    file_put_contents($logFile, implode('', $newLines));
}

// Nettoyer les logs une fois par jour (à minuit)
if (date('H:i') === '00:00') {
    nettoyerLogs();
    ecrireLog("Nettoyage des anciens logs effectué");
}
?>
