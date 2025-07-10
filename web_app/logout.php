<?php
// Temporäres Fehler-Reporting für die Diagnose des 500er Fehlers
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lade Konfiguration für Session-Namen, BASE_URL etc.
// Dies wird config.php laden, welche auch ini_set für Session-Parameter enthält.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Kritischer Fehler, wenn config.php fehlt, da SESSION_NAME benötigt wird.
    // Für den Fall der Fälle einen Default setzen, aber das sollte nicht passieren.
    if (!defined('SESSION_NAME')) define('SESSION_NAME', 'ENDORESERVERBANKSESSID');
    if (!defined('BASE_URL')) define('BASE_URL', '.');
    // In einer Produktivumgebung hier eher abbrechen oder loggen.
}

// Starte die Session explizit mit dem korrekten Namen, falls noch nicht geschehen.
// config.php sollte ini_set('session.name', SESSION_NAME) bereits aufgerufen haben.
// session_start() muss nach allen relevanten ini_set() Aufrufen erfolgen.
if (session_status() == PHP_SESSION_NONE) {
    if (defined('SESSION_NAME')) { // Sicherstellen, dass config.php SESSION_NAME definiert hat
        // Die ini_set Aufrufe für httponly, use_only_cookies, secure sind in config.php
    } else {
        // Fallback, falls SESSION_NAME nicht definiert (sollte nicht sein)
        ini_set('session.name', 'ENDORESERVERBANKSESSID');
    }
    // Weitere Session-Optionen, die nicht in config.php stehen, könnten hier gesetzt werden,
    // aber die wichtigsten (Name, cookie-Optionen) kommen aus config.php
    session_start();
}


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
// Testweise auskommentiert, um Fehlerquelle zu isolieren:
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");
// header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Datum in der Vergangenheit

// Die BASE_URL Konstante sollte durch config.php bereits definiert sein.

// Weiterleitung zur Startseite mit einer Erfolgsmeldung
// Die Erfolgsmeldung wird per GET-Parameter übergeben, da die Session zerstört ist.
// Stelle sicher, dass BASE_URL definiert ist.
if (!defined('BASE_URL')) {
    // Fallback, falls BASE_URL aus irgendeinem Grund nicht definiert wurde (sollte nicht passieren)
    define('BASE_URL', '.');
}

$redirect_target = rtrim(BASE_URL, '/') . "/index.php?logout_success=1";
if (isset($_GET['action']) && $_GET['action'] === 'resync_avatar' && isset($_GET['return_to'])) {
    // Spezifische Weiterleitung nach Avatar-Resync-Logout
    // index.php wird den return_to Parameter an callback.php weitergeben müssen,
    // oder callback.php direkt aufrufen, falls das sicher implementierbar ist.
    // Einfacher: index.php mit speziellem Hinweis und Link zum Login.
    $return_page = basename(filter_var($_GET['return_to'], FILTER_SANITIZE_URL)); // Nur Dateiname als Sicherheit
    $redirect_target = rtrim(BASE_URL, '/') . "/index.php?logout_success=1&action=resync_avatar&return_to=" . urlencode($return_page);
}

header("Location: " . $redirect_target);
exit; // Wichtig, um sicherzustellen, dass nach der Weiterleitung kein weiterer Code ausgeführt wird.
// PHP End-Tag entfernt
// Duplizierter Code wurde entfernt. Der obere Teil der Datei enthält bereits die korrekte Logik.
