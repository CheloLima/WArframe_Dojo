<?php
define('PAGE_TITLE', 'Admin Bereich');
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/user_functions.php';

// Admin-Zugriff prüfen
if (!$is_logged_in || !$is_admin) {
    $_SESSION['global_message'] = "Zugriff verweigert. Nur Administratoren haben Zugriff auf diese Seite.";
    $_SESSION['global_message_type'] = "error";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

// Aktive Seite/Tab im Admin-Bereich bestimmen
$admin_page = $_GET['page'] ?? 'motd'; // Standardseite ist MOTD-Bearbeitung

// MOTD Bearbeitung - Logik (Neues System)
$all_motds_admin = getAllMotds($pdo); // Für die Liste
$edit_motd_id = null;
$edit_motd_title = '';
$edit_motd_content = '';
$edit_motd_is_published = true;
$edit_motd_version = '';

// Laden einer MOTD zum Bearbeiten
if (isset($_GET['action']) && $_GET['action'] === 'edit_motd' && isset($_GET['motd_id'])) {
    $edit_motd_id = filter_var($_GET['motd_id'], FILTER_VALIDATE_INT);
    $motd_to_edit = getMotdById($pdo, $edit_motd_id);
    if ($motd_to_edit) {
        $edit_motd_title = $motd_to_edit['title'];
        $edit_motd_content = $motd_to_edit['content'];
        $edit_motd_is_published = (bool)$motd_to_edit['is_published'];
        $edit_motd_version = $motd_to_edit['version'];
    } else {
        $_SESSION['global_message'] = "MOTD nicht gefunden.";
        $_SESSION['global_message_type'] = "error";
        $edit_motd_id = null; // Zurücksetzen, falls ID ungültig
    }
}

// Speichern (Erstellen oder Aktualisieren) einer MOTD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_motd'])) {
    $motd_id_form = filter_input(INPUT_POST, 'motd_id', FILTER_VALIDATE_INT);
    $motd_title_form = trim($_POST['motd_title'] ?? '');
    $motd_content_form = trim($_POST['motd_content'] ?? '');
    $motd_is_published_form = isset($_POST['motd_is_published']) ? 1 : 0;
    $motd_version_form = trim($_POST['motd_version'] ?? '');

    if (empty($motd_content_form)) {
        $_SESSION['global_message'] = "MOTD Inhalt darf nicht leer sein.";
        $_SESSION['global_message_type'] = "error";
        // Formularwerte für erneute Anzeige setzen
        $edit_motd_id = $motd_id_form;
        $edit_motd_title = $motd_title_form;
        $edit_motd_content = $motd_content_form;
        $edit_motd_is_published = (bool)$motd_is_published_form;
        $edit_motd_version = $motd_version_form;
    } else {
        $data = [
            'title' => $motd_title_form,
            'content' => $motd_content_form,
            'created_by_discord_id' => $_SESSION['discord_id'], // Admin, der es speichert
            'is_published' => $motd_is_published_form,
            'version' => $motd_version_form
        ];

        $success = false;
        if ($motd_id_form) { // Bearbeiten
            $success = updateMotd($pdo, $motd_id_form, $data);
            $action_msg = "aktualisiert";
        } else { // Erstellen
            $new_motd_id = createMotd($pdo, $data);
            if ($new_motd_id) {
                $success = true;
            }
            $action_msg = "erstellt";
        }

        if ($success) {
            $_SESSION['global_message'] = "MOTD erfolgreich {$action_msg}!";
            $_SESSION['global_message_type'] = "success";
            header("Location: " . BASE_URL . "/admin.php?page=motd&motd_saved=1");
            exit;
        } else {
            $_SESSION['global_message'] = "Fehler beim Speichern der MOTD.";
            $_SESSION['global_message_type'] = "error";
            // Formularwerte für erneute Anzeige setzen
            $edit_motd_id = $motd_id_form;
            $edit_motd_title = $motd_title_form;
            $edit_motd_content = $motd_content_form;
            $edit_motd_is_published = (bool)$motd_is_published_form;
            $edit_motd_version = $motd_version_form;
        }
    }
    $admin_page = 'motd'; // Bleibe auf der MOTD-Seite bei Fehlern oder wenn kein Redirect
}

// Löschen einer MOTD
if (isset($_GET['action']) && $_GET['action'] === 'delete_motd' && isset($_GET['motd_id'])) {
    $motd_id_to_delete = filter_var($_GET['motd_id'], FILTER_VALIDATE_INT);
    if ($motd_id_to_delete) {
        if (deleteMotd($pdo, $motd_id_to_delete)) {
            $_SESSION['global_message'] = "MOTD erfolgreich gelöscht.";
            $_SESSION['global_message_type'] = "success";
        } else {
            $_SESSION['global_message'] = "Fehler beim Löschen der MOTD.";
            $_SESSION['global_message_type'] = "error";
        }
    }
    header("Location: " . BASE_URL . "/admin.php?page=motd&motd_action_done=1");
    exit;
}

// Custom Titles - Logik
$all_titles = getAllCustomTitles($pdo);
$all_users = getAllUsersForList($pdo); // Für Zuweisung

// Erstellen/Bearbeiten von Titeln
$edit_title_id = null;
$edit_title_name = '';
$edit_title_description = '';
if (isset($_GET['action']) && $_GET['action'] === 'edit_title' && isset($_GET['title_id'])) {
    $edit_title_id = filter_var($_GET['title_id'], FILTER_VALIDATE_INT);
    $title_to_edit = getCustomTitleById($pdo, $edit_title_id);
    if ($title_to_edit) {
        $edit_title_name = $title_to_edit['title_name'];
        $edit_title_description = $title_to_edit['description'];
    } else {
        $edit_title_id = null; // ID ungültig
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_custom_title'])) {
    $title_id_form = filter_input(INPUT_POST, 'title_id', FILTER_VALIDATE_INT);
    $title_name_form = trim($_POST['title_name'] ?? '');
    $title_description_form = trim($_POST['title_description'] ?? '');

    if (empty($title_name_form)) {
        $_SESSION['global_message'] = "Titelname darf nicht leer sein.";
        $_SESSION['global_message_type'] = "error";
    } else {
        $success = false;
        if ($title_id_form) { // Bearbeiten
            $success = updateCustomTitle($pdo, $title_id_form, $title_name_form, $title_description_form);
            $action_msg = "aktualisiert";
        } else { // Erstellen
            $success = createCustomTitle($pdo, $title_name_form, $title_description_form);
            $action_msg = "erstellt";
        }

        if ($success) {
            $_SESSION['global_message'] = "Titel erfolgreich {$action_msg}!";
            $_SESSION['global_message_type'] = "success";
            header("Location: " . BASE_URL . "/admin.php?page=titles&title_saved=1");
            exit;
        } else {
            $_SESSION['global_message'] = "Fehler beim Speichern des Titels. Ist der Titelname vielleicht schon vergeben?";
            $_SESSION['global_message_type'] = "error";
            // Formularwerte für erneute Anzeige setzen
            $edit_title_id = $title_id_form;
            $edit_title_name = $title_name_form;
            $edit_title_description = $title_description_form;
        }
    }
     // Um die Eingabe bei Fehler im Formular zu behalten (falls kein Redirect):
    if(!$success) {
        $admin_page = 'titles'; // Bleibe auf der Titel-Seite
    }
}

// Löschen von Titeln
if (isset($_GET['action']) && $_GET['action'] === 'delete_title' && isset($_GET['title_id'])) {
    $title_id_to_delete = filter_var($_GET['title_id'], FILTER_VALIDATE_INT);
    // Sicherheitsabfrage: Nicht den Standardtitel (ID 1) löschen
    if ($title_id_to_delete && $title_id_to_delete != 1) {
        if (deleteCustomTitle($pdo, $title_id_to_delete)) {
            $_SESSION['global_message'] = "Titel erfolgreich gelöscht.";
            $_SESSION['global_message_type'] = "success";
        } else {
            $_SESSION['global_message'] = "Fehler beim Löschen des Titels.";
            $_SESSION['global_message_type'] = "error";
        }
    } elseif ($title_id_to_delete == 1) {
        $_SESSION['global_message'] = "Der Standardtitel 'Tenno' (ID 1) kann nicht gelöscht werden.";
        $_SESSION['global_message_type'] = "warning";
    }
    header("Location: " . BASE_URL . "/admin.php?page=titles&title_action_done=1");
    exit;
}

// Titel einem User zuweisen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_title_to_user'])) {
    $user_discord_id_form = trim($_POST['user_discord_id'] ?? '');
    $title_id_to_assign_form = filter_input(INPUT_POST, 'title_id_to_assign', FILTER_VALIDATE_INT);

    // title_id_to_assign_form kann 0 sein, wenn "Kein Titel" gewählt wurde, was zu NULL wird.
    $title_id_for_db = ($title_id_to_assign_form == 0) ? null : $title_id_to_assign_form;

    if (!empty($user_discord_id_form)) {
        if (assignCustomTitleToUser($pdo, $user_discord_id_form, $title_id_for_db)) {
            $_SESSION['global_message'] = "Titel erfolgreich dem Benutzer zugewiesen/entfernt.";
            $_SESSION['global_message_type'] = "success";
        } else {
            $_SESSION['global_message'] = "Fehler beim Zuweisen des Titels.";
            $_SESSION['global_message_type'] = "error";
        }
    } else {
        $_SESSION['global_message'] = "Kein Benutzer ausgewählt für die Titelzuweisung.";
        $_SESSION['global_message_type'] = "warning";
    }
    header("Location: " . BASE_URL . "/admin.php?page=titles&title_assigned=1");
    exit;
}

// Benutzerverwaltung (Basis: isAdmin Status ändern)
$edit_user_id_admin = null;
$user_to_edit_admin = null;
if ($admin_page === 'edit_user' && isset($_GET['id'])) {
    $edit_user_id_admin = trim($_GET['id']);
    $user_to_edit_admin = getUserByDiscordId($pdo, $edit_user_id_admin);
    if (!$user_to_edit_admin) {
        $_SESSION['global_message'] = "Zu bearbeitender Benutzer nicht gefunden.";
        $_SESSION['global_message_type'] = "error";
        header("Location: " . BASE_URL . "/admin.php?page=users");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_admin_status'])) {
    $user_id_form = trim($_POST['user_id_admin_edit'] ?? '');
    $is_admin_form = isset($_POST['is_admin_checkbox']) ? 1 : 0; // 1 für true, 0 für false

    // Sicherheitscheck: SuperAdmin darf nicht seinen eigenen Admin-Status entfernen.
    if ($user_id_form === SUPER_ADMIN_DISCORD_ID && $is_admin_form == 0) {
        $_SESSION['global_message'] = "Der Haupt-Administrator (ID: ".SUPER_ADMIN_DISCORD_ID.") darf sich nicht selbst die Admin-Rechte entziehen.";
        $_SESSION['global_message_type'] = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET isAdmin = :isAdmin WHERE discord_id = :discord_id");
        if ($stmt->execute([':isAdmin' => $is_admin_form, ':discord_id' => $user_id_form])) {
            $_SESSION['global_message'] = "Admin-Status für Benutzer ".htmlspecialchars($user_id_form)." erfolgreich aktualisiert.";
            $_SESSION['global_message_type'] = "success";
        } else {
            $_SESSION['global_message'] = "Fehler beim Aktualisieren des Admin-Status.";
            $_SESSION['global_message_type'] = "error";
        }
    }
    header("Location: " . BASE_URL . "/admin.php?page=users&user_admin_updated=1");
    exit;
}


?>
<h1>Admin Bereich</h1>

<nav class="admin-nav">
    <ul>
        <li><a href="admin.php?page=motd" class="<?php echo ($admin_page === 'motd') ? 'active' : ''; ?>">MOTD Verwalten</a></li>
        <li><a href="admin.php?page=titles" class="<?php echo ($admin_page === 'titles' || (isset($_GET['action']) && strpos($_GET['action'], 'title') !== false) ) ? 'active' : ''; ?>">Titel Verwalten</a></li>
        <li><a href="admin.php?page=users" class="<?php echo ($admin_page === 'users' || $admin_page === 'edit_user') ? 'active' : ''; ?>">Benutzer Verwalten</a></li>
        <li><a href="admin.php?page=changelog" class="<?php echo ($admin_page === 'changelog') ? 'active' : ''; ?>">Changelog Verwalten</a></li>
    </ul>
</nav>

<div class="admin-content mt-2">
    <?php if ($admin_page === 'motd'): ?>
        <section class="card">
            <div class="card-header"><h2>MOTD Verwaltung</h2></div>

            <form action="admin.php?page=motd" method="POST" class="mb-2 card" style="background-color: var(--color-background-light);">
                <input type="hidden" name="motd_id" value="<?php echo $edit_motd_id ? htmlspecialchars($edit_motd_id) : ''; ?>">
                <h4><?php echo $edit_motd_id ? 'MOTD Bearbeiten (ID: '.htmlspecialchars($edit_motd_id).')' : 'Neue MOTD erstellen'; ?></h4>

                <div class="form-group">
                    <label for="motd_title">Titel (Optional):</label>
                    <input type="text" name="motd_title" id="motd_title" value="<?php echo htmlspecialchars($edit_motd_title); ?>">
                </div>
                <div class="form-group">
                    <label for="motd_content">Inhalt:</label>
                    <textarea name="motd_content" id="motd_content" rows="8" required><?php echo htmlspecialchars($edit_motd_content); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="motd_version">Version (Optional, z.B. Patchnummer):</label>
                    <input type="text" name="motd_version" id="motd_version" value="<?php echo htmlspecialchars($edit_motd_version); ?>">
                </div>
                <div class="form-group">
                    <input type="checkbox" name="motd_is_published" id="motd_is_published" value="1" <?php echo $edit_motd_is_published ? 'checked' : ''; ?>>
                    <label for="motd_is_published" style="display:inline; font-weight:normal;">Veröffentlicht (Sichtbar für User)</label>
                </div>
                <button type="submit" name="save_motd" class="button"><?php echo $edit_motd_id ? 'Änderungen Speichern' : 'MOTD Erstellen'; ?></button>
                <?php if ($edit_motd_id): ?>
                    <a href="admin.php?page=motd" class="button button-secondary">Abbrechen / Neu erstellen</a>
                <?php endif; ?>
            </form>

            <h4 class="mt-2">MOTD Verlauf</h4>
            <?php if (empty($all_motds_admin)): ?>
                <p>Noch keine MOTDs vorhanden.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titel</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Autor</th>
                            <th>Erstellt</th>
                            <th>Aktualisiert</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_motds_admin as $motd_item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($motd_item['id']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($motd_item['title']) ? $motd_item['title'] : '<em>Kein Titel</em>'); ?></td>
                            <td><?php echo htmlspecialchars($motd_item['version'] ?? '-'); ?></td>
                            <td><?php echo $motd_item['is_published'] ? '<span class="text-success">Veröffentlicht</span>' : '<span class="text-warning">Entwurf</span>'; ?></td>
                            <td><?php echo htmlspecialchars($motd_item['author_username']); ?></td>
                            <td><?php echo date("d.m.Y H:i", strtotime($motd_item['created_at'])); ?></td>
                            <td><?php echo date("d.m.Y H:i", strtotime($motd_item['updated_at'])); ?></td>
                            <td>
                                <a href="admin.php?page=motd&action=edit_motd&motd_id=<?php echo $motd_item['id']; ?>" class="button btn-sm">Bearbeiten</a>
                                <a href="admin.php?page=motd&action=delete_motd&motd_id=<?php echo $motd_item['id']; ?>" class="button button-danger btn-sm" onclick="return confirm('Bist du sicher, dass du diese MOTD (ID: <?php echo $motd_item['id']; ?>) löschen möchtest?');">Löschen</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($admin_page === 'titles'): ?>
        <section class="card">
            <div class="card-header"><h2>Custom Titles Verwalten</h2></div>

            <!-- Formular zum Erstellen/Bearbeiten von Titeln -->
            <form action="admin.php?page=titles" method="POST" class="mb-2 card" style="background-color: var(--color-background-light);">
                 <h4 class="mt-1"><?php echo $edit_title_id ? 'Titel bearbeiten' : 'Neuen Titel erstellen'; ?></h4>
                <input type="hidden" name="title_id" value="<?php echo $edit_title_id ? htmlspecialchars($edit_title_id) : ''; ?>">
                <div class="form-group">
                    <label for="title_name">Titelname:</label>
                    <input type="text" name="title_name" id="title_name" value="<?php echo htmlspecialchars($edit_title_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="title_description">Beschreibung (Optional):</label>
                    <textarea name="title_description" id="title_description" rows="2"><?php echo htmlspecialchars($edit_title_description); ?></textarea>
                </div>
                <button type="submit" name="save_custom_title" class="button"><?php echo $edit_title_id ? 'Änderungen speichern' : 'Titel erstellen'; ?></button>
                <?php if ($edit_title_id): ?>
                    <a href="admin.php?page=titles" class="button button-secondary">Abbrechen</a>
                <?php endif; ?>
            </form>

            <!-- Liste existierender Titel -->
            <h4>Existierende Titel</h4>
            <?php if (empty($all_titles)): ?>
                <p>Noch keine Custom Titles erstellt.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Titelname</th><th>Beschreibung</th><th>Aktionen</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_titles as $title): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($title['id']); ?></td>
                            <td><?php echo htmlspecialchars($title['title_name']); ?></td>
                            <td><?php echo htmlspecialchars($title['description'] ?? '-'); ?></td>
                            <td>
                                <a href="admin.php?page=titles&action=edit_title&title_id=<?php echo $title['id']; ?>" class="button btn-sm">Bearbeiten</a>
                                <?php if ($title['id'] != 1): // Standardtitel nicht löschbar ?>
                                <a href="admin.php?page=titles&action=delete_title&title_id=<?php echo $title['id']; ?>" class="button button-danger btn-sm" onclick="return confirm('Bist du sicher, dass du diesen Titel löschen möchtest? Benutzer mit diesem Titel verlieren ihn.');">Löschen</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Titel einem User zuweisen -->
            <h4 class="mt-2">Titel einem Benutzer zuweisen</h4>
            <form action="admin.php?page=titles" method="POST" class="card" style="background-color: var(--color-background-light);">
                <div class="form-group">
                    <label for="user_discord_id">Benutzer auswählen:</label>
                    <select name="user_discord_id" id="user_discord_id" required>
                        <option value="">-- Bitte Benutzer wählen --</option>
                        <?php foreach ($all_users as $user_item): ?>
                            <option value="<?php echo htmlspecialchars($user_item['discord_id']); ?>">
                                <?php echo htmlspecialchars($user_item['discord_username']); ?> (IGN: <?php echo htmlspecialchars($user_item['warframe_ign'] ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title_id_to_assign">Titel auswählen:</label>
                    <select name="title_id_to_assign" id="title_id_to_assign" required>
                        <option value="0">-- Keinen Titel (Entfernen) --</option>
                        <?php foreach ($all_titles as $title_item): ?>
                            <option value="<?php echo htmlspecialchars($title_item['id']); ?>">
                                <?php echo htmlspecialchars($title_item['title_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_title_to_user" class="button">Titel zuweisen/ändern</button>
            </form>
        </section>

    <?php elseif ($admin_page === 'users' && !$user_to_edit_admin): ?>
         <section class="card">
            <div class="card-header"><h2>Benutzer Verwalten</h2></div>
            <p>Wähle einen Benutzer aus der Liste, um dessen Admin-Status zu bearbeiten oder weitere Details anzuzeigen.</p>
            <?php if (empty($all_users)): ?>
                <p>Keine Benutzer gefunden.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Discord Name</th><th>Warframe IGN</th><th>Aktueller Titel</th><th>Ist Admin?</th><th>Aktionen</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_users as $user_item_admin):
                        $user_obj_for_admin = getUserByDiscordId($pdo, $user_item_admin['discord_id']); // Hole volles User-Objekt für isAdmin
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_item_admin['discord_username']); ?></td>
                            <td><?php echo htmlspecialchars($user_item_admin['warframe_ign'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user_item_admin['custom_title'] ?? 'Kein Titel'); ?></td>
                            <td><?php echo $user_obj_for_admin['isAdmin'] ? 'Ja' : 'Nein'; ?></td>
                            <td>
                                <a href="admin.php?page=edit_user&id=<?php echo htmlspecialchars($user_item_admin['discord_id']); ?>" class="button btn-sm">Admin-Status bearbeiten</a>
                                <a href="profil.php?id=<?php echo htmlspecialchars($user_item_admin['discord_id']); ?>" class="button btn-sm button-secondary" target="_blank">Profil ansehen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($admin_page === 'edit_user' && $user_to_edit_admin): ?>
        <section class="card">
            <div class="card-header">
                <h2>Admin-Status für <?php echo htmlspecialchars($user_to_edit_admin['discord_username']); ?> bearbeiten</h2>
            </div>
            <form action="admin.php?page=edit_user&id=<?php echo htmlspecialchars($edit_user_id_admin); ?>" method="POST">
                <input type="hidden" name="user_id_admin_edit" value="<?php echo htmlspecialchars($user_to_edit_admin['discord_id']); ?>">
                <div class="form-group">
                    <label for="is_admin_checkbox">
                        <input type="checkbox" name="is_admin_checkbox" id="is_admin_checkbox" value="1" <?php echo $user_to_edit_admin['isAdmin'] ? 'checked' : ''; ?>
                               <?php if ($user_to_edit_admin['discord_id'] === SUPER_ADMIN_DISCORD_ID) echo ' onclick="return false;"'; // Verhindert Klick bei SuperAdmin ?> >
                        Ist Administrator
                    </label>
                    <?php if ($user_to_edit_admin['discord_id'] === SUPER_ADMIN_DISCORD_ID): ?>
                        <p class="message info"><small>Der Haupt-Administrator darf sich nicht selbst die Admin-Rechte entziehen.</small></p>
                    <?php endif; ?>
                </div>
                <button type="submit" name="update_user_admin_status" class="button">Admin-Status Speichern</button>
                <a href="admin.php?page=users" class="button button-secondary">Zurück zur Benutzerübersicht</a>
            </form>
        </section>

    <?php elseif ($admin_page === 'changelog'):
        $all_changelog_entries = getAllChangelogEntries($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changelog_entry'])) {
            $changelog_version_tag = trim($_POST['changelog_version_tag'] ?? '');
            $changelog_summary = trim($_POST['changelog_summary'] ?? '');

            if (empty($changelog_version_tag) || empty($changelog_summary)) {
                $_SESSION['global_message'] = "Version-Tag und Zusammenfassung dürfen nicht leer sein.";
                $_SESSION['global_message_type'] = "error";
            } else {
                if (createChangelogEntry($pdo, $changelog_version_tag, $changelog_summary, $_SESSION['discord_id'])) {
                    $_SESSION['global_message'] = "Changelog-Eintrag erfolgreich erstellt.";
                    $_SESSION['global_message_type'] = "success";
                    header("Location: " . BASE_URL . "/admin.php?page=changelog&changelog_saved=1");
                    exit;
                } else {
                    $_SESSION['global_message'] = "Fehler beim Erstellen des Changelog-Eintrags.";
                    $_SESSION['global_message_type'] = "error";
                }
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete_changelog' && isset($_GET['changelog_id'])) {
            $changelog_id_to_delete = filter_var($_GET['changelog_id'], FILTER_VALIDATE_INT);
            if ($changelog_id_to_delete && deleteChangelogEntry($pdo, $changelog_id_to_delete)) {
                $_SESSION['global_message'] = "Changelog-Eintrag erfolgreich gelöscht.";
                $_SESSION['global_message_type'] = "success";
            } else {
                $_SESSION['global_message'] = "Fehler beim Löschen des Changelog-Eintrags.";
                $_SESSION['global_message_type'] = "error";
            }
            header("Location: " . BASE_URL . "/admin.php?page=changelog&changelog_action_done=1");
            exit;
        }
    ?>
        <section class="card">
            <div class="card-header"><h2>Changelog Verwalten</h2></div>
            <form action="admin.php?page=changelog" method="POST" class="mb-2 card" style="background-color: var(--color-background-light);">
                <h4>Neuen Changelog-Eintrag erstellen</h4>
                <div class="form-group">
                    <label for="changelog_version_tag">Version / Tag (z.B. 1.0.1, Hotfix-Datum):</label>
                    <input type="text" name="changelog_version_tag" id="changelog_version_tag" required>
                </div>
                <div class="form-group">
                    <label for="changelog_summary">Zusammenfassung der Änderungen (Markdown erlaubt für Listen etc.):</label>
                    <textarea name="changelog_summary" id="changelog_summary" rows="5" required></textarea>
                </div>
                <button type="submit" name="save_changelog_entry" class="button">Changelog-Eintrag Speichern</button>
            </form>

            <h4 class="mt-2">Bestehende Changelog-Einträge</h4>
            <?php if (empty($all_changelog_entries)): ?>
                <p>Noch keine Changelog-Einträge vorhanden.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Version/Tag</th>
                            <th>Zusammenfassung (Auszug)</th>
                            <th>Autor</th>
                            <th>Datum</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_changelog_entries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['id']); ?></td>
                            <td><?php echo htmlspecialchars($entry['version_tag']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($entry['summary'], 0, 100) . (strlen($entry['summary']) > 100 ? '...' : ''))); ?></td>
                            <td><?php echo htmlspecialchars($entry['author_username'] ?? 'N/A'); ?></td>
                            <td><?php echo date("d.m.Y H:i", strtotime($entry['created_at'])); ?></td>
                            <td>
                                <a href="admin.php?page=changelog&action=delete_changelog&changelog_id=<?php echo $entry['id']; ?>" class="button button-danger btn-sm" onclick="return confirm('Bist du sicher, dass du diesen Changelog-Eintrag löschen möchtest?');">Löschen</a>
                                <!-- Bearbeiten-Funktion könnte hier noch hinzugefügt werden -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>


    <?php else: ?>
        <p>Wähle einen Bereich aus der Navigation.</p>
    <?php endif; ?>
</div>

<style>
.admin-nav ul {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
    display: flex;
    gap: 0.5rem;
    background-color: var(--color-background-medium);
    padding: 0.5rem;
    border-radius: 4px;
}
.admin-nav li a {
    display: block;
    padding: 0.75rem 1.25rem;
    color: var(--color-text-secondary);
    text-decoration: none;
    border-radius: 3px;
    font-weight: bold;
    transition: background-color 0.2s ease, color 0.2s ease;
}
.admin-nav li a:hover {
    background-color: var(--color-background-light);
    color: var(--color-primary-accent);
}
.admin-nav li a.active {
    background-color: var(--color-primary-accent);
    color: var(--color-background-dark);
    box-shadow: 0 0 8px rgba(var(--color-primary-accent-rgb), 0.5);
}
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.3rem; }
.form-group input[type="text"],
.form-group input[type="checkbox"],
.form-group textarea,
.form-group select {
    width: 100%;
    /* Styling wird von globalem CSS geerbt, ggf. hier anpassen */
}
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
