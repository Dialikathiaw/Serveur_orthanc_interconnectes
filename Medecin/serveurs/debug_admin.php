<?php
// Script de debug pour tester admin_serveurs.php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test du script admin_serveurs.php</h2>";

// Test 1: Lister les serveurs
echo "<h3>1. Test de listage des serveurs</h3>";
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/admin_serveurs.php?action=lister';
echo "<strong>URL testée :</strong> $url<br>";

$response = file_get_contents($url);
echo "<strong>Réponse brute :</strong><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<strong>✅ JSON valide</strong><br>";
    echo "<strong>Nombre de serveurs :</strong> " . (is_array($data) ? count($data) : 'N/A') . "<br>";
} else {
    echo "<strong>❌ JSON invalide :</strong> " . json_last_error_msg() . "<br>";
}

// Test 2: Simuler un ajout
echo "<h3>2. Test d'ajout de serveur</h3>";
$postData = http_build_query([
    'action' => 'ajouter',
    'nom' => 'Test Serveur Debug',
    'ville' => 'Test Ville',
    'url' => 'http://test.example.com:8042',
    'actif' => 1
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/admin_serveurs.php';
$response = file_get_contents($url, false, $context);

echo "<strong>Réponse brute :</strong><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<strong>✅ JSON valide</strong><br>";
    if (isset($data['succes'])) {
        echo "<strong>✅ Ajout réussi :</strong> " . $data['message'] . "<br>";
    } else {
        echo "<strong>⚠️ Erreur :</strong> " . ($data['erreur'] ?? 'Inconnue') . "<br>";
    }
} else {
    echo "<strong>❌ JSON invalide :</strong> " . json_last_error_msg() . "<br>";
}

// Test 3: Vérifier la base de données
echo "<h3>3. Vérification de la base de données</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=orthanc_app;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM serveurs_orthanc");
    $result = $stmt->fetch();
    echo "<strong>✅ Connexion base OK</strong><br>";
    echo "<strong>Nombre total de serveurs :</strong> " . $result['total'] . "<br>";
    
    // Afficher les derniers serveurs
    $stmt = $pdo->query("SELECT id, nom, ville FROM serveurs_orthanc ORDER BY id DESC LIMIT 3");
    $serveurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<strong>Derniers serveurs :</strong><br>";
    foreach ($serveurs as $serveur) {
        echo "- ID {$serveur['id']}: {$serveur['nom']} ({$serveur['ville']})<br>";
    }
    
} catch (PDOException $e) {
    echo "<strong>❌ Erreur base :</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Instructions :</strong></p>";
echo "<ul>";
echo "<li>Si tous les tests sont ✅, le problème vient du JavaScript</li>";
echo "<li>Si il y a des ❌, corrigez les erreurs affichées</li>";
echo "<li>Vérifiez que la réponse JSON est bien formée</li>";
echo "</ul>";
?>
