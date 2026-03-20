(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const particles = document.getElementById("particles");
    if (!particles || particles.dataset.ready === "1") {
      return;
    }

    for (let i = 0; i < 50; i += 1) {
      const particle = document.createElement("div");
      particle.className = "particle";
      particle.style.left = `${Math.random() * 100}%`;
      particle.style.top = `${Math.random() * 100}%`;
      particle.style.animationDelay = `${Math.random() * 6}s`;
      particles.appendChild(particle);
    }

    particles.dataset.ready = "1";
  });
})();
