<?php
/**
 * Datenbank-Verifizierungsskript für MariaDB
 * Führt Verbindungstests und Tabellenprüfungen durch.
 */

// Fehlerberichterstattung aktivieren für CLI-Ausgabe
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== MariaDB Verbindungstest ===\n";

// Helper: Umgebungsvariablen aus .env laden
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    return true;
}

$envPath = __DIR__ . '/.env';
if (!loadEnv($envPath)) {
    echo "[FEHLER] .env-Datei konnte nicht geladen werden an Pfad: $envPath\n";
    exit(1);
}
echo "[INFO] .env-Datei erfolgreich geladen.\n";

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'einkaufs_app';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

echo "[INFO] Host: $dbHost:$dbPort\n";
echo "[INFO] Datenbank: $dbName\n";
echo "[INFO] Benutzer: $dbUser\n";

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "[ERFOLG] Erfolgreich mit MySQL-Server verbunden.\n";

    // Prüfen ob DB existiert
    $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($dbName));
    if (!$stmt->fetch()) {
        echo "[WARNUNG] Datenbank '$dbName' existiert nicht. Bitte erstelle diese oder führe 'database.sql' aus.\n";
        exit(1);
    }
    
    // DB auswählen
    $pdo->query("USE `$dbName`");
    echo "[ERFOLG] Datenbank '$dbName' ausgewählt.\n";

    // Erwartete Tabellen prüfen
    $expectedTables = ['groups', 'stores', 'items', 'price_history', 'shopping_list'];
    $existingTables = [];
    
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }

    $allExist = true;
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "[ERFOLG] Tabelle '$table' existiert.\n";
        } else {
            echo "[FEHLER] Tabelle '$table' fehlt!\n";
            $allExist = false;
        }
    }

    if ($allExist) {
        echo "=== VERIFIZIERUNG ERFOLGREICH ===\n";
        exit(0);
    } else {
        echo "=== VERIFIZIERUNG FEHLGESCHLAGEN ===\n";
        exit(1);
    }

} catch (PDOException $e) {
    echo "[FEHLER] Datenbankverbindung oder Query fehlgeschlagen: " . $e->getMessage() . "\n";
    exit(1);
}
