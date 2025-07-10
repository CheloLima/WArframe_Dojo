document.addEventListener('DOMContentLoaded', () => {
    // Warte, bis tsParticles geladen ist (obwohl es synchron geladen wird, ist dies eine gute Praxis)
    if (typeof tsParticles === 'undefined') {
        console.error('tsParticles not loaded.');
        return;
    }

    // Funktion, um die aktuelle Akzentfarbe aus den CSS-Variablen zu holen
    function getCurrentAccentColor() {
        const rootStyle = getComputedStyle(document.documentElement);
        // Hole die RGB-Version, da viele Partikel-Farben so besser konfiguriert werden können
        const rgbString = rootStyle.getPropertyValue('--color-primary-accent-rgb').trim();
        if (rgbString) {
            return `rgb(${rgbString})`; // Format: "rgb(r,g,b)"
        }
        return '#6AFF00'; // Fallback auf Standardgrün
    }

    // Funktion, um die Partikelkonfiguration zu erstellen
    function getParticleConfig(accentColor) {
        return {
            fpsLimit: 60, // Performance: FPS limitieren
            particles: {
                number: {
                    value: 50, // Wenige Partikel für bessere Performance
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: accentColor // Partikelfarbe = Akzentfarbe
                },
                shape: {
                    type: "circle", // Einfache Form
                },
                opacity: {
                    value: 0.3, // Dezente Deckkraft
                    random: true,
                    anim: {
                        enable: true,
                        speed: 0.2,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 2, // Kleine Partikel
                    random: true,
                    anim: {
                        enable: false, // Keine Größenanimation für Performance
                        speed: 2,
                        size_min: 0.5,
                        sync: false
                    }
                },
                links: {
                    enable: true,
                    distance: 120, // Distanz für Linien
                    color: accentColor, // Linienfarbe = Akzentfarbe
                    opacity: 0.2, // Dezente Linien
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 0.8, // Langsame Bewegung
                    direction: "none",
                    random: true,
                    straight: false,
                    out_mode: "out", // Partikel verlassen den Bildschirmrand
                    bounce: false,
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "repulse" // Partikel weichen Maus aus
                    },
                    onclick: {
                        enable: false, // Keine Aktion bei Klick für Performance
                        mode: "push"
                    },
                    resize: true
                },
                modes: {
                    repulse: {
                        distance: 80, // Distanz für Repulse-Effekt
                        duration: 0.4
                    }
                }
            },
            retina_detect: true,
            // Hintergrund der Partikel-Canvas (optional, wenn body schon Hintergrund hat)
            // background: {
            //     color: "transparent" // Oder var(--color-background-dark)
            // }
        };
    }

    // Partikel initial laden
    let currentParticlesInstance = null;

    async function loadParticlesWithCurrentTheme() {
        const accentColor = getCurrentAccentColor();
        const particleConfig = getParticleConfig(accentColor);

        if (currentParticlesInstance) {
            currentParticlesInstance.destroy(); // Zerstöre alte Instanz
        }
        // Das `tsParticles.load` gibt eine Promise zurück, die die Container-Instanz enthält
        currentParticlesInstance = await tsParticles.load("tsparticles", particleConfig);
    }

    // Initiales Laden
    loadParticlesWithCurrentTheme();

    // Beobachte Änderungen an den CSS-Variablen (speziell --color-primary-accent-rgb)
    // Dies ist etwas komplexer, da es keinen direkten Event für CSS-Variablen-Änderungen gibt.
    // Eine einfache Methode ist, auf den Klick der Theme-Buttons zu hören oder
    // die theme-switcher.js zu erweitern, um ein Custom Event auszulösen.
    // Für jetzt: Neuladen der Partikel, wenn ein Theme-Button geklickt wird.
    const themeButtonsContainer = document.getElementById('theme-color-buttons');
    if (themeButtonsContainer) {
        themeButtonsContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('theme-button')) {
                // Kurze Verzögerung, damit die CSS-Variablen zuerst aktualisiert werden
                setTimeout(loadParticlesWithCurrentTheme, 50);
            }
        });
    }

    // Füge ein div-Element für tsParticles hinzu, falls es nicht existiert
    let particlesDiv = document.getElementById('tsparticles');
    if (!particlesDiv) {
        particlesDiv = document.createElement('div');
        particlesDiv.id = 'tsparticles';
        particlesDiv.style.position = 'fixed';
        particlesDiv.style.width = '100%';
        particlesDiv.style.height = '100%';
        particlesDiv.style.top = '0';
        particlesDiv.style.left = '0';
        particlesDiv.style.zIndex = '-1'; // Hinter den Inhalt legen
        document.body.prepend(particlesDiv); // Füge es am Anfang des body ein
    }
});
