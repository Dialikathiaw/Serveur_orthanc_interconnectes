<?php
/**
 * Script de test pour vérifier le bon fonctionnement du système
 */

echo "<h2>Test du système de vérification des serveurs Orthanc</h2>";

// Test de connexion à la base de données
echo "<h3>1. Test de connexion à la base de données</h3>";
$host = 'localhost';
$db   = 'orthanc_app';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à la base de données réussie<br>";
    
    // Vérifier la structure de la table
    $stmt = $pdo->query("DESCRIBE serveurs_orthanc");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Colonnes de la table : " . implode(', ', $columns) . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "<br>";
    exit;
}

// Test de récupération des serveurs
echo "<h3>2. Test de récupération des serveurs</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM serveurs_orthanc");
    $result = $stmt->fetch();
    echo "✅ Nombre de serveurs en base : " . $result['total'] . "<br>";
    
    if ($result['total'] > 0) {
        $stmt = $pdo->query("SELECT nom, url, actif FROM serveurs_orthanc LIMIT 3");
        $serveurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($serveurs as $serveur) {
            $statut = $serveur['actif'] ? 'Actif' : 'Inactif';
            echo "<li>{$serveur['nom']} - {$serveur['url']} ({$statut})</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "❌ Erreur lors de la récupération : " . $e->getMessage() . "<br>";
}

// Test des APIs
echo "<h3>3. Test des APIs</h3>";

// Test API liste_serveurs.php
echo "<strong>API liste_serveurs.php :</strong><br>";
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/liste_serveurs.php';
$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ API fonctionnelle - " . count($data) . " serveurs retournés<br>";
    } else {
        echo "❌ Réponse JSON invalide<br>";
    }
} else {
    echo "❌ Impossible d'accéder à l'API<br>";
}

// Test API admin_serveurs.php
echo "<strong>API admin_serveurs.php :</strong><br>";
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/admin_serveurs.php?action=lister';
$response = @file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && !isset($data['erreur'])) {
        echo "✅ API admin fonctionnelle<br>";
    } else {
        echo "❌ Erreur dans l'API admin : " . ($data['erreur'] ?? 'Inconnue') . "<br>";
    }
} else {
    echo "❌ Impossible d'accéder à l'API admin<br>";
}

// Test de vérification d'un serveur
echo "<h3>4. Test de vérification de serveur</h3>";
function testerServeur($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 400;
}

// Tester avec un serveur connu (Google par exemple)
if (testerServeur('https://www.google.com')) {
    echo "✅ Fonction de vérification des serveurs opérationnelle<br>";
} else {
    echo "❌ Problème avec la fonction de vérification<br>";
}

// Informations système
echo "<h3>5. Informations système</h3>";
echo "PHP Version : " . phpversion() . "<br>";
echo "Extensions disponibles : ";
$extensions = ['curl', 'pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext ";
    } else {
        echo "❌ $ext ";
    }
}
echo "<br>";

echo "<h3>6. Instructions de configuration</h3>";
echo "<ol>";
echo "<li>Assurez-vous que tous les tests ci-dessus sont ✅</li>";
echo "<li>Configurez la tâche CRON avec : <code>*/5 * * * * /usr/bin/php " . __DIR__ . "/cron_verification.php</code></li>";
echo "<li>Vérifiez les logs dans : <code>" . __DIR__ . "/cron_verification.log</code></li>";
echo "<li>Testez l'interface d'administration à : <a href='../admin.html'>admin.html</a></li>";
echo "</ol>";
?>
