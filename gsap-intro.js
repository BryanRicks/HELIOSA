// Animation GSAP pour l'intro Heliosa
window.addEventListener("DOMContentLoaded", function () {
  const transitionElement = document.getElementById("transition");
  const transitionLogo = document.getElementById("transition-logo");
  const centerContainer = document.getElementById("center-container");
  const logosContainer = document.querySelector(".logos-container");
  const pictos = document.querySelectorAll(".logo-link");
  const svgArrows = document.querySelectorAll(".arrow-svg");

  // Cacher les pictos et flèches au départ
  gsap.set(pictos, { opacity: 0, y: 40 });
  gsap.set(logosContainer, { opacity: 0 });
  svgArrows.forEach((svg) => {
    const line = svg.querySelector("line");
    const length = line.getTotalLength();
    // Cache le marker (triangle) au départ
    const marker = svg.querySelector("marker");
    if (marker) marker.setAttribute("opacity", "0");
    // Cache la ligne au départ
    line.style.strokeDasharray = length;
    line.style.strokeDashoffset = length;
    line.style.opacity = 0;
  });

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
    })
    // Tracé des flèches APRÈS la fin complète
    .add(() => {
      svgArrows.forEach((svg) => {
        const line = svg.querySelector("line");
        const marker = svg.querySelector("marker");
        if (marker) marker.setAttribute("opacity", "0");
        // Affiche la ligne juste avant l'animation
        line.style.opacity = 1;
        gsap.to(line, {
          strokeDashoffset: 0,
          duration: 1.2,
          ease: "power2.out",
          onComplete: () => {
            if (marker) marker.setAttribute("opacity", "1");
          },
        });
      });
    }, "+=0.3"); // Légère pause après les pictos pour plus de fluidité
});
