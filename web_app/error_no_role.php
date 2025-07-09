<?php
define('PAGE_TITLE', 'Fehlende Berechtigung');
require_once __DIR__ . '/includes/header.php';

// Temporäre Discord-Infos aus der Session holen, falls vorhanden (von callback.php gesetzt)
$temp_discord_username = isset($_SESSION['temp_discord_username']) ? htmlspecialchars($_SESSION['temp_discord_username']) : 'Tenno';
unset($_SESSION['temp_discord_id']); // Aufräumen
unset($_SESSION['temp_discord_username']);

$admin_discord_id = defined('SUPER_ADMIN_DISCORD_ID') ? SUPER_ADMIN_DISCORD_ID : '1020559274012852294'; // Fallback, falls nicht in config definiert
?>

<div class="card error-page">
    <div class="card-header">
        <h2>Fehlende Berechtigung</h2>
    </div>
    <div class="message error">
        <p>Hallo <?php echo $temp_discord_username; ?>,</p>
        <p>du bist zwar auf unserem Discord Server, aber dein Account hat noch nicht die notwendige Rolle ("freigeschalteter Tenno" oder ähnlich), um auf den Mitgliederbereich zugreifen zu können.</p>
        <p>Bitte melde dich auf unserem Discord Server bei <strong>Chelo Lima</strong> (Discord User ID: <code><?php echo $admin_discord_id; ?></code>), damit dir die entsprechende Rolle zugewiesen werden kann.</p>
        <p>Alternativ kannst du auch im entsprechenden Channel auf unserem Discord nachfragen.</p>
    </div>
    <p class="mt-2">
        <a href="<?php echo BASE_URL; ?>/index.php" class="button">Zurück zur Startseite</a>
        <a href="https://discord.gg/chelo" target="_blank" class="button button-secondary">Unseren Discord Server öffnen</a>
    </p>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
