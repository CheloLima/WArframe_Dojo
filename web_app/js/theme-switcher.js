document.addEventListener('DOMContentLoaded', () => {
    const themeButtonsContainer = document.getElementById('theme-color-buttons');
    if (!themeButtonsContainer) {
        console.warn('Theme switcher button container not found.');
        return;
    }
    const themeButtons = themeButtonsContainer.querySelectorAll('.theme-button');
    const root = document.documentElement;

    const defaultTheme = {
        color: '#6AFF00', // Standard Giftgrün
        rgb: '106,255,0',
        defaultButtonTextColor: 'var(--button-text-dark)' // Standard für Grün ist dunkler Text
    };

    // Funktion zur Berechnung der relativen Luminanz (Helligkeit) einer Hex-Farbe
    // Gibt einen Wert zwischen 0 (schwarz) und 255 (weiß) zurück
    function getLuminance(hexColor) {
        const hex = hexColor.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        // Formel für relative Luminanz (vereinfacht, YCbCr)
        return 0.299 * r + 0.587 * g + 0.114 * b;
    }

    function applyTheme(hexColor, rgbColorString) {
        if (!hexColor || !rgbColorString) {
            console.error('Invalid theme colors provided:', hexColor, rgbColorString);
            return;
        }
        root.style.setProperty('--color-primary-accent', hexColor);
        root.style.setProperty('--color-primary-accent-rgb', rgbColorString);

        // Button-Textfarbe basierend auf der Helligkeit der Akzentfarbe bestimmen
        const luminance = getLuminance(hexColor);
        // Schwellenwert für Helligkeit (experimentell, kann angepasst werden)
        // Farben mit Luminanz > 128-150 gelten als "hell"
        if (luminance > 140) { // Wenn Akzentfarbe hell ist
            root.style.setProperty('--button-current-text-color', 'var(--button-text-dark)');
        } else { // Wenn Akzentfarbe dunkel ist
            root.style.setProperty('--button-current-text-color', 'var(--button-text-light)');
        }

        // Optional: Anpassung von Border-Glow und Glow-Shadow basierend auf der neuen Primärfarbe.
        // Die aktuellen CSS-Variablen (--color-border-glow, --glow-shadow-primary)
        // nutzen bereits --color-primary-accent-rgb, daher sollten sie sich automatisch anpassen.
        // Falls spezifischere Anpassungen pro Theme nötig wären, könnten sie hier erfolgen.
        // z.B. root.style.setProperty('--color-border-glow', `rgba(${rgbColorString}, 0.3)`);
        // root.style.setProperty('--glow-shadow-primary', `0 0 5px rgba(${rgbColorString}, 0.5), 0 0 8px rgba(${rgbColorString}, 0.3)`);

        // Aktiven Button markieren
        themeButtons.forEach(btn => {
            if (btn.dataset.color === hexColor) {
                btn.classList.add('active-theme');
            } else {
                btn.classList.remove('active-theme');
            }
        });
    }

    function saveThemePreference(hexColor, rgbColorString) {
        localStorage.setItem('userPreferredThemeHex', hexColor);
        localStorage.setItem('userPreferredThemeRgb', rgbColorString);
    }

    function loadThemePreference() {
        const preferredHex = localStorage.getItem('userPreferredThemeHex');
        const preferredRgb = localStorage.getItem('userPreferredThemeRgb');

        if (preferredHex && preferredRgb) {
            applyTheme(preferredHex, preferredRgb);
        } else {
            // Standardtheme anwenden, falls nichts gespeichert ist
            applyTheme(defaultTheme.color, defaultTheme.rgb);
        }
    }

    themeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const hexColor = button.dataset.color;
            const rgbColorString = button.dataset.rgb;
            if (hexColor && rgbColorString) {
                applyTheme(hexColor, rgbColorString);
                saveThemePreference(hexColor, rgbColorString);
            } else {
                console.error('Button is missing color data:', button);
            }
        });
    });

    // Gespeichertes Theme beim Laden der Seite anwenden
    loadThemePreference();
});
