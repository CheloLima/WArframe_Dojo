<?php
// Diese Datei stellt die Datenbankverbindung her.
// Sie sollte von anderen PHP-Skripten eingebunden werden, die DB-Zugriff benötigen.

// Lade die Konfiguration, falls nicht bereits geschehen.
// Dies ist eine Sicherheitsmaßnahme, falls db.php direkt aufgerufen wird oder
// die einbindende Datei config.php nicht geladen hat.
if (!defined('DB_HOST')) {
    $configPath = __DIR__ . '/../config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Kritischer Fehler: Konfiguration nicht gefunden.
        // Im Produktivbetrieb sollte hier eine sicherere Fehlerbehandlung erfolgen,
        // z.B. Logging und eine generische Fehlermeldung für den User.
        die("Kritischer Fehler: Die Konfigurationsdatei config.php konnte nicht gefunden werden.");
    }
}

// PDO DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO-Optionen
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Fehler als Exceptions werfen
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Standard-Fetch-Modus auf assoziatives Array setzen
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Echte Prepared Statements verwenden, um SQL-Injection besser vorzubeugen
];

try {
    // Erstelle die PDO-Instanz (Datenbankverbindung)
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Fehler bei der Datenbankverbindung
    // Im Debug-Modus detaillierte Fehlermeldung anzeigen
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        // Detaillierte Fehlermeldung für Entwickler
        $errorMessage = "Datenbankverbindungsfehler: " . $e->getMessage() . "<br>";
        $errorMessage .= "DSN: " . htmlspecialchars($dsn) . "<br>";
        // In einer echten Anwendung sollten sensible Daten wie User/Pass hier nicht ausgegeben werden.
        // $errorMessage .= "User: " . htmlspecialchars(DB_USER) . "<br>";
        // $errorMessage .= "Passwort verwendet: " . (empty(DB_PASS) ? "Nein" : "Ja") . "<br>";
        $errorMessage .= "Code: " . $e->getCode() . "<br>";
        $errorMessage .= "Datei: " . $e->getFile() . "<br>";
        $errorMessage .= "Zeile: " . $e->getLine() . "<br>";
        // $errorMessage .= "Trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
        // Auf Produktivsystemen sollte eine generische Fehlermeldung angezeigt und der Fehler geloggt werden.
        error_log("Datenbankverbindungsfehler: " . $e->getMessage());
        die($errorMessage); // Beende das Skript mit der Fehlermeldung
    } else {
        // Generische Fehlermeldung für den Endbenutzer
        error_log("Schwerwiegender Datenbankfehler auf " . (defined('SITE_NAME') ? SITE_NAME : 'der Webseite') . ": " . $e->getMessage());
        die("Ein interner Serverfehler ist aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Administrator, falls das Problem weiterhin besteht.");
    }
}

// Die Variable $pdo steht nun global zur Verfügung für Skripte, die diese Datei einbinden.
// Beispiel für eine Abfrage:
// $stmt = $pdo->query("SELECT name FROM users");
// while ($row = $stmt->fetch()) {
//     echo htmlspecialchars($row['name']) . "<br>";
// }

?>
