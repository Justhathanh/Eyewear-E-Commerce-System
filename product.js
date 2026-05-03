// product.js
async function loadProducts() {
    const container = document.getElementById("product-list");
    container.innerHTML = "<p>Đang tải sản phẩm...</p>";

    try {
        const response = await fetch("/api/products");

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const result = await response.json();

        if (result.status !== "success" || !Array.isArray(result.data)) {
            throw new Error(result.message || "Lỗi server");
        }

        const products = result.data;
        container.innerHTML = "";

        if (products.length === 0) {
            container.innerHTML = "<p>Không có sản phẩm nào.</p>";
            return;
        }

        products.forEach(p => {
            const productId = p.product_id ?? p.id;
            const price = parseInt(p.price || 0).toLocaleString('vi-VN');
            
            container.innerHTML += `
                <div class="product-card">
                    <img src="${p.image || 'https://via.placeholder.com/300x200?text=No+Image'}" alt="${p.name}">
                    <h3>${p.name}</h3>
                    <p class="price">${price} VND</p>
                    <button onclick="addToCart(${productId})">Thêm vào giỏ</button>
                </div>
            `;
        });

    } catch (error) {
        console.error(error);
        container.innerHTML = `<p style="color:red;">Lỗi load dữ liệu: ${error.message}</p>`;
    }
}

function addToCart(id) {
    alert(`Đã thêm sản phẩm ID ${id} vào giỏ hàng!`);
}

loadProducts();
