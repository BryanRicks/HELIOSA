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
    line.style.strokeDasharray = length;
    line.style.strokeDashoffset = length;
  });

  // Calcul de déplacement fluide + scale
  const logoRect = transitionLogo.getBoundingClientRect();
  const targetRect = centerContainer.getBoundingClientRect();

  const deltaX =
    targetRect.left +
    centerContainer.clientWidth / 2 -
    (logoRect.left + logoRect.width / 2);
  const deltaY =
    targetRect.top +
    centerContainer.clientHeight / 2 -
    (logoRect.top + logoRect.height / 2);

  const scaleFactor = 0.5; // À adapter selon la taille finale du logo

  const timeline = gsap.timeline({
    defaults: { ease: "power2.inOut" },
  });

  timeline
    // Logo zoome vers sa position finale (avec déplacement progressif)
    .to(transitionLogo, {
      duration: 2,
      scale: scaleFactor,
      x: deltaX,
      y: deltaY,
    })
    // Fade out progressif du fond de transition
    .to(
      transitionElement,
      {
        duration: 1,
        opacity: 0,
        pointerEvents: "none",
        onComplete: () => {
          // Remise en place dans le DOM
          transitionElement.style.display = "none";
          transitionLogo.style.transform = "";
          transitionLogo.classList.add("center-image");
          centerContainer.insertBefore(
            transitionLogo,
            centerContainer.firstChild
          );
        },
      },
      "-=0.8"
    )
    // Affichage du bloc de pictos
    .to(logosContainer, { opacity: 1 }, "-=0.3")
    .to(
      pictos,
      {
        duration: 1,
        opacity: 1,
        y: 0,
        stagger: 0.2,
      },
      "-=0.5"
    )
    // Tracé progressif des flèches
    .add(() => {
      svgArrows.forEach((svg) => {
        const line = svg.querySelector("line");
        gsap.to(line, {
          strokeDashoffset: 0,
          duration: 1.2,
          ease: "power2.out",
        });
      });
    }, "-=1");
});
