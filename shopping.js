function addToCart(productId, quantity = 1) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateCartDisplay() {
    fetch('get_cart.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartItems = document.getElementById('cart-items');
                const cartCount = document.getElementById('cart-count');
                const cartTotal = document.getElementById('cart-total');
                
                if (cartItems) {
                    cartItems.innerHTML = data.items.map(item => `
                        <div class="cart-item">
                            <img src="${item.image}" alt="${item.name}">
                            <div class="item-details">
                                <h4>${item.name}</h4>
                                <p>₹${item.price}</p>
                                <div class="quantity-controls">
                                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                                    <span>${item.quantity}</span>
                                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                                    <button onclick="removeFromCart(${item.id})">Remove</button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
                
                if (cartCount) {
                    cartCount.textContent = data.items.length;
                }
                
                if (cartTotal) {
                    cartTotal.textContent = data.total.toFixed(2);
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateQuantity(productId, change) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            change: change
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function removeFromCart(productId) {
    fetch('remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function proceedToCheckout() {
    // First check if user is logged in
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                window.location.href = 'login.html';
                return;
            }

            // Get cart items from localStorage
            const cartItems = JSON.parse(localStorage.getItem('cart')) || [];
            
            if (cartItems.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Send cart data to server
            fetch('save_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    items: cartItems,
                    total: document.getElementById('cart-total').textContent
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data); // Debug log
                if (data.success) {
                    // Clear localStorage cart
                    localStorage.removeItem('cart');
                    window.location.href = 'checkout.php';
                } else {
                    alert(data.message || 'Failed to process cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        })
        .catch(error => {
            console.error('Session check error:', error);
            window.location.href = 'login.html';
        });
}

// Initialize cart display when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();

    // Add event listener to checkout button
    const checkoutButton = document.querySelector('.checkout-button');
    if (checkoutButton) {
        console.log('Checkout button found'); // Debug log
        checkoutButton.addEventListener('click', proceedToCheckout);
    } else {
        console.log('Checkout button not found'); // Debug log
    }
}); 