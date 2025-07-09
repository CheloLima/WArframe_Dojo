<?php
<?php
// header.php bindet config.php ein und startet die Session mit den korrekten Einstellungen.
// Es ist wichtig, dass dies geschieht, BEVOR wir versuchen, die Session zu manipulieren oder zu zerstören.
require_once __DIR__ . '/includes/header.php';

// Nun, da die Session korrekt gestartet wurde (mit dem Namen aus config.php),
// können wir sie sicher zerstören.

// 1. Alle Session-Variablen löschen
$_SESSION = []; // oder session_unset();

// 2. Das Session-Cookie löschen
// Dies ist wichtig, um den Browser anzuweisen, das Cookie zu entfernen.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), // Holt den korrekten Session-Namen
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Zum Schluss die Session serverseitig zerstören.
session_destroy();

// Um Browser-Caching der vorherigen (eingeloggten) Seite zu minimieren,
// könnten hier Caching-Header gesendet werden.
// Dies ist oft nicht zwingend nötig, wenn der Logout-Prozess serverseitig robust ist,
// aber kann in manchen Fällen helfen.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Datum in der Vergangenheit

// Die BASE_URL Konstante sollte durch header.php (via config.php) bereits definiert sein.
// Ein erneutes Laden von config.php ist nicht nötig und könnte fehlschlagen, wenn header.php sie schon geladen hat.

// Weiterleitung zur Startseite mit einer Erfolgsmeldung
// Die Erfolgsmeldung wird per GET-Parameter übergeben, da die Session zerstört ist.
// Stelle sicher, dass BASE_URL definiert ist.
if (!defined('BASE_URL')) {
    // Fallback, falls BASE_URL aus irgendeinem Grund nicht definiert wurde (sollte nicht passieren)
    define('BASE_URL', '.');
}
header("Location: " . rtrim(BASE_URL, '/') . "/index.php?logout_success=1");
exit; // Wichtig, um sicherzustellen, dass nach der Weiterleitung kein weiterer Code ausgeführt wird.
?>

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
