<?php
// Diese Datei enthält Hilfsfunktionen für Benutzeroperationen.
// Sie geht davon aus, dass $pdo (PDO-Objekt) bereits verfügbar ist (durch Einbinden von db.php).

/**
 * Holt einen Benutzer anhand seiner Discord-ID aus der Datenbank.
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $discord_id Die Discord-ID des Benutzers.
 * @return array|null Die Benutzerdaten als assoziatives Array oder null, wenn nicht gefunden.
 */
function getUserByDiscordId(PDO $pdo, string $discord_id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE discord_id = :discord_id");
    $stmt->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * Erstellt einen neuen Benutzer in der Datenbank.
 * Der SuperAdmin (gemäß schema.sql) erhält standardmäßig isAdmin=true und einen Standardtitel.
 * Andere neue User erhalten isAdmin=false und können später einen Titel bekommen.
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $discord_id Die Discord-ID des Benutzers.
 * @param string $discord_username Der Discord-Benutzername.
 * @param string|null $discord_avatar_url Die URL zum Discord-Avatar (optional).
 * @return bool True bei Erfolg, False bei Fehler.
 */
function createUser(PDO $pdo, string $discord_id, string $discord_username, ?string $discord_avatar_url): bool {
    // Standardwerte für neue Benutzer
    $isAdmin = false;
    $custom_title_id = null; // Standardmäßig kein Titel, oder ID eines Standardtitels

    // Prüfe, ob es der SuperAdmin ist (Discord ID aus der Konfiguration/schema.sql)
    // Die isAdmin-Eigenschaft für den SuperAdmin wird primär durch schema.sql gesetzt.
    // Hier könnten wir es doppelt prüfen oder uns auf das SQL-Schema verlassen.
    // Für Einfachheit verlassen wir uns auf das SQL-Schema für die initiale Admin-Zuweisung.
    // Wenn ein User hier erstellt wird, der zufällig die Admin-ID hat, aber noch nicht in der DB ist,
    // könnte man ihn hier auch als Admin markieren.
    // In schema.sql wird der Admin aber bereits mit discord_username 'InitialAdmin' angelegt.
    // Ein normaler User, der sich neu anmeldet, sollte nicht automatisch Admin werden.

    // Hole den Standardtitel 'Tenno' (ID 1 laut schema.sql), falls er existiert
    $stmt_title = $pdo->query("SELECT id FROM custom_titles WHERE id = 1 OR title_name = 'Tenno' LIMIT 1");
    $default_title = $stmt_title->fetch();
    if ($default_title) {
        $custom_title_id = $default_title['id'];
    }

    // Wenn der User, der erstellt wird, der SuperAdmin ist (gemäß schema.sql),
    // und er aus irgendeinem Grund noch nicht existiert (sollte nicht passieren),
    // dann setze isAdmin hier. Normalerweise ist der Admin schon in der DB.
    $superAdminId = defined('SUPER_ADMIN_DISCORD_ID') ? SUPER_ADMIN_DISCORD_ID : '1020559274012852294';
    if ($discord_id === $superAdminId) {
        $isAdmin = true;
    }

    $sql = "INSERT INTO users (discord_id, discord_username, discord_avatar_url, isAdmin, custom_title_id, warframe_ign)
            VALUES (:discord_id, :discord_username, :discord_avatar_url, :isAdmin, :custom_title_id, NULL)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
        $stmt->bindParam(':discord_username', $discord_username, PDO::PARAM_STR);
        if ($discord_avatar_url === null) {
            $stmt->bindValue(':discord_avatar_url', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':discord_avatar_url', $discord_avatar_url, PDO::PARAM_STR);
        }
        $stmt->bindParam(':isAdmin', $isAdmin, PDO::PARAM_BOOL);
        $stmt->bindParam(':custom_title_id', $custom_title_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        // Fehler loggen
        error_log("Fehler beim Erstellen des Benutzers (createUser): " . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "DB Fehler (createUser): " . $e->getMessage(); // Nur im Debug-Modus
        }
        return false;
    }
}

/**
 * Aktualisiert die Discord-spezifischen Details eines Benutzers (Benutzername, Avatar).
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $discord_id Die Discord-ID des Benutzers.
 * @param string $discord_username Der neue Discord-Benutzername.
 * @param string|null $discord_avatar_url Die neue URL zum Discord-Avatar (optional).
 * @return bool True bei Erfolg, False bei Fehler.
 */
function updateUserDiscordDetails(PDO $pdo, string $discord_id, string $discord_username, ?string $discord_avatar_url): bool {
    $sql = "UPDATE users SET discord_username = :discord_username, discord_avatar_url = :discord_avatar_url, updated_at = NOW()
            WHERE discord_id = :discord_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':discord_username', $discord_username, PDO::PARAM_STR);
        if ($discord_avatar_url === null) {
            $stmt->bindValue(':discord_avatar_url', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':discord_avatar_url', $discord_avatar_url, PDO::PARAM_STR);
        }
        $stmt->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler beim Aktualisieren der Discord-Details (updateUserDiscordDetails): " . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "DB Fehler (updateUserDiscordDetails): " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Aktualisiert die Profildaten eines Benutzers.
 * Erlaubt das Aktualisieren spezifischer Felder, die im $data Array übergeben werden.
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $discord_id Die Discord-ID des zu aktualisierenden Benutzers.
 * @param array $data Ein assoziatives Array mit den zu aktualisierenden Feldern und deren Werten.
 *                    Erlaubte Schlüssel: 'warframe_ign', 'about_me', 'nickname', 'age', 'origin',
 *                                     'main_frame', 'weapons', 'steam_profile', 'nintendo_friend_code'.
 * @return bool True bei Erfolg, False bei Fehler oder wenn keine Daten zum Aktualisieren vorhanden sind.
 */
function updateUserProfile(PDO $pdo, string $discord_id, array $data): bool {
    $allowed_fields = [
        'warframe_ign', 'about_me', 'nickname', 'age',
        'origin', 'main_frame', 'weapons', 'steam_profile', 'nintendo_friend_code'
        // 'custom_avatar_path' entfernt
    ];

    $fields_to_update = [];
    $params = [':discord_id' => $discord_id];

    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $data)) {
            // Spezielle Behandlung für 'age', da es ein Integer sein sollte oder NULL
            if ($field === 'age') {
                if ($data[$field] === '' || $data[$field] === null) {
                    $fields_to_update[] = "`{$field}` = NULL";
                } else {
                    $fields_to_update[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = (int)$data[$field];
                }
            } else {
                 // Für andere Felder: Leerer String wird als NULL gespeichert, wenn das Feld NULL erlaubt
                 // oder als leerer String, falls nicht. In unserem Schema sind die meisten DEFAULT NULL.
                if ($data[$field] === '' && in_array($field, ['warframe_ign', 'about_me', 'nickname', 'origin', 'main_frame', 'weapons', 'steam_profile', 'nintendo_friend_code'])) {
                     // $fields_to_update[] = "`{$field}` = NULL"; // Oder als leerer String speichern, je nach Präferenz
                     $fields_to_update[] = "`{$field}` = :{$field}";
                     $params[":{$field}"] = ($data[$field] === '') ? null : $data[$field];
                } else {
                    $fields_to_update[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }
        }
    }

    if (empty($fields_to_update)) {
        return false; // Keine gültigen Felder zum Aktualisieren
    }

    $sql = "UPDATE users SET " . implode(', ', $fields_to_update) . ", updated_at = NOW() WHERE discord_id = :discord_id";

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Fehler beim Aktualisieren des Benutzerprofils (updateUserProfile): " . $e->getMessage() . " SQL: " . $sql . " Params: " . print_r($params, true));
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "DB Fehler (updateUserProfile): " . $e->getMessage();
        }
        return false;
    }
}


/**
 * Überprüft, ob ein Benutzer Administratorrechte hat.
 *
 * @param PDO $pdo Das PDO-Datenbankobjekt.
 * @param string $discord_id Die Discord-ID des Benutzers.
 * @return bool True, wenn der Benutzer Admin ist, sonst False.
 */
function isUserAdmin(PDO $pdo, string $discord_id): bool {
    $user = getUserByDiscordId($pdo, $discord_id);
    return $user && isset($user['isAdmin']) && (bool)$user['isAdmin'];
}


// --- Weitere Hilfsfunktionen (MOTD, Kommentare, Titel etc.) ---

/**
 * Holt die neueste *veröffentlichte* Message of the Day (MOTD).
 * @param PDO $pdo
 * @return array|null
 */
function getLatestPublishedMotd(PDO $pdo): ?array {
    $sql = "SELECT m.id, m.title, m.content, m.updated_at, m.version, u.discord_username as author_username
            FROM motds m
            JOIN users u ON m.created_by_discord_id = u.discord_id
            WHERE m.is_published = TRUE
            ORDER BY m.created_at DESC, m.id DESC
            LIMIT 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Holt alle MOTDs für die Admin-Übersicht.
 * @param PDO $pdo
 * @return array
 */
function getAllMotds(PDO $pdo): array {
    $sql = "SELECT m.id, m.title, m.content, m.is_published, m.version, m.created_at, m.updated_at, u.discord_username as author_username
            FROM motds m
            JOIN users u ON m.created_by_discord_id = u.discord_id
            ORDER BY m.created_at DESC, m.id DESC"; // m.content hinzugefügt
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Holt eine spezifische MOTD anhand ihrer ID.
 * @param PDO $pdo
 * @param int $motd_id
 * @return array|null
 */
function getMotdById(PDO $pdo, int $motd_id): ?array {
    $sql = "SELECT m.id, m.title, m.content, m.is_published, m.version, m.created_by_discord_id, u.discord_username as author_username
            FROM motds m
            LEFT JOIN users u ON m.created_by_discord_id = u.discord_id
            WHERE m.id = :motd_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':motd_id', $motd_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


/**
 * Erstellt eine neue MOTD.
 * @param PDO $pdo
 * @param array $data (Keys: title, content, created_by_discord_id, is_published, version)
 * @return bool|string ID des neuen Eintrags bei Erfolg, sonst false.
 */
function createMotd(PDO $pdo, array $data) {
    $sql = "INSERT INTO motds (title, content, created_by_discord_id, is_published, version, created_at, updated_at)
            VALUES (:title, :content, :created_by_discord_id, :is_published, :version, NOW(), NOW())";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':content', $data['content']);
        $stmt->bindParam(':created_by_discord_id', $data['created_by_discord_id']);
        $stmt->bindParam(':is_published', $data['is_published'], PDO::PARAM_BOOL);
        $stmt->bindParam(':version', $data['version']);

        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Fehler bei createMotd: " . $e->getMessage());
        return false;
    }
}

/**
 * Aktualisiert eine bestehende MOTD.
 * @param PDO $pdo
 * @param int $motd_id
 * @param array $data (Keys: title, content, is_published, version)
 * @return bool
 */
function updateMotd(PDO $pdo, int $motd_id, array $data): bool {
    $sql = "UPDATE motds SET title = :title, content = :content, is_published = :is_published, version = :version, updated_at = NOW()
            WHERE id = :motd_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':content', $data['content']);
        $stmt->bindParam(':is_published', $data['is_published'], PDO::PARAM_BOOL);
        $stmt->bindParam(':version', $data['version']);
        $stmt->bindParam(':motd_id', $motd_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei updateMotd: " . $e->getMessage());
        return false;
    }
}

/**
 * Löscht eine MOTD.
 * @param PDO $pdo
 * @param int $motd_id
 * @return bool
 */
function deleteMotd(PDO $pdo, int $motd_id): bool {
    $sql = "DELETE FROM motds WHERE id = :motd_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':motd_id', $motd_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei deleteMotd: " . $e->getMessage());
        return false;
    }
}


/**
 * Holt alle Custom Titles.
 * @param PDO $pdo
 * @return array
 */
function getAllCustomTitles(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, title_name, description FROM custom_titles ORDER BY title_name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Holt einen spezifischen Custom Title anhand seiner ID.
 * @param PDO $pdo
 * @param int $title_id
 * @return array|null
 */
function getCustomTitleById(PDO $pdo, int $title_id): ?array {
    $stmt = $pdo->prepare("SELECT id, title_name, description FROM custom_titles WHERE id = :id");
    $stmt->bindParam(':id', $title_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


/**
 * Erstellt einen neuen Custom Title.
 * @param PDO $pdo
 * @param string $title_name
 * @param string|null $description
 * @return bool
 */
function createCustomTitle(PDO $pdo, string $title_name, ?string $description): bool {
    $sql = "INSERT INTO custom_titles (title_name, description) VALUES (:title_name, :description)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title_name', $title_name);
        $stmt->bindParam(':description', $description);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei createCustomTitle: " . $e->getMessage());
        return false; // Evtl. spezifischer Fehlercode für 'unique constraint'
    }
}

/**
 * Aktualisiert einen Custom Title.
 * @param PDO $pdo
 * @param int $title_id
 * @param string $title_name
 * @param string|null $description
 * @return bool
 */
function updateCustomTitle(PDO $pdo, int $title_id, string $title_name, ?string $description): bool {
    $sql = "UPDATE custom_titles SET title_name = :title_name, description = :description WHERE id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title_name', $title_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $title_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei updateCustomTitle: " . $e->getMessage());
        return false;
    }
}

/**
 * Löscht einen Custom Title.
 * Stellt sicher, dass User, die diesen Titel hatten, auf NULL oder einen Standardtitel gesetzt werden.
 * @param PDO $pdo
 * @param int $title_id
 * @return bool
 */
function deleteCustomTitle(PDO $pdo, int $title_id): bool {
    try {
        $pdo->beginTransaction();
        // Setze custom_title_id bei Usern auf NULL, die diesen Titel haben
        $stmt_users = $pdo->prepare("UPDATE users SET custom_title_id = NULL WHERE custom_title_id = :title_id");
        $stmt_users->bindParam(':title_id', $title_id, PDO::PARAM_INT);
        $stmt_users->execute();

        // Lösche den Titel
        $stmt_title = $pdo->prepare("DELETE FROM custom_titles WHERE id = :title_id");
        $stmt_title->bindParam(':title_id', $title_id, PDO::PARAM_INT);
        $stmt_title->execute();

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Fehler bei deleteCustomTitle: " . $e->getMessage());
        return false;
    }
}

/**
 * Weist einem User einen Custom Title zu.
 * @param PDO $pdo
 * @param string $discord_id
 * @param int|null $title_id (null zum Entfernen des Titels)
 * @return bool
 */
function assignCustomTitleToUser(PDO $pdo, string $discord_id, ?int $title_id): bool {
    $sql = "UPDATE users SET custom_title_id = :title_id WHERE discord_id = :discord_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title_id', $title_id, PDO::PARAM_INT); // PDO::PARAM_INT erlaubt NULL
        $stmt->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei assignCustomTitleToUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Holt alle Kommentare für ein bestimmtes Profil.
 * @param PDO $pdo
 * @param string $profile_discord_id
 * @return array
 */
function getProfileComments(PDO $pdo, string $profile_discord_id): array {
    $sql = "SELECT pc.id, pc.comment, pc.created_at,
                   u.discord_username as author_username,
                   u.discord_avatar_url as author_discord_avatar_url,
                   -- u.custom_avatar_path as author_custom_avatar_path, -- Entfernt
                   u.discord_id as author_discord_id
            FROM profile_comments pc
            JOIN users u ON pc.author_discord_id = u.discord_id
            WHERE pc.profile_discord_id = :profile_id
            ORDER BY pc.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':profile_id', $profile_discord_id, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fügt einen neuen Kommentar zu einem Profil hinzu.
 * @param PDO $pdo
 * @param string $profile_discord_id
 * @param string $author_discord_id
 * @param string $comment_text
 * @return bool
 */
function addProfileComment(PDO $pdo, string $profile_discord_id, string $author_discord_id, string $comment_text): bool {
    // Einfache Validierung: Kommentar darf nicht leer sein
    if (trim($comment_text) === '') {
        return false;
    }
    // Weitere Validierungen / Bereinigungen könnten hier stattfinden

    $sql = "INSERT INTO profile_comments (profile_discord_id, author_discord_id, comment)
            VALUES (:profile_id, :author_id, :comment)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':profile_id', $profile_discord_id, PDO::PARAM_STR);
        $stmt->bindParam(':author_id', $author_discord_id, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment_text, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei addProfileComment: " . $e->getMessage());
        return false;
    }
}

/**
 * Löscht einen Kommentar.
 * Stellt sicher, dass nur der Autor des Kommentars oder ein Admin ihn löschen kann.
 * @param PDO $pdo
 * @param int $comment_id
 * @param string $current_user_discord_id
 * @param bool $is_current_user_admin
 * @return bool
 */
function deleteProfileComment(PDO $pdo, int $comment_id, string $current_user_discord_id, bool $is_current_user_admin): bool {
    // Erst den Kommentar holen, um den Autor zu prüfen
    $stmt_check = $pdo->prepare("SELECT author_discord_id FROM profile_comments WHERE id = :comment_id");
    $stmt_check->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $comment_data = $stmt_check->fetch();

    if (!$comment_data) {
        return false; // Kommentar nicht gefunden
    }

    if ($comment_data['author_discord_id'] === $current_user_discord_id || $is_current_user_admin) {
        // User ist Autor oder Admin, darf löschen
        $sql = "DELETE FROM profile_comments WHERE id = :comment_id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Fehler bei deleteProfileComment: " . $e->getMessage());
            return false;
        }
    }
    return false; // Keine Berechtigung zum Löschen
}


/**
 * Holt alle Benutzer für die Mitgliederliste.
 * @param PDO $pdo
 * @return array
 */
function getAllUsersForList(PDO $pdo): array {
    // Wähle relevante Felder für die Liste, ggf. JOIN mit custom_titles
    $sql = "SELECT u.discord_id, u.discord_username, u.discord_avatar_url, u.warframe_ign, ct.title_name as custom_title
            FROM users u
            LEFT JOIN custom_titles ct ON u.custom_title_id = ct.id
            ORDER BY u.discord_username ASC"; // u.custom_avatar_path entfernt
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Holt alle Syndikat-Tags für einen Benutzer.
 * @param PDO $pdo
 * @param string $discord_id
 * @return array
 */
function getUserSyndicates(PDO $pdo, string $discord_id): array {
    $stmt = $pdo->prepare("SELECT syndicate_name, rank, color_hex FROM user_syndicates WHERE user_discord_id = :discord_id ORDER BY syndicate_name");
    $stmt->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Weitere Funktionen für Syndikate (Hinzufügen, Löschen durch Admin) könnten hier folgen.

// --- Syndikat Konstanten und Funktionen ---
define('WARFRAME_SYNDICATES', [
    'Steel Meridian' => '#781d1d',
    'Arbiters of Hexis' => '#1e2d38',
    'Cephalon Suda' => '#1e3d5a',
    'The Perrin Sequence' => '#2a4e8d',
    'Red Veil' => '#ff0000',
    'New Loka' => '#006400'
]);

/**
 * Aktualisiert die Syndikatszugehörigkeiten eines Benutzers.
 * Löscht alte Einträge und fügt die neu ausgewählten hinzu.
 * @param PDO $pdo
 * @param string $discord_id
 * @param array $selected_syndicates_names Array der Namen der ausgewählten Syndikate.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function updateUserSyndicates(PDO $pdo, string $discord_id, array $selected_syndicates_names): bool {
    try {
        $pdo->beginTransaction();

        // 1. Alte Syndikate für den User löschen
        $stmt_delete = $pdo->prepare("DELETE FROM user_syndicates WHERE user_discord_id = :discord_id");
        $stmt_delete->bindParam(':discord_id', $discord_id, PDO::PARAM_STR);
        $stmt_delete->execute();

        // 2. Neue Syndikate einfügen
        if (!empty($selected_syndicates_names)) {
            $sql_insert = "INSERT INTO user_syndicates (user_discord_id, syndicate_name, color_hex) VALUES (:user_discord_id, :syndicate_name, :color_hex)";
            $stmt_insert = $pdo->prepare($sql_insert);

            foreach ($selected_syndicates_names as $syndicate_name) {
                if (array_key_exists($syndicate_name, WARFRAME_SYNDICATES)) {
                    $color_hex = WARFRAME_SYNDICATES[$syndicate_name];
                    $stmt_insert->bindParam(':user_discord_id', $discord_id, PDO::PARAM_STR);
                    $stmt_insert->bindParam(':syndicate_name', $syndicate_name, PDO::PARAM_STR);
                    $stmt_insert->bindParam(':color_hex', $color_hex, PDO::PARAM_STR);
                    $stmt_insert->execute();
                }
            }
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Fehler bei updateUserSyndicates: " . $e->getMessage());
        return false;
    }
}


// --- Changelog Funktionen ---

/**
 * Erstellt einen neuen Changelog-Eintrag.
 * @param PDO $pdo
 * @param string $version_tag
 * @param string $summary
 * @param string $admin_discord_id
 * @return bool|string ID des neuen Eintrags oder false
 */
function createChangelogEntry(PDO $pdo, string $version_tag, string $summary, string $admin_discord_id) {
    $sql = "INSERT INTO changelog (version_tag, summary, created_by_discord_id, created_at)
            VALUES (:version_tag, :summary, :created_by_discord_id, NOW())";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':version_tag', $version_tag);
        $stmt->bindParam(':summary', $summary);
        $stmt->bindParam(':created_by_discord_id', $admin_discord_id);
        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Fehler bei createChangelogEntry: " . $e->getMessage());
        return false;
    }
}

/**
 * Holt alle Changelog-Einträge, neueste zuerst.
 * @param PDO $pdo
 * @return array
 */
function getAllChangelogEntries(PDO $pdo): array {
    $sql = "SELECT cl.id, cl.version_tag, cl.summary, cl.created_at, u.discord_username as author_username
            FROM changelog cl
            LEFT JOIN users u ON cl.created_by_discord_id = u.discord_id
            ORDER BY cl.created_at DESC, cl.id DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Löscht einen Changelog-Eintrag.
 * @param PDO $pdo
 * @param int $changelog_id
 * @return bool
 */
function deleteChangelogEntry(PDO $pdo, int $changelog_id): bool {
    $sql = "DELETE FROM changelog WHERE id = :changelog_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':changelog_id', $changelog_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Fehler bei deleteChangelogEntry: " . $e->getMessage());
        return false;
    }
}

// --- Markdown Parsing Funktion ---

/**
 * Parst einen Text mit erweiterter Markdown-Syntax (Discord-ähnlich) in HTML.
 * Wichtig: htmlspecialchars() wird auf den Inhalt angewendet, bevor HTML-Tags hinzugefügt werden.
 * nl2br() wird am Ende für Zeilenumbrüche angewendet, die nicht Teil von Block-Elementen sind.
 *
 * Unterstützt:
 * - Fett: **text**
 * - Kursiv: *text* oder _text_
 * - Unterstrichen: __text__
 * - Durchgestrichen: ~~text~~
 * - Inline Code: `code`
 * - Code Blöcke: ```[lang]\ncode\n```
 * - Spoiler: ||text||
 * - Blockquotes: > text oder >>> text
 * - Ungeordnete Listen: - text oder * text (einfach, nicht verschachtelt)
 * - Geordnete Listen: 1. text (einfach, nicht verschachtelt)
 *
 * @param string|null $text Der zu parsende Text.
 * @return string Der HTML-formatierte Text.
 */
function parse_markdown_extended(?string $text): string {
    if ($text === null || $text === '') {
        return '';
    }

    // 1. HTML-Sonderzeichen im gesamten Text escapen, um XSS zu verhindern.
    // Dies geschieht später selektiv für Inhalte, um Markdown-Struktur zu erhalten.
    // $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Temporäre Platzhalter für Code-Blöcke und Inline-Code, um Konflikte zu vermeiden
    $code_blocks = [];
    $inline_codes = [];
    $placeholder_prefix_block = "%%CODEBLOCK_PLACEHOLDER_";
    $placeholder_prefix_inline = "%%INLINECODE_PLACEHOLDER_";
    $counter = 0;

    // 2. Mehrzeilige Code-Blöcke extrahieren und escapen
    // ```lang\ncode\n``` oder ```code\n```
    $text = preg_replace_callback('/^```(?:(\w+)\n)?(.*?)^```/ms', function ($matches) use (&$code_blocks, &$counter, $placeholder_prefix_block) {
        $lang = !empty($matches[1]) ? htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') : '';
        $code_content = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8'); // Inhalt des Code-Blocks escapen
        $placeholder = $placeholder_prefix_block . ($counter++) . '%%';
        $code_blocks[$placeholder] = "<pre><code" . ($lang ? " class=\"language-{$lang}\"" : "") . ">" . $code_content . "</code></pre>";
        return $placeholder;
    }, $text);

    // 3. Inline Code extrahieren und escapen
    // `code` (auch mit Backticks im Code, behandelt durch non-greedy Match)
    $text = preg_replace_callback('/`(.+?)`/s', function ($matches) use (&$inline_codes, &$counter, $placeholder_prefix_inline) {
        $code_content = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8'); // Inhalt des Inline-Codes escapen
        $placeholder = $placeholder_prefix_inline . ($counter++) . '%%';
        $inline_codes[$placeholder] = "<code>" . $code_content . "</code>";
        return $placeholder;
    }, $text);

    // Übrige HTML-Sonderzeichen im verbleibenden Text escapen
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 4. Spoiler: ||text||
    $text = preg_replace('/\|\|(.*?)\|\|/s', '<span class="spoiler">$1</span>', $text);

    // 5. Blockquotes: >>> text (mehrzeilig) oder > text (einzeilig)
    // Muss vor nl2br und anderen Inline-Formatierungen passieren
    // Mehrzeilige Blockquotes
    $text = preg_replace('/^>>>\s?(.*)/m', "<blockquote>$1</blockquote>", $text); // Einfache Version für mehrzeilig
    // Einzeilige Blockquotes
    $text = preg_replace('/^>\s?(.*)/m', "<blockquote>$1</blockquote>", $text);


    // 6. Fett: **text**
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    // 7. Unterstrichen: __text__
    $text = preg_replace('/__(.*?)__/s', '<u>$1</u>', $text);
    // 8. Kursiv: *text* oder _text_
    // Um Konflikte mit ** und __ zu vermeiden, und um _innerhalb_von_worten_ zu ignorieren:
    $text = preg_replace('/(?<!\w)(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)(?!\w)/s', '<em>$1</em>', $text); // *kursiv*
    $text = preg_replace('/(?<!\w)(?<!_)_(?!\s)(.+?)(?<!\s)_(?!_)(?!\w)/s', '<em>$1</em>', $text);   // _kursiv_
    // 9. Durchgestrichen: ~~text~~
    $text = preg_replace('/~~(.*?)~~/s', '<s>$1</s>', $text);


    // 10. Listen (einfach, nicht verschachtelt)
    // Ungeordnete Listen
    $text = preg_replace('/^(?:[\*\-]\s+)(.*)/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>\s*)+/s', "<ul>\n$0</ul>\n", $text);
    // Geordnete Listen
    $text = preg_replace('/^(?:\d+\.\s+)(.*)/m', '<li>$1</li>', $text); // Erfasst nummerierte Einträge
    $text = preg_replace('/(<li>.*<\/li>\s*)+/s', "<ol>\n$0</ol>\n", $text); // Gruppiert sie, Problem: gruppiert auch ULs mit

    // Behebung des Listenproblems: UL und OL separat behandeln.
    // Dies ist immer noch rudimentär für komplexe Fälle.
    // Zuerst UL
    // $text = preg_replace('/^(?:[\*\-]\s+)(.*)/m', '<li>$1</li>', $text);
    // $text = preg_replace_callback('/((?:<li>.*?<\/li>\s*)+)/s', function($matches) {
    // if (strpos($matches[0], '<ol>') === false) { // Nur wenn nicht schon in OL
    // return "<ul>\n" . $matches[0] . "</ul>\n";
    // }
    // return $matches[0];
    // }, $text);
    // Dann OL
    // $text = preg_replace('/^(?:\d+\.\s+)(.*)/m', '<li>$1</li>', $text);
    // $text = preg_replace_callback('/((?:<li>.*?<\/li>\s*)+)/s', function($matches) {
    // if (strpos($matches[0], '<ul>') === false) { // Nur wenn nicht schon in UL
    // return "<ol>\n" . $matches[0] . "</ol>\n";
    // }
    // return $matches[0];
    // }, $text);
    // Die Listen-Regex ist knifflig, um UL und OL korrekt zu trennen und Verschachtelung zu vermeiden.
    // Für einfache, nicht-verschachtelte Listen reicht oft die obige Version.
    // Eine robustere Lösung würde einen komplexeren Parser oder eine Bibliothek erfordern.

    // 11. Zeilenumbrüche für Absätze (wird auf den gesamten Text angewendet, der nicht in Block-Elementen ist)
    // Dies muss sorgfältig geschehen, um nicht die Formatierung von <pre> oder <li> zu zerstören.
    // Eine Möglichkeit: nl2br() ganz am Ende, aber <pre> muss dann speziell behandelt werden.
    // Oder nl2br() vor den Block-Elementen, und dann die Block-Elemente parsen.
    // Fürs Erste: nl2br() am Ende, Code-Blöcke sind durch Platzhalter geschützt.
    $text = nl2br($text, false); // false für XHTML-konforme <br />

    // 12. Platzhalter wieder einfügen
    if (!empty($code_blocks)) {
        $text = str_replace(array_keys($code_blocks), array_values($code_blocks), $text);
    }
    if (!empty($inline_codes)) {
        $text = str_replace(array_keys($inline_codes), array_values($inline_codes), $text);
    }

    return $text;
}


// --- Changelog Funktionen ---
