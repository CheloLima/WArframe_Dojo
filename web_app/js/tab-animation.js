document.addEventListener('DOMContentLoaded', () => {
    const baseTitle = document.title;
    const animationFrames = [
        "💰 Endo Reserve Bank",
        "💎 Endo Reserve Bank",
        "E💰do Reserve Bank",
        "E💎do Reserve Bank",
        "En💰o Reserve Bank",
        "En💎o Reserve Bank",
        "End💰 Reserve Bank",
        "End💎 Reserve Bank",
        "Endo💰Reserve Bank",
        "Endo💎Reserve Bank",
        "Endo 💰eserve Bank",
        "Endo 💎eserve Bank",
        "Endo R💰serve Bank",
        "Endo R💎serve Bank",
        "Endo Re💰erve Bank",
        "Endo Re💎erve Bank",
        "Endo Res💰rve Bank",
        "Endo Res💎rve Bank",
        "Endo Rese💰ve Bank",
        "Endo Rese💎ve Bank",
        "Endo Reser💰e Bank",
        "Endo Reser💎e Bank",
        "Endo Reserv💰 Bank",
        "Endo Reserv💎 Bank",
        "Endo Reserve💰Bank",
        "Endo Reserve💎Bank",
        "Endo Reserve 💰ank",
        "Endo Reserve 💎ank",
        "Endo Reserve B💰nk",
        "Endo Reserve B💎nk",
        "Endo Reserve Ba💰k",
        "Endo Reserve Ba💎k",
        "Endo Reserve Ban💰",
        "Endo Reserve Ban💎",
    ];

    let frameIndex = 0;
    let animationInterval;
    const animationSpeed = 750; // Millisekunden pro Frame

    function animateTitle() {
        document.title = animationFrames[frameIndex];
        frameIndex = (frameIndex + 1) % animationFrames.length;
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
