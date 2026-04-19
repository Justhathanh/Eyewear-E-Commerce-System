/* =============================
   NAVBAR — scroll shadow
   ============================= */
const navbar = document.getElementById("navbar");
window.addEventListener("scroll", () => {
  navbar.classList.toggle("scrolled", window.scrollY > 20);
});

/* =============================
   SMOOTH SCROLL HELPER
   ============================= */
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: "smooth" });
}

/* =============================
   STAT COUNTER ANIMATION
   ============================= */
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const duration = 1500;
  const step = 16;
  const steps = duration / step;
  const increment = target / steps;
  let current = 0;

  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    el.textContent =
      target >= 1000
        ? Math.round(current).toLocaleString("vi-VN") + "+"
        : Math.round(current) + "+";
  }, step);
}

const statsObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const counters = entry.target.querySelectorAll(
          ".stat-num[data-target]",
        );
        counters.forEach(animateCounter);
        statsObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.4 },
);

const statsSection = document.querySelector(".stats");
if (statsSection) statsObserver.observe(statsSection);

/* =============================
   CART
   ============================= */
let cartCount = 0;
const cartCountEl = document.getElementById("cartCount");

function addToCart(btn) {
  cartCount++;
  cartCountEl.textContent = cartCount;
  showToast("Đã thêm vào giỏ hàng!");

  btn.textContent = "✓ Đã thêm";
  btn.style.background = "var(--ink)";
  btn.style.color = "var(--cream)";
  btn.style.borderColor = "var(--ink)";

  setTimeout(() => {
    const isPre = btn.closest(".prod-card").dataset.type === "preorder";
    btn.textContent = isPre ? "Đặt trước" : "Thêm vào giỏ";
    btn.style.background = "";
    btn.style.color = "";
    btn.style.borderColor = "";
  }, 2000);
}

/* =============================
   TOAST
   ============================= */
let toastTimer;
function showToast(msg) {
  const toast = document.getElementById("toast");
  toast.textContent = msg;
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2500);
}

/* =============================
   PRODUCT FILTER
   ============================= */
const filterBtns = document.querySelectorAll(".filter-btn");
const prodCards = document.querySelectorAll(".prod-card");

filterBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    filterBtns.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    const filter = btn.dataset.filter;
    prodCards.forEach((card) => {
      const match = filter === "all" || card.dataset.type === filter;
      card.classList.toggle("hidden", !match);
    });
  });
});

/* =============================
   CATEGORY CARD CLICK
   ============================= */
document.querySelectorAll(".cat-card").forEach((card) => {
  card.addEventListener("click", () => {
    const cat = card.dataset.category;
    const labels = {
      regular: "Kính mắt thường",
      sunglasses: "Kính râm",
      prescription: "Kính theo đơn",
    };
    scrollToSection("products");
    if (cat === "prescription") {
      setTimeout(() => scrollToSection("rx-section"), 400);
    }
  });
});

/* =============================
   FADE-IN ON SCROLL
   ============================= */
const fadeEls = document.querySelectorAll(
  ".svc-card, .prod-card, .cat-card, .rx-step, .stat-item",
);

const fadeObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "translateY(0)";
        fadeObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.1 },
);

fadeEls.forEach((el, i) => {
  el.style.opacity = "0";
  el.style.transform = "translateY(16px)";
  el.style.transition = `opacity 0.5s ease ${(i % 4) * 0.1}s, transform 0.5s ease ${(i % 4) * 0.1}s`;
  fadeObserver.observe(el);
});
