<?php
define('PAGE_TITLE', 'Build Kosten Rechner');
require_once __DIR__ . '/includes/header.php'; // Session, $is_logged_in
require_once __DIR__ . '/includes/db.php';     // $pdo (nicht direkt genutzt hier, aber oft im Header)

// Prüfen, ob der Benutzer eingeloggt ist (optional, je nach Anforderung)
if (!$is_logged_in) {
    $_SESSION['global_message'] = "Bitte zuerst einloggen, um den Rechner zu nutzen.";
    $_SESSION['global_message_type'] = "warning";
    header("Location: " . BASE_URL . "/index.php?redirect_to=" . urlencode(BASE_URL . "/rechner.php"));
    exit;
}

$overframe_url = '';
$extracted_mods = [];
$mod_prices = [];
$total_price_plat = 0;
$total_price_ducats = 0; // Für Baro Ki'Teer Items, falls relevant
$calculation_error = null;
$calculation_results_html = '';
$platform = 'pc'; // Standardplattform, könnte konfigurierbar gemacht werden

// Helper function to make cURL requests (generic)
function make_curl_request($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Wichtig für overframe.gg, da es ggf. weiterleitet
    curl_setopt($ch, CURLOPT_USERAGENT, 'EchoSolDojoBuildRechner/1.0 (https://dojo.chelo.lat; Kontakt admin@chelo.lat)'); // Höflicher User-Agent
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout für Anfragen
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['error' => "cURL Error: " . $curl_error, 'http_code' => $http_code, 'response' => null];
    }
    if ($http_code >= 400) {
         return ['error' => "HTTP Error: " . $http_code, 'http_code' => $http_code, 'response' => $response];
    }
    return ['error' => null, 'http_code' => $http_code, 'response' => $response];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_build'])) {
    $overframe_url = trim($_POST['overframe_url'] ?? '');
    $platform_form = trim($_POST['platform'] ?? 'pc');
    if (in_array($platform_form, ['pc', 'ps4', 'xbox', 'switch'])) {
        $platform = $platform_form;
    }

    if (empty($overframe_url)) {
        $calculation_error = "Bitte gib eine Overframe.gg URL ein.";
    } elseif (!filter_var($overframe_url, FILTER_VALIDATE_URL) || !strpos($overframe_url, 'overframe.gg/build/')) {
        $calculation_error = "Ungültige Overframe.gg Build URL.";
    } else {
        // 1. Overframe.gg Seite abrufen
        $calculation_results_html .= "<h3>Schritt 1: Lade Overframe.gg Seite...</h3>";
        $overframe_data = make_curl_request($overframe_url);

        if ($overframe_data['error']) {
            $calculation_error = "Fehler beim Abrufen der Overframe.gg Seite: " . $overframe_data['error'];
        } else {
            $html_content = $overframe_data['response'];
            $calculation_results_html .= "<p class='message success'>Overframe.gg Seite erfolgreich geladen (HTTP Status: {$overframe_data['http_code']}).</p>";

            // 2. Mod-Namen extrahieren (Dieser Teil ist sehr anfällig für Änderungen an der Overframe.gg Struktur)
            $calculation_results_html .= "<h3>Schritt 2: Extrahiere Mod-Namen...</h3>";
            $doc = new DOMDocument();
            @$doc->loadHTML($html_content); // @ unterdrückt Fehler bei ungültigem HTML
            $xpath = new DOMXPath($doc);

            // Verschiedene mögliche XPath-Ausdrücke, da sich die Struktur ändern kann.
            // Annahme: Mods sind oft in Elementen mit Klassennamen, die "mod_card_name", "mod-name", "mod-title" etc. enthalten
            // oder in <a> Tags mit hrefs, die "/items/..." enthalten und einen Titel haben.
            // Dies ist ein sehr generischer Ansatz. Man müsste die aktuelle Struktur von overframe.gg analysieren.
            // Beispiel-Selektor (muss angepasst werden!):
            // Suchen nach Elementen, die den Mod-Namen als Text enthalten könnten.
            // Oft sind Mod-Namen in <a>-Tags oder <span>-Tags innerhalb von komplexeren Strukturen.
            // $nodes = $xpath->query("//div[contains(@class, 'mod-slot')]//a[contains(@href, '/items/')]//span[contains(@class, 'name')] | //div[contains(@class, 'mod-slot')]//div[contains(@class, 'mod-name')]");

            // Ein häufigeres Muster ist, dass Mod-Namen in `<h4>` oder `<a>` Tags innerhalb von `<div>`s mit `data-id` Attributen für Mods stehen.
            // Oder in Elementen mit dem Titel des Mods.
            // Dieser Query sucht nach Elementen, die einen Titel haben, der typisch für Mod-Namen ist.
            // Und filtert dann bekannte Nicht-Mod-Titel heraus.
            // $query = "//*[self::h4 or self::a or self::div][normalize-space(text()) != '']"; // Sehr breit
            // $query = "//div[contains(@class, 'mod-name') or contains(@class, 'mod_card_name') or contains(@class, 'mod--name')]/text()"; // Direkter Text von "mod-name" Klassen
            // $query = "//a[contains(@href, '/modules/') or contains(@href, '/items/mods/')]/@title"; // Titel von Links zu Mods
            // $query = "//h4[contains(@class, 'name') and ../../@data-id]"; // h4 mit Klasse 'name' in einem div mit data-id (oft Mods)

            // Neuer Versuch basierend auf einer häufigen Struktur (Stand Juli 2024):
            // Mods sind oft als `<a>` in einer `div` mit `class="mod-image-wrapper"` und der Name ist im `title` Attribut des `<a>` oder im `alt` des `<img>` darin.
            // Oder in einem `<h4>` mit `class="name"`.
            $mod_name_nodes = $xpath->query("//div[contains(@class, 'mod-image-wrapper')]/a[@title] | //h4[contains(@class, 'name')]");

            if ($mod_name_nodes->length > 0) {
                foreach ($mod_name_nodes as $node) {
                    $mod_name = '';
                    if ($node->nodeName === 'a' && $node->hasAttribute('title')) {
                        $mod_name = trim($node->getAttribute('title'));
                    } elseif ($node->nodeName === 'h4') {
                        $mod_name = trim($node->textContent);
                    }

                    // Filterung und Bereinigung
                    if (!empty($mod_name) && !in_array($mod_name, $extracted_mods)) {
                        // Manchmal sind Ränge angehängt wie "Vitality (Rank 10)"
                        $mod_name = preg_replace('/\s*\(Rank\s*\d+\)\s*$/i', '', $mod_name);
                        // Manchmal sind es "Primed Vitality" oder "Umbral Vitality"
                        // Diese sollten so bleiben.
                        // Vermeide Duplikate
                        if (!in_array($mod_name, $extracted_mods)) {
                             $extracted_mods[] = $mod_name;
                        }
                    }
                }
            } else {
                 $calculation_results_html .= "<p class='message warning'>Keine typischen Mod-Strukturen gefunden mit dem primären XPath. Versuche alternativen Selektor...</p>";
                 // Alternativer, sehr breiter Selektor, falls der spezifische fehlschlägt
                 // Sucht nach <a> Tags, die auf /items/ verlinken und einen Titel haben
                 $alt_nodes = $xpath->query("//a[contains(@href, '/items/') and @title and string-length(@title)>2]");
                 foreach ($alt_nodes as $node) {
                    $mod_name = trim($node->getAttribute('title'));
                    $mod_name = preg_replace('/\s*\(Rank\s*\d+\)\s*$/i', '', $mod_name);
                    // Zusätzliche Filter, um Nicht-Mods auszuschließen
                    if (!preg_match('/(Overview|Details|Comment|Build|Guide|Warframe|Weapon|Archwing|Necramech)/i', $mod_name) && strlen($mod_name) < 50 && !in_array($mod_name, $extracted_mods)) {
                        if (!in_array($mod_name, $extracted_mods)) {
                             $extracted_mods[] = $mod_name;
                        }
                    }
                 }
            }


            if (empty($extracted_mods)) {
                $calculation_error = "Konnte keine Mod-Namen aus der URL extrahieren. Die Struktur der Seite hat sich möglicherweise geändert oder die URL enthält keine typischen Mod-Informationen.";
                $calculation_results_html .= "<p class='message error'>Extraktion fehlgeschlagen.</p>";
                // Debug: Zeige einen Teil des HTML an, um die Struktur zu prüfen
                // $calculation_results_html .= "<pre style='max-height: 200px; overflow:auto; border:1px solid #ccc; padding:5px;'>" . htmlspecialchars(substr($html_content, 0, 5000)) . "</pre>";

            } else {
                $calculation_results_html .= "<p class='message success'>Folgende potenzielle Mod-Namen extrahiert: " . htmlspecialchars(implode(', ', $extracted_mods)) . "</p>";
                $calculation_results_html .= "<h3>Schritt 3: Preise von warframe.market abfragen...</h3>";

                // 3. Preise von warframe.market abfragen
                foreach ($extracted_mods as $mod_name) {
                    $item_url_name = strtolower(str_replace(' ', '_', str_replace(['(', ')', '&', "'"], '', $mod_name))); // Format für warframe.market API
                    $market_api_url = "https://api.warframe.market/v1/items/{$item_url_name}/orders?platform={$platform}&include=item";

                    $calculation_results_html .= "<p>Frage Preis für '<strong>" . htmlspecialchars($mod_name) . "</strong>' (URL-Name: {$item_url_name}) an...</p>";
                    $market_data = make_curl_request($market_api_url, ['accept: application/json', 'language: de']); // Sprache Deutsch für evtl. Item-Namen

                    if ($market_data['error']) {
                        $mod_prices[$mod_name] = ['error' => "Fehler bei API-Anfrage: " . $market_data['error']];
                        $calculation_results_html .= "<p class='message warning'>&nbsp;&nbsp;&nbsp;Fehler für ".htmlspecialchars($mod_name).": " . htmlspecialchars($market_data['error']) . "</p>";
                        continue;
                    }

                    $orders_data = json_decode($market_data['response'], true);
                    if (isset($orders_data['payload']['orders'])) {
                        $sell_orders = array_filter($orders_data['payload']['orders'], function ($order) {
                            return $order['order_type'] === 'sell' && $order['user']['status'] !== 'offline' && $order['visible'] === true;
                        });

                        if (!empty($sell_orders)) {
                            usort($sell_orders, function ($a, $b) { // Sortiere nach Preis aufsteigend
                                return $a['platinum'] <=> $b['platinum'];
                            });
                            $cheapest_order = $sell_orders[0]; // Nimm den günstigsten Online-Verkäufer
                            $price = $cheapest_order['platinum'];
                            $mod_prices[$mod_name] = ['price_platinum' => $price, 'source' => 'warframe.market', 'seller' => $cheapest_order['user']['ingame_name']];
                            $total_price_plat += $price;
                            $calculation_results_html .= "<p class='message success'>&nbsp;&nbsp;&nbsp;Preis für ".htmlspecialchars($mod_name).": <strong>{$price} <img src='assets/images/platinum.png' alt='Platin' class='currency-icon'></strong> (Verkäufer: {$cheapest_order['user']['ingame_name']})</p>";
                        } else {
                            $mod_prices[$mod_name] = ['error' => 'Keine Online-Verkaufsangebote gefunden.'];
                             $calculation_results_html .= "<p class='message info'>&nbsp;&nbsp;&nbsp;Keine Online-Verkaufsangebote für ".htmlspecialchars($mod_name)." gefunden.</p>";
                        }
                    } else {
                        $mod_prices[$mod_name] = ['error' => 'Ungültige API-Antwort von warframe.market.'];
                        $calculation_results_html .= "<p class='message warning'>&nbsp;&nbsp;&nbsp;Ungültige API-Antwort für ".htmlspecialchars($mod_name).". Eventuell ist der Item-Name '{$item_url_name}' falsch oder das Item nicht handelbar.</p>";
                    }
                     usleep(350000); // Rate Limiting: ca. 3 Anfragen pro Sekunde (333ms Pause)
                }
            }
        }
    }
}

?>

<h1>Build Kosten Rechner (von Overframe.gg)</h1>
<p>Dieses Tool versucht, die Mod-Namen aus einem öffentlichen Overframe.gg Build zu extrahieren und die ungefähren Handelskosten auf <a href="https://warframe.market" target="_blank">warframe.market</a> zu ermitteln.</p>
<p class="message info"><strong>Hinweis:</strong> Die Extraktion der Mod-Namen von Overframe.gg ist experimentell und kann fehlschlagen, wenn sich die Struktur der Seite ändert. Die Preise von warframe.market sind Schätzungen basierend auf aktuellen Angeboten.</p>

<form action="rechner.php" method="POST" class="card">
    <div class="form-group">
        <label for="overframe_url">Overframe.gg Build URL:</label>
        <input type="url" name="overframe_url" id="overframe_url" value="<?php echo htmlspecialchars($overframe_url); ?>" placeholder="https://overframe.gg/build/xxxxxx/..." required>
    </div>
    <div class="form-group">
        <label for="platform">Plattform:</label>
        <select name="platform" id="platform">
            <option value="pc" <?php echo ($platform === 'pc') ? 'selected' : ''; ?>>PC</option>
            <option value="ps4" <?php echo ($platform === 'ps4') ? 'selected' : ''; ?>>PlayStation</option>
            <option value="xbox" <?php echo ($platform === 'xbox') ? 'selected' : ''; ?>>Xbox</option>
            <option value="switch" <?php echo ($platform === 'switch') ? 'selected' : ''; ?>>Nintendo Switch</option>
        </select>
    </div>
    <button type="submit" name="calculate_build" class="button">Kosten berechnen</button>
</form>

<?php if ($calculation_error): ?>
    <div class="message error mt-2">
        <p><?php echo htmlspecialchars($calculation_error); ?></p>
    </div>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$calculation_error): ?>
    <div class="results-container card mt-2">
        <h2>Berechnungsergebnisse für <a href="<?php echo htmlspecialchars($overframe_url); ?>" target="_blank"><?php echo htmlspecialchars($overframe_url); ?></a> (Plattform: <?php echo strtoupper($platform); ?>)</h2>

        <h3>Extrahierte Mods und Preise:</h3>
        <?php if (empty($extracted_mods)): ?>
            <p>Keine Mods konnten extrahiert oder ausgewertet werden.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($extracted_mods as $mod_name_display): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($mod_name_display); ?>:</strong>
                        <?php if (isset($mod_prices[$mod_name_display])): ?>
                            <?php if (isset($mod_prices[$mod_name_display]['price_platinum'])): ?>
                                <?php echo htmlspecialchars($mod_prices[$mod_name_display]['price_platinum']); ?> <img src='assets/images/platinum.png' alt='Platin' class='currency-icon'>
                                (Günstigster Verkäufer: <?php echo htmlspecialchars($mod_prices[$mod_name_display]['seller']); ?>)
                            <?php elseif (isset($mod_prices[$mod_name_display]['error'])): ?>
                                <span class="text-error"><?php echo htmlspecialchars($mod_prices[$mod_name_display]['error']); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-warning">Preis konnte nicht ermittelt werden (nicht in Preisliste nach API-Abfrage).</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <hr>
            <h3>Gesamtkosten (Schätzung):</h3>
            <p><strong><?php echo htmlspecialchars($total_price_plat); ?> <img src='assets/images/platinum.png' alt='Platin' class='currency-icon'> Platin</strong></p>
            <?php if ($total_price_ducats > 0) : // Falls später Ducats implementiert werden ?>
                 <p><strong><?php echo htmlspecialchars($total_price_ducats); ?> Dukaten</strong></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (defined('DEBUG_MODE') && DEBUG_MODE === true && !empty($calculation_results_html)): ?>
    <div class="debug-log card mt-2">
        <h3>Debug Log der Berechnungsschritte:</h3>
        <div><?php echo $calculation_results_html; ?></div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php
// Platin Icon (Basis-Version, sollte durch ein richtiges Bild ersetzt werden)
$platinum_icon_path = __DIR__ . '/assets/images/platinum.png';
if (!file_exists($platinum_icon_path)) {
    if(!is_dir(dirname($platinum_icon_path))) {
        // mkdir(dirname($platinum_icon_path), 0755, true);
    }
    // Erstelle ein Dummy-Bild oder Hinweis
    // @file_put_contents($platinum_icon_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAABFSURBVDhPYxgFo2AUjIJBA2DosaNGUYyCUTAKRsEQAAD//wMAA88BCNOAFLMAAAAASUVORK5CYII=')); // 1x1 transparent pixel
    echo "<p class='message info text-center mt-2' style='font-size:0.8em;'>Hinweis: Platin Icon unter `assets/images/platinum.png` nicht gefunden.</p>";
}
?>
<style>
.currency-icon { width: 1em; height: 1em; vertical-align: -0.15em; margin-left: 0.2em; }
.text-error { color: var(--color-error); }
.text-warning { color: var(--color-warning); }
.results-container ul { list-style: disc; padding-left: 20px; }
.results-container li { margin-bottom: 0.5em; }
.debug-log div {
    background-color: var(--color-background-light);
    padding: 10px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
    font-size: 0.9em;
    white-space: pre-wrap; /* Zeilenumbrüche im Log anzeigen */
    word-break: break-all;
}
.debug-log .message { font-size: 0.95em; padding: 0.5em; }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
