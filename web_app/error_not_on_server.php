<?php
define('PAGE_TITLE', 'Nicht auf dem Server');
require_once __DIR__ . '/includes/header.php';

// Diese Seite dient als Fallback. Normalerweise wird die Meldung direkt auf index.php angezeigt.
?>

<div class="card error-page">
    <div class="card-header">
        <h2>Zugriff verweigert</h2>
    </div>
    <div class="message error">
        <p>Zugriff verweigert. Du musst Mitglied auf unserem Discord-Server sein, um dich einloggen und auf den Mitgliederbereich zugreifen zu können.</p>
        <p>Bitte tritt zuerst unserem Discord-Server bei und versuche es dann erneut.</p>
    </div>
    <p class="mt-2">
        <a href="https://discord.gg/chelo" target="_blank" class="button button-secondary">
            <img src="<?php echo BASE_URL; ?>/assets/images/discord_logo.svg" alt="Discord Logo" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 8px;">
            Tritt unserem Discord-Server bei!
        </a>
    </p>
    <p class="mt-1">
        <a href="<?php echo BASE_URL; ?>/index.php" class="button">Zurück zur Startseite</a>
    </p>
</div>

<?php
// Kleiner Hinweis, dass das Discord Logo benötigt wird.
if (!file_exists(__DIR__ . '/assets/images/discord_logo.svg')) {
    if (!is_dir(__DIR__ . '/assets/images')) {
        // mkdir(__DIR__ . '/assets/images', 0755, true);
    }
    echo "<p class='message info text-center mt-2' style='font-size:0.8em;'>Hinweis: Discord Logo unter `assets/images/discord_logo.svg` nicht gefunden. Der Button wird ohne Logo angezeigt.</p>";
}
?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
