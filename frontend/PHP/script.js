const container = document.getElementById('container');
const registerBtn = document.getElementById('register');
const loginBtn = document.getElementById('login');

registerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
});


// Hiện / ẩn mật khẩu - Sign In
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");

if (togglePassword) {
    togglePassword.addEventListener("click", () => {
        const type = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = type;

        togglePassword.classList.toggle("bx-show");
        togglePassword.classList.toggle("bx-hide");
    });
}

// ---------------------------------------------
// Xử lý Popup Modal (Trang chủ)
// ---------------------------------------------
const loginModal = document.getElementById('loginModal');
const openLoginBtn = document.getElementById('openLoginBtn');
const closeLoginBtn = document.getElementById('closeLoginBtn');

// Mở Modal
if (openLoginBtn && loginModal) {
    openLoginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        loginModal.classList.add('show');
    });
}

// Đóng Modal bằng nút [X]
if (closeLoginBtn && loginModal) {
    closeLoginBtn.addEventListener('click', () => {
        loginModal.classList.remove('show');
    });
}

// Click viền nền đen bên ngoài để đóng
if (loginModal) {
    loginModal.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            loginModal.classList.remove('show');
        }
    });
}
