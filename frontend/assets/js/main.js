// frontend/assets/js/main.js

/* =============================
   NAVBAR — scroll shadow
   ============================= */
const navbar = document.getElementById('navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 20);
  });
}

/* =============================
   SMOOTH SCROLL
   ============================= */
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

/* =============================
   STAT COUNTER
   ============================= */
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const steps  = 1500 / 16;
  const inc    = target / steps;
  let current  = 0;
  const timer  = setInterval(() => {
    current += inc;
    if (current >= target) { current = target; clearInterval(timer); }
    el.textContent = target >= 1000
      ? Math.round(current).toLocaleString('vi-VN') + '+'
      : Math.round(current) + '+';
  }, 16);
}

const statsSection = document.querySelector('.stats');
if (statsSection) {
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.querySelectorAll('.stat-num[data-target]').forEach(animateCounter);
        statsObserver.unobserve(e.target);
      }
    });
  }, { threshold: 0.4 });
  statsObserver.observe(statsSection);
}

/* =============================
   TOAST
   ============================= */
let toastTimer;
function showToast(msg) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
}

/* =============================
   PRODUCT FILTER (home / product page)
   ============================= */
document.addEventListener('click', e => {
  if (!e.target.classList.contains('filter-btn')) return;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  e.target.classList.add('active');
  const filter = e.target.dataset.filter;
  document.querySelectorAll('.prod-card').forEach(card => {
    card.classList.toggle('hidden', filter !== 'all' && card.dataset.type !== filter);
  });
});

/* =============================
   LOGIN MODAL
   ============================= */
const loginModal    = document.getElementById('loginModal');
const openLoginBtn  = document.getElementById('openLoginBtn');
const closeLoginBtn = document.getElementById('closeLoginBtn');

if (openLoginBtn && loginModal) {
  openLoginBtn.addEventListener('click', () => loginModal.classList.add('show'));
}
if (closeLoginBtn && loginModal) {
  closeLoginBtn.addEventListener('click', () => loginModal.classList.remove('show'));
}
if (loginModal) {
  loginModal.addEventListener('click', e => {
    if (e.target === loginModal) loginModal.classList.remove('show');
  });
}

function switchModal(panel) {
  const signin = document.getElementById('formSignin');
  const signup = document.getElementById('formSignup');
  if (signin) signin.style.display = panel === 'signin' ? '' : 'none';
  if (signup) signup.style.display = panel === 'signup' ? '' : 'none';
}

// Auto-open modal nếu có lỗi/success trong URL
if (loginModal) {
  const s = location.search;
  if (s.includes('login_error') || s.includes('signup_error') || s.includes('signup_success')) {
    loginModal.classList.add('show');
    if (s.includes('signup_error') || s.includes('signup_success')) switchModal('signup');
  }
}

/* =============================
   addToCart placeholder
   (bị override bởi cart.js nếu được load)
   ============================= */
function addToCart(btn, productId, name, price) {
  // Fallback: mở modal đăng nhập nếu cart.js chưa load
  showToast('Đang xử lý…');
}

/* =============================
   FADE-IN ON SCROLL
   ============================= */
function initFadeObserver() {
  const els = document.querySelectorAll('.svc-card, .prod-card, .cat-card, .rx-step, .stat-item');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity    = '1';
        e.target.style.transform  = 'translateY(0)';
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });

  els.forEach((el, i) => {
    el.style.opacity   = '0';
    el.style.transform = 'translateY(16px)';
    el.style.transition = `opacity 0.5s ease ${(i % 4) * 0.1}s, transform 0.5s ease ${(i % 4) * 0.1}s`;
    obs.observe(el);
  });
}

/* =============================
   INIT
   ============================= */
document.addEventListener('DOMContentLoaded', () => {
  initFadeObserver();
});