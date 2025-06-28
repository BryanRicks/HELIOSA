// Animation GSAP pour l'intro Heliosa
let heliosaAnimationRunning = false;

function runHeliosaIntroAnimation() {
  if (heliosaAnimationRunning) return;
  heliosaAnimationRunning = true;

  const transitionElement = document.getElementById("transition");
  const transitionLogo = document.getElementById("transition-logo");
  const centerContainer = document.getElementById("center-container");
  const logosContainer = document.querySelector(".logos-container");
  const pictos = document.querySelectorAll(".logo-link");
  const pictoLabels = logosContainer.querySelectorAll("span");

  // Nettoyer les styles GSAP sur les pictos
  pictos.forEach((picto) => gsap.set(picto, { clearProps: "all" }));

  // Cache les labels au départ
  pictoLabels.forEach((label) => {
    label.style.opacity = 0;
  });

  // Déplacer le logo dans le container central avant l'animation
  centerContainer.insertBefore(transitionLogo, centerContainer.firstChild);
  transitionLogo.classList.add("center-image");
  gsap.set(transitionLogo, { clearProps: "all" });

  // Supprimer l'écran de transition immédiatement
  if (transitionElement) transitionElement.style.display = "none";

  // Cacher logosContainer mais pas les pictos (car ils seront animés depuis le logo)
  gsap.set(logosContainer, { opacity: 1 });

  // Forcer un reflow pour garantir que le layout est prêt
  void document.body.offsetHeight;

  // Obtenir la position centrale du logo
  const logoBounds = transitionLogo.getBoundingClientRect();

  pictos.forEach((picto) => {
    const pictoBounds = picto.getBoundingClientRect();
    // Calculer la différence entre la position du picto et celle du logo
    const dx =
      logoBounds.left +
      logoBounds.width / 2 -
      (pictoBounds.left + pictoBounds.width / 2);
    const dy =
      logoBounds.top +
      logoBounds.height / 2 -
      (pictoBounds.top + pictoBounds.height / 2);
    // Placer chaque picto à la position du logo
    gsap.set(picto, {
      opacity: 0,
      x: dx,
      y: dy,
      scale: 0.5,
    });
  });

  // Animation GSAP
  const timeline = gsap.timeline({ defaults: { ease: "power2.out" } });

  timeline
    // Zoom out du logo
    .fromTo(transitionLogo, { scale: 2 }, { duration: 2, scale: 0.8 })
    // Animer chaque picto vers sa position finale
    .to(
      pictos,
      {
        opacity: 1,
        x: 0,
        y: 0,
        scale: 1,
        stagger: 0.2,
        duration: 1.2,
      },
      "-=1" // overlap avec la fin de l'animation du logo
    )
    // Afficher les labels sous les pictos après l'animation
    .add(() => {
      pictoLabels.forEach((label) => {
        label.style.transition = "opacity 0.5s";
        label.style.opacity = 1;
      });
      heliosaAnimationRunning = false;
    });
}

function launchHeliosaAnimationWithReflow() {
  if (heliosaAnimationRunning) return;
  setTimeout(runHeliosaIntroAnimation, 100); // petit délai pour garantir le layout mobile
}

// Attendre que toutes les images soient chargées
window.addEventListener("load", launchHeliosaAnimationWithReflow);
window.addEventListener("orientationchange", launchHeliosaAnimationWithReflow);
window.addEventListener("resize", function () {
  if (window.innerWidth < 900) {
    launchHeliosaAnimationWithReflow();
  }
});
