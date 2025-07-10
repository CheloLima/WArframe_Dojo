</div> <!-- Ende .container für site-content -->
    </main> <!-- Ende .site-content -->

    <footer class="site-footer">
        <!-- Theme-Switcher-Container wurde hier entfernt -->
        <div class="container footer-credits">
            <p>Version: 6.9-Echo | &copy; <?php echo date("Y"); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Endo Reserve Bank'; ?>.</p>
            <p>Made with ♥️ in Peru (🇵🇪), Lima for the World & The Origin System!</p>
            <p>Inspiriert von Warframe. Warframe ist ein Warenzeichen von Digital Extremes Ltd.</p>
            <p class="powered-by">Powered by Chelo Lima EIRL | All Rights Reserved</p>
            <!-- Das zweite "Alle Rechte vorbehalten" war hier und wurde entfernt. -->
            <!-- Optional: Link zu Datenschutz / Impressum -->
                <!-- <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/impressum.php">Impressum</a> |
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : '.'; ?>/datenschutz.php">Datenschutz</a> -->
            </p> <!-- Dieser schließende p-Tag war überflüssig und wurde entfernt. -->
        </div>
    </footer>

    <!-- Globale JavaScript-Dateien könnten hier geladen werden -->
    <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/theme-switcher.js?v=<?php echo file_exists(__DIR__ . '/../js/theme-switcher.js') ? filemtime(__DIR__ . '/../js/theme-switcher.js') : time(); ?>"></script>
    <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/tab-animation.js?v=<?php echo file_exists(__DIR__ . '/../js/tab-animation.js') ? filemtime(__DIR__ . '/../js/tab-animation.js') : time(); ?>"></script>

    <!-- tsParticles Integration -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/particle-config.js?v=<?php echo file_exists(__DIR__ . '/../js/particle-config.js') ? filemtime(__DIR__ . '/../js/particle-config.js') : time(); ?>"></script>
    <!-- <script src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.'; ?>/js/main.js?v=<?php echo file_exists(__DIR__ . '/../js/main.js') ? filemtime(__DIR__ . '/../js/main.js') : time(); ?>"></script> -->
</body>
</html>
