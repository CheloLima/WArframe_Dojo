document.addEventListener('DOMContentLoaded', () => {
    const baseTitle = document.title; // Speichert den ursprünglichen Titel
    const siteName = "Endo Reserve Bank";
    const symbols = ["💰", "💎"];
    let currentSymbolIndex = 0;
    let currentPosition = -1; // Startet vor dem String
    const animationFrames = []; // Wird dynamisch generiert

    // Generiere Frames für die Animation
    function generateFrames() {
        animationFrames.length = 0; // Array leeren
        const symbol = symbols[currentSymbolIndex];
        const nameWithSymbol = [];

        // Welle vorwärts
        for (let i = 0; i <= siteName.length; i++) {
            let frame = "";
            for (let j = 0; j < siteName.length; j++) {
                if (j === i) {
                    frame += symbol;
                }
                frame += siteName[j];
            }
            if (i === siteName.length) { // Symbol am Ende
                frame = siteName + symbol;
            }
            animationFrames.push(frame);
        }
        // Welle rückwärts (optional, oder einfach wieder von vorne)
        // Für Einfachheit lassen wir es erstmal nur vorwärts laufen und wechseln dann das Symbol
    }

    generateFrames(); // Initiale Frames generieren

    let frameIndex = 0;
    let animationInterval;
    const animationSpeed = 500; // Millisekunden pro Frame (von 750 auf 500 reduziert)

    function animateTitle() {
        document.title = animationFrames[frameIndex];
        frameIndex++;
        if (frameIndex >= animationFrames.length) {
            frameIndex = 0;
            currentSymbolIndex = (currentSymbolIndex + 1) % symbols.length; // Nächstes Symbol
            generateFrames(); // Frames für neues Symbol generieren
        }
    }

    function startAnimation() {
        if (!animationInterval) {
            // Stelle sicher, dass der erste Frame sofort angezeigt wird, bevor das Intervall startet
            animateTitle();
            animationInterval = setInterval(animateTitle, animationSpeed);
        }
    }

    function stopAnimation() {
        clearInterval(animationInterval);
        animationInterval = null;
        document.title = baseTitle; // Setze auf den ursprünglichen Titel zurück
    }

    // Starte Animation, wenn Seite sichtbar wird
    if (document.visibilityState === 'visible') {
        startAnimation();
    }

    // Pausiere/starte Animation basierend auf Tab-Sichtbarkeit
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            startAnimation();
        } else {
            stopAnimation();
        }
    });
});
