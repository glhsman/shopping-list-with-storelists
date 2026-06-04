<?php
/**
 * Hilfsskript zum Importieren der konvertierten SQL-Daten auf dem Liveserver.
 * Nach der Ausführung sperrt sich das Skript selbst. Bitte nach Gebrauch löschen!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Datenbank-Datenimport (Supabase -> MariaDB)</h2>";

$lockFile = __DIR__ . '/import.lock';
$sqlFile = __DIR__ . '/../import_data.sql';
$envFile = __DIR__ . '/../.env';

// 1. Sicherheitsprüfung: Ist der Import gesperrt?
if (file_exists($lockFile)) {
    echo "<p style='color: orange; font-weight: bold;'>🔒 Import gesperrt!</p>";
    echo "<p>Der Import wurde bereits ausgeführt. Um ihn erneut auszuführen, lösche bitte <code>api/import.lock</code> auf deinem Server.</p>";
    exit;
}

// 2. Button zum Starten anzeigen, falls nicht gepostet
if (!isset($_POST['run_import'])) {
    echo "<p>Dieses Skript importiert deine exportierten Supabase-Daten aus der Datei <code>import_data.sql</code> in deine MariaDB.</p>";
    if (!file_exists($sqlFile)) {
        echo "<p style='color: red;'>[FEHLER] Die Datei <code>import_data.sql</code> wurde nicht gefunden!</p>";
        echo "<p>Bitte lade die Datei <code>import_data.sql</code> ins Hauptverzeichnis deines Servers hoch.</p>";
    } else {
        echo "<form method='POST'><button type='submit' name='run_import' value='1' style='padding: 10px 20px; font-weight: bold; background: #06b6d4; color: white; border: none; border-radius: 5px; cursor: pointer;'>Import jetzt starten</button></form>";
    }
    exit;
}

// 3. Helper: .env laden
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'/.*'$/", $value, $matches)) {
                $value = $matches[1];
            }
            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
        }
    }
    return true;
}

if (!loadEnv($envFile)) {
    echo "<p style='color: red;'>[FEHLER] .env-Datei konnte nicht geladen werden!</p>";
    exit;
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'einkaufs_app';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p style='color: green;'>[OK] Verbindung zur MariaDB hergestellt.</p>";
    
    $sqlContent = file_get_contents($sqlFile);
    echo "<p>Führe SQL-Import aus...</p>";
    
    // SQL ausführen
    $pdo->exec($sqlContent);
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Import erfolgreich abgeschlossen!</p>";
    
    // Sperrdatei anlegen
    file_put_contents($lockFile, "Locked on " . date('Y-m-d H:i:s'));
    echo "<p>Sicherheitsdatei <code>api/import.lock</code> wurde angelegt.</p>";
    echo "<p style='color: orange;'>⚠️ <b>WICHTIG:</b> Bitte lösche die Dateien <code>api/run_import.php</code> und <code>import_data.sql</code> jetzt von deinem Server!</p>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>[FEHLER] Import fehlgeschlagen!</p>";
    echo "<p>Fehlermeldung: <code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
}
