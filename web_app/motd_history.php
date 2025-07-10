<?php
define('PAGE_TITLE', 'MOTD Verlauf & Changelog');
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/user_functions.php';

if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte einloggen, um diese Seite zu sehen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$all_motds = getAllMotds($pdo); // Holt alle MOTDs, neueste zuerst
$all_changelog_entries = getAllChangelogEntries($pdo); // Holt alle Changelog-Einträge

// Markdown-Parsing Funktion (einfach)
if (!function_exists('parse_markdown_simple')) {
    function parse_markdown_simple($text) {
        // Zeilenumbrüche zuerst, dann htmlspecialchars auf den ursprünglichen Text anwenden
        $text_safe = htmlspecialchars($text ?? ''); // Stellt sicher, dass $text kein null ist
        $text_nl2br = nl2br($text_safe);
        // Einfache Listen (ul/li) mit - oder *
        $text_final = preg_replace('/^[-\*]\s+(.*)$/m', '<li>$1</li>', $text_nl2br);
        $text_final = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ul>>$0</ul>', $text_final); // Kleiner Fix für valides HTML bei Listen
        // Einfache Fettung mit **text**
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        // Einfache Kursivschrift mit *text* oder _text_ (vorsichtiger, um nicht mit Listen zu kollidieren)
        // $text = preg_replace('/(?<!\*)\*(?!\s)(.*?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $text);
        return $text;
    }
}

?>

<h1>MOTD Verlauf & Plattform Changelog</h1>

<section class="card">
    <div class="card-header">
        <h2>MOTD Verlauf</h2>
    </div>
    <?php if (empty($all_motds)): ?>
        <p>Keine MOTDs im Verlauf vorhanden.</p>
    <?php else: ?>
        <?php foreach ($all_motds as $motd): ?>
            <article class="motd-history-item <?php echo $motd['is_published'] ? 'published' : 'draft'; ?>">
                <h3>
                    <?php echo htmlspecialchars(!empty($motd['title']) ? $motd['title'] : ('MOTD vom ' . date("d.m.Y", strtotime($motd['created_at'])))); ?>
                    <?php if (!$motd['is_published']): ?>
                        <span class="badge draft-badge">Entwurf</span>
                    <?php endif; ?>
                </h3>
                <?php if (!empty($motd['version'])): ?>
                    <p class="meta"><small>Version: <?php echo htmlspecialchars($motd['version'] ?? ''); ?></small></p>
                <?php endif; ?>
                <div class="motd-content markdown-content">
                    <?php echo parse_markdown_extended($motd['content'] ?? ''); ?>
                </div>
                <p class="meta">
                    <em>
                        Erstellt von <?php echo htmlspecialchars($motd['author_username'] ?? 'Unbekannt'); ?>
                        am <?php echo date("d.m.Y H:i", strtotime($motd['created_at'])); ?>
                        (Zuletzt aktualisiert: <?php echo date("d.m.Y H:i", strtotime($motd['updated_at'])); ?>)
                    </em>
                </p>
            </article>
            <hr class="motd-divider">
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section class="card mt-2">
    <div class="card-header">
        <h2>Plattform Changelog</h2>
    </div>
    <?php if (empty($all_changelog_entries)): ?>
        <p>Noch keine Changelog-Einträge vorhanden.</p>
    <?php else: ?>
        <?php foreach ($all_changelog_entries as $entry): ?>
            <article class="changelog-item">
                <h3>Version: <?php echo htmlspecialchars($entry['version_tag'] ?? 'N/A'); ?>
                    <span class="changelog-date"><small>(<?php echo date("d.m.Y", strtotime($entry['created_at'])); ?>)</small></span>
                </h3>
                <div class="changelog-summary markdown-content">
                    <?php echo parse_markdown_extended($entry['summary'] ?? ''); ?>
                </div>
                <p class="meta"><small>Eingetragen von: <?php echo htmlspecialchars($entry['author_username'] ?? 'Unbekannt'); ?></small></p>
            </article>
            <hr class="changelog-divider">
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<style>
.motd-history-item, .changelog-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
}
.motd-history-item:last-child, .changelog-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
}
hr.motd-divider, hr.changelog-divider {
    border: 0;
    height: 1px;
    background-color: var(--color-border);
    margin: 1rem 0;
}
.motd-history-item:last-child hr.motd-divider, .changelog-item:last-child hr.changelog-divider {
    display: none;
}
.motd-history-item.draft {
    opacity: 0.7;
    border-left: 4px solid var(--color-warning);
}
.badge.draft-badge {
    font-size: 0.8em;
    padding: 0.2em 0.5em;
    background-color: var(--color-warning);
    color: var(--button-text-dark); /* oder passender Kontrast */
    border-radius: 3px;
    margin-left: 0.5em;
    text-transform: uppercase;
}
.motd-content, .changelog-summary {
    padding: 0.5em 0;
}
.motd-content ul, .changelog-summary ul {
    margin-left: 20px; /* Einrücken für Listen */
    margin-bottom: 1em;
}
.motd-content li, .changelog-summary li {
    margin-bottom: 0.3em;
}
.meta {
    font-size: 0.85em;
    color: var(--color-text-secondary);
}
.changelog-date {
    font-weight: normal;
    color: var(--color-text-secondary);
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
