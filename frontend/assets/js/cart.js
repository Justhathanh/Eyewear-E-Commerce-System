function loadCart() {
  const cart = JSON.parse(localStorage.getItem("cart")) || [];

  const container = document.getElementById("cart");
  const totalEl = document.getElementById("total");
  const emptyEl = document.getElementById("empty");

  if (cart.length === 0) {
    container.innerHTML = "";
    totalEl.innerText = "Tổng tiền: 0";
    emptyEl.innerText = "🛒 Giỏ hàng trống";
    return;
  }

  emptyEl.innerText = "";

  let total = 0;

  container.innerHTML = cart.map(item => {
    total += item.price * item.quantity;

    return `
      <div style="border:1px solid #ccc; padding:10px; margin:10px;">
        <p><b>${item.name}</b></p>
        <p>Giá: ${item.price}</p>

        <button onclick="decrease(${item.id})">-</button>
        <span>${item.quantity}</span>
        <button onclick="increase(${item.id})">+</button>

        <button onclick="removeItem(${item.id})" style="color:red;">
          Xoá
        </button>
      </div>
    `;
  }).join("");

  totalEl.innerText = "Tổng tiền: " + total;
}

function saveCart(cart) {
  localStorage.setItem("cart", JSON.stringify(cart));
  loadCart();
}

function increase(id) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  cart = cart.map(item => {
    if (item.id === id) item.quantity++;
    return item;
  });

  saveCart(cart);
}

function decrease(id) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  cart = cart.map(item => {
    if (item.id === id && item.quantity > 1) {
      item.quantity--;
    }
    return item;
  });

  saveCart(cart);
}

function removeItem(id) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  cart = cart.filter(item => item.id !== id);

  saveCart(cart);
}

async function checkout() {
  const cart = JSON.parse(localStorage.getItem("cart")) || [];

  if (cart.length === 0) {
    alert("Giỏ hàng rỗng!");
    return;
  }

  const payload = {
    items: cart.map(item => ({
      productId: item.id,
      quantity: item.quantity
    }))
  };

  try {
    const res = await fetch("/api/orders", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (res.ok) {
      alert("Đặt hàng thành công!");
      localStorage.removeItem("cart");
      loadCart();
    } else {
      alert(data.message || "Lỗi đặt hàng");
    }

  } catch (err) {
    console.error(err);
    alert("Không kết nối được server!");
  }
}

loadCart();