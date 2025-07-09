<?php
define('PAGE_TITLE', 'Startseite');
require_once __DIR__ . '/includes/header.php';

// Lade die config.php, um Zugriff auf Discord Client ID etc. zu haben
// header.php lädt sie zwar auch, aber zur Sicherheit hier explizit, falls header.php geändert wird.
if (!defined('DISCORD_CLIENT_ID')) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        // Kritischer Fehler, wenn config.php fehlt und Konstanten nicht definiert sind.
        echo "<p class='message error'>Fehler: Konfigurationsdatei nicht gefunden. Die Seite kann nicht korrekt funktionieren.</p>";
        // Beende hier oder zeige eine eingeschränkte Seite an.
        // Für den Login-Button ist DISCORD_CLIENT_ID essentiell.
    }
}

// Discord OAuth2 URL generieren
$discord_oauth_url = '';
if (defined('DISCORD_CLIENT_ID') && defined('DISCORD_REDIRECT_URI')) {
    $params = [
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'identify guilds.members.read', // Benötigte Scopes
        // 'prompt' => 'consent' // Optional: Erzwingt erneute Zustimmung (für Tests manchmal nützlich)
    ];
    $discord_oauth_url = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
}

?>

<div class="hero-section">
    <h1>Willkommen im Echo Sol Dojo!</h1>
    <p>Deine zentrale Anlaufstelle für alles rund um unseren Warframe Clan.</p>
    <?php if (!$is_logged_in && !empty($discord_oauth_url)): ?>
        <a href="<?php echo htmlspecialchars($discord_oauth_url); ?>" class="button btn-login">
            <img src="<?php echo BASE_URL; ?>/assets/images/discord_logo.svg" alt="Discord Logo" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;">
            Login / Mitgliederbereich mit Discord
        </a>
    <?php elseif (!$is_logged_in): ?>
        <p class="message error">Discord Login ist derzeit nicht korrekt konfiguriert. Bitte informiere einen Administrator.</p>
    <?php endif; ?>
    <?php if ($is_logged_in): ?>
        <p>Willkommen zurück, <?php echo htmlspecialchars($_SESSION['discord_username']); ?>!</p>
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="button">Zum Dashboard</a>
    <?php endif; ?>
</div>

<div class="content-section">
    <h2>Über Uns</h2>
    <p>Das Echo Sol Dojo ist mehr als nur ein Clan – wir sind eine Gemeinschaft von Tenno, die gemeinsam die Herausforderungen des Origin Systems meistern. Egal ob du ein erfahrener Veteran oder ein neuer Spieler bist, hier findest du Unterstützung, Kameradschaft und jede Menge Action.</p>
    <p>Diese Plattform dient als unser zentraler Hub für Informationen, Organisation und Community-Aktivitäten. Hier findest du:</p>
    <ul>
        <li>Aktuelle Clan-Nachrichten (MOTD)</li>
        <li>Eine Mitgliederliste, um andere Tenno kennenzulernen</li>
        <li>Dein persönliches Profil, das du gestalten kannst</li>
        <li>Nützliche Tools wie den Build-Kosten-Rechner</li>
        <li>Und vieles mehr!</li>
    </ul>
</div>

<div class="content-section">
    <h2>Wie trete ich bei?</h2>
    <p>Wenn du noch kein Mitglied unseres Discord-Servers bist, ist das der erste Schritt! Klicke auf den folgenden Link, um unserem Server beizutreten:</p>
    <p><a href="https://discord.gg/chelo" target="_blank" class="button button-secondary">Tritt unserem Discord-Server bei!</a></p>
    <p>Sobald du auf dem Server bist und die entsprechenden Rollen erhalten hast (normalerweise nach einer kurzen Vorstellung), kannst du dich hier mit deinem Discord-Account einloggen, um Zugriff auf den Mitgliederbereich zu erhalten.</p>
</div>

<?php
// Fehlermeldung für "NOT_ON_SERVER" aus callback.php
if (isset($_GET['error']) && $_GET['error'] === 'NOT_ON_SERVER') {
    echo '<div class="message error global-message">Zugriff verweigert. Du musst Mitglied auf unserem Discord-Server sein, um dich einzuloggen. <a href="https://discord.gg/chelo" target="_blank">Tritt hier bei!</a></div>';
}
if (isset($_GET['error']) && $_GET['error'] === 'NO_ROLE_START') {
    // Diese Meldung wird angezeigt, wenn der Bot "NO_ROLE" zurückgibt, aber der User noch kein Profil hat.
    // Die normale NO_ROLE-Meldung kommt von error_no_role.php
    echo '<div class="message warning global-message">Du bist auf unserem Discord Server, aber dir fehlt die benötigte Rolle für den vollen Zugriff. Bitte melde dich bei Chelo Lima (Discord ID: 1020559274012852294) auf Discord.</div>';
}

// Platzhalter für ein SVG Discord Logo (inline oder als Datei)
// Wenn du ein assets/images Verzeichnis erstellst und dort ein discord_logo.svg ablegst:
// Zum Beispiel von: https://discord.com/branding (Logo herunterladen und als SVG speichern)
// Stelle sicher, dass das Verzeichnis web_app/assets/images existiert oder passe den Pfad an.
// Für dieses Beispiel gehe ich davon aus, dass es existiert.
if (!file_exists(__DIR__ . '/assets/images/discord_logo.svg')) {
    // Erstelle ein Dummy-Verzeichnis, falls es fehlt, um Fehler zu vermeiden, wenn das Bild nicht da ist.
    // Im echten Projekt sollte das Bild vorhanden sein.
    if (!is_dir(__DIR__ . '/assets/images')) {
        // mkdir(__DIR__ . '/assets/images', 0755, true); // PHP Erstellung, FTP Upload ist besser
        echo "<p class='message info'>Hinweis: Discord Logo unter `assets/images/discord_logo.svg` nicht gefunden. Der Login-Button wird ohne Logo angezeigt.</p>";
    }
}
?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
