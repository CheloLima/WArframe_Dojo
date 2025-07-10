<?php
define('PAGE_TITLE', 'Profil vervollständigen');
require_once __DIR__ . '/includes/header.php'; // Session, $is_logged_in
require_once __DIR__ . '/includes/db.php';     // $pdo
require_once __DIR__ . '/includes/user_functions.php'; // updateUserProfile, getUserByDiscordId

// Prüfen, ob der Benutzer eingeloggt ist
if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte zuerst einloggen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$user = getUserByDiscordId($pdo, $_SESSION['discord_id']);
if (!$user) {
    // Sollte nicht passieren, wenn $is_logged_in true ist, aber als Sicherheitscheck
    $_SESSION['global_message'] = "Benutzer nicht gefunden. Bitte erneut einloggen.";
    $_SESSION['global_message_type'] = "error";
    // Session zerstören für sauberen Neulogin
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Initialisiere Variablen für Formularwerte
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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulardaten abrufen und bereinigen
    $warframe_ign = trim($_POST['warframe_ign'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $age_input = trim($_POST['age'] ?? '');
    $age = ($age_input === '') ? null : (int)$age_input; // Alter als Integer oder null
    $origin = trim($_POST['origin'] ?? '');
    $main_frame = trim($_POST['main_frame'] ?? '');
    $weapons = trim($_POST['weapons'] ?? ''); // Textarea, Whitespace am Ende ggf. beibehalten
    $steam_profile = trim($_POST['steam_profile'] ?? '');
    $nintendo_friend_code = trim($_POST['nintendo_friend_code'] ?? '');

    // Validierung
    if (empty($warframe_ign)) {
        $errors['warframe_ign'] = "Warframe In-Game Name (IGN) ist ein Pflichtfeld.";
    } elseif (strlen($warframe_ign) > 50) { // Beispielhafte Längenprüfung
        $errors['warframe_ign'] = "Warframe IGN darf maximal 50 Zeichen lang sein.";
    }

    if ($age !== null && ($age < 10 || $age > 120)) { // Beispielhafte Altersprüfung
        $errors['age'] = "Bitte gib ein gültiges Alter ein.";
    }

    // Weitere Validierungen (Länge, Format für Steam/Nintendo etc.) können hier hinzugefügt werden
    // z.B. Steam Profil URL Validierung
    if (!empty($steam_profile) && !filter_var($steam_profile, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9_]+$/', $steam_profile) && !preg_match('/^7656[0-9]{13}$/', $steam_profile) ) {
        // Akzeptiert volle URL, Profilnamen oder SteamID64
        // $errors['steam_profile'] = "Bitte gib eine gültige Steam Profil URL, deinen Profilnamen oder deine SteamID64 ein.";
        // Fürs erste einfacher:
        if (strlen($steam_profile) > 100) $errors['steam_profile'] = "Steam Profil Link/Name zu lang.";
    }
    if (strlen($nintendo_friend_code) > 50) $errors['nintendo_friend_code'] = "Nintendo Freundescode zu lang.";
    if (strlen($nickname) > 50) $errors['nickname'] = "Spitzname zu lang.";
    if (strlen($origin) > 100) $errors['origin'] = "Herkunft zu lang.";
    if (strlen($main_frame) > 100) $errors['main_frame'] = "Main Frame(s) zu lang.";
    // About Me und Weapons sind TEXT, haben höhere Limits.

    if (empty($errors)) {
        $data_to_update = [
            'warframe_ign' => $warframe_ign,
            'about_me' => $about_me,
            'nickname' => $nickname,
            'age' => $age,
            'origin' => $origin,
            'main_frame' => $main_frame,
            'weapons' => $weapons,
            'steam_profile' => $steam_profile,
            'nintendo_friend_code' => $nintendo_friend_code,
        ];

        if (updateUserProfile($pdo, $_SESSION['discord_id'], $data_to_update)) {
            $_SESSION['global_message'] = "Profil erfolgreich aktualisiert!";
            $_SESSION['global_message_type'] = "success";
            // Leite zum Dashboard weiter, da das Profil nun (mindestens IGN) vervollständigt ist
            header("Location: " . BASE_URL . "/dashboard.php");
            exit;
        } else {
            $errors['general'] = "Ein Fehler ist beim Speichern des Profils aufgetreten. Bitte versuche es später erneut.";
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><?php echo empty($user['warframe_ign']) ? 'Tenno-Identität bestätigen' : 'Profil-Aktualisierung'; ?></h2> <!-- Angepasst -->
    </div>

    <p>Operator <?php echo htmlspecialchars($user['discord_username']); ?>, Eure Daten sind unvollständig oder bedürfen einer Aktualisierung. Bitte vervollständigt Euer Tenno-Profil.</p> <!-- Angepasst -->
    <p>Euer <strong>Warframe In-Game Name (IGN)</strong> ist für die Systemidentifikation zwingend erforderlich.</p> <!-- Angepasst -->

    <?php if (!empty($errors['general'])): ?>
        <div class="message error"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form action="profil-vervollstaendigen.php" method="POST">
        <fieldset>
            <legend>Basisinformationen</legend>
            <div>
                <label for="warframe_ign">Warframe In-Game Name (IGN) <span class="text-primary">*</span></label>
                <input type="text" id="warframe_ign" name="warframe_ign" value="<?php echo htmlspecialchars($warframe_ign); ?>" required maxlength="50">
                <?php if (!empty($errors['warframe_ign'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['warframe_ign']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="nickname">Spitzname (Optional)</label>
                <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($nickname); ?>" maxlength="50">
                <?php if (!empty($errors['nickname'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['nickname']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="about_me">Über mich (Optional)</label>
                <textarea id="about_me" name="about_me" rows="5"><?php echo htmlspecialchars($about_me); ?></textarea>
            </div>
        </fieldset>

        <fieldset>
            <legend>Weitere Details (Optional)</legend>
            <div>
                <label for="age">Alter</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age); ?>" min="10" max="120">
                <?php if (!empty($errors['age'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['age']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="origin">Herkunft (z.B. Land, Region)</label>
                <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars($origin); ?>" maxlength="100">
                <?php if (!empty($errors['origin'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['origin']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="main_frame">Main Warframe(s)</label>
                <input type="text" id="main_frame" name="main_frame" value="<?php echo htmlspecialchars($main_frame); ?>" maxlength="100" placeholder="z.B. Excalibur, Volt, Mag">
                <?php if (!empty($errors['main_frame'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['main_frame']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="weapons">Lieblingswaffen</label>
                <textarea id="weapons" name="weapons" rows="3" placeholder="z.B. Hek, Soma Prime, Glaive Prime"><?php echo htmlspecialchars($weapons); ?></textarea>
            </div>
        </fieldset>

        <fieldset>
            <legend>Gaming Profile (Optional)</legend>
            <div>
                <label for="steam_profile">Steam Profil (Link oder Name)</label>
                <input type="text" id="steam_profile" name="steam_profile" value="<?php echo htmlspecialchars($steam_profile); ?>" maxlength="100">
                <?php if (!empty($errors['steam_profile'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['steam_profile']); ?></p><?php endif; ?>
            </div>
            <div>
                <label for="nintendo_friend_code">Nintendo Freundescode</label>
                <input type="text" id="nintendo_friend_code" name="nintendo_friend_code" value="<?php echo htmlspecialchars($nintendo_friend_code); ?>" maxlength="50" placeholder="SW-XXXX-XXXX-XXXX">
                <?php if (!empty($errors['nintendo_friend_code'])): ?><p class="message error-text"><?php echo htmlspecialchars($errors['nintendo_friend_code']); ?></p><?php endif; ?>
            </div>
        </fieldset>

        <button type="submit" class="button">Profil speichern</button>
        <?php if (!empty($user['warframe_ign'])): // Zeige Abbrechen-Button nur, wenn IGN schon mal gesetzt war (also von Dashboard kommend) ?>
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="button button-secondary">Abbrechen</a>
        <?php endif; ?>
    </form>
</div>
<style>.error-text { color: var(--color-error); font-size: 0.9em; margin-top: -0.75rem; margin-bottom: 0.5rem; }</style>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
