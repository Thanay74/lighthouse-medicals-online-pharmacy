<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Function to check session and return JSON response if needed
function checkSessionAndRespond($isAjax = false) {
    $sessionValid = isset($_SESSION['user_id']) && isset($_SESSION['current_cart_id']);
    
    if (!$sessionValid) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Session expired or user not logged in',
                'redirect' => 'login.html'
            ]);
            exit;
        } else {
            header('Location: login.html');
            exit;
        }
    }
    return true;
}

// Check session status
checkSessionAndRespond();

try {
    // Database connection
    require_once 'db_connect.php';
    
    // Verify user exists in database
    $verify_user = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($verify_user);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found in database
        session_destroy();
        header('Location: login.html?error=invalid_user');
        exit;
    }

    // Get user information
    $user_query = "SELECT name, email, phone, address FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_info = $user_result->fetch_assoc();

    // Get cart and items information
    $cart_query = "SELECT c.id, c.total_amount, c.created_at,
                          ci.product_name, ci.quantity, ci.price, ci.image_url
                   FROM carts c
                   JOIN cart_items ci ON c.id = ci.cart_id
                   WHERE c.id = ? AND c.user_id = ?";
    
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("ii", $_SESSION['current_cart_id'], $_SESSION['user_id']);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    $cart_items = [];
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    // Get cart's delivery address if it exists
    $delivery_address_query = "SELECT delivery_address FROM carts WHERE id = ?";
    $stmt = $conn->prepare($delivery_address_query);
    $stmt->bind_param("i", $_SESSION['current_cart_id']);
    $stmt->execute();
    $delivery_result = $stmt->get_result();
    $delivery_info = $delivery_result->fetch_assoc();

    // Use cart's delivery address if set, otherwise use user's default address
    $current_address = $delivery_info['delivery_address'] ?? $user_info['address'];
    $has_address = !empty($current_address);

} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Medicompanion</title>
    <link href="https://fonts.cdnfonts.com/css/graphik-trial" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            font-family: 'Graphik Trial', sans-serif;
            background-color:white;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkout-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #252b61;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .prescription-upload {
            border: 2px dashed #1F6366;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 20px;
            
        }

        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }

        .submit-button {
            background: #2B5329;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .submit-button:hover {
            background: #3F6782;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        .required {
            color: red;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fbf2fc;
            border-radius: 10px;
        }

        .section-title {
            color: #252b61;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .user-info, .delivery-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #1F6366;
        }

        .cart-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .cart-items th, .cart-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .cart-items th {
            background-color: #007bff;
            color: white;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .total-section {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .proceed-payment {
            background-color: #2B5329;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .proceed-payment:hover {
            background-color: #3F6782;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #1F6366;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .edit-address-btn, .save-btn, .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .edit-address-btn {
            background-color: #1F6366;
            color: white;
        }

        .save-btn {
            background-color: #2B5329;
            color: white;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
            margin-right: 10px;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .new-address-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .current-address {
            margin-bottom: 20px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #orderStatusModal .modal-content {
            text-align: center;
            padding: 30px;
        }

        #orderStatus {
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .status-message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-message.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-message.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-btn {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .payment-btn:hover {
            transform: scale(1.05);
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* For tablets (max-width: 768px) */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 20px;
            }

            .checkout-sections {
                grid-template-columns: 1fr; /* Stack sections vertically */
                gap: 20px;
            }

            .form-section, .order-summary {
                padding: 15px;
            }

            .section-title {
                font-size: 20px;
            }

            .cart-items th, .cart-items td {
                font-size: 14px;
            }

            .submit-button, .proceed-payment {
                font-size: 16px;
                padding: 10px 20px;
            }
        }

        /* For mobile devices (max-width: 375px) */
        @media (max-width: 375px) {
            .body {
                font-size: 14px;
                width:fit-content;
            }
            .checkout-container {
               position: relative;
                width:fit-content;
                align-content: center;
            }
            .checkout-sections{
                font-weight: small;
         
            }
        .section{
                 width: 250px;         
                }
            .section-title {
                font-size: 18px;
            }
            .form-section {
              width:250px;
             }
            .form-group label {
                font-size: 14px;
            }

            input[type="text"], input[type="email"], input[type="tel"], textarea {
                font-size: 14px;
                padding: 8px;
            }

            .section .cart-items {
                padding:0px;
                width:0px
             
            }
            .cart-items th, .cart-items td {
                font-size: 8px;
                padding:7px;
            }
            .section .cart-items img{
              
                width:40px
             
            }

            .submit-button, .proceed-payment {
                font-size: 14px;
                padding: 8px 15px;
            }

            .prescription-upload {
                padding: 15px;
            }
        }
        @media (max-width: 425px) {
            .body {
                font-size: 14px;
                width:fit-content;
            }
            .checkout-container {
         
               position: relative;
               
            
                width:fit-content;
                align-content: center;
            }
            .checkout-sections{
                font-weight: small;
         
            }
        .section{
                 width: 300px;         
                }
            .section-title {
                font-size: 18px;
            }
            .form-section {
              width:250px;
             }
            .form-group label {
                font-size: 14px;
            }

            input[type="text"], input[type="email"], input[type="tel"], textarea {
                font-size: 14px;
                padding: 8px;
            }

            .section .cart-items {
                padding:0px;
                width:0px
             
            }
            .cart-items th, .cart-items td {
                font-size: 12px;
                padding:7px;
            }
            .section .cart-items img{
              
                width:40px
             
            }

            .submit-button, .proceed-payment {
                font-size: 14px;
                padding: 8px 15px;
            }

            .prescription-upload {
                padding: 15px;
            }
        }

        @media (max-width: 600px) {
            .body {
                font-size: 14px;
                width:fit-content;
            }
            .checkout-container {
         
               position: relative;
               
            
                width:fit-content;
                align-content: center;
            }
            .checkout-sections{
                font-weight: small;
         
            }
        .section{
                 width: 300px;         
                }
            .section-title {
                font-size: 18px;
            }
            .form-section {
              width:250px;
             }
            .form-group label {
                font-size: 14px;
            }

            input[type="text"], input[type="email"], input[type="tel"], textarea {
                font-size: 14px;
                padding: 8px;
            }

            .section .cart-items {
                padding:0px;
                width:0px
             
            }
            .cart-items th, .cart-items td {
                font-size: 12px;
                padding:7px;
            }
            .section .cart-items img{
              
                width:40px
             
            }

            .submit-button, .proceed-payment {
                font-size: 14px;
                padding: 8px 15px;
            }

            .prescription-upload {
                padding: 15px;
            }
        }

        /* For smaller mobile devices (max-width: 320px) */
        @media (max-width: 320px) {
            .body {
                font-size: 14px;
                width:fit-content;
            }
            .checkout-container {
         
               position: relative;
               
            
                width:fit-content;
                align-content: center;
            }
            .checkout-sections{
                font-weight: small;
         
            }
        .section{
                 width: 200px;         
                }
            .section-title {
                font-size: 18px;
            }
            .form-section {
              width:200px;
             }
            .form-group label {
                font-size: 14px;
            }
            .form-buttons{
                display: flex;
                flex-direction: column-reverse;
                gap: 5px;
            }
            .new-address-form {
                width: 150px;
            }
            input[type="text"], input[type="email"], input[type="tel"], textarea {
                font-size: 12px;
                padding: 8px;
            }
            .section .cart-items {
                padding:0px;
                width:40px
             
            }
           
            .section .cart-items img{
              
                width:50px
             
            }
            .cart-items th, .cart-items td {
                font-size: 9px;
                padding: 2px;
            }

            .submit-button, .proceed-payment {
                font-size: 10px;
                padding: 6px ;
            }

            .prescription-upload {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
        </div>

        <form id="checkout-form" enctype="multipart/form-data">
            <div class="checkout-sections">
                <div class="left-section">
                    <!-- User Information -->
                    <div class="section">
                        <h2 class="section-title">User Information</h2>
                        <div class="user-info">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span><?php echo htmlspecialchars($user_info['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span><?php echo htmlspecialchars($user_info['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span><?php echo htmlspecialchars($user_info['phone']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="section">
                        <h2 class="section-title">Delivery Address</h2>
                        <div class="delivery-info">
                            <!-- Show current address -->
                            <div class="current-address" id="current-address">
                                <h3>Delivery Address</h3>
                                <div class="info-item">
                                    <span class="info-label">Address:</span>
                                    <span id="delivery-address-display"><?php echo htmlspecialchars($current_address); ?></span>
                                </div>
                                <button type="button" class="edit-address-btn" onclick="showAddressForm()">Change Delivery Address</button>
                            </div>

                            <!-- New address form (changed from form to div) -->
                            <div class="new-address-form" id="new-address-form" style="display: none;">
                                <h3>New Delivery Address</h3>
                                <div id="delivery-address-form">
                                    <div class="form-group">
                                        <label for="street">Street Address <span class="required">*</span></label>
                                        <input type="text" id="street" name="street" >
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="city">City <span class="required">*</span></label>
                                        <input type="text" id="city" name="city" >
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="state">State <span class="required">*</span></label>
                                        <input type="text" id="state" name="state" >
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="pincode">Pincode <span class="required">*</span></label>
                                        <input type="text" id="pincode" name="pincode" pattern="[0-9]{6}" title="Please enter a valid 6-digit pincode" >
                                    </div>
                                    
                                    <div class="form-buttons">
                                        <button type="button" class="cancel-btn" onclick="hideAddressForm()">Cancel</button>
                                        <button type="button" class="save-btn" onclick="submitAddressForm()">Use This Address</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Upload -->
                    <div class="form-section">
                        <h2>Prescription Upload <span class="required">*</span></h2>
                        <div class="prescription-upload">
                            <input type="file" name="prescription" id="prescription" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <p>Upload your prescription (PDF, JPG, PNG)</p>
                        </div>
                    </div>

                    <!-- Add address validation status -->
                    <input type="hidden" id="address-valid" value="<?php echo $has_address ? '1' : '0'; ?>">
                </div>

                <div class="right-section">
                    <!-- Order Summary -->
                    <div class="section">
                        <h2 class="section-title">Order Summary</h2>
                        <table class="cart-items">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($cart_items as $item): 
                                    $subtotal = $item['quantity'] * $item['price'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><img class="product-image" src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>"></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td>₹<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="total-section">
                            <div>Total Amount: ₹<?php echo number_format($total, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-button">Place Order</button>

            <!-- Add order status container -->
            <div id="orderProcessing" style="display: none;">
                <h3>Order Status</h3>
                <div id="statusContainer">
                    <div class="status-message pending">
                        <i class="fas fa-spinner fa-pulse"></i>
                        Your order is being processed...
                    </div>
                    <div id="paymentSection" style="display: none;">
                        <p>Order approved! Proceed to payment:</p>
                        <button type="button" class="proceed-payment" id="proceedToPayment">Secure Payment Gateway</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Button removed -->
    </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Periodically check session status
        function checkSession() {
            fetch('check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.loggedIn) {
                        alert('Your session has expired. Please login again.');
                        window.location.href = 'login.html';
                    }
                })
                .catch(error => {
                    console.error('Session check failed:', error);
                });
        }

        // Check session every 5 minutes
        setInterval(checkSession, 300000);

        // Also check session before important actions
        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check session before proceeding
            try {
                const sessionResponse = await fetch('check_session.php');
                const sessionData = await sessionResponse.json();
                
                if (!sessionData.loggedIn) {
                    alert('Your session has expired. Please login again.');
                    window.location.href = 'login.html';
                    return;
                }

                // Get the prescription file
                const prescriptionFile = document.getElementById('prescription').files[0];
                if (!prescriptionFile) {
                    alert('Please upload a prescription file');
                    return;
                }

                // Validate file type
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(prescriptionFile.type)) {
                    alert('Please upload a valid file type (PDF, JPG, or PNG)');
                    return;
                }

                // Create form data
                const formData = new FormData();
                formData.append('prescription', prescriptionFile);

                const response = await fetch('process_order.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                
                if (data.success) {
                    // Show processing status
                    document.getElementById('orderProcessing').style.display = 'block';
                    document.getElementById('statusContainer').style.display = 'block';
                    document.getElementById('statusContainer').style.opacity = '1';

                    // Remove the "Place Order" button
                    const placeOrderButton = document.querySelector('.submit-button');
                    if (placeOrderButton) {
                        placeOrderButton.remove();
                    }

                    startOrderStatusCheck(data.order_id);
                } else {
                    alert(data.message || 'Failed to place order');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while placing the order');
            }
        });

        // Modified order status checking function
        function startOrderStatusCheck(orderId) {
            const statusCheck = setInterval(async () => {
                try {
                    const response = await fetch(`check_order_status.php?order_id=${orderId}`, {
                        credentials: 'include',
                        headers: {
                            'Cache-Control': 'no-cache'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'approved') {
                        clearInterval(statusCheck);
                        document.querySelector('.status-message').style.display = 'none';
                        document.getElementById('paymentSection').style.display = 'block';
                        document.getElementById('proceedToPayment').onclick = () => {
                            window.location.href = `payment.php?order_id=${orderId}`;
                        };
                    }
                    else if (data.status === 'rejected') {
                        clearInterval(statusCheck);
                        document.querySelector('.status-message').className = 'status-message rejected';
                        document.querySelector('.status-message').innerHTML = `
                            <i class="fas fa-times-circle"></i>
                            Order rejected: ${data.reason || 'Please upload a proper prescription'}
                        `;
                    }
                    
                } catch (error) {
                    console.error('Status check failed:', error);
                }
            }, 3000); // Check every 3 seconds
        }

        // Address form submission handler
        document.getElementById('delivery-address-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const sessionResponse = await fetch('check_session.php');
                const sessionData = await sessionResponse.json();
                
                if (!sessionData.loggedIn) {
                    alert('Your session has expired. Please login again.');
                    window.location.href = 'login.html';
                    return;
                }

                // Validate inputs
                const street = document.getElementById('street').value.trim();
                const city = document.getElementById('city').value.trim();
                const state = document.getElementById('state').value.trim();
                const pincode = document.getElementById('pincode').value.trim();

                // Basic validation
                if (!street || !city || !state || !pincode) {
                    throw new Error('All fields are required');
                }

                // Validate pincode format
                if (!/^\d{6}$/.test(pincode)) {
                    throw new Error('Please enter a valid 6-digit pincode');
                }

                // Format the complete address
                const fullAddress = `${street}, ${city}, ${state} - ${pincode}`;

                const response = await fetch('update_delivery_address.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        delivery_address: fullAddress
                    })
                });

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update address');
                }

                // Update the displayed address
                document.getElementById('delivery-address-display').textContent = fullAddress;
                // Hide the form
                hideAddressForm();
                // Show success message
                
                
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while updating the address');
            }
        });
    });

    function showAddressForm() {
        document.getElementById('current-address').style.display = 'none';
        document.getElementById('new-address-form').style.display = 'block';
    }

    function hideAddressForm() {
        document.getElementById('current-address').style.display = 'block';
        document.getElementById('new-address-form').style.display = 'none';
        // Reset form
        document.getElementById('delivery-address-form').reset();
    }

    function submitAddressForm() {
        const street = document.getElementById('street').value;
        const city = document.getElementById('city').value;
        const state = document.getElementById('state').value;
        const pincode = document.getElementById('pincode').value;
        
        // Validate inputs
        if (!street || !city || !state || !pincode) {
            alert('Please fill all required fields');
            return;
        }

        if (!/^\d{6}$/.test(pincode)) {
            alert('Please enter a valid 6-digit pincode');
            return;
        }

        const fullAddress = `${street}, ${city}, ${state} - ${pincode}`;
        
        // Send AJAX request to update address
        fetch('update_delivery_address.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                delivery_address: fullAddress
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update displayed address
                document.getElementById('delivery-address-display').textContent = data.address;
                document.getElementById('address-valid').value = '1';  // Mark address as valid
                hideAddressForm();
            } else {
                alert(data.message || 'Failed to update address');
            }
        })
        ;
    }

    document.getElementById('proceedToPayment').onclick = function() {
        // Create an order on the server
        fetch('create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error creating order: ' + data.error);
                return;
            }

            // Store our database order ID in session storage
            sessionStorage.setItem('currentOrderId', data.order_id);

            // Initialize Razorpay payment
            var options = {
                key: 'rzp_test_sPafHM8S92pTTJ', // Your Razorpay key
                amount: data.amount, // Amount in paise
                currency: 'INR',
                name: 'Your Company Name',
                description: 'Order Description',
                order_id: data.razorpay_order_id, // Use the Razorpay order ID
                handler: function (response) {
                    // Get the stored order ID
                    const dbOrderId = sessionStorage.getItem('currentOrderId');
                    
                    // Handle successful payment here
                    fetch('store_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: dbOrderId, // Use our database order ID
                            razorpay_order_id: data.razorpay_order_id,
                            payment_id: response.razorpay_payment_id,
                            amount: data.amount / 100, // Convert back to INR
                            currency: 'INR',
                            status: 'completed'
                        })
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            // Redirect to payment success page with our order ID
                            window.location.href = `payment_success.php?order_id=${dbOrderId}`;
                        } else {
                            alert('Failed to store payment details.');
                        }
                    });
                },
                prefill: {
                    name: 'Customer Name',
                    email: 'customer@example.com',
                    contact: '9999999999'
                },
                notes: {
                    order_id: data.order_id // Pass the database order ID in notes
                },
                theme: {
                    color: '#F37254'
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the order.');
        });
    };
    </script>
</body>
</html>
