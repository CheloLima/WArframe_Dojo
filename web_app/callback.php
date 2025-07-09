<?php
define('PAGE_TITLE', 'Login Verarbeitung');
// header.php startet die Session und lädt config.php (implizit durch db.php oder direkt)
// und db.php für die Datenbankverbindung.
require_once __DIR__ . '/includes/header.php'; // Stellt sicher, dass Sessions gestartet sind und $is_logged_in etc. existieren
require_once __DIR__ . '/includes/db.php';     // Stellt $pdo bereit
require_once __DIR__ . '/includes/user_functions.php'; // Stellt Benutzerfunktionen bereit

// Sicherstellen, dass config.php geladen wurde und Konstanten verfügbar sind
if (!defined('DISCORD_CLIENT_ID') || !defined('DISCORD_CLIENT_SECRET') || !defined('DISCORD_REDIRECT_URI') || !defined('VERIFY_BOT_URL') || !defined('BOT_API_SECRET_KEY')) {
    $_SESSION['global_message'] = "Fehler: Die Serverkonfiguration ist unvollständig. Login nicht möglich.";
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// 1. Code von Discord erhalten
if (!isset($_GET['code'])) {
    $_SESSION['global_message'] = "Fehler: Kein Autorisierungscode von Discord erhalten.";
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
$discord_code = $_GET['code'];

// 2. Code gegen Access Token bei Discord eintauschen
$token_url = 'https://discord.com/api/oauth2/token';
$token_data = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $discord_code,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    // 'scope' => 'identify guilds.members.read' // Scope ist hier nicht mehr nötig, wurde bei authorize angefragt
];

$ch_token = curl_init();
curl_setopt($ch_token, CURLOPT_URL, $token_url);
curl_setopt($ch_token, CURLOPT_POST, 1);
curl_setopt($ch_token, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch_token, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_token, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$token_response = curl_exec($ch_token);
$token_response_data = json_decode($token_response, true);
curl_close($ch_token);

if (!isset($token_response_data['access_token'])) {
    $_SESSION['global_message'] = "Fehler beim Austausch des Codes gegen ein Access Token bei Discord.";
    $_SESSION['global_message_type'] = "error";
    if (defined('DEBUG_MODE') && DEBUG_MODE === true && isset($token_response_data['error_description'])) {
         $_SESSION['global_message'] .= " Details: " . htmlspecialchars($token_response_data['error_description']);
    } elseif (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $_SESSION['global_message'] .= " Raw Response: " . htmlspecialchars($token_response);
    }
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
$access_token = $token_response_data['access_token'];

// 3. Discord ID und Benutzerinformationen des Nutzers extrahieren
$user_info_url = 'https://discord.com/api/users/@me';
$ch_user = curl_init();
curl_setopt($ch_user, CURLOPT_URL, $user_info_url);
curl_setopt($ch_user, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_user, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);
$user_info_response = curl_exec($ch_user);
$user_info_data = json_decode($user_info_response, true);
curl_close($ch_user);

if (!isset($user_info_data['id']) || !isset($user_info_data['username'])) {
    $_SESSION['global_message'] = "Fehler beim Abrufen der Benutzerinformationen von Discord.";
    $_SESSION['global_message_type'] = "error";
    if (defined('DEBUG_MODE') && DEBUG_MODE === true && isset($user_info_data['message'])) {
         $_SESSION['global_message'] .= " Details: " . htmlspecialchars($user_info_data['message']);
    }
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$discord_id = $user_info_data['id'];
$discord_username = $user_info_data['username'];
// Avatar-URL zusammenbauen (kann null sein)
$discord_avatar_hash = $user_info_data['avatar'];
$discord_avatar_url = $discord_avatar_hash ? "https://cdn.discordapp.com/avatars/{$discord_id}/{$discord_avatar_hash}.png" : null;


// 4. API des Python-Bots aufrufen, um den Status des Nutzers zu prüfen
$bot_verify_url = rtrim(VERIFY_BOT_URL, '/') . '/' . $discord_id; // Stelle sicher, dass URL korrekt formatiert ist
$ch_bot = curl_init();
curl_setopt($ch_bot, CURLOPT_URL, $bot_verify_url);
curl_setopt($ch_bot, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_bot, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . BOT_API_SECRET_KEY, // Secret Token für Bot-API
    'Content-Type: application/json'
]);
$bot_response_raw = curl_exec($ch_bot);
$curl_error = curl_error($ch_bot);
$curl_errno = curl_errno($ch_bot);
curl_close($ch_bot);

if ($curl_errno > 0) {
    $_SESSION['global_message'] = "Fehler bei der Kommunikation mit dem Verifizierungs-Service (cURL Error).";
    $_SESSION['global_message_type'] = "error";
     if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $_SESSION['global_message'] .= " Details: " . htmlspecialchars($curl_error) . " (Code: {$curl_errno})";
    }
    error_log("cURL Error when contacting bot: {$curl_error} (Code: {$curl_errno}) - URL: {$bot_verify_url}");
    header("Location: " . BASE_URL . "/index.php?error=bot_unavailable");
    exit;
}

$bot_response_data = json_decode($bot_response_raw, true);

if (!$bot_response_data || !isset($bot_response_data['status'])) {
    $_SESSION['global_message'] = "Ungültige oder keine Antwort vom Verifizierungs-Service.";
    $_SESSION['global_message_type'] = "error";
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $_SESSION['global_message'] .= " Raw Response: " . htmlspecialchars($bot_response_raw);
    }
    error_log("Invalid response from bot. Raw: " . $bot_response_raw . " - URL: {$bot_verify_url}");
    header("Location: " . BASE_URL . "/index.php?error=bot_invalid_response");
    exit;
}

// 5. Logik nach API-Antwort
$bot_status = $bot_response_data['status'];

if ($bot_status === "SUCCESS") {
    // Nutzer in DB erstellen oder aktualisieren
    $user = getUserByDiscordId($pdo, $discord_id);
    if (!$user) {
        createUser($pdo, $discord_id, $discord_username, $discord_avatar_url);
        // Hole den neu erstellten User, um isAdmin etc. zu haben
        $user = getUserByDiscordId($pdo, $discord_id);
    } else {
        updateUserDiscordDetails($pdo, $discord_id, $discord_username, $discord_avatar_url);
        // Hole den aktualisierten User, falls sich z.B. isAdmin geändert haben könnte (extern)
        $user = getUserByDiscordId($pdo, $discord_id);
    }

    // Session-Variablen setzen
    $_SESSION['discord_id'] = $user['discord_id'];
    $_SESSION['discord_username'] = $user['discord_username'];
    $_SESSION['discord_avatar_url'] = $user['discord_avatar_url'];
    $_SESSION['custom_avatar_path'] = $user['custom_avatar_path']; // NEU: Custom Avatar Path in Session
    $_SESSION['isAdmin'] = (bool)$user['isAdmin'];
    $_SESSION['last_activity'] = time(); // Aktivität aktualisieren

    // State-Parameter auswerten für spezielle Weiterleitungen
    $final_redirect_url = '';
    if (isset($_GET['state'])) {
        $decoded_state = json_decode(base64_decode($_GET['state']), true);
        if (is_array($decoded_state) && isset($decoded_state['action']) && $decoded_state['action'] === 'resync_avatar' && isset($decoded_state['return_to'])) {
            $return_page = basename(filter_var($decoded_state['return_to'], FILTER_SANITIZE_URL));
            if (in_array($return_page, ['dashboard.php', 'profil.php'])) { // Whitelist für return_to Seiten
                $final_redirect_url = BASE_URL . '/' . $return_page;
                 $_SESSION['global_message'] = "Discord-Avatar erfolgreich synchronisiert!"; // Überschreibt normale Login-Nachricht
                 $_SESSION['global_message_type'] = "success";
            }
        }
    }

    if (!empty($final_redirect_url)) {
        header("Location: " . $final_redirect_url);
    } elseif (empty($user['warframe_ign'])) {
        $_SESSION['global_message'] = "Willkommen! Bitte vervollständige dein Profil mit deinem Warframe In-Game Namen (IGN).";
        $_SESSION['global_message_type'] = "info";
        header("Location: " . BASE_URL . "/profil-vervollstaendigen.php");
    } else {
        $_SESSION['global_message'] = "Erfolgreich eingeloggt!";
        $_SESSION['global_message_type'] = "success";
        header("Location: " . BASE_URL . "/dashboard.php");
    }
    exit;

} elseif ($bot_status === "NO_ROLE") {
    // Leite auf eine Fehlerseite mit dem Text: "Du bist kein freigeschalteter Tenno. Melde dich bei Chelo Lima (1020559274012852294)".
    // Speichere Discord Infos temporär, falls sie auf der Fehlerseite angezeigt werden sollen
    $_SESSION['temp_discord_id'] = $discord_id;
    $_SESSION['temp_discord_username'] = $discord_username;
    header("Location: " . BASE_URL . "/error_no_role.php");
    exit;

} elseif ($bot_status === "NOT_ON_SERVER") {
    // Zeige eine Fehlermeldung auf der aktuellen Seite an (z.B. über ein Pop-up) mit dem Text:
    // "Zugriff verweigert. Du musst Mitglied auf unserem Discord-Server sein." und dem Link: https://discord.gg/chelo.
    // Wir leiten zur Startseite mit einem GET-Parameter, um die Meldung dort anzuzeigen.
    header("Location: " . BASE_URL . "/index.php?error=NOT_ON_SERVER");
    exit;

} else {
    // Unbekannter Status vom Bot
    $_SESSION['global_message'] = "Unbekannter Status vom Verifizierungs-Service erhalten: " . htmlspecialchars($bot_status);
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/index.php?error=bot_unknown_status");
    exit;
}

// Fallback, sollte eigentlich nicht erreicht werden.
require_once __DIR__ . '/includes/footer.php';
?>
