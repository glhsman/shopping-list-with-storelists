<?php
/**
 * Datenbank-Diagnose-Skript für den Webserver
 * Hilft bei der Fehlersuche, falls die API keine Verbindung aufbauen kann.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Einkaufs-App Web-Diagnose</h2>";

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo "<p style='color: red; font-weight: bold;'>[FEHLER] Die .env-Datei wurde im Webserver-Verzeichnis nicht gefunden!</p>";
    echo "<p>Erwarteter Pfad: <code>" . htmlspecialchars(realpath(__DIR__ . '/..') ?: __DIR__ . '/..') . "/.env</code></p>";
    echo "<p><i>Hinweis: Unter Windows sind Dateien, die mit einem Punkt beginnen, oft versteckt. Stelle sicher, dass du die <code>.env</code>-Datei beim Kopieren mitkopiert hast.</i></p>";
} else {
    echo "<p style='color: green;'>[OK] .env-Datei existiert.</p>";
    
    // .env einlesen
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            $config[$key] = $value;
        }
    }
    
    echo "<h3>Geladene Zugangsdaten:</h3>";
    echo "<ul>";
    echo "<li><b>DB_HOST:</b> " . htmlspecialchars($config['DB_HOST'] ?? 'nicht gesetzt (Standard: localhost)') . "</li>";
    echo "<li><b>DB_PORT:</b> " . htmlspecialchars($config['DB_PORT'] ?? 'nicht gesetzt (Standard: 3306)') . "</li>";
    echo "<li><b>DB_NAME:</b> " . htmlspecialchars($config['DB_NAME'] ?? 'nicht gesetzt (Standard: einkaufs_app)') . "</li>";
    echo "<li><b>DB_USER:</b> " . htmlspecialchars($config['DB_USER'] ?? 'nicht gesetzt (Standard: root)') . "</li>";
    echo "<li><b>DB_PASS:</b> " . (isset($config['DB_PASS']) ? (empty($config['DB_PASS']) ? '<i>(leer)</i>' : '********') : 'nicht gesetzt') . "</li>";
    echo "</ul>";
    
    // Verbindungstest
    $dbHost = $config['DB_HOST'] ?? 'localhost';
    $dbPort = $config['DB_PORT'] ?? '3306';
    $dbName = $config['DB_NAME'] ?? 'einkaufs_app';
    $dbUser = $config['DB_USER'] ?? 'root';
    $dbPass = $config['DB_PASS'] ?? '';
    
    try {
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5 // 5 Sekunden Timeout für schnelles Feedback
        ]);
        echo "<p style='color: green; font-weight: bold;'>[ERFOLG] Verbindung zur Datenbank war erfolgreich!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red; font-weight: bold;'>[FEHLER] Verbindung fehlgeschlagen!</p>";
        echo "<p><b>Fehlermeldung:</b> <code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
        
        // Hilfreiche Tipps je nach Fehler
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<p>💡 <i>Tipp: Benutzername oder Passwort sind falsch. Prüfe deine <code>.env</code>-Datei.</i></p>";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "<p>💡 <i>Tipp: Die Datenbank existiert nicht. Hast du das Schema importiert oder die DB manuell angelegt?</i></p>";
        } elseif (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'timed out') !== false) {
            echo "<p>💡 <i>Tipp: Der Datenbank-Server läuft nicht oder blockiert die Verbindung von diesem Webserver. Prüfe IP-Adresse und Port.</i></p>";
        }
    }
}
