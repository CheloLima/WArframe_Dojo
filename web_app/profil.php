<?php
define('PAGE_TITLE', 'Mitgliederprofil');
require_once __DIR__ . '/includes/header.php'; // Session, $is_logged_in
require_once __DIR__ . '/includes/db.php';     // $pdo
require_once __DIR__ . '/includes/user_functions.php'; // Diverse Funktionen

// Prüfen, ob der Benutzer eingeloggt ist
if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte zuerst einloggen, um Profile anzusehen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Profil-ID aus GET-Parameter holen
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    $_SESSION['global_message'] = "Keine Profil-ID angegeben.";
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/mitglieder.php");
    exit;
}
$profile_discord_id = trim($_GET['id']);

// Profildaten des anzuzeigenden Benutzers laden
$profile_user = getUserByDiscordId($pdo, $profile_discord_id);

if (!$profile_user) {
    $_SESSION['global_message'] = "Das angeforderte Profil konnte nicht gefunden werden.";
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/mitglieder.php");
    exit;
}

// Custom Title laden
$custom_title_name = null;
if (!empty($profile_user['custom_title_id'])) {
    $title_data = getCustomTitleById($pdo, $profile_user['custom_title_id']);
    if ($title_data) {
        $custom_title_name = $title_data['title_name'];
    }
}

// Syndikat-Tags laden
$syndicates = getUserSyndicates($pdo, $profile_discord_id);

// Kommentare laden
$comments = getProfileComments($pdo, $profile_discord_id);

// Kommentar hinzufügen Logik
$comment_error = '';
$comment_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $comment_text = trim($_POST['comment_text'] ?? '');
    if (empty($comment_text)) {
        $comment_error = "Kommentar darf nicht leer sein.";
    } elseif (strlen($comment_text) > 1000) { // Maximale Länge für Kommentare
        $comment_error = "Kommentar ist zu lang (max. 1000 Zeichen).";
    } else {
        if (addProfileComment($pdo, $profile_discord_id, $_SESSION['discord_id'], $comment_text)) {
            // Kommentar erfolgreich hinzugefügt, Seite neu laden, um ihn anzuzeigen und Form Resubmission zu verhindern
            $_SESSION['global_message'] = "Kommentar erfolgreich hinzugefügt!";
            $_SESSION['global_message_type'] = "success";
            header("Location: " . BASE_URL . "/profil.php?id=" . urlencode($profile_discord_id) . "&comment_posted=1");
            exit;
        } else {
            $comment_error = "Fehler beim Speichern des Kommentars.";
        }
    }
}

// Kommentar löschen Logik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id_to_delete = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if ($comment_id_to_delete) {
        if (deleteProfileComment($pdo, $comment_id_to_delete, $_SESSION['discord_id'], $is_admin)) {
            $_SESSION['global_message'] = "Kommentar erfolgreich gelöscht!";
            $_SESSION['global_message_type'] = "success";
            header("Location: " . BASE_URL . "/profil.php?id=" . urlencode($profile_discord_id) . "&comment_deleted=1");
            exit;
        } else {
            $_SESSION['global_message'] = "Fehler beim Löschen des Kommentars oder keine Berechtigung.";
            $_SESSION['global_message_type'] = "error";
            header("Location: " . BASE_URL . "/profil.php?id=" . urlencode($profile_discord_id) . "&delete_failed=1");
            exit;
        }
    }
}


// Page Title dynamisch setzen
define('PAGE_TITLE_DYNAMIC', 'Profil von ' . htmlspecialchars($profile_user['discord_username']));

// Avatar URL
$discord_profile_avatar = $profile_user['discord_avatar_url'];
$profile_avatar_url = ($discord_profile_avatar !== null && $discord_profile_avatar !== '') ? htmlspecialchars($discord_profile_avatar) : BASE_URL . '/assets/images/default_avatar.png';

?>
<!-- Dynamischer Seitentitel im Header anpassen -->
<script>document.title = "<?php echo PAGE_TITLE_DYNAMIC . ' - ' . SITE_NAME; ?>";</script>

<div class="profile-page card">
    <div class="profile-header">
        <img src="<?php echo $profile_avatar_url; ?>" alt="Avatar von <?php echo htmlspecialchars($profile_user['discord_username']); ?>" class="profile-avatar">
        <div class="profile-main-info">
            <h1><?php echo htmlspecialchars($profile_user['discord_username']); ?></h1>
            <?php if ($custom_title_name): ?>
                <p class="custom-title"><?php echo htmlspecialchars($custom_title_name); ?></p>
            <?php endif; ?>
            <?php if ($profile_user['isAdmin']): ?>
                <p class="admin-tag"><strong><i class="fas fa-shield-alt"></i> Administrator</strong></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-content-grid">
        <section class="profile-details">
            <h3>Details</h3>
            <dl>
                <?php if (!empty($profile_user['warframe_ign'])): ?>
                    <dt>Warframe IGN:</dt>
                    <dd><?php echo htmlspecialchars($profile_user['warframe_ign']); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['nickname'])): ?>
                    <dt>Spitzname:</dt>
                    <dd><?php echo htmlspecialchars($profile_user['nickname']); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['main_frame'])): ?>
                    <dt>Main Frame(s):</dt>
                    <dd><?php echo htmlspecialchars($profile_user['main_frame']); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['weapons'])): ?>
                    <dt>Lieblingswaffen:</dt>
                    <dd><?php echo nl2br(htmlspecialchars($profile_user['weapons'])); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['age'])): ?>
                    <dt>Alter:</dt>
                    <dd><?php echo htmlspecialchars($profile_user['age']); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['origin'])): ?>
                    <dt>Herkunft:</dt>
                    <dd><?php echo htmlspecialchars($profile_user['origin']); ?></dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['steam_profile'])): ?>
                    <dt>Steam:</dt>
                    <dd>
                        <?php
                        // Versuche, es klickbar zu machen, wenn es eine URL ist
                        if (filter_var($profile_user['steam_profile'], FILTER_VALIDATE_URL)) {
                            echo '<a href="'.htmlspecialchars($profile_user['steam_profile']).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($profile_user['steam_profile']).'</a>';
                        } elseif (preg_match('/^7656[0-9]{13}$/', $profile_user['steam_profile'])) { // SteamID64
                             echo '<a href="https://steamcommunity.com/profiles/'.htmlspecialchars($profile_user['steam_profile']).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($profile_user['steam_profile']).'</a>';
                        } elseif (preg_match('/^[a-zA-Z0-9_]+$/', $profile_user['steam_profile'])) { // Custom URL Name
                             echo '<a href="https://steamcommunity.com/id/'.htmlspecialchars($profile_user['steam_profile']).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($profile_user['steam_profile']).'</a>';
                        }
                        else {
                            echo htmlspecialchars($profile_user['steam_profile']);
                        }
                        ?>
                    </dd>
                <?php endif; ?>

                <?php if (!empty($profile_user['nintendo_friend_code'])): ?>
                    <dt>Nintendo FC:</dt>
                    <dd><?php echo htmlspecialchars($profile_user['nintendo_friend_code']); ?></dd>
                <?php endif; ?>

                <dt>Registriert seit:</dt>
                <dd><?php echo date("d.m.Y", strtotime($profile_user['created_at'])); ?></dd>
            </dl>
        </section>

        <section class="profile-about">
            <?php if (!empty($profile_user['about_me'])): ?>
                <h3>Über mich</h3>
                <p><?php echo nl2br(htmlspecialchars($profile_user['about_me'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($syndicates)): ?>
                <h3 class="mt-2">Syndikat-Zugehörigkeiten</h3>
                <div class="syndicate-tags">
                    <?php foreach ($syndicates as $synd): ?>
                        <span class="syndicate-tag" style="background-color: <?php echo htmlspecialchars($synd['color_hex']); ?>; color: <?php echo getContrastColor($synd['color_hex']); ?>;">
                            <?php echo htmlspecialchars($synd['syndicate_name']); ?>
                            <?php if(!empty($synd['rank'])): ?>
                                <small>(<?php echo htmlspecialchars($synd['rank']); ?>)</small>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="comments-section mt-2">
        <h3>Schwarzes Brett / Kommentare</h3>
        <?php if ($is_logged_in): // Nur eingeloggte User können kommentieren ?>
        <form action="profil.php?id=<?php echo htmlspecialchars($profile_discord_id); ?>" method="POST" class="mb-2">
            <?php if ($comment_error): ?><p class="message error"><?php echo htmlspecialchars($comment_error); ?></p><?php endif; ?>
            <?php if ($comment_success && isset($_GET['comment_posted'])): ?><p class="message success"><?php echo htmlspecialchars($comment_success); ?></p><?php endif; ?>

            <div class="form-group">
                <label for="comment_text">Dein Kommentar:</label>
                <textarea name="comment_text" id="comment_text" rows="3" required maxlength="1000"></textarea>
            </div>
            <button type="submit" name="submit_comment" class="button">Kommentar absenden</button>
        </form>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <p>Noch keine Kommentare für dieses Profil vorhanden.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php
                $comment_author_avatar_src = $comment['author_avatar_url'];
                $comment_avatar_display_url = ($comment_author_avatar_src !== null && $comment_author_avatar_src !== '') ? htmlspecialchars($comment_author_avatar_src) : BASE_URL . '/assets/images/default_avatar.png';
                ?>
                <div class="comment card">
                    <div class="comment-header">
                        <img src="<?php echo $comment_avatar_display_url; ?>" alt="Avatar von <?php echo htmlspecialchars($comment['author_username']); ?>" class="comment-author-avatar">
                        <strong class="comment-author">
                            <a href="profil.php?id=<?php echo htmlspecialchars($comment['author_discord_id']); ?>">
                                <?php echo htmlspecialchars($comment['author_username']); ?>
                            </a>
                        </strong>
                        <span class="comment-date"> kommentierte am <?php echo date("d.m.Y H:i", strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-content">
                        <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    </div>
                    <?php if ($is_admin || (isset($_SESSION['discord_id']) && $_SESSION['discord_id'] === $comment['author_discord_id'])): ?>
                    <div class="comment-actions">
                        <form action="profil.php?id=<?php echo htmlspecialchars($profile_discord_id); ?>" method="POST" style="display: inline;">
                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                            <button type="submit" name="delete_comment" class="button button-danger btn-sm" onclick="return confirm('Bist du sicher, dass du diesen Kommentar löschen möchtest?');">Löschen</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if ($is_admin || (isset($_SESSION['discord_id']) && $_SESSION['discord_id'] === $profile_discord_id)): ?>
        <div class="profile-actions mt-2">
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="button">Eigenes Profil bearbeiten</a>
            <?php if ($is_admin && $_SESSION['discord_id'] !== $profile_discord_id): ?>
                <a href="<?php echo BASE_URL; ?>/admin.php?page=edit_user&id=<?php echo htmlspecialchars($profile_discord_id); ?>" class="button button-secondary">Nutzer im Admin-Panel bearbeiten</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php
// Hilfsfunktion für Kontrastfarbe (einfache Version)
function getContrastColor($hexcolor) {
    $hexcolor = ltrim($hexcolor, '#');
    if (strlen($hexcolor) == 3) {
        $r = hexdec(substr($hexcolor, 0, 1) . substr($hexcolor, 0, 1));
        $g = hexdec(substr($hexcolor, 1, 1) . substr($hexcolor, 1, 1));
        $b = hexdec(substr($hexcolor, 2, 1) . substr($hexcolor, 2, 1));
    } else {
        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));
    }
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? '#000000' : '#FFFFFF';
}
?>
<style>
.profile-page { padding: 1.5rem; }
.profile-header { display: flex; align-items: center; margin-bottom: 2rem; padding-bottom:1.5rem; border-bottom: 1px solid var(--color-border); }
.profile-avatar { width: 120px; height: 120px; border-radius: 50%; margin-right: 2rem; border: 4px solid var(--color-primary-accent); object-fit: cover; }
.profile-main-info h1 { margin-bottom: 0.1em; font-size: 2.2rem; }
.profile-main-info .custom-title { color: var(--color-secondary-accent); font-size: 1.2rem; font-style: italic; margin-top:0; }
.profile-main-info .admin-tag { color: var(--color-warning); font-size: 0.9rem; }
.profile-main-info .admin-tag .fas { margin-right: 0.3em; }

.profile-content-grid {
    display: grid;
    grid-template-columns: 1fr; /* Standard: einspaltig */
    gap: 2rem;
}
@media (min-width: 768px) { /* Zweispaltig auf größeren Bildschirmen */
    .profile-content-grid { grid-template-columns: 2fr 1fr; } /* Details breiter als "Über mich" */
}
@media (min-width: 1024px) {
    .profile-content-grid { grid-template-columns: 1.5fr 1fr; } /* Anpassung für noch größere */
}


.profile-details dl { display: grid; grid-template-columns: auto 1fr; gap: 0.8rem 1rem; }
.profile-details dt { font-weight: bold; color: var(--color-text-secondary); }
.profile-details dd { margin-left: 0; word-break: break-word; }

.syndicate-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
.syndicate-tag {
    padding: 0.3em 0.8em;
    border-radius: 15px; /* Pill-Form */
    font-size: 0.85em;
    font-weight: bold;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.syndicate-tag small { font-weight: normal; opacity: 0.9; }

.comments-section .comment.card { margin-bottom: 1rem; padding: 1rem; }
.comment-header { display: flex; align-items: center; margin-bottom: 0.5rem; }
.comment-author-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 0.75rem; border: 1px solid var(--color-border); }
.comment-author a { color: var(--color-primary-accent); font-weight: bold; }
.comment-date { font-size: 0.8em; color: var(--color-text-secondary); margin-left: 0.5em; }
.comment-content { margin-left: calc(40px + 0.75rem); /* Einrücken unter Avatar */ padding-top: 0.3rem; }
.comment-actions { margin-left: calc(40px + 0.75rem); margin-top: 0.5rem; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }

.profile-actions { border-top: 1px solid var(--color-border); padding-top: 1.5rem; text-align: right; }
.profile-actions .button { margin-left: 1rem; }

/* FontAwesome für Admin-Tag (optional, wenn nicht vorhanden, wird kein Icon angezeigt) */
/* @import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"); */
/* Wenn FontAwesome nicht global geladen wird, kann man es hier spezifisch einbinden oder ein SVG verwenden */
</style>

<?php
// FontAwesome Link hinzufügen, wenn nicht schon im globalen Header (als Beispiel)
// Dies ist nicht ideal hier, besser global oder gar nicht, wenn nicht unbedingt nötig.
$fa_link = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
// Überprüfen, ob der Link bereits im Header gerendert wurde (schwierig ohne Output Buffering)
// Für dieses Beispiel fügen wir es einfach hinzu, wenn $is_admin true ist und das Profil ein Admin ist.
if ($profile_user['isAdmin']) {
    // echo $fa_link; // Besser im <head> des Haupt-Headers
}
?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
