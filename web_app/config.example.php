<?php

// --- Datenbank Konfiguration ---
define('DB_HOST', 'localhost'); // Oft localhost, ansonsten vom Hoster bereitgestellt
define('DB_NAME', 'Warframe_Dojo');    // Dein Datenbankname
define('DB_USER', 'Warframe_Dojo');    // Dein Datenbankbenutzer
define('DB_PASS', '76~Ktx4d8'); // Dein Datenbankpasswort // LOKAL TEST PASSWORT, BITTE ÄNDERN!
define('DB_CHARSET', 'utf8mb4');

// --- Discord OAuth2 Konfiguration ---
define('DISCORD_CLIENT_ID', '1392357117142372436'); // Deine Discord App Client ID
define('DISCORD_CLIENT_SECRET', 'YERKzJLoMyv6r2kRTWRhdXjoRBE8in9m'); // Dein Discord App Client Secret // LOKAL TEST SECRET, BITTE ÄNDERN!
define('DISCORD_REDIRECT_URI', 'https://dojo.chelo.lat/callback.php'); // Deine Redirect URI (muss exakt mit der im Discord Developer Portal übereinstimmen!)
// Stelle sicher, dass die callback.php am Ende steht, wenn deine Callback-URL direkt auf die Datei zeigt.
// Falls du URL-Rewriting verwendest und /callback ohne .php funktioniert, passe es entsprechend an.

// --- Python Verifizierungs-Bot Konfiguration ---
// Die vollständige URL zum /verify-user/ Endpunkt deines Python Bots
// Beispiel: 'http://123.45.67.89:8000/verify-user/' oder 'https://yourbot.yourdomain.com/verify-user/'
define('VERIFY_BOT_URL', 'http://45.13.225.40:8000/verify-user/');

// Das Secret API Key, das sowohl hier als auch in der .env Datei des Python Bots identisch sein muss.
define('BOT_API_SECRET_KEY', 'CheloLima_Security_Dojo69420'); // LOKAL TEST KEY, BITTE ÄNDERN!


// --- Allgemeine Seiteneinstellungen ---
define('SITE_NAME', 'Endo Reserve Bank - Warframe Clan DE'); // Angepasst
define('BASE_URL', 'https://dojo.chelo.lat'); // Deine Basis-URL ohne Slash am Ende

// --- Fehlerbehandlung (für Entwicklung true, für Produktion false) ---
define('DEBUG_MODE', true); // Für Entwicklung true, für Produktion false

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// --- Session Einstellungen ---
// Name der Session, um Konflikte mit anderen Anwendungen auf derselben Domain zu vermeiden.
define('SESSION_NAME', 'ENDORESERVERBANKSESSID'); // Angepasst
ini_set('session.name', SESSION_NAME);


// Stellt sicher, dass Sessions nur über HTTP(S) übertragen werden und nicht per JavaScript zugänglich sind.
ini_set('session.cookie_httponly', 1);

// Verwendet nur Cookies für die Session-ID und nicht auch URL-Parameter.
ini_set('session.use_only_cookies', 1);

// Bei HTTPS-Verbindungen: Sende das Cookie nur über sichere Verbindungen.
// Wenn deine Seite ausschließlich über HTTPS läuft, setze dies auf 1.
// Für lokale Entwicklung ohne HTTPS kann es auf 0 bleiben oder auskommentiert werden.
// Auf einer Live-Seite mit HTTPS sollte es '1' sein.
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
   ini_set('session.cookie_secure', 1);
}


// --- Wichtige Discord IDs (informativ, werden primär im Bot und schema.sql verwendet) ---
// Diese sind hier nur zur Information und als Referenz, die eigentliche Prüfung findet
// im Python Bot bzw. die Admin-Erstellung in schema.sql statt.
define('SUPER_ADMIN_DISCORD_ID', '1020559274012852294');
define('DISCORD_SERVER_ID_TO_CHECK', '1212768310312042566');
define('DISCORD_ROLE_ID_TO_CHECK', '1391269160994209873');

?>
