<?php
/**
 * Einkaufs-App Backend API
 * Sichere Schnittstelle zu MariaDB mit PDO und Prepared Statements.
 */

// Fehlerberichterstattung für Sicherheit deaktivieren und stattdessen loggen
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Helper: Umgebungsvariablen aus .env laden
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Kommentare ignorieren
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Anführungszeichen entfernen
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

// .env laden (liegt ein Verzeichnis über api/)
loadEnv(__DIR__ . '/../.env');

// DB-Zugangsdaten holen
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'einkaufs_app';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

// Verbindung herstellen
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    header('Content-Type: application/json', true, 500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

// CORS- und Content-Type-Header setzen
header('Content-Type: application/json; charset=utf-8');

// Hilfsfunktion: JSON-Eingabe lesen
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// Aktion bestimmen
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'group_details':
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID fehlt']);
                break;
            }
            $stmt = $pdo->prepare("SELECT id, name, pin FROM `groups` WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $group = $stmt->fetch();
            if ($group) {
                echo json_encode(['success' => true, 'data' => $group]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Gruppe nicht gefunden']);
            }
            break;

        case 'join_group':
            $input = getJsonInput();
            $pin = trim($input['pin'] ?? '');
            if (empty($pin)) {
                echo json_encode(['success' => false, 'error' => 'PIN fehlt']);
                break;
            }
            if (!preg_match('/^\d{6}$/', $pin)) {
                echo json_encode(['success' => false, 'error' => 'PIN muss eine 6-stellige Zahl sein']);
                break;
            }
            $stmt = $pdo->prepare("SELECT id, name, pin FROM `groups` WHERE pin = :pin LIMIT 1");
            $stmt->execute(['pin' => $pin]);
            $group = $stmt->fetch();
            if ($group) {
                echo json_encode(['success' => true, 'data' => $group]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Ungültige PIN']);
            }
            break;

        case 'create_group':
            $input = getJsonInput();
            $name = trim($input['name'] ?? '');
            $pin = trim($input['pin'] ?? '');
            if (empty($name) || empty($pin)) {
                echo json_encode(['success' => false, 'error' => 'Name oder PIN fehlt']);
                break;
            }
            if (!preg_match('/^\d{6}$/', $pin)) {
                echo json_encode(['success' => false, 'error' => 'PIN muss eine 6-stellige Zahl sein']);
                break;
            }
            if (mb_strlen($name, 'UTF-8') > 255) {
                echo json_encode(['success' => false, 'error' => 'Gruppenname ist zu lang (max. 255 Zeichen)']);
                break;
            }
            
            // Prüfen, ob PIN bereits vergeben ist
            $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE pin = :pin LIMIT 1");
            $stmt->execute(['pin' => $pin]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Diese PIN ist bereits vergeben. Bitte wähle eine andere.']);
                break;
            }

            // ID generieren: name-slug + random-number
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
            $id = $slug . '-' . rand(100, 999);

            $stmt = $pdo->prepare("INSERT INTO `groups` (id, name, pin) VALUES (:id, :name, :pin)");
            $stmt->execute(['id' => $id, 'name' => $name, 'pin' => $pin]);
            echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $name, 'pin' => $pin]]);
            break;

        case 'get_stores':
            $groupId = $_GET['group_id'] ?? '';
            if (empty($groupId)) {
                echo json_encode(['success' => false, 'error' => 'Gruppen-ID fehlt']);
                break;
            }
            $stmt = $pdo->prepare("SELECT id, name, group_id FROM `stores` WHERE group_id = :group_id ORDER BY name");
            $stmt->execute(['group_id' => $groupId]);
            $stores = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $stores]);
            break;

        case 'add_store':
            $input = getJsonInput();
            $name = trim($input['name'] ?? '');
            $groupId = trim($input['group_id'] ?? '');
            if (empty($name) || empty($groupId)) {
                echo json_encode(['success' => false, 'error' => 'Name oder Gruppen-ID fehlt']);
                break;
            }
            if (mb_strlen($name, 'UTF-8') > 255 || mb_strlen($groupId, 'UTF-8') > 100) {
                echo json_encode(['success' => false, 'error' => 'Eingabewerte überschreiten Längenbegrenzung']);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO `stores` (name, group_id) VALUES (:name, :group_id)");
            $stmt->execute(['name' => $name, 'group_id' => $groupId]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'delete_store':
            $input = getJsonInput();
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM `stores` WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_shopping_list':
            $groupId = $_GET['group_id'] ?? '';
            $storeId = $_GET['store_id'] ?? '';
            if (empty($groupId)) {
                echo json_encode(['success' => false, 'error' => 'Gruppen-ID fehlt']);
                break;
            }

            $sql = "SELECT sl.id, sl.item_id, sl.quantity, sl.price, sl.store_id,
                           i.name, i.category, i.unit, i.last_known_price AS lastPrice
                    FROM `shopping_list` sl
                    LEFT JOIN `items` i ON sl.item_id = i.id
                    WHERE sl.group_id = :group_id AND sl.is_checked = 0";
            
            $params = ['group_id' => $groupId];
            if ($storeId !== '') {
                $sql .= " AND sl.store_id = :store_id";
                $params['store_id'] = (int)$storeId;
            } else {
                $sql .= " AND sl.store_id IS NULL";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            // Für Abwärtskompatibilität schachteln wir auch das "items"-Objekt
            foreach ($list as &$row) {
                $row['items'] = [
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'unit' => $row['unit'],
                    'last_known_price' => (float)$row['lastPrice']
                ];
                $row['price'] = (float)$row['price'];
                $row['quantity'] = (int)$row['quantity'];
                $row['store_id'] = $row['store_id'] !== null ? (int)$row['store_id'] : null;
                $row['id'] = (int)$row['id'];
                $row['item_id'] = (int)$row['item_id'];
            }

            echo json_encode(['success' => true, 'data' => $list]);
            break;

        case 'get_catalog':
            $search = $_GET['search'] ?? '';
            if ($search !== '') {
                $stmt = $pdo->prepare("SELECT id, name, category, unit, last_known_price FROM `items` WHERE name LIKE :search ORDER BY name");
                $stmt->execute(['search' => '%' . $search . '%']);
            } else {
                $stmt = $pdo->query("SELECT id, name, category, unit, last_known_price FROM `items` ORDER BY name");
            }
            $catalog = $stmt->fetchAll();
            foreach ($catalog as &$row) {
                $row['id'] = (int)$row['id'];
                $row['last_known_price'] = (float)$row['last_known_price'];
            }
            echo json_encode(['success' => true, 'data' => $catalog]);
            break;

        case 'add_item':
            $input = getJsonInput();
            $name = trim($input['name'] ?? '');
            $unit = trim($input['unit'] ?? 'Stück');
            $category = trim($input['category'] ?? 'Allgemein');
            $price = (float)($input['last_known_price'] ?? 0.0);
            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Name fehlt']);
                break;
            }
            if (mb_strlen($name, 'UTF-8') > 255 || mb_strlen($unit, 'UTF-8') > 50 || mb_strlen($category, 'UTF-8') > 100) {
                echo json_encode(['success' => false, 'error' => 'Eingabewerte überschreiten Längenbegrenzung']);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO `items` (name, unit, category, last_known_price) VALUES (:name, :unit, :category, :price)");
            $stmt->execute(['name' => $name, 'unit' => $unit, 'category' => $category, 'price' => $price]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'delete_item':
            $input = getJsonInput();
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM `items` WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_quantity':
            $input = getJsonInput();
            $id = (int)($input['id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            if ($id <= 0 || $quantity < 1) {
                echo json_encode(['success' => false, 'error' => 'Ungültige Parameter']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE `shopping_list` SET quantity = :quantity WHERE id = :id");
            $stmt->execute(['quantity' => $quantity, 'id' => $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_price':
            $input = getJsonInput();
            $id = (int)($input['id'] ?? 0);
            $price = (float)($input['price'] ?? 0.0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE `shopping_list` SET price = :price WHERE id = :id");
            $stmt->execute(['price' => $price, 'id' => $id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_in_catalog':
            $input = getJsonInput();
            $itemId = (int)($input['item_id'] ?? 0);
            $groupId = trim($input['group_id'] ?? '');
            $storeId = $input['store_id'] !== '' && $input['store_id'] !== null ? (int)$input['store_id'] : null;
            $currentlyOnList = (bool)($input['currently_on_list'] ?? false);

            if ($itemId <= 0 || empty($groupId) || mb_strlen($groupId, 'UTF-8') > 100) {
                echo json_encode(['success' => false, 'error' => 'Ungültige Parameter']);
                break;
            }

            if ($currentlyOnList) {
                // Von Einkaufsliste entfernen
                $stmt = $pdo->prepare("DELETE FROM `shopping_list` WHERE item_id = :item_id AND group_id = :group_id AND is_checked = 0");
                $stmt->execute(['item_id' => $itemId, 'group_id' => $groupId]);
            } else {
                // Zu Einkaufsliste hinzufügen
                $stmt = $pdo->prepare("INSERT INTO `shopping_list` (item_id, group_id, store_id) VALUES (:item_id, :group_id, :store_id)");
                $stmt->execute(['item_id' => $itemId, 'group_id' => $groupId, 'store_id' => $storeId]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'buy_item':
            $input = getJsonInput();
            $listId = (int)($input['list_id'] ?? 0);
            $itemId = (int)($input['item_id'] ?? 0);
            $price = (float)($input['price'] ?? 0.0);
            $storeId = $input['store_id'] !== '' && $input['store_id'] !== null ? (int)$input['store_id'] : null;

            if ($listId <= 0 || $itemId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Ungültige Parameter']);
                break;
            }

            $pdo->beginTransaction();
            try {
                if ($price > 0) {
                    // Preis in Preishistorie eintragen
                    $stmt = $pdo->prepare("INSERT INTO `price_history` (item_id, price, store_id) VALUES (:item_id, :price, :store_id)");
                    $stmt->execute(['item_id' => $itemId, 'price' => $price, 'store_id' => $storeId]);

                    // Letzten bekannten Preis im Artikel aktualisieren
                    $stmt = $pdo->prepare("UPDATE `items` SET last_known_price = :price WHERE id = :id");
                    $stmt->execute(['price' => $price, 'id' => $itemId]);
                }

                // Aus Einkaufsliste löschen
                $stmt = $pdo->prepare("DELETE FROM `shopping_list` WHERE id = :id");
                $stmt->execute(['id' => $listId]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $ex) {
                $pdo->rollBack();
                error_log("Fehler beim Abschließen des Kaufs: " . $ex->getMessage());
                echo json_encode(['success' => false, 'error' => 'Kauf konnte nicht verbucht werden']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
            break;
    }
} catch (Exception $e) {
    error_log("Fehler in der API: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Interner Serverfehler']);
}
