// Animation GSAP pour l'intro Heliosa
window.addEventListener("DOMContentLoaded", function () {
  const transitionElement = document.getElementById("transition");
  const transitionLogo = document.getElementById("transition-logo");
  const centerContainer = document.getElementById("center-container");
  const logosContainer = document.querySelector(".logos-container");
  const pictos = document.querySelectorAll(".logo-link");

  // Cacher les pictos au départ
  gsap.set(pictos, { opacity: 0, y: 40 });
  gsap.set(logosContainer, { opacity: 0 });

  // Déplacer le logo dans le container central avant l'animation
  centerContainer.insertBefore(transitionLogo, centerContainer.firstChild);
  transitionLogo.classList.add("center-image");
  gsap.set(transitionLogo, { clearProps: "all" });

  // Supprimer l'écran de transition immédiatement
  transitionElement.style.display = "none";

  // Animation GSAP
  const timeline = gsap.timeline({ defaults: { ease: "power2.inOut" } });

  timeline
    // Zoom out du logo
    .fromTo(transitionLogo, { scale: 2 }, { duration: 2, scale: 0.8 })
    // Affichage des pictos
    .to(logosContainer, { opacity: 1 }, "-=0.3")
    .to(pictos, {
      duration: 1,
      opacity: 1,
      y: 0,
      stagger: 0.2,
    });
});
