<?php
// Session starten, um auf Session-Variablen zugreifen und sie zerstören zu können
// Die header.php macht das bereits, aber zur Sicherheit, falls diese Seite direkt aufgerufen wird
// und die Reihenfolge der Inkludierungen sich ändert.
if (session_status() == PHP_SESSION_NONE) {
    // Session-Optionen sollten idealerweise vor session_start() gesetzt werden,
    // wie in header.php. Hier nehmen wir an, dass sie bereits gesetzt wurden,
    // oder verwenden Standardwerte, falls header.php nicht geladen wurde.
    ini_set('session.name', 'ECHOSOLDOJOSESSID'); // Sicherstellen, dass der korrekte Session-Name verwendet wird
    session_start();
}

// Alle Session-Variablen löschen
$_SESSION = array();

// Falls Cookies für die Session verwendet werden (Standard), das Session-Cookie löschen.
// Hinweis: Dies zerstört die Session, nicht nur die Session-Daten!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Zum Schluss die Session zerstören.
session_destroy();

// Lade die Basiskonfiguration für BASE_URL
// Normalerweise durch header.php, aber da wir hier schon die Session zerstört haben,
// und header.php ggf. versucht, auf Session-Daten zuzugreifen, laden wir es manuell, falls nötig.
if (!defined('BASE_URL')) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        // Fallback, falls config.php nicht existiert
        define('BASE_URL', '.'); // Relative URL als Fallback
    }
}

// Weiterleitung zur Startseite mit einer Erfolgsmeldung
// Die Erfolgsmeldung wird per GET-Parameter übergeben, da die Session zerstört ist.
header("Location: " . BASE_URL . "/index.php?logout_success=1");
exit; // Wichtig, um sicherzustellen, dass nach der Weiterleitung kein weiterer Code ausgeführt wird.
?>
