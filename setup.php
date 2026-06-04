<?php
/**
 * Einkaufs-App - Automatisches Datenbank-Setup (v1.5.7)
 * Initialisiert die Tabellen und sichert sich nach erfolgreichem Durchlauf selbst.
 */

// Fehlerberichterstattung aktivieren für die Ausführung
ini_set('display_errors', 1);
error_reporting(E_ALL);

$lockFile = __DIR__ . '/setup.lock';
$sqlFile = __DIR__ . '/database.sql';
$envFile = __DIR__ . '/.env';

// 1. Helper: Umgebungsvariablen aus .env laden
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
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

$envLoaded = loadEnv($envFile);

// Design-Header
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank Setup - Einkaufs-App</title>
    <style>
        :root {
            --bg-color: #020617;
            --card-bg: rgba(30, 41, 59, 0.4);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #06b6d4;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
        }
        .setup-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        .status-box {
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .status-line {
            margin-bottom: 0.5rem;
        }
        .status-ok { color: var(--success); }
        .status-err { color: var(--danger); }
        .status-info { color: var(--primary); }
        .status-warn { color: var(--warning); }
        
        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fde68a;
        }
        .btn {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            border: none;
            color: white;
            padding: 0.85rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            margin-top: 1rem;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>
<div class="setup-card">
    <h1>Datenbank Setup</h1>
    <div class="subtitle">Einkaufs-App &bull; Tabellen initialisieren</div>

    <?php
    // 2. Sicherheitsprüfung: Ist das Setup gesperrt?
    if (file_exists($lockFile)) {
        ?>
        <div class="alert alert-warning">
            <strong>🔒 Setup gesperrt!</strong><br>
            Das Setup wurde bereits erfolgreich ausgeführt und die Datei <code>setup.lock</code> wurde erstellt.<br><br>
            Um das Setup erneut auszuführen, lösche bitte die Datei <code>setup.lock</code> auf deinem Server.
        </div>
        <a href="index.php" class="btn">Zur App wechseln</a>
        <?php
        echo "</div></body></html>";
        exit;
    }

    // Falls der POST-Request noch nicht gesendet wurde, Button zum Starten anzeigen
    if (!isset($_POST['run_setup'])) {
        ?>
        <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 2rem;">
            Dieses Skript initialisiert die benötigten Tabellen in deiner MariaDB/MySQL-Datenbank unter Verwendung der Konfiguration aus deiner <code>.env</code>-Datei.
        </p>
        
        <?php if (!$envLoaded): ?>
            <div class="alert alert-danger">
                <strong>Fehler:</strong> Die Datei <code>.env</code> konnte nicht geladen werden! Bitte lade deine <code>.env</code>-Datei hoch, bevor du das Setup startest.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>Bereit:</strong> <code>.env</code>-Datei erfolgreich geladen.
            </div>
            <form method="POST">
                <button type="submit" name="run_setup" value="1" class="btn">Setup jetzt ausführen</button>
            </form>
        <?php endif; ?>
        <?php
        echo "</div></body></html>";
        exit;
    }

    // 3. Setup ausführen
    echo '<div class="status-box">';
    $hasErrors = false;

    // Log Funktion
    function writeLog($msg, $type = 'info') {
        $class = 'status-info';
        if ($type === 'ok') $class = 'status-ok';
        if ($type === 'err') $class = 'status-err';
        if ($type === 'warn') $class = 'status-warn';
        echo '<div class="status-line ' . $class . '">[' . strtoupper($type) . '] ' . htmlspecialchars($msg) . '</div>';
        flush();
        ob_flush();
    }

    writeLog("Setup gestartet...", "info");

    if (!$envLoaded) {
        writeLog(".env-Datei fehlt!", "err");
        $hasErrors = true;
    } else {
        writeLog(".env-Datei geladen.", "ok");
    }

    if (!file_exists($sqlFile)) {
        writeLog("database.sql-Datei fehlt an Pfad: $sqlFile", "err");
        $hasErrors = true;
    } else {
        writeLog("database.sql-Schema gefunden.", "ok");
    }

    if (!$hasErrors) {
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbPort = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['DB_NAME'] ?? 'einkaufs_app';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASS'] ?? '';

        writeLog("Verbinde mit Server $dbHost:$dbPort...", "info");

        try {
            // Erst ohne Datenbanknamen verbinden, um zu prüfen, ob die DB erstellt werden muss
            $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 8
            ]);
            writeLog("Erfolgreich mit Datenbank-Server verbunden.", "ok");

            // Prüfen ob die DB existiert, falls nicht, erstellen versuchen
            writeLog("Prüfe Datenbank '$dbName'...", "info");
            $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($dbName));
            $dbExists = (bool)$stmt->fetch();

            if (!$dbExists) {
                writeLog("Datenbank '$dbName' existiert nicht. Versuche zu erstellen...", "warn");
                try {
                    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    writeLog("Datenbank '$dbName' erfolgreich erstellt.", "ok");
                } catch (PDOException $ex) {
                    writeLog("Konnte Datenbank nicht erstellen: " . $ex->getMessage(), "err");
                    writeLog("Bitte erstelle die Datenbank '$dbName' manuell in deinem Hosting-Panel und starte das Setup erneut.", "warn");
                    $hasErrors = true;
                }
            } else {
                writeLog("Datenbank '$dbName' existiert bereits.", "ok");
            }

            if (!$hasErrors) {
                // Datenbank auswählen
                $pdo->exec("USE `$dbName`");
                writeLog("Datenbank '$dbName' ausgewählt.", "ok");

                // SQL-Datei einlesen
                $sqlContent = file_get_contents($sqlFile);
                writeLog("Führe Tabellen-Erstellung aus...", "info");

                // SQL ausführen
                $pdo->exec($sqlContent);
                writeLog("Tabellen erfolgreich angelegt oder bereits vorhanden.", "ok");

                // Sicherheits-Lockdatei erstellen
                if (file_put_contents($lockFile, "Setup locked on " . date('Y-m-d H:i:s')) !== false) {
                    writeLog("Sicherheitsdatei setup.lock erfolgreich angelegt.", "ok");
                } else {
                    writeLog("WARNUNG: Konnte setup.lock nicht erstellen. Bitte erstelle diese Datei manuell im Hauptverzeichnis!", "warn");
                }
            }

        } catch (PDOException $e) {
            writeLog("Datenbankverbindung fehlgeschlagen: " . $e->getMessage(), "err");
            $hasErrors = true;
        }
    }

    echo '</div>'; // Ende status-box

    if ($hasErrors) {
        ?>
        <div class="alert alert-danger">
            <strong>❌ Setup fehlgeschlagen!</strong><br>
            Bitte korrigiere die oben genannten Fehler und führe das Setup erneut aus.
        </div>
        <form method="POST">
            <button type="submit" name="run_setup" value="1" class="btn">Erneut versuchen</button>
        </form>
        <?php
    } else {
        ?>
        <div class="alert alert-success">
            <strong>🎉 Setup erfolgreich abgeschlossen!</strong><br>
            Alle Tabellen wurden erfolgreich initialisiert. Aus Sicherheitsgründen wurde die Datei <code>setup.lock</code> erstellt.
        </div>
        <a href="index.php" class="btn">Direkt zur App wechseln</a>
        <?php
    }
    ?>
</div>
</body>
</html>
