/**
 * Villa Marciana Integrated Script
 */

// 1. GLOBAL FUNCTIONS (Defined outside for HTML access)
function toggleAccountMenu(event) {
  if (event) event.stopPropagation();
  const menu = document.getElementById("account-menu");
  if (menu) {
    menu.classList.toggle("show");
  }
}
window.toggleAccountMenu = toggleAccountMenu;

document.addEventListener("DOMContentLoaded", function () {
  // --- 2. FORM VALIDATION & LOADING ---
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const inputs = form.querySelectorAll("input[required], select[required]");
      let valid = true;

      inputs.forEach((input) => {
        if (!input.value.trim()) {
          valid = false;
          input.style.borderColor = "#ff4757";
          input.style.animation = "shake 0.5s";
          setTimeout(() => {
            input.style.animation = "";
          }, 500);
        } else {
          input.style.borderColor = "rgba(255, 255, 255, 0.1)";
        }
      });

      if (!valid) {
        e.preventDefault();
      } else {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.innerHTML = "Processing...";
          submitBtn.style.opacity = "0.7";
          submitBtn.disabled = true;
        }
      }
    });
  });

  // --- 3. THEME TOGGLE ---
  const toggle = document.getElementById("theme-toggle");
  if (toggle) {
    const savedTheme = localStorage.getItem("theme") || "dark";
    document.body.classList.add(savedTheme);
    toggle.addEventListener("click", () => {
      const isDark = document.body.classList.contains("dark");
      document.body.classList.toggle("dark");
      document.body.classList.toggle("light");
      localStorage.setItem("theme", isDark ? "light" : "dark");
    });
  }

  // --- 4. RIPPLE & CLICK ANIMATIONS ---
  const buttons = document.querySelectorAll(
    ".nav-btn, button, .account-toggle",
  );
  buttons.forEach((button) => {
    button.addEventListener("click", function (e) {
      this.classList.add("clicked");
      setTimeout(() => this.classList.remove("clicked"), 600);

      const ripple = document.createElement("span");
      ripple.className = "ripple";
      this.appendChild(ripple);

      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      ripple.style.width = ripple.style.height = size + "px";
      ripple.style.left = e.clientX - rect.left - size / 2 + "px";
      ripple.style.top = e.clientY - rect.top - size / 2 + "px";

      setTimeout(() => ripple.remove(), 600);
    });
  });

  // --- 5. SCROLL REVEAL ---
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("visible");
      });
    },
    { threshold: 0.1 },
  );

  document
    .querySelectorAll(".container, .feature-card, table, .hero-content")
    .forEach((el) => {
      el.classList.add("scroll-animate");
      observer.observe(el);
    });

  // --- 6. CLOSE ACCOUNT MENU ON OUTSIDE CLICK ---
  document.addEventListener("click", function (event) {
    const menu = document.getElementById("account-menu");
    const dropdown = document.querySelector(".account-dropdown");

    if (menu && menu.classList.contains("show")) {
      if (dropdown && !dropdown.contains(event.target)) {
        menu.classList.remove("show");
      }
    }
  });

  // --- 7. DYNAMIC STYLE INJECTION ---
  const style = document.createElement("style");
  style.textContent = `
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
        .ripple { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.4); transform: scale(0); animation: ripple-animation 0.6s linear; pointer-events: none; }
        @keyframes ripple-animation { to { transform: scale(4); opacity: 0; } }
        .scroll-animate { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .scroll-animate.visible { opacity: 1; transform: translateY(0); }
    `;
  document.head.appendChild(style);
});
