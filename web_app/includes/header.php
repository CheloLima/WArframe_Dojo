<?php
// config.php sollte idealerweise einmal global geladen werden, z.B. in einer zentralen Datei,
// die dann von allen anderen Seiten eingebunden wird. Fürs Erste hier, falls direkt aufgerufen.
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    // Fallback, falls config.php noch nicht existiert oder der Pfad nicht stimmt.
    // Dies ist eher für die Entwicklungsphase. Im Live-Betrieb sollte config.php immer da sein.
    if (!defined('SITE_NAME')) define('SITE_NAME', 'Endo Reserve Bank');
    if (!defined('BASE_URL')) define('BASE_URL', '.');
}

if (session_status() == PHP_SESSION_NONE) {
    // Session-Optionen setzen, bevor session_start() aufgerufen wird
    // Der Name der Session wird durch die config.php gesetzt (via define('SESSION_NAME', ...) und ini_set).
    // Dieser Block ist ein Fallback, falls config.php nicht existiert ODER SESSION_NAME dort nicht definiert wurde.
    // Die config.php wird ZUERST geladen. Wenn dort ini_set('session.name', ...) steht, ist das maßgeblich.
    // Wenn config.php nur define('SESSION_NAME',...) hat, würde dieser Block hier den Namen setzen, falls SESSION_NAME definiert ist.
    if (defined('SESSION_NAME') && !headers_sent()) { // Nur setzen, wenn Konstante existiert und Header noch nicht gesendet
        // ini_set('session.name', SESSION_NAME); // Wird bereits in config.php gemacht
    } elseif (!headers_sent()) {
        // Fallback, falls SESSION_NAME nicht in config.php definiert wurde (sollte aber)
        // oder config.php gar nicht existiert.
        ini_set('session.name', 'ENDORESERVERBANKSESSID');
    }
    // Stellt sicher, dass Sessions nur über HTTP(S) übertragen werden und nicht per JavaScript zugänglich sind.
    ini_set('session.cookie_httponly', 1);
    // Verwendet nur Cookies für die Session-ID und nicht auch URL-Parameter.
    ini_set('session.use_only_cookies', 1);
    // Bei HTTPS-Verbindungen: Sende das Cookie nur über sichere Verbindungen.
    // Dies sollte in config.php oder hier basierend auf der Umgebung gesetzt werden.
    // if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    //    ini_set('session.cookie_secure', 1);
    // }
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['discord_id']);
$is_admin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;

// Automatisches Logout nach Inaktivität (Beispiel: 30 Minuten)
$timeout_duration = 1800; // 30 Minuten in Sekunden
if ($is_logged_in && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/index.php?session_expired=1"); // Leite zur Startseite mit Info
    exit;
}
$_SESSION['last_activity'] = time(); // Aktualisiere den Zeitstempel der letzten Aktivität

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(defined('PAGE_TITLE') ? PAGE_TITLE . ' - ' . SITE_NAME : SITE_NAME); ?></title>
    <meta name="description" content="Community-Plattform für den Warframe Clan Endo Reserve Bank."> <!-- Angepasst -->
    <!-- Favicon-Platzhalter - Ersetze dies durch deine eigenen Favicons -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon.ico" sizes="any">
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon.svg" type="image/svg+xml">
    <link rel.apple-touch-icon" href="<?php echo BASE_URL; ?>/apple-touch-icon.png">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">

    <!-- Hier könnten später noch weitere globale JS-Dateien oder CSS-Frameworks eingebunden werden -->
    <!-- Beispiel: <script src="<?php echo BASE_URL; ?>/js/main.js" defer></script> -->
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="logo-container">
                <!-- Optional: Logo-Bild -->
                <!-- <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="<?php echo SITE_NAME; ?> Logo" class="logo-image"> -->
                <a href="<?php echo BASE_URL; ?>/index.php" class="logo-text"><?php echo SITE_NAME; ?></a>
            </div>
            <nav class="main-navigation">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>/index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Startseite</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/mitglieder.php" class="<?php echo ($current_page === 'mitglieder.php' || $current_page === 'profil.php') ? 'active' : ''; ?>">Mitglieder</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/rechner.php" class="<?php echo ($current_page === 'rechner.php') ? 'active' : ''; ?>">Build Rechner</a></li>
                        <?php if ($is_admin): ?>
                            <li><a href="<?php echo BASE_URL; ?>/admin.php" class="<?php echo ($current_page === 'admin.php') ? 'active' : ''; ?>">Admin</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout (<?php echo htmlspecialchars(isset($_SESSION['discord_username']) ? $_SESSION['discord_username'] : ''); ?>)</a></li>
                    <?php else: ?>
                        <!-- Der Login-Button wird oft separat oder auf der Startseite prominent platziert -->
                        <!-- Hier könnte ein direkter Link sein, wenn der Login-Prozess nicht über einen Button auf index.php gestartet wird -->
                        <!-- <li><a href="<?php echo BASE_URL; ?>/login.php" class="<?php echo ($current_page === 'login.php') ? 'active' : ''; ?>">Login</a></li> -->
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="site-content">
        <div class="container">
            <!-- Hauptinhalt der Seite beginnt hier -->
            <?php
            // Globale Nachrichtenanzeige (z.B. für Session-Timeout)
            if (isset($_GET['session_expired']) && $_GET['session_expired'] == '1') {
                echo '<div class="message global-message info">Deine Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte logge dich erneut ein.</div>';
            }
            if (isset($_GET['logout_success']) && $_GET['logout_success'] == '1') {
                echo '<div class="message global-message success">Du wurdest erfolgreich ausgeloggt.</div>';
            }
            if (isset($_SESSION['global_message'])) {
                echo '<div class="message global-message ' . htmlspecialchars($_SESSION['global_message_type'] ?? 'info') . '">' . htmlspecialchars($_SESSION['global_message']) . '</div>';
                unset($_SESSION['global_message']);
                unset($_SESSION['global_message_type']);
            }
            ?>
