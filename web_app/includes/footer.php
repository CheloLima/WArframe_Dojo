</div> <!-- Ende .container für site-content -->
    </main> <!-- Ende .site-content -->

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> <?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Echo Sol Dojo'; ?>. Alle Rechte vorbehalten.</p>
            <p>
                Inspiriert von Warframe. Warframe ist ein Warenzeichen von Digital Extremes Ltd.
                <!-- Optional: Link zu Datenschutz / Impressum -->
                <!-- <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/impressum.php">Impressum</a> |
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/datenschutz.php">Datenschutz</a> -->
            </p>
        </div>
    </footer>

    <!-- Globale JavaScript-Dateien könnten hier geladen werden -->
    <!-- <script src="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/js/main.js?v=<?php echo filemtime(__DIR__ . '/../js/main.js'); ?>"></script> -->
</body>
</html>
