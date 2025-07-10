<?php
define('PAGE_TITLE', 'Dashboard');
require_once __DIR__ . '/includes/header.php'; // Session, $is_logged_in
require_once __DIR__ . '/includes/db.php';     // $pdo
require_once __DIR__ . '/includes/user_functions.php'; // Diverse Funktionen

// Prüfen, ob der Benutzer eingeloggt ist
if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte zuerst einloggen, um das Dashboard zu sehen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$user = getUserByDiscordId($pdo, $_SESSION['discord_id']);
if (!$user) {
    // Sollte nicht passieren
    $_SESSION['global_message'] = "Benutzer nicht gefunden. Bitte erneut einloggen.";
    $_SESSION['global_message_type'] = "error";
    session_unset(); session_destroy();
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Prüfen, ob das Profil (insb. IGN) vervollständigt ist
if (empty($user['warframe_ign'])) {
    $_SESSION['global_message'] = "Bitte vervollständige zuerst dein Profil mit deinem Warframe In-Game Namen.";
    $_SESSION['global_message_type'] = "info";
    header("Location: " . BASE_URL . "/profil-vervollstaendigen.php");
    exit;
}

// MOTD laden (nur die neueste veröffentlichte)
$motd_data = getLatestPublishedMotd($pdo); // Geändert von getMotd zu getLatestPublishedMotd

// Initialisiere Variablen für Formularwerte aus der Datenbank
$warframe_ign = $user['warframe_ign'] ?? '';
$about_me = $user['about_me'] ?? '';
$nickname = $user['nickname'] ?? '';
$age = $user['age'] ?? '';
$origin = $user['origin'] ?? '';
$main_frame = $user['main_frame'] ?? '';
$weapons = $user['weapons'] ?? '';
$steam_profile = $user['steam_profile'] ?? '';
$nintendo_friend_code = $user['nintendo_friend_code'] ?? '';

$errors = [];
// $success_message = ''; // Wird jetzt über $_SESSION['global_message'] gehandhabt

// $upload_dir = __DIR__ . '/uploads/avatars/'; // Nicht mehr benötigt

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Avatar-Verarbeitung entfernt

    // Formulardaten für Profil abrufen und bereinigen
    $warframe_ign_form = trim($_POST['warframe_ign'] ?? '');
    $about_me_form = trim($_POST['about_me'] ?? '');
    $nickname_form = trim($_POST['nickname'] ?? '');
    $age_input_form = trim($_POST['age'] ?? '');
    $age_form = ($age_input_form === '') ? null : (int)$age_input_form;
    $origin_form = trim($_POST['origin'] ?? '');
    $main_frame_form = trim($_POST['main_frame'] ?? '');
    $weapons_form = trim($_POST['weapons'] ?? '');
    $steam_profile_form = trim($_POST['steam_profile'] ?? '');
    $nintendo_friend_code_form = trim($_POST['nintendo_friend_code'] ?? '');

    // Validierung
    if (empty($warframe_ign_form)) {
        $errors['warframe_ign'] = "Warframe In-Game Name (IGN) ist ein Pflichtfeld.";
    } elseif (strlen($warframe_ign_form) > 50) {
        $errors['warframe_ign'] = "Warframe IGN darf maximal 50 Zeichen lang sein.";
    }
    if ($age_form !== null && ($age_form < 10 || $age_form > 120)) {
        $errors['age'] = "Bitte gib ein gültiges Alter ein.";
    }
    // Weitere Validierungen... (wie in profil-vervollständigen.php)
    if (strlen($steam_profile_form) > 100) $errors['steam_profile'] = "Steam Profil Link/Name zu lang.";
    if (strlen($nintendo_friend_code_form) > 50) $errors['nintendo_friend_code'] = "Nintendo Freundescode zu lang.";
    if (strlen($nickname_form) > 50) $errors['nickname'] = "Spitzname zu lang.";
    if (strlen($origin_form) > 100) $errors['origin'] = "Herkunft zu lang.";
    if (strlen($main_frame_form) > 100) $errors['main_frame'] = "Main Frame(s) zu lang.";


    if (empty($errors)) {
        $data_to_update = [
            'warframe_ign' => $warframe_ign_form,
            'about_me' => $about_me_form,
            'nickname' => $nickname_form,
            'age' => $age_form,
            'origin' => $origin_form,
            'main_frame' => $main_frame_form,
            'weapons' => $weapons_form,
            'steam_profile' => $steam_profile_form,
            'nintendo_friend_code' => $nintendo_friend_code_form
            // 'custom_avatar_path' => $custom_avatar_path_to_db // Entfernt
        ];

        if (empty($errors) && updateUserProfile($pdo, $_SESSION['discord_id'], $data_to_update)) {
            $_SESSION['global_message'] = "Profil erfolgreich aktualisiert!";
            $_SESSION['global_message_type'] = "success";
            // Daten neu laden für die Anzeige im Formular und auf der Seite
            $user = getUserByDiscordId($pdo, $_SESSION['discord_id']);
            $warframe_ign = $user['warframe_ign'] ?? '';
            $about_me = $user['about_me'] ?? '';
            $nickname = $user['nickname'] ?? '';
            $age = $user['age'] ?? '';
            $origin = $user['origin'] ?? '';
            $main_frame = $user['main_frame'] ?? '';
            $weapons = $user['weapons'] ?? '';
            $steam_profile = $user['steam_profile'] ?? '';
            $nintendo_friend_code = $user['nintendo_friend_code'] ?? '';
            // Bleibe auf der Dashboard-Seite, redirect nicht nötig
            header("Location: " . BASE_URL . "/dashboard.php?profile_updated=1"); // Verhindert Form Resubmission
            exit;
        } else {
            $errors['general'] = "Ein Fehler ist beim Speichern des Profils aufgetreten.";
        }
    } else {
        // Wenn Validierungsfehler auftreten, die Formularfelder mit den fehlerhaften Eingaben neu befüllen
        $warframe_ign = $warframe_ign_form;
        $about_me = $about_me_form;
        $nickname = $nickname_form;
        $age = $age_form; // Kann jetzt null sein, htmlspecialchars wird damit umgehen
        $origin = $origin_form;
        $main_frame = $main_frame_form;
        $weapons = $weapons_form;
        $steam_profile = $steam_profile_form;
        $nintendo_friend_code = $nintendo_friend_code_form;
    }
}
if(isset($_GET['profile_updated']) && $_GET['profile_updated'] == '1' && !isset($_SESSION['global_message'])) {
    // Falls die globale Nachricht schon gesetzt wurde (z.B. durch header.php), nicht überschreiben
    // Dies ist ein Fallback, falls die Session-Nachricht nicht angezeigt wird nach dem Redirect.
    // Normalerweise sollte die Session-Nachricht aus header.php ausreichen.
    // $success_message = "Dein Profil wurde erfolgreich aktualisiert!";
}


?>

<h1>Persönliches Terminal, Operator <?php echo htmlspecialchars($user['discord_username']); ?></h1> <!-- Angepasst -->

<?php if (isset($_SESSION['global_message_type']) && $_SESSION['global_message_type'] === 'success' && isset($_GET['profile_updated'])): ?>
    <?php /* Die globale Nachricht wird bereits im header.php angezeigt. Hier nichts extra ausgeben, um Dopplung zu vermeiden. */ ?>
<?php endif; ?>


<div class="dashboard-grid">
    <section class="card motd-card">
        <div class="card-header">
            <h2>Systemnachricht (MOTD)</h2> <!-- Angepasst -->
        </div>
        <?php if ($motd_data && !empty($motd_data['content'])): ?>
            <?php if (!empty($motd_data['title'])): ?>
                <h3><?php echo htmlspecialchars($motd_data['title']); ?></h3>
            <?php endif; ?>
            <p><?php echo nl2br(htmlspecialchars($motd_data['content'])); ?></p>
            <p class="meta">
                <em>
                    Veröffentlicht am <?php echo date("d.m.Y H:i", strtotime($motd_data['updated_at'])); ?>
                    von <?php echo htmlspecialchars($motd_data['author_username']); ?>
                    <?php if (!empty($motd_data['version'])): ?>
                        (Version: <?php echo htmlspecialchars($motd_data['version']); ?>)
                    <?php endif; ?>
                </em>
            </p>
        <?php else: ?>
            <p>Derzeit keine MOTD vorhanden.</p>
        <?php endif; ?>
        <div class="motd-actions mt-1">
            <a href="motd_history.php" class="button btn-sm button-secondary">MOTD-Verlauf / Changelog</a>
            <!-- Später wird dies zu einer Seite führen, die ältere MOTDs und Changelog-Einträge anzeigt -->
        </div>
    </section>

    <section class="card profile-edit-card">
        <div class="card-header">
            <h2>Dein Profil bearbeiten</h2>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="message error"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <form action="dashboard.php" method="POST"> <!-- enctype entfernt -->
            <input type="hidden" name="update_profile" value="1">

            <fieldset>
                <legend>Avatar</legend>
                <div class="current-avatar-section">
                    <p>Aktueller Avatar (von Discord):</p>
                    <?php
                    // $display_avatar_url wird jetzt nur noch Discord oder Default sein
                    // und sollte bereits oben im Skript korrekt gesetzt werden, wenn $user geladen wird.
                    // Hier stellen wir sicher, dass es für die Anzeige im Formular aktualisiert wird,
                    // falls es durch POST-Aktionen (die jetzt keine Avatar-Änderungen mehr machen) beeinflusst wurde.
                    // Die Logik zur Avatar-Anzeige muss ggf. noch oben im PHP-Block angepasst werden.
                    $current_display_avatar_url = BASE_URL . '/assets/images/default_avatar.png'; // Default
                    if (!empty($user['discord_avatar_url'])) {
                        $current_display_avatar_url = htmlspecialchars($user['discord_avatar_url']);
                    }
                    ?>
                    <img src="<?php echo $current_display_avatar_url; ?>" alt="Aktueller Avatar" class="profile-avatar-preview">
                </div>

                <div class="form-group">
                    <label for="sync_discord_avatar" style="display: block; margin-bottom: 0.5em;">Discord-Avatar:</label>
                    <?php
                    // Generiere die Discord OAuth URL für den Resync-Button
                    $resync_oauth_url_dashboard = '';
                    if (defined('DISCORD_CLIENT_ID') && defined('DISCORD_REDIRECT_URI')) {
                        $state_params_resync_dash = [
                            'action' => 'resync_avatar',
                            'return_to' => 'dashboard.php' // Hartcodiert für diesen Button
                        ];
                        $oauth_params_resync_dash = [
                            'client_id' => DISCORD_CLIENT_ID,
                            'redirect_uri' => DISCORD_REDIRECT_URI,
                            'response_type' => 'code',
                            'scope' => 'identify guilds.members.read',
                            'state' => base64_encode(json_encode($state_params_resync_dash)),
                            // 'prompt' => 'consent' // Nützlich für Tests, um sicherzustellen, dass der Flow angestoßen wird.
                                                    // Im Produktivbetrieb eher weglassen, damit es nahtlos ist, wenn möglich.
                        ];
                        $resync_oauth_url_dashboard = 'https://discord.com/api/oauth2/authorize?' . http_build_query($oauth_params_resync_dash);
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($resync_oauth_url_dashboard); ?>" class="button button-secondary btn-sm">Discord-Avatar neu laden/synchronisieren</a>
                    <p class="form-hint"><small>Hinweis: Dies erfordert eine erneute Autorisierung mit Discord, um das aktuellste Profilbild zu laden. Du bleibst dabei auf der Seite, bis Discord dich zurückleitet.</small></p>
                </div>
            </fieldset>

            <fieldset>
                <legend>Basisinformationen</legend>
                <div>
                    <label for="warframe_ign">Warframe In-Game Name (IGN) <span class="text-primary">*</span></label>
                    <input type="text" id="warframe_ign" name="warframe_ign" value="<?php echo htmlspecialchars((string)$warframe_ign); ?>" required maxlength="50">
                    <?php if (!empty($errors['warframe_ign'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['warframe_ign']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="nickname">Spitzname (Optional)</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars((string)$nickname); ?>" maxlength="50">
                    <?php if (!empty($errors['nickname'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['nickname']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="about_me">Über mich (Optional)</label>
                    <textarea id="about_me" name="about_me" rows="5"><?php echo htmlspecialchars((string)$about_me); ?></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Weitere Details (Optional)</legend>
                <div>
                    <label for="age">Alter</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars((string)$age); ?>" min="10" max="120">
                    <?php if (!empty($errors['age'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['age']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="origin">Herkunft (z.B. Land, Region)</label>
                    <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars((string)$origin); ?>" maxlength="100">
                    <?php if (!empty($errors['origin'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['origin']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="main_frame">Main Warframe(s)</label>
                    <input type="text" id="main_frame" name="main_frame" value="<?php echo htmlspecialchars((string)$main_frame); ?>" maxlength="100" placeholder="z.B. Excalibur, Volt, Mag">
                     <?php if (!empty($errors['main_frame'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['main_frame']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="weapons">Lieblingswaffen</label>
                    <textarea id="weapons" name="weapons" rows="3" placeholder="z.B. Hek, Soma Prime, Glaive Prime"><?php echo htmlspecialchars((string)$weapons); ?></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Gaming Profile (Optional)</legend>
                <div>
                    <label for="steam_profile">Steam Profil (Link oder Name)</label>
                    <input type="text" id="steam_profile" name="steam_profile" value="<?php echo htmlspecialchars((string)$steam_profile); ?>" maxlength="100">
                    <?php if (!empty($errors['steam_profile'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['steam_profile']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="nintendo_friend_code">Nintendo Freundescode</label>
                    <input type="text" id="nintendo_friend_code" name="nintendo_friend_code" value="<?php echo htmlspecialchars((string)$nintendo_friend_code); ?>" maxlength="50" placeholder="SW-XXXX-XXXX-XXXX">
                    <?php if (!empty($errors['nintendo_friend_code'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['nintendo_friend_code']); ?></p><?php endif; ?>
                </div>
            </fieldset>

            <button type="submit" class="button">Profil aktualisieren</button>
        </form>
    </section>

    <!-- Weitere Sektionen für das Dashboard könnten hier folgen -->
    <!-- z.B. Schnelle Links, Clan-Events, etc. -->
    <section class="card quick-links-card">
        <div class="card-header">
            <h2>Nützliche Links</h2>
        </div>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/mitglieder.php">Mitgliederliste ansehen</a></li>
            <li><a href="<?php echo BASE_URL; ?>/profil.php?id=<?php echo htmlspecialchars($_SESSION['discord_id']); ?>">Dein öffentliches Profil ansehen</a></li>
            <li><a href="<?php echo BASE_URL; ?>/rechner.php">Build-Kosten-Rechner</a></li>
            <?php if ($is_admin): ?>
            <li><a href="<?php echo BASE_URL; ?>/admin.php">Admin Bereich</a></li>
            <?php endif; ?>
        </ul>
    </section>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}
.motd-card .meta {
    font-size: 0.85em;
    color: var(--color-text-secondary);
    margin-top: 1em;
}
.error-text { color: var(--color-error); font-size: 0.9em; margin-top: -0.75rem; margin-bottom: 0.5rem; }
.profile-avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--color-border);
    margin-bottom: 1rem;
}
.current-avatar-section p {
    font-weight: bold;
    color: var(--color-text-secondary);
    margin-bottom: 0.5rem;
}


@media (max-width: 900px) {
    .dashboard-grid {
        grid-template-columns: 1fr; /* Single column on smaller screens */
    }
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
