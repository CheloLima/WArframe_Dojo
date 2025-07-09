<?php
define('PAGE_TITLE', 'Mitgliederliste');
require_once __DIR__ . '/includes/header.php'; // Session, $is_logged_in
require_once __DIR__ . '/includes/db.php';     // $pdo
require_once __DIR__ . '/includes/user_functions.php'; // getAllUsersForList

// Prüfen, ob der Benutzer eingeloggt ist
if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte zuerst einloggen, um die Mitgliederliste zu sehen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Prüfen, ob das Profil des aktuellen Users (insb. IGN) vervollständigt ist
// Dies ist optional, aber oft eine Voraussetzung, um andere Mitglieder zu sehen.
$current_user_profile = getUserByDiscordId($pdo, $_SESSION['discord_id']);
if (!$current_user_profile || empty($current_user_profile['warframe_ign'])) {
    $_SESSION['global_message'] = "Bitte vervollständige zuerst dein eigenes Profil (insbesondere deinen Warframe IGN), um die Mitgliederliste sehen zu können.";
    $_SESSION['global_message_type'] = "info";
    header("Location: " . BASE_URL . "/profil-vervollstaendigen.php");
    exit;
}


$members = getAllUsersForList($pdo);

?>

<h1>Mitgliederliste der Endo Reserve Bank</h1> <!-- Angepasst -->
<p>Hier findest du eine Übersicht aller registrierten Mitglieder der Endo Reserve Bank.</p> <!-- Angepasst -->

<?php if (empty($members)): ?>
    <div class="message info">
        <p>Es sind noch keine Mitglieder registriert oder es konnten keine Mitglieder geladen werden.</p>
    </div>
<?php else: ?>
    <div class="member-list-container">
        <?php foreach ($members as $member): ?>
            <a href="profil.php?id=<?php echo htmlspecialchars($member['discord_id']); ?>" class="member-list-item-link">
                <div class="member-list-item card">
                    <div class="member-avatar-container">
                        <?php
                        $avatar_url = htmlspecialchars(!empty($member['discord_avatar_url']) ? $member['discord_avatar_url'] : BASE_URL . '/assets/images/default_avatar.png');
                        // Fallback, falls default_avatar.png nicht existiert (CSS könnte auch ein Default setzen)
                        // if (!empty($member['discord_avatar_url'])) {
                        //    $avatar_url = htmlspecialchars($member['discord_avatar_url']);
                        // } else {
                        //    $default_avatar_path = __DIR__ . '/assets/images/default_avatar.png';
                        //    if (file_exists($default_avatar_path)) {
                        //        $avatar_url = BASE_URL . '/assets/images/default_avatar.png';
                        //    } else {
                        //        // Fallback zu einem generischen SVG oder leer lassen, damit CSS greift
                        //        $avatar_url = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50' viewBox='0 0 50 50'%3E%3Crect width='50' height='50' fill='%23333'/%3E%3Ctext x='50%25' y='50%25' fill='%23fff' text-anchor='middle' dy='.3em' font-size='20px'%3E?%3C/text%3E%3C/svg%3E";
                        //    }
                        // }
                        ?>
                        <img src="<?php echo $avatar_url; ?>" alt="Avatar von <?php echo htmlspecialchars($member['discord_username']); ?>" class="member-avatar">
                    </div>
                    <div class="member-info">
                        <h4><?php echo htmlspecialchars($member['discord_username']); ?></h4>
                        <?php if (!empty($member['warframe_ign'])): ?>
                            <p class="ign">IGN: <?php echo htmlspecialchars($member['warframe_ign']); ?></p>
                        <?php else: ?>
                            <p class="ign"><em>IGN nicht angegeben</em></p>
                        <?php endif; ?>
                        <?php if (!empty($member['custom_title'])): ?>
                            <p class="custom-title-tag"><?php echo htmlspecialchars($member['custom_title']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
// Erstelle ein Dummy-Default-Avatar-Bild, falls es nicht existiert, um 404-Fehler zu vermeiden.
// Im echten Projekt sollte dieses Bild vorhanden sein.
$default_avatar_path_check = __DIR__ . '/assets/images/default_avatar.png';
if (!file_exists($default_avatar_path_check)) {
    if(!is_dir(dirname($default_avatar_path_check))) {
        // mkdir(dirname($default_avatar_path_check), 0755, true); // Nur wenn assets/images auch fehlt
    }
    // Erstelle ein einfaches Platzhalterbild (optional, besser ist es, eines bereitzustellen)
    // @file_put_contents($default_avatar_path_check, base64_decode('iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAABLSURBVFhH7c5BDQAwEASh+je9N2gD41AQIBAEChAECgQBAoQAAYIAAUIgQIBAECBAgABAIAAQIEAgCIBAgABAIAAQIEAgqAACybwCGWEgdM0AAAAASUVORK5CYII='));
    echo "<p class='message info text-center mt-2' style='font-size:0.8em;'>Hinweis: Standard-Avatar unter `assets/images/default_avatar.png` nicht gefunden. Avatare könnten fehlen.</p>";
}
?>

<style>
.member-list-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive Grid */
    gap: 1.5rem;
}
a.member-list-item-link {
    text-decoration: none; /* Entfernt Unterstrich vom Link */
    color: inherit; /* Erbt Textfarbe */
}
.member-list-item.card {
    display: flex;
    align-items: center;
    padding: 1rem; /* Weniger Padding als Standard-Karte für kompaktere Optik */
    transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
    border-left: 5px solid transparent; /* Für Hover-Effekt */
}
.member-list-item.card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 8px 20px rgba(var(--color-primary-accent-rgb, 106, 255, 0), 0.3); /* RGB für Transparenz */
    border-left-color: var(--color-primary-accent);
}
.member-avatar-container {
    margin-right: 1rem;
}
.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 2px solid var(--color-primary-accent);
    object-fit: cover; /* Stellt sicher, dass das Bild den Kreis füllt */
    background-color: var(--color-background-light); /* Fallback-Hintergrund für transparente Bilder */
}
.member-info h4 {
    margin-bottom: 0.25rem;
    color: var(--color-text-primary); /* Direkte Farbe für Namen */
    font-size: 1.1rem;
}
.member-info p.ign {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.25rem;
}
.member-info p.custom-title-tag {
    font-size: 0.8rem;
    color: var(--color-secondary-accent);
    font-style: italic;
    margin-bottom: 0;
}
/* Für die RGB-Variable in CSS (wird in :root in style.css hinzugefügt) */
/* :root { --color-primary-accent-rgb: 106, 255, 0; } */
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
