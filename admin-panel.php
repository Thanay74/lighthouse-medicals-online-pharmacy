<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="adminstyles.css">
    <style>
        /* Add styles for the modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        /* Add these styles to match the Aadhaar document display */
        .doc-preview {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
        }

        .doc-actions {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .view-btn, .download-btn {
            padding: 5px 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .view-btn:hover, .download-btn:hover {
            background-color: #e0e0e0;
        }

        .pdf-icon, .doc-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .doc-cell {
            min-width: 200px;
        }

        img {
            border: 1px solid #ddd;
            padding: 3px;
            background: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">🌿 Pharma Admin</div>
        <div class="admin-info">
            <span>Welcome, Admin</span>
            <button class="logout-btn" onclick="handleLogout()">Logout</button>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <button class="toggle-btn">☰</button>
            <nav>
                <ul>
                    <li><a href="#" class="active">🏠 Dashboard</a></li>
                    <li><a href="#order">🛒 Order Management</a></li>
                    <li><a href="#payment" onclick="scrollToPayment()">💳 Payment Management</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content">
            <section class="table-section" id="order">
                <h2>Order Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User ID</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                            <th>Delivery Status</th>
                            <th>Prescription</th>
                        </tr>
                    </thead>
                    <tbody id="orderTableBody">
                        <!-- Orders will be loaded here dynamically -->
                    </tbody>
                </table>
            </section>

            <section class="table-section" id="payment">
                <h2>Payment Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Order ID</th>
                            <th>User ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $paymentQuery = "SELECT * FROM payments ORDER BY created_at DESC";
                        $paymentResult = $conn->query($paymentQuery);

                        if ($paymentResult->num_rows > 0) {
                            while ($payment = $paymentResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($payment['payment_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($payment['order_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($payment['user_id']) . "</td>";
                                echo "<td>₹" . htmlspecialchars($payment['amount']) . "</td>";
                                echo "<td>" . htmlspecialchars($payment['status']) . "</td>";
                                echo "<td>" . htmlspecialchars($payment['created_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No payments found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <footer>
        <p>© 2025 Pharma Companion. All rights reserved.</p>
    </footer>

    <!-- Confirmation Modal for Rejecting Orders -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Confirm Rejection</h2>
            <p>Are you sure you want to reject this order?</p>
            <div class="button-group">
                <button id="confirmRejectBtn">Yes, Reject</button>
                <button id="cancelRejectBtn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentOrderId; // Variable to store the current order ID

        // Show the rejection confirmation modal
        function rejectOrder(orderId) {
            currentOrderId = orderId; // Store the order ID to be rejected
            document.getElementById('rejectModal').style.display = 'flex'; // Show the modal
        }

        // Confirm rejection and call the PHP script
        document.getElementById('confirmRejectBtn').onclick = function() {
            fetch('admin_reject_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + currentOrderId
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload(); // Refresh the page to show changes
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the order.');
            });
            document.getElementById('rejectModal').style.display = 'none'; // Hide the modal
        };

        // Cancel rejection and hide the modal
        document.getElementById('cancelRejectBtn').onclick = function() {
            document.getElementById('rejectModal').style.display = 'none'; // Hide the modal
        };

        // Function to load orders (example)
        function loadOrders() {
            fetch('get_orders.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('orderTableBody');
                    tbody.innerHTML = '';
                    
                    data.forEach(order => {
                        const row = document.createElement('tr');
                        row.setAttribute('data-order-id', order.id);
                        
                        let prescriptionDisplay = 'No prescription';
                        if (order.prescription && order.prescription_type) {
                            const docType = order.prescription_type;
                            const docData = order.prescription;

                            if (docType.startsWith('image/')) {
                                prescriptionDisplay = `
                                    <div class="doc-preview">
                                        <img src="data:${docType};base64,${docData}" 
                                             alt="Prescription" 
                                             style="max-width: 150px; max-height: 150px; cursor: pointer;"
                                             onclick="window.open(this.src, '_blank')">
                                        <div class="doc-actions">
                                            <a href="data:${docType};base64,${docData}" 
                                               download="prescription"
                                               class="download-btn">
                                                📥 Download
                                            </a>
                                        </div>
                                    </div>`;
                            } else {
                                // Create a Blob URL for PDF
                                const byteCharacters = atob(docData);
                                const byteNumbers = new Array(byteCharacters.length);
                                for (let i = 0; i < byteCharacters.length; i++) {
                                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                                }
                                const byteArray = new Uint8Array(byteNumbers);
                                const blob = new Blob([byteArray], { type: docType });
                                const blobUrl = URL.createObjectURL(blob);

                                prescriptionDisplay = `
                                    <div class="doc-preview">
                                        <div class="pdf-icon">📄</div>
                                        <div class="doc-actions">
                                            <a href="${blobUrl}" 
                                               target="_blank" 
                                               class="view-btn">
                                                👁️ View
                                            </a>
                                            <a href="${blobUrl}" 
                                               download="prescription"
                                               class="download-btn">
                                                📥 Download
                                            </a>
                                        </div>
                                    </div>`;
                            }
                        }

                        row.innerHTML = `
                            <td>${order.id}</td>
                            <td>${order.user_name}</td>
                            <td>${order.total_amount}</td>
                            <td>${order.status}</td>
                            <td>
                                <button onclick='approveOrder(${order.id})'>Approve</button>
                                <button onclick='rejectOrder(${order.id})'>Reject</button>
                            </td>
                            <td class="doc-cell">${prescriptionDisplay}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading orders:', error));
        }

        // Call loadOrders when the page loads
        document.addEventListener('DOMContentLoaded', loadOrders);

        function approveOrder(orderId) {
            // Create an AJAX request to approve the order
            fetch('admin_approve_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Optionally, refresh the page or directly update the status on the page
                    location.reload(); // Simple way to refresh the page to show changes
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while approving the order.');
            });
        }

        function scrollToPayment() {
            const paymentSection = document.getElementById('payment');
            if (paymentSection) {
                paymentSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function trackDelivery(orderId) {
            // Open a modal or redirect to a tracking page
            window.location.href = 'track_delivery.php?order_id=' + orderId;
            
            // Alternatively, you can open a modal with tracking details
            // fetchTrackingDetails(orderId);
        }

        // Example function to fetch tracking details via AJAX
        function fetchTrackingDetails(orderId) {
            fetch('get_tracking_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display tracking details in a modal
                        showTrackingModal(data.trackingDetails);
                    } else {
                        alert('Failed to fetch tracking details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching tracking details.');
                });
        }

        function showTrackingModal(details) {
            // Create and display a modal with tracking details
            const modal = document.createElement('div');
            modal.className = 'tracking-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Delivery Tracking Details</h3>
                    <p>Status: ${details.status}</p>
                    <p>Last Update: ${details.lastUpdate}</p>
                    <p>Location: ${details.location}</p>
                    <button onclick="closeModal()">Close</button>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function closeModal() {
            const modal = document.querySelector('.tracking-modal');
            if (modal) {
                modal.remove();
            }
        }

        // Add event listeners to all navigation links
        document.querySelectorAll('.sidebar nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all links
                document.querySelectorAll('.sidebar nav a').forEach(l => l.classList.remove('active'));
                // Add active class to the clicked link
                this.classList.add('active');
            });
        });
    </script>
</body>
</html> 