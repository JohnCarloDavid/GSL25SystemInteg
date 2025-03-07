<?php
// Start the session
session_start();

// Include database connection file
include('db_connection.php');

// Check if the user is logged in and if they are an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
    header("Location: login.php");
    exit();
}

// Initialize variables
$selected_date = '';
$searchQuery = ''; // Variable for the search query

// Check if a date filter has been applied
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_date'])) {
    $selected_date = $_POST['selected_date'];
}

// Check if a search query has been entered
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

// Fetch orders from the database based on the selected date or search query
if ($selected_date && $searchQuery) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.order_date = ? AND o.customer_name LIKE ? 
            ORDER BY o.order_id DESC";  // Changed ASC to DESC
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param('ss', $selected_date, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($selected_date) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.order_date = ? ORDER BY o.order_id DESC";  // Changed ASC to DESC
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($searchQuery) {
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            WHERE o.customer_name LIKE ? ORDER BY o.order_id DESC";  // Changed ASC to DESC
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchQuery . "%";
    $stmt->bind_param('s', $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default query if no filters are applied
    $sql = "SELECT o.*, i.size, i.price FROM tb_orders o 
            JOIN tb_inventory i ON o.product_name = i.name 
            ORDER BY o.order_id DESC";  // Changed ASC to DESC
    $result = $conn->query($sql);
}
// Calculate the total number of orders
$total_orders = 0;
if ($result) {
    $total_orders = $result->num_rows;
}

// Initialize variables for total amount and total quantity
$total_amount = 0;
$total_quantity = 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - GSL25 Inventory Management System</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        /* Body and General Styling */
body {
    font-family: 'Poppins', sans-serif;
    display: flex;
    margin: 0;
    color: #2c3e50;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Sidebar Styling */
.sidebar {
    width: 260px;
    background: linear-gradient(145deg, #34495e, #2c3e50);
    color: #ecf0f1;
    padding: 30px 20px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebarHeader h2 {
    font-size: 1.8rem;
    font-weight: bold;
    text-align: center;
}

.sidebarNav ul {
    list-style: none;
    padding: 0;
}

.sidebarNav ul li {
    margin: 1.2rem 0;
}

.sidebarNav ul li a {
    text-decoration: none;
    color: #ecf0f1;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.sidebarNav ul li a:hover {
    background-color: #2980b9;
}

.sidebarNav ul li a i {
    margin-right: 15px;
}

/* Mobile Sidebar */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    font-size: 24px;
    background: none;
    border: none;
    color:black
    cursor: pointer;
    z-index: 1100;
}

@media (max-width: 768px) {
    .sidebar {
        width: 220px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }
}

/* Main Content */
.mainContent {
    margin-left: 280px;
    padding: 30px;
    width: calc(100% - 280px);
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.mainHeader h1 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.ordersTable {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 1rem;
}

.ordersTable th,
.ordersTable td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
    word-wrap: break-word;
}

.ordersTable th {
    background-color: #3498db;
    color: #ffffff;
}

.ordersTable tr:nth-child(even) {
    background-color: #f2f2f2;
}

.button {
    background-color: #ffffff;
    color: #c0392b;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 1rem;
    text-align: center;
    text-decoration: none;
    border: 1px solid #3498db;
    transition: background-color 0.3s ease, color 0.3s ease;
    display: inline-block;
    cursor: pointer;
}

.button:hover {
    background-color: #3498db;
    color: #ffffff;
}

.totalOrders {
    margin-top: 20px;
    font-size: 1.2rem;
    text-align: center;
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .mainContent {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .mainContent {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }

    .mainHeader h1 {
        font-size: 1.8rem;
    }

    .ordersTable th,
    .ordersTable td {
        padding: 10px;
        font-size: 0.9rem;
    }

    .button {
        font-size: 0.9rem;
        padding: 5px 10px;
    }
}

/* iPhone XR (414px width) */
@media (max-width: 480px) {
    .mainContent {
        padding: 10px;
    }

    .mainHeader h1 {
        font-size: 1.6rem;
    }

    .ordersTable {
        font-size: 0.85rem;
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    .ordersTable th,
    .ordersTable td {
        padding: 8px;
    }

    .button {
        font-size: 0.85rem;
        padding: 5px 8px;
    }
}

/* General Styles */
.mainHeader {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #ffffff;
    box-shadow: 0 4px 2px -2px gray;
    flex-wrap: nowrap; /* Prevents wrapping */
    overflow: hidden; /* Ensures no unexpected wrapping */
}

/* Header Actions (Buttons) */
.headerActions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap; /* Prevents wrapping */
}

.headerActions .button {
    padding: 6px 10px;
    font-size: 13px;
    white-space: nowrap; /* Prevents text wrapping */
    background-color: #4CAF50;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.headerActions .button:hover {
    background-color: #45a049;
}

/* Search Form */
.search-form {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: nowrap; /* Prevents wrapping */
}

.search-input {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    max-width: 180px; /* Adjusted for mobile */
    font-size: 13px;
    outline: none;
}

.search-button, .clear-button {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-button {
    background-color: #4CAF50;
    color: white;
}

.search-button:hover {
    background-color: #45a049;
}

.clear-button {
    background-color: #e74c3c;
    color: white;
}

.clear-button:hover {
    background-color: #c0392b;
}

/* Responsive Fix for Small Screens */
@media (max-width: 768px) {
    .mainHeader {
        padding: 8px;
        flex-wrap: nowrap;
        overflow: hidden;
        display: flex;
        gap: 5px;
    }

    .headerActions {
        gap: 5px;
    }

    .search-input {
        max-width: 150px;
    }

    .search-button, .clear-button {
        padding: 5px 10px;
        font-size: 12px;
    }
}

/* iPhone XR (414px width) */
@media (max-width: 414px) {
    .mainHeader {
        padding: 5px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        flex-wrap: nowrap;
    }

    .headerActions {
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 5px;
    }

    .search-form {
        flex-wrap: nowrap;
        gap: 5px;
    }

    .search-input {
        max-width: 130px;
    }

    .search-button, .clear-button {
        padding: 4px 8px;
        font-size: 12px;
    }
}


/* Responsive Styles */
@media (max-width: 768px) {
    .mainHeader {
        flex-direction: column;
        align-items: center;
        padding: 10px;
    }

    .headerActions {
        justify-content: center;
        width: 100%;
    }

    .search-form {
        flex-direction: column;
        width: 100%;
        align-items: center;
    }
}

/* iPhone XR (414px width) */
@media (max-width: 414px) {
    .headerActions {
        flex-direction: column;
        width: 100%;
        align-items: center;
        gap: 5px;
    }

    .search-input {
        width: 100%;
    }

    .search-button, .clear-button {
        width: 100%;
        text-align: center;
    }
}

/* Customer Table */
.customer-table {
    border: 1px solid #ddd;
    margin-bottom: 20px;
    padding: 15px;
    width: 100%;
    overflow-x: auto;
}

.customer-table th {
    background-color: #2980b9;
    color: #ffffff;
    padding: 10px;
    text-align: center;
    font-size: 14px;
}

.customer-table td {
    padding: 10px;
    font-size: 14px;
    text-align: center;
    word-wrap: break-word;
}

.customer-name {
    font-size: 1.3rem;
    font-weight: bold;
    text-align: left;
    margin-bottom: 10px;
}

.order-row td {
    text-align: center;
    font-size: 14px;
}

.order-date-row {
    text-align: left;
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
}

/* Sidebar Logout Button */
.logout-form {
    margin-top: auto;
    display: flex;
    justify-content: center;
    width: 100%;
}

.logout-button {
            background-color: #e74c3c;
            color: #ffffff; 
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: background-color 0.3s;
            margin-top: 10px; 
        }

        .logout-button i {
            margin-right: 8px; 
            font-size: 1.2rem; 
        }

        .logout-button:hover {
            background-color: #c0392b; 
        }

/* Responsive Styles */
@media (max-width: 768px) {
    .customer-table {
        width: 100%;
        overflow-x: auto;
    }

    .customer-name {
        font-size: 1.2rem;
        text-align: center;
    }

    .logout-button {
        width: 100%;
    }
}

@media (max-width: 414px) {
    .customer-table {
        padding: 10px;
    }

    .customer-table th, .customer-table td {
        font-size: 12px;
        padding: 8px;
    }

    .customer-name {
        font-size: 1rem;
        text-align: center;
    }

    .order-date-row {
        font-size: 12px;
    }

    .logout-form {
        justify-content: center;
        width: 100%;
    }

    .logout-button {
        width: 100%;
        padding: 10px;
        font-size: 14px;
    }
}


    </style>
</head>
<body>
<!-- Burger Menu -->
<div class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebarHeader">
        <h2>GSL25 IMS</h2>
    </div>
    <nav class="sidebarNav">
        <ul>
                <li><a href="dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                <li><a href="inventory.php"><i class="fa fa-box"></i> Inventory</a></li>
                <li><a href="orders.php"><i class="fas fa-cash-register"></i> Point of Sale (POS)</a></li>
                <li><a href="reports.php"><i class="fa fa-chart-line"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
        </ul>
    </nav>
    <form action="logout.php" method="POST" class="logout-form">
    <button type="submit" class="logout-button">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</form>
</div>

    <div class="mainContent">
    <header class="mainHeader">
    <div class="headerActions">
        <a href="add-order.php" class="button"><i class="fa fa-plus"></i> Add New Order</a>
        <a href="recently-deleted.php" class="button"><i class="fa fa-undo"></i> Recently Deleted</a>
        <!-- Search Bar -->
        <form action="orders.php" method="GET" class="search-form">
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by customer name" class="search-input" />
            <button type="submit" class="search-button"><i class="fa fa-search"></i></button>
            <!-- Clear Search Button -->
            <button type="button" class="clear-button" onclick="clearSearch()">Clear Search</button>
        </form>
    </div>
</header>


<!-- Orders Section -->
<section class="ordersSection">
    <?php 
    // Initialize a variable to store the last customer name
    $lastCustomerName = "";
    $customerTotalAmount = 0; // Variable to store total amount per customer

    // Display the fetched orders
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { 
            $total_quantity += $row['quantity'];
            $total_price = $row['quantity'] * $row['price']; // Calculate total price for the order
            $total_amount += $total_price; // Add to the total amount

            if ($row['customer_name'] != $lastCustomerName) {
                if ($lastCustomerName != "") {
                    // Display the total amount for the previous customer
                    echo '<tr><td colspan="6"><strong>Total Amount: ' . number_format($customerTotalAmount, 2) . '</strong></td></tr>';
                    echo '<tr><td colspan="6">&nbsp;</td></tr>'; // Add space between customers
                }

                // Reset the customer total for the new customer
                $customerTotalAmount = 0;
                $lastCustomerName = $row['customer_name'];

                // Start a new table for each customer with the class "light-blue-table"
                echo '<table class="ordersTable light-blue-table">';
                echo '<thead>
                        <tr>
                            <th colspan="6" class="customer-info">
                                <strong>Customer: ' . htmlspecialchars($row['customer_name']) . '</strong><br>
                                <strong>Order Date: ' . htmlspecialchars($row['order_date']) . '</strong>
                            </th>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <th>Size</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>';
                echo '<tbody>';
            
            }

            // Add to the customer's total amount
            $customerTotalAmount += $total_price;

            // Display Order Details Row
            echo '<tr class="order-row">
                    <td>' . htmlspecialchars($row['product_name']) . '</td>
                    <td>' . htmlspecialchars($row['size']) . '</td>
                    <td>' . htmlspecialchars($row['quantity']) . '</td>
                    <td>' . htmlspecialchars(number_format($row['price'], 2)) . '</td>
                    <td>' . htmlspecialchars(number_format($total_price, 2)) . '</td>
                    <td>
                        <a href="edit-order.php?id=' . htmlspecialchars($row['order_id']) . '" class="button"><i class="fa fa-edit"></i></a>
                        <a href="delete-order.php?id=' . htmlspecialchars($row['order_id']) . '" class="button" onclick="return confirm(\'Are you sure you want to delete this order?\');"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>';
        }

        // Display the total amount for the last customer
        echo '<tr><td colspan="6"><strong>Total Amount: ' . number_format($customerTotalAmount, 2) . '</strong></td></tr>';
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No orders found.</p>';
    }
    ?>
</section>
    </div>

    <script>
        // Function to clear search input
        function clearSearch() {
            document.querySelector('.search-input').value = '';
            document.querySelector('.search-form').submit();
        }
    </script>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    function toggleDropdown(id) {
        document.getElementById(id).classList.toggle('active');
    }
</script>
</body>
</html>
