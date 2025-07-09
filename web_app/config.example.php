<?php

// --- Datenbank Konfiguration ---
define('DB_HOST', 'localhost'); // Oft localhost, ansonsten vom Hoster bereitgestellt
define('DB_NAME', 'chelo_prime');    // Dein Datenbankname
define('DB_USER', 'chelo_prime');    // Dein Datenbankbenutzer
define('DB_PASS', 'DEIN_MYSQL_PASSWORT'); // Dein Datenbankpasswort
define('DB_CHARSET', 'utf8mb4');

// --- Discord OAuth2 Konfiguration ---
define('DISCORD_CLIENT_ID', 'DEINE_DISCORD_CLIENT_ID'); // Deine Discord App Client ID
define('DISCORD_CLIENT_SECRET', 'DEIN_DISCORD_CLIENT_SECRET'); // Dein Discord App Client Secret
define('DISCORD_REDIRECT_URI', 'https://dojo.chelo.lat/callback.php'); // Deine Redirect URI (muss exakt mit der im Discord Developer Portal übereinstimmen!)
// Stelle sicher, dass die callback.php am Ende steht, wenn deine Callback-URL direkt auf die Datei zeigt.
// Falls du URL-Rewriting verwendest und /callback ohne .php funktioniert, passe es entsprechend an.

// --- Python Verifizierungs-Bot Konfiguration ---
// Die vollständige URL zum /verify-user/ Endpunkt deines Python Bots
// Beispiel: 'http://123.45.67.89:8000/verify-user/' oder 'https://yourbot.yourdomain.com/verify-user/'
define('VERIFY_BOT_URL', 'http://DEINE_BOT_SERVER_IP_ODER_DOMAIN:PORT/verify-user/');

// Das Secret API Key, das sowohl hier als auch in der .env Datei des Python Bots identisch sein muss.
define('BOT_API_SECRET_KEY', 'DEIN_STARKES_GEHEIMES_API_KEY');


// --- Allgemeine Seiteneinstellungen ---
define('SITE_NAME', 'Echo Sol Dojo');
define('BASE_URL', 'https://dojo.chelo.lat'); // Deine Basis-URL ohne Slash am Ende

// --- Fehlerbehandlung (für Entwicklung true, für Produktion false) ---
define('DEBUG_MODE', true);

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
// Kann meistens so belassen werden.
ini_set('session.name', 'ECHOSOLDOJOSESSID');

// Stellt sicher, dass Sessions nur über HTTP(S) übertragen werden und nicht per JavaScript zugänglich sind.
ini_set('session.cookie_httponly', 1);

// Verwendet nur Cookies für die Session-ID und nicht auch URL-Parameter.
ini_set('session.use_only_cookies', 1);

// Bei HTTPS-Verbindungen: Sende das Cookie nur über sichere Verbindungen.
// Wenn deine Seite ausschließlich über HTTPS läuft, setze dies auf 1.
// Für lokale Entwicklung ohne HTTPS kann es auf 0 bleiben oder auskommentiert werden.
// Auf einer Live-Seite mit HTTPS sollte es '1' sein.
// ini_set('session.cookie_secure', 1);


// --- Wichtige Discord IDs (informativ, werden primär im Bot und schema.sql verwendet) ---
// Diese sind hier nur zur Information und als Referenz, die eigentliche Prüfung findet
// im Python Bot bzw. die Admin-Erstellung in schema.sql statt.
define('SUPER_ADMIN_DISCORD_ID', '1020559274012852294');
define('DISCORD_SERVER_ID_TO_CHECK', '1212768310312042566');
define('DISCORD_ROLE_ID_TO_CHECK', '1391269160994209873');

?>
