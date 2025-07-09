</div> <!-- Ende .container für site-content -->
    </main> <!-- Ende .site-content -->

    <footer class="site-footer">
        <div class="theme-switcher-container">
            <label for="theme-select" style="display:none;">Farbschema wählen:</label> <!-- Label für Barrierefreiheit -->
            <div id="theme-color-buttons">
                <button class="theme-button" data-color="#6AFF00" data-rgb="106,255,0" style="background-color:#6AFF00;" aria-label="Grünes Thema"></button>
                <button class="theme-button" data-color="#e602c7" data-rgb="230,2,199" style="background-color:#e602c7;" aria-label="Magenta Thema"></button> <!-- Pink/Rosa zu #e602c7 geändert -->
                <button class="theme-button" data-color="#00FFFF" data-rgb="0,255,255" style="background-color:#00FFFF;" aria-label="Cyan Thema"></button>
                <button class="theme-button" data-color="#9400D3" data-rgb="148,0,211" style="background-color:#9400D3;" aria-label="Violettes Thema"></button>
                <button class="theme-button" data-color="#FF4C4C" data-rgb="255,76,76" style="background-color:#FF4C4C;" aria-label="Rotes Thema"></button>
                <button class="theme-button" data-color="#FFD700" data-rgb="255,215,0" style="background-color:#FFD700;" aria-label="Gold Thema"></button>
            </div>
        </div>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> <?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Endo Reserve Bank'; ?>. Alle Rechte vorbehalten.</p>
            <p>
                Inspiriert von Warframe. Warframe ist ein Warenzeichen von Digital Extremes Ltd.
                <!-- Optional: Link zu Datenschutz / Impressum -->
                <!-- <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/impressum.php">Impressum</a> |
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/datenschutz.php">Datenschutz</a> -->
            </p>
        </div>
    </footer>

    <!-- Globale JavaScript-Dateien könnten hier geladen werden -->
    <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/theme-switcher.js?v=<?php echo file_exists(__DIR__ . '/../js/theme-switcher.js') ? filemtime(__DIR__ . '/../js/theme-switcher.js') : time(); ?>"></script>
    <!-- <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/main.js?v=<?php echo file_exists(__DIR__ . '/../js/main.js') ? filemtime(__DIR__ . '/../js/main.js') : time(); ?>"></script> -->
</body>
</html>
