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
$gemini_api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

// Helper function to make cURL requests (generic)
function make_curl_request($url, $headers = [], $post_fields = null) { // $post_fields hinzugefügt
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Wichtig für overframe.gg, da es ggf. weiterleitet
    curl_setopt($ch, CURLOPT_USERAGENT, 'EndoReserveBankRechner/1.0 (https://dojo.chelo.lat; Kontakt admin@chelo.lat)'); // Angepasster User-Agent
    curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Erhöhter Timeout für potenzielle API-Latenz (Gemini)
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($post_fields !== null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
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
    // $overframe_url = trim($_POST['overframe_url'] ?? ''); // Alte Logik entfernt
    $platform_form = trim($_POST['platform'] ?? 'pc');
    if (in_array($platform_form, ['pc', 'ps4', 'xbox', 'switch'])) {
        $platform = $platform_form;
    }

    if (empty($gemini_api_key)) {
        $calculation_error = "Fehler: Gemini API Key nicht in der Konfiguration gefunden. Der Bild-Rechner kann nicht verwendet werden.";
    } elseif (!isset($_FILES['build_screenshot']) || $_FILES['build_screenshot']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "Die hochgeladene Datei überschreitet die upload_max_filesize Direktive in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "Die hochgeladene Datei überschreitet die MAX_FILE_SIZE Direktive, die im HTML-Formular angegeben wurde.",
            UPLOAD_ERR_PARTIAL    => "Die hochgeladene Datei wurde nur teilweise hochgeladen.",
            UPLOAD_ERR_NO_FILE    => "Es wurde keine Datei hochgeladen.",
            UPLOAD_ERR_NO_TMP_DIR => "Es fehlt ein temporärer Ordner.",
            UPLOAD_ERR_CANT_WRITE => "Fehler beim Schreiben der Datei auf die Festplatte.",
            UPLOAD_ERR_EXTENSION  => "Eine PHP-Erweiterung hat den Datei-Upload gestoppt.",
        ];
        $error_code = $_FILES['build_screenshot']['error'] ?? UPLOAD_ERR_NO_FILE;
        $calculation_error = "Fehler beim Datei-Upload: " . ($upload_errors[$error_code] ?? "Unbekannter Fehler.");
    } else {
        $file = $_FILES['build_screenshot'];
        $allowed_mime_types = ['image/jpeg', 'image/png'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_mime_types)) {
            $calculation_error = "Ungültiger Dateityp. Nur JPG und PNG sind erlaubt.";
        } elseif ($file['size'] > $max_file_size) {
            $calculation_error = "Datei ist zu groß (max. 5MB).";
        } else {
            // Bild erfolgreich validiert
            $calculation_results_html .= "<p class='message success'>Screenshot erfolgreich hochgeladen und validiert.</p>";
            $image_data_base64 = base64_encode(file_get_contents($file['tmp_name']));
            $image_mime_type = $file['type'];

            // Nächste Schritte: Gemini API Anfrage, etc.
            // Dies wird im nächsten Schritt implementiert.
            $calculation_results_html .= "<h3>Schritt 1: Bereite Bild für Gemini API vor...</h3>";
            $calculation_results_html .= "<p>MIME-Typ: " . htmlspecialchars($image_mime_type) . "</p>";

            $extracted_mods = [];
            $mod_prices = [];
            $total_price_plat = 0;

            // 2. Gemini API Anfrage
            $calculation_results_html .= "<h3>Schritt 2: Sende Bild an Gemini API zur Texterkennung...</h3>";
            $gemini_api_endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent?key=" . $gemini_api_key;

            $gemini_payload = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => "Extrahiere alle einzelnen Mod-Namen aus diesem Warframe-Build-Screenshot. Liste jeden Mod-Namen in einer neuen Zeile. Konzentriere dich nur auf die Namen der Mods, ignoriere andere UI-Elemente und Statistiken. Wenn möglich, gib die Namen so an, wie sie im Spiel typischerweise heißen (z.B. 'Vitality', 'Serration', 'Primed Continuity')."],
                            [
                                "inline_data" => [
                                    "mime_type" => $image_mime_type,
                                    "data" => $image_data_base64
                                ]
                            ]
                        ]
                    ]
                ],
                // Optional: Generation Config anpassen für bessere Ergebnisse bei Texterkennung
                // "generationConfig" => [
                //    "temperature" => 0.2, // Niedrigere Temperatur für präzisere, weniger kreative Antworten
                //    "maxOutputTokens" => 1024,
                // ]
            ];

            $gemini_headers = [
                'Content-Type: application/json'
            ];

            $gemini_response_data = make_curl_request($gemini_api_endpoint, $gemini_headers, json_encode($gemini_payload));

            if ($gemini_response_data['error']) {
                $calculation_error = "Fehler bei der Anfrage an die Gemini API: " . htmlspecialchars($gemini_response_data['error']);
                if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($gemini_response_data['response'])) {
                    $calculation_error .= "<br>Antwort-Body: <pre>" . htmlspecialchars($gemini_response_data['response']) . "</pre>";
                }
                $calculation_results_html .= "<p class='message error'>Gemini API Anfrage fehlgeschlagen.</p>";
            } else {
                $gemini_json_response = json_decode($gemini_response_data['response'], true);
                // Debugging der Gemini Antwort:
                // $calculation_results_html .= "<pre>Gemini Rohantwort: " . htmlspecialchars(print_r($gemini_json_response, true)) . "</pre>";

                if (isset($gemini_json_response['candidates'][0]['content']['parts'][0]['text'])) {
                    $detected_text = $gemini_json_response['candidates'][0]['content']['parts'][0]['text'];
                    $calculation_results_html .= "<p class='message success'>Text von Gemini API erfolgreich empfangen.</p>";
                    $calculation_results_html .= "<h4>Erkannter Text (Rohformat von Gemini):</h4><pre>" . htmlspecialchars($detected_text) . "</pre>";

                    // Mod-Namen extrahieren und filtern
                    $lines = explode("\n", $detected_text);
                    $potential_mods = [];
                    foreach ($lines as $line) {
                        $trimmed_line = trim($line);
                        // Einfache Filter: nicht leer, nicht zu lang, keine typischen UI-Texte
                        if (!empty($trimmed_line) && strlen($trimmed_line) < 50 && strlen($trimmed_line) > 2 && !preg_match('/(Rank|Level|Stat|Cost|Drain|Capacity|Polarity|Forma|Slot|Build|Stats|Details|Comments)/i', $trimmed_line)) {
                            // Entferne führende Aufzählungszeichen wie "-", "*", "1." etc.
                            $cleaned_mod_name = preg_replace('/^[\s\-\*\d\.]+\s*/', '', $trimmed_line);
                            $cleaned_mod_name = trim($cleaned_mod_name);
                            if (!empty($cleaned_mod_name) && !in_array($cleaned_mod_name, $potential_mods)) {
                                $potential_mods[] = $cleaned_mod_name;
                            }
                        }
                    }
                    $extracted_mods = $potential_mods; // Für die spätere warframe.market Abfrage

                    if (empty($extracted_mods)) {
                        $calculation_error = "Konnte keine plausiblen Mod-Namen aus dem erkannten Text extrahieren.";
                        $calculation_results_html .= "<p class='message warning'>Keine Mod-Namen extrahiert.</p>";
                    } else {
                        $calculation_results_html .= "<p class='message success'>Potenzielle Mod-Namen extrahiert: " . htmlspecialchars(implode(', ', $extracted_mods)) . "</p>";
                        $calculation_results_html .= "<h3>Schritt 3: Preise von warframe.market abfragen...</h3>";

                        // Rudimentäres Deutsch -> Englisch Mapping für warframe.market Slugs
                        $mod_name_map = [
                            'Vitalität' => 'vitality',
                            'Stahlfasern' => 'steel_fiber',
                            'Kontinuität (Primed)' => 'primed_continuity',
                            'Kontinuität' => 'continuity',
                            'Fluss (Primed)' => 'primed_flow',
                            'Fluss' => 'flow',
                            'Intensivieren' => 'intensify',
                            'Dehnen' => 'stretch',
                            'Verkürzen' => 'streamline',
                            'Stromlinie' => 'streamline', // Alias
                            'Gezackte Pfeilspitze' => 'serration', // Serration
                            'Hornissenstich' => 'hornet_strike',
                            'Spaltkammer' => 'split_chamber',
                            'Kritischer Schaden' => 'point_strike', // Point Strike (Krit-Chance) vs Vital Sense (Krit-Schaden)
                            'Vitalgespür' => 'vital_sense',
                            // Diese Liste MUSS umfangreich erweitert werden!
                        ];

                        foreach ($extracted_mods as $mod_name) {
                            $original_mod_name = $mod_name; // Für Anzeige
                            // Versuche deutsches Mapping, sonst nimm an, es ist schon englisch oder ein Slug-ähnlicher Name
                            $item_url_name = $mod_name_map[$mod_name] ?? strtolower(str_replace([' ', '(', ')', "'",":"], ['_', '', '', '',''], $mod_name));
                            // Entferne " (Primed)" etc. für die URL, falls es nicht im Mapping ist, aber behalte es für die Anzeige
                            $item_url_name = str_replace(['_primed', '_umbral', '_apex'], ['_primed', '_umbral_form_aura', '_apex_form_aura'], $item_url_name); // Beispiel für komplexere Slugs, muss verbessert werden


                            $market_api_url = "https://api.warframe.market/v1/items/{$item_url_name}/orders?platform={$platform}&include=item";
                            $calculation_results_html .= "<p>Frage Preis für '<strong>" . htmlspecialchars($original_mod_name) . "</strong>' (API-Slug: {$item_url_name}) an...</p>";

                            $market_data = make_curl_request($market_api_url, ['accept: application/json']);

                            if ($market_data['error']) {
                                $mod_prices[$original_mod_name] = ['error' => "Fehler bei API-Anfrage: " . $market_data['error']];
                                $calculation_results_html .= "<p class='message warning'>&nbsp;&nbsp;&nbsp;Fehler für ".htmlspecialchars($original_mod_name).": " . htmlspecialchars($market_data['error']) . "</p>";
                                continue;
                            }

                            $orders_data = json_decode($market_data['response'], true);
                            if (isset($orders_data['payload']['orders'])) {
                                $sell_orders = array_filter($orders_data['payload']['orders'], function ($order) {
                                    return $order['order_type'] === 'sell' && $order['user']['status'] !== 'offline' && $order['visible'] === true && isset($order['platinum']);
                                });

                                if (!empty($sell_orders)) {
                                    usort($sell_orders, function ($a, $b) { return $a['platinum'] <=> $b['platinum']; });
                                    $cheapest_order = $sell_orders[0];
                                    $price = $cheapest_order['platinum'];
                                    $mod_prices[$original_mod_name] = ['price_platinum' => $price, 'source' => 'warframe.market', 'seller' => $cheapest_order['user']['ingame_name']];
                                    $total_price_plat += $price;
                                    $calculation_results_html .= "<p class='message success'>&nbsp;&nbsp;&nbsp;Preis für ".htmlspecialchars($original_mod_name).": <strong>{$price} <img src='assets/images/platinum.png' alt='Platin' class='currency-icon'></strong> (Verkäufer: {$cheapest_order['user']['ingame_name']})</p>";
                                } else {
                                    $mod_prices[$original_mod_name] = ['error' => 'Keine Online-Verkaufsangebote gefunden.'];
                                    $calculation_results_html .= "<p class='message info'>&nbsp;&nbsp;&nbsp;Keine Online-Verkaufsangebote für ".htmlspecialchars($original_mod_name)." gefunden.</p>";
                                }
                            } else {
                                $mod_prices[$original_mod_name] = ['error' => 'Ungültige API-Antwort von warframe.market.'];
                                $calculation_results_html .= "<p class='message warning'>&nbsp;&nbsp;&nbsp;Ungültige API-Antwort für ".htmlspecialchars($original_mod_name).". Slug '{$item_url_name}' evtl. falsch oder Item nicht handelbar.</p>";
                            }
                            usleep(350000); // Rate Limiting
                        }
                        $calculation_error = null; // Fehler löschen, wenn bis hierhin alles gut ging (oder teilweise)
                    }

                } else {
                    $calculation_error = "Konnte keinen Text aus der Gemini API Antwort extrahieren oder ungültiges Format.";
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        $calculation_error .= "<br>Gemini Antwort: <pre>" . htmlspecialchars(print_r($gemini_json_response, true)) . "</pre>";
                    }
                    $calculation_results_html .= "<p class='message error'>Fehler beim Verarbeiten der Gemini API Antwort.</p>";
                }
            }
        }
    }
}

?>

<h1>Build Kosten Rechner (BETA - via Screenshot-Analyse)</h1>
<p>Lade einen Screenshot deines Warframe-Builds hoch (idealerweise nur den Mod-Bereich). Das System versucht, die Mods mittels KI (Google Gemini) zu erkennen und die ungefähren Handelskosten auf <a href="https://warframe.market" target="_blank">warframe.market</a> zu ermitteln.</p>
<p class="message info"><strong>Wichtiger Hinweis (BETA):</strong> Die Mod-Erkennung aus Bildern ist komplex und nicht immer 100% präzise. Die Genauigkeit hängt stark von der Qualität und dem Ausschnitt des Screenshots ab. Deutsche Mod-Namen werden nach bestem Wissen den englischen warframe.market-Bezeichnungen zugeordnet.</p>
<p class="message warning">Bitte stelle sicher, dass dein `GEMINI_API_KEY` in der `config.php` korrekt eingetragen ist, damit dieses Feature funktioniert.</p>

<form action="rechner.php" method="POST" class="card" enctype="multipart/form-data"> <!-- enctype hinzugefügt -->
    <div class="form-group">
        <label for="build_screenshot">Build-Screenshot hochladen (max. 5MB, JPG/PNG):</label>
        <input type="file" name="build_screenshot" id="build_screenshot" accept="image/jpeg,image/png" required>
    </div>
    <div class="form-group">
        <label for="platform">Plattform für Preisanfrage:</label>
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
