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
  new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.querySelectorAll('.stat-num[data-target]').forEach(animateCounter);
        statsObserver.unobserve(e.target);
      }
    });
  }, { threshold: 0.4 }).observe(statsSection);
}
// alias để IntersectionObserver có thể unobserve
const statsObserver = new IntersectionObserver(() => {});

/* =============================
   CART (localStorage)
   ============================= */
function getCart() {
  return JSON.parse(localStorage.getItem('cart') || '[]');
}

function saveCart(cart) {
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
}

function updateCartCount() {
  const count = getCart().reduce((s, i) => s + i.qty, 0);
  document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
}

function addToCart(btn, id, name, price) {
  const cart = getCart();
  const idx  = cart.findIndex(i => i.id === id);
  if (idx > -1) cart[idx].qty++;
  else cart.push({ id, name, price: parseFloat(price), qty: 1 });
  saveCart(cart);
  showToast('Đã thêm vào giỏ hàng!');

  btn.textContent = '✓ Đã thêm';
  btn.style.background = 'var(--ink)';
  btn.style.color = 'var(--cream)';
  setTimeout(() => {
    btn.textContent = btn.closest('.prod-card')?.dataset.type === 'preorder' ? 'Đặt trước' : 'Thêm vào giỏ';
    btn.style.background = '';
    btn.style.color = '';
  }, 2000);
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
   PRODUCT FILTER
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
const loginModal   = document.getElementById('loginModal');
const openLoginBtn = document.getElementById('openLoginBtn');
const closeLoginBtn= document.getElementById('closeLoginBtn');

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
  document.getElementById('formSignin').style.display = panel === 'signin' ? '' : 'none';
  document.getElementById('formSignup').style.display = panel === 'signup' ? '' : 'none';
}

// Auto-open modal nếu có lỗi login/signup trong URL
if (loginModal && (location.search.includes('login_error') || location.search.includes('signup_error') || location.search.includes('signup_success'))) {
  loginModal.classList.add('show');
  if (location.search.includes('signup_error') || location.search.includes('signup_success')) {
    switchModal('signup');
  }
}

/* =============================
   FADE-IN ON SCROLL
   ============================= */
function initFadeObserver() {
  const els = document.querySelectorAll('.svc-card, .prod-card, .cat-card, .rx-step, .stat-item');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity = '1';
        e.target.style.transform = 'translateY(0)';
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });

  els.forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(16px)';
    el.style.transition = `opacity 0.5s ease ${(i % 4) * 0.1}s, transform 0.5s ease ${(i % 4) * 0.1}s`;
    obs.observe(el);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  initFadeObserver();
});